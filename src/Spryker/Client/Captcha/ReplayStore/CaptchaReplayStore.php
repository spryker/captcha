<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\ReplayStore;

use Spryker\Client\Captcha\CaptchaConfig;
use Spryker\Client\Captcha\Exception\CaptchaReplayStoreException;
use Spryker\Client\Redis\RedisClientInterface;

class CaptchaReplayStore implements CaptchaReplayStoreInterface
{
    protected const string SPENT_KEY_PATTERN = 'captcha:spent:%s';

    protected const string SPENT_VALUE = '1';

    protected const string REPLAY_KEY_HASH_ALGORITHM = 'sha256';

    protected const string EXPIRE_RESOLUTION_SECONDS = 'EX';

    protected const string FLAG_ONLY_IF_NOT_EXISTS = 'NX';

    public function __construct(
        protected RedisClientInterface $redisClient,
        protected CaptchaConfig $captchaConfig,
    ) {
    }

    /**
     * @throws \Spryker\Client\Captcha\Exception\CaptchaReplayStoreException
     */
    public function markChallengeAsSpent(string $replayKey, int $ttl): bool
    {
        $connectionKey = $this->captchaConfig->getRedisConnectionKey();
        $this->redisClient->setupConnection($connectionKey, $this->captchaConfig->getRedisConnectionConfiguration());
        $spentKey = sprintf(static::SPENT_KEY_PATTERN, hash(static::REPLAY_KEY_HASH_ALGORITHM, $replayKey));

        $isMarkedAsSpent = $this->redisClient->set(
            $connectionKey,
            $spentKey,
            static::SPENT_VALUE,
            static::EXPIRE_RESOLUTION_SECONDS,
            $ttl,
            static::FLAG_ONLY_IF_NOT_EXISTS,
        );

        if ($isMarkedAsSpent) {
            return true;
        }

        if ($this->redisClient->get($connectionKey, $spentKey) !== null) {
            return false;
        }

        throw new CaptchaReplayStoreException(sprintf('The spent CAPTCHA challenge could not be recorded under "%s".', $spentKey));
    }
}
