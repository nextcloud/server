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
    'apiVersion' => '2012-10-25',
    'endpointPrefix' => 'directconnect',
    'serviceFullName' => 'AWS Direct Connect',
    'serviceType' => 'json',
    'jsonVersion' => '1.1',
    'targetPrefix' => 'OvertureService.',
    'signatureVersion' => 'v4',
    'namespace' => 'DirectConnect',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'directconnect.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CreateConnection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'Connection',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new network connection between the customer network and a specific AWS Direct Connect location.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.CreateConnection',
                ),
                'offeringId' => array(
                    'required' => true,
                    'description' => 'The ID of the offering.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionName' => array(
                    'required' => true,
                    'description' => 'The name of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'CreatePrivateVirtualInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'VirtualInterface',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new private virtual interface. A virtual interface is the VLAN that transports AWS Direct Connect traffic. A private virtual interface supports sending traffic to a single Virtual Private Cloud (VPC).',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.CreatePrivateVirtualInterface',
                ),
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'newPrivateVirtualInterface' => array(
                    'description' => 'Detailed information of the private virtual interface to be created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'virtualInterfaceName' => array(
                            'description' => 'The name of the virtual interface assigned by the customer',
                            'type' => 'string',
                        ),
                        'vlan' => array(
                            'description' => 'VLAN ID',
                            'type' => 'numeric',
                        ),
                        'asn' => array(
                            'description' => 'Autonomous system (AS) number for Border Gateway Protocol (BGP) configuration',
                            'type' => 'numeric',
                        ),
                        'authKey' => array(
                            'description' => 'Authentication key for BGP configuration',
                            'type' => 'string',
                        ),
                        'amazonAddress' => array(
                            'description' => 'IP address assigned to the Amazon interface.',
                            'type' => 'string',
                        ),
                        'customerAddress' => array(
                            'description' => 'IP address assigned to the customer interface.',
                            'type' => 'string',
                        ),
                        'virtualGatewayId' => array(
                            'description' => 'The ID of the virtual private gateway to a VPC. Only applies to private virtual interfaces.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'CreatePublicVirtualInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'VirtualInterface',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new public virtual interface. A virtual interface is the VLAN that transports AWS Direct Connect traffic. A public virtual interface supports sending traffic to public services of AWS such as Amazon Simple Storage Service (Amazon S3).',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.CreatePublicVirtualInterface',
                ),
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'newPublicVirtualInterface' => array(
                    'description' => 'Detailed information of the public virtual interface to be created.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'virtualInterfaceName' => array(
                            'description' => 'The name of the virtual interface assigned by the customer',
                            'type' => 'string',
                        ),
                        'vlan' => array(
                            'description' => 'VLAN ID',
                            'type' => 'numeric',
                        ),
                        'asn' => array(
                            'description' => 'Autonomous system (AS) number for Border Gateway Protocol (BGP) configuration',
                            'type' => 'numeric',
                        ),
                        'authKey' => array(
                            'description' => 'Authentication key for BGP configuration',
                            'type' => 'string',
                        ),
                        'amazonAddress' => array(
                            'description' => 'IP address assigned to the Amazon interface.',
                            'type' => 'string',
                        ),
                        'customerAddress' => array(
                            'description' => 'IP address assigned to the customer interface.',
                            'type' => 'string',
                        ),
                        'routeFilterPrefixes' => array(
                            'description' => 'A list of routes to be advertised to the AWS network in this region (public virtual interface) or your VPC (private virtual interface).',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'RouteFilterPrefix',
                                'description' => 'A route filter prefix that the customer can advertise through Border Gateway Protocol (BGP) over a public virtual interface.',
                                'type' => 'object',
                                'properties' => array(
                                    'cidr' => array(
                                        'description' => 'CIDR notation for the advertised route. Multiple routes are separated by commas',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DeleteConnection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'Connection',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes the connection.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DeleteConnection',
                ),
                'connectionId' => array(
                    'required' => true,
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DeleteVirtualInterface' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DeleteVirtualInterfaceResponse',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a virtual interface.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DeleteVirtualInterface',
                ),
                'virtualInterfaceId' => array(
                    'description' => 'ID of the virtual interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeConnectionDetail' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'ConnectionDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Displays details about a specific connection including the order steps for the connection and the current state of the connection order.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeConnectionDetail',
                ),
                'connectionId' => array(
                    'required' => true,
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeConnections' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'Connections',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Displays all connections in this region.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeConnections',
                ),
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeOfferingDetail' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'OfferingDetail',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Displays additional ordering step details for a specified offering.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeOfferingDetail',
                ),
                'offeringId' => array(
                    'required' => true,
                    'description' => 'The ID of the offering.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeOfferings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'Offerings',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes one or more of the offerings that are currently available for creating new connections. The results include offerings for all regions.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeOfferings',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeVirtualGateways' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'VirtualGateways',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Returns a list of virtual private gateways owned by the AWS account.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeVirtualGateways',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
        'DescribeVirtualInterfaces' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'VirtualInterfaces',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Displays all virtual interfaces for an AWS account. Virtual interfaces deleted fewer than 15 minutes before DescribeVirtualInterfaces is called are also returned. If a connection ID is included then only virtual interfaces associated with this connection will be returned. If a virtual interface ID is included then only a single virtual interface will be returned.',
            'parameters' => array(
                'Content-Type' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'application/x-amz-json-1.1',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/json',
                ),
                'X-Amz-Target' => array(
                    'static' => true,
                    'location' => 'header',
                    'default' => 'OvertureService.DescribeVirtualInterfaces',
                ),
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'virtualInterfaceId' => array(
                    'description' => 'ID of the virtual interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A server-side error occurred during the API call. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectServerException',
                ),
                array(
                    'reason' => 'The API was called with invalid parameters. The error message will contain additional details about the cause.',
                    'class' => 'DirectConnectClientException',
                ),
            ),
        ),
    ),
    'models' => array(
        'Connection' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionName' => array(
                    'description' => 'The name of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionState' => array(
                    'description' => 'State of the connection. Requested: The initial state of connection. The connection stays in the requested state until the Letter of Authorization (LOA) is sent to the customer. Pending: The connection has been approved, and is being initialized. Available: The network link is up, and the connection is ready for use. Down: The network link is down. Deleted: The connection has been deleted.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'region' => array(
                    'description' => 'The AWS region where the offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'location' => array(
                    'description' => 'Where the AWS Direct Connect offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'VirtualInterface' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'virtualInterfaceId' => array(
                    'description' => 'ID of the virtual interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'location' => array(
                    'description' => 'Where the AWS Direct Connect offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'virtualInterfaceType' => array(
                    'description' => 'The type of virtual interface',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'virtualInterfaceName' => array(
                    'description' => 'The name of the virtual interface assigned by the customer',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'vlan' => array(
                    'description' => 'VLAN ID',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'asn' => array(
                    'description' => 'Autonomous system (AS) number for Border Gateway Protocol (BGP) configuration',
                    'type' => 'numeric',
                    'location' => 'json',
                ),
                'authKey' => array(
                    'description' => 'Authentication key for BGP configuration',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'amazonAddress' => array(
                    'description' => 'IP address assigned to the Amazon interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'customerAddress' => array(
                    'description' => 'IP address assigned to the customer interface.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'virtualInterfaceState' => array(
                    'description' => 'State of the virtual interface. Verifying: This state only applies to public virtual interfaces. Each public virtual interface needs validation before the virtual interface can be created. Pending: A virtual interface is in this state from the time that it is created until the virtual interface is ready to forward traffic. Available: A virtual interface that is able to forward traffic. Deleting: A virtual interface is in this state immediately after calling DeleteVirtualInterface until it can no longer forward traffic. Deleted: A virtual interface that cannot forward traffic.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'customerRouterConfig' => array(
                    'description' => 'Information for generating the customer router configuration.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'virtualGatewayId' => array(
                    'description' => 'The ID of the virtual private gateway to a VPC. Only applies to private virtual interfaces.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'routeFilterPrefixes' => array(
                    'description' => 'A list of routes to be advertised to the AWS network in this region (public virtual interface) or your VPC (private virtual interface).',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'RouteFilterPrefix',
                        'description' => 'A route filter prefix that the customer can advertise through Border Gateway Protocol (BGP) over a public virtual interface.',
                        'type' => 'object',
                        'properties' => array(
                            'cidr' => array(
                                'description' => 'CIDR notation for the advertised route. Multiple routes are separated by commas',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DeleteVirtualInterfaceResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'virtualInterfaceState' => array(
                    'description' => 'State of the virtual interface. Verifying: This state only applies to public virtual interfaces. Each public virtual interface needs validation before the virtual interface can be created. Pending: A virtual interface is in this state from the time that it is created until the virtual interface is ready to forward traffic. Available: A virtual interface that is able to forward traffic. Deleting: A virtual interface is in this state immediately after calling DeleteVirtualInterface until it can no longer forward traffic. Deleted: A virtual interface that cannot forward traffic.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'ConnectionDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'connectionId' => array(
                    'description' => 'ID of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionName' => array(
                    'description' => 'The name of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionState' => array(
                    'description' => 'State of the connection. Requested: The initial state of connection. The connection stays in the requested state until the Letter of Authorization (LOA) is sent to the customer. Pending: The connection has been approved, and is being initialized. Available: The network link is up, and the connection is ready for use. Down: The network link is down. Deleted: The connection has been deleted.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'region' => array(
                    'description' => 'The AWS region where the offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'location' => array(
                    'description' => 'Where the AWS Direct Connect offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'bandwidth' => array(
                    'description' => 'Bandwidth of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionCosts' => array(
                    'description' => 'A list of connection costs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ConnectionCost',
                        'description' => 'Cost description.',
                        'type' => 'object',
                        'properties' => array(
                            'name' => array(
                                'description' => 'The name of the cost item.',
                                'type' => 'string',
                            ),
                            'unit' => array(
                                'description' => 'The unit used in cost calculation.',
                                'type' => 'string',
                            ),
                            'currencyCode' => array(
                                'description' => 'Currency code based on ISO 4217.',
                                'type' => 'string',
                            ),
                            'amount' => array(
                                'description' => 'The amount of charge per unit.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'orderSteps' => array(
                    'description' => 'A list of connection order steps.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ConnectionOrderStep',
                        'description' => 'A step in the connection order process.',
                        'type' => 'object',
                        'properties' => array(
                            'number' => array(
                                'description' => 'Number of an order step.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the order step.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'More detailed description of the order step.',
                                'type' => 'string',
                            ),
                            'owner' => array(
                                'description' => 'The entity who owns the completion of the order step.',
                                'type' => 'string',
                            ),
                            'sla' => array(
                                'description' => 'Time to complete the order step in minutes.',
                                'type' => 'numeric',
                            ),
                            'stepState' => array(
                                'description' => 'State of the connection step. Pending: This step is not yet completed. Completed: This step has been completed',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'Connections' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'connections' => array(
                    'description' => 'A list of connections.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Connection',
                        'description' => 'A connection represents the physical network connection between the Direct Connect location and the customer.',
                        'type' => 'object',
                        'properties' => array(
                            'connectionId' => array(
                                'description' => 'ID of the connection.',
                                'type' => 'string',
                            ),
                            'connectionName' => array(
                                'description' => 'The name of the connection.',
                                'type' => 'string',
                            ),
                            'connectionState' => array(
                                'description' => 'State of the connection. Requested: The initial state of connection. The connection stays in the requested state until the Letter of Authorization (LOA) is sent to the customer. Pending: The connection has been approved, and is being initialized. Available: The network link is up, and the connection is ready for use. Down: The network link is down. Deleted: The connection has been deleted.',
                                'type' => 'string',
                            ),
                            'region' => array(
                                'description' => 'The AWS region where the offering is located.',
                                'type' => 'string',
                            ),
                            'location' => array(
                                'description' => 'Where the AWS Direct Connect offering is located.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'OfferingDetail' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'offeringId' => array(
                    'description' => 'The ID of the offering.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'region' => array(
                    'description' => 'The AWS region where the offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'location' => array(
                    'description' => 'Where the AWS Direct Connect offering is located.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'offeringName' => array(
                    'type' => 'string',
                    'location' => 'json',
                ),
                'description' => array(
                    'description' => 'Description of the offering.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'bandwidth' => array(
                    'description' => 'Bandwidth of the connection.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'connectionCosts' => array(
                    'description' => 'A list of connection costs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ConnectionCost',
                        'description' => 'Cost description.',
                        'type' => 'object',
                        'properties' => array(
                            'name' => array(
                                'description' => 'The name of the cost item.',
                                'type' => 'string',
                            ),
                            'unit' => array(
                                'description' => 'The unit used in cost calculation.',
                                'type' => 'string',
                            ),
                            'currencyCode' => array(
                                'description' => 'Currency code based on ISO 4217.',
                                'type' => 'string',
                            ),
                            'amount' => array(
                                'description' => 'The amount of charge per unit.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'orderSteps' => array(
                    'description' => 'A list of offering order steps.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'OfferingOrderStep',
                        'description' => 'A step in the offering order process.',
                        'type' => 'object',
                        'properties' => array(
                            'number' => array(
                                'description' => 'Number of an order step.',
                                'type' => 'string',
                            ),
                            'name' => array(
                                'description' => 'Name of the order step.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'More detailed description of the order step.',
                                'type' => 'string',
                            ),
                            'owner' => array(
                                'description' => 'The entity who owns the completion of the order step.',
                                'type' => 'string',
                            ),
                            'sla' => array(
                                'description' => 'Time to complete the order step in minutes.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'Offerings' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'offerings' => array(
                    'description' => 'A list of offerings.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Offering',
                        'description' => 'An offer to create a new connection for a specific price and terms.',
                        'type' => 'object',
                        'properties' => array(
                            'offeringId' => array(
                                'description' => 'The ID of the offering.',
                                'type' => 'string',
                            ),
                            'region' => array(
                                'description' => 'The AWS region where the offering is located.',
                                'type' => 'string',
                            ),
                            'location' => array(
                                'description' => 'Where the AWS Direct Connect offering is located.',
                                'type' => 'string',
                            ),
                            'offeringName' => array(
                                'description' => 'Name of the offering.',
                                'type' => 'string',
                            ),
                            'description' => array(
                                'description' => 'Description of the offering.',
                                'type' => 'string',
                            ),
                            'bandwidth' => array(
                                'description' => 'Bandwidth of the connection.',
                                'type' => 'string',
                            ),
                            'connectionCosts' => array(
                                'description' => 'A list of connection costs.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ConnectionCost',
                                    'description' => 'Cost description.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'name' => array(
                                            'description' => 'The name of the cost item.',
                                            'type' => 'string',
                                        ),
                                        'unit' => array(
                                            'description' => 'The unit used in cost calculation.',
                                            'type' => 'string',
                                        ),
                                        'currencyCode' => array(
                                            'description' => 'Currency code based on ISO 4217.',
                                            'type' => 'string',
                                        ),
                                        'amount' => array(
                                            'description' => 'The amount of charge per unit.',
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
        'VirtualGateways' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'virtualGateways' => array(
                    'description' => 'A list of virtual private gateways.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VirtualGateway',
                        'description' => 'You can create one or more Direct Connect private virtual interfaces linking to your virtual private gateway.',
                        'type' => 'object',
                        'properties' => array(
                            'virtualGatewayId' => array(
                                'description' => 'The ID of the virtual private gateway to a VPC. Only applies to private virtual interfaces.',
                                'type' => 'string',
                            ),
                            'virtualGatewayState' => array(
                                'description' => 'State of the virtual private gateway. Pending: This is the initial state after calling CreateVpnGateway. Available: Ready for use by a private virtual interface. Deleting: This is the initial state after calling DeleteVpnGateway. Deleted: In this state, a private virtual interface is unable to send traffic over this gateway.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'VirtualInterfaces' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'virtualInterfaces' => array(
                    'description' => 'A list of virtual interfaces.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VirtualInterface',
                        'description' => 'A virtual interface (VLAN) transmits the traffic between the Direct Connect location and the customer.',
                        'type' => 'object',
                        'properties' => array(
                            'virtualInterfaceId' => array(
                                'description' => 'ID of the virtual interface.',
                                'type' => 'string',
                            ),
                            'location' => array(
                                'description' => 'Where the AWS Direct Connect offering is located.',
                                'type' => 'string',
                            ),
                            'connectionId' => array(
                                'description' => 'ID of the connection.',
                                'type' => 'string',
                            ),
                            'virtualInterfaceType' => array(
                                'description' => 'The type of virtual interface',
                                'type' => 'string',
                            ),
                            'virtualInterfaceName' => array(
                                'description' => 'The name of the virtual interface assigned by the customer',
                                'type' => 'string',
                            ),
                            'vlan' => array(
                                'description' => 'VLAN ID',
                                'type' => 'numeric',
                            ),
                            'asn' => array(
                                'description' => 'Autonomous system (AS) number for Border Gateway Protocol (BGP) configuration',
                                'type' => 'numeric',
                            ),
                            'authKey' => array(
                                'description' => 'Authentication key for BGP configuration',
                                'type' => 'string',
                            ),
                            'amazonAddress' => array(
                                'description' => 'IP address assigned to the Amazon interface.',
                                'type' => 'string',
                            ),
                            'customerAddress' => array(
                                'description' => 'IP address assigned to the customer interface.',
                                'type' => 'string',
                            ),
                            'virtualInterfaceState' => array(
                                'description' => 'State of the virtual interface. Verifying: This state only applies to public virtual interfaces. Each public virtual interface needs validation before the virtual interface can be created. Pending: A virtual interface is in this state from the time that it is created until the virtual interface is ready to forward traffic. Available: A virtual interface that is able to forward traffic. Deleting: A virtual interface is in this state immediately after calling DeleteVirtualInterface until it can no longer forward traffic. Deleted: A virtual interface that cannot forward traffic.',
                                'type' => 'string',
                            ),
                            'customerRouterConfig' => array(
                                'description' => 'Information for generating the customer router configuration.',
                                'type' => 'string',
                            ),
                            'virtualGatewayId' => array(
                                'description' => 'The ID of the virtual private gateway to a VPC. Only applies to private virtual interfaces.',
                                'type' => 'string',
                            ),
                            'routeFilterPrefixes' => array(
                                'description' => 'A list of routes to be advertised to the AWS network in this region (public virtual interface) or your VPC (private virtual interface).',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RouteFilterPrefix',
                                    'description' => 'A route filter prefix that the customer can advertise through Border Gateway Protocol (BGP) over a public virtual interface.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'cidr' => array(
                                            'description' => 'CIDR notation for the advertised route. Multiple routes are separated by commas',
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
    ),
);
