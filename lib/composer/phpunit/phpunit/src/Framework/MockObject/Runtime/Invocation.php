<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\Framework\MockObject;

use function array_map;
use function implode;
use function is_object;
use function sprintf;
use function str_starts_with;
use function strtolower;
use function substr;
use PHPUnit\Framework\SelfDescribing;
use PHPUnit\Util\Cloner;
use SebastianBergmann\Exporter\Exporter;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
final class Invocation implements SelfDescribing
{
    /**
     * @psalm-var class-string
     */
    private readonly string $className;

    /**
     * @psalm-var non-empty-string
     */
    private readonly string $methodName;
    private readonly array $parameters;
    private readonly string $returnType;
    private readonly bool $isReturnTypeNullable;
    private readonly bool $proxiedCall;
    private readonly MockObjectInternal|StubInternal $object;

    /**
     * @psalm-param class-string $className
     * @psalm-param non-empty-string $methodName
     */
    public function __construct(string $className, string $methodName, array $parameters, string $returnType, MockObjectInternal|StubInternal $object, bool $cloneObjects = false, bool $proxiedCall = false)
    {
        $this->className   = $className;
        $this->methodName  = $methodName;
        $this->object      = $object;
        $this->proxiedCall = $proxiedCall;

        if (strtolower($methodName) === '__tostring') {
            $returnType = 'string';
        }

        if (str_starts_with($returnType, '?')) {
            $returnType                 = substr($returnType, 1);
            $this->isReturnTypeNullable = true;
        } else {
            $this->isReturnTypeNullable = false;
        }

        $this->returnType = $returnType;

        if (!$cloneObjects) {
            $this->parameters = $parameters;

            return;
        }

        foreach ($parameters as $key => $value) {
            if (is_object($value)) {
                $parameters[$key] = Cloner::clone($value);
            }
        }

        $this->parameters = $parameters;
    }

    /**
     * @psalm-return class-string
     */
    public function className(): string
    {
        return $this->className;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function methodName(): string
    {
        return $this->methodName;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * @throws Exception
     */
    public function generateReturnValue(): mixed
    {
        if ($this->returnType === 'never') {
            throw new NeverReturningMethodException(
                $this->className,
                $this->methodName,
            );
        }

        if ($this->isReturnTypeNullable || $this->proxiedCall) {
            return null;
        }

        return (new ReturnValueGenerator)->generate(
            $this->className,
            $this->methodName,
            $this->object::class,
            $this->returnType,
        );
    }

    public function toString(): string
    {
        $exporter = new Exporter;

        return sprintf(
            '%s::%s(%s)%s',
            $this->className,
            $this->methodName,
            implode(
                ', ',
                array_map(
                    [$exporter, 'shortenedExport'],
                    $this->parameters,
                ),
            ),
            $this->returnType ? sprintf(': %s', $this->returnType) : '',
        );
    }

    public function object(): MockObjectInternal|StubInternal
    {
        return $this->object;
    }
}
