# Aeon

[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/aeon-php/calendar-holidays/license)](//packagist.org/packages/aeon-php/calendar-holidays)
![Tests](https://github.com/aeon-php/calendar-holidays/workflows/Tests/badge.svg?branch=1.x)

Time Management Framework for PHP

> The word aeon /ˈiːɒn/, also spelled eon (in American English), originally meant "life", "vital force" or "being", 
> "generation" or "a period of time", though it tended to be translated as "age" in the sense of "ages", "forever", 
> "timeless" or "for eternity".

[Source: Wikipedia](https://en.wikipedia.org/wiki/Aeon) 

This library provides simple but really flexible abstraction representing holidays of any type.

```php
<?php
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays\HolidayName;

interface Holidays
{
    public function isHoliday(Day $day) : bool;

    /**
     * @return array<int, Holiday>
     */
    public function holidaysAt(Day $day) : array;
}

final class Holiday
{
    public function __construct(Day $day, HolidayName $name) {}

    public function day() : Day {}

    public function name(?string $locale = null) : string {}
}
``` 

## Holidays Providers

#### Holidays Chain 

This implementation does not provide any holidays, it's just merging holidays from other implementations returning 
an array of holidays in `holidaysAt`. 

```php
<?php
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays\GoogleCalendar\CountryCodes;
use Aeon\Calendar\Gregorian\Holidays\GoogleCalendarRegionalHolidays;
use Aeon\Calendar\Gregorian\Holidays\HolidaysChain;

$holidays = new HolidaysChain(
    new GoogleCalendarRegionalHolidays(CountryCodes::US),
    new CustomHolidays()
);

if ($holidays->isHoliday(Day::fromString('2020-01-01'))) {
    echo $holidays->holidaysAt(Day::fromString('2020-01-01'))[0]->name(); // New Year's Day
}
```

**Heads Up** It returns holidays from all chained implementations so results might be duplicated.  

#### Google Calendar Regional Holidays

This implementations uses google calendar api to get holidays for different countries.

```php
<?php
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays\GoogleCalendar\CountryCodes;
use Aeon\Calendar\Gregorian\Holidays\GoogleCalendarRegionalHolidays;

$holidays = new GoogleCalendarRegionalHolidays(CountryCodes::US);

if ($holidays->isHoliday(Day::fromString('2020-01-01'))) {
    echo $holidays->holidaysAt(Day::fromString('2020-01-01'))[0]->name(); // New Year's Day
}
``` 

GoogleCalendarRegionalHolidays reads data from static json [regional data files](src/Aeon/Calendar/Gregorian/Holidays/data/regional)
and it only reads the file matching country code passed to the constructor. 

**Lazy initialization**  - when `GoogleCalendarRegionalHolidays` is initialized it does not read the json files yet,
it only validates that country code is valid. It goes to the file first time you use `holidaysAt` or `isHoliday` and 
even then the file is [memoized](https://en.wikipedia.org/wiki/Memoization#:~:text=In%20computing%2C%20memoization%20or%20memoisation,the%20same%20inputs%20occur%20again.) 
and stored as `GoogleCalendarRegionalHolidays` instance state so another usage of 
any above method will not trigger parsing json files again.

#### Empty Holidays

This implementation does not provide any holidays, use it when you are a robot that works whole year with any break.

```php
<?php
use Aeon\Calendar\Gregorian\Day;
use Aeon\Calendar\Gregorian\Holidays\EmptyHolidays;

$holidays = new EmptyHolidays();

if ($holidays->isHoliday(Day::fromString('2020-01-01'))) {
    // code dead as your brain after working without holidays  
}
``` 