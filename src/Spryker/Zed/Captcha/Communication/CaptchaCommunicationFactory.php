<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Captcha\Communication;

use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\Kernel\StrategyResolver;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Shared\ZedUi\ZedUiFactoryInterface;
use Spryker\Zed\Captcha\CaptchaConfig;
use Spryker\Zed\Captcha\CaptchaDependencyProvider;
use Spryker\Zed\Captcha\Communication\Builder\CaptchaChallengeUrlBuilder;
use Spryker\Zed\Captcha\Communication\Builder\CaptchaChallengeUrlBuilderInterface;
use Spryker\Zed\Captcha\Communication\Builder\CaptchaRejectionResponseBuilderInterface;
use Spryker\Zed\Captcha\Communication\Builder\ChallengeResponseBuilder;
use Spryker\Zed\Captcha\Communication\Builder\ChallengeResponseBuilderInterface;
use Spryker\Zed\Captcha\Communication\Builder\RedirectCaptchaRejectionResponseBuilder;
use Spryker\Zed\Captcha\Communication\Builder\ZedUiCaptchaRejectionResponseBuilder;
use Spryker\Zed\Captcha\Communication\Checker\AfterFailedAttemptsCaptchaRequirementChecker;
use Spryker\Zed\Captcha\Communication\Checker\AlwaysCaptchaRequirementChecker;
use Spryker\Zed\Captcha\Communication\Checker\CaptchaRequirementCheckerInterface;
use Spryker\Zed\Captcha\Communication\EventSubscriber\LoginCaptchaEventSubscriber;
use Spryker\Zed\Captcha\Communication\Expander\LoginFormCaptchaExpander;
use Spryker\Zed\Captcha\Communication\Expander\LoginFormCaptchaExpanderInterface;
use Spryker\Zed\Captcha\Communication\Form\Constraint\CaptchaConstraint;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Messenger\Business\MessengerFacadeInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;

/**
 * @method \Spryker\Zed\Captcha\CaptchaConfig getConfig()
 */
class CaptchaCommunicationFactory extends AbstractCommunicationFactory
{
    use LoggerTrait;

    public function createCaptchaConstraint(string $context): Constraint
    {
        return new CaptchaConstraint($this->getCaptchaClient(), $this->getRequestStack(), $context);
    }

    public function createCaptchaRequirementChecker(): CaptchaRequirementCheckerInterface
    {
        return $this->createCaptchaRequirementCheckerStrategyResolver()->get($this->getConfig()->getCaptchaMode());
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Zed\Captcha\Communication\Checker\CaptchaRequirementCheckerInterface>
     */
    public function createCaptchaRequirementCheckerStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver(
            [
                SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS => fn (): CaptchaRequirementCheckerInterface => $this->createAlwaysCaptchaRequirementChecker(),
                SharedCaptchaConfig::CAPTCHA_MODE_AFTER_FAILED_ATTEMPTS => fn (): CaptchaRequirementCheckerInterface => $this->createAfterFailedAttemptsCaptchaRequirementChecker(),
            ],
            SharedCaptchaConfig::CAPTCHA_MODE_ALWAYS,
        );
    }

    public function createAlwaysCaptchaRequirementChecker(): CaptchaRequirementCheckerInterface
    {
        return new AlwaysCaptchaRequirementChecker();
    }

    public function createAfterFailedAttemptsCaptchaRequirementChecker(): CaptchaRequirementCheckerInterface
    {
        return new AfterFailedAttemptsCaptchaRequirementChecker($this->getCaptchaRequirementPlugins(), $this->getLogger());
    }

    /**
     * @return list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface>
     */
    public function getCaptchaRequirementPlugins(): array
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::PLUGINS_CAPTCHA_REQUIREMENT);
    }

    public function createLoginFormCaptchaExpander(): LoginFormCaptchaExpanderInterface
    {
        return new LoginFormCaptchaExpander(
            $this->getCaptchaClient(),
            $this->createCaptchaRequirementChecker(),
            $this->getRequestStack(),
        );
    }

    public function createLoginCaptchaEventSubscriber(): EventSubscriberInterface
    {
        return new LoginCaptchaEventSubscriber(
            $this->getCaptchaClient(),
            $this->createCaptchaRequirementCheckerStrategyResolver(),
            $this->createCaptchaRejectionResponseBuilderStrategyResolver(),
            $this->getConfig(),
        );
    }

    /**
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Zed\Captcha\Communication\Builder\CaptchaRejectionResponseBuilderInterface>
     */
    public function createCaptchaRejectionResponseBuilderStrategyResolver(): StrategyResolverInterface
    {
        return new StrategyResolver([
            CaptchaConfig::REJECTION_RESPONSE_TYPE_REDIRECT => fn (): CaptchaRejectionResponseBuilderInterface => $this->createRedirectCaptchaRejectionResponseBuilder(),
            CaptchaConfig::REJECTION_RESPONSE_TYPE_ZED_UI => fn (): CaptchaRejectionResponseBuilderInterface => $this->createZedUiCaptchaRejectionResponseBuilder(),
        ]);
    }

    public function createChallengeResponseBuilder(): ChallengeResponseBuilderInterface
    {
        return new ChallengeResponseBuilder($this->getCaptchaClient(), $this->getConfig());
    }

    public function createCaptchaChallengeUrlBuilder(): CaptchaChallengeUrlBuilderInterface
    {
        return new CaptchaChallengeUrlBuilder($this->getConfig());
    }

    public function createRedirectCaptchaRejectionResponseBuilder(): CaptchaRejectionResponseBuilderInterface
    {
        return new RedirectCaptchaRejectionResponseBuilder($this->getMessengerFacade());
    }

    public function createZedUiCaptchaRejectionResponseBuilder(): CaptchaRejectionResponseBuilderInterface
    {
        return new ZedUiCaptchaRejectionResponseBuilder($this->getMessengerFacade(), $this->getZedUiFactory(), $this->getLogger());
    }

    public function getMessengerFacade(): MessengerFacadeInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::FACADE_MESSENGER);
    }

    public function getZedUiFactory(): ?ZedUiFactoryInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::SERVICE_ZED_UI_FACTORY);
    }

    public function getCaptchaClient(): CaptchaClientInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::CLIENT_CAPTCHA);
    }

    public function getRequestStack(): RequestStack
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::SERVICE_REQUEST_STACK);
    }
}
