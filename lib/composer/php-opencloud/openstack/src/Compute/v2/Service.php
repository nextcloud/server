<?php

declare(strict_types=1);

namespace OpenStack\Compute\v2;

use OpenStack\Common\Service\AbstractService;
use OpenStack\Compute\v2\Models\AvailabilityZone;
use OpenStack\Compute\v2\Models\Flavor;
use OpenStack\Compute\v2\Models\Host;
use OpenStack\Compute\v2\Models\Hypervisor;
use OpenStack\Compute\v2\Models\HypervisorStatistic;
use OpenStack\Compute\v2\Models\Image;
use OpenStack\Compute\v2\Models\Keypair;
use OpenStack\Compute\v2\Models\Limit;
use OpenStack\Compute\v2\Models\QuotaSet;
use OpenStack\Compute\v2\Models\Server;

/**
 * Compute v2 service for OpenStack.
 *
 * @property \OpenStack\Compute\v2\Api $api
 */
class Service extends AbstractService
{
    /**
     * Create a new server resource. This operation will provision a new virtual machine on a host chosen by your
     * service API.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::postServer}
     */
    public function createServer(array $options): Server
    {
        return $this->model(Server::class)->create($options);
    }

    /**
     * List servers.
     *
     * @param bool     $detailed Determines whether detailed information will be returned. If FALSE is specified, only
     *                           the ID, name and links attributes are returned, saving bandwidth.
     * @param array    $options  {@see \OpenStack\Compute\v2\Api::getServers}
     * @param callable $mapFn    a callable function that will be invoked on every iteration of the list
     */
    public function listServers(bool $detailed = false, array $options = [], callable $mapFn = null): \Generator
    {
        $def = (true === $detailed) ? $this->api->getServersDetail() : $this->api->getServers();

        return $this->model(Server::class)->enumerate($def, $options, $mapFn);
    }

    /**
     * Retrieve a server object without calling the remote API. Any values provided in the array will populate the
     * empty object, allowing you greater control without the expense of network transactions. To call the remote API
     * and have the response populate the object, call {@see Server::retrieve}. For example:.
     *
     * <code>$server = $service->getServer(['id' => '{serverId}']);</code>
     *
     * @param array $options An array of attributes that will be set on the {@see Server} object. The array keys need to
     *                       correspond to the class public properties.
     */
    public function getServer(array $options = []): Server
    {
        $server = $this->model(Server::class);
        $server->populateFromArray($options);

        return $server;
    }

    /**
     * List flavors.
     *
     * @param array    $options  {@see \OpenStack\Compute\v2\Api::getFlavors}
     * @param callable $mapFn    a callable function that will be invoked on every iteration of the list
     * @param bool     $detailed set to true to fetch flavors' details
     */
    public function listFlavors(array $options = [], callable $mapFn = null, bool $detailed = false): \Generator
    {
        $def = true === $detailed ? $this->api->getFlavorsDetail() : $this->api->getFlavors();

        return $this->model(Flavor::class)->enumerate($def, $options, $mapFn);
    }

    /**
     * Retrieve a flavor object without calling the remote API. Any values provided in the array will populate the
     * empty object, allowing you greater control without the expense of network transactions. To call the remote API
     * and have the response populate the object, call {@see Flavor::retrieve}.
     *
     * @param array $options An array of attributes that will be set on the {@see Flavor} object. The array keys need to
     *                       correspond to the class public properties.
     */
    public function getFlavor(array $options = []): Flavor
    {
        $flavor = $this->model(Flavor::class);
        $flavor->populateFromArray($options);

        return $flavor;
    }

    /**
     * Create a new flavor resource.
     *
     * @param array $options {@see \OpenStack\Compute\v2\Api::postFlavors}
     */
    public function createFlavor(array $options = []): Flavor
    {
        return $this->model(Flavor::class)->create($options);
    }

    /**
     * List images.
     *
     * @param array    $options {@see \OpenStack\Compute\v2\Api::getImages}
     * @param callable $mapFn   a callable function that will be invoked on every iteration of the list
     */
    public function listImages(array $options = [], callable $mapFn = null): \Generator
    {
        return $this->model(Image::class)->enumerate($this->api->getImages(), $options, $mapFn);
    }

    /**
     * Retrieve an image object without calling the remote API. Any values provided in the array will populate the
     * empty object, allowing you greater control without the expense of network transactions. To call the remote API
     * and have the response populate the object, call {@see Image::retrieve}.
     *
     * @param array $options An array of attributes that will be set on the {@see Image} object. The array keys need to
     *                       correspond to the class public properties.
     */
    public function getImage(array $options = []): Image
    {
        $image = $this->model(Image::class);
        $image->populateFromArray($options);

        return $image;
    }

    /**
     * List key pairs.
     *
     * @param array    $options {@see \OpenStack\Compute\v2\Api::getKeyPairs}
     * @param callable $mapFn   a callable function that will be invoked on every iteration of the list
     */
    public function listKeypairs(array $options = [], callable $mapFn = null): \Generator
    {
        return $this->model(Keypair::class)->enumerate($this->api->getKeypairs(), $options, $mapFn);
    }

    /**
     * Create or import keypair.
     */
    public function createKeypair(array $options): Keypair
    {
        return $this->model(Keypair::class)->create($options);
    }

    /**
     * Get keypair.
     */
    public function getKeypair(array $options = []): Keypair
    {
        $keypair = $this->model(Keypair::class);
        $keypair->populateFromArray($options);

        return $keypair;
    }

    /**
     * Shows rate and absolute limits for the tenant.
     */
    public function getLimits(): Limit
    {
        $limits = $this->model(Limit::class);
        $limits->populateFromResponse($this->execute($this->api->getLimits(), []));

        return $limits;
    }

    /**
     * Shows summary statistics for all hypervisors over all compute nodes.
     */
    public function getHypervisorStatistics(): HypervisorStatistic
    {
        $statistics = $this->model(HypervisorStatistic::class);
        $statistics->populateFromResponse($this->execute($this->api->getHypervisorStatistics(), []));

        return $statistics;
    }

    /**
     * List hypervisors.
     *
     * @param bool     $detailed Determines whether detailed information will be returned. If FALSE is specified, only
     *                           the ID, name and links attributes are returned, saving bandwidth.
     * @param array    $options  {@see \OpenStack\Compute\v2\Api::getHypervisors}
     * @param callable $mapFn    a callable function that will be invoked on every iteration of the list
     */
    public function listHypervisors(bool $detailed = false, array $options = [], callable $mapFn = null): \Generator
    {
        $def = (true === $detailed) ? $this->api->getHypervisorsDetail() : $this->api->getHypervisors();

        return $this->model(Hypervisor::class)->enumerate($def, $options, $mapFn);
    }

    /**
     * Shows details for a given hypervisor.
     */
    public function getHypervisor(array $options = []): Hypervisor
    {
        $hypervisor = $this->model(Hypervisor::class);

        return $hypervisor->populateFromArray($options);
    }

    /**
     * List hosts.
     *
     * @param array    $options {@see \OpenStack\Compute\v2\Api::getHosts}
     * @param callable $mapFn   a callable function that will be invoked on every iteration of the list
     */
    public function listHosts(array $options = [], callable $mapFn = null): \Generator
    {
        return $this->model(Host::class)->enumerate($this->api->getHosts(), $options, $mapFn);
    }

    /**
     * Retrieve a host object without calling the remote API. Any values provided in the array will populate the
     * empty object, allowing you greater control without the expense of network transactions. To call the remote API
     * and have the response populate the object, call {@see Host::retrieve}. For example:.
     *
     * <code>$server = $service->getHost(['name' => '{name}']);</code>
     *
     * @param array $options An array of attributes that will be set on the {@see Host} object. The array keys need to
     *                       correspond to the class public properties.
     */
    public function getHost(array $options = []): Host
    {
        $host = $this->model(Host::class);
        $host->populateFromArray($options);

        return $host;
    }

    /**
     * List AZs.
     *
     * @param array    $options {@see \OpenStack\Compute\v2\Api::getAvailabilityZones}
     * @param callable $mapFn   a callable function that will be invoked on every iteration of the list
     */
    public function listAvailabilityZones(array $options = [], callable $mapFn = null): \Generator
    {
        return $this->model(AvailabilityZone::class)->enumerate($this->api->getAvailabilityZones(), $options, $mapFn);
    }

    /**
     * Shows A Quota for a tenant.
     */
    public function getQuotaSet(string $tenantId, bool $detailed = false): QuotaSet
    {
        $quotaSet = $this->model(QuotaSet::class);
        $quotaSet->populateFromResponse($this->execute($detailed ? $this->api->getQuotaSetDetail() : $this->api->getQuotaSet(), ['tenantId' => $tenantId]));

        return $quotaSet;
    }
}
