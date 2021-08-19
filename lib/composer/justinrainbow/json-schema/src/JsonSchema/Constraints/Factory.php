<?php

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
     * @var SchemaStorage
     */
    protected $schemaStorage;

    /**
     * @var UriRetriever
     */
    protected $uriRetriever;

    /**
     * @var int
     */
    private $checkMode = Constraint::CHECK_MODE_NORMAL;

    /**
     * @var TypeCheck\TypeCheckInterface[]
     */
    private $typeCheck = array();

    /**
     * @var int Validation context
     */
    protected $errorContext = Validator::ERROR_DOCUMENT_VALIDATION;

    /**
     * @var array
     */
    protected $constraintMap = array(
        'array' => 'JsonSchema\Constraints\CollectionConstraint',
        'collection' => 'JsonSchema\Constraints\CollectionConstraint',
        'object' => 'JsonSchema\Constraints\ObjectConstraint',
        'type' => 'JsonSchema\Constraints\TypeConstraint',
        'undefined' => 'JsonSchema\Constraints\UndefinedConstraint',
        'string' => 'JsonSchema\Constraints\StringConstraint',
        'number' => 'JsonSchema\Constraints\NumberConstraint',
        'enum' => 'JsonSchema\Constraints\EnumConstraint',
        'format' => 'JsonSchema\Constraints\FormatConstraint',
        'schema' => 'JsonSchema\Constraints\SchemaConstraint',
        'validator' => 'JsonSchema\Validator'
    );

    /**
     * @var array<ConstraintInterface>
     */
    private $instanceCache = array();

    /**
     * @param SchemaStorage         $schemaStorage
     * @param UriRetrieverInterface $uriRetriever
     * @param int                   $checkMode
     */
    public function __construct(
        SchemaStorageInterface $schemaStorage = null,
        UriRetrieverInterface $uriRetriever = null,
        $checkMode = Constraint::CHECK_MODE_NORMAL
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
     */
    public function setConfig($checkMode = Constraint::CHECK_MODE_NORMAL)
    {
        $this->checkMode = $checkMode;
    }

    /**
     * Enable checkMode flags
     *
     * @param int $options
     */
    public function addConfig($options)
    {
        $this->checkMode |= $options;
    }

    /**
     * Disable checkMode flags
     *
     * @param int $options
     */
    public function removeConfig($options)
    {
        $this->checkMode &= ~$options;
    }

    /**
     * Get checkMode option
     *
     * @param int $options Options to get, if null then return entire bitmask
     *
     * @return int
     */
    public function getConfig($options = null)
    {
        if ($options === null) {
            return $this->checkMode;
        }

        return $this->checkMode & $options;
    }

    /**
     * @return UriRetrieverInterface
     */
    public function getUriRetriever()
    {
        return $this->uriRetriever;
    }

    public function getSchemaStorage()
    {
        return $this->schemaStorage;
    }

    public function getTypeCheck()
    {
        if (!isset($this->typeCheck[$this->checkMode])) {
            $this->typeCheck[$this->checkMode] = ($this->checkMode & Constraint::CHECK_MODE_TYPE_CAST)
                ? new TypeCheck\LooseTypeCheck()
                : new TypeCheck\StrictTypeCheck();
        }

        return $this->typeCheck[$this->checkMode];
    }

    /**
     * @param string $name
     * @param string $class
     *
     * @return Factory
     */
    public function setConstraintClass($name, $class)
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
     * @return ConstraintInterface|ObjectConstraint
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
     * @return string
     */
    public function getErrorContext()
    {
        return $this->errorContext;
    }

    /**
     * Set the error context
     *
     * @param string $validationContext
     */
    public function setErrorContext($errorContext)
    {
        $this->errorContext = $errorContext;
    }
}
