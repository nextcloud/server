<?php

/*
 * This file is part of the JsonSchema package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JsonSchema\Uri;

use JsonSchema\Exception\InvalidSchemaMediaTypeException;
use JsonSchema\Exception\JsonDecodingException;
use JsonSchema\Exception\ResourceNotFoundException;
use JsonSchema\Uri\Retrievers\FileGetContents;
use JsonSchema\Uri\Retrievers\UriRetrieverInterface;
use JsonSchema\UriRetrieverInterface as BaseUriRetrieverInterface;
use JsonSchema\Validator;

/**
 * Retrieves JSON Schema URIs
 *
 * @author Tyler Akins <fidian@rumkin.com>
 */
class UriRetriever implements BaseUriRetrieverInterface
{
    /**
     * @var array Map of URL translations
     */
    protected $translationMap = array(
        // use local copies of the spec schemas
        '|^https?://json-schema.org/draft-(0[34])/schema#?|' => 'package://dist/schema/json-schema-draft-$1.json'
    );

    /**
     * @var array A list of endpoints for media type check exclusion
     */
    protected $allowedInvalidContentTypeEndpoints = array(
        'http://json-schema.org/',
        'https://json-schema.org/'
    );

    /**
     * @var null|UriRetrieverInterface
     */
    protected $uriRetriever = null;

    /**
     * @var array|object[]
     *
     * @see loadSchema
     */
    private $schemaCache = array();

    /**
     * Adds an endpoint to the media type validation exclusion list
     *
     * @param string $endpoint
     */
    public function addInvalidContentTypeEndpoint($endpoint)
    {
        $this->allowedInvalidContentTypeEndpoints[] = $endpoint;
    }

    /**
     * Guarantee the correct media type was encountered
     *
     * @param UriRetrieverInterface $uriRetriever
     * @param string                $uri
     *
     * @return bool|void
     */
    public function confirmMediaType($uriRetriever, $uri)
    {
        $contentType = $uriRetriever->getContentType();

        if (is_null($contentType)) {
            // Well, we didn't get an invalid one
            return;
        }

        if (in_array($contentType, array(Validator::SCHEMA_MEDIA_TYPE, 'application/json'))) {
            return;
        }

        foreach ($this->allowedInvalidContentTypeEndpoints as $endpoint) {
            if (strpos($uri, $endpoint) === 0) {
                return true;
            }
        }

        throw new InvalidSchemaMediaTypeException(sprintf('Media type %s expected', Validator::SCHEMA_MEDIA_TYPE));
    }

    /**
     * Get a URI Retriever
     *
     * If none is specified, sets a default FileGetContents retriever and
     * returns that object.
     *
     * @return UriRetrieverInterface
     */
    public function getUriRetriever()
    {
        if (is_null($this->uriRetriever)) {
            $this->setUriRetriever(new FileGetContents());
        }

        return $this->uriRetriever;
    }

    /**
     * Resolve a schema based on pointer
     *
     * URIs can have a fragment at the end in the format of
     * #/path/to/object and we are to look up the 'path' property of
     * the first object then the 'to' and 'object' properties.
     *
     * @param object $jsonSchema JSON Schema contents
     * @param string $uri        JSON Schema URI
     *
     * @throws ResourceNotFoundException
     *
     * @return object JSON Schema after walking down the fragment pieces
     */
    public function resolvePointer($jsonSchema, $uri)
    {
        $resolver = new UriResolver();
        $parsed = $resolver->parse($uri);
        if (empty($parsed['fragment'])) {
            return $jsonSchema;
        }

        $path = explode('/', $parsed['fragment']);
        while ($path) {
            $pathElement = array_shift($path);
            if (!empty($pathElement)) {
                $pathElement = str_replace('~1', '/', $pathElement);
                $pathElement = str_replace('~0', '~', $pathElement);
                if (!empty($jsonSchema->$pathElement)) {
                    $jsonSchema = $jsonSchema->$pathElement;
                } else {
                    throw new ResourceNotFoundException(
                        'Fragment "' . $parsed['fragment'] . '" not found'
                        . ' in ' . $uri
                    );
                }

                if (!is_object($jsonSchema)) {
                    throw new ResourceNotFoundException(
                        'Fragment part "' . $pathElement . '" is no object '
                        . ' in ' . $uri
                    );
                }
            }
        }

        return $jsonSchema;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve($uri, $baseUri = null, $translate = true)
    {
        $resolver = new UriResolver();
        $resolvedUri = $fetchUri = $resolver->resolve($uri, $baseUri);

        //fetch URL without #fragment
        $arParts = $resolver->parse($resolvedUri);
        if (isset($arParts['fragment'])) {
            unset($arParts['fragment']);
            $fetchUri = $resolver->generate($arParts);
        }

        // apply URI translations
        if ($translate) {
            $fetchUri = $this->translate($fetchUri);
        }

        $jsonSchema = $this->loadSchema($fetchUri);

        // Use the JSON pointer if specified
        $jsonSchema = $this->resolvePointer($jsonSchema, $resolvedUri);

        if ($jsonSchema instanceof \stdClass) {
            $jsonSchema->id = $resolvedUri;
        }

        return $jsonSchema;
    }

    /**
     * Fetch a schema from the given URI, json-decode it and return it.
     * Caches schema objects.
     *
     * @param string $fetchUri Absolute URI
     *
     * @return object JSON schema object
     */
    protected function loadSchema($fetchUri)
    {
        if (isset($this->schemaCache[$fetchUri])) {
            return $this->schemaCache[$fetchUri];
        }

        $uriRetriever = $this->getUriRetriever();
        $contents = $this->uriRetriever->retrieve($fetchUri);
        $this->confirmMediaType($uriRetriever, $fetchUri);
        $jsonSchema = json_decode($contents);

        if (JSON_ERROR_NONE < $error = json_last_error()) {
            throw new JsonDecodingException($error);
        }

        $this->schemaCache[$fetchUri] = $jsonSchema;

        return $jsonSchema;
    }

    /**
     * Set the URI Retriever
     *
     * @param UriRetrieverInterface $uriRetriever
     *
     * @return $this for chaining
     */
    public function setUriRetriever(UriRetrieverInterface $uriRetriever)
    {
        $this->uriRetriever = $uriRetriever;

        return $this;
    }

    /**
     * Parses a URI into five main components
     *
     * @param string $uri
     *
     * @return array
     */
    public function parse($uri)
    {
        preg_match('|^(([^:/?#]+):)?(//([^/?#]*))?([^?#]*)(\?([^#]*))?(#(.*))?|', $uri, $match);

        $components = array();
        if (5 < count($match)) {
            $components =  array(
                'scheme'    => $match[2],
                'authority' => $match[4],
                'path'      => $match[5]
            );
        }

        if (7 < count($match)) {
            $components['query'] = $match[7];
        }

        if (9 < count($match)) {
            $components['fragment'] = $match[9];
        }

        return $components;
    }

    /**
     * Builds a URI based on n array with the main components
     *
     * @param array $components
     *
     * @return string
     */
    public function generate(array $components)
    {
        $uri = $components['scheme'] . '://'
             . $components['authority']
             . $components['path'];

        if (array_key_exists('query', $components)) {
            $uri .= $components['query'];
        }

        if (array_key_exists('fragment', $components)) {
            $uri .= $components['fragment'];
        }

        return $uri;
    }

    /**
     * Resolves a URI
     *
     * @param string $uri     Absolute or relative
     * @param string $baseUri Optional base URI
     *
     * @return string
     */
    public function resolve($uri, $baseUri = null)
    {
        $components = $this->parse($uri);
        $path = $components['path'];

        if ((array_key_exists('scheme', $components)) && ('http' === $components['scheme'])) {
            return $uri;
        }

        $baseComponents = $this->parse($baseUri);
        $basePath = $baseComponents['path'];

        $baseComponents['path'] = UriResolver::combineRelativePathWithBasePath($path, $basePath);

        return $this->generate($baseComponents);
    }

    /**
     * @param string $uri
     *
     * @return bool
     */
    public function isValid($uri)
    {
        $components = $this->parse($uri);

        return !empty($components);
    }

    /**
     * Set a URL translation rule
     */
    public function setTranslation($from, $to)
    {
        $this->translationMap[$from] = $to;
    }

    /**
     * Apply URI translation rules
     */
    public function translate($uri)
    {
        foreach ($this->translationMap as $from => $to) {
            $uri = preg_replace($from, $to, $uri);
        }

        // translate references to local files within the json-schema package
        $uri = preg_replace('|^package://|', sprintf('file://%s/', realpath(__DIR__ . '/../../..')), $uri);

        return $uri;
    }
}
