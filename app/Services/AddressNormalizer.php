<?php

namespace App\Services;

class AddressNormalizer
{
    /**
     * Cleans a free-form street line before it is persisted.
     *
     * Browser address autofill and manual typing routinely repeat the street
     * (e.g. "2461 P Villanueva St Pasay City 2461 P Villanueva St Pasay City")
     * or append the city/region/postal that are already captured by their own
     * dedicated fields. All of those duplicates are collapsed here so orders,
     * saved addresses, and delivery dispatch receive one clean street line.
     */
    public function line(string $street, string $city = '', string $region = ''): string
    {
        $street = trim(preg_replace('/\s+/', ' ', $street) ?? '');

        // Collapse exact self-repeats: "X X" -> "X".
        if (preg_match('/^(.*)\s+\1$/i', $street, $m) === 1) {
            $street = trim($m[1]);
        }

        // Drop trailing parts that duplicate the dedicated city/region fields.
        foreach ([$region, $city] as $part) {
            $street = $this->stripTrailing($street, $part);
        }

        // Drop a trailing postal code that duplicates the dedicated field.
        $street = trim(preg_replace('/[\s,]+[0-9]{4}$/i', '', $street) ?? '');

        return rtrim($street, " \t,");
    }

    protected function stripTrailing(string $street, string $part): string
    {
        $part = trim($part);

        if ($part === '' || $street === '') {
            return $street;
        }

        // Matches "Pasay", "Pasay City", or "City Pasay" at the very end so a
        // street that ends in either the short or the long city name is cleaned.
        $quoted = preg_quote($part, '/');
        $street = trim(preg_replace(
            '/(?:[\s,]+)(?:'.$quoted.'(?:[\s]+City)?|City[\s]+'.$quoted.')$/i',
            '',
            $street
        ) ?? '');

        return $street;
    }
}
