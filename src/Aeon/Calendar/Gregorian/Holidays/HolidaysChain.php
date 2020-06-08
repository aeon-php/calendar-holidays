<?php

declare(strict_types=1);

namespace Aeon\Calendar\Gregorian\Holidays;

use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays;

/**
 * @psalm-immutable
 */
final class HolidaysChain implements Holidays
{
    /**
     * @var array<int, Holidays>
     */
    private array $holidaysProviders;

    public function __construct(Holidays ...$holidaysProviders)
    {
        $this->holidaysProviders = $holidaysProviders;
    }

    public function isHoliday(Day $day) : bool
    {
        foreach ($this->holidaysProviders as $holidays) {
            if ($holidays->isHoliday($day)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, Holiday>
     */
    public function holidaysAt(Day $day) : array
    {
        return \array_values(
            \array_merge(
                ...\array_map(
                    function (Holidays $holidays) use ($day) : array {
                        return $holidays->holidaysAt($day);
                    },
                    $this->holidaysProviders
                )
            )
        );
    }
}
