<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for Storage (v1).
 *
 * <p>
 * Lets you store and retrieve potentially-large, immutable data objects.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/storage/docs/json_api/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Storage extends Google_Service
{
  /** View and manage your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View your data across Google Cloud Platform services. */
  const CLOUD_PLATFORM_READ_ONLY =
      "https://www.googleapis.com/auth/cloud-platform.read-only";
  /** Manage your data and permissions in Google Cloud Storage. */
  const DEVSTORAGE_FULL_CONTROL =
      "https://www.googleapis.com/auth/devstorage.full_control";
  /** View your data in Google Cloud Storage. */
  const DEVSTORAGE_READ_ONLY =
      "https://www.googleapis.com/auth/devstorage.read_only";
  /** Manage your data in Google Cloud Storage. */
  const DEVSTORAGE_READ_WRITE =
      "https://www.googleapis.com/auth/devstorage.read_write";

  public $bucketAccessControls;
  public $buckets;
  public $channels;
  public $defaultObjectAccessControls;
  public $objectAccessControls;
  public $objects;
  

  /**
   * Constructs the internal representation of the Storage service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'storage/v1/';
    $this->version = 'v1';
    $this->serviceName = 'storage';

    $this->bucketAccessControls = new Google_Service_Storage_BucketAccessControls_Resource(
        $this,
        $this->serviceName,
        'bucketAccessControls',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'b/{bucket}/acl',
              'httpMethod' => 'POST',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'b/{bucket}/acl',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'b/{bucket}/acl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->buckets = new Google_Service_Storage_Buckets_Resource(
        $this,
        $this->serviceName,
        'buckets',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'b/{bucket}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'b/{bucket}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'b',
              'httpMethod' => 'POST',
              'parameters' => array(
                'project' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'predefinedDefaultObjectAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'b',
              'httpMethod' => 'GET',
              'parameters' => array(
                'project' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'prefix' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => 'b/{bucket}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'predefinedDefaultObjectAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'b/{bucket}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'predefinedDefaultObjectAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->channels = new Google_Service_Storage_Channels_Resource(
        $this,
        $this->serviceName,
        'channels',
        array(
          'methods' => array(
            'stop' => array(
              'path' => 'channels/stop',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->defaultObjectAccessControls = new Google_Service_Storage_DefaultObjectAccessControls_Resource(
        $this,
        $this->serviceName,
        'defaultObjectAccessControls',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'b/{bucket}/defaultObjectAcl',
              'httpMethod' => 'POST',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'b/{bucket}/defaultObjectAcl',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'b/{bucket}/defaultObjectAcl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->objectAccessControls = new Google_Service_Storage_ObjectAccessControls_Resource(
        $this,
        $this->serviceName,
        'objectAccessControls',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'b/{bucket}/o/{object}/acl',
              'httpMethod' => 'POST',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'b/{bucket}/o/{object}/acl',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'b/{bucket}/o/{object}/acl/{entity}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'entity' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->objects = new Google_Service_Storage_Objects_Resource(
        $this,
        $this->serviceName,
        'objects',
        array(
          'methods' => array(
            'compose' => array(
              'path' => 'b/{destinationBucket}/o/{destinationObject}/compose',
              'httpMethod' => 'POST',
              'parameters' => array(
                'destinationBucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'destinationObject' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'destinationPredefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'copy' => array(
              'path' => 'b/{sourceBucket}/o/{sourceObject}/copyTo/b/{destinationBucket}/o/{destinationObject}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'sourceBucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sourceObject' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'destinationBucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'destinationObject' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifSourceGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sourceGeneration' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'destinationPredefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'delete' => array(
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'b/{bucket}/o',
              'httpMethod' => 'POST',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'contentEncoding' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'b/{bucket}/o',
              'httpMethod' => 'GET',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'versions' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'prefix' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'delimiter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'rewrite' => array(
              'path' => 'b/{sourceBucket}/o/{sourceObject}/rewriteTo/b/{destinationBucket}/o/{destinationObject}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'sourceBucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sourceObject' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'destinationBucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'destinationObject' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ifSourceGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'rewriteToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sourceGeneration' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'destinationPredefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxBytesRewrittenPerCall' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifSourceMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'b/{bucket}/o/{object}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'object' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'predefinedAcl' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'generation' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifGenerationMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ifMetagenerationNotMatch' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'watchAll' => array(
              'path' => 'b/{bucket}/o/watch',
              'httpMethod' => 'POST',
              'parameters' => array(
                'bucket' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'versions' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'prefix' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'delimiter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "bucketAccessControls" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $bucketAccessControls = $storageService->bucketAccessControls;
 *  </code>
 */
class Google_Service_Storage_BucketAccessControls_Resource extends Google_Service_Resource
{

  /**
   * Permanently deletes the ACL entry for the specified entity on the specified
   * bucket. (bucketAccessControls.delete)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   */
  public function delete($bucket, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns the ACL entry for the specified entity on the specified bucket.
   * (bucketAccessControls.get)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_BucketAccessControl
   */
  public function get($bucket, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Storage_BucketAccessControl");
  }

  /**
   * Creates a new ACL entry on the specified bucket.
   * (bucketAccessControls.insert)
   *
   * @param string $bucket Name of a bucket.
   * @param Google_BucketAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_BucketAccessControl
   */
  public function insert($bucket, Google_Service_Storage_BucketAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Storage_BucketAccessControl");
  }

  /**
   * Retrieves ACL entries on the specified bucket.
   * (bucketAccessControls.listBucketAccessControls)
   *
   * @param string $bucket Name of a bucket.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_BucketAccessControls
   */
  public function listBucketAccessControls($bucket, $optParams = array())
  {
    $params = array('bucket' => $bucket);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Storage_BucketAccessControls");
  }

  /**
   * Updates an ACL entry on the specified bucket. This method supports patch
   * semantics. (bucketAccessControls.patch)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_BucketAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_BucketAccessControl
   */
  public function patch($bucket, $entity, Google_Service_Storage_BucketAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Storage_BucketAccessControl");
  }

  /**
   * Updates an ACL entry on the specified bucket. (bucketAccessControls.update)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_BucketAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_BucketAccessControl
   */
  public function update($bucket, $entity, Google_Service_Storage_BucketAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Storage_BucketAccessControl");
  }
}

/**
 * The "buckets" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $buckets = $storageService->buckets;
 *  </code>
 */
class Google_Service_Storage_Buckets_Resource extends Google_Service_Resource
{

  /**
   * Permanently deletes an empty bucket. (buckets.delete)
   *
   * @param string $bucket Name of a bucket.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifMetagenerationMatch If set, only deletes the bucket if
   * its metageneration matches this value.
   * @opt_param string ifMetagenerationNotMatch If set, only deletes the bucket if
   * its metageneration does not match this value.
   */
  public function delete($bucket, $optParams = array())
  {
    $params = array('bucket' => $bucket);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns metadata for the specified bucket. (buckets.get)
   *
   * @param string $bucket Name of a bucket.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifMetagenerationMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration matches
   * the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration does not
   * match the given value.
   * @opt_param string projection Set of properties to return. Defaults to noAcl.
   * @return Google_Service_Storage_Bucket
   */
  public function get($bucket, $optParams = array())
  {
    $params = array('bucket' => $bucket);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Storage_Bucket");
  }

  /**
   * Creates a new bucket. (buckets.insert)
   *
   * @param string $project A valid API project identifier.
   * @param Google_Bucket $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this bucket.
   * @opt_param string projection Set of properties to return. Defaults to noAcl,
   * unless the bucket resource specifies acl or defaultObjectAcl properties, when
   * it defaults to full.
   * @opt_param string predefinedDefaultObjectAcl Apply a predefined set of
   * default object access controls to this bucket.
   * @return Google_Service_Storage_Bucket
   */
  public function insert($project, Google_Service_Storage_Bucket $postBody, $optParams = array())
  {
    $params = array('project' => $project, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Storage_Bucket");
  }

  /**
   * Retrieves a list of buckets for a given project. (buckets.listBuckets)
   *
   * @param string $project A valid API project identifier.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @opt_param string prefix Filter results to buckets whose names begin with
   * this prefix.
   * @opt_param string projection Set of properties to return. Defaults to noAcl.
   * @opt_param string maxResults Maximum number of buckets to return.
   * @return Google_Service_Storage_Buckets
   */
  public function listBuckets($project, $optParams = array())
  {
    $params = array('project' => $project);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Storage_Buckets");
  }

  /**
   * Updates a bucket. This method supports patch semantics. (buckets.patch)
   *
   * @param string $bucket Name of a bucket.
   * @param Google_Bucket $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Set of properties to return. Defaults to full.
   * @opt_param string ifMetagenerationMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration matches
   * the given value.
   * @opt_param string predefinedDefaultObjectAcl Apply a predefined set of
   * default object access controls to this bucket.
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this bucket.
   * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration does not
   * match the given value.
   * @return Google_Service_Storage_Bucket
   */
  public function patch($bucket, Google_Service_Storage_Bucket $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Storage_Bucket");
  }

  /**
   * Updates a bucket. (buckets.update)
   *
   * @param string $bucket Name of a bucket.
   * @param Google_Bucket $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Set of properties to return. Defaults to full.
   * @opt_param string ifMetagenerationMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration matches
   * the given value.
   * @opt_param string predefinedDefaultObjectAcl Apply a predefined set of
   * default object access controls to this bucket.
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this bucket.
   * @opt_param string ifMetagenerationNotMatch Makes the return of the bucket
   * metadata conditional on whether the bucket's current metageneration does not
   * match the given value.
   * @return Google_Service_Storage_Bucket
   */
  public function update($bucket, Google_Service_Storage_Bucket $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Storage_Bucket");
  }
}

/**
 * The "channels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $channels = $storageService->channels;
 *  </code>
 */
class Google_Service_Storage_Channels_Resource extends Google_Service_Resource
{

  /**
   * Stop watching resources through this channel (channels.stop)
   *
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   */
  public function stop(Google_Service_Storage_Channel $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('stop', array($params));
  }
}

/**
 * The "defaultObjectAccessControls" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $defaultObjectAccessControls = $storageService->defaultObjectAccessControls;
 *  </code>
 */
class Google_Service_Storage_DefaultObjectAccessControls_Resource extends Google_Service_Resource
{

  /**
   * Permanently deletes the default object ACL entry for the specified entity on
   * the specified bucket. (defaultObjectAccessControls.delete)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   */
  public function delete($bucket, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns the default object ACL entry for the specified entity on the
   * specified bucket. (defaultObjectAccessControls.get)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function get($bucket, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Creates a new default object ACL entry on the specified bucket.
   * (defaultObjectAccessControls.insert)
   *
   * @param string $bucket Name of a bucket.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function insert($bucket, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Retrieves default object ACL entries on the specified bucket.
   * (defaultObjectAccessControls.listDefaultObjectAccessControls)
   *
   * @param string $bucket Name of a bucket.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifMetagenerationMatch If present, only return default ACL
   * listing if the bucket's current metageneration matches this value.
   * @opt_param string ifMetagenerationNotMatch If present, only return default
   * ACL listing if the bucket's current metageneration does not match the given
   * value.
   * @return Google_Service_Storage_ObjectAccessControls
   */
  public function listDefaultObjectAccessControls($bucket, $optParams = array())
  {
    $params = array('bucket' => $bucket);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Storage_ObjectAccessControls");
  }

  /**
   * Updates a default object ACL entry on the specified bucket. This method
   * supports patch semantics. (defaultObjectAccessControls.patch)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function patch($bucket, $entity, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Updates a default object ACL entry on the specified bucket.
   * (defaultObjectAccessControls.update)
   *
   * @param string $bucket Name of a bucket.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function update($bucket, $entity, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Storage_ObjectAccessControl");
  }
}

/**
 * The "objectAccessControls" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $objectAccessControls = $storageService->objectAccessControls;
 *  </code>
 */
class Google_Service_Storage_ObjectAccessControls_Resource extends Google_Service_Resource
{

  /**
   * Permanently deletes the ACL entry for the specified entity on the specified
   * object. (objectAccessControls.delete)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   */
  public function delete($bucket, $object, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns the ACL entry for the specified entity on the specified object.
   * (objectAccessControls.get)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function get($bucket, $object, $entity, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Creates a new ACL entry on the specified object.
   * (objectAccessControls.insert)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function insert($bucket, $object, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Retrieves ACL entries on the specified object.
   * (objectAccessControls.listObjectAccessControls)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @return Google_Service_Storage_ObjectAccessControls
   */
  public function listObjectAccessControls($bucket, $object, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Storage_ObjectAccessControls");
  }

  /**
   * Updates an ACL entry on the specified object. This method supports patch
   * semantics. (objectAccessControls.patch)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function patch($bucket, $object, $entity, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Storage_ObjectAccessControl");
  }

  /**
   * Updates an ACL entry on the specified object. (objectAccessControls.update)
   *
   * @param string $bucket Name of a bucket.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $entity The entity holding the permission. Can be user-userId,
   * user-emailAddress, group-groupId, group-emailAddress, allUsers, or
   * allAuthenticatedUsers.
   * @param Google_ObjectAccessControl $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @return Google_Service_Storage_ObjectAccessControl
   */
  public function update($bucket, $object, $entity, Google_Service_Storage_ObjectAccessControl $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'entity' => $entity, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Storage_ObjectAccessControl");
  }
}

/**
 * The "objects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $storageService = new Google_Service_Storage(...);
 *   $objects = $storageService->objects;
 *  </code>
 */
class Google_Service_Storage_Objects_Resource extends Google_Service_Resource
{

  /**
   * Concatenates a list of existing objects into a new object in the same bucket.
   * (objects.compose)
   *
   * @param string $destinationBucket Name of the bucket in which to store the new
   * object.
   * @param string $destinationObject Name of the new object. For information
   * about how to URL encode object names to be path safe, see Encoding URI Path
   * Parts.
   * @param Google_ComposeRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's current generation matches the given value.
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string destinationPredefinedAcl Apply a predefined set of access
   * controls to the destination object.
   * @return Google_Service_Storage_StorageObject
   */
  public function compose($destinationBucket, $destinationObject, Google_Service_Storage_ComposeRequest $postBody, $optParams = array())
  {
    $params = array('destinationBucket' => $destinationBucket, 'destinationObject' => $destinationObject, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('compose', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Copies a source object to a destination object. Optionally overrides
   * metadata. (objects.copy)
   *
   * @param string $sourceBucket Name of the bucket in which to find the source
   * object.
   * @param string $sourceObject Name of the source object. For information about
   * how to URL encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $destinationBucket Name of the bucket in which to store the new
   * object. Overrides the provided object metadata's bucket value, if any.For
   * information about how to URL encode object names to be path safe, see
   * Encoding URI Path Parts.
   * @param string $destinationObject Name of the new object. Required when the
   * object metadata is not otherwise provided. Overrides the object metadata's
   * name value, if any.
   * @param Google_StorageObject $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifSourceGenerationNotMatch Makes the operation conditional
   * on whether the source object's generation does not match the given value.
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the destination object's current generation does not match the given
   * value.
   * @opt_param string ifSourceMetagenerationNotMatch Makes the operation
   * conditional on whether the source object's current metageneration does not
   * match the given value.
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the destination object's current metageneration matches the given
   * value.
   * @opt_param string sourceGeneration If present, selects a specific revision of
   * the source object (as opposed to the latest version, the default).
   * @opt_param string destinationPredefinedAcl Apply a predefined set of access
   * controls to the destination object.
   * @opt_param string ifSourceGenerationMatch Makes the operation conditional on
   * whether the source object's generation matches the given value.
   * @opt_param string ifSourceMetagenerationMatch Makes the operation conditional
   * on whether the source object's current metageneration matches the given
   * value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the destination object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the destination object's current metageneration does not match the
   * given value.
   * @opt_param string projection Set of properties to return. Defaults to noAcl,
   * unless the object resource specifies the acl property, when it defaults to
   * full.
   * @return Google_Service_Storage_StorageObject
   */
  public function copy($sourceBucket, $sourceObject, $destinationBucket, $destinationObject, Google_Service_Storage_StorageObject $postBody, $optParams = array())
  {
    $params = array('sourceBucket' => $sourceBucket, 'sourceObject' => $sourceObject, 'destinationBucket' => $destinationBucket, 'destinationObject' => $destinationObject, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('copy', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Deletes an object and its metadata. Deletions are permanent if versioning is
   * not enabled for the bucket, or if the generation parameter is used.
   * (objects.delete)
   *
   * @param string $bucket Name of the bucket in which the object resides.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the object's current generation does not match the given value.
   * @opt_param string generation If present, permanently deletes a specific
   * revision of this object (as opposed to the latest version, the default).
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the object's current metageneration does not match the given value.
   */
  public function delete($bucket, $object, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves an object or its metadata. (objects.get)
   *
   * @param string $bucket Name of the bucket in which the object resides.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the object's generation does not match the given value.
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the object's current metageneration does not match the given value.
   * @opt_param string projection Set of properties to return. Defaults to noAcl.
   * @return Google_Service_Storage_StorageObject
   */
  public function get($bucket, $object, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Stores a new object and metadata. (objects.insert)
   *
   * @param string $bucket Name of the bucket in which to store the new object.
   * Overrides the provided object metadata's bucket value, if any.
   * @param Google_StorageObject $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this object.
   * @opt_param string projection Set of properties to return. Defaults to noAcl,
   * unless the object resource specifies the acl property, when it defaults to
   * full.
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the object's current generation does not match the given value.
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string contentEncoding If set, sets the contentEncoding property
   * of the final object to this value. Setting this parameter is equivalent to
   * setting the contentEncoding metadata property. This can be useful when
   * uploading an object with uploadType=media to indicate the encoding of the
   * content being uploaded.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the object's current metageneration does not match the given value.
   * @opt_param string name Name of the object. Required when the object metadata
   * is not otherwise provided. Overrides the object metadata's name value, if
   * any. For information about how to URL encode object names to be path safe,
   * see Encoding URI Path Parts.
   * @return Google_Service_Storage_StorageObject
   */
  public function insert($bucket, Google_Service_Storage_StorageObject $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Retrieves a list of objects matching the criteria. (objects.listObjects)
   *
   * @param string $bucket Name of the bucket in which to look for objects.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Set of properties to return. Defaults to noAcl.
   * @opt_param bool versions If true, lists all versions of an object as distinct
   * results. The default is false. For more information, see Object Versioning.
   * @opt_param string prefix Filter results to objects whose names begin with
   * this prefix.
   * @opt_param string maxResults Maximum number of items plus prefixes to return.
   * As duplicate prefixes are omitted, fewer total results may be returned than
   * requested. The default value of this parameter is 1,000 items.
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @opt_param string delimiter Returns results in a directory-like mode. items
   * will contain only objects whose names, aside from the prefix, do not contain
   * delimiter. Objects whose names, aside from the prefix, contain delimiter will
   * have their name, truncated after the delimiter, returned in prefixes.
   * Duplicate prefixes are omitted.
   * @return Google_Service_Storage_Objects
   */
  public function listObjects($bucket, $optParams = array())
  {
    $params = array('bucket' => $bucket);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Storage_Objects");
  }

  /**
   * Updates an object's metadata. This method supports patch semantics.
   * (objects.patch)
   *
   * @param string $bucket Name of the bucket in which the object resides.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param Google_StorageObject $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this object.
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the object's current generation does not match the given value.
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the object's current metageneration does not match the given value.
   * @opt_param string projection Set of properties to return. Defaults to full.
   * @return Google_Service_Storage_StorageObject
   */
  public function patch($bucket, $object, Google_Service_Storage_StorageObject $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Rewrites a source object to a destination object. Optionally overrides
   * metadata. (objects.rewrite)
   *
   * @param string $sourceBucket Name of the bucket in which to find the source
   * object.
   * @param string $sourceObject Name of the source object. For information about
   * how to URL encode object names to be path safe, see Encoding URI Path Parts.
   * @param string $destinationBucket Name of the bucket in which to store the new
   * object. Overrides the provided object metadata's bucket value, if any.
   * @param string $destinationObject Name of the new object. Required when the
   * object metadata is not otherwise provided. Overrides the object metadata's
   * name value, if any. For information about how to URL encode object names to
   * be path safe, see Encoding URI Path Parts.
   * @param Google_StorageObject $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ifSourceGenerationNotMatch Makes the operation conditional
   * on whether the source object's generation does not match the given value.
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the destination object's current generation does not match the given
   * value.
   * @opt_param string rewriteToken Include this field (from the previous rewrite
   * response) on each rewrite request after the first one, until the rewrite
   * response 'done' flag is true. Calls that provide a rewriteToken can omit all
   * other request fields, but if included those fields must match the values
   * provided in the first rewrite request.
   * @opt_param string ifSourceMetagenerationNotMatch Makes the operation
   * conditional on whether the source object's current metageneration does not
   * match the given value.
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the destination object's current metageneration matches the given
   * value.
   * @opt_param string sourceGeneration If present, selects a specific revision of
   * the source object (as opposed to the latest version, the default).
   * @opt_param string destinationPredefinedAcl Apply a predefined set of access
   * controls to the destination object.
   * @opt_param string ifSourceGenerationMatch Makes the operation conditional on
   * whether the source object's generation matches the given value.
   * @opt_param string maxBytesRewrittenPerCall The maximum number of bytes that
   * will be rewritten per rewrite request. Most callers shouldn't need to specify
   * this parameter - it is primarily in place to support testing. If specified
   * the value must be an integral multiple of 1 MiB (1048576). Also, this only
   * applies to requests where the source and destination span locations and/or
   * storage classes. Finally, this value must not change across rewrite calls
   * else you'll get an error that the rewriteToken is invalid.
   * @opt_param string ifSourceMetagenerationMatch Makes the operation conditional
   * on whether the source object's current metageneration matches the given
   * value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the destination object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the destination object's current metageneration does not match the
   * given value.
   * @opt_param string projection Set of properties to return. Defaults to noAcl,
   * unless the object resource specifies the acl property, when it defaults to
   * full.
   * @return Google_Service_Storage_RewriteResponse
   */
  public function rewrite($sourceBucket, $sourceObject, $destinationBucket, $destinationObject, Google_Service_Storage_StorageObject $postBody, $optParams = array())
  {
    $params = array('sourceBucket' => $sourceBucket, 'sourceObject' => $sourceObject, 'destinationBucket' => $destinationBucket, 'destinationObject' => $destinationObject, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('rewrite', array($params), "Google_Service_Storage_RewriteResponse");
  }

  /**
   * Updates an object's metadata. (objects.update)
   *
   * @param string $bucket Name of the bucket in which the object resides.
   * @param string $object Name of the object. For information about how to URL
   * encode object names to be path safe, see Encoding URI Path Parts.
   * @param Google_StorageObject $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string predefinedAcl Apply a predefined set of access controls to
   * this object.
   * @opt_param string ifGenerationNotMatch Makes the operation conditional on
   * whether the object's current generation does not match the given value.
   * @opt_param string generation If present, selects a specific revision of this
   * object (as opposed to the latest version, the default).
   * @opt_param string ifMetagenerationMatch Makes the operation conditional on
   * whether the object's current metageneration matches the given value.
   * @opt_param string ifGenerationMatch Makes the operation conditional on
   * whether the object's current generation matches the given value.
   * @opt_param string ifMetagenerationNotMatch Makes the operation conditional on
   * whether the object's current metageneration does not match the given value.
   * @opt_param string projection Set of properties to return. Defaults to full.
   * @return Google_Service_Storage_StorageObject
   */
  public function update($bucket, $object, Google_Service_Storage_StorageObject $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'object' => $object, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Storage_StorageObject");
  }

  /**
   * Watch for changes on all objects in a bucket. (objects.watchAll)
   *
   * @param string $bucket Name of the bucket in which to look for objects.
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Set of properties to return. Defaults to noAcl.
   * @opt_param bool versions If true, lists all versions of an object as distinct
   * results. The default is false. For more information, see Object Versioning.
   * @opt_param string prefix Filter results to objects whose names begin with
   * this prefix.
   * @opt_param string maxResults Maximum number of items plus prefixes to return.
   * As duplicate prefixes are omitted, fewer total results may be returned than
   * requested. The default value of this parameter is 1,000 items.
   * @opt_param string pageToken A previously-returned page token representing
   * part of the larger set of results to view.
   * @opt_param string delimiter Returns results in a directory-like mode. items
   * will contain only objects whose names, aside from the prefix, do not contain
   * delimiter. Objects whose names, aside from the prefix, contain delimiter will
   * have their name, truncated after the delimiter, returned in prefixes.
   * Duplicate prefixes are omitted.
   * @return Google_Service_Storage_Channel
   */
  public function watchAll($bucket, Google_Service_Storage_Channel $postBody, $optParams = array())
  {
    $params = array('bucket' => $bucket, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('watchAll', array($params), "Google_Service_Storage_Channel");
  }
}




class Google_Service_Storage_Bucket extends Google_Collection
{
  protected $collection_key = 'defaultObjectAcl';
  protected $internal_gapi_mappings = array(
  );
  protected $aclType = 'Google_Service_Storage_BucketAccessControl';
  protected $aclDataType = 'array';
  protected $corsType = 'Google_Service_Storage_BucketCors';
  protected $corsDataType = 'array';
  protected $defaultObjectAclType = 'Google_Service_Storage_ObjectAccessControl';
  protected $defaultObjectAclDataType = 'array';
  public $etag;
  public $id;
  public $kind;
  protected $lifecycleType = 'Google_Service_Storage_BucketLifecycle';
  protected $lifecycleDataType = '';
  public $location;
  protected $loggingType = 'Google_Service_Storage_BucketLogging';
  protected $loggingDataType = '';
  public $metageneration;
  public $name;
  protected $ownerType = 'Google_Service_Storage_BucketOwner';
  protected $ownerDataType = '';
  public $projectNumber;
  public $selfLink;
  public $storageClass;
  public $timeCreated;
  public $updated;
  protected $versioningType = 'Google_Service_Storage_BucketVersioning';
  protected $versioningDataType = '';
  protected $websiteType = 'Google_Service_Storage_BucketWebsite';
  protected $websiteDataType = '';


  public function setAcl($acl)
  {
    $this->acl = $acl;
  }
  public function getAcl()
  {
    return $this->acl;
  }
  public function setCors($cors)
  {
    $this->cors = $cors;
  }
  public function getCors()
  {
    return $this->cors;
  }
  public function setDefaultObjectAcl($defaultObjectAcl)
  {
    $this->defaultObjectAcl = $defaultObjectAcl;
  }
  public function getDefaultObjectAcl()
  {
    return $this->defaultObjectAcl;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLifecycle(Google_Service_Storage_BucketLifecycle $lifecycle)
  {
    $this->lifecycle = $lifecycle;
  }
  public function getLifecycle()
  {
    return $this->lifecycle;
  }
  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setLogging(Google_Service_Storage_BucketLogging $logging)
  {
    $this->logging = $logging;
  }
  public function getLogging()
  {
    return $this->logging;
  }
  public function setMetageneration($metageneration)
  {
    $this->metageneration = $metageneration;
  }
  public function getMetageneration()
  {
    return $this->metageneration;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOwner(Google_Service_Storage_BucketOwner $owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
  public function setProjectNumber($projectNumber)
  {
    $this->projectNumber = $projectNumber;
  }
  public function getProjectNumber()
  {
    return $this->projectNumber;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setStorageClass($storageClass)
  {
    $this->storageClass = $storageClass;
  }
  public function getStorageClass()
  {
    return $this->storageClass;
  }
  public function setTimeCreated($timeCreated)
  {
    $this->timeCreated = $timeCreated;
  }
  public function getTimeCreated()
  {
    return $this->timeCreated;
  }
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }
  public function getUpdated()
  {
    return $this->updated;
  }
  public function setVersioning(Google_Service_Storage_BucketVersioning $versioning)
  {
    $this->versioning = $versioning;
  }
  public function getVersioning()
  {
    return $this->versioning;
  }
  public function setWebsite(Google_Service_Storage_BucketWebsite $website)
  {
    $this->website = $website;
  }
  public function getWebsite()
  {
    return $this->website;
  }
}

class Google_Service_Storage_BucketAccessControl extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $bucket;
  public $domain;
  public $email;
  public $entity;
  public $entityId;
  public $etag;
  public $id;
  public $kind;
  protected $projectTeamType = 'Google_Service_Storage_BucketAccessControlProjectTeam';
  protected $projectTeamDataType = '';
  public $role;
  public $selfLink;


  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  public function getBucket()
  {
    return $this->bucket;
  }
  public function setDomain($domain)
  {
    $this->domain = $domain;
  }
  public function getDomain()
  {
    return $this->domain;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setEntity($entity)
  {
    $this->entity = $entity;
  }
  public function getEntity()
  {
    return $this->entity;
  }
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  public function getEntityId()
  {
    return $this->entityId;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setProjectTeam(Google_Service_Storage_BucketAccessControlProjectTeam $projectTeam)
  {
    $this->projectTeam = $projectTeam;
  }
  public function getProjectTeam()
  {
    return $this->projectTeam;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}

class Google_Service_Storage_BucketAccessControlProjectTeam extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $projectNumber;
  public $team;


  public function setProjectNumber($projectNumber)
  {
    $this->projectNumber = $projectNumber;
  }
  public function getProjectNumber()
  {
    return $this->projectNumber;
  }
  public function setTeam($team)
  {
    $this->team = $team;
  }
  public function getTeam()
  {
    return $this->team;
  }
}

class Google_Service_Storage_BucketAccessControls extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Storage_BucketAccessControl';
  protected $itemsDataType = 'array';
  public $kind;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_Storage_BucketCors extends Google_Collection
{
  protected $collection_key = 'responseHeader';
  protected $internal_gapi_mappings = array(
  );
  public $maxAgeSeconds;
  public $method;
  public $origin;
  public $responseHeader;


  public function setMaxAgeSeconds($maxAgeSeconds)
  {
    $this->maxAgeSeconds = $maxAgeSeconds;
  }
  public function getMaxAgeSeconds()
  {
    return $this->maxAgeSeconds;
  }
  public function setMethod($method)
  {
    $this->method = $method;
  }
  public function getMethod()
  {
    return $this->method;
  }
  public function setOrigin($origin)
  {
    $this->origin = $origin;
  }
  public function getOrigin()
  {
    return $this->origin;
  }
  public function setResponseHeader($responseHeader)
  {
    $this->responseHeader = $responseHeader;
  }
  public function getResponseHeader()
  {
    return $this->responseHeader;
  }
}

class Google_Service_Storage_BucketLifecycle extends Google_Collection
{
  protected $collection_key = 'rule';
  protected $internal_gapi_mappings = array(
  );
  protected $ruleType = 'Google_Service_Storage_BucketLifecycleRule';
  protected $ruleDataType = 'array';


  public function setRule($rule)
  {
    $this->rule = $rule;
  }
  public function getRule()
  {
    return $this->rule;
  }
}

class Google_Service_Storage_BucketLifecycleRule extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $actionType = 'Google_Service_Storage_BucketLifecycleRuleAction';
  protected $actionDataType = '';
  protected $conditionType = 'Google_Service_Storage_BucketLifecycleRuleCondition';
  protected $conditionDataType = '';


  public function setAction(Google_Service_Storage_BucketLifecycleRuleAction $action)
  {
    $this->action = $action;
  }
  public function getAction()
  {
    return $this->action;
  }
  public function setCondition(Google_Service_Storage_BucketLifecycleRuleCondition $condition)
  {
    $this->condition = $condition;
  }
  public function getCondition()
  {
    return $this->condition;
  }
}

class Google_Service_Storage_BucketLifecycleRuleAction extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $type;


  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_Storage_BucketLifecycleRuleCondition extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $age;
  public $createdBefore;
  public $isLive;
  public $numNewerVersions;


  public function setAge($age)
  {
    $this->age = $age;
  }
  public function getAge()
  {
    return $this->age;
  }
  public function setCreatedBefore($createdBefore)
  {
    $this->createdBefore = $createdBefore;
  }
  public function getCreatedBefore()
  {
    return $this->createdBefore;
  }
  public function setIsLive($isLive)
  {
    $this->isLive = $isLive;
  }
  public function getIsLive()
  {
    return $this->isLive;
  }
  public function setNumNewerVersions($numNewerVersions)
  {
    $this->numNewerVersions = $numNewerVersions;
  }
  public function getNumNewerVersions()
  {
    return $this->numNewerVersions;
  }
}

class Google_Service_Storage_BucketLogging extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $logBucket;
  public $logObjectPrefix;


  public function setLogBucket($logBucket)
  {
    $this->logBucket = $logBucket;
  }
  public function getLogBucket()
  {
    return $this->logBucket;
  }
  public function setLogObjectPrefix($logObjectPrefix)
  {
    $this->logObjectPrefix = $logObjectPrefix;
  }
  public function getLogObjectPrefix()
  {
    return $this->logObjectPrefix;
  }
}

class Google_Service_Storage_BucketOwner extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $entity;
  public $entityId;


  public function setEntity($entity)
  {
    $this->entity = $entity;
  }
  public function getEntity()
  {
    return $this->entity;
  }
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  public function getEntityId()
  {
    return $this->entityId;
  }
}

class Google_Service_Storage_BucketVersioning extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $enabled;


  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  public function getEnabled()
  {
    return $this->enabled;
  }
}

class Google_Service_Storage_BucketWebsite extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $mainPageSuffix;
  public $notFoundPage;


  public function setMainPageSuffix($mainPageSuffix)
  {
    $this->mainPageSuffix = $mainPageSuffix;
  }
  public function getMainPageSuffix()
  {
    return $this->mainPageSuffix;
  }
  public function setNotFoundPage($notFoundPage)
  {
    $this->notFoundPage = $notFoundPage;
  }
  public function getNotFoundPage()
  {
    return $this->notFoundPage;
  }
}

class Google_Service_Storage_Buckets extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Storage_Bucket';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Storage_Channel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $address;
  public $expiration;
  public $id;
  public $kind;
  public $params;
  public $payload;
  public $resourceId;
  public $resourceUri;
  public $token;
  public $type;


  public function setAddress($address)
  {
    $this->address = $address;
  }
  public function getAddress()
  {
    return $this->address;
  }
  public function setExpiration($expiration)
  {
    $this->expiration = $expiration;
  }
  public function getExpiration()
  {
    return $this->expiration;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setParams($params)
  {
    $this->params = $params;
  }
  public function getParams()
  {
    return $this->params;
  }
  public function setPayload($payload)
  {
    $this->payload = $payload;
  }
  public function getPayload()
  {
    return $this->payload;
  }
  public function setResourceId($resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setResourceUri($resourceUri)
  {
    $this->resourceUri = $resourceUri;
  }
  public function getResourceUri()
  {
    return $this->resourceUri;
  }
  public function setToken($token)
  {
    $this->token = $token;
  }
  public function getToken()
  {
    return $this->token;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
}

class Google_Service_Storage_ChannelParams extends Google_Model
{
}

class Google_Service_Storage_ComposeRequest extends Google_Collection
{
  protected $collection_key = 'sourceObjects';
  protected $internal_gapi_mappings = array(
  );
  protected $destinationType = 'Google_Service_Storage_StorageObject';
  protected $destinationDataType = '';
  public $kind;
  protected $sourceObjectsType = 'Google_Service_Storage_ComposeRequestSourceObjects';
  protected $sourceObjectsDataType = 'array';


  public function setDestination(Google_Service_Storage_StorageObject $destination)
  {
    $this->destination = $destination;
  }
  public function getDestination()
  {
    return $this->destination;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSourceObjects($sourceObjects)
  {
    $this->sourceObjects = $sourceObjects;
  }
  public function getSourceObjects()
  {
    return $this->sourceObjects;
  }
}

class Google_Service_Storage_ComposeRequestSourceObjects extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $generation;
  public $name;
  protected $objectPreconditionsType = 'Google_Service_Storage_ComposeRequestSourceObjectsObjectPreconditions';
  protected $objectPreconditionsDataType = '';


  public function setGeneration($generation)
  {
    $this->generation = $generation;
  }
  public function getGeneration()
  {
    return $this->generation;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setObjectPreconditions(Google_Service_Storage_ComposeRequestSourceObjectsObjectPreconditions $objectPreconditions)
  {
    $this->objectPreconditions = $objectPreconditions;
  }
  public function getObjectPreconditions()
  {
    return $this->objectPreconditions;
  }
}

class Google_Service_Storage_ComposeRequestSourceObjectsObjectPreconditions extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $ifGenerationMatch;


  public function setIfGenerationMatch($ifGenerationMatch)
  {
    $this->ifGenerationMatch = $ifGenerationMatch;
  }
  public function getIfGenerationMatch()
  {
    return $this->ifGenerationMatch;
  }
}

class Google_Service_Storage_ObjectAccessControl extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $bucket;
  public $domain;
  public $email;
  public $entity;
  public $entityId;
  public $etag;
  public $generation;
  public $id;
  public $kind;
  public $object;
  protected $projectTeamType = 'Google_Service_Storage_ObjectAccessControlProjectTeam';
  protected $projectTeamDataType = '';
  public $role;
  public $selfLink;


  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  public function getBucket()
  {
    return $this->bucket;
  }
  public function setDomain($domain)
  {
    $this->domain = $domain;
  }
  public function getDomain()
  {
    return $this->domain;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setEntity($entity)
  {
    $this->entity = $entity;
  }
  public function getEntity()
  {
    return $this->entity;
  }
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  public function getEntityId()
  {
    return $this->entityId;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setGeneration($generation)
  {
    $this->generation = $generation;
  }
  public function getGeneration()
  {
    return $this->generation;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setObject($object)
  {
    $this->object = $object;
  }
  public function getObject()
  {
    return $this->object;
  }
  public function setProjectTeam(Google_Service_Storage_ObjectAccessControlProjectTeam $projectTeam)
  {
    $this->projectTeam = $projectTeam;
  }
  public function getProjectTeam()
  {
    return $this->projectTeam;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
}

class Google_Service_Storage_ObjectAccessControlProjectTeam extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $projectNumber;
  public $team;


  public function setProjectNumber($projectNumber)
  {
    $this->projectNumber = $projectNumber;
  }
  public function getProjectNumber()
  {
    return $this->projectNumber;
  }
  public function setTeam($team)
  {
    $this->team = $team;
  }
  public function getTeam()
  {
    return $this->team;
  }
}

class Google_Service_Storage_ObjectAccessControls extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $items;
  public $kind;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
}

class Google_Service_Storage_Objects extends Google_Collection
{
  protected $collection_key = 'prefixes';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Storage_StorageObject';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  public $prefixes;


  public function setItems($items)
  {
    $this->items = $items;
  }
  public function getItems()
  {
    return $this->items;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPrefixes($prefixes)
  {
    $this->prefixes = $prefixes;
  }
  public function getPrefixes()
  {
    return $this->prefixes;
  }
}

class Google_Service_Storage_RewriteResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $done;
  public $kind;
  public $objectSize;
  protected $resourceType = 'Google_Service_Storage_StorageObject';
  protected $resourceDataType = '';
  public $rewriteToken;
  public $totalBytesRewritten;


  public function setDone($done)
  {
    $this->done = $done;
  }
  public function getDone()
  {
    return $this->done;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setObjectSize($objectSize)
  {
    $this->objectSize = $objectSize;
  }
  public function getObjectSize()
  {
    return $this->objectSize;
  }
  public function setResource(Google_Service_Storage_StorageObject $resource)
  {
    $this->resource = $resource;
  }
  public function getResource()
  {
    return $this->resource;
  }
  public function setRewriteToken($rewriteToken)
  {
    $this->rewriteToken = $rewriteToken;
  }
  public function getRewriteToken()
  {
    return $this->rewriteToken;
  }
  public function setTotalBytesRewritten($totalBytesRewritten)
  {
    $this->totalBytesRewritten = $totalBytesRewritten;
  }
  public function getTotalBytesRewritten()
  {
    return $this->totalBytesRewritten;
  }
}

class Google_Service_Storage_StorageObject extends Google_Collection
{
  protected $collection_key = 'acl';
  protected $internal_gapi_mappings = array(
  );
  protected $aclType = 'Google_Service_Storage_ObjectAccessControl';
  protected $aclDataType = 'array';
  public $bucket;
  public $cacheControl;
  public $componentCount;
  public $contentDisposition;
  public $contentEncoding;
  public $contentLanguage;
  public $contentType;
  public $crc32c;
  public $etag;
  public $generation;
  public $id;
  public $kind;
  public $md5Hash;
  public $mediaLink;
  public $metadata;
  public $metageneration;
  public $name;
  protected $ownerType = 'Google_Service_Storage_StorageObjectOwner';
  protected $ownerDataType = '';
  public $selfLink;
  public $size;
  public $storageClass;
  public $timeCreated;
  public $timeDeleted;
  public $updated;


  public function setAcl($acl)
  {
    $this->acl = $acl;
  }
  public function getAcl()
  {
    return $this->acl;
  }
  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  public function getBucket()
  {
    return $this->bucket;
  }
  public function setCacheControl($cacheControl)
  {
    $this->cacheControl = $cacheControl;
  }
  public function getCacheControl()
  {
    return $this->cacheControl;
  }
  public function setComponentCount($componentCount)
  {
    $this->componentCount = $componentCount;
  }
  public function getComponentCount()
  {
    return $this->componentCount;
  }
  public function setContentDisposition($contentDisposition)
  {
    $this->contentDisposition = $contentDisposition;
  }
  public function getContentDisposition()
  {
    return $this->contentDisposition;
  }
  public function setContentEncoding($contentEncoding)
  {
    $this->contentEncoding = $contentEncoding;
  }
  public function getContentEncoding()
  {
    return $this->contentEncoding;
  }
  public function setContentLanguage($contentLanguage)
  {
    $this->contentLanguage = $contentLanguage;
  }
  public function getContentLanguage()
  {
    return $this->contentLanguage;
  }
  public function setContentType($contentType)
  {
    $this->contentType = $contentType;
  }
  public function getContentType()
  {
    return $this->contentType;
  }
  public function setCrc32c($crc32c)
  {
    $this->crc32c = $crc32c;
  }
  public function getCrc32c()
  {
    return $this->crc32c;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setGeneration($generation)
  {
    $this->generation = $generation;
  }
  public function getGeneration()
  {
    return $this->generation;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMd5Hash($md5Hash)
  {
    $this->md5Hash = $md5Hash;
  }
  public function getMd5Hash()
  {
    return $this->md5Hash;
  }
  public function setMediaLink($mediaLink)
  {
    $this->mediaLink = $mediaLink;
  }
  public function getMediaLink()
  {
    return $this->mediaLink;
  }
  public function setMetadata($metadata)
  {
    $this->metadata = $metadata;
  }
  public function getMetadata()
  {
    return $this->metadata;
  }
  public function setMetageneration($metageneration)
  {
    $this->metageneration = $metageneration;
  }
  public function getMetageneration()
  {
    return $this->metageneration;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOwner(Google_Service_Storage_StorageObjectOwner $owner)
  {
    $this->owner = $owner;
  }
  public function getOwner()
  {
    return $this->owner;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setStorageClass($storageClass)
  {
    $this->storageClass = $storageClass;
  }
  public function getStorageClass()
  {
    return $this->storageClass;
  }
  public function setTimeCreated($timeCreated)
  {
    $this->timeCreated = $timeCreated;
  }
  public function getTimeCreated()
  {
    return $this->timeCreated;
  }
  public function setTimeDeleted($timeDeleted)
  {
    $this->timeDeleted = $timeDeleted;
  }
  public function getTimeDeleted()
  {
    return $this->timeDeleted;
  }
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }
  public function getUpdated()
  {
    return $this->updated;
  }
}

class Google_Service_Storage_StorageObjectMetadata extends Google_Model
{
}

class Google_Service_Storage_StorageObjectOwner extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $entity;
  public $entityId;


  public function setEntity($entity)
  {
    $this->entity = $entity;
  }
  public function getEntity()
  {
    return $this->entity;
  }
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  public function getEntityId()
  {
    return $this->entityId;
  }
}
