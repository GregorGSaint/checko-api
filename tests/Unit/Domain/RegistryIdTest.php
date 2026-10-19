<?php

declare(strict_types=1);

namespace CheckoApi\Tests\Unit\Domain;

use CheckoApi\Domain\Exception\ValidationException;
use CheckoApi\Domain\Identifier\IdentifierKind;
use CheckoApi\Domain\Identifier\RegistryId;
use PHPUnit\Framework\TestCase;

final class RegistryIdTest extends TestCase
{
    public function testCompanyAcceptsInnAndOgrn(): void
    {
        self::assertSame(IdentifierKind::Inn, RegistryId::forCompany('7707083893')->kind);
        self::assertSame(IdentifierKind::Ogrn, RegistryId::forCompany(' 1027700132195 ')->kind);
    }

    public function testEntrepreneurAcceptsInnAndOgrnip(): void
    {
        self::assertSame(IdentifierKind::Inn, RegistryId::forEntrepreneur('402907671076')->kind);
        self::assertSame(IdentifierKind::Ogrnip, RegistryId::forEntrepreneur('310402910200035')->kind);
    }

    public function testCompanyRejectsBrokenChecksum(): void
    {
        $this->expectExceptionObject(ValidationException::invalidIdentifier('ИНН 7707083894 не прошёл проверку контрольного числа'));

        RegistryId::forCompany('7707083894');
    }

    public function testCompanyPointsEntrepreneurIdToRightResource(): void
    {
        try {
            RegistryId::forCompany('402907671076');
            self::fail('Ожидалось исключение');
        } catch (ValidationException $exception) {
            self::assertSame('WRONG_RESOURCE', $exception->errorCode);
            self::assertStringContainsString('/v1/entrepreneurs/402907671076', $exception->getMessage());
        }
    }

    public function testEntrepreneurPointsCompanyIdToRightResource(): void
    {
        try {
            RegistryId::forEntrepreneur('1027700132195');
            self::fail('Ожидалось исключение');
        } catch (ValidationException $exception) {
            self::assertSame('WRONG_RESOURCE', $exception->errorCode);
            self::assertStringContainsString('/v1/companies/1027700132195', $exception->getMessage());
        }
    }

    public function testGarbageIsInvalidIdentifier(): void
    {
        $this->expectException(ValidationException::class);

        RegistryId::forCompany('123');
    }
}
