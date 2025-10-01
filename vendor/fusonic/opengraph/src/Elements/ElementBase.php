<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph\Elements;

use Fusonic\OpenGraph\Property;

/**
 * Abstract base class for all OpenGraph elements (e.g. images, videos etc.).
 */
abstract class ElementBase
{
    /**
     * Gets all properties set on this element.
     *
     * @return Property[]
     */
    abstract public function getProperties(): array;
}
