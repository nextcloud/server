<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph;

/**
 * Class holding data for a single Open Graph property on a web page.
 */
class Property
{
    public const AUDIO = 'og:audio';
    public const AUDIO_SECURE_URL = 'og:audio:secure_url';
    public const AUDIO_TYPE = 'og:audio:type';
    public const AUDIO_URL = 'og:audio:url';
    public const DESCRIPTION = 'og:description';
    public const DETERMINER = 'og:determiner';
    public const IMAGE = 'og:image';
    public const IMAGE_HEIGHT = 'og:image:height';
    public const IMAGE_SECURE_URL = 'og:image:secure_url';
    public const IMAGE_TYPE = 'og:image:type';
    public const IMAGE_URL = 'og:image:url';
    public const IMAGE_WIDTH = 'og:image:width';
    public const IMAGE_USER_GENERATED = 'og:image:user_generated';
    public const LOCALE = 'og:locale';
    public const LOCALE_ALTERNATE = 'og:locale:alternate';
    public const RICH_ATTACHMENT = 'og:rich_attachment';
    public const SEE_ALSO = 'og:see_also';
    public const SITE_NAME = 'og:site_name';
    public const TITLE = 'og:title';
    public const TYPE = 'og:type';
    public const UPDATED_TIME = 'og:updated_time';
    public const URL = 'og:url';
    public const VIDEO = 'og:video';
    public const VIDEO_HEIGHT = 'og:video:height';
    public const VIDEO_SECURE_URL = 'og:video:secure_url';
    public const VIDEO_TYPE = 'og:video:type';
    public const VIDEO_URL = 'og:video:url';
    public const VIDEO_WIDTH = 'og:video:width';

    public function __construct(
        /**
         * Key of the property without "og:" prefix.
         */
        public string $key,

        /**
         * Value of the property.
         */
        public mixed $value,
    ) {
    }
}
