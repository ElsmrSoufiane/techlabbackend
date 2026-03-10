<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\TelegramBotController;

class TestTelegramBot extends Command
{
    // Fix the signature to accept arguments and options
    protected $signature = 'hi 
                            {message? : The message to send}
                            {--chat= : Chat ID to send to (optional)}';
    
    protected $description = 'Send a test message to Telegram';

    public function handle()
    {
        // Your personal chat ID
        $yourChatId = '6987139712';
        
        // Get message from argument or use default
        $message = $this->argument('message');
        if (!$message) {
            $message = 'Test message from Laravel terminal at ' . now();
        }
        
        // Get chat ID from option or use default
        $chatId = $this->option('chat') ?? $yourChatId;
        
        $this->info("🤖 Sending message to Telegram...");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━");
        $this->line("📱 Chat ID: {$chatId}");
        $this->line("📝 Message: {$message}");
        $this->line("━━━━━━━━━━━━━━━━━━━━━━━━");
        
        $telegramBot = new TelegramBotController();
        $result = $telegramBot->sendMessage($chatId, $message);
        
        if ($result === true) {
            $this->info('✅ Message sent successfully!');
            $this->line('Check your Telegram: @teclabbot_bot');
        } else {
            $this->error('❌ Failed to send message.');
            if (is_string($result)) {
                $this->line('Error: ' . $result);
            }
        }
    }
}