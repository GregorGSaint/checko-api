<?php

declare(strict_types=1);

namespace CheckoApi\Checko\Mapping;

use CheckoApi\Domain\Company\Address;
use CheckoApi\Domain\Company\Branch;
use CheckoApi\Domain\Company\BranchKind;
use CheckoApi\Domain\Company\Capital;
use CheckoApi\Domain\Company\Company;
use CheckoApi\Domain\Company\Founder;
use CheckoApi\Domain\Company\FounderKind;
use CheckoApi\Domain\Company\License;
use CheckoApi\Domain\Company\Manager;
use CheckoApi\Domain\Company\OrganizationRef;
use CheckoApi\Domain\Company\Trademark;
use CheckoApi\Domain\Shared\RiskFlags;

final class CompanyMapper
{
    private const FOUNDER_GROUPS = [
        'ФЛ' => FounderKind::Person,
        'РосОрг' => FounderKind::RussianCompany,
        'ИнОрг' => FounderKind::ForeignCompany,
        'ПИФ' => FounderKind::MutualFund,
        'РФ' => FounderKind::State,
    ];

    private const BRANCH_GROUPS = [
        'Филиал' => BranchKind::Branch,
        'Представ' => BranchKind::RepresentativeOffice,
    ];

    public function map(Row $data): Company
    {
        return new Company(
            ogrn: $data->requiredString('ОГРН'),
            inn: $data->requiredString('ИНН'),
            kpp: $data->string('КПП'),
            okpo: $data->string('ОКПО'),
            shortName: $data->string('НаимСокр'),
            fullName: $data->string('НаимПолн'),
            englishName: $data->string('НаимАнгл'),
            registeredAt: $data->string('ДатаРег'),
            status: CommonFields::status($data->row('Статус')),
            liquidation: CommonFields::termination($data->row('Ликвид')),
            region: CommonFields::classifier($data->row('Регион')),
            address: $this->address($data->row('ЮрАдрес')),
            mainActivity: CommonFields::activity($data->row('ОКВЭД')),
            additionalActivities: CommonFields::activities($data, 'ОКВЭДДоп'),
            legalForm: CommonFields::classifier($data->row('ОКОПФ')),
            capital: $this->capital($data->row('УстКап')),
            managers: array_map($this->manager(...), $data->rows('Руковод')),
            managingOrganization: $this->organization($data->row('УпрОрг')),
            contacts: CommonFields::contacts($data->row('Контакты')),
            taxes: CommonFields::taxes($data->row('Налоги')),
            sme: CommonFields::sme($data->row('РМСП')),
            risks: new RiskFlags(
                unreliableSupplier: $data->bool('НедобПост'),
                massDirector: $data->bool('МассРуковод'),
                massFounder: $data->bool('МассУчред'),
                bankruptcyMessages: $data->count('ЕФРСБ'),
                disqualifiedPersons: $data->bool('ДисквЛица'),
                illegalFinance: $data->bool('НелегалФин'),
                sanctions: $data->bool('Санкции'),
                sanctionedFounders: $data->bool('СанкцУчр'),
            ),
            collectionSizes: [
                'trademarks' => $data->count('ТоварЗнак'),
                'licenses' => $data->count('Лиценз'),
                'branches' => count($this->branches($data)),
                'founders' => count($this->founders($data)),
            ],
            extractedAt: $data->string('ДатаВып'),
        );
    }

    /**
     * @return list<Trademark>
     */
    public function trademarks(Row $data): array
    {
        return array_map(
            static fn(Row $row): Trademark => new Trademark(
                $row->int('ID'),
                $row->string('URL'),
                $row->string('ДатаРег'),
                $row->string('ДатаОконч'),
            ),
            $data->rows('ТоварЗнак'),
        );
    }

    /**
     * @return list<License>
     */
    public function licenses(Row $data): array
    {
        return array_map(
            static fn(Row $row): License => new License(
                $row->string('Номер'),
                $row->string('Дата'),
                $row->string('ДатаНач'),
                $row->string('ДатаОконч'),
                $row->string('ЛицОрг'),
                $row->strings('ВидДеят'),
            ),
            $data->rows('Лиценз'),
        );
    }

    /**
     * @return list<Branch>
     */
    public function branches(Row $data): array
    {
        $branches = [];

        foreach (self::BRANCH_GROUPS as $group => $kind) {
            foreach ($data->row('Подразд')->rows($group) as $row) {
                $branches[] = new Branch(
                    $kind,
                    $row->string('НаимПолн'),
                    $row->string('КПП'),
                    $row->string('Адрес') ?? $row->string('ИнАдрес'),
                    $row->string('Страна'),
                );
            }
        }

        return $branches;
    }

    /**
     * @return list<Founder>
     */
    public function founders(Row $data): array
    {
        $founders = [];

        foreach (self::FOUNDER_GROUPS as $group => $kind) {
            foreach ($data->row('Учред')->rows($group) as $row) {
                $founders[] = new Founder(
                    kind: $kind,
                    name: $this->founderName($kind, $row),
                    inn: $row->string('ИНН'),
                    ogrn: $row->string('ОГРН'),
                    shareNominal: $row->row('Доля')->float('Номинал'),
                    sharePercent: $row->row('Доля')->float('Процент'),
                    since: $row->string('ДатаЗаписи'),
                    accessRestricted: $row->bool('ОгрДоступ'),
                );
            }
        }

        return $founders;
    }

    private function founderName(FounderKind $kind, Row $row): ?string
    {
        return match ($kind) {
            FounderKind::Person => $row->string('ФИО'),
            FounderKind::RussianCompany, FounderKind::ForeignCompany => $row->string('НаимПолн') ?? $row->string('НаимСокр'),
            FounderKind::MutualFund => $row->string('Наим'),
            FounderKind::State => implode(', ', array_filter([$row->string('Тип'), $row->row('Регион')->string('Наим')])) ?: null,
        };
    }

    private function address(Row $row): ?Address
    {
        if ($row->isEmpty()) {
            return null;
        }

        return new Address(
            $row->string('АдресРФ'),
            $row->string('НасПункт'),
            $row->bool('Недост'),
            $row->count('МассАдрес'),
        );
    }

    private function capital(Row $row): ?Capital
    {
        return $row->isEmpty() ? null : new Capital($row->string('Тип'), $row->float('Сумма'));
    }

    private function manager(Row $row): Manager
    {
        return new Manager(
            name: $row->string('ФИО'),
            inn: $row->string('ИНН'),
            position: $row->string('НаимДолжн'),
            since: $row->string('ДатаЗаписи'),
            accessRestricted: $row->bool('ОгрДоступ'),
            isUnreliable: $row->bool('Недост'),
            isMassDirector: $row->bool('МассРуковод'),
            isDisqualified: $row->bool('ДисквЛицо'),
        );
    }

    private function organization(Row $row): ?OrganizationRef
    {
        if ($row->string('ОГРН') === null && $row->string('НаимПолн') === null) {
            return null;
        }

        return new OrganizationRef($row->string('ОГРН'), $row->string('ИНН'), $row->string('НаимПолн') ?? $row->string('НаимСокр'));
    }
}
