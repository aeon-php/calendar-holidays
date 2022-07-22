<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays\GoogleCalendar\ETL;

use Aeon\Calendar\Gregorian\Day;
use Flow\ETL\FlowContext;
use Flow\ETL\Row;
use Flow\ETL\Row\Entries;
use Flow\ETL\Row\Entry\ObjectEntry;
use Flow\ETL\Row\Entry\StringEntry;
use Flow\ETL\Rows;
use Flow\ETL\Transformer;

/**
 * @implements Transformer<array<mixed>>
 */
final class GoogleCalendarEventsTransformer implements Transformer
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
        return $rows->map(function (Row $row) : Row {
            /** @var \Google_Service_Calendar_Event $event */
            $event = $row->valueOf('google_event');

            return new Row(
                new Entries(
                    $row->get('locale'),
                    $row->get('country_code'),
                    new ObjectEntry('year', Day::fromString($event->getStart()->date)->year()),
                    new ObjectEntry('date', Day::fromString($event->getStart()->date)),
                    new StringEntry('name', $event->summary),
                )
            );
        });
    }
}
