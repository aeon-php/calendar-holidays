<?php

declare(strict_types=1);

namespace Aeon\Calendar\Gregorian\Holidays;

use Aeon\Calendar\Gregorian\Holidays;
use Aeon\Calendar\Gregorian\HolidaysFactory;

final class GoogleRegionalHolidaysFactory implements HolidaysFactory
{
    public function create(string $countryCode) : Holidays
    {
        return new GoogleCalendarRegionalHolidays($countryCode);
    }
}
