<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use function Flow\ETL\DSL\array_to_row;
use function Flow\ETL\DSL\rows;
use function Flow\ETL\DSL\string_entry;
use Flow\ETL\Extractor;
use Flow\ETL\FlowContext;

final class GoogleCalendarEventsExtractor implements Extractor
{
    private array $countriesData;

    private \Google_Service_Calendar $googleCalendarService;

    /**
     * @param array<array{countryCode: string, googleHolidaysCalendarId: string}> $countriesData
     * @param \Google_Service_Calendar $googleCalendarService
     */
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

            try {
                $items = $this->googleCalendarService->events->listEvents($calendarId)->getItems();
            } catch (\Google\Service\Exception $e) {
                print "Error[{$countryCode}]: " . $e->getMessage() . "\n";

                continue;
            }

            foreach ($items as $event) {
                $signal = yield rows(
                    array_to_row(
                        \json_decode(\json_encode($event->toSimpleObject()), true),
                        $context->entryFactory(),
                    )
                    ->add(string_entry('locale', 'en'))
                    ->add(string_entry('country_code', \strtoupper($countryCode)))
                );

                if ($signal === Extractor\Signal::STOP) {
                    return;
                }
            }
        }
    }
}
