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
    'apiVersion' => '2012-11-15',
    'endpointPrefix' => 'elasticache',
    'serviceFullName' => 'Amazon ElastiCache',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'ElastiCache',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticache.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AuthorizeCacheSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Authorizes ingress to a CacheSecurityGroup using EC2 Security Groups as authorization (therefore the application using the cache must be running on EC2 clusters). This API requires the following parameters: EC2SecurityGroupName and EC2SecurityGroupOwnerId.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AuthorizeCacheSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Security Group to authorize.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'required' => true,
                    'description' => 'Name of the EC2 Security Group to include in the authorization.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'required' => true,
                    'description' => 'AWS Account Number of the owner of the security group specified in the EC2SecurityGroupName parameter. The AWS Access Key ID is not an acceptable value.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The state of the Cache Security Group does not allow deletion.',
                    'class' => 'InvalidCacheSecurityGroupStateException',
                ),
                array(
                    'reason' => 'The specified EC2 Security Group is already authorized for the specified Cache Security Group.',
                    'class' => 'AuthorizationAlreadyExistsException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'CreateCacheCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Cache Cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCacheCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheClusterId' => array(
                    'required' => true,
                    'description' => 'The Cache Cluster identifier. This parameter is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NumCacheNodes' => array(
                    'required' => true,
                    'description' => 'The number of Cache Nodes the Cache Cluster should have.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'CacheNodeType' => array(
                    'required' => true,
                    'description' => 'The compute and memory capacity of nodes in a Cache Cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Engine' => array(
                    'required' => true,
                    'description' => 'The name of the cache engine to be used for this Cache Cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The version of the cache engine to be used for this cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheParameterGroupName' => array(
                    'description' => 'The name of the cache parameter group to associate with this Cache cluster. If this argument is omitted, the default CacheParameterGroup for the specified engine will be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheSubnetGroupName' => array(
                    'description' => 'The name of the Cache Subnet Group to be used for the Cache Cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheSecurityGroupNames' => array(
                    'description' => 'A list of Cache Security Group Names to associate with this Cache Cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'CacheSecurityGroupNames.member',
                    'items' => array(
                        'name' => 'CacheSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'SecurityGroupIds' => array(
                    'description' => 'Specifies the VPC Security Groups associated with the Cache Cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroupIds.member',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'PreferredAvailabilityZone' => array(
                    'description' => 'The EC2 Availability Zone that the Cache Cluster will be created in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which system maintenance can occur.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which each of the Cache Nodes will accept connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'NotificationTopicArn' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic to which notifications will be sent.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor engine upgrades will be applied automatically to the Cache Cluster during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'User already has a Cache Cluster with the given identifier.',
                    'class' => 'CacheClusterAlreadyExistsException',
                ),
                array(
                    'reason' => 'Specified Cache node type is not available in the specified Availability Zone.',
                    'class' => 'InsufficientCacheClusterCapacityException',
                ),
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'CacheSubnetGroupName does not refer to an existing Cache Subnet Group.',
                    'class' => 'CacheSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Clusters per customer.',
                    'class' => 'ClusterQuotaForCustomerExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Nodes in a single Cache Cluster.',
                    'class' => 'NodeQuotaForClusterExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Nodes per customer.',
                    'class' => 'NodeQuotaForCustomerExceededException',
                ),
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'CreateCacheParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheParameterGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Cache Parameter Group. Cache Parameter groups control the parameters for a Cache Cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCacheParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Parameter Group Family the Cache Parameter Group can be used with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'The description for the Cache Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Parameter Groups.',
                    'class' => 'CacheParameterGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'A Cache Parameter Group with the name specified in CacheParameterGroupName already exists.',
                    'class' => 'CacheParameterGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'The state of the Cache Parameter Group does not allow for the requested action to occur.',
                    'class' => 'InvalidCacheParameterGroupStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'CreateCacheSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Cache Security Group. Cache Security groups control access to one or more Cache Clusters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCacheSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the Cache Security Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'The description for the Cache Security Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A Cache Security Group with the name specified in CacheSecurityGroupName already exists.',
                    'class' => 'CacheSecurityGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Security Groups.',
                    'class' => 'CacheSecurityGroupQuotaExceededException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'CreateCacheSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Cache Subnet Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCacheSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the Cache Subnet Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheSubnetGroupDescription' => array(
                    'required' => true,
                    'description' => 'The description for the Cache Subnet Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'required' => true,
                    'description' => 'The EC2 Subnet IDs for the Cache Subnet Group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SubnetIds.member',
                    'items' => array(
                        'name' => 'SubnetIdentifier',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSubnetGroupName is already used by an existing Cache Subnet Group.',
                    'class' => 'CacheSubnetGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Subnet Groups.',
                    'class' => 'CacheSubnetGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of subnets in a Cache Subnet Group.',
                    'class' => 'CacheSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'Request subnet is invalid, or all subnets are not in the same VPC.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'DeleteCacheCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Deletes a previously provisioned Cache Cluster. A successful response from the web service indicates the request was received correctly. This action cannot be canceled or reverted. DeleteCacheCluster deletes all associated Cache Nodes, node endpoints and the Cache Cluster itself.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCacheCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheClusterId' => array(
                    'required' => true,
                    'description' => 'The Cache Cluster identifier for the Cache Cluster to be deleted. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheClusterId does not refer to an existing Cache Cluster.',
                    'class' => 'CacheClusterNotFoundException',
                ),
                array(
                    'reason' => 'The specified Cache Cluster is not in the available state.',
                    'class' => 'InvalidCacheClusterStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DeleteCacheParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified CacheParameterGroup. The CacheParameterGroup cannot be deleted if it is associated with any cache clusters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCacheParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Parameter Group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the Cache Parameter Group does not allow for the requested action to occur.',
                    'class' => 'InvalidCacheParameterGroupStateException',
                ),
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DeleteCacheSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a Cache Security Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCacheSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Security Group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the Cache Security Group does not allow deletion.',
                    'class' => 'InvalidCacheSecurityGroupStateException',
                ),
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DeleteCacheSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a Cache Subnet Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCacheSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Subnet Group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Request cache subnet group is currently in use.',
                    'class' => 'CacheSubnetGroupInUseException',
                ),
                array(
                    'reason' => 'CacheSubnetGroupName does not refer to an existing Cache Subnet Group.',
                    'class' => 'CacheSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DescribeCacheClusters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheClusterMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about all provisioned Cache Clusters if no Cache Cluster identifier is specified, or about a specific Cache Cluster if a Cache Cluster identifier is supplied.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheClusters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheClusterId' => array(
                    'description' => 'The user-supplied cluster identifier. If this parameter is specified, only information about that specific Cache Cluster is returned. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheClusters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ShowCacheNodeInfo' => array(
                    'description' => 'An optional flag that can be included in the DescribeCacheCluster request to retrieve Cache Nodes information.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheClusterId does not refer to an existing Cache Cluster.',
                    'class' => 'CacheClusterNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeCacheEngineVersions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheEngineVersionMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of the available cache engines and their versions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheEngineVersions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'Engine' => array(
                    'description' => 'The cache engine to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The cache engine version to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheParameterGroupFamily' => array(
                    'description' => 'The name of a specific Cache Parameter Group family to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheParameterGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DefaultOnly' => array(
                    'description' => 'Indicates that only the default version of the specified engine or engine and major version combination is returned.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeCacheParameterGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheParameterGroupsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of CacheParameterGroup descriptions. If a CacheParameterGroupName is specified, the list will contain only the descriptions of the specified CacheParameterGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheParameterGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'description' => 'The name of a specific cache parameter group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheParameterGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeCacheParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheParameterGroupDetails',
            'responseType' => 'model',
            'summary' => 'Returns the detailed parameter list for a particular CacheParameterGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of a specific cache parameter group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Source' => array(
                    'description' => 'The parameter types to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheClusters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeCacheSecurityGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSecurityGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of CacheSecurityGroup descriptions. If a CacheSecurityGroupName is specified, the list will contain only the description of the specified CacheSecurityGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheSecurityGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSecurityGroupName' => array(
                    'description' => 'The name of the Cache Security Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheClusters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeCacheSubnetGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSubnetGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of CacheSubnetGroup descriptions. If a CacheSubnetGroupName is specified, the list will contain only the description of the specified Cache Subnet Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeCacheSubnetGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSubnetGroupName' => array(
                    'description' => 'The name of the Cache Subnet Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheSubnetGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSubnetGroupName does not refer to an existing Cache Subnet Group.',
                    'class' => 'CacheSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DescribeEngineDefaultParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EngineDefaultsWrapper',
            'responseType' => 'model',
            'summary' => 'Returns the default engine and system parameter information for the specified cache engine.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEngineDefaultParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Parameter Group Family.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheClusters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeEvents' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventsMessage',
            'responseType' => 'model',
            'summary' => 'Returns events related to Cache Clusters, Cache Security Groups, and Cache Parameter Groups for the past 14 days. Events specific to a particular Cache Cluster, Cache Security Group, or Cache Parameter Group can be obtained by providing the name as a parameter. By default, the past hour of events are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEvents',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'SourceIdentifier' => array(
                    'description' => 'The identifier of the event source for which events will be returned. If not specified, then all sources are included in the response.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceType' => array(
                    'description' => 'The event source to retrieve events for. If no value is specified, all events are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'cache-cluster',
                        'cache-parameter-group',
                        'cache-security-group',
                        'cache-subnet-group',
                    ),
                ),
                'StartTime' => array(
                    'description' => 'The beginning of the time interval to retrieve events for, specified in ISO 8601 format.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The end of the time interval for which to retrieve events, specified in ISO 8601 format.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'Duration' => array(
                    'description' => 'The number of minutes to retrieve events for.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker provided in the previous DescribeCacheClusters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeReservedCacheNodes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedCacheNodeMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about reserved Cache Nodes for this account, or about a specified reserved Cache Node.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedCacheNodes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'ReservedCacheNodeId' => array(
                    'description' => 'The reserved Cache Node identifier filter value. Specify this parameter to show only the reservation that matches the specified reservation ID.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReservedCacheNodesOfferingId' => array(
                    'description' => 'The offering identifier filter value. Specify this parameter to show only purchased reservations matching the specified offering identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheNodeType' => array(
                    'description' => 'The Cache Node type filter value. Specify this parameter to show only those reservations matching the specified Cache Nodes type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Duration' => array(
                    'description' => 'The duration filter value, specified in years or seconds. Specify this parameter to show only reservations for this duration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ProductDescription' => array(
                    'description' => 'The product description filter value. Specify this parameter to show only those reservations matching the specified product description.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OfferingType' => array(
                    'description' => 'The offering type filter value. Specify this parameter to show only the available offerings matching the specified offering type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'The marker provided in the previous request. If this parameter is specified, the response includes records beyond the marker only, up to MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified reserved Cache Node not found.',
                    'class' => 'ReservedCacheNodeNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'DescribeReservedCacheNodesOfferings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedCacheNodesOfferingMessage',
            'responseType' => 'model',
            'summary' => 'Lists available reserved Cache Node offerings.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedCacheNodesOfferings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'ReservedCacheNodesOfferingId' => array(
                    'description' => 'The offering identifier filter value. Specify this parameter to show only the available offering that matches the specified reservation identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheNodeType' => array(
                    'description' => 'The Cache Node type filter value. Specify this parameter to show only the available offerings matching the specified Cache Node type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Duration' => array(
                    'description' => 'Duration filter value, specified in years or seconds. Specify this parameter to show only reservations for this duration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ProductDescription' => array(
                    'description' => 'Product description filter value. Specify this parameter to show only the available offerings matching the specified product description.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OfferingType' => array(
                    'description' => 'The offering type filter value. Specify this parameter to show only the available offerings matching the specified offering type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'The marker provided in the previous request. If this parameter is specified, the response includes records beyond the marker only, up to MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedCacheNodesOfferingNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'ModifyCacheCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies the Cache Cluster settings. You can change one or more Cache Cluster configuration parameters by specifying the parameters and the new values in the request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyCacheCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheClusterId' => array(
                    'required' => true,
                    'description' => 'The Cache Cluster identifier. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NumCacheNodes' => array(
                    'description' => 'The number of Cache Nodes the Cache Cluster should have. If NumCacheNodes is greater than the existing number of Cache Nodes, Cache Nodes will be added. If NumCacheNodes is less than the existing number of Cache Nodes, Cache Nodes will be removed. When removing Cache Nodes, the Ids of the specific Cache Nodes to be removed must be supplied using the CacheNodeIdsToRemove parameter.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'CacheNodeIdsToRemove' => array(
                    'description' => 'The list of Cache Node IDs to be removed. This parameter is only valid when NumCacheNodes is less than the existing number of Cache Nodes. The number of Cache Node Ids supplied in this parameter must match the difference between the existing number of Cache Nodes in the cluster and the new NumCacheNodes requested.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'CacheNodeIdsToRemove.member',
                    'items' => array(
                        'name' => 'CacheNodeId',
                        'type' => 'string',
                    ),
                ),
                'CacheSecurityGroupNames' => array(
                    'description' => 'A list of Cache Security Group Names to authorize on this Cache Cluster. This change is asynchronously applied as soon as possible.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'CacheSecurityGroupNames.member',
                    'items' => array(
                        'name' => 'CacheSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'SecurityGroupIds' => array(
                    'description' => 'Specifies the VPC Security Groups associated with the Cache Cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroupIds.member',
                    'items' => array(
                        'name' => 'SecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which system maintenance can occur, which may result in an outage. This change is made immediately. If moving this window to the current time, there must be at least 120 minutes between the current time and end of the window to ensure pending changes are applied.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NotificationTopicArn' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the SNS topic to which notifications will be sent.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheParameterGroupName' => array(
                    'description' => 'The name of the Cache Parameter Group to apply to this Cache Cluster. This change is asynchronously applied as soon as possible for parameters when the ApplyImmediately parameter is specified as true for this request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NotificationTopicStatus' => array(
                    'description' => 'The status of the Amazon SNS notification topic. The value can be active or inactive. Notifications are sent only if the status is active.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ApplyImmediately' => array(
                    'description' => 'Specifies whether or not the modifications in this request and any pending modifications are asynchronously applied as soon as possible, regardless of the PreferredMaintenanceWindow setting for the Cache Cluster.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The version of the cache engine to upgrade this cluster to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor engine upgrades will be applied automatically to the Cache Cluster during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified Cache Cluster is not in the available state.',
                    'class' => 'InvalidCacheClusterStateException',
                ),
                array(
                    'reason' => 'The state of the Cache Security Group does not allow deletion.',
                    'class' => 'InvalidCacheSecurityGroupStateException',
                ),
                array(
                    'reason' => 'CacheClusterId does not refer to an existing Cache Cluster.',
                    'class' => 'CacheClusterNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Nodes in a single Cache Cluster.',
                    'class' => 'NodeQuotaForClusterExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of Cache Nodes per customer.',
                    'class' => 'NodeQuotaForCustomerExceededException',
                ),
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'ModifyCacheParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Modifies the parameters of a CacheParameterGroup. To modify more than one parameter, submit a list of ParameterName and ParameterValue parameters. A maximum of 20 parameters can be modified in a single request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyCacheParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the cache parameter group to modify.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ParameterNameValues' => array(
                    'required' => true,
                    'description' => 'An array of parameter names and values for the parameter update. At least one parameter name and value must be supplied; subsequent arguments are optional. A maximum of 20 parameters may be modified in a single request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ParameterNameValues.member',
                    'items' => array(
                        'name' => 'ParameterNameValue',
                        'description' => 'A name and value pair used to update the value of a Parameter.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'Specifies the name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'Specifies the value of the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'The state of the Cache Parameter Group does not allow for the requested action to occur.',
                    'class' => 'InvalidCacheParameterGroupStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'ModifyCacheSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies an existing Cache Subnet Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyCacheSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the Cache Subnet Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheSubnetGroupDescription' => array(
                    'description' => 'The description for the Cache Subnet Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'description' => 'The EC2 Subnet IDs for the Cache Subnet Group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SubnetIds.member',
                    'items' => array(
                        'name' => 'SubnetIdentifier',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSubnetGroupName does not refer to an existing Cache Subnet Group.',
                    'class' => 'CacheSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of subnets in a Cache Subnet Group.',
                    'class' => 'CacheSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'Request subnet is currently in use.',
                    'class' => 'SubnetInUseException',
                ),
                array(
                    'reason' => 'Request subnet is invalid, or all subnets are not in the same VPC.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'PurchaseReservedCacheNodesOffering' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedCacheNodeWrapper',
            'responseType' => 'model',
            'summary' => 'Purchases a reserved Cache Node offering.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PurchaseReservedCacheNodesOffering',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'ReservedCacheNodesOfferingId' => array(
                    'required' => true,
                    'description' => 'The ID of the Reserved Cache Node offering to purchase.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReservedCacheNodeId' => array(
                    'description' => 'Customer-specified identifier to track this reservation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheNodeCount' => array(
                    'description' => 'The number of instances to reserve.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedCacheNodesOfferingNotFoundException',
                ),
                array(
                    'reason' => 'User already has a reservation with the given identifier.',
                    'class' => 'ReservedCacheNodeAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would exceed the user\'s Cache Node quota.',
                    'class' => 'ReservedCacheNodeQuotaExceededException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'RebootCacheCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Reboots some (or all) of the cache cluster nodes within a previously provisioned ElastiCache cluster. This API results in the application of modified CacheParameterGroup parameters to the cache cluster. This action is taken as soon as possible, and results in a momentary outage to the cache cluster during which the cache cluster status is set to rebooting. During that momentary outage, the contents of the cache (for each cache cluster node being rebooted) are lost. A CacheCluster event is created when the reboot is completed.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RebootCacheCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheClusterId' => array(
                    'required' => true,
                    'description' => 'The Cache Cluster identifier. This parameter is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CacheNodeIdsToReboot' => array(
                    'required' => true,
                    'description' => 'A list of Cache Cluster Node Ids to reboot. To reboot an entire cache cluster, specify all cache cluster node Ids.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'CacheNodeIdsToReboot.member',
                    'items' => array(
                        'name' => 'CacheNodeId',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified Cache Cluster is not in the available state.',
                    'class' => 'InvalidCacheClusterStateException',
                ),
                array(
                    'reason' => 'CacheClusterId does not refer to an existing Cache Cluster.',
                    'class' => 'CacheClusterNotFoundException',
                ),
            ),
        ),
        'ResetCacheParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Modifies the parameters of a CacheParameterGroup to the engine or system default value. To reset specific parameters submit a list of the parameter names. To reset the entire CacheParameterGroup, specify the CacheParameterGroup name and ResetAllParameters parameters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetCacheParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ResetAllParameters' => array(
                    'description' => 'Specifies whether (true) or not (false) to reset all parameters in the Cache Parameter Group to default values.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'ParameterNameValues' => array(
                    'required' => true,
                    'description' => 'An array of parameter names which should be reset. If not resetting the entire CacheParameterGroup, at least one parameter name must be supplied.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ParameterNameValues.member',
                    'items' => array(
                        'name' => 'ParameterNameValue',
                        'description' => 'A name and value pair used to update the value of a Parameter.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'Specifies the name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'Specifies the value of the parameter.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the Cache Parameter Group does not allow for the requested action to occur.',
                    'class' => 'InvalidCacheParameterGroupStateException',
                ),
                array(
                    'reason' => 'CacheParameterGroupName does not refer to an existing Cache Parameter Group.',
                    'class' => 'CacheParameterGroupNotFoundException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
        'RevokeCacheSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CacheSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Revokes ingress from a CacheSecurityGroup for previously authorized EC2 Security Groups.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RevokeCacheSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-11-15',
                ),
                'CacheSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Cache Security Group to revoke ingress from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the EC2 Security Group to revoke access from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'required' => true,
                    'description' => 'The AWS Account Number of the owner of the security group specified in the EC2SecurityGroupName parameter. The AWS Access Key ID is not an acceptable value.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'CacheSecurityGroupName does not refer to an existing Cache Security Group.',
                    'class' => 'CacheSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'Specified EC2 Security Group is not authorized for the specified Cache Security Group.',
                    'class' => 'AuthorizationNotFoundException',
                ),
                array(
                    'reason' => 'The state of the Cache Security Group does not allow deletion.',
                    'class' => 'InvalidCacheSecurityGroupStateException',
                ),
                array(
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'class' => 'InvalidParameterCombinationException',
                ),
            ),
        ),
    ),
    'models' => array(
        'CacheSecurityGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CacheSecurityGroup' => array(
                    'description' => 'Defines a set of EC2 Security groups that are allowed to access a Cache Cluster.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'OwnerId' => array(
                            'description' => 'Provides the AWS ID of the owner of a specific Cache Security Group.',
                            'type' => 'string',
                        ),
                        'CacheSecurityGroupName' => array(
                            'description' => 'Specifies the name of the Cache Security Group.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'Provides the description of the Cache Security Group.',
                            'type' => 'string',
                        ),
                        'EC2SecurityGroups' => array(
                            'description' => 'Contains a list of EC2SecurityGroup elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'EC2SecurityGroup',
                                'description' => 'Specifies the current state of this Cache Node.',
                                'type' => 'object',
                                'sentAs' => 'EC2SecurityGroup',
                                'properties' => array(
                                    'Status' => array(
                                        'description' => 'Provides the status of the EC2 Security Group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupName' => array(
                                        'description' => 'Specifies the name of the EC2 Security Group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupOwnerId' => array(
                                        'description' => 'Specifies the AWS ID of the owner of the EC2 Security Group specified in the EC2SecurityGroupName field.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CacheClusterWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CacheCluster' => array(
                    'description' => 'Contains information about a Cache Cluster.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'CacheClusterId' => array(
                            'description' => 'Specifies a user-supplied identifier. This is the unique key that identifies a Cache Cluster.',
                            'type' => 'string',
                        ),
                        'ConfigurationEndpoint' => array(
                            'description' => 'Specifies a user-supplied identifier. This is the unique key that identifies a Cache Cluster.',
                            'type' => 'object',
                            'properties' => array(
                                'Address' => array(
                                    'description' => 'Specifies the DNS address of the Cache Node.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'Specifies the port that the cache engine is listening on.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'ClientDownloadLandingPage' => array(
                            'description' => 'Provides the landing page to download the latest ElastiCache client library.',
                            'type' => 'string',
                        ),
                        'CacheNodeType' => array(
                            'description' => 'Specifies the name of the compute and memory capacity node type for the Cache Cluster.',
                            'type' => 'string',
                        ),
                        'Engine' => array(
                            'description' => 'Provides the name of the cache engine to be used for this Cache Cluster.',
                            'type' => 'string',
                        ),
                        'EngineVersion' => array(
                            'description' => 'Provides the cache engine version of the cache engine to be used for this Cache Cluster.',
                            'type' => 'string',
                        ),
                        'CacheClusterStatus' => array(
                            'description' => 'Specifies the current state of this Cache Cluster.',
                            'type' => 'string',
                        ),
                        'NumCacheNodes' => array(
                            'description' => 'Specifies the number of Cache Nodes the Cache Cluster contains.',
                            'type' => 'numeric',
                        ),
                        'PreferredAvailabilityZone' => array(
                            'description' => 'Specifies the name of the Availability Zone the Cache Cluster is located in.',
                            'type' => 'string',
                        ),
                        'CacheClusterCreateTime' => array(
                            'description' => 'Provides the date and time the Cache Cluster was created.',
                            'type' => 'string',
                        ),
                        'PreferredMaintenanceWindow' => array(
                            'description' => 'Specifies the weekly time range (in UTC) during which system maintenance can occur.',
                            'type' => 'string',
                        ),
                        'PendingModifiedValues' => array(
                            'description' => 'Specifies that changes to the Cache Cluster are pending. This element is only included when changes are pending. Specific changes are identified by sub-elements.',
                            'type' => 'object',
                            'properties' => array(
                                'NumCacheNodes' => array(
                                    'description' => 'Contains the new NumCacheNodes for the Cache Cluster that will be applied or is in progress.',
                                    'type' => 'numeric',
                                ),
                                'CacheNodeIdsToRemove' => array(
                                    'description' => 'Contains the list of node Ids to remove from the Cache Cluster that will be applied or is in progress.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CacheNodeId',
                                        'type' => 'string',
                                        'sentAs' => 'CacheNodeId',
                                    ),
                                ),
                                'EngineVersion' => array(
                                    'description' => 'Contains the new version of the Cache Engine the Cache Cluster will be upgraded to.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'NotificationConfiguration' => array(
                            'description' => 'Specifies the notification details the Cache Cluster contains.',
                            'type' => 'object',
                            'properties' => array(
                                'TopicArn' => array(
                                    'description' => 'Specifies the topic Amazon Resource Name (ARN), identifying this resource.',
                                    'type' => 'string',
                                ),
                                'TopicStatus' => array(
                                    'description' => 'Specifies the current state of this topic.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'CacheSecurityGroups' => array(
                            'description' => 'Provides the list of Cache Security Group elements containing CacheSecurityGroup.Name and CacheSecurityGroup.Status sub-elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheSecurityGroup',
                                'description' => 'Links a CacheCluster to one or more CacheSecurityGroups.',
                                'type' => 'object',
                                'sentAs' => 'CacheSecurityGroup',
                                'properties' => array(
                                    'CacheSecurityGroupName' => array(
                                        'description' => 'The name of the Cache Security Group.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the CacheSecurityGroupMembership, the status changes either when a CacheSecurityGroup is modified, or when the CacheSecurityGroups assigned to a Cache Cluster are modified.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'CacheParameterGroup' => array(
                            'description' => 'Provides the status of the Cache Parameter Group assigned to the Cache Cluster.',
                            'type' => 'object',
                            'properties' => array(
                                'CacheParameterGroupName' => array(
                                    'description' => 'The name of the Cache Parameter Group.',
                                    'type' => 'string',
                                ),
                                'ParameterApplyStatus' => array(
                                    'description' => 'The status of parameter updates.',
                                    'type' => 'string',
                                ),
                                'CacheNodeIdsToReboot' => array(
                                    'description' => 'A list of the Cache Node Ids which need to be rebooted for parameter changes to be applied.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CacheNodeId',
                                        'type' => 'string',
                                        'sentAs' => 'CacheNodeId',
                                    ),
                                ),
                            ),
                        ),
                        'CacheSubnetGroupName' => array(
                            'description' => 'Specifies the name of the Cache Subnet Group associated with the Cache Cluster.',
                            'type' => 'string',
                        ),
                        'CacheNodes' => array(
                            'description' => 'Specifies the list of Cache Nodes the Cache Cluster contains.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheNode',
                                'description' => 'A Cache Cluster is made up of one or more Cache Nodes. Each Cache Node is an separate endpoint servicing the memcached protocol.',
                                'type' => 'object',
                                'sentAs' => 'CacheNode',
                                'properties' => array(
                                    'CacheNodeId' => array(
                                        'description' => 'Specifies a Cache Node identifier. This is the unique key that identifies a Cache Node per Customer (AWS account).',
                                        'type' => 'string',
                                    ),
                                    'CacheNodeStatus' => array(
                                        'description' => 'Specifies the current state of this Cache Node.',
                                        'type' => 'string',
                                    ),
                                    'CacheNodeCreateTime' => array(
                                        'description' => 'Provides the date and time the Cache Node was created.',
                                        'type' => 'string',
                                    ),
                                    'Endpoint' => array(
                                        'description' => 'Specifies the endpoint details for a Cache Node.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Address' => array(
                                                'description' => 'Specifies the DNS address of the Cache Node.',
                                                'type' => 'string',
                                            ),
                                            'Port' => array(
                                                'description' => 'Specifies the port that the cache engine is listening on.',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                    'ParameterGroupStatus' => array(
                                        'description' => 'Specifies the status of the parameter group applied to this Cache Node.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'AutoMinorVersionUpgrade' => array(
                            'description' => 'Indicates that minor version patches are applied automatically.',
                            'type' => 'boolean',
                        ),
                        'SecurityGroups' => array(
                            'description' => 'Specifies the VPC Security Groups associated with the Cache Cluster.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'SecurityGroupMembership',
                                'description' => 'Represents one or more Cache Security Groups to which a Cache Cluster belongs.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'SecurityGroupId' => array(
                                        'description' => 'The identifier of the Cache Security Group.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the Cache Security Group membership. The status changes whenever a Cache Security Group is modified, or when the Cache Security Groups assigned to a Cache Cluster are modified.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CacheParameterGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CacheParameterGroup' => array(
                    'description' => 'Contains a set of parameters and their values which can be applied to a Cache Cluster.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'CacheParameterGroupName' => array(
                            'description' => 'Provides the name of the Cache Parameter Group.',
                            'type' => 'string',
                        ),
                        'CacheParameterGroupFamily' => array(
                            'description' => 'Provides the name of the Cache Parameter Group Family that this Cache Parameter Group is compatible with.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'Provides the customer-specified description for this Cache Parameter Group.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'CacheSubnetGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CacheSubnetGroup' => array(
                    'description' => 'Contains the result of a successful invocation of the following actions:',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'CacheSubnetGroupName' => array(
                            'description' => 'Specifies the name of the Cache Subnet Group.',
                            'type' => 'string',
                        ),
                        'CacheSubnetGroupDescription' => array(
                            'description' => 'Provides the description of the Cache Subnet Group.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'Provides the VPC ID of the Cache Subnet Group.',
                            'type' => 'string',
                        ),
                        'Subnets' => array(
                            'description' => 'Contains a list of subnets for this group.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Subnet',
                                'description' => 'Network Subnet associated with a Cache Cluster',
                                'type' => 'object',
                                'sentAs' => 'Subnet',
                                'properties' => array(
                                    'SubnetIdentifier' => array(
                                        'description' => 'Specifies the unique identifier for the Subnet',
                                        'type' => 'string',
                                    ),
                                    'SubnetAvailabilityZone' => array(
                                        'description' => 'Specifies the Availability Zone associated with the Subnet',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Name' => array(
                                                'description' => 'Specifies the name of the Availability Zone',
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
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CacheClusterMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CacheClusters' => array(
                    'description' => 'A list of CacheClusters.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheCluster',
                        'description' => 'Contains information about a Cache Cluster.',
                        'type' => 'object',
                        'sentAs' => 'CacheCluster',
                        'properties' => array(
                            'CacheClusterId' => array(
                                'description' => 'Specifies a user-supplied identifier. This is the unique key that identifies a Cache Cluster.',
                                'type' => 'string',
                            ),
                            'ConfigurationEndpoint' => array(
                                'description' => 'Specifies a user-supplied identifier. This is the unique key that identifies a Cache Cluster.',
                                'type' => 'object',
                                'properties' => array(
                                    'Address' => array(
                                        'description' => 'Specifies the DNS address of the Cache Node.',
                                        'type' => 'string',
                                    ),
                                    'Port' => array(
                                        'description' => 'Specifies the port that the cache engine is listening on.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'ClientDownloadLandingPage' => array(
                                'description' => 'Provides the landing page to download the latest ElastiCache client library.',
                                'type' => 'string',
                            ),
                            'CacheNodeType' => array(
                                'description' => 'Specifies the name of the compute and memory capacity node type for the Cache Cluster.',
                                'type' => 'string',
                            ),
                            'Engine' => array(
                                'description' => 'Provides the name of the cache engine to be used for this Cache Cluster.',
                                'type' => 'string',
                            ),
                            'EngineVersion' => array(
                                'description' => 'Provides the cache engine version of the cache engine to be used for this Cache Cluster.',
                                'type' => 'string',
                            ),
                            'CacheClusterStatus' => array(
                                'description' => 'Specifies the current state of this Cache Cluster.',
                                'type' => 'string',
                            ),
                            'NumCacheNodes' => array(
                                'description' => 'Specifies the number of Cache Nodes the Cache Cluster contains.',
                                'type' => 'numeric',
                            ),
                            'PreferredAvailabilityZone' => array(
                                'description' => 'Specifies the name of the Availability Zone the Cache Cluster is located in.',
                                'type' => 'string',
                            ),
                            'CacheClusterCreateTime' => array(
                                'description' => 'Provides the date and time the Cache Cluster was created.',
                                'type' => 'string',
                            ),
                            'PreferredMaintenanceWindow' => array(
                                'description' => 'Specifies the weekly time range (in UTC) during which system maintenance can occur.',
                                'type' => 'string',
                            ),
                            'PendingModifiedValues' => array(
                                'description' => 'Specifies that changes to the Cache Cluster are pending. This element is only included when changes are pending. Specific changes are identified by sub-elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'NumCacheNodes' => array(
                                        'description' => 'Contains the new NumCacheNodes for the Cache Cluster that will be applied or is in progress.',
                                        'type' => 'numeric',
                                    ),
                                    'CacheNodeIdsToRemove' => array(
                                        'description' => 'Contains the list of node Ids to remove from the Cache Cluster that will be applied or is in progress.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CacheNodeId',
                                            'type' => 'string',
                                            'sentAs' => 'CacheNodeId',
                                        ),
                                    ),
                                    'EngineVersion' => array(
                                        'description' => 'Contains the new version of the Cache Engine the Cache Cluster will be upgraded to.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'NotificationConfiguration' => array(
                                'description' => 'Specifies the notification details the Cache Cluster contains.',
                                'type' => 'object',
                                'properties' => array(
                                    'TopicArn' => array(
                                        'description' => 'Specifies the topic Amazon Resource Name (ARN), identifying this resource.',
                                        'type' => 'string',
                                    ),
                                    'TopicStatus' => array(
                                        'description' => 'Specifies the current state of this topic.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'CacheSecurityGroups' => array(
                                'description' => 'Provides the list of Cache Security Group elements containing CacheSecurityGroup.Name and CacheSecurityGroup.Status sub-elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'CacheSecurityGroup',
                                    'description' => 'Links a CacheCluster to one or more CacheSecurityGroups.',
                                    'type' => 'object',
                                    'sentAs' => 'CacheSecurityGroup',
                                    'properties' => array(
                                        'CacheSecurityGroupName' => array(
                                            'description' => 'The name of the Cache Security Group.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the CacheSecurityGroupMembership, the status changes either when a CacheSecurityGroup is modified, or when the CacheSecurityGroups assigned to a Cache Cluster are modified.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CacheParameterGroup' => array(
                                'description' => 'Provides the status of the Cache Parameter Group assigned to the Cache Cluster.',
                                'type' => 'object',
                                'properties' => array(
                                    'CacheParameterGroupName' => array(
                                        'description' => 'The name of the Cache Parameter Group.',
                                        'type' => 'string',
                                    ),
                                    'ParameterApplyStatus' => array(
                                        'description' => 'The status of parameter updates.',
                                        'type' => 'string',
                                    ),
                                    'CacheNodeIdsToReboot' => array(
                                        'description' => 'A list of the Cache Node Ids which need to be rebooted for parameter changes to be applied.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CacheNodeId',
                                            'type' => 'string',
                                            'sentAs' => 'CacheNodeId',
                                        ),
                                    ),
                                ),
                            ),
                            'CacheSubnetGroupName' => array(
                                'description' => 'Specifies the name of the Cache Subnet Group associated with the Cache Cluster.',
                                'type' => 'string',
                            ),
                            'CacheNodes' => array(
                                'description' => 'Specifies the list of Cache Nodes the Cache Cluster contains.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'CacheNode',
                                    'description' => 'A Cache Cluster is made up of one or more Cache Nodes. Each Cache Node is an separate endpoint servicing the memcached protocol.',
                                    'type' => 'object',
                                    'sentAs' => 'CacheNode',
                                    'properties' => array(
                                        'CacheNodeId' => array(
                                            'description' => 'Specifies a Cache Node identifier. This is the unique key that identifies a Cache Node per Customer (AWS account).',
                                            'type' => 'string',
                                        ),
                                        'CacheNodeStatus' => array(
                                            'description' => 'Specifies the current state of this Cache Node.',
                                            'type' => 'string',
                                        ),
                                        'CacheNodeCreateTime' => array(
                                            'description' => 'Provides the date and time the Cache Node was created.',
                                            'type' => 'string',
                                        ),
                                        'Endpoint' => array(
                                            'description' => 'Specifies the endpoint details for a Cache Node.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Address' => array(
                                                    'description' => 'Specifies the DNS address of the Cache Node.',
                                                    'type' => 'string',
                                                ),
                                                'Port' => array(
                                                    'description' => 'Specifies the port that the cache engine is listening on.',
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                        'ParameterGroupStatus' => array(
                                            'description' => 'Specifies the status of the parameter group applied to this Cache Node.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'AutoMinorVersionUpgrade' => array(
                                'description' => 'Indicates that minor version patches are applied automatically.',
                                'type' => 'boolean',
                            ),
                            'SecurityGroups' => array(
                                'description' => 'Specifies the VPC Security Groups associated with the Cache Cluster.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'SecurityGroupMembership',
                                    'description' => 'Represents one or more Cache Security Groups to which a Cache Cluster belongs.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'SecurityGroupId' => array(
                                            'description' => 'The identifier of the Cache Security Group.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the Cache Security Group membership. The status changes whenever a Cache Security Group is modified, or when the Cache Security Groups assigned to a Cache Cluster are modified.',
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
        'CacheEngineVersionMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The identifier returned to allow retrieval of paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CacheEngineVersions' => array(
                    'description' => 'A list of CacheEngineVersion elements.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheEngineVersion',
                        'description' => 'This data type is used as a response element in the action DescribeCacheEngineVersions.',
                        'type' => 'object',
                        'sentAs' => 'CacheEngineVersion',
                        'properties' => array(
                            'Engine' => array(
                                'description' => 'The name of the cache engine.',
                                'type' => 'string',
                            ),
                            'EngineVersion' => array(
                                'description' => 'The version number of the cache engine.',
                                'type' => 'string',
                            ),
                            'CacheParameterGroupFamily' => array(
                                'description' => 'The name of the CacheParameterGroupFamily for the cache engine.',
                                'type' => 'string',
                            ),
                            'CacheEngineDescription' => array(
                                'description' => 'The description of the cache engine.',
                                'type' => 'string',
                            ),
                            'CacheEngineVersionDescription' => array(
                                'description' => 'The description of the cache engine version.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CacheParameterGroupsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CacheParameterGroups' => array(
                    'description' => 'A list of CacheParameterGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheParameterGroup',
                        'description' => 'Contains a set of parameters and their values which can be applied to a Cache Cluster.',
                        'type' => 'object',
                        'sentAs' => 'CacheParameterGroup',
                        'properties' => array(
                            'CacheParameterGroupName' => array(
                                'description' => 'Provides the name of the Cache Parameter Group.',
                                'type' => 'string',
                            ),
                            'CacheParameterGroupFamily' => array(
                                'description' => 'Provides the name of the Cache Parameter Group Family that this Cache Parameter Group is compatible with.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides the customer-specified description for this Cache Parameter Group.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'CacheParameterGroupDetails' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Parameters' => array(
                    'description' => 'A list of Parameter instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'A setting controlling some apsect of the service\'s behavior.',
                        'type' => 'object',
                        'sentAs' => 'Parameter',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'Specifies the name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'Specifies the value of the parameter.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides a description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'Indicates the source of the parameter value.',
                                'type' => 'string',
                            ),
                            'DataType' => array(
                                'description' => 'Specifies the valid data type for the parameter.',
                                'type' => 'string',
                            ),
                            'AllowedValues' => array(
                                'description' => 'Specifies the valid range of values for the parameter.',
                                'type' => 'string',
                            ),
                            'IsModifiable' => array(
                                'description' => 'Indicates whether (true) or not (false) the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                'type' => 'boolean',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'CacheNodeTypeSpecificParameters' => array(
                    'description' => 'A list of CacheNodeTypeSpecificParameter instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheNodeTypeSpecificParameter',
                        'description' => 'A parameter that has a different value for each Cache Node Type it is applied to.',
                        'type' => 'object',
                        'sentAs' => 'CacheNodeTypeSpecificParameter',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'Specifies the name of the parameter.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides a description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'Indicates the source of the parameter value.',
                                'type' => 'string',
                            ),
                            'DataType' => array(
                                'description' => 'Specifies the valid data type for the parameter.',
                                'type' => 'string',
                            ),
                            'AllowedValues' => array(
                                'description' => 'Specifies the valid range of values for the parameter.',
                                'type' => 'string',
                            ),
                            'IsModifiable' => array(
                                'description' => 'Indicates whether (true) or not (false) the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                'type' => 'boolean',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                            'CacheNodeTypeSpecificValues' => array(
                                'description' => 'A list of Cache Node types and their corresponding values for this parameter.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'CacheNodeTypeSpecificValue',
                                    'description' => 'A value that applies only to a certain Cache Node Type.',
                                    'type' => 'object',
                                    'sentAs' => 'CacheNodeTypeSpecificValue',
                                    'properties' => array(
                                        'CacheNodeType' => array(
                                            'description' => 'Specifies the Cache Node type for which this value applies.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'Specifies the value for the Cache Node type.',
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
        'CacheSecurityGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CacheSecurityGroups' => array(
                    'description' => 'A list of CacheSecurityGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheSecurityGroup',
                        'description' => 'Defines a set of EC2 Security groups that are allowed to access a Cache Cluster.',
                        'type' => 'object',
                        'sentAs' => 'CacheSecurityGroup',
                        'properties' => array(
                            'OwnerId' => array(
                                'description' => 'Provides the AWS ID of the owner of a specific Cache Security Group.',
                                'type' => 'string',
                            ),
                            'CacheSecurityGroupName' => array(
                                'description' => 'Specifies the name of the Cache Security Group.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides the description of the Cache Security Group.',
                                'type' => 'string',
                            ),
                            'EC2SecurityGroups' => array(
                                'description' => 'Contains a list of EC2SecurityGroup elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EC2SecurityGroup',
                                    'description' => 'Specifies the current state of this Cache Node.',
                                    'type' => 'object',
                                    'sentAs' => 'EC2SecurityGroup',
                                    'properties' => array(
                                        'Status' => array(
                                            'description' => 'Provides the status of the EC2 Security Group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupName' => array(
                                            'description' => 'Specifies the name of the EC2 Security Group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupOwnerId' => array(
                                            'description' => 'Specifies the AWS ID of the owner of the EC2 Security Group specified in the EC2SecurityGroupName field.',
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
        'CacheSubnetGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CacheSubnetGroups' => array(
                    'description' => 'One or more Cache Subnet Groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CacheSubnetGroup',
                        'description' => 'Contains the result of a successful invocation of the following actions:',
                        'type' => 'object',
                        'sentAs' => 'CacheSubnetGroup',
                        'properties' => array(
                            'CacheSubnetGroupName' => array(
                                'description' => 'Specifies the name of the Cache Subnet Group.',
                                'type' => 'string',
                            ),
                            'CacheSubnetGroupDescription' => array(
                                'description' => 'Provides the description of the Cache Subnet Group.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'Provides the VPC ID of the Cache Subnet Group.',
                                'type' => 'string',
                            ),
                            'Subnets' => array(
                                'description' => 'Contains a list of subnets for this group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Subnet',
                                    'description' => 'Network Subnet associated with a Cache Cluster',
                                    'type' => 'object',
                                    'sentAs' => 'Subnet',
                                    'properties' => array(
                                        'SubnetIdentifier' => array(
                                            'description' => 'Specifies the unique identifier for the Subnet',
                                            'type' => 'string',
                                        ),
                                        'SubnetAvailabilityZone' => array(
                                            'description' => 'Specifies the Availability Zone associated with the Subnet',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'Specifies the name of the Availability Zone',
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
        ),
        'EngineDefaultsWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EngineDefaults' => array(
                    'description' => 'The default Parameters and CacheNodeTypeSpecificParameters for a CacheParameterGroupFamily.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'CacheParameterGroupFamily' => array(
                            'description' => 'Specifies the name of the Cache Parameter Group Family which the engine default parameters apply to.',
                            'type' => 'string',
                        ),
                        'Marker' => array(
                            'description' => 'Provides an identifier to allow retrieval of paginated results.',
                            'type' => 'string',
                        ),
                        'Parameters' => array(
                            'description' => 'Contains a list of engine default parameters.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Parameter',
                                'description' => 'A setting controlling some apsect of the service\'s behavior.',
                                'type' => 'object',
                                'sentAs' => 'Parameter',
                                'properties' => array(
                                    'ParameterName' => array(
                                        'description' => 'Specifies the name of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'ParameterValue' => array(
                                        'description' => 'Specifies the value of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Description' => array(
                                        'description' => 'Provides a description of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Source' => array(
                                        'description' => 'Indicates the source of the parameter value.',
                                        'type' => 'string',
                                    ),
                                    'DataType' => array(
                                        'description' => 'Specifies the valid data type for the parameter.',
                                        'type' => 'string',
                                    ),
                                    'AllowedValues' => array(
                                        'description' => 'Specifies the valid range of values for the parameter.',
                                        'type' => 'string',
                                    ),
                                    'IsModifiable' => array(
                                        'description' => 'Indicates whether (true) or not (false) the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                        'type' => 'boolean',
                                    ),
                                    'MinimumEngineVersion' => array(
                                        'description' => 'The earliest engine version to which the parameter can apply.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'CacheNodeTypeSpecificParameters' => array(
                            'description' => 'A list of CacheNodeTypeSpecificParameter instances.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheNodeTypeSpecificParameter',
                                'description' => 'A parameter that has a different value for each Cache Node Type it is applied to.',
                                'type' => 'object',
                                'sentAs' => 'CacheNodeTypeSpecificParameter',
                                'properties' => array(
                                    'ParameterName' => array(
                                        'description' => 'Specifies the name of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Description' => array(
                                        'description' => 'Provides a description of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Source' => array(
                                        'description' => 'Indicates the source of the parameter value.',
                                        'type' => 'string',
                                    ),
                                    'DataType' => array(
                                        'description' => 'Specifies the valid data type for the parameter.',
                                        'type' => 'string',
                                    ),
                                    'AllowedValues' => array(
                                        'description' => 'Specifies the valid range of values for the parameter.',
                                        'type' => 'string',
                                    ),
                                    'IsModifiable' => array(
                                        'description' => 'Indicates whether (true) or not (false) the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                        'type' => 'boolean',
                                    ),
                                    'MinimumEngineVersion' => array(
                                        'description' => 'The earliest engine version to which the parameter can apply.',
                                        'type' => 'string',
                                    ),
                                    'CacheNodeTypeSpecificValues' => array(
                                        'description' => 'A list of Cache Node types and their corresponding values for this parameter.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CacheNodeTypeSpecificValue',
                                            'description' => 'A value that applies only to a certain Cache Node Type.',
                                            'type' => 'object',
                                            'sentAs' => 'CacheNodeTypeSpecificValue',
                                            'properties' => array(
                                                'CacheNodeType' => array(
                                                    'description' => 'Specifies the Cache Node type for which this value applies.',
                                                    'type' => 'string',
                                                ),
                                                'Value' => array(
                                                    'description' => 'Specifies the value for the Cache Node type.',
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
        ),
        'EventsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker obtained from a previous operation response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Events' => array(
                    'description' => 'A list of Event instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Event',
                        'description' => 'An event represents something interesting that has happened in the system.',
                        'type' => 'object',
                        'sentAs' => 'Event',
                        'properties' => array(
                            'SourceIdentifier' => array(
                                'description' => 'Provides the identifier for the source of the event.',
                                'type' => 'string',
                            ),
                            'SourceType' => array(
                                'description' => 'Specifies the source type for this event.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'Provides the text of this event.',
                                'type' => 'string',
                            ),
                            'Date' => array(
                                'description' => 'Specifies the date and time of the event.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ReservedCacheNodeMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The marker provided for paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedCacheNodes' => array(
                    'description' => 'A list of of reserved Cache Nodes.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedCacheNode',
                        'description' => 'This data type is used as a response element in the DescribeReservedCacheNodes and PurchaseReservedCacheNodesOffering actions.',
                        'type' => 'object',
                        'sentAs' => 'ReservedCacheNode',
                        'properties' => array(
                            'ReservedCacheNodeId' => array(
                                'description' => 'The unique identifier for the reservation.',
                                'type' => 'string',
                            ),
                            'ReservedCacheNodesOfferingId' => array(
                                'description' => 'The offering identifier.',
                                'type' => 'string',
                            ),
                            'CacheNodeType' => array(
                                'description' => 'The cache node type for the reserved Cache Node.',
                                'type' => 'string',
                            ),
                            'StartTime' => array(
                                'description' => 'The time the reservation started.',
                                'type' => 'string',
                            ),
                            'Duration' => array(
                                'description' => 'The duration of the reservation in seconds.',
                                'type' => 'numeric',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The fixed price charged for this reserved Cache Node.',
                                'type' => 'numeric',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The hourly price charged for this reserved Cache Node.',
                                'type' => 'numeric',
                            ),
                            'CacheNodeCount' => array(
                                'description' => 'The number of reserved Cache Nodes.',
                                'type' => 'numeric',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The description of the reserved Cache Node.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The offering type of this reserved Cache Node.',
                                'type' => 'string',
                            ),
                            'State' => array(
                                'description' => 'The state of the reserved Cache Node.',
                                'type' => 'string',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring price charged to run this reserved Cache Node.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'This data type is used as a response element in the DescribeReservedCacheNodes and DescribeReservedCacheNodesOfferings actions.',
                                    'type' => 'object',
                                    'sentAs' => 'RecurringCharge',
                                    'properties' => array(
                                        'RecurringChargeAmount' => array(
                                            'description' => 'The amount of the recurring charge.',
                                            'type' => 'numeric',
                                        ),
                                        'RecurringChargeFrequency' => array(
                                            'description' => 'The frequency of the recurring charge.',
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
        'ReservedCacheNodesOfferingMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker provided for paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedCacheNodesOfferings' => array(
                    'description' => 'A list of reserved Cache Node offerings.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedCacheNodesOffering',
                        'description' => 'This data type is used as a response element in the DescribeReservedCacheNodesOfferings action.',
                        'type' => 'object',
                        'sentAs' => 'ReservedCacheNodesOffering',
                        'properties' => array(
                            'ReservedCacheNodesOfferingId' => array(
                                'description' => 'The offering identifier.',
                                'type' => 'string',
                            ),
                            'CacheNodeType' => array(
                                'description' => 'The Cache Node type for the reserved Cache Node.',
                                'type' => 'string',
                            ),
                            'Duration' => array(
                                'description' => 'The duration of the offering in seconds.',
                                'type' => 'numeric',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The fixed price charged for this offering.',
                                'type' => 'numeric',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The hourly price charged for this offering.',
                                'type' => 'numeric',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The cache engine used by the offering.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The offering type.',
                                'type' => 'string',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring price charged to run this reserved Cache Node.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'This data type is used as a response element in the DescribeReservedCacheNodes and DescribeReservedCacheNodesOfferings actions.',
                                    'type' => 'object',
                                    'sentAs' => 'RecurringCharge',
                                    'properties' => array(
                                        'RecurringChargeAmount' => array(
                                            'description' => 'The amount of the recurring charge.',
                                            'type' => 'numeric',
                                        ),
                                        'RecurringChargeFrequency' => array(
                                            'description' => 'The frequency of the recurring charge.',
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
        'CacheParameterGroupNameMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CacheParameterGroupName' => array(
                    'description' => 'The name of the Cache Parameter Group.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ReservedCacheNodeWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedCacheNode' => array(
                    'description' => 'This data type is used as a response element in the DescribeReservedCacheNodes and PurchaseReservedCacheNodesOffering actions.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ReservedCacheNodeId' => array(
                            'description' => 'The unique identifier for the reservation.',
                            'type' => 'string',
                        ),
                        'ReservedCacheNodesOfferingId' => array(
                            'description' => 'The offering identifier.',
                            'type' => 'string',
                        ),
                        'CacheNodeType' => array(
                            'description' => 'The cache node type for the reserved Cache Node.',
                            'type' => 'string',
                        ),
                        'StartTime' => array(
                            'description' => 'The time the reservation started.',
                            'type' => 'string',
                        ),
                        'Duration' => array(
                            'description' => 'The duration of the reservation in seconds.',
                            'type' => 'numeric',
                        ),
                        'FixedPrice' => array(
                            'description' => 'The fixed price charged for this reserved Cache Node.',
                            'type' => 'numeric',
                        ),
                        'UsagePrice' => array(
                            'description' => 'The hourly price charged for this reserved Cache Node.',
                            'type' => 'numeric',
                        ),
                        'CacheNodeCount' => array(
                            'description' => 'The number of reserved Cache Nodes.',
                            'type' => 'numeric',
                        ),
                        'ProductDescription' => array(
                            'description' => 'The description of the reserved Cache Node.',
                            'type' => 'string',
                        ),
                        'OfferingType' => array(
                            'description' => 'The offering type of this reserved Cache Node.',
                            'type' => 'string',
                        ),
                        'State' => array(
                            'description' => 'The state of the reserved Cache Node.',
                            'type' => 'string',
                        ),
                        'RecurringCharges' => array(
                            'description' => 'The recurring price charged to run this reserved Cache Node.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'RecurringCharge',
                                'description' => 'This data type is used as a response element in the DescribeReservedCacheNodes and DescribeReservedCacheNodesOfferings actions.',
                                'type' => 'object',
                                'sentAs' => 'RecurringCharge',
                                'properties' => array(
                                    'RecurringChargeAmount' => array(
                                        'description' => 'The amount of the recurring charge.',
                                        'type' => 'numeric',
                                    ),
                                    'RecurringChargeFrequency' => array(
                                        'description' => 'The frequency of the recurring charge.',
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
    'iterators' => array(
        'operations' => array(
            'DescribeCacheClusters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'CacheClusters',
            ),
            'DescribeCacheEngineVersions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'CacheEngineVersions',
            ),
            'DescribeCacheParameterGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'CacheParameterGroups',
            ),
            'DescribeCacheParameters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Parameters',
            ),
            'DescribeCacheSecurityGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'CacheSecurityGroups',
            ),
            'DescribeCacheSubnetGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'CacheSubnetGroups',
            ),
            'DescribeEngineDefaultParameters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Parameters',
            ),
            'DescribeEvents' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Events',
            ),
            'DescribeReservedCacheNodes' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedCacheNodes',
            ),
            'DescribeReservedCacheNodesOfferings' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedCacheNodesOfferings',
            ),
        ),
    ),
);
