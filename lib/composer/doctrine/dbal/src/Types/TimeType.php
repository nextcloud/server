<?php

namespace Doctrine\DBAL\Types;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Type that maps an SQL TIME to a PHP DateTime object.
 */
class TimeType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Types::TIME_MUTABLE;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getTimeTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getTimeFormatString());
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'DateTime']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $val = DateTime::createFromFormat('!' . $platform->getTimeFormatString(), $value);
        if ($val === false) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getTimeFormatString()
            );
        }

        return $val;
    }
}
