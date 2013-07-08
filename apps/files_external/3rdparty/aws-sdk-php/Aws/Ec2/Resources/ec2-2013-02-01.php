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
    'apiVersion' => '2013-02-01',
    'endpointPrefix' => 'ec2',
    'serviceFullName' => 'Amazon Elastic Compute Cloud',
    'serviceAbbreviation' => 'Amazon EC2',
    'serviceType' => 'query',
    'signatureVersion' => 'v2',
    'namespace' => 'Ec2',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'ec2.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'ec2.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'ActivateLicense' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Activates a specific number of licenses for a 90-day period. Activations can be done against a specific license ID.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ActivateLicense',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'LicenseId' => array(
                    'required' => true,
                    'description' => 'Specifies the ID for the specific license to activate against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Capacity' => array(
                    'required' => true,
                    'description' => 'Specifies the additional number of licenses to activate.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AllocateAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AllocateAddressResult',
            'responseType' => 'model',
            'summary' => 'The AllocateAddress operation acquires an elastic IP address for use with your account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AllocateAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Domain' => array(
                    'description' => 'Set to vpc to allocate the address to your VPC. By default, will allocate to EC2.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'vpc',
                        'standard',
                    ),
                ),
            ),
        ),
        'AssignPrivateIpAddresses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AssignPrivateIpAddresses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PrivateIpAddresses' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PrivateIpAddress',
                    'items' => array(
                        'name' => 'PrivateIpAddress',
                        'type' => 'string',
                    ),
                ),
                'SecondaryPrivateIpAddressCount' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AllowReassignment' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AssociateAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AssociateAddressResult',
            'responseType' => 'model',
            'summary' => 'The AssociateAddress operation associates an elastic IP address with an instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AssociateAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'description' => 'The instance to associate with the IP address.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PublicIp' => array(
                    'description' => 'IP address that you are assigning to the instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllocationId' => array(
                    'description' => 'The allocation ID that AWS returned when you allocated the elastic IP address for use with Amazon VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NetworkInterfaceId' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PrivateIpAddress' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllowReassociation' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AssociateDhcpOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Associates a set of DHCP options (that you\'ve previously created) with the specified VPC. Or, associates the default DHCP options with the VPC. The default set consists of the standard EC2 host name, no domain name, no DNS server, no NTP server, and no NetBIOS server or node type. After you associate the options with the VPC, any existing instances and all new instances that you launch in that VPC use the options. For more information about the supported DHCP options and using them with Amazon VPC, go to Using DHCP Options in the Amazon Virtual Private Cloud Developer Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AssociateDhcpOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'DhcpOptionsId' => array(
                    'required' => true,
                    'description' => 'The ID of the DHCP options to associate with the VPC. Specify "default" to associate the default DHCP options with the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC to associate the DHCP options with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AssociateRouteTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AssociateRouteTableResult',
            'responseType' => 'model',
            'summary' => 'Associates a subnet with a route table. The subnet and route table must be in the same VPC. This association causes traffic originating from the subnet to be routed according to the routes in the route table. The action returns an association ID, which you need if you want to disassociate the route table from the subnet later. A route table can be associated with multiple subnets.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AssociateRouteTable',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SubnetId' => array(
                    'required' => true,
                    'description' => 'The ID of the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the route table.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AttachInternetGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Attaches an Internet gateway to a VPC, enabling connectivity between the Internet and the VPC. For more information about your VPC and Internet gateway, go to the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AttachInternetGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InternetGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the Internet gateway to attach.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AttachNetworkInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AttachNetworkInterfaceResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AttachNetworkInterface',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DeviceIndex' => array(
                    'required' => true,
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AttachVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'attachment',
            'responseType' => 'model',
            'summary' => 'Attach a previously created volume to a running instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AttachVolume',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'description' => 'The ID of the Amazon EBS volume. The volume and instance must be within the same Availability Zone and the instance must be running.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance to which the volume attaches. The volume and instance must be within the same Availability Zone and the instance must be running.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Device' => array(
                    'required' => true,
                    'description' => 'Specifies how the device is exposed to the instance (e.g., /dev/sdh).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AttachVpnGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AttachVpnGatewayResult',
            'responseType' => 'model',
            'summary' => 'Attaches a VPN gateway to a VPC. This is the last step required to get your VPC fully connected to your data center before launching instances in it. For more information, go to Process for Using Amazon VPC in the Amazon Virtual Private Cloud Developer Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AttachVpnGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPN gateway to attach to the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC to attach to the VPN gateway.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'AuthorizeSecurityGroupEgress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This action applies only to security groups in a VPC; it\'s not supported for EC2 security groups. For information about Amazon Virtual Private Cloud and VPC security groups, go to the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AuthorizeSecurityGroupEgress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupId' => array(
                    'required' => true,
                    'description' => 'ID of the VPC security group to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IpPermissions' => array(
                    'description' => 'List of IP permissions to authorize on the specified security group. Specifying permissions through IP permissions is the preferred way of authorizing permissions since it offers more flexibility and control.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'IpPermission',
                        'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                        'type' => 'object',
                        'properties' => array(
                            'IpProtocol' => array(
                                'description' => 'The IP protocol of this permission.',
                                'type' => 'string',
                            ),
                            'FromPort' => array(
                                'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                'type' => 'numeric',
                            ),
                            'ToPort' => array(
                                'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                'type' => 'numeric',
                            ),
                            'UserIdGroupPairs' => array(
                                'description' => 'The list of AWS user IDs and groups included in this permission.',
                                'type' => 'array',
                                'sentAs' => 'Groups',
                                'items' => array(
                                    'name' => 'Groups',
                                    'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'UserId' => array(
                                            'description' => 'The AWS user ID of an account.',
                                            'type' => 'string',
                                        ),
                                        'GroupName' => array(
                                            'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                        'GroupId' => array(
                                            'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IpRanges' => array(
                                'description' => 'The list of CIDR IP ranges included in this permission.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IpRange',
                                    'description' => 'Contains a list of CIRD IP ranges.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'CidrIp' => array(
                                            'description' => 'The list of CIDR IP ranges.',
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
        'AuthorizeSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The AuthorizeSecurityGroupIngress operation adds permissions to a security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AuthorizeSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'description' => 'Name of the standard (EC2) security group to modify. The group must belong to your account. Can be used instead of GroupID for standard (EC2) security groups.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GroupId' => array(
                    'description' => 'ID of the standard (EC2) or VPC security group to modify. The group must belong to your account. Required for VPC security groups; can be used instead of GroupName for standard (EC2) security groups.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IpPermissions' => array(
                    'description' => 'List of IP permissions to authorize on the specified security group. Specifying permissions through IP permissions is the preferred way of authorizing permissions since it offers more flexibility and control.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'IpPermission',
                        'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                        'type' => 'object',
                        'properties' => array(
                            'IpProtocol' => array(
                                'description' => 'The IP protocol of this permission.',
                                'type' => 'string',
                            ),
                            'FromPort' => array(
                                'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                'type' => 'numeric',
                            ),
                            'ToPort' => array(
                                'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                'type' => 'numeric',
                            ),
                            'UserIdGroupPairs' => array(
                                'description' => 'The list of AWS user IDs and groups included in this permission.',
                                'type' => 'array',
                                'sentAs' => 'Groups',
                                'items' => array(
                                    'name' => 'Groups',
                                    'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'UserId' => array(
                                            'description' => 'The AWS user ID of an account.',
                                            'type' => 'string',
                                        ),
                                        'GroupName' => array(
                                            'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                        'GroupId' => array(
                                            'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IpRanges' => array(
                                'description' => 'The list of CIDR IP ranges included in this permission.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IpRange',
                                    'description' => 'Contains a list of CIRD IP ranges.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'CidrIp' => array(
                                            'description' => 'The list of CIDR IP ranges.',
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
        'BundleInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'BundleInstanceResult',
            'responseType' => 'model',
            'summary' => 'The BundleInstance operation request that an instance is bundled the next time it boots. The bundling process creates a new image from a running instance and stores the AMI data in S3. Once bundled, the image must be registered in the normal way using the RegisterImage API.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'BundleInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance to bundle.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Storage' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'S3' => array(
                            'description' => 'The details of S3 storage for bundling a Windows instance.',
                            'type' => 'object',
                            'properties' => array(
                                'Bucket' => array(
                                    'description' => 'The bucket in which to store the AMI. You can specify a bucket that you already own or a new bucket that Amazon EC2 creates on your behalf.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'The prefix to use when storing the AMI in S3.',
                                    'type' => 'string',
                                ),
                                'AWSAccessKeyId' => array(
                                    'description' => 'The Access Key ID of the owner of the Amazon S3 bucket.',
                                    'type' => 'string',
                                ),
                                'UploadPolicy' => array(
                                    'description' => 'A Base64-encoded Amazon S3 upload policy that gives Amazon EC2 permission to upload items into Amazon S3 on the user\'s behalf.',
                                    'type' => 'string',
                                ),
                                'UploadPolicySignature' => array(
                                    'description' => 'The signature of the Base64 encoded JSON document.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CancelBundleTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CancelBundleTaskResult',
            'responseType' => 'model',
            'summary' => 'CancelBundleTask operation cancels a pending or in-progress bundling task. This is an asynchronous call and it make take a while for the task to be canceled. If a task is canceled while it is storing items, there may be parts of the incomplete AMI stored in S3. It is up to the caller to clean up these parts from S3.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelBundleTask',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'BundleId' => array(
                    'required' => true,
                    'description' => 'The ID of the bundle task to cancel.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CancelConversionTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelConversionTask',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ConversionTaskId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReasonMessage' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CancelExportTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelExportTask',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ExportTaskId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CancelReservedInstancesListing' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CancelReservedInstancesListingResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelReservedInstancesListing',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesListingId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CancelSpotInstanceRequests' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CancelSpotInstanceRequestsResult',
            'responseType' => 'model',
            'summary' => 'Cancels one or more Spot Instance requests.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CancelSpotInstanceRequests',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SpotInstanceRequestIds' => array(
                    'required' => true,
                    'description' => 'Specifies the ID of the Spot Instance request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SpotInstanceRequestId',
                    'items' => array(
                        'name' => 'SpotInstanceRequestId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'ConfirmProductInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfirmProductInstanceResult',
            'responseType' => 'model',
            'summary' => 'The ConfirmProductInstance operation returns true if the specified product code is attached to the specified instance. The operation returns false if the product code is not attached to the instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ConfirmProductInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ProductCode' => array(
                    'required' => true,
                    'description' => 'The product code to confirm.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance to confirm.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CopyImage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CopyImageResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CopyImage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SourceRegion' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceImageId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Name' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClientToken' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CopySnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CopySnapshotResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CopySnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SourceRegion' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceSnapshotId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateCustomerGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateCustomerGatewayResult',
            'responseType' => 'model',
            'summary' => 'Provides information to AWS about your customer gateway device. The customer gateway is the appliance at your end of the VPN connection (compared to the VPN gateway, which is the device at the AWS side of the VPN connection). You can have a single active customer gateway per AWS account (active means that you\'ve created a VPN connection to use with the customer gateway). AWS might delete any customer gateway that you create with this operation if you leave it inactive for an extended period of time.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCustomerGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Type' => array(
                    'required' => true,
                    'description' => 'The type of VPN connection this customer gateway supports.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PublicIp' => array(
                    'required' => true,
                    'description' => 'The Internet-routable IP address for the customer gateway\'s outside interface. The address must be static',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'sentAs' => 'IpAddress',
                ),
                'BgpAsn' => array(
                    'required' => true,
                    'description' => 'The customer gateway\'s Border Gateway Protocol (BGP) Autonomous System Number (ASN).',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateDhcpOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateDhcpOptionsResult',
            'responseType' => 'model',
            'summary' => 'Creates a set of DHCP options that you can then associate with one or more VPCs, causing all existing and new instances that you launch in those VPCs to use the set of DHCP options. The following table lists the individual DHCP options you can specify. For more information about the options, go to http://www.ietf.org/rfc/rfc2132.txt',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDhcpOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'DhcpConfigurations' => array(
                    'required' => true,
                    'description' => 'A set of one or more DHCP configurations.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DhcpConfiguration',
                    'items' => array(
                        'name' => 'DhcpConfiguration',
                        'description' => 'The DhcpConfiguration data type',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'Contains the name of a DHCP option.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains a set of values for a DHCP option.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateImage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateImageResult',
            'responseType' => 'model',
            'summary' => 'Creates an Amazon EBS-backed AMI from a "running" or "stopped" instance. AMIs that use an Amazon EBS root device boot faster than AMIs that use instance stores. They can be up to 1 TiB in size, use storage that persists on instance failure, and can be stopped and started.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateImage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance from which to create the new image.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The name for the new AMI being created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'The description for the new AMI being created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NoReboot' => array(
                    'description' => 'By default this property is set to false, which means Amazon EC2 attempts to cleanly shut down the instance before image creation and reboots the instance afterwards. When set to true, Amazon EC2 will not shut down the instance before creating the image. When this option is used, file system integrity on the created image cannot be guaranteed.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'BlockDeviceMappings' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BlockDeviceMapping',
                    'items' => array(
                        'name' => 'BlockDeviceMapping',
                        'description' => 'The BlockDeviceMappingItemType data type.',
                        'type' => 'object',
                        'properties' => array(
                            'VirtualName' => array(
                                'description' => 'Specifies the virtual device name.',
                                'type' => 'string',
                            ),
                            'DeviceName' => array(
                                'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                'type' => 'string',
                            ),
                            'Ebs' => array(
                                'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                'type' => 'object',
                                'properties' => array(
                                    'SnapshotId' => array(
                                        'description' => 'The ID of the snapshot from which the volume will be created.',
                                        'type' => 'string',
                                    ),
                                    'VolumeSize' => array(
                                        'description' => 'The size of the volume, in gigabytes.',
                                        'type' => 'numeric',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'format' => 'boolean-string',
                                    ),
                                    'VolumeType' => array(
                                        'type' => 'string',
                                        'enum' => array(
                                            'standard',
                                            'io1',
                                        ),
                                    ),
                                    'Iops' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'NoDevice' => array(
                                'description' => 'Specifies the device name to suppress during instance launch.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateInstanceExportTask' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateInstanceExportTaskResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateInstanceExportTask',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TargetEnvironment' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'citrix',
                        'vmware',
                    ),
                ),
                'ExportToS3Task' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'sentAs' => 'ExportToS3',
                    'properties' => array(
                        'DiskImageFormat' => array(
                            'type' => 'string',
                            'enum' => array(
                                'vmdk',
                                'vhd',
                            ),
                        ),
                        'ContainerFormat' => array(
                            'type' => 'string',
                            'enum' => array(
                                'ova',
                            ),
                        ),
                        'S3Bucket' => array(
                            'type' => 'string',
                        ),
                        'S3Prefix' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateInternetGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateInternetGatewayResult',
            'responseType' => 'model',
            'summary' => 'Creates a new Internet gateway in your AWS account. After creating the Internet gateway, you then attach it to a VPC using AttachInternetGateway. For more information about your VPC and Internet gateway, go to Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateInternetGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
            ),
        ),
        'CreateKeyPair' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateKeyPairResult',
            'responseType' => 'model',
            'summary' => 'The CreateKeyPair operation creates a new 2048 bit RSA key pair and returns a unique ID that can be used to reference this key pair when launching new instances. For more information, see RunInstances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateKeyPair',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'KeyName' => array(
                    'required' => true,
                    'description' => 'The unique name for the new key pair.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateNetworkAcl' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateNetworkAclResult',
            'responseType' => 'model',
            'summary' => 'Creates a new network ACL in a VPC. Network ACLs provide an optional layer of security (on top of security groups) for the instances in your VPC. For more information about network ACLs, go to Network ACLs in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateNetworkAcl',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC where the network ACL will be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateNetworkAclEntry' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates an entry (i.e., rule) in a network ACL with a rule number you specify. Each network ACL has a set of numbered ingress rules and a separate set of numbered egress rules. When determining whether a packet should be allowed in or out of a subnet associated with the ACL, Amazon VPC processes the entries in the ACL according to the rule numbers, in ascending order.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateNetworkAclEntry',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkAclId' => array(
                    'required' => true,
                    'description' => 'ID of the ACL where the entry will be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RuleNumber' => array(
                    'required' => true,
                    'description' => 'Rule number to assign to the entry (e.g., 100). ACL entries are processed in ascending order by rule number.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Protocol' => array(
                    'required' => true,
                    'description' => 'IP protocol the rule applies to. Valid Values: tcp, udp, icmp or an IP protocol number.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RuleAction' => array(
                    'required' => true,
                    'description' => 'Whether to allow or deny traffic that matches the rule.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'allow',
                        'deny',
                    ),
                ),
                'Egress' => array(
                    'required' => true,
                    'description' => 'Whether this rule applies to egress traffic from the subnet (true) or ingress traffic to the subnet (false).',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'CidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR range to allow or deny, in CIDR notation (e.g., 172.16.0.0/24).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IcmpTypeCode' => array(
                    'description' => 'ICMP values.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'sentAs' => 'Icmp',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'For the ICMP protocol, the ICMP type. A value of -1 is a wildcard meaning all types. Required if specifying icmp for the protocol.',
                            'type' => 'numeric',
                        ),
                        'Code' => array(
                            'description' => 'For the ICMP protocol, the ICMP code. A value of -1 is a wildcard meaning all codes. Required if specifying icmp for the protocol.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'PortRange' => array(
                    'description' => 'Port ranges.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'From' => array(
                            'description' => 'The first port in the range. Required if specifying tcp or udp for the protocol.',
                            'type' => 'numeric',
                        ),
                        'To' => array(
                            'description' => 'The last port in the range. Required if specifying tcp or udp for the protocol.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'CreateNetworkInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateNetworkInterfaceResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateNetworkInterface',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SubnetId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PrivateIpAddress' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Groups' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroupId',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'PrivateIpAddresses' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'PrivateIpAddressSpecification',
                        'type' => 'object',
                        'properties' => array(
                            'PrivateIpAddress' => array(
                                'required' => true,
                                'type' => 'string',
                            ),
                            'Primary' => array(
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
                'SecondaryPrivateIpAddressCount' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreatePlacementGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a PlacementGroup into which multiple Amazon EC2 instances can be launched. Users must give the group a name unique within the scope of the user account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreatePlacementGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'The name of the PlacementGroup.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Strategy' => array(
                    'required' => true,
                    'description' => 'The PlacementGroup strategy.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'cluster',
                    ),
                ),
            ),
        ),
        'CreateReservedInstancesListing' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateReservedInstancesListingResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateReservedInstancesListing',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceCount' => array(
                    'required' => true,
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PriceSchedules' => array(
                    'required' => true,
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'PriceScheduleSpecification',
                        'type' => 'object',
                        'properties' => array(
                            'Term' => array(
                                'type' => 'numeric',
                            ),
                            'Price' => array(
                                'type' => 'numeric',
                            ),
                            'CurrencyCode' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'ClientToken' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateRoute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new route in a route table within a VPC. The route\'s target can be either a gateway attached to the VPC or a NAT instance in the VPC.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateRoute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the route table where the route will be added.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationCidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR address block used for the destination match. For example: 0.0.0.0/0. Routing decisions are based on the most specific match.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GatewayId' => array(
                    'description' => 'The ID of a VPN or Internet gateway attached to your VPC. You must provide either GatewayId or InstanceId, but not both.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'description' => 'The ID of a NAT instance in your VPC. You must provide either GatewayId or InstanceId, but not both.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NetworkInterfaceId' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateRouteTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateRouteTableResult',
            'responseType' => 'model',
            'summary' => 'Creates a new route table within a VPC. After you create a new route table, you can add routes and associate the table with a subnet. For more information about route tables, go to Route Tables in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateRouteTable',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC where the route table will be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateSecurityGroupResult',
            'responseType' => 'model',
            'summary' => 'The CreateSecurityGroup operation creates a new security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'Name of the security group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'Description of the group. This is informational only.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupDescription',
                ),
                'VpcId' => array(
                    'description' => 'ID of the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'snapshot',
            'responseType' => 'model',
            'summary' => 'Create a snapshot of the volume identified by volume ID. A volume does not have to be detached at the time the snapshot is taken.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'description' => 'The ID of the volume from which to create the snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'The description for the new snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateSpotDatafeedSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateSpotDatafeedSubscriptionResult',
            'responseType' => 'model',
            'summary' => 'Creates the data feed for Spot Instances, enabling you to view Spot Instance usage logs. You can create one data feed per account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateSpotDatafeedSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Bucket' => array(
                    'required' => true,
                    'description' => 'The Amazon S3 bucket in which to store the Spot Instance datafeed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Prefix' => array(
                    'description' => 'The prefix that is prepended to datafeed files.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateSubnet' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateSubnetResult',
            'responseType' => 'model',
            'summary' => 'Creates a subnet in an existing VPC. You can create up to 20 subnets in a VPC. If you add more than one subnet to a VPC, they\'re set up in a star topology with a logical router in the middle. When you create each subnet, you provide the VPC ID and the CIDR block you want for the subnet. Once you create a subnet, you can\'t change its CIDR block. The subnet\'s CIDR block can be the same as the VPC\'s CIDR block (assuming you want only a single subnet in the VPC), or a subset of the VPC\'s CIDR block. If you create more than one subnet in a VPC, the subnets\' CIDR blocks must not overlap. The smallest subnet (and VPC) you can create uses a /28 netmask (16 IP addresses), and the largest uses a /18 netmask (16,384 IP addresses).',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateSubnet',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC to create the subnet in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR block the subnet is to cover.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The Availability Zone to create the subnet in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds or overwrites tags for the specified resources. Each resource can have a maximum of 10 tags. Each tag consists of a key-value pair. Tag keys must be unique per resource.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Resources' => array(
                    'required' => true,
                    'description' => 'One or more IDs of resources to tag. This could be the ID of an AMI, an instance, an EBS volume, or snapshot, etc.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ResourceId',
                    'items' => array(
                        'name' => 'ResourceId',
                        'type' => 'string',
                    ),
                ),
                'Tags' => array(
                    'required' => true,
                    'description' => 'The tags to add or overwrite for the specified resources. Each tag item consists of a key-value pair.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tag',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'The tag\'s key.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The tag\'s value.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'volume',
            'responseType' => 'model',
            'summary' => 'Initializes an empty volume of a given size.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVolume',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Size' => array(
                    'description' => 'The size of the volume, in gigabytes. Required if you are not creating a volume from a snapshot.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'SnapshotId' => array(
                    'description' => 'The ID of the snapshot from which to create the new volume.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'required' => true,
                    'description' => 'The Availability Zone in which to create the new volume.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VolumeType' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'standard',
                        'io1',
                    ),
                ),
                'Iops' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateVpc' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateVpcResult',
            'responseType' => 'model',
            'summary' => 'Creates a VPC with the CIDR block you specify. The smallest VPC you can create uses a /28 netmask (16 IP addresses), and the largest uses a /18 netmask (16,384 IP addresses). To help you decide how big to make your VPC, go to the topic about creating VPCs in the Amazon Virtual Private Cloud Developer Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVpc',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'CidrBlock' => array(
                    'required' => true,
                    'description' => 'A valid CIDR block.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceTenancy' => array(
                    'description' => 'The allowed tenancy of instances launched into the VPC. A value of default means instances can be launched with any tenancy; a value of dedicated means instances must be launched with tenancy as dedicated.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateVpnConnection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateVpnConnectionResult',
            'responseType' => 'model',
            'summary' => 'Creates a new VPN connection between an existing VPN gateway and customer gateway. The only supported connection type is ipsec.1.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVpnConnection',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Type' => array(
                    'required' => true,
                    'description' => 'The type of VPN connection.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CustomerGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the customer gateway.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpnGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPN gateway.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Options' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'StaticRoutesOnly' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
        ),
        'CreateVpnConnectionRoute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVpnConnectionRoute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnConnectionId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationCidrBlock' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'CreateVpnGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateVpnGatewayResult',
            'responseType' => 'model',
            'summary' => 'Creates a new VPN gateway. A VPN gateway is the VPC-side endpoint for your VPN connection. You can create a VPN gateway before creating the VPC itself.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateVpnGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Type' => array(
                    'required' => true,
                    'description' => 'The type of VPN connection this VPN gateway supports.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The Availability Zone in which to create the VPN gateway.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeactivateLicense' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deactivates a specific number of licenses. Deactivations can be done against a specific license ID after they have persisted for at least a 90-day period.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeactivateLicense',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'LicenseId' => array(
                    'required' => true,
                    'description' => 'Specifies the ID for the specific license to deactivate against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Capacity' => array(
                    'required' => true,
                    'description' => 'Specifies the amount of capacity to deactivate against the license.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteCustomerGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a customer gateway. You must delete the VPN connection before deleting the customer gateway.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCustomerGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'CustomerGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the customer gateway to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteDhcpOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a set of DHCP options that you specify. Amazon VPC returns an error if the set of options you specify is currently associated with a VPC. You can disassociate the set of options by associating either a new set of options or the default options with the VPC.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDhcpOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'DhcpOptionsId' => array(
                    'required' => true,
                    'description' => 'The ID of the DHCP options set to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteInternetGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes an Internet gateway from your AWS account. The gateway must not be attached to a VPC. For more information about your VPC and Internet gateway, go to Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteInternetGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InternetGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the Internet gateway to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteKeyPair' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeleteKeyPair operation deletes a key pair.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteKeyPair',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'KeyName' => array(
                    'required' => true,
                    'description' => 'The name of the Amazon EC2 key pair to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteNetworkAcl' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a network ACL from a VPC. The ACL must not have any subnets associated with it. You can\'t delete the default network ACL. For more information about network ACLs, go to Network ACLs in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteNetworkAcl',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkAclId' => array(
                    'required' => true,
                    'description' => 'The ID of the network ACL to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteNetworkAclEntry' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes an ingress or egress entry (i.e., rule) from a network ACL. For more information about network ACLs, go to Network ACLs in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteNetworkAclEntry',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkAclId' => array(
                    'required' => true,
                    'description' => 'ID of the network ACL.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RuleNumber' => array(
                    'required' => true,
                    'description' => 'Rule number for the entry to delete.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Egress' => array(
                    'required' => true,
                    'description' => 'Whether the rule to delete is an egress rule (true) or ingress rule (false).',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteNetworkInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteNetworkInterface',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeletePlacementGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a PlacementGroup from a user\'s account. Terminate all Amazon EC2 instances in the placement group before deletion.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeletePlacementGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'required' => true,
                    'description' => 'The name of the PlacementGroup to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteRoute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a route from a route table in a VPC. For more information about route tables, go to Route Tables in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteRoute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the route table where the route will be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationCidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR range for the route you want to delete. The value you specify must exactly match the CIDR for the route you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteRouteTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a route table from a VPC. The route table must not be associated with a subnet. You can\'t delete the main route table. For more information about route tables, go to Route Tables in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteRouteTable',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the route table to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeleteSecurityGroup operation deletes a security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'description' => 'The name of the Amazon EC2 security group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GroupId' => array(
                    'description' => 'The ID of the Amazon EC2 security group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the snapshot identified by snapshotId.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SnapshotId' => array(
                    'required' => true,
                    'description' => 'The ID of the snapshot to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteSpotDatafeedSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the data feed for Spot Instances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteSpotDatafeedSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
            ),
        ),
        'DeleteSubnet' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a subnet from a VPC. You must terminate all running instances in the subnet before deleting it, otherwise Amazon VPC returns an error.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteSubnet',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SubnetId' => array(
                    'required' => true,
                    'description' => 'The ID of the subnet you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes tags from the specified Amazon EC2 resources.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Resources' => array(
                    'required' => true,
                    'description' => 'A list of one or more resource IDs. This could be the ID of an AMI, an instance, an EBS volume, or snapshot, etc.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ResourceId',
                    'items' => array(
                        'name' => 'ResourceId',
                        'type' => 'string',
                    ),
                ),
                'Tags' => array(
                    'description' => 'The tags to delete from the specified resources. Each tag item consists of a key-value pair.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tag',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'The tag\'s key.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The tag\'s value.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a previously created volume. Once successfully deleted, a new volume can be created with the same name.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVolume',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'description' => 'The ID of the EBS volume to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteVpc' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a VPC. You must detach or delete all gateways or other objects that are dependent on the VPC first. For example, you must terminate all running instances, delete all VPC security groups (except the default), delete all the route tables (except the default), etc.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVpc',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteVpnConnection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a VPN connection. Use this if you want to delete a VPC and all its associated components. Another reason to use this operation is if you believe the tunnel credentials for your VPN connection have been compromised. In that situation, you can delete the VPN connection and create a new one that has new keys, without needing to delete the VPC or VPN gateway. If you create a new VPN connection, you must reconfigure the customer gateway using the new configuration information returned with the new VPN connection ID.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVpnConnection',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnConnectionId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPN connection to delete',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteVpnConnectionRoute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVpnConnectionRoute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnConnectionId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationCidrBlock' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeleteVpnGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a VPN gateway. Use this when you want to delete a VPC and all its associated components because you no longer need them. We recommend that before you delete a VPN gateway, you detach it from the VPC and delete the VPN connection. Note that you don\'t need to delete the VPN gateway if you just want to delete and re-create the VPN connection between your VPC and data center.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteVpnGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPN gateway to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DeregisterImage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DeregisterImage operation deregisters an AMI. Once deregistered, instances of the AMI can no longer be launched.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeregisterImage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'The ID of the AMI to deregister.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeAccountAttributes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAccountAttributesResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAccountAttributes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AttributeNames' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AttributeName',
                    'items' => array(
                        'name' => 'AttributeName',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'DescribeAddresses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAddressesResult',
            'responseType' => 'model',
            'summary' => 'The DescribeAddresses operation lists elastic IP addresses assigned to your account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAddresses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'PublicIps' => array(
                    'description' => 'The optional list of Elastic IP addresses to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PublicIp',
                    'items' => array(
                        'name' => 'PublicIp',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Addresses. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'AllocationIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AllocationId',
                    'items' => array(
                        'name' => 'AllocationId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'DescribeAvailabilityZones' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAvailabilityZonesResult',
            'responseType' => 'model',
            'summary' => 'The DescribeAvailabilityZones operation describes availability zones that are currently available to the account and their states.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAvailabilityZones',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ZoneNames' => array(
                    'description' => 'A list of the availability zone names to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ZoneName',
                    'items' => array(
                        'name' => 'ZoneName',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for AvailabilityZones. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeBundleTasks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeBundleTasksResult',
            'responseType' => 'model',
            'summary' => 'The DescribeBundleTasks operation describes in-progress and recent bundle tasks. Complete and failed tasks are removed from the list a short time after completion. If no bundle ids are given, all bundle tasks are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeBundleTasks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'BundleIds' => array(
                    'description' => 'The list of bundle task IDs to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BundleId',
                    'items' => array(
                        'name' => 'BundleId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for BundleTasks. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeConversionTasks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeConversionTasksResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeConversionTasks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Filters' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'ConversionTaskIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ConversionTaskId',
                    'items' => array(
                        'name' => 'ConversionTaskId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'DescribeCustomerGateways' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeCustomerGatewaysResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your customer gateways. You can filter the results to return information only about customer gateways that match criteria you specify. For example, you could ask to get information about a particular customer gateway (or all) only if the gateway\'s state is pending or available. You can specify multiple filters (e.g., the customer gateway has a particular IP address for the Internet-routable external interface, and the gateway\'s state is pending or available). The result includes information for a particular customer gateway only if the gateway matches all your filters. If there\'s no match, no special message is returned; the response is simply empty. The following table shows the available filters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCustomerGateways',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'CustomerGatewayIds' => array(
                    'description' => 'A set of one or more customer gateway IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'CustomerGatewayId',
                    'items' => array(
                        'name' => 'CustomerGatewayId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Customer Gateways. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeDhcpOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeDhcpOptionsResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about one or more sets of DHCP options. You can specify one or more DHCP options set IDs, or no IDs (to describe all your sets of DHCP options). The returned information consists of:',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDhcpOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'DhcpOptionsIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DhcpOptionsId',
                    'items' => array(
                        'name' => 'DhcpOptionsId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for DhcpOptions. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeExportTasks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeExportTasksResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeExportTasks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ExportTaskIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ExportTaskId',
                    'items' => array(
                        'name' => 'ExportTaskId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'DescribeImageAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'imageAttribute',
            'responseType' => 'model',
            'summary' => 'The DescribeImageAttribute operation returns information about an attribute of an AMI. Only one attribute can be specified per call.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeImageAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'The ID of the AMI whose attribute is to be described.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the attribute to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeImages' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeImagesResult',
            'responseType' => 'model',
            'summary' => 'The DescribeImages operation returns information about AMIs, AKIs, and ARIs available to the user. Information returned includes image type, product codes, architecture, and kernel and RAM disk IDs. Images available to the user include public images available for any user to launch, private images owned by the user making the request, and private images owned by other users for which the user has explicit launch permissions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeImages',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageIds' => array(
                    'description' => 'An optional list of the AMI IDs to describe. If not specified, all AMIs will be described.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ImageId',
                    'items' => array(
                        'name' => 'ImageId',
                        'type' => 'string',
                    ),
                ),
                'Owners' => array(
                    'description' => 'The optional list of owners for the described AMIs. The IDs amazon, self, and explicit can be used to include AMIs owned by Amazon, AMIs owned by the user, and AMIs for which the user has explicit launch permissions, respectively.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Owner',
                    'items' => array(
                        'name' => 'Owner',
                        'type' => 'string',
                    ),
                ),
                'ExecutableUsers' => array(
                    'description' => 'The optional list of users with explicit launch permissions for the described AMIs. The user ID can be a user\'s account ID, \'self\' to return AMIs for which the sender of the request has explicit launch permissions, or \'all\' to return AMIs with public launch permissions.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ExecutableBy',
                    'items' => array(
                        'name' => 'ExecutableBy',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Images. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeInstanceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'InstanceAttribute',
            'responseType' => 'model',
            'summary' => 'Returns information about an attribute of an instance. Only one attribute can be specified per call.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeInstanceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance whose instance attribute is being described.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the attribute to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'instanceType',
                        'kernel',
                        'ramdisk',
                        'userData',
                        'disableApiTermination',
                        'instanceInitiatedShutdownBehavior',
                        'rootDeviceName',
                        'blockDeviceMapping',
                        'productCodes',
                        'sourceDestCheck',
                        'groupSet',
                        'ebsOptimized',
                    ),
                ),
            ),
        ),
        'DescribeInstanceStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeInstanceStatusResult',
            'responseType' => 'model',
            'summary' => 'Describes the status of an Amazon Elastic Compute Cloud (Amazon EC2) instance. Instance status provides information about two types of scheduled events for an instance that may require your attention:',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeInstanceStatus',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'description' => 'The list of instance IDs. If not specified, all instances are described.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'The list of filters to limit returned results.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string specifying the next paginated set of results to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxResults' => array(
                    'description' => 'The maximum number of paginated instance items per response.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'IncludeAllInstances' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeInstancesResult',
            'responseType' => 'model',
            'summary' => 'The DescribeInstances operation returns information about instances that you own.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'description' => 'An optional list of the instances to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Instances. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeInternetGateways' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeInternetGatewaysResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your Internet gateways. You can filter the results to return information only about Internet gateways that match criteria you specify. For example, you could get information only about gateways with particular tags. The Internet gateway must match at least one of the specified values for it to be included in the results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeInternetGateways',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InternetGatewayIds' => array(
                    'description' => 'One or more Internet gateway IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InternetGatewayId',
                    'items' => array(
                        'name' => 'InternetGatewayId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Internet Gateways. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeKeyPairs' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeKeyPairsResult',
            'responseType' => 'model',
            'summary' => 'The DescribeKeyPairs operation returns information about key pairs available to you. If you specify key pairs, information about those key pairs is returned. Otherwise, information for all registered key pairs is returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeKeyPairs',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'KeyNames' => array(
                    'description' => 'The optional list of key pair names to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'KeyName',
                    'items' => array(
                        'name' => 'KeyName',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for KeyPairs. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLicenses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeLicensesResult',
            'responseType' => 'model',
            'summary' => 'Provides details of a user\'s registered licenses. Zero or more IDs may be specified on the call. When one or more license IDs are specified, only data for the specified IDs are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeLicenses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'LicenseIds' => array(
                    'description' => 'Specifies the license registration for which details are to be returned.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'LicenseId',
                    'items' => array(
                        'name' => 'LicenseId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Licenses. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNetworkAcls' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeNetworkAclsResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about the network ACLs in your VPC. You can filter the results to return information only about ACLs that match criteria you specify. For example, you could get information only the ACL associated with a particular subnet. The ACL must match at least one of the specified values for it to be included in the results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeNetworkAcls',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkAclIds' => array(
                    'description' => 'One or more network ACL IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'NetworkAclId',
                    'items' => array(
                        'name' => 'NetworkAclId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Network ACLs. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNetworkInterfaceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeNetworkInterfaceAttributeResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeNetworkInterfaceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceDestCheck' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Groups' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupSet',
                ),
                'Attachment' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeNetworkInterfaces' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeNetworkInterfacesResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeNetworkInterfaces',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'NetworkInterfaceId',
                    'items' => array(
                        'name' => 'NetworkInterfaceId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribePlacementGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribePlacementGroupsResult',
            'responseType' => 'model',
            'summary' => 'Returns information about one or more PlacementGroup instances in a user\'s account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribePlacementGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupNames' => array(
                    'description' => 'The name of the PlacementGroup.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupName',
                    'items' => array(
                        'name' => 'GroupName',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Placement Groups. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeRegions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeRegionsResult',
            'responseType' => 'model',
            'summary' => 'The DescribeRegions operation describes regions zones that are currently available to the account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeRegions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RegionNames' => array(
                    'description' => 'The optional list of regions to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'RegionName',
                    'items' => array(
                        'name' => 'RegionName',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Regions. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeReservedInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeReservedInstancesResult',
            'responseType' => 'model',
            'summary' => 'The DescribeReservedInstances operation describes Reserved Instances that were purchased for use with your account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesIds' => array(
                    'description' => 'The optional list of Reserved Instance IDs to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ReservedInstancesId',
                    'items' => array(
                        'name' => 'ReservedInstancesId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for ReservedInstances. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'OfferingType' => array(
                    'description' => 'The Reserved Instance offering type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeReservedInstancesListings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeReservedInstancesListingsResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedInstancesListings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesId' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReservedInstancesListingId' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Filters' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeReservedInstancesOfferings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeReservedInstancesOfferingsResult',
            'responseType' => 'model',
            'summary' => 'The DescribeReservedInstancesOfferings operation describes Reserved Instance offerings that are available for purchase. With Amazon EC2 Reserved Instances, you purchase the right to launch Amazon EC2 instances for a period of time (without getting insufficient capacity errors) and pay a lower usage rate for the actual time used.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedInstancesOfferings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesOfferingIds' => array(
                    'description' => 'An optional list of the unique IDs of the Reserved Instance offerings to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ReservedInstancesOfferingId',
                    'items' => array(
                        'name' => 'ReservedInstancesOfferingId',
                        'type' => 'string',
                    ),
                ),
                'InstanceType' => array(
                    'description' => 'The instance type on which the Reserved Instance can be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        't1.micro',
                        'm1.small',
                        'm1.medium',
                        'm1.large',
                        'm1.xlarge',
                        'm2.xlarge',
                        'm2.2xlarge',
                        'm2.4xlarge',
                        'm3.xlarge',
                        'm3.2xlarge',
                        'c1.medium',
                        'c1.xlarge',
                        'hi1.4xlarge',
                        'hs1.8xlarge',
                        'cc1.4xlarge',
                        'cc2.8xlarge',
                        'cg1.4xlarge',
                    ),
                ),
                'AvailabilityZone' => array(
                    'description' => 'The Availability Zone in which the Reserved Instance can be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ProductDescription' => array(
                    'description' => 'The Reserved Instance product description.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for ReservedInstancesOfferings. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'InstanceTenancy' => array(
                    'description' => 'The tenancy of the Reserved Instance offering. A Reserved Instance with tenancy of dedicated will run on single-tenant hardware and can only be launched within a VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OfferingType' => array(
                    'description' => 'The Reserved Instance offering type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxResults' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeRouteTables' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeRouteTablesResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your route tables. You can filter the results to return information only about tables that match criteria you specify. For example, you could get information only about a table associated with a particular subnet. You can specify multiple values for the filter. The table must match at least one of the specified values for it to be included in the results.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeRouteTables',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableIds' => array(
                    'description' => 'One or more route table IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'RouteTableId',
                    'items' => array(
                        'name' => 'RouteTableId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Route Tables. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSecurityGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSecurityGroupsResult',
            'responseType' => 'model',
            'summary' => 'The DescribeSecurityGroups operation returns information about security groups that you own.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSecurityGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupNames' => array(
                    'description' => 'The optional list of Amazon EC2 security groups to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupName',
                    'items' => array(
                        'name' => 'GroupName',
                        'type' => 'string',
                    ),
                ),
                'GroupIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupId',
                    'items' => array(
                        'name' => 'GroupId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for SecurityGroups. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSnapshotAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSnapshotAttributeResult',
            'responseType' => 'model',
            'summary' => 'Returns information about an attribute of a snapshot. Only one attribute can be specified per call.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSnapshotAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SnapshotId' => array(
                    'required' => true,
                    'description' => 'The ID of the EBS snapshot whose attribute is being described.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the EBS attribute to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'productCodes',
                        'createVolumePermission',
                    ),
                ),
            ),
        ),
        'DescribeSnapshots' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSnapshotsResult',
            'responseType' => 'model',
            'summary' => 'Returns information about the Amazon EBS snapshots available to you. Snapshots available to you include public snapshots available for any AWS account to launch, private snapshots you own, and private snapshots owned by another AWS account but for which you\'ve been given explicit create volume permissions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSnapshots',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SnapshotIds' => array(
                    'description' => 'The optional list of EBS snapshot IDs to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SnapshotId',
                    'items' => array(
                        'name' => 'SnapshotId',
                        'type' => 'string',
                    ),
                ),
                'OwnerIds' => array(
                    'description' => 'The optional list of EBS snapshot owners.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Owner',
                    'items' => array(
                        'name' => 'Owner',
                        'type' => 'string',
                    ),
                ),
                'RestorableByUserIds' => array(
                    'description' => 'The optional list of users who have permission to create volumes from the described EBS snapshots.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'RestorableBy',
                    'items' => array(
                        'name' => 'RestorableBy',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Snapshots. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSpotDatafeedSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSpotDatafeedSubscriptionResult',
            'responseType' => 'model',
            'summary' => 'Describes the data feed for Spot Instances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSpotDatafeedSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
            ),
        ),
        'DescribeSpotInstanceRequests' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSpotInstanceRequestsResult',
            'responseType' => 'model',
            'summary' => 'Describes Spot Instance requests. Spot Instances are instances that Amazon EC2 starts on your behalf when the maximum price that you specify exceeds the current Spot Price. Amazon EC2 periodically sets the Spot Price based on available Spot Instance capacity and current spot instance requests. For conceptual information about Spot Instances, refer to the Amazon Elastic Compute Cloud Developer Guide or Amazon Elastic Compute Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSpotInstanceRequests',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SpotInstanceRequestIds' => array(
                    'description' => 'The ID of the request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SpotInstanceRequestId',
                    'items' => array(
                        'name' => 'SpotInstanceRequestId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for SpotInstances. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSpotPriceHistory' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSpotPriceHistoryResult',
            'responseType' => 'model',
            'summary' => 'Describes the Spot Price history.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSpotPriceHistory',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'StartTime' => array(
                    'description' => 'The start date and time of the Spot Instance price history data.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The end date and time of the Spot Instance price history data.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'InstanceTypes' => array(
                    'description' => 'Specifies the instance type to return.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceType',
                    'items' => array(
                        'name' => 'InstanceType',
                        'type' => 'string',
                        'enum' => array(
                            't1.micro',
                            'm1.small',
                            'm1.medium',
                            'm1.large',
                            'm1.xlarge',
                            'm2.xlarge',
                            'm2.2xlarge',
                            'm2.4xlarge',
                            'm3.xlarge',
                            'm3.2xlarge',
                            'c1.medium',
                            'c1.xlarge',
                            'hi1.4xlarge',
                            'hs1.8xlarge',
                            'cc1.4xlarge',
                            'cc2.8xlarge',
                            'cg1.4xlarge',
                        ),
                    ),
                ),
                'ProductDescriptions' => array(
                    'description' => 'The description of the AMI.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ProductDescription',
                    'items' => array(
                        'name' => 'ProductDescription',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for SpotPriceHistory. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'AvailabilityZone' => array(
                    'description' => 'Filters the results by availability zone (ex: \'us-east-1a\').',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxResults' => array(
                    'description' => 'Specifies the number of rows to return.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'Specifies the next set of rows to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeSubnets' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeSubnetsResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your subnets. You can filter the results to return information only about subnets that match criteria you specify.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeSubnets',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SubnetIds' => array(
                    'description' => 'A set of one or more subnet IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SubnetId',
                    'items' => array(
                        'name' => 'SubnetId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Subnets. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeTagsResult',
            'responseType' => 'model',
            'summary' => 'Describes the tags for the specified resources.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for tags.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVolumeAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVolumeAttributeResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVolumeAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'autoEnableIO',
                        'productCodes',
                    ),
                ),
            ),
        ),
        'DescribeVolumeStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVolumeStatusResult',
            'responseType' => 'model',
            'summary' => 'Describes the status of a volume.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVolumeStatus',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VolumeId',
                    'items' => array(
                        'name' => 'VolumeId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxResults' => array(
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeVolumes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVolumesResult',
            'responseType' => 'model',
            'summary' => 'Describes the status of the indicated volume or, in lieu of any specified, all volumes belonging to the caller. Volumes that have been deleted are not described.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVolumes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeIds' => array(
                    'description' => 'The optional list of EBS volumes to describe.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VolumeId',
                    'items' => array(
                        'name' => 'VolumeId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for Volumes. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpcAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVpcAttributeResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVpcAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'enableDnsSupport',
                        'enableDnsHostnames',
                    ),
                ),
            ),
        ),
        'DescribeVpcs' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVpcsResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your VPCs. You can filter the results to return information only about VPCs that match criteria you specify.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVpcs',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcIds' => array(
                    'description' => 'The ID of a VPC you want information about.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpcId',
                    'items' => array(
                        'name' => 'VpcId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for VPCs. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpnConnections' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVpnConnectionsResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your VPN connections.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVpnConnections',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnConnectionIds' => array(
                    'description' => 'A VPN connection ID. More than one may be specified per request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpnConnectionId',
                    'items' => array(
                        'name' => 'VpnConnectionId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for VPN Connections. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpnGateways' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeVpnGatewaysResult',
            'responseType' => 'model',
            'summary' => 'Gives you information about your VPN gateways. You can filter the results to return information only about VPN gateways that match criteria you specify.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeVpnGateways',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnGatewayIds' => array(
                    'description' => 'A list of filters used to match properties for VPN Gateways. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpnGatewayId',
                    'items' => array(
                        'name' => 'VpnGatewayId',
                        'type' => 'string',
                    ),
                ),
                'Filters' => array(
                    'description' => 'A list of filters used to match properties for VPN Gateways. For a complete reference to the available filter keys for this operation, see the Amazon EC2 API reference.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filter',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'A filter used to limit results when describing tags. Multiple values can be specified per filter. A tag must match at least one of the specified values for it to be returned from an operation.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Specifies the name of the filter.',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'Contains one or more values for the filter.',
                                'type' => 'array',
                                'sentAs' => 'Value',
                                'items' => array(
                                    'name' => 'Value',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DetachInternetGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Detaches an Internet gateway from a VPC, disabling connectivity between the Internet and the VPC. The VPC must not contain any running instances with elastic IP addresses. For more information about your VPC and Internet gateway, go to Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DetachInternetGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InternetGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the Internet gateway to detach.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DetachNetworkInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DetachNetworkInterface',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AttachmentId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Force' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DetachVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'attachment',
            'responseType' => 'model',
            'summary' => 'Detach a previously attached volume from a running instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DetachVolume',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'description' => 'The ID of the volume to detach.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'description' => 'The ID of the instance from which to detach the the specified volume.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Device' => array(
                    'description' => 'The device name to which the volume is attached on the specified instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Force' => array(
                    'description' => 'Forces detachment if the previous detachment attempt did not occur cleanly (logging into an instance, unmounting the volume, and detaching normally).',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DetachVpnGateway' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Detaches a VPN gateway from a VPC. You do this if you\'re planning to turn off the VPC and not use it anymore. You can confirm a VPN gateway has been completely detached from a VPC by describing the VPN gateway (any attachments to the VPN gateway are also described).',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DetachVpnGateway',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpnGatewayId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPN gateway to detach from the VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'VpcId' => array(
                    'required' => true,
                    'description' => 'The ID of the VPC to detach the VPN gateway from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DisableVgwRoutePropagation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisableVgwRoutePropagation',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GatewayId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DisassociateAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The DisassociateAddress operation disassociates the specified elastic IP address from the instance to which it is assigned. This is an idempotent operation. If you enter it more than once, Amazon EC2 does not return an error.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisassociateAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'PublicIp' => array(
                    'description' => 'The elastic IP address that you are disassociating from the instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AssociationId' => array(
                    'description' => 'Association ID corresponding to the VPC elastic IP address you want to disassociate.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DisassociateRouteTable' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Disassociates a subnet from a route table.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisassociateRouteTable',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AssociationId' => array(
                    'required' => true,
                    'description' => 'The association ID representing the current association between the route table and subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'EnableVgwRoutePropagation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableVgwRoutePropagation',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GatewayId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'EnableVolumeIO' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Enable IO on the volume after an event has occured.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableVolumeIO',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'GetConsoleOutput' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetConsoleOutputResult',
            'responseType' => 'model',
            'summary' => 'The GetConsoleOutput operation retrieves console output for the specified instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetConsoleOutput',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance for which you want console output.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'GetPasswordData' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetPasswordDataResult',
            'responseType' => 'model',
            'summary' => 'Retrieves the encrypted administrator password for the instances running Windows.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetPasswordData',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance for which you want the Windows administrator password.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ImportInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ImportInstanceResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ImportInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LaunchSpecification' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Architecture' => array(
                            'type' => 'string',
                        ),
                        'SecurityGroups' => array(
                            'type' => 'array',
                            'sentAs' => 'SecurityGroup',
                            'items' => array(
                                'name' => 'SecurityGroup',
                                'type' => 'string',
                            ),
                        ),
                        'AdditionalInfo' => array(
                            'type' => 'string',
                        ),
                        'UserData' => array(
                            'type' => 'string',
                        ),
                        'InstanceType' => array(
                            'type' => 'string',
                            'enum' => array(
                                't1.micro',
                                'm1.small',
                                'm1.medium',
                                'm1.large',
                                'm1.xlarge',
                                'm2.xlarge',
                                'm2.2xlarge',
                                'm2.4xlarge',
                                'm3.xlarge',
                                'm3.2xlarge',
                                'c1.medium',
                                'c1.xlarge',
                                'hi1.4xlarge',
                                'hs1.8xlarge',
                                'cc1.4xlarge',
                                'cc2.8xlarge',
                                'cg1.4xlarge',
                            ),
                        ),
                        'Placement' => array(
                            'description' => 'Describes where an Amazon EC2 instance is running within an Amazon EC2 region.',
                            'type' => 'object',
                            'properties' => array(
                                'AvailabilityZone' => array(
                                    'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                    'type' => 'string',
                                ),
                                'GroupName' => array(
                                    'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                    'type' => 'string',
                                ),
                                'Tenancy' => array(
                                    'description' => 'The allowed tenancy of instances launched into the VPC. A value of default means instances can be launched with any tenancy; a value of dedicated means all instances launched into the VPC will be launched as dedicated tenancy regardless of the tenancy assigned to the instance at launch.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'BlockDeviceMappings' => array(
                            'type' => 'array',
                            'sentAs' => 'BlockDeviceMapping',
                            'items' => array(
                                'name' => 'BlockDeviceMapping',
                                'description' => 'The BlockDeviceMappingItemType data type.',
                                'type' => 'object',
                                'properties' => array(
                                    'VirtualName' => array(
                                        'description' => 'Specifies the virtual device name.',
                                        'type' => 'string',
                                    ),
                                    'DeviceName' => array(
                                        'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                        'type' => 'string',
                                    ),
                                    'Ebs' => array(
                                        'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'SnapshotId' => array(
                                                'description' => 'The ID of the snapshot from which the volume will be created.',
                                                'type' => 'string',
                                            ),
                                            'VolumeSize' => array(
                                                'description' => 'The size of the volume, in gigabytes.',
                                                'type' => 'numeric',
                                            ),
                                            'DeleteOnTermination' => array(
                                                'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                            'VolumeType' => array(
                                                'type' => 'string',
                                                'enum' => array(
                                                    'standard',
                                                    'io1',
                                                ),
                                            ),
                                            'Iops' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'NoDevice' => array(
                                        'description' => 'Specifies the device name to suppress during instance launch.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Monitoring' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'SubnetId' => array(
                            'type' => 'string',
                        ),
                        'DisableApiTermination' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'InstanceInitiatedShutdownBehavior' => array(
                            'type' => 'string',
                        ),
                        'PrivateIpAddress' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'DiskImages' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DiskImage',
                    'items' => array(
                        'name' => 'DiskImage',
                        'type' => 'object',
                        'properties' => array(
                            'Image' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Format' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                    'Bytes' => array(
                                        'required' => true,
                                        'type' => 'numeric',
                                    ),
                                    'ImportManifestUrl' => array(
                                        'required' => true,
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Description' => array(
                                'type' => 'string',
                            ),
                            'Volume' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Size' => array(
                                        'required' => true,
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Platform' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ImportKeyPair' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ImportKeyPairResult',
            'responseType' => 'model',
            'summary' => 'Imports the public key from an RSA key pair created with a third-party tool. This operation differs from CreateKeyPair as the private key is never transferred between the caller and AWS servers.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ImportKeyPair',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'KeyName' => array(
                    'required' => true,
                    'description' => 'The unique name for the key pair.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PublicKeyMaterial' => array(
                    'required' => true,
                    'description' => 'The public key portion of the key pair being imported. This value will be base64 encoded for you automatically.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'filters' => array(
                        'base64_encode',
                    ),
                ),
            ),
        ),
        'ImportVolume' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ImportVolumeResult',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ImportVolume',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AvailabilityZone' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Image' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Format' => array(
                            'required' => true,
                            'type' => 'string',
                        ),
                        'Bytes' => array(
                            'required' => true,
                            'type' => 'numeric',
                        ),
                        'ImportManifestUrl' => array(
                            'required' => true,
                            'type' => 'string',
                        ),
                    ),
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Volume' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Size' => array(
                            'required' => true,
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'ModifyImageAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The ModifyImageAttribute operation modifies an attribute of an AMI.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyImageAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'The ID of the AMI whose attribute you want to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'description' => 'The name of the AMI attribute you want to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OperationType' => array(
                    'description' => 'The type of operation being requested.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'UserIds' => array(
                    'description' => 'The AWS user ID being added to or removed from the list of users with launch permissions for this AMI. Only valid when the launchPermission attribute is being modified.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'UserId',
                    'items' => array(
                        'name' => 'UserId',
                        'type' => 'string',
                    ),
                ),
                'UserGroups' => array(
                    'description' => 'The user group being added to or removed from the list of user groups with launch permissions for this AMI. Only valid when the launchPermission attribute is being modified.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'UserGroup',
                    'items' => array(
                        'name' => 'UserGroup',
                        'type' => 'string',
                    ),
                ),
                'ProductCodes' => array(
                    'description' => 'The list of product codes being added to or removed from the specified AMI. Only valid when the productCodes attribute is being modified.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ProductCode',
                    'items' => array(
                        'name' => 'ProductCode',
                        'type' => 'string',
                    ),
                ),
                'Value' => array(
                    'description' => 'The value of the attribute being modified. Only valid when the description attribute is being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LaunchPermission' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Add' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LaunchPermission',
                                'description' => 'Describes a permission to launch an Amazon Machine Image (AMI).',
                                'type' => 'object',
                                'properties' => array(
                                    'UserId' => array(
                                        'description' => 'The AWS user ID of the user involved in this launch permission.',
                                        'type' => 'string',
                                    ),
                                    'Group' => array(
                                        'description' => 'The AWS group of the user involved in this launch permission.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Remove' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LaunchPermission',
                                'description' => 'Describes a permission to launch an Amazon Machine Image (AMI).',
                                'type' => 'object',
                                'properties' => array(
                                    'UserId' => array(
                                        'description' => 'The AWS user ID of the user involved in this launch permission.',
                                        'type' => 'string',
                                    ),
                                    'Group' => array(
                                        'description' => 'The AWS group of the user involved in this launch permission.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Description' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'ModifyInstanceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Modifies an attribute of an instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyInstanceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the instance whose attribute is being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'description' => 'The name of the attribute being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'instanceType',
                        'kernel',
                        'ramdisk',
                        'userData',
                        'disableApiTermination',
                        'instanceInitiatedShutdownBehavior',
                        'rootDeviceName',
                        'blockDeviceMapping',
                        'productCodes',
                        'sourceDestCheck',
                        'groupSet',
                        'ebsOptimized',
                    ),
                ),
                'Value' => array(
                    'description' => 'The new value of the instance attribute being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'The new block device mappings for the instance whose attributes are being modified.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BlockDeviceMapping',
                    'items' => array(
                        'name' => 'BlockDeviceMapping',
                        'description' => 'Specifies how an instance\'s block devices should be mapped on a running instance.',
                        'type' => 'object',
                        'properties' => array(
                            'DeviceName' => array(
                                'description' => 'The device name (e.g., /dev/sdh) at which the block device is exposed on the instance.',
                                'type' => 'string',
                            ),
                            'Ebs' => array(
                                'description' => 'The EBS instance block device specification describing the EBS block device to map to the specified device name on a running instance.',
                                'type' => 'object',
                                'properties' => array(
                                    'VolumeId' => array(
                                        'description' => 'The ID of the EBS volume that should be mounted as a block device on an Amazon EC2 instance.',
                                        'type' => 'string',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'format' => 'boolean-string',
                                    ),
                                ),
                            ),
                            'VirtualName' => array(
                                'description' => 'The virtual device name.',
                                'type' => 'string',
                            ),
                            'NoDevice' => array(
                                'description' => 'When set to the empty string, specifies that the device name in this object should not be mapped to any real device.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'SourceDestCheck' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'DisableApiTermination' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'InstanceType' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Kernel' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Ramdisk' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'UserData' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'InstanceInitiatedShutdownBehavior' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Groups' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'GroupId',
                    'items' => array(
                        'name' => 'GroupId',
                        'type' => 'string',
                    ),
                ),
                'EbsOptimized' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
        ),
        'ModifyNetworkInterfaceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyNetworkInterfaceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                        ),
                    ),
                ),
                'SourceDestCheck' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'Groups' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroupId',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'Attachment' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'AttachmentId' => array(
                            'type' => 'string',
                        ),
                        'DeleteOnTermination' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
        ),
        'ModifySnapshotAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds or remove permission settings for the specified snapshot.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifySnapshotAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SnapshotId' => array(
                    'required' => true,
                    'description' => 'The ID of the EBS snapshot whose attributes are being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'description' => 'The name of the attribute being modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'productCodes',
                        'createVolumePermission',
                    ),
                ),
                'OperationType' => array(
                    'description' => 'The operation to perform on the attribute.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'UserIds' => array(
                    'description' => 'The AWS user IDs to add to or remove from the list of users that have permission to create EBS volumes from the specified snapshot. Currently supports "all".',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'UserId',
                    'items' => array(
                        'name' => 'UserId',
                        'type' => 'string',
                    ),
                ),
                'GroupNames' => array(
                    'description' => 'The AWS group names to add to or remove from the list of groups that have permission to create EBS volumes from the specified snapshot. Currently supports "all".',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'UserGroup',
                    'items' => array(
                        'name' => 'UserGroup',
                        'type' => 'string',
                    ),
                ),
                'CreateVolumePermission' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Add' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CreateVolumePermission',
                                'description' => 'Describes a permission allowing either a user or group to create a new EBS volume from a snapshot.',
                                'type' => 'object',
                                'properties' => array(
                                    'UserId' => array(
                                        'description' => 'The user ID of the user that can create volumes from the snapshot.',
                                        'type' => 'string',
                                    ),
                                    'Group' => array(
                                        'description' => 'The group that is allowed to create volumes from the snapshot (currently supports "all").',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Remove' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CreateVolumePermission',
                                'description' => 'Describes a permission allowing either a user or group to create a new EBS volume from a snapshot.',
                                'type' => 'object',
                                'properties' => array(
                                    'UserId' => array(
                                        'description' => 'The user ID of the user that can create volumes from the snapshot.',
                                        'type' => 'string',
                                    ),
                                    'Group' => array(
                                        'description' => 'The group that is allowed to create volumes from the snapshot (currently supports "all").',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ModifyVolumeAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyVolumeAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VolumeId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutoEnableIO' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ModifyVpcAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyVpcAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'VpcId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnableDnsSupport' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'EnableDnsHostnames' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
            ),
        ),
        'MonitorInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'MonitorInstancesResult',
            'responseType' => 'model',
            'summary' => 'Enables monitoring for a running instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'MonitorInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of Amazon EC2 instances on which to enable monitoring.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'PurchaseReservedInstancesOffering' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'PurchaseReservedInstancesOfferingResult',
            'responseType' => 'model',
            'summary' => 'The PurchaseReservedInstancesOffering operation purchases a Reserved Instance for use with your account. With Amazon EC2 Reserved Instances, you purchase the right to launch Amazon EC2 instances for a period of time (without getting insufficient capacity errors) and pay a lower usage rate for the actual time used.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PurchaseReservedInstancesOffering',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ReservedInstancesOfferingId' => array(
                    'required' => true,
                    'description' => 'The unique ID of the Reserved Instances offering being purchased.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceCount' => array(
                    'required' => true,
                    'description' => 'The number of Reserved Instances to purchase.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'LimitPrice' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Amount' => array(
                            'type' => 'numeric',
                        ),
                        'CurrencyCode' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'RebootInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The RebootInstances operation requests a reboot of one or more instances. This operation is asynchronous; it only queues a request to reboot the specified instance(s). The operation will succeed if the instances are valid and belong to the user. Requests to reboot terminated instances are ignored.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RebootInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of instances to terminate.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'RegisterImage' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RegisterImageResult',
            'responseType' => 'model',
            'summary' => 'The RegisterImage operation registers an AMI with Amazon EC2. Images must be registered before they can be launched. For more information, see RunInstances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RegisterImage',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageLocation' => array(
                    'description' => 'The full path to your AMI manifest in Amazon S3 storage.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Name' => array(
                    'description' => 'The name to give the new Amazon Machine Image.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'The description describing the new AMI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Architecture' => array(
                    'description' => 'The architecture of the image. Valid Values: i386, x86_64',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'KernelId' => array(
                    'description' => 'The optional ID of a specific kernel to register with the new AMI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RamdiskId' => array(
                    'description' => 'The optional ID of a specific ramdisk to register with the new AMI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RootDeviceName' => array(
                    'description' => 'The root device name (e.g., /dev/sda1).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'The block device mappings for the new AMI, which specify how different block devices (ex: EBS volumes and ephemeral drives) will be exposed on instances launched from the new image.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BlockDeviceMapping',
                    'items' => array(
                        'name' => 'BlockDeviceMapping',
                        'description' => 'The BlockDeviceMappingItemType data type.',
                        'type' => 'object',
                        'properties' => array(
                            'VirtualName' => array(
                                'description' => 'Specifies the virtual device name.',
                                'type' => 'string',
                            ),
                            'DeviceName' => array(
                                'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                'type' => 'string',
                            ),
                            'Ebs' => array(
                                'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                'type' => 'object',
                                'properties' => array(
                                    'SnapshotId' => array(
                                        'description' => 'The ID of the snapshot from which the volume will be created.',
                                        'type' => 'string',
                                    ),
                                    'VolumeSize' => array(
                                        'description' => 'The size of the volume, in gigabytes.',
                                        'type' => 'numeric',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'format' => 'boolean-string',
                                    ),
                                    'VolumeType' => array(
                                        'type' => 'string',
                                        'enum' => array(
                                            'standard',
                                            'io1',
                                        ),
                                    ),
                                    'Iops' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'NoDevice' => array(
                                'description' => 'Specifies the device name to suppress during instance launch.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ReleaseAddress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The ReleaseAddress operation releases an elastic IP address associated with your account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReleaseAddress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'PublicIp' => array(
                    'description' => 'The elastic IP address that you are releasing from your account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllocationId' => array(
                    'description' => 'The allocation ID that AWS provided when you allocated the address for use with Amazon VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ReplaceNetworkAclAssociation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReplaceNetworkAclAssociationResult',
            'responseType' => 'model',
            'summary' => 'Changes which network ACL a subnet is associated with. By default when you create a subnet, it\'s automatically associated with the default network ACL. For more information about network ACLs, go to Network ACLs in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReplaceNetworkAclAssociation',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AssociationId' => array(
                    'required' => true,
                    'description' => 'The ID representing the current association between the original network ACL and the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NetworkAclId' => array(
                    'required' => true,
                    'description' => 'The ID of the new ACL to associate with the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ReplaceNetworkAclEntry' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Replaces an entry (i.e., rule) in a network ACL. For more information about network ACLs, go to Network ACLs in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReplaceNetworkAclEntry',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkAclId' => array(
                    'required' => true,
                    'description' => 'ID of the ACL where the entry will be replaced.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RuleNumber' => array(
                    'required' => true,
                    'description' => 'Rule number of the entry to replace.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Protocol' => array(
                    'required' => true,
                    'description' => 'IP protocol the rule applies to. Valid Values: tcp, udp, icmp or an IP protocol number.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RuleAction' => array(
                    'required' => true,
                    'description' => 'Whether to allow or deny traffic that matches the rule.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'allow',
                        'deny',
                    ),
                ),
                'Egress' => array(
                    'required' => true,
                    'description' => 'Whether this rule applies to egress traffic from the subnet (true) or ingress traffic (false).',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'CidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR range to allow or deny, in CIDR notation (e.g., 172.16.0.0/24).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IcmpTypeCode' => array(
                    'description' => 'ICMP values.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'sentAs' => 'Icmp',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'For the ICMP protocol, the ICMP type. A value of -1 is a wildcard meaning all types. Required if specifying icmp for the protocol.',
                            'type' => 'numeric',
                        ),
                        'Code' => array(
                            'description' => 'For the ICMP protocol, the ICMP code. A value of -1 is a wildcard meaning all codes. Required if specifying icmp for the protocol.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'PortRange' => array(
                    'description' => 'Port ranges.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'From' => array(
                            'description' => 'The first port in the range. Required if specifying tcp or udp for the protocol.',
                            'type' => 'numeric',
                        ),
                        'To' => array(
                            'description' => 'The last port in the range. Required if specifying tcp or udp for the protocol.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
        ),
        'ReplaceRoute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Replaces an existing route within a route table in a VPC. For more information about route tables, go to Route Tables in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReplaceRoute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the route table where the route will be replaced.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationCidrBlock' => array(
                    'required' => true,
                    'description' => 'The CIDR address block used for the destination match. For example: 0.0.0.0/0. The value you provide must match the CIDR of an existing route in the table.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GatewayId' => array(
                    'description' => 'The ID of a VPN or Internet gateway attached to your VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceId' => array(
                    'description' => 'The ID of a NAT instance in your VPC.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NetworkInterfaceId' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ReplaceRouteTableAssociation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReplaceRouteTableAssociationResult',
            'responseType' => 'model',
            'summary' => 'Changes the route table associated with a given subnet in a VPC. After you execute this action, the subnet uses the routes in the new route table it\'s associated with. For more information about route tables, go to Route Tables in the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReplaceRouteTableAssociation',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'AssociationId' => array(
                    'required' => true,
                    'description' => 'The ID representing the current association between the original route table and the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RouteTableId' => array(
                    'required' => true,
                    'description' => 'The ID of the new route table to associate with the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ReportInstanceStatus' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ReportInstanceStatus',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'Instances' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
                'Status' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'StartTime' => array(
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'ReasonCodes' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ReasonCode',
                    'items' => array(
                        'name' => 'ReasonCode',
                        'type' => 'string',
                    ),
                ),
                'Description' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'RequestSpotInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RequestSpotInstancesResult',
            'responseType' => 'model',
            'summary' => 'Creates a Spot Instance request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RequestSpotInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SpotPrice' => array(
                    'required' => true,
                    'description' => 'Specifies the maximum hourly price for any Spot Instance launched to fulfill the request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceCount' => array(
                    'description' => 'Specifies the maximum number of Spot Instances to launch.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Type' => array(
                    'description' => 'Specifies the Spot Instance type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'one-time',
                        'persistent',
                    ),
                ),
                'ValidFrom' => array(
                    'description' => 'Defines the start date of the request.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'ValidUntil' => array(
                    'description' => 'End date of the request.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'LaunchGroup' => array(
                    'description' => 'Specifies the instance launch group. Launch groups are Spot Instances that launch and terminate together.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZoneGroup' => array(
                    'description' => 'Specifies the Availability Zone group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LaunchSpecification' => array(
                    'description' => 'Specifies additional launch instance information.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'ImageId' => array(
                            'description' => 'The AMI ID.',
                            'type' => 'string',
                        ),
                        'KeyName' => array(
                            'description' => 'The name of the key pair.',
                            'type' => 'string',
                        ),
                        'UserData' => array(
                            'description' => 'Optional data, specific to a user\'s application, to provide in the launch request. All instances that collectively comprise the launch request have access to this data. User data is never returned through API responses.',
                            'type' => 'string',
                        ),
                        'InstanceType' => array(
                            'description' => 'Specifies the instance type.',
                            'type' => 'string',
                            'enum' => array(
                                't1.micro',
                                'm1.small',
                                'm1.medium',
                                'm1.large',
                                'm1.xlarge',
                                'm2.xlarge',
                                'm2.2xlarge',
                                'm2.4xlarge',
                                'm3.xlarge',
                                'm3.2xlarge',
                                'c1.medium',
                                'c1.xlarge',
                                'hi1.4xlarge',
                                'hs1.8xlarge',
                                'cc1.4xlarge',
                                'cc2.8xlarge',
                                'cg1.4xlarge',
                            ),
                        ),
                        'Placement' => array(
                            'description' => 'Defines a placement item.',
                            'type' => 'object',
                            'properties' => array(
                                'AvailabilityZone' => array(
                                    'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                    'type' => 'string',
                                ),
                                'GroupName' => array(
                                    'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'KernelId' => array(
                            'description' => 'Specifies the ID of the kernel to select.',
                            'type' => 'string',
                        ),
                        'RamdiskId' => array(
                            'description' => 'Specifies the ID of the RAM disk to select. Some kernels require additional drivers at launch. Check the kernel requirements for information on whether or not you need to specify a RAM disk and search for the kernel ID.',
                            'type' => 'string',
                        ),
                        'BlockDeviceMappings' => array(
                            'description' => 'Specifies how block devices are exposed to the instance. Each mapping is made up of a virtualName and a deviceName.',
                            'type' => 'array',
                            'sentAs' => 'BlockDeviceMapping',
                            'items' => array(
                                'name' => 'BlockDeviceMapping',
                                'description' => 'The BlockDeviceMappingItemType data type.',
                                'type' => 'object',
                                'properties' => array(
                                    'VirtualName' => array(
                                        'description' => 'Specifies the virtual device name.',
                                        'type' => 'string',
                                    ),
                                    'DeviceName' => array(
                                        'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                        'type' => 'string',
                                    ),
                                    'Ebs' => array(
                                        'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'SnapshotId' => array(
                                                'description' => 'The ID of the snapshot from which the volume will be created.',
                                                'type' => 'string',
                                            ),
                                            'VolumeSize' => array(
                                                'description' => 'The size of the volume, in gigabytes.',
                                                'type' => 'numeric',
                                            ),
                                            'DeleteOnTermination' => array(
                                                'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                            'VolumeType' => array(
                                                'type' => 'string',
                                                'enum' => array(
                                                    'standard',
                                                    'io1',
                                                ),
                                            ),
                                            'Iops' => array(
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'NoDevice' => array(
                                        'description' => 'Specifies the device name to suppress during instance launch.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'MonitoringEnabled' => array(
                            'description' => 'Enables monitoring for the instance.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'SubnetId' => array(
                            'description' => 'Specifies the Amazon VPC subnet ID within which to launch the instance(s) for Amazon Virtual Private Cloud.',
                            'type' => 'string',
                        ),
                        'NetworkInterfaces' => array(
                            'type' => 'array',
                            'sentAs' => 'NetworkInterface',
                            'items' => array(
                                'name' => 'NetworkInterface',
                                'type' => 'object',
                                'properties' => array(
                                    'NetworkInterfaceId' => array(
                                        'type' => 'string',
                                    ),
                                    'DeviceIndex' => array(
                                        'type' => 'numeric',
                                    ),
                                    'SubnetId' => array(
                                        'type' => 'string',
                                    ),
                                    'Description' => array(
                                        'type' => 'string',
                                    ),
                                    'PrivateIpAddress' => array(
                                        'type' => 'string',
                                    ),
                                    'Groups' => array(
                                        'type' => 'array',
                                        'sentAs' => 'SecurityGroupId',
                                        'items' => array(
                                            'name' => 'SecurityGroupId',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'DeleteOnTermination' => array(
                                        'type' => 'boolean',
                                        'format' => 'boolean-string',
                                    ),
                                    'PrivateIpAddresses' => array(
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'PrivateIpAddressSpecification',
                                            'type' => 'object',
                                            'properties' => array(
                                                'PrivateIpAddress' => array(
                                                    'required' => true,
                                                    'type' => 'string',
                                                ),
                                                'Primary' => array(
                                                    'type' => 'boolean',
                                                    'format' => 'boolean-string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'SecondaryPrivateIpAddressCount' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                        'IamInstanceProfile' => array(
                            'type' => 'object',
                            'properties' => array(
                                'Arn' => array(
                                    'type' => 'string',
                                ),
                                'Name' => array(
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'EbsOptimized' => array(
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'SecurityGroupIds' => array(
                            'type' => 'array',
                            'sentAs' => 'SecurityGroupId',
                            'items' => array(
                                'name' => 'SecurityGroupId',
                                'type' => 'string',
                            ),
                        ),
                        'SecurityGroups' => array(
                            'type' => 'array',
                            'sentAs' => 'SecurityGroup',
                            'items' => array(
                                'name' => 'SecurityGroup',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ResetImageAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The ResetImageAttribute operation resets an attribute of an AMI to its default value.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetImageAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'The ID of the AMI whose attribute is being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the attribute being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ResetInstanceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Resets an attribute of an instance to its default value.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetInstanceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the Amazon EC2 instance whose attribute is being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the attribute being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'instanceType',
                        'kernel',
                        'ramdisk',
                        'userData',
                        'disableApiTermination',
                        'instanceInitiatedShutdownBehavior',
                        'rootDeviceName',
                        'blockDeviceMapping',
                        'productCodes',
                        'sourceDestCheck',
                        'groupSet',
                        'ebsOptimized',
                    ),
                ),
            ),
        ),
        'ResetNetworkInterfaceAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetNetworkInterfaceAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceDestCheck' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ResetSnapshotAttribute' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Resets permission settings for the specified snapshot.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetSnapshotAttribute',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'SnapshotId' => array(
                    'required' => true,
                    'description' => 'The ID of the snapshot whose attribute is being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Attribute' => array(
                    'required' => true,
                    'description' => 'The name of the attribute being reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'productCodes',
                        'createVolumePermission',
                    ),
                ),
            ),
        ),
        'RevokeSecurityGroupEgress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'This action applies only to security groups in a VPC. It doesn\'t work with EC2 security groups. For information about Amazon Virtual Private Cloud and VPC security groups, go to the Amazon Virtual Private Cloud User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RevokeSecurityGroupEgress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupId' => array(
                    'required' => true,
                    'description' => 'ID of the VPC security group to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IpPermissions' => array(
                    'description' => 'List of IP permissions to authorize on the specified security group. Specifying permissions through IP permissions is the preferred way of authorizing permissions since it offers more flexibility and control.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'IpPermission',
                        'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                        'type' => 'object',
                        'properties' => array(
                            'IpProtocol' => array(
                                'description' => 'The IP protocol of this permission.',
                                'type' => 'string',
                            ),
                            'FromPort' => array(
                                'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                'type' => 'numeric',
                            ),
                            'ToPort' => array(
                                'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                'type' => 'numeric',
                            ),
                            'UserIdGroupPairs' => array(
                                'description' => 'The list of AWS user IDs and groups included in this permission.',
                                'type' => 'array',
                                'sentAs' => 'Groups',
                                'items' => array(
                                    'name' => 'Groups',
                                    'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'UserId' => array(
                                            'description' => 'The AWS user ID of an account.',
                                            'type' => 'string',
                                        ),
                                        'GroupName' => array(
                                            'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                        'GroupId' => array(
                                            'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IpRanges' => array(
                                'description' => 'The list of CIDR IP ranges included in this permission.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IpRange',
                                    'description' => 'Contains a list of CIRD IP ranges.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'CidrIp' => array(
                                            'description' => 'The list of CIDR IP ranges.',
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
        'RevokeSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'The RevokeSecurityGroupIngress operation revokes permissions from a security group. The permissions used to revoke must be specified using the same values used to grant the permissions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RevokeSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'GroupName' => array(
                    'description' => 'Name of the standard (EC2) security group to modify. The group must belong to your account. Can be used instead of GroupID for standard (EC2) security groups.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'GroupId' => array(
                    'description' => 'ID of the standard (EC2) or VPC security group to modify. The group must belong to your account. Required for VPC security groups; can be used instead of GroupName for standard (EC2) security groups.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'IpPermissions' => array(
                    'description' => 'List of IP permissions to revoke on the specified security group. For an IP permission to be removed, it must exactly match one of the IP permissions you specify in this list. Specifying permissions through IP permissions is the preferred way of revoking permissions since it offers more flexibility and control.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'items' => array(
                        'name' => 'IpPermission',
                        'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                        'type' => 'object',
                        'properties' => array(
                            'IpProtocol' => array(
                                'description' => 'The IP protocol of this permission.',
                                'type' => 'string',
                            ),
                            'FromPort' => array(
                                'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                'type' => 'numeric',
                            ),
                            'ToPort' => array(
                                'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                'type' => 'numeric',
                            ),
                            'UserIdGroupPairs' => array(
                                'description' => 'The list of AWS user IDs and groups included in this permission.',
                                'type' => 'array',
                                'sentAs' => 'Groups',
                                'items' => array(
                                    'name' => 'Groups',
                                    'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'UserId' => array(
                                            'description' => 'The AWS user ID of an account.',
                                            'type' => 'string',
                                        ),
                                        'GroupName' => array(
                                            'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                        'GroupId' => array(
                                            'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IpRanges' => array(
                                'description' => 'The list of CIDR IP ranges included in this permission.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IpRange',
                                    'description' => 'Contains a list of CIRD IP ranges.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'CidrIp' => array(
                                            'description' => 'The list of CIDR IP ranges.',
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
        'RunInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'reservation',
            'responseType' => 'model',
            'summary' => 'The RunInstances operation launches a specified number of instances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RunInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'Unique ID of a machine image, returned by a call to DescribeImages.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MinCount' => array(
                    'required' => true,
                    'description' => 'Minimum number of instances to launch. If the value is more than Amazon EC2 can launch, no instances are launched at all.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxCount' => array(
                    'required' => true,
                    'description' => 'Maximum number of instances to launch. If the value is more than Amazon EC2 can launch, the largest possible number above minCount will be launched instead.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'KeyName' => array(
                    'description' => 'The name of the key pair.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SecurityGroups' => array(
                    'description' => 'The names of the security groups into which the instances will be launched.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroup',
                    'items' => array(
                        'name' => 'SecurityGroup',
                        'type' => 'string',
                    ),
                ),
                'SecurityGroupIds' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroupId',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'UserData' => array(
                    'description' => 'Specifies additional information to make available to the instance(s).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'InstanceType' => array(
                    'description' => 'Specifies the instance type for the launched instances.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        't1.micro',
                        'm1.small',
                        'm1.medium',
                        'm1.large',
                        'm1.xlarge',
                        'm2.xlarge',
                        'm2.2xlarge',
                        'm2.4xlarge',
                        'm3.xlarge',
                        'm3.2xlarge',
                        'c1.medium',
                        'c1.xlarge',
                        'hi1.4xlarge',
                        'hs1.8xlarge',
                        'cc1.4xlarge',
                        'cc2.8xlarge',
                        'cg1.4xlarge',
                    ),
                ),
                'Placement' => array(
                    'description' => 'Specifies the placement constraints (Availability Zones) for launching the instances.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'AvailabilityZone' => array(
                            'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                            'type' => 'string',
                        ),
                        'GroupName' => array(
                            'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                            'type' => 'string',
                        ),
                        'Tenancy' => array(
                            'description' => 'The allowed tenancy of instances launched into the VPC. A value of default means instances can be launched with any tenancy; a value of dedicated means all instances launched into the VPC will be launched as dedicated tenancy regardless of the tenancy assigned to the instance at launch.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'KernelId' => array(
                    'description' => 'The ID of the kernel with which to launch the instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RamdiskId' => array(
                    'description' => 'The ID of the RAM disk with which to launch the instance. Some kernels require additional drivers at launch. Check the kernel requirements for information on whether you need to specify a RAM disk. To find kernel requirements, go to the Resource Center and search for the kernel ID.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'Specifies how block devices are exposed to the instance. Each mapping is made up of a virtualName and a deviceName.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BlockDeviceMapping',
                    'items' => array(
                        'name' => 'BlockDeviceMapping',
                        'description' => 'The BlockDeviceMappingItemType data type.',
                        'type' => 'object',
                        'properties' => array(
                            'VirtualName' => array(
                                'description' => 'Specifies the virtual device name.',
                                'type' => 'string',
                            ),
                            'DeviceName' => array(
                                'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                'type' => 'string',
                            ),
                            'Ebs' => array(
                                'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                'type' => 'object',
                                'properties' => array(
                                    'SnapshotId' => array(
                                        'description' => 'The ID of the snapshot from which the volume will be created.',
                                        'type' => 'string',
                                    ),
                                    'VolumeSize' => array(
                                        'description' => 'The size of the volume, in gigabytes.',
                                        'type' => 'numeric',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'format' => 'boolean-string',
                                    ),
                                    'VolumeType' => array(
                                        'type' => 'string',
                                        'enum' => array(
                                            'standard',
                                            'io1',
                                        ),
                                    ),
                                    'Iops' => array(
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'NoDevice' => array(
                                'description' => 'Specifies the device name to suppress during instance launch.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Monitoring' => array(
                    'description' => 'Enables monitoring for the instance.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'SubnetId' => array(
                    'description' => 'Specifies the subnet ID within which to launch the instance(s) for Amazon Virtual Private Cloud.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DisableApiTermination' => array(
                    'description' => 'Specifies whether the instance can be terminated using the APIs. You must modify this attribute before you can terminate any "locked" instances from the APIs.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'InstanceInitiatedShutdownBehavior' => array(
                    'description' => 'Specifies whether the instance\'s Amazon EBS volumes are stopped or terminated when the instance is shut down.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'License' => array(
                    'description' => 'Specifies active licenses in use and attached to an Amazon EC2 instance.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Pool' => array(
                            'description' => 'The license pool from which to take a license when starting Amazon EC2 instances in the associated RunInstances request.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'PrivateIpAddress' => array(
                    'description' => 'If you\'re using Amazon Virtual Private Cloud, you can optionally use this parameter to assign the instance a specific available IP address from the subnet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClientToken' => array(
                    'description' => 'Unique, case-sensitive identifier you provide to ensure idempotency of the request. For more information, go to How to Ensure Idempotency in the Amazon Elastic Compute Cloud User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AdditionalInfo' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NetworkInterfaces' => array(
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'NetworkInterface',
                    'items' => array(
                        'name' => 'NetworkInterface',
                        'type' => 'object',
                        'properties' => array(
                            'NetworkInterfaceId' => array(
                                'type' => 'string',
                            ),
                            'DeviceIndex' => array(
                                'type' => 'numeric',
                            ),
                            'SubnetId' => array(
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'type' => 'string',
                            ),
                            'PrivateIpAddress' => array(
                                'type' => 'string',
                            ),
                            'Groups' => array(
                                'type' => 'array',
                                'sentAs' => 'SecurityGroupId',
                                'items' => array(
                                    'name' => 'SecurityGroupId',
                                    'type' => 'string',
                                ),
                            ),
                            'DeleteOnTermination' => array(
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                            'PrivateIpAddresses' => array(
                                'type' => 'array',
                                'sentAs' => 'PrivateIpAddressesSet',
                                'items' => array(
                                    'name' => 'PrivateIpAddressesSet',
                                    'type' => 'object',
                                    'properties' => array(
                                        'PrivateIpAddress' => array(
                                            'required' => true,
                                            'type' => 'string',
                                        ),
                                        'Primary' => array(
                                            'type' => 'boolean',
                                            'format' => 'boolean-string',
                                        ),
                                    ),
                                ),
                            ),
                            'SecondaryPrivateIpAddressCount' => array(
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'IamInstanceProfile' => array(
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Arn' => array(
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'EbsOptimized' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'StartInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'StartInstancesResult',
            'responseType' => 'model',
            'summary' => 'Starts an instance that uses an Amazon EBS volume as its root device. Instances that use Amazon EBS volumes as their root devices can be quickly stopped and started. When an instance is stopped, the compute resources are released and you are not billed for hourly instance usage. However, your root partition Amazon EBS volume remains, continues to persist your data, and you are charged for Amazon EBS volume usage. You can restart your instance at any time.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'StartInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of Amazon EC2 instances to start.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
                'AdditionalInfo' => array(
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'StopInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'StopInstancesResult',
            'responseType' => 'model',
            'summary' => 'Stops an instance that uses an Amazon EBS volume as its root device. Instances that use Amazon EBS volumes as their root devices can be quickly stopped and started. When an instance is stopped, the compute resources are released and you are not billed for hourly instance usage. However, your root partition Amazon EBS volume remains, continues to persist your data, and you are charged for Amazon EBS volume usage. You can restart your instance at any time.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'StopInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of Amazon EC2 instances to stop.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
                'Force' => array(
                    'description' => 'Forces the instance to stop. The instance will not have an opportunity to flush file system caches nor file system meta data. If you use this option, you must perform file system check and repair procedures. This option is not recommended for Windows instances.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'TerminateInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'TerminateInstancesResult',
            'responseType' => 'model',
            'summary' => 'The TerminateInstances operation shuts down one or more instances. This operation is idempotent; if you terminate an instance more than once, each call will succeed.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'TerminateInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of instances to terminate.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'UnassignPrivateIpAddresses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UnassignPrivateIpAddresses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'NetworkInterfaceId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PrivateIpAddresses' => array(
                    'required' => true,
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PrivateIpAddress',
                    'items' => array(
                        'name' => 'PrivateIpAddress',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
        'UnmonitorInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'UnmonitorInstancesResult',
            'responseType' => 'model',
            'summary' => 'Disables monitoring for a running instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UnmonitorInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-02-01',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'The list of Amazon EC2 instances on which to disable monitoring.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceId',
                    'items' => array(
                        'name' => 'InstanceId',
                        'type' => 'string',
                    ),
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'AllocateAddressResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PublicIp' => array(
                    'description' => 'IP address for use with your account.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'publicIp',
                ),
                'Domain' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'domain',
                ),
                'AllocationId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'allocationId',
                ),
            ),
        ),
        'AssociateAddressResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AssociationId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'associationId',
                ),
            ),
        ),
        'AssociateRouteTableResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AssociationId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'associationId',
                ),
            ),
        ),
        'AttachNetworkInterfaceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AttachmentId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'attachmentId',
                ),
            ),
        ),
        'attachment' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'volumeId',
                ),
                'InstanceId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'instanceId',
                ),
                'Device' => array(
                    'description' => 'How the device is exposed to the instance (e.g., /dev/sdh).',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'device',
                ),
                'State' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'status',
                ),
                'AttachTime' => array(
                    'description' => 'Timestamp when this attachment initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'attachTime',
                ),
                'DeleteOnTermination' => array(
                    'description' => '` Whether this volume will be deleted or not when the associated instance is terminated.',
                    'type' => 'boolean',
                    'location' => 'xml',
                    'sentAs' => 'deleteOnTermination',
                ),
            ),
        ),
        'AttachVpnGatewayResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpcAttachement' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'attachment',
                    'properties' => array(
                        'VpcId' => array(
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'State' => array(
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                    ),
                ),
            ),
        ),
        'BundleInstanceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'BundleTask' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'bundleInstanceTask',
                    'properties' => array(
                        'InstanceId' => array(
                            'description' => 'Instance associated with this bundle task.',
                            'type' => 'string',
                            'sentAs' => 'instanceId',
                        ),
                        'BundleId' => array(
                            'description' => 'Unique identifier for this task.',
                            'type' => 'string',
                            'sentAs' => 'bundleId',
                        ),
                        'State' => array(
                            'description' => 'The state of this task.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'StartTime' => array(
                            'description' => 'The time this task started.',
                            'type' => 'string',
                            'sentAs' => 'startTime',
                        ),
                        'UpdateTime' => array(
                            'description' => 'The time of the most recent update for the task.',
                            'type' => 'string',
                            'sentAs' => 'updateTime',
                        ),
                        'Storage' => array(
                            'description' => 'Amazon S3 storage locations.',
                            'type' => 'object',
                            'sentAs' => 'storage',
                            'properties' => array(
                                'S3' => array(
                                    'description' => 'The details of S3 storage for bundling a Windows instance.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Bucket' => array(
                                            'description' => 'The bucket in which to store the AMI. You can specify a bucket that you already own or a new bucket that Amazon EC2 creates on your behalf.',
                                            'type' => 'string',
                                            'sentAs' => 'bucket',
                                        ),
                                        'Prefix' => array(
                                            'description' => 'The prefix to use when storing the AMI in S3.',
                                            'type' => 'string',
                                            'sentAs' => 'prefix',
                                        ),
                                        'AWSAccessKeyId' => array(
                                            'description' => 'The Access Key ID of the owner of the Amazon S3 bucket.',
                                            'type' => 'string',
                                        ),
                                        'UploadPolicy' => array(
                                            'description' => 'A Base64-encoded Amazon S3 upload policy that gives Amazon EC2 permission to upload items into Amazon S3 on the user\'s behalf.',
                                            'type' => 'string',
                                            'sentAs' => 'uploadPolicy',
                                        ),
                                        'UploadPolicySignature' => array(
                                            'description' => 'The signature of the Base64 encoded JSON document.',
                                            'type' => 'string',
                                            'sentAs' => 'uploadPolicySignature',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Progress' => array(
                            'description' => 'The level of task completion, in percent (e.g., 20%).',
                            'type' => 'string',
                            'sentAs' => 'progress',
                        ),
                        'BundleTaskError' => array(
                            'description' => 'If the task fails, a description of the error.',
                            'type' => 'object',
                            'sentAs' => 'error',
                            'properties' => array(
                                'Code' => array(
                                    'description' => 'Error code.',
                                    'type' => 'string',
                                    'sentAs' => 'code',
                                ),
                                'Message' => array(
                                    'description' => 'Error message.',
                                    'type' => 'string',
                                    'sentAs' => 'message',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CancelBundleTaskResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'BundleTask' => array(
                    'description' => 'The canceled bundle task.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'bundleInstanceTask',
                    'properties' => array(
                        'InstanceId' => array(
                            'description' => 'Instance associated with this bundle task.',
                            'type' => 'string',
                            'sentAs' => 'instanceId',
                        ),
                        'BundleId' => array(
                            'description' => 'Unique identifier for this task.',
                            'type' => 'string',
                            'sentAs' => 'bundleId',
                        ),
                        'State' => array(
                            'description' => 'The state of this task.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'StartTime' => array(
                            'description' => 'The time this task started.',
                            'type' => 'string',
                            'sentAs' => 'startTime',
                        ),
                        'UpdateTime' => array(
                            'description' => 'The time of the most recent update for the task.',
                            'type' => 'string',
                            'sentAs' => 'updateTime',
                        ),
                        'Storage' => array(
                            'description' => 'Amazon S3 storage locations.',
                            'type' => 'object',
                            'sentAs' => 'storage',
                            'properties' => array(
                                'S3' => array(
                                    'description' => 'The details of S3 storage for bundling a Windows instance.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Bucket' => array(
                                            'description' => 'The bucket in which to store the AMI. You can specify a bucket that you already own or a new bucket that Amazon EC2 creates on your behalf.',
                                            'type' => 'string',
                                            'sentAs' => 'bucket',
                                        ),
                                        'Prefix' => array(
                                            'description' => 'The prefix to use when storing the AMI in S3.',
                                            'type' => 'string',
                                            'sentAs' => 'prefix',
                                        ),
                                        'AWSAccessKeyId' => array(
                                            'description' => 'The Access Key ID of the owner of the Amazon S3 bucket.',
                                            'type' => 'string',
                                        ),
                                        'UploadPolicy' => array(
                                            'description' => 'A Base64-encoded Amazon S3 upload policy that gives Amazon EC2 permission to upload items into Amazon S3 on the user\'s behalf.',
                                            'type' => 'string',
                                            'sentAs' => 'uploadPolicy',
                                        ),
                                        'UploadPolicySignature' => array(
                                            'description' => 'The signature of the Base64 encoded JSON document.',
                                            'type' => 'string',
                                            'sentAs' => 'uploadPolicySignature',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Progress' => array(
                            'description' => 'The level of task completion, in percent (e.g., 20%).',
                            'type' => 'string',
                            'sentAs' => 'progress',
                        ),
                        'BundleTaskError' => array(
                            'description' => 'If the task fails, a description of the error.',
                            'type' => 'object',
                            'sentAs' => 'error',
                            'properties' => array(
                                'Code' => array(
                                    'description' => 'Error code.',
                                    'type' => 'string',
                                    'sentAs' => 'code',
                                ),
                                'Message' => array(
                                    'description' => 'Error message.',
                                    'type' => 'string',
                                    'sentAs' => 'message',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CancelReservedInstancesListingResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstancesListings' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesListingsSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservedInstancesListingId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesListingId',
                            ),
                            'ReservedInstancesId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesId',
                            ),
                            'CreateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'createDate',
                            ),
                            'UpdateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'updateDate',
                            ),
                            'Status' => array(
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'StatusMessage' => array(
                                'type' => 'string',
                                'sentAs' => 'statusMessage',
                            ),
                            'InstanceCounts' => array(
                                'type' => 'array',
                                'sentAs' => 'instanceCounts',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                        'InstanceCount' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'instanceCount',
                                        ),
                                    ),
                                ),
                            ),
                            'PriceSchedules' => array(
                                'type' => 'array',
                                'sentAs' => 'priceSchedules',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Term' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'term',
                                        ),
                                        'Price' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'price',
                                        ),
                                        'CurrencyCode' => array(
                                            'type' => 'string',
                                            'sentAs' => 'currencyCode',
                                        ),
                                        'Active' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'active',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'ClientToken' => array(
                                'type' => 'string',
                                'sentAs' => 'clientToken',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CancelSpotInstanceRequestsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CancelledSpotInstanceRequests' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'spotInstanceRequestSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'SpotInstanceRequestId' => array(
                                'type' => 'string',
                                'sentAs' => 'spotInstanceRequestId',
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ConfirmProductInstanceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OwnerId' => array(
                    'description' => 'The instance owner\'s account ID. Only present if the product code is attached to the instance.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'ownerId',
                ),
            ),
        ),
        'CopyImageResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ImageId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'imageId',
                ),
            ),
        ),
        'CopySnapshotResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SnapshotId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'snapshotId',
                ),
            ),
        ),
        'CreateCustomerGatewayResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CustomerGateway' => array(
                    'description' => 'Information about the customer gateway.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'customerGateway',
                    'properties' => array(
                        'CustomerGatewayId' => array(
                            'description' => 'Specifies the ID of the customer gateway.',
                            'type' => 'string',
                            'sentAs' => 'customerGatewayId',
                        ),
                        'State' => array(
                            'description' => 'Describes the current state of the customer gateway. Valid values are pending, available, deleting, and deleted.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'Type' => array(
                            'description' => 'Specifies the type of VPN connection the customer gateway supports.',
                            'type' => 'string',
                            'sentAs' => 'type',
                        ),
                        'IpAddress' => array(
                            'description' => 'Contains the Internet-routable IP address of the customer gateway\'s outside interface.',
                            'type' => 'string',
                            'sentAs' => 'ipAddress',
                        ),
                        'BgpAsn' => array(
                            'description' => 'Specifies the customer gateway\'s Border Gateway Protocol (BGP) Autonomous System Number (ASN).',
                            'type' => 'string',
                            'sentAs' => 'bgpAsn',
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the CustomerGateway.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateDhcpOptionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DhcpOptions' => array(
                    'description' => 'A set of one or more DHCP options.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'dhcpOptions',
                    'properties' => array(
                        'DhcpOptionsId' => array(
                            'description' => 'Specifies the ID of the set of DHCP options.',
                            'type' => 'string',
                            'sentAs' => 'dhcpOptionsId',
                        ),
                        'DhcpConfigurations' => array(
                            'description' => 'Contains information about the set of DHCP options.',
                            'type' => 'array',
                            'sentAs' => 'dhcpConfigurationSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'The DhcpConfiguration data type',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'Contains the name of a DHCP option.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Values' => array(
                                        'description' => 'Contains a set of values for a DHCP option.',
                                        'type' => 'array',
                                        'sentAs' => 'valueSet',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'string',
                                            'sentAs' => 'item',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the DhcpOptions.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateImageResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ImageId' => array(
                    'description' => 'The ID of the new AMI.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'imageId',
                ),
            ),
        ),
        'CreateInstanceExportTaskResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ExportTask' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'exportTask',
                    'properties' => array(
                        'ExportTaskId' => array(
                            'type' => 'string',
                            'sentAs' => 'exportTaskId',
                        ),
                        'Description' => array(
                            'type' => 'string',
                            'sentAs' => 'description',
                        ),
                        'State' => array(
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'StatusMessage' => array(
                            'type' => 'string',
                            'sentAs' => 'statusMessage',
                        ),
                        'InstanceExportDetails' => array(
                            'type' => 'object',
                            'sentAs' => 'instanceExport',
                            'properties' => array(
                                'InstanceId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'instanceId',
                                ),
                                'TargetEnvironment' => array(
                                    'type' => 'string',
                                    'sentAs' => 'targetEnvironment',
                                ),
                            ),
                        ),
                        'ExportToS3Task' => array(
                            'type' => 'object',
                            'sentAs' => 'exportToS3',
                            'properties' => array(
                                'DiskImageFormat' => array(
                                    'type' => 'string',
                                    'sentAs' => 'diskImageFormat',
                                ),
                                'ContainerFormat' => array(
                                    'type' => 'string',
                                    'sentAs' => 'containerFormat',
                                ),
                                'S3Bucket' => array(
                                    'type' => 'string',
                                    'sentAs' => 's3Bucket',
                                ),
                                'S3Key' => array(
                                    'type' => 'string',
                                    'sentAs' => 's3Key',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateInternetGatewayResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InternetGateway' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'internetGateway',
                    'properties' => array(
                        'InternetGatewayId' => array(
                            'type' => 'string',
                            'sentAs' => 'internetGatewayId',
                        ),
                        'Attachments' => array(
                            'type' => 'array',
                            'sentAs' => 'attachmentSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'VpcId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'vpcId',
                                    ),
                                    'State' => array(
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                        'Tags' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateKeyPairResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'KeyPair' => array(
                    'description' => 'The newly created EC2 key pair.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'keyPair',
                    'properties' => array(
                        'KeyName' => array(
                            'description' => 'The name of the key pair.',
                            'type' => 'string',
                            'sentAs' => 'keyName',
                        ),
                        'KeyFingerprint' => array(
                            'description' => 'The SHA-1 digest of the DER encoded private key.',
                            'type' => 'string',
                            'sentAs' => 'keyFingerprint',
                        ),
                        'KeyMaterial' => array(
                            'description' => 'The unencrypted PEM encoded RSA private key.',
                            'type' => 'string',
                            'sentAs' => 'keyMaterial',
                        ),
                    ),
                ),
            ),
        ),
        'CreateNetworkAclResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NetworkAcl' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'networkAcl',
                    'properties' => array(
                        'NetworkAclId' => array(
                            'type' => 'string',
                            'sentAs' => 'networkAclId',
                        ),
                        'VpcId' => array(
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'IsDefault' => array(
                            'type' => 'boolean',
                            'sentAs' => 'default',
                        ),
                        'Entries' => array(
                            'type' => 'array',
                            'sentAs' => 'entrySet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'RuleNumber' => array(
                                        'type' => 'numeric',
                                        'sentAs' => 'ruleNumber',
                                    ),
                                    'Protocol' => array(
                                        'type' => 'string',
                                        'sentAs' => 'protocol',
                                    ),
                                    'RuleAction' => array(
                                        'type' => 'string',
                                        'sentAs' => 'ruleAction',
                                    ),
                                    'Egress' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'egress',
                                    ),
                                    'CidrBlock' => array(
                                        'type' => 'string',
                                        'sentAs' => 'cidrBlock',
                                    ),
                                    'IcmpTypeCode' => array(
                                        'type' => 'object',
                                        'sentAs' => 'icmpTypeCode',
                                        'properties' => array(
                                            'Type' => array(
                                                'description' => 'For the ICMP protocol, the ICMP type. A value of -1 is a wildcard meaning all types. Required if specifying icmp for the protocol.',
                                                'type' => 'numeric',
                                                'sentAs' => 'type',
                                            ),
                                            'Code' => array(
                                                'description' => 'For the ICMP protocol, the ICMP code. A value of -1 is a wildcard meaning all codes. Required if specifying icmp for the protocol.',
                                                'type' => 'numeric',
                                                'sentAs' => 'code',
                                            ),
                                        ),
                                    ),
                                    'PortRange' => array(
                                        'type' => 'object',
                                        'sentAs' => 'portRange',
                                        'properties' => array(
                                            'From' => array(
                                                'description' => 'The first port in the range. Required if specifying tcp or udp for the protocol.',
                                                'type' => 'numeric',
                                                'sentAs' => 'from',
                                            ),
                                            'To' => array(
                                                'description' => 'The last port in the range. Required if specifying tcp or udp for the protocol.',
                                                'type' => 'numeric',
                                                'sentAs' => 'to',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Associations' => array(
                            'type' => 'array',
                            'sentAs' => 'associationSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'NetworkAclAssociationId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'networkAclAssociationId',
                                    ),
                                    'NetworkAclId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'networkAclId',
                                    ),
                                    'SubnetId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'subnetId',
                                    ),
                                ),
                            ),
                        ),
                        'Tags' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateNetworkInterfaceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NetworkInterface' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'networkInterface',
                    'properties' => array(
                        'NetworkInterfaceId' => array(
                            'type' => 'string',
                            'sentAs' => 'networkInterfaceId',
                        ),
                        'SubnetId' => array(
                            'type' => 'string',
                            'sentAs' => 'subnetId',
                        ),
                        'VpcId' => array(
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'AvailabilityZone' => array(
                            'type' => 'string',
                            'sentAs' => 'availabilityZone',
                        ),
                        'Description' => array(
                            'type' => 'string',
                            'sentAs' => 'description',
                        ),
                        'OwnerId' => array(
                            'type' => 'string',
                            'sentAs' => 'ownerId',
                        ),
                        'RequesterId' => array(
                            'type' => 'string',
                            'sentAs' => 'requesterId',
                        ),
                        'RequesterManaged' => array(
                            'type' => 'boolean',
                            'sentAs' => 'requesterManaged',
                        ),
                        'Status' => array(
                            'type' => 'string',
                            'sentAs' => 'status',
                        ),
                        'MacAddress' => array(
                            'type' => 'string',
                            'sentAs' => 'macAddress',
                        ),
                        'PrivateIpAddress' => array(
                            'type' => 'string',
                            'sentAs' => 'privateIpAddress',
                        ),
                        'PrivateDnsName' => array(
                            'type' => 'string',
                            'sentAs' => 'privateDnsName',
                        ),
                        'SourceDestCheck' => array(
                            'type' => 'boolean',
                            'sentAs' => 'sourceDestCheck',
                        ),
                        'Groups' => array(
                            'type' => 'array',
                            'sentAs' => 'groupSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'GroupName' => array(
                                        'type' => 'string',
                                        'sentAs' => 'groupName',
                                    ),
                                    'GroupId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'groupId',
                                    ),
                                ),
                            ),
                        ),
                        'Attachment' => array(
                            'type' => 'object',
                            'sentAs' => 'attachment',
                            'properties' => array(
                                'AttachmentId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'attachmentId',
                                ),
                                'InstanceId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'instanceId',
                                ),
                                'InstanceOwnerId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'instanceOwnerId',
                                ),
                                'DeviceIndex' => array(
                                    'type' => 'numeric',
                                    'sentAs' => 'deviceIndex',
                                ),
                                'Status' => array(
                                    'type' => 'string',
                                    'sentAs' => 'status',
                                ),
                                'AttachTime' => array(
                                    'type' => 'string',
                                    'sentAs' => 'attachTime',
                                ),
                                'DeleteOnTermination' => array(
                                    'type' => 'boolean',
                                    'sentAs' => 'deleteOnTermination',
                                ),
                            ),
                        ),
                        'Association' => array(
                            'type' => 'object',
                            'sentAs' => 'association',
                            'properties' => array(
                                'PublicIp' => array(
                                    'type' => 'string',
                                    'sentAs' => 'publicIp',
                                ),
                                'IpOwnerId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'ipOwnerId',
                                ),
                                'AllocationId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'allocationId',
                                ),
                                'AssociationId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'associationId',
                                ),
                            ),
                        ),
                        'TagSet' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                        'PrivateIpAddresses' => array(
                            'type' => 'array',
                            'sentAs' => 'privateIpAddressesSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'PrivateIpAddress' => array(
                                        'type' => 'string',
                                        'sentAs' => 'privateIpAddress',
                                    ),
                                    'PrivateDnsName' => array(
                                        'type' => 'string',
                                        'sentAs' => 'privateDnsName',
                                    ),
                                    'Primary' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'primary',
                                    ),
                                    'Association' => array(
                                        'type' => 'object',
                                        'sentAs' => 'association',
                                        'properties' => array(
                                            'PublicIp' => array(
                                                'type' => 'string',
                                                'sentAs' => 'publicIp',
                                            ),
                                            'IpOwnerId' => array(
                                                'type' => 'string',
                                                'sentAs' => 'ipOwnerId',
                                            ),
                                            'AllocationId' => array(
                                                'type' => 'string',
                                                'sentAs' => 'allocationId',
                                            ),
                                            'AssociationId' => array(
                                                'type' => 'string',
                                                'sentAs' => 'associationId',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateReservedInstancesListingResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstancesListings' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesListingsSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservedInstancesListingId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesListingId',
                            ),
                            'ReservedInstancesId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesId',
                            ),
                            'CreateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'createDate',
                            ),
                            'UpdateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'updateDate',
                            ),
                            'Status' => array(
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'StatusMessage' => array(
                                'type' => 'string',
                                'sentAs' => 'statusMessage',
                            ),
                            'InstanceCounts' => array(
                                'type' => 'array',
                                'sentAs' => 'instanceCounts',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                        'InstanceCount' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'instanceCount',
                                        ),
                                    ),
                                ),
                            ),
                            'PriceSchedules' => array(
                                'type' => 'array',
                                'sentAs' => 'priceSchedules',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Term' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'term',
                                        ),
                                        'Price' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'price',
                                        ),
                                        'CurrencyCode' => array(
                                            'type' => 'string',
                                            'sentAs' => 'currencyCode',
                                        ),
                                        'Active' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'active',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'ClientToken' => array(
                                'type' => 'string',
                                'sentAs' => 'clientToken',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateRouteTableResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RouteTable' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'routeTable',
                    'properties' => array(
                        'RouteTableId' => array(
                            'type' => 'string',
                            'sentAs' => 'routeTableId',
                        ),
                        'VpcId' => array(
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'Routes' => array(
                            'type' => 'array',
                            'sentAs' => 'routeSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'DestinationCidrBlock' => array(
                                        'type' => 'string',
                                        'sentAs' => 'destinationCidrBlock',
                                    ),
                                    'GatewayId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'gatewayId',
                                    ),
                                    'InstanceId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceId',
                                    ),
                                    'InstanceOwnerId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceOwnerId',
                                    ),
                                    'NetworkInterfaceId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'networkInterfaceId',
                                    ),
                                    'State' => array(
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                        'Associations' => array(
                            'type' => 'array',
                            'sentAs' => 'associationSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'RouteTableAssociationId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'routeTableAssociationId',
                                    ),
                                    'RouteTableId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'routeTableId',
                                    ),
                                    'SubnetId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'subnetId',
                                    ),
                                    'Main' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'main',
                                    ),
                                ),
                            ),
                        ),
                        'Tags' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                        'PropagatingVgws' => array(
                            'type' => 'array',
                            'sentAs' => 'propagatingVgwSet',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'GatewayId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'gatewayId',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateSecurityGroupResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'GroupId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'groupId',
                ),
            ),
        ),
        'snapshot' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SnapshotId' => array(
                    'description' => 'The unique ID of this snapshot.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'snapshotId',
                ),
                'VolumeId' => array(
                    'description' => 'The ID of the volume from which this snapshot was created.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'volumeId',
                ),
                'State' => array(
                    'description' => 'Snapshot state (e.g., pending, completed, or error).',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'status',
                ),
                'StartTime' => array(
                    'description' => 'Time stamp when the snapshot was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'startTime',
                ),
                'Progress' => array(
                    'description' => 'The progress of the snapshot, in percentage.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'progress',
                ),
                'OwnerId' => array(
                    'description' => 'AWS Access Key ID of the user who owns the snapshot.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'ownerId',
                ),
                'Description' => array(
                    'description' => 'Description of the snapshot.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'description',
                ),
                'VolumeSize' => array(
                    'description' => 'The size of the volume, in gigabytes.',
                    'type' => 'numeric',
                    'location' => 'xml',
                    'sentAs' => 'volumeSize',
                ),
                'OwnerAlias' => array(
                    'description' => 'The AWS account alias (e.g., "amazon", "redhat", "self", etc.) or AWS account ID that owns the AMI.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'ownerAlias',
                ),
                'Tags' => array(
                    'description' => 'A list of tags for the Snapshot.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'tagSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'The tag\'s key.',
                                'type' => 'string',
                                'sentAs' => 'key',
                            ),
                            'Value' => array(
                                'description' => 'The tag\'s value.',
                                'type' => 'string',
                                'sentAs' => 'value',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateSpotDatafeedSubscriptionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SpotDatafeedSubscription' => array(
                    'description' => 'The SpotDatafeedSubscriptionType data type.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'spotDatafeedSubscription',
                    'properties' => array(
                        'OwnerId' => array(
                            'description' => 'Specifies the AWS account ID of the account.',
                            'type' => 'string',
                            'sentAs' => 'ownerId',
                        ),
                        'Bucket' => array(
                            'description' => 'Specifies the Amazon S3 bucket where the Spot Instance data feed is located.',
                            'type' => 'string',
                            'sentAs' => 'bucket',
                        ),
                        'Prefix' => array(
                            'description' => 'Contains the prefix that is prepended to data feed files.',
                            'type' => 'string',
                            'sentAs' => 'prefix',
                        ),
                        'State' => array(
                            'description' => 'Specifies the state of the Spot Instance request.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'Fault' => array(
                            'description' => 'Specifies a fault code for the Spot Instance request, if present.',
                            'type' => 'object',
                            'sentAs' => 'fault',
                            'properties' => array(
                                'Code' => array(
                                    'type' => 'string',
                                    'sentAs' => 'code',
                                ),
                                'Message' => array(
                                    'type' => 'string',
                                    'sentAs' => 'message',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateSubnetResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subnet' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'subnet',
                    'properties' => array(
                        'SubnetId' => array(
                            'description' => 'Specifies the ID of the subnet.',
                            'type' => 'string',
                            'sentAs' => 'subnetId',
                        ),
                        'State' => array(
                            'description' => 'Describes the current state of the subnet. The state of the subnet may be either pending or available.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'VpcId' => array(
                            'description' => 'Contains the ID of the VPC the subnet is in.',
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'CidrBlock' => array(
                            'description' => 'Specifies the CIDR block assigned to the subnet.',
                            'type' => 'string',
                            'sentAs' => 'cidrBlock',
                        ),
                        'AvailableIpAddressCount' => array(
                            'description' => 'Specifies the number of unused IP addresses in the subnet.',
                            'type' => 'numeric',
                            'sentAs' => 'availableIpAddressCount',
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'Specifies the Availability Zone the subnet is in.',
                            'type' => 'string',
                            'sentAs' => 'availabilityZone',
                        ),
                        'DefaultForAz' => array(
                            'type' => 'boolean',
                            'sentAs' => 'defaultForAz',
                        ),
                        'MapPublicIpOnLaunch' => array(
                            'type' => 'boolean',
                            'sentAs' => 'mapPublicIpOnLaunch',
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the Subnet.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'volume' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeId' => array(
                    'description' => 'The unique ID of this volume.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'volumeId',
                ),
                'Size' => array(
                    'description' => 'The size of this volume, in gigabytes.',
                    'type' => 'numeric',
                    'location' => 'xml',
                    'sentAs' => 'size',
                ),
                'SnapshotId' => array(
                    'description' => 'Optional snapshot from which this volume was created.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'snapshotId',
                ),
                'AvailabilityZone' => array(
                    'description' => 'Availability zone in which this volume was created.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'availabilityZone',
                ),
                'State' => array(
                    'description' => 'State of this volume (e.g., creating, available).',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'status',
                ),
                'CreateTime' => array(
                    'description' => 'Timestamp when volume creation was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'createTime',
                ),
                'Attachments' => array(
                    'description' => 'Information on what this volume is attached to.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'attachmentSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Specifies the details of a how an EC2 EBS volume is attached to an instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VolumeId' => array(
                                'type' => 'string',
                                'sentAs' => 'volumeId',
                            ),
                            'InstanceId' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'Device' => array(
                                'description' => 'How the device is exposed to the instance (e.g., /dev/sdh).',
                                'type' => 'string',
                                'sentAs' => 'device',
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'AttachTime' => array(
                                'description' => 'Timestamp when this attachment initiated.',
                                'type' => 'string',
                                'sentAs' => 'attachTime',
                            ),
                            'DeleteOnTermination' => array(
                                'description' => '` Whether this volume will be deleted or not when the associated instance is terminated.',
                                'type' => 'boolean',
                                'sentAs' => 'deleteOnTermination',
                            ),
                        ),
                    ),
                ),
                'Tags' => array(
                    'description' => 'A list of tags for the Volume.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'tagSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'The tag\'s key.',
                                'type' => 'string',
                                'sentAs' => 'key',
                            ),
                            'Value' => array(
                                'description' => 'The tag\'s value.',
                                'type' => 'string',
                                'sentAs' => 'value',
                            ),
                        ),
                    ),
                ),
                'VolumeType' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'volumeType',
                ),
                'Iops' => array(
                    'type' => 'numeric',
                    'location' => 'xml',
                    'sentAs' => 'iops',
                ),
            ),
        ),
        'CreateVpcResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Vpc' => array(
                    'description' => 'Information about the VPC.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'vpc',
                    'properties' => array(
                        'VpcId' => array(
                            'description' => 'Specifies the ID of the VPC.',
                            'type' => 'string',
                            'sentAs' => 'vpcId',
                        ),
                        'State' => array(
                            'description' => 'Describes the current state of the VPC. The state of the subnet may be either pending or available.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'CidrBlock' => array(
                            'description' => 'Specifies the CIDR block the VPC covers.',
                            'type' => 'string',
                            'sentAs' => 'cidrBlock',
                        ),
                        'DhcpOptionsId' => array(
                            'description' => 'Specifies the ID of the set of DHCP options associated with the VPC. Contains a value of default if the default options are associated with the VPC.',
                            'type' => 'string',
                            'sentAs' => 'dhcpOptionsId',
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the VPC.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                        'InstanceTenancy' => array(
                            'description' => 'The allowed tenancy of instances launched into the VPC.',
                            'type' => 'string',
                            'sentAs' => 'instanceTenancy',
                        ),
                        'IsDefault' => array(
                            'type' => 'boolean',
                            'sentAs' => 'isDefault',
                        ),
                    ),
                ),
            ),
        ),
        'CreateVpnConnectionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpnConnection' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'vpnConnection',
                    'properties' => array(
                        'VpnConnectionId' => array(
                            'description' => 'Specifies the ID of the VPN gateway at the VPC end of the VPN connection.',
                            'type' => 'string',
                            'sentAs' => 'vpnConnectionId',
                        ),
                        'State' => array(
                            'description' => 'Describes the current state of the VPN connection. Valid values are pending, available, deleting, and deleted.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'CustomerGatewayConfiguration' => array(
                            'description' => 'Contains configuration information in the native XML format for the VPN connection\'s customer gateway.',
                            'type' => 'string',
                            'sentAs' => 'customerGatewayConfiguration',
                        ),
                        'Type' => array(
                            'description' => 'Specifies the type of VPN connection.',
                            'type' => 'string',
                            'sentAs' => 'type',
                        ),
                        'CustomerGatewayId' => array(
                            'description' => 'Specifies ID of the customer gateway at the end of the VPN connection.',
                            'type' => 'string',
                            'sentAs' => 'customerGatewayId',
                        ),
                        'VpnGatewayId' => array(
                            'description' => 'Specfies the ID of the VPN gateway at the VPC end of the VPN connection.',
                            'type' => 'string',
                            'sentAs' => 'vpnGatewayId',
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the VpnConnection.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                        'VgwTelemetry' => array(
                            'type' => 'array',
                            'sentAs' => 'vgwTelemetry',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'OutsideIpAddress' => array(
                                        'type' => 'string',
                                        'sentAs' => 'outsideIpAddress',
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'LastStatusChange' => array(
                                        'type' => 'string',
                                        'sentAs' => 'lastStatusChange',
                                    ),
                                    'StatusMessage' => array(
                                        'type' => 'string',
                                        'sentAs' => 'statusMessage',
                                    ),
                                    'AcceptedRouteCount' => array(
                                        'type' => 'numeric',
                                        'sentAs' => 'acceptedRouteCount',
                                    ),
                                ),
                            ),
                        ),
                        'Options' => array(
                            'type' => 'object',
                            'sentAs' => 'options',
                            'properties' => array(
                                'StaticRoutesOnly' => array(
                                    'type' => 'boolean',
                                    'sentAs' => 'staticRoutesOnly',
                                ),
                            ),
                        ),
                        'Routes' => array(
                            'type' => 'array',
                            'sentAs' => 'routes',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'DestinationCidrBlock' => array(
                                        'type' => 'string',
                                        'sentAs' => 'destinationCidrBlock',
                                    ),
                                    'Source' => array(
                                        'type' => 'string',
                                        'sentAs' => 'source',
                                    ),
                                    'State' => array(
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CreateVpnGatewayResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpnGateway' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'vpnGateway',
                    'properties' => array(
                        'VpnGatewayId' => array(
                            'description' => 'Specifies the ID of the VPN gateway.',
                            'type' => 'string',
                            'sentAs' => 'vpnGatewayId',
                        ),
                        'State' => array(
                            'description' => 'Describes the current state of the VPN gateway. Valid values are pending, available, deleting, and deleted.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'Type' => array(
                            'description' => 'Specifies the type of VPN connection the VPN gateway supports.',
                            'type' => 'string',
                            'sentAs' => 'type',
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'Specifies the Availability Zone where the VPN gateway was created.',
                            'type' => 'string',
                            'sentAs' => 'availabilityZone',
                        ),
                        'VpcAttachments' => array(
                            'description' => 'Contains information about the VPCs attached to the VPN gateway.',
                            'type' => 'array',
                            'sentAs' => 'attachments',
                            'items' => array(
                                'name' => 'item',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'VpcId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'vpcId',
                                    ),
                                    'State' => array(
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                        'Tags' => array(
                            'description' => 'A list of tags for the VpnGateway.',
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeAccountAttributesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AccountAttributes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'accountAttributeSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'AttributeName' => array(
                                'type' => 'string',
                                'sentAs' => 'attributeName',
                            ),
                            'AttributeValues' => array(
                                'type' => 'array',
                                'sentAs' => 'attributeValueSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'AttributeValue' => array(
                                            'type' => 'string',
                                            'sentAs' => 'attributeValue',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeAddressesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Addresses' => array(
                    'description' => 'The list of Elastic IPs.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'addressesSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'PublicIp' => array(
                                'type' => 'string',
                                'sentAs' => 'publicIp',
                            ),
                            'AllocationId' => array(
                                'type' => 'string',
                                'sentAs' => 'allocationId',
                            ),
                            'AssociationId' => array(
                                'type' => 'string',
                                'sentAs' => 'associationId',
                            ),
                            'Domain' => array(
                                'type' => 'string',
                                'sentAs' => 'domain',
                            ),
                            'NetworkInterfaceId' => array(
                                'type' => 'string',
                                'sentAs' => 'networkInterfaceId',
                            ),
                            'NetworkInterfaceOwnerId' => array(
                                'type' => 'string',
                                'sentAs' => 'networkInterfaceOwnerId',
                            ),
                            'PrivateIpAddress' => array(
                                'type' => 'string',
                                'sentAs' => 'privateIpAddress',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeAvailabilityZonesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AvailabilityZones' => array(
                    'description' => 'The list of described Amazon EC2 availability zones.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'availabilityZoneInfo',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An EC2 availability zone, separate and fault tolerant from other availability zones.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ZoneName' => array(
                                'description' => 'Name of the Availability Zone.',
                                'type' => 'string',
                                'sentAs' => 'zoneName',
                            ),
                            'State' => array(
                                'description' => 'State of the Availability Zone.',
                                'type' => 'string',
                                'sentAs' => 'zoneState',
                            ),
                            'RegionName' => array(
                                'description' => 'Name of the region in which this zone resides.',
                                'type' => 'string',
                                'sentAs' => 'regionName',
                            ),
                            'Messages' => array(
                                'description' => 'A list of messages about the Availability Zone.',
                                'type' => 'array',
                                'sentAs' => 'messageSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Message' => array(
                                            'type' => 'string',
                                            'sentAs' => 'message',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeBundleTasksResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'BundleTasks' => array(
                    'description' => 'The list of described bundle tasks.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'bundleInstanceTasksSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a task to bundle an EC2 Windows instance into a new image.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Instance associated with this bundle task.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'BundleId' => array(
                                'description' => 'Unique identifier for this task.',
                                'type' => 'string',
                                'sentAs' => 'bundleId',
                            ),
                            'State' => array(
                                'description' => 'The state of this task.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'StartTime' => array(
                                'description' => 'The time this task started.',
                                'type' => 'string',
                                'sentAs' => 'startTime',
                            ),
                            'UpdateTime' => array(
                                'description' => 'The time of the most recent update for the task.',
                                'type' => 'string',
                                'sentAs' => 'updateTime',
                            ),
                            'Storage' => array(
                                'description' => 'Amazon S3 storage locations.',
                                'type' => 'object',
                                'sentAs' => 'storage',
                                'properties' => array(
                                    'S3' => array(
                                        'description' => 'The details of S3 storage for bundling a Windows instance.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Bucket' => array(
                                                'description' => 'The bucket in which to store the AMI. You can specify a bucket that you already own or a new bucket that Amazon EC2 creates on your behalf.',
                                                'type' => 'string',
                                                'sentAs' => 'bucket',
                                            ),
                                            'Prefix' => array(
                                                'description' => 'The prefix to use when storing the AMI in S3.',
                                                'type' => 'string',
                                                'sentAs' => 'prefix',
                                            ),
                                            'AWSAccessKeyId' => array(
                                                'description' => 'The Access Key ID of the owner of the Amazon S3 bucket.',
                                                'type' => 'string',
                                            ),
                                            'UploadPolicy' => array(
                                                'description' => 'A Base64-encoded Amazon S3 upload policy that gives Amazon EC2 permission to upload items into Amazon S3 on the user\'s behalf.',
                                                'type' => 'string',
                                                'sentAs' => 'uploadPolicy',
                                            ),
                                            'UploadPolicySignature' => array(
                                                'description' => 'The signature of the Base64 encoded JSON document.',
                                                'type' => 'string',
                                                'sentAs' => 'uploadPolicySignature',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Progress' => array(
                                'description' => 'The level of task completion, in percent (e.g., 20%).',
                                'type' => 'string',
                                'sentAs' => 'progress',
                            ),
                            'BundleTaskError' => array(
                                'description' => 'If the task fails, a description of the error.',
                                'type' => 'object',
                                'sentAs' => 'error',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'Error code.',
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'Message' => array(
                                        'description' => 'Error message.',
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeConversionTasksResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ConversionTasks' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'conversionTasks',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ConversionTaskId' => array(
                                'type' => 'string',
                                'sentAs' => 'conversionTaskId',
                            ),
                            'ExpirationTime' => array(
                                'type' => 'string',
                                'sentAs' => 'expirationTime',
                            ),
                            'ImportInstance' => array(
                                'type' => 'object',
                                'sentAs' => 'importInstance',
                                'properties' => array(
                                    'Volumes' => array(
                                        'type' => 'array',
                                        'sentAs' => 'volumes',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'BytesConverted' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'bytesConverted',
                                                ),
                                                'AvailabilityZone' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'availabilityZone',
                                                ),
                                                'Image' => array(
                                                    'type' => 'object',
                                                    'sentAs' => 'image',
                                                    'properties' => array(
                                                        'Format' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'format',
                                                        ),
                                                        'Size' => array(
                                                            'type' => 'numeric',
                                                            'sentAs' => 'size',
                                                        ),
                                                        'ImportManifestUrl' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'importManifestUrl',
                                                        ),
                                                        'Checksum' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'checksum',
                                                        ),
                                                    ),
                                                ),
                                                'Volume' => array(
                                                    'type' => 'object',
                                                    'sentAs' => 'volume',
                                                    'properties' => array(
                                                        'Size' => array(
                                                            'type' => 'numeric',
                                                            'sentAs' => 'size',
                                                        ),
                                                        'Id' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'id',
                                                        ),
                                                    ),
                                                ),
                                                'Status' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                                'StatusMessage' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'statusMessage',
                                                ),
                                                'Description' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'description',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'InstanceId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceId',
                                    ),
                                    'Platform' => array(
                                        'type' => 'string',
                                        'sentAs' => 'platform',
                                    ),
                                    'Description' => array(
                                        'type' => 'string',
                                        'sentAs' => 'description',
                                    ),
                                ),
                            ),
                            'ImportVolume' => array(
                                'type' => 'object',
                                'sentAs' => 'importVolume',
                                'properties' => array(
                                    'BytesConverted' => array(
                                        'type' => 'numeric',
                                        'sentAs' => 'bytesConverted',
                                    ),
                                    'AvailabilityZone' => array(
                                        'type' => 'string',
                                        'sentAs' => 'availabilityZone',
                                    ),
                                    'Description' => array(
                                        'type' => 'string',
                                        'sentAs' => 'description',
                                    ),
                                    'Image' => array(
                                        'type' => 'object',
                                        'sentAs' => 'image',
                                        'properties' => array(
                                            'Format' => array(
                                                'type' => 'string',
                                                'sentAs' => 'format',
                                            ),
                                            'Size' => array(
                                                'type' => 'numeric',
                                                'sentAs' => 'size',
                                            ),
                                            'ImportManifestUrl' => array(
                                                'type' => 'string',
                                                'sentAs' => 'importManifestUrl',
                                            ),
                                            'Checksum' => array(
                                                'type' => 'string',
                                                'sentAs' => 'checksum',
                                            ),
                                        ),
                                    ),
                                    'Volume' => array(
                                        'type' => 'object',
                                        'sentAs' => 'volume',
                                        'properties' => array(
                                            'Size' => array(
                                                'type' => 'numeric',
                                                'sentAs' => 'size',
                                            ),
                                            'Id' => array(
                                                'type' => 'string',
                                                'sentAs' => 'id',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'StatusMessage' => array(
                                'type' => 'string',
                                'sentAs' => 'statusMessage',
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeCustomerGatewaysResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CustomerGateways' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'customerGatewaySet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The CustomerGateway data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'CustomerGatewayId' => array(
                                'description' => 'Specifies the ID of the customer gateway.',
                                'type' => 'string',
                                'sentAs' => 'customerGatewayId',
                            ),
                            'State' => array(
                                'description' => 'Describes the current state of the customer gateway. Valid values are pending, available, deleting, and deleted.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'Type' => array(
                                'description' => 'Specifies the type of VPN connection the customer gateway supports.',
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'IpAddress' => array(
                                'description' => 'Contains the Internet-routable IP address of the customer gateway\'s outside interface.',
                                'type' => 'string',
                                'sentAs' => 'ipAddress',
                            ),
                            'BgpAsn' => array(
                                'description' => 'Specifies the customer gateway\'s Border Gateway Protocol (BGP) Autonomous System Number (ASN).',
                                'type' => 'string',
                                'sentAs' => 'bgpAsn',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the CustomerGateway.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeDhcpOptionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DhcpOptions' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'dhcpOptionsSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The DhcpOptions data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'DhcpOptionsId' => array(
                                'description' => 'Specifies the ID of the set of DHCP options.',
                                'type' => 'string',
                                'sentAs' => 'dhcpOptionsId',
                            ),
                            'DhcpConfigurations' => array(
                                'description' => 'Contains information about the set of DHCP options.',
                                'type' => 'array',
                                'sentAs' => 'dhcpConfigurationSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'The DhcpConfiguration data type',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'Contains the name of a DHCP option.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Values' => array(
                                            'description' => 'Contains a set of values for a DHCP option.',
                                            'type' => 'array',
                                            'sentAs' => 'valueSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'type' => 'string',
                                                'sentAs' => 'item',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the DhcpOptions.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeExportTasksResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ExportTasks' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'exportTaskSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ExportTaskId' => array(
                                'type' => 'string',
                                'sentAs' => 'exportTaskId',
                            ),
                            'Description' => array(
                                'type' => 'string',
                                'sentAs' => 'description',
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'StatusMessage' => array(
                                'type' => 'string',
                                'sentAs' => 'statusMessage',
                            ),
                            'InstanceExportDetails' => array(
                                'type' => 'object',
                                'sentAs' => 'instanceExport',
                                'properties' => array(
                                    'InstanceId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceId',
                                    ),
                                    'TargetEnvironment' => array(
                                        'type' => 'string',
                                        'sentAs' => 'targetEnvironment',
                                    ),
                                ),
                            ),
                            'ExportToS3Task' => array(
                                'type' => 'object',
                                'sentAs' => 'exportToS3',
                                'properties' => array(
                                    'DiskImageFormat' => array(
                                        'type' => 'string',
                                        'sentAs' => 'diskImageFormat',
                                    ),
                                    'ContainerFormat' => array(
                                        'type' => 'string',
                                        'sentAs' => 'containerFormat',
                                    ),
                                    'S3Bucket' => array(
                                        'type' => 'string',
                                        'sentAs' => 's3Bucket',
                                    ),
                                    'S3Key' => array(
                                        'type' => 'string',
                                        'sentAs' => 's3Key',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'imageAttribute' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ImageId' => array(
                    'description' => 'The ID of the associated AMI.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'imageId',
                ),
                'LaunchPermissions' => array(
                    'description' => 'Launch permissions for the associated AMI.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'launchPermission',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Describes a permission to launch an Amazon Machine Image (AMI).',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'UserId' => array(
                                'description' => 'The AWS user ID of the user involved in this launch permission.',
                                'type' => 'string',
                                'sentAs' => 'userId',
                            ),
                            'Group' => array(
                                'description' => 'The AWS group of the user involved in this launch permission.',
                                'type' => 'string',
                                'sentAs' => 'group',
                            ),
                        ),
                    ),
                ),
                'ProductCodes' => array(
                    'description' => 'Product codes for the associated AMI.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'productCodes',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An AWS DevPay product code.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ProductCodeId' => array(
                                'description' => 'The unique ID of an AWS DevPay product code.',
                                'type' => 'string',
                                'sentAs' => 'productCode',
                            ),
                            'ProductCodeType' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                        ),
                    ),
                ),
                'KernelId' => array(
                    'description' => 'Kernel ID of the associated AMI.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'kernel',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'RamdiskId' => array(
                    'description' => 'Ramdisk ID of the associated AMI.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'ramdisk',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'Description' => array(
                    'description' => 'User-created description of the associated AMI.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'description',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'Block device mappings for the associated AMI.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'blockDeviceMapping',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The BlockDeviceMappingItemType data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VirtualName' => array(
                                'description' => 'Specifies the virtual device name.',
                                'type' => 'string',
                                'sentAs' => 'virtualName',
                            ),
                            'DeviceName' => array(
                                'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                'type' => 'string',
                                'sentAs' => 'deviceName',
                            ),
                            'Ebs' => array(
                                'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                'type' => 'object',
                                'sentAs' => 'ebs',
                                'properties' => array(
                                    'SnapshotId' => array(
                                        'description' => 'The ID of the snapshot from which the volume will be created.',
                                        'type' => 'string',
                                        'sentAs' => 'snapshotId',
                                    ),
                                    'VolumeSize' => array(
                                        'description' => 'The size of the volume, in gigabytes.',
                                        'type' => 'numeric',
                                        'sentAs' => 'volumeSize',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'sentAs' => 'deleteOnTermination',
                                    ),
                                    'VolumeType' => array(
                                        'type' => 'string',
                                        'sentAs' => 'volumeType',
                                    ),
                                    'Iops' => array(
                                        'type' => 'numeric',
                                        'sentAs' => 'iops',
                                    ),
                                ),
                            ),
                            'NoDevice' => array(
                                'description' => 'Specifies the device name to suppress during instance launch.',
                                'type' => 'string',
                                'sentAs' => 'noDevice',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeImagesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Images' => array(
                    'description' => 'The list of the described AMIs.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'imagesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents an Amazon Machine Image (AMI) that can be run on an Amazon EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ImageId' => array(
                                'description' => 'The unique ID of the AMI.',
                                'type' => 'string',
                                'sentAs' => 'imageId',
                            ),
                            'ImageLocation' => array(
                                'description' => 'The location of the AMI.',
                                'type' => 'string',
                                'sentAs' => 'imageLocation',
                            ),
                            'State' => array(
                                'description' => 'Current state of the AMI. If the operation returns available, the image is successfully registered and available for launching. If the operation returns deregistered, the image is deregistered and no longer available for launching.',
                                'type' => 'string',
                                'sentAs' => 'imageState',
                            ),
                            'OwnerId' => array(
                                'description' => 'AWS Access Key ID of the image owner.',
                                'type' => 'string',
                                'sentAs' => 'imageOwnerId',
                            ),
                            'Public' => array(
                                'description' => 'True if this image has public launch permissions. False if it only has implicit and explicit launch permissions.',
                                'type' => 'boolean',
                                'sentAs' => 'isPublic',
                            ),
                            'ProductCodes' => array(
                                'description' => 'Product codes of the AMI.',
                                'type' => 'array',
                                'sentAs' => 'productCodes',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'An AWS DevPay product code.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'ProductCodeId' => array(
                                            'description' => 'The unique ID of an AWS DevPay product code.',
                                            'type' => 'string',
                                            'sentAs' => 'productCode',
                                        ),
                                        'ProductCodeType' => array(
                                            'type' => 'string',
                                            'sentAs' => 'type',
                                        ),
                                    ),
                                ),
                            ),
                            'Architecture' => array(
                                'description' => 'The architecture of the image.',
                                'type' => 'string',
                                'sentAs' => 'architecture',
                            ),
                            'ImageType' => array(
                                'description' => 'The type of image (machine, kernel, or ramdisk).',
                                'type' => 'string',
                                'sentAs' => 'imageType',
                            ),
                            'KernelId' => array(
                                'description' => 'The kernel associated with the image, if any. Only applicable for machine images.',
                                'type' => 'string',
                                'sentAs' => 'kernelId',
                            ),
                            'RamdiskId' => array(
                                'description' => 'The RAM disk associated with the image, if any. Only applicable for machine images.',
                                'type' => 'string',
                                'sentAs' => 'ramdiskId',
                            ),
                            'Platform' => array(
                                'description' => 'The operating platform of the AMI.',
                                'type' => 'string',
                                'sentAs' => 'platform',
                            ),
                            'StateReason' => array(
                                'description' => 'The reason for the state change.',
                                'type' => 'object',
                                'sentAs' => 'stateReason',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'Reason code for the state change.',
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'Message' => array(
                                        'description' => 'Descriptive message for the state change.',
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'ImageOwnerAlias' => array(
                                'description' => 'The AWS account alias (e.g., "amazon", "redhat", "self", etc.) or AWS account ID that owns the AMI.',
                                'type' => 'string',
                                'sentAs' => 'imageOwnerAlias',
                            ),
                            'Name' => array(
                                'description' => 'The name of the AMI that was provided during image creation.',
                                'type' => 'string',
                                'sentAs' => 'name',
                            ),
                            'Description' => array(
                                'description' => 'The description of the AMI that was provided during image creation.',
                                'type' => 'string',
                                'sentAs' => 'description',
                            ),
                            'RootDeviceType' => array(
                                'description' => 'The root device type used by the AMI. The AMI can use an Amazon EBS or instance store root device.',
                                'type' => 'string',
                                'sentAs' => 'rootDeviceType',
                            ),
                            'RootDeviceName' => array(
                                'description' => 'The root device name (e.g., /dev/sda1).',
                                'type' => 'string',
                                'sentAs' => 'rootDeviceName',
                            ),
                            'BlockDeviceMappings' => array(
                                'description' => 'Specifies how block devices are exposed to the instance.',
                                'type' => 'array',
                                'sentAs' => 'blockDeviceMapping',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'The BlockDeviceMappingItemType data type.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'VirtualName' => array(
                                            'description' => 'Specifies the virtual device name.',
                                            'type' => 'string',
                                            'sentAs' => 'virtualName',
                                        ),
                                        'DeviceName' => array(
                                            'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                            'type' => 'string',
                                            'sentAs' => 'deviceName',
                                        ),
                                        'Ebs' => array(
                                            'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                            'type' => 'object',
                                            'sentAs' => 'ebs',
                                            'properties' => array(
                                                'SnapshotId' => array(
                                                    'description' => 'The ID of the snapshot from which the volume will be created.',
                                                    'type' => 'string',
                                                    'sentAs' => 'snapshotId',
                                                ),
                                                'VolumeSize' => array(
                                                    'description' => 'The size of the volume, in gigabytes.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'volumeSize',
                                                ),
                                                'DeleteOnTermination' => array(
                                                    'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                    'type' => 'boolean',
                                                    'sentAs' => 'deleteOnTermination',
                                                ),
                                                'VolumeType' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'volumeType',
                                                ),
                                                'Iops' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'iops',
                                                ),
                                            ),
                                        ),
                                        'NoDevice' => array(
                                            'description' => 'Specifies the device name to suppress during instance launch.',
                                            'type' => 'string',
                                            'sentAs' => 'noDevice',
                                        ),
                                    ),
                                ),
                            ),
                            'VirtualizationType' => array(
                                'type' => 'string',
                                'sentAs' => 'virtualizationType',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Image.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'Hypervisor' => array(
                                'type' => 'string',
                                'sentAs' => 'hypervisor',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'InstanceAttribute' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceId' => array(
                    'description' => 'The ID of the associated instance.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'instanceId',
                ),
                'InstanceType' => array(
                    'description' => 'The instance type (e.g., m1.small, c1.medium, m2.2xlarge, and so on).',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'instanceType',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'KernelId' => array(
                    'description' => 'The kernel ID of the associated instance.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'kernel',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'RamdiskId' => array(
                    'description' => 'The ramdisk ID of the associated instance.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'ramdisk',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'UserData' => array(
                    'description' => 'MIME, Base64-encoded user data.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'userData',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'DisableApiTermination' => array(
                    'description' => 'Whether this instance can be terminated. You must modify this attribute before you can terminate any "locked" instances.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'disableApiTermination',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'InstanceInitiatedShutdownBehavior' => array(
                    'description' => 'Whether this instance\'s Amazon EBS volumes are deleted when the instance is shut down.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'instanceInitiatedShutdownBehavior',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'RootDeviceName' => array(
                    'description' => 'The root device name (e.g., /dev/sda1).',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'rootDeviceName',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'How block devices are exposed to this instance. Each mapping is made up of a virtualName and a deviceName.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'blockDeviceMapping',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Describes how block devices are mapped on an Amazon EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'DeviceName' => array(
                                'description' => 'The device name (e.g., /dev/sdh) at which the block device is exposed on the instance.',
                                'type' => 'string',
                                'sentAs' => 'deviceName',
                            ),
                            'Ebs' => array(
                                'description' => 'The optional EBS device mapped to the specified device name.',
                                'type' => 'object',
                                'sentAs' => 'ebs',
                                'properties' => array(
                                    'VolumeId' => array(
                                        'description' => 'The ID of the EBS volume.',
                                        'type' => 'string',
                                        'sentAs' => 'volumeId',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the EBS volume.',
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'AttachTime' => array(
                                        'description' => 'The time at which the EBS volume was attached to the associated instance.',
                                        'type' => 'string',
                                        'sentAs' => 'attachTime',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                        'type' => 'boolean',
                                        'sentAs' => 'deleteOnTermination',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'ProductCodes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'productCodes',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An AWS DevPay product code.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ProductCodeId' => array(
                                'description' => 'The unique ID of an AWS DevPay product code.',
                                'type' => 'string',
                                'sentAs' => 'productCode',
                            ),
                            'ProductCodeType' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                        ),
                    ),
                ),
                'EbsOptimized' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'ebsOptimized',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
            ),
        ),
        'DescribeInstanceStatusResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceStatuses' => array(
                    'description' => 'Collection of instance statuses describing the state of the requested instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instanceStatusSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents the status of an Amazon EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The ID of the Amazon EC2 instance.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The Amazon EC2 instance\'s availability zone.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'Events' => array(
                                'description' => 'Events that affect the status of the associated Amazon EC2 instance.',
                                'type' => 'array',
                                'sentAs' => 'eventsSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents an event that affects the status of an Amazon EC2 instance.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Code' => array(
                                            'description' => 'The associated code of the event. Valid values: instance-reboot, system-reboot, instance-retirement',
                                            'type' => 'string',
                                            'sentAs' => 'code',
                                        ),
                                        'Description' => array(
                                            'description' => 'A description of the event.',
                                            'type' => 'string',
                                            'sentAs' => 'description',
                                        ),
                                        'NotBefore' => array(
                                            'description' => 'The earliest scheduled start time for the event.',
                                            'type' => 'string',
                                            'sentAs' => 'notBefore',
                                        ),
                                        'NotAfter' => array(
                                            'description' => 'The latest scheduled end time for the event.',
                                            'type' => 'string',
                                            'sentAs' => 'notAfter',
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceState' => array(
                                'description' => 'Represents the state of an Amazon EC2 instance.',
                                'type' => 'object',
                                'sentAs' => 'instanceState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                            'SystemStatus' => array(
                                'type' => 'object',
                                'sentAs' => 'systemStatus',
                                'properties' => array(
                                    'Status' => array(
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'Details' => array(
                                        'type' => 'array',
                                        'sentAs' => 'details',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'Name' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'name',
                                                ),
                                                'Status' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                                'ImpairedSince' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'impairedSince',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceStatus' => array(
                                'type' => 'object',
                                'sentAs' => 'instanceStatus',
                                'properties' => array(
                                    'Status' => array(
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'Details' => array(
                                        'type' => 'array',
                                        'sentAs' => 'details',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'Name' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'name',
                                                ),
                                                'Status' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                                'ImpairedSince' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'impairedSince',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string specifying the next paginated set of results to return.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'nextToken',
                ),
            ),
        ),
        'DescribeInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Reservations' => array(
                    'description' => 'The list of reservations containing the describes instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservationSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An Amazon EC2 reservation of requested EC2 instances.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservationId' => array(
                                'description' => 'The unique ID of this reservation.',
                                'type' => 'string',
                                'sentAs' => 'reservationId',
                            ),
                            'OwnerId' => array(
                                'description' => 'The AWS Access Key ID of the user who owns the reservation.',
                                'type' => 'string',
                                'sentAs' => 'ownerId',
                            ),
                            'RequesterId' => array(
                                'description' => 'The unique ID of the user who requested the instances in this reservation.',
                                'type' => 'string',
                                'sentAs' => 'requesterId',
                            ),
                            'Groups' => array(
                                'description' => 'The list of security groups requested for the instances in this reservation.',
                                'type' => 'array',
                                'sentAs' => 'groupSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'GroupName' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupName',
                                        ),
                                        'GroupId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupId',
                                        ),
                                    ),
                                ),
                            ),
                            'Instances' => array(
                                'description' => 'The list of Amazon EC2 instances included in this reservation.',
                                'type' => 'array',
                                'sentAs' => 'instancesSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents an Amazon EC2 instance.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'InstanceId' => array(
                                            'description' => 'Unique ID of the instance launched.',
                                            'type' => 'string',
                                            'sentAs' => 'instanceId',
                                        ),
                                        'ImageId' => array(
                                            'description' => 'Image ID of the AMI used to launch the instance.',
                                            'type' => 'string',
                                            'sentAs' => 'imageId',
                                        ),
                                        'State' => array(
                                            'description' => 'The current state of the instance.',
                                            'type' => 'object',
                                            'sentAs' => 'instanceState',
                                            'properties' => array(
                                                'Code' => array(
                                                    'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'code',
                                                ),
                                                'Name' => array(
                                                    'description' => 'The current state of the instance.',
                                                    'type' => 'string',
                                                    'sentAs' => 'name',
                                                ),
                                            ),
                                        ),
                                        'PrivateDnsName' => array(
                                            'description' => 'The private DNS name assigned to the instance. This DNS name can only be used inside the Amazon EC2 network. This element remains empty until the instance enters a running state.',
                                            'type' => 'string',
                                            'sentAs' => 'privateDnsName',
                                        ),
                                        'PublicDnsName' => array(
                                            'description' => 'The public DNS name assigned to the instance. This DNS name is contactable from outside the Amazon EC2 network. This element remains empty until the instance enters a running state.',
                                            'type' => 'string',
                                            'sentAs' => 'dnsName',
                                        ),
                                        'StateTransitionReason' => array(
                                            'description' => 'Reason for the most recent state transition. This might be an empty string.',
                                            'type' => 'string',
                                            'sentAs' => 'reason',
                                        ),
                                        'KeyName' => array(
                                            'description' => 'If this instance was launched with an associated key pair, this displays the key pair name.',
                                            'type' => 'string',
                                            'sentAs' => 'keyName',
                                        ),
                                        'AmiLaunchIndex' => array(
                                            'description' => 'The AMI launch index, which can be used to find this instance within the launch group.',
                                            'type' => 'numeric',
                                            'sentAs' => 'amiLaunchIndex',
                                        ),
                                        'ProductCodes' => array(
                                            'description' => 'Product codes attached to this instance.',
                                            'type' => 'array',
                                            'sentAs' => 'productCodes',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'An AWS DevPay product code.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'ProductCodeId' => array(
                                                        'description' => 'The unique ID of an AWS DevPay product code.',
                                                        'type' => 'string',
                                                        'sentAs' => 'productCode',
                                                    ),
                                                    'ProductCodeType' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'type',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'InstanceType' => array(
                                            'description' => 'The instance type. For more information on instance types, please see the Amazon Elastic Compute Cloud Developer Guide.',
                                            'type' => 'string',
                                            'sentAs' => 'instanceType',
                                        ),
                                        'LaunchTime' => array(
                                            'description' => 'The time this instance launched.',
                                            'type' => 'string',
                                            'sentAs' => 'launchTime',
                                        ),
                                        'Placement' => array(
                                            'description' => 'The location where this instance launched.',
                                            'type' => 'object',
                                            'sentAs' => 'placement',
                                            'properties' => array(
                                                'AvailabilityZone' => array(
                                                    'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                                    'type' => 'string',
                                                    'sentAs' => 'availabilityZone',
                                                ),
                                                'GroupName' => array(
                                                    'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                                    'type' => 'string',
                                                    'sentAs' => 'groupName',
                                                ),
                                                'Tenancy' => array(
                                                    'description' => 'The allowed tenancy of instances launched into the VPC. A value of default means instances can be launched with any tenancy; a value of dedicated means all instances launched into the VPC will be launched as dedicated tenancy regardless of the tenancy assigned to the instance at launch.',
                                                    'type' => 'string',
                                                    'sentAs' => 'tenancy',
                                                ),
                                            ),
                                        ),
                                        'KernelId' => array(
                                            'description' => 'Kernel associated with this instance.',
                                            'type' => 'string',
                                            'sentAs' => 'kernelId',
                                        ),
                                        'RamdiskId' => array(
                                            'description' => 'RAM disk associated with this instance.',
                                            'type' => 'string',
                                            'sentAs' => 'ramdiskId',
                                        ),
                                        'Platform' => array(
                                            'description' => 'Platform of the instance (e.g., Windows).',
                                            'type' => 'string',
                                            'sentAs' => 'platform',
                                        ),
                                        'Monitoring' => array(
                                            'description' => 'Monitoring status for this instance.',
                                            'type' => 'object',
                                            'sentAs' => 'monitoring',
                                            'properties' => array(
                                                'State' => array(
                                                    'description' => 'The state of monitoring on an Amazon EC2 instance (ex: enabled, disabled).',
                                                    'type' => 'string',
                                                    'sentAs' => 'state',
                                                ),
                                            ),
                                        ),
                                        'SubnetId' => array(
                                            'description' => 'Specifies the Amazon VPC subnet ID in which the instance is running.',
                                            'type' => 'string',
                                            'sentAs' => 'subnetId',
                                        ),
                                        'VpcId' => array(
                                            'description' => 'Specifies the Amazon VPC in which the instance is running.',
                                            'type' => 'string',
                                            'sentAs' => 'vpcId',
                                        ),
                                        'PrivateIpAddress' => array(
                                            'description' => 'Specifies the private IP address that is assigned to the instance (Amazon VPC).',
                                            'type' => 'string',
                                            'sentAs' => 'privateIpAddress',
                                        ),
                                        'PublicIpAddress' => array(
                                            'description' => 'Specifies the IP address of the instance.',
                                            'type' => 'string',
                                            'sentAs' => 'ipAddress',
                                        ),
                                        'StateReason' => array(
                                            'description' => 'The reason for the state change.',
                                            'type' => 'object',
                                            'sentAs' => 'stateReason',
                                            'properties' => array(
                                                'Code' => array(
                                                    'description' => 'Reason code for the state change.',
                                                    'type' => 'string',
                                                    'sentAs' => 'code',
                                                ),
                                                'Message' => array(
                                                    'description' => 'Descriptive message for the state change.',
                                                    'type' => 'string',
                                                    'sentAs' => 'message',
                                                ),
                                            ),
                                        ),
                                        'Architecture' => array(
                                            'description' => 'The architecture of this instance.',
                                            'type' => 'string',
                                            'sentAs' => 'architecture',
                                        ),
                                        'RootDeviceType' => array(
                                            'description' => 'The root device type used by the AMI. The AMI can use an Amazon EBS or instance store root device.',
                                            'type' => 'string',
                                            'sentAs' => 'rootDeviceType',
                                        ),
                                        'RootDeviceName' => array(
                                            'description' => 'The root device name (e.g., /dev/sda1).',
                                            'type' => 'string',
                                            'sentAs' => 'rootDeviceName',
                                        ),
                                        'BlockDeviceMappings' => array(
                                            'description' => 'Block device mapping set.',
                                            'type' => 'array',
                                            'sentAs' => 'blockDeviceMapping',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'Describes how block devices are mapped on an Amazon EC2 instance.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'DeviceName' => array(
                                                        'description' => 'The device name (e.g., /dev/sdh) at which the block device is exposed on the instance.',
                                                        'type' => 'string',
                                                        'sentAs' => 'deviceName',
                                                    ),
                                                    'Ebs' => array(
                                                        'description' => 'The optional EBS device mapped to the specified device name.',
                                                        'type' => 'object',
                                                        'sentAs' => 'ebs',
                                                        'properties' => array(
                                                            'VolumeId' => array(
                                                                'description' => 'The ID of the EBS volume.',
                                                                'type' => 'string',
                                                                'sentAs' => 'volumeId',
                                                            ),
                                                            'Status' => array(
                                                                'description' => 'The status of the EBS volume.',
                                                                'type' => 'string',
                                                                'sentAs' => 'status',
                                                            ),
                                                            'AttachTime' => array(
                                                                'description' => 'The time at which the EBS volume was attached to the associated instance.',
                                                                'type' => 'string',
                                                                'sentAs' => 'attachTime',
                                                            ),
                                                            'DeleteOnTermination' => array(
                                                                'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                                'type' => 'boolean',
                                                                'sentAs' => 'deleteOnTermination',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'VirtualizationType' => array(
                                            'type' => 'string',
                                            'sentAs' => 'virtualizationType',
                                        ),
                                        'InstanceLifecycle' => array(
                                            'type' => 'string',
                                            'sentAs' => 'instanceLifecycle',
                                        ),
                                        'SpotInstanceRequestId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'spotInstanceRequestId',
                                        ),
                                        'License' => array(
                                            'description' => 'Represents an active license in use and attached to an Amazon EC2 instance.',
                                            'type' => 'object',
                                            'sentAs' => 'license',
                                            'properties' => array(
                                                'Pool' => array(
                                                    'description' => 'The license pool from which this license was used (ex: \'windows\').',
                                                    'type' => 'string',
                                                    'sentAs' => 'pool',
                                                ),
                                            ),
                                        ),
                                        'ClientToken' => array(
                                            'type' => 'string',
                                            'sentAs' => 'clientToken',
                                        ),
                                        'Tags' => array(
                                            'description' => 'A list of tags for the Instance.',
                                            'type' => 'array',
                                            'sentAs' => 'tagSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'Key' => array(
                                                        'description' => 'The tag\'s key.',
                                                        'type' => 'string',
                                                        'sentAs' => 'key',
                                                    ),
                                                    'Value' => array(
                                                        'description' => 'The tag\'s value.',
                                                        'type' => 'string',
                                                        'sentAs' => 'value',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'SecurityGroups' => array(
                                            'type' => 'array',
                                            'sentAs' => 'groupSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'GroupName' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'groupName',
                                                    ),
                                                    'GroupId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'groupId',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'SourceDestCheck' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'sourceDestCheck',
                                        ),
                                        'Hypervisor' => array(
                                            'type' => 'string',
                                            'sentAs' => 'hypervisor',
                                        ),
                                        'NetworkInterfaces' => array(
                                            'type' => 'array',
                                            'sentAs' => 'networkInterfaceSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'NetworkInterfaceId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'networkInterfaceId',
                                                    ),
                                                    'SubnetId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'subnetId',
                                                    ),
                                                    'VpcId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'vpcId',
                                                    ),
                                                    'Description' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'description',
                                                    ),
                                                    'OwnerId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'ownerId',
                                                    ),
                                                    'Status' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'status',
                                                    ),
                                                    'PrivateIpAddress' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'privateIpAddress',
                                                    ),
                                                    'PrivateDnsName' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'privateDnsName',
                                                    ),
                                                    'SourceDestCheck' => array(
                                                        'type' => 'boolean',
                                                        'sentAs' => 'sourceDestCheck',
                                                    ),
                                                    'Groups' => array(
                                                        'type' => 'array',
                                                        'sentAs' => 'groupSet',
                                                        'items' => array(
                                                            'name' => 'item',
                                                            'type' => 'object',
                                                            'sentAs' => 'item',
                                                            'properties' => array(
                                                                'GroupName' => array(
                                                                    'type' => 'string',
                                                                    'sentAs' => 'groupName',
                                                                ),
                                                                'GroupId' => array(
                                                                    'type' => 'string',
                                                                    'sentAs' => 'groupId',
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                    'Attachment' => array(
                                                        'type' => 'object',
                                                        'sentAs' => 'attachment',
                                                        'properties' => array(
                                                            'AttachmentId' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'attachmentId',
                                                            ),
                                                            'DeviceIndex' => array(
                                                                'type' => 'numeric',
                                                                'sentAs' => 'deviceIndex',
                                                            ),
                                                            'Status' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'status',
                                                            ),
                                                            'AttachTime' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'attachTime',
                                                            ),
                                                            'DeleteOnTermination' => array(
                                                                'type' => 'boolean',
                                                                'sentAs' => 'deleteOnTermination',
                                                            ),
                                                        ),
                                                    ),
                                                    'Association' => array(
                                                        'type' => 'object',
                                                        'sentAs' => 'association',
                                                        'properties' => array(
                                                            'PublicIp' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'publicIp',
                                                            ),
                                                            'PublicDnsName' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'publicDnsName',
                                                            ),
                                                            'IpOwnerId' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'ipOwnerId',
                                                            ),
                                                        ),
                                                    ),
                                                    'PrivateIpAddresses' => array(
                                                        'type' => 'array',
                                                        'sentAs' => 'privateIpAddressesSet',
                                                        'items' => array(
                                                            'name' => 'item',
                                                            'type' => 'object',
                                                            'sentAs' => 'item',
                                                            'properties' => array(
                                                                'PrivateIpAddress' => array(
                                                                    'type' => 'string',
                                                                    'sentAs' => 'privateIpAddress',
                                                                ),
                                                                'PrivateDnsName' => array(
                                                                    'type' => 'string',
                                                                    'sentAs' => 'privateDnsName',
                                                                ),
                                                                'Primary' => array(
                                                                    'type' => 'boolean',
                                                                    'sentAs' => 'primary',
                                                                ),
                                                                'Association' => array(
                                                                    'type' => 'object',
                                                                    'sentAs' => 'association',
                                                                    'properties' => array(
                                                                        'PublicIp' => array(
                                                                            'type' => 'string',
                                                                            'sentAs' => 'publicIp',
                                                                        ),
                                                                        'PublicDnsName' => array(
                                                                            'type' => 'string',
                                                                            'sentAs' => 'publicDnsName',
                                                                        ),
                                                                        'IpOwnerId' => array(
                                                                            'type' => 'string',
                                                                            'sentAs' => 'ipOwnerId',
                                                                        ),
                                                                    ),
                                                                ),
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'IamInstanceProfile' => array(
                                            'type' => 'object',
                                            'sentAs' => 'iamInstanceProfile',
                                            'properties' => array(
                                                'Arn' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'arn',
                                                ),
                                                'Id' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'id',
                                                ),
                                            ),
                                        ),
                                        'EbsOptimized' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'ebsOptimized',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeInternetGatewaysResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InternetGateways' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'internetGatewaySet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InternetGatewayId' => array(
                                'type' => 'string',
                                'sentAs' => 'internetGatewayId',
                            ),
                            'Attachments' => array(
                                'type' => 'array',
                                'sentAs' => 'attachmentSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'VpcId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'vpcId',
                                        ),
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeKeyPairsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'KeyPairs' => array(
                    'description' => 'The list of described key pairs.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'keySet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Describes an Amazon EC2 key pair. This is a summary of the key pair data, and will not contain the actual private key material.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'KeyName' => array(
                                'description' => 'The name of the key pair.',
                                'type' => 'string',
                                'sentAs' => 'keyName',
                            ),
                            'KeyFingerprint' => array(
                                'description' => 'The SHA-1 digest of the DER encoded private key.',
                                'type' => 'string',
                                'sentAs' => 'keyFingerprint',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLicensesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Licenses' => array(
                    'description' => 'Specifies active licenses in use and attached to an Amazon EC2 instance.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'licenseSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'A software license that can be associated with an Amazon EC2 instance when launched (ex. a Microsoft Windows license).',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'LicenseId' => array(
                                'description' => 'The unique ID identifying the license.',
                                'type' => 'string',
                                'sentAs' => 'licenseId',
                            ),
                            'Type' => array(
                                'description' => 'The license type (ex. "Microsoft/Windows/Standard").',
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'Pool' => array(
                                'description' => 'The name of the pool in which the license is kept.',
                                'type' => 'string',
                                'sentAs' => 'pool',
                            ),
                            'Capacities' => array(
                                'description' => 'The capacities available for this license, indicating how many licenses are in use, how many are available, how many Amazon EC2 instances can be supported, etc.',
                                'type' => 'array',
                                'sentAs' => 'capacitySet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents the capacity that a license is able to support.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Capacity' => array(
                                            'description' => 'The number of licenses available.',
                                            'type' => 'numeric',
                                            'sentAs' => 'capacity',
                                        ),
                                        'InstanceCapacity' => array(
                                            'description' => 'The number of Amazon EC2 instances that can be supported with the license\'s capacity.',
                                            'type' => 'numeric',
                                            'sentAs' => 'instanceCapacity',
                                        ),
                                        'State' => array(
                                            'description' => 'The state of this license capacity, indicating whether the license is actively being used or not.',
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                        'EarliestAllowedDeactivationTime' => array(
                                            'description' => 'The earliest allowed time at which a license can be deactivated. Some licenses have time restrictions on when they can be activated and reactivated.',
                                            'type' => 'string',
                                            'sentAs' => 'earliestAllowedDeactivationTime',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the License.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNetworkAclsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NetworkAcls' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'networkAclSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'NetworkAclId' => array(
                                'type' => 'string',
                                'sentAs' => 'networkAclId',
                            ),
                            'VpcId' => array(
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'IsDefault' => array(
                                'type' => 'boolean',
                                'sentAs' => 'default',
                            ),
                            'Entries' => array(
                                'type' => 'array',
                                'sentAs' => 'entrySet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'RuleNumber' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'ruleNumber',
                                        ),
                                        'Protocol' => array(
                                            'type' => 'string',
                                            'sentAs' => 'protocol',
                                        ),
                                        'RuleAction' => array(
                                            'type' => 'string',
                                            'sentAs' => 'ruleAction',
                                        ),
                                        'Egress' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'egress',
                                        ),
                                        'CidrBlock' => array(
                                            'type' => 'string',
                                            'sentAs' => 'cidrBlock',
                                        ),
                                        'IcmpTypeCode' => array(
                                            'type' => 'object',
                                            'sentAs' => 'icmpTypeCode',
                                            'properties' => array(
                                                'Type' => array(
                                                    'description' => 'For the ICMP protocol, the ICMP type. A value of -1 is a wildcard meaning all types. Required if specifying icmp for the protocol.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'type',
                                                ),
                                                'Code' => array(
                                                    'description' => 'For the ICMP protocol, the ICMP code. A value of -1 is a wildcard meaning all codes. Required if specifying icmp for the protocol.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'code',
                                                ),
                                            ),
                                        ),
                                        'PortRange' => array(
                                            'type' => 'object',
                                            'sentAs' => 'portRange',
                                            'properties' => array(
                                                'From' => array(
                                                    'description' => 'The first port in the range. Required if specifying tcp or udp for the protocol.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'from',
                                                ),
                                                'To' => array(
                                                    'description' => 'The last port in the range. Required if specifying tcp or udp for the protocol.',
                                                    'type' => 'numeric',
                                                    'sentAs' => 'to',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Associations' => array(
                                'type' => 'array',
                                'sentAs' => 'associationSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'NetworkAclAssociationId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'networkAclAssociationId',
                                        ),
                                        'NetworkAclId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'networkAclId',
                                        ),
                                        'SubnetId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'subnetId',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNetworkInterfaceAttributeResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NetworkInterfaceId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'networkInterfaceId',
                ),
                'Description' => array(
                    'description' => 'String value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'description',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'String value',
                            'type' => 'string',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'SourceDestCheck' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'sourceDestCheck',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'Groups' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'groupSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'GroupName' => array(
                                'type' => 'string',
                                'sentAs' => 'groupName',
                            ),
                            'GroupId' => array(
                                'type' => 'string',
                                'sentAs' => 'groupId',
                            ),
                        ),
                    ),
                ),
                'Attachment' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'attachment',
                    'properties' => array(
                        'AttachmentId' => array(
                            'type' => 'string',
                            'sentAs' => 'attachmentId',
                        ),
                        'InstanceId' => array(
                            'type' => 'string',
                            'sentAs' => 'instanceId',
                        ),
                        'InstanceOwnerId' => array(
                            'type' => 'string',
                            'sentAs' => 'instanceOwnerId',
                        ),
                        'DeviceIndex' => array(
                            'type' => 'numeric',
                            'sentAs' => 'deviceIndex',
                        ),
                        'Status' => array(
                            'type' => 'string',
                            'sentAs' => 'status',
                        ),
                        'AttachTime' => array(
                            'type' => 'string',
                            'sentAs' => 'attachTime',
                        ),
                        'DeleteOnTermination' => array(
                            'type' => 'boolean',
                            'sentAs' => 'deleteOnTermination',
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNetworkInterfacesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NetworkInterfaces' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'networkInterfaceSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'NetworkInterfaceId' => array(
                                'type' => 'string',
                                'sentAs' => 'networkInterfaceId',
                            ),
                            'SubnetId' => array(
                                'type' => 'string',
                                'sentAs' => 'subnetId',
                            ),
                            'VpcId' => array(
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'AvailabilityZone' => array(
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'Description' => array(
                                'type' => 'string',
                                'sentAs' => 'description',
                            ),
                            'OwnerId' => array(
                                'type' => 'string',
                                'sentAs' => 'ownerId',
                            ),
                            'RequesterId' => array(
                                'type' => 'string',
                                'sentAs' => 'requesterId',
                            ),
                            'RequesterManaged' => array(
                                'type' => 'boolean',
                                'sentAs' => 'requesterManaged',
                            ),
                            'Status' => array(
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'MacAddress' => array(
                                'type' => 'string',
                                'sentAs' => 'macAddress',
                            ),
                            'PrivateIpAddress' => array(
                                'type' => 'string',
                                'sentAs' => 'privateIpAddress',
                            ),
                            'PrivateDnsName' => array(
                                'type' => 'string',
                                'sentAs' => 'privateDnsName',
                            ),
                            'SourceDestCheck' => array(
                                'type' => 'boolean',
                                'sentAs' => 'sourceDestCheck',
                            ),
                            'Groups' => array(
                                'type' => 'array',
                                'sentAs' => 'groupSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'GroupName' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupName',
                                        ),
                                        'GroupId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupId',
                                        ),
                                    ),
                                ),
                            ),
                            'Attachment' => array(
                                'type' => 'object',
                                'sentAs' => 'attachment',
                                'properties' => array(
                                    'AttachmentId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'attachmentId',
                                    ),
                                    'InstanceId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceId',
                                    ),
                                    'InstanceOwnerId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'instanceOwnerId',
                                    ),
                                    'DeviceIndex' => array(
                                        'type' => 'numeric',
                                        'sentAs' => 'deviceIndex',
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'AttachTime' => array(
                                        'type' => 'string',
                                        'sentAs' => 'attachTime',
                                    ),
                                    'DeleteOnTermination' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'deleteOnTermination',
                                    ),
                                ),
                            ),
                            'Association' => array(
                                'type' => 'object',
                                'sentAs' => 'association',
                                'properties' => array(
                                    'PublicIp' => array(
                                        'type' => 'string',
                                        'sentAs' => 'publicIp',
                                    ),
                                    'IpOwnerId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'ipOwnerId',
                                    ),
                                    'AllocationId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'allocationId',
                                    ),
                                    'AssociationId' => array(
                                        'type' => 'string',
                                        'sentAs' => 'associationId',
                                    ),
                                ),
                            ),
                            'TagSet' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'PrivateIpAddresses' => array(
                                'type' => 'array',
                                'sentAs' => 'privateIpAddressesSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'PrivateIpAddress' => array(
                                            'type' => 'string',
                                            'sentAs' => 'privateIpAddress',
                                        ),
                                        'PrivateDnsName' => array(
                                            'type' => 'string',
                                            'sentAs' => 'privateDnsName',
                                        ),
                                        'Primary' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'primary',
                                        ),
                                        'Association' => array(
                                            'type' => 'object',
                                            'sentAs' => 'association',
                                            'properties' => array(
                                                'PublicIp' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'publicIp',
                                                ),
                                                'IpOwnerId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'ipOwnerId',
                                                ),
                                                'AllocationId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'allocationId',
                                                ),
                                                'AssociationId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'associationId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribePlacementGroupsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PlacementGroups' => array(
                    'description' => 'Contains information about the specified PlacementGroups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'placementGroupSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a placement group into which multiple Amazon EC2 instances can be launched. A placement group ensures that Amazon EC2 instances are physically located close enough to support HPC features, such as higher IO network connections between instances in the group.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'GroupName' => array(
                                'description' => 'The name of this PlacementGroup.',
                                'type' => 'string',
                                'sentAs' => 'groupName',
                            ),
                            'Strategy' => array(
                                'description' => 'The strategy to use when allocating Amazon EC2 instances for the PlacementGroup.',
                                'type' => 'string',
                                'sentAs' => 'strategy',
                            ),
                            'State' => array(
                                'description' => 'The state of this PlacementGroup.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeRegionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Regions' => array(
                    'description' => 'The list of described Amazon EC2 regions.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'regionInfo',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents an Amazon EC2 region. EC2 regions are completely isolated from each other.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'RegionName' => array(
                                'description' => 'Name of the region.',
                                'type' => 'string',
                                'sentAs' => 'regionName',
                            ),
                            'Endpoint' => array(
                                'description' => 'Region service endpoint.',
                                'type' => 'string',
                                'sentAs' => 'regionEndpoint',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeReservedInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstances' => array(
                    'description' => 'The list of described Reserved Instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'A group of Amazon EC2 Reserved Instances purchased by this account.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservedInstancesId' => array(
                                'description' => 'The unique ID of the Reserved Instances purchase.',
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesId',
                            ),
                            'InstanceType' => array(
                                'description' => 'The instance type on which the Reserved Instances can be used.',
                                'type' => 'string',
                                'sentAs' => 'instanceType',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The Availability Zone in which the Reserved Instances can be used.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'Start' => array(
                                'description' => 'The date and time the Reserved Instances started.',
                                'type' => 'string',
                                'sentAs' => 'start',
                            ),
                            'Duration' => array(
                                'description' => 'The duration of the Reserved Instances, in seconds.',
                                'type' => 'numeric',
                                'sentAs' => 'duration',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The usage price of the Reserved Instances, per hour.',
                                'type' => 'numeric',
                                'sentAs' => 'usagePrice',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The purchase price of the Reserved Instances.',
                                'type' => 'numeric',
                                'sentAs' => 'fixedPrice',
                            ),
                            'InstanceCount' => array(
                                'description' => 'The number of Reserved Instances purchased.',
                                'type' => 'numeric',
                                'sentAs' => 'instanceCount',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The Reserved Instances product description (ex: Windows or Unix/Linux).',
                                'type' => 'string',
                                'sentAs' => 'productDescription',
                            ),
                            'State' => array(
                                'description' => 'The state of the Reserved Instances purchase.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the ReservedInstances.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceTenancy' => array(
                                'description' => 'The tenancy of the reserved instance (ex: default or dedicated).',
                                'type' => 'string',
                                'sentAs' => 'instanceTenancy',
                            ),
                            'CurrencyCode' => array(
                                'description' => 'The currency of the reserved instance. Specified using ISO 4217 standard (e.g., USD, JPY).',
                                'type' => 'string',
                                'sentAs' => 'currencyCode',
                            ),
                            'OfferingType' => array(
                                'description' => 'The Reserved Instance offering type.',
                                'type' => 'string',
                                'sentAs' => 'offeringType',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring charge tag assigned to the resource.',
                                'type' => 'array',
                                'sentAs' => 'recurringCharges',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents a usage charge for Amazon EC2 resources that repeats on a schedule.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Frequency' => array(
                                            'description' => 'The frequency of the recurring charge.',
                                            'type' => 'string',
                                            'sentAs' => 'frequency',
                                        ),
                                        'Amount' => array(
                                            'description' => 'The amount of the recurring charge.',
                                            'type' => 'numeric',
                                            'sentAs' => 'amount',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeReservedInstancesListingsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstancesListings' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesListingsSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservedInstancesListingId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesListingId',
                            ),
                            'ReservedInstancesId' => array(
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesId',
                            ),
                            'CreateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'createDate',
                            ),
                            'UpdateDate' => array(
                                'type' => 'string',
                                'sentAs' => 'updateDate',
                            ),
                            'Status' => array(
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'StatusMessage' => array(
                                'type' => 'string',
                                'sentAs' => 'statusMessage',
                            ),
                            'InstanceCounts' => array(
                                'type' => 'array',
                                'sentAs' => 'instanceCounts',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                        'InstanceCount' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'instanceCount',
                                        ),
                                    ),
                                ),
                            ),
                            'PriceSchedules' => array(
                                'type' => 'array',
                                'sentAs' => 'priceSchedules',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Term' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'term',
                                        ),
                                        'Price' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'price',
                                        ),
                                        'CurrencyCode' => array(
                                            'type' => 'string',
                                            'sentAs' => 'currencyCode',
                                        ),
                                        'Active' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'active',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'ClientToken' => array(
                                'type' => 'string',
                                'sentAs' => 'clientToken',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeReservedInstancesOfferingsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstancesOfferings' => array(
                    'description' => 'The list of described Reserved Instance offerings.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesOfferingsSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An active offer for Amazon EC2 Reserved Instances.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ReservedInstancesOfferingId' => array(
                                'description' => 'The unique ID of this Reserved Instances offering.',
                                'type' => 'string',
                                'sentAs' => 'reservedInstancesOfferingId',
                            ),
                            'InstanceType' => array(
                                'description' => 'The instance type on which the Reserved Instances can be used.',
                                'type' => 'string',
                                'sentAs' => 'instanceType',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The Availability Zone in which the Reserved Instances can be used.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'Duration' => array(
                                'description' => 'The duration of the Reserved Instance, in seconds.',
                                'type' => 'numeric',
                                'sentAs' => 'duration',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The usage price of the Reserved Instance, per hour.',
                                'type' => 'numeric',
                                'sentAs' => 'usagePrice',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The purchase price of the Reserved Instance.',
                                'type' => 'numeric',
                                'sentAs' => 'fixedPrice',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The Reserved Instances description (ex: Windows or Unix/Linux).',
                                'type' => 'string',
                                'sentAs' => 'productDescription',
                            ),
                            'InstanceTenancy' => array(
                                'description' => 'The tenancy of the reserved instance (ex: default or dedicated).',
                                'type' => 'string',
                                'sentAs' => 'instanceTenancy',
                            ),
                            'CurrencyCode' => array(
                                'description' => 'The currency of the reserved instance. Specified using ISO 4217 standard (e.g., USD, JPY).',
                                'type' => 'string',
                                'sentAs' => 'currencyCode',
                            ),
                            'OfferingType' => array(
                                'description' => 'The Reserved Instance offering type.',
                                'type' => 'string',
                                'sentAs' => 'offeringType',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring charge tag assigned to the resource.',
                                'type' => 'array',
                                'sentAs' => 'recurringCharges',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents a usage charge for Amazon EC2 resources that repeats on a schedule.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Frequency' => array(
                                            'description' => 'The frequency of the recurring charge.',
                                            'type' => 'string',
                                            'sentAs' => 'frequency',
                                        ),
                                        'Amount' => array(
                                            'description' => 'The amount of the recurring charge.',
                                            'type' => 'numeric',
                                            'sentAs' => 'amount',
                                        ),
                                    ),
                                ),
                            ),
                            'Marketplace' => array(
                                'type' => 'boolean',
                                'sentAs' => 'marketplace',
                            ),
                            'PricingDetails' => array(
                                'type' => 'array',
                                'sentAs' => 'pricingDetailsSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Price' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'price',
                                        ),
                                        'Count' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'count',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'nextToken',
                ),
            ),
        ),
        'DescribeRouteTablesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RouteTables' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'routeTableSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'RouteTableId' => array(
                                'type' => 'string',
                                'sentAs' => 'routeTableId',
                            ),
                            'VpcId' => array(
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'Routes' => array(
                                'type' => 'array',
                                'sentAs' => 'routeSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'DestinationCidrBlock' => array(
                                            'type' => 'string',
                                            'sentAs' => 'destinationCidrBlock',
                                        ),
                                        'GatewayId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'gatewayId',
                                        ),
                                        'InstanceId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'instanceId',
                                        ),
                                        'InstanceOwnerId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'instanceOwnerId',
                                        ),
                                        'NetworkInterfaceId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'networkInterfaceId',
                                        ),
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                    ),
                                ),
                            ),
                            'Associations' => array(
                                'type' => 'array',
                                'sentAs' => 'associationSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'RouteTableAssociationId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'routeTableAssociationId',
                                        ),
                                        'RouteTableId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'routeTableId',
                                        ),
                                        'SubnetId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'subnetId',
                                        ),
                                        'Main' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'main',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'PropagatingVgws' => array(
                                'type' => 'array',
                                'sentAs' => 'propagatingVgwSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'GatewayId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'gatewayId',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSecurityGroupsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SecurityGroups' => array(
                    'description' => 'The list of described Amazon EC2 security groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'securityGroupInfo',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An Amazon EC2 security group, describing how EC2 instances in this group can receive network traffic.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'OwnerId' => array(
                                'description' => 'The AWS Access Key ID of the owner of the security group.',
                                'type' => 'string',
                                'sentAs' => 'ownerId',
                            ),
                            'GroupName' => array(
                                'description' => 'The name of this security group.',
                                'type' => 'string',
                                'sentAs' => 'groupName',
                            ),
                            'GroupId' => array(
                                'type' => 'string',
                                'sentAs' => 'groupId',
                            ),
                            'Description' => array(
                                'description' => 'The description of this security group.',
                                'type' => 'string',
                                'sentAs' => 'groupDescription',
                            ),
                            'IpPermissions' => array(
                                'description' => 'The permissions enabled for this security group.',
                                'type' => 'array',
                                'sentAs' => 'ipPermissions',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'IpProtocol' => array(
                                            'description' => 'The IP protocol of this permission.',
                                            'type' => 'string',
                                            'sentAs' => 'ipProtocol',
                                        ),
                                        'FromPort' => array(
                                            'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                            'type' => 'numeric',
                                            'sentAs' => 'fromPort',
                                        ),
                                        'ToPort' => array(
                                            'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                            'type' => 'numeric',
                                            'sentAs' => 'toPort',
                                        ),
                                        'UserIdGroupPairs' => array(
                                            'description' => 'The list of AWS user IDs and groups included in this permission.',
                                            'type' => 'array',
                                            'sentAs' => 'groups',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'UserId' => array(
                                                        'description' => 'The AWS user ID of an account.',
                                                        'type' => 'string',
                                                        'sentAs' => 'userId',
                                                    ),
                                                    'GroupName' => array(
                                                        'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                                        'type' => 'string',
                                                        'sentAs' => 'groupName',
                                                    ),
                                                    'GroupId' => array(
                                                        'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                                        'type' => 'string',
                                                        'sentAs' => 'groupId',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'IpRanges' => array(
                                            'description' => 'The list of CIDR IP ranges included in this permission.',
                                            'type' => 'array',
                                            'sentAs' => 'ipRanges',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'Contains a list of CIRD IP ranges.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'CidrIp' => array(
                                                        'description' => 'The list of CIDR IP ranges.',
                                                        'type' => 'string',
                                                        'sentAs' => 'cidrIp',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'IpPermissionsEgress' => array(
                                'type' => 'array',
                                'sentAs' => 'ipPermissionsEgress',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'An IP permission describing allowed incoming IP traffic to an Amazon EC2 security group.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'IpProtocol' => array(
                                            'description' => 'The IP protocol of this permission.',
                                            'type' => 'string',
                                            'sentAs' => 'ipProtocol',
                                        ),
                                        'FromPort' => array(
                                            'description' => 'Start of port range for the TCP and UDP protocols, or an ICMP type number. An ICMP type number of -1 indicates a wildcard (i.e., any ICMP type number).',
                                            'type' => 'numeric',
                                            'sentAs' => 'fromPort',
                                        ),
                                        'ToPort' => array(
                                            'description' => 'End of port range for the TCP and UDP protocols, or an ICMP code. An ICMP code of -1 indicates a wildcard (i.e., any ICMP code).',
                                            'type' => 'numeric',
                                            'sentAs' => 'toPort',
                                        ),
                                        'UserIdGroupPairs' => array(
                                            'description' => 'The list of AWS user IDs and groups included in this permission.',
                                            'type' => 'array',
                                            'sentAs' => 'groups',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'An AWS user ID identifiying an AWS account, and the name of a security group within that account.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'UserId' => array(
                                                        'description' => 'The AWS user ID of an account.',
                                                        'type' => 'string',
                                                        'sentAs' => 'userId',
                                                    ),
                                                    'GroupName' => array(
                                                        'description' => 'Name of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                                        'type' => 'string',
                                                        'sentAs' => 'groupName',
                                                    ),
                                                    'GroupId' => array(
                                                        'description' => 'ID of the security group in the specified AWS account. Cannot be used when specifying a CIDR IP address range.',
                                                        'type' => 'string',
                                                        'sentAs' => 'groupId',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'IpRanges' => array(
                                            'description' => 'The list of CIDR IP ranges included in this permission.',
                                            'type' => 'array',
                                            'sentAs' => 'ipRanges',
                                            'items' => array(
                                                'name' => 'item',
                                                'description' => 'Contains a list of CIRD IP ranges.',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'CidrIp' => array(
                                                        'description' => 'The list of CIDR IP ranges.',
                                                        'type' => 'string',
                                                        'sentAs' => 'cidrIp',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'VpcId' => array(
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'Tags' => array(
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSnapshotAttributeResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SnapshotId' => array(
                    'description' => 'The ID of the snapshot whose attribute is being described.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'snapshotId',
                ),
                'CreateVolumePermissions' => array(
                    'description' => 'The list of permissions describing who can create a volume from the associated EBS snapshot.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'createVolumePermission',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Describes a permission allowing either a user or group to create a new EBS volume from a snapshot.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'UserId' => array(
                                'description' => 'The user ID of the user that can create volumes from the snapshot.',
                                'type' => 'string',
                                'sentAs' => 'userId',
                            ),
                            'Group' => array(
                                'description' => 'The group that is allowed to create volumes from the snapshot (currently supports "all").',
                                'type' => 'string',
                                'sentAs' => 'group',
                            ),
                        ),
                    ),
                ),
                'ProductCodes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'productCodes',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An AWS DevPay product code.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ProductCodeId' => array(
                                'description' => 'The unique ID of an AWS DevPay product code.',
                                'type' => 'string',
                                'sentAs' => 'productCode',
                            ),
                            'ProductCodeType' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSnapshotsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Snapshots' => array(
                    'description' => 'The list of described EBS snapshots.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'snapshotSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a snapshot of an Amazon EC2 EBS volume.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'SnapshotId' => array(
                                'description' => 'The unique ID of this snapshot.',
                                'type' => 'string',
                                'sentAs' => 'snapshotId',
                            ),
                            'VolumeId' => array(
                                'description' => 'The ID of the volume from which this snapshot was created.',
                                'type' => 'string',
                                'sentAs' => 'volumeId',
                            ),
                            'State' => array(
                                'description' => 'Snapshot state (e.g., pending, completed, or error).',
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'StartTime' => array(
                                'description' => 'Time stamp when the snapshot was initiated.',
                                'type' => 'string',
                                'sentAs' => 'startTime',
                            ),
                            'Progress' => array(
                                'description' => 'The progress of the snapshot, in percentage.',
                                'type' => 'string',
                                'sentAs' => 'progress',
                            ),
                            'OwnerId' => array(
                                'description' => 'AWS Access Key ID of the user who owns the snapshot.',
                                'type' => 'string',
                                'sentAs' => 'ownerId',
                            ),
                            'Description' => array(
                                'description' => 'Description of the snapshot.',
                                'type' => 'string',
                                'sentAs' => 'description',
                            ),
                            'VolumeSize' => array(
                                'description' => 'The size of the volume, in gigabytes.',
                                'type' => 'numeric',
                                'sentAs' => 'volumeSize',
                            ),
                            'OwnerAlias' => array(
                                'description' => 'The AWS account alias (e.g., "amazon", "redhat", "self", etc.) or AWS account ID that owns the AMI.',
                                'type' => 'string',
                                'sentAs' => 'ownerAlias',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Snapshot.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSpotDatafeedSubscriptionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SpotDatafeedSubscription' => array(
                    'description' => 'The Spot Instance datafeed subscription.',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'spotDatafeedSubscription',
                    'properties' => array(
                        'OwnerId' => array(
                            'description' => 'Specifies the AWS account ID of the account.',
                            'type' => 'string',
                            'sentAs' => 'ownerId',
                        ),
                        'Bucket' => array(
                            'description' => 'Specifies the Amazon S3 bucket where the Spot Instance data feed is located.',
                            'type' => 'string',
                            'sentAs' => 'bucket',
                        ),
                        'Prefix' => array(
                            'description' => 'Contains the prefix that is prepended to data feed files.',
                            'type' => 'string',
                            'sentAs' => 'prefix',
                        ),
                        'State' => array(
                            'description' => 'Specifies the state of the Spot Instance request.',
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'Fault' => array(
                            'description' => 'Specifies a fault code for the Spot Instance request, if present.',
                            'type' => 'object',
                            'sentAs' => 'fault',
                            'properties' => array(
                                'Code' => array(
                                    'type' => 'string',
                                    'sentAs' => 'code',
                                ),
                                'Message' => array(
                                    'type' => 'string',
                                    'sentAs' => 'message',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSpotInstanceRequestsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SpotInstanceRequests' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'spotInstanceRequestSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'SpotInstanceRequestId' => array(
                                'type' => 'string',
                                'sentAs' => 'spotInstanceRequestId',
                            ),
                            'SpotPrice' => array(
                                'type' => 'string',
                                'sentAs' => 'spotPrice',
                            ),
                            'Type' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'Fault' => array(
                                'type' => 'object',
                                'sentAs' => 'fault',
                                'properties' => array(
                                    'Code' => array(
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'Message' => array(
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'type' => 'object',
                                'sentAs' => 'status',
                                'properties' => array(
                                    'Code' => array(
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'UpdateTime' => array(
                                        'type' => 'string',
                                        'sentAs' => 'updateTime',
                                    ),
                                    'Message' => array(
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'ValidFrom' => array(
                                'type' => 'string',
                                'sentAs' => 'validFrom',
                            ),
                            'ValidUntil' => array(
                                'type' => 'string',
                                'sentAs' => 'validUntil',
                            ),
                            'LaunchGroup' => array(
                                'type' => 'string',
                                'sentAs' => 'launchGroup',
                            ),
                            'AvailabilityZoneGroup' => array(
                                'type' => 'string',
                                'sentAs' => 'availabilityZoneGroup',
                            ),
                            'LaunchSpecification' => array(
                                'description' => 'The LaunchSpecificationType data type.',
                                'type' => 'object',
                                'sentAs' => 'launchSpecification',
                                'properties' => array(
                                    'ImageId' => array(
                                        'description' => 'The AMI ID.',
                                        'type' => 'string',
                                        'sentAs' => 'imageId',
                                    ),
                                    'KeyName' => array(
                                        'description' => 'The name of the key pair.',
                                        'type' => 'string',
                                        'sentAs' => 'keyName',
                                    ),
                                    'SecurityGroups' => array(
                                        'type' => 'array',
                                        'sentAs' => 'groupSet',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'GroupName' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'groupName',
                                                ),
                                                'GroupId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'groupId',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'UserData' => array(
                                        'description' => 'Optional data, specific to a user\'s application, to provide in the launch request. All instances that collectively comprise the launch request have access to this data. User data is never returned through API responses.',
                                        'type' => 'string',
                                        'sentAs' => 'userData',
                                    ),
                                    'AddressingType' => array(
                                        'description' => 'Deprecated.',
                                        'type' => 'string',
                                        'sentAs' => 'addressingType',
                                    ),
                                    'InstanceType' => array(
                                        'description' => 'Specifies the instance type.',
                                        'type' => 'string',
                                        'sentAs' => 'instanceType',
                                    ),
                                    'Placement' => array(
                                        'description' => 'Defines a placement item.',
                                        'type' => 'object',
                                        'sentAs' => 'placement',
                                        'properties' => array(
                                            'AvailabilityZone' => array(
                                                'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                                'type' => 'string',
                                                'sentAs' => 'availabilityZone',
                                            ),
                                            'GroupName' => array(
                                                'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                                'type' => 'string',
                                                'sentAs' => 'groupName',
                                            ),
                                        ),
                                    ),
                                    'KernelId' => array(
                                        'description' => 'Specifies the ID of the kernel to select.',
                                        'type' => 'string',
                                        'sentAs' => 'kernelId',
                                    ),
                                    'RamdiskId' => array(
                                        'description' => 'Specifies the ID of the RAM disk to select. Some kernels require additional drivers at launch. Check the kernel requirements for information on whether or not you need to specify a RAM disk and search for the kernel ID.',
                                        'type' => 'string',
                                        'sentAs' => 'ramdiskId',
                                    ),
                                    'BlockDeviceMappings' => array(
                                        'description' => 'Specifies how block devices are exposed to the instance. Each mapping is made up of a virtualName and a deviceName.',
                                        'type' => 'array',
                                        'sentAs' => 'blockDeviceMapping',
                                        'items' => array(
                                            'name' => 'item',
                                            'description' => 'The BlockDeviceMappingItemType data type.',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'VirtualName' => array(
                                                    'description' => 'Specifies the virtual device name.',
                                                    'type' => 'string',
                                                    'sentAs' => 'virtualName',
                                                ),
                                                'DeviceName' => array(
                                                    'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                                    'type' => 'string',
                                                    'sentAs' => 'deviceName',
                                                ),
                                                'Ebs' => array(
                                                    'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                                    'type' => 'object',
                                                    'sentAs' => 'ebs',
                                                    'properties' => array(
                                                        'SnapshotId' => array(
                                                            'description' => 'The ID of the snapshot from which the volume will be created.',
                                                            'type' => 'string',
                                                            'sentAs' => 'snapshotId',
                                                        ),
                                                        'VolumeSize' => array(
                                                            'description' => 'The size of the volume, in gigabytes.',
                                                            'type' => 'numeric',
                                                            'sentAs' => 'volumeSize',
                                                        ),
                                                        'DeleteOnTermination' => array(
                                                            'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                            'type' => 'boolean',
                                                            'sentAs' => 'deleteOnTermination',
                                                        ),
                                                        'VolumeType' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'volumeType',
                                                        ),
                                                        'Iops' => array(
                                                            'type' => 'numeric',
                                                            'sentAs' => 'iops',
                                                        ),
                                                    ),
                                                ),
                                                'NoDevice' => array(
                                                    'description' => 'Specifies the device name to suppress during instance launch.',
                                                    'type' => 'string',
                                                    'sentAs' => 'noDevice',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'MonitoringEnabled' => array(
                                        'description' => 'Enables monitoring for the instance.',
                                        'type' => 'boolean',
                                        'sentAs' => 'monitoringEnabled',
                                    ),
                                    'SubnetId' => array(
                                        'description' => 'Specifies the Amazon VPC subnet ID within which to launch the instance(s) for Amazon Virtual Private Cloud.',
                                        'type' => 'string',
                                        'sentAs' => 'subnetId',
                                    ),
                                    'NetworkInterfaces' => array(
                                        'type' => 'array',
                                        'sentAs' => 'networkInterfaceSet',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'NetworkInterfaceId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'networkInterfaceId',
                                                ),
                                                'DeviceIndex' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'deviceIndex',
                                                ),
                                                'SubnetId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'subnetId',
                                                ),
                                                'Description' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'description',
                                                ),
                                                'PrivateIpAddress' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'privateIpAddress',
                                                ),
                                                'Groups' => array(
                                                    'type' => 'array',
                                                    'sentAs' => 'SecurityGroupId',
                                                    'items' => array(
                                                        'name' => 'SecurityGroupId',
                                                        'type' => 'string',
                                                        'sentAs' => 'SecurityGroupId',
                                                    ),
                                                ),
                                                'DeleteOnTermination' => array(
                                                    'type' => 'boolean',
                                                    'sentAs' => 'deleteOnTermination',
                                                ),
                                                'PrivateIpAddresses' => array(
                                                    'type' => 'array',
                                                    'sentAs' => 'privateIpAddressesSet',
                                                    'items' => array(
                                                        'name' => 'item',
                                                        'type' => 'object',
                                                        'sentAs' => 'item',
                                                        'properties' => array(
                                                            'PrivateIpAddress' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'privateIpAddress',
                                                            ),
                                                            'Primary' => array(
                                                                'type' => 'boolean',
                                                                'sentAs' => 'primary',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                'SecondaryPrivateIpAddressCount' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'secondaryPrivateIpAddressCount',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IamInstanceProfile' => array(
                                        'type' => 'object',
                                        'sentAs' => 'iamInstanceProfile',
                                        'properties' => array(
                                            'Arn' => array(
                                                'type' => 'string',
                                                'sentAs' => 'arn',
                                            ),
                                            'Name' => array(
                                                'type' => 'string',
                                                'sentAs' => 'name',
                                            ),
                                        ),
                                    ),
                                    'EbsOptimized' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'ebsOptimized',
                                    ),
                                ),
                            ),
                            'InstanceId' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'CreateTime' => array(
                                'type' => 'string',
                                'sentAs' => 'createTime',
                            ),
                            'ProductDescription' => array(
                                'type' => 'string',
                                'sentAs' => 'productDescription',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for this spot instance request.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'LaunchedAvailabilityZone' => array(
                                'description' => 'The Availability Zone in which the bid is launched.',
                                'type' => 'string',
                                'sentAs' => 'launchedAvailabilityZone',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeSpotPriceHistoryResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SpotPriceHistory' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'spotPriceHistorySet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceType' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceType',
                            ),
                            'ProductDescription' => array(
                                'type' => 'string',
                                'sentAs' => 'productDescription',
                            ),
                            'SpotPrice' => array(
                                'type' => 'string',
                                'sentAs' => 'spotPrice',
                            ),
                            'Timestamp' => array(
                                'type' => 'string',
                                'sentAs' => 'timestamp',
                            ),
                            'AvailabilityZone' => array(
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'The string marking the next set of results returned. Displays empty if there are no more results to be returned.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'nextToken',
                ),
            ),
        ),
        'DescribeSubnetsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Subnets' => array(
                    'description' => 'Contains a set of one or more Subnet instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'subnetSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The Subnet data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'SubnetId' => array(
                                'description' => 'Specifies the ID of the subnet.',
                                'type' => 'string',
                                'sentAs' => 'subnetId',
                            ),
                            'State' => array(
                                'description' => 'Describes the current state of the subnet. The state of the subnet may be either pending or available.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'VpcId' => array(
                                'description' => 'Contains the ID of the VPC the subnet is in.',
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'CidrBlock' => array(
                                'description' => 'Specifies the CIDR block assigned to the subnet.',
                                'type' => 'string',
                                'sentAs' => 'cidrBlock',
                            ),
                            'AvailableIpAddressCount' => array(
                                'description' => 'Specifies the number of unused IP addresses in the subnet.',
                                'type' => 'numeric',
                                'sentAs' => 'availableIpAddressCount',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'Specifies the Availability Zone the subnet is in.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'DefaultForAz' => array(
                                'type' => 'boolean',
                                'sentAs' => 'defaultForAz',
                            ),
                            'MapPublicIpOnLaunch' => array(
                                'type' => 'boolean',
                                'sentAs' => 'mapPublicIpOnLaunch',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Subnet.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTagsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Tags' => array(
                    'description' => 'A list of the tags for the specified resources.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'tagSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Provides information about an Amazon EC2 resource Tag.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ResourceId' => array(
                                'description' => 'The resource ID for the tag.',
                                'type' => 'string',
                                'sentAs' => 'resourceId',
                            ),
                            'ResourceType' => array(
                                'description' => 'The type of resource identified by the associated resource ID (ex: instance, AMI, EBS volume, etc).',
                                'type' => 'string',
                                'sentAs' => 'resourceType',
                            ),
                            'Key' => array(
                                'description' => 'The tag\'s key.',
                                'type' => 'string',
                                'sentAs' => 'key',
                            ),
                            'Value' => array(
                                'description' => 'The tag\'s value.',
                                'type' => 'string',
                                'sentAs' => 'value',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVolumeAttributeResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'volumeId',
                ),
                'AutoEnableIO' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'autoEnableIO',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'ProductCodes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'productCodes',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'An AWS DevPay product code.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'ProductCodeId' => array(
                                'description' => 'The unique ID of an AWS DevPay product code.',
                                'type' => 'string',
                                'sentAs' => 'productCode',
                            ),
                            'ProductCodeType' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVolumeStatusResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VolumeStatuses' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'volumeStatusSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VolumeId' => array(
                                'type' => 'string',
                                'sentAs' => 'volumeId',
                            ),
                            'AvailabilityZone' => array(
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'VolumeStatus' => array(
                                'type' => 'object',
                                'sentAs' => 'volumeStatus',
                                'properties' => array(
                                    'Status' => array(
                                        'type' => 'string',
                                        'sentAs' => 'status',
                                    ),
                                    'Details' => array(
                                        'type' => 'array',
                                        'sentAs' => 'details',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'Name' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'name',
                                                ),
                                                'Status' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Events' => array(
                                'type' => 'array',
                                'sentAs' => 'eventsSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'EventType' => array(
                                            'type' => 'string',
                                            'sentAs' => 'eventType',
                                        ),
                                        'Description' => array(
                                            'type' => 'string',
                                            'sentAs' => 'description',
                                        ),
                                        'NotBefore' => array(
                                            'type' => 'string',
                                            'sentAs' => 'notBefore',
                                        ),
                                        'NotAfter' => array(
                                            'type' => 'string',
                                            'sentAs' => 'notAfter',
                                        ),
                                        'EventId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'eventId',
                                        ),
                                    ),
                                ),
                            ),
                            'Actions' => array(
                                'type' => 'array',
                                'sentAs' => 'actionsSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Code' => array(
                                            'type' => 'string',
                                            'sentAs' => 'code',
                                        ),
                                        'Description' => array(
                                            'type' => 'string',
                                            'sentAs' => 'description',
                                        ),
                                        'EventType' => array(
                                            'type' => 'string',
                                            'sentAs' => 'eventType',
                                        ),
                                        'EventId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'eventId',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'nextToken',
                ),
            ),
        ),
        'DescribeVolumesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Volumes' => array(
                    'description' => 'The list of described EBS volumes.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'volumeSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents an Amazon Elastic Block Storage (EBS) volume.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VolumeId' => array(
                                'description' => 'The unique ID of this volume.',
                                'type' => 'string',
                                'sentAs' => 'volumeId',
                            ),
                            'Size' => array(
                                'description' => 'The size of this volume, in gigabytes.',
                                'type' => 'numeric',
                                'sentAs' => 'size',
                            ),
                            'SnapshotId' => array(
                                'description' => 'Optional snapshot from which this volume was created.',
                                'type' => 'string',
                                'sentAs' => 'snapshotId',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'Availability zone in which this volume was created.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'State' => array(
                                'description' => 'State of this volume (e.g., creating, available).',
                                'type' => 'string',
                                'sentAs' => 'status',
                            ),
                            'CreateTime' => array(
                                'description' => 'Timestamp when volume creation was initiated.',
                                'type' => 'string',
                                'sentAs' => 'createTime',
                            ),
                            'Attachments' => array(
                                'description' => 'Information on what this volume is attached to.',
                                'type' => 'array',
                                'sentAs' => 'attachmentSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Specifies the details of a how an EC2 EBS volume is attached to an instance.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'VolumeId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'volumeId',
                                        ),
                                        'InstanceId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'instanceId',
                                        ),
                                        'Device' => array(
                                            'description' => 'How the device is exposed to the instance (e.g., /dev/sdh).',
                                            'type' => 'string',
                                            'sentAs' => 'device',
                                        ),
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'status',
                                        ),
                                        'AttachTime' => array(
                                            'description' => 'Timestamp when this attachment initiated.',
                                            'type' => 'string',
                                            'sentAs' => 'attachTime',
                                        ),
                                        'DeleteOnTermination' => array(
                                            'description' => '` Whether this volume will be deleted or not when the associated instance is terminated.',
                                            'type' => 'boolean',
                                            'sentAs' => 'deleteOnTermination',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Volume.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'VolumeType' => array(
                                'type' => 'string',
                                'sentAs' => 'volumeType',
                            ),
                            'Iops' => array(
                                'type' => 'numeric',
                                'sentAs' => 'iops',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpcAttributeResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpcId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'vpcId',
                ),
                'EnableDnsSupport' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'enableDnsSupport',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
                'EnableDnsHostnames' => array(
                    'description' => 'Boolean value',
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'enableDnsHostnames',
                    'properties' => array(
                        'Value' => array(
                            'description' => 'Boolean value',
                            'type' => 'boolean',
                            'sentAs' => 'value',
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpcsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Vpcs' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'vpcSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The Vpc data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VpcId' => array(
                                'description' => 'Specifies the ID of the VPC.',
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'State' => array(
                                'description' => 'Describes the current state of the VPC. The state of the subnet may be either pending or available.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'CidrBlock' => array(
                                'description' => 'Specifies the CIDR block the VPC covers.',
                                'type' => 'string',
                                'sentAs' => 'cidrBlock',
                            ),
                            'DhcpOptionsId' => array(
                                'description' => 'Specifies the ID of the set of DHCP options associated with the VPC. Contains a value of default if the default options are associated with the VPC.',
                                'type' => 'string',
                                'sentAs' => 'dhcpOptionsId',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the VPC.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceTenancy' => array(
                                'description' => 'The allowed tenancy of instances launched into the VPC.',
                                'type' => 'string',
                                'sentAs' => 'instanceTenancy',
                            ),
                            'IsDefault' => array(
                                'type' => 'boolean',
                                'sentAs' => 'isDefault',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpnConnectionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpnConnections' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'vpnConnectionSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The VpnConnection data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VpnConnectionId' => array(
                                'description' => 'Specifies the ID of the VPN gateway at the VPC end of the VPN connection.',
                                'type' => 'string',
                                'sentAs' => 'vpnConnectionId',
                            ),
                            'State' => array(
                                'description' => 'Describes the current state of the VPN connection. Valid values are pending, available, deleting, and deleted.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'CustomerGatewayConfiguration' => array(
                                'description' => 'Contains configuration information in the native XML format for the VPN connection\'s customer gateway.',
                                'type' => 'string',
                                'sentAs' => 'customerGatewayConfiguration',
                            ),
                            'Type' => array(
                                'description' => 'Specifies the type of VPN connection.',
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'CustomerGatewayId' => array(
                                'description' => 'Specifies ID of the customer gateway at the end of the VPN connection.',
                                'type' => 'string',
                                'sentAs' => 'customerGatewayId',
                            ),
                            'VpnGatewayId' => array(
                                'description' => 'Specfies the ID of the VPN gateway at the VPC end of the VPN connection.',
                                'type' => 'string',
                                'sentAs' => 'vpnGatewayId',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the VpnConnection.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'VgwTelemetry' => array(
                                'type' => 'array',
                                'sentAs' => 'vgwTelemetry',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'OutsideIpAddress' => array(
                                            'type' => 'string',
                                            'sentAs' => 'outsideIpAddress',
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                            'sentAs' => 'status',
                                        ),
                                        'LastStatusChange' => array(
                                            'type' => 'string',
                                            'sentAs' => 'lastStatusChange',
                                        ),
                                        'StatusMessage' => array(
                                            'type' => 'string',
                                            'sentAs' => 'statusMessage',
                                        ),
                                        'AcceptedRouteCount' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'acceptedRouteCount',
                                        ),
                                    ),
                                ),
                            ),
                            'Options' => array(
                                'type' => 'object',
                                'sentAs' => 'options',
                                'properties' => array(
                                    'StaticRoutesOnly' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'staticRoutesOnly',
                                    ),
                                ),
                            ),
                            'Routes' => array(
                                'type' => 'array',
                                'sentAs' => 'routes',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'DestinationCidrBlock' => array(
                                            'type' => 'string',
                                            'sentAs' => 'destinationCidrBlock',
                                        ),
                                        'Source' => array(
                                            'type' => 'string',
                                            'sentAs' => 'source',
                                        ),
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVpnGatewaysResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'VpnGateways' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'vpnGatewaySet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'The VpnGateway data type.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'VpnGatewayId' => array(
                                'description' => 'Specifies the ID of the VPN gateway.',
                                'type' => 'string',
                                'sentAs' => 'vpnGatewayId',
                            ),
                            'State' => array(
                                'description' => 'Describes the current state of the VPN gateway. Valid values are pending, available, deleting, and deleted.',
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'Type' => array(
                                'description' => 'Specifies the type of VPN connection the VPN gateway supports.',
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'Specifies the Availability Zone where the VPN gateway was created.',
                                'type' => 'string',
                                'sentAs' => 'availabilityZone',
                            ),
                            'VpcAttachments' => array(
                                'description' => 'Contains information about the VPCs attached to the VPN gateway.',
                                'type' => 'array',
                                'sentAs' => 'attachments',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'VpcId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'vpcId',
                                        ),
                                        'State' => array(
                                            'type' => 'string',
                                            'sentAs' => 'state',
                                        ),
                                    ),
                                ),
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the VpnGateway.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetConsoleOutputResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceId' => array(
                    'description' => 'The ID of the instance whose console output was requested.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'instanceId',
                ),
                'Timestamp' => array(
                    'description' => 'The time the output was last updated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'timestamp',
                ),
                'Output' => array(
                    'description' => 'The console output, Base64 encoded.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'output',
                ),
            ),
        ),
        'GetPasswordDataResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceId' => array(
                    'description' => 'The ID of the instance whose Windows administrator password was requested.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'instanceId',
                ),
                'Timestamp' => array(
                    'description' => 'The time the data was last updated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'timestamp',
                ),
                'PasswordData' => array(
                    'description' => 'The Windows administrator password of the specified instance.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'passwordData',
                ),
            ),
        ),
        'ImportInstanceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ConversionTask' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'conversionTask',
                    'properties' => array(
                        'ConversionTaskId' => array(
                            'type' => 'string',
                            'sentAs' => 'conversionTaskId',
                        ),
                        'ExpirationTime' => array(
                            'type' => 'string',
                            'sentAs' => 'expirationTime',
                        ),
                        'ImportInstance' => array(
                            'type' => 'object',
                            'sentAs' => 'importInstance',
                            'properties' => array(
                                'Volumes' => array(
                                    'type' => 'array',
                                    'sentAs' => 'volumes',
                                    'items' => array(
                                        'name' => 'item',
                                        'type' => 'object',
                                        'sentAs' => 'item',
                                        'properties' => array(
                                            'BytesConverted' => array(
                                                'type' => 'numeric',
                                                'sentAs' => 'bytesConverted',
                                            ),
                                            'AvailabilityZone' => array(
                                                'type' => 'string',
                                                'sentAs' => 'availabilityZone',
                                            ),
                                            'Image' => array(
                                                'type' => 'object',
                                                'sentAs' => 'image',
                                                'properties' => array(
                                                    'Format' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'format',
                                                    ),
                                                    'Size' => array(
                                                        'type' => 'numeric',
                                                        'sentAs' => 'size',
                                                    ),
                                                    'ImportManifestUrl' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'importManifestUrl',
                                                    ),
                                                    'Checksum' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'checksum',
                                                    ),
                                                ),
                                            ),
                                            'Volume' => array(
                                                'type' => 'object',
                                                'sentAs' => 'volume',
                                                'properties' => array(
                                                    'Size' => array(
                                                        'type' => 'numeric',
                                                        'sentAs' => 'size',
                                                    ),
                                                    'Id' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'id',
                                                    ),
                                                ),
                                            ),
                                            'Status' => array(
                                                'type' => 'string',
                                                'sentAs' => 'status',
                                            ),
                                            'StatusMessage' => array(
                                                'type' => 'string',
                                                'sentAs' => 'statusMessage',
                                            ),
                                            'Description' => array(
                                                'type' => 'string',
                                                'sentAs' => 'description',
                                            ),
                                        ),
                                    ),
                                ),
                                'InstanceId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'instanceId',
                                ),
                                'Platform' => array(
                                    'type' => 'string',
                                    'sentAs' => 'platform',
                                ),
                                'Description' => array(
                                    'type' => 'string',
                                    'sentAs' => 'description',
                                ),
                            ),
                        ),
                        'ImportVolume' => array(
                            'type' => 'object',
                            'sentAs' => 'importVolume',
                            'properties' => array(
                                'BytesConverted' => array(
                                    'type' => 'numeric',
                                    'sentAs' => 'bytesConverted',
                                ),
                                'AvailabilityZone' => array(
                                    'type' => 'string',
                                    'sentAs' => 'availabilityZone',
                                ),
                                'Description' => array(
                                    'type' => 'string',
                                    'sentAs' => 'description',
                                ),
                                'Image' => array(
                                    'type' => 'object',
                                    'sentAs' => 'image',
                                    'properties' => array(
                                        'Format' => array(
                                            'type' => 'string',
                                            'sentAs' => 'format',
                                        ),
                                        'Size' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'size',
                                        ),
                                        'ImportManifestUrl' => array(
                                            'type' => 'string',
                                            'sentAs' => 'importManifestUrl',
                                        ),
                                        'Checksum' => array(
                                            'type' => 'string',
                                            'sentAs' => 'checksum',
                                        ),
                                    ),
                                ),
                                'Volume' => array(
                                    'type' => 'object',
                                    'sentAs' => 'volume',
                                    'properties' => array(
                                        'Size' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'size',
                                        ),
                                        'Id' => array(
                                            'type' => 'string',
                                            'sentAs' => 'id',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'State' => array(
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'StatusMessage' => array(
                            'type' => 'string',
                            'sentAs' => 'statusMessage',
                        ),
                        'Tags' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ImportKeyPairResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'KeyName' => array(
                    'description' => 'The specified unique key pair name.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'keyName',
                ),
                'KeyFingerprint' => array(
                    'description' => 'The MD5 public key fingerprint as specified in section 4 of RFC4716 .',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'keyFingerprint',
                ),
            ),
        ),
        'ImportVolumeResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ConversionTask' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'sentAs' => 'conversionTask',
                    'properties' => array(
                        'ConversionTaskId' => array(
                            'type' => 'string',
                            'sentAs' => 'conversionTaskId',
                        ),
                        'ExpirationTime' => array(
                            'type' => 'string',
                            'sentAs' => 'expirationTime',
                        ),
                        'ImportInstance' => array(
                            'type' => 'object',
                            'sentAs' => 'importInstance',
                            'properties' => array(
                                'Volumes' => array(
                                    'type' => 'array',
                                    'sentAs' => 'volumes',
                                    'items' => array(
                                        'name' => 'item',
                                        'type' => 'object',
                                        'sentAs' => 'item',
                                        'properties' => array(
                                            'BytesConverted' => array(
                                                'type' => 'numeric',
                                                'sentAs' => 'bytesConverted',
                                            ),
                                            'AvailabilityZone' => array(
                                                'type' => 'string',
                                                'sentAs' => 'availabilityZone',
                                            ),
                                            'Image' => array(
                                                'type' => 'object',
                                                'sentAs' => 'image',
                                                'properties' => array(
                                                    'Format' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'format',
                                                    ),
                                                    'Size' => array(
                                                        'type' => 'numeric',
                                                        'sentAs' => 'size',
                                                    ),
                                                    'ImportManifestUrl' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'importManifestUrl',
                                                    ),
                                                    'Checksum' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'checksum',
                                                    ),
                                                ),
                                            ),
                                            'Volume' => array(
                                                'type' => 'object',
                                                'sentAs' => 'volume',
                                                'properties' => array(
                                                    'Size' => array(
                                                        'type' => 'numeric',
                                                        'sentAs' => 'size',
                                                    ),
                                                    'Id' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'id',
                                                    ),
                                                ),
                                            ),
                                            'Status' => array(
                                                'type' => 'string',
                                                'sentAs' => 'status',
                                            ),
                                            'StatusMessage' => array(
                                                'type' => 'string',
                                                'sentAs' => 'statusMessage',
                                            ),
                                            'Description' => array(
                                                'type' => 'string',
                                                'sentAs' => 'description',
                                            ),
                                        ),
                                    ),
                                ),
                                'InstanceId' => array(
                                    'type' => 'string',
                                    'sentAs' => 'instanceId',
                                ),
                                'Platform' => array(
                                    'type' => 'string',
                                    'sentAs' => 'platform',
                                ),
                                'Description' => array(
                                    'type' => 'string',
                                    'sentAs' => 'description',
                                ),
                            ),
                        ),
                        'ImportVolume' => array(
                            'type' => 'object',
                            'sentAs' => 'importVolume',
                            'properties' => array(
                                'BytesConverted' => array(
                                    'type' => 'numeric',
                                    'sentAs' => 'bytesConverted',
                                ),
                                'AvailabilityZone' => array(
                                    'type' => 'string',
                                    'sentAs' => 'availabilityZone',
                                ),
                                'Description' => array(
                                    'type' => 'string',
                                    'sentAs' => 'description',
                                ),
                                'Image' => array(
                                    'type' => 'object',
                                    'sentAs' => 'image',
                                    'properties' => array(
                                        'Format' => array(
                                            'type' => 'string',
                                            'sentAs' => 'format',
                                        ),
                                        'Size' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'size',
                                        ),
                                        'ImportManifestUrl' => array(
                                            'type' => 'string',
                                            'sentAs' => 'importManifestUrl',
                                        ),
                                        'Checksum' => array(
                                            'type' => 'string',
                                            'sentAs' => 'checksum',
                                        ),
                                    ),
                                ),
                                'Volume' => array(
                                    'type' => 'object',
                                    'sentAs' => 'volume',
                                    'properties' => array(
                                        'Size' => array(
                                            'type' => 'numeric',
                                            'sentAs' => 'size',
                                        ),
                                        'Id' => array(
                                            'type' => 'string',
                                            'sentAs' => 'id',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'State' => array(
                            'type' => 'string',
                            'sentAs' => 'state',
                        ),
                        'StatusMessage' => array(
                            'type' => 'string',
                            'sentAs' => 'statusMessage',
                        ),
                        'Tags' => array(
                            'type' => 'array',
                            'sentAs' => 'tagSet',
                            'items' => array(
                                'name' => 'item',
                                'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                'type' => 'object',
                                'sentAs' => 'item',
                                'properties' => array(
                                    'Key' => array(
                                        'description' => 'The tag\'s key.',
                                        'type' => 'string',
                                        'sentAs' => 'key',
                                    ),
                                    'Value' => array(
                                        'description' => 'The tag\'s value.',
                                        'type' => 'string',
                                        'sentAs' => 'value',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'MonitorInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceMonitorings' => array(
                    'description' => 'A list of updated monitoring information for the instances specified in the request.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents the monitoring state of an EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Instance ID.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'Monitoring' => array(
                                'description' => 'Monitoring state for the associated instance.',
                                'type' => 'object',
                                'sentAs' => 'monitoring',
                                'properties' => array(
                                    'State' => array(
                                        'description' => 'The state of monitoring on an Amazon EC2 instance (ex: enabled, disabled).',
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'PurchaseReservedInstancesOfferingResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedInstancesId' => array(
                    'description' => 'The unique ID of the Reserved Instances purchased for your account.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'reservedInstancesId',
                ),
            ),
        ),
        'RegisterImageResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ImageId' => array(
                    'description' => 'The ID of the new Amazon Machine Image (AMI).',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'imageId',
                ),
            ),
        ),
        'ReplaceNetworkAclAssociationResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NewAssociationId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'newAssociationId',
                ),
            ),
        ),
        'ReplaceRouteTableAssociationResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NewAssociationId' => array(
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'newAssociationId',
                ),
            ),
        ),
        'RequestSpotInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SpotInstanceRequests' => array(
                    'description' => 'Contains a list of Spot Instance requests.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'spotInstanceRequestSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'SpotInstanceRequestId' => array(
                                'type' => 'string',
                                'sentAs' => 'spotInstanceRequestId',
                            ),
                            'SpotPrice' => array(
                                'type' => 'string',
                                'sentAs' => 'spotPrice',
                            ),
                            'Type' => array(
                                'type' => 'string',
                                'sentAs' => 'type',
                            ),
                            'State' => array(
                                'type' => 'string',
                                'sentAs' => 'state',
                            ),
                            'Fault' => array(
                                'type' => 'object',
                                'sentAs' => 'fault',
                                'properties' => array(
                                    'Code' => array(
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'Message' => array(
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'type' => 'object',
                                'sentAs' => 'status',
                                'properties' => array(
                                    'Code' => array(
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'UpdateTime' => array(
                                        'type' => 'string',
                                        'sentAs' => 'updateTime',
                                    ),
                                    'Message' => array(
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'ValidFrom' => array(
                                'type' => 'string',
                                'sentAs' => 'validFrom',
                            ),
                            'ValidUntil' => array(
                                'type' => 'string',
                                'sentAs' => 'validUntil',
                            ),
                            'LaunchGroup' => array(
                                'type' => 'string',
                                'sentAs' => 'launchGroup',
                            ),
                            'AvailabilityZoneGroup' => array(
                                'type' => 'string',
                                'sentAs' => 'availabilityZoneGroup',
                            ),
                            'LaunchSpecification' => array(
                                'description' => 'The LaunchSpecificationType data type.',
                                'type' => 'object',
                                'sentAs' => 'launchSpecification',
                                'properties' => array(
                                    'ImageId' => array(
                                        'description' => 'The AMI ID.',
                                        'type' => 'string',
                                        'sentAs' => 'imageId',
                                    ),
                                    'KeyName' => array(
                                        'description' => 'The name of the key pair.',
                                        'type' => 'string',
                                        'sentAs' => 'keyName',
                                    ),
                                    'SecurityGroups' => array(
                                        'type' => 'array',
                                        'sentAs' => 'groupSet',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'GroupName' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'groupName',
                                                ),
                                                'GroupId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'groupId',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'UserData' => array(
                                        'description' => 'Optional data, specific to a user\'s application, to provide in the launch request. All instances that collectively comprise the launch request have access to this data. User data is never returned through API responses.',
                                        'type' => 'string',
                                        'sentAs' => 'userData',
                                    ),
                                    'AddressingType' => array(
                                        'description' => 'Deprecated.',
                                        'type' => 'string',
                                        'sentAs' => 'addressingType',
                                    ),
                                    'InstanceType' => array(
                                        'description' => 'Specifies the instance type.',
                                        'type' => 'string',
                                        'sentAs' => 'instanceType',
                                    ),
                                    'Placement' => array(
                                        'description' => 'Defines a placement item.',
                                        'type' => 'object',
                                        'sentAs' => 'placement',
                                        'properties' => array(
                                            'AvailabilityZone' => array(
                                                'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                                'type' => 'string',
                                                'sentAs' => 'availabilityZone',
                                            ),
                                            'GroupName' => array(
                                                'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                                'type' => 'string',
                                                'sentAs' => 'groupName',
                                            ),
                                        ),
                                    ),
                                    'KernelId' => array(
                                        'description' => 'Specifies the ID of the kernel to select.',
                                        'type' => 'string',
                                        'sentAs' => 'kernelId',
                                    ),
                                    'RamdiskId' => array(
                                        'description' => 'Specifies the ID of the RAM disk to select. Some kernels require additional drivers at launch. Check the kernel requirements for information on whether or not you need to specify a RAM disk and search for the kernel ID.',
                                        'type' => 'string',
                                        'sentAs' => 'ramdiskId',
                                    ),
                                    'BlockDeviceMappings' => array(
                                        'description' => 'Specifies how block devices are exposed to the instance. Each mapping is made up of a virtualName and a deviceName.',
                                        'type' => 'array',
                                        'sentAs' => 'blockDeviceMapping',
                                        'items' => array(
                                            'name' => 'item',
                                            'description' => 'The BlockDeviceMappingItemType data type.',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'VirtualName' => array(
                                                    'description' => 'Specifies the virtual device name.',
                                                    'type' => 'string',
                                                    'sentAs' => 'virtualName',
                                                ),
                                                'DeviceName' => array(
                                                    'description' => 'Specifies the device name (e.g., /dev/sdh).',
                                                    'type' => 'string',
                                                    'sentAs' => 'deviceName',
                                                ),
                                                'Ebs' => array(
                                                    'description' => 'Specifies parameters used to automatically setup Amazon EBS volumes when the instance is launched.',
                                                    'type' => 'object',
                                                    'sentAs' => 'ebs',
                                                    'properties' => array(
                                                        'SnapshotId' => array(
                                                            'description' => 'The ID of the snapshot from which the volume will be created.',
                                                            'type' => 'string',
                                                            'sentAs' => 'snapshotId',
                                                        ),
                                                        'VolumeSize' => array(
                                                            'description' => 'The size of the volume, in gigabytes.',
                                                            'type' => 'numeric',
                                                            'sentAs' => 'volumeSize',
                                                        ),
                                                        'DeleteOnTermination' => array(
                                                            'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                            'type' => 'boolean',
                                                            'sentAs' => 'deleteOnTermination',
                                                        ),
                                                        'VolumeType' => array(
                                                            'type' => 'string',
                                                            'sentAs' => 'volumeType',
                                                        ),
                                                        'Iops' => array(
                                                            'type' => 'numeric',
                                                            'sentAs' => 'iops',
                                                        ),
                                                    ),
                                                ),
                                                'NoDevice' => array(
                                                    'description' => 'Specifies the device name to suppress during instance launch.',
                                                    'type' => 'string',
                                                    'sentAs' => 'noDevice',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'MonitoringEnabled' => array(
                                        'description' => 'Enables monitoring for the instance.',
                                        'type' => 'boolean',
                                        'sentAs' => 'monitoringEnabled',
                                    ),
                                    'SubnetId' => array(
                                        'description' => 'Specifies the Amazon VPC subnet ID within which to launch the instance(s) for Amazon Virtual Private Cloud.',
                                        'type' => 'string',
                                        'sentAs' => 'subnetId',
                                    ),
                                    'NetworkInterfaces' => array(
                                        'type' => 'array',
                                        'sentAs' => 'networkInterfaceSet',
                                        'items' => array(
                                            'name' => 'item',
                                            'type' => 'object',
                                            'sentAs' => 'item',
                                            'properties' => array(
                                                'NetworkInterfaceId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'networkInterfaceId',
                                                ),
                                                'DeviceIndex' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'deviceIndex',
                                                ),
                                                'SubnetId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'subnetId',
                                                ),
                                                'Description' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'description',
                                                ),
                                                'PrivateIpAddress' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'privateIpAddress',
                                                ),
                                                'Groups' => array(
                                                    'type' => 'array',
                                                    'sentAs' => 'SecurityGroupId',
                                                    'items' => array(
                                                        'name' => 'SecurityGroupId',
                                                        'type' => 'string',
                                                        'sentAs' => 'SecurityGroupId',
                                                    ),
                                                ),
                                                'DeleteOnTermination' => array(
                                                    'type' => 'boolean',
                                                    'sentAs' => 'deleteOnTermination',
                                                ),
                                                'PrivateIpAddresses' => array(
                                                    'type' => 'array',
                                                    'sentAs' => 'privateIpAddressesSet',
                                                    'items' => array(
                                                        'name' => 'item',
                                                        'type' => 'object',
                                                        'sentAs' => 'item',
                                                        'properties' => array(
                                                            'PrivateIpAddress' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'privateIpAddress',
                                                            ),
                                                            'Primary' => array(
                                                                'type' => 'boolean',
                                                                'sentAs' => 'primary',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                'SecondaryPrivateIpAddressCount' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'secondaryPrivateIpAddressCount',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'IamInstanceProfile' => array(
                                        'type' => 'object',
                                        'sentAs' => 'iamInstanceProfile',
                                        'properties' => array(
                                            'Arn' => array(
                                                'type' => 'string',
                                                'sentAs' => 'arn',
                                            ),
                                            'Name' => array(
                                                'type' => 'string',
                                                'sentAs' => 'name',
                                            ),
                                        ),
                                    ),
                                    'EbsOptimized' => array(
                                        'type' => 'boolean',
                                        'sentAs' => 'ebsOptimized',
                                    ),
                                ),
                            ),
                            'InstanceId' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'CreateTime' => array(
                                'type' => 'string',
                                'sentAs' => 'createTime',
                            ),
                            'ProductDescription' => array(
                                'type' => 'string',
                                'sentAs' => 'productDescription',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for this spot instance request.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'LaunchedAvailabilityZone' => array(
                                'description' => 'The Availability Zone in which the bid is launched.',
                                'type' => 'string',
                                'sentAs' => 'launchedAvailabilityZone',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'reservation' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservationId' => array(
                    'description' => 'The unique ID of this reservation.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'reservationId',
                ),
                'OwnerId' => array(
                    'description' => 'The AWS Access Key ID of the user who owns the reservation.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'ownerId',
                ),
                'RequesterId' => array(
                    'description' => 'The unique ID of the user who requested the instances in this reservation.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'requesterId',
                ),
                'Groups' => array(
                    'description' => 'The list of security groups requested for the instances in this reservation.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'groupSet',
                    'items' => array(
                        'name' => 'item',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'GroupName' => array(
                                'type' => 'string',
                                'sentAs' => 'groupName',
                            ),
                            'GroupId' => array(
                                'type' => 'string',
                                'sentAs' => 'groupId',
                            ),
                        ),
                    ),
                ),
                'Instances' => array(
                    'description' => 'The list of Amazon EC2 instances included in this reservation.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents an Amazon EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Unique ID of the instance launched.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'ImageId' => array(
                                'description' => 'Image ID of the AMI used to launch the instance.',
                                'type' => 'string',
                                'sentAs' => 'imageId',
                            ),
                            'State' => array(
                                'description' => 'The current state of the instance.',
                                'type' => 'object',
                                'sentAs' => 'instanceState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                            'PrivateDnsName' => array(
                                'description' => 'The private DNS name assigned to the instance. This DNS name can only be used inside the Amazon EC2 network. This element remains empty until the instance enters a running state.',
                                'type' => 'string',
                                'sentAs' => 'privateDnsName',
                            ),
                            'PublicDnsName' => array(
                                'description' => 'The public DNS name assigned to the instance. This DNS name is contactable from outside the Amazon EC2 network. This element remains empty until the instance enters a running state.',
                                'type' => 'string',
                                'sentAs' => 'dnsName',
                            ),
                            'StateTransitionReason' => array(
                                'description' => 'Reason for the most recent state transition. This might be an empty string.',
                                'type' => 'string',
                                'sentAs' => 'reason',
                            ),
                            'KeyName' => array(
                                'description' => 'If this instance was launched with an associated key pair, this displays the key pair name.',
                                'type' => 'string',
                                'sentAs' => 'keyName',
                            ),
                            'AmiLaunchIndex' => array(
                                'description' => 'The AMI launch index, which can be used to find this instance within the launch group.',
                                'type' => 'numeric',
                                'sentAs' => 'amiLaunchIndex',
                            ),
                            'ProductCodes' => array(
                                'description' => 'Product codes attached to this instance.',
                                'type' => 'array',
                                'sentAs' => 'productCodes',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'An AWS DevPay product code.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'ProductCodeId' => array(
                                            'description' => 'The unique ID of an AWS DevPay product code.',
                                            'type' => 'string',
                                            'sentAs' => 'productCode',
                                        ),
                                        'ProductCodeType' => array(
                                            'type' => 'string',
                                            'sentAs' => 'type',
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceType' => array(
                                'description' => 'The instance type. For more information on instance types, please see the Amazon Elastic Compute Cloud Developer Guide.',
                                'type' => 'string',
                                'sentAs' => 'instanceType',
                            ),
                            'LaunchTime' => array(
                                'description' => 'The time this instance launched.',
                                'type' => 'string',
                                'sentAs' => 'launchTime',
                            ),
                            'Placement' => array(
                                'description' => 'The location where this instance launched.',
                                'type' => 'object',
                                'sentAs' => 'placement',
                                'properties' => array(
                                    'AvailabilityZone' => array(
                                        'description' => 'The availability zone in which an Amazon EC2 instance runs.',
                                        'type' => 'string',
                                        'sentAs' => 'availabilityZone',
                                    ),
                                    'GroupName' => array(
                                        'description' => 'The name of the PlacementGroup in which an Amazon EC2 instance runs. Placement groups are primarily used for launching High Performance Computing instances in the same group to ensure fast connection speeds.',
                                        'type' => 'string',
                                        'sentAs' => 'groupName',
                                    ),
                                    'Tenancy' => array(
                                        'description' => 'The allowed tenancy of instances launched into the VPC. A value of default means instances can be launched with any tenancy; a value of dedicated means all instances launched into the VPC will be launched as dedicated tenancy regardless of the tenancy assigned to the instance at launch.',
                                        'type' => 'string',
                                        'sentAs' => 'tenancy',
                                    ),
                                ),
                            ),
                            'KernelId' => array(
                                'description' => 'Kernel associated with this instance.',
                                'type' => 'string',
                                'sentAs' => 'kernelId',
                            ),
                            'RamdiskId' => array(
                                'description' => 'RAM disk associated with this instance.',
                                'type' => 'string',
                                'sentAs' => 'ramdiskId',
                            ),
                            'Platform' => array(
                                'description' => 'Platform of the instance (e.g., Windows).',
                                'type' => 'string',
                                'sentAs' => 'platform',
                            ),
                            'Monitoring' => array(
                                'description' => 'Monitoring status for this instance.',
                                'type' => 'object',
                                'sentAs' => 'monitoring',
                                'properties' => array(
                                    'State' => array(
                                        'description' => 'The state of monitoring on an Amazon EC2 instance (ex: enabled, disabled).',
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                            'SubnetId' => array(
                                'description' => 'Specifies the Amazon VPC subnet ID in which the instance is running.',
                                'type' => 'string',
                                'sentAs' => 'subnetId',
                            ),
                            'VpcId' => array(
                                'description' => 'Specifies the Amazon VPC in which the instance is running.',
                                'type' => 'string',
                                'sentAs' => 'vpcId',
                            ),
                            'PrivateIpAddress' => array(
                                'description' => 'Specifies the private IP address that is assigned to the instance (Amazon VPC).',
                                'type' => 'string',
                                'sentAs' => 'privateIpAddress',
                            ),
                            'PublicIpAddress' => array(
                                'description' => 'Specifies the IP address of the instance.',
                                'type' => 'string',
                                'sentAs' => 'ipAddress',
                            ),
                            'StateReason' => array(
                                'description' => 'The reason for the state change.',
                                'type' => 'object',
                                'sentAs' => 'stateReason',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'Reason code for the state change.',
                                        'type' => 'string',
                                        'sentAs' => 'code',
                                    ),
                                    'Message' => array(
                                        'description' => 'Descriptive message for the state change.',
                                        'type' => 'string',
                                        'sentAs' => 'message',
                                    ),
                                ),
                            ),
                            'Architecture' => array(
                                'description' => 'The architecture of this instance.',
                                'type' => 'string',
                                'sentAs' => 'architecture',
                            ),
                            'RootDeviceType' => array(
                                'description' => 'The root device type used by the AMI. The AMI can use an Amazon EBS or instance store root device.',
                                'type' => 'string',
                                'sentAs' => 'rootDeviceType',
                            ),
                            'RootDeviceName' => array(
                                'description' => 'The root device name (e.g., /dev/sda1).',
                                'type' => 'string',
                                'sentAs' => 'rootDeviceName',
                            ),
                            'BlockDeviceMappings' => array(
                                'description' => 'Block device mapping set.',
                                'type' => 'array',
                                'sentAs' => 'blockDeviceMapping',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Describes how block devices are mapped on an Amazon EC2 instance.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'DeviceName' => array(
                                            'description' => 'The device name (e.g., /dev/sdh) at which the block device is exposed on the instance.',
                                            'type' => 'string',
                                            'sentAs' => 'deviceName',
                                        ),
                                        'Ebs' => array(
                                            'description' => 'The optional EBS device mapped to the specified device name.',
                                            'type' => 'object',
                                            'sentAs' => 'ebs',
                                            'properties' => array(
                                                'VolumeId' => array(
                                                    'description' => 'The ID of the EBS volume.',
                                                    'type' => 'string',
                                                    'sentAs' => 'volumeId',
                                                ),
                                                'Status' => array(
                                                    'description' => 'The status of the EBS volume.',
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                                'AttachTime' => array(
                                                    'description' => 'The time at which the EBS volume was attached to the associated instance.',
                                                    'type' => 'string',
                                                    'sentAs' => 'attachTime',
                                                ),
                                                'DeleteOnTermination' => array(
                                                    'description' => 'Specifies whether the Amazon EBS volume is deleted on instance termination.',
                                                    'type' => 'boolean',
                                                    'sentAs' => 'deleteOnTermination',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'VirtualizationType' => array(
                                'type' => 'string',
                                'sentAs' => 'virtualizationType',
                            ),
                            'InstanceLifecycle' => array(
                                'type' => 'string',
                                'sentAs' => 'instanceLifecycle',
                            ),
                            'SpotInstanceRequestId' => array(
                                'type' => 'string',
                                'sentAs' => 'spotInstanceRequestId',
                            ),
                            'License' => array(
                                'description' => 'Represents an active license in use and attached to an Amazon EC2 instance.',
                                'type' => 'object',
                                'sentAs' => 'license',
                                'properties' => array(
                                    'Pool' => array(
                                        'description' => 'The license pool from which this license was used (ex: \'windows\').',
                                        'type' => 'string',
                                        'sentAs' => 'pool',
                                    ),
                                ),
                            ),
                            'ClientToken' => array(
                                'type' => 'string',
                                'sentAs' => 'clientToken',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Instance.',
                                'type' => 'array',
                                'sentAs' => 'tagSet',
                                'items' => array(
                                    'name' => 'item',
                                    'description' => 'Represents metadata to associate with Amazon EC2 resources. Each tag consists of a user-defined key and value. Use tags to categorize EC2 resources, such as by purpose, owner, or environment.',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'Key' => array(
                                            'description' => 'The tag\'s key.',
                                            'type' => 'string',
                                            'sentAs' => 'key',
                                        ),
                                        'Value' => array(
                                            'description' => 'The tag\'s value.',
                                            'type' => 'string',
                                            'sentAs' => 'value',
                                        ),
                                    ),
                                ),
                            ),
                            'SecurityGroups' => array(
                                'type' => 'array',
                                'sentAs' => 'groupSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'GroupName' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupName',
                                        ),
                                        'GroupId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'groupId',
                                        ),
                                    ),
                                ),
                            ),
                            'SourceDestCheck' => array(
                                'type' => 'boolean',
                                'sentAs' => 'sourceDestCheck',
                            ),
                            'Hypervisor' => array(
                                'type' => 'string',
                                'sentAs' => 'hypervisor',
                            ),
                            'NetworkInterfaces' => array(
                                'type' => 'array',
                                'sentAs' => 'networkInterfaceSet',
                                'items' => array(
                                    'name' => 'item',
                                    'type' => 'object',
                                    'sentAs' => 'item',
                                    'properties' => array(
                                        'NetworkInterfaceId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'networkInterfaceId',
                                        ),
                                        'SubnetId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'subnetId',
                                        ),
                                        'VpcId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'vpcId',
                                        ),
                                        'Description' => array(
                                            'type' => 'string',
                                            'sentAs' => 'description',
                                        ),
                                        'OwnerId' => array(
                                            'type' => 'string',
                                            'sentAs' => 'ownerId',
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                            'sentAs' => 'status',
                                        ),
                                        'PrivateIpAddress' => array(
                                            'type' => 'string',
                                            'sentAs' => 'privateIpAddress',
                                        ),
                                        'PrivateDnsName' => array(
                                            'type' => 'string',
                                            'sentAs' => 'privateDnsName',
                                        ),
                                        'SourceDestCheck' => array(
                                            'type' => 'boolean',
                                            'sentAs' => 'sourceDestCheck',
                                        ),
                                        'Groups' => array(
                                            'type' => 'array',
                                            'sentAs' => 'groupSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'GroupName' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'groupName',
                                                    ),
                                                    'GroupId' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'groupId',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'Attachment' => array(
                                            'type' => 'object',
                                            'sentAs' => 'attachment',
                                            'properties' => array(
                                                'AttachmentId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'attachmentId',
                                                ),
                                                'DeviceIndex' => array(
                                                    'type' => 'numeric',
                                                    'sentAs' => 'deviceIndex',
                                                ),
                                                'Status' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'status',
                                                ),
                                                'AttachTime' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'attachTime',
                                                ),
                                                'DeleteOnTermination' => array(
                                                    'type' => 'boolean',
                                                    'sentAs' => 'deleteOnTermination',
                                                ),
                                            ),
                                        ),
                                        'Association' => array(
                                            'type' => 'object',
                                            'sentAs' => 'association',
                                            'properties' => array(
                                                'PublicIp' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'publicIp',
                                                ),
                                                'PublicDnsName' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'publicDnsName',
                                                ),
                                                'IpOwnerId' => array(
                                                    'type' => 'string',
                                                    'sentAs' => 'ipOwnerId',
                                                ),
                                            ),
                                        ),
                                        'PrivateIpAddresses' => array(
                                            'type' => 'array',
                                            'sentAs' => 'privateIpAddressesSet',
                                            'items' => array(
                                                'name' => 'item',
                                                'type' => 'object',
                                                'sentAs' => 'item',
                                                'properties' => array(
                                                    'PrivateIpAddress' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'privateIpAddress',
                                                    ),
                                                    'PrivateDnsName' => array(
                                                        'type' => 'string',
                                                        'sentAs' => 'privateDnsName',
                                                    ),
                                                    'Primary' => array(
                                                        'type' => 'boolean',
                                                        'sentAs' => 'primary',
                                                    ),
                                                    'Association' => array(
                                                        'type' => 'object',
                                                        'sentAs' => 'association',
                                                        'properties' => array(
                                                            'PublicIp' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'publicIp',
                                                            ),
                                                            'PublicDnsName' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'publicDnsName',
                                                            ),
                                                            'IpOwnerId' => array(
                                                                'type' => 'string',
                                                                'sentAs' => 'ipOwnerId',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'IamInstanceProfile' => array(
                                'type' => 'object',
                                'sentAs' => 'iamInstanceProfile',
                                'properties' => array(
                                    'Arn' => array(
                                        'type' => 'string',
                                        'sentAs' => 'arn',
                                    ),
                                    'Id' => array(
                                        'type' => 'string',
                                        'sentAs' => 'id',
                                    ),
                                ),
                            ),
                            'EbsOptimized' => array(
                                'type' => 'boolean',
                                'sentAs' => 'ebsOptimized',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'StartInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StartingInstances' => array(
                    'description' => 'The list of the starting instances and details on how their state has changed.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a state change for a specific EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The ID of the instance whose state changed.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'CurrentState' => array(
                                'description' => 'The current state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'currentState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                            'PreviousState' => array(
                                'description' => 'The previous state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'previousState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'StopInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StoppingInstances' => array(
                    'description' => 'The list of the stopping instances and details on how their state has changed.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a state change for a specific EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The ID of the instance whose state changed.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'CurrentState' => array(
                                'description' => 'The current state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'currentState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                            'PreviousState' => array(
                                'description' => 'The previous state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'previousState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'TerminateInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TerminatingInstances' => array(
                    'description' => 'The list of the terminating instances and details on how their state has changed.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents a state change for a specific EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The ID of the instance whose state changed.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'CurrentState' => array(
                                'description' => 'The current state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'currentState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                            'PreviousState' => array(
                                'description' => 'The previous state of the specified instance.',
                                'type' => 'object',
                                'sentAs' => 'previousState',
                                'properties' => array(
                                    'Code' => array(
                                        'description' => 'A 16-bit unsigned integer. The high byte is an opaque internal value and should be ignored. The low byte is set based on the state represented.',
                                        'type' => 'numeric',
                                        'sentAs' => 'code',
                                    ),
                                    'Name' => array(
                                        'description' => 'The current state of the instance.',
                                        'type' => 'string',
                                        'sentAs' => 'name',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'UnmonitorInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceMonitorings' => array(
                    'description' => 'A list of updated monitoring information for the instances specified in the request.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'instancesSet',
                    'items' => array(
                        'name' => 'item',
                        'description' => 'Represents the monitoring state of an EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'item',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'Instance ID.',
                                'type' => 'string',
                                'sentAs' => 'instanceId',
                            ),
                            'Monitoring' => array(
                                'description' => 'Monitoring state for the associated instance.',
                                'type' => 'object',
                                'sentAs' => 'monitoring',
                                'properties' => array(
                                    'State' => array(
                                        'description' => 'The state of monitoring on an Amazon EC2 instance (ex: enabled, disabled).',
                                        'type' => 'string',
                                        'sentAs' => 'state',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeAccountAttributes' => array(
                'result_key' => 'AccountAttributes',
            ),
            'DescribeAddresses' => array(
                'result_key' => 'Addresses',
            ),
            'DescribeAvailabilityZones' => array(
                'result_key' => 'AvailabilityZones',
            ),
            'DescribeBundleTasks' => array(
                'result_key' => 'BundleTasks',
            ),
            'DescribeConversionTasks' => array(
                'result_key' => 'ConversionTasks',
            ),
            'DescribeCustomerGateways' => array(
                'result_key' => 'CustomerGateways',
            ),
            'DescribeDhcpOptions' => array(
                'result_key' => 'DhcpOptions',
            ),
            'DescribeExportTasks' => array(
                'result_key' => 'ExportTasks',
            ),
            'DescribeImages' => array(
                'result_key' => 'Images',
            ),
            'DescribeInstanceStatus' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxResults',
                'result_key' => 'InstanceStatuses',
            ),
            'DescribeInstances' => array(
                'result_key' => 'Reservations',
            ),
            'DescribeInternetGateways' => array(
                'result_key' => 'InternetGateways',
            ),
            'DescribeKeyPairs' => array(
                'result_key' => 'KeyPairs',
            ),
            'DescribeLicenses' => array(
                'result_key' => 'Licenses',
            ),
            'DescribeNetworkAcls' => array(
                'result_key' => 'NetworkAcls',
            ),
            'DescribeNetworkInterfaces' => array(
                'result_key' => 'NetworkInterfaces',
            ),
            'DescribePlacementGroups' => array(
                'result_key' => 'PlacementGroups',
            ),
            'DescribeRegions' => array(
                'result_key' => 'Regions',
            ),
            'DescribeReservedInstances' => array(
                'result_key' => 'ReservedInstances',
            ),
            'DescribeReservedInstancesListings' => array(
                'result_key' => 'ReservedInstancesListings',
            ),
            'DescribeReservedInstancesOfferings' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxResults',
                'result_key' => 'ReservedInstancesOfferings',
            ),
            'DescribeRouteTables' => array(
                'result_key' => 'RouteTables',
            ),
            'DescribeSecurityGroups' => array(
                'result_key' => 'SecurityGroups',
            ),
            'DescribeSnapshots' => array(
                'result_key' => 'Snapshots',
            ),
            'DescribeSpotInstanceRequests' => array(
                'result_key' => 'SpotInstanceRequests',
            ),
            'DescribeSpotPriceHistory' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxResults',
                'result_key' => 'SpotPriceHistory',
            ),
            'DescribeSubnets' => array(
                'result_key' => 'Subnets',
            ),
            'DescribeTags' => array(
                'result_key' => 'Tags',
            ),
            'DescribeVolumeStatus' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxResults',
                'result_key' => 'VolumeStatuses',
            ),
            'DescribeVolumes' => array(
                'result_key' => 'Volumes',
            ),
            'DescribeVpcs' => array(
                'result_key' => 'Vpcs',
            ),
            'DescribeVpnConnections' => array(
                'result_key' => 'VpnConnections',
            ),
            'DescribeVpnGateways' => array(
                'result_key' => 'VpnGateways',
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 15,
            'max_attempts' => 40,
            'acceptor.type' => 'output',
        ),
        '__InstanceState' => array(
            'operation' => 'DescribeInstances',
            'acceptor.path' => 'Reservations/*/Instances/*/State/Name',
        ),
        'InstanceRunning' => array(
            'extends' => '__InstanceState',
            'success.value' => 'running',
            'failure.value' => array(
                'shutting-down',
                'terminated',
                'stopping',
            ),
        ),
        'InstanceStopped' => array(
            'extends' => '__InstanceState',
            'success.value' => 'stopped',
            'failure.value' => array(
                'pending',
                'terminated',
            ),
        ),
        'InstanceTerminated' => array(
            'extends' => '__InstanceState',
            'success.value' => 'terminated',
            'failure.value' => array(
                'pending',
                'stopping',
            ),
        ),
        '__ExportTaskState' => array(
            'operation' => 'DescribeExportTasks',
            'acceptor.path' => 'ExportTasks/*/State',
        ),
        'ExportTaskCompleted' => array(
            'extends' => '__ExportTaskState',
            'success.value' => 'completed',
        ),
        'ExportTaskCancelled' => array(
            'extends' => '__ExportTaskState',
            'success.value' => 'cancelled',
        ),
        'SnapshotCompleted' => array(
            'operation' => 'DescribeSnapshots',
            'success.path' => 'Snapshots/*/State',
            'success.value' => 'completed',
        ),
        'SubnetAvailable' => array(
            'operation' => 'DescribeSubnets',
            'success.path' => 'Subnets/*/State',
            'success.value' => 'available',
        ),
        '__VolumeStatus' => array(
            'operation' => 'DescribeVolumes',
            'acceptor.key' => 'VolumeStatuses/*/VolumeStatus/Status',
        ),
        'VolumeAvailable' => array(
            'extends' => '__VolumeStatus',
            'success.value' => 'available',
            'failure.value' => array(
                'deleted',
            ),
        ),
        'VolumeInUse' => array(
            'extends' => '__VolumeStatus',
            'success.value' => 'in-use',
            'failure.value' => array(
                'deleted',
            ),
        ),
        'VolumeDeleted' => array(
            'extends' => '__VolumeStatus',
            'success.value' => 'deleted',
        ),
        'VpcAvailable' => array(
            'operation' => 'DescribeVpcs',
            'success.path' => 'Vpcs/*/State',
            'success.value' => 'available',
        ),
        '__VpnConnectionState' => array(
            'operation' => 'DescribeVpnConnections',
            'acceptor.path' => 'VpnConnections/*/State',
        ),
        'VpnConnectionAvailable' => array(
            'extends' => '__VpnConnectionState',
            'success.value' => 'available',
            'failure.value' => array(
                'deleting',
                'deleted',
            ),
        ),
        'VpnConnectionDeleted' => array(
            'extends' => '__VpnConnectionState',
            'success.value' => 'deleted',
            'failure.value' => array(
                'pending',
            ),
        ),
        'BundleTaskComplete' => array(
            'operation' => 'DescribeBundleTasks',
            'acceptor.path' => 'BundleTasks/*/State',
            'success.value' => 'complete',
            'failure.value' => array(
                'failed',
            ),
        ),
        '__ConversionTaskState' => array(
            'operation' => 'DescribeConversionTasks',
            'acceptor.path' => 'ConversionTasks/*/State',
        ),
        'ConversionTaskCompleted' => array(
            'extends' => '__ConversionTaskState',
            'success.value' => 'completed',
            'failure.value' => array(
                'cancelled',
                'cancelling',
            ),
        ),
        'ConversionTaskCancelled' => array(
            'extends' => '__ConversionTaskState',
            'success.value' => 'cancelled',
        ),
        '__CustomerGatewayState' => array(
            'operation' => 'DescribeCustomerGateways',
            'acceptor.path' => 'CustomerGateways/*/State',
        ),
        'CustomerGatewayAvailable' => array(
            'extends' => '__CustomerGatewayState',
            'success.value' => 'available',
            'failure.value' => array(
                'deleted',
                'deleting',
            ),
        ),
        'ConversionTaskDeleted' => array(
            'extends' => '__CustomerGatewayState',
            'success.value' => 'deleted',
        ),
    ),
);
