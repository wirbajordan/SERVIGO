<?php
require_once __DIR__ . '/../vendor/autoload.php'; // If using Composer

use GuzzleHttp\Client;

class MTNMomo {
    private $client;
    private $apiUser;
    private $apiKey;
    private $primaryKey;
    private $baseUrl;
    private $targetEnv;

    public function __construct() {
        $this->client = new Client();
        $this->apiUser = 'YOUR_API_USER'; // TODO: Replace with your API User
        $this->apiKey = 'YOUR_API_KEY';   // TODO: Replace with your API Key
        $this->primaryKey = 'YOUR_PRIMARY_KEY'; // TODO: Replace with your Primary Key
        $this->baseUrl = 'https://sandbox.momodeveloper.mtn.com/collection/v1_0';
        $this->targetEnv = 'sandbox';
    }

    // Get access token
    public function getToken() {
        $response = $this->client->post('https://sandbox.momodeveloper.mtn.com/collection/token/', [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
                'Ocp-Apim-Subscription-Key' => $this->primaryKey,
            ]
        ]);
        $data = json_decode($response->getBody(), true);
        return $data['access_token'];
    }

    // Request to pay
    public function requestToPay($amount, $currency, $externalId, $payerNumber, $payerMessage, $payeeNote) {
        $token = $this->getToken();
        $referenceId = $this->generateUUID();
        $this->client->post($this->baseUrl . '/requesttopay', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'X-Reference-Id' => $referenceId,
                'X-Target-Environment' => $this->targetEnv,
                'Ocp-Apim-Subscription-Key' => $this->primaryKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'amount' => $amount,
                'currency' => $currency,
                'externalId' => $externalId,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $payerNumber
                ],
                'payerMessage' => $payerMessage,
                'payeeNote' => $payeeNote
            ]
        ]);
        return $referenceId;
    }

    // Check payment status
    public function getPaymentStatus($referenceId) {
        $token = $this->getToken();
        $response = $this->client->get($this->baseUrl . '/requesttopay/' . $referenceId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'X-Target-Environment' => $this->targetEnv,
                'Ocp-Apim-Subscription-Key' => $this->primaryKey,
            ]
        ]);
        return json_decode($response->getBody(), true);
    }

    // Helper to generate UUID
    private function generateUUID() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
} 