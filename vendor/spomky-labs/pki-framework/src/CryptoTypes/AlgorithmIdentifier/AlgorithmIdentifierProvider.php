<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier;

/**
 * Interface to provide lookup from OID to class name of specific algorithm identifier type implementations.
 *
 * This allows AlgorithmIdentifier types to be implemented in external libraries and to use AlgorithmIdentifierFactory
 * to resolve them.
 */
interface AlgorithmIdentifierProvider
{
    /**
     * Check whether this provider supports algorithm identifier of given OID.
     *
     * @param string $oid Object identifier in dotted format
     */
    public function supportsOID(string $oid): bool;

    /**
     * Get the name of a class that implements algorithm identifier for given OID.
     *
     * @param string $oid Object identifier in dotted format
     *
     * @return string Fully qualified name of a class that extends
     * SpecificAlgorithmIdentifier
     */
    public function getClassByOID(string $oid): string;
}
