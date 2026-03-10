<?php
// app/Mail/VerificationEmail.php
namespace App\Mail;

use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $customer;

    public function __construct(Customer $customer)
    {
        $this->customer = $customer;
    }

    public function build()
    {
        $verificationUrl = url("/api/v1/verify-email/{$this->customer->verification_token}");
        
        return $this->subject('Vérification de votre email - TECLAB')
                    ->view('emails.verification')
                    ->with([
                        'name' => $this->customer->name,
                        'verificationUrl' => $verificationUrl
                    ]);
    }
}