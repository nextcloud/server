<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph;

use Fusonic\OpenGraph\Objects\ObjectBase;

/**
 * Class for generating Open Graph tags from objects.
 */
class Publisher
{
    public const DOCTYPE_HTML5 = 1;
    public const DOCTYPE_XHTML = 2;

    /**
     * Defines the style in which HTML tags should be written. Use one of Publisher::DOCTYPE_HTML5 or
     * Publisher::DOCTYPE_XHTML.
     */
    public int $doctype = self::DOCTYPE_HTML5;

    /**
     * Generated HTML tags from the given object.
     */
    public function generateHtml(ObjectBase $object): string
    {
        $html = '';
        $format = '<meta property="%s" content="%s"'.(self::DOCTYPE_XHTML === $this->doctype ? ' />' : '>');

        foreach ($object->getProperties() as $property) {
            if ('' !== $html) {
                $html .= "\n";
            }

            if (null === $property->value) {
                continue;
            } elseif ($property->value instanceof \DateTimeInterface) {
                $value = $property->value->format('c');
            } elseif (\is_object($property->value)) {
                throw new \UnexpectedValueException(
                    \sprintf(
                        "Cannot handle value of type '%s' for property '%s'.",
                        \get_class($property->value),
                        $property->key
                    )
                );
            } elseif (true === $property->value) {
                $value = '1';
            } elseif (false === $property->value) {
                $value = '0';
            } else {
                $value = (string) $property->value;
            }

            $html .= \sprintf($format, $property->key, htmlspecialchars($value));
        }

        return $html;
    }
}
