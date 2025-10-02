<?php

namespace Doctrine\DBAL\Types;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;

use function get_class;

/**
 * DateTime type accepting additional information about timezone offsets.
 *
 * Caution: Databases are not necessarily experts at storing timezone related
 * data of dates. First, of not all the supported vendors support storing Timezone data, and some of
 * them only use the offset to calculate the timestamp in its default timezone (usually UTC) and persist
 * the value without the offset information. They even don't save the actual timezone names attached
 * to a DateTime instance (for example "Europe/Berlin" or "America/Montreal") but the current offset
 * of them related to UTC. That means, depending on daylight saving times or not, you may get different
 * offsets.
 *
 * This datatype makes only sense to use, if your application only needs to accept the timezone offset,
 * not the actual timezone that uses transitions. Otherwise your DateTime instance
 * attached with a timezone such as "Europe/Berlin" gets saved into the database with
 * the offset and re-created from persistence with only the offset, not the original timezone
 * attached.
 */
class DateTimeTzType extends Type implements PhpDateTimeMappingType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Types::DATETIMETZ_MUTABLE;
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getDateTimeTzTypeDeclarationSQL($column);
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
                DateTimeTzImmutableType::class,
                __FUNCTION__,
            );
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getDateTimeTzFormatString());
        }

        throw ConversionException::conversionFailedInvalidType(
            $value,
            $this->getName(),
            ['null', DateTime::class],
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
                DateTimeTzImmutableType::class,
                __FUNCTION__,
            );
        }

        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }

        $dateTime = DateTime::createFromFormat($platform->getDateTimeTzFormatString(), $value);
        if ($dateTime !== false) {
            return $dateTime;
        }

        throw ConversionException::conversionFailedFormat(
            $value,
            $this->getName(),
            $platform->getDateTimeTzFormatString(),
        );
    }
}
