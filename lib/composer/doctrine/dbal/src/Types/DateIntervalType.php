<?php

namespace Doctrine\DBAL\Types;

use DateInterval;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Throwable;

use function substr;

/**
 * Type that maps interval string to a PHP DateInterval Object.
 */
class DateIntervalType extends Type
{
    public const FORMAT = '%RP%YY%MM%DDT%HH%IM%SS';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Types::DATEINTERVAL;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        $column['length'] = 255;

        return $platform->getVarcharTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateInterval) {
            return $value->format(self::FORMAT);
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'DateInterval']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null || $value instanceof DateInterval) {
            return $value;
        }

        $negative = false;

        if (isset($value[0]) && ($value[0] === '+' || $value[0] === '-')) {
            $negative = $value[0] === '-';
            $value    = substr($value, 1);
        }

        try {
            $interval = new DateInterval($value);

            if ($negative) {
                $interval->invert = 1;
            }

            return $interval;
        } catch (Throwable $exception) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), self::FORMAT, $exception);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
