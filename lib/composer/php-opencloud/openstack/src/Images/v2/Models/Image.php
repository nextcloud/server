<?php

declare(strict_types=1);

namespace OpenStack\Images\v2\Models;

use OpenStack\Common\JsonSchema\Schema;
use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;
use OpenStack\Images\v2\JsonPatch;
use Psr\Http\Message\StreamInterface;

/**
 * @property \OpenStack\Images\v2\Api $api
 */
class Image extends OperatorResource implements Creatable, Listable, Retrievable, Deletable
{
    /** @var string */
    public $status;

    /** @var string */
    public $name;

    /** @var array */
    public $tags;

    /** @var string */
    public $containerFormat;

    /** @var \DateTimeImmutable */
    public $createdAt;

    /** @var string */
    public $diskFormat;

    /** @var \DateTimeImmutable */
    public $updatedAt;

    /** @var string */
    public $visibility;

    /** @var int */
    public $minDisk;

    /** @var bool */
    public $protected;

    /** @var string */
    public $id;

    /** @var \GuzzleHttp\Psr7\Uri */
    public $fileUri;

    /** @var string */
    public $checksum;

    /** @var string */
    public $ownerId;

    /** @var int */
    public $size;

    /** @var int */
    public $minRam;

    /** @var \GuzzleHttp\Psr7\Uri */
    public $schemaUri;

    /** @var int */
    public $virtualSize;

    private $jsonSchema;

    protected $aliases = [
        'container_format' => 'containerFormat',
        'disk_format'      => 'diskFormat',
        'min_disk'         => 'minDisk',
        'owner'            => 'ownerId',
        'min_ram'          => 'minRam',
        'virtual_size'     => 'virtualSize',
    ];

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'created_at' => new Alias('createdAt', \DateTimeImmutable::class),
            'updated_at' => new Alias('updatedAt', \DateTimeImmutable::class),
            'fileUri'    => new Alias('fileUri', \GuzzleHttp\Psr7\Uri::class),
            'schemaUri'  => new Alias('schemaUri', \GuzzleHttp\Psr7\Uri::class),
        ];
    }

    public function populateFromArray(array $data): self
    {
        parent::populateFromArray($data);

        $baseUri = $this->getHttpBaseUrl();

        if (isset($data['file'])) {
            $this->fileUri = Utils::appendPath($baseUri, $data['file']);
        }

        if (isset($data['schema'])) {
            $this->schemaUri = Utils::appendPath($baseUri, $data['schema']);
        }

        return $this;
    }

    public function create(array $data): Creatable
    {
        $response = $this->execute($this->api->postImages(), $data);

        return $this->populateFromResponse($response);
    }

    public function retrieve()
    {
        $response = $this->executeWithState($this->api->getImage());
        $this->populateFromResponse($response);
    }

    private function getSchema(): Schema
    {
        if (null === $this->jsonSchema) {
            $response         = $this->execute($this->api->getImageSchema());
            $this->jsonSchema = new Schema(Utils::jsonDecode($response, false));
        }

        return $this->jsonSchema;
    }

    public function update(array $data)
    {
        // retrieve latest state so we can accurately produce a diff
        $this->retrieve();

        $schema     = $this->getSchema();
        $data       = (object) $data;
        $aliasNames = array_map(
            function (Alias $a) {
                return $a->propertyName;
            },
            $this->getAliases()
        );

        // formulate src and des structures
        $des = $schema->normalizeObject($data, $aliasNames);
        $src = $schema->normalizeObject($this, $aliasNames);

        // validate user input
        $schema->validate($des);
        if (!$schema->isValid()) {
            throw new \RuntimeException($schema->getErrorString());
        }

        // formulate diff
        $patch = new JsonPatch();
        $diff  = $patch->disableRestrictedPropRemovals($patch->diff($src, $des), $schema->getPropertyPaths());
        $json  = json_encode($diff, JSON_UNESCAPED_SLASHES);

        // execute patch operation
        $response = $this->execute($this->api->patchImage(), [
            'id'          => $this->id,
            'patchDoc'    => $json,
            'contentType' => 'application/openstack-images-v2.1-json-patch',
        ]);

        $this->populateFromResponse($response);
    }

    public function delete()
    {
        $this->executeWithState($this->api->deleteImage());
    }

    public function deactivate()
    {
        $this->executeWithState($this->api->deactivateImage());
    }

    public function reactivate()
    {
        $this->executeWithState($this->api->reactivateImage());
    }

    public function uploadData(StreamInterface $stream)
    {
        $this->execute($this->api->postImageData(), [
            'id'          => $this->id,
            'data'        => $stream,
            'contentType' => 'application/octet-stream',
        ]);
    }

    public function downloadData(): StreamInterface
    {
        $response = $this->executeWithState($this->api->getImageData());

        return $response->getBody();
    }

    public function addMember($memberId): Member
    {
        return $this->model(Member::class, ['imageId' => $this->id, 'id' => $memberId])->create([]);
    }

    public function listMembers(): \Generator
    {
        return $this->model(Member::class)->enumerate($this->api->getImageMembers(), ['imageId' => $this->id]);
    }

    public function getMember($memberId): Member
    {
        return $this->model(Member::class, ['imageId' => $this->id, 'id' => $memberId]);
    }
}
