<?php

declare(strict_types=1);

namespace Aeon\GoogleCalendar\ETL;

use Flow\ETL\Row;

/**
 * @psalm-immutable
 */
final class DateTimeComparator implements Row\Comparator
{
    public function equals(Row $row, Row $nextRow) : bool
    {
        return $row->valueOf('name') === $nextRow->valueOf('name')
            && $row->valueOf('date')->isEqual($nextRow->valueOf('date'));
    }
}
