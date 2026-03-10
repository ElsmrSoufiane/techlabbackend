<?php
// app/Http/Controllers/TelegramBotController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Api;
use Illuminate\Support\Facades\Log;

class TelegramBotController extends Controller
{
    protected $telegram;
    protected $adminChatId = '-5051267768'; // Your admin chat ID
    
    public function __construct()
    {
        $this->telegram = new Api('8623243001:AAGrAvf8LJEGE13bEEaQ4mA8BTEt5mZz8wg');
    }
    
    public function sendMessage($chatId, $message)
    {
        try {
            // Convert to integer if it's a string
            $chatId = (int) $chatId;
            
            $result = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
            
            Log::info('Telegram message sent successfully', ['chat_id' => $chatId]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Telegram Error: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sendOrderNotification($order)
    {
        $message = "🛍️ <b>NOUVELLE COMMANDE</b>\n\n";
        $message .= "📦 <b>Commande #{$order->order_number}</b>\n";
        $message .= "👤 <b>Client:</b> {$order->customer->name}\n";
        $message .= "📧 <b>Email:</b> {$order->customer->email}\n";
        $message .= "📞 <b>Téléphone:</b> " . ($order->customer->phone ?? 'Non renseigné') . "\n";
        $message .= "📍 <b>Adresse:</b> {$order->shipping_address}\n";
        $message .= "💰 <b>Total:</b> {$order->total} MAD\n";
        $message .= "💳 <b>Paiement:</b> " . ($order->payment_method === 'carte' ? 'Carte' : 'Espèces (COD)') . "\n\n";
        $message .= "📋 <b>Articles:</b>\n";
        
        foreach ($order->items as $item) {
            $message .= "• {$item->product_name} x{$item->quantity} - " . ($item->price * $item->quantity) . " MAD\n";
        }
        
        return $this->sendMessage($this->adminChatId, $message);
    }
    
    public function testConnection()
    {
        try {
            $me = $this->telegram->getMe();
            return response()->json([
                'success' => true,
                'message' => "Bot @" . $me->getUsername() . " is working!"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    // Webhook method if needed
    public function webhook(Request $request)
    {
        // Handle incoming messages if needed
        $update = $this->telegram->getWebhookUpdate();
        
        if ($update->has('message')) {
            $message = $update->getMessage();
            $chatId = $message->getChat()->getId();
            $text = $message->getText();
            
            // Simple echo bot
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "You said: " . $text
            ]);
        }
        
        return response()->json(['status' => 'ok']);
    }
}