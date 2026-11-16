<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Yves\Captcha\Builder;

use Psr\Log\LoggerInterface;
use Spryker\Shared\Captcha\CaptchaConfig as SharedCaptchaConfig;
use Spryker\Yves\Captcha\CaptchaConfig;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CaptchaChallengeUrlBuilder implements CaptchaChallengeUrlBuilderInterface
{
    protected const string LOG_MESSAGE_ROUTE_NOT_FOUND = 'The CAPTCHA challenge route "%s" is not registered, so the login form creates its challenge while the page renders. Register CaptchaRouteProviderPlugin in RouterDependencyProvider::getRouteProvider().';

    public function __construct(
        protected UrlGeneratorInterface $urlGenerator,
        protected CaptchaConfig $captchaConfig,
        protected LoggerInterface $logger
    ) {
    }

    public function findChallengeUrl(string $context): ?string
    {
        $challengeRouteName = $this->captchaConfig->getChallengeRouteName();

        try {
            return $this->urlGenerator->generate($challengeRouteName, [SharedCaptchaConfig::CHALLENGE_URL_QUERY_PARAMETER_CONTEXT => $context]);
        } catch (RouteNotFoundException $routeNotFoundException) {
            $this->logger->error(sprintf(static::LOG_MESSAGE_ROUTE_NOT_FOUND, $challengeRouteName));

            return null;
        }
    }
}
