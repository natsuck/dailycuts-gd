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

    private ?string $lastError = null;

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

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function getQuotation(float $pickupLat, float $pickupLng, string $pickupAddress, float $dropoffLat, float $dropoffLng, string $dropoffAddress, string $serviceType = 'MOTORCYCLE', array $item = [], array $specialRequests = ['THERMAL_BAG_1']): ?array
    {
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

        if ($item !== []) {
            $body['data']['item'] = $item;
        }

        if ($specialRequests !== []) {
            $body['data']['specialRequests'] = array_values($specialRequests);
        }

        return $this->send('POST', '/v3/quotations', $body, 'Lalamove quotation failed');
    }

    public function getCityInfo(string $city): ?array
    {
        return $this->send('GET', '/v3/cities/'.$this->market, null, 'Lalamove city info error');
    }

    public function createOrder(string $quotationId, array $sender, array $recipients): ?array
    {
        $body = [
            'data' => [
                'quotationId' => $quotationId,
                'sender' => $sender,
                'recipients' => $recipients,
                'isPODEnabled' => true,
                'partner' => config('shop.store.name', 'The Daily Cuts by GD'),
            ],
        ];

        return $this->send('POST', '/v3/orders', $body, 'Lalamove order creation failed');
    }

    public function getOrder(string $orderId): ?array
    {
        return $this->send('GET', '/v3/orders/'.$orderId, null, 'Lalamove order info error');
    }

    public function updateWebhook(string $url): ?array
    {
        return $this->send('PATCH', '/v3/webhook', ['data' => ['url' => $url]], 'Lalamove webhook registration failed');
    }

    public function formatPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '63')) {
            return '+'.$digits;
        }

        return '+63'.$digits;
    }

    private function send(string $method, string $path, ?array $body = null, string $logLabel = 'Lalamove API error'): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $timestamp = (string) (int) (microtime(true) * 1000);
        $bodyJson = $body === null ? '' : json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $signature = $this->generateSignature($timestamp, $method, $path, $bodyJson);

        $maxAttempts = 3; // initial request + 2 retries for temporary failures
        $backoffMs = 250;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $start = microtime(true);

            try {
                $request = Http::timeout(15)->withHeaders([
                    'Authorization' => "hmac {$this->apiKey}:{$timestamp}:{$signature}",
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Accept' => 'application/json',
                    'MARKET' => $this->market,
                ]);

                if ($body === null) {
                    $response = $request->get($this->baseUrl.$path);
                } else {
                    $response = $request->withBody($bodyJson, 'application/json')->send(strtoupper($method), $this->baseUrl.$path);
                }

                $data = $response->json();
                $durationMs = (int) ((microtime(true) - $start) * 1000);

                if ($response->successful() && isset($data['data'])) {
                    Log::info('Lalamove API request succeeded', [
                        'method' => $method,
                        'path' => $path,
                        'status' => $response->status(),
                        'duration_ms' => $durationMs,
                        'attempt' => $attempt,
                    ]);

                    return $data['data'];
                }

                $this->lastError = $this->errorMessage($data, $response->status());

                Log::warning($logLabel, [
                    'status' => $response->status(),
                    'response' => $data,
                    'body_sent' => $body === null ? null : $this->redactBody($body),
                    'attempt' => $attempt,
                ]);

                if ($this->isRetryableStatus($response->status()) && $attempt < $maxAttempts) {
                    usleep($backoffMs * 1000);
                    $backoffMs *= 2;

                    continue;
                }

                return null;
            } catch (\Exception $e) {
                $this->lastError = $e->getMessage();

                // Network/timeout failures are transient — retry before giving up.
                if ($attempt < $maxAttempts) {
                    Log::warning($logLabel, [
                        'message' => $e->getMessage(),
                        'attempt' => $attempt,
                    ]);

                    usleep($backoffMs * 1000);
                    $backoffMs *= 2;

                    continue;
                }

                Log::error($logLabel, [
                    'message' => $e->getMessage(),
                    'method' => $method,
                    'path' => $path,
                ]);

                return null;
            }
        }

        return null;
    }

    /**
     * Retry only on temporary upstream failures; never on client/auth errors.
     */
    private function isRetryableStatus(int $status): bool
    {
        return in_array($status, [429, 500, 502, 503, 504], true);
    }

    private function errorMessage(?array $data, int $status): string
    {
        $data = is_array($data) ? $data : [];

        $messages = [];

        foreach ($data['errors'] ?? [] as $error) {
            if (! is_array($error)) {
                continue;
            }

            $id = $error['id'] ?? null;
            $message = $error['message'] ?? null;

            if ($message !== null) {
                $messages[] = $id !== null ? $id.': '.$message : $message;
            }
        }

        if ($messages !== []) {
            return implode(' | ', $messages);
        }

        $id = data_get($data, 'errors.id') ?? data_get($data, 'error.code') ?? null;
        $message = data_get($data, 'errors.message') ?? data_get($data, 'error.message') ?? data_get($data, 'message') ?? 'HTTP '.$status;

        return $id !== null ? $id.': '.$message : $message;
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
