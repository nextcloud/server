<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

use Psr\Http\Message\ResponseInterface;

interface HasMetadata
{
    /**
     * Retrieves the metadata for the resource in the form of an associative array or hash. Each key represents the
     * metadata item's name, and each value represents the metadata item's remote value.
     */
    public function getMetadata(): array;

    /**
     * Merges a set of new values with those which already exist (on the remote API) for a resource. For example, if
     * the resource has this metadata already set:.
     *
     *  Foo: val1
     *  Bar: val2
     *
     * and mergeMetadata(['Foo' => 'val3', 'Baz' => 'val4']); is called, then the resource will have the following
     * metadata:
     *
     *  Foo: val3
     *  Bar: val2
     *  Baz: val4
     *
     * You will notice that any metadata items which are not specified in the call are preserved.
     *
     * @param array $metadata The new metadata items
     */
    public function mergeMetadata(array $metadata);

    /**
     * Replaces all of the existing metadata items for a resource with a new set of values. Any metadata items which
     * are not provided in the call are removed from the resource. For example, if the resource has this metadata
     * already set:.
     *
     *  Foo: val1
     *  Bar: val2
     *
     * and resetMetadata(['Foo' => 'val3', 'Baz' => 'val4']); is called, then the resource will have the following
     * metadata:
     *
     *  Foo: val3
     *  Baz: val4
     *
     * @param array $metadata The new metadata items
     */
    public function resetMetadata(array $metadata);

    /**
     * Extracts metadata from a response object and returns it in the form of an associative array.
     */
    public function parseMetadata(ResponseInterface $response): array;
}
