<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Alias;
use OpenStack\Common\Resource\Creatable;
use OpenStack\Common\Resource\Deletable;
use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;
use OpenStack\Common\Transport\Utils;

/**
 * Represents a Compute v2 Keypair.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class Keypair extends OperatorResource implements Listable, Retrievable, Deletable, Creatable
{
    /** @var string */
    public $fingerprint;

    /** @var string */
    public $name;

    /** @var string */
    public $publicKey;

    /** @var string */
    public $privateKey;

    /** @var bool */
    public $deleted;

    /** @var string */
    public $userId;

    /** @var string */
    public $type;

    /** @var string */
    public $id;

    /** @var \DateTimeImmutable */
    public $createdAt;

    protected $aliases = [
        'public_key'  => 'publicKey',
        'private_key' => 'privateKey',
        'user_id'     => 'userId',
        'type'        => 'type',
    ];

    protected $resourceKey  = 'keypair';
    protected $resourcesKey = 'keypairs';

    /**
     * {@inheritdoc}
     */
    protected function getAliases(): array
    {
        return parent::getAliases() + [
            'created_at' => new Alias('createdAt', \DateTimeImmutable::class),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getKeypair(), $this->getAttrs(['name', 'userId']));
        $this->populateFromResponse($response);
    }

    public function create(array $userOptions): Creatable
    {
        $response = $this->execute($this->api->postKeypair(), $userOptions);

        return $this->populateFromResponse($response);
    }

    /**
     * {@inheritdoc}
     */
    public function populateFromArray(array $array): self
    {
        return parent::populateFromArray(Utils::flattenJson($array, $this->resourceKey));
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        $this->execute($this->api->deleteKeypair(), ['name' => (string) $this->name]);
    }
}
