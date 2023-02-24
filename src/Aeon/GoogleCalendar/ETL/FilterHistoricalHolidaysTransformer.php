<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use Aeon\Calendar\Gregorian\Calendar;
use Flow\ETL\FlowContext;
use Flow\ETL\Row;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

/**
 * @implements Transformer<array<mixed>>
 */
final class FilterHistoricalHolidaysTransformer implements Transformer
{
    private Calendar $calendar;

    private string $holidaysFilesPath;

    public function __construct(Calendar $calendar, string $holidaysFilesPath)
    {
        $this->calendar = $calendar;
        $this->holidaysFilesPath = $holidaysFilesPath;
    }

    public function __serialize() : array
    {
        return [];
    }

    public function __unserialize(array $data) : void
    {
    }

    public function transform(Rows $rows, FlowContext $context) : Rows
    {
        if (!$rows->count()) {
            return $rows;
        }

        $filePath = "{$this->holidaysFilesPath}{$rows->first()->valueOf('country_code')}.json";

        if (!\file_exists($filePath)) {
            return $rows;
        }

        /** @psalm-suppress InvalidArgument */
        return $rows->filter(function (Row $row) : bool {
            return $row->valueOf('date')->isAfterOrEqual($this->calendar->currentDay());
        });
    }
}
