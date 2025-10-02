<?php

declare(strict_types=1);

namespace JsonSchema;

interface SchemaStorageInterface
{
    /**
     * Adds schema with given identifier
     *
     * @param object $schema
     */
    public function addSchema(string $id, $schema = null): void;

    /**
     * Returns schema for given identifier, or null if it does not exist
     *
     * @return object
     */
    public function getSchema(string $id);

    /**
     * Returns schema for given reference with all sub-references resolved
     *
     * @return object
     */
    public function resolveRef(string $ref);

    /**
     * Returns schema referenced by '$ref' property
     *
     * @param mixed $refSchema
     *
     * @return object
     */
    public function resolveRefSchema($refSchema);
}
