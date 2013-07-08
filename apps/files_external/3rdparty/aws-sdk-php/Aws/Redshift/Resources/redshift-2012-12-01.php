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
    'apiVersion' => '2012-12-01',
    'endpointPrefix' => 'redshift',
    'serviceFullName' => 'Amazon Redshift',
    'serviceType' => 'query',
    'timestampFormat' => 'iso8601',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Redshift',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'redshift.us-east-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'redshift.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'redshift.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'redshift.ap-northeast-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AuthorizeClusterSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Adds an inbound (ingress) rule to an Amazon Redshift security group. Depending on whether the application accessing your cluster is running on the Internet or an EC2 instance, you can authorize inbound access to either a Classless Interdomain Routing (CIDR) IP address range or an EC2 security group. You can add as many as 20 ingress rules to an Amazon Redshift security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AuthorizeClusterSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the security group to which the ingress rule is added.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CIDRIP' => array(
                    'description' => 'The IP range to be added the Amazon Redshift security group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'description' => 'The EC2 security group to be added the Amazon Redshift security group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'description' => 'The AWS account number of the owner of the security group specified by the EC2SecurityGroupName parameter. The AWS Access Key ID is not an acceptable value.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The state of the cluster security group is not "available".',
                    'class' => 'InvalidClusterSecurityGroupStateException',
                ),
                array(
                    'reason' => 'The specified CIDR block or EC2 security group is already authorized for the specified cluster security group.',
                    'class' => 'AuthorizationAlreadyExistsException',
                ),
                array(
                    'reason' => 'The authorization quota for the cluster security group has been reached.',
                    'class' => 'AuthorizationQuotaExceededException',
                ),
            ),
        ),
        'CopyClusterSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Copies the specified automated cluster snapshot to a new manual cluster snapshot. The source must be an automated snapshot and it must be in the available state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CopyClusterSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'SourceSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier for the source snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TargetSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier given to the new manual snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value specified as a snapshot identifier is already used by an existing snapshot.',
                    'class' => 'ClusterSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'The snapshot identifier does not refer to an existing cluster snapshot.',
                    'class' => 'ClusterSnapshotNotFoundException',
                ),
                array(
                    'reason' => 'The state of the cluster snapshot is not "available".',
                    'class' => 'InvalidClusterSnapshotStateException',
                ),
                array(
                    'reason' => 'The request would result in the user exceeding the allowed number of cluster snapshots.',
                    'class' => 'ClusterSnapshotQuotaExceededException',
                ),
            ),
        ),
        'CreateCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new cluster. To create the cluster in virtual private cloud (VPC), you must provide cluster subnet group name. If you don\'t provide a cluster subnet group name or the cluster security group parameter, Amazon Redshift creates a non-VPC cluster, it associates the default cluster security group with the cluster. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide .',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'DBName' => array(
                    'description' => 'The name of the first database to be created when the cluster is created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'A unique identifier for the cluster. You use this identifier to refer to the cluster for any subsequent cluster operations such as deleting or modifying. The identifier also appears in the Amazon Redshift console.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterType' => array(
                    'description' => 'The type of the cluster. When cluster type is specified as single-node, the NumberOfNodes parameter is not required. multi-node, the NumberOfNodes parameter is required.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NodeType' => array(
                    'required' => true,
                    'description' => 'The node type to be provisioned for the cluster. For information about node types, go to Working with Clusters in the Amazon Redshift Management Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MasterUsername' => array(
                    'required' => true,
                    'description' => 'The user name associated with the master user account for the cluster that is being created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MasterUserPassword' => array(
                    'required' => true,
                    'description' => 'The password associated with the master user account for the cluster that is being created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterSecurityGroups' => array(
                    'description' => 'A list of security groups to be associated with this cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ClusterSecurityGroups.member',
                    'items' => array(
                        'name' => 'ClusterSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'VpcSecurityGroupIds' => array(
                    'description' => 'A list of Virtual Private Cloud (VPC) security groups to be associated with the cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpcSecurityGroupIds.member',
                    'items' => array(
                        'name' => 'VpcSecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'ClusterSubnetGroupName' => array(
                    'description' => 'The name of a cluster subnet group to be associated with this cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The EC2 Availability Zone (AZ) in which you want Amazon Redshift to provision the cluster. For example, if you have several EC2 instances running in a specific Availability Zone, then you might want the cluster to be provisioned in the same zone in order to decrease network latency.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which automated cluster maintenance can occur.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterParameterGroupName' => array(
                    'description' => 'The name of the parameter group to be associated with this cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutomatedSnapshotRetentionPeriod' => array(
                    'description' => 'The number of days that automated snapshots are retained. If the value is 0, automated snapshots are disabled. Even if automated snapshots are disabled, you can still create manual snapshots when you want with CreateClusterSnapshot.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which the cluster accepts incoming connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'ClusterVersion' => array(
                    'description' => 'The version of the Amazon Redshift engine software that you want to deploy on the cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllowVersionUpgrade' => array(
                    'description' => 'If true, upgrades can be applied during the maintenance window to the Amazon Redshift engine that is running on the cluster.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'NumberOfNodes' => array(
                    'description' => 'The number of compute nodes in the cluster. This parameter is required when the ClusterType parameter is specified as multi-node.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'If true, the cluster can be accessed from a public network.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'Encrypted' => array(
                    'description' => 'If true, the data in cluster is encrypted at rest.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The account already has a cluster with the given identifier.',
                    'class' => 'ClusterAlreadyExistsException',
                ),
                array(
                    'reason' => 'The number of nodes specified exceeds the allotted capacity of the cluster.',
                    'class' => 'InsufficientClusterCapacityException',
                ),
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The request would exceed the allowed number of cluster instances for this account.',
                    'class' => 'ClusterQuotaExceededException',
                ),
                array(
                    'reason' => 'The operation would exceed the number of nodes allotted to the account.',
                    'class' => 'NumberOfNodesQuotaExceededException',
                ),
                array(
                    'reason' => 'The operation would exceed the number of nodes allowed for a cluster.',
                    'class' => 'NumberOfNodesPerClusterLimitExceededException',
                ),
                array(
                    'reason' => 'The cluster subnet group name does not refer to an existing cluster subnet group.',
                    'class' => 'ClusterSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'The cluster subnet group does not cover all Availability Zones.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
            ),
        ),
        'CreateClusterParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterParameterGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates an Amazon Redshift parameter group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateClusterParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the cluster parameter group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The Amazon Redshift engine version to which the cluster parameter group applies. The cluster engine version determines the set of parameters.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'A description of the parameter group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The request would result in the user exceeding the allowed number of cluster parameter groups.',
                    'class' => 'ClusterParameterGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'A cluster parameter group with the same name already exists.',
                    'class' => 'ClusterParameterGroupAlreadyExistsException',
                ),
            ),
        ),
        'CreateClusterSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Amazon Redshift security group. You use security groups to control access to non-VPC clusters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateClusterSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the security group. Amazon Redshift stores the value as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'A description for the security group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A cluster security group with the same name already exists.',
                    'class' => 'ClusterSecurityGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request would result in the user exceeding the allowed number of cluster security groups.',
                    'class' => 'ClusterSecurityGroupQuotaExceededException',
                ),
            ),
        ),
        'CreateClusterSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a manual snapshot of the specified cluster. The cluster must be in the "available" state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateClusterSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'SnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'A unique identifier for the snapshot that you are requesting. This identifier must be unique for all snapshots within the AWS account.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The cluster identifier for which you want a snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The value specified as a snapshot identifier is already used by an existing snapshot.',
                    'class' => 'ClusterSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'The specified cluster is not in the available state.',
                    'class' => 'InvalidClusterStateException',
                ),
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
                array(
                    'reason' => 'The request would result in the user exceeding the allowed number of cluster snapshots.',
                    'class' => 'ClusterSnapshotQuotaExceededException',
                ),
            ),
        ),
        'CreateClusterSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Amazon Redshift subnet group. You must provide a list of one or more subnets in your existing Amazon Virtual Private Cloud (Amazon VPC) when creating Amazon Redshift subnet group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateClusterSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the subnet group. Amazon Redshift stores the value as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'A description for the subnet group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'required' => true,
                    'description' => 'An array of VPC subnet IDs. A maximum of 20 subnets can be modified in a single request.',
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
                    'reason' => 'A ClusterSubnetGroupName is already used by an existing cluster subnet group.',
                    'class' => 'ClusterSubnetGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request would result in user exceeding the allowed number of cluster subnet groups.',
                    'class' => 'ClusterSubnetGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'The request would result in user exceeding the allowed number of subnets in a cluster subnet groups.',
                    'class' => 'ClusterSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'The requested subnet is valid, or not all of the subnets are in the same VPC.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'DeleteCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Deletes a previously provisioned cluster. A successful response from the web service indicates that the request was received correctly. If a final cluster snapshot is requested the status of the cluster will be "final-snapshot" while the snapshot is being taken, then it\'s "deleting" once Amazon Redshift begins deleting the cluster. Use DescribeClusters to monitor the status of the deletion. The delete operation cannot be canceled or reverted once submitted. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide .',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier of the cluster to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SkipFinalClusterSnapshot' => array(
                    'description' => 'Determines whether a final snapshot of the cluster is created before Amazon Redshift deletes the cluster. If true, a final cluster snapshot is not created. If false, a final cluster snapshot is created before the cluster is deleted.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'FinalClusterSnapshotIdentifier' => array(
                    'description' => 'The identifier of the final snapshot that is to be created immediately before deleting the cluster. If this parameter is provided, SkipFinalClusterSnapshot must be false.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
                array(
                    'reason' => 'The specified cluster is not in the available state.',
                    'class' => 'InvalidClusterStateException',
                ),
                array(
                    'reason' => 'The value specified as a snapshot identifier is already used by an existing snapshot.',
                    'class' => 'ClusterSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'The request would result in the user exceeding the allowed number of cluster snapshots.',
                    'class' => 'ClusterSnapshotQuotaExceededException',
                ),
            ),
        ),
        'DeleteClusterParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a specified Amazon Redshift parameter group. You cannot delete a parameter group if it is associated with a cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteClusterParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the parameter group to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster parameter group action can not be completed because another task is in progress that involves the parameter group. Wait a few moments and try the operation again.',
                    'class' => 'InvalidClusterParameterGroupStateException',
                ),
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
            ),
        ),
        'DeleteClusterSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes an Amazon Redshift security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteClusterSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the cluster security group to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the cluster security group is not "available".',
                    'class' => 'InvalidClusterSecurityGroupStateException',
                ),
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
            ),
        ),
        'DeleteClusterSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Deletes the specified manual snapshot. The snapshot must be in the "available" state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteClusterSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'SnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The unique identifier of the manual snapshot to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the cluster snapshot is not "available".',
                    'class' => 'InvalidClusterSnapshotStateException',
                ),
                array(
                    'reason' => 'The snapshot identifier does not refer to an existing cluster snapshot.',
                    'class' => 'ClusterSnapshotNotFoundException',
                ),
            ),
        ),
        'DeleteClusterSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified cluster subnet group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteClusterSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the cluster subnet group name to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster subnet group cannot be deleted because it is in use.',
                    'class' => 'InvalidClusterSubnetGroupStateException',
                ),
                array(
                    'reason' => 'The state of the subnet is invalid.',
                    'class' => 'InvalidClusterSubnetStateException',
                ),
                array(
                    'reason' => 'The cluster subnet group name does not refer to an existing cluster subnet group.',
                    'class' => 'ClusterSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DescribeClusterParameterGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterParameterGroupsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of Amazon Redshift parameter groups, including parameter groups you created and the default parameter group. For each parameter group, the response includes the parameter group name, description, and parameter group family name. You can optionally specify a name to retrieve the description of a specific parameter group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterParameterGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'description' => 'The name of a specific parameter group for which to return details. By default, details about all parameter groups and the default parameter group are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of parameter group records to include in the response. If more records exist than the specified MaxRecords value, the response includes a marker that you can use in a subsequent DescribeClusterParameterGroups request to retrieve the next set of records.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeClusterParameterGroups request to indicate the first parameter group that the current request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
            ),
        ),
        'DescribeClusterParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterParameterGroupDetails',
            'responseType' => 'model',
            'summary' => 'Returns a detailed list of parameters contained within the specified Amazon Redshift parameter group. For each parameter the response includes information such as parameter name, description, data type, value, whether the parameter value is modifiable, and so on.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of a cluster parameter group for which to return details.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Source' => array(
                    'description' => 'The parameter types to return. Specify user to show parameters that are different form the default. Similarly, specify engine-default to show parameters that are the same as the default parameter group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, response includes a marker that you can specify in your subsequent request to retrieve remaining result.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned from a previous DescribeClusterParameters request. If this parameter is specified, the response includes only records beyond the specified marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
            ),
        ),
        'DescribeClusterSecurityGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSecurityGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about Amazon Redshift security groups. If the name of a security group is specified, the response will contain only information about only that security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterSecurityGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSecurityGroupName' => array(
                    'description' => 'The name of a cluster security group for which you are requesting details. You can specify either the Marker parameter or a ClusterSecurityGroupName parameter, but not both.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to be included in the response. If more records exist than the specified MaxRecords value, a marker is included in the response, which you can use in a subsequent DescribeClusterSecurityGroups request.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeClusterSecurityGroups request to indicate the first security group that the current request will return. You can specify either the Marker parameter or a ClusterSecurityGroupName parameter, but not both.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
            ),
        ),
        'DescribeClusterSnapshots' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'SnapshotMessage',
            'responseType' => 'model',
            'summary' => 'Returns one or more snapshot objects, which contain metadata about your cluster snapshots. By default, this operation returns information about all snapshots of all clusters that are owned by the AWS account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterSnapshots',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'description' => 'The identifier of the cluster for which information about snapshots is requested.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnapshotIdentifier' => array(
                    'description' => 'The snapshot identifier of the snapshot about which to return information.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnapshotType' => array(
                    'description' => 'The type of snapshots for which you are requesting information. By default, snapshots of all types are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'StartTime' => array(
                    'description' => 'A value that requests only snapshots created at or after the specified time. The time value is specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'A time value that requests only snapshots created at or before the specified time. The time value is specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of snapshot records to include in the response. If more records exist than the specified MaxRecords value, the response returns a marker that you can use in a subsequent DescribeClusterSnapshots request in order to retrieve the next set of snapshot records.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeClusterSnapshots request to indicate the first snapshot that the request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The snapshot identifier does not refer to an existing cluster snapshot.',
                    'class' => 'ClusterSnapshotNotFoundException',
                ),
            ),
        ),
        'DescribeClusterSubnetGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSubnetGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns one or more cluster subnet group objects, which contain metadata about your cluster subnet groups. By default, this operation returns information about all cluster subnet groups that are defined in you AWS account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterSubnetGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSubnetGroupName' => array(
                    'description' => 'The name of the cluster subnet group for which information is requested.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of cluster subnet group records to include in the response. If more records exist than the specified MaxRecords value, the response returns a marker that you can use in a subsequent DescribeClusterSubnetGroups request in order to retrieve the next set of cluster subnet group records.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeClusterSubnetGroups request to indicate the first cluster subnet group that the current request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster subnet group name does not refer to an existing cluster subnet group.',
                    'class' => 'ClusterSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DescribeClusterVersions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterVersionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns descriptions of the available Amazon Redshift cluster versions. You can call this operation even before creating any clusters to learn more about the Amazon Redshift versions. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusterVersions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterVersion' => array(
                    'description' => 'The specific cluster version to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterParameterGroupFamily' => array(
                    'description' => 'The name of a specific cluster parameter group family to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'The marker returned from a previous request. If this parameter is specified, the response includes records beyond the marker only, up to MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeClusters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClustersMessage',
            'responseType' => 'model',
            'summary' => 'Returns properties of provisioned clusters including general cluster properties, cluster database properties, maintenance and backup properties, and security and access properties. This operation supports pagination. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide .',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeClusters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'description' => 'The unique identifier of a cluster whose properties you are requesting. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records that the response can include. If more records exist than the specified MaxRecords value, a marker is included in the response that can be used in a new DescribeClusters request to continue listing results.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeClusters request to indicate the first cluster that the current DescribeClusters request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
            ),
        ),
        'DescribeDefaultClusterParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DefaultClusterParametersWrapper',
            'responseType' => 'model',
            'summary' => 'Returns a list of parameter settings for the specified parameter group family.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDefaultClusterParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The name of the cluster parameter group family.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned from a previous DescribeDefaultClusterParameters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeEvents' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventsMessage',
            'responseType' => 'model',
            'summary' => 'Returns events related to clusters, security groups, snapshots, and parameter groups for the past 14 days. Events specific to a particular cluster, security group, snapshot or parameter group can be obtained by providing the name as a parameter. By default, the past hour of events are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEvents',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'SourceIdentifier' => array(
                    'description' => 'The identifier of the event source for which events will be returned. If this parameter is not specified, then all sources are included in the response.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceType' => array(
                    'description' => 'The event source to retrieve events for. If no value is specified, all events are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'cluster',
                        'cluster-parameter-group',
                        'cluster-security-group',
                        'cluster-snapshot',
                    ),
                ),
                'StartTime' => array(
                    'description' => 'The beginning of the time interval to retrieve events for, specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The end of the time interval for which to retrieve events, specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'Duration' => array(
                    'description' => 'The number of minutes prior to the time of the request for which to retrieve events. For example, if the request is sent at 18:00 and you specify a duration of 60, then only events which have occurred after 17:00 will be returned.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned from a previous DescribeEvents request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeOrderableClusterOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OrderableClusterOptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of orderable cluster options. Before you create a new cluster you can use this operation to find what options are available, such as the EC2 Availability Zones (AZ) in the specific AWS region that you can specify, and the node types you can request. The node types differ by available storage, memory, CPU and price. With the cost involved you might want to obtain a list of cluster options in the specific region and specify values when creating a cluster. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeOrderableClusterOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterVersion' => array(
                    'description' => 'The version filter value. Specify this parameter to show only the available offerings matching the specified version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NodeType' => array(
                    'description' => 'The node type filter value. Specify this parameter to show only the available offerings matching the specified node type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned from a previous DescribeOrderableClusterOptions request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeReservedNodeOfferings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedNodeOfferingsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of the available reserved node offerings by Amazon Redshift with their descriptions including the node type, the fixed and recurring costs of reserving the node and duration the node will be reserved for you. These descriptions help you determine which reserve node offering you want to purchase. You then use the unique offering ID in you call to PurchaseReservedNodeOffering to reserve one or more nodes for your Amazon Redshift cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedNodeOfferings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ReservedNodeOfferingId' => array(
                    'description' => 'The unique identifier for the offering.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeReservedNodeOfferings request to indicate the first offering that the request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedNodeOfferingNotFoundException',
                ),
            ),
        ),
        'DescribeReservedNodes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedNodesMessage',
            'responseType' => 'model',
            'summary' => 'Returns the descriptions of the reserved nodes.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedNodes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ReservedNodeId' => array(
                    'description' => 'Identifier for the node reservation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeReservedNodes request to indicate the first parameter group that the current request will return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified reserved compute node not found.',
                    'class' => 'ReservedNodeNotFoundException',
                ),
            ),
        ),
        'DescribeResize' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ResizeProgressMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about the last resize operation for the specified cluster. If no resize operation has ever been initiated for the specified cluster, a HTTP 404 error is returned. If a resize operation was initiated and completed, the status of the resize remains as SUCCEEDED until the next resize.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeResize',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The unique identifier of a cluster whose resize progress you are requesting. This parameter isn\'t case-sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
                array(
                    'reason' => 'A resize operation for the specified cluster is not found.',
                    'class' => 'ResizeNotFoundException',
                ),
            ),
        ),
        'ModifyCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies the settings for a cluster. For example, you can add another security or parameter group, update the preferred maintenance window, or change the master user password. Resetting a cluster password or modifying the security groups associated with a cluster do not need a reboot. However, modifying parameter group requires a reboot for parameters to take effect. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The unique identifier of the cluster to be modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterType' => array(
                    'description' => 'The new cluster type.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NodeType' => array(
                    'description' => 'The new node type of the cluster. If you specify a new node type, you must also specify the number of nodes parameter also.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NumberOfNodes' => array(
                    'description' => 'The new number of nodes of the cluster. If you specify a new number of nodes, you must also specify the node type parameter also.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'ClusterSecurityGroups' => array(
                    'description' => 'A list of cluster security groups to be authorized on this cluster. This change is asynchronously applied as soon as possible.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ClusterSecurityGroups.member',
                    'items' => array(
                        'name' => 'ClusterSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'VpcSecurityGroupIds' => array(
                    'description' => 'A list of Virtual Private Cloud (VPC) security groups to be associated with the cluster.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpcSecurityGroupIds.member',
                    'items' => array(
                        'name' => 'VpcSecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'MasterUserPassword' => array(
                    'description' => 'The new password for the cluster master user. This change is asynchronously applied as soon as possible. Between the time of the request and the completion of the request, the MasterUserPassword element exists in the PendingModifiedValues element of the operation response. Operations never return the password, so this operation provides a way to regain access to the master user account for a cluster if the password is lost.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterParameterGroupName' => array(
                    'description' => 'The name of the cluster parameter group to apply to this cluster. This change is applied only after the cluster is rebooted. To reboot a cluster use RebootCluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutomatedSnapshotRetentionPeriod' => array(
                    'description' => 'The number of days that automated snapshots are retained. If the value is 0, automated snapshots are disabled. Even if automated snapshots are disabled, you can still create manual snapshots when you want with CreateClusterSnapshot.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which system maintenance can occur, if necessary. If system maintenance is necessary during the window, it may result in an outage.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ClusterVersion' => array(
                    'description' => 'The new version number of the Amazon Redshift engine to upgrade to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllowVersionUpgrade' => array(
                    'description' => 'If true, upgrades will be applied automatically to the cluster during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified cluster is not in the available state.',
                    'class' => 'InvalidClusterStateException',
                ),
                array(
                    'reason' => 'The state of the cluster security group is not "available".',
                    'class' => 'InvalidClusterSecurityGroupStateException',
                ),
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
                array(
                    'reason' => 'The operation would exceed the number of nodes allotted to the account.',
                    'class' => 'NumberOfNodesQuotaExceededException',
                ),
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'The number of nodes specified exceeds the allotted capacity of the cluster.',
                    'class' => 'InsufficientClusterCapacityException',
                ),
                array(
                    'reason' => 'An request option was specified that is not supported.',
                    'class' => 'UnsupportedOptionException',
                ),
            ),
        ),
        'ModifyClusterParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Modifies the parameters of a parameter group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyClusterParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the parameter group to be modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Parameters' => array(
                    'required' => true,
                    'description' => 'An array of parameters to be modified. A maximum of 20 parameters can be modified in a single request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'Describes a parameter in a cluster parameter group.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'The name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value of the parameter.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'The source of the parameter value, such as "engine-default" or "user".',
                                'type' => 'string',
                            ),
                            'DataType' => array(
                                'description' => 'The data type of the parameter.',
                                'type' => 'string',
                            ),
                            'AllowedValues' => array(
                                'description' => 'The valid range of values for the parameter.',
                                'type' => 'string',
                            ),
                            'IsModifiable' => array(
                                'description' => 'If true, the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'The cluster parameter group action can not be completed because another task is in progress that involves the parameter group. Wait a few moments and try the operation again.',
                    'class' => 'InvalidClusterParameterGroupStateException',
                ),
            ),
        ),
        'ModifyClusterSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies a cluster subnet group to include the specified list of VPC subnets. The operation replaces the existing list of subnets with the new list of subnets.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyClusterSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the subnet group to be modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'A text description of the subnet group to be modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'required' => true,
                    'description' => 'An array of VPC subnet IDs. A maximum of 20 subnets can be modified in a single request.',
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
                    'reason' => 'The cluster subnet group name does not refer to an existing cluster subnet group.',
                    'class' => 'ClusterSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'The request would result in user exceeding the allowed number of subnets in a cluster subnet groups.',
                    'class' => 'ClusterSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'A specified subnet is already in use by another cluster.',
                    'class' => 'SubnetAlreadyInUseException',
                ),
                array(
                    'reason' => 'The requested subnet is valid, or not all of the subnets are in the same VPC.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'PurchaseReservedNodeOffering' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedNodeWrapper',
            'responseType' => 'model',
            'summary' => 'Allows you to purchase reserved nodes. Amazon Redshift offers a predefined set of reserved node offerings. You can purchase one of the offerings. You can call the DescribeReservedNodeOfferings API to obtain the available reserved node offerings. You can call this API by providing a specific reserved node offering and the number of nodes you want to reserve.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PurchaseReservedNodeOffering',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ReservedNodeOfferingId' => array(
                    'required' => true,
                    'description' => 'The unique identifier of the reserved node offering you want to purchase.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NodeCount' => array(
                    'description' => 'The number of reserved nodes you want to purchase.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedNodeOfferingNotFoundException',
                ),
                array(
                    'reason' => 'User already has a reservation with the given identifier.',
                    'class' => 'ReservedNodeAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would exceed the user\'s compute node quota.',
                    'class' => 'ReservedNodeQuotaExceededException',
                ),
            ),
        ),
        'RebootCluster' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Reboots a cluster. This action is taken as soon as possible. It results in a momentary outage to the cluster, during which the cluster status is set to rebooting. A cluster event is created when the reboot is completed. Any pending cluster modifications (see ModifyCluster) are applied at this reboot. For more information about managing clusters, go to Amazon Redshift Clusters in the Amazon Redshift Management Guide',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RebootCluster',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The cluster identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified cluster is not in the available state.',
                    'class' => 'InvalidClusterStateException',
                ),
                array(
                    'reason' => 'The ClusterIdentifier parameter does not refer to an existing cluster.',
                    'class' => 'ClusterNotFoundException',
                ),
            ),
        ),
        'ResetClusterParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Sets one or more parameters of the specified parameter group to their default values and sets the source values of the parameters to "engine-default". To reset the entire parameter group specify the ResetAllParameters parameter. For parameter changes to take effect you must reboot any associated clusters.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetClusterParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the cluster parameter group to be reset.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ResetAllParameters' => array(
                    'description' => 'If true, all parameters in the specified parameter group will be reset to their default values.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'Parameters' => array(
                    'description' => 'An array of names of parameters to be reset. If ResetAllParameters option is not used, then at least one parameter name must be supplied.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'Describes a parameter in a cluster parameter group.',
                        'type' => 'object',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'The name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value of the parameter.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'The source of the parameter value, such as "engine-default" or "user".',
                                'type' => 'string',
                            ),
                            'DataType' => array(
                                'description' => 'The data type of the parameter.',
                                'type' => 'string',
                            ),
                            'AllowedValues' => array(
                                'description' => 'The valid range of values for the parameter.',
                                'type' => 'string',
                            ),
                            'IsModifiable' => array(
                                'description' => 'If true, the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster parameter group action can not be completed because another task is in progress that involves the parameter group. Wait a few moments and try the operation again.',
                    'class' => 'InvalidClusterParameterGroupStateException',
                ),
                array(
                    'reason' => 'The parameter group name does not refer to an existing parameter group.',
                    'class' => 'ClusterParameterGroupNotFoundException',
                ),
            ),
        ),
        'RestoreFromClusterSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new cluster from a snapshot. Amazon Redshift creates the resulting cluster with the same configuration as the original cluster from which the snapshot was created, except that the new cluster is created with the default cluster security and parameter group. After Amazon Redshift creates the cluster you can use the ModifyCluster API to associate a different security group and different parameter group with the restored cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RestoreFromClusterSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier of the cluster that will be created from restoring the snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The name of the snapshot from which to create the new cluster. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which the cluster accepts connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The Amazon EC2 Availability Zone in which to restore the cluster.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllowVersionUpgrade' => array(
                    'description' => 'If true, upgrades can be applied during the maintenance window to the Amazon Redshift engine that is running on the cluster.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'ClusterSubnetGroupName' => array(
                    'description' => 'The name of the subnet group where you want to cluster restored.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'If true, the cluster can be accessed from a public network.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The account already has a cluster with the given identifier.',
                    'class' => 'ClusterAlreadyExistsException',
                ),
                array(
                    'reason' => 'The snapshot identifier does not refer to an existing cluster snapshot.',
                    'class' => 'ClusterSnapshotNotFoundException',
                ),
                array(
                    'reason' => 'The request would exceed the allowed number of cluster instances for this account.',
                    'class' => 'ClusterQuotaExceededException',
                ),
                array(
                    'reason' => 'The number of nodes specified exceeds the allotted capacity of the cluster.',
                    'class' => 'InsufficientClusterCapacityException',
                ),
                array(
                    'reason' => 'The state of the cluster snapshot is not "available".',
                    'class' => 'InvalidClusterSnapshotStateException',
                ),
                array(
                    'reason' => 'The restore is invalid.',
                    'class' => 'InvalidRestoreException',
                ),
                array(
                    'reason' => 'The operation would exceed the number of nodes allotted to the account.',
                    'class' => 'NumberOfNodesQuotaExceededException',
                ),
                array(
                    'reason' => 'The operation would exceed the number of nodes allowed for a cluster.',
                    'class' => 'NumberOfNodesPerClusterLimitExceededException',
                ),
            ),
        ),
        'RevokeClusterSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ClusterSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Revokes an ingress rule in an Amazon Redshift security group for a previously authorized IP range or Amazon EC2 security group. To add an ingress rule, see AuthorizeClusterSecurityGroupIngress. For information about managing security groups, go to Amazon Redshift Cluster Security Groups in the Amazon Redshift Management Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RevokeClusterSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2012-12-01',
                ),
                'ClusterSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the security Group from which to revoke the ingress rule.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CIDRIP' => array(
                    'description' => 'The IP range for which to revoke access. This range must be a valid Classless Inter-Domain Routing (CIDR) block of IP addresses. If CIDRIP is specified, EC2SecurityGroupName and EC2SecurityGroupOwnerId cannot be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'description' => 'The name of the EC2 Security Group whose access is to be revoked. If EC2SecurityGroupName is specified, EC2SecurityGroupOwnerId must also be provided and CIDRIP cannot be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'description' => 'The AWS account number of the owner of the security group specified in the EC2SecurityGroupName parameter. The AWS access key ID is not an acceptable value. If EC2SecurityGroupOwnerId is specified, EC2SecurityGroupName must also be provided. and CIDRIP cannot be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The cluster security group name does not refer to an existing cluster security group.',
                    'class' => 'ClusterSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The specified CIDR IP range or EC2 security group is not authorized for the specified cluster security group.',
                    'class' => 'AuthorizationNotFoundException',
                ),
                array(
                    'reason' => 'The state of the cluster security group is not "available".',
                    'class' => 'InvalidClusterSecurityGroupStateException',
                ),
            ),
        ),
    ),
    'models' => array(
        'ClusterSecurityGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ClusterSecurityGroup' => array(
                    'description' => 'Describes a security group.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ClusterSecurityGroupName' => array(
                            'description' => 'The name of the cluster security group to which the operation was applied.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'A description of the security group.',
                            'type' => 'string',
                        ),
                        'EC2SecurityGroups' => array(
                            'description' => 'A list of EC2 security groups that are permitted to access clusters associated with this cluster security group.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'EC2SecurityGroup',
                                'description' => 'Describes an Amazon EC2 security group.',
                                'type' => 'object',
                                'sentAs' => 'EC2SecurityGroup',
                                'properties' => array(
                                    'Status' => array(
                                        'description' => 'The status of the EC2 security group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupName' => array(
                                        'description' => 'The name of the EC2 Security Group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupOwnerId' => array(
                                        'description' => 'The AWS ID of the owner of the EC2 security group specified in the EC2SecurityGroupName field.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'IPRanges' => array(
                            'description' => 'A list of IP ranges (CIDR blocks) that are permitted to access clusters associated with this cluster security group.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'IPRange',
                                'description' => 'Describes an IP range used in a security group.',
                                'type' => 'object',
                                'sentAs' => 'IPRange',
                                'properties' => array(
                                    'Status' => array(
                                        'description' => 'The status of the IP range, for example, "authorized".',
                                        'type' => 'string',
                                    ),
                                    'CIDRIP' => array(
                                        'description' => 'The IP range in Classless Inter-Domain Routing (CIDR) notation.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'SnapshotWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Snapshot' => array(
                    'description' => 'Describes a snapshot.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'SnapshotIdentifier' => array(
                            'description' => 'The snapshot identifier that is provided in the request.',
                            'type' => 'string',
                        ),
                        'ClusterIdentifier' => array(
                            'description' => 'The identifier of the cluster for which the snapshot was taken.',
                            'type' => 'string',
                        ),
                        'SnapshotCreateTime' => array(
                            'description' => 'The time (UTC) when Amazon Redshift began the snapshot. A snapshot contains a copy of the cluster data as of this exact time.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The snapshot status. The value of the status depends on the API operation used. CreateClusterSnapshot and CopyClusterSnapshot returns status as "creating". DescribeClusterSnapshots returns status as "creating", "available", or "failed". DeleteClusterSnapshot returns status as "deleted".',
                            'type' => 'string',
                        ),
                        'Port' => array(
                            'description' => 'The port that the cluster is listening on.',
                            'type' => 'numeric',
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'The Availability Zone in which the cluster was created.',
                            'type' => 'string',
                        ),
                        'ClusterCreateTime' => array(
                            'description' => 'The time (UTC) when the cluster was originally created.',
                            'type' => 'string',
                        ),
                        'MasterUsername' => array(
                            'description' => 'The master user name for the cluster.',
                            'type' => 'string',
                        ),
                        'ClusterVersion' => array(
                            'description' => 'The version ID of the Amazon Redshift engine that is running on the cluster.',
                            'type' => 'string',
                        ),
                        'SnapshotType' => array(
                            'description' => 'The snapshot type. Snapshots created using CreateClusterSnapshot and CopyClusterSnapshot will be of type "manual".',
                            'type' => 'string',
                        ),
                        'NodeType' => array(
                            'description' => 'The node type of the nodes in the cluster.',
                            'type' => 'string',
                        ),
                        'NumberOfNodes' => array(
                            'description' => 'The number of nodes in the cluster.',
                            'type' => 'numeric',
                        ),
                        'DBName' => array(
                            'description' => 'The name of the database that was created when the cluster was created.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'The VPC identifier of the cluster if the snapshot is from a cluster in a VPC. Otherwise, this field is not in the output.',
                            'type' => 'string',
                        ),
                        'Encrypted' => array(
                            'description' => 'If true, the data in the snapshot is encrypted at rest.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
            ),
        ),
        'ClusterWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Cluster' => array(
                    'description' => 'Describes a cluster.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ClusterIdentifier' => array(
                            'description' => 'The unique identifier of the cluster.',
                            'type' => 'string',
                        ),
                        'NodeType' => array(
                            'description' => 'The node type for the nodes in the cluster.',
                            'type' => 'string',
                        ),
                        'ClusterStatus' => array(
                            'description' => 'The current state of this cluster. Possible values include available, creating, deleting, rebooting, and resizing.',
                            'type' => 'string',
                        ),
                        'ModifyStatus' => array(
                            'description' => 'The status of a modify operation, if any, initiated for the cluster.',
                            'type' => 'string',
                        ),
                        'MasterUsername' => array(
                            'description' => 'The master user name for the cluster. This name is used to connect to the database that is specified in DBName.',
                            'type' => 'string',
                        ),
                        'DBName' => array(
                            'description' => 'The name of the initial database that was created when the cluster was created. This same name is returned for the life of the cluster. If an initial database was not specified, a database named "dev" was created by default.',
                            'type' => 'string',
                        ),
                        'Endpoint' => array(
                            'description' => 'The connection endpoint.',
                            'type' => 'object',
                            'properties' => array(
                                'Address' => array(
                                    'description' => 'The DNS address of the Cluster.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'The port that the database engine is listening on.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'ClusterCreateTime' => array(
                            'description' => 'The date and time that the cluster was created.',
                            'type' => 'string',
                        ),
                        'AutomatedSnapshotRetentionPeriod' => array(
                            'description' => 'The number of days that automatic cluster snapshots are retained.',
                            'type' => 'numeric',
                        ),
                        'ClusterSecurityGroups' => array(
                            'description' => 'A list of cluster security group that are associated with the cluster. Each security group is represented by an element that contains ClusterSecurityGroup.Name and ClusterSecurityGroup.Status subelements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ClusterSecurityGroup',
                                'description' => 'Describes a security group.',
                                'type' => 'object',
                                'sentAs' => 'ClusterSecurityGroup',
                                'properties' => array(
                                    'ClusterSecurityGroupName' => array(
                                        'description' => 'The name of the cluster security group.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the cluster security group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'VpcSecurityGroups' => array(
                            'description' => 'A list of Virtual Private Cloud (VPC) security groups that are associated with the cluster. This parameter is returned only if the cluster is in a VPC.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'VpcSecurityGroup',
                                'description' => 'Describes the members of a VPC security group.',
                                'type' => 'object',
                                'sentAs' => 'VpcSecurityGroup',
                                'properties' => array(
                                    'VpcSecurityGroupId' => array(
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'ClusterParameterGroups' => array(
                            'description' => 'The list of cluster parameter groups that are associated with this cluster.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ClusterParameterGroup',
                                'description' => 'Describes the status of a parameter group.',
                                'type' => 'object',
                                'sentAs' => 'ClusterParameterGroup',
                                'properties' => array(
                                    'ParameterGroupName' => array(
                                        'description' => 'The name of the cluster parameter group.',
                                        'type' => 'string',
                                    ),
                                    'ParameterApplyStatus' => array(
                                        'description' => 'The status of parameter updates.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'ClusterSubnetGroupName' => array(
                            'description' => 'The name of the subnet group that is associated with the cluster. This parameter is valid only when the cluster is in a VPC.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'The identifier of the VPC the cluster is in, if the cluster is in a VPC.',
                            'type' => 'string',
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'The name of the Availability Zone in which the cluster is located.',
                            'type' => 'string',
                        ),
                        'PreferredMaintenanceWindow' => array(
                            'description' => 'The weekly time range (in UTC) during which system maintenance can occur.',
                            'type' => 'string',
                        ),
                        'PendingModifiedValues' => array(
                            'description' => 'If present, changes to the cluster are pending. Specific pending changes are identified by subelements.',
                            'type' => 'object',
                            'properties' => array(
                                'MasterUserPassword' => array(
                                    'description' => 'The pending or in-progress change of the master credentials for the cluster.',
                                    'type' => 'string',
                                ),
                                'NodeType' => array(
                                    'description' => 'The pending or in-progress change of the cluster\'s node type.',
                                    'type' => 'string',
                                ),
                                'NumberOfNodes' => array(
                                    'description' => 'The pending or in-progress change of the number nodes in the cluster.',
                                    'type' => 'numeric',
                                ),
                                'ClusterType' => array(
                                    'description' => 'The pending or in-progress change of the cluster type.',
                                    'type' => 'string',
                                ),
                                'ClusterVersion' => array(
                                    'description' => 'The pending or in-progress change of the service version.',
                                    'type' => 'string',
                                ),
                                'AutomatedSnapshotRetentionPeriod' => array(
                                    'description' => 'The pending or in-progress change of the automated snapshot retention period.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'ClusterVersion' => array(
                            'description' => 'The version ID of the Amazon Redshift engine that is running on the cluster.',
                            'type' => 'string',
                        ),
                        'AllowVersionUpgrade' => array(
                            'description' => 'If true, version upgrades will be applied automatically to the cluster during the maintenance window.',
                            'type' => 'boolean',
                        ),
                        'NumberOfNodes' => array(
                            'description' => 'The number of compute nodes in the cluster.',
                            'type' => 'numeric',
                        ),
                        'PubliclyAccessible' => array(
                            'description' => 'If true, the cluster can be accessed from a public network.',
                            'type' => 'boolean',
                        ),
                        'Encrypted' => array(
                            'description' => 'If true, data in cluster is encrypted at rest.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
            ),
        ),
        'ClusterParameterGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ClusterParameterGroup' => array(
                    'description' => 'Describes a parameter group.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ParameterGroupName' => array(
                            'description' => 'The name of the cluster parameter group.',
                            'type' => 'string',
                        ),
                        'ParameterGroupFamily' => array(
                            'description' => 'The name of the cluster parameter group family that this cluster parameter group is compatible with.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'The description of the parameter group.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'ClusterSubnetGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ClusterSubnetGroup' => array(
                    'description' => 'Describes a subnet group.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ClusterSubnetGroupName' => array(
                            'description' => 'The name of the cluster subnet group.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'The description of the cluster subnet group.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'The VPC ID of the cluster subnet group.',
                            'type' => 'string',
                        ),
                        'SubnetGroupStatus' => array(
                            'description' => 'The status of the cluster subnet group. Possible values are Complete, Incomplete and Invalid.',
                            'type' => 'string',
                        ),
                        'Subnets' => array(
                            'description' => 'A list of the VPC Subnet elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Subnet',
                                'description' => 'Describes a subnet.',
                                'type' => 'object',
                                'sentAs' => 'Subnet',
                                'properties' => array(
                                    'SubnetIdentifier' => array(
                                        'description' => 'The identifier of the subnet.',
                                        'type' => 'string',
                                    ),
                                    'SubnetAvailabilityZone' => array(
                                        'description' => 'Describes an availability zone.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Name' => array(
                                                'description' => 'The name of the availability zone.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'SubnetStatus' => array(
                                        'description' => 'The status of the subnet.',
                                        'type' => 'string',
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
        'ClusterParameterGroupsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker at which to continue listing cluster parameter groups in a new request. The response returns a marker if there are more parameter groups to list than returned in the response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ParameterGroups' => array(
                    'description' => 'A list of ClusterParameterGroup instances. Each instance describes one cluster parameter group.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ClusterParameterGroup',
                        'description' => 'Describes a parameter group.',
                        'type' => 'object',
                        'sentAs' => 'ClusterParameterGroup',
                        'properties' => array(
                            'ParameterGroupName' => array(
                                'description' => 'The name of the cluster parameter group.',
                                'type' => 'string',
                            ),
                            'ParameterGroupFamily' => array(
                                'description' => 'The name of the cluster parameter group family that this cluster parameter group is compatible with.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'The description of the parameter group.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ClusterParameterGroupDetails' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Parameters' => array(
                    'description' => 'A list of Parameter instances. Each instance lists the parameters of one cluster parameter group.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'Describes a parameter in a cluster parameter group.',
                        'type' => 'object',
                        'sentAs' => 'Parameter',
                        'properties' => array(
                            'ParameterName' => array(
                                'description' => 'The name of the parameter.',
                                'type' => 'string',
                            ),
                            'ParameterValue' => array(
                                'description' => 'The value of the parameter.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'The source of the parameter value, such as "engine-default" or "user".',
                                'type' => 'string',
                            ),
                            'DataType' => array(
                                'description' => 'The data type of the parameter.',
                                'type' => 'string',
                            ),
                            'AllowedValues' => array(
                                'description' => 'The valid range of values for the parameter.',
                                'type' => 'string',
                            ),
                            'IsModifiable' => array(
                                'description' => 'If true, the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                'type' => 'boolean',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'A marker that indicates the first parameter group that a subsequent DescribeClusterParameterGroups request will return. The response returns a marker only if there are more parameter groups details to list than the current response can return.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ClusterSecurityGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker at which to continue listing cluster security groups in a new request. The response returns a marker if there are more security groups to list than could be returned in the response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ClusterSecurityGroups' => array(
                    'description' => 'A list of ClusterSecurityGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ClusterSecurityGroup',
                        'description' => 'Describes a security group.',
                        'type' => 'object',
                        'sentAs' => 'ClusterSecurityGroup',
                        'properties' => array(
                            'ClusterSecurityGroupName' => array(
                                'description' => 'The name of the cluster security group to which the operation was applied.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the security group.',
                                'type' => 'string',
                            ),
                            'EC2SecurityGroups' => array(
                                'description' => 'A list of EC2 security groups that are permitted to access clusters associated with this cluster security group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EC2SecurityGroup',
                                    'description' => 'Describes an Amazon EC2 security group.',
                                    'type' => 'object',
                                    'sentAs' => 'EC2SecurityGroup',
                                    'properties' => array(
                                        'Status' => array(
                                            'description' => 'The status of the EC2 security group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupName' => array(
                                            'description' => 'The name of the EC2 Security Group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupOwnerId' => array(
                                            'description' => 'The AWS ID of the owner of the EC2 security group specified in the EC2SecurityGroupName field.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IPRanges' => array(
                                'description' => 'A list of IP ranges (CIDR blocks) that are permitted to access clusters associated with this cluster security group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IPRange',
                                    'description' => 'Describes an IP range used in a security group.',
                                    'type' => 'object',
                                    'sentAs' => 'IPRange',
                                    'properties' => array(
                                        'Status' => array(
                                            'description' => 'The status of the IP range, for example, "authorized".',
                                            'type' => 'string',
                                        ),
                                        'CIDRIP' => array(
                                            'description' => 'The IP range in Classless Inter-Domain Routing (CIDR) notation.',
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
        'SnapshotMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker that indicates the first snapshot that a subsequent DescribeClusterSnapshots request will return. The response returns a marker only if there are more snapshots to list than the current response can return.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Snapshots' => array(
                    'description' => 'A list of Snapshot instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Snapshot',
                        'description' => 'Describes a snapshot.',
                        'type' => 'object',
                        'sentAs' => 'Snapshot',
                        'properties' => array(
                            'SnapshotIdentifier' => array(
                                'description' => 'The snapshot identifier that is provided in the request.',
                                'type' => 'string',
                            ),
                            'ClusterIdentifier' => array(
                                'description' => 'The identifier of the cluster for which the snapshot was taken.',
                                'type' => 'string',
                            ),
                            'SnapshotCreateTime' => array(
                                'description' => 'The time (UTC) when Amazon Redshift began the snapshot. A snapshot contains a copy of the cluster data as of this exact time.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The snapshot status. The value of the status depends on the API operation used. CreateClusterSnapshot and CopyClusterSnapshot returns status as "creating". DescribeClusterSnapshots returns status as "creating", "available", or "failed". DeleteClusterSnapshot returns status as "deleted".',
                                'type' => 'string',
                            ),
                            'Port' => array(
                                'description' => 'The port that the cluster is listening on.',
                                'type' => 'numeric',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The Availability Zone in which the cluster was created.',
                                'type' => 'string',
                            ),
                            'ClusterCreateTime' => array(
                                'description' => 'The time (UTC) when the cluster was originally created.',
                                'type' => 'string',
                            ),
                            'MasterUsername' => array(
                                'description' => 'The master user name for the cluster.',
                                'type' => 'string',
                            ),
                            'ClusterVersion' => array(
                                'description' => 'The version ID of the Amazon Redshift engine that is running on the cluster.',
                                'type' => 'string',
                            ),
                            'SnapshotType' => array(
                                'description' => 'The snapshot type. Snapshots created using CreateClusterSnapshot and CopyClusterSnapshot will be of type "manual".',
                                'type' => 'string',
                            ),
                            'NodeType' => array(
                                'description' => 'The node type of the nodes in the cluster.',
                                'type' => 'string',
                            ),
                            'NumberOfNodes' => array(
                                'description' => 'The number of nodes in the cluster.',
                                'type' => 'numeric',
                            ),
                            'DBName' => array(
                                'description' => 'The name of the database that was created when the cluster was created.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'The VPC identifier of the cluster if the snapshot is from a cluster in a VPC. Otherwise, this field is not in the output.',
                                'type' => 'string',
                            ),
                            'Encrypted' => array(
                                'description' => 'If true, the data in the snapshot is encrypted at rest.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ClusterSubnetGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker at which to continue listing cluster subnet groups in a new request. A marker is returned if there are more cluster subnet groups to list than were returned in the response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ClusterSubnetGroups' => array(
                    'description' => 'A list of ClusterSubnetGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ClusterSubnetGroup',
                        'description' => 'Describes a subnet group.',
                        'type' => 'object',
                        'sentAs' => 'ClusterSubnetGroup',
                        'properties' => array(
                            'ClusterSubnetGroupName' => array(
                                'description' => 'The name of the cluster subnet group.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'The description of the cluster subnet group.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'The VPC ID of the cluster subnet group.',
                                'type' => 'string',
                            ),
                            'SubnetGroupStatus' => array(
                                'description' => 'The status of the cluster subnet group. Possible values are Complete, Incomplete and Invalid.',
                                'type' => 'string',
                            ),
                            'Subnets' => array(
                                'description' => 'A list of the VPC Subnet elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Subnet',
                                    'description' => 'Describes a subnet.',
                                    'type' => 'object',
                                    'sentAs' => 'Subnet',
                                    'properties' => array(
                                        'SubnetIdentifier' => array(
                                            'description' => 'The identifier of the subnet.',
                                            'type' => 'string',
                                        ),
                                        'SubnetAvailabilityZone' => array(
                                            'description' => 'Describes an availability zone.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'The name of the availability zone.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                        'SubnetStatus' => array(
                                            'description' => 'The status of the subnet.',
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
        'ClusterVersionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The identifier returned to allow retrieval of paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ClusterVersions' => array(
                    'description' => 'A list of Version elements.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ClusterVersion',
                        'description' => 'Describes a cluster version, including the parameter group family and description of the version.',
                        'type' => 'object',
                        'sentAs' => 'ClusterVersion',
                        'properties' => array(
                            'ClusterVersion' => array(
                                'description' => 'The version number used by the cluster.',
                                'type' => 'string',
                            ),
                            'ClusterParameterGroupFamily' => array(
                                'description' => 'The name of the cluster parameter group family for the cluster.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'The description of the cluster version.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ClustersMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker at which to continue listing clusters in a new request. A marker is returned if there are more clusters to list than were returned in the response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Clusters' => array(
                    'description' => 'A list of Cluster objects, where each object describes one cluster.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Cluster',
                        'description' => 'Describes a cluster.',
                        'type' => 'object',
                        'sentAs' => 'Cluster',
                        'properties' => array(
                            'ClusterIdentifier' => array(
                                'description' => 'The unique identifier of the cluster.',
                                'type' => 'string',
                            ),
                            'NodeType' => array(
                                'description' => 'The node type for the nodes in the cluster.',
                                'type' => 'string',
                            ),
                            'ClusterStatus' => array(
                                'description' => 'The current state of this cluster. Possible values include available, creating, deleting, rebooting, and resizing.',
                                'type' => 'string',
                            ),
                            'ModifyStatus' => array(
                                'description' => 'The status of a modify operation, if any, initiated for the cluster.',
                                'type' => 'string',
                            ),
                            'MasterUsername' => array(
                                'description' => 'The master user name for the cluster. This name is used to connect to the database that is specified in DBName.',
                                'type' => 'string',
                            ),
                            'DBName' => array(
                                'description' => 'The name of the initial database that was created when the cluster was created. This same name is returned for the life of the cluster. If an initial database was not specified, a database named "dev" was created by default.',
                                'type' => 'string',
                            ),
                            'Endpoint' => array(
                                'description' => 'The connection endpoint.',
                                'type' => 'object',
                                'properties' => array(
                                    'Address' => array(
                                        'description' => 'The DNS address of the Cluster.',
                                        'type' => 'string',
                                    ),
                                    'Port' => array(
                                        'description' => 'The port that the database engine is listening on.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'ClusterCreateTime' => array(
                                'description' => 'The date and time that the cluster was created.',
                                'type' => 'string',
                            ),
                            'AutomatedSnapshotRetentionPeriod' => array(
                                'description' => 'The number of days that automatic cluster snapshots are retained.',
                                'type' => 'numeric',
                            ),
                            'ClusterSecurityGroups' => array(
                                'description' => 'A list of cluster security group that are associated with the cluster. Each security group is represented by an element that contains ClusterSecurityGroup.Name and ClusterSecurityGroup.Status subelements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ClusterSecurityGroup',
                                    'description' => 'Describes a security group.',
                                    'type' => 'object',
                                    'sentAs' => 'ClusterSecurityGroup',
                                    'properties' => array(
                                        'ClusterSecurityGroupName' => array(
                                            'description' => 'The name of the cluster security group.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the cluster security group.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'VpcSecurityGroups' => array(
                                'description' => 'A list of Virtual Private Cloud (VPC) security groups that are associated with the cluster. This parameter is returned only if the cluster is in a VPC.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'VpcSecurityGroup',
                                    'description' => 'Describes the members of a VPC security group.',
                                    'type' => 'object',
                                    'sentAs' => 'VpcSecurityGroup',
                                    'properties' => array(
                                        'VpcSecurityGroupId' => array(
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'ClusterParameterGroups' => array(
                                'description' => 'The list of cluster parameter groups that are associated with this cluster.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ClusterParameterGroup',
                                    'description' => 'Describes the status of a parameter group.',
                                    'type' => 'object',
                                    'sentAs' => 'ClusterParameterGroup',
                                    'properties' => array(
                                        'ParameterGroupName' => array(
                                            'description' => 'The name of the cluster parameter group.',
                                            'type' => 'string',
                                        ),
                                        'ParameterApplyStatus' => array(
                                            'description' => 'The status of parameter updates.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'ClusterSubnetGroupName' => array(
                                'description' => 'The name of the subnet group that is associated with the cluster. This parameter is valid only when the cluster is in a VPC.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'The identifier of the VPC the cluster is in, if the cluster is in a VPC.',
                                'type' => 'string',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The name of the Availability Zone in which the cluster is located.',
                                'type' => 'string',
                            ),
                            'PreferredMaintenanceWindow' => array(
                                'description' => 'The weekly time range (in UTC) during which system maintenance can occur.',
                                'type' => 'string',
                            ),
                            'PendingModifiedValues' => array(
                                'description' => 'If present, changes to the cluster are pending. Specific pending changes are identified by subelements.',
                                'type' => 'object',
                                'properties' => array(
                                    'MasterUserPassword' => array(
                                        'description' => 'The pending or in-progress change of the master credentials for the cluster.',
                                        'type' => 'string',
                                    ),
                                    'NodeType' => array(
                                        'description' => 'The pending or in-progress change of the cluster\'s node type.',
                                        'type' => 'string',
                                    ),
                                    'NumberOfNodes' => array(
                                        'description' => 'The pending or in-progress change of the number nodes in the cluster.',
                                        'type' => 'numeric',
                                    ),
                                    'ClusterType' => array(
                                        'description' => 'The pending or in-progress change of the cluster type.',
                                        'type' => 'string',
                                    ),
                                    'ClusterVersion' => array(
                                        'description' => 'The pending or in-progress change of the service version.',
                                        'type' => 'string',
                                    ),
                                    'AutomatedSnapshotRetentionPeriod' => array(
                                        'description' => 'The pending or in-progress change of the automated snapshot retention period.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'ClusterVersion' => array(
                                'description' => 'The version ID of the Amazon Redshift engine that is running on the cluster.',
                                'type' => 'string',
                            ),
                            'AllowVersionUpgrade' => array(
                                'description' => 'If true, version upgrades will be applied automatically to the cluster during the maintenance window.',
                                'type' => 'boolean',
                            ),
                            'NumberOfNodes' => array(
                                'description' => 'The number of compute nodes in the cluster.',
                                'type' => 'numeric',
                            ),
                            'PubliclyAccessible' => array(
                                'description' => 'If true, the cluster can be accessed from a public network.',
                                'type' => 'boolean',
                            ),
                            'Encrypted' => array(
                                'description' => 'If true, data in cluster is encrypted at rest.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DefaultClusterParametersWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DefaultClusterParameters' => array(
                    'description' => 'Describes the default cluster parameters for a parameter group family.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ParameterGroupFamily' => array(
                            'description' => 'The name of the cluster parameter group family to which the engine default parameters apply.',
                            'type' => 'string',
                        ),
                        'Marker' => array(
                            'description' => 'An identifier to allow retrieval of paginated results.',
                            'type' => 'string',
                        ),
                        'Parameters' => array(
                            'description' => 'The list of cluster default parameters.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Parameter',
                                'description' => 'Describes a parameter in a cluster parameter group.',
                                'type' => 'object',
                                'sentAs' => 'Parameter',
                                'properties' => array(
                                    'ParameterName' => array(
                                        'description' => 'The name of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'ParameterValue' => array(
                                        'description' => 'The value of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Description' => array(
                                        'description' => 'A description of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'Source' => array(
                                        'description' => 'The source of the parameter value, such as "engine-default" or "user".',
                                        'type' => 'string',
                                    ),
                                    'DataType' => array(
                                        'description' => 'The data type of the parameter.',
                                        'type' => 'string',
                                    ),
                                    'AllowedValues' => array(
                                        'description' => 'The valid range of values for the parameter.',
                                        'type' => 'string',
                                    ),
                                    'IsModifiable' => array(
                                        'description' => 'If true, the parameter can be modified. Some parameters have security or operational implications that prevent them from being changed.',
                                        'type' => 'boolean',
                                    ),
                                    'MinimumEngineVersion' => array(
                                        'description' => 'The earliest engine version to which the parameter can apply.',
                                        'type' => 'string',
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
                    'description' => 'A marker at which to continue listing events in a new request. The response returns a marker if there are more events to list than returned in the response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Events' => array(
                    'description' => 'A list of Event instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Event',
                        'description' => 'Describes an event.',
                        'type' => 'object',
                        'sentAs' => 'Event',
                        'properties' => array(
                            'SourceIdentifier' => array(
                                'description' => 'The identifier for the source of the event.',
                                'type' => 'string',
                            ),
                            'SourceType' => array(
                                'description' => 'The source type for this event.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'The text of this event.',
                                'type' => 'string',
                            ),
                            'Date' => array(
                                'description' => 'The date and time of the event.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'OrderableClusterOptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OrderableClusterOptions' => array(
                    'description' => 'An OrderableClusterOption structure containing information about orderable options for the Cluster.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'OrderableClusterOption',
                        'description' => 'Describes an orderable cluster option.',
                        'type' => 'object',
                        'sentAs' => 'OrderableClusterOption',
                        'properties' => array(
                            'ClusterVersion' => array(
                                'description' => 'The version of the orderable cluster.',
                                'type' => 'string',
                            ),
                            'ClusterType' => array(
                                'description' => 'The cluster type, for example multi-node.',
                                'type' => 'string',
                            ),
                            'NodeType' => array(
                                'description' => 'The node type for the orderable cluster.',
                                'type' => 'string',
                            ),
                            'AvailabilityZones' => array(
                                'description' => 'A list of availability zones for the orderable cluster.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AvailabilityZone',
                                    'description' => 'Describes an availability zone.',
                                    'type' => 'object',
                                    'sentAs' => 'AvailabilityZone',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the availability zone.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'A marker that can be used to retrieve paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ReservedNodeOfferingsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional marker returned by a previous DescribeReservedNodeOfferings request to indicate the first reserved node offering that the request will return.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedNodeOfferings' => array(
                    'description' => 'A list of reserved node offerings.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedNodeOffering',
                        'description' => 'Describes a reserved node offering.',
                        'type' => 'object',
                        'sentAs' => 'ReservedNodeOffering',
                        'properties' => array(
                            'ReservedNodeOfferingId' => array(
                                'description' => 'The offering identifier.',
                                'type' => 'string',
                            ),
                            'NodeType' => array(
                                'description' => 'The node type offered by the reserved node offering.',
                                'type' => 'string',
                            ),
                            'Duration' => array(
                                'description' => 'The duration, in seconds, for which the offering will reserve the node.',
                                'type' => 'numeric',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The upfront fixed charge you will pay to purchase the specific reserved node offering.',
                                'type' => 'numeric',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The rate you are charged for each hour the cluster that is using the offering is running.',
                                'type' => 'numeric',
                            ),
                            'CurrencyCode' => array(
                                'description' => 'The currency code for the compute nodes offering.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The anticipated utilization of the reserved node, as defined in the reserved node offering.',
                                'type' => 'string',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The charge to your account regardless of whether you are creating any clusters using the node offering. Recurring charges are only in effect for heavy-utilization reserved nodes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'Describes a recurring charge.',
                                    'type' => 'object',
                                    'sentAs' => 'RecurringCharge',
                                    'properties' => array(
                                        'RecurringChargeAmount' => array(
                                            'description' => 'The amount charged per the period of time specified by the recurring charge frequency.',
                                            'type' => 'numeric',
                                        ),
                                        'RecurringChargeFrequency' => array(
                                            'description' => 'The frequency at which the recurring charge amount is applied.',
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
        'ReservedNodesMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'A marker that can be used to retrieve paginated results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedNodes' => array(
                    'description' => 'The list of reserved nodes.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedNode',
                        'description' => 'Describes a reserved node.',
                        'type' => 'object',
                        'sentAs' => 'ReservedNode',
                        'properties' => array(
                            'ReservedNodeId' => array(
                                'description' => 'The unique identifier for the reservation.',
                                'type' => 'string',
                            ),
                            'ReservedNodeOfferingId' => array(
                                'description' => 'The identifier for the reserved node offering.',
                                'type' => 'string',
                            ),
                            'NodeType' => array(
                                'description' => 'The node type of the reserved node.',
                                'type' => 'string',
                            ),
                            'StartTime' => array(
                                'description' => 'The time the reservation started. You purchase a reserved node offering for a duration. This is the start time of that duration.',
                                'type' => 'string',
                            ),
                            'Duration' => array(
                                'description' => 'The duration of the node reservation in seconds.',
                                'type' => 'numeric',
                            ),
                            'FixedPrice' => array(
                                'description' => 'The fixed cost Amazon Redshift charged you for this reserved node.',
                                'type' => 'numeric',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The hourly rate Amazon Redshift charge you for this reserved node.',
                                'type' => 'numeric',
                            ),
                            'CurrencyCode' => array(
                                'description' => 'The currency code for the reserved cluster.',
                                'type' => 'string',
                            ),
                            'NodeCount' => array(
                                'description' => 'The number of reserved compute nodes.',
                                'type' => 'numeric',
                            ),
                            'State' => array(
                                'description' => 'The state of the reserved Compute Node.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The anticipated utilization of the reserved node, as defined in the reserved node offering.',
                                'type' => 'string',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring charges for the reserved node.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'Describes a recurring charge.',
                                    'type' => 'object',
                                    'sentAs' => 'RecurringCharge',
                                    'properties' => array(
                                        'RecurringChargeAmount' => array(
                                            'description' => 'The amount charged per the period of time specified by the recurring charge frequency.',
                                            'type' => 'numeric',
                                        ),
                                        'RecurringChargeFrequency' => array(
                                            'description' => 'The frequency at which the recurring charge amount is applied.',
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
        'ResizeProgressMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TargetNodeType' => array(
                    'description' => 'The node type that the cluster will have after the resize is complete.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'TargetNumberOfNodes' => array(
                    'description' => 'The number of nodes that the cluster will have after the resize is complete.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'TargetClusterType' => array(
                    'description' => 'The cluster type after the resize is complete.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The status of the resize operation.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ImportTablesCompleted' => array(
                    'description' => 'The names of tables that have been completely imported .',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'ImportTablesInProgress' => array(
                    'description' => 'The names of tables that are being currently imported.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'ImportTablesNotStarted' => array(
                    'description' => 'The names of tables that have not been yet imported.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'ClusterParameterGroupNameMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ParameterGroupName' => array(
                    'description' => 'The name of the cluster parameter group.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ParameterGroupStatus' => array(
                    'description' => 'The status of the parameter group. For example, if you made a change to a parameter group name-value pair, then the change could be pending a reboot of an associated cluster.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ReservedNodeWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedNode' => array(
                    'description' => 'Describes a reserved node.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ReservedNodeId' => array(
                            'description' => 'The unique identifier for the reservation.',
                            'type' => 'string',
                        ),
                        'ReservedNodeOfferingId' => array(
                            'description' => 'The identifier for the reserved node offering.',
                            'type' => 'string',
                        ),
                        'NodeType' => array(
                            'description' => 'The node type of the reserved node.',
                            'type' => 'string',
                        ),
                        'StartTime' => array(
                            'description' => 'The time the reservation started. You purchase a reserved node offering for a duration. This is the start time of that duration.',
                            'type' => 'string',
                        ),
                        'Duration' => array(
                            'description' => 'The duration of the node reservation in seconds.',
                            'type' => 'numeric',
                        ),
                        'FixedPrice' => array(
                            'description' => 'The fixed cost Amazon Redshift charged you for this reserved node.',
                            'type' => 'numeric',
                        ),
                        'UsagePrice' => array(
                            'description' => 'The hourly rate Amazon Redshift charge you for this reserved node.',
                            'type' => 'numeric',
                        ),
                        'CurrencyCode' => array(
                            'description' => 'The currency code for the reserved cluster.',
                            'type' => 'string',
                        ),
                        'NodeCount' => array(
                            'description' => 'The number of reserved compute nodes.',
                            'type' => 'numeric',
                        ),
                        'State' => array(
                            'description' => 'The state of the reserved Compute Node.',
                            'type' => 'string',
                        ),
                        'OfferingType' => array(
                            'description' => 'The anticipated utilization of the reserved node, as defined in the reserved node offering.',
                            'type' => 'string',
                        ),
                        'RecurringCharges' => array(
                            'description' => 'The recurring charges for the reserved node.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'RecurringCharge',
                                'description' => 'Describes a recurring charge.',
                                'type' => 'object',
                                'sentAs' => 'RecurringCharge',
                                'properties' => array(
                                    'RecurringChargeAmount' => array(
                                        'description' => 'The amount charged per the period of time specified by the recurring charge frequency.',
                                        'type' => 'numeric',
                                    ),
                                    'RecurringChargeFrequency' => array(
                                        'description' => 'The frequency at which the recurring charge amount is applied.',
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
            'DescribeClusterParameterGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ParameterGroups',
            ),
            'DescribeClusterParameters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Parameters',
            ),
            'DescribeClusterSecurityGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ClusterSecurityGroups',
            ),
            'DescribeClusterSnapshots' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Snapshots',
            ),
            'DescribeClusterSubnetGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ClusterSubnetGroups',
            ),
            'DescribeClusterVersions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ClusterVersions',
            ),
            'DescribeClusters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Clusters',
            ),
            'DescribeDefaultClusterParameters' => array(
                'token_param' => 'Marker',
                'limit_key' => 'MaxRecords',
            ),
            'DescribeEvents' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Events',
            ),
            'DescribeOrderableClusterOptions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'OrderableClusterOptions',
            ),
            'DescribeReservedNodeOfferings' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedNodeOfferings',
            ),
            'DescribeReservedNodes' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedNodes',
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'acceptor.type' => 'output',
        ),
        '__ClusterState' => array(
            'interval' => 60,
            'max_attempts' => 30,
            'operation' => 'DescribeClusters',
            'acceptor.path' => 'Clusters/*/ClusterStatus',
        ),
        'ClusterAvailable' => array(
            'extends' => '__ClusterState',
            'success.value' => 'available',
            'failure.value' => array(
                'deleting',
            ),
            'ignore_errors' => array(
                'ClusterNotFound',
            ),
        ),
        'ClusterDeleted' => array(
            'extends' => '__ClusterState',
            'success.type' => 'error',
            'success.value' => 'ClusterNotFound',
            'failure.value' => array(
                'creating',
                'rebooting',
            ),
        ),
        'SnapshotAvailable' => array(
            'interval' => 15,
            'max_attempts' => 20,
            'operation' => 'DescribeClusterSnapshots',
            'acceptor.path' => 'Snapshots/*/Status',
            'success.value' => 'available',
            'failure.value' => array(
                'failed',
                'deleted',
            ),
        ),
    ),
);
