<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use Flow\ETL\FlowContext;
use Flow\ETL\Row;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

/**
 * @implements Transformer<array<mixed>>
 */
final class SortHolidaysTransformer implements Transformer
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
        return $rows->sort(function (Row $row, Row $nextRow) : int {
            if ($row->valueOf('date')->isEqual($nextRow->valueOf('date'))) {
                return $row->valueOf('name') <=> $nextRow->valueOf('name');
            }

            return $row->valueOf('date')->toDateTimeImmutable() <=> $nextRow->valueOf('date')->toDateTimeImmutable();
        })
            ->unique(new DateTimeComparator());
    }
}
