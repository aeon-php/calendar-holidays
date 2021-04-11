#!/usr/bin/env php
<?php

use Aeon\Calendar\Gregorian\GregorianCalendar;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\FilterHistoricalHolidaysTransformer;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\FlattenHolidaysTransformer;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\GoogleCalendarEventsExtractor;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\GoogleCalendarEventsTransformer;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\HolidaysJsonLoader;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\SortHolidaysTransformer;
use Aeon\Calendar\Holidays\GoogleCalendar\ETL\UpdateFutureHolidaysTransformer;
use Flow\ETL\Adapter\JSON\JSONMachineExtractor;
use Flow\ETL\ETL;
use Flow\ETL\Loader\MemoryLoader;
use Flow\ETL\Memory\ArrayMemory;
use Flow\ETL\Transformer\ArrayUnpackTransformer;
use Flow\ETL\Transformer\KeepEntriesTransformer;
use JsonMachine\JsonMachine;

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

$countries = new ArrayMemory();
$countriesExtractor = new JSONMachineExtractor(JsonMachine::fromFile(__DIR__ . '/../resources/countries.json'), 10, 'row');

ETL::extract($countriesExtractor)
    ->transform(new ArrayUnpackTransformer('row'))
    ->transform(new KeepEntriesTransformer('countryCode', 'googleHolidaysCalendarId'))
    ->load(new MemoryLoader($countries));

$holidaysFilesPath = __DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/';

ETL::extract(
    new GoogleCalendarEventsExtractor($countries->dump(), $googleCalendarService)
)->transform(
    new GoogleCalendarEventsTransformer(),
    new FilterHistoricalHolidaysTransformer($calendar, $holidaysFilesPath),
    new UpdateFutureHolidaysTransformer($calendar, $holidaysFilesPath),
    new SortHolidaysTransformer(),
    new FlattenHolidaysTransformer()
)->load(
    new HolidaysJsonLoader($holidaysFilesPath)
);
