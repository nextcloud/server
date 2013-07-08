<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

return array (
    'apiVersion' => '2012-06-01',
    'endpointPrefix' => 'elasticloadbalancing',
    'serviceFullName' => 'Elastic Load Balancing',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'ElasticLoadBalancing',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticloadbalancing.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'ApplySecurityGroupsToLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplySecurityGroupsToLoadBalancerOutput',
            'responseType' => 'model',
            'summary' => 'Associates one or more security groups with your LoadBalancer in VPC. The provided security group IDs will override any currently applied security groups.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ApplySecurityGroupsToLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SecurityGroups' => array(
                    'required' => true,
                    'description' => 'A list of security group IDs to associate with your LoadBalancer in VPC. The security group IDs must be provided as the ID and not the security group name (For example, sg-1234).',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroups.member',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
                array(
                    'reason' => 'One or more specified security groups do not exist.',
                    'class' => 'InvalidSecurityGroupException',
                ),
            ),
        ),
        'AttachLoadBalancerToSubnets' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AttachLoadBalancerToSubnetsOutput',
            'responseType' => 'model',
            'summary' => 'Adds one or more subnets to the set of configured subnets in the VPC for the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AttachLoadBalancerToSubnets',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Subnets' => array(
                    'required' => true,
                    'description' => 'A list of subnet IDs to add for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Subnets.member',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
                array(
                    'reason' => 'One or more subnets were not found.',
                    'class' => 'SubnetNotFoundException',
                ),
                array(
                    'reason' => 'The VPC has no Internet gateway.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'ConfigureHealthCheck' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigureHealthCheckOutput',
            'responseType' => 'model',
            'summary' => 'Enables the client to define an application healthcheck for the instances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ConfigureHealthCheck',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The mnemonic name associated with the LoadBalancer. This name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'HealthCheck' => array(
                    'required' => true,
                    'description' => 'A structure containing the configuration information for the new healthcheck.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Target' => array(
                            'required' => true,
                            'description' => 'Specifies the instance being checked. The protocol is either TCP, HTTP, HTTPS, or SSL. The range of valid ports is one (1) through 65535.',
                            'type' => 'string',
                        ),
                        'Interval' => array(
                            'required' => true,
                            'description' => 'Specifies the approximate interval, in seconds, between health checks of an individual instance.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 300,
                        ),
                        'Timeout' => array(
                            'required' => true,
                            'description' => 'Specifies the amount of time, in seconds, during which no response means a failed health probe.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 300,
                        ),
                        'UnhealthyThreshold' => array(
                            'required' => true,
                            'description' => 'Specifies the number of consecutive health probe failures required before moving the instance to the Unhealthy state.',
                            'type' => 'numeric',
                            'minimum' => 2,
                            'maximum' => 10,
                        ),
                        'HealthyThreshold' => array(
                            'required' => true,
                            'description' => 'Specifies the number of consecutive health probe successes required before moving the instance to the Healthy state.',
                            'type' => 'numeric',
                            'minimum' => 2,
                            'maximum' => 10,
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
            ),
        ),
        'CreateAppCookieStickinessPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Generates a stickiness policy with sticky session lifetimes that follow that of an application-generated cookie. This policy can be associated only with HTTP/HTTPS listeners.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateAppCookieStickinessPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name of the policy being created. The name must be unique within the set of policies for this LoadBalancer.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CookieName' => array(
                    'required' => true,
                    'description' => 'Name of the application cookie used for stickiness.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Policy with the same name exists for this LoadBalancer. Please choose another name.',
                    'class' => 'DuplicatePolicyNameException',
                ),
                array(
                    'reason' => 'Quota for number of policies for this LoadBalancer has already been reached.',
                    'class' => 'TooManyPoliciesException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'CreateLBCookieStickinessPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Generates a stickiness policy with sticky session lifetimes controlled by the lifetime of the browser (user-agent) or a specified expiration period. This policy can be associated only with HTTP/HTTPS listeners.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLBCookieStickinessPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name of the policy being created. The name must be unique within the set of policies for this LoadBalancer.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CookieExpirationPeriod' => array(
                    'description' => 'The time period in seconds after which the cookie should be considered stale. Not specifying this parameter indicates that the sticky session will last for the duration of the browser session.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Policy with the same name exists for this LoadBalancer. Please choose another name.',
                    'class' => 'DuplicatePolicyNameException',
                ),
                array(
                    'reason' => 'Quota for number of policies for this LoadBalancer has already been reached.',
                    'class' => 'TooManyPoliciesException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'CreateLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateAccessPointOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within your set of LoadBalancers.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Listeners' => array(
                    'required' => true,
                    'description' => 'A list of the following tuples: LoadBalancerPort, InstancePort, and Protocol.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Listeners.member',
                    'items' => array(
                        'name' => 'Listener',
                        'description' => 'The Listener data type.',
                        'type' => 'object',
                        'properties' => array(
                            'Protocol' => array(
                                'required' => true,
                                'description' => 'Specifies the LoadBalancer transport protocol to use for routing - HTTP, HTTPS, TCP or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'LoadBalancerPort' => array(
                                'required' => true,
                                'description' => 'Specifies the external LoadBalancer port number. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'numeric',
                            ),
                            'InstanceProtocol' => array(
                                'description' => 'Specifies the protocol to use for routing traffic to back-end instances - HTTP, HTTPS, TCP, or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'InstancePort' => array(
                                'required' => true,
                                'description' => 'Specifies the TCP port on which the instance server is listening. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'numeric',
                                'minimum' => 1,
                                'maximum' => 65535,
                            ),
                            'SSLCertificateId' => array(
                                'description' => 'The ARN string of the server certificate. To get the ARN of the server certificate, call the AWS Identity and Access Management UploadServerCertificate API.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'AvailabilityZones' => array(
                    'description' => 'A list of Availability Zones.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AvailabilityZones.member',
                    'items' => array(
                        'name' => 'AvailabilityZone',
                        'type' => 'string',
                    ),
                ),
                'Subnets' => array(
                    'description' => 'A list of subnet IDs in your VPC to attach to your LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Subnets.member',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                    ),
                ),
                'SecurityGroups' => array(
                    'description' => 'The security groups assigned to your LoadBalancer within your VPC.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroups.member',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'Scheme' => array(
                    'description' => 'The type of a LoadBalancer. This option is only available for LoadBalancers attached to a Amazon VPC. By default, Elastic Load Balancer creates an internet-facing load balancer with publicly resolvable DNS name that resolves to public IP addresses. Specify the value internal for this option to create an internal load balancer with a DNS name that resolves to private IP addresses.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'LoadBalancer name already exists for this account. Please choose another name.',
                    'class' => 'DuplicateAccessPointNameException',
                ),
                array(
                    'reason' => 'The quota for the number of LoadBalancers has already been reached.',
                    'class' => 'TooManyAccessPointsException',
                ),
                array(
                    'reason' => 'The specified SSL ID does not refer to a valid SSL certificate in the AWS Identity and Access Management Service.',
                    'class' => 'CertificateNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
                array(
                    'reason' => 'One or more subnets were not found.',
                    'class' => 'SubnetNotFoundException',
                ),
                array(
                    'reason' => 'The VPC has no Internet gateway.',
                    'class' => 'InvalidSubnetException',
                ),
                array(
                    'reason' => 'One or more specified security groups do not exist.',
                    'class' => 'InvalidSecurityGroupException',
                ),
                array(
                    'reason' => 'Invalid value for scheme. Scheme can only be specified for load balancers in VPC.',
                    'class' => 'InvalidSchemeException',
                ),
            ),
        ),
        'CreateLoadBalancerListeners' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates one or more listeners on a LoadBalancer for the specified port. If a listener with the given port does not already exist, it will be created; otherwise, the properties of the new listener must match the properties of the existing listener.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLoadBalancerListeners',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name of the new LoadBalancer. The name must be unique within your AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Listeners' => array(
                    'required' => true,
                    'description' => 'A list of LoadBalancerPort, InstancePort, Protocol, and SSLCertificateId items.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Listeners.member',
                    'items' => array(
                        'name' => 'Listener',
                        'description' => 'The Listener data type.',
                        'type' => 'object',
                        'properties' => array(
                            'Protocol' => array(
                                'required' => true,
                                'description' => 'Specifies the LoadBalancer transport protocol to use for routing - HTTP, HTTPS, TCP or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'LoadBalancerPort' => array(
                                'required' => true,
                                'description' => 'Specifies the external LoadBalancer port number. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'numeric',
                            ),
                            'InstanceProtocol' => array(
                                'description' => 'Specifies the protocol to use for routing traffic to back-end instances - HTTP, HTTPS, TCP, or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'InstancePort' => array(
                                'required' => true,
                                'description' => 'Specifies the TCP port on which the instance server is listening. This property cannot be modified for the life of the LoadBalancer.',
                                'type' => 'numeric',
                                'minimum' => 1,
                                'maximum' => 65535,
                            ),
                            'SSLCertificateId' => array(
                                'description' => 'The ARN string of the server certificate. To get the ARN of the server certificate, call the AWS Identity and Access Management UploadServerCertificate API.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'A Listener already exists for the given LoadBalancerName and LoadBalancerPort, but with a different InstancePort, Protocol, or SSLCertificateId.',
                    'class' => 'DuplicateListenerException',
                ),
                array(
                    'reason' => 'The specified SSL ID does not refer to a valid SSL certificate in the AWS Identity and Access Management Service.',
                    'class' => 'CertificateNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'CreateLoadBalancerPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new policy that contains the necessary attributes depending on the policy type. Policies are settings that are saved for your Elastic LoadBalancer and that can be applied to the front-end listener, or the back-end application server, depending on your policy type.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLoadBalancerPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer for which the policy is being created. This name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name of the LoadBalancer policy being created. The name must be unique within the set of policies for this LoadBalancer.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyTypeName' => array(
                    'required' => true,
                    'description' => 'The name of the base policy type being used to create this policy. To get the list of policy types, use the DescribeLoadBalancerPolicyTypes action.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyAttributes' => array(
                    'description' => 'A list of attributes associated with the policy being created.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyAttributes.member',
                    'items' => array(
                        'name' => 'PolicyAttribute',
                        'description' => 'The PolicyAttribute data type. This data type contains a key/value pair that defines properties of a specific policy.',
                        'type' => 'object',
                        'properties' => array(
                            'AttributeName' => array(
                                'description' => 'The name of the attribute associated with the policy.',
                                'type' => 'string',
                            ),
                            'AttributeValue' => array(
                                'description' => 'The value of the attribute associated with the policy.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'One or more of the specified policy types do not exist.',
                    'class' => 'PolicyTypeNotFoundException',
                ),
                array(
                    'reason' => 'Policy with the same name exists for this LoadBalancer. Please choose another name.',
                    'class' => 'DuplicatePolicyNameException',
                ),
                array(
                    'reason' => 'Quota for number of policies for this LoadBalancer has already been reached.',
                    'class' => 'TooManyPoliciesException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'DeleteLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteLoadBalancerListeners' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes listeners from the LoadBalancer for the specified port.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteLoadBalancerListeners',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The mnemonic name associated with the LoadBalancer.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LoadBalancerPorts' => array(
                    'required' => true,
                    'description' => 'The client port number(s) of the LoadBalancerListener(s) to be removed.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'LoadBalancerPorts.member',
                    'items' => array(
                        'name' => 'AccessPointPort',
                        'type' => 'numeric',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
            ),
        ),
        'DeleteLoadBalancerPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a policy from the LoadBalancer. The specified policy must not be enabled for any listeners.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteLoadBalancerPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The mnemonic name associated with the LoadBalancer. The name must be unique within your AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The mnemonic name for the policy being deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'DeregisterInstancesFromLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DeregisterEndPointsOutput',
            'responseType' => 'model',
            'summary' => 'Deregisters instances from the LoadBalancer. Once the instance is deregistered, it will stop receiving traffic from the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeregisterInstancesFromLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Instances' => array(
                    'required' => true,
                    'description' => 'A list of EC2 instance IDs consisting of all instances to be deregistered.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Instances.member',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'The Instance data type.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'The specified EndPoint is not valid.',
                    'class' => 'InvalidEndPointException',
                ),
            ),
        ),
        'DescribeInstanceHealth' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeEndPointStateOutput',
            'responseType' => 'model',
            'summary' => 'Returns the current state of the instances of the specified LoadBalancer. If no instances are specified, the state of all the instances for the LoadBalancer is returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeInstanceHealth',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Instances' => array(
                    'description' => 'A list of instance IDs whose states are being queried.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Instances.member',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'The Instance data type.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'The specified EndPoint is not valid.',
                    'class' => 'InvalidEndPointException',
                ),
            ),
        ),
        'DescribeLoadBalancerPolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeLoadBalancerPoliciesOutput',
            'responseType' => 'model',
            'summary' => 'Returns detailed descriptions of the policies. If you specify a LoadBalancer name, the operation returns either the descriptions of the specified policies, or descriptions of all the policies created for the LoadBalancer. If you don\'t specify a LoadBalancer name, the operation returns descriptions of the specified sample policies, or descriptions of all the sample policies. The names of the sample policies have the ELBSample- prefix.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeLoadBalancerPolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'description' => 'The mnemonic name associated with the LoadBalancer. If no name is specified, the operation returns the attributes of either all the sample policies pre-defined by Elastic Load Balancing or the specified sample polices.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PolicyNames' => array(
                    'description' => 'The names of LoadBalancer policies you\'ve created or Elastic Load Balancing sample policy names.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyNames.member',
                    'items' => array(
                        'name' => 'PolicyName',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'One or more specified policies were not found.',
                    'class' => 'PolicyNotFoundException',
                ),
            ),
        ),
        'DescribeLoadBalancerPolicyTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeLoadBalancerPolicyTypesOutput',
            'responseType' => 'model',
            'summary' => 'Returns meta-information on the specified LoadBalancer policies defined by the Elastic Load Balancing service. The policy types that are returned from this action can be used in a CreateLoadBalancerPolicy action to instantiate specific policy configurations that will be applied to an Elastic LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeLoadBalancerPolicyTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'PolicyTypeNames' => array(
                    'description' => 'Specifies the name of the policy types. If no names are specified, returns the description of all the policy types defined by Elastic Load Balancing service.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyTypeNames.member',
                    'items' => array(
                        'name' => 'PolicyTypeName',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'One or more of the specified policy types do not exist.',
                    'class' => 'PolicyTypeNotFoundException',
                ),
            ),
        ),
        'DescribeLoadBalancers' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAccessPointsOutput',
            'responseType' => 'model',
            'summary' => 'Returns detailed configuration information for the specified LoadBalancers. If no LoadBalancers are specified, the operation returns configuration information for all LoadBalancers created by the caller.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeLoadBalancers',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerNames' => array(
                    'description' => 'A list of names associated with the LoadBalancers at creation time.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'LoadBalancerNames.member',
                    'items' => array(
                        'name' => 'AccessPointName',
                        'type' => 'string',
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional parameter reserved for future use.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
            ),
        ),
        'DetachLoadBalancerFromSubnets' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DetachLoadBalancerFromSubnetsOutput',
            'responseType' => 'model',
            'summary' => 'Removes subnets from the set of configured subnets in the VPC for the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DetachLoadBalancerFromSubnets',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer to be detached. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Subnets' => array(
                    'required' => true,
                    'description' => 'A list of subnet IDs to remove from the set of configured subnets for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Subnets.member',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'DisableAvailabilityZonesForLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RemoveAvailabilityZonesOutput',
            'responseType' => 'model',
            'summary' => 'Removes the specified EC2 Availability Zones from the set of configured Availability Zones for the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisableAvailabilityZonesForLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZones' => array(
                    'required' => true,
                    'description' => 'A list of Availability Zones to be removed from the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AvailabilityZones.member',
                    'items' => array(
                        'name' => 'AvailabilityZone',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'EnableAvailabilityZonesForLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AddAvailabilityZonesOutput',
            'responseType' => 'model',
            'summary' => 'Adds one or more EC2 Availability Zones to the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableAvailabilityZonesForLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZones' => array(
                    'required' => true,
                    'description' => 'A list of new Availability Zones for the LoadBalancer. Each Availability Zone must be in the same Region as the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AvailabilityZones.member',
                    'items' => array(
                        'name' => 'AvailabilityZone',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
            ),
        ),
        'RegisterInstancesWithLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RegisterEndPointsOutput',
            'responseType' => 'model',
            'summary' => 'Adds new instances to the LoadBalancer.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RegisterInstancesWithLoadBalancer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Instances' => array(
                    'required' => true,
                    'description' => 'A list of instance IDs that should be registered with the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Instances.member',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'The Instance data type.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'The specified EndPoint is not valid.',
                    'class' => 'InvalidEndPointException',
                ),
            ),
        ),
        'SetLoadBalancerListenerSSLCertificate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Sets the certificate that terminates the specified listener\'s SSL connections. The specified certificate replaces any prior certificate that was used on the same LoadBalancer and port.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetLoadBalancerListenerSSLCertificate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name of the the LoadBalancer.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LoadBalancerPort' => array(
                    'required' => true,
                    'description' => 'The port that uses the specified SSL certificate.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'SSLCertificateId' => array(
                    'required' => true,
                    'description' => 'The ID of the SSL certificate chain to use. For more information on SSL certificates, see Managing Server Certificates in the AWS Identity and Access Management documentation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified SSL ID does not refer to a valid SSL certificate in the AWS Identity and Access Management Service.',
                    'class' => 'CertificateNotFoundException',
                ),
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'LoadBalancer does not have a listener configured at the given port.',
                    'class' => 'ListenerNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'SetLoadBalancerPoliciesForBackendServer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Replaces the current set of policies associated with a port on which the back-end server is listening with a new set of policies. After the policies have been created using CreateLoadBalancerPolicy, they can be applied here as a list. At this time, only the back-end server authentication policy type can be applied to the back-end ports; this policy type is composed of multiple public key policies.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetLoadBalancerPoliciesForBackendServer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The mnemonic name associated with the LoadBalancer. This name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstancePort' => array(
                    'required' => true,
                    'description' => 'The port number associated with the back-end server.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PolicyNames' => array(
                    'required' => true,
                    'description' => 'List of policy names to be set. If the list is empty, then all current polices are removed from the back-end server.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyNames.member',
                    'items' => array(
                        'name' => 'PolicyName',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'One or more specified policies were not found.',
                    'class' => 'PolicyNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
        'SetLoadBalancerPoliciesOfListener' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Associates, updates, or disables a policy with a listener on the LoadBalancer. You can associate multiple policies with a listener.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetLoadBalancerPoliciesOfListener',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-06-01',
                ),
                'LoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The name associated with the LoadBalancer. The name must be unique within the client AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LoadBalancerPort' => array(
                    'required' => true,
                    'description' => 'The external port of the LoadBalancer with which this policy applies to.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PolicyNames' => array(
                    'required' => true,
                    'description' => 'List of policies to be associated with the listener. Currently this list can have at most one policy. If the list is empty, the current policy is removed from the listener.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyNames.member',
                    'items' => array(
                        'name' => 'PolicyName',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified LoadBalancer could not be found.',
                    'class' => 'AccessPointNotFoundException',
                ),
                array(
                    'reason' => 'One or more specified policies were not found.',
                    'class' => 'PolicyNotFoundException',
                ),
                array(
                    'reason' => 'LoadBalancer does not have a listener configured at the given port.',
                    'class' => 'ListenerNotFoundException',
                ),
                array(
                    'reason' => 'Requested configuration change is invalid.',
                    'class' => 'InvalidConfigurationRequestException',
                ),
            ),
        ),
    ),
    'models' => array(
        'ApplySecurityGroupsToLoadBalancerOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SecurityGroups' => array(
                    'description' => 'A list of security group IDs associated with your LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'AttachLoadBalancerToSubnetsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subnets' => array(
                    'description' => 'A list of subnet IDs added for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'ConfigureHealthCheckOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HealthCheck' => array(
                    'description' => 'The updated healthcheck for the instances.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Target' => array(
                            'description' => 'Specifies the instance being checked. The protocol is either TCP, HTTP, HTTPS, or SSL. The range of valid ports is one (1) through 65535.',
                            'type' => 'string',
                        ),
                        'Interval' => array(
                            'description' => 'Specifies the approximate interval, in seconds, between health checks of an individual instance.',
                            'type' => 'numeric',
                        ),
                        'Timeout' => array(
                            'description' => 'Specifies the amount of time, in seconds, during which no response means a failed health probe.',
                            'type' => 'numeric',
                        ),
                        'UnhealthyThreshold' => array(
                            'description' => 'Specifies the number of consecutive health probe failures required before moving the instance to the Unhealthy state.',
                            'type' => 'numeric',
                        ),
                        'HealthyThreshold' => array(
                            'description' => 'Specifies the number of consecutive health probe successes required before moving the instance to the Healthy state.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CreateAccessPointOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DNSName' => array(
                    'description' => 'The DNS name for the LoadBalancer.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DeregisterEndPointsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Instances' => array(
                    'description' => 'An updated list of remaining instances registered with the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'The Instance data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeEndPointStateOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceStates' => array(
                    'description' => 'A list containing health information for the specified instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'InstanceState',
                        'description' => 'The InstanceState data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                            'State' => array(
                                'description' => 'Specifies the current status of the instance.',
                                'type' => 'string',
                            ),
                            'ReasonCode' => array(
                                'description' => 'Provides information about the cause of OutOfService instances. Specifically, it indicates whether the cause is Elastic Load Balancing or the instance behind the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides a description of the instance.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLoadBalancerPoliciesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyDescriptions' => array(
                    'description' => 'A list of policy description structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'PolicyDescription',
                        'description' => 'The PolicyDescription data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'PolicyName' => array(
                                'description' => 'The name mof the policy associated with the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'PolicyTypeName' => array(
                                'description' => 'The name of the policy type associated with the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'PolicyAttributeDescriptions' => array(
                                'description' => 'A list of policy attribute description structures.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'PolicyAttributeDescription',
                                    'description' => 'The PolicyAttributeDescription data type. This data type is used to describe the attributes and values associated with a policy.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The name of the attribute associated with the policy.',
                                            'type' => 'string',
                                        ),
                                        'AttributeValue' => array(
                                            'description' => 'The value of the attribute associated with the policy.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLoadBalancerPolicyTypesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyTypeDescriptions' => array(
                    'description' => 'List of policy type description structures of the specified policy type. If no policy type names are specified, returns the description of all the policy types defined by Elastic Load Balancing service.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'PolicyTypeDescription',
                        'description' => 'The PolicyTypeDescription data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'PolicyTypeName' => array(
                                'description' => 'The name of the policy type.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A human-readable description of the policy type.',
                                'type' => 'string',
                            ),
                            'PolicyAttributeTypeDescriptions' => array(
                                'description' => 'The description of the policy attributes associated with the LoadBalancer policies defined by the Elastic Load Balancing service.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'PolicyAttributeTypeDescription',
                                    'description' => 'The PolicyAttributeTypeDescription data type. This data type is used to describe values that are acceptable for the policy attribute.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'AttributeName' => array(
                                            'description' => 'The name of the attribute associated with the policy type.',
                                            'type' => 'string',
                                        ),
                                        'AttributeType' => array(
                                            'description' => 'The type of attribute. For example, Boolean, Integer, etc.',
                                            'type' => 'string',
                                        ),
                                        'Description' => array(
                                            'description' => 'A human-readable description of the attribute.',
                                            'type' => 'string',
                                        ),
                                        'DefaultValue' => array(
                                            'description' => 'The default value of the attribute, if applicable.',
                                            'type' => 'string',
                                        ),
                                        'Cardinality' => array(
                                            'description' => 'The cardinality of the attribute. Valid Values: ONE(1) : Single value required ZERO_OR_ONE(0..1) : Up to one value can be supplied ZERO_OR_MORE(0..*) : Optional. Multiple values are allowed ONE_OR_MORE(1..*0) : Required. Multiple values are allowed',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeAccessPointsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LoadBalancerDescriptions' => array(
                    'description' => 'A list of LoadBalancer description structures.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'LoadBalancerDescription',
                        'description' => 'Contains the result of a successful invocation of DescribeLoadBalancers.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'LoadBalancerName' => array(
                                'description' => 'Specifies the name associated with the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'DNSName' => array(
                                'description' => 'Specifies the external DNS name associated with the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'CanonicalHostedZoneName' => array(
                                'description' => 'Provides the name of the Amazon Route 53 hosted zone that is associated with the LoadBalancer. For information on how to associate your load balancer with a hosted zone, go to Using Domain Names With Elastic Load Balancing in the Elastic Load Balancing Developer Guide.',
                                'type' => 'string',
                            ),
                            'CanonicalHostedZoneNameID' => array(
                                'description' => 'Provides the ID of the Amazon Route 53 hosted zone name that is associated with the LoadBalancer. For information on how to associate or disassociate your load balancer with a hosted zone, go to Using Domain Names With Elastic Load Balancing in the Elastic Load Balancing Developer Guide.',
                                'type' => 'string',
                            ),
                            'ListenerDescriptions' => array(
                                'description' => 'LoadBalancerPort, InstancePort, Protocol, InstanceProtocol, and PolicyNames are returned in a list of tuples in the ListenerDescriptions element.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ListenerDescription',
                                    'description' => 'The ListenerDescription data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Listener' => array(
                                            'description' => 'The Listener data type.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Protocol' => array(
                                                    'description' => 'Specifies the LoadBalancer transport protocol to use for routing - HTTP, HTTPS, TCP or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                                    'type' => 'string',
                                                ),
                                                'LoadBalancerPort' => array(
                                                    'description' => 'Specifies the external LoadBalancer port number. This property cannot be modified for the life of the LoadBalancer.',
                                                    'type' => 'numeric',
                                                ),
                                                'InstanceProtocol' => array(
                                                    'description' => 'Specifies the protocol to use for routing traffic to back-end instances - HTTP, HTTPS, TCP, or SSL. This property cannot be modified for the life of the LoadBalancer.',
                                                    'type' => 'string',
                                                ),
                                                'InstancePort' => array(
                                                    'description' => 'Specifies the TCP port on which the instance server is listening. This property cannot be modified for the life of the LoadBalancer.',
                                                    'type' => 'numeric',
                                                ),
                                                'SSLCertificateId' => array(
                                                    'description' => 'The ARN string of the server certificate. To get the ARN of the server certificate, call the AWS Identity and Access Management UploadServerCertificate API.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'PolicyNames' => array(
                                            'description' => 'A list of policies enabled for this listener. An empty list indicates that no policies are enabled.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'PolicyName',
                                                'type' => 'string',
                                                'sentAs' => 'member',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Policies' => array(
                                'description' => 'Provides a list of policies defined for the LoadBalancer.',
                                'type' => 'object',
                                'properties' => array(
                                    'AppCookieStickinessPolicies' => array(
                                        'description' => 'A list of the AppCookieStickinessPolicy objects created with CreateAppCookieStickinessPolicy.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'AppCookieStickinessPolicy',
                                            'description' => 'The AppCookieStickinessPolicy data type.',
                                            'type' => 'object',
                                            'sentAs' => 'member',
                                            'properties' => array(
                                                'PolicyName' => array(
                                                    'description' => 'The mnemonic name for the policy being created. The name must be unique within a set of policies for this LoadBalancer.',
                                                    'type' => 'string',
                                                ),
                                                'CookieName' => array(
                                                    'description' => 'The name of the application cookie used for stickiness.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'LBCookieStickinessPolicies' => array(
                                        'description' => 'A list of LBCookieStickinessPolicy objects created with CreateAppCookieStickinessPolicy.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'LBCookieStickinessPolicy',
                                            'description' => 'The LBCookieStickinessPolicy data type.',
                                            'type' => 'object',
                                            'sentAs' => 'member',
                                            'properties' => array(
                                                'PolicyName' => array(
                                                    'description' => 'The name for the policy being created. The name must be unique within the set of policies for this LoadBalancer.',
                                                    'type' => 'string',
                                                ),
                                                'CookieExpirationPeriod' => array(
                                                    'description' => 'The time period in seconds after which the cookie should be considered stale. Not specifying this parameter indicates that the stickiness session will last for the duration of the browser session.',
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'OtherPolicies' => array(
                                        'description' => 'A list of policy names other than the stickiness policies.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'PolicyName',
                                            'type' => 'string',
                                            'sentAs' => 'member',
                                        ),
                                    ),
                                ),
                            ),
                            'BackendServerDescriptions' => array(
                                'description' => 'Contains a list of back-end server descriptions.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BackendServerDescription',
                                    'description' => 'This data type is used as a response element in the DescribeLoadBalancers action to describe the configuration of the back-end server.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'InstancePort' => array(
                                            'description' => 'Provides the port on which the back-end server is listening.',
                                            'type' => 'numeric',
                                        ),
                                        'PolicyNames' => array(
                                            'description' => 'Provides a list of policy names enabled for the back-end server.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'PolicyName',
                                                'type' => 'string',
                                                'sentAs' => 'member',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'AvailabilityZones' => array(
                                'description' => 'Specifies a list of Availability Zones.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AvailabilityZone',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'Subnets' => array(
                                'description' => 'Provides a list of VPC subnet IDs for the LoadBalancer.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'SubnetId',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'VPCId' => array(
                                'description' => 'Provides the ID of the VPC attached to the LoadBalancer.',
                                'type' => 'string',
                            ),
                            'Instances' => array(
                                'description' => 'Provides a list of EC2 instance IDs for the LoadBalancer.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Instance',
                                    'description' => 'The Instance data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'InstanceId' => array(
                                            'description' => 'Provides an EC2 instance ID.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'HealthCheck' => array(
                                'description' => 'Specifies information regarding the various health probes conducted on the LoadBalancer.',
                                'type' => 'object',
                                'properties' => array(
                                    'Target' => array(
                                        'description' => 'Specifies the instance being checked. The protocol is either TCP, HTTP, HTTPS, or SSL. The range of valid ports is one (1) through 65535.',
                                        'type' => 'string',
                                    ),
                                    'Interval' => array(
                                        'description' => 'Specifies the approximate interval, in seconds, between health checks of an individual instance.',
                                        'type' => 'numeric',
                                    ),
                                    'Timeout' => array(
                                        'description' => 'Specifies the amount of time, in seconds, during which no response means a failed health probe.',
                                        'type' => 'numeric',
                                    ),
                                    'UnhealthyThreshold' => array(
                                        'description' => 'Specifies the number of consecutive health probe failures required before moving the instance to the Unhealthy state.',
                                        'type' => 'numeric',
                                    ),
                                    'HealthyThreshold' => array(
                                        'description' => 'Specifies the number of consecutive health probe successes required before moving the instance to the Healthy state.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'SourceSecurityGroup' => array(
                                'description' => 'The security group that you can use as part of your inbound rules for your LoadBalancer\'s back-end Amazon EC2 application instances. To only allow traffic from LoadBalancers, add a security group rule to your back end instance that specifies this source security group as the inbound source.',
                                'type' => 'object',
                                'properties' => array(
                                    'OwnerAlias' => array(
                                        'description' => 'Owner of the source security group. Use this value for the --source-group-user parameter of the ec2-authorize command in the Amazon EC2 command line tool.',
                                        'type' => 'string',
                                    ),
                                    'GroupName' => array(
                                        'description' => 'Name of the source security group. Use this value for the --source-group parameter of the ec2-authorize command in the Amazon EC2 command line tool.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'SecurityGroups' => array(
                                'description' => 'The security groups the LoadBalancer is a member of (VPC only).',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'SecurityGroupId',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'CreatedTime' => array(
                                'description' => 'Provides the date and time the LoadBalancer was created.',
                                'type' => 'string',
                            ),
                            'Scheme' => array(
                                'description' => 'Specifies the type of a load balancer. If it is internet-facing, the load balancer has a publicly resolvable DNS name that resolves to public IP addresses. If it is internal, the load balancer has a publicly resolvable DNS name that resolves to private IP addresses. This option is only available for load balancers attached to a VPC.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextMarker' => array(
                    'description' => 'An optional parameter reserved for future use.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DetachLoadBalancerFromSubnetsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subnets' => array(
                    'description' => 'A list of subnet IDs removed from the configured set of subnets for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'RemoveAvailabilityZonesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AvailabilityZones' => array(
                    'description' => 'A list of updated Availability Zones for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AvailabilityZone',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'AddAvailabilityZonesOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AvailabilityZones' => array(
                    'description' => 'An updated list of Availability Zones for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AvailabilityZone',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'RegisterEndPointsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Instances' => array(
                    'description' => 'An updated list of instances for the LoadBalancer.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'The Instance data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Provides an EC2 instance ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeInstanceHealth' => array(
                'result_key' => 'InstanceStates',
            ),
            'DescribeLoadBalancerPolicies' => array(
                'result_key' => 'PolicyDescriptions',
            ),
            'DescribeLoadBalancerPolicyTypes' => array(
                'result_key' => 'PolicyTypeDescriptions',
            ),
            'DescribeLoadBalancers' => array(
                'token_param' => 'Marker',
                'token_key' => 'NextMarker',
                'result_key' => 'LoadBalancerDescriptions',
            ),
        ),
    ),
);
