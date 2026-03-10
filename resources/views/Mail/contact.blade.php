{{-- resources/views/emails/contact.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <title>Nouveau message de contact</title>
</head>
<body>
    <h2>Nouveau message de contact</h2>
    
    <div style="border: 1px solid #ddd; padding: 20px; margin: 20px 0;">
        <p><strong>Nom :</strong> {{ $name }}</p>
        <p><strong>Email :</strong> {{ $email }}</p>
        <p><strong>Sujet :</strong> {{ $subject }}</p>
        <p><strong>Message :</strong></p>
        <p style="background: #f5f5f5; padding: 15px;">{{ $message }}</p>
    </div>
    
    <p>Vous pouvez répondre à ce message en répondant directement à cet email.</p>
    <br>
    <p>Cordialement,<br>L'équipe TECLAB</p>
</body>
</html>