<?php

declare(strict_types=1);

/**
 * This file is part of phpDocumentor.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link      http://phpdoc.org
 */

namespace phpDocumentor\Reflection;

use phpDocumentor\Reflection\DocBlock\Tag;
use Webmozart\Assert\Assert;

final class DocBlock
{
    /** @var string The opening line for this docblock. */
    private $summary;

    /** @var DocBlock\Description The actual description for this docblock. */
    private $description;

    /** @var Tag[] An array containing all the tags in this docblock; except inline. */
    private $tags = [];

    /** @var Types\Context|null Information about the context of this DocBlock. */
    private $context;

    /** @var Location|null Information about the location of this DocBlock. */
    private $location;

    /** @var bool Is this DocBlock (the start of) a template? */
    private $isTemplateStart;

    /** @var bool Does this DocBlock signify the end of a DocBlock template? */
    private $isTemplateEnd;

    /**
     * @param DocBlock\Tag[] $tags
     * @param Types\Context  $context  The context in which the DocBlock occurs.
     * @param Location       $location The location within the file that this DocBlock occurs in.
     */
    public function __construct(
        string $summary = '',
        ?DocBlock\Description $description = null,
        array $tags = [],
        ?Types\Context $context = null,
        ?Location $location = null,
        bool $isTemplateStart = false,
        bool $isTemplateEnd = false
    ) {
        Assert::allIsInstanceOf($tags, Tag::class);

        $this->summary     = $summary;
        $this->description = $description ?: new DocBlock\Description('');
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        $this->context  = $context;
        $this->location = $location;

        $this->isTemplateEnd   = $isTemplateEnd;
        $this->isTemplateStart = $isTemplateStart;
    }

    public function getSummary() : string
    {
        return $this->summary;
    }

    public function getDescription() : DocBlock\Description
    {
        return $this->description;
    }

    /**
     * Returns the current context.
     */
    public function getContext() : ?Types\Context
    {
        return $this->context;
    }

    /**
     * Returns the current location.
     */
    public function getLocation() : ?Location
    {
        return $this->location;
    }

    /**
     * Returns whether this DocBlock is the start of a Template section.
     *
     * A Docblock may serve as template for a series of subsequent DocBlocks. This is indicated by a special marker
     * (`#@+`) that is appended directly after the opening `/**` of a DocBlock.
     *
     * An example of such an opening is:
     *
     * ```
     * /**#@+
     *  * My DocBlock
     *  * /
     * ```
     *
     * The description and tags (not the summary!) are copied onto all subsequent DocBlocks and also applied to all
     * elements that follow until another DocBlock is found that contains the closing marker (`#@-`).
     *
     * @see self::isTemplateEnd() for the check whether a closing marker was provided.
     */
    public function isTemplateStart() : bool
    {
        return $this->isTemplateStart;
    }

    /**
     * Returns whether this DocBlock is the end of a Template section.
     *
     * @see self::isTemplateStart() for a more complete description of the Docblock Template functionality.
     */
    public function isTemplateEnd() : bool
    {
        return $this->isTemplateEnd;
    }

    /**
     * Returns the tags for this DocBlock.
     *
     * @return Tag[]
     */
    public function getTags() : array
    {
        return $this->tags;
    }

    /**
     * Returns an array of tags matching the given name. If no tags are found
     * an empty array is returned.
     *
     * @param string $name String to search by.
     *
     * @return Tag[]
     */
    public function getTagsByName(string $name) : array
    {
        $result = [];

        foreach ($this->getTags() as $tag) {
            if ($tag->getName() !== $name) {
                continue;
            }

            $result[] = $tag;
        }

        return $result;
    }

    /**
     * Checks if a tag of a certain type is present in this DocBlock.
     *
     * @param string $name Tag name to check for.
     */
    public function hasTag(string $name) : bool
    {
        foreach ($this->getTags() as $tag) {
            if ($tag->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a tag from this DocBlock.
     *
     * @param Tag $tagToRemove The tag to remove.
     */
    public function removeTag(Tag $tagToRemove) : void
    {
        foreach ($this->tags as $key => $tag) {
            if ($tag === $tagToRemove) {
                unset($this->tags[$key]);
                break;
            }
        }
    }

    /**
     * Adds a tag to this DocBlock.
     *
     * @param Tag $tag The tag to add.
     */
    private function addTag(Tag $tag) : void
    {
        $this->tags[] = $tag;
    }
}
