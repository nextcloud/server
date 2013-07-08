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
    'apiVersion' => '2012-12-12',
    'endpointPrefix' => 'route53',
    'serviceFullName' => 'Amazon Route 53',
    'serviceAbbreviation' => 'Route 53',
    'serviceType' => 'rest-xml',
    'globalEndpoint' => 'route53.amazonaws.com',
    'signatureVersion' => 'v3https',
    'namespace' => 'Route53',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'route53.amazonaws.com',
        ),
    ),
    'operations' => array(
        'ChangeResourceRecordSets' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-12-12/hostedzone/{HostedZoneId}/rrset/',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ChangeResourceRecordSetsResponse',
            'responseType' => 'model',
            'summary' => 'Use this action to create or change your authoritative DNS information. To use this action, send a POST request to the 2012-12-12/hostedzone/hosted Zone ID/rrset resource. The request body must include an XML document with a ChangeResourceRecordSetsRequest element.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'ChangeResourceRecordSetsRequest',
                    'namespaces' => array(
                        'https://route53.amazonaws.com/doc/2012-12-12/',
                    ),
                ),
            ),
            'parameters' => array(
                'HostedZoneId' => array(
                    'required' => true,
                    'description' => 'Alias resource record sets only: The value of the hosted zone ID for the AWS resource.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 32,
                    'filters' => array(
                        'Aws\\Route53\\Route53Client::cleanId',
                    ),
                ),
                'ChangeBatch' => array(
                    'required' => true,
                    'description' => 'A complex type that contains an optional comment and the Changes element.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Comment' => array(
                            'description' => 'Optional: Any comments you want to include about a change batch request.',
                            'type' => 'string',
                            'maxLength' => 256,
                        ),
                        'Changes' => array(
                            'required' => true,
                            'description' => 'A complex type that contains one Change element for each resource record set that you want to create or delete.',
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => array(
                                'name' => 'Change',
                                'description' => 'A complex type that contains the information for each change in a change batch request.',
                                'type' => 'object',
                                'properties' => array(
                                    'Action' => array(
                                        'required' => true,
                                        'description' => 'The action to perform.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'CREATE',
                                            'DELETE',
                                        ),
                                    ),
                                    'ResourceRecordSet' => array(
                                        'required' => true,
                                        'description' => 'Information about the resource record set to create or delete.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Name' => array(
                                                'required' => true,
                                                'description' => 'The domain name of the current resource record set.',
                                                'type' => 'string',
                                                'maxLength' => 1024,
                                            ),
                                            'Type' => array(
                                                'required' => true,
                                                'description' => 'The type of the current resource record set.',
                                                'type' => 'string',
                                                'enum' => array(
                                                    'SOA',
                                                    'A',
                                                    'TXT',
                                                    'NS',
                                                    'CNAME',
                                                    'MX',
                                                    'PTR',
                                                    'SRV',
                                                    'SPF',
                                                    'AAAA',
                                                ),
                                            ),
                                            'SetIdentifier' => array(
                                                'description' => 'Weighted, Regional, and Failover resource record sets only: An identifier that differentiates among multiple resource record sets that have the same combination of DNS name and type.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 128,
                                            ),
                                            'Weight' => array(
                                                'description' => 'Weighted resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that determines what portion of traffic for the current resource record set is routed to the associated location.',
                                                'type' => 'numeric',
                                                'maximum' => 255,
                                            ),
                                            'Region' => array(
                                                'description' => 'Regional resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that specifies the AWS region for the current resource record set.',
                                                'type' => 'string',
                                                'minLength' => 1,
                                                'maxLength' => 64,
                                                'enum' => array(
                                                    'us-east-1',
                                                    'us-west-1',
                                                    'us-west-2',
                                                    'eu-west-1',
                                                    'ap-southeast-1',
                                                    'ap-southeast-2',
                                                    'ap-northeast-1',
                                                    'sa-east-1',
                                                ),
                                            ),
                                            'Failover' => array(
                                                'description' => 'Failover resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that indicates whether the current resource record set is a primary or secondary resource record set. A failover set may contain at most one resource record set marked as primary and one resource record set marked as secondary. A resource record set marked as primary will be returned if any of the following are true: (1) an associated health check is passing, (2) if the resource record set is an alias with the evaluate target health and at least one target resource record set is healthy, (3) both the primary and secondary resource record set are failing health checks or (4) there is no secondary resource record set. A secondary resource record set will be returned if: (1) the primary is failing a health check and either the secondary is passing a health check or has no associated health check, or (2) there is no primary resource record set.',
                                                'type' => 'string',
                                                'enum' => array(
                                                    'PRIMARY',
                                                    'SECONDARY',
                                                ),
                                            ),
                                            'TTL' => array(
                                                'description' => 'The cache time to live for the current resource record set.',
                                                'type' => 'numeric',
                                                'maximum' => 2147483647,
                                            ),
                                            'ResourceRecords' => array(
                                                'description' => 'A complex type that contains the resource records for the current resource record set.',
                                                'type' => 'array',
                                                'minItems' => 1,
                                                'items' => array(
                                                    'name' => 'ResourceRecord',
                                                    'description' => 'A complex type that contains the value of the Value element for the current resource record set.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'Value' => array(
                                                            'required' => true,
                                                            'description' => 'The value of the Value element for the current resource record set.',
                                                            'type' => 'string',
                                                            'maxLength' => 4000,
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'AliasTarget' => array(
                                                'description' => 'Alias resource record sets only: Information about the AWS resource to which you are redirecting traffic.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'HostedZoneId' => array(
                                                        'required' => true,
                                                        'description' => 'Alias resource record sets only: The value of the hosted zone ID for the AWS resource.',
                                                        'type' => 'string',
                                                        'maxLength' => 32,
                                                    ),
                                                    'DNSName' => array(
                                                        'required' => true,
                                                        'description' => 'Alias resource record sets only: The external DNS name associated with the AWS Resource.',
                                                        'type' => 'string',
                                                        'maxLength' => 1024,
                                                    ),
                                                    'EvaluateTargetHealth' => array(
                                                        'required' => true,
                                                        'description' => 'Alias resource record sets only: A boolean value that indicates whether this Resource Record Set should respect the health status of any health checks associated with the ALIAS target record which it is linked to.',
                                                        'type' => 'boolean',
                                                        'format' => 'boolean-string',
                                                    ),
                                                ),
                                            ),
                                            'HealthCheckId' => array(
                                                'description' => 'Health Check resource record sets only, not required for alias resource record sets: An identifier that is used to identify health check associated with the resource record set.',
                                                'type' => 'string',
                                                'maxLength' => 64,
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchHostedZoneException',
                ),
                array(
                    'reason' => 'The health check you are trying to get or delete does not exist.',
                    'class' => 'NoSuchHealthCheckException',
                ),
                array(
                    'reason' => 'This error contains a list of one or more error messages. Each error message indicates one error in the change batch. For more information, see Example InvalidChangeBatch Errors.',
                    'class' => 'InvalidChangeBatchException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
                array(
                    'reason' => 'The request was rejected because Route 53 was still processing a prior request.',
                    'class' => 'PriorRequestNotCompleteException',
                ),
            ),
        ),
        'CreateHealthCheck' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-12-12/healthcheck',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateHealthCheckResponse',
            'responseType' => 'model',
            'summary' => 'This action creates a new health check.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CreateHealthCheckRequest',
                    'namespaces' => array(
                        'https://route53.amazonaws.com/doc/2012-12-12/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique string that identifies the request and that allows failed CreateHealthCheck requests to be retried without the risk of executing the operation twice. You must use a unique CallerReference string every time you create a health check. CallerReference can be any unique string; you might choose to use a string that identifies your project.',
                    'type' => 'string',
                    'location' => 'xml',
                    'minLength' => 1,
                    'maxLength' => 64,
                ),
                'HealthCheckConfig' => array(
                    'required' => true,
                    'description' => 'A complex type that contains health check configuration.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'IPAddress' => array(
                            'required' => true,
                            'description' => 'IP Address of the instance being checked.',
                            'type' => 'string',
                            'maxLength' => 15,
                        ),
                        'Port' => array(
                            'description' => 'Port on which connection will be opened to the instance to health check. For HTTP this defaults to 80 if the port is not specified.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 65535,
                        ),
                        'Type' => array(
                            'required' => true,
                            'description' => 'The type of health check to be performed. Currently supported protocols are TCP and HTTP.',
                            'type' => 'string',
                            'enum' => array(
                                'HTTP',
                                'TCP',
                            ),
                        ),
                        'ResourcePath' => array(
                            'description' => 'Path to ping on the instance to check the health. Required only for HTTP health checks, HTTP request is issued to the instance on the given port and path.',
                            'type' => 'string',
                            'maxLength' => 255,
                        ),
                        'FullyQualifiedDomainName' => array(
                            'description' => 'Fully qualified domain name of the instance to be health checked.',
                            'type' => 'string',
                            'maxLength' => 255,
                        ),
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'TooManyHealthChecksException',
                ),
                array(
                    'reason' => 'The health check you are trying to create already exists. Route 53 returns this error when a health check has already been created with the specified CallerReference.',
                    'class' => 'HealthCheckAlreadyExistsException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'CreateHostedZone' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-12-12/hostedzone',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateHostedZoneResponse',
            'responseType' => 'model',
            'summary' => 'This action creates a new hosted zone.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CreateHostedZoneRequest',
                    'namespaces' => array(
                        'https://route53.amazonaws.com/doc/2012-12-12/',
                    ),
                ),
            ),
            'parameters' => array(
                'Name' => array(
                    'required' => true,
                    'description' => 'The name of the domain. This must be a fully-specified domain, for example, www.example.com. The trailing dot is optional; Route 53 assumes that the domain name is fully qualified. This means that Route 53 treats www.example.com (without a trailing dot) and www.example.com. (with a trailing dot) as identical.',
                    'type' => 'string',
                    'location' => 'xml',
                    'maxLength' => 1024,
                ),
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique string that identifies the request and that allows failed CreateHostedZone requests to be retried without the risk of executing the operation twice. You must use a unique CallerReference string every time you create a hosted zone. CallerReference can be any unique string; you might choose to use a string that identifies your project, such as DNSMigration_01.',
                    'type' => 'string',
                    'location' => 'xml',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'HostedZoneConfig' => array(
                    'description' => 'A complex type that contains an optional comment about your hosted zone.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Comment' => array(
                            'description' => 'An optional comment about your hosted zone. If you don\'t want to specify a comment, you can omit the HostedZoneConfig and Comment elements from the XML document.',
                            'type' => 'string',
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This error indicates that the specified domain name is not valid.',
                    'class' => 'InvalidDomainNameException',
                ),
                array(
                    'reason' => 'The hosted zone you are trying to create already exists. Route 53 returns this error when a hosted zone has already been created with the specified CallerReference.',
                    'class' => 'HostedZoneAlreadyExistsException',
                ),
                array(
                    'reason' => 'This error indicates that you\'ve reached the maximum number of hosted zones that can be created for the current AWS account. You can request an increase to the limit on the Contact Us page.',
                    'class' => 'TooManyHostedZonesException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
                array(
                    'reason' => 'Route 53 allows some duplicate domain names, but there is a maximum number of duplicate names. This error indicates that you have reached that maximum. If you want to create another hosted zone with the same name and Route 53 generates this error, you can request an increase to the limit on the Contact Us page.',
                    'class' => 'DelegationSetNotAvailableException',
                ),
            ),
        ),
        'DeleteHealthCheck' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-12-12/healthcheck/{HealthCheckId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DeleteHealthCheckResponse',
            'responseType' => 'model',
            'summary' => 'This action deletes a health check. To delete a health check, send a DELETE request to the 2012-12-12/healthcheck/health check ID resource.',
            'parameters' => array(
                'HealthCheckId' => array(
                    'required' => true,
                    'description' => 'The ID of the health check to delete.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 64,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The health check you are trying to get or delete does not exist.',
                    'class' => 'NoSuchHealthCheckException',
                ),
                array(
                    'reason' => 'There are resource records associated with this health check. Before you can delete the health check, you must disassociate it from the resource record sets.',
                    'class' => 'HealthCheckInUseException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'DeleteHostedZone' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-12-12/hostedzone/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DeleteHostedZoneResponse',
            'responseType' => 'model',
            'summary' => 'This action deletes a hosted zone. To delete a hosted zone, send a DELETE request to the 2012-12-12/hostedzone/hosted zone ID resource.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The ID of the request. Include this ID in a call to GetChange to track when the change has propagated to all Route 53 DNS servers.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 32,
                    'filters' => array(
                        'Aws\\Route53\\Route53Client::cleanId',
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchHostedZoneException',
                ),
                array(
                    'reason' => 'The hosted zone contains resource record sets in addition to the default NS and SOA resource record sets. Before you can delete the hosted zone, you must delete the additional resource record sets.',
                    'class' => 'HostedZoneNotEmptyException',
                ),
                array(
                    'reason' => 'The request was rejected because Route 53 was still processing a prior request.',
                    'class' => 'PriorRequestNotCompleteException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'GetChange' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/change/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetChangeResponse',
            'responseType' => 'model',
            'summary' => 'This action returns the current status of a change batch request. The status is one of the following values:',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The ID of the change batch request. The value that you specify here is the value that ChangeResourceRecordSets returned in the Id element when you submitted the request.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 32,
                    'filters' => array(
                        'Aws\\Route53\\Route53Client::cleanId',
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchChangeException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'GetHealthCheck' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/healthcheck/{HealthCheckId}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetHealthCheckResponse',
            'responseType' => 'model',
            'summary' => 'To retrieve the health check, send a GET request to the 2012-12-12/healthcheck/health check ID resource.',
            'parameters' => array(
                'HealthCheckId' => array(
                    'required' => true,
                    'description' => 'The ID of the health check to retrieve.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 64,
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The health check you are trying to get or delete does not exist.',
                    'class' => 'NoSuchHealthCheckException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'GetHostedZone' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/hostedzone/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetHostedZoneResponse',
            'responseType' => 'model',
            'summary' => 'To retrieve the delegation set for a hosted zone, send a GET request to the 2012-12-12/hostedzone/hosted zone ID resource. The delegation set is the four Route 53 name servers that were assigned to the hosted zone when you created it.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The ID of the hosted zone for which you want to get a list of the name servers in the delegation set.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 32,
                    'filters' => array(
                        'Aws\\Route53\\Route53Client::cleanId',
                    ),
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchHostedZoneException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'ListHealthChecks' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/healthcheck',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListHealthChecksResponse',
            'responseType' => 'model',
            'summary' => 'To retrieve a list of your health checks, send a GET request to the 2012-12-12/healthcheck resource. The response to this request includes a HealthChecks element with zero, one, or multiple HealthCheck child elements. By default, the list of health checks is displayed on a single page. You can control the length of the page that is displayed by using the MaxItems parameter. You can use the Marker parameter to control the health check that the list begins with.',
            'parameters' => array(
                'Marker' => array(
                    'description' => 'If the request returned more than one page of results, submit another request and specify the value of NextMarker from the last response in the marker parameter to get the next page of results.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'marker',
                    'maxLength' => 64,
                ),
                'MaxItems' => array(
                    'description' => 'Specify the maximum number of health checks to return per page of results.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'maxitems',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'ListHostedZones' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/hostedzone',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListHostedZonesResponse',
            'responseType' => 'model',
            'summary' => 'To retrieve a list of your hosted zones, send a GET request to the 2012-12-12/hostedzone resource. The response to this request includes a HostedZones element with zero, one, or multiple HostedZone child elements. By default, the list of hosted zones is displayed on a single page. You can control the length of the page that is displayed by using the MaxItems parameter. You can use the Marker parameter to control the hosted zone that the list begins with.',
            'parameters' => array(
                'Marker' => array(
                    'description' => 'If the request returned more than one page of results, submit another request and specify the value of NextMarker from the last response in the marker parameter to get the next page of results.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'marker',
                    'maxLength' => 64,
                ),
                'MaxItems' => array(
                    'description' => 'Specify the maximum number of hosted zones to return per page of results.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'maxitems',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
        'ListResourceRecordSets' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-12-12/hostedzone/{HostedZoneId}/rrset',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListResourceRecordSetsResponse',
            'responseType' => 'model',
            'summary' => 'Imagine all the resource record sets in a zone listed out in front of you. Imagine them sorted lexicographically first by DNS name (with the labels reversed, like "com.amazon.www" for example), and secondarily, lexicographically by record type. This operation retrieves at most MaxItems resource record sets from this list, in order, starting at a position specified by the Name and Type arguments:',
            'parameters' => array(
                'HostedZoneId' => array(
                    'required' => true,
                    'description' => 'The ID of the hosted zone that contains the resource record sets that you want to get.',
                    'type' => 'string',
                    'location' => 'uri',
                    'maxLength' => 32,
                    'filters' => array(
                        'Aws\\Route53\\Route53Client::cleanId',
                    ),
                ),
                'StartRecordName' => array(
                    'description' => 'The first name in the lexicographic ordering of domain names that you want the ListResourceRecordSets request to list.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'name',
                    'maxLength' => 1024,
                ),
                'StartRecordType' => array(
                    'description' => 'The DNS type at which to begin the listing of resource record sets.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'type',
                    'enum' => array(
                        'SOA',
                        'A',
                        'TXT',
                        'NS',
                        'CNAME',
                        'MX',
                        'PTR',
                        'SRV',
                        'SPF',
                        'AAAA',
                    ),
                ),
                'StartRecordIdentifier' => array(
                    'description' => 'Weighted resource record sets only: If results were truncated for a given DNS name and type, specify the value of ListResourceRecordSetsResponse$NextRecordIdentifier from the previous response to get the next resource record set that has the current DNS name and type.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'identifier',
                    'minLength' => 1,
                    'maxLength' => 128,
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of records you want in the response body.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'maxitems',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchHostedZoneException',
                ),
                array(
                    'reason' => 'Some value specified in the request is invalid or the XML document is malformed.',
                    'class' => 'InvalidInputException',
                ),
            ),
        ),
    ),
    'models' => array(
        'ChangeResourceRecordSetsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ChangeInfo' => array(
                    'description' => 'A complex type that contains information about changes made to your hosted zone.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the request. Use this ID to track when the change has completed across all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current state of the request. PENDING indicates that this request has not yet been applied to all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'SubmittedAt' => array(
                            'description' => 'The date and time the change was submitted, in the format YYYY-MM-DDThh:mm:ssZ, as specified in the ISO 8601 standard (for example, 2009-11-19T19:37:58Z). The Z after the time indicates that the time is listed in Coordinated Universal Time (UTC), which is synonymous with Greenwich Mean Time in this context.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'A complex type that describes change information about changes made to your hosted zone.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateHealthCheckResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HealthCheck' => array(
                    'description' => 'A complex type that contains identifying information about the health check.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the specified health check.',
                            'type' => 'string',
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique string that identifies the request to create the health check.',
                            'type' => 'string',
                        ),
                        'HealthCheckConfig' => array(
                            'description' => 'A complex type that contains the health check configuration.',
                            'type' => 'object',
                            'properties' => array(
                                'IPAddress' => array(
                                    'description' => 'IP Address of the instance being checked.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'Port on which connection will be opened to the instance to health check. For HTTP this defaults to 80 if the port is not specified.',
                                    'type' => 'numeric',
                                ),
                                'Type' => array(
                                    'description' => 'The type of health check to be performed. Currently supported protocols are TCP and HTTP.',
                                    'type' => 'string',
                                ),
                                'ResourcePath' => array(
                                    'description' => 'Path to ping on the instance to check the health. Required only for HTTP health checks, HTTP request is issued to the instance on the given port and path.',
                                    'type' => 'string',
                                ),
                                'FullyQualifiedDomainName' => array(
                                    'description' => 'Fully qualified domain name of the instance to be health checked.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'Location' => array(
                    'description' => 'The unique URL representing the new health check.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateHostedZoneResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HostedZone' => array(
                    'description' => 'A complex type that contains identifying information about the hosted zone.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the specified hosted zone.',
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the domain. This must be a fully-specified domain, for example, www.example.com. The trailing dot is optional; Route 53 assumes that the domain name is fully qualified. This means that Route 53 treats www.example.com (without a trailing dot) and www.example.com. (with a trailing dot) as identical.',
                            'type' => 'string',
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique string that identifies the request to create the hosted zone.',
                            'type' => 'string',
                        ),
                        'Config' => array(
                            'description' => 'A complex type that contains the Comment element.',
                            'type' => 'object',
                            'properties' => array(
                                'Comment' => array(
                                    'description' => 'An optional comment about your hosted zone. If you don\'t want to specify a comment, you can omit the HostedZoneConfig and Comment elements from the XML document.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ResourceRecordSetCount' => array(
                            'description' => 'Total number of resource record sets in the hosted zone.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'ChangeInfo' => array(
                    'description' => 'A complex type that contains information about the request to create a hosted zone. This includes an ID that you use when you call the GetChange action to get the current status of the change request.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the request. Use this ID to track when the change has completed across all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current state of the request. PENDING indicates that this request has not yet been applied to all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'SubmittedAt' => array(
                            'description' => 'The date and time the change was submitted, in the format YYYY-MM-DDThh:mm:ssZ, as specified in the ISO 8601 standard (for example, 2009-11-19T19:37:58Z). The Z after the time indicates that the time is listed in Coordinated Universal Time (UTC), which is synonymous with Greenwich Mean Time in this context.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'A complex type that describes change information about changes made to your hosted zone.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'DelegationSet' => array(
                    'description' => 'A complex type that contains name server information.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'NameServers' => array(
                            'description' => 'A complex type that contains the authoritative name servers for the hosted zone. Use the method provided by your domain registrar to add an NS record to your domain for each NameServer that is assigned to your hosted zone.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'NameServer',
                                'type' => 'string',
                                'sentAs' => 'NameServer',
                            ),
                        ),
                    ),
                ),
                'Location' => array(
                    'description' => 'The unique URL representing the new hosted zone.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteHealthCheckResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteHostedZoneResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ChangeInfo' => array(
                    'description' => 'A complex type that contains the ID, the status, and the date and time of your delete request.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the request. Use this ID to track when the change has completed across all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current state of the request. PENDING indicates that this request has not yet been applied to all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'SubmittedAt' => array(
                            'description' => 'The date and time the change was submitted, in the format YYYY-MM-DDThh:mm:ssZ, as specified in the ISO 8601 standard (for example, 2009-11-19T19:37:58Z). The Z after the time indicates that the time is listed in Coordinated Universal Time (UTC), which is synonymous with Greenwich Mean Time in this context.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'A complex type that describes change information about changes made to your hosted zone.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetChangeResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ChangeInfo' => array(
                    'description' => 'A complex type that contains information about the specified change batch, including the change batch ID, the status of the change, and the date and time of the request.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the request. Use this ID to track when the change has completed across all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The current state of the request. PENDING indicates that this request has not yet been applied to all Amazon Route 53 DNS servers.',
                            'type' => 'string',
                        ),
                        'SubmittedAt' => array(
                            'description' => 'The date and time the change was submitted, in the format YYYY-MM-DDThh:mm:ssZ, as specified in the ISO 8601 standard (for example, 2009-11-19T19:37:58Z). The Z after the time indicates that the time is listed in Coordinated Universal Time (UTC), which is synonymous with Greenwich Mean Time in this context.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'A complex type that describes change information about changes made to your hosted zone.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetHealthCheckResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HealthCheck' => array(
                    'description' => 'A complex type that contains the information about the specified health check.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the specified health check.',
                            'type' => 'string',
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique string that identifies the request to create the health check.',
                            'type' => 'string',
                        ),
                        'HealthCheckConfig' => array(
                            'description' => 'A complex type that contains the health check configuration.',
                            'type' => 'object',
                            'properties' => array(
                                'IPAddress' => array(
                                    'description' => 'IP Address of the instance being checked.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'Port on which connection will be opened to the instance to health check. For HTTP this defaults to 80 if the port is not specified.',
                                    'type' => 'numeric',
                                ),
                                'Type' => array(
                                    'description' => 'The type of health check to be performed. Currently supported protocols are TCP and HTTP.',
                                    'type' => 'string',
                                ),
                                'ResourcePath' => array(
                                    'description' => 'Path to ping on the instance to check the health. Required only for HTTP health checks, HTTP request is issued to the instance on the given port and path.',
                                    'type' => 'string',
                                ),
                                'FullyQualifiedDomainName' => array(
                                    'description' => 'Fully qualified domain name of the instance to be health checked.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetHostedZoneResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HostedZone' => array(
                    'description' => 'A complex type that contains the information about the specified hosted zone.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Id' => array(
                            'description' => 'The ID of the specified hosted zone.',
                            'type' => 'string',
                        ),
                        'Name' => array(
                            'description' => 'The name of the domain. This must be a fully-specified domain, for example, www.example.com. The trailing dot is optional; Route 53 assumes that the domain name is fully qualified. This means that Route 53 treats www.example.com (without a trailing dot) and www.example.com. (with a trailing dot) as identical.',
                            'type' => 'string',
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique string that identifies the request to create the hosted zone.',
                            'type' => 'string',
                        ),
                        'Config' => array(
                            'description' => 'A complex type that contains the Comment element.',
                            'type' => 'object',
                            'properties' => array(
                                'Comment' => array(
                                    'description' => 'An optional comment about your hosted zone. If you don\'t want to specify a comment, you can omit the HostedZoneConfig and Comment elements from the XML document.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'ResourceRecordSetCount' => array(
                            'description' => 'Total number of resource record sets in the hosted zone.',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'DelegationSet' => array(
                    'description' => 'A complex type that contains information about the name servers for the specified hosted zone.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'NameServers' => array(
                            'description' => 'A complex type that contains the authoritative name servers for the hosted zone. Use the method provided by your domain registrar to add an NS record to your domain for each NameServer that is assigned to your hosted zone.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'NameServer',
                                'type' => 'string',
                                'sentAs' => 'NameServer',
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListHealthChecksResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HealthChecks' => array(
                    'description' => 'A complex type that contains information about the health checks associated with the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'HealthCheck',
                        'description' => 'A complex type that contains identifying information about the health check.',
                        'type' => 'object',
                        'sentAs' => 'HealthCheck',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The ID of the specified health check.',
                                'type' => 'string',
                            ),
                            'CallerReference' => array(
                                'description' => 'A unique string that identifies the request to create the health check.',
                                'type' => 'string',
                            ),
                            'HealthCheckConfig' => array(
                                'description' => 'A complex type that contains the health check configuration.',
                                'type' => 'object',
                                'properties' => array(
                                    'IPAddress' => array(
                                        'description' => 'IP Address of the instance being checked.',
                                        'type' => 'string',
                                    ),
                                    'Port' => array(
                                        'description' => 'Port on which connection will be opened to the instance to health check. For HTTP this defaults to 80 if the port is not specified.',
                                        'type' => 'numeric',
                                    ),
                                    'Type' => array(
                                        'description' => 'The type of health check to be performed. Currently supported protocols are TCP and HTTP.',
                                        'type' => 'string',
                                    ),
                                    'ResourcePath' => array(
                                        'description' => 'Path to ping on the instance to check the health. Required only for HTTP health checks, HTTP request is issued to the instance on the given port and path.',
                                        'type' => 'string',
                                    ),
                                    'FullyQualifiedDomainName' => array(
                                        'description' => 'Fully qualified domain name of the instance to be health checked.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'If the request returned more than one page of results, submit another request and specify the value of NextMarker from the last response in the marker parameter to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag indicating whether there are more health checks to be listed. If your results were truncated, you can make a follow-up request for the next page of results by using the Marker element.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'Indicates where to continue listing health checks. If ListHealthChecksResponse$IsTruncated is true, make another request to ListHealthChecks and include the value of the NextMarker element in the Marker element to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of health checks to be included in the response body. If the number of health checks associated with this AWS account exceeds MaxItems, the value of ListHealthChecksResponse$IsTruncated in the response is true. Call ListHealthChecks again and specify the value of ListHealthChecksResponse$NextMarker in the ListHostedZonesRequest$Marker element to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListHostedZonesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'HostedZones' => array(
                    'description' => 'A complex type that contains information about the hosted zones associated with the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'HostedZone',
                        'description' => 'A complex type that contain information about the specified hosted zone.',
                        'type' => 'object',
                        'sentAs' => 'HostedZone',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The ID of the specified hosted zone.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The name of the domain. This must be a fully-specified domain, for example, www.example.com. The trailing dot is optional; Route 53 assumes that the domain name is fully qualified. This means that Route 53 treats www.example.com (without a trailing dot) and www.example.com. (with a trailing dot) as identical.',
                                'type' => 'string',
                            ),
                            'CallerReference' => array(
                                'description' => 'A unique string that identifies the request to create the hosted zone.',
                                'type' => 'string',
                            ),
                            'Config' => array(
                                'description' => 'A complex type that contains the Comment element.',
                                'type' => 'object',
                                'properties' => array(
                                    'Comment' => array(
                                        'description' => 'An optional comment about your hosted zone. If you don\'t want to specify a comment, you can omit the HostedZoneConfig and Comment elements from the XML document.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'ResourceRecordSetCount' => array(
                                'description' => 'Total number of resource record sets in the hosted zone.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'If the request returned more than one page of results, submit another request and specify the value of NextMarker from the last response in the marker parameter to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag indicating whether there are more hosted zones to be listed. If your results were truncated, you can make a follow-up request for the next page of results by using the Marker element.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'Indicates where to continue listing hosted zones. If ListHostedZonesResponse$IsTruncated is true, make another request to ListHostedZones and include the value of the NextMarker element in the Marker element to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of hosted zones to be included in the response body. If the number of hosted zones associated with this AWS account exceeds MaxItems, the value of ListHostedZonesResponse$IsTruncated in the response is true. Call ListHostedZones again and specify the value of ListHostedZonesResponse$NextMarker in the ListHostedZonesRequest$Marker element to get the next page of results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListResourceRecordSetsResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ResourceRecordSets' => array(
                    'description' => 'A complex type that contains information about the resource record sets that are returned by the request.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ResourceRecordSet',
                        'description' => 'A complex type that contains information about the current resource record set.',
                        'type' => 'object',
                        'sentAs' => 'ResourceRecordSet',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The domain name of the current resource record set.',
                                'type' => 'string',
                            ),
                            'Type' => array(
                                'description' => 'The type of the current resource record set.',
                                'type' => 'string',
                            ),
                            'SetIdentifier' => array(
                                'description' => 'Weighted, Regional, and Failover resource record sets only: An identifier that differentiates among multiple resource record sets that have the same combination of DNS name and type.',
                                'type' => 'string',
                            ),
                            'Weight' => array(
                                'description' => 'Weighted resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that determines what portion of traffic for the current resource record set is routed to the associated location.',
                                'type' => 'numeric',
                            ),
                            'Region' => array(
                                'description' => 'Regional resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that specifies the AWS region for the current resource record set.',
                                'type' => 'string',
                            ),
                            'Failover' => array(
                                'description' => 'Failover resource record sets only: Among resource record sets that have the same combination of DNS name and type, a value that indicates whether the current resource record set is a primary or secondary resource record set. A failover set may contain at most one resource record set marked as primary and one resource record set marked as secondary. A resource record set marked as primary will be returned if any of the following are true: (1) an associated health check is passing, (2) if the resource record set is an alias with the evaluate target health and at least one target resource record set is healthy, (3) both the primary and secondary resource record set are failing health checks or (4) there is no secondary resource record set. A secondary resource record set will be returned if: (1) the primary is failing a health check and either the secondary is passing a health check or has no associated health check, or (2) there is no primary resource record set.',
                                'type' => 'string',
                            ),
                            'TTL' => array(
                                'description' => 'The cache time to live for the current resource record set.',
                                'type' => 'numeric',
                            ),
                            'ResourceRecords' => array(
                                'description' => 'A complex type that contains the resource records for the current resource record set.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceRecord',
                                    'description' => 'A complex type that contains the value of the Value element for the current resource record set.',
                                    'type' => 'object',
                                    'sentAs' => 'ResourceRecord',
                                    'properties' => array(
                                        'Value' => array(
                                            'description' => 'The value of the Value element for the current resource record set.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'AliasTarget' => array(
                                'description' => 'Alias resource record sets only: Information about the AWS resource to which you are redirecting traffic.',
                                'type' => 'object',
                                'properties' => array(
                                    'HostedZoneId' => array(
                                        'description' => 'Alias resource record sets only: The value of the hosted zone ID for the AWS resource.',
                                        'type' => 'string',
                                    ),
                                    'DNSName' => array(
                                        'description' => 'Alias resource record sets only: The external DNS name associated with the AWS Resource.',
                                        'type' => 'string',
                                    ),
                                    'EvaluateTargetHealth' => array(
                                        'description' => 'Alias resource record sets only: A boolean value that indicates whether this Resource Record Set should respect the health status of any health checks associated with the ALIAS target record which it is linked to.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                            'HealthCheckId' => array(
                                'description' => 'Health Check resource record sets only, not required for alias resource record sets: An identifier that is used to identify health check associated with the resource record set.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether there are more resource record sets to be listed. If your results were truncated, you can make a follow-up request for the next page of results by using the ListResourceRecordSetsResponse$NextRecordName element.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'NextRecordName' => array(
                    'description' => 'If the results were truncated, the name of the next record in the list. This element is present only if ListResourceRecordSetsResponse$IsTruncated is true.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextRecordType' => array(
                    'description' => 'If the results were truncated, the type of the next record in the list. This element is present only if ListResourceRecordSetsResponse$IsTruncated is true.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextRecordIdentifier' => array(
                    'description' => 'Weighted resource record sets only: If results were truncated for a given DNS name and type, the value of SetIdentifier for the next resource record set that has the current DNS name and type.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of records you requested. The maximum value of MaxItems is 100.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'ListHealthChecks' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'HealthChecks',
            ),
            'ListHostedZones' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'HostedZones',
            ),
            'ListResourceRecordSets' => array(
                'more_key' => 'IsTruncated',
                'limit_key' => 'MaxItems',
                'result_key' => 'ResourceRecordSets',
                'token_param' => array(
                    'StartRecordName',
                    'StartRecordType',
                    'StartRecordIdentifier',
                ),
                'token_key' => array(
                    'NextRecordName',
                    'NextRecordType',
                    'NextRecordIdentifier',
                ),
            ),
        ),
    ),
);
