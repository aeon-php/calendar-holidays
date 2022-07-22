<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use Flow\ETL\FlowContext;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

/**
 * @implements Transformer<array<mixed>>
 */
final class FlattenHolidaysTransformer implements Transformer
{
    public function __serialize() : array
    {
        return [];
    }

    public function __unserialize(array $data) : void
    {
    }

    public function transform(Rows $rows, FlowContext $context) : Rows
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
