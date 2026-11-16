<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Captcha\Communication\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Zed\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\CaptchaCommunicationFactory;
use Spryker\Zed\Captcha\Communication\Form\CaptchaType;
use Spryker\Zed\Captcha\Communication\Plugin\AgentSecurityMerchantPortalGui\AgentMerchantPortalLoginFormCaptchaExpanderPlugin;
use Spryker\Zed\Captcha\Communication\Plugin\SecurityGui\UserLoginFormCaptchaExpanderPlugin;
use Spryker\Zed\Captcha\Communication\Plugin\SecurityMerchantPortalGui\MerchantPortalLoginFormCaptchaExpanderPlugin;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use SprykerTest\Zed\Captcha\CaptchaCommunicationTester;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Captcha
 * @group Communication
 * @group Plugin
 * @group LoginFormCaptchaExpanderPluginTest
 * Add your own group annotations below this line
 */
class LoginFormCaptchaExpanderPluginTest extends Unit
{
    protected const string LAST_USERNAME = 'admin@spryker.com';

    protected CaptchaCommunicationTester $tester;

    public function testBackOfficePluginAddsTheFieldWithItsContextInTheAlwaysMode(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->once())
            ->method('add')
            ->with(SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA, CaptchaType::class, [CaptchaType::OPTION_CAPTCHA_CONTEXT => SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN])
            ->willReturnSelf();

        // Act
        $this->createPlugin(new UserLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, true)->expand($formBuilderMock, []);
    }

    /**
     * @dataProvider provideMerchantPortalPlugins
     */
    public function testMerchantPortalPluginsLeaveTheWidgetScriptsToTheMerchantPortalBuild(AbstractPlugin $plugin, string $context): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->once())
            ->method('add')
            ->with(SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA, CaptchaType::class, [
                CaptchaType::OPTION_CAPTCHA_CONTEXT => $context,
                CaptchaType::OPTION_INCLUDE_SCRIPT_ASSETS => false,
            ])
            ->willReturnSelf();

        // Act
        $this->createPlugin($plugin, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, true)->expand($formBuilderMock, []);
    }

    /**
     * @return array<string, array{\Spryker\Zed\Kernel\Communication\AbstractPlugin, string}>
     */
    public function provideMerchantPortalPlugins(): array
    {
        return [
            'Merchant Portal' => [new MerchantPortalLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_USER_LOGIN],
            'Merchant Portal agent' => [new AgentMerchantPortalLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CONTEXT_MERCHANT_PORTAL_AGENT_LOGIN],
        ];
    }

    public function testPluginAddsNothingWhenCaptchaIsDisabled(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->never())->method('add');

        // Act
        $this->createPlugin(new UserLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, false)->expand($formBuilderMock, []);
    }

    public function testAfterFailedAttemptsModeAddsNothingWhileNoPluginRequiresACaptcha(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->never())->method('add');

        // Act
        $this->createPlugin(new UserLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, true, [$this->createCaptchaRequirementPlugin(false)])
            ->expand($formBuilderMock, []);
    }

    public function testAfterFailedAttemptsModeAsksThePluginsWithTheLastUsernameOfTheSession(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);
        $formBuilderMock->method('add')->willReturnSelf();
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);

        // Assert
        $captchaRequirementPluginMock->expects($this->once())
            ->method('isCaptchaRequired')
            ->with($this->callback(fn (CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool => $captchaRequirementRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_BACKOFFICE_USER_LOGIN
                && $captchaRequirementRequestTransfer->getLoginIdentifier() === static::LAST_USERNAME))
            ->willReturn(true);
        $formBuilderMock->expects($this->once())->method('add');

        // Act
        $this->createPlugin(new UserLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, true, [$captchaRequirementPluginMock])
            ->expand($formBuilderMock, []);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function createPlugin(AbstractPlugin $plugin, string $captchaMode, bool $isCaptchaEnabled, array $captchaRequirementPlugins = []): AbstractPlugin
    {
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('isCaptchaEnabled')->willReturn($isCaptchaEnabled);

        $request = Request::create('/security-gui/login');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, static::LAST_USERNAME);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClientMock);
        $this->tester->setDependency(CaptchaDependencyProvider::SERVICE_REQUEST_STACK, $requestStack);
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaCommunicationFactory = new CaptchaCommunicationFactory();
        $captchaCommunicationFactory->setConfig($this->createConfiguredMock(CaptchaConfig::class, ['getCaptchaMode' => $captchaMode]));
        $plugin->setFactory($captchaCommunicationFactory);

        return $plugin;
    }

    protected function createCaptchaRequirementPlugin(bool $isCaptchaRequired): CaptchaRequirementPluginInterface
    {
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);
        $captchaRequirementPluginMock->method('isCaptchaRequired')->willReturn($isCaptchaRequired);

        return $captchaRequirementPluginMock;
    }
}
