<?php

declare(strict_types=1);

namespace Aeon\Calendar\Tests\Unit\Holidays;

use Aeon\Calendar\Gregorian\Holidays\HolidayLocaleName;
use PHPUnit\Framework\TestCase;

final class HolidayLocaleNameTest extends TestCase
{
    public function test_in_locale() : void
    {
        $localeName = new HolidayLocaleName('ßT', 'Lörem');

        $this->assertTrue($localeName->in('ßt'));
    }
}
