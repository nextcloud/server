<?php

namespace JsonSchema;

interface SchemaStorageInterface
{
    /**
     * Adds schema with given identifier
     *
     * @param string $id
     * @param object $schema
     */
    public function addSchema($id, $schema = null);

    /**
     * Returns schema for given identifier, or null if it does not exist
     *
     * @param string $id
     *
     * @return object
     */
    public function getSchema($id);

    /**
     * Returns schema for given reference with all sub-references resolved
     *
     * @param string $ref
     *
     * @return object
     */
    public function resolveRef($ref);

    /**
     * Returns schema referenced by '$ref' property
     *
     * @param mixed $refSchema
     *
     * @return object
     */
    public function resolveRefSchema($refSchema);
}
