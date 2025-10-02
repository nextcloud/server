<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2\Extensions\SecurityGroups;

use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroup;
use OpenStack\Networking\v2\Extensions\SecurityGroups\Models\SecurityGroupRule;

/**
 * @property \OpenStack\Networking\v2\Api $api
 *
 * @internal please use the Networking\v2\Service instead of this one
 */
trait ServiceTrait
{
    private function securityGroup(array $info = []): SecurityGroup
    {
        return $this->model(SecurityGroup::class, $info);
    }

    private function securityGroupRule(array $info = []): SecurityGroupRule
    {
        return $this->model(SecurityGroupRule::class, $info);
    }

    /**
     * @return \Generator<mixed, SecurityGroup>
     */
    public function listSecurityGroups(array $options = []): \Generator
    {
        return $this->securityGroup()->enumerate($this->api->getSecurityGroups(), $options);
    }

    public function createSecurityGroup(array $options): SecurityGroup
    {
        return $this->securityGroup()->create($options);
    }

    public function getSecurityGroup(string $id): SecurityGroup
    {
        return $this->securityGroup(['id' => $id]);
    }

    /**
     * @return \Generator<mixed, SecurityGroupRule>
     */
    public function listSecurityGroupRules(): \Generator
    {
        return $this->securityGroupRule()->enumerate($this->api->getSecurityRules());
    }

    public function createSecurityGroupRule(array $options): SecurityGroupRule
    {
        return $this->securityGroupRule()->create($options);
    }

    public function getSecurityGroupRule(string $id): SecurityGroupRule
    {
        return $this->securityGroupRule(['id' => $id]);
    }
}
