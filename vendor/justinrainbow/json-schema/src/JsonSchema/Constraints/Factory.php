<?php

declare(strict_types=1);

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Constraints;

use JsonSchema\Exception\InvalidArgumentException;
use JsonSchema\SchemaStorage;
use JsonSchema\SchemaStorageInterface;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\UriRetrieverInterface;
use JsonSchema\Validator;

/**
 * Factory for centralize constraint initialization.
 */
class Factory
{
    /**
     * @var SchemaStorageInterface
     */
    protected $schemaStorage;

    /**
     * @var UriRetriever
     */
    protected $uriRetriever;

    /**
     * @var int
     * @phpstan-var int-mask-of<Constraint::CHECK_MODE_*>
     */
    private $checkMode = Constraint::CHECK_MODE_NORMAL;

    /**
     * @var array<int, TypeCheck\TypeCheckInterface>
     * @phpstan-var array<int-mask-of<Constraint::CHECK_MODE_*>, TypeCheck\TypeCheckInterface>
     */
    private $typeCheck = [];

    /**
     * @var int Validation context
     */
    protected $errorContext = Validator::ERROR_DOCUMENT_VALIDATION;

    /**
     * @var array
     */
    protected $constraintMap = [
        'array' => 'JsonSchema\Constraints\CollectionConstraint',
        'collection' => 'JsonSchema\Constraints\CollectionConstraint',
        'object' => 'JsonSchema\Constraints\ObjectConstraint',
        'type' => 'JsonSchema\Constraints\TypeConstraint',
        'undefined' => 'JsonSchema\Constraints\UndefinedConstraint',
        'string' => 'JsonSchema\Constraints\StringConstraint',
        'number' => 'JsonSchema\Constraints\NumberConstraint',
        'enum' => 'JsonSchema\Constraints\EnumConstraint',
        'const' => 'JsonSchema\Constraints\ConstConstraint',
        'format' => 'JsonSchema\Constraints\FormatConstraint',
        'schema' => 'JsonSchema\Constraints\SchemaConstraint',
        'validator' => 'JsonSchema\Validator'
    ];

    /**
     * @var array<ConstraintInterface>
     */
    private $instanceCache = [];

    /**
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function __construct(
        ?SchemaStorageInterface $schemaStorage = null,
        ?UriRetrieverInterface $uriRetriever = null,
        int $checkMode = Constraint::CHECK_MODE_NORMAL
    ) {
        // set provided config options
        $this->setConfig($checkMode);

        $this->uriRetriever = $uriRetriever ?: new UriRetriever();
        $this->schemaStorage = $schemaStorage ?: new SchemaStorage($this->uriRetriever);
    }

    /**
     * Set config values
     *
     * @param int $checkMode Set checkMode options - does not preserve existing flags
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $checkMode
     */
    public function setConfig(int $checkMode = Constraint::CHECK_MODE_NORMAL): void
    {
        $this->checkMode = $checkMode;
    }

    /**
     * Enable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function addConfig(int $options): void
    {
        $this->checkMode |= $options;
    }

    /**
     * Disable checkMode flags
     *
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*> $options
     */
    public function removeConfig(int $options): void
    {
        $this->checkMode &= ~$options;
    }

    /**
     * Get checkMode option
     *
     * @param int|null $options Options to get, if null then return entire bitmask
     * @phpstan-param int-mask-of<Constraint::CHECK_MODE_*>|null $options Options to get, if null then return entire bitmask
     *
     * @phpstan-return int-mask-of<Constraint::CHECK_MODE_*>
     */
    public function getConfig(?int $options = null): int
    {
        if ($options === null) {
            return $this->checkMode;
        }

        return $this->checkMode & $options;
    }

    public function getUriRetriever(): UriRetrieverInterface
    {
        return $this->uriRetriever;
    }

    public function getSchemaStorage(): SchemaStorageInterface
    {
        return $this->schemaStorage;
    }

    public function getTypeCheck(): TypeCheck\TypeCheckInterface
    {
        if (!isset($this->typeCheck[$this->checkMode])) {
            $this->typeCheck[$this->checkMode] = ($this->checkMode & Constraint::CHECK_MODE_TYPE_CAST)
                ? new TypeCheck\LooseTypeCheck()
                : new TypeCheck\StrictTypeCheck();
        }

        return $this->typeCheck[$this->checkMode];
    }

    public function setConstraintClass(string $name, string $class): Factory
    {
        // Ensure class exists
        if (!class_exists($class)) {
            throw new InvalidArgumentException('Unknown constraint ' . $name);
        }
        // Ensure class is appropriate
        if (!in_array('JsonSchema\Constraints\ConstraintInterface', class_implements($class))) {
            throw new InvalidArgumentException('Invalid class ' . $name);
        }
        $this->constraintMap[$name] = $class;

        return $this;
    }

    /**
     * Create a constraint instance for the given constraint name.
     *
     * @param string $constraintName
     *
     * @throws InvalidArgumentException if is not possible create the constraint instance
     *
     * @return ConstraintInterface&BaseConstraint
     * @phpstan-return ConstraintInterface&BaseConstraint
     */
    public function createInstanceFor($constraintName)
    {
        if (!isset($this->constraintMap[$constraintName])) {
            throw new InvalidArgumentException('Unknown constraint ' . $constraintName);
        }

        if (!isset($this->instanceCache[$constraintName])) {
            $this->instanceCache[$constraintName] = new $this->constraintMap[$constraintName]($this);
        }

        return clone $this->instanceCache[$constraintName];
    }

    /**
     * Get the error context
     *
     * @phpstan-return Validator::ERROR_DOCUMENT_VALIDATION|Validator::ERROR_SCHEMA_VALIDATION
     */
    public function getErrorContext(): int
    {
        return $this->errorContext;
    }

    /**
     * Set the error context
     *
     * @phpstan-param Validator::ERROR_DOCUMENT_VALIDATION|Validator::ERROR_SCHEMA_VALIDATION $errorContext
     */
    public function setErrorContext(int $errorContext): void
    {
        $this->errorContext = $errorContext;
    }
}
