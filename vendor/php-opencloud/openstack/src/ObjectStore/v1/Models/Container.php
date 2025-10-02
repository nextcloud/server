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

    public const METADATA_PREFIX = 'X-Container-Meta-';

    /** @var int */
    public $objectCount;

    /** @var int */
    public $bytesUsed;

    /** @var string */
    public $name;

    /** @var array */
    public $metadata;

    protected $markerKey = 'name';

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
     *
     * @return \Generator<mixed, \OpenStack\ObjectStore\v1\Models\StorageObject>
     */
    public function listObjects(array $options = [], ?callable $mapFn = null): \Generator
    {
        $options = array_merge($options, ['name' => $this->name, 'format' => 'json']);

        $appendContainerNameFn       = function (StorageObject $resource) use ($mapFn) {
            $resource->containerName = $this->name;
            if ($mapFn) {
                call_user_func_array($mapFn, [&$resource]);
            }
        };

        return $this->model(StorageObject::class)->enumerate($this->api->getContainer(), $options, $appendContainerNameFn);
    }

    public function retrieve()
    {
        $response = $this->executeWithState($this->api->headContainer());
        $this->populateFromResponse($response);
    }

    /**
     * @param array $userOptions {@see \OpenStack\ObjectStore\v1\Api::putContainer}
     *
     * @return self
     */
    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->putContainer(), $userOptions);

        $this->populateFromResponse($response);
        $this->name = $userOptions['name'];

        return $this;
    }

    public function delete()
    {
        $this->executeWithState($this->api->deleteContainer());
    }

    public function mergeMetadata(array $metadata)
    {
        $response       = $this->execute($this->api->postContainer(), ['name' => $this->name, 'metadata' => $metadata]);
        $this->metadata = $this->parseMetadata($response);
    }

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
     * Verifies if provied segment index format for DLOs is valid.
     *
     * @param string $fmt The format of segment index name, e.g. %05d for 00001, 00002, etc.
     *
     * @return bool TRUE if the format is valid, FALSE if it is not
     */
    public function isValidSegmentIndexFormat($fmt)
    {
        $testValue1 = sprintf($fmt, 1);
        $testValue2 = sprintf($fmt, 10);

        // Test if different results of the same string length
        return ($testValue1 !== $testValue2) && (strlen($testValue1) === strlen($testValue2));
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
     * @param array $data {@see \OpenStack\ObjectStore\v1\Api::putObject}
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
        $segmentIndexFormat = isset($data['segmentIndexFormat']) ? $data['segmentIndexFormat'] : '%05d';

        if (!$this->isValidSegmentIndexFormat($segmentIndexFormat)) {
            throw new \InvalidArgumentException('The provided segmentIndexFormat is not valid.');
        }

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
                'name'          => sprintf('%s/'.$segmentIndexFormat, $segmentPrefix, ++$count),
                'stream'        => new LimitStream($stream, $segmentSize, ($count - 1) * $segmentSize),
                'containerName' => $segmentContainer,
            ]);
        }

        /** @var Promise $p */
        $p = function_exists('\GuzzleHttp\Promise\all')
            ? \GuzzleHttp\Promise\all($promises)
            : \GuzzleHttp\Promise\Utils::all($promises);
        $p->wait();

        return $this->createObject([
            'name'           => $data['name'],
            'objectManifest' => sprintf('%s/%s', $segmentContainer, $segmentPrefix),
        ]);
    }
}
