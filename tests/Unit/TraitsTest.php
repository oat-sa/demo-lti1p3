<?php

namespace App\Tests\Unit;

use OAT\Library\Lti1p3Core\Tests\Traits\DomainTestingTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class TraitsTest extends KernelTestCase
{
    use DomainTestingTrait;

    /**
     * Test to ensure core lib testing traits can be used into an SF application
     */
    public function testStuff(): void
    {
        $registration = $this->createTestRegistration();

        $token = $this->buildJwt(
            [],
            [
                'some' => 'value'
            ],
            $registration->getPlatformKeyChain()->getPrivateKey()
        );

        $parsedToken = $this->parseJwt($token->toString());

        $this->assertTrue($this->verifyJwt($parsedToken, $registration->getPlatformKeyChain()->getPublicKey()));
        $this->assertEquals('value', $parsedToken->getClaims()->get('some'));
    }
}
