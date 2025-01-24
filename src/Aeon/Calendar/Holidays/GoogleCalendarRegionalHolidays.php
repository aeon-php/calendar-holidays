<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays;

use Aeon\Calendar\Exception\HolidayYearException;
use Aeon\Calendar\Exception\InvalidArgumentException;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\TimePeriod;
use Aeon\Calendar\Holidays;

/**
 * @psalm-immutable
 */
final class GoogleCalendarRegionalHolidays implements Holidays
{
    /**
     * @var array<string>
     */
    private array $countryCodes;

    /**
     * @var null|array<string, array<Holiday>>
     */
    private ?array $calendars;

    public function __construct(string ...$countryCodes)
    {
        if (\count($countryCodes) === 0) {
            throw new InvalidArgumentException('List of country codes must not be empty');
        }

        /**
         * @psalm-suppress ImpureFunctionCall
         */
        \array_map(
            function (string $countryCode) : void {
                if (!\in_array($countryCode, GoogleCalendar\CountryCodes::all(), true)) {
                    throw new InvalidArgumentException('Country with code ' . $countryCode . ' does not exists.');
                }
            },
            $normalizedCountryCodes = \array_map(
                function (string $countryCode) : string {
                    return \mb_strtoupper($countryCode);
                },
                $countryCodes
            )
        );

        $this->countryCodes = $normalizedCountryCodes;
        $this->calendars = null;
    }

    /**
     * @param Day $day
     *
     * @throws HolidayYearException
     *
     * @return bool
     */
    public function isHoliday(Day $day) : bool
    {
        if ($this->calendars === null) {
            /** @psalm-suppress UnusedMethodCall */
            $this->loadCalendars();
        }

        /** @var array<string, array<string, Holiday>> $calendars */
        $calendars = $this->calendars;

        if (!\count($calendars)) {
            // @codeCoverageIgnoreStart
            throw new HolidayYearException('Holidays list is empty');
            // @codeCoverageIgnoreEnd
        }

        foreach ($calendars as $calendar) {
            if (isset($calendar[$day->toString()])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws HolidayYearException
     *
     * @return array<Holiday>
     */
    public function holidaysAt(Day $day) : array
    {
        if ($this->calendars === null) {
            /** @psalm-suppress UnusedMethodCall */
            $this->loadCalendars();
        }

        /** @var array<string, array<Holiday>> $calendars */
        $calendars = $this->calendars;

        if (!\count($calendars)) {
            throw new HolidayYearException('Holidays list is empty');
        }

        $holidays = [];

        foreach ($calendars as $calendar) {
            if (isset($calendar[$day->toString()])) {
                $holidays[] = $calendar[$day->toString()];
            }
        }

        return $holidays;
    }

    /**
     * @return array<Holiday>
     */
    public function in(TimePeriod $period) : array
    {
        if ($this->calendars === null) {
            /** @psalm-suppress UnusedMethodCall */
            $this->loadCalendars();
        }

        /** @var array<string, array<string, Holiday>> $calendars */
        $calendars = $this->calendars;

        $holidays = [];

        foreach ($calendars as $calendar) {
            foreach ($calendar as $holiday) {
                if ($holiday->day()->isAfterOrEqualTo($period->start()->day()) && $holiday->day()->isBeforeOrEqualTo($period->end()->day())) {
                    $holidays[] = $holiday;
                }
            }
        }

        return $holidays;
    }

    private function loadCalendars() : void
    {
        if ($this->calendars !== null) {
            return;
        }

        foreach ($this->countryCodes as $countryCode) {
            /** @psalm-suppress UnusedMethodCall */
            $this->loadCalendar($countryCode);
        }
    }

    /**
     * @psalm-suppress InaccessibleProperty
     * @psalm-suppress ImpureFunctionCall
     */
    private function loadCalendar(string $countryCode) : void
    {
        /**
         * @var array<array{date: string, name: string}> $data
         */
        $data = (array) \json_decode((string) \file_get_contents(__DIR__ . '/data/regional/google_calendar/' . $countryCode . '.json'), true, JSON_THROW_ON_ERROR);

        if ($this->calendars === null) {
            $this->calendars = [];
        }

        /** @psalm-suppress PossiblyNullArgument */
        if (!\array_key_exists($countryCode, $this->calendars)) {
            $this->calendars[$countryCode] = [];
        }

        foreach ($data as $holidayData) {
            /** @psalm-suppress PossiblyNullArrayAssignment */
            $this->calendars[$countryCode][$holidayData['date']] = new Holiday(
                Day::fromString($holidayData['date']),
                new HolidayName(new HolidayLocaleName('en', $holidayData['name']))
            );
        }
    }
}
