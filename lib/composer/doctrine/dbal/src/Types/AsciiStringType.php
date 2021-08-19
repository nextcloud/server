<?php

declare(strict_types=1);

namespace Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;

final class AsciiStringType extends StringType
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform)
    {
        return $platform->getAsciiStringTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return ParameterType::ASCII;
    }

    public function getName(): string
    {
        return Types::ASCII_STRING;
    }
}
