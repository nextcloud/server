<?php declare(strict_types=1);
/*
 * This file is part of sebastian/type.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace SebastianBergmann\Type;

use function array_pop;
use function explode;
use function implode;
use function substr;
use ReflectionClass;

final class TypeName
{
    private ?string $namespaceName;
    private string $simpleName;

    public static function fromQualifiedName(string $fullClassName): self
    {
        if ($fullClassName[0] === '\\') {
            $fullClassName = substr($fullClassName, 1);
        }

        $classNameParts = explode('\\', $fullClassName);

        $simpleName    = array_pop($classNameParts);
        $namespaceName = implode('\\', $classNameParts);

        return new self($namespaceName, $simpleName);
    }

    public static function fromReflection(ReflectionClass $type): self
    {
        return new self(
            $type->getNamespaceName(),
            $type->getShortName()
        );
    }

    public function __construct(?string $namespaceName, string $simpleName)
    {
        if ($namespaceName === '') {
            $namespaceName = null;
        }

        $this->namespaceName = $namespaceName;
        $this->simpleName    = $simpleName;
    }

    public function namespaceName(): ?string
    {
        return $this->namespaceName;
    }

    public function simpleName(): string
    {
        return $this->simpleName;
    }

    public function qualifiedName(): string
    {
        return $this->namespaceName === null
             ? $this->simpleName
             : $this->namespaceName . '\\' . $this->simpleName;
    }

    public function isNamespaced(): bool
    {
        return $this->namespaceName !== null;
    }
}
