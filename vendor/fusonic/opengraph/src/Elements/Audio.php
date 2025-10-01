<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph\Elements;

use Fusonic\OpenGraph\Property;

/**
 * An Open Graph audio element.
 */
class Audio extends ElementBase
{
    /**
     * The URL of an audio resource associated with the object.
     */
    public ?string $url = null;

    /**
     * An alternate URL to use if an audio resource requires HTTPS.
     */
    public ?string $secureUrl = null;

    /**
     * The MIME type of an audio resource associated with the object.
     */
    public ?string $type = null;

    /**
     * @param string $url URL to the audio file
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
            $properties[] = new Property(Property::AUDIO_URL, $this->url);
        }

        if (null !== $this->secureUrl) {
            $properties[] = new Property(Property::AUDIO_SECURE_URL, $this->secureUrl);
        }

        if (null !== $this->type) {
            $properties[] = new Property(Property::AUDIO_TYPE, $this->type);
        }

        return $properties;
    }
}
