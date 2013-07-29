<?php

namespace Guzzle\Inflection;

/**
 * Inflector interface used to convert the casing of words
 */
interface InflectorInterface
{
    /**
     * Converts strings from camel case to snake case (e.g. CamelCase camel_case).
     *
     * @param string $word Word to convert to snake case
     *
     * @return string
     */
    public function snake($word);

    /**
     * Converts strings from snake_case to upper CamelCase
     *
     * @param string $word Value to convert into upper CamelCase
     *
     * @return string
     */
    public function camel($word);
}
