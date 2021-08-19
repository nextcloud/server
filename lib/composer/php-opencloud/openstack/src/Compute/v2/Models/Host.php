<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2\Models;

use OpenStack\Common\Resource\Listable;
use OpenStack\Common\Resource\OperatorResource;
use OpenStack\Common\Resource\Retrievable;

/**
 * Represents a Compute v2 Host.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class Host extends OperatorResource implements Listable, Retrievable
{
    /** @var string * */
    public $name;

    /** @var string * */
    public $service;

    /** @var string * */
    public $zone;

    protected $resourceKey  = 'host';
    protected $resourcesKey = 'hosts';

    protected $aliases = [
      'host_name' => 'name',
    ];

    /**
     * {@inheritdoc}
     */
    public function retrieve()
    {
        $response = $this->execute($this->api->getHost(), $this->getAttrs(['name']));
        $this->populateFromResponse($response);
    }
}
