<?php

declare(strict_types=1);

namespace Aeon\Calendar\Tests\Functional\Holidays;

use Aeon\Calendar\Exception\InvalidArgumentException;
use Aeon\Calendar\Gregorian\DateTime;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\TimePeriod;
use Aeon\Calendar\Holidays\GoogleCalendar\CountryCodes;
use Aeon\Calendar\Holidays\GoogleCalendarRegionalHolidays;
use Aeon\Calendar\Holidays\Holiday;
use PHPUnit\Framework\TestCase;

final class GoogleCalendarRegionalHolidaysTest extends TestCase
{
    public function test_checking_regional_holidays() : void
    {
        $holidays = new GoogleCalendarRegionalHolidays(CountryCodes::PL);

        $this->assertTrue($holidays->isHoliday(Day::fromString('2020-01-01')));
        $this->assertFalse($holidays->isHoliday(Day::fromString('2020-01-02')));
    }

    public function test_getting_regional_holidays() : void
    {
        $holidays = new GoogleCalendarRegionalHolidays(CountryCodes::PL);

        $this->assertCount(1, $holidays->holidaysAt(Day::fromString('2020-01-01')));
        $this->assertInstanceOf(Holiday::class, $holidays->holidaysAt(Day::fromString('2020-01-01'))[0]);
    }

    public function test_getting_regional_holidays_from_multiple_regions() : void
    {
        $holidays = new GoogleCalendarRegionalHolidays(CountryCodes::PL, CountryCodes::US);

        $this->assertCount(2, $holidays->holidaysAt(Day::fromString('2020-01-01')));
        $this->assertInstanceOf(Holiday::class, $holidays->holidaysAt(Day::fromString('2020-01-01'))[0]);
        $this->assertInstanceOf(Holiday::class, $holidays->holidaysAt(Day::fromString('2020-01-01'))[1]);
        $this->assertSame('New Year\'s Day', $holidays->holidaysAt(Day::fromString('2020-01-01'))[0]->name());
        $this->assertSame('New Year\'s Day', $holidays->holidaysAt(Day::fromString('2020-01-01'))[1]->name());
    }

    public function test_getting_holidays_without_providing_country_code() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('List of country codes must not be empty');

        $holidays = new GoogleCalendarRegionalHolidays();

        $holidays->holidaysAt(Day::fromString('2020-01-01'));
    }

    public function test_getting_holidays_for_a_time_period() : void
    {
        $holidays = new GoogleCalendarRegionalHolidays(CountryCodes::PL);

        $januaryHolidays = $holidays->in(
            new TimePeriod(
                DateTime::fromString('2021-01-01'),
                DateTime::fromString('2021-01-31')
            )
        );

        $this->assertCount(2, $januaryHolidays);
        $this->assertSame('New Year\'s Day', $januaryHolidays[0]->name());
        $this->assertSame('Epiphany', $januaryHolidays[1]->name());
    }
}
