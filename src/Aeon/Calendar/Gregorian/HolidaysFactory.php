<?php declare(strict_types=1);

namespace Aeon\Calendar\Gregorian;

interface HolidaysFactory
{
    public function create(string $countryCode) : Holidays;
}
