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
use Flow\ETL\Adapter\JSON\JSONMachine\JsonExtractor;
use Flow\ETL\Adapter\JSON\JSONMachineExtractor;
use Flow\ETL\DSL\Json;
use Flow\ETL\DSL\To;
use Flow\ETL\ETL;
use Flow\ETL\Flow;
use Flow\ETL\Loader\MemoryLoader;
use Flow\ETL\Memory\ArrayMemory;
use Flow\ETL\Stream\LocalFile;
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


(new Flow())
    ->read(Json::from(new LocalFile(__DIR__ . '/../resources/countries.json')))
    ->transform(new ArrayUnpackTransformer('row'))
    ->transform(new KeepEntriesTransformer('countryCode', 'googleHolidaysCalendarId'))
    ->write(To::memory($countries = new ArrayMemory()))
    ->run();

$holidaysFilesPath = __DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/';

(new Flow())
    ->read(new GoogleCalendarEventsExtractor($countries->dump(), $googleCalendarService))
    ->transform(new GoogleCalendarEventsTransformer())
    ->transform(new FilterHistoricalHolidaysTransformer($calendar, $holidaysFilesPath))
    ->transform(new UpdateFutureHolidaysTransformer($calendar, $holidaysFilesPath))
    ->transform(new SortHolidaysTransformer())
    ->transform(new FlattenHolidaysTransformer())
    ->write(new HolidaysJsonLoader($holidaysFilesPath))
    ->run();
