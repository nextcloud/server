<?php

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;

use function count;
use function explode;
use function implode;
use function is_array;
use function is_resource;
use function stream_get_contents;

/**
 * Array Type which can be used for simple values.
 *
 * Only use this type if you are sure that your values cannot contain a ",".
 */
class SimpleArrayType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if (! is_array($value) || count($value) === 0) {
            return null;
        }

        return implode(',', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return [];
        }

        $value = is_resource($value) ? stream_get_contents($value) : $value;

        return explode(',', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Types::SIMPLE_ARRAY;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
