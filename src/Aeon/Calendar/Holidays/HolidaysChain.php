<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays;

use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Holidays;

/**
 * @psalm-immutable
 */
final class HolidaysChain implements Holidays
{
    /**
     * @var array<Holidays>
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
     * @return array<Holiday>
     */
    public function holidaysAt(Day $day) : array
    {
        return \array_merge(
            ...\array_map(
                function (Holidays $holidays) use ($day) : array {
                    return $holidays->holidaysAt($day);
                },
                $this->holidaysProviders
            )
        );
    }
}
