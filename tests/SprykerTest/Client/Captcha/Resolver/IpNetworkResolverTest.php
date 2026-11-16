<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha\Resolver;

use Codeception\Test\Unit;
use SprykerTest\Client\Captcha\CaptchaClientTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group Resolver
 * @group IpNetworkResolverTest
 * Add your own group annotations below this line
 */
class IpNetworkResolverTest extends Unit
{
    protected CaptchaClientTester $tester;

    /**
     * @dataProvider provideIpsAndTheirNetworks
     */
    public function testResolvesTheNetworkTheRequestsOfAnIpCountFor(string $ip, string $expectedNetwork): void
    {
        // Act
        $network = $this->tester->createCaptchaFactory()->createIpNetworkResolver()->resolveNetwork($ip);

        // Assert
        $this->assertSame($expectedNetwork, $network);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function provideIpsAndTheirNetworks(): array
    {
        return [
            'IPv4 address' => ['203.0.113.5', '203.0.113.5'],
            'IPv6 address' => ['2001:db8:1:2:3:4:5:6', '2001:db8:1:2::/64'],
            'IPv6 address of the same network, in upper case' => ['2001:DB8:1:2::FFFF', '2001:db8:1:2::/64'],
            'IPv6 address with a compressed network' => ['2001:db8::1', '2001:db8::/64'],
            'IPv4-mapped IPv6 address' => ['::ffff:203.0.113.5', '203.0.113.5'],
            'no IP address' => ['unknown', 'unknown'],
            'empty IP address' => ['', ''],
        ];
    }
}
