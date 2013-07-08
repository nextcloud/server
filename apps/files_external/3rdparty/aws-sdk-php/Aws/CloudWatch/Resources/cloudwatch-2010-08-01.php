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
    'apiVersion' => '2010-08-01',
    'endpointPrefix' => 'monitoring',
    'serviceFullName' => 'Amazon CloudWatch',
    'serviceAbbreviation' => 'CloudWatch',
    'serviceType' => 'query',
    'timestampFormat' => 'iso8601',
    'resultWrapped' => true,
    'signatureVersion' => 'v2',
    'namespace' => 'CloudWatch',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.us-east-1.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'monitoring.sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => false,
            'https' => true,
            'hostname' => 'monitoring.us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'DeleteAlarms' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes all specified alarms. In the event of an error, no alarms are deleted.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DeleteAlarms',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmNames' => array(
                    'required' => true,
                    'description' => 'A list of alarms to be deleted.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AlarmNames.member',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'AlarmName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The named resource does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
            ),
        ),
        'DescribeAlarmHistory' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAlarmHistoryOutput',
            'responseType' => 'model',
            'summary' => 'Retrieves history for the specified alarm. Filter alarms by date range or item type. If an alarm name is not specified, Amazon CloudWatch returns histories for all of the owner\'s alarms.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAlarmHistory',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmName' => array(
                    'description' => 'The name of the alarm.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'HistoryItemType' => array(
                    'description' => 'The type of alarm histories to retrieve.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'ConfigurationUpdate',
                        'StateUpdate',
                        'Action',
                    ),
                ),
                'StartDate' => array(
                    'description' => 'The starting date to retrieve alarm history.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'EndDate' => array(
                    'description' => 'The ending date to retrieve alarm history.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of alarm history records to retrieve.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'NextToken' => array(
                    'description' => 'The token returned by a previous call to indicate that there is more data available.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The next token specified is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeAlarms' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAlarmsOutput',
            'responseType' => 'model',
            'summary' => 'Retrieves alarms with the specified names. If no name is specified, all alarms for the user are returned. Alarms can be retrieved by using only a prefix for the alarm name, the alarm state, or a prefix for any action.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAlarms',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmNames' => array(
                    'description' => 'A list of alarm names to retrieve information for.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AlarmNames.member',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'AlarmName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
                'AlarmNamePrefix' => array(
                    'description' => 'The alarm name prefix. AlarmNames cannot be specified if this parameter is specified.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'StateValue' => array(
                    'description' => 'The state value to be used in matching alarms.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'OK',
                        'ALARM',
                        'INSUFFICIENT_DATA',
                    ),
                ),
                'ActionPrefix' => array(
                    'description' => 'The action name prefix.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 1024,
                ),
                'MaxRecords' => array(
                    'description' => 'The maximum number of alarm descriptions to retrieve.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                    'maximum' => 100,
                ),
                'NextToken' => array(
                    'description' => 'The token returned by a previous call to indicate that there is more data available.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The next token specified is invalid.',
                    'class' => 'InvalidNextTokenException',
                ),
            ),
        ),
        'DescribeAlarmsForMetric' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'DescribeAlarmsForMetricOutput',
            'responseType' => 'model',
            'summary' => 'Retrieves all alarms for a single metric. Specify a statistic, period, or unit to filter the set of alarms further.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DescribeAlarmsForMetric',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'MetricName' => array(
                    'required' => true,
                    'description' => 'The name of the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Namespace' => array(
                    'required' => true,
                    'description' => 'The namespace of the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Statistic' => array(
                    'description' => 'The statistic for the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'SampleCount',
                        'Average',
                        'Sum',
                        'Minimum',
                        'Maximum',
                    ),
                ),
                'Dimensions' => array(
                    'description' => 'The list of dimensions associated with the metric.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Dimensions.member',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'Dimension',
                        'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the dimension.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'The value representing the dimension measurement',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                        ),
                    ),
                ),
                'Period' => array(
                    'description' => 'The period in seconds over which the statistic is applied.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 60,
                ),
                'Unit' => array(
                    'description' => 'The unit for the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Seconds',
                        'Microseconds',
                        'Milliseconds',
                        'Bytes',
                        'Kilobytes',
                        'Megabytes',
                        'Gigabytes',
                        'Terabytes',
                        'Bits',
                        'Kilobits',
                        'Megabits',
                        'Gigabits',
                        'Terabits',
                        'Percent',
                        'Count',
                        'Bytes/Second',
                        'Kilobytes/Second',
                        'Megabytes/Second',
                        'Gigabytes/Second',
                        'Terabytes/Second',
                        'Bits/Second',
                        'Kilobits/Second',
                        'Megabits/Second',
                        'Gigabits/Second',
                        'Terabits/Second',
                        'Count/Second',
                        'None',
                    ),
                ),
            ),
        ),
        'DisableAlarmActions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Disables actions for the specified alarms. When an alarm\'s actions are disabled the alarm\'s state may change, but none of the alarm\'s actions will execute.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'DisableAlarmActions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmNames' => array(
                    'required' => true,
                    'description' => 'The names of the alarms to disable actions for.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AlarmNames.member',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'AlarmName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
        ),
        'EnableAlarmActions' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Enables actions for the specified alarms.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'EnableAlarmActions',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmNames' => array(
                    'required' => true,
                    'description' => 'The names of the alarms to enable actions for.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AlarmNames.member',
                    'maxItems' => 100,
                    'items' => array(
                        'name' => 'AlarmName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 255,
                    ),
                ),
            ),
        ),
        'GetMetricStatistics' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'GetMetricStatisticsOutput',
            'responseType' => 'model',
            'summary' => 'Gets statistics for the specified metric.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'GetMetricStatistics',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'Namespace' => array(
                    'required' => true,
                    'description' => 'The namespace of the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'MetricName' => array(
                    'required' => true,
                    'description' => 'The name of the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Dimensions' => array(
                    'description' => 'A list of dimensions describing qualities of the metric.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Dimensions.member',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'Dimension',
                        'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the dimension.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'The value representing the dimension measurement',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                        ),
                    ),
                ),
                'StartTime' => array(
                    'required' => true,
                    'description' => 'The time stamp to use for determining the first datapoint to return. The value specified is inclusive; results include datapoints with the time stamp specified.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'EndTime' => array(
                    'required' => true,
                    'description' => 'The time stamp to use for determining the last datapoint to return. The value specified is exclusive; results will include datapoints up to the time stamp specified.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time',
                    'location' => 'aws.query',
                ),
                'Period' => array(
                    'required' => true,
                    'description' => 'The granularity, in seconds, of the returned datapoints. Period must be at least 60 seconds and must be a multiple of 60. The default value is 60.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 60,
                ),
                'Statistics' => array(
                    'required' => true,
                    'description' => 'The metric statistics to return.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Statistics.member',
                    'minItems' => 1,
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'Statistic',
                        'type' => 'string',
                        'enum' => array(
                            'SampleCount',
                            'Average',
                            'Sum',
                            'Minimum',
                            'Maximum',
                        ),
                    ),
                ),
                'Unit' => array(
                    'description' => 'The unit for the metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Seconds',
                        'Microseconds',
                        'Milliseconds',
                        'Bytes',
                        'Kilobytes',
                        'Megabytes',
                        'Gigabytes',
                        'Terabytes',
                        'Bits',
                        'Kilobits',
                        'Megabits',
                        'Gigabits',
                        'Terabits',
                        'Percent',
                        'Count',
                        'Bytes/Second',
                        'Kilobytes/Second',
                        'Megabytes/Second',
                        'Gigabytes/Second',
                        'Terabytes/Second',
                        'Bits/Second',
                        'Kilobits/Second',
                        'Megabits/Second',
                        'Gigabits/Second',
                        'Terabits/Second',
                        'Count/Second',
                        'None',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Bad or out-of-range value was supplied for the input parameter.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'An input parameter that is mandatory for processing the request is not supplied.',
                    'class' => 'MissingRequiredParameterException',
                ),
                array(
                    'reason' => 'Parameters that must not be used together were used together.',
                    'class' => 'InvalidParameterCombinationException',
                ),
                array(
                    'reason' => 'Indicates that the request processing has failed due to some unknown error, exception, or failure.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'ListMetrics' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'ListMetricsOutput',
            'responseType' => 'model',
            'summary' => 'Returns a list of valid metrics stored for the AWS account owner. Returned metrics can be used with GetMetricStatistics to obtain statistical data for a given metric.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'ListMetrics',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'Namespace' => array(
                    'description' => 'The namespace to filter against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'MetricName' => array(
                    'description' => 'The name of the metric to filter against.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Dimensions' => array(
                    'description' => 'A list of dimensions to filter against.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Dimensions.member',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'DimensionFilter',
                        'description' => 'The DimensionFilter data type is used to filter ListMetrics results.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The dimension name to be matched.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Value' => array(
                                'description' => 'The value of the dimension to be matched.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                        ),
                    ),
                ),
                'NextToken' => array(
                    'description' => 'The token returned by a previous call to indicate that there is more data available.',
                    'type' => 'string',
                    'location' => 'aws.query',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Indicates that the request processing has failed due to some unknown error, exception, or failure.',
                    'class' => 'InternalServiceException',
                ),
                array(
                    'reason' => 'Bad or out-of-range value was supplied for the input parameter.',
                    'class' => 'InvalidParameterValueException',
                ),
            ),
        ),
        'PutMetricAlarm' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Creates or updates an alarm and associates it with the specified Amazon CloudWatch metric. Optionally, this operation can associate one or more Amazon Simple Notification Service resources with the alarm.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutMetricAlarm',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmName' => array(
                    'required' => true,
                    'description' => 'The descriptive name for the alarm. This name must be unique within the user\'s AWS account',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'AlarmDescription' => array(
                    'description' => 'The description for the alarm.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 255,
                ),
                'ActionsEnabled' => array(
                    'description' => 'Indicates whether or not actions should be executed during any changes to the alarm\'s state.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'aws.query',
                ),
                'OKActions' => array(
                    'description' => 'The list of actions to execute when this alarm transitions into an OK state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only action supported is publishing to an Amazon SNS topic or an Amazon Auto Scaling policy.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'OKActions.member',
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
                'AlarmActions' => array(
                    'description' => 'The list of actions to execute when this alarm transitions into an ALARM state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only action supported is publishing to an Amazon SNS topic or an Amazon Auto Scaling policy.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'AlarmActions.member',
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
                'InsufficientDataActions' => array(
                    'description' => 'The list of actions to execute when this alarm transitions into an INSUFFICIENT_DATA state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only action supported is publishing to an Amazon SNS topic or an Amazon Auto Scaling policy.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'InsufficientDataActions.member',
                    'maxItems' => 5,
                    'items' => array(
                        'name' => 'ResourceName',
                        'type' => 'string',
                        'minLength' => 1,
                        'maxLength' => 1024,
                    ),
                ),
                'MetricName' => array(
                    'required' => true,
                    'description' => 'The name for the alarm\'s associated metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Namespace' => array(
                    'required' => true,
                    'description' => 'The namespace for the alarm\'s associated metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'Statistic' => array(
                    'required' => true,
                    'description' => 'The statistic to apply to the alarm\'s associated metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'SampleCount',
                        'Average',
                        'Sum',
                        'Minimum',
                        'Maximum',
                    ),
                ),
                'Dimensions' => array(
                    'description' => 'The dimensions for the alarm\'s associated metric.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'Dimensions.member',
                    'maxItems' => 10,
                    'items' => array(
                        'name' => 'Dimension',
                        'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                        'type' => 'object',
                        'properties' => array(
                            'Name' => array(
                                'required' => true,
                                'description' => 'The name of the dimension.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'The value representing the dimension measurement',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                        ),
                    ),
                ),
                'Period' => array(
                    'required' => true,
                    'description' => 'The period in seconds over which the specified statistic is applied.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 60,
                ),
                'Unit' => array(
                    'description' => 'The unit for the alarm\'s associated metric.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'Seconds',
                        'Microseconds',
                        'Milliseconds',
                        'Bytes',
                        'Kilobytes',
                        'Megabytes',
                        'Gigabytes',
                        'Terabytes',
                        'Bits',
                        'Kilobits',
                        'Megabits',
                        'Gigabits',
                        'Terabits',
                        'Percent',
                        'Count',
                        'Bytes/Second',
                        'Kilobytes/Second',
                        'Megabytes/Second',
                        'Gigabytes/Second',
                        'Terabytes/Second',
                        'Bits/Second',
                        'Kilobits/Second',
                        'Megabits/Second',
                        'Gigabits/Second',
                        'Terabits/Second',
                        'Count/Second',
                        'None',
                    ),
                ),
                'EvaluationPeriods' => array(
                    'required' => true,
                    'description' => 'The number of periods over which data is compared to the specified threshold.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                    'minimum' => 1,
                ),
                'Threshold' => array(
                    'required' => true,
                    'description' => 'The value against which the specified statistic is compared.',
                    'type' => 'numeric',
                    'location' => 'aws.query',
                ),
                'ComparisonOperator' => array(
                    'required' => true,
                    'description' => 'The arithmetic operation to use when comparing the specified Statistic and Threshold. The specified Statistic value is used as the first operand.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'GreaterThanOrEqualToThreshold',
                        'GreaterThanThreshold',
                        'LessThanThreshold',
                        'LessThanOrEqualToThreshold',
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The quota for alarms for this customer has already been reached.',
                    'class' => 'LimitExceededException',
                ),
            ),
        ),
        'PutMetricData' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Publishes metric data points to Amazon CloudWatch. Amazon Cloudwatch associates the data points with the specified metric. If the specified metric does not exist, Amazon CloudWatch creates the metric.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'PutMetricData',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'Namespace' => array(
                    'required' => true,
                    'description' => 'The namespace for the metric data.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'MetricData' => array(
                    'required' => true,
                    'description' => 'A list of data describing the metric.',
                    'type' => 'array',
                    'location' => 'aws.query',
                    'sentAs' => 'MetricData.member',
                    'items' => array(
                        'name' => 'MetricDatum',
                        'description' => 'The MetricDatum data type encapsulates the information sent with PutMetricData to either create a new metric or add new values to be aggregated into an existing metric.',
                        'type' => 'object',
                        'properties' => array(
                            'MetricName' => array(
                                'required' => true,
                                'description' => 'The name of the metric.',
                                'type' => 'string',
                                'minLength' => 1,
                                'maxLength' => 255,
                            ),
                            'Dimensions' => array(
                                'description' => 'A list of dimensions associated with the metric.',
                                'type' => 'array',
                                'sentAs' => 'Dimensions.member',
                                'maxItems' => 10,
                                'items' => array(
                                    'name' => 'Dimension',
                                    'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Name' => array(
                                            'required' => true,
                                            'description' => 'The name of the dimension.',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 255,
                                        ),
                                        'Value' => array(
                                            'required' => true,
                                            'description' => 'The value representing the dimension measurement',
                                            'type' => 'string',
                                            'minLength' => 1,
                                            'maxLength' => 255,
                                        ),
                                    ),
                                ),
                            ),
                            'Timestamp' => array(
                                'description' => 'The time stamp used for the metric. If not specified, the default value is set to the time the metric data was received.',
                                'type' => array(
                                    'object',
                                    'string',
                                    'integer',
                                ),
                                'format' => 'date-time',
                            ),
                            'Value' => array(
                                'description' => 'The value for the metric.',
                                'type' => 'numeric',
                            ),
                            'StatisticValues' => array(
                                'description' => 'A set of statistical values describing the metric.',
                                'type' => 'object',
                                'properties' => array(
                                    'SampleCount' => array(
                                        'required' => true,
                                        'description' => 'The number of samples used for the statistic set.',
                                        'type' => 'numeric',
                                    ),
                                    'Sum' => array(
                                        'required' => true,
                                        'description' => 'The sum of values for the sample set.',
                                        'type' => 'numeric',
                                    ),
                                    'Minimum' => array(
                                        'required' => true,
                                        'description' => 'The minimum value of the sample set.',
                                        'type' => 'numeric',
                                    ),
                                    'Maximum' => array(
                                        'required' => true,
                                        'description' => 'The maximum value of the sample set.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'Unit' => array(
                                'description' => 'The unit of the metric.',
                                'type' => 'string',
                                'enum' => array(
                                    'Seconds',
                                    'Microseconds',
                                    'Milliseconds',
                                    'Bytes',
                                    'Kilobytes',
                                    'Megabytes',
                                    'Gigabytes',
                                    'Terabytes',
                                    'Bits',
                                    'Kilobits',
                                    'Megabits',
                                    'Gigabits',
                                    'Terabits',
                                    'Percent',
                                    'Count',
                                    'Bytes/Second',
                                    'Kilobytes/Second',
                                    'Megabytes/Second',
                                    'Gigabytes/Second',
                                    'Terabytes/Second',
                                    'Bits/Second',
                                    'Kilobits/Second',
                                    'Megabits/Second',
                                    'Gigabits/Second',
                                    'Terabits/Second',
                                    'Count/Second',
                                    'None',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'Bad or out-of-range value was supplied for the input parameter.',
                    'class' => 'InvalidParameterValueException',
                ),
                array(
                    'reason' => 'An input parameter that is mandatory for processing the request is not supplied.',
                    'class' => 'MissingRequiredParameterException',
                ),
                array(
                    'reason' => 'Parameters that must not be used together were used together.',
                    'class' => 'InvalidParameterCombinationException',
                ),
                array(
                    'reason' => 'Indicates that the request processing has failed due to some unknown error, exception, or failure.',
                    'class' => 'InternalServiceException',
                ),
            ),
        ),
        'SetAlarmState' => array(
            'httpMethod' => 'POST',
            'uri' => '/',
            'class' => 'Aws\\Common\\Command\\QueryCommand',
            'responseClass' => 'EmptyOutput',
            'responseType' => 'model',
            'summary' => 'Temporarily sets the state of an alarm. When the updated StateValue differs from the previous value, the action configured for the appropriate state is invoked. This is not a permanent change. The next periodic alarm check (in about a minute) will set the alarm to its actual state.',
            'parameters' => array(
                'Action' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => 'SetAlarmState',
                ),
                'Version' => array(
                    'static' => true,
                    'location' => 'aws.query',
                    'default' => '2010-08-01',
                ),
                'AlarmName' => array(
                    'required' => true,
                    'description' => 'The descriptive name for the alarm. This name must be unique within the user\'s AWS account. The maximum length is 255 characters.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'minLength' => 1,
                    'maxLength' => 255,
                ),
                'StateValue' => array(
                    'required' => true,
                    'description' => 'The value of the state.',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'enum' => array(
                        'OK',
                        'ALARM',
                        'INSUFFICIENT_DATA',
                    ),
                ),
                'StateReason' => array(
                    'required' => true,
                    'description' => 'The reason that this alarm is set to this specific state (in human-readable text format)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 1023,
                ),
                'StateReasonData' => array(
                    'description' => 'The reason that this alarm is set to this specific state (in machine-readable JSON format)',
                    'type' => 'string',
                    'location' => 'aws.query',
                    'maxLength' => 4000,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The named resource does not exist.',
                    'class' => 'ResourceNotFoundException',
                ),
                array(
                    'reason' => 'Data was not syntactically valid JSON.',
                    'class' => 'InvalidFormatException',
                ),
            ),
        ),
    ),
    'models' => array(
        'EmptyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
        ),
        'DescribeAlarmHistoryOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'AlarmHistoryItems' => array(
                    'description' => 'A list of alarm histories in JSON format.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'AlarmHistoryItem',
                        'description' => 'The AlarmHistoryItem data type contains descriptive information about the history of a specific alarm. If you call DescribeAlarmHistory, Amazon CloudWatch returns this data type as part of the DescribeAlarmHistoryResult data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AlarmName' => array(
                                'description' => 'The descriptive name for the alarm.',
                                'type' => 'string',
                            ),
                            'Timestamp' => array(
                                'description' => 'The time stamp for the alarm history item.',
                                'type' => 'string',
                            ),
                            'HistoryItemType' => array(
                                'description' => 'The type of alarm history item.',
                                'type' => 'string',
                            ),
                            'HistorySummary' => array(
                                'description' => 'A human-readable summary of the alarm history.',
                                'type' => 'string',
                            ),
                            'HistoryData' => array(
                                'description' => 'Machine-readable data about the alarm in JSON format.',
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
        'DescribeAlarmsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MetricAlarms' => array(
                    'description' => 'A list of information for the specified alarms.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'MetricAlarm',
                        'description' => 'The MetricAlarm data type represents an alarm. You can use PutMetricAlarm to create or update an alarm.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AlarmName' => array(
                                'description' => 'The name of the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmArn' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmDescription' => array(
                                'description' => 'The description for the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmConfigurationUpdatedTimestamp' => array(
                                'description' => 'The time stamp of the last update to the alarm configuration.',
                                'type' => 'string',
                            ),
                            'ActionsEnabled' => array(
                                'description' => 'Indicates whether actions should be executed during any changes to the alarm\'s state.',
                                'type' => 'boolean',
                            ),
                            'OKActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an OK state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic and triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'AlarmActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an ALARM state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic and triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'InsufficientDataActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an INSUFFICIENT_DATA state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic or triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'StateValue' => array(
                                'description' => 'The state value for the alarm.',
                                'type' => 'string',
                            ),
                            'StateReason' => array(
                                'description' => 'A human-readable explanation for the alarm\'s state.',
                                'type' => 'string',
                            ),
                            'StateReasonData' => array(
                                'description' => 'An explanation for the alarm\'s state in machine-readable JSON format',
                                'type' => 'string',
                            ),
                            'StateUpdatedTimestamp' => array(
                                'description' => 'The time stamp of the last update to the alarm\'s state.',
                                'type' => 'string',
                            ),
                            'MetricName' => array(
                                'description' => 'The name of the alarm\'s metric.',
                                'type' => 'string',
                            ),
                            'Namespace' => array(
                                'description' => 'The namespace of alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'Statistic' => array(
                                'description' => 'The statistic to apply to the alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'Dimensions' => array(
                                'description' => 'The list of dimensions associated with the alarm\'s associated metric.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Dimension',
                                    'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the dimension.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value representing the dimension measurement',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Period' => array(
                                'description' => 'The period in seconds over which the statistic is applied.',
                                'type' => 'numeric',
                            ),
                            'Unit' => array(
                                'description' => 'The unit of the alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'EvaluationPeriods' => array(
                                'description' => 'The number of periods over which data is compared to the specified threshold.',
                                'type' => 'numeric',
                            ),
                            'Threshold' => array(
                                'description' => 'The value against which the specified statistic is compared.',
                                'type' => 'numeric',
                            ),
                            'ComparisonOperator' => array(
                                'description' => 'The arithmetic operation to use when comparing the specified Statistic and Threshold. The specified Statistic value is used as the first operand.',
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
        'DescribeAlarmsForMetricOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'MetricAlarms' => array(
                    'description' => 'A list of information for each alarm with the specified metric.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'MetricAlarm',
                        'description' => 'The MetricAlarm data type represents an alarm. You can use PutMetricAlarm to create or update an alarm.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'AlarmName' => array(
                                'description' => 'The name of the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmArn' => array(
                                'description' => 'The Amazon Resource Name (ARN) of the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmDescription' => array(
                                'description' => 'The description for the alarm.',
                                'type' => 'string',
                            ),
                            'AlarmConfigurationUpdatedTimestamp' => array(
                                'description' => 'The time stamp of the last update to the alarm configuration.',
                                'type' => 'string',
                            ),
                            'ActionsEnabled' => array(
                                'description' => 'Indicates whether actions should be executed during any changes to the alarm\'s state.',
                                'type' => 'boolean',
                            ),
                            'OKActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an OK state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic and triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'AlarmActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an ALARM state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic and triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'InsufficientDataActions' => array(
                                'description' => 'The list of actions to execute when this alarm transitions into an INSUFFICIENT_DATA state from any other state. Each action is specified as an Amazon Resource Number (ARN). Currently the only actions supported are publishing to an Amazon SNS topic or triggering an Auto Scaling policy.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'ResourceName',
                                    'type' => 'string',
                                    'sentAs' => 'member',
                                ),
                            ),
                            'StateValue' => array(
                                'description' => 'The state value for the alarm.',
                                'type' => 'string',
                            ),
                            'StateReason' => array(
                                'description' => 'A human-readable explanation for the alarm\'s state.',
                                'type' => 'string',
                            ),
                            'StateReasonData' => array(
                                'description' => 'An explanation for the alarm\'s state in machine-readable JSON format',
                                'type' => 'string',
                            ),
                            'StateUpdatedTimestamp' => array(
                                'description' => 'The time stamp of the last update to the alarm\'s state.',
                                'type' => 'string',
                            ),
                            'MetricName' => array(
                                'description' => 'The name of the alarm\'s metric.',
                                'type' => 'string',
                            ),
                            'Namespace' => array(
                                'description' => 'The namespace of alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'Statistic' => array(
                                'description' => 'The statistic to apply to the alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'Dimensions' => array(
                                'description' => 'The list of dimensions associated with the alarm\'s associated metric.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Dimension',
                                    'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the dimension.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value representing the dimension measurement',
                                            'type' => 'string',
                                        ),
                                    ),
                                ),
                            ),
                            'Period' => array(
                                'description' => 'The period in seconds over which the statistic is applied.',
                                'type' => 'numeric',
                            ),
                            'Unit' => array(
                                'description' => 'The unit of the alarm\'s associated metric.',
                                'type' => 'string',
                            ),
                            'EvaluationPeriods' => array(
                                'description' => 'The number of periods over which data is compared to the specified threshold.',
                                'type' => 'numeric',
                            ),
                            'Threshold' => array(
                                'description' => 'The value against which the specified statistic is compared.',
                                'type' => 'numeric',
                            ),
                            'ComparisonOperator' => array(
                                'description' => 'The arithmetic operation to use when comparing the specified Statistic and Threshold. The specified Statistic value is used as the first operand.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'GetMetricStatisticsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Label' => array(
                    'description' => 'A label describing the specified metric.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Datapoints' => array(
                    'description' => 'The datapoints for the specified metric.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Datapoint',
                        'description' => 'The Datapoint data type encapsulates the statistical data that Amazon CloudWatch computes from metric data.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Timestamp' => array(
                                'description' => 'The time stamp used for the datapoint.',
                                'type' => 'string',
                            ),
                            'SampleCount' => array(
                                'description' => 'The number of metric values that contributed to the aggregate value of this datapoint.',
                                'type' => 'numeric',
                            ),
                            'Average' => array(
                                'description' => 'The average of metric values that correspond to the datapoint.',
                                'type' => 'numeric',
                            ),
                            'Sum' => array(
                                'description' => 'The sum of metric values used for the datapoint.',
                                'type' => 'numeric',
                            ),
                            'Minimum' => array(
                                'description' => 'The minimum metric value used for the datapoint.',
                                'type' => 'numeric',
                            ),
                            'Maximum' => array(
                                'description' => 'The maximum of the metric value used for the datapoint.',
                                'type' => 'numeric',
                            ),
                            'Unit' => array(
                                'description' => 'The standard unit used for the datapoint.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'ListMetricsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Metrics' => array(
                    'description' => 'A list of metrics used to generate statistics for an AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Metric',
                        'description' => 'The Metric data type contains information about a specific metric. If you call ListMetrics, Amazon CloudWatch returns information contained by this data type.',
                        'type' => 'object',
                        'sentAs' => 'member',
                        'properties' => array(
                            'Namespace' => array(
                                'description' => 'The namespace of the metric.',
                                'type' => 'string',
                            ),
                            'MetricName' => array(
                                'description' => 'The name of the metric.',
                                'type' => 'string',
                            ),
                            'Dimensions' => array(
                                'description' => 'A list of dimensions associated with the metric.',
                                'type' => 'array',
                                'items' => array(
                                    'name' => 'Dimension',
                                    'description' => 'The Dimension data type further expands on the identity of a metric using a Name, Value pair.',
                                    'type' => 'object',
                                    'sentAs' => 'member',
                                    'properties' => array(
                                        'Name' => array(
                                            'description' => 'The name of the dimension.',
                                            'type' => 'string',
                                        ),
                                        'Value' => array(
                                            'description' => 'The value representing the dimension measurement',
                                            'type' => 'string',
                                        ),
                                    ),
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
    ),
    'iterators' => array(
        'operations' => array(
            'DescribeAlarmHistory' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'AlarmHistoryItems',
            ),
            'DescribeAlarms' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'limit_key' => 'MaxRecords',
                'result_key' => 'MetricAlarms',
            ),
            'DescribeAlarmsForMetric' => array(
                'result_key' => 'MetricAlarms',
            ),
            'ListMetrics' => array(
                'token_param' => 'NextToken',
                'token_key' => 'NextToken',
                'result_key' => 'Metrics',
            ),
        ),
    ),
);
