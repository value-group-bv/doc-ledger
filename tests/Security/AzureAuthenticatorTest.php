<?php

namespace App\Tests\Security;

use App\Security\AzureAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;
use TheNetworg\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\TestCase;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;

class AzureAuthenticatorTest extends TestCase
{
    public static function claims(): iterable
    {
        yield 'given_name wins' => [['given_name' => 'Rutger', 'name' => 'R. Vollenberg'], 'Rutger'];
        yield 'name up to first space' => [['name' => 'Rutger Vollenberg'], 'Rutger'];
        yield 'single word name' => [['name' => 'Rutger'], 'Rutger'];
        yield 'blank given_name falls back' => [['given_name' => ' ', 'name' => 'Rutger van Dijk'], 'Rutger'];
        yield 'no name claims' => [[], null];
    }

    #[DataProvider('claims')]
    public function testFirstNameOf(array $claims, ?string $expected): void
    {
        $method = new \ReflectionMethod(AzureAuthenticator::class, 'firstNameOf');

        self::assertSame($expected, $method->invoke(null, new AzureResourceOwner($claims, self::createStub(AccessToken::class))));
    }
}
