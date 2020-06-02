<?php

declare(strict_types=1);

namespace Aeon\Calendar\Gregorian\Holidays;

use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays;

/**
 * @psalm-immutable
 * @codeCoverageIgnore
 */
final class EmptyHolidays implements Holidays
{
    public function isHoliday(Day $day) : bool
    {
        return false;
    }

    public function holidaysAt(Day $day) : array
    {
        return [];
    }
}
