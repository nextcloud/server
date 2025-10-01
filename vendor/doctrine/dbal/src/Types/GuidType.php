<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\Deprecations\Deprecation;

/**
 * Represents a GUID/UUID datatype (both are actually synonyms) in the database.
 */
class GuidType extends StringType
{
    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return Types::GUID;
    }

    /**
     * {@inheritDoc}
     *
     * @deprecated
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        Deprecation::triggerIfCalledFromOutside(
            'doctrine/dbal',
            'https://github.com/doctrine/dbal/pull/5509',
            '%s is deprecated.',
            __METHOD__,
        );

        return ! $platform->hasNativeGuidType();
    }
}
