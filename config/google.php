<?php
require_once __DIR__ . "/env.php";
loadEnvFile(__DIR__ . "/../.env");

return [
    "client_id" => getenv('GOOGLE_CLIENT_ID') ?: '852715235838-ap4dhp5te64h4otq5mgo3td70g21419m.apps.googleusercontent.com',
    "client_secret" => getenv('GOOGLE_CLIENT_SECRET') ?: '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',
    "redirect_uri" => getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/Coding%20RPL/auth/google_callback.php'
];
