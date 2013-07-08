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
    'apiVersion' => '2011-01-01',
    'endpointPrefix' => 'autoscaling',
    'serviceFullName' => 'Auto Scaling',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'AutoScaling',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'autoscaling.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CreateAutoScalingGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new Auto Scaling group with the specified name and other attributes. When the creation request is completed, the Auto Scaling group is ready to be used in other calls.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateAutoScalingGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'LaunchConfigurationName' => array(
                    'required' => true,
                    'description' => 'The name of the launch configuration to use with the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'MinSize' => array(
                    'required' => true,
                    'description' => 'The minimum size of the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxSize' => array(
                    'required' => true,
                    'description' => 'The maximum size of the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DesiredCapacity' => array(
                    'description' => 'The number of Amazon EC2 instances that should be running in the group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DefaultCooldown' => array(
                    'description' => 'The amount of time, in seconds, after a scaling activity completes before any further trigger-related scaling activities can start.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AvailabilityZones' => array(
                    'description' => 'A list of Availability Zones for the Auto Scaling group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AvailabilityZones.member',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
                'LoadBalancerNames' => array(
                    'description' => 'A list of load balancers to use.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'LoadBalancerNames.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
                'HealthCheckType' => array(
                    'description' => 'The service you want the health status from, Amazon EC2 or Elastic Load Balancer. Valid values are EC2 or ELB.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 32,
                ),
                'HealthCheckGracePeriod' => array(
                    'description' => 'Length of time in seconds after a new Amazon EC2 instance comes into service that Auto Scaling starts checking its health.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PlacementGroup' => array(
                    'description' => 'Physical location of your cluster placement group created in Amazon EC2. For more information about cluster placement group, see Using Cluster Instances',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'VPCZoneIdentifier' => array(
                    'description' => 'A comma-separated list of subnet identifiers of Amazon Virtual Private Clouds (Amazon VPCs).',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'TerminationPolicies' => array(
                    'description' => 'A standalone termination policy or a list of termination policies used to select the instance to terminate. The policies are executed in the order that they are listed.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'TerminationPolicies.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen1600',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'Tags' => array(
                    'description' => 'The tag to be created or updated. Each tag should be defined by its resource type, resource ID, key, value, and a propagate flag. Valid values: key=value, value=value, propagate=true or false. Value and propagate are optional parameters.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tags.member',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'The tag applied to an Auto Scaling group.',
                        'type' => 'object',
                        'properties' => array(
                            'ResourceId' => array(
                                'description' => 'The name of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'The kind of resource to which the tag is applied. Currently, Auto Scaling supports the auto-scaling-group resource type.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'required' => true,
                                'description' => 'The key of the tag.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 128,
                            ),
                            'Value' => array(
                                'description' => 'The value of the tag.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'PropagateAtLaunch' => array(
                                'description' => 'Specifies whether the new tag will be applied to instances launched after the tag is created. The same behavior applies to updates: If you change a tag, the changed tag will be applied to all instances launched after you made the change.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The named Auto Scaling group or launch configuration already exists.',
                    'class' => 'AlreadyExistsException',
                ),
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'CreateLaunchConfiguration' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new launch configuration. The launch configuration name must be unique within the scope of the client\'s AWS account. The maximum limit of launch configurations, which by default is 100, must not yet have been met; otherwise, the call will fail. When created, the new launch configuration is available for immediate use.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateLaunchConfiguration',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'LaunchConfigurationName' => array(
                    'required' => true,
                    'description' => 'The name of the launch configuration to create.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'ImageId' => array(
                    'required' => true,
                    'description' => 'Unique ID of the Amazon Machine Image (AMI) which was assigned during registration. For more information about Amazon EC2 images, please see Amazon EC2 product documentation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'KeyName' => array(
                    'description' => 'The name of the Amazon EC2 key pair.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'SecurityGroups' => array(
                    'description' => 'The names of the security groups with which to associate Amazon EC2 or Amazon VPC instances. Specify Amazon EC2 security groups using security group names, such as websrv. Specify Amazon VPC security groups using security group IDs, such as sg-12345678. For more information about Amazon EC2 security groups, go to Using Security Groups in the Amazon EC2 product documentation. For more information about Amazon VPC security groups, go to Security Groups in the Amazon VPC product documentation.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'SecurityGroups.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                    ),
                ),
                'UserData' => array(
                    'description' => 'The user data available to the launched Amazon EC2 instances. For more information about Amazon EC2 user data, please see Amazon EC2 product documentation.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 21847,
                ),
                'InstanceType' => array(
                    'required' => true,
                    'description' => 'The instance type of the Amazon EC2 instance. For more information about Amazon EC2 instance types, please see Amazon EC2 product documentation',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'KernelId' => array(
                    'description' => 'The ID of the kernel associated with the Amazon EC2 AMI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'RamdiskId' => array(
                    'description' => 'The ID of the RAM disk associated with the Amazon EC2 AMI.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'BlockDeviceMappings' => array(
                    'description' => 'A list of mappings that specify how block devices are exposed to the instance. Each mapping is made up of a VirtualName, a DeviceName, and an ebs data structure that contains information about the associated Elastic Block Storage volume. For more information about Amazon EC2 BlockDeviceMappings, go to Block Device Mapping in the Amazon EC2 product documentation.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'BlockDeviceMappings.member',
                    'items' => array(
                        'name' => 'BlockDeviceMapping',
                        'description' => 'The BlockDeviceMapping data type.',
                        'type' => 'object',
                        'properties' => array(
                            'VirtualName' => array(
                                'description' => 'The virtual name associated with the device.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'DeviceName' => array(
                                'required' => true,
                                'description' => 'The name of the device within Amazon EC2.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Ebs' => array(
                                'description' => 'The Elastic Block Storage volume information.',
                                'type' => 'object',
                                'properties' => array(
                                    'SnapshotId' => array(
                                        'description' => 'The snapshot ID.',
                                        'type' => 'string',
                                        'minLength' => 1,
                                        'maxLength' => 255,
                                    ),
                                    'VolumeSize' => array(
                                        'description' => 'The volume size, in gigabytes.',
                                        'type' => 'numeric',
                                        'minimum' => 1,
                                        'maximum' => 1024,
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'InstanceMonitoring' => array(
                    'description' => 'Enables detailed monitoring, which is enabled by default.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'If True, instance monitoring is enabled.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                    ),
                ),
                'SpotPrice' => array(
                    'description' => 'The maximum hourly price to be paid for any Spot Instance launched to fulfill the request. Spot Instances are launched when the price you specify exceeds the current Spot market price. For more information on launching Spot Instances, go to Using Auto Scaling to Launch Spot Instances in the Auto Scaling Developer Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'IamInstanceProfile' => array(
                    'description' => 'The name or the Amazon Resource Name (ARN) of the instance profile associated with the IAM role for the instance. For information on launching EC2 instances with an IAM role, go to Launching Auto Scaling Instances With an IAM Role in the Auto Scaling Developer Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'EbsOptimized' => array(
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The named Auto Scaling group or launch configuration already exists.',
                    'class' => 'AlreadyExistsException',
                ),
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'CreateOrUpdateTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates new tags or updates existing tags for an Auto Scaling group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateOrUpdateTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'Tags' => array(
                    'required' => true,
                    'description' => 'The tag to be created or updated. Each tag should be defined by its resource type, resource ID, key, value, and a propagate flag. The resource type and resource ID identify the type and name of resource for which the tag is created. Currently, auto-scaling-group is the only supported resource type. The valid value for the resource ID is groupname.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tags.member',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'The tag applied to an Auto Scaling group.',
                        'type' => 'object',
                        'properties' => array(
                            'ResourceId' => array(
                                'description' => 'The name of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'The kind of resource to which the tag is applied. Currently, Auto Scaling supports the auto-scaling-group resource type.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'required' => true,
                                'description' => 'The key of the tag.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 128,
                            ),
                            'Value' => array(
                                'description' => 'The value of the tag.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'PropagateAtLaunch' => array(
                                'description' => 'Specifies whether the new tag will be applied to instances launched after the tag is created. The same behavior applies to updates: If you change a tag, the changed tag will be applied to all instances launched after you made the change.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
                array(
                    'reason' => 'The named Auto Scaling group or launch configuration already exists.',
                    'class' => 'AlreadyExistsException',
                ),
            ),
        ),
        'DeleteAutoScalingGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified Auto Scaling group if the group has no instances and no scaling activities in progress.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAutoScalingGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ForceDelete' => array(
                    'description' => 'Starting with API version 2011-01-01, specifies that the Auto Scaling group will be deleted along with all instances associated with the group, without waiting for all instances to be terminated.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You cannot delete an Auto Scaling group while there are scaling activities in progress for that group.',
                    'class' => 'ScalingActivityInProgressException',
                ),
                array(
                    'reason' => 'This is returned when you cannot delete a launch configuration or Auto Scaling group because it is being used.',
                    'class' => 'ResourceInUseException',
                ),
            ),
        ),
        'DeleteLaunchConfiguration' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified LaunchConfiguration.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteLaunchConfiguration',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'LaunchConfigurationName' => array(
                    'required' => true,
                    'description' => 'The name of the launch configuration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This is returned when you cannot delete a launch configuration or Auto Scaling group because it is being used.',
                    'class' => 'ResourceInUseException',
                ),
            ),
        ),
        'DeleteNotificationConfiguration' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes notifications created by PutNotificationConfiguration.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteNotificationConfiguration',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'TopicARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
            ),
        ),
        'DeletePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a policy created by PutScalingPolicy.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeletePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name or PolicyARN of the policy you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
            ),
        ),
        'DeleteScheduledAction' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes a scheduled action previously created using the PutScheduledUpdateGroupAction.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteScheduledAction',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ScheduledActionName' => array(
                    'required' => true,
                    'description' => 'The name of the action you want to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
            ),
        ),
        'DeleteTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Removes the specified tags or a set of tags from a set of resources.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'Tags' => array(
                    'required' => true,
                    'description' => 'Each tag should be defined by its resource type, resource ID, key, value, and a propagate flag. Valid values are: Resource type = auto-scaling-group, Resource ID = AutoScalingGroupName, key=value, value=value, propagate=true or false.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Tags.member',
                    'items' => array(
                        'name' => 'Tag',
                        'description' => 'The tag applied to an Auto Scaling group.',
                        'type' => 'object',
                        'properties' => array(
                            'ResourceId' => array(
                                'description' => 'The name of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'The kind of resource to which the tag is applied. Currently, Auto Scaling supports the auto-scaling-group resource type.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'required' => true,
                                'description' => 'The key of the tag.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 128,
                            ),
                            'Value' => array(
                                'description' => 'The value of the tag.',
                                'type' => 'string',
                                'maxLength' => 256,
                            ),
                            'PropagateAtLaunch' => array(
                                'description' => 'Specifies whether the new tag will be applied to instances launched after the tag is created. The same behavior applies to updates: If you change a tag, the changed tag will be applied to all instances launched after you made the change.',
                                'type' => 'boolean',
                                'format' => 'boolean-string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeAdjustmentTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAdjustmentTypesAnswer',
            'responseType' => 'model',
            'summary' => 'Returns policy adjustment types for use in the PutScalingPolicy action.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAdjustmentTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
            ),
        ),
        'DescribeAutoScalingGroups' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AutoScalingGroupsType',
            'responseType' => 'model',
            'summary' => 'Returns a full description of each Auto Scaling group in the given list. This includes all Amazon EC2 instances that are members of the group. If a list of names is not provided, the service returns the full details of all Auto Scaling groups.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAutoScalingGroups',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupNames' => array(
                    'description' => 'A list of Auto Scaling group names.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AutoScalingGroupNames.member',
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to return.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeAutoScalingInstances' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'AutoScalingInstancesType',
            'responseType' => 'model',
            'summary' => 'Returns a description of each Auto Scaling instance in the InstanceIds list. If a list is not provided, the service returns the full details of all instances up to a maximum of 50. By default, the service returns a list of 20 items.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAutoScalingInstances',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'InstanceIds' => array(
                    'description' => 'The list of Auto Scaling instances to describe. If this list is omitted, all auto scaling instances are described. The list of requested instances cannot contain more than 50 items. If unknown instances are requested, they are ignored with no error.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InstanceIds.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen16',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 16,
                    ),
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of Auto Scaling instances to be described with each call.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'NextToken' => array(
                    'description' => 'The token returned by a previous call to indicate that there is more data available.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeAutoScalingNotificationTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAutoScalingNotificationTypesAnswer',
            'responseType' => 'model',
            'summary' => 'Returns a list of all notification types that are supported by Auto Scaling.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAutoScalingNotificationTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
            ),
        ),
        'DescribeLaunchConfigurations' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'LaunchConfigurationsType',
            'responseType' => 'model',
            'summary' => 'Returns a full description of the launch configurations, or the specified launch configurations, if they exist.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeLaunchConfigurations',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'LaunchConfigurationNames' => array(
                    'description' => 'A list of launch configuration names.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'LaunchConfigurationNames.member',
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of launch configurations. The default is 100.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeMetricCollectionTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeMetricCollectionTypesAnswer',
            'responseType' => 'model',
            'summary' => 'Returns a list of metrics and a corresponding list of granularities for each metric.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeMetricCollectionTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
            ),
        ),
        'DescribeNotificationConfigurations' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeNotificationConfigurationsAnswer',
            'responseType' => 'model',
            'summary' => 'Returns a list of notification actions associated with Auto Scaling groups for specified events.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeNotificationConfigurations',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupNames' => array(
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AutoScalingGroupNames.member',
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that is used to mark the start of the next batch of returned results for pagination.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'Maximum number of records to be returned.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribePolicies' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'PoliciesType',
            'responseType' => 'model',
            'summary' => 'Returns descriptions of what each policy does. This action supports pagination. If the response includes a token, there are more records available. To get the additional records, repeat the request with the response token as the NextToken parameter.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribePolicies',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'PolicyNames' => array(
                    'description' => 'A list of policy names or policy ARNs to be described. If this list is omitted, all policy names are described. If an auto scaling group name is provided, the results are limited to that group. The list of requested policy names cannot contain more than 50 items. If unknown policy names are requested, they are ignored with no error.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'PolicyNames.member',
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that is used to mark the start of the next batch of returned results for pagination.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of policies that will be described with each call.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeScalingActivities' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ActivitiesType',
            'responseType' => 'model',
            'summary' => 'Returns the scaling activities for the specified Auto Scaling group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeScalingActivities',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'ActivityIds' => array(
                    'description' => 'A list containing the activity IDs of the desired scaling activities. If this list is omitted, all activities are described. If an AutoScalingGroupName is provided, the results are limited to that group. The list of requested activities cannot contain more than 50 items. If unknown activities are requested, they are ignored with no error.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ActivityIds.member',
                    'items' => array(
                        'name' => 'XmlString',
                        'type' => 'string',
                    ),
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name of the AutoScalingGroup.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of scaling activities to return.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results for pagination.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeScalingProcessTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ProcessesType',
            'responseType' => 'model',
            'summary' => 'Returns scaling process types for use in the ResumeProcesses and SuspendProcesses actions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeScalingProcessTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
            ),
        ),
        'DescribeScheduledActions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ScheduledActionsType',
            'responseType' => 'model',
            'summary' => 'Lists all the actions scheduled for your Auto Scaling group that haven\'t been executed. To see a list of actions already executed, see the activity record returned in DescribeScalingActivities.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeScheduledActions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ScheduledActionNames' => array(
                    'description' => 'A list of scheduled actions to be described. If this list is omitted, all scheduled actions are described. The list of requested scheduled actions cannot contain more than 50 items. If an auto scaling group name is provided, the results are limited to that group. If unknown scheduled actions are requested, they are ignored with no error.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ScheduledActionNames.member',
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
                'StartTime' => array(
                    'description' => 'The earliest scheduled start time to return. If scheduled action names are provided, this field will be ignored.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The latest scheduled start time to return. If scheduled action names are provided, this field is ignored.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of scheduled actions to return.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeTags' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'TagsType',
            'responseType' => 'model',
            'summary' => 'Lists the Auto Scaling group tags.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeTags',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'Filters' => array(
                    'description' => 'The value of the filter type used to identify the tags to be returned. For example, you can filter so that tags are returned according to Auto Scaling group, the key and value, or whether the new tag will be applied to instances launched after the tag is created (PropagateAtLaunch).',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Filters.member',
                    'items' => array(
                        'name' => 'Filter',
                        'description' => 'The Filter data type.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name of the filter. Valid Name values are: "auto-scaling-group", "key", "value", and "propagate-at-launch".',
                                'type' => 'string',
                            ),
                            'Values' => array(
                                'description' => 'The value of the filter.',
                                'type' => 'array',
                                'sentAs' => 'Values.member',
                                'items' => array(
                                    'name' => 'XmlString',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of records to return.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 50,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The NextToken value is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeTerminationPolicyTypes' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeTerminationPolicyTypesAnswer',
            'responseType' => 'model',
            'summary' => 'Returns a list of all termination policies supported by Auto Scaling.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeTerminationPolicyTypes',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
            ),
        ),
        'DisableMetricsCollection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Disables monitoring of group metrics for the Auto Scaling group specified in AutoScalingGroupName. You can specify the list of affected metrics with the Metrics parameter.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisableMetricsCollection',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or ARN of the Auto Scaling Group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'Metrics' => array(
                    'description' => 'The list of metrics to disable. If no metrics are specified, all metrics are disabled. The following metrics are supported:',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Metrics.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
        ),
        'EnableMetricsCollection' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Enables monitoring of group metrics for the Auto Scaling group specified in AutoScalingGroupName. You can specify the list of enabled metrics with the Metrics parameter.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableMetricsCollection',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or ARN of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'Metrics' => array(
                    'description' => 'The list of metrics to collect. If no metrics are specified, all metrics are enabled. The following metrics are supported:',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Metrics.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
                'Granularity' => array(
                    'required' => true,
                    'description' => 'The granularity to associate with the metrics to collect. Currently, the only legal granularity is "1Minute".',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
            ),
        ),
        'ExecutePolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Runs the policy you create for your Auto Scaling group in PutScalingPolicy.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ExecutePolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'description' => 'The name or ARN of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name or PolicyARN of the policy you want to run.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'HonorCooldown' => array(
                    'description' => 'Set to True if you want Auto Scaling to reject this request when the Auto Scaling group is in cooldown.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You cannot delete an Auto Scaling group while there are scaling activities in progress for that group.',
                    'class' => 'ScalingActivityInProgressException',
                ),
            ),
        ),
        'PutNotificationConfiguration' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Configures an Auto Scaling group to send notifications when specified events take place. Subscribers to this topic can have messages for events delivered to an endpoint such as a web server or email address.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutNotificationConfiguration',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'TopicARN' => array(
                    'required' => true,
                    'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'NotificationTypes' => array(
                    'required' => true,
                    'description' => 'The type of events that will trigger the notification. For more information, go to DescribeAutoScalingNotificationTypes.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'NotificationTypes.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'PutScalingPolicy' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'PolicyARNType',
            'responseType' => 'model',
            'summary' => 'Creates or updates a policy for an Auto Scaling group. To update an existing policy, use the existing policy name and set the parameter(s) you want to change. Any existing parameter not changed in an update to an existing policy is not changed in this update request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutScalingPolicy',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or ARN of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'PolicyName' => array(
                    'required' => true,
                    'description' => 'The name of the policy you want to create or update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'ScalingAdjustment' => array(
                    'required' => true,
                    'description' => 'The number of instances by which to scale. AdjustmentType determines the interpretation of this number (e.g., as an absolute number or as a percentage of the existing Auto Scaling group size). A positive increment adds to the current capacity and a negative value removes from the current capacity.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AdjustmentType' => array(
                    'required' => true,
                    'description' => 'Specifies whether the ScalingAdjustment is an absolute number or a percentage of the current capacity. Valid values are ChangeInCapacity, ExactCapacity, and PercentChangeInCapacity.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Cooldown' => array(
                    'description' => 'The amount of time, in seconds, after a scaling activity completes before any further trigger-related scaling activities can start.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MinAdjustmentStep' => array(
                    'description' => 'Used with AdjustmentType with the value PercentChangeInCapacity, the scaling policy changes the DesiredCapacity of the Auto Scaling group by at least the number of instances specified in the value.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'PutScheduledUpdateGroupAction' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates a scheduled scaling action for an Auto Scaling group. If you leave a parameter unspecified, the corresponding value remains unchanged in the affected Auto Scaling group.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutScheduledUpdateGroupAction',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or ARN of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ScheduledActionName' => array(
                    'required' => true,
                    'description' => 'The name of this scaling action.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Time' => array(
                    'description' => 'Time is deprecated.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'StartTime' => array(
                    'description' => 'The time for this action to start, as in --start-time 2010-06-01T00:00:00Z.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'The time for this action to end.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'Recurrence' => array(
                    'description' => 'The time when recurring future actions will start. Start time is specified by the user following the Unix cron syntax format. For information about cron syntax, go to Wikipedia, The Free Encyclopedia.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'MinSize' => array(
                    'description' => 'The minimum size for the new Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxSize' => array(
                    'description' => 'The maximum size for the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DesiredCapacity' => array(
                    'description' => 'The number of Amazon EC2 instances that should be running in the group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The named Auto Scaling group or launch configuration already exists.',
                    'class' => 'AlreadyExistsException',
                ),
                array(
                    'reason' => 'The quota for capacity groups or launch configurations for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'ResumeProcesses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Resumes Auto Scaling processes for an Auto Scaling group. For more information, see SuspendProcesses and ProcessType.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ResumeProcesses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or Amazon Resource Name (ARN) of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ScalingProcesses' => array(
                    'description' => 'The processes that you want to suspend or resume, which can include one or more of the following:',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ScalingProcesses.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
        ),
        'SetDesiredCapacity' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Adjusts the desired size of the AutoScalingGroup by initiating scaling activities. When reducing the size of the group, it is not possible to define which Amazon EC2 instances will be terminated. This applies to any Auto Scaling decisions that might result in terminating instances.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetDesiredCapacity',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'DesiredCapacity' => array(
                    'required' => true,
                    'description' => 'The new capacity setting for the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'HonorCooldown' => array(
                    'description' => 'By default, SetDesiredCapacity overrides any cooldown period. Set to True if you want Auto Scaling to reject this request when the Auto Scaling group is in cooldown.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You cannot delete an Auto Scaling group while there are scaling activities in progress for that group.',
                    'class' => 'ScalingActivityInProgressException',
                ),
            ),
        ),
        'SetInstanceHealth' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Sets the health status of an instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetInstanceHealth',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The identifier of the Amazon EC2 instance.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 16,
                ),
                'HealthStatus' => array(
                    'required' => true,
                    'description' => 'The health status of the instance. "Healthy" means that the instance is healthy and should remain in service. "Unhealthy" means that the instance is unhealthy. Auto Scaling should terminate and replace it.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 32,
                ),
                'ShouldRespectGracePeriod' => array(
                    'description' => 'If True, this call should respect the grace period associated with the group.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'SuspendProcesses' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Suspends Auto Scaling processes for an Auto Scaling group. To suspend specific process types, specify them by name with the ScalingProcesses.member.N parameter. To suspend all process types, omit the ScalingProcesses.member.N parameter.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SuspendProcesses',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name or Amazon Resource Name (ARN) of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'ScalingProcesses' => array(
                    'description' => 'The processes that you want to suspend or resume, which can include one or more of the following:',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ScalingProcesses.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
        ),
        'TerminateInstanceInAutoScalingGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ActivityType',
            'responseType' => 'model',
            'summary' => 'Terminates the specified instance. Optionally, the desired group size can be adjusted.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'TerminateInstanceInAutoScalingGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'InstanceId' => array(
                    'required' => true,
                    'description' => 'The ID of the Amazon EC2 instance to be terminated.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 16,
                ),
                'ShouldDecrementDesiredCapacity' => array(
                    'required' => true,
                    'description' => 'Specifies whether (true) or not (false) terminating this instance should also decrement the size of the AutoScalingGroup.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You cannot delete an Auto Scaling group while there are scaling activities in progress for that group.',
                    'class' => 'ScalingActivityInProgressException',
                ),
            ),
        ),
        'UpdateAutoScalingGroup' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Updates the configuration for the specified AutoScalingGroup.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateAutoScalingGroup',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2011-01-01',
                ),
                'AutoScalingGroupName' => array(
                    'required' => true,
                    'description' => 'The name of the Auto Scaling group.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'LaunchConfigurationName' => array(
                    'description' => 'The name of the launch configuration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1600,
                ),
                'MinSize' => array(
                    'description' => 'The minimum size of the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'MaxSize' => array(
                    'description' => 'The maximum size of the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DesiredCapacity' => array(
                    'description' => 'The desired capacity for the Auto Scaling group.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'DefaultCooldown' => array(
                    'description' => 'The amount of time, in seconds, after a scaling activity completes before any further trigger-related scaling activities can start.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'AvailabilityZones' => array(
                    'description' => 'Availability Zones for the group.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AvailabilityZones.member',
                    'minItems' => 1,
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
                'HealthCheckType' => array(
                    'description' => 'The service of interest for the health status check, either "EC2" for Amazon EC2 or "ELB" for Elastic Load Balancing.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 32,
                ),
                'HealthCheckGracePeriod' => array(
                    'description' => 'The length of time that Auto Scaling waits before checking an instance\'s health status. The grace period begins when an instance comes into service.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'PlacementGroup' => array(
                    'description' => 'The name of the cluster placement group, if applicable. For more information, go to Using Cluster Instances in the Amazon EC2 User Guide.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'VPCZoneIdentifier' => array(
                    'description' => 'The subnet identifier for the Amazon VPC connection, if applicable. You can specify several subnets in a comma-separated list.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'TerminationPolicies' => array(
                    'description' => 'A standalone termination policy or a list of termination policies used to select the instance to terminate. The policies are executed in the order that they are listed.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'TerminationPolicies.member',
                    'items' => array(
                        'name' => 'XmlStringMaxLen1600',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1600,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'You cannot delete an Auto Scaling group while there are scaling activities in progress for that group.',
                    'class' => 'ScalingActivityInProgressException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'DescribeAdjustmentTypesAnswer' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AdjustmentTypes' => array(
                    'description' => 'A list of specific policy adjustment types.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AdjustmentType',
                        'description' => 'Specifies whether the PutScalingPolicy ScalingAdjustment parameter is an absolute number or a percentage of the current capacity.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AdjustmentType' => array(
                                'description' => 'A policy adjustment type. Valid values are ChangeInCapacity, ExactCapacity, and PercentChangeInCapacity.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'AutoScalingGroupsType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AutoScalingGroups' => array(
                    'description' => 'A list of Auto Scaling groups.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AutoScalingGroup',
                        'description' => 'The AutoScalingGroup data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AutoScalingGroupName' => array(
                                'description' => 'Specifies the name of the group.',
                                'type' => 'string',
                            ),
                            'AutoScalingGroupARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'LaunchConfigurationName' => array(
                                'description' => 'Specifies the name of the associated LaunchConfiguration.',
                                'type' => 'string',
                            ),
                            'MinSize' => array(
                                'description' => 'Contains the minimum size of the Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                            'MaxSize' => array(
                                'description' => 'Contains the maximum size of the Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                            'DesiredCapacity' => array(
                                'description' => 'Specifies the desired capacity for the Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                            'DefaultCooldown' => array(
                                'description' => 'The number of seconds after a scaling activity completes before any further scaling activities can start.',
                                'type' => 'numeric',
                            ),
                            'AvailabilityZones' => array(
                                'description' => 'Contains a list of Availability Zones for the group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'XmlStringMaxLen255',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'LoadBalancerNames' => array(
                                'description' => 'A list of load balancers associated with this Auto Scaling group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'XmlStringMaxLen255',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'HealthCheckType' => array(
                                'description' => 'The service of interest for the health status check, either "EC2" for Amazon EC2 or "ELB" for Elastic Load Balancing.',
                                'type' => 'string',
                            ),
                            'HealthCheckGracePeriod' => array(
                                'description' => 'The length of time that Auto Scaling waits before checking an instance\'s health status. The grace period begins when an instance comes into service.',
                                'type' => 'numeric',
                            ),
                            'Instances' => array(
                                'description' => 'Provides a summary list of Amazon EC2 instances.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Instance',
                                    'description' => 'The Instance data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'InstanceId' => array(
                                            'description' => 'Specifies the ID of the Amazon EC2 instance.',
                                            'type' => 'string',
                                        ),
                                        'AvailabilityZone' => array(
                                            'description' => 'Availability Zones associated with this instance.',
                                            'type' => 'string',
                                        ),
                                        'LifecycleState' => array(
                                            'description' => 'Contains a description of the current lifecycle state.',
                                            'type' => 'string',
                                        ),
                                        'HealthStatus' => array(
                                            'description' => 'The instance\'s health status.',
                                            'type' => 'string',
                                        ),
                                        'LaunchConfigurationName' => array(
                                            'description' => 'The launch configuration associated with this instance.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'CreatedTime' => array(
                                'description' => 'Specifies the date and time the Auto Scaling group was created.',
                                'type' => 'string',
                            ),
                            'SuspendedProcesses' => array(
                                'description' => 'Suspended processes associated with this Auto Scaling group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'SuspendedProcess',
                                    'description' => 'An Auto Scaling process that has been suspended. For more information, see ProcessType.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'ProcessName' => array(
                                            'description' => 'The name of the suspended process.',
                                            'type' => 'string',
                                        ),
                                        'SuspensionReason' => array(
                                            'description' => 'The reason that the process was suspended.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'PlacementGroup' => array(
                                'description' => 'The name of the cluster placement group, if applicable. For more information, go to Using Cluster Instances in the Amazon EC2 User Guide.',
                                'type' => 'string',
                            ),
                            'VPCZoneIdentifier' => array(
                                'description' => 'The subnet identifier for the Amazon VPC connection, if applicable. You can specify several subnets in a comma-separated list.',
                                'type' => 'string',
                            ),
                            'EnabledMetrics' => array(
                                'description' => 'A list of metrics enabled for this Auto Scaling group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'EnabledMetric',
                                    'description' => 'The EnabledMetric data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Metric' => array(
                                            'description' => 'The name of the enabled metric.',
                                            'type' => 'string',
                                        ),
                                        'Granularity' => array(
                                            'description' => 'The granularity of the enabled metric.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Status' => array(
                                'description' => 'A list of status conditions for the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'Tags' => array(
                                'description' => 'A list of tags for the Auto Scaling group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'TagDescription',
                                    'description' => 'The tag applied to an Auto Scaling group.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'ResourceId' => array(
                                            'description' => 'The name of the Auto Scaling group.',
                                            'type' => 'string',
                                        ),
                                        'ResourceType' => array(
                                            'description' => 'The kind of resource to which the tag is applied. Currently, Auto Scaling supports the auto-scaling-group resource type.',
                                            'type' => 'string',
                                        ),
                                        'Key' => array(
                                            'description' => 'The key of the tag.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value of the tag.',
                                            'type' => 'string',
                                        ),
                                        'PropagateAtLaunch' => array(
                                            'description' => 'Specifies whether the new tag will be applied to instances launched after the tag is created. The same behavior applies to updates: If you change a tag, the changed tag will be applied to all instances launched after you made the change.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                            ),
                            'TerminationPolicies' => array(
                                'description' => 'A standalone termination policy or a list of termination policies for this Auto Scaling group.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'XmlStringMaxLen1600',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'AutoScalingInstancesType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AutoScalingInstances' => array(
                    'description' => 'A list of Auto Scaling instances.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AutoScalingInstanceDetails',
                        'description' => 'The AutoScalingInstanceDetails data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'InstanceId' => array(
                                'description' => 'The instance ID of the Amazon EC2 instance.',
                                'type' => 'string',
                            ),
                            'AutoScalingGroupName' => array(
                                'description' => 'The name of the Auto Scaling group associated with this instance.',
                                'type' => 'string',
                            ),
                            'AvailabilityZone' => array(
                                'description' => 'The Availability Zone in which this instance resides.',
                                'type' => 'string',
                            ),
                            'LifecycleState' => array(
                                'description' => 'The life cycle state of this instance.',
                                'type' => 'string',
                            ),
                            'HealthStatus' => array(
                                'description' => 'The health status of this instance. "Healthy" means that the instance is healthy and should remain in service. "Unhealthy" means that the instance is unhealthy. Auto Scaling should terminate and replace it.',
                                'type' => 'string',
                            ),
                            'LaunchConfigurationName' => array(
                                'description' => 'The launch configuration associated with this instance.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DescribeAutoScalingNotificationTypesAnswer' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AutoScalingNotificationTypes' => array(
                    'description' => 'Notification types supported by Auto Scaling. They are: autoscaling:EC2_INSTANCE_LAUNCH, autoscaling:EC2_INSTANCE_LAUNCH_ERROR, autoscaling:EC2_INSTANCE_TERMINATE, autoscaling:EC2_INSTANCE_TERMINATE_ERROR, autoscaling:TEST_NOTIFICATION',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'XmlStringMaxLen255',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'LaunchConfigurationsType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LaunchConfigurations' => array(
                    'description' => 'A list of launch configurations.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'LaunchConfiguration',
                        'description' => 'The LaunchConfiguration data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'LaunchConfigurationName' => array(
                                'description' => 'Specifies the name of the launch configuration.',
                                'type' => 'string',
                            ),
                            'LaunchConfigurationARN' => array(
                                'description' => 'The launch configuration\'s Amazon Resource Name (ARN).',
                                'type' => 'string',
                            ),
                            'ImageId' => array(
                                'description' => 'Provides the unique ID of the Amazon Machine Image (AMI) that was assigned during registration.',
                                'type' => 'string',
                            ),
                            'KeyName' => array(
                                'description' => 'Provides the name of the Amazon EC2 key pair.',
                                'type' => 'string',
                            ),
                            'SecurityGroups' => array(
                                'description' => 'A description of the security groups to associate with the Amazon EC2 instances.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'XmlString',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'UserData' => array(
                                'description' => 'The user data available to the launched Amazon EC2 instances.',
                                'type' => 'string',
                            ),
                            'InstanceType' => array(
                                'description' => 'Specifies the instance type of the Amazon EC2 instance.',
                                'type' => 'string',
                            ),
                            'KernelId' => array(
                                'description' => 'Provides the ID of the kernel associated with the Amazon EC2 AMI.',
                                'type' => 'string',
                            ),
                            'RamdiskId' => array(
                                'description' => 'Provides ID of the RAM disk associated with the Amazon EC2 AMI.',
                                'type' => 'string',
                            ),
                            'BlockDeviceMappings' => array(
                                'description' => 'Specifies how block devices are exposed to the instance. Each mapping is made up of a virtualName and a deviceName.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'BlockDeviceMapping',
                                    'description' => 'The BlockDeviceMapping data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'VirtualName' => array(
                                            'description' => 'The virtual name associated with the device.',
                                            'type' => 'string',
                                        ),
                                        'DeviceName' => array(
                                            'description' => 'The name of the device within Amazon EC2.',
                                            'type' => 'string',
                                        ),
                                        'Ebs' => array(
                                            'description' => 'The Elastic Block Storage volume information.',
                                            'type' => 'object',
                                            'properties' => array(
                                                'SnapshotId' => array(
                                                    'description' => 'The snapshot ID.',
                                                    'type' => 'string',
                                                ),
                                                'VolumeSize' => array(
                                                    'description' => 'The volume size, in gigabytes.',
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'InstanceMonitoring' => array(
                                'description' => 'Controls whether instances in this group are launched with detailed monitoring or not.',
                                'type' => 'object',
                                'properties' => array(
                                    'Enabled' => array(
                                        'description' => 'If True, instance monitoring is enabled.',
                                        'type' => 'boolean',
                                    ),
                                ),
                            ),
                            'SpotPrice' => array(
                                'description' => 'Specifies the price to bid when launching Spot Instances.',
                                'type' => 'string',
                            ),
                            'IamInstanceProfile' => array(
                                'description' => 'Provides the name or the Amazon Resource Name (ARN) of the instance profile associated with the IAM role for the instance. The instance profile contains the IAM role.',
                                'type' => 'string',
                            ),
                            'CreatedTime' => array(
                                'description' => 'Provides the creation date and time for this launch configuration.',
                                'type' => 'string',
                            ),
                            'EbsOptimized' => array(
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DescribeMetricCollectionTypesAnswer' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Metrics' => array(
                    'description' => 'The list of Metrics collected.The following metrics are supported:',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'MetricCollectionType',
                        'description' => 'The MetricCollectionType data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Metric' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Granularities' => array(
                    'description' => 'A list of granularities for the listed Metrics.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'MetricGranularityType',
                        'description' => 'The MetricGranularityType data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Granularity' => array(
                                'description' => 'The granularity of a Metric.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeNotificationConfigurationsAnswer' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'NotificationConfigurations' => array(
                    'description' => 'The list of notification configurations.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'NotificationConfiguration',
                        'description' => 'The NotificationConfiguration data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AutoScalingGroupName' => array(
                                'description' => 'Specifies the Auto Scaling group name.',
                                'type' => 'string',
                            ),
                            'TopicARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the Amazon Simple Notification Service (SNS) topic.',
                                'type' => 'string',
                            ),
                            'NotificationType' => array(
                                'description' => 'The types of events for an action to start.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that is used to mark the start of the next batch of returned results for pagination.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'PoliciesType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ScalingPolicies' => array(
                    'description' => 'A list of scaling policies.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ScalingPolicy',
                        'description' => 'The ScalingPolicy data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AutoScalingGroupName' => array(
                                'description' => 'The name of the Auto Scaling group associated with this scaling policy.',
                                'type' => 'string',
                            ),
                            'PolicyName' => array(
                                'description' => 'The name of the scaling policy.',
                                'type' => 'string',
                            ),
                            'ScalingAdjustment' => array(
                                'description' => 'The number associated with the specified adjustment type. A positive value adds to the current capacity and a negative value removes from the current capacity.',
                                'type' => 'numeric',
                            ),
                            'AdjustmentType' => array(
                                'description' => 'Specifies whether the ScalingAdjustment is an absolute number or a percentage of the current capacity. Valid values are ChangeInCapacity, ExactCapacity, and PercentChangeInCapacity.',
                                'type' => 'string',
                            ),
                            'Cooldown' => array(
                                'description' => 'The amount of time, in seconds, after a scaling activity completes before any further trigger-related scaling activities can start.',
                                'type' => 'numeric',
                            ),
                            'PolicyARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the policy.',
                                'type' => 'string',
                            ),
                            'Alarms' => array(
                                'description' => 'A list of CloudWatch Alarms related to the policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Alarm',
                                    'description' => 'The Alarm data type.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'AlarmName' => array(
                                            'description' => 'The name of the alarm.',
                                            'type' => 'string',
                                        ),
                                        'AlarmARN' => array(
                                            'description' => 'The Amazon Resource Name (ARN) of the alarm.',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'MinAdjustmentStep' => array(
                                'description' => 'Changes the DesiredCapacity of the Auto Scaling group by at least the specified number of instances.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ActivitiesType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Activities' => array(
                    'description' => 'A list of the requested scaling activities.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Activity',
                        'description' => 'A scaling Activity is a long-running process that represents a change to your AutoScalingGroup, such as changing the size of the group. It can also be a process to replace an instance, or a process to perform any other long-running operations supported by the API.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ActivityId' => array(
                                'description' => 'Specifies the ID of the activity.',
                                'type' => 'string',
                            ),
                            'AutoScalingGroupName' => array(
                                'description' => 'The name of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Contains a friendly, more verbose description of the scaling activity.',
                                'type' => 'string',
                            ),
                            'Cause' => array(
                                'description' => 'Contains the reason the activity was begun.',
                                'type' => 'string',
                            ),
                            'StartTime' => array(
                                'description' => 'Provides the start time of this activity.',
                                'type' => 'string',
                            ),
                            'EndTime' => array(
                                'description' => 'Provides the end time of this activity.',
                                'type' => 'string',
                            ),
                            'StatusCode' => array(
                                'description' => 'Contains the current status of the activity.',
                                'type' => 'string',
                            ),
                            'StatusMessage' => array(
                                'description' => 'Contains a friendly, more verbose description of the activity status.',
                                'type' => 'string',
                            ),
                            'Progress' => array(
                                'description' => 'Specifies a value between 0 and 100 that indicates the progress of the activity.',
                                'type' => 'numeric',
                            ),
                            'Details' => array(
                                'description' => 'Contains details of the scaling activity.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'Acts as a paging mechanism for large result sets. Set to a non-empty string if there are additional results waiting to be returned. Pass this in to subsequent calls to return additional results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ProcessesType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Processes' => array(
                    'description' => 'A list of ProcessType names.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ProcessType',
                        'description' => 'There are two primary Auto Scaling process types--Launch and Terminate. The Launch process creates a new Amazon EC2 instance for an Auto Scaling group, and the Terminate process removes an existing Amazon EC2 instance.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ProcessName' => array(
                                'description' => 'The name of a process.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ScheduledActionsType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ScheduledUpdateGroupActions' => array(
                    'description' => 'A list of scheduled actions designed to update an Auto Scaling group.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ScheduledUpdateGroupAction',
                        'description' => 'This data type stores information about a scheduled update to an Auto Scaling group.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AutoScalingGroupName' => array(
                                'description' => 'The name of the Auto Scaling group to be updated.',
                                'type' => 'string',
                            ),
                            'ScheduledActionName' => array(
                                'description' => 'The name of this scheduled action.',
                                'type' => 'string',
                            ),
                            'ScheduledActionARN' => array(
                                'description' => 'The Amazon Resource Name (ARN) of this scheduled action.',
                                'type' => 'string',
                            ),
                            'Time' => array(
                                'description' => 'Time is deprecated.',
                                'type' => 'string',
                            ),
                            'StartTime' => array(
                                'description' => 'The time that the action is scheduled to begin. This value can be up to one month in the future.',
                                'type' => 'string',
                            ),
                            'EndTime' => array(
                                'description' => 'The time that the action is scheduled to end. This value can be up to one month in the future.',
                                'type' => 'string',
                            ),
                            'Recurrence' => array(
                                'description' => 'The regular schedule that an action occurs.',
                                'type' => 'string',
                            ),
                            'MinSize' => array(
                                'description' => 'The minimum size of the Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                            'MaxSize' => array(
                                'description' => 'The maximum size of the Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                            'DesiredCapacity' => array(
                                'description' => 'The number of instances you prefer to maintain in your Auto Scaling group.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string that marks the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'TagsType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Tags' => array(
                    'description' => 'The list of tags.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'TagDescription',
                        'description' => 'The tag applied to an Auto Scaling group.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ResourceId' => array(
                                'description' => 'The name of the Auto Scaling group.',
                                'type' => 'string',
                            ),
                            'ResourceType' => array(
                                'description' => 'The kind of resource to which the tag is applied. Currently, Auto Scaling supports the auto-scaling-group resource type.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'description' => 'The key of the tag.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The value of the tag.',
                                'type' => 'string',
                            ),
                            'PropagateAtLaunch' => array(
                                'description' => 'Specifies whether the new tag will be applied to instances launched after the tag is created. The same behavior applies to updates: If you change a tag, the changed tag will be applied to all instances launched after you made the change.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'A string used to mark the start of the next batch of returned results.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'DescribeTerminationPolicyTypesAnswer' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TerminationPolicyTypes' => array(
                    'description' => 'Termination policies supported by Auto Scaling. They are: OldestInstance, OldestLaunchConfiguration, NewestInstance, ClosestToNextInstanceHour, Default',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'XmlStringMaxLen1600',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
            ),
        ),
        'PolicyARNType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'PolicyARN' => array(
                    'description' => 'A policy\'s Amazon Resource Name (ARN).',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ActivityType' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Activity' => array(
                    'description' => 'A scaling Activity.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ActivityId' => array(
                            'description' => 'Specifies the ID of the activity.',
                            'type' => 'string',
                        ),
                        'AutoScalingGroupName' => array(
                            'description' => 'The name of the Auto Scaling group.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'Contains a friendly, more verbose description of the scaling activity.',
                            'type' => 'string',
                        ),
                        'Cause' => array(
                            'description' => 'Contains the reason the activity was begun.',
                            'type' => 'string',
                        ),
                        'StartTime' => array(
                            'description' => 'Provides the start time of this activity.',
                            'type' => 'string',
                        ),
                        'EndTime' => array(
                            'description' => 'Provides the end time of this activity.',
                            'type' => 'string',
                        ),
                        'StatusCode' => array(
                            'description' => 'Contains the current status of the activity.',
                            'type' => 'string',
                        ),
                        'StatusMessage' => array(
                            'description' => 'Contains a friendly, more verbose description of the activity status.',
                            'type' => 'string',
                        ),
                        'Progress' => array(
                            'description' => 'Specifies a value between 0 and 100 that indicates the progress of the activity.',
                            'type' => 'numeric',
                        ),
                        'Details' => array(
                            'description' => 'Contains details of the scaling activity.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeAutoScalingGroups' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'AutoScalingGroups',
            ),
            'DescribeAutoScalingInstances' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'AutoScalingInstances',
            ),
            'DescribeLaunchConfigurations' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'LaunchConfigurations',
            ),
            'DescribeNotificationConfigurations' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'NotificationConfigurations',
            ),
            'DescribePolicies' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ScalingPolicies',
            ),
            'DescribeScalingActivities' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Activities',
            ),
            'DescribeScheduledActions' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'ScheduledUpdateGroupActions',
            ),
            'DescribeTags' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Tags',
            ),
        ),
    ),
);
