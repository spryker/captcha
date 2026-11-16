<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha;

use Spryker\Client\Captcha\CaptchaClientInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Shared\Kernel\StrategyResolver;
use Spryker\Shared\Kernel\StrategyResolverInterface;
use Spryker\Shared\Log\LoggerTrait;
use Spryker\Yves\Captcha\Builder\CaptchaChallengeUrlBuilder;
use Spryker\Yves\Captcha\Builder\CaptchaChallengeUrlBuilderInterface;
use Spryker\Yves\Captcha\Builder\CaptchaRejectionResponseBuilder;
use Spryker\Yves\Captcha\Builder\CaptchaRejectionResponseBuilderInterface;
use Spryker\Yves\Captcha\Builder\ChallengeResponseBuilder;
use Spryker\Yves\Captcha\Builder\ChallengeResponseBuilderInterface;
use Spryker\Yves\Captcha\Checker\AfterFailedAttemptsCaptchaRequirementChecker;
use Spryker\Yves\Captcha\Checker\AlwaysCaptchaRequirementChecker;
use Spryker\Yves\Captcha\Checker\CaptchaRequirementCheckerInterface;
use Spryker\Yves\Captcha\EventSubscriber\LoginCaptchaEventSubscriber;
use Spryker\Yves\Captcha\Expander\LoginFormCaptchaExpander;
use Spryker\Yves\Captcha\Expander\LoginFormCaptchaExpanderInterface;
use Spryker\Yves\Captcha\Form\Constraint\CaptchaConstraint;
use Spryker\Yves\Captcha\Resolver\LoginFormResolver;
use Spryker\Yves\Captcha\Resolver\LoginFormResolverInterface;
use Spryker\Yves\Kernel\AbstractFactory;
use Spryker\Yves\Messenger\FlashMessenger\FlashMessengerInterface;
use Symfony\Cmf\Component\Routing\ChainRouterInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;

/**
 * @method \Spryker\Yves\Captcha\CaptchaConfig getConfig()
 */
class CaptchaFactory extends AbstractFactory
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
     * @return \Spryker\Shared\Kernel\StrategyResolverInterface<\Spryker\Yves\Captcha\Checker\CaptchaRequirementCheckerInterface>
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
            $this->getConfig(),
            $this->createLoginFormResolver(),
            $this->createCaptchaRejectionResponseBuilder(),
        );
    }

    public function createLoginFormResolver(): LoginFormResolverInterface
    {
        return new LoginFormResolver($this->getConfig());
    }

    public function createCaptchaRejectionResponseBuilder(): CaptchaRejectionResponseBuilderInterface
    {
        return new CaptchaRejectionResponseBuilder($this->getFlashMessenger());
    }

    public function createChallengeResponseBuilder(): ChallengeResponseBuilderInterface
    {
        return new ChallengeResponseBuilder($this->getCaptchaClient(), $this->getConfig());
    }

    public function createCaptchaChallengeUrlBuilder(): CaptchaChallengeUrlBuilderInterface
    {
        return new CaptchaChallengeUrlBuilder($this->getRouter(), $this->getConfig(), $this->getLogger());
    }

    public function getRouter(): ChainRouterInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::SERVICE_ROUTER);
    }

    public function getFlashMessenger(): FlashMessengerInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::SERVICE_FLASH_MESSENGER);
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
