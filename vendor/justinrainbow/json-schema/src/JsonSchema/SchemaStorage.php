<?php

declare(strict_types=1);

namespace JsonSchema;

use JsonSchema\Constraints\BaseConstraint;
use JsonSchema\Entity\JsonPointer;
use JsonSchema\Exception\UnresolvableJsonPointerException;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;

class SchemaStorage implements SchemaStorageInterface
{
    public const INTERNAL_PROVIDED_SCHEMA_URI = 'internal://provided-schema/';

    protected $uriRetriever;
    protected $uriResolver;
    protected $schemas = [];

    public function __construct(
        ?UriRetrieverInterface $uriRetriever = null,
        ?UriResolverInterface $uriResolver = null
    ) {
        $this->uriRetriever = $uriRetriever ?: new UriRetriever();
        $this->uriResolver = $uriResolver ?: new UriResolver();
    }

    /**
     * @return UriRetrieverInterface
     */
    public function getUriRetriever()
    {
        return $this->uriRetriever;
    }

    /**
     * @return UriResolverInterface
     */
    public function getUriResolver()
    {
        return $this->uriResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function addSchema(string $id, $schema = null): void
    {
        if (is_null($schema) && $id !== self::INTERNAL_PROVIDED_SCHEMA_URI) {
            // if the schema was user-provided to Validator and is still null, then assume this is
            // what the user intended, as there's no way for us to retrieve anything else. User-supplied
            // schemas do not have an associated URI when passed via Validator::validate().
            $schema = $this->uriRetriever->retrieve($id);
        }

        // cast array schemas to object
        if (is_array($schema)) {
            $schema = BaseConstraint::arrayToObjectRecursive($schema);
        }

        // workaround for bug in draft-03 & draft-04 meta-schemas (id & $ref defined with incorrect format)
        // see https://github.com/json-schema-org/JSON-Schema-Test-Suite/issues/177#issuecomment-293051367
        if (is_object($schema) && property_exists($schema, 'id')) {
            if ($schema->id === 'http://json-schema.org/draft-04/schema#') {
                $schema->properties->id->format = 'uri-reference';
            } elseif ($schema->id === 'http://json-schema.org/draft-03/schema#') {
                $schema->properties->id->format = 'uri-reference';
                $schema->properties->{'$ref'}->format = 'uri-reference';
            }
        }

        $this->scanForSubschemas($schema, $id);

        // resolve references
        $this->expandRefs($schema, $id);

        $this->schemas[$id] = $schema;
    }

    /**
     * Recursively resolve all references against the provided base
     *
     * @param mixed $schema
     */
    private function expandRefs(&$schema, ?string $parentId = null): void
    {
        if (!is_object($schema)) {
            if (is_array($schema)) {
                foreach ($schema as &$member) {
                    $this->expandRefs($member, $parentId);
                }
            }

            return;
        }

        if (property_exists($schema, '$ref') && is_string($schema->{'$ref'})) {
            $refPointer = new JsonPointer($this->uriResolver->resolve($schema->{'$ref'}, $parentId));
            $schema->{'$ref'} = (string) $refPointer;
        }

        foreach ($schema as $propertyName => &$member) {
            if (in_array($propertyName, ['enum', 'const'])) {
                // Enum and const don't allow $ref as a keyword, see https://github.com/json-schema-org/JSON-Schema-Test-Suite/pull/445
                continue;
            }

            $childId = $parentId;
            if (property_exists($schema, 'id') && is_string($schema->id) && $childId !== $schema->id) {
                $childId = $this->uriResolver->resolve($schema->id, $childId);
            }

            $this->expandRefs($member, $childId);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(string $id)
    {
        if (!array_key_exists($id, $this->schemas)) {
            $this->addSchema($id);
        }

        return $this->schemas[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRef(string $ref, $resolveStack = [])
    {
        $jsonPointer = new JsonPointer($ref);

        // resolve filename for pointer
        $fileName = $jsonPointer->getFilename();
        if (!strlen($fileName)) {
            throw new UnresolvableJsonPointerException(sprintf(
                "Could not resolve fragment '%s': no file is defined",
                $jsonPointer->getPropertyPathAsString()
            ));
        }

        // get & process the schema
        $refSchema = $this->getSchema($fileName);
        foreach ($jsonPointer->getPropertyPaths() as $path) {
            if (is_object($refSchema) && property_exists($refSchema, $path)) {
                $refSchema = $this->resolveRefSchema($refSchema->{$path}, $resolveStack);
            } elseif (is_array($refSchema) && array_key_exists($path, $refSchema)) {
                $refSchema = $this->resolveRefSchema($refSchema[$path], $resolveStack);
            } else {
                throw new UnresolvableJsonPointerException(sprintf(
                    'File: %s is found, but could not resolve fragment: %s',
                    $jsonPointer->getFilename(),
                    $jsonPointer->getPropertyPathAsString()
                ));
            }
        }

        return $refSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function resolveRefSchema($refSchema, $resolveStack = [])
    {
        if (is_object($refSchema) && property_exists($refSchema, '$ref') && is_string($refSchema->{'$ref'})) {
            if (in_array($refSchema, $resolveStack, true)) {
                throw new UnresolvableJsonPointerException(sprintf(
                    'Dereferencing a pointer to %s results in an infinite loop',
                    $refSchema->{'$ref'}
                ));
            }
            $resolveStack[] = $refSchema;

            return $this->resolveRef($refSchema->{'$ref'}, $resolveStack);
        }

        return $refSchema;
    }

    /**
     * @param mixed $schema
     */
    private function scanForSubschemas($schema, string $parentId): void
    {
        if (!$schema instanceof \stdClass  && !is_array($schema)) {
            return;
        }

        foreach ($schema as $propertyName => $potentialSubSchema) {
            if (!is_object($potentialSubSchema)) {
                continue;
            }

            if (property_exists($potentialSubSchema, 'id') && is_string($potentialSubSchema->id) && property_exists($potentialSubSchema, 'type')) {
                // Enum and const don't allow id as a keyword, see https://github.com/json-schema-org/JSON-Schema-Test-Suite/pull/471
                if (in_array($propertyName, ['enum', 'const'])) {
                    continue;
                }

                // Found sub schema
                $this->addSchema($this->uriResolver->resolve($potentialSubSchema->id, $parentId), $potentialSubSchema);
            }

            $this->scanForSubschemas($potentialSubSchema, $parentId);
        }
    }
}
