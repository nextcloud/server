<?php

namespace OpenStack\Networking\v2\Extensions\SecurityGroups;

class Params extends \OpenStack\Networking\v2\Params
{
    public function directionJson()
    {
        return [
            'type'        => self::STRING_TYPE,
            'description' => 'Ingress or egress: the direction in which the security group rule is applied. For a compute instance, an ingress security group rule is applied to incoming (ingress) traffic for that instance. An egress rule is applied to traffic leaving the instance.',
        ];
    }

    public function ethertypeJson()
    {
        return [
            'type'        => self::STRING_TYPE,
            'description' => 'Must be IPv4 or IPv6, and addresses represented in CIDR must match the ingress or egress rules.',
        ];
    }

    public function idJson()
    {
        return [
            'type'        => self::STRING_TYPE,
            'description' => 'The UUID of the security group rule.',
        ];
    }

    public function portRangeMaxJson()
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'port_range_max',
            'description' => 'The maximum port number in the range that is matched by the security group rule. The port_range_min attribute constrains the port_range_max attribute. If the protocol is ICMP, this value must be an ICMP type.',
        ];
    }

    public function portRangeMinJson()
    {
        return [
            'sentAs'      => 'port_range_min',
            'type'        => self::STRING_TYPE,
            'description' => 'The minimum port number in the range that is matched by the security group rule. If the protocol is TCP or UDP, this value must be less than or equal to the port_range_max attribute value. If the protocol is ICMP, this value must be an ICMP type.',
        ];
    }

    public function protocolJson()
    {
        return [
            'type'        => self::STRING_TYPE,
            'description' => 'The protocol that is matched by the security group rule. Value is null, icmp, icmpv6, tcp, or udp.',
        ];
    }

    public function remoteGroupIdJson()
    {
        return [
            'sentAs'      => 'remote_group_id',
            'type'        => self::STRING_TYPE,
            'description' => 'The remote group UUID to associate with this security group rule. You can specify either the remote_group_id or remote_ip_prefix attribute in the request body.',
        ];
    }

    public function remoteIpPrefixJson()
    {
        return [
            'sentAs'      => 'remote_ip_prefix',
            'type'        => self::STRING_TYPE,
            'description' => 'The remote IP prefix to associate with this security group rule. You can specify either the remote_group_id or remote_ip_prefix attribute in the request body. This attribute value matches the IP prefix as the source IP address of the IP packet.',
        ];
    }

    public function securityGroupIdJson()
    {
        return [
            'sentAs'      => 'security_group_id',
            'type'        => self::STRING_TYPE,
            'description' => 'The UUID of the security group.',
        ];
    }

    public function tenantIdJson()
    {
        return [
            'sentAs'      => 'tenant_id',
            'type'        => self::STRING_TYPE,
            'description' => 'The UUID of the tenant who owns the security group rule. Only administrative users can specify a tenant UUID other than their own.',
        ];
    }

    public function filterName(): array
    {
        return [
            'description' => sprintf('Filter the list result by the human-readable name of the resource'),
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
        ];
    }
}
