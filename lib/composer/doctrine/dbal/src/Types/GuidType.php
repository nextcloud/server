<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Represents a GUID/UUID datatype (both are actually synonyms) in the database.
 */
class GuidType extends StringType
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Types::GUID;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return ! $platform->hasNativeGuidType();
    }
}
