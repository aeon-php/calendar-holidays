<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

final class FlattenHolidaysTransformer implements Transformer
{
    public function transform(Rows $rows) : Rows
    {
        return $rows
            ->map(function (Row $row) : Row {
                return new Row(
                    new Entries(
                        $row->get('country_code'),
                        new StringEntry('date', $row->valueOf('date')->toString()),
                        $row->get('name'),
                    )
                );
            });
    }
}