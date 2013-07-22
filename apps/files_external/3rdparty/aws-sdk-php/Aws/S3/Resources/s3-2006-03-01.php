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
    'apiVersion' => '2006-03-01',
    'endpointPrefix' => 's3',
    'serviceFullName' => 'Amazon Simple Storage Service',
    'serviceAbbreviation' => 'Amazon S3',
    'serviceType' => 'rest-xml',
    'timestampFormat' => 'rfc822',
    'globalEndpoint' => 's3.amazonaws.com',
    'signatureVersion' => 's3',
    'namespace' => 'S3',
    'regions' => array(
        'us-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3.amazonaws.com',
        ),
        'us-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-us-west-1.amazonaws.com',
        ),
        'us-west-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-us-west-2.amazonaws.com',
        ),
        'eu-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-eu-west-1.amazonaws.com',
        ),
        'ap-northeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-ap-northeast-1.amazonaws.com',
        ),
        'ap-southeast-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-ap-southeast-1.amazonaws.com',
        ),
        'ap-southeast-2' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-ap-southeast-2.amazonaws.com',
        ),
        'sa-east-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-sa-east-1.amazonaws.com',
        ),
        'us-gov-west-1' => array(
            'http' => true,
            'https' => true,
            'hostname' => 's3-us-gov-west-1.amazonaws.com',
        ),
    ),
    'operations' => array(
        'AbortMultipartUpload' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'AbortMultipartUploadOutput',
            'responseType' => 'model',
            'summary' => 'Aborts a multipart upload.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadAbort.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'UploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified multipart upload does not exist.',
                    'class' => 'NoSuchUploadException',
                ),
            ),
        ),
        'CompleteMultipartUpload' => array(
            'httpMethod' => 'POST',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'CompleteMultipartUploadOutput',
            'responseType' => 'model',
            'summary' => 'Completes a multipart upload by assembling previously uploaded parts.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadComplete.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'MultipartUpload',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Parts' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Part',
                        'properties' => array(
                            'ETag' => array(
                                'description' => 'Entity tag returned when the part was uploaded.',
                                'type' => 'string',
                            ),
                            'PartNumber' => array(
                                'description' => 'Part number that identifies the part.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'UploadId' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'CopyObject' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'CopyObjectOutput',
            'responseType' => 'model',
            'summary' => 'Creates a copy of an object that is already stored in Amazon S3.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectCOPY.html',
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CacheControl' => array(
                    'description' => 'Specifies caching behavior along the request/reply chain.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Cache-Control',
                ),
                'ContentDisposition' => array(
                    'description' => 'Specifies presentational information for the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Disposition',
                ),
                'ContentEncoding' => array(
                    'description' => 'Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to obtain the media-type referenced by the Content-Type header field.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Encoding',
                ),
                'ContentLanguage' => array(
                    'description' => 'The language the content is in.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Language',
                ),
                'ContentType' => array(
                    'description' => 'A standard MIME type describing the format of the object data.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'CopySource' => array(
                    'required' => true,
                    'description' => 'The name of the source bucket and key name of the source object, separated by a slash (/). Must be URL-encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source',
                ),
                'CopySourceIfMatch' => array(
                    'description' => 'Copies the object if its entity tag (ETag) matches the specified tag.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-match',
                ),
                'CopySourceIfModifiedSince' => array(
                    'description' => 'Copies the object if it has been modified since the specified time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-modified-since',
                ),
                'CopySourceIfNoneMatch' => array(
                    'description' => 'Copies the object if its entity tag (ETag) is different than the specified ETag.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-none-match',
                ),
                'CopySourceIfUnmodifiedSince' => array(
                    'description' => 'Copies the object if it hasn\'t been modified since the specified time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-unmodified-since',
                ),
                'Expires' => array(
                    'description' => 'The date and time at which the object is no longer cacheable.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                ),
                'GrantFullControl' => array(
                    'description' => 'Gives the grantee READ, READ_ACP, and WRITE_ACP permissions on the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to read the object data and its metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the object ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Metadata' => array(
                    'description' => 'A map of metadata to store with the object in S3.',
                    'type' => 'object',
                    'location' => 'header',
                    'sentAs' => 'x-amz-meta-',
                    'additionalProperties' => array(
                        'description' => 'The metadata key. This will be prefixed with x-amz-meta- before sending to S3 as a header. The x-amz-meta- header will be stripped from the key when retrieving headers.',
                        'type' => 'string',
                    ),
                ),
                'MetadataDirective' => array(
                    'description' => 'Specifies whether the metadata is copied from the source object or replaced with metadata provided in the request.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-metadata-directive',
                    'enum' => array(
                        'COPY',
                        'REPLACE',
                    ),
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                    'enum' => array(
                        'AES256',
                    ),
                ),
                'StorageClass' => array(
                    'description' => 'The type of storage to use for the object. Defaults to \'STANDARD\'.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-storage-class',
                    'enum' => array(
                        'STANDARD',
                        'REDUCED_REDUNDANCY',
                    ),
                ),
                'WebsiteRedirectLocation' => array(
                    'description' => 'If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or to an external URL. Amazon S3 stores the value of this header in the object metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-website-redirect-location',
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add access control policy headers to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The source object of the COPY operation is not in the active tier and is only stored in Amazon Glacier.',
                    'class' => 'ObjectNotInActiveTierErrorException',
                ),
            ),
        ),
        'CreateBucket' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'CreateBucketOutput',
            'responseType' => 'model',
            'summary' => 'Creates a new bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUT.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CreateBucketConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'LocationConstraint' => array(
                    'description' => 'Specifies the region where the bucket will be created.',
                    'type' => 'string',
                    'location' => 'xml',
                    'enum' => array(
                        'EU',
                        'eu-west-1',
                        'us-west-1',
                        'us-west-2',
                        'ap-southeast-1',
                        'ap-northeast-1',
                        'sa-east-1',
                    ),
                ),
                'GrantFullControl' => array(
                    'description' => 'Allows grantee the read, write, read ACP, and write ACP permissions on the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to list the objects in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the bucket ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWrite' => array(
                    'description' => 'Allows grantee to create, overwrite, and delete any object in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add access control policy headers to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The requested bucket name is not available. The bucket namespace is shared by all users of the system. Please select a different name and try again.',
                    'class' => 'BucketAlreadyExistsException',
                ),
            ),
        ),
        'CreateMultipartUpload' => array(
            'httpMethod' => 'POST',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'CreateMultipartUploadOutput',
            'responseType' => 'model',
            'summary' => 'Initiates a multipart upload and returns an upload ID.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadInitiate.html',
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CacheControl' => array(
                    'description' => 'Specifies caching behavior along the request/reply chain.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Cache-Control',
                ),
                'ContentDisposition' => array(
                    'description' => 'Specifies presentational information for the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Disposition',
                ),
                'ContentEncoding' => array(
                    'description' => 'Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to obtain the media-type referenced by the Content-Type header field.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Encoding',
                ),
                'ContentLanguage' => array(
                    'description' => 'The language the content is in.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Language',
                ),
                'ContentType' => array(
                    'description' => 'A standard MIME type describing the format of the object data.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'Expires' => array(
                    'description' => 'The date and time at which the object is no longer cacheable.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                ),
                'GrantFullControl' => array(
                    'description' => 'Gives the grantee READ, READ_ACP, and WRITE_ACP permissions on the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to read the object data and its metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the object ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Metadata' => array(
                    'description' => 'A map of metadata to store with the object in S3.',
                    'type' => 'object',
                    'location' => 'header',
                    'sentAs' => 'x-amz-meta-',
                    'additionalProperties' => array(
                        'description' => 'The metadata key. This will be prefixed with x-amz-meta- before sending to S3 as a header. The x-amz-meta- header will be stripped from the key when retrieving headers.',
                        'type' => 'string',
                    ),
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                    'enum' => array(
                        'AES256',
                    ),
                ),
                'StorageClass' => array(
                    'description' => 'The type of storage to use for the object. Defaults to \'STANDARD\'.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-storage-class',
                    'enum' => array(
                        'STANDARD',
                        'REDUCED_REDUNDANCY',
                    ),
                ),
                'WebsiteRedirectLocation' => array(
                    'description' => 'If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or to an external URL. Amazon S3 stores the value of this header in the object metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-website-redirect-location',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'uploads',
                    'default' => '_guzzle_blank_',
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add access control policy headers to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'DeleteBucket' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the bucket. All objects (including all object versions and Delete Markers) in the bucket must be deleted before the bucket itself can be deleted.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETE.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
        ),
        'DeleteBucketCors' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketCorsOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the cors configuration information set for the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEcors.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'cors',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'DeleteBucketLifecycle' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketLifecycleOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the lifecycle configuration from the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETElifecycle.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'lifecycle',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'DeleteBucketPolicy' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketPolicyOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the policy from the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEpolicy.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'policy',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'DeleteBucketTagging' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketTaggingOutput',
            'responseType' => 'model',
            'summary' => 'Deletes the tags from the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEtagging.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'tagging',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'DeleteBucketWebsite' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteBucketWebsiteOutput',
            'responseType' => 'model',
            'summary' => 'This operation removes the website configuration from the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketDELETEwebsite.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'website',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'DeleteObject' => array(
            'httpMethod' => 'DELETE',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteObjectOutput',
            'responseType' => 'model',
            'summary' => 'Removes the null version (if there is one) of an object and inserts a delete marker, which becomes the latest version of the object. If there isn\'t a null version, Amazon S3 does not remove any objects.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectDELETE.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
            ),
        ),
        'DeleteObjects' => array(
            'httpMethod' => 'POST',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'DeleteObjectsOutput',
            'responseType' => 'model',
            'summary' => 'This operation enables you to delete multiple objects from a bucket using a single HTTP request. You may specify up to 1000 keys.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/multiobjectdeleteapi.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'Delete',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Objects' => array(
                    'required' => true,
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Object',
                        'properties' => array(
                            'Key' => array(
                                'required' => true,
                                'description' => 'Key name of the object to delete.',
                                'type' => 'string',
                            ),
                            'VersionId' => array(
                                'description' => 'VersionId for the specific version of the object to delete.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Quiet' => array(
                    'description' => 'Element to enable quiet mode for the request. When you add this element, you must set its value to true.',
                    'type' => 'boolean',
                    'format' => 'boolean-string',
                    'location' => 'xml',
                ),
                'MFA' => array(
                    'description' => 'The concatenation of the authentication device\'s serial number, a space, and the value that is displayed on your authentication device.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-mfa',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'delete',
                    'default' => '_guzzle_blank_',
                ),
                'ContentMD5' => array(
                    'required' => true,
                    'default' => true,
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketAcl' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketAclOutput',
            'responseType' => 'model',
            'summary' => 'Gets the access control policy for the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETacl.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'acl',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketCors' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketCorsOutput',
            'responseType' => 'model',
            'summary' => 'Returns the cors configuration for the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETcors.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'cors',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketLifecycle' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketLifecycleOutput',
            'responseType' => 'model',
            'summary' => 'Returns the lifecycle configuration information set on the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETlifecycle.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'lifecycle',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketLocation' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketLocationOutput',
            'responseType' => 'model',
            'summary' => 'Returns the region the bucket resides in.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETlocation.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'location',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'GetBucketLogging' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketLoggingOutput',
            'responseType' => 'model',
            'summary' => 'Returns the logging status of a bucket and the permissions users have to view and modify that status. To use GET, you must be the bucket owner.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETlogging.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'logging',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketNotification' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketNotificationOutput',
            'responseType' => 'model',
            'summary' => 'Return the notification configuration of a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETnotification.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'notification',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketPolicy' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketPolicyOutput',
            'responseType' => 'model',
            'summary' => 'Returns the policy of a specified bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETpolicy.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'policy',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'GetBucketRequestPayment' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketRequestPaymentOutput',
            'responseType' => 'model',
            'summary' => 'Returns the request payment configuration of a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTrequestPaymentGET.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'requestPayment',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketTagging' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketTaggingOutput',
            'responseType' => 'model',
            'summary' => 'Returns the tag set associated with the bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETtagging.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'tagging',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketVersioning' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketVersioningOutput',
            'responseType' => 'model',
            'summary' => 'Returns the versioning state of a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETversioningStatus.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'versioning',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetBucketWebsite' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetBucketWebsiteOutput',
            'responseType' => 'model',
            'summary' => 'Returns the website configuration for a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETwebsite.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'website',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'GetObject' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetObjectOutput',
            'responseType' => 'model',
            'summary' => 'Retrieves objects from Amazon S3.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectGET.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'Return the object only if its entity tag (ETag) is the same as the one specified, otherwise return a 412 (precondition failed).',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
                'IfModifiedSince' => array(
                    'description' => 'Return the object only if it has been modified since the specified time, otherwise return a 304 (not modified).',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'If-Modified-Since',
                ),
                'IfNoneMatch' => array(
                    'description' => 'Return the object only if its entity tag (ETag) is different from the one specified, otherwise return a 304 (not modified).',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-None-Match',
                ),
                'IfUnmodifiedSince' => array(
                    'description' => 'Return the object only if it has not been modified since the specified time, otherwise return a 412 (precondition failed).',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'If-Unmodified-Since',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Range' => array(
                    'description' => 'Downloads the specified range bytes of an object. For more information about the HTTP Range header, go to http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'ResponseCacheControl' => array(
                    'description' => 'Sets the Cache-Control header of the response.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'response-cache-control',
                ),
                'ResponseContentDisposition' => array(
                    'description' => 'Sets the Content-Disposition header of the response',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'response-content-disposition',
                ),
                'ResponseContentEncoding' => array(
                    'description' => 'Sets the Content-Encoding header of the response.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'response-content-encoding',
                ),
                'ResponseContentLanguage' => array(
                    'description' => 'Sets the Content-Language header of the response.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'response-content-language',
                ),
                'ResponseContentType' => array(
                    'description' => 'Sets the Content-Type header of the response.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'response-content-type',
                ),
                'ResponseExpires' => array(
                    'description' => 'Sets the Expires header of the response.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'query',
                    'sentAs' => 'response-expires',
                ),
                'VersionId' => array(
                    'description' => 'VersionId used to reference a specific version of the object.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'versionId',
                ),
                'SaveAs' => array(
                    'description' => 'Specify where the contents of the object should be downloaded. Can be the path to a file, a resource returned by fopen, or a Guzzle\\Http\\EntityBodyInterface object.',
                    'location' => 'response_body',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified key does not exist.',
                    'class' => 'NoSuchKeyException',
                ),
            ),
        ),
        'GetObjectAcl' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetObjectAclOutput',
            'responseType' => 'model',
            'summary' => 'Returns the access control list (ACL) of an object.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectGETacl.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'VersionId' => array(
                    'description' => 'VersionId used to reference a specific version of the object.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'versionId',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'acl',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified key does not exist.',
                    'class' => 'NoSuchKeyException',
                ),
            ),
        ),
        'GetObjectTorrent' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'GetObjectTorrentOutput',
            'responseType' => 'model',
            'summary' => 'Return torrent files from a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectGETtorrent.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'torrent',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'HeadBucket' => array(
            'httpMethod' => 'HEAD',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'HeadBucketOutput',
            'responseType' => 'model',
            'summary' => 'This operation is useful to determine if a bucket exists and you have permission to access it.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketHEAD.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified bucket does not exist.',
                    'class' => 'NoSuchBucketException',
                ),
            ),
        ),
        'HeadObject' => array(
            'httpMethod' => 'HEAD',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'HeadObjectOutput',
            'responseType' => 'model',
            'summary' => 'The HEAD operation retrieves metadata from an object without returning the object itself. This operation is useful if you\'re only interested in an object\'s metadata. To use HEAD, you must have READ access to the object.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectHEAD.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'IfMatch' => array(
                    'description' => 'Return the object only if its entity tag (ETag) is the same as the one specified, otherwise return a 412 (precondition failed).',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-Match',
                ),
                'IfModifiedSince' => array(
                    'description' => 'Return the object only if it has been modified since the specified time, otherwise return a 304 (not modified).',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'If-Modified-Since',
                ),
                'IfNoneMatch' => array(
                    'description' => 'Return the object only if its entity tag (ETag) is different from the one specified, otherwise return a 304 (not modified).',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'If-None-Match',
                ),
                'IfUnmodifiedSince' => array(
                    'description' => 'Return the object only if it has not been modified since the specified time, otherwise return a 412 (precondition failed).',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'If-Unmodified-Since',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Range' => array(
                    'description' => 'Downloads the specified range bytes of an object. For more information about the HTTP Range header, go to http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.35.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'VersionId' => array(
                    'description' => 'VersionId used to reference a specific version of the object.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'versionId',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified key does not exist.',
                    'class' => 'NoSuchKeyException',
                ),
            ),
        ),
        'ListBuckets' => array(
            'httpMethod' => 'GET',
            'uri' => '/',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'ListBucketsOutput',
            'responseType' => 'model',
            'summary' => 'Returns a list of all buckets owned by the authenticated sender of the request.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTServiceGET.html',
            'parameters' => array(
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'ListMultipartUploads' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'ListMultipartUploadsOutput',
            'responseType' => 'model',
            'summary' => 'This operation lists in-progress multipart uploads.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadListMPUpload.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Delimiter' => array(
                    'description' => 'Character you use to group keys.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'delimiter',
                ),
                'KeyMarker' => array(
                    'description' => 'Together with upload-id-marker, this parameter specifies the multipart upload after which listing should begin.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'key-marker',
                ),
                'MaxUploads' => array(
                    'description' => 'Sets the maximum number of multipart uploads, from 1 to 1,000, to return in the response body. 1,000 is the maximum number of uploads that can be returned in a response.',
                    'type' => 'numeric',
                    'location' => 'query',
                    'sentAs' => 'max-uploads',
                ),
                'Prefix' => array(
                    'description' => 'Lists in-progress uploads only for those keys that begin with the specified prefix.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'prefix',
                ),
                'UploadIdMarker' => array(
                    'description' => 'Together with key-marker, specifies the multipart upload after which listing should begin. If key-marker is not specified, the upload-id-marker parameter is ignored.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'upload-id-marker',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'uploads',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'ListObjectVersions' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'ListObjectVersionsOutput',
            'responseType' => 'model',
            'summary' => 'Returns metadata about all of the versions of objects in a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGETVersion.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Delimiter' => array(
                    'description' => 'A delimiter is a character you use to group keys.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'delimiter',
                ),
                'KeyMarker' => array(
                    'description' => 'Specifies the key to start with when listing objects in a bucket.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'key-marker',
                ),
                'MaxKeys' => array(
                    'description' => 'Sets the maximum number of keys returned in the response. The response might contain fewer keys but will never contain more.',
                    'type' => 'numeric',
                    'location' => 'query',
                    'sentAs' => 'max-keys',
                ),
                'Prefix' => array(
                    'description' => 'Limits the response to keys that begin with the specified prefix.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'prefix',
                ),
                'VersionIdMarker' => array(
                    'description' => 'Specifies the object version you want to start listing from.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'version-id-marker',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'versions',
                    'default' => '_guzzle_blank_',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'ListObjects' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'ListObjectsOutput',
            'responseType' => 'model',
            'summary' => 'Returns some or all (up to 1000) of the objects in a bucket. You can use the request parameters as selection criteria to return a subset of the objects in a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketGET.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Delimiter' => array(
                    'description' => 'A delimiter is a character you use to group keys.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'delimiter',
                ),
                'Marker' => array(
                    'description' => 'Specifies the key to start with when listing objects in a bucket.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'marker',
                ),
                'MaxKeys' => array(
                    'description' => 'Sets the maximum number of keys returned in the response. The response might contain fewer keys but will never contain more.',
                    'type' => 'numeric',
                    'location' => 'query',
                    'sentAs' => 'max-keys',
                ),
                'Prefix' => array(
                    'description' => 'Limits the response to keys that begin with the specified prefix.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'prefix',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified bucket does not exist.',
                    'class' => 'NoSuchBucketException',
                ),
            ),
        ),
        'ListParts' => array(
            'httpMethod' => 'GET',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'ListPartsOutput',
            'responseType' => 'model',
            'summary' => 'Lists the parts that have been uploaded for a specific multipart upload.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadListParts.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'MaxParts' => array(
                    'description' => 'Sets the maximum number of parts to return.',
                    'type' => 'numeric',
                    'location' => 'query',
                    'sentAs' => 'max-parts',
                ),
                'PartNumberMarker' => array(
                    'description' => 'Specifies the part after which listing should begin. Only parts with higher part numbers will be listed.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'part-number-marker',
                ),
                'UploadId' => array(
                    'required' => true,
                    'description' => 'Upload ID identifying the multipart upload whose parts are being listed.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
        'PutBucketAcl' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketAclOutput',
            'responseType' => 'model',
            'summary' => 'Sets the permissions on a bucket using access control lists (ACL).',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTacl.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'AccessControlPolicy',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Grants' => array(
                    'description' => 'A list of grants.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'AccessControlList',
                    'items' => array(
                        'name' => 'Grant',
                        'type' => 'object',
                        'properties' => array(
                            'Grantee' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'description' => 'Screen name of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'EmailAddress' => array(
                                        'description' => 'Email address of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'description' => 'The canonical user ID of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'Type' => array(
                                        'required' => true,
                                        'description' => 'Type of grantee',
                                        'type' => 'string',
                                        'sentAs' => 'xsi:type',
                                        'data' => array(
                                            'xmlAttribute' => true,
                                            'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                        ),
                                        'enum' => array(
                                            'CanonicalUser',
                                            'AmazonCustomerByEmail',
                                            'Group',
                                        ),
                                    ),
                                    'URI' => array(
                                        'description' => 'URI of the grantee group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Permission' => array(
                                'description' => 'Specifies the permission given to the grantee.',
                                'type' => 'string',
                                'enum' => array(
                                    'FULL_CONTROL',
                                    'WRITE',
                                    'WRITE_ACP',
                                    'READ',
                                    'READ_ACP',
                                ),
                            ),
                        ),
                    ),
                ),
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DisplayName' => array(
                            'type' => 'string',
                        ),
                        'ID' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'GrantFullControl' => array(
                    'description' => 'Allows grantee the read, write, read ACP, and write ACP permissions on the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to list the objects in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the bucket ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWrite' => array(
                    'description' => 'Allows grantee to create, overwrite, and delete any object in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'acl',
                    'default' => '_guzzle_blank_',
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add an access control policy to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
            ),
        ),
        'PutBucketCors' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketCorsOutput',
            'responseType' => 'model',
            'summary' => 'Sets the cors configuration for a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTcors.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'CORSConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CORSRules' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'CORSRule',
                        'properties' => array(
                            'AllowedHeaders' => array(
                                'description' => 'Specifies which headers are allowed in a pre-flight OPTIONS request.',
                                'type' => 'array',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedHeader',
                                ),
                            ),
                            'AllowedMethods' => array(
                                'description' => 'Identifies HTTP methods that the domain/origin specified in the rule is allowed to execute.',
                                'type' => 'array',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedMethod',
                                ),
                            ),
                            'AllowedOrigins' => array(
                                'description' => 'One or more origins you want customers to be able to access the bucket from.',
                                'type' => 'array',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedOrigin',
                                ),
                            ),
                            'ExposeHeaders' => array(
                                'description' => 'One or more headers in the response that you want customers to be able to access from their applications (for example, from a JavaScript XMLHttpRequest object).',
                                'type' => 'array',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'ExposeHeader',
                                ),
                            ),
                            'MaxAgeSeconds' => array(
                                'description' => 'The time in seconds that your browser is to cache the preflight response for the specified resource.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'cors',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketLifecycle' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketLifecycleOutput',
            'responseType' => 'model',
            'summary' => 'Sets lifecycle configuration for your bucket. If a lifecycle configuration exists, it replaces it.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTlifecycle.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'LifecycleConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'Rules' => array(
                    'required' => true,
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Rule',
                        'properties' => array(
                            'Expiration' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Date' => array(
                                        'description' => 'Indicates at what date the object is to be moved or deleted. Should be in GMT ISO 8601 Format.',
                                        'type' => array(
                                            'object',
                                            'string',
                                            'integer',
                                        ),
                                        'format' => 'date-time',
                                    ),
                                    'Days' => array(
                                        'description' => 'Indicates the lifetime, in days, of the objects that are subject to the rule. The value must be a non-zero positive integer.',
                                        'type' => 'numeric',
                                    ),
                                ),
                            ),
                            'ID' => array(
                                'description' => 'Unique identifier for the rule. The value cannot be longer than 255 characters.',
                                'type' => 'string',
                            ),
                            'Prefix' => array(
                                'required' => true,
                                'description' => 'Prefix identifying one or more objects to which the rule applies.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'required' => true,
                                'description' => 'If \'Enabled\', the rule is currently being applied. If \'Disabled\', the rule is not currently being applied.',
                                'type' => 'string',
                                'enum' => array(
                                    'Enabled',
                                    'Disabled',
                                ),
                            ),
                            'Transition' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Date' => array(
                                        'description' => 'Indicates at what date the object is to be moved or deleted. Should be in GMT ISO 8601 Format.',
                                        'type' => array(
                                            'object',
                                            'string',
                                            'integer',
                                        ),
                                        'format' => 'date-time',
                                    ),
                                    'Days' => array(
                                        'description' => 'Indicates the lifetime, in days, of the objects that are subject to the rule. The value must be a non-zero positive integer.',
                                        'type' => 'numeric',
                                    ),
                                    'StorageClass' => array(
                                        'description' => 'The class of storage used to store the object.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'STANDARD',
                                            'REDUCED_REDUNDANCY',
                                            'GLACIER',
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'lifecycle',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketLogging' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketLoggingOutput',
            'responseType' => 'model',
            'summary' => 'Set the logging parameters for a bucket and to specify permissions for who can view and modify the logging parameters. To set the logging status of a bucket, you must be the bucket owner.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTlogging.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'BucketLoggingStatus',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'LoggingEnabled' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'TargetBucket' => array(
                            'description' => 'Specifies the bucket where you want Amazon S3 to store server access logs. You can have your logs delivered to any bucket that you own, including the same bucket that is being logged. You can also configure multiple buckets to deliver their logs to the same target bucket. In this case you should choose a different TargetPrefix for each source bucket so that the delivered log files can be distinguished by key.',
                            'type' => 'string',
                        ),
                        'TargetGrants' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Grant',
                                'type' => 'object',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'DisplayName' => array(
                                                'description' => 'Screen name of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'EmailAddress' => array(
                                                'description' => 'Email address of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'ID' => array(
                                                'description' => 'The canonical user ID of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'Type' => array(
                                                'required' => true,
                                                'description' => 'Type of grantee',
                                                'type' => 'string',
                                                'sentAs' => 'xsi:type',
                                                'data' => array(
                                                    'xmlAttribute' => true,
                                                    'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                                ),
                                                'enum' => array(
                                                    'CanonicalUser',
                                                    'AmazonCustomerByEmail',
                                                    'Group',
                                                ),
                                            ),
                                            'URI' => array(
                                                'description' => 'URI of the grantee group.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Permission' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                        'TargetPrefix' => array(
                            'description' => 'This element lets you specify a prefix for the keys that the log files will be stored under.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'logging',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketNotification' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketNotificationOutput',
            'responseType' => 'model',
            'summary' => 'Enables notifications of specified events for a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTnotification.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'NotificationConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'TopicConfiguration' => array(
                    'required' => true,
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Event' => array(
                            'description' => 'Bucket event for which to send notifications.',
                            'type' => 'string',
                            'enum' => array(
                                's3:ReducedRedundancyLostObject',
                            ),
                        ),
                        'Topic' => array(
                            'description' => 'Amazon SNS topic to which Amazon S3 will publish a message to report the specified events for the bucket.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'notification',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketPolicy' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketPolicyOutput',
            'responseType' => 'model',
            'summary' => 'Replaces a policy on a bucket. If the bucket already has a policy, the one in this request completely replaces it.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTpolicy.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'Policy' => array(
                    'required' => true,
                    'description' => 'The bucket policy as a JSON document.',
                    'type' => array(
                        'string',
                        'object',
                    ),
                    'location' => 'body',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'policy',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketRequestPayment' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketRequestPaymentOutput',
            'responseType' => 'model',
            'summary' => 'Sets the request payment configuration for a bucket. By default, the bucket owner pays for downloads from the bucket. This configuration parameter enables the bucket owner (only) to specify that the person requesting the download will be charged for the download.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTrequestPaymentPUT.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'RequestPaymentConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'Payer' => array(
                    'required' => true,
                    'description' => 'Specifies who pays for the download and request fees.',
                    'type' => 'string',
                    'location' => 'xml',
                    'enum' => array(
                        'Requester',
                        'BucketOwner',
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'requestPayment',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketTagging' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketTaggingOutput',
            'responseType' => 'model',
            'summary' => 'Sets the tags for a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTtagging.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'Tagging',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'TagSet' => array(
                    'required' => true,
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Tag',
                        'required' => true,
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'required' => true,
                                'description' => 'Name of the tag.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'required' => true,
                                'description' => 'Value of the tag.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'tagging',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketVersioning' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketVersioningOutput',
            'responseType' => 'model',
            'summary' => 'Sets the versioning state of an existing bucket. To set the versioning state, you must be the bucket owner.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTVersioningStatus.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'VersioningConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'MFA' => array(
                    'description' => 'The value is the concatenation of the authentication device\'s serial number, a space, and the value displayed on your authentication device.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-mfa',
                ),
                'MFADelete' => array(
                    'description' => 'Specifies whether MFA delete is enabled in the bucket versioning configuration. This element is only returned if the bucket has been configured with MFA delete. If the bucket has never been so configured, this element is not returned.',
                    'type' => 'string',
                    'location' => 'xml',
                    'enum' => array(
                        'Enabled',
                        'Disabled',
                    ),
                ),
                'Status' => array(
                    'description' => 'The versioning state of the bucket.',
                    'type' => 'string',
                    'location' => 'xml',
                    'enum' => array(
                        'Enabled',
                        'Disabled',
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'versioning',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutBucketWebsite' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutBucketWebsiteOutput',
            'responseType' => 'model',
            'summary' => 'Set the website configuration for a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTBucketPUTwebsite.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'WebsiteConfiguration',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
                'xmlAllowEmpty' => true,
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'ErrorDocument' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Key' => array(
                            'required' => true,
                            'description' => 'The object key name to use when a 4XX class error occurs.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'IndexDocument' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Suffix' => array(
                            'required' => true,
                            'description' => 'A suffix that is appended to a request that is for a directory on the website endpoint (e.g. if the suffix is index.html and you make a request to samplebucket/images/ the data that is returned will be for the object with the key name images/index.html) The suffix must not be empty and must not include a slash character.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RedirectAllRequestsTo' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'HostName' => array(
                            'required' => true,
                            'description' => 'Name of the host where requests will be redirected.',
                            'type' => 'string',
                        ),
                        'Protocol' => array(
                            'description' => 'Protocol to use (http, https) when redirecting requests. The default is the protocol that is used in the original request.',
                            'type' => 'string',
                            'enum' => array(
                                'http',
                                'https',
                            ),
                        ),
                    ),
                ),
                'RoutingRules' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'RoutingRule',
                        'type' => 'object',
                        'properties' => array(
                            'Condition' => array(
                                'description' => 'A container for describing a condition that must be met for the specified redirect to apply. For example, 1. If request is for pages in the /docs folder, redirect to the /documents folder. 2. If request results in HTTP error 4xx, redirect request to another host where you might process the error.',
                                'type' => 'object',
                                'properties' => array(
                                    'HttpErrorCodeReturnedEquals' => array(
                                        'description' => 'The HTTP error code when the redirect is applied. In the event of an error, if the error code equals this value, then the specified redirect is applied. Required when parent element Condition is specified and sibling KeyPrefixEquals is not specified. If both are specified, then both must be true for the redirect to be applied.',
                                        'type' => 'string',
                                    ),
                                    'KeyPrefixEquals' => array(
                                        'description' => 'The object key name prefix when the redirect is applied. For example, to redirect requests for ExamplePage.html, the key prefix will be ExamplePage.html. To redirect request for all pages with the prefix docs/, the key prefix will be /docs, which identifies all objects in the docs/ folder. Required when the parent element Condition is specified and sibling HttpErrorCodeReturnedEquals is not specified. If both conditions are specified, both must be true for the redirect to be applied.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Redirect' => array(
                                'required' => true,
                                'description' => 'Container for redirect information. You can redirect requests to another host, to another page, or with another protocol. In the event of an error, you can can specify a different error code to return.',
                                'type' => 'object',
                                'properties' => array(
                                    'HostName' => array(
                                        'required' => true,
                                        'description' => 'Name of the host where requests will be redirected.',
                                        'type' => 'string',
                                    ),
                                    'HttpRedirectCode' => array(
                                        'description' => 'The HTTP redirect code to use on the response. Not required if one of the siblings is present.',
                                        'type' => 'string',
                                    ),
                                    'Protocol' => array(
                                        'description' => 'Protocol to use (http, https) when redirecting requests. The default is the protocol that is used in the original request.',
                                        'type' => 'string',
                                        'enum' => array(
                                            'http',
                                            'https',
                                        ),
                                    ),
                                    'ReplaceKeyPrefixWith' => array(
                                        'description' => 'The object key prefix to use in the redirect request. For example, to redirect requests for all pages with prefix docs/ (objects in the docs/ folder) to documents/, you can set a condition block with KeyPrefixEquals set to docs/ and in the Redirect set ReplaceKeyPrefixWith to /documents. Not required if one of the siblings is present. Can be present only if ReplaceKeyWith is not provided.',
                                        'type' => 'string',
                                    ),
                                    'ReplaceKeyWith' => array(
                                        'description' => 'The specific object key to use in the redirect request. For example, redirect request to error.html. Not required if one of the sibling is present. Can be present only if ReplaceKeyPrefixWith is not provided.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'website',
                    'default' => '_guzzle_blank_',
                ),
            ),
        ),
        'PutObject' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutObjectOutput',
            'responseType' => 'model',
            'summary' => 'Adds an object to a bucket.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectPUT.html',
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Body' => array(
                    'description' => 'Pass a string containing the body, a handle returned by fopen, or a Guzzle\\Http\\EntityBodyInterface object',
                    'type' => array(
                        'string',
                        'object',
                    ),
                    'location' => 'body',
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CacheControl' => array(
                    'description' => 'Specifies caching behavior along the request/reply chain.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Cache-Control',
                ),
                'ContentDisposition' => array(
                    'description' => 'Specifies presentational information for the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Disposition',
                ),
                'ContentEncoding' => array(
                    'description' => 'Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to obtain the media-type referenced by the Content-Type header field.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Encoding',
                ),
                'ContentLanguage' => array(
                    'description' => 'The language the content is in.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Language',
                ),
                'ContentLength' => array(
                    'description' => 'Size of the body in bytes. This parameter is useful when the size of the body cannot be determined automatically.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'Content-Length',
                ),
                'ContentMD5' => array(
                    'description' => 'Content-MD5 checksum of the body. Set to false to disable',
                    'default' => true,
                ),
                'ContentType' => array(
                    'description' => 'A standard MIME type describing the format of the object data.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'Expires' => array(
                    'description' => 'The date and time at which the object is no longer cacheable.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                ),
                'GrantFullControl' => array(
                    'description' => 'Gives the grantee READ, READ_ACP, and WRITE_ACP permissions on the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to read the object data and its metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the object ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Metadata' => array(
                    'description' => 'A map of metadata to store with the object in S3.',
                    'type' => 'object',
                    'location' => 'header',
                    'sentAs' => 'x-amz-meta-',
                    'additionalProperties' => array(
                        'description' => 'The metadata key. This will be prefixed with x-amz-meta- before sending to S3 as a header. The x-amz-meta- header will be stripped from the key when retrieving headers.',
                        'type' => 'string',
                    ),
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                    'enum' => array(
                        'AES256',
                    ),
                ),
                'StorageClass' => array(
                    'description' => 'The type of storage to use for the object. Defaults to \'STANDARD\'.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-storage-class',
                    'enum' => array(
                        'STANDARD',
                        'REDUCED_REDUNDANCY',
                    ),
                ),
                'WebsiteRedirectLocation' => array(
                    'description' => 'If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or to an external URL. Amazon S3 stores the value of this header in the object metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-website-redirect-location',
                ),
                'ValidateMD5' => array(
                    'description' => 'Whether or not the Content-MD5 header of the response is validated. Default is true.',
                    'default' => true,
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add access control policy headers to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
            ),
        ),
        'PutObjectAcl' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'PutObjectAclOutput',
            'responseType' => 'model',
            'summary' => 'uses the acl subresource to set the access control list (ACL) permissions for an object that already exists in a bucket',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectPUTacl.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'AccessControlPolicy',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'ACL' => array(
                    'description' => 'The canned ACL to apply to the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-acl',
                    'enum' => array(
                        'private',
                        'public-read',
                        'public-read-write',
                        'authenticated-read',
                        'bucket-owner-read',
                        'bucket-owner-full-control',
                    ),
                ),
                'Grants' => array(
                    'description' => 'A list of grants.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'AccessControlList',
                    'items' => array(
                        'name' => 'Grant',
                        'type' => 'object',
                        'properties' => array(
                            'Grantee' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'DisplayName' => array(
                                        'description' => 'Screen name of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'EmailAddress' => array(
                                        'description' => 'Email address of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'ID' => array(
                                        'description' => 'The canonical user ID of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'Type' => array(
                                        'required' => true,
                                        'description' => 'Type of grantee',
                                        'type' => 'string',
                                        'sentAs' => 'xsi:type',
                                        'data' => array(
                                            'xmlAttribute' => true,
                                            'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                        ),
                                        'enum' => array(
                                            'CanonicalUser',
                                            'AmazonCustomerByEmail',
                                            'Group',
                                        ),
                                    ),
                                    'URI' => array(
                                        'description' => 'URI of the grantee group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Permission' => array(
                                'description' => 'Specifies the permission given to the grantee.',
                                'type' => 'string',
                                'enum' => array(
                                    'FULL_CONTROL',
                                    'WRITE',
                                    'WRITE_ACP',
                                    'READ',
                                    'READ_ACP',
                                ),
                            ),
                        ),
                    ),
                ),
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'DisplayName' => array(
                            'type' => 'string',
                        ),
                        'ID' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentMD5' => array(
                    'default' => true,
                ),
                'GrantFullControl' => array(
                    'description' => 'Allows grantee the read, write, read ACP, and write ACP permissions on the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-full-control',
                ),
                'GrantRead' => array(
                    'description' => 'Allows grantee to list the objects in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read',
                ),
                'GrantReadACP' => array(
                    'description' => 'Allows grantee to read the bucket ACL.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-read-acp',
                ),
                'GrantWrite' => array(
                    'description' => 'Allows grantee to create, overwrite, and delete any object in the bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write',
                ),
                'GrantWriteACP' => array(
                    'description' => 'Allows grantee to write the ACL for the applicable bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-grant-write-acp',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'acl',
                    'default' => '_guzzle_blank_',
                ),
                'ACP' => array(
                    'description' => 'Pass an Aws\\S3\\Model\\Acp object as an alternative way to add an access control policy to the operation',
                    'type' => 'object',
                    'additionalProperties' => true,
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'The specified key does not exist.',
                    'class' => 'NoSuchKeyException',
                ),
            ),
        ),
        'RestoreObject' => array(
            'httpMethod' => 'POST',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'RestoreObjectOutput',
            'responseType' => 'model',
            'summary' => 'Restores an archived copy of an object back into Amazon S3',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/RESTObjectRestore.html',
            'data' => array(
                'xmlRoot' => array(
                    'name' => 'RestoreRequest',
                    'namespaces' => array(
                        'http://s3.amazonaws.com/doc/2006-03-01/',
                    ),
                ),
            ),
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'Days' => array(
                    'required' => true,
                    'description' => 'Lifetime of the active copy in days',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'SubResource' => array(
                    'required' => true,
                    'static' => true,
                    'location' => 'query',
                    'sentAs' => 'restore',
                    'default' => '_guzzle_blank_',
                ),
            ),
            'errorResponses' => array(
                array(
                    'reason' => 'This operation is not allowed against this storage tier',
                    'class' => 'ObjectAlreadyInActiveTierErrorException',
                ),
            ),
        ),
        'UploadPart' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'UploadPartOutput',
            'responseType' => 'model',
            'summary' => 'Uploads a part in a multipart upload.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadUploadPart.html',
            'parameters' => array(
                'Body' => array(
                    'description' => 'Pass a string containing the body, a handle returned by fopen, or a Guzzle\\Http\\EntityBodyInterface object',
                    'type' => array(
                        'string',
                        'object',
                    ),
                    'location' => 'body',
                ),
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'ContentLength' => array(
                    'description' => 'Size of the body in bytes. This parameter is useful when the size of the body cannot be determined automatically.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'Content-Length',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'PartNumber' => array(
                    'required' => true,
                    'description' => 'Part number of part being uploaded.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'partNumber',
                ),
                'UploadId' => array(
                    'required' => true,
                    'description' => 'Upload ID identifying the multipart upload whose part is being uploaded.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
                'ContentMD5' => array(
                    'description' => 'Content-MD5 checksum of the body. Set to false to disable',
                    'default' => true,
                ),
                'ValidateMD5' => array(
                    'description' => 'Whether or not the Content-MD5 header of the response is validated. Default is true.',
                    'default' => true,
                ),
            ),
        ),
        'UploadPartCopy' => array(
            'httpMethod' => 'PUT',
            'uri' => '/{Bucket}{/Key*}',
            'class' => 'Aws\\S3\\Command\\S3Command',
            'responseClass' => 'UploadPartCopyOutput',
            'responseType' => 'model',
            'summary' => 'Uploads a part by copying data from an existing object as data source.',
            'documentationUrl' => 'http://docs.amazonwebservices.com/AmazonS3/latest/API/mpUploadUploadPartCopy.html',
            'parameters' => array(
                'Bucket' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                ),
                'CopySource' => array(
                    'required' => true,
                    'description' => 'The name of the source bucket and key name of the source object, separated by a slash (/). Must be URL-encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source',
                ),
                'CopySourceIfMatch' => array(
                    'description' => 'Copies the object if its entity tag (ETag) matches the specified tag.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-match',
                ),
                'CopySourceIfModifiedSince' => array(
                    'description' => 'Copies the object if it has been modified since the specified time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-modified-since',
                ),
                'CopySourceIfNoneMatch' => array(
                    'description' => 'Copies the object if its entity tag (ETag) is different than the specified ETag.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-none-match',
                ),
                'CopySourceIfUnmodifiedSince' => array(
                    'description' => 'Copies the object if it hasn\'t been modified since the specified time.',
                    'type' => array(
                        'object',
                        'string',
                        'integer',
                    ),
                    'format' => 'date-time-http',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-if-unmodified-since',
                ),
                'CopySourceRange' => array(
                    'description' => 'The range of bytes to copy from the source object. The range value must use the form bytes=first-last, where the first and last are the zero-based byte offsets to copy. For example, bytes=0-9 indicates that you want to copy the first ten bytes of the source. You can copy a range only if the source object is greater than 5 GB.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-range',
                ),
                'Key' => array(
                    'required' => true,
                    'type' => 'string',
                    'location' => 'uri',
                    'filters' => array(
                        'Aws\\S3\\S3Client::explodeKey',
                    ),
                ),
                'PartNumber' => array(
                    'required' => true,
                    'description' => 'Part number of part being copied.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'partNumber',
                ),
                'UploadId' => array(
                    'required' => true,
                    'description' => 'Upload ID identifying the multipart upload whose part is being copied.',
                    'type' => 'string',
                    'location' => 'query',
                    'sentAs' => 'uploadId',
                ),
                'command.expects' => array(
                    'static' => true,
                    'default' => 'application/xml',
                ),
            ),
        ),
    ),
    'models' => array(
        'AbortMultipartUploadOutput' => array(
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
        'CompleteMultipartUploadOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Location' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Bucket' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Key' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Expiration' => array(
                    'description' => 'If the object expiration is configured, this will contain the expiration date (expiry-date) and rule ID (rule-id). The value of rule-id is URL encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-expiration',
                ),
                'ETag' => array(
                    'description' => 'Entity tag of the object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'VersionId' => array(
                    'description' => 'Version of the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-version-id',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CopyObjectOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ETag' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModified' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Expiration' => array(
                    'description' => 'If the object expiration is configured, the response includes this header.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-expiration',
                ),
                'CopySourceVersionId' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-version-id',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'CreateBucketOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Location' => array(
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
        'CreateMultipartUploadOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Bucket' => array(
                    'description' => 'Name of the bucket to which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                    'sentAs' => 'Bucket',
                ),
                'Key' => array(
                    'description' => 'Object key for which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'UploadId' => array(
                    'description' => 'ID for the initiated multipart upload.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteBucketOutput' => array(
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
        'DeleteBucketCorsOutput' => array(
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
        'DeleteBucketLifecycleOutput' => array(
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
        'DeleteBucketPolicyOutput' => array(
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
        'DeleteBucketTaggingOutput' => array(
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
        'DeleteBucketWebsiteOutput' => array(
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
        'DeleteObjectOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DeleteMarker' => array(
                    'description' => 'Specifies whether the versioned object that was permanently deleted was (true) or was not (false) a delete marker.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-delete-marker',
                ),
                'VersionId' => array(
                    'description' => 'Returns the version ID of the delete marker created as a result of the DELETE operation.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-version-id',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'DeleteObjectsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Deleted' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'type' => 'string',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                            ),
                            'DeleteMarker' => array(
                                'type' => 'boolean',
                            ),
                            'DeleteMarkerVersionId' => array(
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Errors' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Error',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Error',
                        'properties' => array(
                            'Key' => array(
                                'type' => 'string',
                            ),
                            'VersionId' => array(
                                'type' => 'string',
                            ),
                            'Code' => array(
                                'type' => 'string',
                            ),
                            'Message' => array(
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
        'GetBucketAclOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ID' => array(
                            'type' => 'string',
                        ),
                        'DisplayName' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'Grants' => array(
                    'description' => 'A list of grants.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'AccessControlList',
                    'items' => array(
                        'name' => 'Grant',
                        'type' => 'object',
                        'sentAs' => 'Grant',
                        'properties' => array(
                            'Grantee' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Type' => array(
                                        'description' => 'Type of grantee',
                                        'type' => 'string',
                                        'sentAs' => 'xsi:type',
                                        'data' => array(
                                            'xmlAttribute' => true,
                                            'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                        ),
                                    ),
                                    'ID' => array(
                                        'description' => 'The canonical user ID of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'description' => 'Screen name of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'EmailAddress' => array(
                                        'description' => 'Email address of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'URI' => array(
                                        'description' => 'URI of the grantee group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Permission' => array(
                                'description' => 'Specifies the permission given to the grantee.',
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
        'GetBucketCorsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CORSRules' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'CORSRule',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'CORSRule',
                        'properties' => array(
                            'AllowedHeaders' => array(
                                'description' => 'Specifies which headers are allowed in a pre-flight OPTIONS request.',
                                'type' => 'array',
                                'sentAs' => 'AllowedHeader',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedHeader',
                                ),
                            ),
                            'AllowedOrigins' => array(
                                'description' => 'One or more origins you want customers to be able to access the bucket from.',
                                'type' => 'array',
                                'sentAs' => 'AllowedOrigin',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedOrigin',
                                ),
                            ),
                            'AllowedMethods' => array(
                                'description' => 'Identifies HTTP methods that the domain/origin specified in the rule is allowed to execute.',
                                'type' => 'array',
                                'sentAs' => 'AllowedMethod',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'AllowedMethod',
                                ),
                            ),
                            'MaxAgeSeconds' => array(
                                'description' => 'The time in seconds that your browser is to cache the preflight response for the specified resource.',
                                'type' => 'numeric',
                            ),
                            'ExposeHeaders' => array(
                                'description' => 'One or more headers in the response that you want customers to be able to access from their applications (for example, from a JavaScript XMLHttpRequest object).',
                                'type' => 'array',
                                'sentAs' => 'ExposeHeader',
                                'data' => array(
                                    'xmlFlattened' => true,
                                ),
                                'items' => array(
                                    'type' => 'string',
                                    'sentAs' => 'ExposeHeader',
                                ),
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
        'GetBucketLifecycleOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Rules' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Rule',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Rule',
                        'properties' => array(
                            'ID' => array(
                                'description' => 'Unique identifier for the rule. The value cannot be longer than 255 characters.',
                                'type' => 'string',
                            ),
                            'Prefix' => array(
                                'description' => 'Prefix identifying one or more objects to which the rule applies.',
                                'type' => 'string',
                            ),
                            'Status' => array(
                                'description' => 'If \'Enabled\', the rule is currently being applied. If \'Disabled\', the rule is not currently being applied.',
                                'type' => 'string',
                            ),
                            'Transition' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Days' => array(
                                        'description' => 'Indicates the lifetime, in days, of the objects that are subject to the rule. The value must be a non-zero positive integer.',
                                        'type' => 'numeric',
                                    ),
                                    'Date' => array(
                                        'description' => 'Indicates at what date the object is to be moved or deleted. Should be in GMT ISO 8601 Format.',
                                        'type' => 'string',
                                    ),
                                    'StorageClass' => array(
                                        'description' => 'The class of storage used to store the object.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Expiration' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Days' => array(
                                        'description' => 'Indicates the lifetime, in days, of the objects that are subject to the rule. The value must be a non-zero positive integer.',
                                        'type' => 'numeric',
                                    ),
                                    'Date' => array(
                                        'description' => 'Indicates at what date the object is to be moved or deleted. Should be in GMT ISO 8601 Format.',
                                        'type' => 'string',
                                    ),
                                ),
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
        'GetBucketLocationOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Location' => array(
                    'type' => 'string',
                    'location' => 'body',
                    'filters' => array(
                        'strval',
                        'strip_tags',
                        'trim',
                    ),
                ),
            ),
        ),
        'GetBucketLoggingOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'LoggingEnabled' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'TargetBucket' => array(
                            'description' => 'Specifies the bucket where you want Amazon S3 to store server access logs. You can have your logs delivered to any bucket that you own, including the same bucket that is being logged. You can also configure multiple buckets to deliver their logs to the same target bucket. In this case you should choose a different TargetPrefix for each source bucket so that the delivered log files can be distinguished by key.',
                            'type' => 'string',
                        ),
                        'TargetPrefix' => array(
                            'description' => 'This element lets you specify a prefix for the keys that the log files will be stored under.',
                            'type' => 'string',
                        ),
                        'TargetGrants' => array(
                            'type' => 'array',
                            'items' => array(
                                'name' => 'Grant',
                                'type' => 'object',
                                'sentAs' => 'Grant',
                                'properties' => array(
                                    'Grantee' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'Type' => array(
                                                'description' => 'Type of grantee',
                                                'type' => 'string',
                                                'sentAs' => 'xsi:type',
                                                'data' => array(
                                                    'xmlAttribute' => true,
                                                    'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                                ),
                                            ),
                                            'ID' => array(
                                                'description' => 'The canonical user ID of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'DisplayName' => array(
                                                'description' => 'Screen name of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'EmailAddress' => array(
                                                'description' => 'Email address of the grantee.',
                                                'type' => 'string',
                                            ),
                                            'URI' => array(
                                                'description' => 'URI of the grantee group.',
                                                'type' => 'string',
                                            ),
                                        ),
                                    ),
                                    'Permission' => array(
                                        'type' => 'string',
                                    ),
                                ),
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
        'GetBucketNotificationOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TopicConfiguration' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Topic' => array(
                            'description' => 'Amazon SNS topic to which Amazon S3 will publish a message to report the specified events for the bucket.',
                            'type' => 'string',
                        ),
                        'Event' => array(
                            'description' => 'Bucket event for which to send notifications.',
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
        'GetBucketPolicyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Policy' => array(
                    'description' => 'The bucket policy as a JSON document.',
                    'type' => 'string',
                    'instanceOf' => 'Guzzle\\Http\\EntityBody',
                    'location' => 'body',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetBucketRequestPaymentOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Payer' => array(
                    'description' => 'Specifies who pays for the download and request fees.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetBucketTaggingOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'TagSet' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Tag',
                        'type' => 'object',
                        'sentAs' => 'Tag',
                        'properties' => array(
                            'Key' => array(
                                'description' => 'Name of the tag.',
                                'type' => 'string',
                            ),
                            'Value' => array(
                                'description' => 'Value of the tag.',
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
        'GetBucketVersioningOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Status' => array(
                    'description' => 'The versioning state of the bucket.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MFADelete' => array(
                    'description' => 'Specifies whether MFA delete is enabled in the bucket versioning configuration. This element is only returned if the bucket has been configured with MFA delete. If the bucket has never been so configured, this element is not returned.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetBucketWebsiteOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'RedirectAllRequestsTo' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'HostName' => array(
                            'description' => 'Name of the host where requests will be redirected.',
                            'type' => 'string',
                        ),
                        'Protocol' => array(
                            'description' => 'Protocol to use (http, https) when redirecting requests. The default is the protocol that is used in the original request.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'IndexDocument' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Suffix' => array(
                            'description' => 'A suffix that is appended to a request that is for a directory on the website endpoint (e.g. if the suffix is index.html and you make a request to samplebucket/images/ the data that is returned will be for the object with the key name images/index.html) The suffix must not be empty and must not include a slash character.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'ErrorDocument' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'Key' => array(
                            'description' => 'The object key name to use when a 4XX class error occurs.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'RoutingRules' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'RoutingRule',
                        'type' => 'object',
                        'sentAs' => 'RoutingRule',
                        'properties' => array(
                            'Condition' => array(
                                'description' => 'A container for describing a condition that must be met for the specified redirect to apply. For example, 1. If request is for pages in the /docs folder, redirect to the /documents folder. 2. If request results in HTTP error 4xx, redirect request to another host where you might process the error.',
                                'type' => 'object',
                                'properties' => array(
                                    'KeyPrefixEquals' => array(
                                        'description' => 'The object key name prefix when the redirect is applied. For example, to redirect requests for ExamplePage.html, the key prefix will be ExamplePage.html. To redirect request for all pages with the prefix docs/, the key prefix will be /docs, which identifies all objects in the docs/ folder. Required when the parent element Condition is specified and sibling HttpErrorCodeReturnedEquals is not specified. If both conditions are specified, both must be true for the redirect to be applied.',
                                        'type' => 'string',
                                    ),
                                    'HttpErrorCodeReturnedEquals' => array(
                                        'description' => 'The HTTP error code when the redirect is applied. In the event of an error, if the error code equals this value, then the specified redirect is applied. Required when parent element Condition is specified and sibling KeyPrefixEquals is not specified. If both are specified, then both must be true for the redirect to be applied.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Redirect' => array(
                                'description' => 'Container for redirect information. You can redirect requests to another host, to another page, or with another protocol. In the event of an error, you can can specify a different error code to return.',
                                'type' => 'object',
                                'properties' => array(
                                    'ReplaceKeyPrefixWith' => array(
                                        'description' => 'The object key prefix to use in the redirect request. For example, to redirect requests for all pages with prefix docs/ (objects in the docs/ folder) to documents/, you can set a condition block with KeyPrefixEquals set to docs/ and in the Redirect set ReplaceKeyPrefixWith to /documents. Not required if one of the siblings is present. Can be present only if ReplaceKeyWith is not provided.',
                                        'type' => 'string',
                                    ),
                                    'ReplaceKeyWith' => array(
                                        'description' => 'The specific object key to use in the redirect request. For example, redirect request to error.html. Not required if one of the sibling is present. Can be present only if ReplaceKeyPrefixWith is not provided.',
                                        'type' => 'string',
                                    ),
                                    'HttpRedirectCode' => array(
                                        'description' => 'The HTTP redirect code to use on the response. Not required if one of the siblings is present.',
                                        'type' => 'string',
                                    ),
                                    'HostName' => array(
                                        'description' => 'Name of the host where requests will be redirected.',
                                        'type' => 'string',
                                    ),
                                    'Protocol' => array(
                                        'description' => 'Protocol to use (http, https) when redirecting requests. The default is the protocol that is used in the original request.',
                                        'type' => 'string',
                                    ),
                                ),
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
        'GetObjectOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Body' => array(
                    'description' => 'Object data.',
                    'type' => 'string',
                    'instanceOf' => 'Guzzle\\Http\\EntityBody',
                    'location' => 'body',
                ),
                'DeleteMarker' => array(
                    'description' => 'Specifies whether the object retrieved was (true) or was not (false) a Delete Marker. If false, this response header does not appear in the response.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-delete-marker',
                ),
                'AcceptRanges' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'accept-ranges',
                ),
                'Expiration' => array(
                    'description' => 'If the object expiration is configured (see PUT Bucket lifecycle), the response includes this header. It includes the expiry-date and rule-id key value pairs providing object expiration information. The value of the rule-id is URL encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-expiration',
                ),
                'Restore' => array(
                    'description' => 'Provides information about object restoration operation and expiration time of the restored object copy.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-restore',
                ),
                'LastModified' => array(
                    'description' => 'Last modified date of the object',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Last-Modified',
                ),
                'ContentLength' => array(
                    'description' => 'Size of the body in bytes.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'Content-Length',
                ),
                'ETag' => array(
                    'description' => 'An ETag is an opaque identifier assigned by a web server to a specific version of a resource found at a URL',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'MissingMeta' => array(
                    'description' => 'This is set to the number of metadata entries not returned in x-amz-meta headers. This can happen if you create metadata using an API like SOAP that supports more flexible metadata than the REST API. For example, using SOAP, you can create metadata whose values are not legal HTTP headers.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'x-amz-missing-meta',
                ),
                'VersionId' => array(
                    'description' => 'Version of the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-version-id',
                ),
                'CacheControl' => array(
                    'description' => 'Specifies caching behavior along the request/reply chain.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Cache-Control',
                ),
                'ContentDisposition' => array(
                    'description' => 'Specifies presentational information for the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Disposition',
                ),
                'ContentEncoding' => array(
                    'description' => 'Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to obtain the media-type referenced by the Content-Type header field.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Encoding',
                ),
                'ContentLanguage' => array(
                    'description' => 'The language the content is in.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Language',
                ),
                'ContentType' => array(
                    'description' => 'A standard MIME type describing the format of the object data.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'Expires' => array(
                    'description' => 'The date and time at which the object is no longer cacheable.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'WebsiteRedirectLocation' => array(
                    'description' => 'If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or to an external URL. Amazon S3 stores the value of this header in the object metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-website-redirect-location',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'Metadata' => array(
                    'description' => 'A map of metadata to store with the object in S3.',
                    'type' => 'object',
                    'location' => 'header',
                    'sentAs' => 'x-amz-meta-',
                    'additionalProperties' => array(
                        'description' => 'The metadata value.',
                        'type' => 'string',
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'GetObjectAclOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ID' => array(
                            'type' => 'string',
                        ),
                        'DisplayName' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'Grants' => array(
                    'description' => 'A list of grants.',
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'AccessControlList',
                    'items' => array(
                        'name' => 'Grant',
                        'type' => 'object',
                        'sentAs' => 'Grant',
                        'properties' => array(
                            'Grantee' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'Type' => array(
                                        'description' => 'Type of grantee',
                                        'type' => 'string',
                                        'sentAs' => 'xsi:type',
                                        'data' => array(
                                            'xmlAttribute' => true,
                                            'xmlNamespace' => 'http://www.w3.org/2001/XMLSchema-instance',
                                        ),
                                    ),
                                    'ID' => array(
                                        'description' => 'The canonical user ID of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'description' => 'Screen name of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'EmailAddress' => array(
                                        'description' => 'Email address of the grantee.',
                                        'type' => 'string',
                                    ),
                                    'URI' => array(
                                        'description' => 'URI of the grantee group.',
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Permission' => array(
                                'description' => 'Specifies the permission given to the grantee.',
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
        'GetObjectTorrentOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Body' => array(
                    'type' => 'string',
                    'instanceOf' => 'Guzzle\\Http\\EntityBody',
                    'location' => 'body',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'HeadBucketOutput' => array(
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
        'HeadObjectOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'DeleteMarker' => array(
                    'description' => 'Specifies whether the object retrieved was (true) or was not (false) a Delete Marker. If false, this response header does not appear in the response.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-delete-marker',
                ),
                'AcceptRanges' => array(
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'accept-ranges',
                ),
                'Expiration' => array(
                    'description' => 'If the object expiration is configured (see PUT Bucket lifecycle), the response includes this header. It includes the expiry-date and rule-id key value pairs providing object expiration information. The value of the rule-id is URL encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-expiration',
                ),
                'Restore' => array(
                    'description' => 'Provides information about object restoration operation and expiration time of the restored object copy.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-restore',
                ),
                'LastModified' => array(
                    'description' => 'Last modified date of the object',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Last-Modified',
                ),
                'ContentLength' => array(
                    'description' => 'Size of the body in bytes.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'Content-Length',
                ),
                'ETag' => array(
                    'description' => 'An ETag is an opaque identifier assigned by a web server to a specific version of a resource found at a URL',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'MissingMeta' => array(
                    'description' => 'This is set to the number of metadata entries not returned in x-amz-meta headers. This can happen if you create metadata using an API like SOAP that supports more flexible metadata than the REST API. For example, using SOAP, you can create metadata whose values are not legal HTTP headers.',
                    'type' => 'numeric',
                    'location' => 'header',
                    'sentAs' => 'x-amz-missing-meta',
                ),
                'VersionId' => array(
                    'description' => 'Version of the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-version-id',
                ),
                'CacheControl' => array(
                    'description' => 'Specifies caching behavior along the request/reply chain.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Cache-Control',
                ),
                'ContentDisposition' => array(
                    'description' => 'Specifies presentational information for the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Disposition',
                ),
                'ContentEncoding' => array(
                    'description' => 'Specifies what content encodings have been applied to the object and thus what decoding mechanisms must be applied to obtain the media-type referenced by the Content-Type header field.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Encoding',
                ),
                'ContentLanguage' => array(
                    'description' => 'The language the content is in.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Language',
                ),
                'ContentType' => array(
                    'description' => 'A standard MIME type describing the format of the object data.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'Content-Type',
                ),
                'Expires' => array(
                    'description' => 'The date and time at which the object is no longer cacheable.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'WebsiteRedirectLocation' => array(
                    'description' => 'If the bucket is configured as a website, redirects requests for this object to another object in the same bucket or to an external URL. Amazon S3 stores the value of this header in the object metadata.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-website-redirect-location',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'Metadata' => array(
                    'description' => 'A map of metadata to store with the object in S3.',
                    'type' => 'object',
                    'location' => 'header',
                    'sentAs' => 'x-amz-meta-',
                    'additionalProperties' => array(
                        'description' => 'The metadata value.',
                        'type' => 'string',
                    ),
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'ListBucketsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Buckets' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'items' => array(
                        'name' => 'Bucket',
                        'type' => 'object',
                        'sentAs' => 'Bucket',
                        'properties' => array(
                            'Name' => array(
                                'description' => 'The name of the bucket.',
                                'type' => 'string',
                            ),
                            'CreationDate' => array(
                                'description' => 'Date the bucket was created.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ID' => array(
                            'type' => 'string',
                        ),
                        'DisplayName' => array(
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
        'ListMultipartUploadsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Bucket' => array(
                    'description' => 'Name of the bucket to which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'KeyMarker' => array(
                    'description' => 'The key at or after which the listing began.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'UploadIdMarker' => array(
                    'description' => 'Upload ID after which listing began.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextKeyMarker' => array(
                    'description' => 'When a list is truncated, this element specifies the value that should be used for the key-marker request parameter in a subsequent request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextUploadIdMarker' => array(
                    'description' => 'When a list is truncated, this element specifies the value that should be used for the upload-id-marker request parameter in a subsequent request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxUploads' => array(
                    'description' => 'Maximum number of multipart uploads that could have been included in the response.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'Indicates whether the returned list of multipart uploads is truncated. A value of true indicates that the list was truncated. The list can be truncated if the number of multipart uploads exceeds the limit allowed or specified by max uploads.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Uploads' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Upload',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Upload',
                        'properties' => array(
                            'UploadId' => array(
                                'description' => 'Upload ID that identifies the multipart upload.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'description' => 'Key of the object for which the multipart upload was initiated.',
                                'type' => 'string',
                            ),
                            'Initiated' => array(
                                'description' => 'Date and time at which the multipart upload was initiated.',
                                'type' => 'string',
                            ),
                            'StorageClass' => array(
                                'description' => 'The class of storage used to store the object.',
                                'type' => 'string',
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Initiator' => array(
                                'description' => 'Identifies who initiated the multipart upload.',
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'description' => 'If the principal is an AWS account, it provides the Canonical User ID. If the principal is an IAM User, it provides a user ARN value.',
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'description' => 'Name of the Principal.',
                                        'type' => 'string',
                                    ),
                                ),
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
        'ListObjectVersionsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether or not Amazon S3 returned all of the results that satisfied the search criteria. If your results were truncated, you can make a follow-up paginated request using the NextKeyMarker and NextVersionIdMarker response parameters as a starting place in another request to return the rest of the results.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'KeyMarker' => array(
                    'description' => 'Marks the last Key returned in a truncated response.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'VersionIdMarker' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextKeyMarker' => array(
                    'description' => 'Use this value for the key marker request parameter in a subsequent request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'NextVersionIdMarker' => array(
                    'description' => 'Use this value for the next version id marker parameter in a subsequent request.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Versions' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Version',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Version',
                        'properties' => array(
                            'ETag' => array(
                                'type' => 'string',
                            ),
                            'Size' => array(
                                'description' => 'Size in bytes of the object.',
                                'type' => 'string',
                            ),
                            'StorageClass' => array(
                                'description' => 'The class of storage used to store the object.',
                                'type' => 'string',
                            ),
                            'Key' => array(
                                'description' => 'The object key.',
                                'type' => 'string',
                            ),
                            'VersionId' => array(
                                'description' => 'Version ID of an object.',
                                'type' => 'string',
                            ),
                            'IsLatest' => array(
                                'description' => 'Specifies whether the object is (true) or is not (false) the latest version of an object.',
                                'type' => 'boolean',
                            ),
                            'LastModified' => array(
                                'description' => 'Date and time the object was last modified.',
                                'type' => 'string',
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'DeleteMarkers' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'DeleteMarker',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'DeleteMarker',
                        'properties' => array(
                            'Owner' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                            'Key' => array(
                                'description' => 'The object key.',
                                'type' => 'string',
                            ),
                            'VersionId' => array(
                                'description' => 'Version ID of an object.',
                                'type' => 'string',
                            ),
                            'IsLatest' => array(
                                'description' => 'Specifies whether the object is (true) or is not (false) the latest version of an object.',
                                'type' => 'boolean',
                            ),
                            'LastModified' => array(
                                'description' => 'Date and time the object was last modified.',
                                'type' => 'string',
                            ),
                        ),
                    ),
                ),
                'Name' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Prefix' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxKeys' => array(
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'CommonPrefixes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'Prefix' => array(
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
        'ListObjectsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'IsTruncated' => array(
                    'description' => 'A flag that indicates whether or not Amazon S3 returned all of the results that satisfied the search criteria.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Marker' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Contents' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'Key' => array(
                                'type' => 'string',
                            ),
                            'LastModified' => array(
                                'type' => 'string',
                            ),
                            'ETag' => array(
                                'type' => 'string',
                            ),
                            'Size' => array(
                                'type' => 'numeric',
                            ),
                            'StorageClass' => array(
                                'description' => 'The class of storage used to store the object.',
                                'type' => 'string',
                            ),
                            'Owner' => array(
                                'type' => 'object',
                                'properties' => array(
                                    'ID' => array(
                                        'type' => 'string',
                                    ),
                                    'DisplayName' => array(
                                        'type' => 'string',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'Name' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Prefix' => array(
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'MaxKeys' => array(
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'CommonPrefixes' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'properties' => array(
                            'Prefix' => array(
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
        'ListPartsOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Bucket' => array(
                    'description' => 'Name of the bucket to which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'Key' => array(
                    'description' => 'Object key for which the multipart upload was initiated.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'UploadId' => array(
                    'description' => 'Upload ID identifying the multipart upload whose parts are being listed.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'PartNumberMarker' => array(
                    'description' => 'Part number after which listing begins.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'NextPartNumberMarker' => array(
                    'description' => 'When a list is truncated, this element specifies the last part in the list, as well as the value to use for the part-number-marker request parameter in a subsequent request.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'MaxParts' => array(
                    'description' => 'Maximum number of parts that were allowed in the response.',
                    'type' => 'numeric',
                    'location' => 'xml',
                ),
                'IsTruncated' => array(
                    'description' => 'Indicates whether the returned list of parts is truncated.',
                    'type' => 'boolean',
                    'location' => 'xml',
                ),
                'Parts' => array(
                    'type' => 'array',
                    'location' => 'xml',
                    'sentAs' => 'Part',
                    'data' => array(
                        'xmlFlattened' => true,
                    ),
                    'items' => array(
                        'type' => 'object',
                        'sentAs' => 'Part',
                        'properties' => array(
                            'PartNumber' => array(
                                'description' => 'Part number identifying the part.',
                                'type' => 'numeric',
                            ),
                            'LastModified' => array(
                                'description' => 'Date and time at which the part was uploaded.',
                                'type' => 'string',
                            ),
                            'ETag' => array(
                                'description' => 'Entity tag returned when the part was uploaded.',
                                'type' => 'string',
                            ),
                            'Size' => array(
                                'description' => 'Size of the uploaded part data.',
                                'type' => 'numeric',
                            ),
                        ),
                    ),
                ),
                'Initiator' => array(
                    'description' => 'Identifies who initiated the multipart upload.',
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ID' => array(
                            'description' => 'If the principal is an AWS account, it provides the Canonical User ID. If the principal is an IAM User, it provides a user ARN value.',
                            'type' => 'string',
                        ),
                        'DisplayName' => array(
                            'description' => 'Name of the Principal.',
                            'type' => 'string',
                        ),
                    ),
                ),
                'Owner' => array(
                    'type' => 'object',
                    'location' => 'xml',
                    'properties' => array(
                        'ID' => array(
                            'type' => 'string',
                        ),
                        'DisplayName' => array(
                            'type' => 'string',
                        ),
                    ),
                ),
                'StorageClass' => array(
                    'description' => 'The class of storage used to store the object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
            ),
        ),
        'PutBucketAclOutput' => array(
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
        'PutBucketCorsOutput' => array(
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
        'PutBucketLifecycleOutput' => array(
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
        'PutBucketLoggingOutput' => array(
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
        'PutBucketNotificationOutput' => array(
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
        'PutBucketPolicyOutput' => array(
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
        'PutBucketRequestPaymentOutput' => array(
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
        'PutBucketTaggingOutput' => array(
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
        'PutBucketVersioningOutput' => array(
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
        'PutBucketWebsiteOutput' => array(
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
        'PutObjectOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'Expiration' => array(
                    'description' => 'If the object expiration is configured, this will contain the expiration date (expiry-date) and rule ID (rule-id). The value of rule-id is URL encoded.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-expiration',
                ),
                'ETag' => array(
                    'description' => 'Entity tag for the uploaded object.',
                    'type' => 'string',
                    'location' => 'header',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'VersionId' => array(
                    'description' => 'Version of the object.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-version-id',
                ),
                'RequestId' => array(
                    'description' => 'Request ID of the operation',
                    'location' => 'header',
                    'sentAs' => 'x-amz-request-id',
                ),
                'ObjectURL' => array(
                    'description' => 'URL of the uploaded object',
                ),
            ),
        ),
        'PutObjectAclOutput' => array(
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
        'RestoreObjectOutput' => array(
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
        'UploadPartOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
                ),
                'ETag' => array(
                    'description' => 'Entity tag for the uploaded object.',
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
        'UploadPartCopyOutput' => array(
            'type' => 'object',
            'additionalProperties' => true,
            'properties' => array(
                'CopySourceVersionId' => array(
                    'description' => 'The version of the source object that was copied, if you have enabled versioning on the source bucket.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-copy-source-version-id',
                ),
                'ETag' => array(
                    'description' => 'Entity tag of the object.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'LastModified' => array(
                    'description' => 'Date and time at which the object was uploaded.',
                    'type' => 'string',
                    'location' => 'xml',
                ),
                'ServerSideEncryption' => array(
                    'description' => 'The Server-side encryption algorithm used when storing this object in S3.',
                    'type' => 'string',
                    'location' => 'header',
                    'sentAs' => 'x-amz-server-side-encryption',
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
            'interval' => 5,
            'max_attempts' => 20,
        ),
        'BucketExists' => array(
            'operation' => 'HeadBucket',
            'description' => 'Wait until a bucket exists.',
            'success.type' => 'output',
            'ignore_errors' => array(
                'NoSuchBucket',
            ),
        ),
        'BucketNotExists' => array(
            'operation' => 'HeadBucket',
            'description' => 'Wait until a bucket does not exist.',
            'success.type' => 'error',
            'success.value' => 'NoSuchBucket',
        ),
        'ObjectExists' => array(
            'operation' => 'HeadObject',
            'description' => 'Wait until an object exists.',
            'success.type' => 'output',
            'ignore_errors' => array(
                'NoSuchKey',
            ),
        ),
    ),
);
