<?php

declare(strict_types=1);

namespace OpenStack\ObjectStore\v1\Models;

use OpenStack\Common\Resource\HasMetadata;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \OpenStack\ObjectStore\v1\Api $api
 */
class Account extends OperatorResource implements Retrievable, HasMetadata
{
    use MetadataTrait;

    const METADATA_PREFIX = 'X-Account-Meta-';

    /** @var int */
    public $objectCount;

    /** @var int */
    public $bytesUsed;

    /** @var int */
    public $containerCount;

    /** @var array */
    public $metadata;

    /** @var string */
    public $tempUrl;

    /**
     * {@inheritdoc}
     */
    public function populateFromResponse(ResponseInterface $response): self
    {
        parent::populateFromResponse($response);

        $this->containerCount = $response->getHeaderLine('X-Account-Container-Count');
        $this->objectCount    = $response->getHeaderLine('X-Account-Object-Count');
        $this->bytesUsed      = $response->getHeaderLine('X-Account-Bytes-Used');
        $this->tempUrl        = $response->getHeaderLine('X-Account-Meta-Temp-URL-Key');
        $this->metadata       = $this->parseMetadata($response);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->headAccount());
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function mergeMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->postAccount(), ['metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * {@inheritdoc}
     */
    public function resetMetadata(array $metadata)
    {
        $options = [
            'removeMetadata' => [],
            'metadata'       => $metadata,
        ];

        foreach ($this->getMetadata() as $key => $val) {
            if (!array_key_exists($key, $metadata)) {
                $options['removeMetadata'][$key] = 'True';
            }
        }

        $response       = $this->execute($this->api->postAccount(), $options);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        $response = $this->execute($this->api->headAccount());

        return $this->parseMetadata($response);
    }
}
