<?php
// app/Http/Controllers/EmailNotificationController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;
use App\Models\Order;

class EmailNotificationController extends Controller
{
    private function configureGmail()
    {
        $gmailUsername = 'eemssoufiane@gmail.com';
        $gmailPassword = 'hmjdcatkbgledfhl';
        $fromName = 'TECLAB - Laboratoire Maroc';
        
        Config::set('mail.default', 'smtp');

        Config::set('mail.mailers.smtp', [
            'transport' => 'smtp',
            'host' => 'smtp.gmail.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => $gmailUsername,
            'password' => $gmailPassword,
            'timeout' => 30,
        ]);

        Config::set('mail.from', [
            'address' => $gmailUsername,
            'name' => $fromName,
        ]);

        app('mail.manager')->forgetMailers();
    }

    private function authorizeAdmin(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'admin') {
            abort(403, 'Accès non autorisé');
        }
    }

    private function successResponse($data, $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data
        ], $code);
    }

    private function errorResponse($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'error' => $message
        ], $code);
    }

    /**
     * Send email notification to all customers
     */
    public function sendToAllCustomers(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'nullable|in:notification,offer,newsletter',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $this->configureGmail();

            $customers = Customer::where('role', 'customer')->get();
            $sentCount = 0;
            $failedEmails = [];

            foreach ($customers as $customer) {
                try {
                    $html = $this->buildEmailHtml($request, $customer);
                    
                    Mail::html($html, function ($message) use ($customer, $request) {
                        $message->to($customer->email)
                                ->subject($request->subject);
                    });

                    $sentCount++;
                    
                    Log::info('Email sent to customer', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'subject' => $request->subject
                    ]);

                } catch (\Exception $e) {
                    $failedEmails[] = $customer->email;
                    Log::error('Failed to send email to ' . $customer->email . ': ' . $e->getMessage());
                }
            }

            // Log the campaign
            $this->logEmailCampaign($request, 'all', $sentCount, count($failedEmails));

            return $this->successResponse([
                'message' => 'Emails envoyés avec succès',
                'stats' => [
                    'total_customers' => $customers->count(),
                    'sent_successfully' => $sentCount,
                    'failed' => count($failedEmails),
                    'failed_emails' => $failedEmails
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk email error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi des emails: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send email to selected customers
     */
    public function sendToSelectedCustomers(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'exists:customers,id',
            'type' => 'nullable|in:notification,offer,newsletter',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $this->configureGmail();

            $customers = Customer::whereIn('id', $request->customer_ids)->get();
            $sentCount = 0;
            $failedEmails = [];

            foreach ($customers as $customer) {
                try {
                    $html = $this->buildEmailHtml($request, $customer);
                    
                    Mail::html($html, function ($message) use ($customer, $request) {
                        $message->to($customer->email)
                                ->subject($request->subject);
                    });

                    $sentCount++;
                    
                    Log::info('Email sent to selected customer', [
                        'customer_id' => $customer->id,
                        'email' => $customer->email
                    ]);

                } catch (\Exception $e) {
                    $failedEmails[] = $customer->email;
                    Log::error('Failed to send email to ' . $customer->email . ': ' . $e->getMessage());
                }
            }

            // Log the campaign
            $this->logEmailCampaign($request, 'selected', $sentCount, count($failedEmails));

            return $this->successResponse([
                'message' => 'Emails envoyés avec succès',
                'stats' => [
                    'total_selected' => $customers->count(),
                    'sent_successfully' => $sentCount,
                    'failed' => count($failedEmails),
                    'failed_emails' => $failedEmails
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Selected emails error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi des emails: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send email to customers who have made purchases
     */
    public function sendToActiveCustomers(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'min_orders' => 'nullable|integer|min:1',
            'type' => 'nullable|in:notification,offer,newsletter',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $this->configureGmail();

            $minOrders = $request->min_orders ?? 1;
            
            $customers = Customer::where('role', 'customer')
                ->whereHas('orders', function($query) use ($minOrders) {
                    $query->select(DB::raw('count(*)'))
                          ->havingRaw('count(*) >= ?', [$minOrders]);
                })
                ->get();

            if ($customers->isEmpty()) {
                return $this->errorResponse('Aucun client actif trouvé', 404);
            }

            $sentCount = 0;
            $failedEmails = [];

            foreach ($customers as $customer) {
                try {
                    $html = $this->buildEmailHtml($request, $customer);
                    
                    Mail::html($html, function ($message) use ($customer, $request) {
                        $message->to($customer->email)
                                ->subject($request->subject);
                    });

                    $sentCount++;

                } catch (\Exception $e) {
                    $failedEmails[] = $customer->email;
                    Log::error('Failed to send email to ' . $customer->email . ': ' . $e->getMessage());
                }
            }

            // Log the campaign
            $this->logEmailCampaign($request, 'active', $sentCount, count($failedEmails));

            return $this->successResponse([
                'message' => 'Emails envoyés aux clients actifs',
                'stats' => [
                    'total_active' => $customers->count(),
                    'sent_successfully' => $sentCount,
                    'failed' => count($failedEmails),
                    'min_orders_required' => $minOrders
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Active customers email error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi des emails: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Send test email
     */
    public function sendTestEmail(Request $request)
    {
        $this->authorizeAdmin($request);

        $validator = validator($request->all(), [
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'test_email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        try {
            $this->configureGmail();

            // Create a dummy customer for the test email
            $dummyCustomer = (object) [
                'name' => 'Test User',
                'email' => $request->test_email
            ];

            $html = $this->buildEmailHtml($request, $dummyCustomer);
            
            Mail::html($html, function ($message) use ($request) {
                $message->to($request->test_email)
                        ->subject('[TEST] ' . $request->subject);
            });

            return $this->successResponse([
                'message' => 'Email de test envoyé avec succès à ' . $request->test_email
            ]);

        } catch (\Exception $e) {
            Log::error('Test email error: ' . $e->getMessage());
            return $this->errorResponse('Erreur lors de l\'envoi de l\'email de test: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get email templates
     */
    public function getEmailTemplates()
    {
        $templates = [
            [
                'id' => 'welcome',
                'name' => 'Bienvenue',
                'subject' => 'Bienvenue chez TECLAB !',
                'body' => $this->getWelcomeTemplate()
            ],
            [
                'id' => 'offer',
                'name' => 'Offre Spéciale',
                'subject' => 'Offre spéciale pour vous !',
                'body' => $this->getOfferTemplate()
            ],
            [
                'id' => 'newsletter',
                'name' => 'Newsletter',
                'subject' => 'Newsletter TECLAB',
                'body' => $this->getNewsletterTemplate()
            ],
            [
                'id' => 'promotion',
                'name' => 'Promotion',
                'subject' => 'Profitez de nos promotions !',
                'body' => $this->getPromotionTemplate()
            ]
        ];

        return $this->successResponse($templates);
    }

    /**
     * Build email HTML
     */
    private function buildEmailHtml($request, $customer)
    {
        $type = $request->type ?? 'notification';
        $body = $request->body;
        
        // Replace placeholders
        $body = str_replace('{{customer_name}}', $customer->name, $body);
        $body = str_replace('{{customer_email}}', $customer->email, $body);
        $body = str_replace('{{date}}', date('d/m/Y'), $body);
        $body = str_replace('{{year}}', date('Y'), $body);

        $typeColor = '#6d9eeb'; // Default blue
        $typeIcon = '📧';
        
        switch ($type) {
            case 'offer':
                $typeColor = '#e67e22'; // Orange
                $typeIcon = '🎉';
                break;
            case 'newsletter':
                $typeColor = '#27ae60'; // Green
                $typeIcon = '📰';
                break;
        }

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$request->subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: {$typeColor}; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
                .button { display: inline-block; background: {$typeColor}; color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; margin: 20px 0; }
                .badge { background: {$typeColor}; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; display: inline-block; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>{$typeIcon} TECLAB</h1>
            </div>
            <div class='content'>
                <div class='badge'>{$typeIcon} " . ucfirst($type) . "</div>
                <h2>Bonjour {$customer->name} !</h2>
                <div>
                    {$body}
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000' class='button'>Visiter notre site</a>
                </div>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " TECLAB. Tous droits réservés.</p>
                <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
                <p>
                    <small>Si vous ne souhaitez plus recevoir nos emails, <a href='#'>cliquez ici</a>.</small>
                </p>
            </div>
        </body>
        </html>";
    }

    /**
     * Log email campaign
     */
    private function logEmailCampaign($request, $target, $sentCount, $failedCount)
    {
        Log::info('Email campaign sent', [
            'target' => $target,
            'subject' => $request->subject,
            'type' => $request->type ?? 'notification',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'sent_by' => auth()->user()->id ?? 'system',
            'timestamp' => now()
        ]);
    }

    /**
     * Template methods
     */
    private function getWelcomeTemplate()
    {
        return "<p>Nous sommes ravis de vous accueillir chez TECLAB !</p>
                <p>Découvrez notre large gamme de produits de qualité.</p>
                <p>En bonus de bienvenue, profitez de -10% sur votre première commande avec le code <strong>BIENVENUE10</strong>.</p>";
    }

    private function getOfferTemplate()
    {
        return "<p>Nous avons une offre spéciale pour vous !</p>
                <p>Profitez de -20% sur tous nos produits jusqu'à la fin du mois.</p>
                <p>Utilisez le code <strong>PROMO20</strong> lors de votre commande.</p>";
    }

    private function getNewsletterTemplate()
    {
        return "<p>Découvrez les dernières nouveautés et actualités de TECLAB.</p>
                <p>Nouveaux produits, promotions exclusives et conseils d'experts.</p>";
    }

    private function getPromotionTemplate()
    {
        return "<p>Promotion flash ! ⚡</p>
                <p>-30% sur une sélection de produits pour 48h seulement.</p>
                <p>Ne manquez pas cette occasion unique !</p>";
    }
}