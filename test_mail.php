<?php
// Quick SMTP test — run from the talentosWeb directory
// Usage: php test_mail.php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

$dsn = 'smtp://talentos.pidev%40gmail.com:ywscuivzumlbekpu@smtp.gmail.com:587';

echo "Testing SMTP with DSN: $dsn\n";

try {
    $transport = Transport::fromDsn($dsn);
    $mailer = new Mailer($transport);

    $email = (new Email())
        ->from('talentos.pidev@gmail.com')
        ->to('talentos.pidev@gmail.com') // send to self
        ->subject('Test from Talentos Web')
        ->text('If you see this, SMTP works!');

    $mailer->send($email);
    echo "✅ Email sent successfully!\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
