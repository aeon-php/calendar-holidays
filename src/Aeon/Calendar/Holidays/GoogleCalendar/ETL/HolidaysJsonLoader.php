<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Flow\ETL\Loader;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Rows;

final class HolidaysJsonLoader implements Loader
{
    private string $holidaysFilesPath;

    public function __construct(string $holidaysFilesPath)
    {
        $this->holidaysFilesPath = $holidaysFilesPath;
    }

    public function load(Rows $rows) : void
    {
        if (!$rows->count()) {
            return;
        }

        $countryCode = $rows->first()->get('country_code');
        $filePath = "{$this->holidaysFilesPath}{$rows->first()->valueOf('country_code')}.json";

        $rows = $rows->map(function (Row $row) : Row {
            return new Row(
                new Entries(
                    $row->get('date'),
                    $row->get('name'),
                )
            );
        });

        \file_put_contents(
            $filePath,
            \json_encode($rows->toArray(), JSON_PRETTY_PRINT)
        );

        print "{$countryCode->value()} - Loaded \n";
    }
}
