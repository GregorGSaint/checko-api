<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Checko;

use CheckoApi\Checko\Exception\InvalidResponseException;
use CheckoApi\Checko\Mapping\CompanyMapper;
use CheckoApi\Checko\Mapping\Row;
use CheckoApi\Domain\Company\BranchKind;
use CheckoApi\Domain\Company\FounderKind;
use CheckoApi\Tests\Support\Fixture;
use PHPUnit\Framework\TestCase;

final class CompanyMapperTest extends TestCase
{
    private CompanyMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new CompanyMapper();
    }

    public function testMapsCompanyCard(): void
    {
        $company = $this->mapper->map($this->data('company.ok.full.json'));

        self::assertSame('1027700132195', $company->ogrn);
        self::assertSame('ПАО СБЕРБАНК', $company->shortName);
        self::assertTrue($company->isActive());
        self::assertSame('Действует', $company->status->name);
        self::assertSame('64.19', $company->mainActivity?->code);
        self::assertCount(2, $company->additionalActivities);
        self::assertSame('Публичные акционерные общества', $company->legalForm?->name);
        self::assertSame(67_760_844_000.0, $company->capital?->amount);
        self::assertSame(16, $company->address?->massRegistrationCount);
        self::assertSame(['sberbank@sberbank.ru'], $company->contacts->emails);
    }

    public function testMapsManagers(): void
    {
        $manager = $this->mapper->map($this->data('company.ok.full.json'))->managers[0];

        self::assertSame('Греф Герман Оскарович', $manager->name);
        self::assertSame('ПРЕЗИДЕНТ, ПРЕДСЕДАТЕЛЬ ПРАВЛЕНИЯ', $manager->position);
        self::assertFalse($manager->accessRestricted);
    }

    public function testCountsHeavyCollectionsInsteadOfEmbeddingThem(): void
    {
        $company = $this->mapper->map($this->data('company.ok.full.json'));

        self::assertSame(['trademarks' => 584, 'licenses' => 15, 'branches' => 88, 'founders' => 0], $company->collectionSizes);
        self::assertArrayNotHasKey('trademarks', $company->jsonSerialize());
    }

    public function testRestrictedAccessKeepsRecordWithoutPersonalData(): void
    {
        $data = $this->data('company.ok.restricted.json');
        $company = $this->mapper->map($data);
        $founders = $this->mapper->founders($data);

        self::assertTrue($company->managers[0]->accessRestricted);
        self::assertNull($company->managers[0]->name);
        self::assertCount(2, $founders);
        self::assertSame(FounderKind::RussianCompany, $founders[0]->kind);
        self::assertTrue($founders[0]->accessRestricted);
        self::assertNull($founders[0]->name);
        self::assertSame('131', $company->status->code);
        self::assertSame('2026-09-21', $company->status->since);
    }

    public function testMapsSubResources(): void
    {
        $data = $this->data('company.ok.full.json');

        $branches = $this->mapper->branches($data);
        $trademark = $this->mapper->trademarks($data)[0];
        $license = $this->mapper->licenses($data)[0];

        self::assertSame(BranchKind::Branch, $branches[0]->kind);
        self::assertSame('БАЙКАЛЬСКИЙ БАНК ПАО СБЕРБАНК', $branches[0]->name);
        self::assertSame(BranchKind::RepresentativeOffice, $branches[87]->kind);
        self::assertSame(1256379, $trademark->id);
        self::assertSame('02970', $license->number);
        self::assertSame('2033-09-01', $license->validUntil);
    }

    public function testMissingOgrnIsBrokenContract(): void
    {
        $this->expectException(InvalidResponseException::class);

        $this->mapper->map(Row::of(['ИНН' => '7707083893']));
    }

    private function data(string $fixture): Row
    {
        return Row::of(Fixture::json($fixture)['data'] ?? null);
    }
}
