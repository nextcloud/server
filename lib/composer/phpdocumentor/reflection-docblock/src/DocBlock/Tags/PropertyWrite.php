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

namespace phpDocumentor\Reflection\DocBlock\Tags;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Types\Context as TypeContext;
use phpDocumentor\Reflection\Utils;
use Webmozart\Assert\Assert;
use function array_shift;
use function array_unshift;
use function implode;
use function strpos;
use function substr;
use const PREG_SPLIT_DELIM_CAPTURE;

/**
 * Reflection class for a {@}property-write tag in a Docblock.
 */
final class PropertyWrite extends TagWithType implements Factory\StaticMethod
{
    /** @var string */
    protected $variableName;

    public function __construct(?string $variableName, ?Type $type = null, ?Description $description = null)
    {
        Assert::string($variableName);

        $this->name         = 'property-write';
        $this->variableName = $variableName;
        $this->type         = $type;
        $this->description  = $description;
    }

    public static function create(
        string $body,
        ?TypeResolver $typeResolver = null,
        ?DescriptionFactory $descriptionFactory = null,
        ?TypeContext $context = null
    ) : self {
        Assert::stringNotEmpty($body);
        Assert::notNull($typeResolver);
        Assert::notNull($descriptionFactory);

        [$firstPart, $body] = self::extractTypeFromBody($body);
        $type               = null;
        $parts              = Utils::pregSplit('/(\s+)/Su', $body, 2, PREG_SPLIT_DELIM_CAPTURE);
        $variableName = '';

        // if the first item that is encountered is not a variable; it is a type
        if ($firstPart && $firstPart[0] !== '$') {
            $type = $typeResolver->resolve($firstPart, $context);
        } else {
            // first part is not a type; we should prepend it to the parts array for further processing
            array_unshift($parts, $firstPart);
        }

        // if the next item starts with a $ it must be the variable name
        if (isset($parts[0]) && strpos($parts[0], '$') === 0) {
            $variableName = array_shift($parts);
            if ($type) {
                array_shift($parts);
            }

            Assert::notNull($variableName);

            $variableName = substr($variableName, 1);
        }

        $description = $descriptionFactory->create(implode('', $parts), $context);

        return new static($variableName, $type, $description);
    }

    /**
     * Returns the variable's name.
     */
    public function getVariableName() : ?string
    {
        return $this->variableName;
    }

    /**
     * Returns a string representation for this tag.
     */
    public function __toString() : string
    {
        if ($this->description) {
            $description = $this->description->render();
        } else {
            $description = '';
        }

        if ($this->variableName) {
            $variableName = '$' . $this->variableName;
        } else {
            $variableName = '';
        }

        $type = (string) $this->type;

        return $type
            . ($variableName !== '' ? ($type !== '' ? ' ' : '') . $variableName : '')
            . ($description !== '' ? ($type !== '' || $variableName !== '' ? ' ' : '') . $description : '');
    }
}
