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
    'apiVersion' => '2013-05-15',
    'endpointPrefix' => 'rds',
    'serviceFullName' => 'Amazon Relational Database Service',
    'serviceAbbreviation' => 'Amazon RDS',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'Rds',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'rds.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddSourceIdentifierToSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionWrapper',
            'responseType' => 'model',
            'summary' => 'Adds a source identifier to an existing RDS event notification subscription.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddSourceIdentifierToSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'required' => true,
                    'description' => 'The name of the RDS event notification subscription you want to add a source identifier to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier of the event source to be added. An identifier must begin with a letter and must contain only ASCII letters, digits, and hyphens; it cannot end with a hyphen or contain two consecutive hyphens.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The subscription name does not exist.',
                    'class' => 'SubscriptionNotFoundException',
                ),
                array(
                    'reason' => 'The requested source could not be found.',
                    'class' => 'SourceNotFoundException',
                ),
            ),
        ),
        'AddTagsToResource' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adds metadata tags to a DB Instance. These tags can also be used with cost allocation reporting to track cost associated with a DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddTagsToResource',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ResourceName' => array(
                    'required' => true,
                    'description' => 'The DB Instance the tags will be added to. This value is an Amazon Resource Name (ARN). For information about creating an ARN, see Constructing an RDS Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Tags' => array(
                    'required' => true,
                    'description' => 'The tags to be assigned to the DB Instance.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tags.member',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'Metadata assigned to a DB Instance consisting of a key-value pair.',
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'A key is the required name of the tag. The string value can be from 1 to 128 Unicode characters in length and cannot be prefixed with "aws:". The string may only contain only the set of Unicode letters, digits, white-space, \'_\', \'.\', \'/\', \'=\', \'+\', \'-\' (Java regex: "^([\\\\p{L}\\\\p{Z}\\\\p{N}_.:/=+\\\\-]*)$").',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'A value is the optional value of the tag. The string value can be from 1 to 256 Unicode characters in length and cannot be prefixed with "aws:". The string may only contain only the set of Unicode letters, digits, white-space, \'_\', \'.\', \'/\', \'=\', \'+\', \'-\' (Java regex: "^([\\\\p{L}\\\\p{Z}\\\\p{N}_.:/=+\\\\-]*)$").',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
            ),
        ),
        'AuthorizeDBSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Enables ingress to a DBSecurityGroup using one of two forms of authorization. First, EC2 or VPC Security Groups can be added to the DBSecurityGroup if the application using the database is running on EC2 or VPC instances. Second, IP ranges are available if the application accessing your database is running on the Internet. Required parameters for this API are one of CIDR range, EC2SecurityGroupId for VPC, or (EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId for non-VPC).',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AuthorizeDBSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Security Group to add authorization to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CIDRIP' => array(
                    'description' => 'The IP range to authorize.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'description' => 'Name of the EC2 Security Group to authorize. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupId' => array(
                    'description' => 'Id of the EC2 Security Group to authorize. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'description' => 'AWS Account Number of the owner of the EC2 Security Group specified in the EC2SecurityGroupName parameter. The AWS Access Key ID is not an acceptable value. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'The state of the DB Security Group does not allow deletion.',
                    'class' => 'InvalidDBSecurityGroupStateException',
                ),
                array(
                    'reason' => 'The specified CIDRIP or EC2 security group is already authorized for the specified DB security group.',
                    'class' => 'AuthorizationAlreadyExistsException',
                ),
                array(
                    'reason' => 'Database security group authorization quota has been reached.',
                    'class' => 'AuthorizationQuotaExceededException',
                ),
            ),
        ),
        'CopyDBSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Copies the specified DBSnapshot. The source DBSnapshot must be in the "available" state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CopyDBSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SourceDBSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier for the source DB snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TargetDBSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier for the copied snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSnapshotIdentifier is already used by an existing snapshot.',
                    'class' => 'DBSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
                array(
                    'reason' => 'The state of the DB Security Snapshot does not allow deletion.',
                    'class' => 'InvalidDBSnapshotStateException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Snapshots.',
                    'class' => 'SnapshotQuotaExceededException',
                ),
            ),
        ),
        'CreateDBInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new DB instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBName' => array(
                    'description' => 'The meaning of this parameter differs according to the database engine you use.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier. This parameter is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllocatedStorage' => array(
                    'required' => true,
                    'description' => 'The amount of storage (in gigabytes) to be initially allocated for the database instance.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'required' => true,
                    'description' => 'The compute and memory capacity of the DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Engine' => array(
                    'required' => true,
                    'description' => 'The name of the database engine to be used for this instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MasterUsername' => array(
                    'required' => true,
                    'description' => 'The name of master user for the client DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MasterUserPassword' => array(
                    'required' => true,
                    'description' => 'The password for the master database user. Can be any printable ASCII character except "/", "\\", or "@".',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSecurityGroups' => array(
                    'description' => 'A list of DB Security Groups to associate with this DB Instance.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DBSecurityGroups.member',
                    'items' => array(
                        'name' => 'DBSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'VpcSecurityGroupIds' => array(
                    'description' => 'A list of EC2 VPC Security Groups to associate with this DB Instance.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpcSecurityGroupIds.member',
                    'items' => array(
                        'name' => 'VpcSecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'AvailabilityZone' => array(
                    'description' => 'The EC2 Availability Zone that the database instance will be created in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSubnetGroupName' => array(
                    'description' => 'A DB Subnet Group to associate with this DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which system maintenance can occur.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBParameterGroupName' => array(
                    'description' => 'The name of the DB Parameter Group to associate with this DB instance. If this argument is omitted, the default DBParameterGroup for the specified engine will be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BackupRetentionPeriod' => array(
                    'description' => 'The number of days for which automated backups are retained. Setting this parameter to a positive number enables backups. Setting this parameter to 0 disables automated backups.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PreferredBackupWindow' => array(
                    'description' => 'The daily time range during which automated backups are created if automated backups are enabled, using the BackupRetentionPeriod parameter.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which the database accepts connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MultiAZ' => array(
                    'description' => 'Specifies if the DB Instance is a Multi-AZ deployment. You cannot set the AvailabilityZone parameter if the MultiAZ parameter is set to true.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The version number of the database engine to use.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor engine upgrades will be applied automatically to the DB Instance during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'LicenseModel' => array(
                    'description' => 'License model information for this DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Iops' => array(
                    'description' => 'The amount of Provisioned IOPS (input/output operations per second) to be initially allocated for the DB Instance.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'OptionGroupName' => array(
                    'description' => 'Indicates that the DB Instance should be associated with the specified option group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CharacterSetName' => array(
                    'description' => 'For supported engines, indicates that the DB Instance should be associated with the specified CharacterSet.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'User already has a DB Instance with the given identifier.',
                    'class' => 'DBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'Specified DB Instance class is not available in the specified Availability Zone.',
                    'class' => 'InsufficientDBInstanceCapacityException',
                ),
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Instances.',
                    'class' => 'InstanceQuotaExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed amount of storage available across all DB Instances.',
                    'class' => 'StorageQuotaExceededException',
                ),
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
                array(
                    'reason' => 'DB Subnet Group does not cover all availability zones after it is created because users\' change.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'reason' => 'Provisioned IOPS not available in the specified Availability Zone.',
                    'class' => 'ProvisionedIopsNotAvailableInAZException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'CreateDBInstanceReadReplica' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a DB Instance that acts as a Read Replica of a source DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBInstanceReadReplica',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier of the Read Replica. This is the unique key that identifies a DB Instance. This parameter is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceDBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier of the DB Instance that will act as the source for the Read Replica. Each DB Instance can have up to five Read Replicas.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The compute and memory capacity of the Read Replica.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The Amazon EC2 Availability Zone that the Read Replica will be created in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number that the DB Instance uses for connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor engine upgrades will be applied automatically to the Read Replica during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'Iops' => array(
                    'description' => 'The amount of Provisioned IOPS (input/output operations per second) to be initially allocated for the DB Instance.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'OptionGroupName' => array(
                    'description' => 'The option group the DB instance will be associated with. If omitted, the default Option Group for the engine specified will be used.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'User already has a DB Instance with the given identifier.',
                    'class' => 'DBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'Specified DB Instance class is not available in the specified Availability Zone.',
                    'class' => 'InsufficientDBInstanceCapacityException',
                ),
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Instances.',
                    'class' => 'InstanceQuotaExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed amount of storage available across all DB Instances.',
                    'class' => 'StorageQuotaExceededException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
                array(
                    'reason' => 'DB Subnet Group does not cover all availability zones after it is created because users\' change.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'reason' => 'Provisioned IOPS not available in the specified Availability Zone.',
                    'class' => 'ProvisionedIopsNotAvailableInAZException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'CreateDBParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBParameterGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new DB Parameter Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The DB Parameter Group Family name. A DB Parameter Group can be associated with one and only one DB Parameter Group Family, and can be applied only to a DB Instance running a database engine and engine version compatible with that DB Parameter Group Family.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'required' => true,
                    'description' => 'The description for the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Parameter Groups.',
                    'class' => 'DBParameterGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'A DB Parameter Group with the same name exists.',
                    'class' => 'DBParameterGroupAlreadyExistsException',
                ),
            ),
        ),
        'CreateDBSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new DB Security Group. DB Security Groups control access to a DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the DB Security Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSecurityGroupDescription' => array(
                    'required' => true,
                    'description' => 'The description for the DB Security Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'A database security group with the name specified in DBSecurityGroupName already exists.',
                    'class' => 'DBSecurityGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Security Groups.',
                    'class' => 'DBSecurityGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'A DB security group is not allowed for this action.',
                    'class' => 'DBSecurityGroupNotSupportedException',
                ),
            ),
        ),
        'CreateDBSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a DBSnapshot. The source DBInstance must be in "available" state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier for the DB Snapshot.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier. This is the unique key that identifies a DB Instance. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSnapshotIdentifier is already used by an existing snapshot.',
                    'class' => 'DBSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Snapshots.',
                    'class' => 'SnapshotQuotaExceededException',
                ),
            ),
        ),
        'CreateDBSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new DB subnet group. DB subnet groups must contain at least one subnet in at least two AZs in the region.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateDBSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the DB Subnet Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSubnetGroupDescription' => array(
                    'required' => true,
                    'description' => 'The description for the DB Subnet Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'required' => true,
                    'description' => 'The EC2 Subnet IDs for the DB Subnet Group.',
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
                    'reason' => 'DBSubnetGroupName is already used by an existing DBSubnetGroup.',
                    'class' => 'DBSubnetGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Subnet Groups.',
                    'class' => 'DBSubnetGroupQuotaExceededException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of subnets in a DB subnet Groups.',
                    'class' => 'DBSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'CreateEventSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionWrapper',
            'responseType' => 'model',
            'summary' => 'Creates an RDS event notification subscription. This action requires a topic ARN (Amazon Resource Name) created by either the RDS console, the SNS console, or the SNS API. To obtain an ARN with SNS, you must create a topic in Amazon SNS and subscribe to the topic. The ARN is displayed in the SNS console.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateEventSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'required' => true,
                    'description' => 'The name of the subscription.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnsTopicArn' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the SNS topic created for event notification. The ARN is created by Amazon SNS when you create a topic and subscribe to it.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceType' => array(
                    'description' => 'The type of source that will be generating the events. For example, if you want to be notified of events generated by a DB instance, you would set this parameter to db-instance. if this value is not specified, all events are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EventCategories' => array(
                    'description' => 'A list of event categories for a SourceType that you want to subscribe to. You can see a list of the categories for a given SourceType in the Events topic in the Amazon RDS User Guide or by using the DescribeEventCategories action.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'EventCategories.member',
                    'items' => array(
                        'name' => 'EventCategory',
                        'type' => 'string',
                    ),
                ),
                'SourceIds' => array(
                    'description' => 'The list of identifiers of the event sources for which events will be returned. If not specified, then all sources are included in the response. An identifier must begin with a letter and must contain only ASCII letters, digits, and hyphens; it cannot end with a hyphen or contain two consecutive hyphens.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SourceIds.member',
                    'items' => array(
                        'name' => 'SourceId',
                        'type' => 'string',
                    ),
                ),
                'Enabled' => array(
                    'description' => 'A Boolean value; set to true to activate the subscription, set to false to create the subscription but not active it.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You have reached the maximum number of event subscriptions.',
                    'class' => 'EventSubscriptionQuotaExceededException',
                ),
                array(
                    'reason' => 'The supplied subscription name already exists.',
                    'class' => 'SubscriptionAlreadyExistException',
                ),
                array(
                    'reason' => 'SNS has responded that there is a problem with the SND topic specified.',
                    'class' => 'SNSInvalidTopicException',
                ),
                array(
                    'reason' => 'You do not have permission to publish to the SNS topic ARN.',
                    'class' => 'SNSNoAuthorizationException',
                ),
                array(
                    'reason' => 'The SNS topic ARN does not exist.',
                    'class' => 'SNSTopicArnNotFoundException',
                ),
                array(
                    'reason' => 'The supplied category does not exist.',
                    'class' => 'SubscriptionCategoryNotFoundException',
                ),
                array(
                    'reason' => 'The requested source could not be found.',
                    'class' => 'SourceNotFoundException',
                ),
            ),
        ),
        'CreateOptionGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OptionGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new Option Group. You can create up to 20 option groups.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateOptionGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'OptionGroupName' => array(
                    'required' => true,
                    'description' => 'Specifies the name of the option group to be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EngineName' => array(
                    'required' => true,
                    'description' => 'Specifies the name of the engine that this option group should be associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MajorEngineVersion' => array(
                    'required' => true,
                    'description' => 'Specifies the major version of the engine that this option group should be associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OptionGroupDescription' => array(
                    'required' => true,
                    'description' => 'The description of the option group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The option group you are trying to create already exists.',
                    'class' => 'OptionGroupAlreadyExistsException',
                ),
                array(
                    'reason' => 'The quota of 20 option groups was exceeded for this AWS account.',
                    'class' => 'OptionGroupQuotaExceededException',
                ),
            ),
        ),
        'DeleteDBInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'The DeleteDBInstance action deletes a previously provisioned DB instance. A successful response from the web service indicates the request was received correctly. When you delete a DB instance, all automated backups for that instance are deleted and cannot be recovered. Manual DB Snapshots of the DB instance to be deleted are not deleted.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDBInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier for the DB Instance to be deleted. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SkipFinalSnapshot' => array(
                    'description' => 'Determines whether a final DB Snapshot is created before the DB Instance is deleted. If true is specified, no DBSnapshot is created. If false is specified, a DB Snapshot is created before the DB Instance is deleted.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'FinalDBSnapshotIdentifier' => array(
                    'description' => 'The DBSnapshotIdentifier of the new DBSnapshot created when SkipFinalSnapshot is set to false.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier is already used by an existing snapshot.',
                    'class' => 'DBSnapshotAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Snapshots.',
                    'class' => 'SnapshotQuotaExceededException',
                ),
            ),
        ),
        'DeleteDBParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a specified DBParameterGroup. The DBParameterGroup cannot be associated with any RDS instances to be deleted.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDBParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The DB Parameter Group cannot be deleted because it is in use.',
                    'class' => 'InvalidDBParameterGroupStateException',
                ),
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
            ),
        ),
        'DeleteDBSecurityGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a DB Security Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDBSecurityGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Security Group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the DB Security Group does not allow deletion.',
                    'class' => 'InvalidDBSecurityGroupStateException',
                ),
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
            ),
        ),
        'DeleteDBSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSnapshotWrapper',
            'responseType' => 'model',
            'summary' => 'Deletes a DBSnapshot.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDBSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'The DBSnapshot identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The state of the DB Security Snapshot does not allow deletion.',
                    'class' => 'InvalidDBSnapshotStateException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
            ),
        ),
        'DeleteDBSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a DB subnet group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteDBSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the database subnet group to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The DB Subnet Group cannot be deleted because it is in use.',
                    'class' => 'InvalidDBSubnetGroupStateException',
                ),
                array(
                    'reason' => 'The DB subnet is not in the available state.',
                    'class' => 'InvalidDBSubnetStateException',
                ),
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DeleteEventSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionWrapper',
            'responseType' => 'model',
            'summary' => 'Deletes an RDS event notification subscription.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteEventSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'required' => true,
                    'description' => 'The name of the RDS event notification subscription you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The subscription name does not exist.',
                    'class' => 'SubscriptionNotFoundException',
                ),
                array(
                    'reason' => 'This error can occur if someone else is modifying a subscription. You should retry the action.',
                    'class' => 'InvalidEventSubscriptionStateException',
                ),
            ),
        ),
        'DeleteOptionGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes an existing Option Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteOptionGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'OptionGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the option group to be deleted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
                array(
                    'reason' => 'The Option Group is not in the available state.',
                    'class' => 'InvalidOptionGroupStateException',
                ),
            ),
        ),
        'DescribeDBEngineVersions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBEngineVersionMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of the available DB engines.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBEngineVersions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'Engine' => array(
                    'description' => 'The database engine to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The database engine version to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBParameterGroupFamily' => array(
                    'description' => 'The name of a specific DB Parameter Group family to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a pagination token called a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DefaultOnly' => array(
                    'description' => 'Indicates that only the default version of the specified engine or engine and major version combination is returned.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'ListSupportedCharacterSets' => array(
                    'description' => 'If this parameter is specified, and if the requested engine supports the CharacterSetName parameter for CreateDBInstance, the response includes a list of supported character sets for each engine version.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeDBInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about provisioned RDS instances. This API supports pagination.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'description' => 'The user-supplied instance identifier. If this parameter is specified, information from only the specific DB Instance is returned. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBInstances request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
            ),
        ),
        'DescribeDBLogFiles' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeDBLogFilesResponse',
            'responseType' => 'model',
            'summary' => 'Returns a list of DB log files for the DB instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBLogFiles',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'description' => 'The customer-assigned name of the DB Instance that contains the log files you want to list.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'FilenameContains' => array(
                    'description' => 'Filters the available log files for log file names that contain the specified string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'FileLastWritten' => array(
                    'description' => 'Filters the available log files for files written since the specified date, in POSIX timestamp format.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'FileSize' => array(
                    'description' => 'Filters the available log files for files larger than the specified size.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'The pagination token provided in the previous request. If this parameter is specified the response includes only records beyond the marker, up to MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
            ),
        ),
        'DescribeDBParameterGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBParameterGroupsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of DBParameterGroup descriptions. If a DBParameterGroupName is specified, the list will contain only the description of the specified DBParameterGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBParameterGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'description' => 'The name of a specific DB Parameter Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBParameterGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
            ),
        ),
        'DescribeDBParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBParameterGroupDetails',
            'responseType' => 'model',
            'summary' => 'Returns the detailed parameter list for a particular DBParameterGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of a specific DB Parameter Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Source' => array(
                    'description' => 'The parameter types to return.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBParameters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
            ),
        ),
        'DescribeDBSecurityGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSecurityGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of DBSecurityGroup descriptions. If a DBSecurityGroupName is specified, the list will contain only the descriptions of the specified DBSecurityGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBSecurityGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSecurityGroupName' => array(
                    'description' => 'The name of the DB Security Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBSecurityGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
            ),
        ),
        'DescribeDBSnapshots' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSnapshotMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about DBSnapshots. This API supports pagination.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBSnapshots',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'description' => 'A DB Instance Identifier to retrieve the list of DB Snapshots for. Cannot be used in conjunction with DBSnapshotIdentifier. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSnapshotIdentifier' => array(
                    'description' => 'A specific DB Snapshot Identifier to describe. Cannot be used in conjunction with DBInstanceIdentifier. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnapshotType' => array(
                    'description' => 'An optional snapshot type for which snapshots will be returned. If not specified, the returned results will include snapshots of all types.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBSnapshots request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
            ),
        ),
        'DescribeDBSubnetGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSubnetGroupMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of DBSubnetGroup descriptions. If a DBSubnetGroupName is specified, the list will contain only the descriptions of the specified DBSubnetGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeDBSubnetGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSubnetGroupName' => array(
                    'description' => 'The name of the DB Subnet Group to return details for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeDBSubnetGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
            ),
        ),
        'DescribeEngineDefaultParameters' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EngineDefaultsWrapper',
            'responseType' => 'model',
            'summary' => 'Returns the default engine and system parameter information for the specified database engine.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEngineDefaultParameters',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupFamily' => array(
                    'required' => true,
                    'description' => 'The name of the DB Parameter Group Family.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeEngineDefaultParameters request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeEventCategories' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventCategoriesMessage',
            'responseType' => 'model',
            'summary' => 'Displays a list of categories for all event source types, or, if specified, for a specified source type. You can see a list of the event categories and source types in the Events topic in the Amazon RDS User Guide.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEventCategories',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SourceType' => array(
                    'description' => 'The type of source that will be generating the events.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeEventSubscriptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Lists all the subscription descriptions for a customer account. The description for a subscription includes SubscriptionName, SNSTopicARN, CustomerID, SourceType, SourceID, CreationTime, and Status.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEventSubscriptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'description' => 'The name of the RDS event notification subscription you want to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeOrderableDBInstanceOptions request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The subscription name does not exist.',
                    'class' => 'SubscriptionNotFoundException',
                ),
            ),
        ),
        'DescribeEvents' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventsMessage',
            'responseType' => 'model',
            'summary' => 'Returns events related to DB Instances, DB Security Groups, DB Snapshots and DB Parameter Groups for the past 14 days. Events specific to a particular DB Instance, DB Security Group, database snapshot or DB Parameter Group can be obtained by providing the name as a parameter. By default, the past hour of events are returned.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEvents',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
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
                        'db-instance',
                        'db-parameter-group',
                        'db-security-group',
                        'db-snapshot',
                    ),
                ),
                'StartTime' => array(
                    'description' => 'The beginning of the time interval to retrieve events for, specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The end of the time interval for which to retrieve events, specified in ISO 8601 format. For more information about ISO 8601, go to the ISO8601 Wikipedia page.',
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
                'EventCategories' => array(
                    'description' => 'A list of event categories that trigger notifications for a event notification subscription.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'EventCategories.member',
                    'items' => array(
                        'name' => 'EventCategory',
                        'type' => 'string',
                    ),
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results may be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeEvents request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeOptionGroupOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OptionGroupOptionsMessage',
            'responseType' => 'model',
            'summary' => 'Describes all available options.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeOptionGroupOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'EngineName' => array(
                    'required' => true,
                    'description' => 'A required parameter. Options available for the given Engine name will be described.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MajorEngineVersion' => array(
                    'description' => 'If specified, filters the results to include only options for the specified major engine version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeOptionGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OptionGroups',
            'responseType' => 'model',
            'summary' => 'Describes the available option groups.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeOptionGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'OptionGroupName' => array(
                    'description' => 'The name of the option group to describe. Cannot be supplied together with EngineName or MajorEngineVersion.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeOptionGroups request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'EngineName' => array(
                    'description' => 'Filters the list of option groups to only include groups associated with a specific database engine.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MajorEngineVersion' => array(
                    'description' => 'Filters the list of option groups to only include groups associated with a specific database engine version. If specified, then EngineName must also be specified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'DescribeOrderableDBInstanceOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OrderableDBInstanceOptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of orderable DB Instance options for the specified engine.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeOrderableDBInstanceOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'Engine' => array(
                    'required' => true,
                    'description' => 'The name of the engine to retrieve DB Instance options for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The engine version filter value. Specify this parameter to show only the available offerings matching the specified engine version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The DB Instance class filter value. Specify this parameter to show only the available offerings matching the specified DB Instance class.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LicenseModel' => array(
                    'description' => 'The license model filter value. Specify this parameter to show only the available offerings matching the specified license model.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Vpc' => array(
                    'description' => 'The VPC filter value. Specify this parameter to show only the available VPC or non-VPC offerings.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more records exist than the specified MaxRecords value, a pagination token called a marker is included in the response so that the remaining results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeOrderableDBInstanceOptions request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeReservedDBInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedDBInstanceMessage',
            'responseType' => 'model',
            'summary' => 'Returns information about reserved DB Instances for this account, or about a specified reserved DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedDBInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ReservedDBInstanceId' => array(
                    'description' => 'The reserved DB Instance identifier filter value. Specify this parameter to show only the reservation that matches the specified reservation ID.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReservedDBInstancesOfferingId' => array(
                    'description' => 'The offering identifier filter value. Specify this parameter to show only purchased reservations matching the specified offering identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The DB Instance class filter value. Specify this parameter to show only those reservations matching the specified DB Instances class.',
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
                'MultiAZ' => array(
                    'description' => 'The Multi-AZ filter value. Specify this parameter to show only those reservations matching the specified Multi-AZ parameter.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a pagination token called a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified reserved DB Instance not found.',
                    'class' => 'ReservedDBInstanceNotFoundException',
                ),
            ),
        ),
        'DescribeReservedDBInstancesOfferings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedDBInstancesOfferingMessage',
            'responseType' => 'model',
            'summary' => 'Lists available reserved DB Instance offerings.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeReservedDBInstancesOfferings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ReservedDBInstancesOfferingId' => array(
                    'description' => 'The offering identifier filter value. Specify this parameter to show only the available offering that matches the specified reservation identifier.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The DB Instance class filter value. Specify this parameter to show only the available offerings matching the specified DB Instance class.',
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
                'MultiAZ' => array(
                    'description' => 'The Multi-AZ filter value. Specify this parameter to show only the available offerings matching the specified Multi-AZ parameter.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to include in the response. If more than the MaxRecords value is available, a pagination token called a marker is included in the response so that the following results can be retrieved.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedDBInstancesOfferingNotFoundException',
                ),
            ),
        ),
        'DownloadDBLogFilePortion' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DownloadDBLogFilePortionDetails',
            'responseType' => 'model',
            'summary' => 'Downloads the last line of the specified log file.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DownloadDBLogFilePortion',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'description' => 'The customer-assigned name of the DB Instance that contains the log files you want to list.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'LogFileName' => array(
                    'description' => 'The name of the log file to be downloaded.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Marker' => array(
                    'description' => 'The pagination token provided in the previous request. If this parameter is specified the response includes only records beyond the marker, up to MaxRecords.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NumberOfLines' => array(
                    'description' => 'The number of lines remaining to be downloaded.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
            ),
        ),
        'ListTagsForResource' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'TagListMessage',
            'responseType' => 'model',
            'summary' => 'Lists all tags on a DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListTagsForResource',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ResourceName' => array(
                    'required' => true,
                    'description' => 'The DB Instance with tags to be listed. This value is an Amazon Resource Name (ARN). For information about creating an ARN, see Constructing an RDS Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
            ),
        ),
        'ModifyDBInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Modify settings for a DB Instance. You can change one or more database configuration parameters by specifying these parameters and the new values in the request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyDBInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllocatedStorage' => array(
                    'description' => 'The new storage capacity of the RDS instance. Changing this parameter does not result in an outage and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The new compute and memory capacity of the DB Instance. To determine the instance classes that are available for a particular DB engine, use the DescribeOrderableDBInstanceOptions action.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSecurityGroups' => array(
                    'description' => 'A list of DB Security Groups to authorize on this DB Instance. Changing this parameter does not result in an outage and the change is asynchronously applied as soon as possible.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'DBSecurityGroups.member',
                    'items' => array(
                        'name' => 'DBSecurityGroupName',
                        'type' => 'string',
                    ),
                ),
                'VpcSecurityGroupIds' => array(
                    'description' => 'A list of EC2 VPC Security Groups to authorize on this DB Instance. This change is asynchronously applied as soon as possible.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VpcSecurityGroupIds.member',
                    'items' => array(
                        'name' => 'VpcSecurityGroupId',
                        'type' => 'string',
                    ),
                ),
                'ApplyImmediately' => array(
                    'description' => 'Specifies whether or not the modifications in this request and any pending modifications are asynchronously applied as soon as possible, regardless of the PreferredMaintenanceWindow setting for the DB Instance.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'MasterUserPassword' => array(
                    'description' => 'The new password for the DB Instance master user. Can be any printable ASCII character except "/", "\\", or "@".',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBParameterGroupName' => array(
                    'description' => 'The name of the DB Parameter Group to apply to this DB Instance. Changing this parameter does not result in an outage and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BackupRetentionPeriod' => array(
                    'description' => 'The number of days to retain automated backups. Setting this parameter to a positive number enables backups. Setting this parameter to 0 disables automated backups.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PreferredBackupWindow' => array(
                    'description' => 'The daily time range during which automated backups are created if automated backups are enabled, as determined by the BackupRetentionPeriod. Changing this parameter does not result in an outage and the change is asynchronously applied as soon as possible.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'PreferredMaintenanceWindow' => array(
                    'description' => 'The weekly time range (in UTC) during which system maintenance can occur, which may result in an outage. Changing this parameter does not result in an outage, except in the following situation, and the change is asynchronously applied as soon as possible. If there are pending actions that cause a reboot, and the maintenance window is changed to include the current time, then changing this parameter will cause a reboot of the DB Instance. If moving this window to the current time, there must be at least 30 minutes between the current time and end of the window to ensure pending changes are applied.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MultiAZ' => array(
                    'description' => 'Specifies if the DB Instance is a Multi-AZ deployment. Changing this parameter does not result in an outage and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'EngineVersion' => array(
                    'description' => 'The version number of the database engine to upgrade to. Changing this parameter results in an outage and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'AllowMajorVersionUpgrade' => array(
                    'description' => 'Indicates that major version upgrades are allowed. Changing this parameter does not result in an outage and the change is asynchronously applied as soon as possible.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor version upgrades will be applied automatically to the DB Instance during the maintenance window. Changing this parameter does not result in an outage except in the following case and the change is asynchronously applied as soon as possible. An outage will result if this parameter is set to true during the maintenance window, and a newer minor version is available, and RDS has enabled auto patching for that engine version.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'Iops' => array(
                    'description' => 'The new Provisioned IOPS (I/O operations per second) value for the RDS instance. Changing this parameter does not result in an outage and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'OptionGroupName' => array(
                    'description' => 'Indicates that the DB Instance should be associated with the specified option group. Changing this parameter does not result in an outage except in the following case and the change is applied during the next maintenance window unless the ApplyImmediately parameter is set to true for this request. If the parameter change results in an option group that enables OEM, this change can cause a brief (sub-second) period during which new connections are rejected but existing connections are not interrupted.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'NewDBInstanceIdentifier' => array(
                    'description' => 'The new DB Instance identifier for the DB Instance when renaming a DB Instance. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'The state of the DB Security Group does not allow deletion.',
                    'class' => 'InvalidDBSecurityGroupStateException',
                ),
                array(
                    'reason' => 'User already has a DB Instance with the given identifier.',
                    'class' => 'DBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'Specified DB Instance class is not available in the specified Availability Zone.',
                    'class' => 'InsufficientDBInstanceCapacityException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed amount of storage available across all DB Instances.',
                    'class' => 'StorageQuotaExceededException',
                ),
                array(
                    'reason' => 'DB Subnet Group does not cover all availability zones after it is created because users\' change.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'reason' => 'Provisioned IOPS not available in the specified Availability Zone.',
                    'class' => 'ProvisionedIopsNotAvailableInAZException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
                array(
                    'class' => 'DBUpgradeDependencyFailureException',
                ),
            ),
        ),
        'ModifyDBParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Modifies the parameters of a DBParameterGroup. To modify more than one parameter submit a list of the following: ParameterName, ParameterValue, and ApplyMethod. A maximum of 20 parameters can be modified in a single request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyDBParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Parameters' => array(
                    'required' => true,
                    'description' => 'An array of parameter names, values, and the apply method for the parameter update. At least one parameter name, value, and apply method must be supplied; subsequent arguments are optional. A maximum of 20 parameters may be modified in a single request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'This data type is used as a request parameter in the ModifyDBParameterGroup and ResetDBParameterGroup actions.',
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
                            'Description' => array(
                                'description' => 'Provides a description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'Indicates the source of the parameter value.',
                                'type' => 'string',
                            ),
                            'ApplyType' => array(
                                'description' => 'Specifies the engine specific parameters type.',
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
                                'format' => 'boolean-string',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                            'ApplyMethod' => array(
                                'description' => 'Indicates when to apply parameter updates.',
                                'type' => 'string',
                                'enum' => array(
                                    'immediate',
                                    'pending-reboot',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
                array(
                    'reason' => 'The DB Parameter Group cannot be deleted because it is in use.',
                    'class' => 'InvalidDBParameterGroupStateException',
                ),
            ),
        ),
        'ModifyDBSubnetGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSubnetGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies an existing DB subnet group. DB subnet groups must contain at least one subnet in at least two AZs in the region.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyDBSubnetGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSubnetGroupName' => array(
                    'required' => true,
                    'description' => 'The name for the DB Subnet Group. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSubnetGroupDescription' => array(
                    'description' => 'The description for the DB Subnet Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SubnetIds' => array(
                    'required' => true,
                    'description' => 'The EC2 Subnet IDs for the DB Subnet Group.',
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
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of subnets in a DB subnet Groups.',
                    'class' => 'DBSubnetQuotaExceededException',
                ),
                array(
                    'reason' => 'The DB subnet is already in use in the availability zone.',
                    'class' => 'SubnetAlreadyInUseException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
            ),
        ),
        'ModifyEventSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies an existing RDS event notification subscription. Note that you cannot modify the source identifiers using this call; to change source identifiers for a subscription, use the AddSourceIdentifierToSubscription and RemoveSourceIdentifierFromSubscription calls.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyEventSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'required' => true,
                    'description' => 'The name of the RDS event notification subscription.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SnsTopicArn' => array(
                    'description' => 'The Amazon Resource Name (ARN) of the SNS topic created for event notification. The ARN is created by Amazon SNS when you create a topic and subscribe to it.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceType' => array(
                    'description' => 'The type of source that will be generating the events. For example, if you want to be notified of events generated by a DB instance, you would set this parameter to db-instance. if this value is not specified, all events are returned.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EventCategories' => array(
                    'description' => 'A list of event categories for a SourceType that you want to subscribe to. You can see a list of the categories for a given SourceType in the Events topic in the Amazon RDS User Guide or by using the DescribeEventCategories action.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'EventCategories.member',
                    'items' => array(
                        'name' => 'EventCategory',
                        'type' => 'string',
                    ),
                ),
                'Enabled' => array(
                    'description' => 'A Boolean value; set to true to activate the subscription.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You have reached the maximum number of event subscriptions.',
                    'class' => 'EventSubscriptionQuotaExceededException',
                ),
                array(
                    'reason' => 'The subscription name does not exist.',
                    'class' => 'SubscriptionNotFoundException',
                ),
                array(
                    'reason' => 'SNS has responded that there is a problem with the SND topic specified.',
                    'class' => 'SNSInvalidTopicException',
                ),
                array(
                    'reason' => 'You do not have permission to publish to the SNS topic ARN.',
                    'class' => 'SNSNoAuthorizationException',
                ),
                array(
                    'reason' => 'The SNS topic ARN does not exist.',
                    'class' => 'SNSTopicArnNotFoundException',
                ),
                array(
                    'reason' => 'The supplied category does not exist.',
                    'class' => 'SubscriptionCategoryNotFoundException',
                ),
            ),
        ),
        'ModifyOptionGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'OptionGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Modifies an existing Option Group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyOptionGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'OptionGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the option group to be modified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'OptionsToInclude' => array(
                    'description' => 'Options in this list are added to the Option Group or, if already present, the specified configuration is used to update the existing configuration.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionsToInclude.member',
                    'items' => array(
                        'name' => 'OptionConfiguration',
                        'description' => 'A list of all available options',
                        'type' => 'object',
                        'properties' => array(
                            'OptionName' => array(
                                'required' => true,
                                'description' => 'The configuration of options to include in a group.',
                                'type' => 'string',
                            ),
                            'Port' => array(
                                'description' => 'The optional port for the option.',
                                'type' => 'numeric',
                            ),
                            'DBSecurityGroupMemberships' => array(
                                'description' => 'A list of DBSecurityGroupMemebrship name strings used for this option.',
                                'type' => 'array',
                                'sentAs' => 'DBSecurityGroupMemberships.member',
                                'items' => array(
                                    'name' => 'DBSecurityGroupName',
                                    'type' => 'string',
                                ),
                            ),
                            'VpcSecurityGroupMemberships' => array(
                                'description' => 'A list of VpcSecurityGroupMemebrship name strings used for this option.',
                                'type' => 'array',
                                'sentAs' => 'VpcSecurityGroupMemberships.member',
                                'items' => array(
                                    'name' => 'VpcSecurityGroupId',
                                    'type' => 'string',
                                ),
                            ),
                            'OptionSettings' => array(
                                'description' => 'The option settings to include in an option group.',
                                'type' => 'array',
                                'sentAs' => 'OptionSettings.member',
                                'items' => array(
                                    'name' => 'OptionSetting',
                                    'description' => 'Option settings are the actual settings being applied or configured for that option. It is used when you modify an option group or describe option groups. For example, the NATIVE_NETWORK_ENCRYPTION option has a setting called SQLNET.ENCRYPTION_SERVER that can have several different values.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the option that has settings that you can set.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The current value of the option setting.',
                                            'type' => 'string',
                                        ),
                                        'DefaultValue' => array(
                                            'description' => 'The default value of the option setting.',
                                            'type' => 'string',
                                        ),
                                        'Description' => array(
                                            'description' => 'The description of the option setting.',
                                            'type' => 'string',
                                        ),
                                        'ApplyType' => array(
                                            'description' => 'The DB engine specific parameter type.',
                                            'type' => 'string',
                                        ),
                                        'DataType' => array(
                                            'description' => 'The data type of the option setting.',
                                            'type' => 'string',
                                        ),
                                        'AllowedValues' => array(
                                            'description' => 'The allowed values of the option setting.',
                                            'type' => 'string',
                                        ),
                                        'IsModifiable' => array(
                                            'description' => 'A Boolean value that, when true, indicates the option setting can be modified from the default.',
                                            'type' => 'boolean',
                                            'format' => 'boolean-string',
                                        ),
                                        'IsCollection' => array(
                                            'description' => 'Indicates if the option setting is part of a collection.',
                                            'type' => 'boolean',
                                            'format' => 'boolean-string',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'OptionsToRemove' => array(
                    'description' => 'Options in this list are removed from the Option Group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionsToRemove.member',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'ApplyImmediately' => array(
                    'description' => 'Indicates whether the changes should be applied immediately, or during the next maintenance window for each instance associated with the Option Group.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The Option Group is not in the available state.',
                    'class' => 'InvalidOptionGroupStateException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'PromoteReadReplica' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Promotes a Read Replica DB Instance to a standalone DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PromoteReadReplica',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier. This value is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'BackupRetentionPeriod' => array(
                    'description' => 'The number of days to retain automated backups. Setting this parameter to a positive number enables backups. Setting this parameter to 0 disables automated backups.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PreferredBackupWindow' => array(
                    'description' => 'The daily time range during which automated backups are created if automated backups are enabled, using the BackupRetentionPeriod parameter.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
            ),
        ),
        'PurchaseReservedDBInstancesOffering' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ReservedDBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Purchases a reserved DB Instance offering.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PurchaseReservedDBInstancesOffering',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ReservedDBInstancesOfferingId' => array(
                    'required' => true,
                    'description' => 'The ID of the Reserved DB Instance offering to purchase.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ReservedDBInstanceId' => array(
                    'description' => 'Customer-specified identifier to track this reservation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceCount' => array(
                    'description' => 'The number of instances to reserve.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Specified offering does not exist.',
                    'class' => 'ReservedDBInstancesOfferingNotFoundException',
                ),
                array(
                    'reason' => 'User already has a reservation with the given identifier.',
                    'class' => 'ReservedDBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'Request would exceed the user\'s DB Instance quota.',
                    'class' => 'ReservedDBInstanceQuotaExceededException',
                ),
            ),
        ),
        'RebootDBInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Reboots a previously provisioned RDS instance. This API results in the application of modified DBParameterGroup parameters with ApplyStatus of pending-reboot to the RDS instance. This action is taken as soon as possible, and results in a momentary outage to the RDS instance during which the RDS instance status is set to rebooting. If the RDS instance is configured for MultiAZ, it is possible that the reboot will be conducted through a failover. A DBInstance event is created when the reboot is completed.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RebootDBInstance',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The DB Instance identifier. This parameter is stored as a lowercase string.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ForceFailover' => array(
                    'description' => 'When true, the reboot will be conducted through a MultiAZ failover.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
            ),
        ),
        'RemoveSourceIdentifierFromSubscription' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventSubscriptionWrapper',
            'responseType' => 'model',
            'summary' => 'Removes a source identifier from an existing RDS event notification subscription.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemoveSourceIdentifierFromSubscription',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SubscriptionName' => array(
                    'required' => true,
                    'description' => 'The name of the RDS event notification subscription you want to remove a source identifier from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceIdentifier' => array(
                    'required' => true,
                    'description' => 'The source identifier to be removed from the subscription, such as the DB instance identifier for a DB instance or the name of a security group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The subscription name does not exist.',
                    'class' => 'SubscriptionNotFoundException',
                ),
                array(
                    'reason' => 'The requested source could not be found.',
                    'class' => 'SourceNotFoundException',
                ),
            ),
        ),
        'RemoveTagsFromResource' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Removes metadata tags from a DB Instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RemoveTagsFromResource',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'ResourceName' => array(
                    'required' => true,
                    'description' => 'The DB Instance the tags will be removed from. This value is an Amazon Resource Name (ARN). For information about creating an ARN, see Constructing an RDS Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TagKeys' => array(
                    'required' => true,
                    'description' => 'The tag key (name) of the tag to be removed.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'TagKeys.member',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
            ),
        ),
        'ResetDBParameterGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBParameterGroupNameMessage',
            'responseType' => 'model',
            'summary' => 'Modifies the parameters of a DBParameterGroup to the engine/system default value. To reset specific parameters submit a list of the following: ParameterName and ApplyMethod. To reset the entire DBParameterGroup specify the DBParameterGroup name and ResetAllParameters parameters. When resetting the entire group, dynamic parameters are updated immediately and static parameters are set to pending-reboot to take effect on the next DB instance restart or RebootDBInstance request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResetDBParameterGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBParameterGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'ResetAllParameters' => array(
                    'description' => 'Specifies whether (true) or not (false) to reset all parameters in the DB Parameter Group to default values.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'Parameters' => array(
                    'description' => 'An array of parameter names, values, and the apply method for the parameter update. At least one parameter name, value, and apply method must be supplied; subsequent arguments are optional. A maximum of 20 parameters may be modified in a single request.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Parameters.member',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'This data type is used as a request parameter in the ModifyDBParameterGroup and ResetDBParameterGroup actions.',
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
                            'Description' => array(
                                'description' => 'Provides a description of the parameter.',
                                'type' => 'string',
                            ),
                            'Source' => array(
                                'description' => 'Indicates the source of the parameter value.',
                                'type' => 'string',
                            ),
                            'ApplyType' => array(
                                'description' => 'Specifies the engine specific parameters type.',
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
                                'format' => 'boolean-string',
                            ),
                            'MinimumEngineVersion' => array(
                                'description' => 'The earliest engine version to which the parameter can apply.',
                                'type' => 'string',
                            ),
                            'ApplyMethod' => array(
                                'description' => 'Indicates when to apply parameter updates.',
                                'type' => 'string',
                                'enum' => array(
                                    'immediate',
                                    'pending-reboot',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The DB Parameter Group cannot be deleted because it is in use.',
                    'class' => 'InvalidDBParameterGroupStateException',
                ),
                array(
                    'reason' => 'DBParameterGroupName does not refer to an existing DB Parameter Group.',
                    'class' => 'DBParameterGroupNotFoundException',
                ),
            ),
        ),
        'RestoreDBInstanceFromDBSnapshot' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Creates a new DB Instance from a DB snapshot. The target database is created from the source database restore point with the same configuration as the original source database, except that the new RDS instance is created with the default security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RestoreDBInstanceFromDBSnapshot',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier for the DB Snapshot to restore from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSnapshotIdentifier' => array(
                    'required' => true,
                    'description' => 'Name of the DB Instance to create from the DB Snapshot. This parameter isn\'t case sensitive.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The compute and memory capacity of the Amazon RDS DB instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which the database accepts connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The EC2 Availability Zone that the database instance will be created in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSubnetGroupName' => array(
                    'description' => 'The DB Subnet Group name to use for the new instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MultiAZ' => array(
                    'description' => 'Specifies if the DB Instance is a Multi-AZ deployment.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor version upgrades will be applied automatically to the DB Instance during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'LicenseModel' => array(
                    'description' => 'License model information for the restored DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBName' => array(
                    'description' => 'The database name for the restored DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Engine' => array(
                    'description' => 'The database engine to use for the new instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Iops' => array(
                    'description' => 'Specifies the amount of provisioned IOPS for the DB Instance, expressed in I/O operations per second. If this parameter is not specified, the IOPS value will be taken from the backup. If this parameter is set to 0, the new instance will be converted to a non-PIOPS instance, which will take additional time, though your DB instance will be available for connections before the conversion starts.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'OptionGroupName' => array(
                    'description' => 'The name of the option group to be used for the restored DB instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'User already has a DB Instance with the given identifier.',
                    'class' => 'DBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'DBSnapshotIdentifier does not refer to an existing DB Snapshot.',
                    'class' => 'DBSnapshotNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Instances.',
                    'class' => 'InstanceQuotaExceededException',
                ),
                array(
                    'reason' => 'Specified DB Instance class is not available in the specified Availability Zone.',
                    'class' => 'InsufficientDBInstanceCapacityException',
                ),
                array(
                    'reason' => 'The state of the DB Security Snapshot does not allow deletion.',
                    'class' => 'InvalidDBSnapshotStateException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed amount of storage available across all DB Instances.',
                    'class' => 'StorageQuotaExceededException',
                ),
                array(
                    'reason' => 'DB Subnet Group does not cover all availability zones after it is created because users\' change.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'reason' => 'Cannot restore from vpc backup to non-vpc DB instance.',
                    'class' => 'InvalidRestoreException',
                ),
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
                array(
                    'reason' => 'Provisioned IOPS not available in the specified Availability Zone.',
                    'class' => 'ProvisionedIopsNotAvailableInAZException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'RestoreDBInstanceToPointInTime' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBInstanceWrapper',
            'responseType' => 'model',
            'summary' => 'Restores a DB Instance to an arbitrary point-in-time. Users can restore to any point in time before the latestRestorableTime for up to backupRetentionPeriod days. The target database is created from the source database with the same configuration as the original database except that the DB instance is created with the default DB security group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RestoreDBInstanceToPointInTime',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'SourceDBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The identifier of the source DB Instance from which to restore.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'TargetDBInstanceIdentifier' => array(
                    'required' => true,
                    'description' => 'The name of the new database instance to be created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'RestoreTime' => array(
                    'description' => 'The date and time to restore from.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'UseLatestRestorableTime' => array(
                    'description' => 'Specifies whether (true) or not (false) the DB Instance is restored from the latest backup time.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'DBInstanceClass' => array(
                    'description' => 'The compute and memory capacity of the Amazon RDS DB instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Port' => array(
                    'description' => 'The port number on which the database accepts connections.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The EC2 Availability Zone that the database instance will be created in.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBSubnetGroupName' => array(
                    'description' => 'The DB subnet group name to use for the new instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MultiAZ' => array(
                    'description' => 'Specifies if the DB Instance is a Multi-AZ deployment.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'PubliclyAccessible' => array(
                    'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'AutoMinorVersionUpgrade' => array(
                    'description' => 'Indicates that minor version upgrades will be applied automatically to the DB Instance during the maintenance window.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'LicenseModel' => array(
                    'description' => 'License model information for the restored DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DBName' => array(
                    'description' => 'The database name for the restored DB Instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Engine' => array(
                    'description' => 'The database engine to use for the new instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Iops' => array(
                    'description' => 'The amount of Provisioned IOPS (input/output operations per second) to be initially allocated for the DB Instance.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'OptionGroupName' => array(
                    'description' => 'The name of the option group to be used for the restored DB instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'User already has a DB Instance with the given identifier.',
                    'class' => 'DBInstanceAlreadyExistsException',
                ),
                array(
                    'reason' => 'DBInstanceIdentifier does not refer to an existing DB Instance.',
                    'class' => 'DBInstanceNotFoundException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed number of DB Instances.',
                    'class' => 'InstanceQuotaExceededException',
                ),
                array(
                    'reason' => 'Specified DB Instance class is not available in the specified Availability Zone.',
                    'class' => 'InsufficientDBInstanceCapacityException',
                ),
                array(
                    'reason' => 'The specified DB Instance is not in the available state.',
                    'class' => 'InvalidDBInstanceStateException',
                ),
                array(
                    'reason' => 'SourceDBInstanceIdentifier refers to a DB Instance with BackupRetentionPeriod equal to 0.',
                    'class' => 'PointInTimeRestoreNotEnabledException',
                ),
                array(
                    'reason' => 'Request would result in user exceeding the allowed amount of storage available across all DB Instances.',
                    'class' => 'StorageQuotaExceededException',
                ),
                array(
                    'reason' => 'DB Subnet Group does not cover all availability zones after it is created because users\' change.',
                    'class' => 'InvalidVPCNetworkStateException',
                ),
                array(
                    'reason' => 'Cannot restore from vpc backup to non-vpc DB instance.',
                    'class' => 'InvalidRestoreException',
                ),
                array(
                    'reason' => 'DBSubnetGroupName does not refer to an existing DB Subnet Group.',
                    'class' => 'DBSubnetGroupNotFoundException',
                ),
                array(
                    'reason' => 'Subnets in the DB subnet group should cover at least 2 availability zones unless there\'s\'only 1 available zone.',
                    'class' => 'DBSubnetGroupDoesNotCoverEnoughAZsException',
                ),
                array(
                    'reason' => 'Request subnet is valid, or all subnets are not in common Vpc.',
                    'class' => 'InvalidSubnetException',
                ),
                array(
                    'reason' => 'Provisioned IOPS not available in the specified Availability Zone.',
                    'class' => 'ProvisionedIopsNotAvailableInAZException',
                ),
                array(
                    'reason' => 'The specified option group could not be found.',
                    'class' => 'OptionGroupNotFoundException',
                ),
            ),
        ),
        'RevokeDBSecurityGroupIngress' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DBSecurityGroupWrapper',
            'responseType' => 'model',
            'summary' => 'Revokes ingress from a DBSecurityGroup for previously authorized IP ranges or EC2 or VPC Security Groups. Required parameters for this API are one of CIDRIP, EC2SecurityGroupId for VPC, or (EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId).',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RevokeDBSecurityGroupIngress',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2013-05-15',
                ),
                'DBSecurityGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the DB Security Group to revoke ingress from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'CIDRIP' => array(
                    'description' => 'The IP range to revoke access from. Must be a valid CIDR range. If CIDRIP is specified, EC2SecurityGroupName, EC2SecurityGroupId and EC2SecurityGroupOwnerId cannot be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupName' => array(
                    'description' => 'The name of the EC2 Security Group to revoke access from. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupId' => array(
                    'description' => 'The id of the EC2 Security Group to revoke access from. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EC2SecurityGroupOwnerId' => array(
                    'description' => 'The AWS Account Number of the owner of the EC2 security group specified in the EC2SecurityGroupName parameter. The AWS Access Key ID is not an acceptable value. For VPC DB Security Groups, EC2SecurityGroupId must be provided. Otherwise, EC2SecurityGroupOwnerId and either EC2SecurityGroupName or EC2SecurityGroupId must be provided.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'DBSecurityGroupName does not refer to an existing DB Security Group.',
                    'class' => 'DBSecurityGroupNotFoundException',
                ),
                array(
                    'reason' => 'Specified CIDRIP or EC2 security group is not authorized for the specified DB Security Group.',
                    'class' => 'AuthorizationNotFoundException',
                ),
                array(
                    'reason' => 'The state of the DB Security Group does not allow deletion.',
                    'class' => 'InvalidDBSecurityGroupStateException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EventSubscriptionWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EventSubscription' => array(
                    'description' => 'Contains the results of a successful invocation of the DescribeEventSubscriptions action.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'CustomerAwsId' => array(
                            'description' => 'The AWS customer account associated with the RDS event notification subscription.',
                            'type' => 'string',
                        ),
                        'CustSubscriptionId' => array(
                            'description' => 'The RDS event notification subscription Id.',
                            'type' => 'string',
                        ),
                        'SnsTopicArn' => array(
                            'description' => 'The topic ARN of the RDS event notification subscription.',
                            'type' => 'string',
                        ),
                        'Status' => array(
                            'description' => 'The status of the RDS event notification subscription.',
                            'type' => 'string',
                        ),
                        'SubscriptionCreationTime' => array(
                            'description' => 'The time the RDS event notification subscription was created.',
                            'type' => 'string',
                        ),
                        'SourceType' => array(
                            'description' => 'The source type for the RDS event notification subscription.',
                            'type' => 'string',
                        ),
                        'SourceIdsList' => array(
                            'description' => 'A list of source Ids for the RDS event notification subscription.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'SourceId',
                                'type' => 'string',
                                'sentAs' => 'SourceId',
                            ),
                        ),
                        'EventCategoriesList' => array(
                            'description' => 'A list of event categories for the RDS event notification subscription.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'EventCategory',
                                'type' => 'string',
                                'sentAs' => 'EventCategory',
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'A Boolean value indicating if the subscription is enabled. True indicates the subscription is enabled.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
            ),
        ),
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'DBSecurityGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBSecurityGroup' => array(
                    'description' => 'Contains the result of a successful invocation of the following actions:',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'OwnerId' => array(
                            'description' => 'Provides the AWS ID of the owner of a specific DB Security Group.',
                            'type' => 'string',
                        ),
                        'DBSecurityGroupName' => array(
                            'description' => 'Specifies the name of the DB Security Group.',
                            'type' => 'string',
                        ),
                        'DBSecurityGroupDescription' => array(
                            'description' => 'Provides the description of the DB Security Group.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'Provides the VpcId of the DB Security Group.',
                            'type' => 'string',
                        ),
                        'EC2SecurityGroups' => array(
                            'description' => 'Contains a list of EC2SecurityGroup elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'EC2SecurityGroup',
                                'description' => 'This data type is used as a response element in the following actions:',
                                'type' => 'object',
                                'sentAs' => 'EC2SecurityGroup',
                                'properties' => array(
                                    'Status' => array(
                                        'description' => 'Provides the status of the EC2 security group. Status can be "authorizing", "authorized", "revoking", and "revoked".',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupName' => array(
                                        'description' => 'Specifies the name of the EC2 Security Group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupId' => array(
                                        'description' => 'Specifies the id of the EC2 Security Group.',
                                        'type' => 'string',
                                    ),
                                    'EC2SecurityGroupOwnerId' => array(
                                        'description' => 'Specifies the AWS ID of the owner of the EC2 security group specified in the EC2SecurityGroupName field.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'IPRanges' => array(
                            'description' => 'Contains a list of IPRange elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'IPRange',
                                'description' => 'This data type is used as a response element in the DescribeDBSecurityGroups action.',
                                'type' => 'object',
                                'sentAs' => 'IPRange',
                                'properties' => array(
                                    'Status' => array(
                                        'description' => 'Specifies the status of the IP range. Status can be "authorizing", "authorized", "revoking", and "revoked".',
                                        'type' => 'string',
                                    ),
                                    'CIDRIP' => array(
                                        'description' => 'Specifies the IP range.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DBSnapshotWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBSnapshot' => array(
                    'description' => 'Contains the result of a successful invocation of the following actions:',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'DBSnapshotIdentifier' => array(
                            'description' => 'Specifies the identifier for the DB Snapshot.',
                            'type' => 'string',
                        ),
                        'DBInstanceIdentifier' => array(
                            'description' => 'Specifies the the DBInstanceIdentifier of the DB Instance this DB Snapshot was created from.',
                            'type' => 'string',
                        ),
                        'SnapshotCreateTime' => array(
                            'description' => 'Provides the time (UTC) when the snapshot was taken.',
                            'type' => 'string',
                        ),
                        'Engine' => array(
                            'description' => 'Specifies the name of the database engine.',
                            'type' => 'string',
                        ),
                        'AllocatedStorage' => array(
                            'description' => 'Specifies the allocated storage size in gigabytes (GB).',
                            'type' => 'numeric',
                        ),
                        'Status' => array(
                            'description' => 'Specifies the status of this DB Snapshot.',
                            'type' => 'string',
                        ),
                        'Port' => array(
                            'description' => 'Specifies the port that the database engine was listening on at the time of the snapshot.',
                            'type' => 'numeric',
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'Specifies the name of the Availability Zone the DB Instance was located in at the time of the DB Snapshot.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'Provides the Vpc Id associated with the DB Snapshot.',
                            'type' => 'string',
                        ),
                        'InstanceCreateTime' => array(
                            'description' => 'Specifies the time (UTC) when the snapshot was taken.',
                            'type' => 'string',
                        ),
                        'MasterUsername' => array(
                            'description' => 'Provides the master username for the DB Snapshot.',
                            'type' => 'string',
                        ),
                        'EngineVersion' => array(
                            'description' => 'Specifies the version of the database engine.',
                            'type' => 'string',
                        ),
                        'LicenseModel' => array(
                            'description' => 'License model information for the restored DB Instance.',
                            'type' => 'string',
                        ),
                        'SnapshotType' => array(
                            'description' => 'Provides the type of the DB Snapshot.',
                            'type' => 'string',
                        ),
                        'Iops' => array(
                            'description' => 'Specifies the Provisioned IOPS (I/O operations per second) value of the DB Instance at the time of the snapshot.',
                            'type' => 'numeric',
                        ),
                        'OptionGroupName' => array(
                            'description' => 'Provides the option group name for the DB Snapshot.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'DBInstanceWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBInstance' => array(
                    'description' => 'Contains the result of a successful invocation of the following actions:',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'DBInstanceIdentifier' => array(
                            'description' => 'Contains a user-supplied database identifier. This is the unique key that identifies a DB Instance.',
                            'type' => 'string',
                        ),
                        'DBInstanceClass' => array(
                            'description' => 'Contains the name of the compute and memory capacity class of the DB Instance.',
                            'type' => 'string',
                        ),
                        'Engine' => array(
                            'description' => 'Provides the name of the database engine to be used for this DB Instance.',
                            'type' => 'string',
                        ),
                        'DBInstanceStatus' => array(
                            'description' => 'Specifies the current state of this database.',
                            'type' => 'string',
                        ),
                        'MasterUsername' => array(
                            'description' => 'Contains the master username for the DB Instance.',
                            'type' => 'string',
                        ),
                        'DBName' => array(
                            'description' => 'The meaning of this parameter differs according to the database engine you use.',
                            'type' => 'string',
                        ),
                        'Endpoint' => array(
                            'description' => 'Specifies the connection endpoint.',
                            'type' => 'object',
                            'properties' => array(
                                'Address' => array(
                                    'description' => 'Specifies the DNS address of the DB Instance.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'Specifies the port that the database engine is listening on.',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'AllocatedStorage' => array(
                            'description' => 'Specifies the allocated storage size specified in gigabytes.',
                            'type' => 'numeric',
                        ),
                        'InstanceCreateTime' => array(
                            'description' => 'Provides the date and time the DB Instance was created.',
                            'type' => 'string',
                        ),
                        'PreferredBackupWindow' => array(
                            'description' => 'Specifies the daily time range during which automated backups are created if automated backups are enabled, as determined by the BackupRetentionPeriod.',
                            'type' => 'string',
                        ),
                        'BackupRetentionPeriod' => array(
                            'description' => 'Specifies the number of days for which automatic DB Snapshots are retained.',
                            'type' => 'numeric',
                        ),
                        'DBSecurityGroups' => array(
                            'description' => 'Provides List of DB Security Group elements containing only DBSecurityGroup.Name and DBSecurityGroup.Status subelements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'DBSecurityGroup',
                                'description' => 'This data type is used as a response element in the following actions:',
                                'type' => 'object',
                                'sentAs' => 'DBSecurityGroup',
                                'properties' => array(
                                    'DBSecurityGroupName' => array(
                                        'description' => 'The name of the DB Security Group.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the DB Security Group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'VpcSecurityGroups' => array(
                            'description' => 'Provides List of VPC security group elements that the DB Instance belongs to.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'VpcSecurityGroupMembership',
                                'description' => 'This data type is used as a response element for queries on VPC security group membership.',
                                'type' => 'object',
                                'sentAs' => 'VpcSecurityGroupMembership',
                                'properties' => array(
                                    'VpcSecurityGroupId' => array(
                                        'description' => 'The name of the VPC security group.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the VPC Security Group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'DBParameterGroups' => array(
                            'description' => 'Provides the list of DB Parameter Groups applied to this DB Instance.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'DBParameterGroup',
                                'description' => 'The status of the DB Parameter Group.',
                                'type' => 'object',
                                'sentAs' => 'DBParameterGroup',
                                'properties' => array(
                                    'DBParameterGroupName' => array(
                                        'description' => 'The name of the DP Parameter Group.',
                                        'type' => 'string',
                                    ),
                                    'ParameterApplyStatus' => array(
                                        'description' => 'The status of parameter updates.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'AvailabilityZone' => array(
                            'description' => 'Specifies the name of the Availability Zone the DB Instance is located in.',
                            'type' => 'string',
                        ),
                        'DBSubnetGroup' => array(
                            'description' => 'Provides the inforamtion of the subnet group associated with the DB instance, including the name, descrption and subnets in the subnet group.',
                            'type' => 'object',
                            'properties' => array(
                                'DBSubnetGroupName' => array(
                                    'description' => 'Specifies the name of the DB Subnet Group.',
                                    'type' => 'string',
                                ),
                                'DBSubnetGroupDescription' => array(
                                    'description' => 'Provides the description of the DB Subnet Group.',
                                    'type' => 'string',
                                ),
                                'VpcId' => array(
                                    'description' => 'Provides the VpcId of the DB Subnet Group.',
                                    'type' => 'string',
                                ),
                                'SubnetGroupStatus' => array(
                                    'description' => 'Provides the status of the DB Subnet Group.',
                                    'type' => 'string',
                                ),
                                'Subnets' => array(
                                    'description' => 'Contains a list of Subnet elements.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Subnet',
                                        'description' => 'This data type is used as a response element in the DescribeDBSubnetGroups action.',
                                        'type' => 'object',
                                        'sentAs' => 'Subnet',
                                        'properties' => array(
                                            'SubnetIdentifier' => array(
                                                'description' => 'Specifies the identifier of the subnet.',
                                                'type' => 'string',
                                            ),
                                            'SubnetAvailabilityZone' => array(
                                                'description' => 'Contains Availability Zone information.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Name' => array(
                                                        'description' => 'The name of the availability zone.',
                                                        'type' => 'string',
                                                    ),
                                                    'ProvisionedIopsCapable' => array(
                                                        'description' => 'True indicates the availability zone is capable of provisioned IOPs.',
                                                        'type' => 'boolean',
                                                    ),
                                                ),
                                            ),
                                            'SubnetStatus' => array(
                                                'description' => 'Specifies the status of the subnet.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'PreferredMaintenanceWindow' => array(
                            'description' => 'Specifies the weekly time range (in UTC) during which system maintenance can occur.',
                            'type' => 'string',
                        ),
                        'PendingModifiedValues' => array(
                            'description' => 'Specifies that changes to the DB Instance are pending. This element is only included when changes are pending. Specific changes are identified by subelements.',
                            'type' => 'object',
                            'properties' => array(
                                'DBInstanceClass' => array(
                                    'description' => 'Contains the new DBInstanceClass for the DB Instance that will be applied or is in progress.',
                                    'type' => 'string',
                                ),
                                'AllocatedStorage' => array(
                                    'description' => 'Contains the new AllocatedStorage size for the DB Instance that will be applied or is in progress.',
                                    'type' => 'numeric',
                                ),
                                'MasterUserPassword' => array(
                                    'description' => 'Contains the pending or in-progress change of the master credentials for the DB Instance.',
                                    'type' => 'string',
                                ),
                                'Port' => array(
                                    'description' => 'Specifies the pending port for the DB Instance.',
                                    'type' => 'numeric',
                                ),
                                'BackupRetentionPeriod' => array(
                                    'description' => 'Specifies the pending number of days for which automated backups are retained.',
                                    'type' => 'numeric',
                                ),
                                'MultiAZ' => array(
                                    'description' => 'Indicates that the Single-AZ DB Instance is to change to a Multi-AZ deployment.',
                                    'type' => 'boolean',
                                ),
                                'EngineVersion' => array(
                                    'description' => 'Indicates the database engine version.',
                                    'type' => 'string',
                                ),
                                'Iops' => array(
                                    'description' => 'Specifies the new Provisioned IOPS value for the DB Instance that will be applied or is being applied.',
                                    'type' => 'numeric',
                                ),
                                'DBInstanceIdentifier' => array(
                                    'description' => 'Contains the new DBInstanceIdentifier for the DB Instance that will be applied or is in progress.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'LatestRestorableTime' => array(
                            'description' => 'Specifies the latest time to which a database can be restored with point-in-time restore.',
                            'type' => 'string',
                        ),
                        'MultiAZ' => array(
                            'description' => 'Specifies if the DB Instance is a Multi-AZ deployment.',
                            'type' => 'boolean',
                        ),
                        'EngineVersion' => array(
                            'description' => 'Indicates the database engine version.',
                            'type' => 'string',
                        ),
                        'AutoMinorVersionUpgrade' => array(
                            'description' => 'Indicates that minor version patches are applied automatically.',
                            'type' => 'boolean',
                        ),
                        'ReadReplicaSourceDBInstanceIdentifier' => array(
                            'description' => 'Contains the identifier of the source DB Instance if this DB Instance is a Read Replica.',
                            'type' => 'string',
                        ),
                        'ReadReplicaDBInstanceIdentifiers' => array(
                            'description' => 'Contains one or more identifiers of the Read Replicas associated with this DB Instance.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ReadReplicaDBInstanceIdentifier',
                                'type' => 'string',
                                'sentAs' => 'ReadReplicaDBInstanceIdentifier',
                            ),
                        ),
                        'LicenseModel' => array(
                            'description' => 'License model information for this DB Instance.',
                            'type' => 'string',
                        ),
                        'Iops' => array(
                            'description' => 'Specifies the Provisioned IOPS (I/O operations per second) value.',
                            'type' => 'numeric',
                        ),
                        'OptionGroupMemberships' => array(
                            'description' => 'Provides the list of option group memberships for this DB Instance.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'OptionGroupMembership',
                                'description' => 'Provides information on the option groups the DB instance is a member of.',
                                'type' => 'object',
                                'sentAs' => 'OptionGroupMembership',
                                'properties' => array(
                                    'OptionGroupName' => array(
                                        'description' => 'The name of the option group that the instance belongs to.',
                                        'type' => 'string',
                                    ),
                                    'Status' => array(
                                        'description' => 'The status of the DB Instance\'s option group membership (e.g. in-sync, pending, pending-maintenance, applying).',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'CharacterSetName' => array(
                            'description' => 'If present, specifies the name of the character set that this instance is associated with.',
                            'type' => 'string',
                        ),
                        'SecondaryAvailabilityZone' => array(
                            'description' => 'If present, specifies the name of the secondary Availability Zone for a DB instance with multi-AZ support.',
                            'type' => 'string',
                        ),
                        'PubliclyAccessible' => array(
                            'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                            'type' => 'boolean',
                        ),
                        'StatusInfos' => array(
                            'description' => 'The status of a Read Replica. If the instance is not a for a read replica, this will be blank.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'DBInstanceStatusInfo',
                                'description' => 'Provides a list of status information for a DB instance.',
                                'type' => 'object',
                                'sentAs' => 'DBInstanceStatusInfo',
                                'properties' => array(
                                    'StatusType' => array(
                                        'description' => 'This value is currently "read replication."',
                                        'type' => 'string',
                                    ),
                                    'Normal' => array(
                                        'description' => 'Boolean value that is true if the instance is operating normally, or false if the instance is in an error state.',
                                        'type' => 'boolean',
                                    ),
                                    'Status' => array(
                                        'description' => 'Status of the DB instance. For a StatusType of Read Replica, the values can be replicating, error, stopped, or terminated.',
                                        'type' => 'string',
                                    ),
                                    'Message' => array(
                                        'description' => 'Details of the error if there is an error for the instance. If the instance is not in an error state, this value is blank.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DBParameterGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBParameterGroup' => array(
                    'description' => 'Contains the result of a successful invocation of the CreateDBParameterGroup action.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'DBParameterGroupName' => array(
                            'description' => 'Provides the name of the DB Parameter Group.',
                            'type' => 'string',
                        ),
                        'DBParameterGroupFamily' => array(
                            'description' => 'Provides the name of the DB Parameter Group Family that this DB Parameter Group is compatible with.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'Provides the customer-specified description for this DB Parameter Group.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'DBSubnetGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBSubnetGroup' => array(
                    'description' => 'Contains the result of a successful invocation of the following actions:',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'DBSubnetGroupName' => array(
                            'description' => 'Specifies the name of the DB Subnet Group.',
                            'type' => 'string',
                        ),
                        'DBSubnetGroupDescription' => array(
                            'description' => 'Provides the description of the DB Subnet Group.',
                            'type' => 'string',
                        ),
                        'VpcId' => array(
                            'description' => 'Provides the VpcId of the DB Subnet Group.',
                            'type' => 'string',
                        ),
                        'SubnetGroupStatus' => array(
                            'description' => 'Provides the status of the DB Subnet Group.',
                            'type' => 'string',
                        ),
                        'Subnets' => array(
                            'description' => 'Contains a list of Subnet elements.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Subnet',
                                'description' => 'This data type is used as a response element in the DescribeDBSubnetGroups action.',
                                'type' => 'object',
                                'sentAs' => 'Subnet',
                                'properties' => array(
                                    'SubnetIdentifier' => array(
                                        'description' => 'Specifies the identifier of the subnet.',
                                        'type' => 'string',
                                    ),
                                    'SubnetAvailabilityZone' => array(
                                        'description' => 'Contains Availability Zone information.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Name' => array(
                                                'description' => 'The name of the availability zone.',
                                                'type' => 'string',
                                            ),
                                            'ProvisionedIopsCapable' => array(
                                                'description' => 'True indicates the availability zone is capable of provisioned IOPs.',
                                                'type' => 'boolean',
                                            ),
                                        ),
                                    ),
                                    'SubnetStatus' => array(
                                        'description' => 'Specifies the status of the subnet.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'OptionGroupWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OptionGroup' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'OptionGroupName' => array(
                            'description' => 'Specifies the name of the option group.',
                            'type' => 'string',
                        ),
                        'OptionGroupDescription' => array(
                            'description' => 'Provides the description of the option group.',
                            'type' => 'string',
                        ),
                        'EngineName' => array(
                            'description' => 'Engine name that this option group can be applied to.',
                            'type' => 'string',
                        ),
                        'MajorEngineVersion' => array(
                            'description' => 'Indicates the major engine version associated with this option group.',
                            'type' => 'string',
                        ),
                        'Options' => array(
                            'description' => 'Indicates what options are available in the option group.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Option',
                                'description' => 'Option details.',
                                'type' => 'object',
                                'sentAs' => 'Option',
                                'properties' => array(
                                    'OptionName' => array(
                                        'description' => 'The name of the option.',
                                        'type' => 'string',
                                    ),
                                    'OptionDescription' => array(
                                        'description' => 'The description of the option.',
                                        'type' => 'string',
                                    ),
                                    'Persistent' => array(
                                        'description' => 'Indicate if this option is persistent.',
                                        'type' => 'boolean',
                                    ),
                                    'Permanent' => array(
                                        'description' => 'Indicate if this option is permanent.',
                                        'type' => 'boolean',
                                    ),
                                    'Port' => array(
                                        'description' => 'If required, the port configured for this option to use.',
                                        'type' => 'numeric',
                                    ),
                                    'OptionSettings' => array(
                                        'description' => 'The option settings for this option.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'OptionSetting',
                                            'description' => 'Option settings are the actual settings being applied or configured for that option. It is used when you modify an option group or describe option groups. For example, the NATIVE_NETWORK_ENCRYPTION option has a setting called SQLNET.ENCRYPTION_SERVER that can have several different values.',
                                            'type' => 'object',
                                            'sentAs' => 'OptionSetting',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'The name of the option that has settings that you can set.',
                                                    'type' => 'string',
                                                ),
                                                'Value' => array(
                                                    'description' => 'The current value of the option setting.',
                                                    'type' => 'string',
                                                ),
                                                'DefaultValue' => array(
                                                    'description' => 'The default value of the option setting.',
                                                    'type' => 'string',
                                                ),
                                                'Description' => array(
                                                    'description' => 'The description of the option setting.',
                                                    'type' => 'string',
                                                ),
                                                'ApplyType' => array(
                                                    'description' => 'The DB engine specific parameter type.',
                                                    'type' => 'string',
                                                ),
                                                'DataType' => array(
                                                    'description' => 'The data type of the option setting.',
                                                    'type' => 'string',
                                                ),
                                                'AllowedValues' => array(
                                                    'description' => 'The allowed values of the option setting.',
                                                    'type' => 'string',
                                                ),
                                                'IsModifiable' => array(
                                                    'description' => 'A Boolean value that, when true, indicates the option setting can be modified from the default.',
                                                    'type' => 'boolean',
                                                ),
                                                'IsCollection' => array(
                                                    'description' => 'Indicates if the option setting is part of a collection.',
                                                    'type' => 'boolean',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'DBSecurityGroupMemberships' => array(
                                        'description' => 'If the option requires access to a port, then this DB Security Group allows access to the port.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'DBSecurityGroup',
                                            'description' => 'This data type is used as a response element in the following actions:',
                                            'type' => 'object',
                                            'sentAs' => 'DBSecurityGroup',
                                            'properties' => array(
                                                'DBSecurityGroupName' => array(
                                                    'description' => 'The name of the DB Security Group.',
                                                    'type' => 'string',
                                                ),
                                                'Status' => array(
                                                    'description' => 'The status of the DB Security Group.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'VpcSecurityGroupMemberships' => array(
                                        'description' => 'If the option requires access to a port, then this VPC Security Group allows access to the port.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'VpcSecurityGroupMembership',
                                            'description' => 'This data type is used as a response element for queries on VPC security group membership.',
                                            'type' => 'object',
                                            'sentAs' => 'VpcSecurityGroupMembership',
                                            'properties' => array(
                                                'VpcSecurityGroupId' => array(
                                                    'description' => 'The name of the VPC security group.',
                                                    'type' => 'string',
                                                ),
                                                'Status' => array(
                                                    'description' => 'The status of the VPC Security Group.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'AllowsVpcAndNonVpcInstanceMemberships' => array(
                            'description' => 'Indicates whether this option group can be applied to both VPC and non-VPC instances. The value \'true\' indicates the option group can be applied to both VPC and non-VPC instances.',
                            'type' => 'boolean',
                        ),
                        'VpcId' => array(
                            'description' => 'If AllowsVpcAndNonVpcInstanceMemberships is \'false\', this field is blank. If AllowsVpcAndNonVpcInstanceMemberships is \'true\' and this field is blank, then this option group can be applied to both VPC and non-VPC instances. If this field contains a value, then this option group can only be applied to instances that are in the VPC indicated by this field.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'DBEngineVersionMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBEngineVersions' => array(
                    'description' => 'A list of DBEngineVersion elements.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBEngineVersion',
                        'description' => 'This data type is used as a response element in the action DescribeDBEngineVersions.',
                        'type' => 'object',
                        'sentAs' => 'DBEngineVersion',
                        'properties' => array(
                            'Engine' => array(
                                'description' => 'The name of the database engine.',
                                'type' => 'string',
                            ),
                            'EngineVersion' => array(
                                'description' => 'The version number of the database engine.',
                                'type' => 'string',
                            ),
                            'DBParameterGroupFamily' => array(
                                'description' => 'The name of the DBParameterGroupFamily for the database engine.',
                                'type' => 'string',
                            ),
                            'DBEngineDescription' => array(
                                'description' => 'The description of the database engine.',
                                'type' => 'string',
                            ),
                            'DBEngineVersionDescription' => array(
                                'description' => 'The description of the database engine version.',
                                'type' => 'string',
                            ),
                            'DefaultCharacterSet' => array(
                                'description' => 'The default character set for new instances of this engine version, if the CharacterSetName parameter of the CreateDBInstance API is not specified.',
                                'type' => 'object',
                                'properties' => array(
                                    'CharacterSetName' => array(
                                        'description' => 'The name of the character set.',
                                        'type' => 'string',
                                    ),
                                    'CharacterSetDescription' => array(
                                        'description' => 'The description of the character set.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'SupportedCharacterSets' => array(
                                'description' => 'A list of the character sets supported by this engine for the CharacterSetName parameter of the CreateDBInstance API.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'CharacterSet',
                                    'description' => 'This data type is used as a response element in the action DescribeDBEngineVersions.',
                                    'type' => 'object',
                                    'sentAs' => 'CharacterSet',
                                    'properties' => array(
                                        'CharacterSetName' => array(
                                            'description' => 'The name of the character set.',
                                            'type' => 'string',
                                        ),
                                        'CharacterSetDescription' => array(
                                            'description' => 'The description of the character set.',
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
        'DBInstanceMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBInstances' => array(
                    'description' => 'A list of DBInstance instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBInstance',
                        'description' => 'Contains the result of a successful invocation of the following actions:',
                        'type' => 'object',
                        'sentAs' => 'DBInstance',
                        'properties' => array(
                            'DBInstanceIdentifier' => array(
                                'description' => 'Contains a user-supplied database identifier. This is the unique key that identifies a DB Instance.',
                                'type' => 'string',
                            ),
                            'DBInstanceClass' => array(
                                'description' => 'Contains the name of the compute and memory capacity class of the DB Instance.',
                                'type' => 'string',
                            ),
                            'Engine' => array(
                                'description' => 'Provides the name of the database engine to be used for this DB Instance.',
                                'type' => 'string',
                            ),
                            'DBInstanceStatus' => array(
                                'description' => 'Specifies the current state of this database.',
                                'type' => 'string',
                            ),
                            'MasterUsername' => array(
                                'description' => 'Contains the master username for the DB Instance.',
                                'type' => 'string',
                            ),
                            'DBName' => array(
                                'description' => 'The meaning of this parameter differs according to the database engine you use.',
                                'type' => 'string',
                            ),
                            'Endpoint' => array(
                                'description' => 'Specifies the connection endpoint.',
                                'type' => 'object',
                                'properties' => array(
                                    'Address' => array(
                                        'description' => 'Specifies the DNS address of the DB Instance.',
                                        'type' => 'string',
                                    ),
                                    'Port' => array(
                                        'description' => 'Specifies the port that the database engine is listening on.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'AllocatedStorage' => array(
                                'description' => 'Specifies the allocated storage size specified in gigabytes.',
                                'type' => 'numeric',
                            ),
                            'InstanceCreateTime' => array(
                                'description' => 'Provides the date and time the DB Instance was created.',
                                'type' => 'string',
                            ),
                            'PreferredBackupWindow' => array(
                                'description' => 'Specifies the daily time range during which automated backups are created if automated backups are enabled, as determined by the BackupRetentionPeriod.',
                                'type' => 'string',
                            ),
                            'BackupRetentionPeriod' => array(
                                'description' => 'Specifies the number of days for which automatic DB Snapshots are retained.',
                                'type' => 'numeric',
                            ),
                            'DBSecurityGroups' => array(
                                'description' => 'Provides List of DB Security Group elements containing only DBSecurityGroup.Name and DBSecurityGroup.Status subelements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'DBSecurityGroup',
                                    'description' => 'This data type is used as a response element in the following actions:',
                                    'type' => 'object',
                                    'sentAs' => 'DBSecurityGroup',
                                    'properties' => array(
                                        'DBSecurityGroupName' => array(
                                            'description' => 'The name of the DB Security Group.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the DB Security Group.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'VpcSecurityGroups' => array(
                                'description' => 'Provides List of VPC security group elements that the DB Instance belongs to.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'VpcSecurityGroupMembership',
                                    'description' => 'This data type is used as a response element for queries on VPC security group membership.',
                                    'type' => 'object',
                                    'sentAs' => 'VpcSecurityGroupMembership',
                                    'properties' => array(
                                        'VpcSecurityGroupId' => array(
                                            'description' => 'The name of the VPC security group.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the VPC Security Group.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'DBParameterGroups' => array(
                                'description' => 'Provides the list of DB Parameter Groups applied to this DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'DBParameterGroup',
                                    'description' => 'The status of the DB Parameter Group.',
                                    'type' => 'object',
                                    'sentAs' => 'DBParameterGroup',
                                    'properties' => array(
                                        'DBParameterGroupName' => array(
                                            'description' => 'The name of the DP Parameter Group.',
                                            'type' => 'string',
                                        ),
                                        'ParameterApplyStatus' => array(
                                            'description' => 'The status of parameter updates.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'Specifies the name of the Availability Zone the DB Instance is located in.',
                                'type' => 'string',
                            ),
                            'DBSubnetGroup' => array(
                                'description' => 'Provides the inforamtion of the subnet group associated with the DB instance, including the name, descrption and subnets in the subnet group.',
                                'type' => 'object',
                                'properties' => array(
                                    'DBSubnetGroupName' => array(
                                        'description' => 'Specifies the name of the DB Subnet Group.',
                                        'type' => 'string',
                                    ),
                                    'DBSubnetGroupDescription' => array(
                                        'description' => 'Provides the description of the DB Subnet Group.',
                                        'type' => 'string',
                                    ),
                                    'VpcId' => array(
                                        'description' => 'Provides the VpcId of the DB Subnet Group.',
                                        'type' => 'string',
                                    ),
                                    'SubnetGroupStatus' => array(
                                        'description' => 'Provides the status of the DB Subnet Group.',
                                        'type' => 'string',
                                    ),
                                    'Subnets' => array(
                                        'description' => 'Contains a list of Subnet elements.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Subnet',
                                            'description' => 'This data type is used as a response element in the DescribeDBSubnetGroups action.',
                                            'type' => 'object',
                                            'sentAs' => 'Subnet',
                                            'properties' => array(
                                                'SubnetIdentifier' => array(
                                                    'description' => 'Specifies the identifier of the subnet.',
                                                    'type' => 'string',
                                                ),
                                                'SubnetAvailabilityZone' => array(
                                                    'description' => 'Contains Availability Zone information.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'Name' => array(
                                                            'description' => 'The name of the availability zone.',
                                                            'type' => 'string',
                                                        ),
                                                        'ProvisionedIopsCapable' => array(
                                                            'description' => 'True indicates the availability zone is capable of provisioned IOPs.',
                                                            'type' => 'boolean',
                                                        ),
                                                    ),
                                                ),
                                                'SubnetStatus' => array(
                                                    'description' => 'Specifies the status of the subnet.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'PreferredMaintenanceWindow' => array(
                                'description' => 'Specifies the weekly time range (in UTC) during which system maintenance can occur.',
                                'type' => 'string',
                            ),
                            'PendingModifiedValues' => array(
                                'description' => 'Specifies that changes to the DB Instance are pending. This element is only included when changes are pending. Specific changes are identified by subelements.',
                                'type' => 'object',
                                'properties' => array(
                                    'DBInstanceClass' => array(
                                        'description' => 'Contains the new DBInstanceClass for the DB Instance that will be applied or is in progress.',
                                        'type' => 'string',
                                    ),
                                    'AllocatedStorage' => array(
                                        'description' => 'Contains the new AllocatedStorage size for the DB Instance that will be applied or is in progress.',
                                        'type' => 'numeric',
                                    ),
                                    'MasterUserPassword' => array(
                                        'description' => 'Contains the pending or in-progress change of the master credentials for the DB Instance.',
                                        'type' => 'string',
                                    ),
                                    'Port' => array(
                                        'description' => 'Specifies the pending port for the DB Instance.',
                                        'type' => 'numeric',
                                    ),
                                    'BackupRetentionPeriod' => array(
                                        'description' => 'Specifies the pending number of days for which automated backups are retained.',
                                        'type' => 'numeric',
                                    ),
                                    'MultiAZ' => array(
                                        'description' => 'Indicates that the Single-AZ DB Instance is to change to a Multi-AZ deployment.',
                                        'type' => 'boolean',
                                    ),
                                    'EngineVersion' => array(
                                        'description' => 'Indicates the database engine version.',
                                        'type' => 'string',
                                    ),
                                    'Iops' => array(
                                        'description' => 'Specifies the new Provisioned IOPS value for the DB Instance that will be applied or is being applied.',
                                        'type' => 'numeric',
                                    ),
                                    'DBInstanceIdentifier' => array(
                                        'description' => 'Contains the new DBInstanceIdentifier for the DB Instance that will be applied or is in progress.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'LatestRestorableTime' => array(
                                'description' => 'Specifies the latest time to which a database can be restored with point-in-time restore.',
                                'type' => 'string',
                            ),
                            'MultiAZ' => array(
                                'description' => 'Specifies if the DB Instance is a Multi-AZ deployment.',
                                'type' => 'boolean',
                            ),
                            'EngineVersion' => array(
                                'description' => 'Indicates the database engine version.',
                                'type' => 'string',
                            ),
                            'AutoMinorVersionUpgrade' => array(
                                'description' => 'Indicates that minor version patches are applied automatically.',
                                'type' => 'boolean',
                            ),
                            'ReadReplicaSourceDBInstanceIdentifier' => array(
                                'description' => 'Contains the identifier of the source DB Instance if this DB Instance is a Read Replica.',
                                'type' => 'string',
                            ),
                            'ReadReplicaDBInstanceIdentifiers' => array(
                                'description' => 'Contains one or more identifiers of the Read Replicas associated with this DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ReadReplicaDBInstanceIdentifier',
                                    'type' => 'string',
                                    'sentAs' => 'ReadReplicaDBInstanceIdentifier',
                                ),
                            ),
                            'LicenseModel' => array(
                                'description' => 'License model information for this DB Instance.',
                                'type' => 'string',
                            ),
                            'Iops' => array(
                                'description' => 'Specifies the Provisioned IOPS (I/O operations per second) value.',
                                'type' => 'numeric',
                            ),
                            'OptionGroupMemberships' => array(
                                'description' => 'Provides the list of option group memberships for this DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'OptionGroupMembership',
                                    'description' => 'Provides information on the option groups the DB instance is a member of.',
                                    'type' => 'object',
                                    'sentAs' => 'OptionGroupMembership',
                                    'properties' => array(
                                        'OptionGroupName' => array(
                                            'description' => 'The name of the option group that the instance belongs to.',
                                            'type' => 'string',
                                        ),
                                        'Status' => array(
                                            'description' => 'The status of the DB Instance\'s option group membership (e.g. in-sync, pending, pending-maintenance, applying).',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CharacterSetName' => array(
                                'description' => 'If present, specifies the name of the character set that this instance is associated with.',
                                'type' => 'string',
                            ),
                            'SecondaryAvailabilityZone' => array(
                                'description' => 'If present, specifies the name of the secondary Availability Zone for a DB instance with multi-AZ support.',
                                'type' => 'string',
                            ),
                            'PubliclyAccessible' => array(
                                'description' => 'Specifies the accessibility options for the DB Instance. A value of true specifies an Internet-facing instance with a publicly resolvable DNS name, which resolves to a public IP address. A value of false specifies an internal instance with a DNS name that resolves to a private IP address.',
                                'type' => 'boolean',
                            ),
                            'StatusInfos' => array(
                                'description' => 'The status of a Read Replica. If the instance is not a for a read replica, this will be blank.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'DBInstanceStatusInfo',
                                    'description' => 'Provides a list of status information for a DB instance.',
                                    'type' => 'object',
                                    'sentAs' => 'DBInstanceStatusInfo',
                                    'properties' => array(
                                        'StatusType' => array(
                                            'description' => 'This value is currently "read replication."',
                                            'type' => 'string',
                                        ),
                                        'Normal' => array(
                                            'description' => 'Boolean value that is true if the instance is operating normally, or false if the instance is in an error state.',
                                            'type' => 'boolean',
                                        ),
                                        'Status' => array(
                                            'description' => 'Status of the DB instance. For a StatusType of Read Replica, the values can be replicating, error, stopped, or terminated.',
                                            'type' => 'string',
                                        ),
                                        'Message' => array(
                                            'description' => 'Details of the error if there is an error for the instance. If the instance is not in an error state, this value is blank.',
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
        'DescribeDBLogFilesResponse' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DescribeDBLogFiles' => array(
                    'description' => 'The DB log files returned.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DescribeDBLogFilesDetails',
                        'description' => 'This data type is used as a response element to DescribeDBLogFiles.',
                        'type' => 'object',
                        'sentAs' => 'DescribeDBLogFilesDetails',
                        'properties' => array(
                            'LogFileName' => array(
                                'description' => 'The name of the log file for the specified DB instance.',
                                'type' => 'string',
                            ),
                            'LastWritten' => array(
                                'description' => 'A POSIX timestamp when the last log entry was written.',
                                'type' => 'numeric',
                            ),
                            'Size' => array(
                                'description' => 'The size, in bytes, of the log file for the specified DB instance.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional paging token.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DBParameterGroupsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBParameterGroups' => array(
                    'description' => 'A list of DBParameterGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBParameterGroup',
                        'description' => 'Contains the result of a successful invocation of the CreateDBParameterGroup action.',
                        'type' => 'object',
                        'sentAs' => 'DBParameterGroup',
                        'properties' => array(
                            'DBParameterGroupName' => array(
                                'description' => 'Provides the name of the DB Parameter Group.',
                                'type' => 'string',
                            ),
                            'DBParameterGroupFamily' => array(
                                'description' => 'Provides the name of the DB Parameter Group Family that this DB Parameter Group is compatible with.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Provides the customer-specified description for this DB Parameter Group.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DBParameterGroupDetails' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Parameters' => array(
                    'description' => 'A list of Parameter instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Parameter',
                        'description' => 'This data type is used as a request parameter in the ModifyDBParameterGroup and ResetDBParameterGroup actions.',
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
                            'ApplyType' => array(
                                'description' => 'Specifies the engine specific parameters type.',
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
                            'ApplyMethod' => array(
                                'description' => 'Indicates when to apply parameter updates.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DBSecurityGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBSecurityGroups' => array(
                    'description' => 'A list of DBSecurityGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBSecurityGroup',
                        'description' => 'Contains the result of a successful invocation of the following actions:',
                        'type' => 'object',
                        'sentAs' => 'DBSecurityGroup',
                        'properties' => array(
                            'OwnerId' => array(
                                'description' => 'Provides the AWS ID of the owner of a specific DB Security Group.',
                                'type' => 'string',
                            ),
                            'DBSecurityGroupName' => array(
                                'description' => 'Specifies the name of the DB Security Group.',
                                'type' => 'string',
                            ),
                            'DBSecurityGroupDescription' => array(
                                'description' => 'Provides the description of the DB Security Group.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'Provides the VpcId of the DB Security Group.',
                                'type' => 'string',
                            ),
                            'EC2SecurityGroups' => array(
                                'description' => 'Contains a list of EC2SecurityGroup elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EC2SecurityGroup',
                                    'description' => 'This data type is used as a response element in the following actions:',
                                    'type' => 'object',
                                    'sentAs' => 'EC2SecurityGroup',
                                    'properties' => array(
                                        'Status' => array(
                                            'description' => 'Provides the status of the EC2 security group. Status can be "authorizing", "authorized", "revoking", and "revoked".',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupName' => array(
                                            'description' => 'Specifies the name of the EC2 Security Group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupId' => array(
                                            'description' => 'Specifies the id of the EC2 Security Group.',
                                            'type' => 'string',
                                        ),
                                        'EC2SecurityGroupOwnerId' => array(
                                            'description' => 'Specifies the AWS ID of the owner of the EC2 security group specified in the EC2SecurityGroupName field.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'IPRanges' => array(
                                'description' => 'Contains a list of IPRange elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'IPRange',
                                    'description' => 'This data type is used as a response element in the DescribeDBSecurityGroups action.',
                                    'type' => 'object',
                                    'sentAs' => 'IPRange',
                                    'properties' => array(
                                        'Status' => array(
                                            'description' => 'Specifies the status of the IP range. Status can be "authorizing", "authorized", "revoking", and "revoked".',
                                            'type' => 'string',
                                        ),
                                        'CIDRIP' => array(
                                            'description' => 'Specifies the IP range.',
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
        'DBSnapshotMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBSnapshots' => array(
                    'description' => 'A list of DBSnapshot instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBSnapshot',
                        'description' => 'Contains the result of a successful invocation of the following actions:',
                        'type' => 'object',
                        'sentAs' => 'DBSnapshot',
                        'properties' => array(
                            'DBSnapshotIdentifier' => array(
                                'description' => 'Specifies the identifier for the DB Snapshot.',
                                'type' => 'string',
                            ),
                            'DBInstanceIdentifier' => array(
                                'description' => 'Specifies the the DBInstanceIdentifier of the DB Instance this DB Snapshot was created from.',
                                'type' => 'string',
                            ),
                            'SnapshotCreateTime' => array(
                                'description' => 'Provides the time (UTC) when the snapshot was taken.',
                                'type' => 'string',
                            ),
                            'Engine' => array(
                                'description' => 'Specifies the name of the database engine.',
                                'type' => 'string',
                            ),
                            'AllocatedStorage' => array(
                                'description' => 'Specifies the allocated storage size in gigabytes (GB).',
                                'type' => 'numeric',
                            ),
                            'Status' => array(
                                'description' => 'Specifies the status of this DB Snapshot.',
                                'type' => 'string',
                            ),
                            'Port' => array(
                                'description' => 'Specifies the port that the database engine was listening on at the time of the snapshot.',
                                'type' => 'numeric',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'Specifies the name of the Availability Zone the DB Instance was located in at the time of the DB Snapshot.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'Provides the Vpc Id associated with the DB Snapshot.',
                                'type' => 'string',
                            ),
                            'InstanceCreateTime' => array(
                                'description' => 'Specifies the time (UTC) when the snapshot was taken.',
                                'type' => 'string',
                            ),
                            'MasterUsername' => array(
                                'description' => 'Provides the master username for the DB Snapshot.',
                                'type' => 'string',
                            ),
                            'EngineVersion' => array(
                                'description' => 'Specifies the version of the database engine.',
                                'type' => 'string',
                            ),
                            'LicenseModel' => array(
                                'description' => 'License model information for the restored DB Instance.',
                                'type' => 'string',
                            ),
                            'SnapshotType' => array(
                                'description' => 'Provides the type of the DB Snapshot.',
                                'type' => 'string',
                            ),
                            'Iops' => array(
                                'description' => 'Specifies the Provisioned IOPS (I/O operations per second) value of the DB Instance at the time of the snapshot.',
                                'type' => 'numeric',
                            ),
                            'OptionGroupName' => array(
                                'description' => 'Provides the option group name for the DB Snapshot.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DBSubnetGroupMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DBSubnetGroups' => array(
                    'description' => 'A list of DBSubnetGroup instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DBSubnetGroup',
                        'description' => 'Contains the result of a successful invocation of the following actions:',
                        'type' => 'object',
                        'sentAs' => 'DBSubnetGroup',
                        'properties' => array(
                            'DBSubnetGroupName' => array(
                                'description' => 'Specifies the name of the DB Subnet Group.',
                                'type' => 'string',
                            ),
                            'DBSubnetGroupDescription' => array(
                                'description' => 'Provides the description of the DB Subnet Group.',
                                'type' => 'string',
                            ),
                            'VpcId' => array(
                                'description' => 'Provides the VpcId of the DB Subnet Group.',
                                'type' => 'string',
                            ),
                            'SubnetGroupStatus' => array(
                                'description' => 'Provides the status of the DB Subnet Group.',
                                'type' => 'string',
                            ),
                            'Subnets' => array(
                                'description' => 'Contains a list of Subnet elements.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Subnet',
                                    'description' => 'This data type is used as a response element in the DescribeDBSubnetGroups action.',
                                    'type' => 'object',
                                    'sentAs' => 'Subnet',
                                    'properties' => array(
                                        'SubnetIdentifier' => array(
                                            'description' => 'Specifies the identifier of the subnet.',
                                            'type' => 'string',
                                        ),
                                        'SubnetAvailabilityZone' => array(
                                            'description' => 'Contains Availability Zone information.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'The name of the availability zone.',
                                                    'type' => 'string',
                                                ),
                                                'ProvisionedIopsCapable' => array(
                                                    'description' => 'True indicates the availability zone is capable of provisioned IOPs.',
                                                    'type' => 'boolean',
                                                ),
                                            ),
                                        ),
                                        'SubnetStatus' => array(
                                            'description' => 'Specifies the status of the subnet.',
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
        'EngineDefaultsWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EngineDefaults' => array(
                    'description' => 'Contains the result of a successful invocation of the DescribeEngineDefaultParameters action.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'DBParameterGroupFamily' => array(
                            'description' => 'Specifies the name of the DB Parameter Group Family which the engine default parameters apply to.',
                            'type' => 'string',
                        ),
                        'Marker' => array(
                            'description' => 'An optional pagination token provided by a previous EngineDefaults request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                            'type' => 'string',
                        ),
                        'Parameters' => array(
                            'description' => 'Contains a list of engine default parameters.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Parameter',
                                'description' => 'This data type is used as a request parameter in the ModifyDBParameterGroup and ResetDBParameterGroup actions.',
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
                                    'ApplyType' => array(
                                        'description' => 'Specifies the engine specific parameters type.',
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
                                    'ApplyMethod' => array(
                                        'description' => 'Indicates when to apply parameter updates.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'EventCategoriesMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EventCategoriesMapList' => array(
                    'description' => 'A list of EventCategoriesMap data types.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'EventCategoriesMap',
                        'description' => 'Contains the results of a successful invocation of the DescribeEventCategories action.',
                        'type' => 'object',
                        'sentAs' => 'EventCategoriesMap',
                        'properties' => array(
                            'SourceType' => array(
                                'description' => 'The source type that the returned categories belong to',
                                'type' => 'string',
                            ),
                            'EventCategories' => array(
                                'description' => 'The event categories for the specified source type',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EventCategory',
                                    'type' => 'string',
                                    'sentAs' => 'EventCategory',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'EventSubscriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DescribeOrderableDBInstanceOptions request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'EventSubscriptionsList' => array(
                    'description' => 'A list of EventSubscriptions data types.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'EventSubscription',
                        'description' => 'Contains the results of a successful invocation of the DescribeEventSubscriptions action.',
                        'type' => 'object',
                        'sentAs' => 'EventSubscription',
                        'properties' => array(
                            'CustomerAwsId' => array(
                                'description' => 'The AWS customer account associated with the RDS event notification subscription.',
                                'type' => 'string',
                            ),
                            'CustSubscriptionId' => array(
                                'description' => 'The RDS event notification subscription Id.',
                                'type' => 'string',
                            ),
                            'SnsTopicArn' => array(
                                'description' => 'The topic ARN of the RDS event notification subscription.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The status of the RDS event notification subscription.',
                                'type' => 'string',
                            ),
                            'SubscriptionCreationTime' => array(
                                'description' => 'The time the RDS event notification subscription was created.',
                                'type' => 'string',
                            ),
                            'SourceType' => array(
                                'description' => 'The source type for the RDS event notification subscription.',
                                'type' => 'string',
                            ),
                            'SourceIdsList' => array(
                                'description' => 'A list of source Ids for the RDS event notification subscription.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'SourceId',
                                    'type' => 'string',
                                    'sentAs' => 'SourceId',
                                ),
                            ),
                            'EventCategoriesList' => array(
                                'description' => 'A list of event categories for the RDS event notification subscription.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EventCategory',
                                    'type' => 'string',
                                    'sentAs' => 'EventCategory',
                                ),
                            ),
                            'Enabled' => array(
                                'description' => 'A Boolean value indicating if the subscription is enabled. True indicates the subscription is enabled.',
                                'type' => 'boolean',
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
                    'description' => 'An optional pagination token provided by a previous Events request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Events' => array(
                    'description' => 'A list of Event instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Event',
                        'description' => 'This data type is used as a response element in the DescribeEvents action.',
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
                            'EventCategories' => array(
                                'description' => 'Specifies the category for the event.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EventCategory',
                                    'type' => 'string',
                                    'sentAs' => 'EventCategory',
                                ),
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
        'OptionGroupOptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OptionGroupOptions' => array(
                    'description' => 'List of available option group options.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'OptionGroupOption',
                        'description' => 'Available option.',
                        'type' => 'object',
                        'sentAs' => 'OptionGroupOption',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name of the option.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'The description of the option.',
                                'type' => 'string',
                            ),
                            'EngineName' => array(
                                'description' => 'Engine name that this option can be applied to.',
                                'type' => 'string',
                            ),
                            'MajorEngineVersion' => array(
                                'description' => 'Indicates the major engine version that the option is available for.',
                                'type' => 'string',
                            ),
                            'MinimumRequiredMinorEngineVersion' => array(
                                'description' => 'The minimum required engine version for the option to be applied.',
                                'type' => 'string',
                            ),
                            'PortRequired' => array(
                                'description' => 'Specifies whether the option requires a port.',
                                'type' => 'boolean',
                            ),
                            'DefaultPort' => array(
                                'description' => 'If the option requires a port, specifies the default port for the option.',
                                'type' => 'numeric',
                            ),
                            'OptionsDependedOn' => array(
                                'description' => 'List of all options that are prerequisites for this option.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'OptionName',
                                    'type' => 'string',
                                    'sentAs' => 'OptionName',
                                ),
                            ),
                            'Persistent' => array(
                                'description' => 'A persistent option cannot be removed from the option group once the option group is used, but this option can be removed from the db instance while modifying the related data and assigning another option group without this option.',
                                'type' => 'boolean',
                            ),
                            'Permanent' => array(
                                'description' => 'A permanent option cannot be removed from the option group once the option group is used, and it cannot be removed from the db instance after assigning an option group with this permanent option.',
                                'type' => 'boolean',
                            ),
                            'OptionGroupOptionSettings' => array(
                                'description' => 'Specifies the option settings that are available (and the default value) for each option in an option group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'OptionGroupOptionSetting',
                                    'description' => 'Option Group option settings are used to display settings available for each option with their default values and other information. These values are used with the DescribeOptionGroupOptions action.',
                                    'type' => 'object',
                                    'sentAs' => 'OptionGroupOptionSetting',
                                    'properties' => array(
                                        'SettingName' => array(
                                            'description' => 'The name of the option group option.',
                                            'type' => 'string',
                                        ),
                                        'SettingDescription' => array(
                                            'description' => 'The description of the option group option.',
                                            'type' => 'string',
                                        ),
                                        'DefaultValue' => array(
                                            'description' => 'The default value for the option group option.',
                                            'type' => 'string',
                                        ),
                                        'ApplyType' => array(
                                            'description' => 'The DB engine specific parameter type for the option group option.',
                                            'type' => 'string',
                                        ),
                                        'AllowedValues' => array(
                                            'description' => 'Indicates the acceptable values for the option group option.',
                                            'type' => 'string',
                                        ),
                                        'IsModifiable' => array(
                                            'description' => 'Boolean value where true indicates that this option group option can be changed from the default value.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'OptionGroups' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OptionGroupsList' => array(
                    'description' => 'List of option groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'OptionGroup',
                        'type' => 'object',
                        'sentAs' => 'OptionGroup',
                        'properties' => array(
                            'OptionGroupName' => array(
                                'description' => 'Specifies the name of the option group.',
                                'type' => 'string',
                            ),
                            'OptionGroupDescription' => array(
                                'description' => 'Provides the description of the option group.',
                                'type' => 'string',
                            ),
                            'EngineName' => array(
                                'description' => 'Engine name that this option group can be applied to.',
                                'type' => 'string',
                            ),
                            'MajorEngineVersion' => array(
                                'description' => 'Indicates the major engine version associated with this option group.',
                                'type' => 'string',
                            ),
                            'Options' => array(
                                'description' => 'Indicates what options are available in the option group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Option',
                                    'description' => 'Option details.',
                                    'type' => 'object',
                                    'sentAs' => 'Option',
                                    'properties' => array(
                                        'OptionName' => array(
                                            'description' => 'The name of the option.',
                                            'type' => 'string',
                                        ),
                                        'OptionDescription' => array(
                                            'description' => 'The description of the option.',
                                            'type' => 'string',
                                        ),
                                        'Persistent' => array(
                                            'description' => 'Indicate if this option is persistent.',
                                            'type' => 'boolean',
                                        ),
                                        'Permanent' => array(
                                            'description' => 'Indicate if this option is permanent.',
                                            'type' => 'boolean',
                                        ),
                                        'Port' => array(
                                            'description' => 'If required, the port configured for this option to use.',
                                            'type' => 'numeric',
                                        ),
                                        'OptionSettings' => array(
                                            'description' => 'The option settings for this option.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'OptionSetting',
                                                'description' => 'Option settings are the actual settings being applied or configured for that option. It is used when you modify an option group or describe option groups. For example, the NATIVE_NETWORK_ENCRYPTION option has a setting called SQLNET.ENCRYPTION_SERVER that can have several different values.',
                                                'type' => 'object',
                                                'sentAs' => 'OptionSetting',
                                                'properties' => array(
                                                    'Name' => array(
                                                        'description' => 'The name of the option that has settings that you can set.',
                                                        'type' => 'string',
                                                    ),
                                                    'Value' => array(
                                                        'description' => 'The current value of the option setting.',
                                                        'type' => 'string',
                                                    ),
                                                    'DefaultValue' => array(
                                                        'description' => 'The default value of the option setting.',
                                                        'type' => 'string',
                                                    ),
                                                    'Description' => array(
                                                        'description' => 'The description of the option setting.',
                                                        'type' => 'string',
                                                    ),
                                                    'ApplyType' => array(
                                                        'description' => 'The DB engine specific parameter type.',
                                                        'type' => 'string',
                                                    ),
                                                    'DataType' => array(
                                                        'description' => 'The data type of the option setting.',
                                                        'type' => 'string',
                                                    ),
                                                    'AllowedValues' => array(
                                                        'description' => 'The allowed values of the option setting.',
                                                        'type' => 'string',
                                                    ),
                                                    'IsModifiable' => array(
                                                        'description' => 'A Boolean value that, when true, indicates the option setting can be modified from the default.',
                                                        'type' => 'boolean',
                                                    ),
                                                    'IsCollection' => array(
                                                        'description' => 'Indicates if the option setting is part of a collection.',
                                                        'type' => 'boolean',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'DBSecurityGroupMemberships' => array(
                                            'description' => 'If the option requires access to a port, then this DB Security Group allows access to the port.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'DBSecurityGroup',
                                                'description' => 'This data type is used as a response element in the following actions:',
                                                'type' => 'object',
                                                'sentAs' => 'DBSecurityGroup',
                                                'properties' => array(
                                                    'DBSecurityGroupName' => array(
                                                        'description' => 'The name of the DB Security Group.',
                                                        'type' => 'string',
                                                    ),
                                                    'Status' => array(
                                                        'description' => 'The status of the DB Security Group.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'VpcSecurityGroupMemberships' => array(
                                            'description' => 'If the option requires access to a port, then this VPC Security Group allows access to the port.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'VpcSecurityGroupMembership',
                                                'description' => 'This data type is used as a response element for queries on VPC security group membership.',
                                                'type' => 'object',
                                                'sentAs' => 'VpcSecurityGroupMembership',
                                                'properties' => array(
                                                    'VpcSecurityGroupId' => array(
                                                        'description' => 'The name of the VPC security group.',
                                                        'type' => 'string',
                                                    ),
                                                    'Status' => array(
                                                        'description' => 'The status of the VPC Security Group.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'AllowsVpcAndNonVpcInstanceMemberships' => array(
                                'description' => 'Indicates whether this option group can be applied to both VPC and non-VPC instances. The value \'true\' indicates the option group can be applied to both VPC and non-VPC instances.',
                                'type' => 'boolean',
                            ),
                            'VpcId' => array(
                                'description' => 'If AllowsVpcAndNonVpcInstanceMemberships is \'false\', this field is blank. If AllowsVpcAndNonVpcInstanceMemberships is \'true\' and this field is blank, then this option group can be applied to both VPC and non-VPC instances. If this field contains a value, then this option group can only be applied to instances that are in the VPC indicated by this field.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'OrderableDBInstanceOptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'OrderableDBInstanceOptions' => array(
                    'description' => 'An OrderableDBInstanceOption structure containing information about orderable options for the DB Instance.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'OrderableDBInstanceOption',
                        'description' => 'Contains a list of available options for a DB Instance',
                        'type' => 'object',
                        'sentAs' => 'OrderableDBInstanceOption',
                        'properties' => array(
                            'Engine' => array(
                                'description' => 'The engine type of the orderable DB Instance.',
                                'type' => 'string',
                            ),
                            'EngineVersion' => array(
                                'description' => 'The engine version of the orderable DB Instance.',
                                'type' => 'string',
                            ),
                            'DBInstanceClass' => array(
                                'description' => 'The DB Instance Class for the orderable DB Instance',
                                'type' => 'string',
                            ),
                            'LicenseModel' => array(
                                'description' => 'The license model for the orderable DB Instance.',
                                'type' => 'string',
                            ),
                            'AvailabilityZones' => array(
                                'description' => 'A list of availability zones for the orderable DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'AvailabilityZone',
                                    'description' => 'Contains Availability Zone information.',
                                    'type' => 'object',
                                    'sentAs' => 'AvailabilityZone',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the availability zone.',
                                            'type' => 'string',
                                        ),
                                        'ProvisionedIopsCapable' => array(
                                            'description' => 'True indicates the availability zone is capable of provisioned IOPs.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                            ),
                            'MultiAZCapable' => array(
                                'description' => 'Indicates whether this orderable DB Instance is multi-AZ capable.',
                                'type' => 'boolean',
                            ),
                            'ReadReplicaCapable' => array(
                                'description' => 'Indicates whether this orderable DB Instance can have a read replica.',
                                'type' => 'boolean',
                            ),
                            'Vpc' => array(
                                'description' => 'Indicates whether this is a VPC orderable DB Instance.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous OrderableDBInstanceOptions request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords .',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ReservedDBInstanceMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedDBInstances' => array(
                    'description' => 'A list of of reserved DB Instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedDBInstance',
                        'description' => 'This data type is used as a response element in the DescribeReservedDBInstances and PurchaseReservedDBInstancesOffering actions.',
                        'type' => 'object',
                        'sentAs' => 'ReservedDBInstance',
                        'properties' => array(
                            'ReservedDBInstanceId' => array(
                                'description' => 'The unique identifier for the reservation.',
                                'type' => 'string',
                            ),
                            'ReservedDBInstancesOfferingId' => array(
                                'description' => 'The offering identifier.',
                                'type' => 'string',
                            ),
                            'DBInstanceClass' => array(
                                'description' => 'The DB instance class for the reserved DB Instance.',
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
                                'description' => 'The fixed price charged for this reserved DB Instance.',
                                'type' => 'numeric',
                            ),
                            'UsagePrice' => array(
                                'description' => 'The hourly price charged for this reserved DB Instance.',
                                'type' => 'numeric',
                            ),
                            'CurrencyCode' => array(
                                'description' => 'The currency code for the reserved DB Instance.',
                                'type' => 'string',
                            ),
                            'DBInstanceCount' => array(
                                'description' => 'The number of reserved DB Instances.',
                                'type' => 'numeric',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The description of the reserved DB Instance.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The offering type of this reserved DB Instance.',
                                'type' => 'string',
                            ),
                            'MultiAZ' => array(
                                'description' => 'Indicates if the reservation applies to Multi-AZ deployments.',
                                'type' => 'boolean',
                            ),
                            'State' => array(
                                'description' => 'The state of the reserved DB Instance.',
                                'type' => 'string',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring price charged to run this reserved DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'This data type is used as a response element in the DescribeReservedDBInstances and DescribeReservedDBInstancesOfferings actions.',
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
        'ReservedDBInstancesOfferingMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous request. If this parameter is specified, the response includes only records beyond the marker, up to the value specified by MaxRecords.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ReservedDBInstancesOfferings' => array(
                    'description' => 'A list of reserved DB Instance offerings.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ReservedDBInstancesOffering',
                        'description' => 'This data type is used as a response element in the DescribeReservedDBInstancesOfferings action.',
                        'type' => 'object',
                        'sentAs' => 'ReservedDBInstancesOffering',
                        'properties' => array(
                            'ReservedDBInstancesOfferingId' => array(
                                'description' => 'The offering identifier.',
                                'type' => 'string',
                            ),
                            'DBInstanceClass' => array(
                                'description' => 'The DB instance class for the reserved DB Instance.',
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
                            'CurrencyCode' => array(
                                'description' => 'The currency code for the reserved DB Instance offering.',
                                'type' => 'string',
                            ),
                            'ProductDescription' => array(
                                'description' => 'The database engine used by the offering.',
                                'type' => 'string',
                            ),
                            'OfferingType' => array(
                                'description' => 'The offering type.',
                                'type' => 'string',
                            ),
                            'MultiAZ' => array(
                                'description' => 'Indicates if the offering applies to Multi-AZ deployments.',
                                'type' => 'boolean',
                            ),
                            'RecurringCharges' => array(
                                'description' => 'The recurring price charged to run this reserved DB Instance.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'RecurringCharge',
                                    'description' => 'This data type is used as a response element in the DescribeReservedDBInstances and DescribeReservedDBInstancesOfferings actions.',
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
        'DownloadDBLogFilePortionDetails' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LogFileData' => array(
                    'description' => 'Entries from the specified log file.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'description' => 'An optional pagination token provided by a previous DownloadDBLogFilePortion request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'AdditionalDataPending' => array(
                    'description' => 'Boolean value that if true, indicates there is more data to be downloaded.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
            ),
        ),
        'TagListMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TagList' => array(
                    'description' => 'List of tags returned by the ListTagsForResource operation.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'Metadata assigned to a DB Instance consisting of a key-value pair.',
                        'type' => 'object',
                        'sentAs' => 'Tag',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'A key is the required name of the tag. The string value can be from 1 to 128 Unicode characters in length and cannot be prefixed with "aws:". The string may only contain only the set of Unicode letters, digits, white-space, \'_\', \'.\', \'/\', \'=\', \'+\', \'-\' (Java regex: "^([\\\\p{L}\\\\p{Z}\\\\p{N}_.:/=+\\\\-]*)$").',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'A value is the optional value of the tag. The string value can be from 1 to 256 Unicode characters in length and cannot be prefixed with "aws:". The string may only contain only the set of Unicode letters, digits, white-space, \'_\', \'.\', \'/\', \'=\', \'+\', \'-\' (Java regex: "^([\\\\p{L}\\\\p{Z}\\\\p{N}_.:/=+\\\\-]*)$").',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DBParameterGroupNameMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DBParameterGroupName' => array(
                    'description' => 'The name of the DB Parameter Group.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ReservedDBInstanceWrapper' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ReservedDBInstance' => array(
                    'description' => 'This data type is used as a response element in the DescribeReservedDBInstances and PurchaseReservedDBInstancesOffering actions.',
                    'type' => 'object',
                    'location' => 'xml',
                    'data' => array(
                        'wrapper' => true,
                    ),
                    'properties' => array(
                        'ReservedDBInstanceId' => array(
                            'description' => 'The unique identifier for the reservation.',
                            'type' => 'string',
                        ),
                        'ReservedDBInstancesOfferingId' => array(
                            'description' => 'The offering identifier.',
                            'type' => 'string',
                        ),
                        'DBInstanceClass' => array(
                            'description' => 'The DB instance class for the reserved DB Instance.',
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
                            'description' => 'The fixed price charged for this reserved DB Instance.',
                            'type' => 'numeric',
                        ),
                        'UsagePrice' => array(
                            'description' => 'The hourly price charged for this reserved DB Instance.',
                            'type' => 'numeric',
                        ),
                        'CurrencyCode' => array(
                            'description' => 'The currency code for the reserved DB Instance.',
                            'type' => 'string',
                        ),
                        'DBInstanceCount' => array(
                            'description' => 'The number of reserved DB Instances.',
                            'type' => 'numeric',
                        ),
                        'ProductDescription' => array(
                            'description' => 'The description of the reserved DB Instance.',
                            'type' => 'string',
                        ),
                        'OfferingType' => array(
                            'description' => 'The offering type of this reserved DB Instance.',
                            'type' => 'string',
                        ),
                        'MultiAZ' => array(
                            'description' => 'Indicates if the reservation applies to Multi-AZ deployments.',
                            'type' => 'boolean',
                        ),
                        'State' => array(
                            'description' => 'The state of the reserved DB Instance.',
                            'type' => 'string',
                        ),
                        'RecurringCharges' => array(
                            'description' => 'The recurring price charged to run this reserved DB Instance.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'RecurringCharge',
                                'description' => 'This data type is used as a response element in the DescribeReservedDBInstances and DescribeReservedDBInstancesOfferings actions.',
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
            'DescribeDBEngineVersions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBEngineVersions',
            ),
            'DescribeDBInstances' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBInstances',
            ),
            'DescribeDBLogFiles' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DescribeDBLogFiles',
            ),
            'DescribeDBParameterGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBParameterGroups',
            ),
            'DescribeDBParameters' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Parameters',
            ),
            'DescribeDBSecurityGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBSecurityGroups',
            ),
            'DescribeDBSnapshots' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBSnapshots',
            ),
            'DescribeDBSubnetGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'DBSubnetGroups',
            ),
            'DescribeEngineDefaultParameters' => array(
                'token_param' => 'Marker',
                'limit_key' => 'MaxRecords',
            ),
            'DescribeEventSubscriptions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'EventSubscriptionsList',
            ),
            'DescribeEvents' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Events',
            ),
            'DescribeOptionGroupOptions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'OptionGroupOptions',
            ),
            'DescribeOptionGroups' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'OptionGroupsList',
            ),
            'DescribeOrderableDBInstanceOptions' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'OrderableDBInstanceOptions',
            ),
            'DescribeReservedDBInstances' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedDBInstances',
            ),
            'DescribeReservedDBInstancesOfferings' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ReservedDBInstancesOfferings',
            ),
            'DownloadDBLogFilePortion' => array(
                'token_param' => 'Marker',
                'token_key' => 'Marker',
            ),
            'ListTagsForResource' => array(
                'result_key' => 'TagList',
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 30,
            'max_attempts' => 60,
        ),
        '__DBInstanceState' => array(
            'operation' => 'DescribeDBInstances',
            'acceptor.path' => 'DBInstances/*/DBInstanceStatus',
            'acceptor.type' => 'output',
        ),
        'DBInstanceAvailable' => array(
            'extends' => '__DBInstanceState',
            'success.value' => 'available',
            'failure.value' => array(
                'deleted',
                'deleting',
                'failed',
                'incompatible-restore',
                'incompatible-parameters',
                'incompatible-parameters',
                'incompatible-restore',
            ),
        ),
        'DBInstanceDeleted' => array(
            'extends' => '__DBInstanceState',
            'success.value' => 'deleted',
            'failure.value' => array(
                'creating',
                'modifying',
                'rebooting',
                'resetting-master-credentials',
            ),
        ),
    ),
);
