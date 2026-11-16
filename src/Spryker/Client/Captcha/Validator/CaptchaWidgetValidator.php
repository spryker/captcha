<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\Captcha\Validator;

use Generated\Shared\Transfer\CaptchaWidgetTransfer;

class CaptchaWidgetValidator implements CaptchaWidgetValidatorInterface
{
    protected const string PATTERN_MARKUP_NAME = '/^[a-z][a-z0-9-]*$/D';

    protected const string EVENT_HANDLER_ATTRIBUTE_PREFIX = 'on';

    protected const string SCRIPT_URL_SCHEME = 'https';

    protected const string VIOLATION_TAG_NAME = 'Tag name "%s" does not match %s.';

    protected const string VIOLATION_ATTRIBUTE_NAME = 'Attribute name "%s" does not match %s or starts with "%s".';

    protected const string VIOLATION_SCRIPT_URL = 'Script URL "%s" is not an absolute %s URL.';

    /**
     * @return list<string>
     */
    public function validate(CaptchaWidgetTransfer $captchaWidgetTransfer): array
    {
        return array_merge(
            $this->validateTagNames($captchaWidgetTransfer),
            $this->validateAttributeNames($captchaWidgetTransfer),
            $this->validateScriptUrls($captchaWidgetTransfer),
        );
    }

    /**
     * @return list<string>
     */
    protected function validateTagNames(CaptchaWidgetTransfer $captchaWidgetTransfer): array
    {
        $tagNames = [(string)$captchaWidgetTransfer->getTagName()];

        if ((string)$captchaWidgetTransfer->getLoaderTagName() !== '') {
            $tagNames[] = (string)$captchaWidgetTransfer->getLoaderTagName();
        }

        $violations = [];

        foreach ($tagNames as $tagName) {
            if (preg_match(static::PATTERN_MARKUP_NAME, $tagName) !== 1) {
                $violations[] = sprintf(static::VIOLATION_TAG_NAME, $tagName, static::PATTERN_MARKUP_NAME);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    protected function validateAttributeNames(CaptchaWidgetTransfer $captchaWidgetTransfer): array
    {
        $attributeNames = array_merge(
            [(string)$captchaWidgetTransfer->getNameAttribute()],
            array_map('strval', array_keys($captchaWidgetTransfer->getAttributes())),
        );

        $violations = [];

        foreach ($attributeNames as $attributeName) {
            if (!$this->isSafeAttributeName($attributeName)) {
                $violations[] = sprintf(static::VIOLATION_ATTRIBUTE_NAME, $attributeName, static::PATTERN_MARKUP_NAME, static::EVENT_HANDLER_ATTRIBUTE_PREFIX);
            }
        }

        return $violations;
    }

    /**
     * @return list<string>
     */
    protected function validateScriptUrls(CaptchaWidgetTransfer $captchaWidgetTransfer): array
    {
        $violations = [];

        foreach ($captchaWidgetTransfer->getScriptUrls() as $scriptUrl) {
            if (!$this->isAbsoluteHttpsUrl($scriptUrl)) {
                $violations[] = sprintf(static::VIOLATION_SCRIPT_URL, $scriptUrl, static::SCRIPT_URL_SCHEME);
            }
        }

        return $violations;
    }

    protected function isSafeAttributeName(string $attributeName): bool
    {
        return preg_match(static::PATTERN_MARKUP_NAME, $attributeName) === 1
            && !str_starts_with($attributeName, static::EVENT_HANDLER_ATTRIBUTE_PREFIX);
    }

    protected function isAbsoluteHttpsUrl(string $scriptUrl): bool
    {
        return parse_url($scriptUrl, PHP_URL_SCHEME) === static::SCRIPT_URL_SCHEME
            && (string)parse_url($scriptUrl, PHP_URL_HOST) !== '';
    }
}
