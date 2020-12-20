#!/usr/bin/env php
<?php

use Aeon\Calendar\Gregorian\GregorianCalendar;
use Aeon\Calendar\Gregorian\Year;

require_once __DIR__ . '/../vendor/autoload.php';

if (!\is_string(\getenv('GOOGLE_API_KEY'))) {
    die('Please run this script by passing GOOGLE_API_KEY through env variable first.');
}

$googleApiClient = new \Google_Client();
$googleApiClient->setApplicationName("Google Holidays Calendar Scraper");

// Setup one at https://console.developers.google.com/
$googleApiClient->setDeveloperKey(getenv('GOOGLE_API_KEY'));

$googleCalendarService = new \Google_Service_Calendar($googleApiClient);
$calendar = GregorianCalendar::UTC();

$countries = \json_decode(\file_get_contents(__DIR__ . '/../resources/countries.json'), true);
$timezones = \json_decode(\file_get_contents(__DIR__ . '/../resources/timezones.json'), true);

$count = \count($countries['countries']);

$index = 0;

foreach ($countries['countries'] as $countryCode => $countryData) {
    $index++;
    echo "$countryCode - $index / $count \n";

    $tz = null;
    foreach ($timezones as $tzData) {
        if ($countryCode === $tzData['country_code']) {
            $tz = $tzData;
        }
    }

    if ($tz === null) {
        continue;
    }

    if (!isset($countryData['googleHolidaysCalendarId'])) {
        continue;
    }

    $countryData['timezones'] = $tz['timezones'];
    $countryData['location'] = [
        'lat' => $tz['latlng'][0],
        'lng' => $tz['latlng'][1]
    ];

    $calendarId = \str_replace('{{ locale }}', 'en', $countryData['googleHolidaysCalendarId']);

    try {
        // Iterate over results from google holidays calendar for given country and save them into
        // holidays json structure used later by google calendar holidays adapter for aeon holidays library.
        $holidays = fetchHolidaysFromApi($googleCalendarService, $calendarId);

        // Sort holidays fetched from the API in order to avoid opening pull requests with just changed order of the
        // same holidays.
        $holidays = sortHolidaysByDate($holidays);

        // If file with holidays already exists take from past year holidays and override whatever comes from the API.
        // This way API is used only to update upcoming/current year without removing historical holidays.
        $holidays = takeOldHolidaysFromExistingFile($countryCode, $holidays, $calendar);

        \file_put_contents(__DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/' . $countryCode . '.json', \json_encode([
            'country_code' => $countryCode,
            'name' => $countryData['name'],
            'timezones' => $tz['timezones'],
            'location' => [
                'lat' => $tz['latlng'][0],
                'lng' => $tz['latlng'][1]
            ],
            'google_calendar' => [
                [
                    'locale' => 'en',
                    'calendar' => $calendarId,
                    'years' => $holidays
                ]
            ]
        ], JSON_PRETTY_PRINT));
    } catch (Google_Service_Exception $e) {
        echo "google calendar not found for - $countryCode \n";
    }
}

/**
 * @param Google_Service_Calendar $googleCalendarService
 * @param $calendarId
 * @return array
 * @throws Google_Service_Exception
 */
function fetchHolidaysFromApi(Google_Service_Calendar $googleCalendarService, $calendarId): array
{
    $holidaysFromAPI = [];

    /** @var Google_Service_Calendar_Event $event */
    foreach ($googleCalendarService->events->listEvents($calendarId)->getItems() as $event) {
        $date = new \DateTimeImmutable($event->getStart()->date);

        $found = false;
        foreach ($holidaysFromAPI as &$year) {
            if ($year['year'] === (int)$date->format('Y')) {
                $year['holidays'][] = ['date' => $event->getStart()->date, 'name' => $event->summary];
                $found = true;
            }
        }

        if (!$found) {
            $holidaysFromAPI[] = [
                'year' => (int)$date->format('Y'),
                'holidays' => [
                    ['date' => $event->getStart()->date, 'name' => $event->summary]
                ]
            ];
        }
    }

    return $holidaysFromAPI;
}

/**
 * @param array $holidaysFromAPI
 * @return array
 */
function sortHolidaysByDate(array $holidaysFromAPI) : array
{
    foreach ($holidaysFromAPI as &$holidaysYear) {
        \uasort(
            $holidaysYear['holidays'],
            function (array $holidayA, array $holidayB): int {
                return new \DateTimeImmutable($holidayA['date']) <=> new \DateTimeImmutable($holidayB['date']);
            }
        );

        $holidaysYear['holidays'] = \array_values($holidaysYear['holidays']);
    }

    return $holidaysFromAPI;
}

/**
 * @param $countryCode
 * @param array $holidaysFromAPI
 * @param GregorianCalendar $calendar
 */
function takeOldHolidaysFromExistingFile(string $countryCode, array $holidaysFromAPI, GregorianCalendar $calendar): array
{
    if (\file_exists(__DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/' . $countryCode . '.json')) {
        $holidaysFromFile = \json_decode(
            \file_get_contents(__DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/' . $countryCode . '.json'),
            true
        );

        foreach ($holidaysFromFile['google_calendar'][0]['years'] as $holidaysFromFileYear) {
            $fileYear = new Year($holidaysFromFileYear['year']);

            foreach ($holidaysFromAPI as &$holidaysFromAPIYear) {
                $apiYear = new Year((int)$holidaysFromAPIYear['year']);

                if ($fileYear->isEqual($apiYear)) {

                    if ($fileYear->isBefore($calendar->currentYear())) {
                        $holidaysFromAPIYear = $holidaysFromFileYear;
                    }
                }
            }
        }
    }

    return $holidaysFromAPI;
}