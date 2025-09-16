<?php
// MTN MoMo Collection configuration
// Fill in your sandbox or production credentials here.
return [
    // 'sandbox' or 'production'
    'target_env' => 'sandbox',

    // Base URLs for each environment
    'base_urls' => [
        'sandbox' => 'https://sandbox.momodeveloper.mtn.com/collection/v1_0',
        'production' => 'https://momodeveloper.mtn.com/collection/v1_0',
    ],

    // REQUIRED: Your Collection subscription primary key
    'primary_key' => '',

    // REQUIRED: The API User (UUID you created for this primary key)
    'api_user' => '',

    // REQUIRED: The API Key generated for the api_user above
    'api_key' => '',
];
