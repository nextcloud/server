<?php

/*
 * Copyright (c) Fusonic GmbH. All rights reserved.
 * Licensed under the MIT License. See LICENSE file in the project root for license information.
 */

declare(strict_types=1);

namespace Fusonic\OpenGraph\Objects;

use Fusonic\OpenGraph\Elements\Audio;
use Fusonic\OpenGraph\Elements\Image;
use Fusonic\OpenGraph\Elements\Video;
use Fusonic\OpenGraph\Property;

/**
 * Abstract base class for all Open Graph objects (website, video, ...).
 */
abstract class ObjectBase
{
    /**
     * An array of audio resources attached to the object.
     *
     * @var Audio[]
     */
    public array $audios = [];

    /**
     * A short description of the object.
     */
    public ?string $description = null;

    /**
     * The word that appears before the object's title in a sentence. This is an list of words from 'a', 'an', 'the',
     * ' "" ', or 'auto'. If 'auto' is chosen, the consumer of the object will chose between 'a' or 'an'. The default is
     * the blank, "".
     */
    public ?string $determiner = null;

    /**
     * An array of images attached to the object.
     *
     * @var Image[]
     */
    public array $images = [];

    /**
     * The locale that the object's tags are marked up in, in the format language_TERRITORY.
     */
    public ?string $locale = null;

    /**
     * An array of alternate locales in which the resource is available.
     *
     * @var string[]
     */
    public array $localeAlternate = [];

    public ?bool $richAttachment = null;

    /**
     * An array of URLs of related resources.
     *
     * @var string[]
     */
    public array $seeAlso = [];

    /**
     * The name of the web site upon which the object resides.
     */
    public ?string $siteName = null;

    /**
     * The title of the object as it should appear in the graph.
     */
    public ?string $title = null;

    /**
     * The type of the object, such as 'article'.
     */
    public ?string $type = null;

    /**
     * The time when the object was last updated.
     */
    public ?\DateTimeImmutable $updatedTime = null;

    /**
     * The canonical URL of the object, used as its ID in the graph.
     */
    public ?string $url = null;

    /**
     * An array of videos attached to the object.
     *
     * @var Video[]
     */
    public array $videos = [];

    /**
     * Assigns all properties given to the this Object instance.
     *
     * @param array|Property[] $properties array of all properties to assign
     * @param bool             $debug      throw exceptions when parsing or not
     *
     * @throws \UnexpectedValueException
     */
    public function assignProperties(array $properties, bool $debug = false): void
    {
        foreach ($properties as $property) {
            $name = $property->key;
            $value = $property->value;

            switch ($name) {
                case Property::AUDIO:
                case Property::AUDIO_URL:
                    $this->audios[] = new Audio($value);
                    break;
                case Property::AUDIO_SECURE_URL:
                case Property::AUDIO_TYPE:
                    if (\count($this->audios) > 0) {
                        $this->handleAudioAttribute($this->audios[\count($this->audios) - 1], $name, $value);
                    } elseif ($debug) {
                        throw new \UnexpectedValueException(
                            \sprintf(
                                "Found '%s' property but no audio was found before.",
                                $name
                            )
                        );
                    }
                    break;
                case Property::DESCRIPTION:
                    if (null === $this->description) {
                        $this->description = $value;
                    }
                    break;
                case Property::DETERMINER:
                    if (null === $this->determiner) {
                        $this->determiner = $value;
                    }
                    break;
                case Property::IMAGE:
                case Property::IMAGE_URL:
                    $this->images[] = new Image($value);
                    break;
                case Property::IMAGE_HEIGHT:
                case Property::IMAGE_SECURE_URL:
                case Property::IMAGE_TYPE:
                case Property::IMAGE_WIDTH:
                case Property::IMAGE_USER_GENERATED:
                    if (\count($this->images) > 0) {
                        $this->handleImageAttribute($this->images[\count($this->images) - 1], $name, $value);
                    } elseif ($debug) {
                        throw new \UnexpectedValueException(
                            \sprintf(
                                "Found '%s' property but no image was found before.",
                                $name
                            )
                        );
                    }
                    break;
                case Property::LOCALE:
                    if (null === $this->locale) {
                        $this->locale = $value;
                    }
                    break;
                case Property::LOCALE_ALTERNATE:
                    $this->localeAlternate[] = $value;
                    break;
                case Property::RICH_ATTACHMENT:
                    $this->richAttachment = $this->convertToBoolean($value);
                    break;
                case Property::SEE_ALSO:
                    $this->seeAlso[] = $value;
                    break;
                case Property::SITE_NAME:
                    if (null === $this->siteName) {
                        $this->siteName = $value;
                    }
                    break;
                case Property::TITLE:
                    if (null === $this->title) {
                        $this->title = $value;
                    }
                    break;
                case Property::UPDATED_TIME:
                    if (null === $this->updatedTime) {
                        $this->updatedTime = $this->convertToDateTime($value);
                    }
                    break;
                case Property::URL:
                    if (null === $this->url) {
                        $this->url = $value;
                    }
                    break;
                case Property::VIDEO:
                case Property::VIDEO_URL:
                    $this->videos[] = new Video($value);
                    break;
                case Property::VIDEO_HEIGHT:
                case Property::VIDEO_SECURE_URL:
                case Property::VIDEO_TYPE:
                case Property::VIDEO_WIDTH:
                    if (\count($this->videos) > 0) {
                        $this->handleVideoAttribute($this->videos[\count($this->videos) - 1], $name, $value);
                    } elseif ($debug) {
                        throw new \UnexpectedValueException(\sprintf(
                            "Found '%s' property but no video was found before.",
                            $name
                        ));
                    }
            }
        }
    }

    private function handleImageAttribute(Image $element, string $name, string $value): void
    {
        switch ($name) {
            case Property::IMAGE_HEIGHT:
                $element->height = (int) $value;
                break;
            case Property::IMAGE_WIDTH:
                $element->width = (int) $value;
                break;
            case Property::IMAGE_TYPE:
                $element->type = $value;
                break;
            case Property::IMAGE_SECURE_URL:
                $element->secureUrl = $value;
                break;
            case Property::IMAGE_USER_GENERATED:
                $element->userGenerated = $this->convertToBoolean($value);
                break;
        }
    }

    private function handleVideoAttribute(Video $element, string $name, string $value): void
    {
        switch ($name) {
            case Property::VIDEO_HEIGHT:
                $element->height = (int) $value;
                break;
            case Property::VIDEO_WIDTH:
                $element->width = (int) $value;
                break;
            case Property::VIDEO_TYPE:
                $element->type = $value;
                break;
            case Property::VIDEO_SECURE_URL:
                $element->secureUrl = $value;
                break;
        }
    }

    private function handleAudioAttribute(Audio $element, string $name, string $value): void
    {
        switch ($name) {
            case Property::AUDIO_TYPE:
                $element->type = $value;
                break;
            case Property::AUDIO_SECURE_URL:
                $element->secureUrl = $value;
                break;
        }
    }

    protected function convertToDateTime(string $value): ?\DateTimeImmutable
    {
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function convertToBoolean(string $value): bool
    {
        switch (strtolower($value)) {
            case '1':
            case 'true':
                return true;
            default:
                return false;
        }
    }

    /**
     * Gets all properties set on this object.
     *
     * @return Property[]
     */
    public function getProperties(): array
    {
        $properties = [];

        foreach ($this->audios as $audio) {
            $properties = array_merge($properties, $audio->getProperties());
        }

        if (null !== $this->title) {
            $properties[] = new Property(Property::TITLE, $this->title);
        }

        if (null !== $this->description) {
            $properties[] = new Property(Property::DESCRIPTION, $this->description);
        }

        if (null !== $this->determiner) {
            $properties[] = new Property(Property::DETERMINER, $this->determiner);
        }

        foreach ($this->images as $image) {
            $properties = array_merge($properties, $image->getProperties());
        }

        if (null !== $this->locale) {
            $properties[] = new Property(Property::LOCALE, $this->locale);
        }

        foreach ($this->localeAlternate as $locale) {
            $properties[] = new Property(Property::LOCALE_ALTERNATE, $locale);
        }

        if (null !== $this->richAttachment) {
            $properties[] = new Property(Property::RICH_ATTACHMENT, (int) $this->richAttachment);
        }

        foreach ($this->seeAlso as $seeAlso) {
            $properties[] = new Property(Property::SEE_ALSO, $seeAlso);
        }

        if (null !== $this->siteName) {
            $properties[] = new Property(Property::SITE_NAME, $this->siteName);
        }

        if (null !== $this->type) {
            $properties[] = new Property(Property::TYPE, $this->type);
        }

        if (null !== $this->updatedTime) {
            $properties[] = new Property(Property::UPDATED_TIME, $this->updatedTime->format('c'));
        }

        if (null !== $this->url) {
            $properties[] = new Property(Property::URL, $this->url);
        }

        foreach ($this->videos as $video) {
            $properties = array_merge($properties, $video->getProperties());
        }

        return $properties;
    }
}
