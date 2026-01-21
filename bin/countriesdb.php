#!/usr/bin/env php
<?php

use Aeon\Calendar\Gregorian\GregorianCalendar;
use Aeon\GoogleCalendar\ETL\GoogleCalendarEventsExtractor;
use function Flow\ETL\Adapter\JSON\from_json;
use function Flow\ETL\Adapter\JSON\to_json;
use function Flow\ETL\DSL\config_builder;
use function Flow\ETL\DSL\df;
use function Flow\ETL\DSL\filesystem_cache;
use function Flow\ETL\DSL\from_cache;
use function Flow\ETL\DSL\lit;
use function Flow\ETL\DSL\overwrite;
use function Flow\ETL\DSL\ref;
use function Flow\Filesystem\DSL\native_local_filesystem;
use function Flow\Filesystem\DSL\path;
use function Flow\Types\DSL\type_date;
use function Flow\Types\DSL\type_integer;

$monorepoAutoload = __DIR__ . '/../../../vendor/autoload.php';
$vendorAutoload = __DIR__ . '/../../../autoload.php';

if (file_exists($monorepoAutoload)) {
    require_once $monorepoAutoload;
} elseif (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    die('Please run "composer install" first.');
}

if (!\is_string(\getenv('GOOGLE_API_KEY'))) {
    die('Please run this script by passing GOOGLE_API_KEY through env variable first.');
}

$options = getopt('', ['force', 'path:']);
$forceRefresh = isset($options['force']);
$cachePath = path(__DIR__ . '/../var/google_holidays');

if ($forceRefresh) {
    native_local_filesystem()->rm($cachePath);
    echo "Cache cleared.\n";
}

$googleApiClient = new Google_Client();
$googleApiClient->setApplicationName('Google Holidays Calendar Scraper');

// Setup one at https://console.developers.google.com/
$googleApiClient->setDeveloperKey(\getenv('GOOGLE_API_KEY'));

$googleCalendarService = new Google_Service_Calendar($googleApiClient);
$calendar = GregorianCalendar::UTC();


$countriesData = df()
    ->read(from_json(__DIR__ . '/../resources/countries.json'))
    ->select('countryCode', 'googleHolidaysCalendarId')
    ->fetch()
    ->toArray();

$holidaysFilesPath = path($options['path'] ?? __DIR__ . '/../src/Aeon/Calendar/Holidays/data/regional/google_calendar/');

$today = lit($calendar->now()->toDateTimeImmutable())->cast(type_date());

df(config_builder()->cache(filesystem_cache($cachePath)))
    ->read(
        from_cache(
            'google_holidays',
            new GoogleCalendarEventsExtractor($countriesData, $googleCalendarService)
        )
    )
    ->collect()
    ->cache('google_holidays')
    ->mode(overwrite())
    ->select('locale', 'country_code', 'summary', 'start')
    ->withEntry('start_date', ref('start')->arrayGet('date'))
    ->drop('start')
    ->withEntry('year', ref('start_date')->cast(type_date())->dateFormat('Y')->cast(type_integer()))
    ->rename('summary', 'name')
    ->withEntry('date', ref('start_date')->cast(type_date()))
    ->filter(ref('date')->lessThanEqual($today))
    ->select('country_code', 'date', 'name')
    ->sortBy(ref('date'))
    ->partitionBy(ref('country_code'))
    ->write(to_json($holidaysFilesPath->suffix('/holidays.json')))
    ->run();
