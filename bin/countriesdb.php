<?php

require_once __DIR__ . '/../vendor/autoload.php';


if (!\is_string(\getenv('GOOGLE_API_KEY'))) {
    die('Please run this script by passing GOOGLE_API_KEY through env variable first.');
}

$client = new \Google_Client();
$client->setApplicationName("Google Holidays Calendar Scraper");

// Setup one at https://console.developers.google.com/
$client->setDeveloperKey(getenv('GOOGLE_API_KEY'));

$calendar = new \Google_Service_Calendar($client);

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

    $holidays = [];

    $calendarId = \str_replace('{{ locale }}', 'en', $countryData['googleHolidaysCalendarId']);

    try {
        /** @var Google_Service_Calendar_Event $event */
        foreach ($calendar->events->listEvents($calendarId)->getItems() as $event) {
            $date = new \DateTimeImmutable($event->getStart()->date);

            $found = false;
            foreach ($holidays as &$holidaysYear) {
                if ($holidaysYear['year'] === (int)$date->format('Y')) {
                    $holidaysYear['holidays'][] = ['date' => $event->getStart()->date, 'name' => $event->summary];
                    $found = true;
                }
            }

            if (!$found) {
                $holidays[] = [
                    'year' => (int)$date->format('Y'),
                    'holidays' => [
                        ['date' => $event->getStart()->date, 'name' => $event->summary]
                    ]
                ];
            }
        }


        \file_put_contents(__DIR__ . '/../src/Aeon/Calendar/Gregorian/Holidays/data/regional/' . $countryCode . '.json', \json_encode([
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
        ]));
    } catch (Google_Service_Exception $e) {
        echo "google calendar not found for - $countryCode \n";
    }
}