<?php

namespace Doctrine\DBAL\Schema;

use Doctrine\Deprecations\Deprecation;

use function in_array;

/**
 * Represents the change of a column.
 */
class ColumnDiff
{
    /**
     * @deprecated Use {@see $fromColumn} and {@see Column::getName()} instead.
     *
     * @var string
     */
    public $oldColumnName;

    /**
     * @internal Use {@see getNewColumn()} instead.
     *
     * @var Column
     */
    public $column;

    /**
     * @deprecated Use {@see hasTypeChanged()}, {@see hasLengthChanged()}, {@see hasPrecisionChanged()},
     * {@see hasScaleChanged()}, {@see hasUnsignedChanged()}, {@see hasFixedChanged()}, {@see hasNotNullChanged()},
     * {@see hasDefaultChanged()}, {@see hasAutoIncrementChanged()} or {@see hasCommentChanged()} instead.
     *
     * @var string[]
     */
    public $changedProperties = [];

    /**
     * @internal Use {@see getOldColumn()} instead.
     *
     * @var Column|null
     */
    public $fromColumn;

    /**
     * @internal The diff can be only instantiated by a {@see Comparator}.
     *
     * @param string   $oldColumnName
     * @param string[] $changedProperties
     */
    public function __construct(
        $oldColumnName,
        Column $column,
        array $changedProperties = [],
        ?Column $fromColumn = null
    ) {
        if ($fromColumn === null) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/4785',
                'Not passing the $fromColumn to %s is deprecated.',
                __METHOD__,
            );
        }

        $this->oldColumnName     = $oldColumnName;
        $this->column            = $column;
        $this->changedProperties = $changedProperties;
        $this->fromColumn        = $fromColumn;
    }

    public function getOldColumn(): ?Column
    {
        return $this->fromColumn;
    }

    public function getNewColumn(): Column
    {
        return $this->column;
    }

    public function hasTypeChanged(): bool
    {
        return $this->hasChanged('type');
    }

    public function hasLengthChanged(): bool
    {
        return $this->hasChanged('length');
    }

    public function hasPrecisionChanged(): bool
    {
        return $this->hasChanged('precision');
    }

    public function hasScaleChanged(): bool
    {
        return $this->hasChanged('scale');
    }

    public function hasUnsignedChanged(): bool
    {
        return $this->hasChanged('unsigned');
    }

    public function hasFixedChanged(): bool
    {
        return $this->hasChanged('fixed');
    }

    public function hasNotNullChanged(): bool
    {
        return $this->hasChanged('notnull');
    }

    public function hasDefaultChanged(): bool
    {
        return $this->hasChanged('default');
    }

    public function hasAutoIncrementChanged(): bool
    {
        return $this->hasChanged('autoincrement');
    }

    public function hasCommentChanged(): bool
    {
        return $this->hasChanged('comment');
    }

    /**
     * @deprecated Use {@see hasTypeChanged()}, {@see hasLengthChanged()}, {@see hasPrecisionChanged()},
     * {@see hasScaleChanged()}, {@see hasUnsignedChanged()}, {@see hasFixedChanged()}, {@see hasNotNullChanged()},
     * {@see hasDefaultChanged()}, {@see hasAutoIncrementChanged()} or {@see hasCommentChanged()} instead.
     *
     * @param string $propertyName
     *
     * @return bool
     */
    public function hasChanged($propertyName)
    {
        return in_array($propertyName, $this->changedProperties, true);
    }

    /**
     * @deprecated Use {@see $fromColumn} instead.
     *
     * @return Identifier
     */
    public function getOldColumnName()
    {
        Deprecation::trigger(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5622',
            '%s is deprecated. Use $fromColumn instead.',
            __METHOD__,
        );

        if ($this->fromColumn !== null) {
            $name  = $this->fromColumn->getName();
            $quote = $this->fromColumn->isQuoted();
        } else {
            $name  = $this->oldColumnName;
            $quote = false;
        }

        return new Identifier($name, $quote);
    }
}
