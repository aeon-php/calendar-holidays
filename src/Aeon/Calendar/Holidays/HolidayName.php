<?php

declare(strict_types=1);

namespace Aeon\Calendar\Holidays;

use Aeon\Calendar\Exception\HolidayException;
use Aeon\Calendar\Exception\InvalidArgumentException;

/**
 * @psalm-immutable
 */
final class HolidayName
{
    /**
     * @var array<HolidayLocaleName>
     */
    private array $localeHolidayNames;

    public function __construct(HolidayLocaleName ...$localeHolidayNames)
    {
        if (\count($localeHolidayNames) === 0) {
            throw new InvalidArgumentException('Holiday should have name in at least one locale.');
        }

        $this->localeHolidayNames = $localeHolidayNames;
    }

    public function name(?string $locale = null) : string
    {
        if ($locale === null) {
            /** @phpstan-ignore-next-line */
            return \current($this->localeHolidayNames)->name();
        }

        $localeNames = \array_filter(
            $this->localeHolidayNames,
            fn (HolidayLocaleName $localeHolidayName) : bool => $localeHolidayName->in($locale)
        );

        if (!\count($localeNames)) {
            throw new HolidayException(\sprintf('Holiday "%s" does not have name in %s locale', $this->name(), $locale));
        }

        return \current($localeNames)->name();
    }

    /**
     * @return array<string>
     */
    public function locales() : array
    {
        return \array_map(
            function (HolidayLocaleName $holidayLocaleName) : string {
                return $holidayLocaleName->locale();
            },
            $this->localeHolidayNames
        );
    }
}
