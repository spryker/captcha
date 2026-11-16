<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Checker;

use Exception;
use Generated\Shared\Transfer\CaptchaRequirementRequestTransfer;
use Psr\Log\LoggerInterface;
use Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface;

class AfterFailedAttemptsCaptchaRequirementChecker implements CaptchaRequirementCheckerInterface
{
    protected const string LOG_MESSAGE_NO_CAPTCHA_REQUIREMENT_PLUGIN = 'The CAPTCHA mode is "after_failed_attempts", but no CaptchaRequirementPluginInterface plugin is wired in CaptchaDependencyProvider::getCaptchaRequirementPlugins(), so every login form asks for a CAPTCHA.';

    protected const string LOG_MESSAGE_CAPTCHA_REQUIREMENT_PLUGIN_FAILED = 'CAPTCHA requirement plugin %s failed, so the login form asks for a CAPTCHA: %s';

    protected const string LOG_CONTEXT_EXCEPTION = 'exception';

    /**
     * @param list<\Spryker\Shared\CaptchaExtension\Dependency\Plugin\CaptchaRequirementPluginInterface> $captchaRequirementPlugins
     */
    public function __construct(
        protected array $captchaRequirementPlugins,
        protected LoggerInterface $logger
    ) {
    }

    public function isCaptchaRequired(CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer): bool
    {
        if ($this->captchaRequirementPlugins === []) {
            $this->logger->error(static::LOG_MESSAGE_NO_CAPTCHA_REQUIREMENT_PLUGIN);

            return true;
        }

        foreach ($this->captchaRequirementPlugins as $captchaRequirementPlugin) {
            if ($this->isCaptchaRequiredByPlugin($captchaRequirementPlugin, $captchaRequirementRequestTransfer)) {
                return true;
            }
        }

        return false;
    }

    protected function isCaptchaRequiredByPlugin(
        CaptchaRequirementPluginInterface $captchaRequirementPlugin,
        CaptchaRequirementRequestTransfer $captchaRequirementRequestTransfer
    ): bool {
        try {
            return $captchaRequirementPlugin->isCaptchaRequired($captchaRequirementRequestTransfer);
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(static::LOG_MESSAGE_CAPTCHA_REQUIREMENT_PLUGIN_FAILED, $captchaRequirementPlugin::class, $exception->getMessage()),
                [static::LOG_CONTEXT_EXCEPTION => $exception],
            );

            return true;
        }
    }
}
