<?php

namespace OpenStack\Integration\Networking\v2;

use OpenStack\Networking\v2\Models\Network;
use OpenStack\Networking\v2\Models\Port;
use OpenStack\Networking\v2\Models\Subnet;
use OpenStack\Integration\TestCase;

class CoreTest extends TestCase
{
    public function runTests()
    {
        $this->startTimer();

        $this->networks();
        $this->subnets();
        $this->ports();

        $this->outputTimeTaken();
    }

    public function subnets()
    {
        $this->createSubnetsAndDelete();
        $this->createSubnetWithHostRoutes();

        list($subnetId, $networkId) = $this->createSubnet();

        $this->updateSubnet($subnetId);
        $this->retrieveSubnet($subnetId);
        $this->deleteSubnet($subnetId);
        $this->deleteNetwork($networkId);
    }

    public function networks()
    {
        $this->createNetworksAndDelete();

        $networkId = $this->createNetwork();
        $this->updateNetwork($networkId);
        $this->retrieveNetwork($networkId);
        $this->deleteNetwork($networkId);
    }

    private function createNetworksAndDelete()
    {
        $replacements = [
            '{networkName1}' => $this->randomStr(),
            '{networkName2}' => $this->randomStr()
        ];

        /** @var $networks array */
        $path = $this->sampleFile($replacements, 'networks/create_batch.php');
        require_once $path;

        foreach ($networks as $network) {
            self::assertInstanceOf(Network::class, $network);
            self::assertNotEmpty($network->id);

            $this->networkId = $network->id;
            $this->logStep('Created network {id}', ['{id}' => $this->networkId]);

            $this->deleteNetwork($network->id);
        }
    }

    private function createNetwork()
    {
        $replacements = [
            '{networkName}' => $this->randomStr(),
        ];

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile($replacements, 'networks/create.php');
        require_once $path;

        self::assertInstanceOf(Network::class, $network);
        self::assertNotEmpty($network->id);

        $this->logStep('Created network {id}', ['{id}' => $this->networkId]);

        return $network->id;
    }

    private function updateNetwork($networkId)
    {
        $name = $this->randomStr();

        $replacements = [
            '{networkId}' => $networkId,
            '{newName}'   => $name,
        ];

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile($replacements, 'networks/update.php');
        require_once $path;

        self::assertInstanceOf(Network::class, $network);
        self::assertEquals($name, $network->name);

        $this->logStep('Updated network ID to use this name: NAME', ['ID' => $networkId, 'NAME' => $name]);
    }

    private function retrieveNetwork($networkId)
    {
        $replacements = ['{networkId}' => $networkId];

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile($replacements, 'networks/get.php');
        require_once $path;

        self::assertInstanceOf(Network::class, $network);

        $this->logStep('Retrieved the details of network ID', ['ID' => $networkId]);
    }

    private function deleteNetwork($networkId)
    {
        $replacements = ['{networkId}' => $networkId];

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile($replacements, 'networks/delete.php');
        require_once $path;

        $this->logStep('Deleted network ID', ['ID' => $networkId]);
    }

    private function createSubnetsAndDelete()
    {
        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{newName}' => $this->randomStr()], 'networks/create.php');
        require_once $path;

        $replacements = [
            '{subnetName1}' => $this->randomStr(),
            '{subnetName2}' => $this->randomStr(),
            '{networkId1}'  => $network->id,
            '{networkId2}'  => $network->id,
        ];

        /** @var $subnets array */
        $path = $this->sampleFile($replacements, 'subnets/create_batch.php');
        require_once $path;

        foreach ($subnets as $subnet) {
            self::assertInstanceOf(Subnet::class, $subnet);
            self::assertNotEmpty($subnet->id);

            $this->logStep('Created subnet {id}', ['{id}' => $subnet->id]);

            $this->deleteSubnet($subnet->id);
        }

        $path = $this->sampleFile(['{networkId}' => $network->id], 'networks/delete.php');
        require_once $path;
    }

    private function createSubnet()
    {
        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{newName}' => $this->randomStr()], 'networks/create.php');
        require_once $path;

        $replacements = [
            '{subnetName}' => $this->randomStr(),
            '{networkId}'  => $network->id,
        ];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/create.php');
        require_once $path;

        self::assertInstanceOf(Subnet::class, $subnet);
        self::assertNotEmpty($subnet->id);

        $this->logStep('Created subnet {id}', ['{id}' => $subnet->id]);

        return [$subnet->id, $network->id];
    }

    private function createSubnetWithGatewayIp()
    {
        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{newName}' => $this->randomStr()], 'networks/create.php');
        require_once $path;

        $replacements = [
            '{networkId}' => $network->id,
        ];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/create_with_gateway_ip.php');
        require_once $path;

        self::assertInstanceOf(Subnet::class, $subnet);
        self::assertNotEmpty($subnet->id);

        $this->subnetId = $subnet->id;

        $this->logStep('Created subnet {id} with gateway ip', ['{id}' => $this->subnetId]);

        $path = $this->sampleFile($replacements, 'networks/delete.php');
        require_once $path;
    }

    private function createSubnetWithHostRoutes()
    {
        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{newName}' => $this->randomStr()], 'networks/create.php');
        require_once $path;

        $replacements = [
            '{networkId}' => $network->id,
        ];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/create_with_host_routes.php');
        require_once $path;

        self::assertInstanceOf(Subnet::class, $subnet);
        self::assertNotEmpty($subnet->id);

        $this->logStep('Created subnet {id} with host routes', ['{id}' => $subnet->id]);

        $path = $this->sampleFile($replacements, 'networks/delete.php');
        require_once $path;
    }

    private function updateSubnet($subnetId)
    {
        $name = $this->randomStr();

        $replacements = [
            '{subnetId}' => $subnetId,
            '{newName}'  => $name,
        ];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/update.php');
        require_once $path;

        self::assertInstanceOf(Subnet::class, $subnet);
        self::assertEquals($name, $subnet->name);

        $this->logStep('Updated subnet ID to use this name: NAME', ['ID' => $subnetId, 'NAME' => $name]);
    }


    private function retrieveSubnet($subnetId)
    {
        $replacements = ['{subnetId}' => $subnetId];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/get.php');
        require_once $path;

        self::assertInstanceOf(Subnet::class, $subnet);

        $this->logStep('Retrieved the details of subnet ID', ['ID' => $subnetId]);
    }

    private function deleteSubnet($subnetId)
    {
        $replacements = ['{subnetId}' => $subnetId];

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile($replacements, 'subnets/delete.php');
        require_once $path;

        $this->logStep('Deleted subnet ID', ['ID' => $subnetId]);
    }

    public function ports()
    {
        $this->logStep('Test port');

        $replacements = ['{newName}' => $this->randomStr()];

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile($replacements, 'networks/create.php');
        require_once $path;

        $replacements = ['{networkId}' => $network->id];

        /** @var $port \OpenStack\Networking\v2\Models\Port */
        $path = $this->sampleFile($replacements, 'ports/create.php');
        require_once $path;

        $replacements['{portId}'] = $port->id;
        $port->networkId = $network->id;

        /** @var $ports array */
        $path = $this->sampleFile($replacements, 'ports/create_batch.php');
        require_once $path;
        foreach ($ports as $port) {
            self::assertInstanceOf(Port::class, $port);
            $port->delete();
        }

        /** @var $port \OpenStack\Networking\v2\Models\Port */
        $path = $this->sampleFile($replacements, 'ports/list.php');
        require_once $path;

        /** @var $port \OpenStack\Networking\v2\Models\Port */
        $path = $this->sampleFile($replacements, 'ports/get.php');
        require_once $path;
        self::assertInstanceOf(Port::class, $port);

        /** @var $port \OpenStack\Networking\v2\Models\Port */
        $path = $this->sampleFile($replacements, 'ports/update.php');
        require_once $path;
        self::assertInstanceOf(Port::class, $port);

        $path = $this->sampleFile($replacements, 'ports/delete.php');
        require_once $path;

        $path = $this->sampleFile($replacements, 'networks/delete.php');
        require_once $path;

        $this->createPortWithFixedIps();
    }

    private function createPortWithFixedIps()
    {
        $this->logStep('Test port with fixed IP');

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{networkName}' => $this->randomStr()], 'networks/create.php');
        require_once $path;
        $this->logStep('Created network {id}', ['{id}' => $network->id]);


        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile(['{subnetName}' => $this->randomStr(), '{networkId}' => $network->id], 'subnets/create.php');
        require_once $path;
        $this->logStep('Created subnet {id}', ['{id}' => $subnet->id]);

        /** @var $port \OpenStack\Networking\v2\Models\Port */
        $path = $this->sampleFile(['{networkId}' => $network->id], 'ports/create_with_fixed_ips.php');
        require_once $path;
        $this->logStep('Created port {id}', ['{id}' => $port->id]);

        $path = $this->sampleFile(['{portId}' => $port->id], 'ports/delete.php');
        require_once $path;

        $this->logStep('Deleted port {id}', ['{id}' => $port->id]);

        /** @var $subnet \OpenStack\Networking\v2\Models\Subnet */
        $path = $this->sampleFile(['{subnetId}' => $subnet->id], 'subnets/delete.php');
        require_once $path;
        $this->logStep('Deleted subnet {id}', ['{id}' => $subnet->id]);

        /** @var $network \OpenStack\Networking\v2\Models\Network */
        $path = $this->sampleFile(['{networkId}' => $network->id], 'networks/delete.php');
        require_once $path;
        $this->logStep('Deleted network {id}', ['{id}' => $network->id]);
    }
}
