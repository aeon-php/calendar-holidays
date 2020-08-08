<?php

declare(strict_types=1);

namespace Aeon\Calendar\Gregorian\Holidays;

use Aeon\Calendar\Exception\HolidayYearException;
use Aeon\Calendar\Exception\InvalidArgumentException;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays;

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
     * @var null|array<array<string, array<Holiday>>>
     */
    private ?array $calendars;

    public function __construct(string ...$countryCodes)
    {
        if (\count($countryCodes) === 0) {
            throw new InvalidArgumentException('List of country codes must not be empty');
        }

        \array_map(
            function (string $countryCode) : void {
                if (!\in_array($countryCode, Holidays\GoogleCalendar\CountryCodes::all(), true)) {
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
            $this->loadCalendars();
        }

        /** @var array<array<string, array<int, Holiday>>> $calendars */
        $calendars = $this->calendars;

        if (!\count($calendars)) {
            // @codeCoverageIgnoreStart
            throw new HolidayYearException('Holidays list is empty');
            // @codeCoverageIgnoreEnd
        }

        if (!\array_key_exists($day->year()->number(), $calendars)) {
            // @codeCoverageIgnoreStart
            throw new HolidayYearException(\sprintf('There are no holidays in %d, please check regional holidays data set.', $day->year()->number()));
            // @codeCoverageIgnoreStart
        }

        return isset($calendars[$day->year()->number()][$day->format('Y-m-d')]);
    }

    /**
     * @return array<Holiday>
     */
    public function holidaysAt(Day $day) : array
    {
        if ($this->calendars === null) {
            $this->loadCalendars();
        }

        /** @var array<array<string, array<Holiday>>> $calendars */
        $calendars = $this->calendars;

        if (!\count($calendars)) {
            throw new HolidayYearException('Holidays list is empty');
        }

        if (!\array_key_exists($day->year()->number(), $calendars)) {
            throw new HolidayYearException(\sprintf('There are no holidays in %d, please check regional holidays data set.', $day->year()->number()));
        }

        if (isset($calendars[$day->year()->number()][$day->format('Y-m-d')])) {
            return $calendars[$day->year()->number()][$day->format('Y-m-d')];
        }

        return [];
    }

    private function loadCalendars() : void
    {
        if ($this->calendars !== null) {
            return;
        }

        foreach ($this->countryCodes as $countryCode) {
            $this->loadCalendar($countryCode);
        }
    }

    /** @psalm-suppress InaccessibleProperty */
    private function loadCalendar(string $countryCode) : void
    {
        /**
         * @var array{
         *             country_code: string,
         *             name: string,
         *             timezones: array<int, string>,
         *             location: array<int, array<string, float>>,
         *             google_calendar: array<int, array{
         *             locale: string,
         *             calendar: string,
         *             years: array<int, array{
         *             year: int,
         *             holidays: array<int, array{date: string, name: string}>
         *             }>
         *             }>
         *             } $data
         */
        $data = (array) \json_decode((string) \file_get_contents(__DIR__ . '/data/regional/' . $countryCode . '.json'), true, JSON_THROW_ON_ERROR);

        if ($this->calendars === null) {
            $this->calendars = [];
        }

        foreach ($data['google_calendar'] as $googleCalendar) {
            foreach ($googleCalendar['years'] as $googleCalendarYear) {
                if (!\array_key_exists($googleCalendarYear['year'], $this->calendars)) {
                    $this->calendars[$googleCalendarYear['year']] = [];
                }

                foreach ($googleCalendarYear['holidays'] as $holiday) {
                    if (!\array_key_exists($holiday['date'], $this->calendars[$googleCalendarYear['year']])) {
                        $this->calendars[$googleCalendarYear['year']][$holiday['date']] = [];
                    }

                    $this->calendars[$googleCalendarYear['year']][$holiday['date']][] = new Holiday(
                        Day::fromString($holiday['date']),
                        new HolidayName(new HolidayLocaleName($googleCalendar['locale'], $holiday['name']))
                    );
                }
            }
        }
    }
}
