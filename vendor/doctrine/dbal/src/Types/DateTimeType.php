<?php

namespace Doctrine\DBAL\Types;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;
use Exception;

use function get_class;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to a PHP DateTime object.
 */
class DateTimeType extends Type implements PhpDateTimeMappingType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Types::DATETIME_MUTABLE;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
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
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof DateTimeImmutable) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6017',
                'Passing an instance of %s is deprecated, use %s::%s() instead.',
                get_class($value),
                DateTimeImmutableType::class,
                __FUNCTION__,
            );
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', DateTime::class, DateTimeImmutable::class],
        );
    }

    /**
     * {@inheritDoc}
     *
     * @param T $value
     *
     * @return (T is null ? null : DateTimeInterface)
     *
     * @template T
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value instanceof DateTimeImmutable) {
            Deprecation::triggerIfCalledFromOutside(
                'doctrine/dbal',
                'https://github.com/doctrine/dbal/pull/6017',
                'Passing an instance of %s is deprecated, use %s::%s() instead.',
                get_class($value),
                DateTimeImmutableType::class,
                __FUNCTION__,
            );
        }

        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $dateTime = DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);

        if ($dateTime !== false) {
            return $dateTime;
        }

        try {
            return new DateTime($value);
        } catch (Exception $e) {
            throw ConversionException::conversionFailedFormat(
                $value,
                $this->getName(),
                $platform->getDateTimeFormatString(),
                $e,
            );
        }
    }
}
