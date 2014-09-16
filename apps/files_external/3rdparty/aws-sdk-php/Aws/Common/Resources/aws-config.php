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

return array(
    'class' => 'Aws\Common\Aws',
    'services' => array(

        'default_settings' => array(
            'params' => array()
        ),

        'autoscaling' => array(
            'alias'   => 'AutoScaling',
            'extends' => 'default_settings',
            'class'   => 'Aws\AutoScaling\AutoScalingClient'
        ),

        'cloudformation' => array(
            'alias'   => 'CloudFormation',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudFormation\CloudFormationClient'
        ),

        'cloudfront' => array(
            'alias'   => 'CloudFront',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudFront\CloudFrontClient'
        ),

        'cloudfront_20120505' => array(
            'extends' => 'cloudfront',
            'params' => array(
                'version' => '2012-05-05'
            )
        ),

        'cloudsearch' => array(
            'alias'   => 'CloudSearch',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudSearch\CloudSearchClient'
        ),

        'cloudsearch_20110201' => array(
            'extends' => 'cloudsearch',
            'params' => array(
                'version' => '2011-02-01'
            )
        ),

        'cloudsearchdomain' => array(
            'alias'   => 'CloudSearchDomain',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudSearchDomain\CloudSearchDomainClient'
        ),

        'cloudtrail' => array(
            'alias'   => 'CloudTrail',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudTrail\CloudTrailClient'
        ),

        'cloudwatch' => array(
            'alias'   => 'CloudWatch',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudWatch\CloudWatchClient'
        ),

        'cognito-identity' => array(
            'alias'   => 'CognitoIdentity',
            'extends' => 'default_settings',
            'class'   => 'Aws\CognitoIdentity\CognitoIdentityClient'
        ),

        'cognitoidentity' => array('extends' => 'cognito-identity'),

        'cognito-sync' => array(
            'alias'   => 'CognitoSync',
            'extends' => 'default_settings',
            'class'   => 'Aws\CognitoSync\CognitoSyncClient'
        ),

        'cognitosync' => array('extends' => 'cognito-sync'),

        'cloudwatchlogs' => array(
            'alias'   => 'CloudWatchLogs',
            'extends' => 'default_settings',
            'class'   => 'Aws\CloudWatchLogs\CloudWatchLogsClient'
        ),

        'datapipeline' => array(
            'alias'   => 'DataPipeline',
            'extends' => 'default_settings',
            'class'   => 'Aws\DataPipeline\DataPipelineClient'
        ),

        'directconnect' => array(
            'alias'   => 'DirectConnect',
            'extends' => 'default_settings',
            'class'   => 'Aws\DirectConnect\DirectConnectClient'
        ),

        'dynamodb' => array(
            'alias'   => 'DynamoDb',
            'extends' => 'default_settings',
            'class'   => 'Aws\DynamoDb\DynamoDbClient'
        ),

        'dynamodb_20111205' => array(
            'extends' => 'dynamodb',
            'params' => array(
                'version' => '2011-12-05'
            )
        ),

        'ec2' => array(
            'alias'   => 'Ec2',
            'extends' => 'default_settings',
            'class'   => 'Aws\Ec2\Ec2Client'
        ),

        'elasticache' => array(
            'alias'   => 'ElastiCache',
            'extends' => 'default_settings',
            'class'   => 'Aws\ElastiCache\ElastiCacheClient'
        ),

        'elasticbeanstalk' => array(
            'alias'   => 'ElasticBeanstalk',
            'extends' => 'default_settings',
            'class'   => 'Aws\ElasticBeanstalk\ElasticBeanstalkClient'
        ),

        'elasticloadbalancing' => array(
            'alias'   => 'ElasticLoadBalancing',
            'extends' => 'default_settings',
            'class'   => 'Aws\ElasticLoadBalancing\ElasticLoadBalancingClient'
        ),

        'elastictranscoder' => array(
            'alias'   => 'ElasticTranscoder',
            'extends' => 'default_settings',
            'class'   => 'Aws\ElasticTranscoder\ElasticTranscoderClient'
        ),

        'emr' => array(
            'alias'   => 'Emr',
            'extends' => 'default_settings',
            'class'   => 'Aws\Emr\EmrClient'
        ),

        'glacier' => array(
            'alias'   => 'Glacier',
            'extends' => 'default_settings',
            'class'   => 'Aws\Glacier\GlacierClient'
        ),

        'kinesis' => array(
            'alias'   => 'Kinesis',
            'extends' => 'default_settings',
            'class'   => 'Aws\Kinesis\KinesisClient'
        ),

        'iam' => array(
            'alias'   => 'Iam',
            'extends' => 'default_settings',
            'class'   => 'Aws\Iam\IamClient'
        ),

        'importexport' => array(
            'alias'   => 'ImportExport',
            'extends' => 'default_settings',
            'class'   => 'Aws\ImportExport\ImportExportClient'
        ),

        'opsworks' => array(
            'alias'   => 'OpsWorks',
            'extends' => 'default_settings',
            'class'   => 'Aws\OpsWorks\OpsWorksClient'
        ),

        'rds' => array(
            'alias'   => 'Rds',
            'extends' => 'default_settings',
            'class'   => 'Aws\Rds\RdsClient'
        ),

        'redshift' => array(
            'alias'   => 'Redshift',
            'extends' => 'default_settings',
            'class'   => 'Aws\Redshift\RedshiftClient'
        ),

        'route53' => array(
            'alias'   => 'Route53',
            'extends' => 'default_settings',
            'class'   => 'Aws\Route53\Route53Client'
        ),

        'route53domains' => array(
            'alias'   => 'Route53Domains',
            'extends' => 'default_settings',
            'class'   => 'Aws\Route53Domains\Route53DomainsClient'
        ),

        's3' => array(
            'alias'   => 'S3',
            'extends' => 'default_settings',
            'class'   => 'Aws\S3\S3Client'
        ),

        'sdb' => array(
            'alias'   => 'SimpleDb',
            'extends' => 'default_settings',
            'class'   => 'Aws\SimpleDb\SimpleDbClient'
        ),

        'ses' => array(
            'alias'   => 'Ses',
            'extends' => 'default_settings',
            'class'   => 'Aws\Ses\SesClient'
        ),

        'sns' => array(
            'alias'   => 'Sns',
            'extends' => 'default_settings',
            'class'   => 'Aws\Sns\SnsClient'
        ),

        'sqs' => array(
            'alias'   => 'Sqs',
            'extends' => 'default_settings',
            'class'   => 'Aws\Sqs\SqsClient'
        ),

        'storagegateway' => array(
            'alias'   => 'StorageGateway',
            'extends' => 'default_settings',
            'class'   => 'Aws\StorageGateway\StorageGatewayClient'
        ),

        'sts' => array(
            'alias'   => 'Sts',
            'extends' => 'default_settings',
            'class'   => 'Aws\Sts\StsClient'
        ),

        'support' => array(
            'alias'   => 'Support',
            'extends' => 'default_settings',
            'class'   => 'Aws\Support\SupportClient'
        ),

        'swf' => array(
            'alias'   => 'Swf',
            'extends' => 'default_settings',
            'class'   => 'Aws\Swf\SwfClient'
        ),
    )
);
