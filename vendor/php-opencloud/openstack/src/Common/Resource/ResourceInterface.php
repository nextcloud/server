<?php

declare(strict_types=1);

namespace OpenStack\Common\Resource;

use Psr\Http\Message\ResponseInterface;

/**
 * Represents an API resource.
 */
interface ResourceInterface
{
    /**
     * All models which represent an API resource should be able to be populated
     * from a {@see ResponseInterface} object.
     *
     * @return self
     */
    public function populateFromResponse(ResponseInterface $response);

    /**
     * @return self
     */
    public function populateFromArray(array $data);

    /**
     * @template T of \OpenStack\Common\Resource\ResourceInterface
     *
     * @param class-string<T> $class the name of the model class
     * @param mixed           $data  either a {@see ResponseInterface} or data array that will populate the newly
     *                               created model class
     *
     * @return T
     */
    public function model(string $class, $data = null): ResourceInterface;
}
