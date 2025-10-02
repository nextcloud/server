<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Extensions\Layer3;

use OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp;
use OpenStack\Networking\v2\Extensions\Layer3\Models\Router;

/**
 * @property \OpenStack\Networking\v2\Api $api
 *
 * @internal please use the Networking\v2\Service instead of this one
 */
trait ServiceTrait
{
    private function floatingIp(array $info = []): FloatingIp
    {
        return $this->model(FloatingIp::class, $info);
    }

    private function router(array $info = []): Router
    {
        return $this->model(Router::class, $info);
    }

    public function createFloatingIp(array $options): FloatingIp
    {
        return $this->floatingIp()->create($options);
    }

    public function getFloatingIp($id): FloatingIp
    {
        return $this->floatingIp(['id' => $id]);
    }

    /**
     * @return \Generator<mixed, FloatingIp>
     */
    public function listFloatingIps(array $options = []): \Generator
    {
        return $this->floatingIp()->enumerate($this->api->getFloatingIps(), $options);
    }

    public function createRouter(array $options): Router
    {
        return $this->router()->create($options);
    }

    public function getRouter($id): Router
    {
        return $this->router(['id' => $id]);
    }

    /**
     * @return \Generator<mixed, Router>
     */
    public function listRouters(array $options = []): \Generator
    {
        return $this->router()->enumerate($this->api->getRouters(), $options);
    }
}
