<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Aeon\Calendar\Gregorian\Calendar;
use Aeon\Calendar\Gregorian\Day;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\ObjectEntry;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

final class UpdateFutureHolidaysTransformer implements Transformer
{
    private Calendar $calendar;

    private string $holidaysFilesPath;

    public function __construct(Calendar $calendar, string $holidaysFilesPath)
    {
        $this->calendar = $calendar;
        $this->holidaysFilesPath = $holidaysFilesPath;
    }

    public function transform(Rows $rows) : Rows
    {
        $filePath = "{$this->holidaysFilesPath}{$rows->first()->valueOf('country_code')}.json";

        if (\file_exists($filePath)) {
            $holidaysData = \json_decode(\file_get_contents($filePath), true);

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
                        )
                    )
                );
            }
        }

        return $rows;
    }
}
