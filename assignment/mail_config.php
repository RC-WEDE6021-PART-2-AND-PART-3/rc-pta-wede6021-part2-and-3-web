<?php
// Mail configuration - update with real SMTP credentials for production
return [
    'use_smtp' => false, // set true to use SMTP via PHPMailer
    'smtp' => [
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'password',
        'secure' => 'tls', // 'ssl' or 'tls'
    ],
    'from_email' => 'no-reply@example.com',
    'from_name' => 'Pastimes Clothing'
];
