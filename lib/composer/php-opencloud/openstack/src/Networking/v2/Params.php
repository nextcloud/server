<?php

declare(strict_types=1);

namespace OpenStack\Networking\v2;

use OpenStack\Common\Api\AbstractParams;

class Params extends AbstractParams
{
    /**
     * Returns information about description parameter.
     */
    public function descriptionJson(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
        ];
    }

    /**
     * Returns information about name parameter.
     */
    public function nameJson(): array
    {
        return [
            'type'     => self::STRING_TYPE,
            'location' => self::JSON,
        ];
    }

    public function urlId($type): array
    {
        return array_merge(parent::id($type), [
            'required' => true,
            'location' => self::URL,
        ]);
    }

    public function shared(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'description' => 'Indicates whether this network is shared across all tenants',
        ];
    }

    public function adminStateUp(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'admin_state_up',
            'description' => 'The administrative state of the network',
        ];
    }

    public function portSecurityEnabled(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'port_security_enabled',
            'description' => 'The port security status. A valid value is enabled (true) or disabled (false). If port security is enabled for the port, security 
                              group rules and anti-spoofing rules are applied to the traffic on the port. If disabled, no such rules are applied.',
        ];
    }

    public function networkId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'network_id',
            'description' => 'The ID of the attached network',
        ];
    }

    public function ipVersion(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'required'    => true,
            'sentAs'      => 'ip_version',
            'description' => 'The IP version, which is 4 or 6',
        ];
    }

    public function cidr(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'required'    => true,
            'sentAs'      => 'cidr',
            'description' => 'The CIDR',
        ];
    }

    public function tenantId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'tenant_id',
            'description' => 'The ID of the tenant who owns the network. Only administrative users can specify a tenant ID other than their own. You cannot change this value through authorization policies',
        ];
    }

    public function projectId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'project_id',
            'location'    => self::QUERY,
            'description' => 'The ID of the tenant who owns the network. Only administrative users can specify a tenant ID other than their own. You cannot change this value through authorization policies',
        ];
    }

    public function gatewayIp(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'sentAs'      => 'gateway_ip',
            'description' => 'The gateway IP address',
        ];
    }

    public function enableDhcp(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'sentAs'      => 'enable_dhcp',
            'description' => 'Set to true if DHCP is enabled and false if DHCP is disabled.',
        ];
    }

    public function dnsNameservers(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'sentAs'      => 'dns_nameservers',
            'description' => 'A list of DNS name servers for the subnet.',
            'items'       => [
                'type'        => self::STRING_TYPE,
                'description' => 'The nameserver',
            ],
        ];
    }

    public function allocationPools(): array
    {
        return [
            'type'   => self::ARRAY_TYPE,
            'sentAs' => 'allocation_pools',
            'items'  => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'start' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'The start address for the allocation pools',
                    ],
                    'end' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'The end address for the allocation pools',
                    ],
                ],
            ],
            'description' => 'The start and end addresses for the allocation pools',
        ];
    }

    public function hostRoutes(): array
    {
        return [
            'type'   => self::ARRAY_TYPE,
            'sentAs' => 'host_routes',
            'items'  => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'destination' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'Destination for static route',
                    ],
                    'nexthop' => [
                        'type'        => self::STRING_TYPE,
                        'description' => 'Nexthop for the destination',
                    ],
                ],
            ],
            'description' => 'A list of host route dictionaries for the subnet',
        ];
    }

    public function statusQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'description' => 'Allows filtering by port status.',
            'enum'        => ['ACTIVE', 'DOWN'],
        ];
    }

    public function displayNameQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'display_name',
            'description' => 'Allows filtering by port name.',
        ];
    }

    public function adminStateQuery(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'admin_state',
            'description' => 'Allows filtering by admin state.',
        ];
    }

    public function networkIdQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'network_id',
            'description' => 'Allows filtering by network ID.',
        ];
    }

    public function tenantIdQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'tenant_id',
            'description' => 'Allows filtering by tenant ID.',
        ];
    }

    public function deviceOwnerQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'device_owner',
            'description' => 'Allows filtering by device owner.',
        ];
    }

    public function macAddrQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'mac_address',
            'description' => 'Allows filtering by MAC address.',
        ];
    }

    public function portIdQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'port_id',
            'description' => 'Allows filtering by port UUID.',
        ];
    }

    public function secGroupsQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'security_groups',
            'description' => 'Allows filtering by device owner. Format should be a comma-delimeted list.',
        ];
    }

    public function deviceIdQuery(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => 'device_id',
            'description' => 'The UUID of the device that uses this port. For example, a virtual server.',
        ];
    }

    public function macAddr(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'mac_address',
            'description' => 'The MAC address. If you specify an address that is not valid, a Bad Request (400) status code is returned. If you do not specify a MAC address, OpenStack Networking tries to allocate one. If a failure occurs, a Service Unavailable (503) response code is returned.',
        ];
    }

    public function fixedIps(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'fixed_ips',
            'description' => 'The IP addresses for the port. If you would like to assign multiple IP addresses for the
                              port, specify multiple entries in this field. Each entry consists of IP address (ipAddress)
                              and the subnet ID from which the IP address is assigned (subnetId)',
            'items' => [
                'type'       => self::OBJECT_TYPE,
                'properties' => [
                    'ipAddress' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'ip_address',
                        'description' => 'If you specify only an IP address, OpenStack Networking tries to allocate the IP address if the address is a valid IP for any of the subnets on the specified network.',
                    ],
                    'subnetId' => [
                        'type'        => self::STRING_TYPE,
                        'sentAs'      => 'subnet_id',
                        'description' => 'Subnet id. If you specify only a subnet ID, OpenStack Networking allocates an available IP from that subnet to the port.',
                    ],
                ],
            ],
        ];
    }

    public function subnetId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'subnet_id',
            'description' => 'If you specify only a subnet UUID, OpenStack Networking allocates an available IP from that subnet to the port. If you specify both a subnet UUID and an IP address, OpenStack Networking tries to allocate the address to the port.',
        ];
    }

    public function ipAddress(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'ip_address',
            'description' => 'If you specify both a subnet UUID and an IP address, OpenStack Networking tries to allocate the address to the port.',
        ];
    }

    public function secGroupIds(): array
    {
        return [
            'type'     => self::ARRAY_TYPE,
            'location' => self::JSON,
            'sentAs'   => 'security_groups',
            'items'    => [
                'type'        => self::STRING_TYPE,
                'description' => 'The UUID of the security group',
            ],
        ];
    }

    public function allowedAddrPairs(): array
    {
        return [
            'type'        => self::ARRAY_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'allowed_address_pairs',
            'description' => 'Address pairs extend the port attribute to enable you to specify arbitrary mac_address/ip_address(cidr) pairs that are allowed to pass through a port regardless of the subnet associated with the network.',
            'items'       => [
                'type'        => self::OBJECT_TYPE,
                'description' => 'A MAC addr/IP addr pair',
                'properties'  => [
                    'ipAddress' => [
                        'sentAs'   => 'ip_address',
                        'type'     => self::STRING_TYPE,
                        'location' => self::JSON,
                    ],
                    'macAddress' => [
                        'sentAs'   => 'mac_address',
                        'type'     => self::STRING_TYPE,
                        'location' => self::JSON,
                    ],
                ],
            ],
        ];
    }

    public function deviceOwner(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'device_owner',
            'description' => 'The UUID of the entity that uses this port. For example, a DHCP agent.',
        ];
    }

    public function deviceId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'device_id',
            'description' => 'The UUID of the device that uses this port. For example, a virtual server.',
        ];
    }

    public function queryName(): array
    {
        return $this->queryFilter('name');
    }

    public function queryTenantId(): array
    {
        return $this->queryFilter('tenant_id');
    }

    public function queryStatus(): array
    {
        return $this->queryFilter('status');
    }

    private function queryFilter($field): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::QUERY,
            'sentAs'      => $field,
            'description' => 'The Neutron API supports filtering based on all top level attributes of a resource.
            Filters are applicable to all list requests.',
        ];
    }

    public function routerAccessibleJson(): array
    {
        return [
            'type'        => self::BOOL_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'router:external',
            'description' => 'Indicates whether this network is externally accessible.',
        ];
    }

    public function queryRouterExternal(): array
    {
        return [
            'type'     => self::BOOL_TYPE,
            'location' => self::QUERY,
            'sentAs'   => 'router:external',
        ];
    }

    protected function quotaLimit(string $sentAs, string $description): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => $sentAs,
            'description' => $description,
        ];
    }

    public function quotaLimitFloatingIp(): array
    {
        return $this->quotaLimit('floatingip', 'The number of floating IP addresses allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitNetwork(): array
    {
        return $this->quotaLimit('network', 'The number of networks allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitPort(): array
    {
        return $this->quotaLimit('port', 'The number of ports allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitRbacPolicy(): array
    {
        return $this->quotaLimit('rbac_policy', 'The number of role-based access control (RBAC) policies for each project. A value of -1 means no limit.');
    }

    public function quotaLimitRouter(): array
    {
        return $this->quotaLimit('router', 'The number of routers allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitSecurityGroup(): array
    {
        return $this->quotaLimit('security_group', 'The number of security groups allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitSecurityGroupRule(): array
    {
        return $this->quotaLimit('security_group_rule', 'The number of security group rules allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitSubnet(): array
    {
        return $this->quotaLimit('subnet', 'The number of subnets allowed for each project. A value of -1 means no limit.');
    }

    public function quotaLimitSubnetPool(): array
    {
        return $this->quotaLimit('subnetpool', 'The number of subnet pools allowed for each project. A value of -1 means no limit.');
    }

    public function vipSubnetId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'vip_subnet_id',
            'description' => 'The network on which to allocate the load balancer\'s vip address.',
        ];
    }

    public function vipAddress(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'vip_address',
            'description' => 'The address to assign the load balancer\'s vip address to.',
        ];
    }

    public function provider(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'description' => 'The name of a valid provider to provision the load balancer.',
        ];
    }

    public function connectionLimit(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'connection_limit',
            'description' => 'The number of connections allowed by this listener.',
        ];
    }

    public function loadbalancerId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'loadbalancer_id',
            'description' => 'The ID of a load balancer.',
        ];
    }

    public function loadbalancerIdUrl(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'description' => 'The ID of a load balancer.',
        ];
    }

    public function protocol(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'description' => 'The protocol the frontend will be listening for. (TCP, HTTP, HTTPS)',
        ];
    }

    public function protocolPort(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'protocol_port',
            'description' => 'The port in which the frontend will be listening. (1-65535)',
        ];
    }

    public function lbAlgorithm(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'lb_algorithm',
            'description' => 'The load balancing algorithm to distribute traffic to the pool\'s members. (ROUND_ROBIN|LEAST_CONNECTIONS|SOURCE_IP)',
        ];
    }

    public function listenerId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'listener_id',
            'description' => 'The listener in which this pool will become the default pool. There can only be one default pool for a listener.',
        ];
    }

    public function sessionPersistence(): array
    {
        return [
            'type'        => self::OBJECT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'session_persistence',
            'description' => 'The default value for this is an empty dictionary.',
        ];
    }

    public function address(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'address',
            'description' => 'The IP Address of the member to receive traffic from the load balancer.',
        ];
    }

    public function poolId(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::URL,
            'description' => 'The ID of the load balancer pool.',
        ];
    }

    public function poolIdJson(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'pool_id',
            'description' => 'The ID of the load balancer pool.',
        ];
    }

    public function weight(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'description' => 'The default value for this attribute will be 1.',
        ];
    }

    public function delay(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'description' => 'The interval in seconds between health checks.',
        ];
    }

    public function timeout(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'description' => 'The time in seconds that a health check times out.',
        ];
    }

    public function maxRetries(): array
    {
        return [
            'type'        => self::INT_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'max_retries',
            'description' => 'Number of failed health checks before marked as OFFLINE.',
        ];
    }

    public function httpMethod(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'http_method',
            'description' => 'The default value for this attribute is GET.',
        ];
    }

    public function urlPath(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'url_path',
            'description' => 'The default value is "/"',
        ];
    }

    public function expectedCodes(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'sentAs'      => 'expected_codes',
            'description' => 'The expected http status codes to get from a successful health check. Defaults to 200. (comma separated)',
        ];
    }

    public function type(): array
    {
        return [
            'type'        => self::STRING_TYPE,
            'location'    => self::JSON,
            'description' => 'The type of health monitor. Must be one of TCP, HTTP, HTTPS',
        ];
    }
}
