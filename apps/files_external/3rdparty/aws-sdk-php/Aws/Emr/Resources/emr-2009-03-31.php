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
    'apiVersion' => '2009-03-31',
    'endpointPrefix' => 'elasticmapreduce',
    'serviceFullName' => 'Amazon Elastic MapReduce',
    'serviceAbbreviation' => 'Amazon EMR',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'Emr',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'elasticmapreduce.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AddInstanceGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AddInstanceGroupsOutput',
            'responseType' => 'model',
            'summary' => 'AddInstanceGroups adds an instance group to a running cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddInstanceGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'InstanceGroups' => array(
                    'required' => true,
                    'description' => 'Instance Groups to add.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceGroups.member',
                    'items' => array(
                        'name' => 'InstanceGroupConfig',
                        'description' => 'Configuration defining a new instance group.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'Friendly name given to the instance group.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'Market' => array(
                                'description' => 'Market type of the Amazon EC2 instances used to create a cluster node.',
                                'type' => 'string',
                                'enum' => array(
                                    'ON_DEMAND',
                                    'SPOT',
                                ),
                            ),
                            'InstanceRole' => array(
                                'required' => true,
                                'description' => 'The role of the instance group in the cluster.',
                                'type' => 'string',
                                'enum' => array(
                                    'MASTER',
                                    'CORE',
                                    'TASK',
                                ),
                            ),
                            'BidPrice' => array(
                                'description' => 'Bid price for each Amazon EC2 instance in the instance group when launching nodes as Spot Instances, expressed in USD.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'InstanceType' => array(
                                'required' => true,
                                'description' => 'The Amazon EC2 instance type for all instances in the instance group.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 256,
                            ),
                            'InstanceCount' => array(
                                'required' => true,
                                'description' => 'Target number of instances for the instance group.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'JobFlowId' => array(
                    'required' => true,
                    'description' => 'Job flow in which to add the instance groups.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 256,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'AddJobFlowSteps' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'AddJobFlowSteps adds new steps to a running job flow. A maximum of 256 steps are allowed in each job flow.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'AddJobFlowSteps',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'JobFlowId' => array(
                    'required' => true,
                    'description' => 'A string that uniquely identifies the job flow. This identifier is returned by RunJobFlow and can also be obtained from DescribeJobFlows.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 256,
                ),
                'Steps' => array(
                    'required' => true,
                    'description' => 'A list of StepConfig to be executed by the job flow.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Steps.member',
                    'items' => array(
                        'name' => 'StepConfig',
                        'description' => 'Specification of a job flow step.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the job flow step.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'ActionOnFailure' => array(
                                'description' => 'Specifies the action to take if the job flow step fails.',
                                'type' => 'string',
                                'enum' => array(
                                    'TERMINATE_JOB_FLOW',
                                    'CANCEL_AND_WAIT',
                                    'CONTINUE',
                                ),
                            ),
                            'HadoopJarStep' => array(
                                'required' => true,
                                'description' => 'Specifies the JAR file used for the job flow step.',
                                'type' => 'object',
                                'properties' => array(
                                    'Properties' => array(
                                        'description' => 'A list of Java properties that are set when the step runs. You can use these properties to pass key value pairs to your main function.',
                                        'type' => 'array',
                                        'sentAs' => 'Properties.member',
                                        'items' => array(
                                            'name' => 'KeyValue',
                                            'description' => 'A key value pair.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Key' => array(
                                                    'description' => 'The unique identifier of a key value pair.',
                                                    'type' => 'string',
                                                    'maxLength' => 10280,
                                                ),
                                                'Value' => array(
                                                    'description' => 'The value part of the identified key.',
                                                    'type' => 'string',
                                                    'maxLength' => 10280,
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Jar' => array(
                                        'required' => true,
                                        'description' => 'A path to a JAR file run during the step.',
                                        'type' => 'string',
                                        'maxLength' => 10280,
                                    ),
                                    'MainClass' => array(
                                        'description' => 'The name of the main class in the specified Java file. If not specified, the JAR file should specify a Main-Class in its manifest file.',
                                        'type' => 'string',
                                        'maxLength' => 10280,
                                    ),
                                    'Args' => array(
                                        'description' => 'A list of command line arguments passed to the JAR file\'s main function when executed.',
                                        'type' => 'array',
                                        'sentAs' => 'Args.member',
                                        'items' => array(
                                            'name' => 'XmlString',
                                            'type' => 'string',
                                            'maxLength' => 10280,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'DescribeJobFlows' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeJobFlowsOutput',
            'responseType' => 'model',
            'summary' => 'DescribeJobFlows returns a list of job flows that match all of the supplied parameters. The parameters can include a list of job flow IDs, job flow states, and restrictions on job flow creation date and time.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeJobFlows',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'CreatedAfter' => array(
                    'description' => 'Return only job flows created after this date and time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'CreatedBefore' => array(
                    'description' => 'Return only job flows created before this date and time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'JobFlowIds' => array(
                    'description' => 'Return only job flows whose job flow ID is contained in this list.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'JobFlowIds.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                        'maxLength' => 10280,
                    ),
                ),
                'JobFlowStates' => array(
                    'description' => 'Return only job flows whose state is contained in this list.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'JobFlowStates.member',
                    'items' => array(
                        'name' => 'JobFlowExecutionState',
                        'description' => 'The type of instance.',
                        'type' => 'string',
                        'enum' => array(
                            'COMPLETED',
                            'FAILED',
                            'TERMINATED',
                            'RUNNING',
                            'SHUTTING_DOWN',
                            'STARTING',
                            'WAITING',
                            'BOOTSTRAPPING',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'ModifyInstanceGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'ModifyInstanceGroups modifies the number of nodes and configuration settings of an instance group. The input parameters include the new target instance count for the group and the instance group ID. The call will either succeed or fail atomically.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ModifyInstanceGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'InstanceGroups' => array(
                    'description' => 'Instance groups to change.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceGroups.member',
                    'items' => array(
                        'name' => 'InstanceGroupModifyConfig',
                        'description' => 'Modify an instance group size.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceGroupId' => array(
                                'required' => true,
                                'description' => 'Unique ID of the instance group to expand or shrink.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'InstanceCount' => array(
                                'required' => true,
                                'description' => 'Target size for the instance group.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'RunJobFlow' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RunJobFlowOutput',
            'responseType' => 'model',
            'summary' => 'RunJobFlow creates and starts running a new job flow. The job flow will run the steps specified. Once the job flow completes, the cluster is stopped and the HDFS partition is lost. To prevent loss of data, configure the last step of the job flow to store results in Amazon S3. If the JobFlowInstancesConfig KeepJobFlowAliveWhenNoSteps parameter is set to TRUE, the job flow will transition to the WAITING state rather than shutting down once the steps have completed.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RunJobFlow',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The name of the job flow.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 256,
                ),
                'LogUri' => array(
                    'description' => 'Specifies the location in Amazon S3 to write the log files of the job flow. If a value is not provided, logs are not created.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 10280,
                ),
                'AdditionalInfo' => array(
                    'description' => 'A JSON string for selecting additional features.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 10280,
                ),
                'AmiVersion' => array(
                    'description' => 'The version of the Amazon Machine Image (AMI) to use when launching Amazon EC2 instances in the job flow. The following values are valid:',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 256,
                ),
                'Instances' => array(
                    'required' => true,
                    'description' => 'A specification of the number and type of Amazon EC2 instances on which to run the job flow.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'MasterInstanceType' => array(
                            'description' => 'The EC2 instance type of the master node.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'SlaveInstanceType' => array(
                            'description' => 'The EC2 instance type of the slave nodes.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 256,
                        ),
                        'InstanceCount' => array(
                            'description' => 'The number of Amazon EC2 instances used to execute the job flow.',
                            'type' => 'numeric',
                        ),
                        'InstanceGroups' => array(
                            'description' => 'Configuration for the job flow\'s instance groups.',
                            'type' => 'array',
                            'sentAs' => 'InstanceGroups.member',
                            'items' => array(
                                'name' => 'InstanceGroupConfig',
                                'description' => 'Configuration defining a new instance group.',
                                'type' => 'object',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'Friendly name given to the instance group.',
                                        'type' => 'string',
                                        'maxLength' => 256,
                                    ),
                                    'Market' => array(
                                        'description' => 'Market type of the Amazon EC2 instances used to create a cluster node.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'ON_DEMAND',
                                            'SPOT',
                                        ),
                                    ),
                                    'InstanceRole' => array(
                                        'required' => true,
                                        'description' => 'The role of the instance group in the cluster.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'MASTER',
                                            'CORE',
                                            'TASK',
                                        ),
                                    ),
                                    'BidPrice' => array(
                                        'description' => 'Bid price for each Amazon EC2 instance in the instance group when launching nodes as Spot Instances, expressed in USD.',
                                        'type' => 'string',
                                        'maxLength' => 256,
                                    ),
                                    'InstanceType' => array(
                                        'required' => true,
                                        'description' => 'The Amazon EC2 instance type for all instances in the instance group.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 256,
                                    ),
                                    'InstanceCount' => array(
                                        'required' => true,
                                        'description' => 'Target number of instances for the instance group.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                        'Ec2KeyName' => array(
                            'description' => 'Specifies the name of the Amazon EC2 key pair that can be used to ssh to the master node as the user called "hadoop."',
                            'type' => 'string',
                            'maxLength' => 256,
                        ),
                        'Placement' => array(
                            'description' => 'Specifies the Availability Zone the job flow will run in.',
                            'type' => 'object',
                            'properties' => array(
                                'AvailabilityZone' => array(
                                    'required' => true,
                                    'description' => 'The Amazon EC2 Availability Zone for the job flow.',
                                    'type' => 'string',
                                    'maxLength' => 10280,
                                ),
                            ),
                        ),
                        'KeepJobFlowAliveWhenNoSteps' => array(
                            'description' => 'Specifies whether the job flow should terminate after completing all steps.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'TerminationProtected' => array(
                            'description' => 'Specifies whether to lock the job flow to prevent the Amazon EC2 instances from being terminated by API call, user intervention, or in the event of a job flow error.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'HadoopVersion' => array(
                            'description' => 'Specifies the Hadoop version for the job flow. Valid inputs are "0.18", "0.20", or "0.20.205". If you do not set this value, the default of 0.18 is used, unless the AmiVersion parameter is set in the RunJobFlow call, in which case the default version of Hadoop for that AMI version is used.',
                            'type' => 'string',
                            'maxLength' => 256,
                        ),
                        'Ec2SubnetId' => array(
                            'description' => 'To launch the job flow in Amazon Virtual Private Cloud (Amazon VPC), set this parameter to the identifier of the Amazon VPC subnet where you want the job flow to launch. If you do not specify this value, the job flow is launched in the normal Amazon Web Services cloud, outside of an Amazon VPC.',
                            'type' => 'string',
                            'maxLength' => 256,
                        ),
                    ),
                ),
                'Steps' => array(
                    'description' => 'A list of steps to be executed by the job flow.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Steps.member',
                    'items' => array(
                        'name' => 'StepConfig',
                        'description' => 'Specification of a job flow step.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the job flow step.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'ActionOnFailure' => array(
                                'description' => 'Specifies the action to take if the job flow step fails.',
                                'type' => 'string',
                                'enum' => array(
                                    'TERMINATE_JOB_FLOW',
                                    'CANCEL_AND_WAIT',
                                    'CONTINUE',
                                ),
                            ),
                            'HadoopJarStep' => array(
                                'required' => true,
                                'description' => 'Specifies the JAR file used for the job flow step.',
                                'type' => 'object',
                                'properties' => array(
                                    'Properties' => array(
                                        'description' => 'A list of Java properties that are set when the step runs. You can use these properties to pass key value pairs to your main function.',
                                        'type' => 'array',
                                        'sentAs' => 'Properties.member',
                                        'items' => array(
                                            'name' => 'KeyValue',
                                            'description' => 'A key value pair.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Key' => array(
                                                    'description' => 'The unique identifier of a key value pair.',
                                                    'type' => 'string',
                                                    'maxLength' => 10280,
                                                ),
                                                'Value' => array(
                                                    'description' => 'The value part of the identified key.',
                                                    'type' => 'string',
                                                    'maxLength' => 10280,
                                                ),
                                            ),
                                        ),
                                    ),
                                    'Jar' => array(
                                        'required' => true,
                                        'description' => 'A path to a JAR file run during the step.',
                                        'type' => 'string',
                                        'maxLength' => 10280,
                                    ),
                                    'MainClass' => array(
                                        'description' => 'The name of the main class in the specified Java file. If not specified, the JAR file should specify a Main-Class in its manifest file.',
                                        'type' => 'string',
                                        'maxLength' => 10280,
                                    ),
                                    'Args' => array(
                                        'description' => 'A list of command line arguments passed to the JAR file\'s main function when executed.',
                                        'type' => 'array',
                                        'sentAs' => 'Args.member',
                                        'items' => array(
                                            'name' => 'XmlString',
                                            'type' => 'string',
                                            'maxLength' => 10280,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'BootstrapActions' => array(
                    'description' => 'A list of bootstrap actions that will be run before Hadoop is started on the cluster nodes.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BootstrapActions.member',
                    'items' => array(
                        'name' => 'BootstrapActionConfig',
                        'description' => 'Configuration of a bootstrap action.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the bootstrap action.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'ScriptBootstrapAction' => array(
                                'required' => true,
                                'description' => 'The script run by the bootstrap action.',
                                'type' => 'object',
                                'properties' => array(
                                    'Path' => array(
                                        'required' => true,
                                        'description' => 'Location of the script to run during a bootstrap action. Can be either a location in Amazon S3 or on a local file system.',
                                        'type' => 'string',
                                        'maxLength' => 10280,
                                    ),
                                    'Args' => array(
                                        'description' => 'A list of command line arguments to pass to the bootstrap action script.',
                                        'type' => 'array',
                                        'sentAs' => 'Args.member',
                                        'items' => array(
                                            'name' => 'XmlString',
                                            'type' => 'string',
                                            'maxLength' => 10280,
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'SupportedProducts' => array(
                    'description' => 'A list of strings that indicates third-party software to use with the job flow. For more information, go to Use Third Party Applications with Amazon EMR. Currently supported values are:',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SupportedProducts.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen256',
                        'type' => 'string',
                        'maxLength' => 256,
                    ),
                ),
                'VisibleToAllUsers' => array(
                    'description' => 'Whether the job flow is visible to all IAM users of the AWS account associated with the job flow. If this value is set to true, all IAM users of that AWS account can view and (if they have the proper policy permissions set) manage the job flow. If it is set to false, only the IAM user that created the job flow can view and manage it.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'JobFlowRole' => array(
                    'description' => 'An IAM role for the job flow. The EC2 instances of the job flow assume this role. The default role is EMRJobflowDefault. In order to use the default role, you must have already created it using the CLI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 10280,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'SetTerminationProtection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'SetTerminationProtection locks a job flow so the Amazon EC2 instances in the cluster cannot be terminated by user intervention, an API call, or in the event of a job-flow error. The cluster still terminates upon successful completion of the job flow. Calling SetTerminationProtection on a job flow is analogous to calling the Amazon EC2 DisableAPITermination API on all of the EC2 instances in a cluster.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetTerminationProtection',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'JobFlowIds' => array(
                    'required' => true,
                    'description' => 'A list of strings that uniquely identify the job flows to protect. This identifier is returned by RunJobFlow and can also be obtained from DescribeJobFlows .',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'JobFlowIds.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                        'maxLength' => 10280,
                    ),
                ),
                'TerminationProtected' => array(
                    'required' => true,
                    'description' => 'A Boolean that indicates whether to protect the job flow and prevent the Amazon EC2 instances in the cluster from shutting down due to API calls, user intervention, or job-flow error.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'SetVisibleToAllUsers' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Sets whether all AWS Identity and Access Management (IAM) users under your account can access the specifed job flows. This action works on running job flows. You can also set the visibility of a job flow when you launch it using the VisibleToAllUsers parameter of RunJobFlow. The SetVisibleToAllUsers action can be called only by an IAM user who created the job flow or the AWS account that owns the job flow.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetVisibleToAllUsers',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'JobFlowIds' => array(
                    'required' => true,
                    'description' => 'Identifiers of the job flows to receive the new visibility setting.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'JobFlowIds.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                        'maxLength' => 10280,
                    ),
                ),
                'VisibleToAllUsers' => array(
                    'required' => true,
                    'description' => 'Whether the specified job flows are visible to all IAM users of the AWS account associated with the job flow. If this value is set to True, all IAM users of that AWS account can view and, if they have the proper IAM policy permissions set, manage the job flows. If it is set to False, only the IAM user that created a job flow can view and manage it.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
        'TerminateJobFlows' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'TerminateJobFlows shuts a list of job flows down. When a job flow is shut down, any step not yet completed is canceled and the EC2 instances on which the job flow is running are stopped. Any log files not already saved are uploaded to Amazon S3 if a LogUri was specified when the job flow was created.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'TerminateJobFlows',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2009-03-31',
                ),
                'JobFlowIds' => array(
                    'required' => true,
                    'description' => 'A list of job flows to be shutdown.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'JobFlowIds.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                        'maxLength' => 10280,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that an error occurred while processing the request and that the request was not completed.',
                    'class' => 'InternalServerErrorException',
                ),
            ),
        ),
    ),
    'models' => array(
        'AddInstanceGroupsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobFlowId' => array(
                    'description' => 'The job flow ID in which the instance groups are added.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InstanceGroupIds' => array(
                    'description' => 'Instance group IDs of the newly created instance groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'XmlStringMaxLen256',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'DescribeJobFlowsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobFlows' => array(
                    'description' => 'A list of job flows matching the parameters supplied.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'JobFlowDetail',
                        'description' => 'A description of a job flow.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'JobFlowId' => array(
                                'description' => 'The job flow identifier.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The name of the job flow.',
                                'type' => 'string',
                            ),
                            'LogUri' => array(
                                'description' => 'The location in Amazon S3 where log files for the job are stored.',
                                'type' => 'string',
                            ),
                            'AmiVersion' => array(
                                'description' => 'The version of the AMI used to initialize Amazon EC2 instances in the job flow. For a list of AMI versions currently supported by Amazon ElasticMapReduce, go to AMI Versions Supported in Elastic MapReduce in the Amazon Elastic MapReduce Developer\'s Guide.',
                                'type' => 'string',
                            ),
                            'ExecutionStatusDetail' => array(
                                'description' => 'Describes the execution status of the job flow.',
                                'type' => 'object',
                                'properties' => array(
                                    'State' => array(
                                        'description' => 'The state of the job flow.',
                                        'type' => 'string',
                                    ),
                                    'CreationDateTime' => array(
                                        'description' => 'The creation date and time of the job flow.',
                                        'type' => 'string',
                                    ),
                                    'StartDateTime' => array(
                                        'description' => 'The start date and time of the job flow.',
                                        'type' => 'string',
                                    ),
                                    'ReadyDateTime' => array(
                                        'description' => 'The date and time when the job flow was ready to start running bootstrap actions.',
                                        'type' => 'string',
                                    ),
                                    'EndDateTime' => array(
                                        'description' => 'The completion date and time of the job flow.',
                                        'type' => 'string',
                                    ),
                                    'LastStateChangeReason' => array(
                                        'description' => 'Description of the job flow last changed state.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Instances' => array(
                                'description' => 'Describes the Amazon EC2 instances of the job flow.',
                                'type' => 'object',
                                'properties' => array(
                                    'MasterInstanceType' => array(
                                        'description' => 'The Amazon EC2 master node instance type.',
                                        'type' => 'string',
                                    ),
                                    'MasterPublicDnsName' => array(
                                        'description' => 'The DNS name of the master node.',
                                        'type' => 'string',
                                    ),
                                    'MasterInstanceId' => array(
                                        'description' => 'The Amazon EC2 instance identifier of the master node.',
                                        'type' => 'string',
                                    ),
                                    'SlaveInstanceType' => array(
                                        'description' => 'The Amazon EC2 slave node instance type.',
                                        'type' => 'string',
                                    ),
                                    'InstanceCount' => array(
                                        'description' => 'The number of Amazon EC2 instances in the cluster. If the value is 1, the same instance serves as both the master and slave node. If the value is greater than 1, one instance is the master node and all others are slave nodes.',
                                        'type' => 'numeric',
                                    ),
                                    'InstanceGroups' => array(
                                        'description' => 'Details about the job flow\'s instance groups.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'InstanceGroupDetail',
                                            'description' => 'Detailed information about an instance group.',
                                            'type' => 'object',
                                            'sentAs' => 'member',
                                            'properties' => array(
                                                'InstanceGroupId' => array(
                                                    'description' => 'Unique identifier for the instance group.',
                                                    'type' => 'string',
                                                ),
                                                'Name' => array(
                                                    'description' => 'Friendly name for the instance group.',
                                                    'type' => 'string',
                                                ),
                                                'Market' => array(
                                                    'description' => 'Market type of the Amazon EC2 instances used to create a cluster node.',
                                                    'type' => 'string',
                                                ),
                                                'InstanceRole' => array(
                                                    'description' => 'Instance group role in the cluster',
                                                    'type' => 'string',
                                                ),
                                                'BidPrice' => array(
                                                    'description' => 'Bid price for EC2 Instances when launching nodes as Spot Instances, expressed in USD.',
                                                    'type' => 'string',
                                                ),
                                                'InstanceType' => array(
                                                    'description' => 'Amazon EC2 Instance type.',
                                                    'type' => 'string',
                                                ),
                                                'InstanceRequestCount' => array(
                                                    'description' => 'Target number of instances to run in the instance group.',
                                                    'type' => 'numeric',
                                                ),
                                                'InstanceRunningCount' => array(
                                                    'description' => 'Actual count of running instances.',
                                                    'type' => 'numeric',
                                                ),
                                                'State' => array(
                                                    'description' => 'State of instance group. The following values are deprecated: STARTING, TERMINATED, and FAILED.',
                                                    'type' => 'string',
                                                ),
                                                'LastStateChangeReason' => array(
                                                    'description' => 'Details regarding the state of the instance group.',
                                                    'type' => 'string',
                                                ),
                                                'CreationDateTime' => array(
                                                    'description' => 'The date/time the instance group was created.',
                                                    'type' => 'string',
                                                ),
                                                'StartDateTime' => array(
                                                    'description' => 'The date/time the instance group was started.',
                                                    'type' => 'string',
                                                ),
                                                'ReadyDateTime' => array(
                                                    'description' => 'The date/time the instance group was available to the cluster.',
                                                    'type' => 'string',
                                                ),
                                                'EndDateTime' => array(
                                                    'description' => 'The date/time the instance group was terminated.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'NormalizedInstanceHours' => array(
                                        'description' => 'An approximation of the cost of the job flow, represented in m1.small/hours. This value is incremented once for every hour an m1.small runs. Larger instances are weighted more, so an Amazon EC2 instance that is roughly four times more expensive would result in the normalized instance hours being incremented by four. This result is only an approximation and does not reflect the actual billing rate.',
                                        'type' => 'numeric',
                                    ),
                                    'Ec2KeyName' => array(
                                        'description' => 'The name of an Amazon EC2 key pair that can be used to ssh to the master node of job flow.',
                                        'type' => 'string',
                                    ),
                                    'Ec2SubnetId' => array(
                                        'description' => 'For job flows launched within Amazon Virtual Private Cloud, this value specifies the identifier of the subnet where the job flow was launched.',
                                        'type' => 'string',
                                    ),
                                    'Placement' => array(
                                        'description' => 'Specifies the Amazon EC2 Availability Zone for the job flow.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'AvailabilityZone' => array(
                                                'description' => 'The Amazon EC2 Availability Zone for the job flow.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'KeepJobFlowAliveWhenNoSteps' => array(
                                        'description' => 'Specifies whether or not the job flow should terminate after completing all steps.',
                                        'type' => 'boolean',
                                    ),
                                    'TerminationProtected' => array(
                                        'description' => 'Specifies whether the Amazon EC2 instances in the cluster are protected from termination by API calls, user intervention, or in the event of a job flow error.',
                                        'type' => 'boolean',
                                    ),
                                    'HadoopVersion' => array(
                                        'description' => 'Specifies the Hadoop version for the job flow.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Steps' => array(
                                'description' => 'A list of steps run by the job flow.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'StepDetail',
                                    'description' => 'Combines the execution state and configuration of a step.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'StepConfig' => array(
                                            'description' => 'The step configuration.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'The name of the job flow step.',
                                                    'type' => 'string',
                                                ),
                                                'ActionOnFailure' => array(
                                                    'description' => 'Specifies the action to take if the job flow step fails.',
                                                    'type' => 'string',
                                                ),
                                                'HadoopJarStep' => array(
                                                    'description' => 'Specifies the JAR file used for the job flow step.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'Properties' => array(
                                                            'description' => 'A list of Java properties that are set when the step runs. You can use these properties to pass key value pairs to your main function.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'KeyValue',
                                                                'description' => 'A key value pair.',
                                                                'type' => 'object',
                                                                'sentAs' => 'member',
                                                                'properties' => array(
                                                                    'Key' => array(
                                                                        'description' => 'The unique identifier of a key value pair.',
                                                                        'type' => 'string',
                                                                    ),
                                                                    'Value' => array(
                                                                        'description' => 'The value part of the identified key.',
                                                                        'type' => 'string',
                                                                    ),
                                                                ),
                                                            ),
                                                        ),
                                                        'Jar' => array(
                                                            'description' => 'A path to a JAR file run during the step.',
                                                            'type' => 'string',
                                                        ),
                                                        'MainClass' => array(
                                                            'description' => 'The name of the main class in the specified Java file. If not specified, the JAR file should specify a Main-Class in its manifest file.',
                                                            'type' => 'string',
                                                        ),
                                                        'Args' => array(
                                                            'description' => 'A list of command line arguments passed to the JAR file\'s main function when executed.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'XmlString',
                                                                'type' => 'string',
                                                                'sentAs' => 'member',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                        'ExecutionStatusDetail' => array(
                                            'description' => 'The description of the step status.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'State' => array(
                                                    'description' => 'The state of the job flow step.',
                                                    'type' => 'string',
                                                ),
                                                'CreationDateTime' => array(
                                                    'description' => 'The creation date and time of the step.',
                                                    'type' => 'string',
                                                ),
                                                'StartDateTime' => array(
                                                    'description' => 'The start date and time of the step.',
                                                    'type' => 'string',
                                                ),
                                                'EndDateTime' => array(
                                                    'description' => 'The completion date and time of the step.',
                                                    'type' => 'string',
                                                ),
                                                'LastStateChangeReason' => array(
                                                    'description' => 'A description of the step\'s current state.',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'BootstrapActions' => array(
                                'description' => 'A list of the bootstrap actions run by the job flow.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BootstrapActionDetail',
                                    'description' => 'Reports the configuration of a bootstrap action in a job flow.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'BootstrapActionConfig' => array(
                                            'description' => 'A description of the bootstrap action.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'Name' => array(
                                                    'description' => 'The name of the bootstrap action.',
                                                    'type' => 'string',
                                                ),
                                                'ScriptBootstrapAction' => array(
                                                    'description' => 'The script run by the bootstrap action.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'Path' => array(
                                                            'description' => 'Location of the script to run during a bootstrap action. Can be either a location in Amazon S3 or on a local file system.',
                                                            'type' => 'string',
                                                        ),
                                                        'Args' => array(
                                                            'description' => 'A list of command line arguments to pass to the bootstrap action script.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'XmlString',
                                                                'type' => 'string',
                                                                'sentAs' => 'member',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'SupportedProducts' => array(
                                'description' => 'A list of strings set by third party software when the job flow is launched. If you are not using third party software to manage the job flow this value is empty.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'XmlStringMaxLen256',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'VisibleToAllUsers' => array(
                                'description' => 'Specifies whether the job flow is visible to all IAM users of the AWS account associated with the job flow. If this value is set to true, all IAM users of that AWS account can view and (if they have the proper policy permissions set) manage the job flow. If it is set to false, only the IAM user that created the job flow can view and manage it. This value can be changed using the SetVisibleToAllUsers action.',
                                'type' => 'boolean',
                            ),
                            'JobFlowRole' => array(
                                'description' => 'The IAM role that was specified when the job flow was launched. The EC2 instances of the job flow assume this role.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'RunJobFlowOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'JobFlowId' => array(
                    'description' => 'An unique identifier for the job flow.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeJobFlows' => array(
                'result_key' => 'JobFlows',
            ),
        ),
    ),
);
