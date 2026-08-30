<?php

namespace App\Services;

use Carbon\Carbon;

/**
 * Same-day dispatch window for Lalamove fulfillment.
 *
 * Config-driven (default 7:00am-3:00pm), all times in the application
 * timezone (Asia/Manila). The window behaves as a half-open range: dispatch
 * is allowed from start_hour (inclusive) up to end_hour (exclusive).
 */
class DeliveryWindow
{
    public function startHour(): int
    {
        return (int) config('shop.shipping.dispatch_window.start_hour', 7);
    }

    public function endHour(): int
    {
        return (int) config('shop.shipping.dispatch_window.end_hour', 15);
    }

    public function isOpen(?Carbon $time = null): bool
    {
        $hour = (int) ($time ?? now())->format('G');

        return $hour >= $this->startHour() && $hour < $this->endHour();
    }

    /**
     * The next moment the dispatch window opens (today or tomorrow).
     */
    public function nextOpenAt(?Carbon $time = null): Carbon
    {
        $time = $time ?? now();
        $candidate = $time->copy()->startOfDay()->addHours($this->startHour());

        if ($candidate->lte($time)) {
            $candidate->addDay();
        }

        return $candidate;
    }

    public function startLabel(): string
    {
        return $this->hourLabel($this->startHour());
    }

    public function endLabel(): string
    {
        return $this->hourLabel($this->endHour());
    }

    public function label(): string
    {
        return $this->startLabel().' - '.$this->endLabel();
    }

    protected function hourLabel(int $hour): string
    {
        $normalized = (($hour % 12) === 0 ? 12 : $hour % 12);

        return $normalized.':00'.(($hour >= 12) ? 'pm' : 'am');
    }
}