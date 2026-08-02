<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LalamoveService
{
    private string $apiKey;

    private string $apiSecret;

    private string $baseUrl;

    private string $market;

    public function __construct()
    {
        $this->apiKey = config('services.lalamove.key', '');
        $this->apiSecret = config('services.lalamove.secret', '');
        $this->market = config('services.lalamove.market', 'PH');
        $sandbox = config('services.lalamove.sandbox', true);
        $this->baseUrl = $sandbox
            ? 'https://rest.sandbox.lalamove.com'
            : 'https://rest.lalamove.com';
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiSecret !== '';
    }

    public function getQuotation(float $pickupLat, float $pickupLng, string $pickupAddress, float $dropoffLat, float $dropoffLng, string $dropoffAddress, string $serviceType = 'MOTORCYCLE'): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $timestamp = (string) (int) (microtime(true) * 1000);
        $requestPath = '/v3/quotations';
        $method = 'POST';

        $body = [
            'data' => [
                'serviceType' => $serviceType,
                'language' => 'en_PH',
                'stops' => [
                    [
                        'coordinates' => [
                            'lat' => (string) number_format($pickupLat, 7, '.', ''),
                            'lng' => (string) number_format($pickupLng, 7, '.', ''),
                        ],
                        'address' => $pickupAddress,
                    ],
                    [
                        'coordinates' => [
                            'lat' => (string) number_format($dropoffLat, 7, '.', ''),
                            'lng' => (string) number_format($dropoffLng, 7, '.', ''),
                        ],
                        'address' => $dropoffAddress,
                    ],
                ],
            ],
        ];

        $bodyJson = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($timestamp, $method, $requestPath, $bodyJson);

        $logSafeBody = $this->redactBody($body);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "hmac {$this->apiKey}:{$timestamp}:{$signature}",
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                    'MARKET' => $this->market,
                ])
                ->withBody($bodyJson, 'application/json')
                ->post($this->baseUrl.$requestPath);

            $data = $response->json();

            if ($response->successful() && isset($data['data'])) {
                return $data['data'];
            }

            Log::warning('Lalamove quotation failed', [
                'status' => $response->status(),
                'response' => $data,
                'body_sent' => $logSafeBody,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Lalamove API error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function getCityInfo(string $city): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $timestamp = (string) (int) (microtime(true) * 1000);
        $requestPath = '/v3/cities/'.$this->market;
        $method = 'GET';

        $signature = $this->generateSignature($timestamp, $method, $requestPath);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "hmac {$this->apiKey}:{$timestamp}:{$signature}",
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                    'MARKET' => $this->market,
                ])
                ->get($this->baseUrl.$requestPath);

            $data = $response->json();

            if ($response->successful() && isset($data['data'])) {
                return $data['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Lalamove city info error', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function generateSignature(string $timestamp, string $method, string $path, string $body = ''): string
    {
        $toSign = $timestamp."\r\n".$method."\r\n".$path."\r\n\r\n".$body;

        return hash_hmac('sha256', $toSign, $this->apiSecret);
    }

    private function redactBody(array $body): string
    {
        foreach (($body['data']['stops'] ?? []) as &$stop) {
            if (isset($stop['address'])) {
                $stop['address'] = '[REDACTED]';
            }
        }

        unset($stop);

        return json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
