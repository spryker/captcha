<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Client\Captcha;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaWidgetTransfer;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaChallengeUrlProviderPluginInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Shared\Kernel\Transfer\Exception\RequiredTransferPropertyException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Client
 * @group Captcha
 * @group CreateChallengeTest
 * Add your own group annotations below this line
 */
class CreateChallengeTest extends Unit
{
    protected const string TAG_NAME = 'test-widget';

    protected const string CHALLENGE_URL = '/captcha/challenge?context=customer';

    protected CaptchaClientTester $tester;

    public function testDelegatesRequestToProviderAndReturnsItsResponse(): void
    {
        // Arrange
        $captchaChallengeRequestTransfer = (new CaptchaChallengeRequestTransfer())->setContext(CaptchaClientTester::CONTEXT);
        $providerResponseTransfer = (new CaptchaChallengeResponseTransfer())
            ->setIsSuccessful(true)
            ->setWidget($this->createWidget());
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->once())
            ->method('createChallenge')
            ->with($captchaChallengeRequestTransfer)
            ->willReturn($providerResponseTransfer);
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($captchaChallengeRequestTransfer);

        // Assert
        $this->assertSame($providerResponseTransfer, $captchaChallengeResponseTransfer);
    }

    public function testCreatesTheChallengeUrlWidgetWhenTheProviderFetchesItsChallengeFromAUrl(): void
    {
        // Arrange
        $captchaChallengeRequestTransfer = (new CaptchaChallengeRequestTransfer())
            ->setContext(CaptchaClientTester::CONTEXT)
            ->setChallengeUrl(static::CHALLENGE_URL);
        $challengeUrlWidgetTransfer = $this->createWidget()->setAttributes(['challenge' => static::CHALLENGE_URL]);
        $captchaProviderPluginMock = $this->createMockForIntersectionOfInterfaces([
            CaptchaProviderPluginInterface::class,
            CaptchaChallengeUrlProviderPluginInterface::class,
        ]);
        $captchaProviderPluginMock->expects($this->never())->method('createChallenge');
        $captchaProviderPluginMock->expects($this->once())
            ->method('createChallengeUrlWidget')
            ->with($captchaChallengeRequestTransfer)
            ->willReturn((new CaptchaChallengeResponseTransfer())->setIsSuccessful(true)->setWidget($challengeUrlWidgetTransfer));
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($captchaChallengeRequestTransfer);

        // Assert
        $this->assertSame($challengeUrlWidgetTransfer, $captchaChallengeResponseTransfer->getWidget());
    }

    public function testCreatesTheChallengeInlineWhenTheProviderCannotFetchItFromAUrl(): void
    {
        // Arrange
        $captchaChallengeRequestTransfer = (new CaptchaChallengeRequestTransfer())
            ->setContext(CaptchaClientTester::CONTEXT)
            ->setChallengeUrl(static::CHALLENGE_URL);
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->once())
            ->method('createChallenge')
            ->willReturn((new CaptchaChallengeResponseTransfer())->setIsSuccessful(true)->setWidget($this->createWidget()));
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($captchaChallengeRequestTransfer);

        // Assert
        $this->assertTrue($captchaChallengeResponseTransfer->getIsSuccessful());
    }

    public function testReturnsUnavailableWithoutCallingProviderWhenDisabled(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->expects($this->never())->method('createChallenge');
        $this->tester->haveCaptchaEnabled(false);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge(
            (new CaptchaChallengeRequestTransfer())->setContext(CaptchaClientTester::CONTEXT),
        );

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertNull($captchaChallengeResponseTransfer->getWidget());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    public function testReturnsUnavailableAndLogsErrorWhenEnabledWithoutProvider(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge(
            (new CaptchaChallengeRequestTransfer())->setContext(CaptchaClientTester::CONTEXT),
        );

        // Assert
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }

    public function testRequiresContext(): void
    {
        // Arrange
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($this->createMock(CaptchaProviderPluginInterface::class));

        // Assert
        $this->expectException(RequiredTransferPropertyException::class);

        // Act
        $this->tester->getCaptchaClient()->createChallenge(new CaptchaChallengeRequestTransfer());
    }

    public function testReturnsUnavailableAndLogsErrorWhenProviderThrows(): void
    {
        // Arrange
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('createChallenge')->willThrowException(new RuntimeException('Vendor unreachable'));
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeResponseTransfer);
    }

    public function testAcceptsWidgetWithCustomElementDataAttributesAndHttpsScripts(): void
    {
        // Arrange
        $captchaWidgetTransfer = (new CaptchaWidgetTransfer())
            ->setTagName(static::TAG_NAME)
            ->setLoaderTagName('test-widget-loader')
            ->setNameAttribute('data-response-field-name')
            ->setAttributes(['data-sitekey' => '0x4AAA', 'aria-label' => 'Security check'])
            ->addScriptUrl('https://challenges.example.com/api.js?render=explicit');
        $this->haveProviderReturningWidget($captchaWidgetTransfer);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertTrue($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertSame($captchaWidgetTransfer, $captchaChallengeResponseTransfer->getWidget());
    }

    /**
     * @dataProvider provideUnsafeWidgets
     */
    public function testReturnsUnavailableAndLogsErrorForUnsafeWidget(CaptchaWidgetTransfer $captchaWidgetTransfer): void
    {
        // Arrange
        $this->haveProviderReturningWidget($captchaWidgetTransfer);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->once())->method('error');
        $this->tester->haveCaptchaLogger($loggerMock);

        // Act
        $captchaChallengeResponseTransfer = $this->tester->getCaptchaClient()->createChallenge($this->tester->createCaptchaChallengeRequest());

        // Assert
        $this->assertUnavailable($captchaChallengeResponseTransfer);
    }

    /**
     * @return array<string, array<\Generated\Shared\Transfer\CaptchaWidgetTransfer>>
     */
    public function provideUnsafeWidgets(): array
    {
        return [
            'tag name with an attribute' => [$this->createWidget()->setTagName('div onclick=alert(1)')],
            'tag name in upper case' => [$this->createWidget()->setTagName('TEST-WIDGET')],
            'loader tag name with a space' => [$this->createWidget()->setLoaderTagName('test loader')],
            'name attribute with a quote' => [$this->createWidget()->setNameAttribute('name"')],
            'event handler attribute' => [$this->createWidget()->setAttributes(['onfocus' => 'alert(1)'])],
            'attribute name with a space' => [$this->createWidget()->setAttributes(['x autofocus' => ''])],
            'plain http script' => [$this->createWidget()->addScriptUrl('http://challenges.example.com/api.js')],
            'relative script' => [$this->createWidget()->addScriptUrl('/assets/api.js')],
            'scheme-relative script' => [$this->createWidget()->addScriptUrl('//challenges.example.com/api.js')],
        ];
    }

    protected function createWidget(): CaptchaWidgetTransfer
    {
        return (new CaptchaWidgetTransfer())
            ->setTagName(static::TAG_NAME)
            ->setNameAttribute('name');
    }

    protected function haveProviderReturningWidget(CaptchaWidgetTransfer $captchaWidgetTransfer): void
    {
        $captchaProviderPluginMock = $this->createMock(CaptchaProviderPluginInterface::class);
        $captchaProviderPluginMock->method('createChallenge')->willReturn(
            (new CaptchaChallengeResponseTransfer())->setIsSuccessful(true)->setWidget($captchaWidgetTransfer),
        );
        $this->tester->haveCaptchaEnabled(true);
        $this->tester->haveCaptchaProviderPlugin($captchaProviderPluginMock);
    }

    protected function assertUnavailable(CaptchaChallengeResponseTransfer $captchaChallengeResponseTransfer): void
    {
        $this->assertFalse($captchaChallengeResponseTransfer->getIsSuccessful());
        $this->assertNull($captchaChallengeResponseTransfer->getWidget());
        $this->assertSame(
            CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE,
            $captchaChallengeResponseTransfer->getMessages()->offsetGet(0)->getValue(),
        );
    }
}
