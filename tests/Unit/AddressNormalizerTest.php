<?php

use App\Services\AddressNormalizer;
use Tests\TestCase;

uses(TestCase::class);

test('address line collapses a repeated street', function () {
    $normalizer = app(AddressNormalizer::class);

    expect($normalizer->line('2461 P Villanueva St Pasay City 2461 P Villanueva St Pasay City', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
    expect($normalizer->line('2461 P Villanueva St 2461 P Villanueva St', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
});

test('address line strips a trailing city that duplicates the city field', function () {
    $normalizer = app(AddressNormalizer::class);

    expect($normalizer->line('2461 P Villanueva St Pasay City', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
    expect($normalizer->line('2461 P Villanueva St Pasay', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
    expect($normalizer->line('2461 P Villanueva St, Pasay, Metro Manila', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
    expect($normalizer->line('1234 Makati Ave Metro Manila', 'Makati', 'Metro Manila'))
        ->toBe('1234 Makati Ave');
});

test('address line strips a trailing postal code that duplicates the postal field', function () {
    $normalizer = app(AddressNormalizer::class);

    expect($normalizer->line('2461 P Villanueva St 1300', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
});

test('address line leaves a normal street address untouched', function () {
    $normalizer = app(AddressNormalizer::class);

    expect($normalizer->line('2461 P Villanueva St', 'Pasay', 'Metro Manila'))
        ->toBe('2461 P Villanueva St');
    expect($normalizer->line('Unit 4B 1234 Bonifacio Ave', 'Makati', 'Metro Manila'))
        ->toBe('Unit 4B 1234 Bonifacio Ave');
    expect($normalizer->line(''))->toBe('');
});
