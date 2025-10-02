<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph\Objects;

/**
 * This object type represents a website. It is a simple object type and uses only common Open Graph properties. For
 * specific pages within a website, the article object type should be used.
 *
 * https://developers.facebook.com/docs/reference/opengraph/object-type/website/
 */
class Website extends ObjectBase
{
    public const TYPE = 'website';

    public function __construct()
    {
        $this->type = self::TYPE;
    }
}
