<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    public function geocode(string $address): ?array
    {
        if (trim($address) === '') {
            return null;
        }

        foreach ($this->providers() as $provider) {
            $result = match ($provider) {
                'google' => $this->geocodeWithGoogle($address),
                'photon' => $this->geocodeWithPhoton($address),
                'nominatim' => $this->geocodeWithNominatim($address),
                default => null,
            };

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    protected function providers(): array
    {
        $configured = config('services.geocoder.providers', 'google,photon,nominatim');

        if (is_string($configured)) {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }

        return is_array($configured) && $configured !== [] ? array_values($configured) : ['google', 'photon', 'nominatim'];
    }

    protected function geocodeWithGoogle(string $address): ?array
    {
        $key = (string) config('services.google_maps.key', '');

        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(6)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $address,
                    'key' => $key,
                    'region' => 'ph',
                ]);

            $data = $response->json();

            if (! $response->successful() || ($data['status'] ?? '') !== 'OK') {
                return null;
            }

            $location = $data['results'][0]['geometry']['location'] ?? null;

            if (! $location || ! isset($location['lat'], $location['lng'])) {
                return null;
            }

            return [
                'lat' => (float) $location['lat'],
                'lng' => (float) $location['lng'],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function geocodeWithPhoton(string $address): ?array
    {
        $url = (string) config('services.geocoder.photon_url', 'https://photon.komoot.io/api');

        try {
            $response = Http::timeout(6)
                ->get($url, [
                    'q' => $address,
                    'limit' => 1,
                    'lang' => 'en',
                ]);

            $data = $response->json();

            if (! $response->successful()) {
                return null;
            }

            $coordinates = $data['features'][0]['geometry']['coordinates'] ?? null;

            if (! is_array($coordinates) || count($coordinates) < 2) {
                return null;
            }

            return [
                'lat' => (float) $coordinates[1],
                'lng' => (float) $coordinates[0],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function geocodeWithNominatim(string $address): ?array
    {
        $url = (string) config('services.geocoder.nominatim_url', 'https://nominatim.openstreetmap.org/search');

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'User-Agent' => config('services.geocoder.nominatim_ua', 'TheDailyCutsByGD/1.0'),
                ])
                ->get($url, [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'ph',
                ]);

            $data = $response->json();

            if (! $response->successful() || ! is_array($data) || empty($data[0]['lat']) || empty($data[0]['lon'])) {
                return null;
            }

            return [
                'lat' => (float) $data[0]['lat'],
                'lng' => (float) $data[0]['lon'],
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function reverseGeocode(float $lat, float $lng): ?array
    {
        foreach ($this->providers() as $provider) {
            $result = match ($provider) {
                'google' => $this->reverseGeocodeWithGoogle($lat, $lng),
                'photon' => $this->reverseGeocodeWithPhoton($lat, $lng),
                'nominatim' => $this->reverseGeocodeWithNominatim($lat, $lng),
                default => null,
            };

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    protected function reverseGeocodeWithGoogle(float $lat, float $lng): ?array
    {
        $key = (string) config('services.google_maps.key', '');

        if ($key === '') {
            return null;
        }

        try {
            $response = Http::timeout(6)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'latlng' => $lat.','.$lng,
                    'key' => $key,
                    'region' => 'ph',
                ]);

            $data = $response->json();

            if (! $response->successful() || ($data['status'] ?? '') !== 'OK') {
                return null;
            }

            $result = $data['results'][0] ?? null;

            if (! $result) {
                return null;
            }

            $components = collect($result['address_components'] ?? []);
            $pick = static fn (array $keys) => $components->first(
                static fn ($component) => count(array_intersect($component['types'] ?? [], $keys)) > 0
            )['long_name'] ?? '';

            return [
                'address' => $result['formatted_address'] ?? '',
                'locality' => $pick(['locality', 'sublocality_level_1', 'neighborhood']),
                'region' => $pick(['administrative_area_level_1']),
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function reverseGeocodeWithPhoton(float $lat, float $lng): ?array
    {
        $url = (string) config('services.geocoder.photon_url', 'https://photon.komoot.io/api');

        try {
            $response = Http::timeout(6)
                ->get($url, [
                    'lat' => $lat,
                    'lon' => $lng,
                    'limit' => 1,
                    'lang' => 'en',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $feature = $response->json('features.0');

            if (! is_array($feature)) {
                return null;
            }

            $properties = $feature['properties'] ?? [];

            return [
                'address' => $properties['name'] ?? '',
                'locality' => trim(($properties['city'] ?? '').' '.($properties['street'] ?? '')),
                'region' => $properties['state'] ?? '',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function reverseGeocodeWithNominatim(float $lat, float $lng): ?array
    {
        $url = (string) config('services.geocoder.nominatim_url', 'https://nominatim.openstreetmap.org/reverse');

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'User-Agent' => config('services.geocoder.nominatim_ua', 'TheDailyCutsByGD/1.0'),
                ])
                ->get($url, [
                    'lat' => $lat,
                    'lon' => $lng,
                    'format' => 'json',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();

            if (! is_array($data)) {
                return null;
            }

            $address = $data['address'] ?? [];

            return [
                'address' => $data['display_name'] ?? '',
                'locality' => $address['city'] ?? $address['town'] ?? $address['village'] ?? '',
                'region' => $address['state'] ?? '',
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    public function fullAddress(array $parts): string
    {
        $address = trim($parts['address'] ?? '');
        $barangay = trim($parts['barangay'] ?? '');
        $city = trim($parts['city'] ?? '');
        $region = trim($parts['region'] ?? '');
        $postal = trim($parts['postal'] ?? '');

        $lines = array_filter([
            $address,
            $barangay,
            $city,
            trim($region.' '.$postal),
        ], fn ($line) => $line !== '');

        return implode(', ', $lines);
    }
}
