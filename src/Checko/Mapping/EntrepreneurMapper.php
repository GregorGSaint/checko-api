<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Domain\Entrepreneur\Entrepreneur;
use CheckoApi\Domain\Shared\RiskFlags;

final class EntrepreneurMapper
{
    public function map(Row $data): Entrepreneur
    {
        return new Entrepreneur(
            ogrnip: $data->requiredString('ОГРНИП'),
            inn: $data->requiredString('ИНН'),
            okpo: $data->string('ОКПО'),
            fullName: $data->string('ФИО'),
            type: $data->string('Тип'),
            registeredAt: $data->string('ДатаРег'),
            status: CommonFields::status($data->row('Статус')),
            termination: CommonFields::termination($data->row('Прекращ')),
            region: CommonFields::classifier($data->row('Регион')),
            locality: $data->string('НасПункт'),
            mainActivity: CommonFields::activity($data->row('ОКВЭД')),
            additionalActivities: CommonFields::activities($data, 'ОКВЭДДоп'),
            contacts: CommonFields::contacts($data->row('Контакты')),
            taxes: CommonFields::taxes($data->row('Налоги')),
            sme: CommonFields::sme($data->row('РМСП')),
            risks: new RiskFlags(
                unreliableSupplier: $data->bool('НедобПост'),
                massDirector: $data->bool('МассРуковод'),
                massFounder: $data->bool('МассУчред'),
                bankruptcyMessages: $data->count('ЕФРСБ'),
            ),
            collectionSizes: [
                'licenses' => $data->count('Лиценз'),
                'trademarks' => $data->count('ТоварЗнак'),
            ],
            extractedAt: $data->string('ДатаВып'),
        );
    }
}
