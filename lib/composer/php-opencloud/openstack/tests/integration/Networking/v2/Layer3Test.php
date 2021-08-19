<?php

namespace OpenStack\Integration\Networking\v2;

use Guzzle\Tests\Service\Mock\Command\Sub\Sub;
use OpenStack\Integration\TestCase;
use OpenStack\Networking\v2\Extensions\Layer3\Models\FloatingIp;
use OpenStack\Networking\v2\Models\Network;
use OpenStack\Networking\v2\Models\Port;
use OpenStack\Networking\v2\Models\Subnet;

class Layer3Test extends TestCase
{
    public function getService(): \OpenStack\Networking\v2\Extensions\Layer3\Service
    {
        return $this->getBaseClient()->networkingV2ExtLayer3();
    }

    private function getV2Service(): \OpenStack\Networking\v2\Service
    {
        return $this->getBaseClient()->networkingV2();
    }

    public function runTests()
    {
        $this->startTimer();
        $this->floatingIps();
        $this->outputTimeTaken();
    }

    public function teardown()
    {
        parent::teardown();

        $this->deleteItems($this->getV2Service()->listPorts());
        $this->deleteItems($this->getV2Service()->listNetworks());
        $this->deleteItems($this->getService()->listFloatingIps());
    }

    private function createNetwork(bool $routerAccessible = true): Network
    {
        $network = $this->getV2Service()->createNetwork([
            'name'             => $this->randomStr(),
            'routerAccessible' => $routerAccessible,
        ]);
        $network->waitUntilActive();
        return $network;
    }

    private function createSubnet(Network $network, string $cidr = '192.168.199.0/24'): Subnet
    {
        return $this->getV2Service()->createSubnet([
            'networkId' => $network->id,
            'name'      => $this->randomStr(),
            'ipVersion' => 4,
            'cidr'      => $cidr,
        ]);
    }

    private function createPort(Network $network): Port
    {
        return $this->getV2Service()->createPort([
            'networkId' => $network->id,
            'name'      => $this->randomStr(),
        ]);
    }

    private function findSubnetIp(Port $port, Subnet $subnet): string
    {
        foreach ($port->fixedIps as $fixedIp) {
            if ($fixedIp['subnet_id'] == $subnet->id) {
                return $fixedIp['ip_address'];
            }
        }

        return '';
    }

    public function floatingIps()
    {
        $this->logStep('Creating external network');
        $externalNetwork = $this->createNetwork();

        $this->logStep('Creating subnet for external network %id%', ['%id%' => $externalNetwork->id]);
        $this->createSubnet($externalNetwork, '10.0.0.0/24');

        $this->logStep('Creating internal network');
        $internalNetwork = $this->createNetwork(false);

        $this->logStep('Creating subnet for internal network %id%', ['%id%' => $internalNetwork->id]);
        $subnet = $this->createSubnet($internalNetwork);

        $this->logStep('Creating router for external network %id%', ['%id%' => $externalNetwork->id]);
        $router = $this->getService()->createRouter([
            'name'                => $this->randomStr(),
            'externalGatewayInfo' => [
                'networkId'  => $externalNetwork->id,
                'enableSnat' => true,
            ],
        ]);

        $this->logStep('Create interface for subnet %subnet% and router %router%', [
            '%subnet%' => $subnet->id, '%router%' => $router->id,
        ]);
        $router->addInterface(['subnetId' => $subnet->id]);

        $this->logStep('Creating port for internal network %id%', ['%id%' => $internalNetwork->id]);
        $port1 = $this->createPort($internalNetwork);
        $fixedIp = $this->findSubnetIp($port1, $subnet);

        $replacements = [
            '{networkId}'      => $externalNetwork->id,
            '{portId}'         => $port1->id,
            '{fixedIpAddress}' => $fixedIp,
        ];

        $this->logStep('Create floating IP');
        /** @var FloatingIp $ip */
        $path = $this->sampleFile($replacements, 'floatingIPs/create.php');
        require_once $path;
        self::assertInstanceOf(FloatingIp::class, $ip);
        self::assertEquals($externalNetwork->id, $ip->floatingNetworkId);
        self::assertEquals($port1->id, $ip->portId);

        $this->logStep('List floating IPs');
        $path = $this->sampleFile($replacements, 'floatingIPs/list.php');
        require_once $path;

        $this->logStep('Get floating IP');
        $replacements['{id}'] = $ip->id;
        $path = $this->sampleFile($replacements, 'floatingIPs/get.php');
        require_once $path;
        self::assertInstanceOf(FloatingIp::class, $ip);

        $this->logStep('Update floating IP');
        $port2 = $this->createPort($internalNetwork);
        $replacements['{newPortId}'] = $port2->id;
        $path = $this->sampleFile($replacements, 'floatingIPs/update.php');
        require_once $path;

        $this->logStep('Delete floating IP');
        $path = $this->sampleFile($replacements, 'floatingIPs/delete.php');
        require_once $path;

        $router->removeInterface(['subnetId' => $subnet->id]);
        $router->delete();
        $router->waitUntilDeleted();

        $port1->delete();
        $port2->delete();

        $internalNetwork->delete();
        $internalNetwork->waitUntilDeleted();

        $externalNetwork->delete();
        $externalNetwork->waitUntilDeleted();
    }
}
