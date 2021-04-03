#!/usr/bin/env php
<?php

use Aeon\Calendar\Gregorian\Calendar;
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\GregorianCalendar;
use Aeon\Calendar\Gregorian\TimeZone;
use Flow\ETL\ETL;
use Flow\ETL\Extractor;
use Flow\ETL\Loader;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\IntegerEntry;
use Flow\ETL\Row\Entry\ObjectEntry;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

require_once __DIR__ . '/../vendor/autoload.php';

if (!\is_string(\getenv('GOOGLE_API_KEY'))) {
    die('Please run this script by passing GOOGLE_API_KEY through env variable first.');
}

$googleApiClient = new Google_Client();
$googleApiClient->setApplicationName('Google Holidays Calendar Scraper');

// Setup one at https://console.developers.google.com/
$googleApiClient->setDeveloperKey(\getenv('GOOGLE_API_KEY'));

$googleCalendarService = new Google_Service_Calendar($googleApiClient);
$calendar = GregorianCalendar::UTC();

$countries = \json_decode(\file_get_contents(__DIR__ . '/../resources/countries.json'), true);

$count = \count($countries['countries']);

$index = 0;

ETL::extract(
    new class($countries, $googleCalendarService) implements Extractor {
        private array $countriesData;

        private Google_Service_Calendar $googleCalendarService;

        public function __construct(array $countriesData, Google_Service_Calendar $googleCalendarService)
        {
            $this->countriesData = $countriesData;
            $this->googleCalendarService = $googleCalendarService;
        }

        public function extract() : Generator
        {
            foreach ($this->countriesData['countries'] as $countryCode => $countryData) {
                if (!isset($countryData['googleHolidaysCalendarId'])) {
                    continue;
                }
                $calendarId = \str_replace('{{ locale }}', 'en', $countryData['googleHolidaysCalendarId']);

                $rows = new Rows();

                try {
                    $items = $this->googleCalendarService->events->listEvents($calendarId)->getItems();
                } catch (Google\Service\Exception $e) {
                    continue;
                }

                foreach ($items as $event) {
                    $rows = $rows->add(
                        new Row(
                            new Entries(
                                new StringEntry('locale', 'en'),
                                StringEntry::uppercase('country_code', $countryCode),
                                new ObjectEntry('google_event', $event)
                            )
                        )
                    );
                }

                print "{$countryCode} - Loading...\n";

                yield $rows;
            }
        }
    }
)->transform(
    /**
     * Transform Google Calendar Event into flat data structure.
     */
    new class implements Transformer {
        public function transform(Rows $rows) : Rows
        {
            return $rows->map(function (Row $row) : Row {
                /** @var \Google_Service_Calendar_Event $event */
                $event = $row->valueOf('google_event');

                return new Row(
                    new Entries(
                        $row->get('locale'),
                        $row->get('country_code'),
                        new ObjectEntry('year', Day::fromString($event->getStart()->date)->year()),
                        new ObjectEntry('date', Day::fromString($event->getStart()->date)),
                        new StringEntry('name', $event->summary),
                        new IntegerEntry('timestamp', Day::fromString($event->getStart()->date)->midnight(TimeZone::UTC())->timestampUNIX()->inSeconds())
                    )
                );
            });
        }
    },
    /**
     * If events dataset exists, filter out all historical events from google calendar events.
     */
    new class($calendar) implements Transformer {
        private Calendar $calendar;

        public function __construct(Calendar $calendar)
        {
            $this->calendar = $calendar;
        }

        public function transform(Rows $rows) : Rows
        {
            $filePath = __DIR__ . "/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/{$rows->first()->valueOf('country_code')}.json";

            if (!\file_exists($filePath)) {
                return $rows;
            }

            return $rows->filter(function (Row $row) : bool {
                return $row->valueOf('date')->isAfterOrEqual($this->calendar->currentDay());
            });
        }
    },
    /**
     * If events dataset exists, load it and extract all future events and merge both data sets.
     */
    new class($calendar) implements Transformer {
        private Calendar $calendar;

        public function __construct(Calendar $calendar)
        {
            $this->calendar = $calendar;
        }

        public function transform(Rows $rows) : Rows
        {
            $filePath = __DIR__ . "/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/{$rows->first()->valueOf('country_code')}.json";

            if (\file_exists($filePath)) {
                $holidaysData = \json_decode(\file_get_contents($filePath), true);

                if (!\is_array($holidaysData)) {
                    \var_dump($filePath);

                    die();
                }

                foreach ($holidaysData as $holidayData) {
                    $date = Day::fromString($holidayData['date']);
                    $name = $holidayData['name'];

                    if ($date->isAfter($this->calendar->currentDay())) {
                        continue;
                    }

                    $rows = $rows->add(
                        new Row(
                            new Entries(
                                $rows->first()->get('locale'),
                                $rows->first()->get('country_code'),
                                new ObjectEntry('year', $date->year()),
                                new ObjectEntry('date', $date),
                                new StringEntry('name', $name),
                                new IntegerEntry('timestamp', $date->midnight(TimeZone::UTC())->timestampUNIX()->inSeconds())
                            )
                        )
                    );
                }
            }

            return $rows;
        }
    },
    /**
     * Sort events and map data structure into simpler structure.
     */
    new class implements Transformer {
        public function transform(Rows $rows) : Rows
        {
            return $rows->sortAscending('timestamp')
                ->map(function (Row $row) : Row {
                    return new Row(
                        new Entries(
                            $row->get('country_code'),
                            new StringEntry('date', $row->valueOf('date')->toString()),
                            $row->get('name'),
                        )
                    );
                });
        }
    },
)->load(
    new class implements Loader {
        public function load(Rows $rows) : void
        {
            $countryCode = $rows->first()->get('country_code');
            $filePath = __DIR__ . "/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/{$countryCode->value()}.json";

            $rows = $rows->map(function (Row $row) : Row {
                return new Row(
                    new Entries(
                        $row->get('date'),
                        $row->get('name'),
                    )
                );
            });

            \file_put_contents($filePath, \json_encode($rows->toArray(), JSON_PRETTY_PRINT));

            print "{$countryCode->value()} - Loaded \n";
        }
    }
);
