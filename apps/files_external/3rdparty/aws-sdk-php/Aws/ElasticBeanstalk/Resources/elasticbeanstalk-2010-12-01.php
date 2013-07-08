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
    'apiVersion' => '2010-12-01',
    'endpointPrefix' => 'elasticbeanstalk',
    'serviceFullName' => 'AWS Elastic Beanstalk',
    'serviceAbbreviation' => 'Elastic Beanstalk',
    'serviceType' => 'query',
    'resultWrapped' => true,
    'signatureVersion' => 'v4',
    'namespace' => 'ElasticBeanstalk',
    'regions' => array(
        'us-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'elasticbeanstalk.sa-east-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CheckDNSAvailability' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CheckDNSAvailabilityResultMessage',
            'responseType' => 'model',
            'summary' => 'Checks if the specified CNAME is available.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CheckDNSAvailability',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'CNAMEPrefix' => array(
                    'required' => true,
                    'description' => 'The prefix used when this CNAME is reserved.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 63,
                ),
            ),
        ),
        'CreateApplication' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationDescriptionMessage',
            'responseType' => 'model',
            'summary' => 'Creates an application that has one configuration template named default and no application versions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateApplication',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'Describes the application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The caller has exceeded the limit on the number of applications associated with their account.',
                    'class' => 'TooManyApplicationsException',
                ),
            ),
        ),
        'CreateApplicationVersion' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationVersionDescriptionMessage',
            'responseType' => 'model',
            'summary' => 'Creates an application version for the specified application.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateApplicationVersion',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application. If no application is found with this name, and AutoCreateApplication is false, returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'required' => true,
                    'description' => 'A label identifying this version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'Describes this version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
                'SourceBundle' => array(
                    'description' => 'The Amazon S3 bucket and key that identify the location of the source bundle for this version.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'S3Bucket' => array(
                            'description' => 'The Amazon S3 bucket where the data is located.',
                            'type' => 'string',
                            'maxLength' => 255,
                        ),
                        'S3Key' => array(
                            'description' => 'The Amazon S3 key where the data is located.',
                            'type' => 'string',
                            'maxLength' => 1024,
                        ),
                    ),
                ),
                'AutoCreateApplication' => array(
                    'description' => 'Determines how the system behaves if the specified application for this version does not already exist:',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The caller has exceeded the limit on the number of applications associated with their account.',
                    'class' => 'TooManyApplicationsException',
                ),
                array(
                    'reason' => 'The caller has exceeded the limit on the number of application versions associated with their account.',
                    'class' => 'TooManyApplicationVersionsException',
                ),
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
                array(
                    'reason' => 'The specified S3 bucket does not belong to the S3 region in which the service is running.',
                    'class' => 'S3LocationNotInServiceRegionException',
                ),
            ),
        ),
        'CreateConfigurationTemplate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigurationSettingsDescription',
            'responseType' => 'model',
            'summary' => 'Creates a configuration template. Templates are associated with a specific application and are used to deploy different versions of the application with the same configuration settings.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateConfigurationTemplate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application to associate with this configuration template. If no application is found with this name, AWS Elastic Beanstalk returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'required' => true,
                    'description' => 'The name of the configuration template.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'SolutionStackName' => array(
                    'description' => 'The name of the solution stack used by this configuration. The solution stack specifies the operating system, architecture, and application server for a configuration template. It determines the set of configuration options as well as the possible and default values.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 100,
                ),
                'SourceConfiguration' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk uses the configuration values from the specified configuration template to create a new configuration.',
                    'type' => 'object',
                    'location' => 'aws.query',
                    'properties' => array(
                        'ApplicationName' => array(
                            'description' => 'The name of the application associated with the configuration.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 100,
                        ),
                        'TemplateName' => array(
                            'description' => 'The name of the configuration template.',
                            'type' => 'string',
                            'minLength' => 1,
                            'maxLength' => 100,
                        ),
                    ),
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment used with this configuration template.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Description' => array(
                    'description' => 'Describes this configuration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
                'OptionSettings' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk sets the specified configuration option to the requested value. The new value overrides the value obtained from the solution stack or the source configuration template.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionSettings.member',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
                array(
                    'reason' => 'The caller has exceeded the limit on the number of configuration templates associated with their account.',
                    'class' => 'TooManyConfigurationTemplatesException',
                ),
            ),
        ),
        'CreateEnvironment' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EnvironmentDescription',
            'responseType' => 'model',
            'summary' => 'Launches an environment for the specified application using the specified configuration.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateEnvironment',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application that contains the version to be deployed.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'description' => 'The name of the application version to deploy.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentName' => array(
                    'required' => true,
                    'description' => 'A unique name for the deployment environment. Used in the application URL.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'TemplateName' => array(
                    'description' => 'The name of the configuration template to use in deployment. If no configuration template is found with this name, AWS Elastic Beanstalk returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'SolutionStackName' => array(
                    'description' => 'This is an alternative to specifying a configuration name. If specified, AWS Elastic Beanstalk sets the configuration values to the default values associated with the specified solution stack.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 100,
                ),
                'CNAMEPrefix' => array(
                    'description' => 'If specified, the environment attempts to use this value as the prefix for the CNAME. If not specified, the environment uses the environment name.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 63,
                ),
                'Description' => array(
                    'description' => 'Describes this environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
                'OptionSettings' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk sets the specified configuration options to the requested value in the configuration set for the new environment. These override the values obtained from the solution stack or the configuration template.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionSettings.member',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'OptionsToRemove' => array(
                    'description' => 'A list of custom user-defined configuration options to remove from the configuration set for this new environment.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionsToRemove.member',
                    'items' => array(
                        'name' => 'OptionSpecification',
                        'description' => 'A specification identifying an individual configuration option.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The caller has exceeded the limit of allowed environments associated with the account.',
                    'class' => 'TooManyEnvironmentsException',
                ),
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'CreateStorageLocation' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'CreateStorageLocationResultMessage',
            'responseType' => 'model',
            'summary' => 'Creates the Amazon S3 storage location for the account.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'CreateStorageLocation',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The web service attempted to create a bucket in an Amazon S3 account that already has 100 buckets.',
                    'class' => 'TooManyBucketsException',
                ),
                array(
                    'reason' => 'The caller does not have a subscription to Amazon S3.',
                    'class' => 'S3SubscriptionRequiredException',
                ),
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'DeleteApplication' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified application along with all associated versions and configurations. The application versions will not be deleted from your Amazon S3 bucket.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteApplication',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TerminateEnvByForce' => array(
                    'description' => 'When set to true, running environments will be terminated before deleting the application.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because another operation is already in progress affecting an an element in this activity.',
                    'class' => 'OperationInProgressException',
                ),
            ),
        ),
        'DeleteApplicationVersion' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified version from the specified application.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteApplicationVersion',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application to delete releases from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'required' => true,
                    'description' => 'The label of the version to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'DeleteSourceBundle' => array(
                    'description' => 'Indicates whether to delete the associated source bundle from Amazon S3:',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to delete the Amazon S3 source bundle associated with the application version, although the application version deleted successfully.',
                    'class' => 'SourceBundleDeletionException',
                ),
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
                array(
                    'reason' => 'Unable to perform the specified operation because another operation is already in progress affecting an an element in this activity.',
                    'class' => 'OperationInProgressException',
                ),
                array(
                    'reason' => 'The specified S3 bucket does not belong to the S3 region in which the service is running.',
                    'class' => 'S3LocationNotInServiceRegionException',
                ),
            ),
        ),
        'DeleteConfigurationTemplate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the specified configuration template.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteConfigurationTemplate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application to delete the configuration template from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'required' => true,
                    'description' => 'The name of the configuration template to delete.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because another operation is already in progress affecting an an element in this activity.',
                    'class' => 'OperationInProgressException',
                ),
            ),
        ),
        'DeleteEnvironmentConfiguration' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the draft configuration associated with the running environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteEnvironmentConfiguration',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application the environment is associated with.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentName' => array(
                    'required' => true,
                    'description' => 'The name of the environment to delete the draft configuration from.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
        ),
        'DescribeApplicationVersions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationVersionDescriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns descriptions for existing application versions.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeApplicationVersions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to only include ones that are associated with the specified application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabels' => array(
                    'description' => 'If specified, restricts the returned descriptions to only include ones that have the specified version labels.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'VersionLabels.member',
                    'items' => array(
                        'name' => 'VersionLabel',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 100,
                    ),
                ),
            ),
        ),
        'DescribeApplications' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationDescriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns the descriptions of existing applications.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeApplications',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationNames' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to only include those with the specified names.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'ApplicationNames.member',
                    'items' => array(
                        'name' => 'ApplicationName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 100,
                    ),
                ),
            ),
        ),
        'DescribeConfigurationOptions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigurationOptionsDescription',
            'responseType' => 'model',
            'summary' => 'Describes the configuration options that are used in a particular configuration template or environment, or that a specified solution stack defines. The description includes the values the options, their default values, and an indication of the required action on a running environment if an option value is changed.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeConfigurationOptions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'description' => 'The name of the application associated with the configuration template or environment. Only needed if you want to describe the configuration options associated with either the configuration template or environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'description' => 'The name of the configuration template whose configuration options you want to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment whose configuration options you want to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'SolutionStackName' => array(
                    'description' => 'The name of the solution stack whose configuration options you want to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 100,
                ),
                'Options' => array(
                    'description' => 'If specified, restricts the descriptions to only the specified options.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Options.member',
                    'items' => array(
                        'name' => 'OptionSpecification',
                        'description' => 'A specification identifying an individual configuration option.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'DescribeConfigurationSettings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigurationSettingsDescriptions',
            'responseType' => 'model',
            'summary' => 'Returns a description of the settings for the specified configuration set, that is, either a configuration template or the configuration set associated with a running environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeConfigurationSettings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The application for the environment or configuration template.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'description' => 'The name of the configuration template to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to describe.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
        ),
        'DescribeEnvironmentResources' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EnvironmentResourceDescriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns AWS resources for this environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEnvironmentResources',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment to retrieve AWS resource usage data.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to retrieve AWS resource usage data.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'DescribeEnvironments' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EnvironmentDescriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns descriptions for existing environments.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEnvironments',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to include only those that are associated with this application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to include only those that are associated with this application version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentIds' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to include only those that have the specified IDs.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'EnvironmentIds.member',
                    'items' => array(
                        'name' => 'EnvironmentId',
                        'type' => 'string',
                    ),
                ),
                'EnvironmentNames' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to include only those that have the specified names.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'EnvironmentNames.member',
                    'items' => array(
                        'name' => 'EnvironmentName',
                        'type' => 'string',
                        'minLength' => 4,
                        'maxLength' => 23,
                    ),
                ),
                'IncludeDeleted' => array(
                    'description' => 'Indicates whether to include deleted environments:',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'IncludedDeletedBackTo' => array(
                    'description' => 'If specified when IncludeDeleted is set to true, then environments deleted after this date are displayed.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'DescribeEvents' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EventDescriptionsMessage',
            'responseType' => 'model',
            'summary' => 'Returns list of event descriptions matching criteria up to the last 6 weeks.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeEvents',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to include only those associated with this application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those associated with this application version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those that are associated with this environment configuration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentId' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those associated with this environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those associated with this environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'RequestId' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the described events to include only those associated with this request ID.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'Severity' => array(
                    'description' => 'If specified, limits the events returned from this call to include only those with the specified severity or higher.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'TRACE',
                        'DEBUG',
                        'INFO',
                        'WARN',
                        'ERROR',
                        'FATAL',
                    ),
                ),
                'StartTime' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those that occur on or after this time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk restricts the returned descriptions to those that occur up to, but not including, the EndTime.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'Specifies the maximum number of events that can be returned, beginning with the most recent event.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 1000,
                ),
                'NextToken' => array(
                    'description' => 'Pagination token. If specified, the events return the next batch of results.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
        ),
        'ListAvailableSolutionStacks' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListAvailableSolutionStacksResultMessage',
            'responseType' => 'model',
            'summary' => 'Returns a list of the available solution stack names.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListAvailableSolutionStacks',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
            ),
        ),
        'RebuildEnvironment' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes and recreates all of the AWS resources (for example: the Auto Scaling group, load balancer, etc.) for a specified environment and forces a restart.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RebuildEnvironment',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment to rebuild.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to rebuild.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'RequestEnvironmentInfo' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Initiates a request to compile the specified type of information of the deployed environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RequestEnvironmentInfo',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment of the requested data.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment of the requested data.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'InfoType' => array(
                    'required' => true,
                    'description' => 'The type of information to request.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'tail',
                    ),
                ),
            ),
        ),
        'RestartAppServer' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Causes the environment to restart the application container server running on each Amazon EC2 instance.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RestartAppServer',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment to restart the server for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to restart the server for.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
        ),
        'RetrieveEnvironmentInfo' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'RetrieveEnvironmentInfoResultMessage',
            'responseType' => 'model',
            'summary' => 'Retrieves the compiled information from a RequestEnvironmentInfo request.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'RetrieveEnvironmentInfo',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the data\'s environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the data\'s environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'InfoType' => array(
                    'required' => true,
                    'description' => 'The type of information to retrieve.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'tail',
                    ),
                ),
            ),
        ),
        'SwapEnvironmentCNAMEs' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Swaps the CNAMEs of two environments.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SwapEnvironmentCNAMEs',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'SourceEnvironmentId' => array(
                    'description' => 'The ID of the source environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'SourceEnvironmentName' => array(
                    'description' => 'The name of the source environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'DestinationEnvironmentId' => array(
                    'description' => 'The ID of the destination environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'DestinationEnvironmentName' => array(
                    'description' => 'The name of the destination environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
            ),
        ),
        'TerminateEnvironment' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EnvironmentDescription',
            'responseType' => 'model',
            'summary' => 'Terminates the specified environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'TerminateEnvironment',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment to terminate.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to terminate.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'TerminateResources' => array(
                    'description' => 'Indicates whether the associated AWS resources should shut down when the environment is terminated:',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'UpdateApplication' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationDescriptionMessage',
            'responseType' => 'model',
            'summary' => 'Updates the specified application to have the specified properties.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateApplication',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application to update. If no such application is found, UpdateApplication returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'A new description for the application.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
            ),
        ),
        'UpdateApplicationVersion' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ApplicationVersionDescriptionMessage',
            'responseType' => 'model',
            'summary' => 'Updates the specified application version to have the specified properties.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateApplicationVersion',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application associated with this version.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'VersionLabel' => array(
                    'required' => true,
                    'description' => 'The name of the version to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'A new description for this release.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
            ),
        ),
        'UpdateConfigurationTemplate' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigurationSettingsDescription',
            'responseType' => 'model',
            'summary' => 'Updates the specified configuration template to have the specified properties or configuration option values.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateConfigurationTemplate',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application associated with the configuration template to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'required' => true,
                    'description' => 'The name of the configuration template to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'A new description for the configuration.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
                'OptionSettings' => array(
                    'description' => 'A list of configuration option settings to update with the new specified option value.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionSettings.member',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'OptionsToRemove' => array(
                    'description' => 'A list of configuration options to remove from the configuration set.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionsToRemove.member',
                    'items' => array(
                        'name' => 'OptionSpecification',
                        'description' => 'A specification identifying an individual configuration option.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'UpdateEnvironment' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EnvironmentDescription',
            'responseType' => 'model',
            'summary' => 'Updates the environment description, deploys a new application version, updates the configuration settings to an entirely new configuration template, or updates select configuration option values in the running environment.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'UpdateEnvironment',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of the environment to update.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to update. If no environment with this name exists, AWS Elastic Beanstalk returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'VersionLabel' => array(
                    'description' => 'If this parameter is specified, AWS Elastic Beanstalk deploys the named application version to the environment. If no such application version is found, returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'description' => 'If this parameter is specified, AWS Elastic Beanstalk deploys this configuration template to the environment. If no such configuration template is found, AWS Elastic Beanstalk returns an InvalidParameterValue error.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'Description' => array(
                    'description' => 'If this parameter is specified, AWS Elastic Beanstalk updates the description of this environment.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 200,
                ),
                'OptionSettings' => array(
                    'description' => 'If specified, AWS Elastic Beanstalk updates the configuration set associated with the running environment and sets the specified configuration options to the requested value.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionSettings.member',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'OptionsToRemove' => array(
                    'description' => 'A list of custom user-defined configuration options to remove from the configuration set for this environment.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionsToRemove.member',
                    'items' => array(
                        'name' => 'OptionSpecification',
                        'description' => 'A specification identifying an individual configuration option.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
        'ValidateConfigurationSettings' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ConfigurationSettingsValidationMessages',
            'responseType' => 'model',
            'summary' => 'Takes a set of configuration settings and either a configuration template or environment, and determines whether those values are valid.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ValidateConfigurationSettings',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-12-01',
                ),
                'ApplicationName' => array(
                    'required' => true,
                    'description' => 'The name of the application that the configuration template or environment belongs to.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'TemplateName' => array(
                    'description' => 'The name of the configuration template to validate the settings against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 100,
                ),
                'EnvironmentName' => array(
                    'description' => 'The name of the environment to validate the settings against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 4,
                    'maxLength' => 23,
                ),
                'OptionSettings' => array(
                    'required' => true,
                    'description' => 'A list of the options and desired values to evaluate.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OptionSettings.member',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Unable to perform the specified operation because the user does not have enough privileges for one of more downstream aws services',
                    'class' => 'InsufficientPrivilegesException',
                ),
            ),
        ),
    ),
    'models' => array(
        'CheckDNSAvailabilityResultMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Available' => array(
                    'description' => 'Indicates if the specified CNAME is available:',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'FullyQualifiedCNAME' => array(
                    'description' => 'The fully qualified CNAME to reserve when CreateEnvironment is called with the provided prefix.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ApplicationDescriptionMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Application' => array(
                    'description' => 'The ApplicationDescription of the application.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ApplicationName' => array(
                            'description' => 'The name of the application.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'User-defined description of the application.',
                            'type' => 'string',
                        ),
                        'DateCreated' => array(
                            'description' => 'The date when the application was created.',
                            'type' => 'string',
                        ),
                        'DateUpdated' => array(
                            'description' => 'The date when the application was last modified.',
                            'type' => 'string',
                        ),
                        'Versions' => array(
                            'description' => 'The names of the versions for this application.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'VersionLabel',
                                'type' => 'string',
                                'sentAs' => 'member',
                            ),
                        ),
                        'ConfigurationTemplates' => array(
                            'description' => 'The names of the configuration templates associated with this application.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'ConfigurationTemplateName',
                                'type' => 'string',
                                'sentAs' => 'member',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ApplicationVersionDescriptionMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ApplicationVersion' => array(
                    'description' => 'The ApplicationVersionDescription of the application version.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ApplicationName' => array(
                            'description' => 'The name of the application associated with this release.',
                            'type' => 'string',
                        ),
                        'Description' => array(
                            'description' => 'The description of this application version.',
                            'type' => 'string',
                        ),
                        'VersionLabel' => array(
                            'description' => 'A label uniquely identifying the version for the associated application.',
                            'type' => 'string',
                        ),
                        'SourceBundle' => array(
                            'description' => 'The location where the source bundle is located for this version.',
                            'type' => 'object',
                            'properties' => array(
                                'S3Bucket' => array(
                                    'description' => 'The Amazon S3 bucket where the data is located.',
                                    'type' => 'string',
                                ),
                                'S3Key' => array(
                                    'description' => 'The Amazon S3 key where the data is located.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'DateCreated' => array(
                            'description' => 'The creation date of the application version.',
                            'type' => 'string',
                        ),
                        'DateUpdated' => array(
                            'description' => 'The last modified date of the application version.',
                            'type' => 'string',
                        ),
                    ),
                ),
            ),
        ),
        'ConfigurationSettingsDescription' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SolutionStackName' => array(
                    'description' => 'The name of the solution stack this configuration set uses.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ApplicationName' => array(
                    'description' => 'The name of the application associated with this configuration set.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'TemplateName' => array(
                    'description' => 'If not null, the name of the configuration template for this configuration set.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Description' => array(
                    'description' => 'Describes this configuration set.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'EnvironmentName' => array(
                    'description' => 'If not null, the name of the environment for this configuration set.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DeploymentStatus' => array(
                    'description' => 'If this configuration set is associated with an environment, the DeploymentStatus parameter indicates the deployment status of this configuration set:',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DateCreated' => array(
                    'description' => 'The date (in UTC time) when this configuration set was created.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DateUpdated' => array(
                    'description' => 'The date (in UTC time) when this configuration set was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'OptionSettings' => array(
                    'description' => 'A list of the configuration options and their values in this configuration set.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ConfigurationOptionSetting',
                        'description' => 'A specification identifying an individual configuration option along with its current value.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'OptionName' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'The current value for the configuration option.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'EnvironmentDescription' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EnvironmentName' => array(
                    'description' => 'The name of this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'EnvironmentId' => array(
                    'description' => 'The ID of this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ApplicationName' => array(
                    'description' => 'The name of the application associated with this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'VersionLabel' => array(
                    'description' => 'The application version deployed in this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'SolutionStackName' => array(
                    'description' => 'The name of the SolutionStack deployed with this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'TemplateName' => array(
                    'description' => 'The name of the configuration template used to originally launch this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Description' => array(
                    'description' => 'Describes this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'EndpointURL' => array(
                    'description' => 'The URL to the LoadBalancer for this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CNAME' => array(
                    'description' => 'The URL to the CNAME for this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DateCreated' => array(
                    'description' => 'The creation date for this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DateUpdated' => array(
                    'description' => 'The last modified date for this environment.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The current operational status of the environment:',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Health' => array(
                    'description' => 'Describes the health status of the environment. AWS Elastic Beanstalk indicates the failure levels for a running environment:',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Resources' => array(
                    'description' => 'The description of the AWS resources used by this environment.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'LoadBalancer' => array(
                            'description' => 'Describes the LoadBalancer.',
                            'type' => 'object',
                            'properties' => array(
                                'LoadBalancerName' => array(
                                    'description' => 'The name of the LoadBalancer.',
                                    'type' => 'string',
                                ),
                                'Domain' => array(
                                    'description' => 'The domain name of the LoadBalancer.',
                                    'type' => 'string',
                                ),
                                'Listeners' => array(
                                    'description' => 'A list of Listeners used by the LoadBalancer.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Listener',
                                        'description' => 'Describes the properties of a Listener for the LoadBalancer.',
                                        'type' => 'object',
                                        'sentAs' => 'member',
                                        'properties' => array(
                                            'Protocol' => array(
                                                'description' => 'The protocol that is used by the Listener.',
                                                'type' => 'string',
                                            ),
                                            'Port' => array(
                                                'description' => 'The port that is used by the Listener.',
                                                'type' => 'numeric',
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
        'CreateStorageLocationResultMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'S3Bucket' => array(
                    'description' => 'The name of the Amazon S3 bucket created.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'ApplicationVersionDescriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ApplicationVersions' => array(
                    'description' => 'A list of ApplicationVersionDescription .',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ApplicationVersionDescription',
                        'description' => 'Describes the properties of an application version.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ApplicationName' => array(
                                'description' => 'The name of the application associated with this release.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'The description of this application version.',
                                'type' => 'string',
                            ),
                            'VersionLabel' => array(
                                'description' => 'A label uniquely identifying the version for the associated application.',
                                'type' => 'string',
                            ),
                            'SourceBundle' => array(
                                'description' => 'The location where the source bundle is located for this version.',
                                'type' => 'object',
                                'properties' => array(
                                    'S3Bucket' => array(
                                        'description' => 'The Amazon S3 bucket where the data is located.',
                                        'type' => 'string',
                                    ),
                                    'S3Key' => array(
                                        'description' => 'The Amazon S3 key where the data is located.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'DateCreated' => array(
                                'description' => 'The creation date of the application version.',
                                'type' => 'string',
                            ),
                            'DateUpdated' => array(
                                'description' => 'The last modified date of the application version.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ApplicationDescriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Applications' => array(
                    'description' => 'This parameter contains a list of ApplicationDescription.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ApplicationDescription',
                        'description' => 'Describes the properties of an application.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'ApplicationName' => array(
                                'description' => 'The name of the application.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'User-defined description of the application.',
                                'type' => 'string',
                            ),
                            'DateCreated' => array(
                                'description' => 'The date when the application was created.',
                                'type' => 'string',
                            ),
                            'DateUpdated' => array(
                                'description' => 'The date when the application was last modified.',
                                'type' => 'string',
                            ),
                            'Versions' => array(
                                'description' => 'The names of the versions for this application.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'VersionLabel',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'ConfigurationTemplates' => array(
                                'description' => 'The names of the configuration templates associated with this application.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ConfigurationTemplateName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ConfigurationOptionsDescription' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SolutionStackName' => array(
                    'description' => 'The name of the solution stack these configuration options belong to.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Options' => array(
                    'description' => 'A list of ConfigurationOptionDescription.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ConfigurationOptionDescription',
                        'description' => 'Describes the possible values for a configuration option.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                'type' => 'string',
                            ),
                            'Name' => array(
                                'description' => 'The name of the configuration option.',
                                'type' => 'string',
                            ),
                            'DefaultValue' => array(
                                'description' => 'The default value for this configuration option.',
                                'type' => 'string',
                            ),
                            'ChangeSeverity' => array(
                                'description' => 'An indication of which action is required if the value for this configuration option changes:',
                                'type' => 'string',
                            ),
                            'UserDefined' => array(
                                'description' => 'An indication of whether the user defined this configuration option:',
                                'type' => 'boolean',
                            ),
                            'ValueType' => array(
                                'description' => 'An indication of which type of values this option has and whether it is allowable to select one or more than one of the possible values:',
                                'type' => 'string',
                            ),
                            'ValueOptions' => array(
                                'description' => 'If specified, values for the configuration option are selected from this list.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ConfigurationOptionPossibleValue',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'MinValue' => array(
                                'description' => 'If specified, the configuration option must be a numeric value greater than this value.',
                                'type' => 'numeric',
                            ),
                            'MaxValue' => array(
                                'description' => 'If specified, the configuration option must be a numeric value less than this value.',
                                'type' => 'numeric',
                            ),
                            'MaxLength' => array(
                                'description' => 'If specified, the configuration option must be a string value no longer than this value.',
                                'type' => 'numeric',
                            ),
                            'Regex' => array(
                                'description' => 'If specified, the configuration option must be a string value that satisfies this regular expression.',
                                'type' => 'object',
                                'properties' => array(
                                    'Pattern' => array(
                                        'description' => 'The regular expression pattern that a string configuration option value with this restriction must match.',
                                        'type' => 'string',
                                    ),
                                    'Label' => array(
                                        'description' => 'A unique name representing this regular expression.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ConfigurationSettingsDescriptions' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ConfigurationSettings' => array(
                    'description' => 'A list of ConfigurationSettingsDescription.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ConfigurationSettingsDescription',
                        'description' => 'Describes the settings for a configuration set.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'SolutionStackName' => array(
                                'description' => 'The name of the solution stack this configuration set uses.',
                                'type' => 'string',
                            ),
                            'ApplicationName' => array(
                                'description' => 'The name of the application associated with this configuration set.',
                                'type' => 'string',
                            ),
                            'TemplateName' => array(
                                'description' => 'If not null, the name of the configuration template for this configuration set.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Describes this configuration set.',
                                'type' => 'string',
                            ),
                            'EnvironmentName' => array(
                                'description' => 'If not null, the name of the environment for this configuration set.',
                                'type' => 'string',
                            ),
                            'DeploymentStatus' => array(
                                'description' => 'If this configuration set is associated with an environment, the DeploymentStatus parameter indicates the deployment status of this configuration set:',
                                'type' => 'string',
                            ),
                            'DateCreated' => array(
                                'description' => 'The date (in UTC time) when this configuration set was created.',
                                'type' => 'string',
                            ),
                            'DateUpdated' => array(
                                'description' => 'The date (in UTC time) when this configuration set was last modified.',
                                'type' => 'string',
                            ),
                            'OptionSettings' => array(
                                'description' => 'A list of the configuration options and their values in this configuration set.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ConfigurationOptionSetting',
                                    'description' => 'A specification identifying an individual configuration option along with its current value.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Namespace' => array(
                                            'description' => 'A unique namespace identifying the option\'s associated AWS resource.',
                                            'type' => 'string',
                                        ),
                                        'OptionName' => array(
                                            'description' => 'The name of the configuration option.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The current value for the configuration option.',
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
        'EnvironmentResourceDescriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EnvironmentResources' => array(
                    'description' => 'A list of EnvironmentResourceDescription.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'EnvironmentName' => array(
                            'description' => 'The name of the environment.',
                            'type' => 'string',
                        ),
                        'AutoScalingGroups' => array(
                            'description' => 'The AutoScalingGroups used by this environment.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AutoScalingGroup',
                                'description' => 'Describes an Auto Scaling launch configuration.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'The name of the AutoScalingGroup .',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Instances' => array(
                            'description' => 'The Amazon EC2 instances used by this environment.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Instance',
                                'description' => 'The description of an Amazon EC2 instance.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'The ID of the Amazon EC2 instance.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'LaunchConfigurations' => array(
                            'description' => 'The Auto Scaling launch configurations in use by this environment.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LaunchConfiguration',
                                'description' => 'Describes an Auto Scaling launch configuration.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'The name of the launch configuration.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'LoadBalancers' => array(
                            'description' => 'The LoadBalancers in use by this environment.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'LoadBalancer',
                                'description' => 'Describes a LoadBalancer.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'The name of the LoadBalancer.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'Triggers' => array(
                            'description' => 'The AutoScaling triggers in use by this environment.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Trigger',
                                'description' => 'Describes a trigger.',
                                'type' => 'object',
                                'sentAs' => 'member',
                                'properties' => array(
                                    'Name' => array(
                                        'description' => 'The name of the trigger.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'EnvironmentDescriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Environments' => array(
                    'description' => 'Returns an EnvironmentDescription list.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'EnvironmentDescription',
                        'description' => 'Describes the properties of an environment.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'EnvironmentName' => array(
                                'description' => 'The name of this environment.',
                                'type' => 'string',
                            ),
                            'EnvironmentId' => array(
                                'description' => 'The ID of this environment.',
                                'type' => 'string',
                            ),
                            'ApplicationName' => array(
                                'description' => 'The name of the application associated with this environment.',
                                'type' => 'string',
                            ),
                            'VersionLabel' => array(
                                'description' => 'The application version deployed in this environment.',
                                'type' => 'string',
                            ),
                            'SolutionStackName' => array(
                                'description' => 'The name of the SolutionStack deployed with this environment.',
                                'type' => 'string',
                            ),
                            'TemplateName' => array(
                                'description' => 'The name of the configuration template used to originally launch this environment.',
                                'type' => 'string',
                            ),
                            'Description' => array(
                                'description' => 'Describes this environment.',
                                'type' => 'string',
                            ),
                            'EndpointURL' => array(
                                'description' => 'The URL to the LoadBalancer for this environment.',
                                'type' => 'string',
                            ),
                            'CNAME' => array(
                                'description' => 'The URL to the CNAME for this environment.',
                                'type' => 'string',
                            ),
                            'DateCreated' => array(
                                'description' => 'The creation date for this environment.',
                                'type' => 'string',
                            ),
                            'DateUpdated' => array(
                                'description' => 'The last modified date for this environment.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The current operational status of the environment:',
                                'type' => 'string',
                            ),
                            'Health' => array(
                                'description' => 'Describes the health status of the environment. AWS Elastic Beanstalk indicates the failure levels for a running environment:',
                                'type' => 'string',
                            ),
                            'Resources' => array(
                                'description' => 'The description of the AWS resources used by this environment.',
                                'type' => 'object',
                                'properties' => array(
                                    'LoadBalancer' => array(
                                        'description' => 'Describes the LoadBalancer.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'LoadBalancerName' => array(
                                                'description' => 'The name of the LoadBalancer.',
                                                'type' => 'string',
                                            ),
                                            'Domain' => array(
                                                'description' => 'The domain name of the LoadBalancer.',
                                                'type' => 'string',
                                            ),
                                            'Listeners' => array(
                                                'description' => 'A list of Listeners used by the LoadBalancer.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'Listener',
                                                    'description' => 'Describes the properties of a Listener for the LoadBalancer.',
                                                    'type' => 'object',
                                                    'sentAs' => 'member',
                                                    'properties' => array(
                                                        'Protocol' => array(
                                                            'description' => 'The protocol that is used by the Listener.',
                                                            'type' => 'string',
                                                        ),
                                                        'Port' => array(
                                                            'description' => 'The port that is used by the Listener.',
                                                            'type' => 'numeric',
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
            ),
        ),
        'EventDescriptionsMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Events' => array(
                    'description' => 'A list of EventDescription.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'EventDescription',
                        'description' => 'Describes an event.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'EventDate' => array(
                                'description' => 'The date when the event occurred.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'The event message.',
                                'type' => 'string',
                            ),
                            'ApplicationName' => array(
                                'description' => 'The application associated with the event.',
                                'type' => 'string',
                            ),
                            'VersionLabel' => array(
                                'description' => 'The release label for the application version associated with this event.',
                                'type' => 'string',
                            ),
                            'TemplateName' => array(
                                'description' => 'The name of the configuration associated with this event.',
                                'type' => 'string',
                            ),
                            'EnvironmentName' => array(
                                'description' => 'The name of the environment associated with this event.',
                                'type' => 'string',
                            ),
                            'RequestId' => array(
                                'description' => 'The web service request ID for the activity of this event.',
                                'type' => 'string',
                            ),
                            'Severity' => array(
                                'description' => 'The severity level of this event.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'If returned, this indicates that there are more results to obtain. Use this token in the next DescribeEvents call to get the next batch of events.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
            ),
        ),
        'ListAvailableSolutionStacksResultMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'SolutionStacks' => array(
                    'description' => 'A list of available solution stacks.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SolutionStackName',
                        'type' => 'string',
                        'sentAs' => 'member',
                    ),
                ),
                'SolutionStackDetails' => array(
                    'description' => 'A list of available solution stacks and their SolutionStackDescription.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'SolutionStackDescription',
                        'description' => 'Describes the solution stack.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'SolutionStackName' => array(
                                'description' => 'The name of the solution stack.',
                                'type' => 'string',
                            ),
                            'PermittedFileTypes' => array(
                                'description' => 'The permitted file types allowed for a solution stack.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'FileTypeExtension',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'RetrieveEnvironmentInfoResultMessage' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'EnvironmentInfo' => array(
                    'description' => 'The EnvironmentInfoDescription of the environment.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'EnvironmentInfoDescription',
                        'description' => 'The information retrieved from the Amazon EC2 instances.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'InfoType' => array(
                                'description' => 'The type of information retrieved.',
                                'type' => 'string',
                            ),
                            'Ec2InstanceId' => array(
                                'description' => 'The Amazon EC2 Instance ID for this information.',
                                'type' => 'string',
                            ),
                            'SampleTimestamp' => array(
                                'description' => 'The time stamp when this information was retrieved.',
                                'type' => 'string',
                            ),
                            'Message' => array(
                                'description' => 'The retrieved information.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ConfigurationSettingsValidationMessages' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Messages' => array(
                    'description' => 'A list of ValidationMessage.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'ValidationMessage',
                        'description' => 'An error or warning for a desired configuration option value.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Message' => array(
                                'description' => 'A message describing the error or warning.',
                                'type' => 'string',
                            ),
                            'Severity' => array(
                                'description' => 'An indication of the severity of this message:',
                                'type' => 'string',
                            ),
                            'Namespace' => array(
                                'type' => 'string',
                            ),
                            'OptionName' => array(
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
            'DescribeApplicationVersions' => array(
                'result_key' => 'ApplicationVersions',
            ),
            'DescribeApplications' => array(
                'result_key' => 'Applications',
            ),
            'DescribeConfigurationOptions' => array(
                'result_key' => 'Options',
            ),
            'DescribeEnvironments' => array(
                'result_key' => 'Environments',
            ),
            'DescribeEvents' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'Events',
            ),
            'ListAvailableSolutionStacks' => array(
                'result_key' => 'SolutionStacks',
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'interval' => 20,
            'max_attempts' => 40,
            'acceptor.type' => 'output',
        ),
        '__EnvironmentState' => array(
            'operation' => 'DescribeEnvironments',
            'acceptor.path' => 'Environments/*/Status',
        ),
        'EnvironmentReady' => array(
            'extends' => '__EnvironmentState',
            'success.value' => 'Ready',
            'failure.value' => array(
                'Terminated',
                'Terminating',
            ),
        ),
        'EnvironmentTerminated' => array(
            'extends' => '__EnvironmentState',
            'success.value' => 'Terminated',
            'failure.value' => array(
                'Launching',
                'Updating',
            ),
        ),
    ),
);
