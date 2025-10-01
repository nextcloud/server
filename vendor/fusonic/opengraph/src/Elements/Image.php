<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph\Elements;

use Fusonic\OpenGraph\Property;

/**
 * An Open Graph image element.
 */
class Image extends ElementBase
{
    /**
     * The URL of an image resource associated with the object.
     */
    public ?string $url = null;

    /**
     * An alternate URL to use if an image resource requires HTTPS.
     */
    public ?string $secureUrl = null;

    /**
     * The MIME type of an image resource.
     */
    public ?string $type = null;

    /**
     * The width of an image resource in pixels.
     */
    public ?int $width = null;

    /**
     * The height of an image resource in pixels.
     */
    public ?int $height = null;

    /**
     * Whether the image is user-generated or not.
     */
    public ?bool $userGenerated = null;

    /**
     * @param string $url URL to the image file
     */
    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * Gets all properties set on this element.
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        $properties = [];

        // URL must precede all other properties
        if (null !== $this->url) {
            $properties[] = new Property(Property::IMAGE_URL, $this->url);
        }

        if (null !== $this->height) {
            $properties[] = new Property(Property::IMAGE_HEIGHT, $this->height);
        }

        if (null !== $this->secureUrl) {
            $properties[] = new Property(Property::IMAGE_SECURE_URL, $this->secureUrl);
        }

        if (null !== $this->type) {
            $properties[] = new Property(Property::IMAGE_TYPE, $this->type);
        }

        if (null !== $this->width) {
            $properties[] = new Property(Property::IMAGE_WIDTH, $this->width);
        }

        if (null !== $this->userGenerated) {
            $properties[] = new Property(Property::IMAGE_USER_GENERATED, $this->userGenerated);
        }

        return $properties;
    }
}
