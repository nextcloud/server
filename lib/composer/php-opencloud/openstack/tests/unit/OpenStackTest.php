<?php

namespace OpenStack\Test;

use GuzzleHttp\ClientInterface;
use OpenStack\Common\Service\Builder;
use OpenStack\Identity\v3\Api;
use OpenStack\OpenStack;
use OpenStack\Compute\v2\Service as ComputeServiceV2;
use OpenStack\Identity\v2\Service as IdentityServiceV2;
use OpenStack\Identity\v3\Service as IdentityServiceV3;
use OpenStack\Networking\v2\Service as NetworkingServiceV2;
use OpenStack\Networking\v2\Extensions\Layer3\Service as NetworkingServiceV2ExtLayer3;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Service as NetworkingServiceV2ExtSecGroup;
use OpenStack\ObjectStore\v1\Service as ObjectStoreServiceV1;
use OpenStack\BlockStorage\v2\Service as BlockStorageServiceV2;
use OpenStack\Images\v2\Service as ImageServiceV2;
use OpenStack\Metric\v1\Gnocchi\Service as MetricGnocchiV1;

class OpenStackTest extends TestCase
{
    private $builder;
    /** @var OpenStack */
    private $openstack;

    public function setUp(): void
    {
        $this->builder = $this->prophesize(Builder::class);
        $this->openstack = new OpenStack(['authUrl' => ''], $this->builder->reveal());
    }

    public function test_it_supports_compute_v2()
    {
        $this->builder
            ->createService('Compute\\v2', ['catalogName' => 'nova', 'catalogType' => 'compute'])
            ->shouldBeCalled()
            ->willReturn($this->service(ComputeServiceV2::class));

        $this->openstack->computeV2();
    }

    public function test_it_supports_identity_v2()
    {
        $this->builder
            ->createService('Identity\\v2', ['catalogName' => 'keystone', 'catalogType' => 'identity'])
            ->shouldBeCalled()
            ->willReturn($this->service(IdentityServiceV2::class));

        $this->openstack->identityV2();
    }

    public function test_it_supports_identity_v3()
    {
        $this->builder
            ->createService('Identity\\v3', ['catalogName' => 'keystone', 'catalogType' => 'identity'])
            ->shouldBeCalled()
            ->willReturn($this->service(IdentityServiceV3::class));

        $this->openstack->identityV3();
    }

    public function test_it_supports_networking_v2()
    {
        $this->builder
            ->createService('Networking\\v2', ['catalogName' => 'neutron', 'catalogType' => 'network'])
            ->shouldBeCalled()
            ->willReturn($this->service(NetworkingServiceV2::class));

        $this->openstack->networkingV2();
    }

    public function test_it_supports_networking_v2_ext_layer3()
    {
        $this->builder
            ->createService('Networking\\v2\\Extensions\\Layer3', ['catalogName' => 'neutron', 'catalogType' => 'network'])
            ->shouldBeCalled()
            ->willReturn($this->service(NetworkingServiceV2ExtLayer3::class));

        $this->openstack->networkingV2ExtLayer3();
    }

    public function test_it_supports_networking_v2_ext_security_group()
    {
        $this->builder
            ->createService('Networking\\v2\\Extensions\\SecurityGroups', ['catalogName' => 'neutron', 'catalogType' => 'network'])
            ->shouldBeCalled()
            ->willReturn($this->service(NetworkingServiceV2ExtSecGroup::class));

        $this->openstack->networkingV2ExtSecGroups();
    }

    public function test_it_supports_object_store_v1()
    {
        $this->builder
            ->createService('ObjectStore\\v1', ['catalogName' => 'swift', 'catalogType' => 'object-store'])
            ->shouldBeCalled()
            ->willReturn($this->service(ObjectStoreServiceV1::class));

        $this->openstack->objectStoreV1();
    }

    public function test_it_supports_block_storage_v2()
    {
        $this->builder
            ->createService('BlockStorage\\v2', ['catalogName' => 'cinderv2', 'catalogType' => 'volumev2'])
            ->shouldBeCalled()
            ->willReturn($this->service(BlockStorageServiceV2::class));

        $this->openstack->blockStorageV2();
    }

    public function test_it_supports_images_v2()
    {
        $this->builder
            ->createService('Images\\v2', ['catalogName' => 'glance', 'catalogType' => 'image'])
            ->shouldBeCalled()
            ->willReturn($this->service(ImageServiceV2::class));

        $this->openstack->imagesV2();
    }

    public function test_it_support_metrics_gnocchi_v1()
    {
        $this->builder
            ->createService('Metric\\v1\\Gnocchi', ['catalogName' => 'gnocchi', 'catalogType' => 'metric'])
            ->shouldBeCalled()
            ->willReturn($this->service(MetricGnocchiV1::class));

        $this->openstack->metricGnocchiV1();
    }

    private function service($class)
    {
        return new $class($this->prophesize(ClientInterface::class)->reveal(), new Api());
    }
}
