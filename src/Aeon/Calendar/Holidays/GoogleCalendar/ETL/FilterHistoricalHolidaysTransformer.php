<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Aeon\Calendar\Gregorian\Calendar;
use Flow\ETL\Row;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

final class FilterHistoricalHolidaysTransformer implements Transformer
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

        if (!\file_exists($filePath)) {
            return $rows;
        }

        return $rows->filter(function (Row $row) : bool {
            return $row->valueOf('date')->isAfterOrEqual($this->calendar->currentDay());
        });
    }
}