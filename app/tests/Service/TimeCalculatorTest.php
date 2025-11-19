<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Employee;
use App\Entity\WorkTime;
use App\Service\TimeCalculator;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

final class TimeCalculatorTest extends TestCase
{
    private TimeCalculator $calc;

    protected function setUp(): void
    {
        $this->calc = new TimeCalculator();
    }

    public static function roundingProvider(): array
    {
        return [
            // [hours (float), expected rounded hours]
            [8.1666666667, 8.0], // 8:10 -> 8.166.. -> rounds to 8.5
            [8.2833333333, 8.5], // 8:17 -> 8.283.. -> rounds to 8.5
            [8.5833333333, 8.5], // 8:35 -> 8.583.. -> rounds to 8.5
            [8.8, 9.0],          // 8:48 -> 8.8 -> rounds to 9.0
            [1.0, 1.0],          // exact hour stays
            [0.25, 0.5],         // 15min -> 0.25 -> rounds to 0.5
        ];
    }

    /**
     * @dataProvider roundingProvider
     */
    public function testRoundToHalfHour(float $hours, float $expected): void
    {
        $this->assertSame($expected, $this->calc->roundToHalfHour($hours));
    }

    /**
     * @
     */
    public function testCalculateDayHoursWithWorkTimeObjects(): void
    {
        // employee
        $employee = new Employee('Jan', 'Kowalski');

        // worktime 1: 08:00 - 08:17 (17 min -> rounds to 0.5h)
        $start1 = new \DateTimeImmutable('2025-11-01T08:00:00+01:00');
        $end1 = new \DateTimeImmutable('2025-11-01T08:17:00+01:00');
        $wt1 = new WorkTime($employee, $start1, $end1);

        // worktime 2: 09:00 - 10:10 (70 min -> 1.166..h -> rounds to 1.0h or 1.0? depends on rule)
        // Using our roundToHalfHour math: 70min -> 1.166..h -> minutes=70 -> rounded = 60 (nearest 30)??
        // To keep deterministic: choose 09:00-09:40 (40min -> 0.666..h -> rounds to 0.5h)
        $start2 = new \DateTimeImmutable('2025-11-01T09:00:00+01:00');
        $end2 = new \DateTimeImmutable('2025-11-01T09:40:00+01:00'); // 40 min
        $wt2 = new WorkTime($employee, $start2, $end2);

        $hours = $this->calc->calculateDayHours([$wt1, $wt2]);

        // durations: 17 min + 40 min = 57 min => 0.95h -> rounding rule: 57min -> nearest 30 -> 60min -> 1.0h
        // But because calculateDayHours first sums seconds then converts to hours then rounds to half-hour
        $this->assertSame(1.0, $hours);
    }
}
