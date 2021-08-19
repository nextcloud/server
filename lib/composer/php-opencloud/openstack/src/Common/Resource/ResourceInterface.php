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
     * @return mixed
     */
    public function populateFromArray(array $data);

    /**
     * @param string $name the name of the model class
     * @param mixed  $data either a {@see ResponseInterface} or data array that will populate the newly
     *                     created model class
     *
     * @return \OpenStack\Common\Resource\ResourceInterface
     */
    public function model(string $class, $data = null): ResourceInterface;
}
