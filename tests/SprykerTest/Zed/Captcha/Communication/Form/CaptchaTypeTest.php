<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Form;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\Communication\Form\CaptchaType;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Form
 * @group CaptchaTypeTest
 * Add your own group annotations below this line
 */
class CaptchaTypeTest extends Unit
{
    protected const string VENDOR_SCRIPT_URL = 'https://challenges.example.com/api.js?render=explicit&onload=init';

    protected const string PAYLOAD = 'solved-payload';

    protected const string EXPECTED_FULL_NAME = 'auth[captcha]';

    protected CaptchaCommunicationTester $tester;

    public function testFieldIsUnmappedAndUnlabelled(): void
    {
        // Arrange
        $this->tester->haveCaptchaTypeDependencies($this->createMock(CaptchaClientInterface::class));

        // Act
        $captchaFieldConfig = $this->tester->createLoginFormWithCaptchaField()
            ->get(CaptchaConfig::FORM_FIELD_NAME_CAPTCHA)
            ->getConfig();

        // Assert
        $this->assertFalse($captchaFieldConfig->getMapped());
        $this->assertFalse($captchaFieldConfig->getOption('label'));
        $this->assertTrue($captchaFieldConfig->getOption(CaptchaType::OPTION_INCLUDE_SCRIPT_ASSETS));
    }

    public function testRequiresCaptchaContextOption(): void
    {
        // Arrange
        $this->tester->haveCaptchaTypeDependencies($this->createMock(CaptchaClientInterface::class));

        // Assert
        $this->expectException(MissingOptionsException::class);

        // Act
        $this->tester->createFormFactory()->create(CaptchaType::class);
    }

    public function testBuildViewExposesWidgetOfChallengeCreatedForFieldContext(): void
    {
        // Arrange
        $captchaChallengeResponseTransfer = $this->tester->createSuccessfulCaptchaChallengeResponse();
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('createChallenge')
            ->with($this->callback(function (CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool {
                return $captchaChallengeRequestTransfer->getContext() === CaptchaCommunicationTester::CONTEXT;
            }))
            ->willReturn($captchaChallengeResponseTransfer);
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Act
        $captchaFieldView = $this->tester->createLoginFormWithCaptchaField()->createView()[CaptchaConfig::FORM_FIELD_NAME_CAPTCHA];

        // Assert
        $this->assertSame($captchaChallengeResponseTransfer->getWidget(), $captchaFieldView->vars['captchaWidget']);
        $this->assertCount(0, $captchaFieldView->vars['captchaMessages']);
        $this->assertSame(static::EXPECTED_FULL_NAME, $captchaFieldView->vars['full_name']);
    }

    public function testBuildViewRequestsTheChallengeWithTheChallengeUrlOfTheLoginForm(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Expect
        $captchaClientMock->expects($this->once())
            ->method('createChallenge')
            ->with($this->callback(fn (CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool => $captchaChallengeRequestTransfer->getChallengeUrl() === '/captcha/merchant-portal-challenge?context=merchant_portal_agent_login'))
            ->willReturn($this->tester->createSuccessfulCaptchaChallengeResponse());

        // Act
        $this->tester->createLoginFormWithCaptchaField([CaptchaType::OPTION_CAPTCHA_CONTEXT => CaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN])->createView();
    }

    public function testBuildViewRequestsAnInlineChallengeForAContextWithoutChallengeUrl(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Expect
        $captchaClientMock->expects($this->once())
            ->method('createChallenge')
            ->with($this->callback(fn (CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool => $captchaChallengeRequestTransfer->getChallengeUrl() === null))
            ->willReturn($this->tester->createSuccessfulCaptchaChallengeResponse());

        // Act
        $this->tester->createLoginFormWithCaptchaField()->createView();
    }

    public function testThemeRendersWidgetWithFullNameAsPostedFieldNameAndScriptAssets(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('createChallenge')->willReturn($this->tester->createSuccessfulCaptchaChallengeResponse());
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Act
        $html = $this->tester->renderCaptchaWidget($this->tester->createLoginFormWithCaptchaField()->createView());

        // Assert
        $this->assertStringContainsString(
            sprintf(
                '<altcha-widget challenge="%s" name="%s" data-qa="captcha-widget"></altcha-widget>',
                htmlspecialchars(CaptchaCommunicationTester::WIDGET_CHALLENGE, ENT_QUOTES),
                static::EXPECTED_FULL_NAME,
            ),
            $html,
        );
        $this->assertStringContainsString(
            sprintf('<script src="%s%s"></script>', CaptchaCommunicationTester::ASSETS_BASE_PATH, CaptchaCommunicationTester::WIDGET_SCRIPT_ASSET_PATH),
            $html,
        );
        $this->assertStringContainsString('<noscript>captcha.noscript</noscript>', $html);
        $this->assertStringNotContainsString('<captcha-altcha>', $html);
    }

    public function testThemeOmitsScriptAssetsWhenDisabledByOption(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('createChallenge')->willReturn($this->tester->createSuccessfulCaptchaChallengeResponse());
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);
        $form = $this->tester->createLoginFormWithCaptchaField([CaptchaType::OPTION_INCLUDE_SCRIPT_ASSETS => false]);

        // Act
        $html = $this->tester->renderCaptchaWidget($form->createView());

        // Assert
        $this->assertStringContainsString('data-qa="captcha-widget"', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testThemeRendersChallengeMessagesWhenChallengeIsUnavailable(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('createChallenge')->willReturn($this->tester->createUnavailableCaptchaChallengeResponse());
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Act
        $html = $this->tester->renderCaptchaWidget($this->tester->createLoginFormWithCaptchaField()->createView());

        // Assert
        $this->assertStringContainsString(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_UNAVAILABLE, $html);
        $this->assertStringNotContainsString('data-qa="captcha-widget"', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function testSubmittedPayloadIsVerifiedForFieldContext(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->expects($this->once())
            ->method('verifyCaptcha')
            ->with($this->callback(function (CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): bool {
                return $captchaVerificationRequestTransfer->getPayload() === static::PAYLOAD
                    && $captchaVerificationRequestTransfer->getContext() === CaptchaCommunicationTester::CONTEXT;
            }))
            ->willReturn((new CaptchaVerificationResponseTransfer())->setIsValid(true));
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);
        $form = $this->tester->createLoginFormWithCaptchaField();

        // Act
        $form->submit([CaptchaConfig::FORM_FIELD_NAME_CAPTCHA => static::PAYLOAD]);

        // Assert
        $this->assertTrue($form->isValid());
    }

    public function testRejectedPayloadAddsVerificationMessagesAsFieldErrors(): void
    {
        // Arrange
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('verifyCaptcha')->willReturn(
            (new CaptchaVerificationResponseTransfer())
                ->setIsValid(false)
                ->addMessage((new MessageTransfer())->setValue(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID)),
        );
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);
        $form = $this->tester->createLoginFormWithCaptchaField();

        // Act
        $form->submit([CaptchaConfig::FORM_FIELD_NAME_CAPTCHA => static::PAYLOAD]);

        // Assert
        $this->assertFalse($form->isValid());
        $captchaFieldErrors = $form->get(CaptchaConfig::FORM_FIELD_NAME_CAPTCHA)->getErrors();
        $this->assertCount(1, $captchaFieldErrors);
        $this->assertSame(CaptchaConfig::GLOSSARY_KEY_CAPTCHA_ERROR_INVALID, $captchaFieldErrors[0]->getMessage());
    }

    public function testThemeRendersVendorScriptUrlsAsyncAndDeferred(): void
    {
        // Arrange
        $captchaChallengeResponseTransfer = $this->tester->createSuccessfulCaptchaChallengeResponse();
        $captchaChallengeResponseTransfer->getWidgetOrFail()->addScriptUrl(static::VENDOR_SCRIPT_URL);
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('createChallenge')->willReturn($captchaChallengeResponseTransfer);
        $this->tester->haveCaptchaTypeDependencies($captchaClientMock);

        // Act
        $html = $this->tester->renderCaptchaWidget($this->tester->createLoginFormWithCaptchaField([CaptchaType::OPTION_INCLUDE_SCRIPT_ASSETS => false])->createView());

        // Assert
        $this->assertStringContainsString(
            sprintf('<script src="%s" async defer></script>', htmlspecialchars(static::VENDOR_SCRIPT_URL, ENT_QUOTES)),
            $html,
        );
    }
}
