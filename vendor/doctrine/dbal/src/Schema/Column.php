<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\DBAL\Schema\Exception\UnknownColumnOption;
use Doctrine\DBAL\Types\Type;
use Doctrine\Deprecations\Deprecation;

use function array_merge;
use function is_numeric;
use function method_exists;

/**
 * Object representation of a database column.
 */
class Column extends AbstractAsset
{
    /** @var Type */
    protected $_type;

    /** @var int|null */
    protected $_length;

    /** @var int */
    protected $_precision = 10;

    /** @var int */
    protected $_scale = 0;

    /** @var bool */
    protected $_unsigned = false;

    /** @var bool */
    protected $_fixed = false;

    /** @var bool */
    protected $_notnull = true;

    /** @var mixed */
    protected $_default;

    /** @var bool */
    protected $_autoincrement = false;

    /** @var mixed[] */
    protected $_platformOptions = [];

    /** @var string|null */
    protected $_columnDefinition;

    /** @var string|null */
    protected $_comment;

    /**
     * @deprecated Use {@link $_platformOptions} instead
     *
     * @var mixed[]
     */
    protected $_customSchemaOptions = [];

    /**
     * Creates a new Column.
     *
     * @param string  $name
     * @param mixed[] $options
     *
     * @throws SchemaException
     */
    public function __construct($name, Type $type, array $options = [])
    {
        $this->_setName($name);
        $this->setType($type);
        $this->setOptions($options);
    }

    /**
     * @param mixed[] $options
     *
     * @return Column
     *
     * @throws SchemaException
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $method = 'set' . $name;

            if (! method_exists($this, $method)) {
                throw UnknownColumnOption::new($name);
            }

            $this->$method($value);
        }

        return $this;
    }

    /** @return Column */
    public function setType(Type $type)
    {
        $this->_type = $type;

        return $this;
    }

    /**
     * @param int|null $length
     *
     * @return Column
     */
    public function setLength($length)
    {
        if ($length !== null) {
            $this->_length = (int) $length;
        } else {
            $this->_length = null;
        }

        return $this;
    }

    /**
     * @param int $precision
     *
     * @return Column
     */
    public function setPrecision($precision)
    {
        if (! is_numeric($precision)) {
            $precision = 10; // defaults to 10 when no valid precision is given.
        }

        $this->_precision = (int) $precision;

        return $this;
    }

    /**
     * @param int $scale
     *
     * @return Column
     */
    public function setScale($scale)
    {
        if (! is_numeric($scale)) {
            $scale = 0;
        }

        $this->_scale = (int) $scale;

        return $this;
    }

    /**
     * @param bool $unsigned
     *
     * @return Column
     */
    public function setUnsigned($unsigned)
    {
        $this->_unsigned = (bool) $unsigned;

        return $this;
    }

    /**
     * @param bool $fixed
     *
     * @return Column
     */
    public function setFixed($fixed)
    {
        $this->_fixed = (bool) $fixed;

        return $this;
    }

    /**
     * @param bool $notnull
     *
     * @return Column
     */
    public function setNotnull($notnull)
    {
        $this->_notnull = (bool) $notnull;

        return $this;
    }

    /**
     * @param mixed $default
     *
     * @return Column
     */
    public function setDefault($default)
    {
        $this->_default = $default;

        return $this;
    }

    /**
     * @param mixed[] $platformOptions
     *
     * @return Column
     */
    public function setPlatformOptions(array $platformOptions)
    {
        $this->_platformOptions = $platformOptions;

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Column
     */
    public function setPlatformOption($name, $value)
    {
        $this->_platformOptions[$name] = $value;

        return $this;
    }

    /**
     * @param string|null $value
     *
     * @return Column
     */
    public function setColumnDefinition($value)
    {
        $this->_columnDefinition = $value;

        return $this;
    }

    /** @return Type */
    public function getType()
    {
        return $this->_type;
    }

    /** @return int|null */
    public function getLength()
    {
        return $this->_length;
    }

    /** @return int */
    public function getPrecision()
    {
        return $this->_precision;
    }

    /** @return int */
    public function getScale()
    {
        return $this->_scale;
    }

    /** @return bool */
    public function getUnsigned()
    {
        return $this->_unsigned;
    }

    /** @return bool */
    public function getFixed()
    {
        return $this->_fixed;
    }

    /** @return bool */
    public function getNotnull()
    {
        return $this->_notnull;
    }

    /** @return mixed */
    public function getDefault()
    {
        return $this->_default;
    }

    /** @return mixed[] */
    public function getPlatformOptions()
    {
        return $this->_platformOptions;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasPlatformOption($name)
    {
        return isset($this->_platformOptions[$name]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getPlatformOption($name)
    {
        return $this->_platformOptions[$name];
    }

    /** @return string|null */
    public function getColumnDefinition()
    {
        return $this->_columnDefinition;
    }

    /** @return bool */
    public function getAutoincrement()
    {
        return $this->_autoincrement;
    }

    /**
     * @param bool $flag
     *
     * @return Column
     */
    public function setAutoincrement($flag)
    {
        $this->_autoincrement = $flag;

        return $this;
    }

    /**
     * @param string|null $comment
     *
     * @return Column
     */
    public function setComment($comment)
    {
        $this->_comment = $comment;

        return $this;
    }

    /** @return string|null */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * @deprecated Use {@link setPlatformOption()} instead
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Column
     */
    public function setCustomSchemaOption($name, $value)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5476',
            'Column::setCustomSchemaOption() is deprecated. Use setPlatformOption() instead.',
        );

        $this->_customSchemaOptions[$name] = $value;

        return $this;
    }

    /**
     * @deprecated Use {@link hasPlatformOption()} instead
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasCustomSchemaOption($name)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5476',
            'Column::hasCustomSchemaOption() is deprecated. Use hasPlatformOption() instead.',
        );

        return isset($this->_customSchemaOptions[$name]);
    }

    /**
     * @deprecated Use {@link getPlatformOption()} instead
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getCustomSchemaOption($name)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5476',
            'Column::getCustomSchemaOption() is deprecated. Use getPlatformOption() instead.',
        );

        return $this->_customSchemaOptions[$name];
    }

    /**
     * @deprecated Use {@link setPlatformOptions()} instead
     *
     * @param mixed[] $customSchemaOptions
     *
     * @return Column
     */
    public function setCustomSchemaOptions(array $customSchemaOptions)
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5476',
            'Column::setCustomSchemaOptions() is deprecated. Use setPlatformOptions() instead.',
        );

        $this->_customSchemaOptions = $customSchemaOptions;

        return $this;
    }

    /**
     * @deprecated Use {@link getPlatformOptions()} instead
     *
     * @return mixed[]
     */
    public function getCustomSchemaOptions()
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5476',
            'Column::getCustomSchemaOptions() is deprecated. Use getPlatformOptions() instead.',
        );

        return $this->_customSchemaOptions;
    }

    /** @return mixed[] */
    public function toArray()
    {
        return array_merge([
            'name'          => $this->_name,
            'type'          => $this->_type,
            'default'       => $this->_default,
            'notnull'       => $this->_notnull,
            'length'        => $this->_length,
            'precision'     => $this->_precision,
            'scale'         => $this->_scale,
            'fixed'         => $this->_fixed,
            'unsigned'      => $this->_unsigned,
            'autoincrement' => $this->_autoincrement,
            'columnDefinition' => $this->_columnDefinition,
            'comment' => $this->_comment,
        ], $this->_platformOptions, $this->_customSchemaOptions);
    }
}
