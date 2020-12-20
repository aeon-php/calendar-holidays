<?php

declare(strict_types=1);

namespace Aeon\Calendar\Tests\Unit\Holidays;

use Aeon\Calendar\Exception\InvalidArgumentException;
use Aeon\Calendar\Holidays\HolidayLocaleName;
use Aeon\Calendar\Holidays\HolidayName;
use PHPUnit\Framework\TestCase;

final class HolidayNameTest extends TestCase
{
    public function test_creating_name_without_locale_names() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Holiday should have name in at least one locale.');

        new HolidayName();
    }

    public function test_locales() : void
    {
        $name = new HolidayName(
            new HolidayLocaleName('pl', 'Święto'),
            new HolidayLocaleName('en', 'holiday'),
        );

        $this->assertSame(['pl', 'en'], $name->locales());
    }
}
