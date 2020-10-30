<?php
namespace Psalm\Plugin\Hook;

use Psalm\StatementsSource;

interface FunctionExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getFunctionIds() : array;

    /**
     * Use this hook for informing whether or not a global function exists. If you know the function does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis
     * will continue to determine if the function actually exists.
     *
     */
    public static function doesFunctionExist(
        StatementsSource $statements_source,
        string $function_id
    ): ?bool;
}
