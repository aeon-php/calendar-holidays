<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Flow\ETL\Extractor;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\ObjectEntry;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;

final class GoogleCalendarEventsExtractor implements Extractor
{
    private array $countriesData;

    private \Google_Service_Calendar $googleCalendarService;

    public function __construct(array $countriesData, \Google_Service_Calendar $googleCalendarService)
    {
        $this->countriesData = $countriesData;
        $this->googleCalendarService = $googleCalendarService;
    }

    public function extract() : \Generator
    {
        foreach ($this->countriesData['countries'] as $countryCode => $countryData) {
            if (!isset($countryData['googleHolidaysCalendarId'])) {
                continue;
            }
            $calendarId = \str_replace('{{ locale }}', 'en', $countryData['googleHolidaysCalendarId']);

            $rows = new Rows();

            try {
                $items = $this->googleCalendarService->events->listEvents($calendarId)->getItems();
            } catch (\Google\Service\Exception $e) {
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