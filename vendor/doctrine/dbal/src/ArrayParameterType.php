<?php

namespace Doctrine\DBAL;

final class ArrayParameterType
{
    /**
     * Represents an array of ints to be expanded by Doctrine SQL parsing.
     */
    public const INTEGER = ParameterType::INTEGER + Connection::ARRAY_PARAM_OFFSET;

    /**
     * Represents an array of strings to be expanded by Doctrine SQL parsing.
     */
    public const STRING = ParameterType::STRING + Connection::ARRAY_PARAM_OFFSET;

    /**
     * Represents an array of ascii strings to be expanded by Doctrine SQL parsing.
     */
    public const ASCII = ParameterType::ASCII + Connection::ARRAY_PARAM_OFFSET;

    /**
     * Represents an array of ascii strings to be expanded by Doctrine SQL parsing.
     */
    public const BINARY = ParameterType::BINARY + Connection::ARRAY_PARAM_OFFSET;

    /**
     * @internal
     *
     * @phpstan-param self::* $type
     *
     * @phpstan-return ParameterType::INTEGER|ParameterType::STRING|ParameterType::ASCII|ParameterType::BINARY
     */
    public static function toElementParameterType(int $type): int
    {
        return $type - Connection::ARRAY_PARAM_OFFSET;
    }

    private function __construct()
    {
    }
}
