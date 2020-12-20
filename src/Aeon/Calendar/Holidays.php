<?php

declare(strict_types=1);

namespace Aeon\Calendar;

use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Holidays\Holiday;

/**
 * @psalm-immutable
 */
interface Holidays
{
    public function isHoliday(Day $day) : bool;

    /**
     * @return array<Holiday>
     */
    public function holidaysAt(Day $day) : array;
}
