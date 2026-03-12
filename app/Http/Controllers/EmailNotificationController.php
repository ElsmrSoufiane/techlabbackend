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
                    // Replace placeholders in the raw HTML body
                    $html = $this->replacePlaceholders($request->body, $customer);
                    
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
                    // Replace placeholders in the raw HTML body
                    $html = $this->replacePlaceholders($request->body, $customer);
                    
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
                    // Replace placeholders in the raw HTML body
                    $html = $this->replacePlaceholders($request->body, $customer);
                    
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

            // Replace placeholders in the raw HTML body
            $html = $this->replacePlaceholders($request->body, $dummyCustomer);
            
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
     * Replace placeholders in HTML content
     */
    private function replacePlaceholders($html, $customer)
    {
        $replacements = [
            '{{customer_name}}' => $customer->name,
            '{{customer_email}}' => $customer->email,
            '{{date}}' => date('d/m/Y'),
            '{{year}}' => date('Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $html);
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
                'body' => $this->getFullWelcomeTemplate()
            ],
            [
                'id' => 'offer',
                'name' => 'Offre Spéciale',
                'subject' => 'Offre spéciale pour vous !',
                'body' => $this->getFullOfferTemplate()
            ],
            [
                'id' => 'newsletter',
                'name' => 'Newsletter',
                'subject' => 'Newsletter TECLAB',
                'body' => $this->getFullNewsletterTemplate()
            ],
            [
                'id' => 'promotion',
                'name' => 'Promotion',
                'subject' => 'Profitez de nos promotions !',
                'body' => $this->getFullPromotionTemplate()
            ]
        ];

        return $this->successResponse($templates);
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
     * Full HTML templates (now returned directly)
     */
    private function getFullWelcomeTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue chez TECLAB</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 32px; }
        .content { padding: 40px 30px; }
        .footer { background-color: #f8f9fa; padding: 30px 20px; text-align: center; color: #666; font-size: 14px; }
        .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>TECLAB</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{customer_name}},</h2>
            <p>Nous sommes ravis de vous accueillir chez TECLAB, votre partenaire de confiance pour l\'équipement de laboratoire au Maroc.</p>
            
            <h3>Votre offre de bienvenue :</h3>
            <p>Profitez de <strong>-10%</strong> sur votre première commande avec le code : <strong style="background: #f0f0f0; padding: 5px 10px;">BIENVENUE10</strong></p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="#" class="button">Découvrir nos produits</a>
            </div>
            
            <p>Notre équipe reste à votre disposition pour toute question.</p>
            <p>À très bientôt !</p>
        </div>
        <div class="footer">
            <p>TECLAB - Laboratoire Maroc</p>
            <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
            <p>contact@teclab.ma | www.teclab.ma</p>
            <p>© {{year}} TECLAB. Tous droits réservés.</p>
            <p><small>Si vous ne souhaitez plus recevoir nos emails, <a href="#">cliquez ici</a>.</small></p>
        </div>
    </div>
</body>
</html>';
    }

    private function getFullOfferTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offre spéciale TECLAB</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 40px 20px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 32px; }
        .header .badge { background: #ffd700; color: #333; padding: 5px 15px; border-radius: 20px; display: inline-block; margin-top: 10px; }
        .content { padding: 40px 30px; }
        .footer { background-color: #f8f9fa; padding: 30px 20px; text-align: center; color: #666; font-size: 14px; }
        .offer-box { background: #fff3cd; border: 2px solid #ffd700; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
        .offer-code { font-size: 24px; font-weight: bold; color: #f5576c; letter-spacing: 2px; }
        .button { display: inline-block; padding: 12px 30px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; text-decoration: none; border-radius: 25px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OFFRE SPÉCIALE</h1>
            <div class="badge">-20%</div>
        </div>
        <div class="content">
            <h2>Bonjour {{customer_name}},</h2>
            
            <div class="offer-box">
                <p style="font-size: 18px; margin-bottom: 10px;">Profitez de <strong>20% de réduction</strong></p>
                <p>sur tous nos produits jusqu\'à la fin du mois !</p>
                <p class="offer-code">PROMO20</p>
                <p><small>*Offre valable sur tout le site</small></p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="#" class="button">Profiter de l\'offre</a>
            </div>
            
            <p>Ne manquez pas cette occasion exceptionnelle !</p>
            <p>L\'équipe TECLAB</p>
        </div>
        <div class="footer">
            <p>TECLAB - Laboratoire Maroc</p>
            <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
            <p>© {{year}} TECLAB. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>';
    }

    private function getFullNewsletterTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter TECLAB</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); padding: 40px 20px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 32px; }
        .content { padding: 40px 30px; }
        .news-item { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .news-item h3 { color: #43e97b; margin-bottom: 10px; }
        .footer { background-color: #f8f9fa; padding: 30px 20px; text-align: center; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📰 Newsletter TECLAB</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{customer_name}},</h2>
            <p>Découvrez les dernières actualités de TECLAB !</p>
            
            <div class="news-item">
                <h3>🔬 Nouveaux microscopes numériques</h3>
                <p>Découvrez notre nouvelle gamme de microscopes avec caméra intégrée et analyse d\'images.</p>
            </div>
            
            <div class="news-item">
                <h3>⚗️ Promotions sur les consommables</h3>
                <p>-15% sur tous les consommables de laboratoire jusqu\'au 30 du mois.</p>
            </div>
            
            <div class="news-item">
                <h3>💡 Conseil d\'expert</h3>
                <p>Comment choisir votre équipement de laboratoire ? Guide complet pour faire le bon choix.</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="#" style="color: #43e97b;">Voir toutes les actualités →</a>
            </div>
        </div>
        <div class="footer">
            <p>TECLAB - Laboratoire Maroc</p>
            <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
            <p>© {{year}} TECLAB. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>';
    }

    private function getFullPromotionTemplate()
    {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotion Flash TECLAB</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #ff0844 0%, #ffb199 100%); padding: 40px 20px; text-align: center; }
        .header h1 { color: white; margin: 0; font-size: 32px; }
        .flash-badge { background: #ffd700; color: #333; padding: 10px 20px; border-radius: 30px; display: inline-block; font-size: 24px; font-weight: bold; margin-top: 10px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); } 100% { transform: scale(1); } }
        .content { padding: 40px 30px; }
        .countdown { background: #333; color: white; padding: 20px; text-align: center; border-radius: 10px; margin: 20px 0; }
        .countdown .hours { font-size: 36px; font-weight: bold; color: #ff0844; }
        .footer { background-color: #f8f9fa; padding: 30px 20px; text-align: center; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ PROMOTION FLASH ⚡</h1>
            <div class="flash-badge">-30%</div>
        </div>
        <div class="content">
            <h2>Bonjour {{customer_name}},</h2>
            
            <div class="countdown">
                <p>Offre valable uniquement aujourd\'hui !</p>
                <div class="hours">48h</div>
                <p>Il vous reste <strong>48 heures</strong> pour profiter de cette offre exceptionnelle !</p>
            </div>
            
            <p style="font-size: 18px; text-align: center;"><strong>-30% sur une sélection de produits</strong></p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="#" style="display: inline-block; padding: 15px 40px; background: #ff0844; color: white; text-decoration: none; border-radius: 30px; font-weight: bold; font-size: 18px;">Je profite de l\'offre</a>
            </div>
            
            <p style="text-align: center; color: #666;"><small>Ne manquez pas cette occasion unique !</small></p>
        </div>
        <div class="footer">
            <p>TECLAB - Laboratoire Maroc</p>
            <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
            <p>© {{year}} TECLAB. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>';
    }
} 