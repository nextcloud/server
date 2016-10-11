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
    'includes' => array('_aws'),
    'services' => array(

        'sdk1_settings' => array(
            'extends' => 'default_settings',
            'params'  => array(
                'certificate_authority' => false
            )
        ),

        'v1.autoscaling' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonAS'
        ),

        'v1.cloudformation' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonCloudFormation'
        ),

        'v1.cloudfront' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonCloudFront'
        ),

        'v1.cloudsearch' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonCloudSearch'
        ),

        'v1.cloudwatch' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonCloudWatch'
        ),

        'v1.dynamodb' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonDynamoDB'
        ),

        'v1.ec2' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonEC2'
        ),

        'v1.elasticache' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonElastiCache'
        ),

        'v1.elasticbeanstalk' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonElasticBeanstalk'
        ),

        'v1.elb' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonELB'
        ),

        'v1.emr' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonEMR'
        ),

        'v1.iam' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonIAM'
        ),

        'v1.importexport'     => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonImportExport'
        ),

        'v1.rds' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonRDS'
        ),

        'v1.s3'  => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonS3'
        ),

        'v1.sdb' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSDB'
        ),

        'v1.ses' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSES'
        ),

        'v1.sns' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSNS'
        ),

        'v1.sqs' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSQS'
        ),

        'v1.storagegateway'   => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonStorageGateway'
        ),

        'v1.sts' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSTS'
        ),

        'v1.swf' => array(
            'extends' => 'sdk1_settings',
            'class'   => 'AmazonSWF'
        )
    )
);
