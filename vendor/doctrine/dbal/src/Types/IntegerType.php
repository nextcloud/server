<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL INT to a PHP integer.
 */
class IntegerType extends Type implements PhpIntegerMappingType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Types::INTEGER;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     *
     * @param T $value
     *
     * @return (T is null ? null : int)
     *
     * @template T
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value === null ? null : (int) $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getBindingType()
    {
        return ParameterType::INTEGER;
    }
}
