<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps a database BIGINT to a PHP string.
 */
class BigIntType extends Type implements PhpIntegerMappingType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Types::BIGINT;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getBigIntTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getBindingType()
    {
        return ParameterType::STRING;
    }

    /**
     * {@inheritDoc}
     *
     * @param T $value
     *
     * @return (T is null ? null : string)
     *
     * @template T
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        return $value === null ? null : (string) $value;
    }
}
