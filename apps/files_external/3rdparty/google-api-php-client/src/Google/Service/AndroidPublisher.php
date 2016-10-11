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
 * Service definition for AndroidPublisher (v2).
 *
 * <p>
 * Lets Android application developers access their Google Play accounts.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/android-publisher" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_AndroidPublisher extends Google_Service
{
  /** View and manage your Google Play Developer account. */
  const ANDROIDPUBLISHER =
      "https://www.googleapis.com/auth/androidpublisher";

  public $edits;
  public $edits_apklistings;
  public $edits_apks;
  public $edits_details;
  public $edits_expansionfiles;
  public $edits_images;
  public $edits_listings;
  public $edits_testers;
  public $edits_tracks;
  public $entitlements;
  public $inappproducts;
  public $purchases_products;
  public $purchases_subscriptions;
  

  /**
   * Constructs the internal representation of the AndroidPublisher service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'androidpublisher/v2/applications/';
    $this->version = 'v2';
    $this->serviceName = 'androidpublisher';

    $this->edits = new Google_Service_AndroidPublisher_Edits_Resource(
        $this,
        $this->serviceName,
        'edits',
        array(
          'methods' => array(
            'commit' => array(
              'path' => '{packageName}/edits/{editId}:commit',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => '{packageName}/edits/{editId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{packageName}/edits/{editId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{packageName}/edits',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'validate' => array(
              'path' => '{packageName}/edits/{editId}:validate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_apklistings = new Google_Service_AndroidPublisher_EditsApklistings_Resource(
        $this,
        $this->serviceName,
        'apklistings',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings/{language}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'deleteall' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings/{language}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings/{language}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/listings/{language}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_apks = new Google_Service_AndroidPublisher_EditsApks_Resource(
        $this,
        $this->serviceName,
        'apks',
        array(
          'methods' => array(
            'addexternallyhosted' => array(
              'path' => '{packageName}/edits/{editId}/apks/externallyHosted',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/edits/{editId}/apks',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'upload' => array(
              'path' => '{packageName}/edits/{editId}/apks',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_details = new Google_Service_AndroidPublisher_EditsDetails_Resource(
        $this,
        $this->serviceName,
        'details',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{packageName}/edits/{editId}/details',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/details',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/details',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_expansionfiles = new Google_Service_AndroidPublisher_EditsExpansionfiles_Resource(
        $this,
        $this->serviceName,
        'expansionfiles',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/expansionFiles/{expansionFileType}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'expansionFileType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/expansionFiles/{expansionFileType}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'expansionFileType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/expansionFiles/{expansionFileType}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'expansionFileType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'upload' => array(
              'path' => '{packageName}/edits/{editId}/apks/{apkVersionCode}/expansionFiles/{expansionFileType}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'apkVersionCode' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'expansionFileType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_images = new Google_Service_AndroidPublisher_EditsImages_Resource(
        $this,
        $this->serviceName,
        'images',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}/{imageType}/{imageId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'deleteall' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}/{imageType}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}/{imageType}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'upload' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}/{imageType}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'imageType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_listings = new Google_Service_AndroidPublisher_EditsListings_Resource(
        $this,
        $this->serviceName,
        'listings',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'deleteall' => array(
              'path' => '{packageName}/edits/{editId}/listings',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/edits/{editId}/listings',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/listings/{language}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'language' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_testers = new Google_Service_AndroidPublisher_EditsTesters_Resource(
        $this,
        $this->serviceName,
        'testers',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{packageName}/edits/{editId}/testers/{track}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/testers/{track}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/testers/{track}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->edits_tracks = new Google_Service_AndroidPublisher_EditsTracks_Resource(
        $this,
        $this->serviceName,
        'tracks',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{packageName}/edits/{editId}/tracks/{track}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/edits/{editId}/tracks',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/edits/{editId}/tracks/{track}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/edits/{editId}/tracks/{track}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'editId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'track' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->entitlements = new Google_Service_AndroidPublisher_Entitlements_Resource(
        $this,
        $this->serviceName,
        'entitlements',
        array(
          'methods' => array(
            'list' => array(
              'path' => '{packageName}/entitlements',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'startIndex' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'productId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->inappproducts = new Google_Service_AndroidPublisher_Inappproducts_Resource(
        $this,
        $this->serviceName,
        'inappproducts',
        array(
          'methods' => array(
            'batch' => array(
              'path' => 'inappproducts/batch',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'delete' => array(
              'path' => '{packageName}/inappproducts/{sku}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sku' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{packageName}/inappproducts/{sku}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sku' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{packageName}/inappproducts',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'autoConvertMissingPrices' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'list' => array(
              'path' => '{packageName}/inappproducts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'startIndex' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => '{packageName}/inappproducts/{sku}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sku' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'autoConvertMissingPrices' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'update' => array(
              'path' => '{packageName}/inappproducts/{sku}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sku' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'autoConvertMissingPrices' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),
          )
        )
    );
    $this->purchases_products = new Google_Service_AndroidPublisher_PurchasesProducts_Resource(
        $this,
        $this->serviceName,
        'products',
        array(
          'methods' => array(
            'get' => array(
              'path' => '{packageName}/purchases/products/{productId}/tokens/{token}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->purchases_subscriptions = new Google_Service_AndroidPublisher_PurchasesSubscriptions_Resource(
        $this,
        $this->serviceName,
        'subscriptions',
        array(
          'methods' => array(
            'cancel' => array(
              'path' => '{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}:cancel',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'subscriptionId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'defer' => array(
              'path' => '{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}:defer',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'subscriptionId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'subscriptionId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'refund' => array(
              'path' => '{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}:refund',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'subscriptionId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'revoke' => array(
              'path' => '{packageName}/purchases/subscriptions/{subscriptionId}/tokens/{token}:revoke',
              'httpMethod' => 'POST',
              'parameters' => array(
                'packageName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'subscriptionId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'token' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
  }
}


/**
 * The "edits" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $edits = $androidpublisherService->edits;
 *  </code>
 */
class Google_Service_AndroidPublisher_Edits_Resource extends Google_Service_Resource
{

  /**
   * Commits/applies the changes made in this edit back to the app. (edits.commit)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppEdit
   */
  public function commit($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('commit', array($params), "Google_Service_AndroidPublisher_AppEdit");
  }

  /**
   * Deletes an edit for an app. Creating a new edit will automatically delete any
   * of your previous edits so this method need only be called if you want to
   * preemptively abandon an edit. (edits.delete)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   */
  public function delete($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns information about the edit specified. Calls will fail if the edit is
   * no long active (e.g. has been deleted, superseded or expired). (edits.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppEdit
   */
  public function get($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_AppEdit");
  }

  /**
   * Creates a new edit for an app, populated with the app's current state.
   * (edits.insert)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param Google_AppEdit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppEdit
   */
  public function insert($packageName, Google_Service_AndroidPublisher_AppEdit $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AndroidPublisher_AppEdit");
  }

  /**
   * Checks that the edit can be successfully committed. The edit's changes are
   * not applied to the live app. (edits.validate)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppEdit
   */
  public function validate($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('validate', array($params), "Google_Service_AndroidPublisher_AppEdit");
  }
}

/**
 * The "apklistings" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $apklistings = $androidpublisherService->apklistings;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsApklistings_Resource extends Google_Service_Resource
{

  /**
   * Deletes the APK-specific localized listing for a specified APK and language
   * code. (apklistings.delete)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param string $language The language code (a BCP-47 language tag) of the APK-
   * specific localized listing to read or modify. For example, to select Austrian
   * German, pass "de-AT".
   * @param array $optParams Optional parameters.
   */
  public function delete($packageName, $editId, $apkVersionCode, $language, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'language' => $language);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Deletes all the APK-specific localized listings for a specified APK.
   * (apklistings.deleteall)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param array $optParams Optional parameters.
   */
  public function deleteall($packageName, $editId, $apkVersionCode, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode);
    $params = array_merge($params, $optParams);
    return $this->call('deleteall', array($params));
  }

  /**
   * Fetches the APK-specific localized listing for a specified APK and language
   * code. (apklistings.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param string $language The language code (a BCP-47 language tag) of the APK-
   * specific localized listing to read or modify. For example, to select Austrian
   * German, pass "de-AT".
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApkListing
   */
  public function get($packageName, $editId, $apkVersionCode, $language, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'language' => $language);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_ApkListing");
  }

  /**
   * Lists all the APK-specific localized listings for a specified APK.
   * (apklistings.listEditsApklistings)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApkListingsListResponse
   */
  public function listEditsApklistings($packageName, $editId, $apkVersionCode, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_ApkListingsListResponse");
  }

  /**
   * Updates or creates the APK-specific localized listing for a specified APK and
   * language code. This method supports patch semantics. (apklistings.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param string $language The language code (a BCP-47 language tag) of the APK-
   * specific localized listing to read or modify. For example, to select Austrian
   * German, pass "de-AT".
   * @param Google_ApkListing $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApkListing
   */
  public function patch($packageName, $editId, $apkVersionCode, $language, Google_Service_AndroidPublisher_ApkListing $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'language' => $language, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_ApkListing");
  }

  /**
   * Updates or creates the APK-specific localized listing for a specified APK and
   * language code. (apklistings.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The APK version code whose APK-specific listings
   * should be read or modified.
   * @param string $language The language code (a BCP-47 language tag) of the APK-
   * specific localized listing to read or modify. For example, to select Austrian
   * German, pass "de-AT".
   * @param Google_ApkListing $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApkListing
   */
  public function update($packageName, $editId, $apkVersionCode, $language, Google_Service_AndroidPublisher_ApkListing $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'language' => $language, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_ApkListing");
  }
}
/**
 * The "apks" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $apks = $androidpublisherService->apks;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsApks_Resource extends Google_Service_Resource
{

  /**
   * Creates a new APK without uploading the APK itself to Google Play, instead
   * hosting the APK at a specified URL. This function is only available to
   * enterprises using Google Play for Work whose application is configured to
   * restrict distribution to the enterprise domain. (apks.addexternallyhosted)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param Google_ApksAddExternallyHostedRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApksAddExternallyHostedResponse
   */
  public function addexternallyhosted($packageName, $editId, Google_Service_AndroidPublisher_ApksAddExternallyHostedRequest $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('addexternallyhosted', array($params), "Google_Service_AndroidPublisher_ApksAddExternallyHostedResponse");
  }

  /**
   * (apks.listEditsApks)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ApksListResponse
   */
  public function listEditsApks($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_ApksListResponse");
  }

  /**
   * (apks.upload)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Apk
   */
  public function upload($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('upload', array($params), "Google_Service_AndroidPublisher_Apk");
  }
}
/**
 * The "details" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $details = $androidpublisherService->details;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsDetails_Resource extends Google_Service_Resource
{

  /**
   * Fetches app details for this edit. This includes the default language and
   * developer support contact information. (details.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppDetails
   */
  public function get($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_AppDetails");
  }

  /**
   * Updates app details for this edit. This method supports patch semantics.
   * (details.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param Google_AppDetails $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppDetails
   */
  public function patch($packageName, $editId, Google_Service_AndroidPublisher_AppDetails $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_AppDetails");
  }

  /**
   * Updates app details for this edit. (details.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param Google_AppDetails $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_AppDetails
   */
  public function update($packageName, $editId, Google_Service_AndroidPublisher_AppDetails $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_AppDetails");
  }
}
/**
 * The "expansionfiles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $expansionfiles = $androidpublisherService->expansionfiles;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsExpansionfiles_Resource extends Google_Service_Resource
{

  /**
   * Fetches the Expansion File configuration for the APK specified.
   * (expansionfiles.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The version code of the APK whose Expansion File
   * configuration is being read or modified.
   * @param string $expansionFileType
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ExpansionFile
   */
  public function get($packageName, $editId, $apkVersionCode, $expansionFileType, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'expansionFileType' => $expansionFileType);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_ExpansionFile");
  }

  /**
   * Updates the APK's Expansion File configuration to reference another APK's
   * Expansion Files. To add a new Expansion File use the Upload method. This
   * method supports patch semantics. (expansionfiles.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The version code of the APK whose Expansion File
   * configuration is being read or modified.
   * @param string $expansionFileType
   * @param Google_ExpansionFile $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ExpansionFile
   */
  public function patch($packageName, $editId, $apkVersionCode, $expansionFileType, Google_Service_AndroidPublisher_ExpansionFile $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'expansionFileType' => $expansionFileType, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_ExpansionFile");
  }

  /**
   * Updates the APK's Expansion File configuration to reference another APK's
   * Expansion Files. To add a new Expansion File use the Upload method.
   * (expansionfiles.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The version code of the APK whose Expansion File
   * configuration is being read or modified.
   * @param string $expansionFileType
   * @param Google_ExpansionFile $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ExpansionFile
   */
  public function update($packageName, $editId, $apkVersionCode, $expansionFileType, Google_Service_AndroidPublisher_ExpansionFile $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'expansionFileType' => $expansionFileType, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_ExpansionFile");
  }

  /**
   * Uploads and attaches a new Expansion File to the APK specified.
   * (expansionfiles.upload)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param int $apkVersionCode The version code of the APK whose Expansion File
   * configuration is being read or modified.
   * @param string $expansionFileType
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ExpansionFilesUploadResponse
   */
  public function upload($packageName, $editId, $apkVersionCode, $expansionFileType, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'apkVersionCode' => $apkVersionCode, 'expansionFileType' => $expansionFileType);
    $params = array_merge($params, $optParams);
    return $this->call('upload', array($params), "Google_Service_AndroidPublisher_ExpansionFilesUploadResponse");
  }
}
/**
 * The "images" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $images = $androidpublisherService->images;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsImages_Resource extends Google_Service_Resource
{

  /**
   * Deletes the image (specified by id) from the edit. (images.delete)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing whose images are to read or modified. For example, to
   * select Austrian German, pass "de-AT".
   * @param string $imageType
   * @param string $imageId Unique identifier an image within the set of images
   * attached to this edit.
   * @param array $optParams Optional parameters.
   */
  public function delete($packageName, $editId, $language, $imageType, $imageId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'imageType' => $imageType, 'imageId' => $imageId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Deletes all images for the specified language and image type.
   * (images.deleteall)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing whose images are to read or modified. For example, to
   * select Austrian German, pass "de-AT".
   * @param string $imageType
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ImagesDeleteAllResponse
   */
  public function deleteall($packageName, $editId, $language, $imageType, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'imageType' => $imageType);
    $params = array_merge($params, $optParams);
    return $this->call('deleteall', array($params), "Google_Service_AndroidPublisher_ImagesDeleteAllResponse");
  }

  /**
   * Lists all images for the specified language and image type.
   * (images.listEditsImages)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing whose images are to read or modified. For example, to
   * select Austrian German, pass "de-AT".
   * @param string $imageType
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ImagesListResponse
   */
  public function listEditsImages($packageName, $editId, $language, $imageType, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'imageType' => $imageType);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_ImagesListResponse");
  }

  /**
   * Uploads a new image and adds it to the list of images for the specified
   * language and image type. (images.upload)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing whose images are to read or modified. For example, to
   * select Austrian German, pass "de-AT".
   * @param string $imageType
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ImagesUploadResponse
   */
  public function upload($packageName, $editId, $language, $imageType, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'imageType' => $imageType);
    $params = array_merge($params, $optParams);
    return $this->call('upload', array($params), "Google_Service_AndroidPublisher_ImagesUploadResponse");
  }
}
/**
 * The "listings" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $listings = $androidpublisherService->listings;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsListings_Resource extends Google_Service_Resource
{

  /**
   * Deletes the specified localized store listing from an edit. (listings.delete)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing to read or modify. For example, to select Austrian German,
   * pass "de-AT".
   * @param array $optParams Optional parameters.
   */
  public function delete($packageName, $editId, $language, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Deletes all localized listings from an edit. (listings.deleteall)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   */
  public function deleteall($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('deleteall', array($params));
  }

  /**
   * Fetches information about a localized store listing. (listings.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing to read or modify. For example, to select Austrian German,
   * pass "de-AT".
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Listing
   */
  public function get($packageName, $editId, $language, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_Listing");
  }

  /**
   * Returns all of the localized store listings attached to this edit.
   * (listings.listEditsListings)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ListingsListResponse
   */
  public function listEditsListings($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_ListingsListResponse");
  }

  /**
   * Creates or updates a localized store listing. This method supports patch
   * semantics. (listings.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing to read or modify. For example, to select Austrian German,
   * pass "de-AT".
   * @param Google_Listing $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Listing
   */
  public function patch($packageName, $editId, $language, Google_Service_AndroidPublisher_Listing $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_Listing");
  }

  /**
   * Creates or updates a localized store listing. (listings.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $language The language code (a BCP-47 language tag) of the
   * localized listing to read or modify. For example, to select Austrian German,
   * pass "de-AT".
   * @param Google_Listing $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Listing
   */
  public function update($packageName, $editId, $language, Google_Service_AndroidPublisher_Listing $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'language' => $language, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_Listing");
  }
}
/**
 * The "testers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $testers = $androidpublisherService->testers;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsTesters_Resource extends Google_Service_Resource
{

  /**
   * (testers.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Testers
   */
  public function get($packageName, $editId, $track, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_Testers");
  }

  /**
   * (testers.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track
   * @param Google_Testers $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Testers
   */
  public function patch($packageName, $editId, $track, Google_Service_AndroidPublisher_Testers $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_Testers");
  }

  /**
   * (testers.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track
   * @param Google_Testers $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Testers
   */
  public function update($packageName, $editId, $track, Google_Service_AndroidPublisher_Testers $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_Testers");
  }
}
/**
 * The "tracks" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $tracks = $androidpublisherService->tracks;
 *  </code>
 */
class Google_Service_AndroidPublisher_EditsTracks_Resource extends Google_Service_Resource
{

  /**
   * Fetches the track configuration for the specified track type. Includes the
   * APK version codes that are in this track. (tracks.get)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track The track type to read or modify.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Track
   */
  public function get($packageName, $editId, $track, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_Track");
  }

  /**
   * Lists all the track configurations for this edit. (tracks.listEditsTracks)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_TracksListResponse
   */
  public function listEditsTracks($packageName, $editId, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_TracksListResponse");
  }

  /**
   * Updates the track configuration for the specified track type. When halted,
   * the rollout track cannot be updated without adding new APKs, and adding new
   * APKs will cause it to resume. This method supports patch semantics.
   * (tracks.patch)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track The track type to read or modify.
   * @param Google_Track $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Track
   */
  public function patch($packageName, $editId, $track, Google_Service_AndroidPublisher_Track $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_Track");
  }

  /**
   * Updates the track configuration for the specified track type. When halted,
   * the rollout track cannot be updated without adding new APKs, and adding new
   * APKs will cause it to resume. (tracks.update)
   *
   * @param string $packageName Unique identifier for the Android app that is
   * being updated; for example, "com.spiffygame".
   * @param string $editId Unique identifier for this edit.
   * @param string $track The track type to read or modify.
   * @param Google_Track $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_Track
   */
  public function update($packageName, $editId, $track, Google_Service_AndroidPublisher_Track $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'editId' => $editId, 'track' => $track, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_Track");
  }
}

/**
 * The "entitlements" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $entitlements = $androidpublisherService->entitlements;
 *  </code>
 */
class Google_Service_AndroidPublisher_Entitlements_Resource extends Google_Service_Resource
{

  /**
   * Lists the user's current inapp item or subscription entitlements
   * (entitlements.listEntitlements)
   *
   * @param string $packageName The package name of the application the inapp
   * product was sold in (for example, 'com.some.thing').
   * @param array $optParams Optional parameters.
   *
   * @opt_param string token
   * @opt_param string startIndex
   * @opt_param string maxResults
   * @opt_param string productId The product id of the inapp product (for example,
   * 'sku1'). This can be used to restrict the result set.
   * @return Google_Service_AndroidPublisher_EntitlementsListResponse
   */
  public function listEntitlements($packageName, $optParams = array())
  {
    $params = array('packageName' => $packageName);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_EntitlementsListResponse");
  }
}

/**
 * The "inappproducts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $inappproducts = $androidpublisherService->inappproducts;
 *  </code>
 */
class Google_Service_AndroidPublisher_Inappproducts_Resource extends Google_Service_Resource
{

  /**
   * (inappproducts.batch)
   *
   * @param Google_InappproductsBatchRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_InappproductsBatchResponse
   */
  public function batch(Google_Service_AndroidPublisher_InappproductsBatchRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('batch', array($params), "Google_Service_AndroidPublisher_InappproductsBatchResponse");
  }

  /**
   * Delete an in-app product for an app. (inappproducts.delete)
   *
   * @param string $packageName Unique identifier for the Android app with the in-
   * app product; for example, "com.spiffygame".
   * @param string $sku Unique identifier for the in-app product.
   * @param array $optParams Optional parameters.
   */
  public function delete($packageName, $sku, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'sku' => $sku);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Returns information about the in-app product specified. (inappproducts.get)
   *
   * @param string $packageName
   * @param string $sku Unique identifier for the in-app product.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_InAppProduct
   */
  public function get($packageName, $sku, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'sku' => $sku);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_InAppProduct");
  }

  /**
   * Creates a new in-app product for an app. (inappproducts.insert)
   *
   * @param string $packageName Unique identifier for the Android app; for
   * example, "com.spiffygame".
   * @param Google_InAppProduct $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool autoConvertMissingPrices If true the prices for all regions
   * targeted by the parent app that don't have a price specified for this in-app
   * product will be auto converted to the target currency based on the default
   * price. Defaults to false.
   * @return Google_Service_AndroidPublisher_InAppProduct
   */
  public function insert($packageName, Google_Service_AndroidPublisher_InAppProduct $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AndroidPublisher_InAppProduct");
  }

  /**
   * List all the in-app products for an Android app, both subscriptions and
   * managed in-app products.. (inappproducts.listInappproducts)
   *
   * @param string $packageName Unique identifier for the Android app with in-app
   * products; for example, "com.spiffygame".
   * @param array $optParams Optional parameters.
   *
   * @opt_param string token
   * @opt_param string startIndex
   * @opt_param string maxResults
   * @return Google_Service_AndroidPublisher_InappproductsListResponse
   */
  public function listInappproducts($packageName, $optParams = array())
  {
    $params = array('packageName' => $packageName);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AndroidPublisher_InappproductsListResponse");
  }

  /**
   * Updates the details of an in-app product. This method supports patch
   * semantics. (inappproducts.patch)
   *
   * @param string $packageName Unique identifier for the Android app with the in-
   * app product; for example, "com.spiffygame".
   * @param string $sku Unique identifier for the in-app product.
   * @param Google_InAppProduct $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool autoConvertMissingPrices If true the prices for all regions
   * targeted by the parent app that don't have a price specified for this in-app
   * product will be auto converted to the target currency based on the default
   * price. Defaults to false.
   * @return Google_Service_AndroidPublisher_InAppProduct
   */
  public function patch($packageName, $sku, Google_Service_AndroidPublisher_InAppProduct $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'sku' => $sku, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AndroidPublisher_InAppProduct");
  }

  /**
   * Updates the details of an in-app product. (inappproducts.update)
   *
   * @param string $packageName Unique identifier for the Android app with the in-
   * app product; for example, "com.spiffygame".
   * @param string $sku Unique identifier for the in-app product.
   * @param Google_InAppProduct $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool autoConvertMissingPrices If true the prices for all regions
   * targeted by the parent app that don't have a price specified for this in-app
   * product will be auto converted to the target currency based on the default
   * price. Defaults to false.
   * @return Google_Service_AndroidPublisher_InAppProduct
   */
  public function update($packageName, $sku, Google_Service_AndroidPublisher_InAppProduct $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'sku' => $sku, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AndroidPublisher_InAppProduct");
  }
}

/**
 * The "purchases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $purchases = $androidpublisherService->purchases;
 *  </code>
 */
class Google_Service_AndroidPublisher_Purchases_Resource extends Google_Service_Resource
{
}

/**
 * The "products" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $products = $androidpublisherService->products;
 *  </code>
 */
class Google_Service_AndroidPublisher_PurchasesProducts_Resource extends Google_Service_Resource
{

  /**
   * Checks the purchase and consumption status of an inapp item. (products.get)
   *
   * @param string $packageName The package name of the application the inapp
   * product was sold in (for example, 'com.some.thing').
   * @param string $productId The inapp product SKU (for example,
   * 'com.some.thing.inapp1').
   * @param string $token The token provided to the user's device when the inapp
   * product was purchased.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_ProductPurchase
   */
  public function get($packageName, $productId, $token, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'productId' => $productId, 'token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_ProductPurchase");
  }
}
/**
 * The "subscriptions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidpublisherService = new Google_Service_AndroidPublisher(...);
 *   $subscriptions = $androidpublisherService->subscriptions;
 *  </code>
 */
class Google_Service_AndroidPublisher_PurchasesSubscriptions_Resource extends Google_Service_Resource
{

  /**
   * Cancels a user's subscription purchase. The subscription remains valid until
   * its expiration time. (subscriptions.cancel)
   *
   * @param string $packageName The package name of the application for which this
   * subscription was purchased (for example, 'com.some.thing').
   * @param string $subscriptionId The purchased subscription ID (for example,
   * 'monthly001').
   * @param string $token The token provided to the user's device when the
   * subscription was purchased.
   * @param array $optParams Optional parameters.
   */
  public function cancel($packageName, $subscriptionId, $token, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'subscriptionId' => $subscriptionId, 'token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('cancel', array($params));
  }

  /**
   * Defers a user's subscription purchase until a specified future expiration
   * time. (subscriptions.defer)
   *
   * @param string $packageName The package name of the application for which this
   * subscription was purchased (for example, 'com.some.thing').
   * @param string $subscriptionId The purchased subscription ID (for example,
   * 'monthly001').
   * @param string $token The token provided to the user's device when the
   * subscription was purchased.
   * @param Google_SubscriptionPurchasesDeferRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_SubscriptionPurchasesDeferResponse
   */
  public function defer($packageName, $subscriptionId, $token, Google_Service_AndroidPublisher_SubscriptionPurchasesDeferRequest $postBody, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'subscriptionId' => $subscriptionId, 'token' => $token, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('defer', array($params), "Google_Service_AndroidPublisher_SubscriptionPurchasesDeferResponse");
  }

  /**
   * Checks whether a user's subscription purchase is valid and returns its expiry
   * time. (subscriptions.get)
   *
   * @param string $packageName The package name of the application for which this
   * subscription was purchased (for example, 'com.some.thing').
   * @param string $subscriptionId The purchased subscription ID (for example,
   * 'monthly001').
   * @param string $token The token provided to the user's device when the
   * subscription was purchased.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AndroidPublisher_SubscriptionPurchase
   */
  public function get($packageName, $subscriptionId, $token, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'subscriptionId' => $subscriptionId, 'token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AndroidPublisher_SubscriptionPurchase");
  }

  /**
   * Refunds a user's subscription purchase, but the subscription remains valid
   * until its expiration time and it will continue to recur.
   * (subscriptions.refund)
   *
   * @param string $packageName The package name of the application for which this
   * subscription was purchased (for example, 'com.some.thing').
   * @param string $subscriptionId The purchased subscription ID (for example,
   * 'monthly001').
   * @param string $token The token provided to the user's device when the
   * subscription was purchased.
   * @param array $optParams Optional parameters.
   */
  public function refund($packageName, $subscriptionId, $token, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'subscriptionId' => $subscriptionId, 'token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('refund', array($params));
  }

  /**
   * Refunds and immediately revokes a user's subscription purchase. Access to the
   * subscription will be terminated immediately and it will stop recurring.
   * (subscriptions.revoke)
   *
   * @param string $packageName The package name of the application for which this
   * subscription was purchased (for example, 'com.some.thing').
   * @param string $subscriptionId The purchased subscription ID (for example,
   * 'monthly001').
   * @param string $token The token provided to the user's device when the
   * subscription was purchased.
   * @param array $optParams Optional parameters.
   */
  public function revoke($packageName, $subscriptionId, $token, $optParams = array())
  {
    $params = array('packageName' => $packageName, 'subscriptionId' => $subscriptionId, 'token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('revoke', array($params));
  }
}




class Google_Service_AndroidPublisher_Apk extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $binaryType = 'Google_Service_AndroidPublisher_ApkBinary';
  protected $binaryDataType = '';
  public $versionCode;


  public function setBinary(Google_Service_AndroidPublisher_ApkBinary $binary)
  {
    $this->binary = $binary;
  }
  public function getBinary()
  {
    return $this->binary;
  }
  public function setVersionCode($versionCode)
  {
    $this->versionCode = $versionCode;
  }
  public function getVersionCode()
  {
    return $this->versionCode;
  }
}

class Google_Service_AndroidPublisher_ApkBinary extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $sha1;


  public function setSha1($sha1)
  {
    $this->sha1 = $sha1;
  }
  public function getSha1()
  {
    return $this->sha1;
  }
}

class Google_Service_AndroidPublisher_ApkListing extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $language;
  public $recentChanges;


  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setRecentChanges($recentChanges)
  {
    $this->recentChanges = $recentChanges;
  }
  public function getRecentChanges()
  {
    return $this->recentChanges;
  }
}

class Google_Service_AndroidPublisher_ApkListingsListResponse extends Google_Collection
{
  protected $collection_key = 'listings';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $listingsType = 'Google_Service_AndroidPublisher_ApkListing';
  protected $listingsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setListings($listings)
  {
    $this->listings = $listings;
  }
  public function getListings()
  {
    return $this->listings;
  }
}

class Google_Service_AndroidPublisher_ApksAddExternallyHostedRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $externallyHostedApkType = 'Google_Service_AndroidPublisher_ExternallyHostedApk';
  protected $externallyHostedApkDataType = '';


  public function setExternallyHostedApk(Google_Service_AndroidPublisher_ExternallyHostedApk $externallyHostedApk)
  {
    $this->externallyHostedApk = $externallyHostedApk;
  }
  public function getExternallyHostedApk()
  {
    return $this->externallyHostedApk;
  }
}

class Google_Service_AndroidPublisher_ApksAddExternallyHostedResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $externallyHostedApkType = 'Google_Service_AndroidPublisher_ExternallyHostedApk';
  protected $externallyHostedApkDataType = '';


  public function setExternallyHostedApk(Google_Service_AndroidPublisher_ExternallyHostedApk $externallyHostedApk)
  {
    $this->externallyHostedApk = $externallyHostedApk;
  }
  public function getExternallyHostedApk()
  {
    return $this->externallyHostedApk;
  }
}

class Google_Service_AndroidPublisher_ApksListResponse extends Google_Collection
{
  protected $collection_key = 'apks';
  protected $internal_gapi_mappings = array(
  );
  protected $apksType = 'Google_Service_AndroidPublisher_Apk';
  protected $apksDataType = 'array';
  public $kind;


  public function setApks($apks)
  {
    $this->apks = $apks;
  }
  public function getApks()
  {
    return $this->apks;
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

class Google_Service_AndroidPublisher_AppDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contactEmail;
  public $contactPhone;
  public $contactWebsite;
  public $defaultLanguage;


  public function setContactEmail($contactEmail)
  {
    $this->contactEmail = $contactEmail;
  }
  public function getContactEmail()
  {
    return $this->contactEmail;
  }
  public function setContactPhone($contactPhone)
  {
    $this->contactPhone = $contactPhone;
  }
  public function getContactPhone()
  {
    return $this->contactPhone;
  }
  public function setContactWebsite($contactWebsite)
  {
    $this->contactWebsite = $contactWebsite;
  }
  public function getContactWebsite()
  {
    return $this->contactWebsite;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
}

class Google_Service_AndroidPublisher_AppEdit extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $expiryTimeSeconds;
  public $id;


  public function setExpiryTimeSeconds($expiryTimeSeconds)
  {
    $this->expiryTimeSeconds = $expiryTimeSeconds;
  }
  public function getExpiryTimeSeconds()
  {
    return $this->expiryTimeSeconds;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
}

class Google_Service_AndroidPublisher_Entitlement extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $productId;
  public $productType;
  public $token;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setProductId($productId)
  {
    $this->productId = $productId;
  }
  public function getProductId()
  {
    return $this->productId;
  }
  public function setProductType($productType)
  {
    $this->productType = $productType;
  }
  public function getProductType()
  {
    return $this->productType;
  }
  public function setToken($token)
  {
    $this->token = $token;
  }
  public function getToken()
  {
    return $this->token;
  }
}

class Google_Service_AndroidPublisher_EntitlementsListResponse extends Google_Collection
{
  protected $collection_key = 'resources';
  protected $internal_gapi_mappings = array(
  );
  protected $pageInfoType = 'Google_Service_AndroidPublisher_PageInfo';
  protected $pageInfoDataType = '';
  protected $resourcesType = 'Google_Service_AndroidPublisher_Entitlement';
  protected $resourcesDataType = 'array';
  protected $tokenPaginationType = 'Google_Service_AndroidPublisher_TokenPagination';
  protected $tokenPaginationDataType = '';


  public function setPageInfo(Google_Service_AndroidPublisher_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setResources($resources)
  {
    $this->resources = $resources;
  }
  public function getResources()
  {
    return $this->resources;
  }
  public function setTokenPagination(Google_Service_AndroidPublisher_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
}

class Google_Service_AndroidPublisher_ExpansionFile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $fileSize;
  public $referencesVersion;


  public function setFileSize($fileSize)
  {
    $this->fileSize = $fileSize;
  }
  public function getFileSize()
  {
    return $this->fileSize;
  }
  public function setReferencesVersion($referencesVersion)
  {
    $this->referencesVersion = $referencesVersion;
  }
  public function getReferencesVersion()
  {
    return $this->referencesVersion;
  }
}

class Google_Service_AndroidPublisher_ExpansionFilesUploadResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $expansionFileType = 'Google_Service_AndroidPublisher_ExpansionFile';
  protected $expansionFileDataType = '';


  public function setExpansionFile(Google_Service_AndroidPublisher_ExpansionFile $expansionFile)
  {
    $this->expansionFile = $expansionFile;
  }
  public function getExpansionFile()
  {
    return $this->expansionFile;
  }
}

class Google_Service_AndroidPublisher_ExternallyHostedApk extends Google_Collection
{
  protected $collection_key = 'usesPermissions';
  protected $internal_gapi_mappings = array(
  );
  public $applicationLabel;
  public $certificateBase64s;
  public $externallyHostedUrl;
  public $fileSha1Base64;
  public $fileSha256Base64;
  public $fileSize;
  public $iconBase64;
  public $maximumSdk;
  public $minimumSdk;
  public $nativeCodes;
  public $packageName;
  public $usesFeatures;
  protected $usesPermissionsType = 'Google_Service_AndroidPublisher_ExternallyHostedApkUsesPermission';
  protected $usesPermissionsDataType = 'array';
  public $versionCode;
  public $versionName;


  public function setApplicationLabel($applicationLabel)
  {
    $this->applicationLabel = $applicationLabel;
  }
  public function getApplicationLabel()
  {
    return $this->applicationLabel;
  }
  public function setCertificateBase64s($certificateBase64s)
  {
    $this->certificateBase64s = $certificateBase64s;
  }
  public function getCertificateBase64s()
  {
    return $this->certificateBase64s;
  }
  public function setExternallyHostedUrl($externallyHostedUrl)
  {
    $this->externallyHostedUrl = $externallyHostedUrl;
  }
  public function getExternallyHostedUrl()
  {
    return $this->externallyHostedUrl;
  }
  public function setFileSha1Base64($fileSha1Base64)
  {
    $this->fileSha1Base64 = $fileSha1Base64;
  }
  public function getFileSha1Base64()
  {
    return $this->fileSha1Base64;
  }
  public function setFileSha256Base64($fileSha256Base64)
  {
    $this->fileSha256Base64 = $fileSha256Base64;
  }
  public function getFileSha256Base64()
  {
    return $this->fileSha256Base64;
  }
  public function setFileSize($fileSize)
  {
    $this->fileSize = $fileSize;
  }
  public function getFileSize()
  {
    return $this->fileSize;
  }
  public function setIconBase64($iconBase64)
  {
    $this->iconBase64 = $iconBase64;
  }
  public function getIconBase64()
  {
    return $this->iconBase64;
  }
  public function setMaximumSdk($maximumSdk)
  {
    $this->maximumSdk = $maximumSdk;
  }
  public function getMaximumSdk()
  {
    return $this->maximumSdk;
  }
  public function setMinimumSdk($minimumSdk)
  {
    $this->minimumSdk = $minimumSdk;
  }
  public function getMinimumSdk()
  {
    return $this->minimumSdk;
  }
  public function setNativeCodes($nativeCodes)
  {
    $this->nativeCodes = $nativeCodes;
  }
  public function getNativeCodes()
  {
    return $this->nativeCodes;
  }
  public function setPackageName($packageName)
  {
    $this->packageName = $packageName;
  }
  public function getPackageName()
  {
    return $this->packageName;
  }
  public function setUsesFeatures($usesFeatures)
  {
    $this->usesFeatures = $usesFeatures;
  }
  public function getUsesFeatures()
  {
    return $this->usesFeatures;
  }
  public function setUsesPermissions($usesPermissions)
  {
    $this->usesPermissions = $usesPermissions;
  }
  public function getUsesPermissions()
  {
    return $this->usesPermissions;
  }
  public function setVersionCode($versionCode)
  {
    $this->versionCode = $versionCode;
  }
  public function getVersionCode()
  {
    return $this->versionCode;
  }
  public function setVersionName($versionName)
  {
    $this->versionName = $versionName;
  }
  public function getVersionName()
  {
    return $this->versionName;
  }
}

class Google_Service_AndroidPublisher_ExternallyHostedApkUsesPermission extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $maxSdkVersion;
  public $name;


  public function setMaxSdkVersion($maxSdkVersion)
  {
    $this->maxSdkVersion = $maxSdkVersion;
  }
  public function getMaxSdkVersion()
  {
    return $this->maxSdkVersion;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_AndroidPublisher_Image extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $sha1;
  public $url;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setSha1($sha1)
  {
    $this->sha1 = $sha1;
  }
  public function getSha1()
  {
    return $this->sha1;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_AndroidPublisher_ImagesDeleteAllResponse extends Google_Collection
{
  protected $collection_key = 'deleted';
  protected $internal_gapi_mappings = array(
  );
  protected $deletedType = 'Google_Service_AndroidPublisher_Image';
  protected $deletedDataType = 'array';


  public function setDeleted($deleted)
  {
    $this->deleted = $deleted;
  }
  public function getDeleted()
  {
    return $this->deleted;
  }
}

class Google_Service_AndroidPublisher_ImagesListResponse extends Google_Collection
{
  protected $collection_key = 'images';
  protected $internal_gapi_mappings = array(
  );
  protected $imagesType = 'Google_Service_AndroidPublisher_Image';
  protected $imagesDataType = 'array';


  public function setImages($images)
  {
    $this->images = $images;
  }
  public function getImages()
  {
    return $this->images;
  }
}

class Google_Service_AndroidPublisher_ImagesUploadResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $imageType = 'Google_Service_AndroidPublisher_Image';
  protected $imageDataType = '';


  public function setImage(Google_Service_AndroidPublisher_Image $image)
  {
    $this->image = $image;
  }
  public function getImage()
  {
    return $this->image;
  }
}

class Google_Service_AndroidPublisher_InAppProduct extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $defaultLanguage;
  protected $defaultPriceType = 'Google_Service_AndroidPublisher_Price';
  protected $defaultPriceDataType = '';
  protected $listingsType = 'Google_Service_AndroidPublisher_InAppProductListing';
  protected $listingsDataType = 'map';
  public $packageName;
  protected $pricesType = 'Google_Service_AndroidPublisher_Price';
  protected $pricesDataType = 'map';
  public $purchaseType;
  protected $seasonType = 'Google_Service_AndroidPublisher_Season';
  protected $seasonDataType = '';
  public $sku;
  public $status;
  public $subscriptionPeriod;
  public $trialPeriod;


  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDefaultPrice(Google_Service_AndroidPublisher_Price $defaultPrice)
  {
    $this->defaultPrice = $defaultPrice;
  }
  public function getDefaultPrice()
  {
    return $this->defaultPrice;
  }
  public function setListings($listings)
  {
    $this->listings = $listings;
  }
  public function getListings()
  {
    return $this->listings;
  }
  public function setPackageName($packageName)
  {
    $this->packageName = $packageName;
  }
  public function getPackageName()
  {
    return $this->packageName;
  }
  public function setPrices($prices)
  {
    $this->prices = $prices;
  }
  public function getPrices()
  {
    return $this->prices;
  }
  public function setPurchaseType($purchaseType)
  {
    $this->purchaseType = $purchaseType;
  }
  public function getPurchaseType()
  {
    return $this->purchaseType;
  }
  public function setSeason(Google_Service_AndroidPublisher_Season $season)
  {
    $this->season = $season;
  }
  public function getSeason()
  {
    return $this->season;
  }
  public function setSku($sku)
  {
    $this->sku = $sku;
  }
  public function getSku()
  {
    return $this->sku;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSubscriptionPeriod($subscriptionPeriod)
  {
    $this->subscriptionPeriod = $subscriptionPeriod;
  }
  public function getSubscriptionPeriod()
  {
    return $this->subscriptionPeriod;
  }
  public function setTrialPeriod($trialPeriod)
  {
    $this->trialPeriod = $trialPeriod;
  }
  public function getTrialPeriod()
  {
    return $this->trialPeriod;
  }
}

class Google_Service_AndroidPublisher_InAppProductListing extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $title;


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
}

class Google_Service_AndroidPublisher_InAppProductListings extends Google_Model
{
}

class Google_Service_AndroidPublisher_InAppProductPrices extends Google_Model
{
}

class Google_Service_AndroidPublisher_InappproductsBatchRequest extends Google_Collection
{
  protected $collection_key = 'entrys';
  protected $internal_gapi_mappings = array(
  );
  protected $entrysType = 'Google_Service_AndroidPublisher_InappproductsBatchRequestEntry';
  protected $entrysDataType = 'array';


  public function setEntrys($entrys)
  {
    $this->entrys = $entrys;
  }
  public function getEntrys()
  {
    return $this->entrys;
  }
}

class Google_Service_AndroidPublisher_InappproductsBatchRequestEntry extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $batchId;
  protected $inappproductsinsertrequestType = 'Google_Service_AndroidPublisher_InappproductsInsertRequest';
  protected $inappproductsinsertrequestDataType = '';
  protected $inappproductsupdaterequestType = 'Google_Service_AndroidPublisher_InappproductsUpdateRequest';
  protected $inappproductsupdaterequestDataType = '';
  public $methodName;


  public function setBatchId($batchId)
  {
    $this->batchId = $batchId;
  }
  public function getBatchId()
  {
    return $this->batchId;
  }
  public function setInappproductsinsertrequest(Google_Service_AndroidPublisher_InappproductsInsertRequest $inappproductsinsertrequest)
  {
    $this->inappproductsinsertrequest = $inappproductsinsertrequest;
  }
  public function getInappproductsinsertrequest()
  {
    return $this->inappproductsinsertrequest;
  }
  public function setInappproductsupdaterequest(Google_Service_AndroidPublisher_InappproductsUpdateRequest $inappproductsupdaterequest)
  {
    $this->inappproductsupdaterequest = $inappproductsupdaterequest;
  }
  public function getInappproductsupdaterequest()
  {
    return $this->inappproductsupdaterequest;
  }
  public function setMethodName($methodName)
  {
    $this->methodName = $methodName;
  }
  public function getMethodName()
  {
    return $this->methodName;
  }
}

class Google_Service_AndroidPublisher_InappproductsBatchResponse extends Google_Collection
{
  protected $collection_key = 'entrys';
  protected $internal_gapi_mappings = array(
  );
  protected $entrysType = 'Google_Service_AndroidPublisher_InappproductsBatchResponseEntry';
  protected $entrysDataType = 'array';
  public $kind;


  public function setEntrys($entrys)
  {
    $this->entrys = $entrys;
  }
  public function getEntrys()
  {
    return $this->entrys;
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

class Google_Service_AndroidPublisher_InappproductsBatchResponseEntry extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $batchId;
  protected $inappproductsinsertresponseType = 'Google_Service_AndroidPublisher_InappproductsInsertResponse';
  protected $inappproductsinsertresponseDataType = '';
  protected $inappproductsupdateresponseType = 'Google_Service_AndroidPublisher_InappproductsUpdateResponse';
  protected $inappproductsupdateresponseDataType = '';


  public function setBatchId($batchId)
  {
    $this->batchId = $batchId;
  }
  public function getBatchId()
  {
    return $this->batchId;
  }
  public function setInappproductsinsertresponse(Google_Service_AndroidPublisher_InappproductsInsertResponse $inappproductsinsertresponse)
  {
    $this->inappproductsinsertresponse = $inappproductsinsertresponse;
  }
  public function getInappproductsinsertresponse()
  {
    return $this->inappproductsinsertresponse;
  }
  public function setInappproductsupdateresponse(Google_Service_AndroidPublisher_InappproductsUpdateResponse $inappproductsupdateresponse)
  {
    $this->inappproductsupdateresponse = $inappproductsupdateresponse;
  }
  public function getInappproductsupdateresponse()
  {
    return $this->inappproductsupdateresponse;
  }
}

class Google_Service_AndroidPublisher_InappproductsInsertRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $inappproductType = 'Google_Service_AndroidPublisher_InAppProduct';
  protected $inappproductDataType = '';


  public function setInappproduct(Google_Service_AndroidPublisher_InAppProduct $inappproduct)
  {
    $this->inappproduct = $inappproduct;
  }
  public function getInappproduct()
  {
    return $this->inappproduct;
  }
}

class Google_Service_AndroidPublisher_InappproductsInsertResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $inappproductType = 'Google_Service_AndroidPublisher_InAppProduct';
  protected $inappproductDataType = '';


  public function setInappproduct(Google_Service_AndroidPublisher_InAppProduct $inappproduct)
  {
    $this->inappproduct = $inappproduct;
  }
  public function getInappproduct()
  {
    return $this->inappproduct;
  }
}

class Google_Service_AndroidPublisher_InappproductsListResponse extends Google_Collection
{
  protected $collection_key = 'inappproduct';
  protected $internal_gapi_mappings = array(
  );
  protected $inappproductType = 'Google_Service_AndroidPublisher_InAppProduct';
  protected $inappproductDataType = 'array';
  public $kind;
  protected $pageInfoType = 'Google_Service_AndroidPublisher_PageInfo';
  protected $pageInfoDataType = '';
  protected $tokenPaginationType = 'Google_Service_AndroidPublisher_TokenPagination';
  protected $tokenPaginationDataType = '';


  public function setInappproduct($inappproduct)
  {
    $this->inappproduct = $inappproduct;
  }
  public function getInappproduct()
  {
    return $this->inappproduct;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPageInfo(Google_Service_AndroidPublisher_PageInfo $pageInfo)
  {
    $this->pageInfo = $pageInfo;
  }
  public function getPageInfo()
  {
    return $this->pageInfo;
  }
  public function setTokenPagination(Google_Service_AndroidPublisher_TokenPagination $tokenPagination)
  {
    $this->tokenPagination = $tokenPagination;
  }
  public function getTokenPagination()
  {
    return $this->tokenPagination;
  }
}

class Google_Service_AndroidPublisher_InappproductsUpdateRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $inappproductType = 'Google_Service_AndroidPublisher_InAppProduct';
  protected $inappproductDataType = '';


  public function setInappproduct(Google_Service_AndroidPublisher_InAppProduct $inappproduct)
  {
    $this->inappproduct = $inappproduct;
  }
  public function getInappproduct()
  {
    return $this->inappproduct;
  }
}

class Google_Service_AndroidPublisher_InappproductsUpdateResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $inappproductType = 'Google_Service_AndroidPublisher_InAppProduct';
  protected $inappproductDataType = '';


  public function setInappproduct(Google_Service_AndroidPublisher_InAppProduct $inappproduct)
  {
    $this->inappproduct = $inappproduct;
  }
  public function getInappproduct()
  {
    return $this->inappproduct;
  }
}

class Google_Service_AndroidPublisher_Listing extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $fullDescription;
  public $language;
  public $shortDescription;
  public $title;
  public $video;


  public function setFullDescription($fullDescription)
  {
    $this->fullDescription = $fullDescription;
  }
  public function getFullDescription()
  {
    return $this->fullDescription;
  }
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setShortDescription($shortDescription)
  {
    $this->shortDescription = $shortDescription;
  }
  public function getShortDescription()
  {
    return $this->shortDescription;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
  }
  public function setVideo($video)
  {
    $this->video = $video;
  }
  public function getVideo()
  {
    return $this->video;
  }
}

class Google_Service_AndroidPublisher_ListingsListResponse extends Google_Collection
{
  protected $collection_key = 'listings';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $listingsType = 'Google_Service_AndroidPublisher_Listing';
  protected $listingsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setListings($listings)
  {
    $this->listings = $listings;
  }
  public function getListings()
  {
    return $this->listings;
  }
}

class Google_Service_AndroidPublisher_MonthDay extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $day;
  public $month;


  public function setDay($day)
  {
    $this->day = $day;
  }
  public function getDay()
  {
    return $this->day;
  }
  public function setMonth($month)
  {
    $this->month = $month;
  }
  public function getMonth()
  {
    return $this->month;
  }
}

class Google_Service_AndroidPublisher_PageInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $resultPerPage;
  public $startIndex;
  public $totalResults;


  public function setResultPerPage($resultPerPage)
  {
    $this->resultPerPage = $resultPerPage;
  }
  public function getResultPerPage()
  {
    return $this->resultPerPage;
  }
  public function setStartIndex($startIndex)
  {
    $this->startIndex = $startIndex;
  }
  public function getStartIndex()
  {
    return $this->startIndex;
  }
  public function setTotalResults($totalResults)
  {
    $this->totalResults = $totalResults;
  }
  public function getTotalResults()
  {
    return $this->totalResults;
  }
}

class Google_Service_AndroidPublisher_Price extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $currency;
  public $priceMicros;


  public function setCurrency($currency)
  {
    $this->currency = $currency;
  }
  public function getCurrency()
  {
    return $this->currency;
  }
  public function setPriceMicros($priceMicros)
  {
    $this->priceMicros = $priceMicros;
  }
  public function getPriceMicros()
  {
    return $this->priceMicros;
  }
}

class Google_Service_AndroidPublisher_ProductPurchase extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $consumptionState;
  public $developerPayload;
  public $kind;
  public $purchaseState;
  public $purchaseTimeMillis;


  public function setConsumptionState($consumptionState)
  {
    $this->consumptionState = $consumptionState;
  }
  public function getConsumptionState()
  {
    return $this->consumptionState;
  }
  public function setDeveloperPayload($developerPayload)
  {
    $this->developerPayload = $developerPayload;
  }
  public function getDeveloperPayload()
  {
    return $this->developerPayload;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPurchaseState($purchaseState)
  {
    $this->purchaseState = $purchaseState;
  }
  public function getPurchaseState()
  {
    return $this->purchaseState;
  }
  public function setPurchaseTimeMillis($purchaseTimeMillis)
  {
    $this->purchaseTimeMillis = $purchaseTimeMillis;
  }
  public function getPurchaseTimeMillis()
  {
    return $this->purchaseTimeMillis;
  }
}

class Google_Service_AndroidPublisher_Season extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $endType = 'Google_Service_AndroidPublisher_MonthDay';
  protected $endDataType = '';
  protected $startType = 'Google_Service_AndroidPublisher_MonthDay';
  protected $startDataType = '';


  public function setEnd(Google_Service_AndroidPublisher_MonthDay $end)
  {
    $this->end = $end;
  }
  public function getEnd()
  {
    return $this->end;
  }
  public function setStart(Google_Service_AndroidPublisher_MonthDay $start)
  {
    $this->start = $start;
  }
  public function getStart()
  {
    return $this->start;
  }
}

class Google_Service_AndroidPublisher_SubscriptionDeferralInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $desiredExpiryTimeMillis;
  public $expectedExpiryTimeMillis;


  public function setDesiredExpiryTimeMillis($desiredExpiryTimeMillis)
  {
    $this->desiredExpiryTimeMillis = $desiredExpiryTimeMillis;
  }
  public function getDesiredExpiryTimeMillis()
  {
    return $this->desiredExpiryTimeMillis;
  }
  public function setExpectedExpiryTimeMillis($expectedExpiryTimeMillis)
  {
    $this->expectedExpiryTimeMillis = $expectedExpiryTimeMillis;
  }
  public function getExpectedExpiryTimeMillis()
  {
    return $this->expectedExpiryTimeMillis;
  }
}

class Google_Service_AndroidPublisher_SubscriptionPurchase extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $autoRenewing;
  public $expiryTimeMillis;
  public $kind;
  public $startTimeMillis;


  public function setAutoRenewing($autoRenewing)
  {
    $this->autoRenewing = $autoRenewing;
  }
  public function getAutoRenewing()
  {
    return $this->autoRenewing;
  }
  public function setExpiryTimeMillis($expiryTimeMillis)
  {
    $this->expiryTimeMillis = $expiryTimeMillis;
  }
  public function getExpiryTimeMillis()
  {
    return $this->expiryTimeMillis;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setStartTimeMillis($startTimeMillis)
  {
    $this->startTimeMillis = $startTimeMillis;
  }
  public function getStartTimeMillis()
  {
    return $this->startTimeMillis;
  }
}

class Google_Service_AndroidPublisher_SubscriptionPurchasesDeferRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $deferralInfoType = 'Google_Service_AndroidPublisher_SubscriptionDeferralInfo';
  protected $deferralInfoDataType = '';


  public function setDeferralInfo(Google_Service_AndroidPublisher_SubscriptionDeferralInfo $deferralInfo)
  {
    $this->deferralInfo = $deferralInfo;
  }
  public function getDeferralInfo()
  {
    return $this->deferralInfo;
  }
}

class Google_Service_AndroidPublisher_SubscriptionPurchasesDeferResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $newExpiryTimeMillis;


  public function setNewExpiryTimeMillis($newExpiryTimeMillis)
  {
    $this->newExpiryTimeMillis = $newExpiryTimeMillis;
  }
  public function getNewExpiryTimeMillis()
  {
    return $this->newExpiryTimeMillis;
  }
}

class Google_Service_AndroidPublisher_Testers extends Google_Collection
{
  protected $collection_key = 'googlePlusCommunities';
  protected $internal_gapi_mappings = array(
  );
  public $googleGroups;
  public $googlePlusCommunities;


  public function setGoogleGroups($googleGroups)
  {
    $this->googleGroups = $googleGroups;
  }
  public function getGoogleGroups()
  {
    return $this->googleGroups;
  }
  public function setGooglePlusCommunities($googlePlusCommunities)
  {
    $this->googlePlusCommunities = $googlePlusCommunities;
  }
  public function getGooglePlusCommunities()
  {
    return $this->googlePlusCommunities;
  }
}

class Google_Service_AndroidPublisher_TokenPagination extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $nextPageToken;
  public $previousPageToken;


  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setPreviousPageToken($previousPageToken)
  {
    $this->previousPageToken = $previousPageToken;
  }
  public function getPreviousPageToken()
  {
    return $this->previousPageToken;
  }
}

class Google_Service_AndroidPublisher_Track extends Google_Collection
{
  protected $collection_key = 'versionCodes';
  protected $internal_gapi_mappings = array(
  );
  public $track;
  public $userFraction;
  public $versionCodes;


  public function setTrack($track)
  {
    $this->track = $track;
  }
  public function getTrack()
  {
    return $this->track;
  }
  public function setUserFraction($userFraction)
  {
    $this->userFraction = $userFraction;
  }
  public function getUserFraction()
  {
    return $this->userFraction;
  }
  public function setVersionCodes($versionCodes)
  {
    $this->versionCodes = $versionCodes;
  }
  public function getVersionCodes()
  {
    return $this->versionCodes;
  }
}

class Google_Service_AndroidPublisher_TracksListResponse extends Google_Collection
{
  protected $collection_key = 'tracks';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $tracksType = 'Google_Service_AndroidPublisher_Track';
  protected $tracksDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setTracks($tracks)
  {
    $this->tracks = $tracks;
  }
  public function getTracks()
  {
    return $this->tracks;
  }
}
