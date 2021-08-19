<?php

namespace OpenStack\Networking\v2\Extensions\Layer3;

use OpenStack\Common\Service\AbstractService;
use OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp;
use OpenStack\Networking\v2\Extensions\Layer3\Models\Router;

/**
 * @property Api $api
 */
class Service extends AbstractService
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

    public function listRouters(array $options = []): \Generator
    {
        return $this->router()->enumerate($this->api->getRouters(), $options);
    }
}
