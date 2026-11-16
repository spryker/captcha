<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha;

use Spryker\Client\Captcha\Checker\CaptchaAvailabilityChecker;
use Spryker\Client\Captcha\Checker\CaptchaAvailabilityCheckerInterface;
use Spryker\Client\Captcha\Checker\CaptchaProviderConfigurationChecker;
use Spryker\Client\Captcha\Checker\CaptchaProviderConfigurationCheckerInterface;
use Spryker\Client\Captcha\Creator\CaptchaChallengeCreator;
use Spryker\Client\Captcha\Creator\CaptchaChallengeCreatorInterface;
use Spryker\Client\Captcha\Creator\CaptchaChallengeDataCreator;
use Spryker\Client\Captcha\Creator\CaptchaChallengeDataCreatorInterface;
use Spryker\Client\Captcha\RateLimiter\CaptchaChallengeRateLimiter;
use Spryker\Client\Captcha\RateLimiter\CaptchaChallengeRateLimiterInterface;
use Spryker\Client\Captcha\Reader\CaptchaResponseFieldNameReader;
use Spryker\Client\Captcha\Reader\CaptchaResponseFieldNameReaderInterface;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStore;
use Spryker\Client\Captcha\ReplayStore\CaptchaReplayStoreInterface;
use Spryker\Client\Captcha\Resolver\CaptchaProviderPluginResolver;
use Spryker\Client\Captcha\Resolver\CaptchaProviderPluginResolverInterface;
use Spryker\Client\Captcha\Resolver\IpNetworkResolver;
use Spryker\Client\Captcha\Resolver\IpNetworkResolverInterface;
use Spryker\Client\Captcha\Validator\CaptchaWidgetValidator;
use Spryker\Client\Captcha\Validator\CaptchaWidgetValidatorInterface;
use Spryker\Client\Captcha\Verifier\CaptchaVerifier;
use Spryker\Client\Captcha\Verifier\CaptchaVerifierInterface;
use Spryker\Client\CaptchaExtension\Dependency\Plugin\CaptchaProviderPluginInterface;
use Spryker\Client\Kernel\AbstractFactory;
use Spryker\Client\Redis\RedisClientInterface;
use Spryker\Shared\Log\LoggerTrait;

/**
 * @method \Spryker\Client\Captcha\CaptchaConfig getConfig()
 */
class CaptchaFactory extends AbstractFactory
{
    use LoggerTrait;

    public function createCaptchaAvailabilityChecker(): CaptchaAvailabilityCheckerInterface
    {
        return new CaptchaAvailabilityChecker($this->getConfig());
    }

    public function createCaptchaProviderConfigurationChecker(): CaptchaProviderConfigurationCheckerInterface
    {
        return new CaptchaProviderConfigurationChecker($this->getLogger(), $this->getCaptchaProviderPlugin());
    }

    public function createCaptchaChallengeCreator(): CaptchaChallengeCreatorInterface
    {
        return new CaptchaChallengeCreator(
            $this->createCaptchaProviderPluginResolver(),
            $this->createCaptchaWidgetValidator(),
            $this->getLogger(),
        );
    }

    public function createCaptchaChallengeDataCreator(): CaptchaChallengeDataCreatorInterface
    {
        return new CaptchaChallengeDataCreator(
            $this->createCaptchaProviderPluginResolver(),
            $this->createCaptchaChallengeRateLimiter(),
            $this->getLogger(),
        );
    }

    public function createCaptchaChallengeRateLimiter(): CaptchaChallengeRateLimiterInterface
    {
        return new CaptchaChallengeRateLimiter(
            $this->getRedisClient(),
            $this->getConfig(),
            $this->createIpNetworkResolver(),
            $this->getLogger(),
        );
    }

    public function createIpNetworkResolver(): IpNetworkResolverInterface
    {
        return new IpNetworkResolver();
    }

    public function createCaptchaVerifier(): CaptchaVerifierInterface
    {
        return new CaptchaVerifier(
            $this->createCaptchaProviderPluginResolver(),
            $this->createCaptchaReplayStore(),
            $this->getLogger(),
        );
    }

    public function createCaptchaResponseFieldNameReader(): CaptchaResponseFieldNameReaderInterface
    {
        return new CaptchaResponseFieldNameReader($this->getConfig(), $this->getCaptchaProviderPlugin());
    }

    public function createCaptchaWidgetValidator(): CaptchaWidgetValidatorInterface
    {
        return new CaptchaWidgetValidator();
    }

    public function createCaptchaReplayStore(): CaptchaReplayStoreInterface
    {
        return new CaptchaReplayStore($this->getRedisClient(), $this->getConfig());
    }

    public function createCaptchaProviderPluginResolver(): CaptchaProviderPluginResolverInterface
    {
        return new CaptchaProviderPluginResolver(
            $this->getConfig(),
            $this->getLogger(),
            $this->getCaptchaProviderPlugin(),
        );
    }

    public function getCaptchaProviderPlugin(): ?CaptchaProviderPluginInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::PLUGIN_CAPTCHA_PROVIDER);
    }

    public function getRedisClient(): RedisClientInterface
    {
        return $this->getProvidedDependency(CaptchaDependencyProvider::CLIENT_REDIS);
    }
}
