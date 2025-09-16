<?php
require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MTNMomo {
    private $client;
    private $apiUser;
    private $apiKey;
    private $primaryKey;
    private $baseUrl;
    private $targetEnv;

    public function __construct() {
        $this->client = new Client([
            'timeout' => 15,
            'connect_timeout' => 10,
        ]);
        $cfg = require __DIR__ . '/../config/momo.php';
        $this->targetEnv = $cfg['target_env'] ?? 'sandbox';
        $baseMap = $cfg['base_urls'] ?? [];
        $this->baseUrl = $baseMap[$this->targetEnv] ?? 'https://sandbox.momodeveloper.mtn.com/collection/v1_0';
        $this->apiUser = $cfg['api_user'] ?? '';
        $this->apiKey = $cfg['api_key'] ?? '';
        $this->primaryKey = $cfg['primary_key'] ?? '';
        if (!$this->apiUser || !$this->apiKey || !$this->primaryKey) {
            throw new \RuntimeException('MTN MoMo is not configured. Please fill config/momo.php with api_user, api_key and primary_key.');
        }
    }

    // Get access token
    public function getToken() {
        try {
            $response = $this->client->post('https://sandbox.momodeveloper.mtn.com/collection/token/', [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode($this->apiUser . ':' . $this->apiKey),
                    'Ocp-Apim-Subscription-Key' => $this->primaryKey,
                ]
            ]);
            $data = json_decode($response->getBody(), true);
            if (!isset($data['access_token'])) {
                throw new \RuntimeException('Failed to obtain access token from MTN MoMo.');
            }
            return $data['access_token'];
        } catch (RequestException $e) {
            $msg = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new \RuntimeException('Token request failed: ' . $msg, 0, $e);
        }
    }

    // Request to pay
    public function requestToPay($amount, $currency, $externalId, $payerNumber, $payerMessage, $payeeNote) {
        $token = $this->getToken();
        $referenceId = $this->generateUUID();
        try {
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
        } catch (RequestException $e) {
            $msg = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new \RuntimeException('RequestToPay failed: ' . $msg, 0, $e);
        }
        return $referenceId;
    }

    // Check payment status
    public function getPaymentStatus($referenceId) {
        $token = $this->getToken();
        try {
            $response = $this->client->get($this->baseUrl . '/requesttopay/' . $referenceId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'X-Target-Environment' => $this->targetEnv,
                    'Ocp-Apim-Subscription-Key' => $this->primaryKey,
                ]
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $msg = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            throw new \RuntimeException('GetPaymentStatus failed: ' . $msg, 0, $e);
        }
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