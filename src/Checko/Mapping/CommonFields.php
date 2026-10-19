<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Domain\Shared\Activity;
use CheckoApi\Domain\Shared\Classifier;
use CheckoApi\Domain\Shared\Contacts;
use CheckoApi\Domain\Shared\SmeEntry;
use CheckoApi\Domain\Shared\Status;
use CheckoApi\Domain\Shared\Taxes;
use CheckoApi\Domain\Shared\Termination;

final class CommonFields
{
    public static function status(Row $row): Status
    {
        return new Status($row->string('Код'), $row->string('Наим'), $row->string('ДатаЗаписи'));
    }

    public static function termination(Row $row): ?Termination
    {
        return $row->isEmpty() ? null : new Termination($row->string('Дата'), $row->string('Наим'));
    }

    public static function classifier(Row $row): ?Classifier
    {
        $code = $row->string('Код');
        $name = $row->string('Наим');

        return $code === null && $name === null ? null : new Classifier($code, $name);
    }

    public static function activity(Row $row): ?Activity
    {
        $code = $row->string('Код');

        return $code === null ? null : new Activity($code, $row->string('Наим'), $row->string('Версия'));
    }

    /**
     * @return list<Activity>
     */
    public static function activities(Row $parent, string $key): array
    {
        return array_values(array_filter(array_map(self::activity(...), $parent->rows($key))));
    }

    public static function contacts(Row $row): Contacts
    {
        return new Contacts($row->strings('Тел'), $row->strings('Емэйл'), $row->string('ВебСайт'));
    }

    public static function taxes(Row $row): Taxes
    {
        return new Taxes(
            $row->strings('ОсобРежим'),
            $row->float('СумУпл'),
            $row->string('СведУплГод'),
            $row->float('СумНедоим'),
            $row->string('НедоимДата'),
        );
    }

    public static function sme(Row $row): ?SmeEntry
    {
        $category = $row->string('Кат');

        return $category === null ? null : new SmeEntry($category, $row->string('ДатаВкл'));
    }
}
