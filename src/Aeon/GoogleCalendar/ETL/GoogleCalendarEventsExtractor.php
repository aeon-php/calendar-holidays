<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use Flow\ETL\Extractor;
use Flow\ETL\FlowContext;
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

    public function extract(FlowContext $context) : \Generator
    {
        foreach ($this->countriesData as $countryData) {
            if (!isset($countryData['googleHolidaysCalendarId']) || !isset($countryData['countryCode'])) {
                continue;
            }

            $countryCode = $countryData['countryCode'];
            $calendarId = \str_replace('{{ locale }}', 'en', $countryData['googleHolidaysCalendarId']);

            $rows = new Rows();

            try {
                $items = $this->googleCalendarService->events->listEvents($calendarId)->getItems();
            } catch (\Google\Service\Exception $e) {
                print "Error[{$countryCode}]: " . $e->getMessage() . "\n";

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

            if ($rows->count()) {
                yield $rows;
            } else {
                print "Inf[{$countryCode}]: no holidays found.\n";
            }
        }
    }
}
