<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\RateLimiter;

use Psr\Log\LoggerInterface;
use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\Captcha\Resolver\IpNetworkResolverInterface;
use Spryker\Client\Redis\RedisClientInterface;

class CaptchaChallengeRateLimiter implements CaptchaChallengeRateLimiterInterface
{
    protected const string IP_COUNTER_KEY_PATTERN = 'captcha:challenge-rate:{%s}:ip:%s';

    protected const string CONTEXT_COUNTER_KEY_PATTERN = 'captcha:challenge-rate:{%s}:all';

    protected const string IP_HASH_ALGORITHM = 'sha256';

    protected const int FIRST_REQUEST_COUNT = 1;

    protected const int TIME_TO_LIVE_NOT_SET = -1;

    protected const int MINIMUM_CONFIGURED_VALUE = 1;

    protected const int WARNING_THRESHOLD_DISABLED = 0;

    protected const string LOG_MESSAGE_CONTEXT_WARNING_THRESHOLD_REACHED = 'The CAPTCHA challenge requests of context "%s" from all IPs reached %d in a %d-second window. The challenge endpoint keeps creating challenges; limit the requests before the application, for example in the load balancer or a web application firewall.';

    public function __construct(
        protected RedisClientInterface $redisClient,
        protected CaptchaConfig $captchaConfig,
        protected IpNetworkResolverInterface $ipNetworkResolver,
        protected LoggerInterface $logger,
    ) {
    }

    public function isRateLimited(string $context, string $ip): bool
    {
        $connectionKey = $this->captchaConfig->getRedisConnectionKey();
        $this->redisClient->setupConnection($connectionKey, $this->captchaConfig->getRedisConnectionConfiguration());

        if ($this->countRequest($connectionKey, $this->buildIpCounterKey($context, $ip)) > $this->getLimitPerIp()) {
            return true;
        }

        $this->countContextRequest($connectionKey, $context);

        return false;
    }

    protected function buildIpCounterKey(string $context, string $ip): string
    {
        return sprintf(
            static::IP_COUNTER_KEY_PATTERN,
            $context,
            hash(static::IP_HASH_ALGORITHM, $this->ipNetworkResolver->resolveNetwork($ip)),
        );
    }

    protected function countContextRequest(string $connectionKey, string $context): void
    {
        $warningThreshold = $this->captchaConfig->getChallengeRateWarningThresholdPerContext();

        if ($warningThreshold <= static::WARNING_THRESHOLD_DISABLED) {
            return;
        }

        if ($this->countRequest($connectionKey, sprintf(static::CONTEXT_COUNTER_KEY_PATTERN, $context)) !== $warningThreshold) {
            return;
        }

        $this->logger->warning(sprintf(
            static::LOG_MESSAGE_CONTEXT_WARNING_THRESHOLD_REACHED,
            $context,
            $warningThreshold,
            $this->getWindowSeconds(),
        ));
    }

    protected function countRequest(string $connectionKey, string $counterKey): int
    {
        $count = $this->redisClient->incr($connectionKey, $counterKey);

        if ($count === static::FIRST_REQUEST_COUNT || $this->redisClient->ttl($connectionKey, $counterKey) === static::TIME_TO_LIVE_NOT_SET) {
            $this->redisClient->expire($connectionKey, $counterKey, $this->getWindowSeconds());
        }

        return $count;
    }

    protected function getLimitPerIp(): int
    {
        return max(static::MINIMUM_CONFIGURED_VALUE, $this->captchaConfig->getChallengeRateLimitPerIp());
    }

    public function getWindowSeconds(): int
    {
        return max(static::MINIMUM_CONFIGURED_VALUE, $this->captchaConfig->getChallengeRateLimitWindowSeconds());
    }
}
