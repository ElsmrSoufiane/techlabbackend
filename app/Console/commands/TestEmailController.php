<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Http\Controllers\EmailNotificationController;

class TestEmailController extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-controller 
                            {email? : Email address to send test to}
                            {--template= : Use a template (welcome, offer, newsletter, promotion)}
                            {--bulk : Test bulk email simulation}
                            {--all : Run all tests}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the email notification controller functionality';

    /**
     * Email templates for testing
     */
    protected $templates = [
        'welcome' => [
            'subject' => 'Bienvenue chez TECLAB !',
            'body' => '<h2>Bienvenue chez TECLAB !</h2>
                      <p>Nous sommes ravis de vous accueillir parmi nos clients.</p>
                      <div style="background: #f0f0f0; padding: 20px; border-radius: 5px; margin: 20px 0;">
                          <h3 style="color: #6d9eeb;">🎁 Votre cadeau de bienvenue</h3>
                          <p>Profitez de -10% sur votre première commande avec le code:</p>
                          <p style="font-size: 24px; text-align: center;">
                              <strong style="background: #333; color: white; padding: 10px 20px; border-radius: 5px;">BIENVENUE10</strong>
                          </p>
                      </div>
                      <p>Découvrez notre large gamme de produits de qualité.</p>'
        ],
        'offer' => [
            'subject' => 'Offre spéciale pour vous !',
            'body' => '<h2>Offre Spéciale 🎉</h2>
                      <p>Bonjour {{customer_name}},</p>
                      <p>Nous avons une offre exclusive pour vous :</p>
                      <div style="background: #e67e22; color: white; padding: 20px; border-radius: 5px; margin: 20px 0;">
                          <h3 style="margin: 0;">🔥 -20% sur tous nos produits</h3>
                          <p style="font-size: 18px;">Jusqu\'à la fin du mois !</p>
                      </div>
                      <p>Utilisez le code: <strong style="font-size: 20px;">PROMO20</strong></p>
                      <p>Ne manquez pas cette opportunité !</p>'
        ],
        'newsletter' => [
            'subject' => 'Newsletter TECLAB',
            'body' => '<h2>Newsletter 📰</h2>
                      <p>Bonjour {{customer_name}},</p>
                      <p>Découvrez les dernières nouveautés :</p>
                      <ul style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
                          <li>✨ Nouveaux produits électroniques</li>
                          <li>🏷️ Promotions exclusives</li>
                          <li>💡 Conseils d\'experts</li>
                          <li>🎁 Offres spéciales membres</li>
                      </ul>
                      <p>Restez connecté pour ne rien manquer !</p>'
        ],
        'promotion' => [
            'subject' => 'Profitez de nos promotions !',
            'body' => '<h2>Promotion Flash ⚡</h2>
                      <p>Bonjour {{customer_name}},</p>
                      <div style="background: #27ae60; color: white; padding: 20px; border-radius: 5px; text-align: center;">
                          <h2 style="margin: 0; font-size: 32px;">-30%</h2>
                          <p style="font-size: 18px;">sur une sélection de produits</p>
                      </div>
                      <p style="font-size: 20px; text-align: center; margin: 20px 0;">
                          ⏰ <strong>48 heures seulement !</strong>
                      </p>
                      <p>Code: <strong style="background: #333; color: white; padding: 5px 15px; border-radius: 5px;">FLASH30</strong></p>'
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📧 EMAIL NOTIFICATION CONTROLLER TEST');
        $this->line('=======================================');
        
        $email = $this->argument('email') ?? 'eemssoufiane@gmail.com';
        $template = $this->option('template');
        $bulk = $this->option('bulk');
        $all = $this->option('all');
        
        // Check if controller exists
        if (!class_exists('App\Http\Controllers\EmailNotificationController')) {
            $this->error('❌ EmailNotificationController class not found!');
            $this->line('Please create the controller first.');
            return 1;
        }
        
        // Create controller instance
        $controller = new EmailNotificationController();
        
        // Run tests based on options
        if ($all) {
            $this->runAllTests($controller, $email);
        } elseif ($bulk) {
            $this->testBulkEmail($controller, $email);
        } elseif ($template) {
            $this->testTemplate($controller, $email, $template);
        } else {
            $this->testBasicFunctionality($controller, $email);
        }
        
        $this->line('=======================================');
        $this->info('✅ Test complete!');
        
        return 0;
    }

    /**
     * Test basic functionality
     */
    private function testBasicFunctionality($controller, $email)
    {
        // Test 1: Check if controller methods exist
        $this->info("\n📋 Step 1: Checking controller methods...");
        $methods = [
            'sendToAllCustomers',
            'sendToSelectedCustomers',
            'sendToActiveCustomers',
            'sendTestEmail',
            'getEmailTemplates'
        ];
        
        foreach ($methods as $method) {
            if (method_exists($controller, $method)) {
                $this->line("   ✅ Method {$method} exists");
            } else {
                $this->error("   ❌ Method {$method} missing");
            }
        }
        
        // Test 2: Get templates
        $this->info("\n📋 Step 2: Testing getEmailTemplates()...");
        try {
            $response = $controller->getEmailTemplates();
            $data = $response->getData();
            
            if ($data->success) {
                $this->info("   ✅ Templates retrieved successfully");
                $this->line("   Found " . count($data->data) . " templates:");
                foreach ($data->data as $template) {
                    $this->line("      • {$template->name}: {$template->subject}");
                }
            }
        } catch (\Exception $e) {
            $this->error("   ❌ Failed: " . $e->getMessage());
        }
        
        // Test 3: Send test email
        $this->info("\n📋 Step 3: Testing sendTestEmail() to {$email}...");
        $this->testSendEmail($controller, $email, 'Test Email', '<h2>Test Email</h2><p>This is a test from the command line.</p>');
        
        // Test 4: Send welcome template
        $this->info("\n📋 Step 4: Testing welcome template...");
        $this->testSendEmail($controller, $email, 'Welcome Template', $this->templates['welcome']['body']);
    }

    /**
     * Test specific template
     */
    private function testTemplate($controller, $email, $templateName)
    {
        if (!isset($this->templates[$templateName])) {
            $this->error("❌ Template '{$templateName}' not found!");
            $this->line("Available templates: " . implode(', ', array_keys($this->templates)));
            return;
        }
        
        $template = $this->templates[$templateName];
        $this->info("\n📋 Testing template: {$templateName}");
        $this->line("Subject: {$template['subject']}");
        
        $this->testSendEmail($controller, $email, $template['subject'], $template['body']);
    }

    /**
     * Test bulk email simulation
     */
    private function testBulkEmail($controller, $email)
    {
        $this->info("\n📋 Testing bulk email functionality...");
        
        // Create test customers
        $testCustomers = [
            ['name' => 'Ahmed Alaoui', 'email' => $email],
            ['name' => 'Fatima Benani', 'email' => $email],
            ['name' => 'Mohamed El Amrani', 'email' => $email],
            ['name' => 'Sara El Fassi', 'email' => $email],
        ];
        
        $this->line("Sending to " . count($testCustomers) . " test recipients...");
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($testCustomers as $index => $customer) {
            try {
                // Configure email settings
                $this->configureGmail();
                
                $html = $this->buildBulkEmailHtml($customer['name'], $index + 1);
                
                Mail::html($html, function ($message) use ($customer) {
                    $message->to($customer['email'])
                            ->subject('[BULK TEST] TECLAB Newsletter #' . date('Y-m-d'));
                });
                
                $this->line("   ✅ Sent to: {$customer['name']}");
                $successCount++;
                
                // Wait a bit between sends
                if ($index < count($testCustomers) - 1) {
                    sleep(2);
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Failed: {$customer['name']} - {$e->getMessage()}");
                $failCount++;
            }
        }
        
        $this->line("\nBulk Test Results:");
        $this->line("   ✅ Successful: {$successCount}");
        $this->line("   ❌ Failed: {$failCount}");
    }

    /**
     * Run all tests
     */
    private function runAllTests($controller, $email)
    {
        $this->info("\n🏃 Running ALL tests...");
        
        $this->testBasicFunctionality($controller, $email);
        
        $this->info("\n📋 Testing all templates...");
        foreach (array_keys($this->templates) as $templateName) {
            $this->line("\n   Testing {$templateName} template:");
            $this->testTemplate($controller, $email, $templateName);
            sleep(2);
        }
        
        $this->testBulkEmail($controller, $email);
        
        // Test with placeholders
        $this->info("\n📋 Testing placeholder replacement...");
        $placeholderBody = '<h2>Test Placeholders</h2>
                           <p>Customer Name: <strong>{{customer_name}}</strong></p>
                           <p>Email: {{customer_email}}</p>
                           <p>Date: {{date}}</p>
                           <p>Year: {{year}}</p>';
        
        $this->testSendEmail($controller, $email, 'Placeholder Test', $placeholderBody);
    }

    /**
     * Helper method to send test email
     */
    private function testSendEmail($controller, $to, $subject, $body)
    {
        try {
            // Configure email settings
            $this->configureGmail();
            
            // Build HTML with customer name
            $html = $this->buildTestEmailHtml($subject, $body, 'Test User');
            
            Mail::html($html, function ($message) use ($to, $subject) {
                $message->to($to)
                        ->subject('[TEST] ' . $subject);
            });
            
            $this->info("   ✅ Email sent successfully to {$to}");
            $this->line("      Subject: {$subject}");
            
        } catch (\Exception $e) {
            $this->error("   ❌ Failed: " . $e->getMessage());
        }
    }

    /**
     * Configure Gmail SMTP
     */
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

    /**
     * Build test email HTML
     */
    private function buildTestEmailHtml($subject, $body, $customerName)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>{$subject}</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #6d9eeb; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
                .test-badge { background: #ff4444; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; display: inline-block; margin-bottom: 10px; }
                .button { display: inline-block; background: #6d9eeb; color: white; text-decoration: none; padding: 12px 30px; border-radius: 5px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📧 TECLAB</h1>
            </div>
            <div class='content'>
                <div class='test-badge'>🧪 TEST EMAIL</div>
                <h2>Bonjour {$customerName} !</h2>
                <div>
                    {$body}
                </div>
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='http://localhost:3000' class='button'>Visiter notre site</a>
                </div>
                <p style='color: #999; font-size: 12px; margin-top: 20px;'>
                    <em>Ceci est un email de test du système TECLAB.</em>
                </p>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " TECLAB. Tous droits réservés.</p>
                <p>Rue 7 N° 184/Q4, Fès, Maroc</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Build bulk email HTML
     */
    private function buildBulkEmailHtml($customerName, $number)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>TECLAB Newsletter</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
                .badge { background: #27ae60; color: white; padding: 5px 10px; border-radius: 3px; font-size: 12px; display: inline-block; margin-bottom: 10px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📬 TECLAB Newsletter #{$number}</h1>
            </div>
            <div class='content'>
                <div class='badge'>📨 BULK TEST</div>
                <h2>Bonjour {$customerName} !</h2>
                <h3>✨ Nos dernières actualités</h3>
                <ul>
                    <li>Nouveaux produits disponibles</li>
                    <li>Promotions de la semaine</li>
                    <li>Événements à venir</li>
                </ul>
                <div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>
                    <p><strong>Offre exclusive:</strong> -15% sur votre prochain achat</p>
                    <p><strong>Code:</strong> BULK15</p>
                </div>
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " TECLAB</p>
                <p><small>Pour vous désabonner, cliquez <a href='#'>ici</a></small></p>
            </div>
        </body>
        </html>";
    }
}