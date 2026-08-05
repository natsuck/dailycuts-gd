<?php

use App\Services\LalamoveService;
use Tests\TestCase;

uses(TestCase::class);

test('formatPhone converts local and spaced numbers to E.164', function () {
    $service = app(LalamoveService::class);

    expect($service->formatPhone('+63 962 796 1415'))->toBe('+639627961415');
    expect($service->formatPhone('09627961415'))->toBe('+639627961415');
    expect($service->formatPhone('639627961415'))->toBe('+639627961415');
    expect($service->formatPhone('+63 962-796-1415'))->toBe('+639627961415');
    expect($service->formatPhone('09171234567'))->toBe('+639171234567');
});
