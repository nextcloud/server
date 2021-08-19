<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasMetadata;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;
use Psr\Http\Message\ResponseInterface;

/**
 * Represents a Compute v2 Image.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class Image extends OperatorResource implements Listable, Retrievable, Deletable, HasMetadata
{
    /** @var string */
    public $id;

    /** @var array */
    public $links;

    /** @var array */
    public $metadata;

    /** @var int */
    public $minDisk;

    /** @var int */
    public $minRam;

    /** @var string */
    public $name;

    /** @var string */
    public $progress;

    /** @var string */
    public $status;

    /** @var \DateTimeImmutable */
    public $created;

    /** @var \DateTimeImmutable */
    public $updated;

    protected $resourceKey  = 'image';
    protected $resourcesKey = 'images';

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'created' => new Alias('created', \DateTimeImmutable::class),
            'updated' => new Alias('updated', \DateTimeImmutable::class),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getImage(), ['id' => (string) $this->id]);
        $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->execute($this->api->deleteImage(), ['id' => (string) $this->id]);
    }

    /**
     * Retrieves metadata from the API.
     */
    public function getMetadata(): array
    {
        $response = $this->execute($this->api->getImageMetadata(), ['id' => $this->id]);

        return $this->parseMetadata($response);
    }

    /**
     * Resets all the metadata for this image with the values provided. All existing metadata keys
     * will either be replaced or removed.
     *
     * @param array $metadata {@see \OpenStack\Compute\v2\Api::putImageMetadata}
     */
    public function resetMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->putImageMetadata(), ['id' => $this->id, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * Merges the existing metadata for the image with the values provided. Any existing keys
     * referenced in the user options will be replaced with the user's new values. All other
     * existing keys will remain unaffected.
     *
     * @param array $metadata {@see \OpenStack\Compute\v2\Api::postImageMetadata}
     */
    public function mergeMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->postImageMetadata(), ['id' => $this->id, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * Retrieve the value for a specific metadata key.
     *
     * @param string $key {@see \OpenStack\Compute\v2\Api::getImageMetadataKey}
     *
     * @return mixed
     */
    public function getMetadataItem(string $key)
    {
        $response             = $this->execute($this->api->getImageMetadataKey(), ['id' => $this->id, 'key' => $key]);
        $value                = $this->parseMetadata($response)[$key];
        $this->metadata[$key] = $value;

        return $value;
    }

    /**
     * Remove a specific metadata key.
     *
     * @param string $key {@see \OpenStack\Compute\v2\Api::deleteImageMetadataKey}
     */
    public function deleteMetadataItem(string $key)
    {
        if (isset($this->metadata[$key])) {
            unset($this->metadata[$key]);
        }

        $this->execute($this->api->deleteImageMetadataKey(), ['id' => $this->id, 'key' => $key]);
    }

    public function parseMetadata(ResponseInterface $response): array
    {
        return Utils::jsonDecode($response)['metadata'];
    }
}
