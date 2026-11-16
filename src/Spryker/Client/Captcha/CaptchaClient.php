<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha;

use Generated\Shared\Transfer\CaptchaChallengeDataRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeDataResponseTransfer;
use Generated\Shared\Transfer\CaptchaChallengeRequestTransfer;
use Generated\Shared\Transfer\CaptchaChallengeResponseTransfer;
use Generated\Shared\Transfer\CaptchaVerificationRequestTransfer;
use Generated\Shared\Transfer\CaptchaVerificationResponseTransfer;
use Spryker\Client\Kernel\AbstractClient;

/**
 * @method \Spryker\Client\Captcha\CaptchaFactory getFactory()
 */
class CaptchaClient extends AbstractClient implements CaptchaClientInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isCaptchaEnabled(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): bool
    {
        return $this->getFactory()
            ->createCaptchaAvailabilityChecker()
            ->isCaptchaEnabled($captchaChallengeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function isCaptchaProviderConfigured(): bool
    {
        return $this->getFactory()
            ->createCaptchaProviderConfigurationChecker()
            ->isCaptchaProviderConfigured();
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createChallenge(CaptchaChallengeRequestTransfer $captchaChallengeRequestTransfer): CaptchaChallengeResponseTransfer
    {
        return $this->getFactory()
            ->createCaptchaChallengeCreator()
            ->createChallenge($captchaChallengeRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function createChallengeData(
        CaptchaChallengeDataRequestTransfer $captchaChallengeDataRequestTransfer
    ): CaptchaChallengeDataResponseTransfer {
        return $this->getFactory()
            ->createCaptchaChallengeDataCreator()
            ->createChallengeData($captchaChallengeDataRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function verifyCaptcha(CaptchaVerificationRequestTransfer $captchaVerificationRequestTransfer): CaptchaVerificationResponseTransfer
    {
        return $this->getFactory()
            ->createCaptchaVerifier()
            ->verifyCaptcha($captchaVerificationRequestTransfer);
    }

    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function findResponseFieldName(): ?string
    {
        return $this->getFactory()
            ->createCaptchaResponseFieldNameReader()
            ->findResponseFieldName();
    }
}
