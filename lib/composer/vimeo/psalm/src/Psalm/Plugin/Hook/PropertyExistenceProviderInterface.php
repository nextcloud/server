<?php
namespace Psalm\Plugin\Hook;

use Psalm\CodeLocation;
use Psalm\Context;
use Psalm\StatementsSource;

interface PropertyExistenceProviderInterface
{
    /**
     * @return array<string>
     */
    public static function getClassLikeNames() : array;

    /**
     * Use this hook for informing whether or not a property exists on a given object. If you know the property does
     * not exist, return false. If you aren't sure if it exists or not, return null and the default analysis will
     * continue to determine if the property actually exists.
     *
     */
    public static function doesPropertyExist(
        string $fq_classlike_name,
        string $property_name,
        bool $read_mode,
        ?StatementsSource $source = null,
        ?Context $context = null,
        ?CodeLocation $code_location = null
    ): ?bool;
}
