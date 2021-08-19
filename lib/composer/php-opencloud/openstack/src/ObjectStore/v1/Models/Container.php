<?php

declare(strict_types=1);

namespace OpenStack\ObjectStore\v1\Models;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\LimitStream;
use OpenStack\Common\Error\BadResponseError;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\HasMetadata;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use Psr\Http\Message\ResponseInterface;

/**
 * @property \OpenStack\ObjectStore\v1\Api $api
 */
class Container extends OperatorResource implements Creatable, Deletable, Retrievable, Listable, HasMetadata
{
    use MetadataTrait;

    const METADATA_PREFIX = 'X-Container-Meta-';

    /** @var int */
    public $objectCount;

    /** @var int */
    public $bytesUsed;

    /** @var string */
    public $name;

    /** @var array */
    public $metadata;

    protected $markerKey = 'name';

    /**
     * {@inheritdoc}
     */
    public function populateFromResponse(ResponseInterface $response): self
    {
        parent::populateFromResponse($response);

        $this->objectCount = $response->getHeaderLine('X-Container-Object-Count');
        $this->bytesUsed   = $response->getHeaderLine('X-Container-Bytes-Used');
        $this->metadata    = $this->parseMetadata($response);

        return $this;
    }

    /**
     * Retrieves a collection of object resources in the form of a generator.
     *
     * @param array         $options {@see \OpenStack\ObjectStore\v1\Api::getContainer}
     * @param callable|null $mapFn   allows a function to be mapped over each element
     */
    public function listObjects(array $options = [], callable $mapFn = null): \Generator
    {
        $options = array_merge($options, ['name' => $this->name, 'format' => 'json']);

        $appendContainerNameFn = function (StorageObject $resource) use ($mapFn) {
            $resource->containerName = $this->name;
            if ($mapFn) {
                call_user_func_array($mapFn, [&$resource]);
            }
        };

        return $this->model(StorageObject::class)->enumerate($this->api->getContainer(), $options, $appendContainerNameFn);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->executeWithState($this->api->headContainer());
        $this->populateFromResponse($response);
    }

    /**
     * @param array $data {@see \OpenStack\ObjectStore\v1\Api::putContainer}
     *
     * @return $this
     */
    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->putContainer(), $data);

        $this->populateFromResponse($response);
        $this->name = $data['name'];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->executeWithState($this->api->deleteContainer());
    }

    /**
     * {@inheritdoc}
     */
    public function mergeMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->postContainer(), ['name' => $this->name, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * {@inheritdoc}
     */
    public function resetMetadata(array $metadata)
    {
        $options = [
            'name'           => $this->name,
            'removeMetadata' => [],
            'metadata'       => $metadata,
        ];

        foreach ($this->getMetadata() as $key => $val) {
            if (!array_key_exists($key, $metadata)) {
                $options['removeMetadata'][$key] = 'True';
            }
        }

        $response       = $this->execute($this->api->postContainer(), $options);
        $this->metadata = $this->parseMetadata($response);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata(): array
    {
        $response = $this->executeWithState($this->api->headContainer());

        return $this->parseMetadata($response);
    }

    /**
     * Retrieves an StorageObject and populates its `name` and `containerName` properties according to the name provided and
     * the name of this container. A HTTP call will not be executed by default - you need to call
     * {@see StorageObject::retrieve} or {@see StorageObject::download} on the returned StorageObject object to do that.
     *
     * @param string $name The name of the object
     */
    public function getObject($name): StorageObject
    {
        return $this->model(StorageObject::class, ['containerName' => $this->name, 'name' => $name]);
    }

    /**
     * Identifies whether an object exists in this container.
     *
     * @param string $name the name of the object
     *
     * @return bool TRUE if the object exists, FALSE if it does not
     *
     * @throws BadResponseError for any other HTTP error which does not have a 404 Not Found status
     * @throws \Exception       for any other type of fatal error
     */
    public function objectExists(string $name): bool
    {
        try {
            $this->getObject($name)->retrieve();

            return true;
        } catch (BadResponseError $e) {
            if (404 === $e->getResponse()->getStatusCode()) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Creates a single object according to the values provided.
     *
     * @param array $data {@see \OpenStack\ObjectStore\v1\Api::putObject}
     *
     * @return object
     */
    public function createObject(array $data): StorageObject
    {
        return $this->model(StorageObject::class)->create($data + ['containerName' => $this->name]);
    }

    /**
     * Creates a Dynamic Large Object by chunking a file into smaller segments and uploading them into a holding
     * container. When this completes, a manifest file is uploaded which references the prefix of the segments,
     * allowing concatenation when a request is executed against the manifest.
     *
     * @param array  $data                     {@see \OpenStack\ObjectStore\v1\Api::putObject}
     * @param int    $data['segmentSize']      The size in Bytes of each segment
     * @param string $data['segmentContainer'] The container to which each segment will be uploaded
     * @param string $data['segmentPrefix']    The prefix that will come before each segment. If omitted, a default
     *                                         is used: name/timestamp/filesize
     */
    public function createLargeObject(array $data): StorageObject
    {
        /** @var \Psr\Http\Message\StreamInterface $stream */
        $stream = $data['stream'];

        $segmentSize      = isset($data['segmentSize']) ? $data['segmentSize'] : 1073741824;
        $segmentContainer = isset($data['segmentContainer']) ? $data['segmentContainer'] : $this->name.'_segments';
        $segmentPrefix    = isset($data['segmentPrefix'])
            ? $data['segmentPrefix']
            : sprintf('%s/%s/%d', $data['name'], microtime(true), $stream->getSize());

        /** @var \OpenStack\ObjectStore\v1\Service $service */
        $service = $this->getService();
        if (!$service->containerExists($segmentContainer)) {
            $service->createContainer(['name' => $segmentContainer]);
        }

        $promises      = [];
        $count         = 0;
        $totalSegments = $stream->getSize() / $segmentSize;

        while (!$stream->eof() && $count < $totalSegments) {
            $promises[] = $this->model(StorageObject::class)->createAsync([
                'name'          => sprintf('%s/%d', $segmentPrefix, ++$count),
                'stream'        => new LimitStream($stream, $segmentSize, ($count - 1) * $segmentSize),
                'containerName' => $segmentContainer,
            ]);
        }

        /** @var Promise $p */
        $p = \GuzzleHttp\Promise\all($promises);
        $p->wait();

        return $this->createObject([
            'name'           => $data['name'],
            'objectManifest' => sprintf('%s/%s', $segmentContainer, $segmentPrefix),
        ]);
    }
}
