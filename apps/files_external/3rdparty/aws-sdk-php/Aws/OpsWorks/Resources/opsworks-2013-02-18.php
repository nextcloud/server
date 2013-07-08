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
    'apiVersion' => '2013-02-18',
    'endpointPrefix' => 'opsworks',
    'serviceFullName' => 'AWS OpsWorks',
    'serviceType' => 'json',
    'jsonVersion' => '1.1',
    'targetPrefix' => 'OpsWorks_20130218.',
    'signatureVersion' => 'v4',
    'namespace' => 'OpsWorks',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'opsworks.us-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AttachElasticLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Attaches an Elastic Load Balancing instance to a specified layer.',
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
                    'default' => 'OpsWorks_20130218.AttachElasticLoadBalancer',
                ),
                'ElasticLoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The Elastic Load Balancing instance\'s name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The ID of the layer that the Elastic Load Balancing instance is to be attached to.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CloneStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CloneStackResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a clone of a specified stack. For more information, see Clone a Stack.',
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
                    'default' => 'OpsWorks_20130218.CloneStack',
                ),
                'SourceStackId' => array(
                    'required' => true,
                    'description' => 'The source stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Name' => array(
                    'description' => 'The cloned stack name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Region' => array(
                    'description' => 'The cloned stack AWS region, such as "us-east-1". For more information about AWS regions, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Attributes' => array(
                    'description' => 'A list of stack attributes and values as key/value pairs to be added to the cloned stack.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'StackAttributesKeys',
                        ),
                    ),
                ),
                'ServiceRoleArn' => array(
                    'required' => true,
                    'description' => 'The stack AWS Identity and Access Management (IAM) role, which allows OpsWorks to work with AWS resources on your behalf. You must set this parameter to the Amazon Resource Name (ARN) for an existing IAM role. If you create a stack by using the OpsWorks console, it creates the role for you. You can obtain an existing stack\'s IAM ARN programmatically by calling DescribePermissions. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultInstanceProfileArn' => array(
                    'description' => 'The ARN of an IAM profile that is the default profile for all of the stack\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultOs' => array(
                    'description' => 'The cloned stack default operating system, which must be either "Amazon Linux" or "Ubuntu 12.04 LTS".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'HostnameTheme' => array(
                    'description' => 'The stack\'s host name theme, with spaces are replaced by underscores. The theme is used to generate hostnames for the stack\'s instances. By default, HostnameTheme is set to Layer_Dependent, which creates hostnames by appending integers to the layer\'s shortname. The other themes are:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultAvailabilityZone' => array(
                    'description' => 'The cloned stack\'s Availability Zone. For more information, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomJson' => array(
                    'description' => 'A string that contains user-defined, custom JSON. It is used to override the corresponding default stack configuration JSON values. The string should be in the following format and must escape characters such as \'"\'.:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'UseCustomCookbooks' => array(
                    'description' => 'Whether to use custom cookbooks.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CustomCookbooksSource' => array(
                    'description' => 'Contains the information required to retrieve an app or cookbook from a repository. For more information, see Creating Apps or Custom Recipes and Cookbooks.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'The repository type.',
                            'type' => 'string',
                            'enum' => array(
                                'git',
                                'svn',
                                'archive',
                                's3',
                            ),
                        ),
                        'Url' => array(
                            'description' => 'The source URL.',
                            'type' => 'string',
                        ),
                        'Username' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'Password' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'SshKey' => array(
                            'description' => 'The repository\'s SSH key.',
                            'type' => 'string',
                        ),
                        'Revision' => array(
                            'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'DefaultSshKeyName' => array(
                    'description' => 'A default SSH key for the stack instances. You can override this value when you create or update an instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ClonePermissions' => array(
                    'description' => 'Whether to clone the source stack\'s permissions.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CloneAppIds' => array(
                    'description' => 'A list of source stack app IDs to be included in the cloned stack.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'DefaultRootDeviceType' => array(
                    'description' => 'The default root device type. This value is used by default for all instances in the cloned stack, but you can override it when you create an instance. For more information, see Storage for the Root Device.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ebs',
                        'instance-store',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CreateApp' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateAppResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates an app for a specified stack. For more information, see Creating Apps.',
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
                    'default' => 'OpsWorks_20130218.CreateApp',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Shortname' => array(
                    'description' => 'The app\'s short name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The app name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Description' => array(
                    'description' => 'A description of the app.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Type' => array(
                    'required' => true,
                    'description' => 'The app type. Each supported type is associated with a particular layer. For example, PHP applications are associated with a PHP layer. OpsWorks deploys an application to those instances that are members of the corresponding layer.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'rails',
                        'php',
                        'nodejs',
                        'static',
                        'other',
                    ),
                ),
                'AppSource' => array(
                    'description' => 'A Source object that specifies the app repository.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'The repository type.',
                            'type' => 'string',
                            'enum' => array(
                                'git',
                                'svn',
                                'archive',
                                's3',
                            ),
                        ),
                        'Url' => array(
                            'description' => 'The source URL.',
                            'type' => 'string',
                        ),
                        'Username' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'Password' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'SshKey' => array(
                            'description' => 'The repository\'s SSH key.',
                            'type' => 'string',
                        ),
                        'Revision' => array(
                            'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Domains' => array(
                    'description' => 'The app virtual host settings, with multiple domains separated by commas. For example: \'www.example.com, example.com\'',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'EnableSsl' => array(
                    'description' => 'Whether to enable SSL for the app.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'SslConfiguration' => array(
                    'description' => 'An SslConfiguration object with the SSL configuration.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Certificate' => array(
                            'required' => true,
                            'description' => 'The contents of the certificate\'s domain.crt file.',
                            'type' => 'string',
                        ),
                        'PrivateKey' => array(
                            'required' => true,
                            'description' => 'The private key; the contents of the certificate\'s domain.kex file.',
                            'type' => 'string',
                        ),
                        'Chain' => array(
                            'description' => 'Optional. Can be used to specify an intermediate certificate authority key or client authentication.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'AppAttributesKeys',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CreateDeployment' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateDeploymentResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deploys a stack or app.',
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
                    'default' => 'OpsWorks_20130218.CreateDeployment',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AppId' => array(
                    'description' => 'The app ID. This parameter is required for app deployments, but not for other deployment commands.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InstanceIds' => array(
                    'description' => 'The instance IDs for the deployment targets.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'Command' => array(
                    'required' => true,
                    'description' => 'A DeploymentCommand object that specifies the deployment command and any associated arguments.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Name' => array(
                            'required' => true,
                            'description' => 'Specifies the deployment operation. You can specify only one command.',
                            'type' => 'string',
                            'enum' => array(
                                'install_dependencies',
                                'update_dependencies',
                                'update_custom_cookbooks',
                                'execute_recipes',
                                'deploy',
                                'rollback',
                                'start',
                                'stop',
                                'restart',
                                'undeploy',
                            ),
                        ),
                        'Args' => array(
                            'description' => 'An array of command arguments. This parameter is currently used only to specify the list of recipes to be executed by the ExecuteRecipes command.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'array',
                                'data' => array(
                                    'shape_name' => 'String',
                                ),
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'description' => 'A user-defined comment.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomJson' => array(
                    'description' => 'A string that contains user-defined, custom JSON. It is used to override the corresponding default stack configuration JSON values. The string should be in the following format and must escape characters such as \'"\'.:',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CreateInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateInstanceResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates an instance in a specified stack. For more information, see Adding an Instance to a Layer.',
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
                    'default' => 'OpsWorks_20130218.CreateInstance',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerIds' => array(
                    'required' => true,
                    'description' => 'An array that contains the instance layer IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'InstanceType' => array(
                    'required' => true,
                    'description' => 'The instance type. OpsWorks supports all instance types except Cluster Compute, Cluster GPU, and High Memory Cluster. For more information, see Instance Families and Types. The parameter values that you use to specify the various types are in the API Name column of the Available Instance Types table.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AutoScalingType' => array(
                    'description' => 'The instance auto scaling type, which has three possible values:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'load',
                        'timer',
                    ),
                ),
                'Hostname' => array(
                    'description' => 'The instance host name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Os' => array(
                    'description' => 'The instance\'s operating system, which must be either "Amazon Linux" or "Ubuntu 12.04 LTS".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshKeyName' => array(
                    'description' => 'The instance SSH key name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AvailabilityZone' => array(
                    'description' => 'The instance Availability Zone. For more information, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Architecture' => array(
                    'description' => 'The instance architecture. Instance types do not necessarily support both architectures. For a list of the architectures that are supported by the different instance types, see Instance Families and Types.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'x86_64',
                        'i386',
                    ),
                ),
                'RootDeviceType' => array(
                    'description' => 'The instance root device type. For more information, see Storage for the Root Device.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ebs',
                        'instance-store',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CreateLayer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateLayerResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a layer. For more information, see How to Create a Layer.',
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
                    'default' => 'OpsWorks_20130218.CreateLayer',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The layer stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Type' => array(
                    'required' => true,
                    'description' => 'The layer type. A stack cannot have more than one layer of the same type. This parameter must be set to one of the following:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'lb',
                        'web',
                        'php-app',
                        'rails-app',
                        'nodejs-app',
                        'memcached',
                        'db-master',
                        'monitoring-master',
                        'custom',
                    ),
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The layer name, which is used by the console.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Shortname' => array(
                    'required' => true,
                    'description' => 'The layer short name, which is used internally by OpsWorks and by Chef recipes. The shortname is also used as the name for the directory where your app files are installed. It can have a maximum of 200 characters, which are limited to the alphanumeric characters, \'-\', \'_\', and \'.\'.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'LayerAttributesKeys',
                        ),
                    ),
                ),
                'CustomInstanceProfileArn' => array(
                    'description' => 'The ARN of an IAM profile that to be used for the layer\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomSecurityGroupIds' => array(
                    'description' => 'An array containing the layer custom security group IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'Packages' => array(
                    'description' => 'An array of Package objects that describe the layer packages.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'VolumeConfigurations' => array(
                    'description' => 'A VolumeConfigurations object that describes the layer Amazon EBS volumes.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeConfiguration',
                        'description' => 'Describes an Amazon EBS volume configuration.',
                        'type' => 'object',
                        'properties' => array(
                            'MountPoint' => array(
                                'required' => true,
                                'description' => 'The volume mount point. For example "/dev/sdh".',
                                'type' => 'string',
                            ),
                            'RaidLevel' => array(
                                'description' => 'The volume RAID level.',
                                'type' => 'numeric',
                            ),
                            'NumberOfDisks' => array(
                                'required' => true,
                                'description' => 'The number of disks in the volume.',
                                'type' => 'numeric',
                            ),
                            'Size' => array(
                                'required' => true,
                                'description' => 'The volume size.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'EnableAutoHealing' => array(
                    'description' => 'Whether to disable auto healing for the layer.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'AutoAssignElasticIps' => array(
                    'description' => 'Whether to automatically assign an Elastic IP address to the layer.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CustomRecipes' => array(
                    'description' => 'A LayerCustomRecipes object that specifies the layer custom recipes.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Setup' => array(
                            'description' => 'An array of custom recipe names to be run following a setup event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Configure' => array(
                            'description' => 'An array of custom recipe names to be run following a configure event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Deploy' => array(
                            'description' => 'An array of custom recipe names to be run following a deploy event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Undeploy' => array(
                            'description' => 'An array of custom recipe names to be run following a undeploy event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Shutdown' => array(
                            'description' => 'An array of custom recipe names to be run following a shutdown event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'CreateStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateStackResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new stack. For more information, see Create a New Stack.',
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
                    'default' => 'OpsWorks_20130218.CreateStack',
                ),
                'Name' => array(
                    'required' => true,
                    'description' => 'The stack name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Region' => array(
                    'required' => true,
                    'description' => 'The stack AWS region, such as "us-east-1". For more information about Amazon regions, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'StackAttributesKeys',
                        ),
                    ),
                ),
                'ServiceRoleArn' => array(
                    'required' => true,
                    'description' => 'The stack AWS Identity and Access Management (IAM) role, which allows OpsWorks to work with AWS resources on your behalf. You must set this parameter to the Amazon Resource Name (ARN) for an existing IAM role. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultInstanceProfileArn' => array(
                    'required' => true,
                    'description' => 'The ARN of an IAM profile that is the default profile for all of the stack\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultOs' => array(
                    'description' => 'The cloned stack default operating system, which must be either "Amazon Linux" or "Ubuntu 12.04 LTS".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'HostnameTheme' => array(
                    'description' => 'The stack\'s host name theme, with spaces are replaced by underscores. The theme is used to generate hostnames for the stack\'s instances. By default, HostnameTheme is set to Layer_Dependent, which creates hostnames by appending integers to the layer\'s shortname. The other themes are:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultAvailabilityZone' => array(
                    'description' => 'The stack default Availability Zone. For more information, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomJson' => array(
                    'description' => 'A string that contains user-defined, custom JSON. It is used to override the corresponding default stack configuration JSON values. The string should be in the following format and must escape characters such as \'"\'.:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'UseCustomCookbooks' => array(
                    'description' => 'Whether the stack uses custom cookbooks.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CustomCookbooksSource' => array(
                    'description' => 'Contains the information required to retrieve an app or cookbook from a repository. For more information, see Creating Apps or Custom Recipes and Cookbooks.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'The repository type.',
                            'type' => 'string',
                            'enum' => array(
                                'git',
                                'svn',
                                'archive',
                                's3',
                            ),
                        ),
                        'Url' => array(
                            'description' => 'The source URL.',
                            'type' => 'string',
                        ),
                        'Username' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'Password' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'SshKey' => array(
                            'description' => 'The repository\'s SSH key.',
                            'type' => 'string',
                        ),
                        'Revision' => array(
                            'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'DefaultSshKeyName' => array(
                    'description' => 'A default SSH key for the stack instances. You can override this value when you create or update an instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultRootDeviceType' => array(
                    'description' => 'The default root device type. This value is used by default for all instances in the cloned stack, but you can override it when you create an instance. For more information, see Storage for the Root Device.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ebs',
                        'instance-store',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
            ),
        ),
        'CreateUserProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'CreateUserProfileResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Creates a new user profile.',
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
                    'default' => 'OpsWorks_20130218.CreateUserProfile',
                ),
                'IamUserArn' => array(
                    'required' => true,
                    'description' => 'The user\'s IAM ARN.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshUsername' => array(
                    'description' => 'The user\'s SSH user name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshPublicKey' => array(
                    'description' => 'The user\'s public SSH key.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
            ),
        ),
        'DeleteApp' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a specified app.',
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
                    'default' => 'OpsWorks_20130218.DeleteApp',
                ),
                'AppId' => array(
                    'required' => true,
                    'description' => 'The app ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a specified instance. You must stop an instance before you can delete it. For more information, see Deleting Instances.',
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
                    'default' => 'OpsWorks_20130218.DeleteInstance',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DeleteElasticIp' => array(
                    'description' => 'Whether to delete the instance Elastic IP address.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'DeleteVolumes' => array(
                    'description' => 'Whether to delete the instance Amazon EBS volumes.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteLayer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a specified layer. You must first stop and then delete all associated instances. For more information, see How to Delete a Layer.',
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
                    'default' => 'OpsWorks_20130218.DeleteLayer',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a specified stack. You must first delete all instances, layers, and apps. For more information, see Shut Down a Stack.',
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
                    'default' => 'OpsWorks_20130218.DeleteStack',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DeleteUserProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Deletes a user profile.',
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
                    'default' => 'OpsWorks_20130218.DeleteUserProfile',
                ),
                'IamUserArn' => array(
                    'required' => true,
                    'description' => 'The user\'s IAM ARN.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeApps' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeAppsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests a description of a specified set of apps.',
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
                    'default' => 'OpsWorks_20130218.DescribeApps',
                ),
                'StackId' => array(
                    'description' => 'The app stack ID. If you use this parameter, DescribeApps returns a description of the apps in the specified stack.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AppIds' => array(
                    'description' => 'An array of app IDs for the apps to be described. If you use this parameter, DescribeApps returns a description of the specified apps. Otherwise, it returns a description of every app.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeCommands' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeCommandsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes the results of specified commands.',
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
                    'default' => 'OpsWorks_20130218.DescribeCommands',
                ),
                'DeploymentId' => array(
                    'description' => 'The deployment ID. If you include this parameter, DescribeCommands returns a description of the commands associated with the specified deployment.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InstanceId' => array(
                    'description' => 'The instance ID. If you include this parameter, DescribeCommands returns a description of the commands associated with the specified instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CommandIds' => array(
                    'description' => 'An array of command IDs. If you include this parameter, DescribeCommands returns a description of the specified commands. Otherwise, it returns a description of every command.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeDeployments' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeDeploymentsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests a description of a specified set of deployments.',
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
                    'default' => 'OpsWorks_20130218.DescribeDeployments',
                ),
                'StackId' => array(
                    'description' => 'The stack ID. If you include this parameter, DescribeDeployments returns a description of the commands associated with the specified stack.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AppId' => array(
                    'description' => 'The app ID. If you include this parameter, DescribeDeployments returns a description of the commands associated with the specified app.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DeploymentIds' => array(
                    'description' => 'An array of deployment IDs to be described. If you include this parameter, DescribeDeployments returns a description of the specified deployments. Otherwise, it returns a description of every deployment.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeElasticIps' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeElasticIpsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes an instance\'s Elastic IP addresses.',
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
                    'default' => 'OpsWorks_20130218.DescribeElasticIps',
                ),
                'InstanceId' => array(
                    'description' => 'The instance ID. If you include this parameter, DescribeElasticIps returns a description of the Elastic IP addresses associated with the specified instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Ips' => array(
                    'description' => 'An array of Elastic IP addresses to be described. If you include this parameter, DescribeElasticIps returns a description of the specified Elastic IP addresses. Otherwise, it returns a description of every Elastic IP address.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeElasticLoadBalancers' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeElasticLoadBalancersResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes a stack\'s Elastic Load Balancing instances.',
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
                    'default' => 'OpsWorks_20130218.DescribeElasticLoadBalancers',
                ),
                'StackId' => array(
                    'description' => 'A stack ID. The action describes the Elastic Load Balancing instances for the stack.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerIds' => array(
                    'description' => 'A list of layer IDs. The action describes the Elastic Load Balancing instances for the specified layers.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeInstancesResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests a description of a set of instances associated with a specified ID or IDs.',
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
                    'default' => 'OpsWorks_20130218.DescribeInstances',
                ),
                'StackId' => array(
                    'description' => 'A stack ID. If you use this parameter, DescribeInstances returns descriptions of the instances associated with the specified stack.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerId' => array(
                    'description' => 'A layer ID. If you use this parameter, DescribeInstances returns descriptions of the instances associated with the specified layer.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InstanceIds' => array(
                    'description' => 'An array of instance IDs to be described. If you use this parameter, DescribeInstances returns a description of the specified instances. Otherwise, it returns a description of every instance.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeLayers' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeLayersResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests a description of one or more layers in a specified stack.',
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
                    'default' => 'OpsWorks_20130218.DescribeLayers',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerIds' => array(
                    'description' => 'An array of layer IDs that specify the layers to be described. If you omit this parameter, DescribeLayers returns a description of every layer in the specified stack.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeLoadBasedAutoScaling' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeLoadBasedAutoScalingResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes load-based auto scaling configurations for specified layers.',
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
                    'default' => 'OpsWorks_20130218.DescribeLoadBasedAutoScaling',
                ),
                'LayerIds' => array(
                    'required' => true,
                    'description' => 'An array of layer IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribePermissions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribePermissionsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes the permissions for a specified stack.',
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
                    'default' => 'OpsWorks_20130218.DescribePermissions',
                ),
                'IamUserArn' => array(
                    'required' => true,
                    'description' => 'The user\'s IAM ARN. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeRaidArrays' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeRaidArraysResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describe an instance\'s RAID arrays.',
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
                    'default' => 'OpsWorks_20130218.DescribeRaidArrays',
                ),
                'InstanceId' => array(
                    'description' => 'The instance ID. If you use this parameter, DescribeRaidArrays returns descriptions of the RAID arrays associated with the specified instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'RaidArrayIds' => array(
                    'description' => 'An array of RAID array IDs. If you use this parameter, DescribeRaidArrays returns descriptions of the specified arrays. Otherwise, it returns a description of every array.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeServiceErrors' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeServiceErrorsResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes OpsWorks service errors.',
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
                    'default' => 'OpsWorks_20130218.DescribeServiceErrors',
                ),
                'StackId' => array(
                    'description' => 'The stack ID. If you use this parameter, DescribeServiceErrors returns descriptions of the errors associated with the specified stack.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'InstanceId' => array(
                    'description' => 'The instance ID. If you use this parameter, DescribeServiceErrors returns descriptions of the errors associated with the specified instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'ServiceErrorIds' => array(
                    'description' => 'An array of service error IDs. If you use this parameter, DescribeServiceErrors returns descriptions of the specified errors. Otherwise, it returns a description of every error.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeStacks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeStacksResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Requests a description of one or more stacks.',
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
                    'default' => 'OpsWorks_20130218.DescribeStacks',
                ),
                'StackIds' => array(
                    'description' => 'An array of stack IDs that specify the stacks to be described. If you omit this parameter, DescribeStacks returns a description of every stack.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeTimeBasedAutoScaling' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeTimeBasedAutoScalingResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes time-based auto scaling configurations for specified instances.',
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
                    'default' => 'OpsWorks_20130218.DescribeTimeBasedAutoScaling',
                ),
                'InstanceIds' => array(
                    'required' => true,
                    'description' => 'An array of instance IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeUserProfiles' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeUserProfilesResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describe specified users.',
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
                    'default' => 'OpsWorks_20130218.DescribeUserProfiles',
                ),
                'IamUserArns' => array(
                    'required' => true,
                    'description' => 'An array of IAM user ARNs that identify the users to be described.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeVolumes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'DescribeVolumesResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Describes an instance\'s Amazon EBS volumes.',
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
                    'default' => 'OpsWorks_20130218.DescribeVolumes',
                ),
                'InstanceId' => array(
                    'description' => 'The instance ID. If you use this parameter, DescribeVolumes returns descriptions of the volumes associated with the specified instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'RaidArrayId' => array(
                    'description' => 'The RAID array ID. If you use this parameter, DescribeVolumes returns descriptions of the volumes associated with the specified RAID array.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'VolumeIds' => array(
                    'description' => 'Am array of volume IDs. If you use this parameter, DescribeVolumes returns descriptions of the specified volumes. Otherwise, it returns a description of every volume.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DetachElasticLoadBalancer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Detaches a specified Elastic Load Balancing instance from it\'s layer.',
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
                    'default' => 'OpsWorks_20130218.DetachElasticLoadBalancer',
                ),
                'ElasticLoadBalancerName' => array(
                    'required' => true,
                    'description' => 'The Elastic Load Balancing instance\'s name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The ID of the layer that the Elastic Load Balancing instance is attached to.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'GetHostnameSuggestion' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'GetHostnameSuggestionResult',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Gets a generated hostname for the specified layer, based on the current hostname theme.',
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
                    'default' => 'OpsWorks_20130218.GetHostnameSuggestion',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
            ),
        ),
        'RebootInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Reboots a specified instance. For more information, see Starting, Stopping, and Rebooting Instances.',
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
                    'default' => 'OpsWorks_20130218.RebootInstance',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'SetLoadBasedAutoScaling' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Specify the load-based auto scaling configuration for a specified layer. For more information, see Managing Load with Time-based and Load-based Instances.',
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
                    'default' => 'OpsWorks_20130218.SetLoadBasedAutoScaling',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Enable' => array(
                    'description' => 'Enables load-based auto scaling for the layer.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'UpScaling' => array(
                    'description' => 'An AutoScalingThresholds object with the upscaling threshold configuration. If the load exceeds these thresholds for a specified amount of time, OpsWorks starts a specified number of instances.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'InstanceCount' => array(
                            'description' => 'The number of instances to add or remove when the load exceeds a threshold.',
                            'type' => 'numeric',
                        ),
                        'ThresholdsWaitTime' => array(
                            'description' => 'The amount of time, in minutes, that the load must exceed a threshold before more instances are added or removed.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 100,
                        ),
                        'IgnoreMetricsTime' => array(
                            'description' => 'The amount of time (in minutes) after a scaling event occurs that OpsWorks should ignore metrics and not raise any additional scaling events. For example, OpsWorks adds new instances following an upscaling event but the instances won\'t start reducing the load until they have been booted and configured. There is no point in raising additional scaling events during that operation, which typically takes several minutes. IgnoreMetricsTime allows you to direct OpsWorks to not raise any scaling events long enough to get the new instances online.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 100,
                        ),
                        'CpuThreshold' => array(
                            'description' => 'The CPU utilization threshold, as a percent of the available CPU.',
                            'type' => 'numeric',
                        ),
                        'MemoryThreshold' => array(
                            'description' => 'The memory utilization threshold, as a percent of the available memory.',
                            'type' => 'numeric',
                        ),
                        'LoadThreshold' => array(
                            'description' => 'The load threshold. For more information about how load is computed, see Load (computing).',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'DownScaling' => array(
                    'description' => 'An AutoScalingThresholds object with the downscaling threshold configuration. If the load falls below these thresholds for a specified amount of time, OpsWorks stops a specified number of instances.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'InstanceCount' => array(
                            'description' => 'The number of instances to add or remove when the load exceeds a threshold.',
                            'type' => 'numeric',
                        ),
                        'ThresholdsWaitTime' => array(
                            'description' => 'The amount of time, in minutes, that the load must exceed a threshold before more instances are added or removed.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 100,
                        ),
                        'IgnoreMetricsTime' => array(
                            'description' => 'The amount of time (in minutes) after a scaling event occurs that OpsWorks should ignore metrics and not raise any additional scaling events. For example, OpsWorks adds new instances following an upscaling event but the instances won\'t start reducing the load until they have been booted and configured. There is no point in raising additional scaling events during that operation, which typically takes several minutes. IgnoreMetricsTime allows you to direct OpsWorks to not raise any scaling events long enough to get the new instances online.',
                            'type' => 'numeric',
                            'minimum' => 1,
                            'maximum' => 100,
                        ),
                        'CpuThreshold' => array(
                            'description' => 'The CPU utilization threshold, as a percent of the available CPU.',
                            'type' => 'numeric',
                        ),
                        'MemoryThreshold' => array(
                            'description' => 'The memory utilization threshold, as a percent of the available memory.',
                            'type' => 'numeric',
                        ),
                        'LoadThreshold' => array(
                            'description' => 'The load threshold. For more information about how load is computed, see Load (computing).',
                            'type' => 'numeric',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'SetPermission' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Specifies a stack\'s permissions. For more information, see Security and Permissions.',
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
                    'default' => 'OpsWorks_20130218.SetPermission',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'IamUserArn' => array(
                    'required' => true,
                    'description' => 'The user\'s IAM ARN.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AllowSsh' => array(
                    'description' => 'The user is allowed to use SSH to communicate with the instance.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'AllowSudo' => array(
                    'description' => 'The user is allowed to use sudo to elevate privileges.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'SetTimeBasedAutoScaling' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Specify the time-based auto scaling configuration for a specified instance. For more information, see Managing Load with Time-based and Load-based Instances.',
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
                    'default' => 'OpsWorks_20130218.SetTimeBasedAutoScaling',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AutoScalingSchedule' => array(
                    'description' => 'An AutoScalingSchedule with the instance schedule.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Monday' => array(
                            'description' => 'The schedule for Monday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Tuesday' => array(
                            'description' => 'The schedule for Tuesday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Wednesday' => array(
                            'description' => 'The schedule for Wednesday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Thursday' => array(
                            'description' => 'The schedule for Thursday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Friday' => array(
                            'description' => 'The schedule for Friday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Saturday' => array(
                            'description' => 'The schedule for Saturday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                        'Sunday' => array(
                            'description' => 'The schedule for Sunday.',
                            'type' => 'object',
                            'additionalProperties' => array(
                                'type' => 'string',
                                'data' => array(
                                    'shape_name' => 'Hour',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'StartInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Starts a specified instance. For more information, see Starting, Stopping, and Rebooting Instances.',
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
                    'default' => 'OpsWorks_20130218.StartInstance',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'StartStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Starts stack\'s instances.',
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
                    'default' => 'OpsWorks_20130218.StartStack',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'StopInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Stops a specified instance. When you stop a standard instance, the data disappears and must be reinstalled when you restart the instance. You can stop an Amazon EBS-backed instance without losing data. For more information, see Starting, Stopping, and Rebooting Instances.',
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
                    'default' => 'OpsWorks_20130218.StopInstance',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'StopStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Stops a specified stack.',
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
                    'default' => 'OpsWorks_20130218.StopStack',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateApp' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates a specified app.',
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
                    'default' => 'OpsWorks_20130218.UpdateApp',
                ),
                'AppId' => array(
                    'required' => true,
                    'description' => 'The app ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Name' => array(
                    'description' => 'The app name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Description' => array(
                    'description' => 'A description of the app.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Type' => array(
                    'description' => 'The app type.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'rails',
                        'php',
                        'nodejs',
                        'static',
                        'other',
                    ),
                ),
                'AppSource' => array(
                    'description' => 'A Source object that specifies the app repository.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'The repository type.',
                            'type' => 'string',
                            'enum' => array(
                                'git',
                                'svn',
                                'archive',
                                's3',
                            ),
                        ),
                        'Url' => array(
                            'description' => 'The source URL.',
                            'type' => 'string',
                        ),
                        'Username' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'Password' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'SshKey' => array(
                            'description' => 'The repository\'s SSH key.',
                            'type' => 'string',
                        ),
                        'Revision' => array(
                            'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Domains' => array(
                    'description' => 'The app\'s virtual host settings, with multiple domains separated by commas. For example: \'www.example.com, example.com\'',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'EnableSsl' => array(
                    'description' => 'Whether SSL is enabled for the app.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'SslConfiguration' => array(
                    'description' => 'An SslConfiguration object with the SSL configuration.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Certificate' => array(
                            'required' => true,
                            'description' => 'The contents of the certificate\'s domain.crt file.',
                            'type' => 'string',
                        ),
                        'PrivateKey' => array(
                            'required' => true,
                            'description' => 'The private key; the contents of the certificate\'s domain.kex file.',
                            'type' => 'string',
                        ),
                        'Chain' => array(
                            'description' => 'Optional. Can be used to specify an intermediate certificate authority key or client authentication.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'AppAttributesKeys',
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateInstance' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates a specified instance.',
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
                    'default' => 'OpsWorks_20130218.UpdateInstance',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'LayerIds' => array(
                    'description' => 'The instance\'s layer IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'InstanceType' => array(
                    'description' => 'The instance type. OpsWorks supports all instance types except Cluster Compute, Cluster GPU, and High Memory Cluster. For more information, see Instance Families and Types. The parameter values that you use to specify the various types are in the API Name column of the Available Instance Types table.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'AutoScalingType' => array(
                    'description' => 'The instance\'s auto scaling type, which has three possible values:',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'load',
                        'timer',
                    ),
                ),
                'Hostname' => array(
                    'description' => 'The instance host name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Os' => array(
                    'description' => 'The instance operating system.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshKeyName' => array(
                    'description' => 'The instance SSH key name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Architecture' => array(
                    'description' => 'The instance architecture. Instance types do not necessarily support both architectures. For a list of the architectures that are supported by the different instance types, see Instance Families and Types.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'x86_64',
                        'i386',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateLayer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates a specified layer.',
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
                    'default' => 'OpsWorks_20130218.UpdateLayer',
                ),
                'LayerId' => array(
                    'required' => true,
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Name' => array(
                    'description' => 'The layer name, which is used by the console.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Shortname' => array(
                    'description' => 'The layer short name, which is used internally by OpsWorksand by Chef. The shortname is also used as the name for the directory where your app files are installed. It can have a maximum of 200 characters and must be in the following format: /\\A[a-z0-9\\-\\_\\.]+\\Z/.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'LayerAttributesKeys',
                        ),
                    ),
                ),
                'CustomInstanceProfileArn' => array(
                    'description' => 'The ARN of an IAM profile to be used for all of the layer\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomSecurityGroupIds' => array(
                    'description' => 'An array containing the layer\'s custom security group IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'Packages' => array(
                    'description' => 'An array of Package objects that describe the layer\'s packages.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'String',
                        'type' => 'string',
                    ),
                ),
                'VolumeConfigurations' => array(
                    'description' => 'A VolumeConfigurations object that describes the layer\'s Amazon EBS volumes.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'VolumeConfiguration',
                        'description' => 'Describes an Amazon EBS volume configuration.',
                        'type' => 'object',
                        'properties' => array(
                            'MountPoint' => array(
                                'required' => true,
                                'description' => 'The volume mount point. For example "/dev/sdh".',
                                'type' => 'string',
                            ),
                            'RaidLevel' => array(
                                'description' => 'The volume RAID level.',
                                'type' => 'numeric',
                            ),
                            'NumberOfDisks' => array(
                                'required' => true,
                                'description' => 'The number of disks in the volume.',
                                'type' => 'numeric',
                            ),
                            'Size' => array(
                                'required' => true,
                                'description' => 'The volume size.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'EnableAutoHealing' => array(
                    'description' => 'Whether to disable auto healing for the layer.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'AutoAssignElasticIps' => array(
                    'description' => 'Whether to automatically assign an Elastic IP address to the layer.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CustomRecipes' => array(
                    'description' => 'A LayerCustomRecipes object that specifies the layer\'s custom recipes.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Setup' => array(
                            'description' => 'An array of custom recipe names to be run following a setup event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Configure' => array(
                            'description' => 'An array of custom recipe names to be run following a configure event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Deploy' => array(
                            'description' => 'An array of custom recipe names to be run following a deploy event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Undeploy' => array(
                            'description' => 'An array of custom recipe names to be run following a undeploy event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                        'Shutdown' => array(
                            'description' => 'An array of custom recipe names to be run following a shutdown event.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'String',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateStack' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates a specified stack.',
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
                    'default' => 'OpsWorks_20130218.UpdateStack',
                ),
                'StackId' => array(
                    'required' => true,
                    'description' => 'The stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Name' => array(
                    'description' => 'The stack\'s new name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Attributes' => array(
                    'description' => 'One or more user-defined key/value pairs to be added to the stack attributes bag.',
                    'type' => 'object',
                    'location' => 'json',
                    'additionalProperties' => array(
                        'type' => 'string',
                        'data' => array(
                            'shape_name' => 'StackAttributesKeys',
                        ),
                    ),
                ),
                'ServiceRoleArn' => array(
                    'description' => 'The stack AWS Identity and Access Management (IAM) role, which allows OpsWorks to work with AWS resources on your behalf. You must set this parameter to the Amazon Resource Name (ARN) for an existing IAM role. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultInstanceProfileArn' => array(
                    'description' => 'The ARN of an IAM profile that is the default profile for all of the stack\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultOs' => array(
                    'description' => 'The cloned stack default operating system, which must be either "Amazon Linux" or "Ubuntu 12.04 LTS".',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'HostnameTheme' => array(
                    'description' => 'The stack\'s new host name theme, with spaces are replaced by underscores. The theme is used to generate hostnames for the stack\'s instances. By default, HostnameTheme is set to Layer_Dependent, which creates hostnames by appending integers to the layer\'s shortname. The other themes are:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultAvailabilityZone' => array(
                    'description' => 'The stack new default Availability Zone. For more information, see Regions and Endpoints.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'CustomJson' => array(
                    'description' => 'A string that contains user-defined, custom JSON. It is used to override the corresponding default stack configuration JSON values. The string should be in the following format and must escape characters such as \'"\'.:',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'UseCustomCookbooks' => array(
                    'description' => 'Whether the stack uses custom cookbooks.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'json',
                ),
                'CustomCookbooksSource' => array(
                    'description' => 'Contains the information required to retrieve an app or cookbook from a repository. For more information, see Creating Apps or Custom Recipes and Cookbooks.',
                    'type' => 'object',
                    'location' => 'json',
                    'properties' => array(
                        'Type' => array(
                            'description' => 'The repository type.',
                            'type' => 'string',
                            'enum' => array(
                                'git',
                                'svn',
                                'archive',
                                's3',
                            ),
                        ),
                        'Url' => array(
                            'description' => 'The source URL.',
                            'type' => 'string',
                        ),
                        'Username' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'Password' => array(
                            'description' => 'This parameter depends on the repository type.',
                            'type' => 'string',
                        ),
                        'SshKey' => array(
                            'description' => 'The repository\'s SSH key.',
                            'type' => 'string',
                        ),
                        'Revision' => array(
                            'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'DefaultSshKeyName' => array(
                    'description' => 'A default SSH key for the stack instances. You can override this value when you create or update an instance.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'DefaultRootDeviceType' => array(
                    'description' => 'The default root device type. This value is used by default for all instances in the cloned stack, but you can override it when you create an instance. For more information, see Storage for the Root Device.',
                    'type' => 'string',
                    'location' => 'json',
                    'enum' => array(
                        'ebs',
                        'instance-store',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'UpdateUserProfile' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\JsonCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'responseNotes' => 'Returns a json_decoded array of the response body',
            'summary' => 'Updates a specified user profile.',
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
                    'default' => 'OpsWorks_20130218.UpdateUserProfile',
                ),
                'IamUserArn' => array(
                    'required' => true,
                    'description' => 'The user IAM ARN.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshUsername' => array(
                    'description' => 'The user\'s new SSH user name.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'SshPublicKey' => array(
                    'description' => 'The user\'s new SSH public key.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that a request was invalid.',
                    'class' => 'ValidationException',
                ),
                array(
                    'reason' => 'Indicates that a resource was not found.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'CloneStackResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackId' => array(
                    'description' => 'The cloned stack ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateAppResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AppId' => array(
                    'description' => 'The app ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateDeploymentResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DeploymentId' => array(
                    'description' => 'The deployment ID, which can be used with other requests to identify the deployment.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateInstanceResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'InstanceId' => array(
                    'description' => 'The instance ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateLayerResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LayerId' => array(
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateStackResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'StackId' => array(
                    'description' => 'The stack ID, which is an opaque string that you use to identify the stack when performing actions such as DescribeStacks.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'CreateUserProfileResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IamUserArn' => array(
                    'description' => 'The user\'s IAM ARN.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
        'DescribeAppsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Apps' => array(
                    'description' => 'An array of App objects that describe the specified apps.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'App',
                        'description' => 'A description of the app.',
                        'type' => 'object',
                        'properties' => array(
                            'AppId' => array(
                                'description' => 'The app ID.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'The app stack ID.',
                                'type' => 'string',
                            ),
                            'Shortname' => array(
                                'description' => 'The app\'s short name.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The app name.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'A description of the app.',
                                'type' => 'string',
                            ),
                            'Type' => array(
                                'description' => 'The app type.',
                                'type' => 'string',
                            ),
                            'AppSource' => array(
                                'description' => 'A Source object that describes the app repository.',
                                'type' => 'object',
                                'properties' => array(
                                    'Type' => array(
                                        'description' => 'The repository type.',
                                        'type' => 'string',
                                    ),
                                    'Url' => array(
                                        'description' => 'The source URL.',
                                        'type' => 'string',
                                    ),
                                    'Username' => array(
                                        'description' => 'This parameter depends on the repository type.',
                                        'type' => 'string',
                                    ),
                                    'Password' => array(
                                        'description' => 'This parameter depends on the repository type.',
                                        'type' => 'string',
                                    ),
                                    'SshKey' => array(
                                        'description' => 'The repository\'s SSH key.',
                                        'type' => 'string',
                                    ),
                                    'Revision' => array(
                                        'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Domains' => array(
                                'description' => 'The app vhost settings, with multiple domains separated by commas. For example: \'www.example.com, example.com\'',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'EnableSsl' => array(
                                'description' => 'Whether to enable SSL for the app.',
                                'type' => 'boolean',
                            ),
                            'SslConfiguration' => array(
                                'description' => 'An SslConfiguration object with the SSL configuration.',
                                'type' => 'object',
                                'properties' => array(
                                    'Certificate' => array(
                                        'description' => 'The contents of the certificate\'s domain.crt file.',
                                        'type' => 'string',
                                    ),
                                    'PrivateKey' => array(
                                        'description' => 'The private key; the contents of the certificate\'s domain.kex file.',
                                        'type' => 'string',
                                    ),
                                    'Chain' => array(
                                        'description' => 'Optional. Can be used to specify an intermediate certificate authority key or client authentication.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Attributes' => array(
                                'description' => 'The contents of the stack attributes bag.',
                                'type' => 'object',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'CreatedAt' => array(
                                'description' => 'When the app was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeCommandsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Commands' => array(
                    'description' => 'An array of Command objects that describe each of the specified commands.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Command',
                        'description' => 'Describes a command.',
                        'type' => 'object',
                        'properties' => array(
                            'CommandId' => array(
                                'description' => 'The command ID.',
                                'type' => 'string',
                            ),
                            'InstanceId' => array(
                                'description' => 'The ID of the instance where the command was executed.',
                                'type' => 'string',
                            ),
                            'DeploymentId' => array(
                                'description' => 'The command deployment ID.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'Date and time when the command was run.',
                                'type' => 'string',
                            ),
                            'AcknowledgedAt' => array(
                                'description' => 'Date and time when the command was acknowledged.',
                                'type' => 'string',
                            ),
                            'CompletedAt' => array(
                                'description' => 'Date when the command completed.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The command status:',
                                'type' => 'string',
                            ),
                            'ExitCode' => array(
                                'description' => 'The command exit code.',
                                'type' => 'numeric',
                            ),
                            'LogUrl' => array(
                                'description' => 'The URL of the command log.',
                                'type' => 'string',
                            ),
                            'Type' => array(
                                'description' => 'The command type:',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeDeploymentsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Deployments' => array(
                    'description' => 'An array of Deployment objects that describe the deployments.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Deployment',
                        'description' => 'Describes a deployment of a stack or app.',
                        'type' => 'object',
                        'properties' => array(
                            'DeploymentId' => array(
                                'description' => 'The deployment ID.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'The stack ID.',
                                'type' => 'string',
                            ),
                            'AppId' => array(
                                'description' => 'The app ID.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'Date when the deployment was created.',
                                'type' => 'string',
                            ),
                            'CompletedAt' => array(
                                'description' => 'Date when the deployment completed.',
                                'type' => 'string',
                            ),
                            'Duration' => array(
                                'description' => 'The deployment duration.',
                                'type' => 'numeric',
                            ),
                            'IamUserArn' => array(
                                'description' => 'The user\'s IAM ARN.',
                                'type' => 'string',
                            ),
                            'Comment' => array(
                                'description' => 'A user-defined comment.',
                                'type' => 'string',
                            ),
                            'Command' => array(
                                'description' => 'Used to specify a deployment operation.',
                                'type' => 'object',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'Specifies the deployment operation. You can specify only one command.',
                                        'type' => 'string',
                                    ),
                                    'Args' => array(
                                        'description' => 'An array of command arguments. This parameter is currently used only to specify the list of recipes to be executed by the ExecuteRecipes command.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'String',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'The deployment status:',
                                'type' => 'string',
                            ),
                            'CustomJson' => array(
                                'description' => 'A string that contains user-defined custom JSON. It is used to override the corresponding default stack configuration JSON values for stack. The string should be in the following format and must escape characters such as \'"\'.:',
                                'type' => 'string',
                            ),
                            'InstanceIds' => array(
                                'description' => 'The IDs of the target instances.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeElasticIpsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ElasticIps' => array(
                    'description' => 'An ElasticIps object that describes the specified Elastic IP addresses.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ElasticIp',
                        'description' => 'Describes an Elastic IP address.',
                        'type' => 'object',
                        'properties' => array(
                            'Ip' => array(
                                'description' => 'The Elastic IP address',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The Elastic IP address name.',
                                'type' => 'string',
                            ),
                            'Region' => array(
                                'description' => 'The AWS region. For more information, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeElasticLoadBalancersResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ElasticLoadBalancers' => array(
                    'description' => 'A list of ElasticLoadBalancer objects that describe the specified Elastic Load Balancing instances.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ElasticLoadBalancer',
                        'description' => 'Describes an Elastic Load Balancing instance.',
                        'type' => 'object',
                        'properties' => array(
                            'ElasticLoadBalancerName' => array(
                                'description' => 'The Elastic Load Balancing instance\'s name.',
                                'type' => 'string',
                            ),
                            'Region' => array(
                                'description' => 'The instance\'s AWS region.',
                                'type' => 'string',
                            ),
                            'DnsName' => array(
                                'description' => 'The instance\'s public DNS name.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'The ID of the stack that the instance is associated with.',
                                'type' => 'string',
                            ),
                            'LayerId' => array(
                                'description' => 'The ID of the layer that the instance is attached to.',
                                'type' => 'string',
                            ),
                            'AvailabilityZones' => array(
                                'description' => 'The instance\'s Availability Zones.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'Ec2InstanceIds' => array(
                                'description' => 'A list of the EC2 instances that the Elastic Load Balancing instance is managing traffic for.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeInstancesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Instances' => array(
                    'description' => 'An array of Instance objects that describe the instances.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Instance',
                        'description' => 'Describes an instance.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The instance ID.',
                                'type' => 'string',
                            ),
                            'Ec2InstanceId' => array(
                                'description' => 'The ID of the associated Amazon EC2 instance.',
                                'type' => 'string',
                            ),
                            'Hostname' => array(
                                'description' => 'The instance host name.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'The stack ID.',
                                'type' => 'string',
                            ),
                            'LayerIds' => array(
                                'description' => 'An array containing the instance layer IDs.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'SecurityGroupIds' => array(
                                'description' => 'An array containing the instance security group IDs.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'InstanceType' => array(
                                'description' => 'The instance type. OpsWorks supports all instance types except Cluster Compute, Cluster GPU, and High Memory Cluster. For more information, see Instance Families and Types. The parameter values that specify the various types are in the API Name column of the Available Instance Types table.',
                                'type' => 'string',
                            ),
                            'InstanceProfileArn' => array(
                                'description' => 'The ARN of the instance\'s IAM profile. For more information about IAM ARNs, see Using Identifiers.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The instance status:',
                                'type' => 'string',
                            ),
                            'Os' => array(
                                'description' => 'The instance operating system.',
                                'type' => 'string',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The instance Availability Zone. For more information, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                            'PublicDns' => array(
                                'description' => 'The instance public DNS name.',
                                'type' => 'string',
                            ),
                            'PrivateDns' => array(
                                'description' => 'The instance private DNS name.',
                                'type' => 'string',
                            ),
                            'PublicIp' => array(
                                'description' => 'The instance public IP address.',
                                'type' => 'string',
                            ),
                            'PrivateIp' => array(
                                'description' => 'The instance private IP address.',
                                'type' => 'string',
                            ),
                            'ElasticIp' => array(
                                'description' => 'The instance Elastic IP address .',
                                'type' => 'string',
                            ),
                            'AutoScalingType' => array(
                                'description' => 'The instance\'s auto scaling type, which has three possible values:',
                                'type' => 'string',
                            ),
                            'SshKeyName' => array(
                                'description' => 'The instance SSH key name.',
                                'type' => 'string',
                            ),
                            'SshHostRsaKeyFingerprint' => array(
                                'description' => 'The SSH key\'s RSA fingerprint.',
                                'type' => 'string',
                            ),
                            'SshHostDsaKeyFingerprint' => array(
                                'description' => 'The SSH key\'s DSA fingerprint.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'The time that the instance was created.',
                                'type' => 'string',
                            ),
                            'LastServiceErrorId' => array(
                                'description' => 'The ID of the last service error. For more information, call DescribeServiceErrors.',
                                'type' => 'string',
                            ),
                            'Architecture' => array(
                                'description' => 'The instance architecture, "i386" or "x86_64".',
                                'type' => 'string',
                            ),
                            'RootDeviceType' => array(
                                'description' => 'The instance root device type. For more information, see Storage for the Root Device.',
                                'type' => 'string',
                            ),
                            'RootDeviceVolumeId' => array(
                                'description' => 'The root device volume ID.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLayersResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Layers' => array(
                    'description' => 'An array of Layer objects that describe the layers.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Layer',
                        'description' => 'Describes a layer.',
                        'type' => 'object',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'The layer stack ID.',
                                'type' => 'string',
                            ),
                            'LayerId' => array(
                                'description' => 'The layer ID.',
                                'type' => 'string',
                            ),
                            'Type' => array(
                                'description' => 'The layer type, which must be one of the following:',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The layer name.',
                                'type' => 'string',
                            ),
                            'Shortname' => array(
                                'description' => 'The layer short name.',
                                'type' => 'string',
                            ),
                            'Attributes' => array(
                                'description' => 'The layer attributes.',
                                'type' => 'object',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'CustomInstanceProfileArn' => array(
                                'description' => 'The ARN of the default IAM profile to be used for the layer\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                                'type' => 'string',
                            ),
                            'CustomSecurityGroupIds' => array(
                                'description' => 'An array containing the layer\'s custom security group IDs.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'DefaultSecurityGroupNames' => array(
                                'description' => 'An array containing the layer\'s security group names.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'Packages' => array(
                                'description' => 'An array of Package objects that describe the layer\'s packages.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'String',
                                    'type' => 'string',
                                ),
                            ),
                            'VolumeConfigurations' => array(
                                'description' => 'A VolumeConfigurations object that describes the layer\'s Amazon EBS volumes.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'VolumeConfiguration',
                                    'description' => 'Describes an Amazon EBS volume configuration.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'MountPoint' => array(
                                            'description' => 'The volume mount point. For example "/dev/sdh".',
                                            'type' => 'string',
                                        ),
                                        'RaidLevel' => array(
                                            'description' => 'The volume RAID level.',
                                            'type' => 'numeric',
                                        ),
                                        'NumberOfDisks' => array(
                                            'description' => 'The number of disks in the volume.',
                                            'type' => 'numeric',
                                        ),
                                        'Size' => array(
                                            'description' => 'The volume size.',
                                            'type' => 'numeric',
                                        ),
                                    ),
                                ),
                            ),
                            'EnableAutoHealing' => array(
                                'description' => 'Whether auto healing is disabled for the layer.',
                                'type' => 'boolean',
                            ),
                            'AutoAssignElasticIps' => array(
                                'description' => 'Whether the layer has an automatically assigned Elastic IP address.',
                                'type' => 'boolean',
                            ),
                            'DefaultRecipes' => array(
                                'description' => 'OpsWorks supports five life',
                                'type' => 'object',
                                'properties' => array(
                                    'Setup' => array(
                                        'description' => 'An array of custom recipe names to be run following a setup event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Configure' => array(
                                        'description' => 'An array of custom recipe names to be run following a configure event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Deploy' => array(
                                        'description' => 'An array of custom recipe names to be run following a deploy event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Undeploy' => array(
                                        'description' => 'An array of custom recipe names to be run following a undeploy event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Shutdown' => array(
                                        'description' => 'An array of custom recipe names to be run following a shutdown event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CustomRecipes' => array(
                                'description' => 'A LayerCustomRecipes object that specifies the layer\'s custom recipes.',
                                'type' => 'object',
                                'properties' => array(
                                    'Setup' => array(
                                        'description' => 'An array of custom recipe names to be run following a setup event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Configure' => array(
                                        'description' => 'An array of custom recipe names to be run following a configure event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Deploy' => array(
                                        'description' => 'An array of custom recipe names to be run following a deploy event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Undeploy' => array(
                                        'description' => 'An array of custom recipe names to be run following a undeploy event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Shutdown' => array(
                                        'description' => 'An array of custom recipe names to be run following a shutdown event.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'String',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CreatedAt' => array(
                                'description' => 'Date when the layer was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeLoadBasedAutoScalingResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LoadBasedAutoScalingConfigurations' => array(
                    'description' => 'An array of LoadBasedAutoScalingConfiguration objects that describe each layer\'s configuration.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'LoadBasedAutoScalingConfiguration',
                        'description' => 'Describes a layer\'s load-based auto scaling configuration.',
                        'type' => 'object',
                        'properties' => array(
                            'LayerId' => array(
                                'description' => 'The layer ID.',
                                'type' => 'string',
                            ),
                            'Enable' => array(
                                'description' => 'Whether load-based auto scaling is enabled for the layer.',
                                'type' => 'boolean',
                            ),
                            'UpScaling' => array(
                                'description' => 'A LoadBasedAutoscalingInstruction object that describes the upscaling configuration, which defines how and when OpsWorks increases the number of instances.',
                                'type' => 'object',
                                'properties' => array(
                                    'InstanceCount' => array(
                                        'description' => 'The number of instances to add or remove when the load exceeds a threshold.',
                                        'type' => 'numeric',
                                    ),
                                    'ThresholdsWaitTime' => array(
                                        'description' => 'The amount of time, in minutes, that the load must exceed a threshold before more instances are added or removed.',
                                        'type' => 'numeric',
                                    ),
                                    'IgnoreMetricsTime' => array(
                                        'description' => 'The amount of time (in minutes) after a scaling event occurs that OpsWorks should ignore metrics and not raise any additional scaling events. For example, OpsWorks adds new instances following an upscaling event but the instances won\'t start reducing the load until they have been booted and configured. There is no point in raising additional scaling events during that operation, which typically takes several minutes. IgnoreMetricsTime allows you to direct OpsWorks to not raise any scaling events long enough to get the new instances online.',
                                        'type' => 'numeric',
                                    ),
                                    'CpuThreshold' => array(
                                        'description' => 'The CPU utilization threshold, as a percent of the available CPU.',
                                        'type' => 'numeric',
                                    ),
                                    'MemoryThreshold' => array(
                                        'description' => 'The memory utilization threshold, as a percent of the available memory.',
                                        'type' => 'numeric',
                                    ),
                                    'LoadThreshold' => array(
                                        'description' => 'The load threshold. For more information about how load is computed, see Load (computing).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'DownScaling' => array(
                                'description' => 'A LoadBasedAutoscalingInstruction object that describes the downscaling configuration, which defines how and when OpsWorks reduces the number of instances.',
                                'type' => 'object',
                                'properties' => array(
                                    'InstanceCount' => array(
                                        'description' => 'The number of instances to add or remove when the load exceeds a threshold.',
                                        'type' => 'numeric',
                                    ),
                                    'ThresholdsWaitTime' => array(
                                        'description' => 'The amount of time, in minutes, that the load must exceed a threshold before more instances are added or removed.',
                                        'type' => 'numeric',
                                    ),
                                    'IgnoreMetricsTime' => array(
                                        'description' => 'The amount of time (in minutes) after a scaling event occurs that OpsWorks should ignore metrics and not raise any additional scaling events. For example, OpsWorks adds new instances following an upscaling event but the instances won\'t start reducing the load until they have been booted and configured. There is no point in raising additional scaling events during that operation, which typically takes several minutes. IgnoreMetricsTime allows you to direct OpsWorks to not raise any scaling events long enough to get the new instances online.',
                                        'type' => 'numeric',
                                    ),
                                    'CpuThreshold' => array(
                                        'description' => 'The CPU utilization threshold, as a percent of the available CPU.',
                                        'type' => 'numeric',
                                    ),
                                    'MemoryThreshold' => array(
                                        'description' => 'The memory utilization threshold, as a percent of the available memory.',
                                        'type' => 'numeric',
                                    ),
                                    'LoadThreshold' => array(
                                        'description' => 'The load threshold. For more information about how load is computed, see Load (computing).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribePermissionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Permissions' => array(
                    'description' => 'An array of Permission objects that describe the stack permissions.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Permission',
                        'description' => 'Describes stack or user permissions.',
                        'type' => 'object',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'A stack ID.',
                                'type' => 'string',
                            ),
                            'IamUserArn' => array(
                                'description' => 'The Amazon Resource Name (ARN) for an AWS Identity and Access Management (IAM) role. For more information about IAM ARNs, see Using Identifiers.',
                                'type' => 'string',
                            ),
                            'AllowSsh' => array(
                                'description' => 'Whether the user can use SSH.',
                                'type' => 'boolean',
                            ),
                            'AllowSudo' => array(
                                'description' => 'Whether the user can use sudo.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeRaidArraysResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RaidArrays' => array(
                    'description' => 'A RaidArrays object that describes the specified RAID arrays.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'RaidArray',
                        'description' => 'Describes an instance\'s RAID array.',
                        'type' => 'object',
                        'properties' => array(
                            'RaidArrayId' => array(
                                'description' => 'The array ID.',
                                'type' => 'string',
                            ),
                            'InstanceId' => array(
                                'description' => 'The instance ID.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The array name.',
                                'type' => 'string',
                            ),
                            'RaidLevel' => array(
                                'description' => 'The RAID level.',
                                'type' => 'numeric',
                            ),
                            'NumberOfDisks' => array(
                                'description' => 'The number of disks in the array.',
                                'type' => 'numeric',
                            ),
                            'Size' => array(
                                'description' => 'The array\'s size.',
                                'type' => 'numeric',
                            ),
                            'Device' => array(
                                'description' => 'The array\'s Linux device. For example /dev/mdadm0.',
                                'type' => 'string',
                            ),
                            'MountPoint' => array(
                                'description' => 'The array\'s mount point.',
                                'type' => 'string',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The array\'s Availability Zone. For more information, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'When the RAID array was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeServiceErrorsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ServiceErrors' => array(
                    'description' => 'An array of ServiceError objects that describe the specified service errors.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'ServiceError',
                        'description' => 'Describes an OpsWorks service error.',
                        'type' => 'object',
                        'properties' => array(
                            'ServiceErrorId' => array(
                                'description' => 'The error ID.',
                                'type' => 'string',
                            ),
                            'StackId' => array(
                                'description' => 'The stack ID.',
                                'type' => 'string',
                            ),
                            'InstanceId' => array(
                                'description' => 'The instance ID.',
                                'type' => 'string',
                            ),
                            'Type' => array(
                                'description' => 'The error type.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'A message that describes the error.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'When the error occurred.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeStacksResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Stacks' => array(
                    'description' => 'An array of Stack objects that describe the stacks.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Stack',
                        'description' => 'Describes a stack.',
                        'type' => 'object',
                        'properties' => array(
                            'StackId' => array(
                                'description' => 'The stack ID.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The stack name.',
                                'type' => 'string',
                            ),
                            'Region' => array(
                                'description' => 'The stack AWS region, such as "us-east-1". For more information about AWS regions, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                            'Attributes' => array(
                                'description' => 'The contents of the stack\'s attributes bag.',
                                'type' => 'object',
                                'additionalProperties' => array(
                                    'type' => 'string',
                                ),
                            ),
                            'ServiceRoleArn' => array(
                                'description' => 'The stack AWS Identity and Access Management (IAM) role.',
                                'type' => 'string',
                            ),
                            'DefaultInstanceProfileArn' => array(
                                'description' => 'The ARN of an IAM profile that is the default profile for all of the stack\'s EC2 instances. For more information about IAM ARNs, see Using Identifiers.',
                                'type' => 'string',
                            ),
                            'DefaultOs' => array(
                                'description' => 'The cloned stack default operating system, which must be either "Amazon Linux" or "Ubuntu 12.04 LTS".',
                                'type' => 'string',
                            ),
                            'HostnameTheme' => array(
                                'description' => 'The stack host name theme, with spaces replaced by underscores.',
                                'type' => 'string',
                            ),
                            'DefaultAvailabilityZone' => array(
                                'description' => 'The stack\'s default Availability Zone. For more information, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                            'CustomJson' => array(
                                'description' => 'A string that contains user-defined, custom JSON. It is used to override the corresponding default stack configuration JSON values. The string should be in the following format and must escape characters such as \'"\'.:',
                                'type' => 'string',
                            ),
                            'UseCustomCookbooks' => array(
                                'description' => 'Whether the stack uses custom cookbooks.',
                                'type' => 'boolean',
                            ),
                            'CustomCookbooksSource' => array(
                                'description' => 'Contains the information required to retrieve an app or cookbook from a repository. For more information, see Creating Apps or Custom Recipes and Cookbooks.',
                                'type' => 'object',
                                'properties' => array(
                                    'Type' => array(
                                        'description' => 'The repository type.',
                                        'type' => 'string',
                                    ),
                                    'Url' => array(
                                        'description' => 'The source URL.',
                                        'type' => 'string',
                                    ),
                                    'Username' => array(
                                        'description' => 'This parameter depends on the repository type.',
                                        'type' => 'string',
                                    ),
                                    'Password' => array(
                                        'description' => 'This parameter depends on the repository type.',
                                        'type' => 'string',
                                    ),
                                    'SshKey' => array(
                                        'description' => 'The repository\'s SSH key.',
                                        'type' => 'string',
                                    ),
                                    'Revision' => array(
                                        'description' => 'The application\'s version. OpsWorks enables you to easily deploy new versions of an application. One of the simplest approaches is to have branches or revisions in your repository that represent different versions that can potentially be deployed.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'DefaultSshKeyName' => array(
                                'description' => 'A default SSH key for the stack\'s instances. You can override this value when you create or update an instance.',
                                'type' => 'string',
                            ),
                            'CreatedAt' => array(
                                'description' => 'Date when the stack was created.',
                                'type' => 'string',
                            ),
                            'DefaultRootDeviceType' => array(
                                'description' => 'The default root device type. This value is used by default for all instances in the cloned stack, but you can override it when you create an instance. For more information, see Storage for the Root Device.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeTimeBasedAutoScalingResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TimeBasedAutoScalingConfigurations' => array(
                    'description' => 'An array of TimeBasedAutoScalingConfiguration objects that describe the configuration for the specified instances.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'TimeBasedAutoScalingConfiguration',
                        'description' => 'Describes an instance\'s time-based auto scaling configuration.',
                        'type' => 'object',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The instance ID.',
                                'type' => 'string',
                            ),
                            'AutoScalingSchedule' => array(
                                'description' => 'A WeeklyAutoScalingSchedule object with the instance schedule.',
                                'type' => 'object',
                                'properties' => array(
                                    'Monday' => array(
                                        'description' => 'The schedule for Monday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Tuesday' => array(
                                        'description' => 'The schedule for Tuesday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Wednesday' => array(
                                        'description' => 'The schedule for Wednesday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Thursday' => array(
                                        'description' => 'The schedule for Thursday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Friday' => array(
                                        'description' => 'The schedule for Friday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Saturday' => array(
                                        'description' => 'The schedule for Saturday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
                                            'type' => 'string',
                                        ),
                                    ),
                                    'Sunday' => array(
                                        'description' => 'The schedule for Sunday.',
                                        'type' => 'object',
                                        'additionalProperties' => array(
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
        'DescribeUserProfilesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'UserProfiles' => array(
                    'description' => 'A Users object that describes the specified users.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'UserProfile',
                        'description' => 'Describes a user\'s SSH information.',
                        'type' => 'object',
                        'properties' => array(
                            'IamUserArn' => array(
                                'description' => 'The user IAM ARN.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The user name.',
                                'type' => 'string',
                            ),
                            'SshUsername' => array(
                                'description' => 'The user\'s SSH user name.',
                                'type' => 'string',
                            ),
                            'SshPublicKey' => array(
                                'description' => 'The user\'s SSH public key.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeVolumesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Volumes' => array(
                    'description' => 'An array of volume IDs.',
                    'type' => 'array',
                    'location' => 'json',
                    'items' => array(
                        'name' => 'Volume',
                        'description' => 'Describes an instance\'s Amazon EBS volume.',
                        'type' => 'object',
                        'properties' => array(
                            'VolumeId' => array(
                                'description' => 'The volume ID.',
                                'type' => 'string',
                            ),
                            'Ec2VolumeId' => array(
                                'description' => 'The Amazon EC2 volume ID.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The volume name.',
                                'type' => 'string',
                            ),
                            'RaidArrayId' => array(
                                'description' => 'The RAID array ID.',
                                'type' => 'string',
                            ),
                            'InstanceId' => array(
                                'description' => 'The instance ID.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The value returned by DescribeVolumes.',
                                'type' => 'string',
                            ),
                            'Size' => array(
                                'description' => 'The volume size.',
                                'type' => 'numeric',
                            ),
                            'Device' => array(
                                'description' => 'The device name.',
                                'type' => 'string',
                            ),
                            'MountPoint' => array(
                                'description' => 'The volume mount point. For example "/dev/sdh".',
                                'type' => 'string',
                            ),
                            'Region' => array(
                                'description' => 'The AWS region. For more information about AWS regions, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The volume Availability Zone. For more information, see Regions and Endpoints.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetHostnameSuggestionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LayerId' => array(
                    'description' => 'The layer ID.',
                    'type' => 'string',
                    'location' => 'json',
                ),
                'Hostname' => array(
                    'description' => 'The generated hostname.',
                    'type' => 'string',
                    'location' => 'json',
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeApps' => array(
                'result_key' => 'Apps',
            ),
            'DescribeCommands' => array(
                'result_key' => 'Commands',
            ),
            'DescribeDeployments' => array(
                'result_key' => 'Deployments',
            ),
            'DescribeElasticIps' => array(
                'result_key' => 'ElasticIps',
            ),
            'DescribeElasticLoadBalancers' => array(
                'result_key' => 'ElasticLoadBalancers',
            ),
            'DescribeInstances' => array(
                'result_key' => 'Instances',
            ),
            'DescribeLayers' => array(
                'result_key' => 'Layers',
            ),
            'DescribeLoadBasedAutoScaling' => array(
                'result_key' => 'LoadBasedAutoScalingConfigurations',
            ),
            'DescribeRaidArrays' => array(
                'result_key' => 'RaidArrays',
            ),
            'DescribeServiceErrors' => array(
                'result_key' => 'ServiceErrors',
            ),
            'DescribeStacks' => array(
                'result_key' => 'Stacks',
            ),
            'DescribeTimeBasedAutoScaling' => array(
                'result_key' => 'TimeBasedAutoScalingConfigurations',
            ),
            'DescribeUserProfiles' => array(
                'result_key' => 'UserProfiles',
            ),
            'DescribeVolumes' => array(
                'result_key' => 'Volumes',
            ),
        ),
    ),
);
