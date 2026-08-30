<?php

use App\Services\DeliveryWindow;
use Carbon\Carbon;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'shop.shipping.dispatch_window.start_hour' => 7,
        'shop.shipping.dispatch_window.end_hour' => 15,
    ]);
});

test('isOpen is true inside the dispatch window', function () {
    $window = app(DeliveryWindow::class);

    expect($window->isOpen(Carbon::parse('2026-08-30 07:00:00')))->toBeTrue();
    expect($window->isOpen(Carbon::parse('2026-08-30 10:30:00')))->toBeTrue();
    expect($window->isOpen(Carbon::parse('2026-08-30 14:59:00')))->toBeTrue();
});

test('isOpen is false outside the dispatch window', function () {
    $window = app(DeliveryWindow::class);

    expect($window->isOpen(Carbon::parse('2026-08-30 06:59:00')))->toBeFalse();
    expect($window->isOpen(Carbon::parse('2026-08-30 15:00:00')))->toBeFalse();
    expect($window->isOpen(Carbon::parse('2026-08-30 20:00:00')))->toBeFalse();
});

test('nextOpenAt returns today when still ahead of the window', function () {
    $window = app(DeliveryWindow::class);

    $next = $window->nextOpenAt(Carbon::parse('2026-08-30 05:00:00'));

    expect($next->toDateTimeString())->toBe('2026-08-30 07:00:00');
});

test('nextOpenAt returns the next day when the window is closed later', function () {
    $window = app(DeliveryWindow::class);

    $next = $window->nextOpenAt(Carbon::parse('2026-08-30 20:00:00'));

    expect($next->toDateTimeString())->toBe('2026-08-31 07:00:00');
});

test('nextOpenAt returns tomorrow when the window is currently open', function () {
    $window = app(DeliveryWindow::class);

    $next = $window->nextOpenAt(Carbon::parse('2026-08-30 10:00:00'));

    expect($next->toDateTimeString())->toBe('2026-08-31 07:00:00');
});

test('labels reflect the configured window', function () {
    $window = app(DeliveryWindow::class);

    expect($window->label())->toBe('7:00am - 3:00pm');
    expect($window->startLabel())->toBe('7:00am');
    expect($window->endLabel())->toBe('3:00pm');
});