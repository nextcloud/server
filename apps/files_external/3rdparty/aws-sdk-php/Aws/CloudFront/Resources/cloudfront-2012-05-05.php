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
    'apiVersion' => '2012-05-05',
    'endpointPrefix' => 'cloudfront',
    'serviceFullName' => 'Amazon CloudFront',
    'serviceAbbreviation' => 'CloudFront',
    'serviceType' => 'rest-xml',
    'globalEndpoint' => 'cloudfront.amazonaws.com',
    'signatureVersion' => 'cloudfront',
    'namespace' => 'CloudFront',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 'cloudfront.amazonaws.com',
        ),
    ),
    'operations' => array(
        'CreateCloudFrontOriginAccessIdentity' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateCloudFrontOriginAccessIdentityResult',
            'responseType' => 'model',
            'summary' => 'Create a new origin access identity.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CloudFrontOriginAccessIdentityConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the origin access identity.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'CloudFrontOriginAccessIdentityAlreadyExistsException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'TooManyCloudFrontOriginAccessIdentitiesException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'CreateDistribution' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-05-05/distribution',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateDistributionResult',
            'responseType' => 'model',
            'summary' => 'Create a new distribution.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'DistributionConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Aliases' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'DefaultRootObject' => array(
                    'required' => true,
                    'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Origins' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about origins for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of origins for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains origins for this distribution.',
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => array(
                                'name' => 'Origin',
                                'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'required' => true,
                                        'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'DomainName' => array(
                                        'required' => true,
                                        'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                        'type' => 'string',
                                    ),
                                    'S3OriginConfig' => array(
                                        'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'OriginAccessIdentity' => array(
                                                'required' => true,
                                                'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'CustomOriginConfig' => array(
                                        'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'HTTPPort' => array(
                                                'required' => true,
                                                'description' => 'The HTTP port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'HTTPSPort' => array(
                                                'required' => true,
                                                'description' => 'The HTTPS port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'OriginProtocolPolicy' => array(
                                                'required' => true,
                                                'description' => 'The origin protocol policy to apply to your origin.',
                                                'type' => 'string',
                                                'enum' => array(
                                                    'http-only',
                                                    'match-viewer',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DefaultCacheBehavior' => array(
                    'required' => true,
                    'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'TargetOriginId' => array(
                            'required' => true,
                            'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                            'type' => 'string',
                        ),
                        'ForwardedValues' => array(
                            'required' => true,
                            'description' => 'A complex type that specifies how CloudFront handles query strings.',
                            'type' => 'object',
                            'properties' => array(
                                'QueryString' => array(
                                    'required' => true,
                                    'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'required' => true,
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'required' => true,
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'Quantity' => array(
                                    'required' => true,
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'ViewerProtocolPolicy' => array(
                            'required' => true,
                            'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                            'type' => 'string',
                            'enum' => array(
                                'allow-all',
                                'https-only',
                            ),
                        ),
                        'MinTTL' => array(
                            'required' => true,
                            'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'CacheBehaviors' => array(
                    'required' => true,
                    'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of cache behaviors for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheBehavior',
                                'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'PathPattern' => array(
                                        'required' => true,
                                        'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'TargetOriginId' => array(
                                        'required' => true,
                                        'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'ForwardedValues' => array(
                                        'required' => true,
                                        'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'QueryString' => array(
                                                'required' => true,
                                                'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                        ),
                                    ),
                                    'TrustedSigners' => array(
                                        'required' => true,
                                        'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Enabled' => array(
                                                'required' => true,
                                                'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                            'Quantity' => array(
                                                'required' => true,
                                                'description' => 'The number of trusted signers for this cache behavior.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AwsAccountNumber',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'ViewerProtocolPolicy' => array(
                                        'required' => true,
                                        'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'allow-all',
                                            'https-only',
                                        ),
                                    ),
                                    'MinTTL' => array(
                                        'required' => true,
                                        'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'required' => true,
                    'description' => 'A complex type that controls whether access logs are written for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'required' => true,
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Enabled' => array(
                    'required' => true,
                    'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'xml',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'CNAMEAlreadyExistsException',
                ),
                array(
                    'class' => 'DistributionAlreadyExistsException',
                ),
                array(
                    'class' => 'InvalidOriginException',
                ),
                array(
                    'class' => 'InvalidOriginAccessIdentityException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'TooManyTrustedSignersException',
                ),
                array(
                    'class' => 'TrustedSignerDoesNotExistException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'TooManyDistributionCNAMEsException',
                ),
                array(
                    'class' => 'TooManyDistributionsException',
                ),
                array(
                    'class' => 'InvalidDefaultRootObjectException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InvalidRequiredProtocolException',
                ),
                array(
                    'class' => 'NoSuchOriginException',
                ),
                array(
                    'class' => 'TooManyOriginsException',
                ),
                array(
                    'class' => 'TooManyCacheBehaviorsException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'CreateInvalidation' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-05-05/distribution/{DistributionId}/invalidation',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateInvalidationResult',
            'responseType' => 'model',
            'summary' => 'Create a new invalidation.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'InvalidationBatch',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'DistributionId' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Paths' => array(
                    'required' => true,
                    'description' => 'The path of the object to invalidate. The path is relative to the distribution and must begin with a slash (/). You must enclose each invalidation object with the Path element tags. If the path includes non-ASCII characters or unsafe characters as defined in RFC 1783 (http://www.ietf.org/rfc/rfc1738.txt), URL encode those characters. Do not URL encode any other characters in the path, or CloudFront will not invalidate the old version of the updated object.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of objects that you want to invalidate.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains a list of the objects that you want to invalidate.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Path',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique name that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the Path object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create an invalidation batch, and the content of each Path element is identical to the original request, the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of any Path is different from the original request, CloudFront returns an InvalidationBatchAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'BatchTooLargeException',
                ),
                array(
                    'class' => 'TooManyInvalidationsInProgressException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'CreateStreamingDistribution' => array(
            'httpMethod' => 'POST',
            'uri' => '/2012-05-05/streaming-distribution',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'CreateStreamingDistributionResult',
            'responseType' => 'model',
            'summary' => 'Create a new streaming distribution.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'StreamingDistributionConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3Origin' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DomainName' => array(
                            'required' => true,
                            'description' => 'The DNS name of the S3 origin.',
                            'type' => 'string',
                        ),
                        'OriginAccessIdentity' => array(
                            'required' => true,
                            'description' => 'Your S3 origin\'s origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Aliases' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the streaming distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'required' => true,
                    'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'required' => true,
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'TrustedSigners' => array(
                    'required' => true,
                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of trusted signers for this cache behavior.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AwsAccountNumber',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Enabled' => array(
                    'required' => true,
                    'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'xml',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'CNAMEAlreadyExistsException',
                ),
                array(
                    'class' => 'StreamingDistributionAlreadyExistsException',
                ),
                array(
                    'class' => 'InvalidOriginException',
                ),
                array(
                    'class' => 'InvalidOriginAccessIdentityException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'TooManyTrustedSignersException',
                ),
                array(
                    'class' => 'TrustedSignerDoesNotExistException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'TooManyStreamingDistributionCNAMEsException',
                ),
                array(
                    'class' => 'TooManyStreamingDistributionsException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'DeleteCloudFrontOriginAccessIdentity' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DeleteCloudFrontOriginAccessIdentity2012_05_05Output',
            'responseType' => 'model',
            'summary' => 'Delete an origin access identity.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The origin access identity\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received from a previous GET or PUT request. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'NoSuchCloudFrontOriginAccessIdentityException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
                array(
                    'class' => 'CloudFrontOriginAccessIdentityInUseException',
                ),
            ),
        ),
        'DeleteDistribution' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-05-05/distribution/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DeleteDistribution2012_05_05Output',
            'responseType' => 'model',
            'summary' => 'Delete a distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The distribution id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received when you disabled the distribution. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'DistributionNotDisabledException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
            ),
        ),
        'DeleteStreamingDistribution' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/2012-05-05/streaming-distribution/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'DeleteStreamingDistribution2012_05_05Output',
            'responseType' => 'model',
            'summary' => 'Delete a streaming distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The distribution id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received when you disabled the streaming distribution. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'StreamingDistributionNotDisabledException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'NoSuchStreamingDistributionException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
            ),
        ),
        'GetCloudFrontOriginAccessIdentity' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetCloudFrontOriginAccessIdentityResult',
            'responseType' => 'model',
            'summary' => 'Get the information about an origin access identity.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identity\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchCloudFrontOriginAccessIdentityException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetCloudFrontOriginAccessIdentityConfig' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetCloudFrontOriginAccessIdentityConfigResult',
            'responseType' => 'model',
            'summary' => 'Get the configuration information about an origin access identity.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The identity\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchCloudFrontOriginAccessIdentityException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetDistribution' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/distribution/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetDistributionResult',
            'responseType' => 'model',
            'summary' => 'Get the information about a distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetDistributionConfig' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/distribution/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetDistributionConfigResult',
            'responseType' => 'model',
            'summary' => 'Get the configuration information about a distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetInvalidation' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/distribution/{DistributionId}/invalidation/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetInvalidationResult',
            'responseType' => 'model',
            'summary' => 'Get the information about an invalidation.',
            'parameters' => array(
                'DistributionId' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Id' => array(
                    'required' => true,
                    'description' => 'The invalidation\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchInvalidationException',
                ),
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetStreamingDistribution' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/streaming-distribution/{Id}',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetStreamingDistributionResult',
            'responseType' => 'model',
            'summary' => 'Get the information about a streaming distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The streaming distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchStreamingDistributionException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'GetStreamingDistributionConfig' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/streaming-distribution/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'GetStreamingDistributionConfigResult',
            'responseType' => 'model',
            'summary' => 'Get the configuration information about a streaming distribution.',
            'parameters' => array(
                'Id' => array(
                    'required' => true,
                    'description' => 'The streaming distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'NoSuchStreamingDistributionException',
                ),
                array(
                    'class' => 'AccessDeniedException',
                ),
            ),
        ),
        'ListCloudFrontOriginAccessIdentities' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListCloudFrontOriginAccessIdentitiesResult',
            'responseType' => 'model',
            'summary' => 'List origin access identities.',
            'parameters' => array(
                'Marker' => array(
                    'description' => 'Use this when paginating results to indicate where to begin in your list of origin access identities. The results include identities in the list that occur after the marker. To get the next page of results, set the Marker to the value of the NextMarker from the current page\'s response (which is also the ID of the last identity on that page).',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of origin access identities you want in the response body.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidArgumentException',
                ),
            ),
        ),
        'ListDistributions' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/distribution',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListDistributionsResult',
            'responseType' => 'model',
            'summary' => 'List distributions.',
            'parameters' => array(
                'Marker' => array(
                    'description' => 'Use this when paginating results to indicate where to begin in your list of distributions. The results include distributions in the list that occur after the marker. To get the next page of results, set the Marker to the value of the NextMarker from the current page\'s response (which is also the ID of the last distribution on that page).',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of distributions you want in the response body.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidArgumentException',
                ),
            ),
        ),
        'ListInvalidations' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/distribution/{DistributionId}/invalidation',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListInvalidationsResult',
            'responseType' => 'model',
            'summary' => 'List invalidation batches.',
            'parameters' => array(
                'DistributionId' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Marker' => array(
                    'description' => 'Use this parameter when paginating results to indicate where to begin in your list of invalidation batches. Because the results are returned in decreasing order from most recent to oldest, the most recent results are on the first page, the second page will contain earlier results, and so on. To get the next page of results, set the Marker to the value of the NextMarker from the current page\'s response. This value is the same as the ID of the last invalidation batch on that page.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of invalidation batches you want in the response body.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'NoSuchDistributionException',
                ),
            ),
        ),
        'ListStreamingDistributions' => array(
            'httpMethod' => 'GET',
            'uri' => '/2012-05-05/streaming-distribution',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'ListStreamingDistributionsResult',
            'responseType' => 'model',
            'summary' => 'List streaming distributions.',
            'parameters' => array(
                'Marker' => array(
                    'description' => 'Use this when paginating results to indicate where to begin in your list of streaming distributions. The results include distributions in the list that occur after the marker. To get the next page of results, set the Marker to the value of the NextMarker from the current page\'s response (which is also the ID of the last distribution on that page).',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'MaxItems' => array(
                    'description' => 'The maximum number of streaming distributions you want in the response body.',
                    'type' => 'string',
                    'location' => 'query',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'InvalidArgumentException',
                ),
            ),
        ),
        'UpdateCloudFrontOriginAccessIdentity' => array(
            'httpMethod' => 'PUT',
            'uri' => '/2012-05-05/origin-access-identity/cloudfront/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdateCloudFrontOriginAccessIdentityResult',
            'responseType' => 'model',
            'summary' => 'Update an origin access identity.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CloudFrontOriginAccessIdentityConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the origin access identity.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Id' => array(
                    'required' => true,
                    'description' => 'The identity\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received when retrieving the identity\'s configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'IllegalUpdateException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'NoSuchCloudFrontOriginAccessIdentityException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'UpdateDistribution' => array(
            'httpMethod' => 'PUT',
            'uri' => '/2012-05-05/distribution/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdateDistributionResult',
            'responseType' => 'model',
            'summary' => 'Update a distribution.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'DistributionConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Aliases' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'DefaultRootObject' => array(
                    'required' => true,
                    'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Origins' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about origins for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of origins for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains origins for this distribution.',
                            'type' => 'array',
                            'minItems' => 1,
                            'items' => array(
                                'name' => 'Origin',
                                'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                'type' => 'object',
                                'properties' => array(
                                    'Id' => array(
                                        'required' => true,
                                        'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'DomainName' => array(
                                        'required' => true,
                                        'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                        'type' => 'string',
                                    ),
                                    'S3OriginConfig' => array(
                                        'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'OriginAccessIdentity' => array(
                                                'required' => true,
                                                'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'CustomOriginConfig' => array(
                                        'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'HTTPPort' => array(
                                                'required' => true,
                                                'description' => 'The HTTP port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'HTTPSPort' => array(
                                                'required' => true,
                                                'description' => 'The HTTPS port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'OriginProtocolPolicy' => array(
                                                'required' => true,
                                                'description' => 'The origin protocol policy to apply to your origin.',
                                                'type' => 'string',
                                                'enum' => array(
                                                    'http-only',
                                                    'match-viewer',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DefaultCacheBehavior' => array(
                    'required' => true,
                    'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'TargetOriginId' => array(
                            'required' => true,
                            'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                            'type' => 'string',
                        ),
                        'ForwardedValues' => array(
                            'required' => true,
                            'description' => 'A complex type that specifies how CloudFront handles query strings.',
                            'type' => 'object',
                            'properties' => array(
                                'QueryString' => array(
                                    'required' => true,
                                    'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'required' => true,
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'required' => true,
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                    'format' => 'boolean-string',
                                ),
                                'Quantity' => array(
                                    'required' => true,
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'ViewerProtocolPolicy' => array(
                            'required' => true,
                            'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                            'type' => 'string',
                            'enum' => array(
                                'allow-all',
                                'https-only',
                            ),
                        ),
                        'MinTTL' => array(
                            'required' => true,
                            'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'CacheBehaviors' => array(
                    'required' => true,
                    'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of cache behaviors for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheBehavior',
                                'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'PathPattern' => array(
                                        'required' => true,
                                        'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'TargetOriginId' => array(
                                        'required' => true,
                                        'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'ForwardedValues' => array(
                                        'required' => true,
                                        'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'QueryString' => array(
                                                'required' => true,
                                                'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                        ),
                                    ),
                                    'TrustedSigners' => array(
                                        'required' => true,
                                        'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Enabled' => array(
                                                'required' => true,
                                                'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                'type' => 'boolean',
                                                'format' => 'boolean-string',
                                            ),
                                            'Quantity' => array(
                                                'required' => true,
                                                'description' => 'The number of trusted signers for this cache behavior.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AwsAccountNumber',
                                                    'type' => 'string',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'ViewerProtocolPolicy' => array(
                                        'required' => true,
                                        'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'allow-all',
                                            'https-only',
                                        ),
                                    ),
                                    'MinTTL' => array(
                                        'required' => true,
                                        'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'required' => true,
                    'description' => 'A complex type that controls whether access logs are written for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'required' => true,
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Enabled' => array(
                    'required' => true,
                    'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'xml',
                ),
                'Id' => array(
                    'required' => true,
                    'description' => 'The distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received when retrieving the distribution\'s configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'CNAMEAlreadyExistsException',
                ),
                array(
                    'class' => 'IllegalUpdateException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'NoSuchDistributionException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
                array(
                    'class' => 'TooManyDistributionCNAMEsException',
                ),
                array(
                    'class' => 'InvalidDefaultRootObjectException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InvalidOriginAccessIdentityException',
                ),
                array(
                    'class' => 'TooManyTrustedSignersException',
                ),
                array(
                    'class' => 'TrustedSignerDoesNotExistException',
                ),
                array(
                    'class' => 'InvalidRequiredProtocolException',
                ),
                array(
                    'class' => 'NoSuchOriginException',
                ),
                array(
                    'class' => 'TooManyOriginsException',
                ),
                array(
                    'class' => 'TooManyCacheBehaviorsException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
        'UpdateStreamingDistribution' => array(
            'httpMethod' => 'PUT',
            'uri' => '/2012-05-05/streaming-distribution/{Id}/config',
            'class' => 'Guzzle\\Service\\Command\\OperationCommand',
            'responseClass' => 'UpdateStreamingDistributionResult',
            'responseType' => 'model',
            'summary' => 'Update a streaming distribution.',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'StreamingDistributionConfig',
                    'namespaces' => array(
                        'http://cloudfront.amazonaws.com/doc/2012-05-05/',
                    ),
                ),
            ),
            'parameters' => array(
                'CallerReference' => array(
                    'required' => true,
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3Origin' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DomainName' => array(
                            'required' => true,
                            'description' => 'The DNS name of the S3 origin.',
                            'type' => 'string',
                        ),
                        'OriginAccessIdentity' => array(
                            'required' => true,
                            'description' => 'Your S3 origin\'s origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Aliases' => array(
                    'required' => true,
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'required' => true,
                    'description' => 'Any comments you want to include about the streaming distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'required' => true,
                    'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Bucket' => array(
                            'required' => true,
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'required' => true,
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'TrustedSigners' => array(
                    'required' => true,
                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'required' => true,
                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                            'type' => 'boolean',
                            'format' => 'boolean-string',
                        ),
                        'Quantity' => array(
                            'required' => true,
                            'description' => 'The number of trusted signers for this cache behavior.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AwsAccountNumber',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Enabled' => array(
                    'required' => true,
                    'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'xml',
                ),
                'Id' => array(
                    'required' => true,
                    'description' => 'The streaming distribution\'s id.',
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'The value of the ETag header you received when retrieving the streaming distribution\'s configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'class' => 'AccessDeniedException',
                ),
                array(
                    'class' => 'CNAMEAlreadyExistsException',
                ),
                array(
                    'class' => 'IllegalUpdateException',
                ),
                array(
                    'class' => 'InvalidIfMatchVersionException',
                ),
                array(
                    'class' => 'MissingBodyException',
                ),
                array(
                    'class' => 'NoSuchStreamingDistributionException',
                ),
                array(
                    'class' => 'PreconditionFailedException',
                ),
                array(
                    'class' => 'TooManyStreamingDistributionCNAMEsException',
                ),
                array(
                    'class' => 'InvalidArgumentException',
                ),
                array(
                    'class' => 'InvalidOriginAccessIdentityException',
                ),
                array(
                    'class' => 'TooManyTrustedSignersException',
                ),
                array(
                    'class' => 'TrustedSignerDoesNotExistException',
                ),
                array(
                    'class' => 'InconsistentQuantitiesException',
                ),
            ),
        ),
    ),
    'models' => array(
        'CreateCloudFrontOriginAccessIdentityResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The ID for the origin access identity. For example: E74FTE3AJFJ256A.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3CanonicalUserId' => array(
                    'description' => 'The Amazon S3 canonical user ID for the origin access identity, which you use when giving the origin access identity read permission to an object in Amazon S3.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CloudFrontOriginAccessIdentityConfig' => array(
                    'description' => 'The current configuration information for the identity.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Location' => array(
                    'description' => 'The fully qualified URI of the new origin access identity just created. For example: https://cloudfront.amazonaws.com/2010-11-01/origin-access-identity/cloudfront/E74FTE3AJFJ256A.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'ETag' => array(
                    'description' => 'The current version of the origin access identity created.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the distribution. For example: EDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'This response element indicates the current status of the distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InProgressInvalidationBatches' => array(
                    'description' => 'The number of invalidation batches currently in progress.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the distribution. For example: d604721fxaaqy9.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DistributionConfig' => array(
                    'description' => 'The current configuration information for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'DefaultRootObject' => array(
                            'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                            'type' => 'string',
                        ),
                        'Origins' => array(
                            'description' => 'A complex type that contains information about origins for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of origins for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'A complex type that contains origins for this distribution.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Origin',
                                        'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                        'type' => 'object',
                                        'sentAs' => 'Origin',
                                        'properties' => array(
                                            'Id' => array(
                                                'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'DomainName' => array(
                                                'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                                'type' => 'string',
                                            ),
                                            'S3OriginConfig' => array(
                                                'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'OriginAccessIdentity' => array(
                                                        'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'CustomOriginConfig' => array(
                                                'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'HTTPPort' => array(
                                                        'description' => 'The HTTP port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'HTTPSPort' => array(
                                                        'description' => 'The HTTPS port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'OriginProtocolPolicy' => array(
                                                        'description' => 'The origin protocol policy to apply to your origin.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'DefaultCacheBehavior' => array(
                            'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                            'type' => 'object',
                            'properties' => array(
                                'TargetOriginId' => array(
                                    'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                    'type' => 'string',
                                ),
                                'ForwardedValues' => array(
                                    'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'QueryString' => array(
                                            'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                                'TrustedSigners' => array(
                                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Enabled' => array(
                                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                            'type' => 'boolean',
                                        ),
                                        'Quantity' => array(
                                            'description' => 'The number of trusted signers for this cache behavior.',
                                            'type' => 'numeric',
                                        ),
                                        'Items' => array(
                                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'AwsAccountNumber',
                                                'type' => 'string',
                                                'sentAs' => 'AwsAccountNumber',
                                            ),
                                        ),
                                    ),
                                ),
                                'ViewerProtocolPolicy' => array(
                                    'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                    'type' => 'string',
                                ),
                                'MinTTL' => array(
                                    'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'CacheBehaviors' => array(
                            'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of cache behaviors for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CacheBehavior',
                                        'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'sentAs' => 'CacheBehavior',
                                        'properties' => array(
                                            'PathPattern' => array(
                                                'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'TargetOriginId' => array(
                                                'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'ForwardedValues' => array(
                                                'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'QueryString' => array(
                                                        'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                        'type' => 'boolean',
                                                    ),
                                                ),
                                            ),
                                            'TrustedSigners' => array(
                                                'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Enabled' => array(
                                                        'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                        'type' => 'boolean',
                                                    ),
                                                    'Quantity' => array(
                                                        'description' => 'The number of trusted signers for this cache behavior.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'Items' => array(
                                                        'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'AwsAccountNumber',
                                                            'type' => 'string',
                                                            'sentAs' => 'AwsAccountNumber',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'ViewerProtocolPolicy' => array(
                                                'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                                'type' => 'string',
                                            ),
                                            'MinTTL' => array(
                                                'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'Location' => array(
                    'description' => 'The fully qualified URI of the new distribution resource just created. For example: https://cloudfront.amazonaws.com/2010-11-01/distribution/EDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'ETag' => array(
                    'description' => 'The current version of the distribution created.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateInvalidationResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Location' => array(
                    'description' => 'The fully qualified URI of the distribution and invalidation batch request, including the Invalidation ID.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'Id' => array(
                    'description' => 'The identifier for the invalidation request. For example: IDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The status of the invalidation request. When the invalidation batch is finished, the status is Completed.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CreateTime' => array(
                    'description' => 'The date and time the invalidation request was first made.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InvalidationBatch' => array(
                    'description' => 'The current invalidation information for the batch request.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Paths' => array(
                            'description' => 'The path of the object to invalidate. The path is relative to the distribution and must begin with a slash (/). You must enclose each invalidation object with the Path element tags. If the path includes non-ASCII characters or unsafe characters as defined in RFC 1783 (http://www.ietf.org/rfc/rfc1738.txt), URL encode those characters. Do not URL encode any other characters in the path, or CloudFront will not invalidate the old version of the updated object.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of objects that you want to invalidate.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'A complex type that contains a list of the objects that you want to invalidate.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Path',
                                        'type' => 'string',
                                        'sentAs' => 'Path',
                                    ),
                                ),
                            ),
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique name that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the Path object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create an invalidation batch, and the content of each Path element is identical to the original request, the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of any Path is different from the original request, CloudFront returns an InvalidationBatchAlreadyExists error.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateStreamingDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the streaming distribution. For example: EGTXBD79H29TRA8.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The current status of the streaming distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the streaming distribution. For example: s5c39gqb8ow64r.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'StreamingDistributionConfig' => array(
                    'description' => 'The current configuration information for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'S3Origin' => array(
                            'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'DomainName' => array(
                                    'description' => 'The DNS name of the S3 origin.',
                                    'type' => 'string',
                                ),
                                'OriginAccessIdentity' => array(
                                    'description' => 'Your S3 origin\'s origin access identity.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the streaming distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                ),
                                'Quantity' => array(
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                        'sentAs' => 'AwsAccountNumber',
                                    ),
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'Location' => array(
                    'description' => 'The fully qualified URI of the new streaming distribution resource just created. For example: https://cloudfront.amazonaws.com/2010-11-01/streaming-distribution/EGTXBD79H29TRA8.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'ETag' => array(
                    'description' => 'The current version of the streaming distribution created.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteCloudFrontOriginAccessIdentity2012_05_05Output' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteDistribution2012_05_05Output' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteStreamingDistribution2012_05_05Output' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetCloudFrontOriginAccessIdentityResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The ID for the origin access identity. For example: E74FTE3AJFJ256A.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3CanonicalUserId' => array(
                    'description' => 'The Amazon S3 canonical user ID for the origin access identity, which you use when giving the origin access identity read permission to an object in Amazon S3.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CloudFrontOriginAccessIdentityConfig' => array(
                    'description' => 'The current configuration information for the identity.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the origin access identity\'s information. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetCloudFrontOriginAccessIdentityConfigResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CallerReference' => array(
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Comment' => array(
                    'description' => 'Any comments you want to include about the origin access identity.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the distribution. For example: EDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'This response element indicates the current status of the distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InProgressInvalidationBatches' => array(
                    'description' => 'The number of invalidation batches currently in progress.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the distribution. For example: d604721fxaaqy9.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DistributionConfig' => array(
                    'description' => 'The current configuration information for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'DefaultRootObject' => array(
                            'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                            'type' => 'string',
                        ),
                        'Origins' => array(
                            'description' => 'A complex type that contains information about origins for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of origins for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'A complex type that contains origins for this distribution.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Origin',
                                        'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                        'type' => 'object',
                                        'sentAs' => 'Origin',
                                        'properties' => array(
                                            'Id' => array(
                                                'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'DomainName' => array(
                                                'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                                'type' => 'string',
                                            ),
                                            'S3OriginConfig' => array(
                                                'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'OriginAccessIdentity' => array(
                                                        'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'CustomOriginConfig' => array(
                                                'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'HTTPPort' => array(
                                                        'description' => 'The HTTP port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'HTTPSPort' => array(
                                                        'description' => 'The HTTPS port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'OriginProtocolPolicy' => array(
                                                        'description' => 'The origin protocol policy to apply to your origin.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'DefaultCacheBehavior' => array(
                            'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                            'type' => 'object',
                            'properties' => array(
                                'TargetOriginId' => array(
                                    'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                    'type' => 'string',
                                ),
                                'ForwardedValues' => array(
                                    'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'QueryString' => array(
                                            'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                                'TrustedSigners' => array(
                                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Enabled' => array(
                                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                            'type' => 'boolean',
                                        ),
                                        'Quantity' => array(
                                            'description' => 'The number of trusted signers for this cache behavior.',
                                            'type' => 'numeric',
                                        ),
                                        'Items' => array(
                                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'AwsAccountNumber',
                                                'type' => 'string',
                                                'sentAs' => 'AwsAccountNumber',
                                            ),
                                        ),
                                    ),
                                ),
                                'ViewerProtocolPolicy' => array(
                                    'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                    'type' => 'string',
                                ),
                                'MinTTL' => array(
                                    'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'CacheBehaviors' => array(
                            'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of cache behaviors for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CacheBehavior',
                                        'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'sentAs' => 'CacheBehavior',
                                        'properties' => array(
                                            'PathPattern' => array(
                                                'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'TargetOriginId' => array(
                                                'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'ForwardedValues' => array(
                                                'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'QueryString' => array(
                                                        'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                        'type' => 'boolean',
                                                    ),
                                                ),
                                            ),
                                            'TrustedSigners' => array(
                                                'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Enabled' => array(
                                                        'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                        'type' => 'boolean',
                                                    ),
                                                    'Quantity' => array(
                                                        'description' => 'The number of trusted signers for this cache behavior.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'Items' => array(
                                                        'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'AwsAccountNumber',
                                                            'type' => 'string',
                                                            'sentAs' => 'AwsAccountNumber',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'ViewerProtocolPolicy' => array(
                                                'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                                'type' => 'string',
                                            ),
                                            'MinTTL' => array(
                                                'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the distribution\'s information. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetDistributionConfigResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CallerReference' => array(
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Aliases' => array(
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                                'sentAs' => 'CNAME',
                            ),
                        ),
                    ),
                ),
                'DefaultRootObject' => array(
                    'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Origins' => array(
                    'description' => 'A complex type that contains information about origins for this distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'description' => 'The number of origins for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains origins for this distribution.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Origin',
                                'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                'type' => 'object',
                                'sentAs' => 'Origin',
                                'properties' => array(
                                    'Id' => array(
                                        'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'DomainName' => array(
                                        'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                        'type' => 'string',
                                    ),
                                    'S3OriginConfig' => array(
                                        'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'OriginAccessIdentity' => array(
                                                'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'CustomOriginConfig' => array(
                                        'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'HTTPPort' => array(
                                                'description' => 'The HTTP port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'HTTPSPort' => array(
                                                'description' => 'The HTTPS port the custom origin listens on.',
                                                'type' => 'numeric',
                                            ),
                                            'OriginProtocolPolicy' => array(
                                                'description' => 'The origin protocol policy to apply to your origin.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DefaultCacheBehavior' => array(
                    'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'TargetOriginId' => array(
                            'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                            'type' => 'string',
                        ),
                        'ForwardedValues' => array(
                            'description' => 'A complex type that specifies how CloudFront handles query strings.',
                            'type' => 'object',
                            'properties' => array(
                                'QueryString' => array(
                                    'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                    'type' => 'boolean',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                ),
                                'Quantity' => array(
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                        'sentAs' => 'AwsAccountNumber',
                                    ),
                                ),
                            ),
                        ),
                        'ViewerProtocolPolicy' => array(
                            'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                            'type' => 'string',
                        ),
                        'MinTTL' => array(
                            'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                            'type' => 'numeric',
                        ),
                    ),
                ),
                'CacheBehaviors' => array(
                    'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'description' => 'The number of cache behaviors for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CacheBehavior',
                                'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                'type' => 'object',
                                'sentAs' => 'CacheBehavior',
                                'properties' => array(
                                    'PathPattern' => array(
                                        'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'TargetOriginId' => array(
                                        'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'ForwardedValues' => array(
                                        'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'QueryString' => array(
                                                'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                'type' => 'boolean',
                                            ),
                                        ),
                                    ),
                                    'TrustedSigners' => array(
                                        'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Enabled' => array(
                                                'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                'type' => 'boolean',
                                            ),
                                            'Quantity' => array(
                                                'description' => 'The number of trusted signers for this cache behavior.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AwsAccountNumber',
                                                    'type' => 'string',
                                                    'sentAs' => 'AwsAccountNumber',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'ViewerProtocolPolicy' => array(
                                        'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                        'type' => 'string',
                                    ),
                                    'MinTTL' => array(
                                        'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'description' => 'Any comments you want to include about the distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'description' => 'A complex type that controls whether access logs are written for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                        ),
                        'Bucket' => array(
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Enabled' => array(
                    'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetInvalidationResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the invalidation request. For example: IDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The status of the invalidation request. When the invalidation batch is finished, the status is Completed.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CreateTime' => array(
                    'description' => 'The date and time the invalidation request was first made.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InvalidationBatch' => array(
                    'description' => 'The current invalidation information for the batch request.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Paths' => array(
                            'description' => 'The path of the object to invalidate. The path is relative to the distribution and must begin with a slash (/). You must enclose each invalidation object with the Path element tags. If the path includes non-ASCII characters or unsafe characters as defined in RFC 1783 (http://www.ietf.org/rfc/rfc1738.txt), URL encode those characters. Do not URL encode any other characters in the path, or CloudFront will not invalidate the old version of the updated object.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of objects that you want to invalidate.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'A complex type that contains a list of the objects that you want to invalidate.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Path',
                                        'type' => 'string',
                                        'sentAs' => 'Path',
                                    ),
                                ),
                            ),
                        ),
                        'CallerReference' => array(
                            'description' => 'A unique name that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the Path object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create an invalidation batch, and the content of each Path element is identical to the original request, the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of any Path is different from the original request, CloudFront returns an InvalidationBatchAlreadyExists error.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetStreamingDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the streaming distribution. For example: EGTXBD79H29TRA8.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The current status of the streaming distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the streaming distribution. For example: s5c39gqb8ow64r.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'StreamingDistributionConfig' => array(
                    'description' => 'The current configuration information for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'S3Origin' => array(
                            'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'DomainName' => array(
                                    'description' => 'The DNS name of the S3 origin.',
                                    'type' => 'string',
                                ),
                                'OriginAccessIdentity' => array(
                                    'description' => 'Your S3 origin\'s origin access identity.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the streaming distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                ),
                                'Quantity' => array(
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                        'sentAs' => 'AwsAccountNumber',
                                    ),
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the streaming distribution\'s information. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetStreamingDistributionConfigResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CallerReference' => array(
                    'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3Origin' => array(
                    'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DomainName' => array(
                            'description' => 'The DNS name of the S3 origin.',
                            'type' => 'string',
                        ),
                        'OriginAccessIdentity' => array(
                            'description' => 'Your S3 origin\'s origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Aliases' => array(
                    'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Quantity' => array(
                            'description' => 'The number of CNAMEs, if any, for this distribution.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'CNAME',
                                'type' => 'string',
                                'sentAs' => 'CNAME',
                            ),
                        ),
                    ),
                ),
                'Comment' => array(
                    'description' => 'Any comments you want to include about the streaming distribution.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Logging' => array(
                    'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                            'type' => 'boolean',
                        ),
                        'Bucket' => array(
                            'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                            'type' => 'string',
                        ),
                        'Prefix' => array(
                            'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'TrustedSigners' => array(
                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of trusted signers for this cache behavior.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'AwsAccountNumber',
                                'type' => 'string',
                                'sentAs' => 'AwsAccountNumber',
                            ),
                        ),
                    ),
                ),
                'Enabled' => array(
                    'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListCloudFrontOriginAccessIdentitiesResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The value you provided for the Marker request parameter.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value you can use for the Marker request parameter to continue listing your origin access identities where they left off.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The value you provided for the MaxItems request parameter.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether more origin access identities remain to be listed. If your results were truncated, you can make a follow-up pagination request using the Marker request parameter to retrieve more items in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Quantity' => array(
                    'description' => 'The number of CloudFront origin access identities that were created by the current AWS account.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Items' => array(
                    'description' => 'A complex type that contains one CloudFrontOriginAccessIdentitySummary element for each origin access identity that was created by the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'CloudFrontOriginAccessIdentitySummary',
                        'description' => 'Summary of the information about a CloudFront origin access identity.',
                        'type' => 'object',
                        'sentAs' => 'CloudFrontOriginAccessIdentitySummary',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The ID for the origin access identity. For example: E74FTE3AJFJ256A.',
                                'type' => 'string',
                            ),
                            'S3CanonicalUserId' => array(
                                'description' => 'The Amazon S3 canonical user ID for the origin access identity, which you use when giving the origin access identity read permission to an object in Amazon S3.',
                                'type' => 'string',
                            ),
                            'Comment' => array(
                                'description' => 'The comment for this origin access identity, as originally specified when created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListDistributionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The value you provided for the Marker request parameter.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value you can use for the Marker request parameter to continue listing your distributions where they left off.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The value you provided for the MaxItems request parameter.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether more distributions remain to be listed. If your results were truncated, you can make a follow-up pagination request using the Marker request parameter to retrieve more distributions in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Quantity' => array(
                    'description' => 'The number of distributions that were created by the current AWS account.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Items' => array(
                    'description' => 'A complex type that contains one DistributionSummary element for each distribution that was created by the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'DistributionSummary',
                        'description' => 'A summary of the information for an Amazon CloudFront distribution.',
                        'type' => 'object',
                        'sentAs' => 'DistributionSummary',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The identifier for the distribution. For example: EDFDVBD632BHDS5.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'This response element indicates the current status of the distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                                'type' => 'string',
                            ),
                            'LastModifiedTime' => array(
                                'description' => 'The date and time the distribution was last modified.',
                                'type' => 'string',
                            ),
                            'DomainName' => array(
                                'description' => 'The domain name corresponding to the distribution. For example: d604721fxaaqy9.cloudfront.net.',
                                'type' => 'string',
                            ),
                            'Aliases' => array(
                                'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'Quantity' => array(
                                        'description' => 'The number of CNAMEs, if any, for this distribution.',
                                        'type' => 'numeric',
                                    ),
                                    'Items' => array(
                                        'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CNAME',
                                            'type' => 'string',
                                            'sentAs' => 'CNAME',
                                        ),
                                    ),
                                ),
                            ),
                            'Origins' => array(
                                'description' => 'A complex type that contains information about origins for this distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'Quantity' => array(
                                        'description' => 'The number of origins for this distribution.',
                                        'type' => 'numeric',
                                    ),
                                    'Items' => array(
                                        'description' => 'A complex type that contains origins for this distribution.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'Origin',
                                            'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                            'type' => 'object',
                                            'sentAs' => 'Origin',
                                            'properties' => array(
                                                'Id' => array(
                                                    'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                                    'type' => 'string',
                                                ),
                                                'DomainName' => array(
                                                    'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                                    'type' => 'string',
                                                ),
                                                'S3OriginConfig' => array(
                                                    'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'OriginAccessIdentity' => array(
                                                            'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                                'CustomOriginConfig' => array(
                                                    'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'HTTPPort' => array(
                                                            'description' => 'The HTTP port the custom origin listens on.',
                                                            'type' => 'numeric',
                                                        ),
                                                        'HTTPSPort' => array(
                                                            'description' => 'The HTTPS port the custom origin listens on.',
                                                            'type' => 'numeric',
                                                        ),
                                                        'OriginProtocolPolicy' => array(
                                                            'description' => 'The origin protocol policy to apply to your origin.',
                                                            'type' => 'string',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'DefaultCacheBehavior' => array(
                                'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                                'type' => 'object',
                                'properties' => array(
                                    'TargetOriginId' => array(
                                        'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                        'type' => 'string',
                                    ),
                                    'ForwardedValues' => array(
                                        'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'QueryString' => array(
                                                'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                'type' => 'boolean',
                                            ),
                                        ),
                                    ),
                                    'TrustedSigners' => array(
                                        'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Enabled' => array(
                                                'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                'type' => 'boolean',
                                            ),
                                            'Quantity' => array(
                                                'description' => 'The number of trusted signers for this cache behavior.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'AwsAccountNumber',
                                                    'type' => 'string',
                                                    'sentAs' => 'AwsAccountNumber',
                                                ),
                                            ),
                                        ),
                                    ),
                                    'ViewerProtocolPolicy' => array(
                                        'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                        'type' => 'string',
                                    ),
                                    'MinTTL' => array(
                                        'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'CacheBehaviors' => array(
                                'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                                'type' => 'object',
                                'properties' => array(
                                    'Quantity' => array(
                                        'description' => 'The number of cache behaviors for this distribution.',
                                        'type' => 'numeric',
                                    ),
                                    'Items' => array(
                                        'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CacheBehavior',
                                            'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                            'type' => 'object',
                                            'sentAs' => 'CacheBehavior',
                                            'properties' => array(
                                                'PathPattern' => array(
                                                    'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                                    'type' => 'string',
                                                ),
                                                'TargetOriginId' => array(
                                                    'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                                    'type' => 'string',
                                                ),
                                                'ForwardedValues' => array(
                                                    'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'QueryString' => array(
                                                            'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                            'type' => 'boolean',
                                                        ),
                                                    ),
                                                ),
                                                'TrustedSigners' => array(
                                                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'Enabled' => array(
                                                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                            'type' => 'boolean',
                                                        ),
                                                        'Quantity' => array(
                                                            'description' => 'The number of trusted signers for this cache behavior.',
                                                            'type' => 'numeric',
                                                        ),
                                                        'Items' => array(
                                                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                            'type' => 'array',
                                                            'items' => array(
                                                                'name' => 'AwsAccountNumber',
                                                                'type' => 'string',
                                                                'sentAs' => 'AwsAccountNumber',
                                                            ),
                                                        ),
                                                    ),
                                                ),
                                                'ViewerProtocolPolicy' => array(
                                                    'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                                    'type' => 'string',
                                                ),
                                                'MinTTL' => array(
                                                    'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                                    'type' => 'numeric',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                            'Comment' => array(
                                'description' => 'The comment originally specified when this distribution was created.',
                                'type' => 'string',
                            ),
                            'Enabled' => array(
                                'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListInvalidationsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The value you provided for the Marker request parameter.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value you can use for the Marker request parameter to continue listing your invalidation batches where they left off.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The value you provided for the MaxItems request parameter.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether more invalidation batch requests remain to be listed. If your results were truncated, you can make a follow-up pagination request using the Marker request parameter to retrieve more invalidation batches in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Quantity' => array(
                    'description' => 'The number of invalidation batches that were created by the current AWS account.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Items' => array(
                    'description' => 'A complex type that contains one InvalidationSummary element for each invalidation batch that was created by the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'InvalidationSummary',
                        'description' => 'Summary of an invalidation request.',
                        'type' => 'object',
                        'sentAs' => 'InvalidationSummary',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The unique ID for an invalidation request.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'The status of an invalidation request.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListStreamingDistributionsResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Marker' => array(
                    'description' => 'The value you provided for the Marker request parameter.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextMarker' => array(
                    'description' => 'If IsTruncated is true, this element is present and contains the value you can use for the Marker request parameter to continue listing your streaming distributions where they left off.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxItems' => array(
                    'description' => 'The value you provided for the MaxItems request parameter.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether more streaming distributions remain to be listed. If your results were truncated, you can make a follow-up pagination request using the Marker request parameter to retrieve more distributions in the list.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Quantity' => array(
                    'description' => 'The number of streaming distributions that were created by the current AWS account.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'Items' => array(
                    'description' => 'A complex type that contains one StreamingDistributionSummary element for each distribution that was created by the current AWS account.',
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'StreamingDistributionSummary',
                        'description' => 'A summary of the information for an Amazon CloudFront streaming distribution.',
                        'type' => 'object',
                        'sentAs' => 'StreamingDistributionSummary',
                        'properties' => array(
                            'Id' => array(
                                'description' => 'The identifier for the distribution. For example: EDFDVBD632BHDS5.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'Indicates the current status of the distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                                'type' => 'string',
                            ),
                            'LastModifiedTime' => array(
                                'description' => 'The date and time the distribution was last modified.',
                                'type' => 'string',
                            ),
                            'DomainName' => array(
                                'description' => 'The domain name corresponding to the distribution. For example: d604721fxaaqy9.cloudfront.net.',
                                'type' => 'string',
                            ),
                            'S3Origin' => array(
                                'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'DomainName' => array(
                                        'description' => 'The DNS name of the S3 origin.',
                                        'type' => 'string',
                                    ),
                                    'OriginAccessIdentity' => array(
                                        'description' => 'Your S3 origin\'s origin access identity.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Aliases' => array(
                                'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'Quantity' => array(
                                        'description' => 'The number of CNAMEs, if any, for this distribution.',
                                        'type' => 'numeric',
                                    ),
                                    'Items' => array(
                                        'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'CNAME',
                                            'type' => 'string',
                                            'sentAs' => 'CNAME',
                                        ),
                                    ),
                                ),
                            ),
                            'TrustedSigners' => array(
                                'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                'type' => 'object',
                                'properties' => array(
                                    'Enabled' => array(
                                        'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                        'type' => 'boolean',
                                    ),
                                    'Quantity' => array(
                                        'description' => 'The number of trusted signers for this cache behavior.',
                                        'type' => 'numeric',
                                    ),
                                    'Items' => array(
                                        'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                        'type' => 'array',
                                        'items' => array(
                                            'name' => 'AwsAccountNumber',
                                            'type' => 'string',
                                            'sentAs' => 'AwsAccountNumber',
                                        ),
                                    ),
                                ),
                            ),
                            'Comment' => array(
                                'description' => 'The comment originally specified when this distribution was created.',
                                'type' => 'string',
                            ),
                            'Enabled' => array(
                                'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                                'type' => 'boolean',
                            ),
                        ),
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'UpdateCloudFrontOriginAccessIdentityResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The ID for the origin access identity. For example: E74FTE3AJFJ256A.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'S3CanonicalUserId' => array(
                    'description' => 'The Amazon S3 canonical user ID for the origin access identity, which you use when giving the origin access identity read permission to an object in Amazon S3.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'CloudFrontOriginAccessIdentityConfig' => array(
                    'description' => 'The current configuration information for the identity.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the CloudFrontOriginAccessIdentityConfig object), a new origin access identity is created. If the CallerReference is a value you already sent in a previous request to create an identity, and the content of the CloudFrontOriginAccessIdentityConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create an identity but the content of the CloudFrontOriginAccessIdentityConfig is different from the original request, CloudFront returns a CloudFrontOriginAccessIdentityAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the origin access identity.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'UpdateDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the distribution. For example: EDFDVBD632BHDS5.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'This response element indicates the current status of the distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'InProgressInvalidationBatches' => array(
                    'description' => 'The number of invalidation batches currently in progress.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the distribution. For example: d604721fxaaqy9.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DistributionConfig' => array(
                    'description' => 'The current configuration information for the distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the DistributionConfig object), a new distribution is created. If the CallerReference is a value you already sent in a previous request to create a distribution, and the content of the DistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a distribution but the content of the DistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'DefaultRootObject' => array(
                            'description' => 'The object that you want CloudFront to return (for example, index.html) when an end user requests the root URL for your distribution (http://www.example.com) instead of an object in your distribution (http://www.example.com/index.html). Specifying a default root object avoids exposing the contents of your distribution. If you don\'t want to specify a default root object when you create a distribution, include an empty DefaultRootObject element. To delete the default root object from an existing distribution, update the distribution configuration and include an empty DefaultRootObject element. To replace the default root object, update the distribution configuration and specify the new object.',
                            'type' => 'string',
                        ),
                        'Origins' => array(
                            'description' => 'A complex type that contains information about origins for this distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of origins for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'A complex type that contains origins for this distribution.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'Origin',
                                        'description' => 'A complex type that describes the Amazon S3 bucket or the HTTP server (for example, a web server) from which CloudFront gets your files.You must create at least one origin.',
                                        'type' => 'object',
                                        'sentAs' => 'Origin',
                                        'properties' => array(
                                            'Id' => array(
                                                'description' => 'A unique identifier for the origin. The value of Id must be unique within the distribution. You use the value of Id when you create a cache behavior. The Id identifies the origin that CloudFront routes a request to when the request matches the path pattern for that cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'DomainName' => array(
                                                'description' => 'Amazon S3 origins: The DNS name of the Amazon S3 bucket from which you want CloudFront to get objects for this origin, for example, myawsbucket.s3.amazonaws.com. Custom origins: The DNS domain name for the HTTP server from which you want CloudFront to get objects for this origin, for example, www.example.com.',
                                                'type' => 'string',
                                            ),
                                            'S3OriginConfig' => array(
                                                'description' => 'A complex type that contains information about the Amazon S3 origin. If the origin is a custom origin, use the CustomOriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'OriginAccessIdentity' => array(
                                                        'description' => 'The CloudFront origin access identity to associate with the origin. Use an origin access identity to configure the origin so that end users can only access objects in an Amazon S3 bucket through CloudFront. If you want end users to be able to access objects using either the CloudFront URL or the Amazon S3 URL, specify an empty OriginAccessIdentity element. To delete the origin access identity from an existing distribution, update the distribution configuration and include an empty OriginAccessIdentity element. To replace the origin access identity, update the distribution configuration and specify the new origin access identity.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                            'CustomOriginConfig' => array(
                                                'description' => 'A complex type that contains information about a custom origin. If the origin is an Amazon S3 bucket, use the S3OriginConfig element instead.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'HTTPPort' => array(
                                                        'description' => 'The HTTP port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'HTTPSPort' => array(
                                                        'description' => 'The HTTPS port the custom origin listens on.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'OriginProtocolPolicy' => array(
                                                        'description' => 'The origin protocol policy to apply to your origin.',
                                                        'type' => 'string',
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'DefaultCacheBehavior' => array(
                            'description' => 'A complex type that describes the default cache behavior if you do not specify a CacheBehavior element or if files don\'t match any of the values of PathPattern in CacheBehavior elements.You must create exactly one default cache behavior.',
                            'type' => 'object',
                            'properties' => array(
                                'TargetOriginId' => array(
                                    'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                    'type' => 'string',
                                ),
                                'ForwardedValues' => array(
                                    'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'QueryString' => array(
                                            'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                            'type' => 'boolean',
                                        ),
                                    ),
                                ),
                                'TrustedSigners' => array(
                                    'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                    'type' => 'object',
                                    'properties' => array(
                                        'Enabled' => array(
                                            'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                            'type' => 'boolean',
                                        ),
                                        'Quantity' => array(
                                            'description' => 'The number of trusted signers for this cache behavior.',
                                            'type' => 'numeric',
                                        ),
                                        'Items' => array(
                                            'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                            'type' => 'array',
                                            'items' => array(
                                                'name' => 'AwsAccountNumber',
                                                'type' => 'string',
                                                'sentAs' => 'AwsAccountNumber',
                                            ),
                                        ),
                                    ),
                                ),
                                'ViewerProtocolPolicy' => array(
                                    'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                    'type' => 'string',
                                ),
                                'MinTTL' => array(
                                    'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                    'type' => 'numeric',
                                ),
                            ),
                        ),
                        'CacheBehaviors' => array(
                            'description' => 'A complex type that contains zero or more CacheBehavior elements.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of cache behaviors for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains cache behaviors for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CacheBehavior',
                                        'description' => 'A complex type that describes how CloudFront processes requests. You can create up to 10 cache behaviors.You must create at least as many cache behaviors (including the default cache behavior) as you have origins if you want CloudFront to distribute objects from all of the origins. Each cache behavior specifies the one origin from which you want CloudFront to get objects. If you have two origins and only the default cache behavior, the default cache behavior will cause CloudFront to get objects from one of the origins, but the other origin will never be used. If you don\'t want to specify any cache behaviors, include only an empty CacheBehaviors element. Don\'t include an empty CacheBehavior element, or CloudFront returns a MalformedXML error. To delete all cache behaviors in an existing distribution, update the distribution configuration and include only an empty CacheBehaviors element. To add, change, or remove one or more cache behaviors, update the distribution configuration and specify all of the cache behaviors that you want to include in the updated distribution.',
                                        'type' => 'object',
                                        'sentAs' => 'CacheBehavior',
                                        'properties' => array(
                                            'PathPattern' => array(
                                                'description' => 'The pattern (for example, images/*.jpg) that specifies which requests you want this cache behavior to apply to. When CloudFront receives an end-user request, the requested path is compared with path patterns in the order in which cache behaviors are listed in the distribution. The path pattern for the default cache behavior is * and cannot be changed. If the request for an object does not match the path pattern for any cache behaviors, CloudFront applies the behavior in the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'TargetOriginId' => array(
                                                'description' => 'The value of ID for the origin that you want CloudFront to route requests to when a request matches the path pattern either for a cache behavior or for the default cache behavior.',
                                                'type' => 'string',
                                            ),
                                            'ForwardedValues' => array(
                                                'description' => 'A complex type that specifies how CloudFront handles query strings.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'QueryString' => array(
                                                        'description' => 'Indicates whether you want CloudFront to forward query strings to the origin that is associated with this cache behavior. If so, specify true; if not, specify false.',
                                                        'type' => 'boolean',
                                                    ),
                                                ),
                                            ),
                                            'TrustedSigners' => array(
                                                'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                                                'type' => 'object',
                                                'properties' => array(
                                                    'Enabled' => array(
                                                        'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                                        'type' => 'boolean',
                                                    ),
                                                    'Quantity' => array(
                                                        'description' => 'The number of trusted signers for this cache behavior.',
                                                        'type' => 'numeric',
                                                    ),
                                                    'Items' => array(
                                                        'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                                        'type' => 'array',
                                                        'items' => array(
                                                            'name' => 'AwsAccountNumber',
                                                            'type' => 'string',
                                                            'sentAs' => 'AwsAccountNumber',
                                                        ),
                                                    ),
                                                ),
                                            ),
                                            'ViewerProtocolPolicy' => array(
                                                'description' => 'Use this element to specify the protocol that users can use to access the files in the origin specified by TargetOriginId when a request matches the path pattern in PathPattern. If you want CloudFront to allow end users to use any available protocol, specify allow-all. If you want CloudFront to require HTTPS, specify https.',
                                                'type' => 'string',
                                            ),
                                            'MinTTL' => array(
                                                'description' => 'The minimum amount of time that you want objects to stay in CloudFront caches before CloudFront queries your origin to see whether the object has been updated.You can specify a value from 0 to 3,153,600,000 seconds (100 years).',
                                                'type' => 'numeric',
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'UpdateStreamingDistributionResult' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Id' => array(
                    'description' => 'The identifier for the streaming distribution. For example: EGTXBD79H29TRA8.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Status' => array(
                    'description' => 'The current status of the streaming distribution. When the status is Deployed, the distribution\'s information is fully propagated throughout the Amazon CloudFront system.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModifiedTime' => array(
                    'description' => 'The date and time the distribution was last modified.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'DomainName' => array(
                    'description' => 'The domain name corresponding to the streaming distribution. For example: s5c39gqb8ow64r.cloudfront.net.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ActiveTrustedSigners' => array(
                    'description' => 'CloudFront automatically adds this element to the response only if you\'ve set up the distribution to serve private content with signed URLs. The element lists the key pair IDs that CloudFront is aware of for each trusted signer. The Signer child element lists the AWS account number of the trusted signer (or an empty Self element if the signer is you). The Signer element also includes the IDs of any active key pairs associated with the trusted signer\'s AWS account. If no KeyPairId element appears for a Signer, that signer can\'t create working signed URLs.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Enabled' => array(
                            'description' => 'Each active trusted signer.',
                            'type' => 'boolean',
                        ),
                        'Quantity' => array(
                            'description' => 'The number of unique trusted signers included in all cache behaviors. For example, if three cache behaviors all list the same three AWS accounts, the value of Quantity for ActiveTrustedSigners will be 3.',
                            'type' => 'numeric',
                        ),
                        'Items' => array(
                            'description' => 'A complex type that contains one Signer complex type for each unique trusted signer that is specified in the TrustedSigners complex type, including trusted signers in the default cache behavior and in all of the other cache behaviors.',
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Signer',
                                'description' => 'A complex type that lists the AWS accounts that were included in the TrustedSigners complex type, as well as their active CloudFront key pair IDs, if any.',
                                'type' => 'object',
                                'sentAs' => 'Signer',
                                'properties' => array(
                                    'AwsAccountNumber' => array(
                                        'description' => 'Specifies an AWS account that can create signed URLs. Values: self, which indicates that the AWS account that was used to create the distribution can created signed URLs, or an AWS account number. Omit the dashes in the account number.',
                                        'type' => 'string',
                                    ),
                                    'KeyPairIds' => array(
                                        'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                        'type' => 'object',
                                        'properties' => array(
                                            'Quantity' => array(
                                                'description' => 'The number of active CloudFront key pairs for AwsAccountNumber.',
                                                'type' => 'numeric',
                                            ),
                                            'Items' => array(
                                                'description' => 'A complex type that lists the active CloudFront key pairs, if any, that are associated with AwsAccountNumber.',
                                                'type' => 'array',
                                                'items' => array(
                                                    'name' => 'KeyPairId',
                                                    'type' => 'string',
                                                    'sentAs' => 'KeyPairId',
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'StreamingDistributionConfig' => array(
                    'description' => 'The current configuration information for the streaming distribution.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'CallerReference' => array(
                            'description' => 'A unique number that ensures the request can\'t be replayed. If the CallerReference is new (no matter the content of the StreamingDistributionConfig object), a new streaming distribution is created. If the CallerReference is a value you already sent in a previous request to create a streaming distribution, and the content of the StreamingDistributionConfig is identical to the original request (ignoring white space), the response includes the same information returned to the original request. If the CallerReference is a value you already sent in a previous request to create a streaming distribution but the content of the StreamingDistributionConfig is different from the original request, CloudFront returns a DistributionAlreadyExists error.',
                            'type' => 'string',
                        ),
                        'S3Origin' => array(
                            'description' => 'A complex type that contains information about the Amazon S3 bucket from which you want CloudFront to get your media files for distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'DomainName' => array(
                                    'description' => 'The DNS name of the S3 origin.',
                                    'type' => 'string',
                                ),
                                'OriginAccessIdentity' => array(
                                    'description' => 'Your S3 origin\'s origin access identity.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'Aliases' => array(
                            'description' => 'A complex type that contains information about CNAMEs (alternate domain names), if any, for this streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Quantity' => array(
                                    'description' => 'The number of CNAMEs, if any, for this distribution.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains CNAME elements, if any, for this distribution. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'CNAME',
                                        'type' => 'string',
                                        'sentAs' => 'CNAME',
                                    ),
                                ),
                            ),
                        ),
                        'Comment' => array(
                            'description' => 'Any comments you want to include about the streaming distribution.',
                            'type' => 'string',
                        ),
                        'Logging' => array(
                            'description' => 'A complex type that controls whether access logs are written for the streaming distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want CloudFront to save access logs to an Amazon S3 bucket. If you do not want to enable logging when you create a distribution or if you want to disable logging for an existing distribution, specify false for Enabled, and specify empty Bucket and Prefix elements. If you specify false for Enabled but you specify values for Bucket and Prefix, the values are automatically deleted.',
                                    'type' => 'boolean',
                                ),
                                'Bucket' => array(
                                    'description' => 'The Amazon S3 bucket to store the access logs in, for example, myawslogbucket.s3.amazonaws.com.',
                                    'type' => 'string',
                                ),
                                'Prefix' => array(
                                    'description' => 'An optional string that you want CloudFront to prefix to the access log filenames for this distribution, for example, myprefix/. If you want to enable logging, but you do not want to specify a prefix, you still must include an empty Prefix element in the Logging element.',
                                    'type' => 'string',
                                ),
                            ),
                        ),
                        'TrustedSigners' => array(
                            'description' => 'A complex type that specifies the AWS accounts, if any, that you want to allow to create signed URLs for private content. If you want to require signed URLs in requests for objects in the target origin that match the PathPattern for this cache behavior, specify true for Enabled, and specify the applicable values for Quantity and Items. For more information, go to Using a Signed URL to Serve Private Content in the Amazon CloudFront Developer Guide. If you don\'t want to require signed URLs in requests for objects that match PathPattern, specify false for Enabled and 0 for Quantity. Omit Items. To add, change, or remove one or more trusted signers, change Enabled to true (if it\'s currently false), change Quantity as applicable, and specify all of the trusted signers that you want to include in the updated distribution.',
                            'type' => 'object',
                            'properties' => array(
                                'Enabled' => array(
                                    'description' => 'Specifies whether you want to require end users to use signed URLs to access the files specified by PathPattern and TargetOriginId.',
                                    'type' => 'boolean',
                                ),
                                'Quantity' => array(
                                    'description' => 'The number of trusted signers for this cache behavior.',
                                    'type' => 'numeric',
                                ),
                                'Items' => array(
                                    'description' => 'Optional: A complex type that contains trusted signers for this cache behavior. If Quantity is 0, you can omit Items.',
                                    'type' => 'array',
                                    'items' => array(
                                        'name' => 'AwsAccountNumber',
                                        'type' => 'string',
                                        'sentAs' => 'AwsAccountNumber',
                                    ),
                                ),
                            ),
                        ),
                        'Enabled' => array(
                            'description' => 'Whether the streaming distribution is enabled to accept end user requests for content.',
                            'type' => 'boolean',
                        ),
                    ),
                ),
                'ETag' => array(
                    'description' => 'The current version of the configuration. For example: E2QWRUHAPOMQZL.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
    ),
    'waiters' => array(
        '__default__' => array(
            'success.type' => 'output',
            'success.path' => 'Status',
        ),
        'StreamingDistributionDeployed' => array(
            'operation' => 'GetStreamingDistribution',
            'description' => 'Wait until a streaming distribution is deployed.',
            'interval' => 60,
            'max_attempts' => 25,
            'success.value' => 'Deployed',
        ),
        'DistributionDeployed' => array(
            'operation' => 'GetDistribution',
            'description' => 'Wait until a distribution is deployed.',
            'interval' => 60,
            'max_attempts' => 25,
            'success.value' => 'Deployed',
        ),
        'InvalidationCompleted' => array(
            'operation' => 'GetInvalidation',
            'description' => 'Wait until an invalidation has completed.',
            'interval' => 20,
            'max_attempts' => 30,
            'success.value' => 'Completed',
        ),
    ),
);
