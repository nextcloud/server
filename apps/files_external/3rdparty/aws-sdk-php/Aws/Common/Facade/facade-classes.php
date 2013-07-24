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

namespace Aws\Common\Facade;

/**
 * The following classes are used to implement the static client facades and are aliased into the global namespaced. We
 * discourage the use of these classes directly by their full namespace since they are not autoloaded and are considered
 * an implementation detail that could possibly be changed in the future.
 */

// @codeCoverageIgnoreStart

class AutoScaling extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'autoscaling';
    }
}

class CloudFormation extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'cloudformation';
    }
}

class CloudFront extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'cloudfront';
    }
}

class CloudSearch extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'cloudsearch';
    }
}

class CloudWatch extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'cloudwatch';
    }
}

class DataPipeline extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'datapipeline';
    }
}

class DirectConnect extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'directconnect';
    }
}

class DynamoDb extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'dynamodb';
    }
}

class Ec2 extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'ec2';
    }
}

class ElastiCache extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'elasticache';
    }
}

class ElasticBeanstalk extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'elasticbeanstalk';
    }
}

class ElasticLoadBalancing extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'elasticloadbalancing';
    }
}

class ElasticTranscoder extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'elastictranscoder';
    }
}

class Emr extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'emr';
    }
}

class Glacier extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'glacier';
    }
}

class Iam extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'iam';
    }
}

class ImportExport extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'importexport';
    }
}

class OpsWorks extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'opsworks';
    }
}

class Rds extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'rds';
    }
}

class Redshift extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'redshift';
    }
}

class Route53 extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'route53';
    }
}

class S3 extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 's3';
    }
}

class SimpleDb extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'sdb';
    }
}

class Ses extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'ses';
    }
}

class Sns extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'sns';
    }
}

class Sqs extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'sqs';
    }
}

class StorageGateway extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'storagegateway';
    }
}

class Sts extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'sts';
    }
}

class Support extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'support';
    }
}

class Swf extends Facade
{
    public static function getServiceBuilderKey()
    {
        return 'swf';
    }
}

// @codeCoverageIgnoreEnd
