<?php

declare(strict_types=1);

namespace JsonSchema\Tool;

use JsonSchema\Exception\JsonDecodingException;
use JsonSchema\Exception\RuntimeException;

class DeepCopy
{
    /**
     * @param mixed $input
     *
     * @return mixed
     */
    public static function copyOf($input)
    {
        $json = json_encode($input);
        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new JsonDecodingException($error);
        }

        if ($json === false) {
            throw new RuntimeException('Failed to encode input to JSON: ' . json_last_error_msg());
        }

        return json_decode($json, self::isAssociativeArray($input));
    }

    /**
     * @param mixed $input
     */
    private static function isAssociativeArray($input): bool
    {
        return is_array($input) && array_keys($input) !== range(0, count($input) - 1);
    }
}
