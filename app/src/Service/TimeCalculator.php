<?php

namespace App\Service;

use App\Entity\WorkTime;

class TimeCalculator
{
    /**
     * Zaokrąglanie godzin do najbliższych 30 minut (np. 8.17 -> 8.5)
     */
    public function roundToHalfHour(float $hours): float
    {
        $minutes = $hours * 60;
        $rounded = round($minutes / 30) * 30;
        return $rounded / 60.0;
    }

    public function secondsToHours(float $seconds): float
    {
        return $seconds / 3600.0;
    }

    /**
     * @param WorkTime[] $workTimes
     */
    public function calculateDayHours(array $workTimes): float
    {
        $totalSeconds = 0;
        foreach ($workTimes as $wt) {
            $totalSeconds += $wt->getDurationSeconds();
        }
        $hours = $this->secondsToHours($totalSeconds);
        return $this->roundToHalfHour($hours);
    }

    /**
     * @param WorkTime[] $workTimes
     * @return array{normal_hours: float, overtime_hours: float, total_hours: float}
     */
    public function calculateMonthSummary(array $workTimes, float $monthlyNorm): array
    {
        $totalSeconds = 0;
        foreach ($workTimes as $wt) {
            $totalSeconds += $wt->getDurationSeconds();
        }
        $hours = $this->roundToHalfHour($this->secondsToHours($totalSeconds));

        $normal = min($hours, $monthlyNorm);
        $overtime = max(0.0, $hours - $monthlyNorm);

        return [
            'normal_hours' => $normal,
            'overtime_hours' => $overtime,
            'total_hours' => $hours,
        ];
    }
}
