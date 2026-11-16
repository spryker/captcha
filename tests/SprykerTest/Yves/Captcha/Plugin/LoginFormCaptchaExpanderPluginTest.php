<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Yves\Captcha\Plugin;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;
use Spryker\Yves\Captcha\CaptchaConfig;
use Spryker\Yves\Captcha\CaptchaDependencyProvider;
use Spryker\Yves\Captcha\CaptchaFactory;
use Spryker\Yves\Captcha\Form\CaptchaType;
use Spryker\Yves\Captcha\Plugin\AgentPage\AgentLoginFormCaptchaExpanderPlugin;
use Spryker\Yves\Captcha\Plugin\CustomerPage\CustomerLoginFormCaptchaExpanderPlugin;
use Spryker\Yves\Kernel\AbstractPlugin;
use SprykerTest\Yves\Captcha\CaptchaYvesTester;
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
 * @group Yves
 * @group Captcha
 * @group Plugin
 * @group LoginFormCaptchaExpanderPluginTest
 * Add your own group annotations below this line
 */
class LoginFormCaptchaExpanderPluginTest extends Unit
{
    protected const string LAST_USERNAME = 'customer@spryker.com';

    protected CaptchaYvesTester $tester;

    /**
     * @dataProvider provideLoginFormPlugins
     */
    public function testPluginAddsTheFieldWithItsContextInTheAlwaysMode(AbstractPlugin $plugin, string $context): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->once())
            ->method('add')
            ->with(SharedCaptchaConfig::FORM_FIELD_NAME_CAPTCHA, CaptchaType::class, [CaptchaType::OPTION_CAPTCHA_CONTEXT => $context])
            ->willReturnSelf();

        // Act
        $this->createPlugin($plugin, SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, true)->expand($formBuilderMock, []);
    }

    /**
     * @return array<string, array{\Spryker\Yves\Kernel\AbstractPlugin, string}>
     */
    public function provideLoginFormPlugins(): array
    {
        return [
            'customer' => [new CustomerLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN],
            'agent' => [new AgentLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CONTEXT_STOREFRONT_AGENT_LOGIN],
        ];
    }

    public function testPluginAddsNothingWhenCaptchaIsDisabled(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);

        // Assert
        $formBuilderMock->expects($this->never())->method('add');

        // Act
        $this->createPlugin(new CustomerLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS, false)->expand($formBuilderMock, []);
    }

    public function testAfterFailedAttemptsModeAsksThePluginsWithTheLastEmailOfTheSession(): void
    {
        // Arrange
        $formBuilderMock = $this->createMock(FormBuilderInterface::class);
        $captchaRequirementPluginMock = $this->createMock(CaptchaRequirementPluginInterface::class);

        // Assert
        $captchaRequirementPluginMock->expects($this->once())
            ->method('isCaptchaRequired')
            ->with($this->callback(fn (CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool => $captchaRequirementRequestTransfer->getContext() === SharedCaptchaConfig::CONTEXT_STOREFRONT_CUSTOMER_LOGIN
                && $captchaRequirementRequestTransfer->getLoginIdentifier() === static::LAST_USERNAME))
            ->willReturn(false);
        $formBuilderMock->expects($this->never())->method('add');

        // Act
        $this->createPlugin(new CustomerLoginFormCaptchaExpanderPlugin(), SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS, true, [$captchaRequirementPluginMock])
            ->expand($formBuilderMock, []);
    }

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    protected function createPlugin(AbstractPlugin $plugin, string $captchaMode, bool $isCaptchaEnabled, array $captchaRequirementPlugins = []): AbstractPlugin
    {
        $captchaClientMock = $this->createMock(CaptchaClientInterface::class);
        $captchaClientMock->method('isCaptchaEnabled')->willReturn($isCaptchaEnabled);

        $request = Request::create('/login');
        $request->setSession(new Session(new MockArraySessionStorage()));
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, static::LAST_USERNAME);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->tester->setDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA, $captchaClientMock);
        $this->tester->setDependency(CaptchaDependencyProvider::SERVICE_REQUEST_STACK, $requestStack);
        $this->tester->setDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT, $captchaRequirementPlugins);

        $captchaFactory = new CaptchaFactory();
        $captchaFactory->setConfig($this->createConfiguredMock(CaptchaConfig::class, ['getCaptchaMode' => $captchaMode]));
        $plugin->setFactory($captchaFactory);

        return $plugin;
    }
}
