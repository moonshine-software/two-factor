<?php

namespace MoonShine\TwoFactor\Tests\Unit;

use MoonShine\TwoFactor\TwoFactorProvider;
use PHPUnit\Framework\TestCase;

class Google2FACompatibilityTest extends TestCase
{
    private TwoFactorProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new TwoFactorProvider();
    }

    public function testGeneratesSecretKey(): void
    {
        $secret = $this->provider->generateSecretKey();

        $this->assertIsString($secret);
        $this->assertNotEmpty($secret);
        $this->assertGreaterThanOrEqual(16, strlen($secret));
    }

    public function testVerifiesSecret(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $code = '123456';

        $result = $this->provider->verify($secret, $code);

        $this->assertIsBool($result);
    }

    public function testGeneratesQrCodeUrl(): void
    {
        $url = $this->provider->qrCodeUrl('TestCompany', 'test@example.com', 'JBSWY3DPEHPK3PXP');

        $this->assertIsString($url);
        $this->assertNotEmpty($url);
        $this->assertStringContainsString('otpauth://totp', $url);
    }
}
