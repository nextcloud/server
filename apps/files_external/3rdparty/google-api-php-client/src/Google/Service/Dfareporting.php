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
 * Service definition for Dfareporting (v2.1).
 *
 * <p>
 * Manage your DoubleClick Campaign Manager ad campaigns and reports.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/doubleclick-advertisers/reporting/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Dfareporting extends Google_Service
{
  /** View and manage DoubleClick for Advertisers reports. */
  const DFAREPORTING =
      "https://www.googleapis.com/auth/dfareporting";
  /** View and manage your DoubleClick Campaign Manager's (DCM) display ad campaigns. */
  const DFATRAFFICKING =
      "https://www.googleapis.com/auth/dfatrafficking";

  public $accountActiveAdSummaries;
  public $accountPermissionGroups;
  public $accountPermissions;
  public $accountUserProfiles;
  public $accounts;
  public $ads;
  public $advertiserGroups;
  public $advertisers;
  public $browsers;
  public $campaignCreativeAssociations;
  public $campaigns;
  public $changeLogs;
  public $cities;
  public $connectionTypes;
  public $contentCategories;
  public $countries;
  public $creativeAssets;
  public $creativeFieldValues;
  public $creativeFields;
  public $creativeGroups;
  public $creatives;
  public $dimensionValues;
  public $directorySiteContacts;
  public $directorySites;
  public $eventTags;
  public $files;
  public $floodlightActivities;
  public $floodlightActivityGroups;
  public $floodlightConfigurations;
  public $inventoryItems;
  public $landingPages;
  public $metros;
  public $mobileCarriers;
  public $operatingSystemVersions;
  public $operatingSystems;
  public $orderDocuments;
  public $orders;
  public $placementGroups;
  public $placementStrategies;
  public $placements;
  public $platformTypes;
  public $postalCodes;
  public $projects;
  public $regions;
  public $remarketingListShares;
  public $remarketingLists;
  public $reports;
  public $reports_compatibleFields;
  public $reports_files;
  public $sites;
  public $sizes;
  public $subaccounts;
  public $targetableRemarketingLists;
  public $userProfiles;
  public $userRolePermissionGroups;
  public $userRolePermissions;
  public $userRoles;
  

  /**
   * Constructs the internal representation of the Dfareporting service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->servicePath = 'dfareporting/v2.1/';
    $this->version = 'v2.1';
    $this->serviceName = 'dfareporting';

    $this->accountActiveAdSummaries = new Google_Service_Dfareporting_AccountActiveAdSummaries_Resource(
        $this,
        $this->serviceName,
        'accountActiveAdSummaries',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/accountActiveAdSummaries/{summaryAccountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'summaryAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accountPermissionGroups = new Google_Service_Dfareporting_AccountPermissionGroups_Resource(
        $this,
        $this->serviceName,
        'accountPermissionGroups',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/accountPermissionGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/accountPermissionGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accountPermissions = new Google_Service_Dfareporting_AccountPermissions_Resource(
        $this,
        $this->serviceName,
        'accountPermissions',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/accountPermissions/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/accountPermissions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accountUserProfiles = new Google_Service_Dfareporting_AccountUserProfiles_Resource(
        $this,
        $this->serviceName,
        'accountUserProfiles',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/accountUserProfiles/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/accountUserProfiles',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/accountUserProfiles',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'subaccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userRoleId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/accountUserProfiles',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/accountUserProfiles',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accounts = new Google_Service_Dfareporting_Accounts_Resource(
        $this,
        $this->serviceName,
        'accounts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/accounts/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/accounts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/accounts',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/accounts',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->ads = new Google_Service_Dfareporting_Ads_Resource(
        $this,
        $this->serviceName,
        'ads',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/ads/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/ads',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/ads',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'landingPageIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'overriddenEventTagId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'campaignIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'archived' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'creativeOptimizationConfigurationIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sslCompliant' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sizeIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'type' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sslRequired' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'creativeIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'creativeType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'placementIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'compatibility' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'audienceSegmentIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'remarketingListIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'dynamicClickTracker' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/ads',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/ads',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->advertiserGroups = new Google_Service_Dfareporting_AdvertiserGroups_Resource(
        $this,
        $this->serviceName,
        'advertiserGroups',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/advertiserGroups',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->advertisers = new Google_Service_Dfareporting_Advertisers_Resource(
        $this,
        $this->serviceName,
        'advertisers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/advertisers/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/advertisers',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/advertisers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'status' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'subaccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'includeAdvertisersWithoutGroupsOnly' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'onlyParent' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'floodlightConfigurationIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'advertiserGroupIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/advertisers',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/advertisers',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->browsers = new Google_Service_Dfareporting_Browsers_Resource(
        $this,
        $this->serviceName,
        'browsers',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'userprofiles/{profileId}/browsers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->campaignCreativeAssociations = new Google_Service_Dfareporting_CampaignCreativeAssociations_Resource(
        $this,
        $this->serviceName,
        'campaignCreativeAssociations',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/campaignCreativeAssociations',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/campaignCreativeAssociations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->campaigns = new Google_Service_Dfareporting_Campaigns_Resource(
        $this,
        $this->serviceName,
        'campaigns',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/campaigns',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'defaultLandingPageName' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'defaultLandingPageUrl' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/campaigns',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'archived' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'subaccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'excludedIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserGroupIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'overriddenEventTagId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'atLeastOneOptimizationActivity' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/campaigns',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/campaigns',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->changeLogs = new Google_Service_Dfareporting_ChangeLogs_Resource(
        $this,
        $this->serviceName,
        'changeLogs',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/changeLogs/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/changeLogs',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'minChangeTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxChangeTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userProfileIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'objectIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'action' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'objectType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->cities = new Google_Service_Dfareporting_Cities_Resource(
        $this,
        $this->serviceName,
        'cities',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'userprofiles/{profileId}/cities',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'dartIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'namePrefix' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'regionDartIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'countryDartIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->connectionTypes = new Google_Service_Dfareporting_ConnectionTypes_Resource(
        $this,
        $this->serviceName,
        'connectionTypes',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/connectionTypes/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/connectionTypes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->contentCategories = new Google_Service_Dfareporting_ContentCategories_Resource(
        $this,
        $this->serviceName,
        'contentCategories',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/contentCategories/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/contentCategories/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/contentCategories',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/contentCategories',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/contentCategories',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/contentCategories',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->countries = new Google_Service_Dfareporting_Countries_Resource(
        $this,
        $this->serviceName,
        'countries',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/countries/{dartId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'dartId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/countries',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creativeAssets = new Google_Service_Dfareporting_CreativeAssets_Resource(
        $this,
        $this->serviceName,
        'creativeAssets',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'userprofiles/{profileId}/creativeAssets/{advertiserId}/creativeAssets',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'advertiserId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creativeFieldValues = new Google_Service_Dfareporting_CreativeFieldValues_Resource(
        $this,
        $this->serviceName,
        'creativeFieldValues',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{creativeFieldId}/creativeFieldValues',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'creativeFieldId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creativeFields = new Google_Service_Dfareporting_CreativeFields_Resource(
        $this,
        $this->serviceName,
        'creativeFields',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/creativeFields/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/creativeFields',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/creativeFields',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/creativeFields',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/creativeFields',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creativeGroups = new Google_Service_Dfareporting_CreativeGroups_Resource(
        $this,
        $this->serviceName,
        'creativeGroups',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/creativeGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/creativeGroups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/creativeGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'groupNumber' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/creativeGroups',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/creativeGroups',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creatives = new Google_Service_Dfareporting_Creatives_Resource(
        $this,
        $this->serviceName,
        'creatives',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/creatives/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/creatives',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/creatives',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sizeIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'archived' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'campaignId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'renderingIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'studioCreativeId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'companionCreativeIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'creativeFieldIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'types' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/creatives',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/creatives',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->dimensionValues = new Google_Service_Dfareporting_DimensionValues_Resource(
        $this,
        $this->serviceName,
        'dimensionValues',
        array(
          'methods' => array(
            'query' => array(
              'path' => 'userprofiles/{profileId}/dimensionvalues/query',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->directorySiteContacts = new Google_Service_Dfareporting_DirectorySiteContacts_Resource(
        $this,
        $this->serviceName,
        'directorySiteContacts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/directorySiteContacts/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/directorySiteContacts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'directorySiteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->directorySites = new Google_Service_Dfareporting_DirectorySites_Resource(
        $this,
        $this->serviceName,
        'directorySites',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/directorySites/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/directorySites',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/directorySites',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'acceptsInterstitialPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'countryId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'acceptsInStreamVideoPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'acceptsPublisherPaidPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'parentId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'dfp_network_code' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->eventTags = new Google_Service_Dfareporting_EventTags_Resource(
        $this,
        $this->serviceName,
        'eventTags',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/eventTags/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/eventTags/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/eventTags',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/eventTags',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'campaignId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'enabled' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'adId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'eventTagTypes' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'definitionsOnly' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/eventTags',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/eventTags',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->files = new Google_Service_Dfareporting_Files_Resource(
        $this,
        $this->serviceName,
        'files',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'reports/{reportId}/files/{fileId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'fileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/files',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sortField' => array(
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
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'scope' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->floodlightActivities = new Google_Service_Dfareporting_FloodlightActivities_Resource(
        $this,
        $this->serviceName,
        'floodlightActivities',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'generatetag' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities/generatetag',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'floodlightActivityId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'floodlightActivityGroupIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'floodlightConfigurationId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'floodlightActivityGroupName' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'tagString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'floodlightActivityGroupTagString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'floodlightActivityGroupType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivities',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->floodlightActivityGroups = new Google_Service_Dfareporting_FloodlightActivityGroups_Resource(
        $this,
        $this->serviceName,
        'floodlightActivityGroups',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'floodlightConfigurationId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'type' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/floodlightActivityGroups',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->floodlightConfigurations = new Google_Service_Dfareporting_FloodlightConfigurations_Resource(
        $this,
        $this->serviceName,
        'floodlightConfigurations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/floodlightConfigurations/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/floodlightConfigurations',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/floodlightConfigurations',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/floodlightConfigurations',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->inventoryItems = new Google_Service_Dfareporting_InventoryItems_Resource(
        $this,
        $this->serviceName,
        'inventoryItems',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/inventoryItems/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/inventoryItems',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'siteId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'inPlan' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->landingPages = new Google_Service_Dfareporting_LandingPages_Resource(
        $this,
        $this->serviceName,
        'landingPages',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/campaigns/{campaignId}/landingPages',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'campaignId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->metros = new Google_Service_Dfareporting_Metros_Resource(
        $this,
        $this->serviceName,
        'metros',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'userprofiles/{profileId}/metros',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->mobileCarriers = new Google_Service_Dfareporting_MobileCarriers_Resource(
        $this,
        $this->serviceName,
        'mobileCarriers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/mobileCarriers/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/mobileCarriers',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->operatingSystemVersions = new Google_Service_Dfareporting_OperatingSystemVersions_Resource(
        $this,
        $this->serviceName,
        'operatingSystemVersions',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/operatingSystemVersions/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/operatingSystemVersions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->operatingSystems = new Google_Service_Dfareporting_OperatingSystems_Resource(
        $this,
        $this->serviceName,
        'operatingSystems',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/operatingSystems/{dartId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'dartId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/operatingSystems',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->orderDocuments = new Google_Service_Dfareporting_OrderDocuments_Resource(
        $this,
        $this->serviceName,
        'orderDocuments',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/orderDocuments/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/orderDocuments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'siteId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'approved' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),
          )
        )
    );
    $this->orders = new Google_Service_Dfareporting_Orders_Resource(
        $this,
        $this->serviceName,
        'orders',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/orders/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/projects/{projectId}/orders',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projectId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'siteId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->placementGroups = new Google_Service_Dfareporting_PlacementGroups_Resource(
        $this,
        $this->serviceName,
        'placementGroups',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/placementGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/placementGroups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/placementGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'placementStrategyIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'archived' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'contentCategoryIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'directorySiteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'placementGroupType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pricingTypes' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'siteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'campaignIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/placementGroups',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/placementGroups',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->placementStrategies = new Google_Service_Dfareporting_PlacementStrategies_Resource(
        $this,
        $this->serviceName,
        'placementStrategies',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/placementStrategies',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->placements = new Google_Service_Dfareporting_Placements_Resource(
        $this,
        $this->serviceName,
        'placements',
        array(
          'methods' => array(
            'generatetags' => array(
              'path' => 'userprofiles/{profileId}/placements/generatetags',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'tagFormats' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'placementIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'campaignId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/placements/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/placements',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/placements',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'placementStrategyIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'archived' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'contentCategoryIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'directorySiteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'paymentSource' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'sizeIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'compatibilities' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'groupIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'pricingTypes' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'siteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'campaignIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/placements',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/placements',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->platformTypes = new Google_Service_Dfareporting_PlatformTypes_Resource(
        $this,
        $this->serviceName,
        'platformTypes',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/platformTypes/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/platformTypes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->postalCodes = new Google_Service_Dfareporting_PostalCodes_Resource(
        $this,
        $this->serviceName,
        'postalCodes',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/postalCodes/{code}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'code' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/postalCodes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->projects = new Google_Service_Dfareporting_Projects_Resource(
        $this,
        $this->serviceName,
        'projects',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/projects/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/projects',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'advertiserIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->regions = new Google_Service_Dfareporting_Regions_Resource(
        $this,
        $this->serviceName,
        'regions',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'userprofiles/{profileId}/regions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->remarketingListShares = new Google_Service_Dfareporting_RemarketingListShares_Resource(
        $this,
        $this->serviceName,
        'remarketingListShares',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/remarketingListShares/{remarketingListId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'remarketingListId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/remarketingListShares',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'remarketingListId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/remarketingListShares',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->remarketingLists = new Google_Service_Dfareporting_RemarketingLists_Resource(
        $this,
        $this->serviceName,
        'remarketingLists',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/remarketingLists/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/remarketingLists',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/remarketingLists',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
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
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'floodlightActivityId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/remarketingLists',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/remarketingLists',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->reports = new Google_Service_Dfareporting_Reports_Resource(
        $this,
        $this->serviceName,
        'reports',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/reports',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/reports',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sortField' => array(
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
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'scope' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'run' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}/run',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'synchronous' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->reports_compatibleFields = new Google_Service_Dfareporting_ReportsCompatibleFields_Resource(
        $this,
        $this->serviceName,
        'compatibleFields',
        array(
          'methods' => array(
            'query' => array(
              'path' => 'userprofiles/{profileId}/reports/compatiblefields/query',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->reports_files = new Google_Service_Dfareporting_ReportsFiles_Resource(
        $this,
        $this->serviceName,
        'files',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}/files/{fileId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'fileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/reports/{reportId}/files',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'reportId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sortField' => array(
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
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->sites = new Google_Service_Dfareporting_Sites_Resource(
        $this,
        $this->serviceName,
        'sites',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/sites/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/sites',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/sites',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'acceptsInterstitialPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'subaccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'directorySiteIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'acceptsInStreamVideoPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'acceptsPublisherPaidPlacements' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'adWordsSite' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'unmappedSite' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'approved' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'campaignIds' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/sites',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/sites',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->sizes = new Google_Service_Dfareporting_Sizes_Resource(
        $this,
        $this->serviceName,
        'sizes',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/sizes/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/sizes',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/sizes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'iabStandard' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'width' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'height' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->subaccounts = new Google_Service_Dfareporting_Subaccounts_Resource(
        $this,
        $this->serviceName,
        'subaccounts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/subaccounts/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/subaccounts',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/subaccounts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/subaccounts',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/subaccounts',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->targetableRemarketingLists = new Google_Service_Dfareporting_TargetableRemarketingLists_Resource(
        $this,
        $this->serviceName,
        'targetableRemarketingLists',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/targetableRemarketingLists/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/targetableRemarketingLists',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'advertiserId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
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
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'active' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),
          )
        )
    );
    $this->userProfiles = new Google_Service_Dfareporting_UserProfiles_Resource(
        $this,
        $this->serviceName,
        'userProfiles',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->userRolePermissionGroups = new Google_Service_Dfareporting_UserRolePermissionGroups_Resource(
        $this,
        $this->serviceName,
        'userRolePermissionGroups',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/userRolePermissionGroups/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/userRolePermissionGroups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->userRolePermissions = new Google_Service_Dfareporting_UserRolePermissions_Resource(
        $this,
        $this->serviceName,
        'userRolePermissions',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'userprofiles/{profileId}/userRolePermissions/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/userRolePermissions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->userRoles = new Google_Service_Dfareporting_UserRoles_Resource(
        $this,
        $this->serviceName,
        'userRoles',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'userprofiles/{profileId}/userRoles/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'userprofiles/{profileId}/userRoles/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'userprofiles/{profileId}/userRoles',
              'httpMethod' => 'POST',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'userprofiles/{profileId}/userRoles',
              'httpMethod' => 'GET',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'searchString' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'subaccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortField' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'accountUserRoleOnly' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'patch' => array(
              'path' => 'userprofiles/{profileId}/userRoles',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'profileId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'userprofiles/{profileId}/userRoles',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'profileId' => array(
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
 * The "accountActiveAdSummaries" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $accountActiveAdSummaries = $dfareportingService->accountActiveAdSummaries;
 *  </code>
 */
class Google_Service_Dfareporting_AccountActiveAdSummaries_Resource extends Google_Service_Resource
{

  /**
   * Gets the account's active ad summary by account ID.
   * (accountActiveAdSummaries.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $summaryAccountId Account ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountActiveAdSummary
   */
  public function get($profileId, $summaryAccountId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'summaryAccountId' => $summaryAccountId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_AccountActiveAdSummary");
  }
}

/**
 * The "accountPermissionGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $accountPermissionGroups = $dfareportingService->accountPermissionGroups;
 *  </code>
 */
class Google_Service_Dfareporting_AccountPermissionGroups_Resource extends Google_Service_Resource
{

  /**
   * Gets one account permission group by ID. (accountPermissionGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Account permission group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountPermissionGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_AccountPermissionGroup");
  }

  /**
   * Retrieves the list of account permission groups.
   * (accountPermissionGroups.listAccountPermissionGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountPermissionGroupsListResponse
   */
  public function listAccountPermissionGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AccountPermissionGroupsListResponse");
  }
}

/**
 * The "accountPermissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $accountPermissions = $dfareportingService->accountPermissions;
 *  </code>
 */
class Google_Service_Dfareporting_AccountPermissions_Resource extends Google_Service_Resource
{

  /**
   * Gets one account permission by ID. (accountPermissions.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Account permission ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountPermission
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_AccountPermission");
  }

  /**
   * Retrieves the list of account permissions.
   * (accountPermissions.listAccountPermissions)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountPermissionsListResponse
   */
  public function listAccountPermissions($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AccountPermissionsListResponse");
  }
}

/**
 * The "accountUserProfiles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $accountUserProfiles = $dfareportingService->accountUserProfiles;
 *  </code>
 */
class Google_Service_Dfareporting_AccountUserProfiles_Resource extends Google_Service_Resource
{

  /**
   * Gets one account user profile by ID. (accountUserProfiles.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User profile ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountUserProfile
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_AccountUserProfile");
  }

  /**
   * Inserts a new account user profile. (accountUserProfiles.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_AccountUserProfile $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountUserProfile
   */
  public function insert($profileId, Google_Service_Dfareporting_AccountUserProfile $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_AccountUserProfile");
  }

  /**
   * Retrieves a list of account user profiles, possibly filtered.
   * (accountUserProfiles.listAccountUserProfiles)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name, ID or
   * email. Wildcards (*) are allowed. For example, "user profile*2015" will
   * return objects with names like "user profile June 2015", "user profile April
   * 2015", or simply "user profile 2015". Most of the searches also add wildcards
   * implicitly at the start and the end of the search string. For example, a
   * search string of "user profile" will match objects with name "my user
   * profile", "user profile 2015", or simply "user profile".
   * @opt_param string subaccountId Select only user profiles with the specified
   * subaccount ID.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only user profiles with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string userRoleId Select only user profiles with the specified
   * user role ID.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool active Select only active user profiles.
   * @return Google_Service_Dfareporting_AccountUserProfilesListResponse
   */
  public function listAccountUserProfiles($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AccountUserProfilesListResponse");
  }

  /**
   * Updates an existing account user profile. This method supports patch
   * semantics. (accountUserProfiles.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User profile ID.
   * @param Google_AccountUserProfile $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountUserProfile
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_AccountUserProfile $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_AccountUserProfile");
  }

  /**
   * Updates an existing account user profile. (accountUserProfiles.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_AccountUserProfile $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AccountUserProfile
   */
  public function update($profileId, Google_Service_Dfareporting_AccountUserProfile $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_AccountUserProfile");
  }
}

/**
 * The "accounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $accounts = $dfareportingService->accounts;
 *  </code>
 */
class Google_Service_Dfareporting_Accounts_Resource extends Google_Service_Resource
{

  /**
   * Gets one account by ID. (accounts.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Account ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Account
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Account");
  }

  /**
   * Retrieves the list of accounts, possibly filtered. (accounts.listAccounts)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "account*2015" will return objects
   * with names like "account June 2015", "account April 2015", or simply "account
   * 2015". Most of the searches also add wildcards implicitly at the start and
   * the end of the search string. For example, a search string of "account" will
   * match objects with name "my account", "account 2015", or simply "account".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only accounts with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool active Select only active accounts. Don't set this field to
   * select both active and non-active accounts.
   * @return Google_Service_Dfareporting_AccountsListResponse
   */
  public function listAccounts($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AccountsListResponse");
  }

  /**
   * Updates an existing account. This method supports patch semantics.
   * (accounts.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Account ID.
   * @param Google_Account $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Account
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Account $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Account");
  }

  /**
   * Updates an existing account. (accounts.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Account $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Account
   */
  public function update($profileId, Google_Service_Dfareporting_Account $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Account");
  }
}

/**
 * The "ads" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $ads = $dfareportingService->ads;
 *  </code>
 */
class Google_Service_Dfareporting_Ads_Resource extends Google_Service_Resource
{

  /**
   * Gets one ad by ID. (ads.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Ad ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Ad
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Ad");
  }

  /**
   * Inserts a new ad. (ads.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Ad
   */
  public function insert($profileId, Google_Service_Dfareporting_Ad $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Ad");
  }

  /**
   * Retrieves a list of ads, possibly filtered. (ads.listAds)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string landingPageIds Select only ads with these landing page IDs.
   * @opt_param string overriddenEventTagId Select only ads with this event tag
   * override ID.
   * @opt_param string campaignIds Select only ads with these campaign IDs.
   * @opt_param bool archived Select only archived ads.
   * @opt_param string creativeOptimizationConfigurationIds Select only ads with
   * these creative optimization configuration IDs.
   * @opt_param bool sslCompliant Select only ads that are SSL-compliant.
   * @opt_param string sizeIds Select only ads with these size IDs.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string type Select only ads with these types.
   * @opt_param bool sslRequired Select only ads that require SSL.
   * @opt_param string creativeIds Select only ads with these creative IDs
   * assigned.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string creativeType Select only ads with the specified
   * creativeType.
   * @opt_param string placementIds Select only ads with these placement IDs
   * assigned.
   * @opt_param bool active Select only active ads.
   * @opt_param string compatibility Select default ads with the specified
   * compatibility. Applicable when type is AD_SERVING_DEFAULT_AD. WEB and
   * WEB_INTERSTITIAL refer to rendering either on desktop or on mobile devices
   * for regular or interstitial ads, respectively. APP and APP_INTERSTITIAL are
   * for rendering in mobile apps. IN_STREAM_VIDEO refers to rendering an in-
   * stream video ads developed with the VAST standard.
   * @opt_param string advertiserId Select only ads with this advertiser ID.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "ad*2015" will return objects with
   * names like "ad June 2015", "ad April 2015", or simply "ad 2015". Most of the
   * searches also add wildcards implicitly at the start and the end of the search
   * string. For example, a search string of "ad" will match objects with name "my
   * ad", "ad 2015", or simply "ad".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string audienceSegmentIds Select only ads with these audience
   * segment IDs.
   * @opt_param string ids Select only ads with these IDs.
   * @opt_param string remarketingListIds Select only ads whose list targeting
   * expression use these remarketing list IDs.
   * @opt_param bool dynamicClickTracker Select only dynamic click trackers.
   * Applicable when type is AD_SERVING_CLICK_TRACKER. If true, select dynamic
   * click trackers. If false, select static click trackers. Leave unset to select
   * both.
   * @return Google_Service_Dfareporting_AdsListResponse
   */
  public function listAds($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AdsListResponse");
  }

  /**
   * Updates an existing ad. This method supports patch semantics. (ads.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Ad ID.
   * @param Google_Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Ad
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Ad $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Ad");
  }

  /**
   * Updates an existing ad. (ads.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Ad
   */
  public function update($profileId, Google_Service_Dfareporting_Ad $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Ad");
  }
}

/**
 * The "advertiserGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $advertiserGroups = $dfareportingService->advertiserGroups;
 *  </code>
 */
class Google_Service_Dfareporting_AdvertiserGroups_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing advertiser group. (advertiserGroups.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Advertiser group ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one advertiser group by ID. (advertiserGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Advertiser group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AdvertiserGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_AdvertiserGroup");
  }

  /**
   * Inserts a new advertiser group. (advertiserGroups.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_AdvertiserGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AdvertiserGroup
   */
  public function insert($profileId, Google_Service_Dfareporting_AdvertiserGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_AdvertiserGroup");
  }

  /**
   * Retrieves a list of advertiser groups, possibly filtered.
   * (advertiserGroups.listAdvertiserGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "advertiser*2015" will return objects
   * with names like "advertiser group June 2015", "advertiser group April 2015",
   * or simply "advertiser group 2015". Most of the searches also add wildcards
   * implicitly at the start and the end of the search string. For example, a
   * search string of "advertisergroup" will match objects with name "my
   * advertisergroup", "advertisergroup 2015", or simply "advertisergroup".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only advertiser groups with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_AdvertiserGroupsListResponse
   */
  public function listAdvertiserGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AdvertiserGroupsListResponse");
  }

  /**
   * Updates an existing advertiser group. This method supports patch semantics.
   * (advertiserGroups.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Advertiser group ID.
   * @param Google_AdvertiserGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AdvertiserGroup
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_AdvertiserGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_AdvertiserGroup");
  }

  /**
   * Updates an existing advertiser group. (advertiserGroups.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_AdvertiserGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_AdvertiserGroup
   */
  public function update($profileId, Google_Service_Dfareporting_AdvertiserGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_AdvertiserGroup");
  }
}

/**
 * The "advertisers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $advertisers = $dfareportingService->advertisers;
 *  </code>
 */
class Google_Service_Dfareporting_Advertisers_Resource extends Google_Service_Resource
{

  /**
   * Gets one advertiser by ID. (advertisers.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Advertiser ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Advertiser
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Advertiser");
  }

  /**
   * Inserts a new advertiser. (advertisers.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Advertiser $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Advertiser
   */
  public function insert($profileId, Google_Service_Dfareporting_Advertiser $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Advertiser");
  }

  /**
   * Retrieves a list of advertisers, possibly filtered.
   * (advertisers.listAdvertisers)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string status Select only advertisers with the specified status.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "advertiser*2015" will return objects
   * with names like "advertiser June 2015", "advertiser April 2015", or simply
   * "advertiser 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "advertiser" will match objects with name "my advertiser", "advertiser 2015",
   * or simply "advertiser".
   * @opt_param string subaccountId Select only advertisers with these subaccount
   * IDs.
   * @opt_param bool includeAdvertisersWithoutGroupsOnly Select only advertisers
   * which do not belong to any advertiser group.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only advertisers with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param bool onlyParent Select only advertisers which use another
   * advertiser's floodlight configuration.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string floodlightConfigurationIds Select only advertisers with
   * these floodlight configuration IDs.
   * @opt_param string advertiserGroupIds Select only advertisers with these
   * advertiser group IDs.
   * @return Google_Service_Dfareporting_AdvertisersListResponse
   */
  public function listAdvertisers($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_AdvertisersListResponse");
  }

  /**
   * Updates an existing advertiser. This method supports patch semantics.
   * (advertisers.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Advertiser ID.
   * @param Google_Advertiser $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Advertiser
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Advertiser $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Advertiser");
  }

  /**
   * Updates an existing advertiser. (advertisers.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Advertiser $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Advertiser
   */
  public function update($profileId, Google_Service_Dfareporting_Advertiser $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Advertiser");
  }
}

/**
 * The "browsers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $browsers = $dfareportingService->browsers;
 *  </code>
 */
class Google_Service_Dfareporting_Browsers_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of browsers. (browsers.listBrowsers)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_BrowsersListResponse
   */
  public function listBrowsers($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_BrowsersListResponse");
  }
}

/**
 * The "campaignCreativeAssociations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $campaignCreativeAssociations = $dfareportingService->campaignCreativeAssociations;
 *  </code>
 */
class Google_Service_Dfareporting_CampaignCreativeAssociations_Resource extends Google_Service_Resource
{

  /**
   * Associates a creative with the specified campaign. This method creates a
   * default ad with dimensions matching the creative in the campaign if such a
   * default ad does not exist already. (campaignCreativeAssociations.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Campaign ID in this association.
   * @param Google_CampaignCreativeAssociation $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CampaignCreativeAssociation
   */
  public function insert($profileId, $campaignId, Google_Service_Dfareporting_CampaignCreativeAssociation $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_CampaignCreativeAssociation");
  }

  /**
   * Retrieves the list of creative IDs associated with the specified campaign.
   * (campaignCreativeAssociations.listCampaignCreativeAssociations)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Campaign ID in this association.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param int maxResults Maximum number of results to return.
   * @return Google_Service_Dfareporting_CampaignCreativeAssociationsListResponse
   */
  public function listCampaignCreativeAssociations($profileId, $campaignId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CampaignCreativeAssociationsListResponse");
  }
}

/**
 * The "campaigns" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $campaigns = $dfareportingService->campaigns;
 *  </code>
 */
class Google_Service_Dfareporting_Campaigns_Resource extends Google_Service_Resource
{

  /**
   * Gets one campaign by ID. (campaigns.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Campaign ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Campaign
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Campaign");
  }

  /**
   * Inserts a new campaign. (campaigns.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $defaultLandingPageName Default landing page name for this new
   * campaign. Must be less than 256 characters long.
   * @param string $defaultLandingPageUrl Default landing page URL for this new
   * campaign.
   * @param Google_Campaign $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Campaign
   */
  public function insert($profileId, $defaultLandingPageName, $defaultLandingPageUrl, Google_Service_Dfareporting_Campaign $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'defaultLandingPageName' => $defaultLandingPageName, 'defaultLandingPageUrl' => $defaultLandingPageUrl, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Campaign");
  }

  /**
   * Retrieves a list of campaigns, possibly filtered. (campaigns.listCampaigns)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool archived Select only archived campaigns. Don't set this field
   * to select both archived and non-archived campaigns.
   * @opt_param string searchString Allows searching for campaigns by name or ID.
   * Wildcards (*) are allowed. For example, "campaign*2015" will return campaigns
   * with names like "campaign June 2015", "campaign April 2015", or simply
   * "campaign 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "campaign" will match campaigns with name "my campaign", "campaign 2015", or
   * simply "campaign".
   * @opt_param string subaccountId Select only campaigns that belong to this
   * subaccount.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only campaigns that belong to these
   * advertisers.
   * @opt_param string ids Select only campaigns with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string excludedIds Exclude campaigns with these IDs.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string advertiserGroupIds Select only campaigns whose advertisers
   * belong to these advertiser groups.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string overriddenEventTagId Select only campaigns that have
   * overridden this event tag ID.
   * @opt_param bool atLeastOneOptimizationActivity Select only campaigns that
   * have at least one optimization activity.
   * @return Google_Service_Dfareporting_CampaignsListResponse
   */
  public function listCampaigns($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CampaignsListResponse");
  }

  /**
   * Updates an existing campaign. This method supports patch semantics.
   * (campaigns.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Campaign ID.
   * @param Google_Campaign $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Campaign
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Campaign $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Campaign");
  }

  /**
   * Updates an existing campaign. (campaigns.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Campaign $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Campaign
   */
  public function update($profileId, Google_Service_Dfareporting_Campaign $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Campaign");
  }
}

/**
 * The "changeLogs" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $changeLogs = $dfareportingService->changeLogs;
 *  </code>
 */
class Google_Service_Dfareporting_ChangeLogs_Resource extends Google_Service_Resource
{

  /**
   * Gets one change log by ID. (changeLogs.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Change log ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ChangeLog
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_ChangeLog");
  }

  /**
   * Retrieves a list of change logs. (changeLogs.listChangeLogs)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string minChangeTime Select only change logs whose change time is
   * before the specified minChangeTime.The time should be formatted as an RFC3339
   * date/time string. For example, for 10:54 PM on July 18th, 2015, in the
   * America/New York time zone, the format is "2015-07-18T22:54:00-04:00". In
   * other words, the year, month, day, the letter T, the hour (24-hour clock
   * system), minute, second, and then the time zone offset.
   * @opt_param string searchString Select only change logs whose object ID, user
   * name, old or new values match the search string.
   * @opt_param string maxChangeTime Select only change logs whose change time is
   * before the specified maxChangeTime.The time should be formatted as an RFC3339
   * date/time string. For example, for 10:54 PM on July 18th, 2015, in the
   * America/New York time zone, the format is "2015-07-18T22:54:00-04:00". In
   * other words, the year, month, day, the letter T, the hour (24-hour clock
   * system), minute, second, and then the time zone offset.
   * @opt_param string userProfileIds Select only change logs with these user
   * profile IDs.
   * @opt_param string ids Select only change logs with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string objectIds Select only change logs with these object IDs.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string action Select only change logs with the specified action.
   * @opt_param string objectType Select only change logs with the specified
   * object type.
   * @return Google_Service_Dfareporting_ChangeLogsListResponse
   */
  public function listChangeLogs($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_ChangeLogsListResponse");
  }
}

/**
 * The "cities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $cities = $dfareportingService->cities;
 *  </code>
 */
class Google_Service_Dfareporting_Cities_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of cities, possibly filtered. (cities.listCities)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string dartIds Select only cities with these DART IDs.
   * @opt_param string namePrefix Select only cities with names starting with this
   * prefix.
   * @opt_param string regionDartIds Select only cities from these regions.
   * @opt_param string countryDartIds Select only cities from these countries.
   * @return Google_Service_Dfareporting_CitiesListResponse
   */
  public function listCities($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CitiesListResponse");
  }
}

/**
 * The "connectionTypes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $connectionTypes = $dfareportingService->connectionTypes;
 *  </code>
 */
class Google_Service_Dfareporting_ConnectionTypes_Resource extends Google_Service_Resource
{

  /**
   * Gets one connection type by ID. (connectionTypes.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Connection type ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ConnectionType
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_ConnectionType");
  }

  /**
   * Retrieves a list of connection types. (connectionTypes.listConnectionTypes)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ConnectionTypesListResponse
   */
  public function listConnectionTypes($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_ConnectionTypesListResponse");
  }
}

/**
 * The "contentCategories" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $contentCategories = $dfareportingService->contentCategories;
 *  </code>
 */
class Google_Service_Dfareporting_ContentCategories_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing content category. (contentCategories.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Content category ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one content category by ID. (contentCategories.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Content category ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ContentCategory
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_ContentCategory");
  }

  /**
   * Inserts a new content category. (contentCategories.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_ContentCategory $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ContentCategory
   */
  public function insert($profileId, Google_Service_Dfareporting_ContentCategory $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_ContentCategory");
  }

  /**
   * Retrieves a list of content categories, possibly filtered.
   * (contentCategories.listContentCategories)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "contentcategory*2015" will return
   * objects with names like "contentcategory June 2015", "contentcategory April
   * 2015", or simply "contentcategory 2015". Most of the searches also add
   * wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "contentcategory" will match objects with name
   * "my contentcategory", "contentcategory 2015", or simply "contentcategory".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only content categories with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_ContentCategoriesListResponse
   */
  public function listContentCategories($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_ContentCategoriesListResponse");
  }

  /**
   * Updates an existing content category. This method supports patch semantics.
   * (contentCategories.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Content category ID.
   * @param Google_ContentCategory $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ContentCategory
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_ContentCategory $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_ContentCategory");
  }

  /**
   * Updates an existing content category. (contentCategories.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_ContentCategory $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_ContentCategory
   */
  public function update($profileId, Google_Service_Dfareporting_ContentCategory $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_ContentCategory");
  }
}

/**
 * The "countries" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $countries = $dfareportingService->countries;
 *  </code>
 */
class Google_Service_Dfareporting_Countries_Resource extends Google_Service_Resource
{

  /**
   * Gets one country by ID. (countries.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $dartId Country DART ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Country
   */
  public function get($profileId, $dartId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'dartId' => $dartId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Country");
  }

  /**
   * Retrieves a list of countries. (countries.listCountries)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CountriesListResponse
   */
  public function listCountries($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CountriesListResponse");
  }
}

/**
 * The "creativeAssets" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $creativeAssets = $dfareportingService->creativeAssets;
 *  </code>
 */
class Google_Service_Dfareporting_CreativeAssets_Resource extends Google_Service_Resource
{

  /**
   * Inserts a new creative asset. (creativeAssets.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $advertiserId Advertiser ID of this creative. This is a
   * required field.
   * @param Google_CreativeAssetMetadata $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeAssetMetadata
   */
  public function insert($profileId, $advertiserId, Google_Service_Dfareporting_CreativeAssetMetadata $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'advertiserId' => $advertiserId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_CreativeAssetMetadata");
  }
}

/**
 * The "creativeFieldValues" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $creativeFieldValues = $dfareportingService->creativeFieldValues;
 *  </code>
 */
class Google_Service_Dfareporting_CreativeFieldValues_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing creative field value. (creativeFieldValues.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param string $id Creative Field Value ID
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $creativeFieldId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one creative field value by ID. (creativeFieldValues.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param string $id Creative Field Value ID
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeFieldValue
   */
  public function get($profileId, $creativeFieldId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_CreativeFieldValue");
  }

  /**
   * Inserts a new creative field value. (creativeFieldValues.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param Google_CreativeFieldValue $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeFieldValue
   */
  public function insert($profileId, $creativeFieldId, Google_Service_Dfareporting_CreativeFieldValue $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_CreativeFieldValue");
  }

  /**
   * Retrieves a list of creative field values, possibly filtered.
   * (creativeFieldValues.listCreativeFieldValues)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for creative field values by
   * their values. Wildcards (e.g. *) are not allowed.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only creative field values with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_CreativeFieldValuesListResponse
   */
  public function listCreativeFieldValues($profileId, $creativeFieldId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CreativeFieldValuesListResponse");
  }

  /**
   * Updates an existing creative field value. This method supports patch
   * semantics. (creativeFieldValues.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param string $id Creative Field Value ID
   * @param Google_CreativeFieldValue $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeFieldValue
   */
  public function patch($profileId, $creativeFieldId, $id, Google_Service_Dfareporting_CreativeFieldValue $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_CreativeFieldValue");
  }

  /**
   * Updates an existing creative field value. (creativeFieldValues.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $creativeFieldId Creative field ID for this creative field
   * value.
   * @param Google_CreativeFieldValue $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeFieldValue
   */
  public function update($profileId, $creativeFieldId, Google_Service_Dfareporting_CreativeFieldValue $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'creativeFieldId' => $creativeFieldId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_CreativeFieldValue");
  }
}

/**
 * The "creativeFields" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $creativeFields = $dfareportingService->creativeFields;
 *  </code>
 */
class Google_Service_Dfareporting_CreativeFields_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing creative field. (creativeFields.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative Field ID
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one creative field by ID. (creativeFields.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative Field ID
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeField
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_CreativeField");
  }

  /**
   * Inserts a new creative field. (creativeFields.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_CreativeField $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeField
   */
  public function insert($profileId, Google_Service_Dfareporting_CreativeField $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_CreativeField");
  }

  /**
   * Retrieves a list of creative fields, possibly filtered.
   * (creativeFields.listCreativeFields)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for creative fields by name
   * or ID. Wildcards (*) are allowed. For example, "creativefield*2015" will
   * return creative fields with names like "creativefield June 2015",
   * "creativefield April 2015", or simply "creativefield 2015". Most of the
   * searches also add wild-cards implicitly at the start and the end of the
   * search string. For example, a search string of "creativefield" will match
   * creative fields with the name "my creativefield", "creativefield 2015", or
   * simply "creativefield".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only creative fields that belong to
   * these advertisers.
   * @opt_param string ids Select only creative fields with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_CreativeFieldsListResponse
   */
  public function listCreativeFields($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CreativeFieldsListResponse");
  }

  /**
   * Updates an existing creative field. This method supports patch semantics.
   * (creativeFields.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative Field ID
   * @param Google_CreativeField $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeField
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_CreativeField $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_CreativeField");
  }

  /**
   * Updates an existing creative field. (creativeFields.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_CreativeField $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeField
   */
  public function update($profileId, Google_Service_Dfareporting_CreativeField $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_CreativeField");
  }
}

/**
 * The "creativeGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $creativeGroups = $dfareportingService->creativeGroups;
 *  </code>
 */
class Google_Service_Dfareporting_CreativeGroups_Resource extends Google_Service_Resource
{

  /**
   * Gets one creative group by ID. (creativeGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_CreativeGroup");
  }

  /**
   * Inserts a new creative group. (creativeGroups.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_CreativeGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeGroup
   */
  public function insert($profileId, Google_Service_Dfareporting_CreativeGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_CreativeGroup");
  }

  /**
   * Retrieves a list of creative groups, possibly filtered.
   * (creativeGroups.listCreativeGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for creative groups by name
   * or ID. Wildcards (*) are allowed. For example, "creativegroup*2015" will
   * return creative groups with names like "creativegroup June 2015",
   * "creativegroup April 2015", or simply "creativegroup 2015". Most of the
   * searches also add wild-cards implicitly at the start and the end of the
   * search string. For example, a search string of "creativegroup" will match
   * creative groups with the name "my creativegroup", "creativegroup 2015", or
   * simply "creativegroup".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only creative groups that belong to
   * these advertisers.
   * @opt_param int groupNumber Select only creative groups that belong to this
   * subgroup.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string ids Select only creative groups with these IDs.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_CreativeGroupsListResponse
   */
  public function listCreativeGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CreativeGroupsListResponse");
  }

  /**
   * Updates an existing creative group. This method supports patch semantics.
   * (creativeGroups.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative group ID.
   * @param Google_CreativeGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeGroup
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_CreativeGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_CreativeGroup");
  }

  /**
   * Updates an existing creative group. (creativeGroups.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_CreativeGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CreativeGroup
   */
  public function update($profileId, Google_Service_Dfareporting_CreativeGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_CreativeGroup");
  }
}

/**
 * The "creatives" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $creatives = $dfareportingService->creatives;
 *  </code>
 */
class Google_Service_Dfareporting_Creatives_Resource extends Google_Service_Resource
{

  /**
   * Gets one creative by ID. (creatives.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Creative
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Creative");
  }

  /**
   * Inserts a new creative. (creatives.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Creative $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Creative
   */
  public function insert($profileId, Google_Service_Dfareporting_Creative $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Creative");
  }

  /**
   * Retrieves a list of creatives, possibly filtered. (creatives.listCreatives)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sizeIds Select only creatives with these size IDs.
   * @opt_param bool archived Select only archived creatives. Leave blank to
   * select archived and unarchived creatives.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "creative*2015" will return objects
   * with names like "creative June 2015", "creative April 2015", or simply
   * "creative 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "creative" will match objects with name "my creative", "creative 2015", or
   * simply "creative".
   * @opt_param string campaignId Select only creatives with this campaign ID.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string renderingIds Select only creatives with these rendering
   * IDs.
   * @opt_param string ids Select only creatives with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string advertiserId Select only creatives with this advertiser ID.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string studioCreativeId Select only creatives corresponding to
   * this Studio creative ID.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string companionCreativeIds Select only in-stream video creatives
   * with these companion IDs.
   * @opt_param bool active Select only active creatives. Leave blank to select
   * active and inactive creatives.
   * @opt_param string creativeFieldIds Select only creatives with these creative
   * field IDs.
   * @opt_param string types Select only creatives with these creative types.
   * @return Google_Service_Dfareporting_CreativesListResponse
   */
  public function listCreatives($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_CreativesListResponse");
  }

  /**
   * Updates an existing creative. This method supports patch semantics.
   * (creatives.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Creative ID.
   * @param Google_Creative $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Creative
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Creative $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Creative");
  }

  /**
   * Updates an existing creative. (creatives.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Creative $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Creative
   */
  public function update($profileId, Google_Service_Dfareporting_Creative $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Creative");
  }
}

/**
 * The "dimensionValues" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $dimensionValues = $dfareportingService->dimensionValues;
 *  </code>
 */
class Google_Service_Dfareporting_DimensionValues_Resource extends Google_Service_Resource
{

  /**
   * Retrieves list of report dimension values for a list of filters.
   * (dimensionValues.query)
   *
   * @param string $profileId The DFA user profile ID.
   * @param Google_DimensionValueRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The value of the nextToken from the previous
   * result page.
   * @opt_param int maxResults Maximum number of results to return.
   * @return Google_Service_Dfareporting_DimensionValueList
   */
  public function query($profileId, Google_Service_Dfareporting_DimensionValueRequest $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('query', array($params), "Google_Service_Dfareporting_DimensionValueList");
  }
}

/**
 * The "directorySiteContacts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $directorySiteContacts = $dfareportingService->directorySiteContacts;
 *  </code>
 */
class Google_Service_Dfareporting_DirectorySiteContacts_Resource extends Google_Service_Resource
{

  /**
   * Gets one directory site contact by ID. (directorySiteContacts.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Directory site contact ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_DirectorySiteContact
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_DirectorySiteContact");
  }

  /**
   * Retrieves a list of directory site contacts, possibly filtered.
   * (directorySiteContacts.listDirectorySiteContacts)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name, ID or
   * email. Wildcards (*) are allowed. For example, "directory site contact*2015"
   * will return objects with names like "directory site contact June 2015",
   * "directory site contact April 2015", or simply "directory site contact 2015".
   * Most of the searches also add wildcards implicitly at the start and the end
   * of the search string. For example, a search string of "directory site
   * contact" will match objects with name "my directory site contact", "directory
   * site contact 2015", or simply "directory site contact".
   * @opt_param string directorySiteIds Select only directory site contacts with
   * these directory site IDs. This is a required field.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only directory site contacts with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_DirectorySiteContactsListResponse
   */
  public function listDirectorySiteContacts($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_DirectorySiteContactsListResponse");
  }
}

/**
 * The "directorySites" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $directorySites = $dfareportingService->directorySites;
 *  </code>
 */
class Google_Service_Dfareporting_DirectorySites_Resource extends Google_Service_Resource
{

  /**
   * Gets one directory site by ID. (directorySites.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Directory site ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_DirectorySite
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_DirectorySite");
  }

  /**
   * Inserts a new directory site. (directorySites.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_DirectorySite $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_DirectorySite
   */
  public function insert($profileId, Google_Service_Dfareporting_DirectorySite $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_DirectorySite");
  }

  /**
   * Retrieves a list of directory sites, possibly filtered.
   * (directorySites.listDirectorySites)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool acceptsInterstitialPlacements This search filter is no longer
   * supported and will have no effect on the results returned.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string searchString Allows searching for objects by name, ID or
   * URL. Wildcards (*) are allowed. For example, "directory site*2015" will
   * return objects with names like "directory site June 2015", "directory site
   * April 2015", or simply "directory site 2015". Most of the searches also add
   * wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "directory site" will match objects with name "my
   * directory site", "directory site 2015" or simply, "directory site".
   * @opt_param string countryId Select only directory sites with this country ID.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param bool acceptsInStreamVideoPlacements This search filter is no
   * longer supported and will have no effect on the results returned.
   * @opt_param string ids Select only directory sites with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param bool acceptsPublisherPaidPlacements Select only directory sites
   * that accept publisher paid placements. This field can be left blank.
   * @opt_param string parentId Select only directory sites with this parent ID.
   * @opt_param bool active Select only active directory sites. Leave blank to
   * retrieve both active and inactive directory sites.
   * @opt_param string dfp_network_code Select only directory sites with this DFP
   * network code.
   * @return Google_Service_Dfareporting_DirectorySitesListResponse
   */
  public function listDirectorySites($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_DirectorySitesListResponse");
  }
}

/**
 * The "eventTags" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $eventTags = $dfareportingService->eventTags;
 *  </code>
 */
class Google_Service_Dfareporting_EventTags_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing event tag. (eventTags.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Event tag ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one event tag by ID. (eventTags.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Event tag ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_EventTag
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_EventTag");
  }

  /**
   * Inserts a new event tag. (eventTags.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_EventTag $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_EventTag
   */
  public function insert($profileId, Google_Service_Dfareporting_EventTag $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_EventTag");
  }

  /**
   * Retrieves a list of event tags, possibly filtered. (eventTags.listEventTags)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "eventtag*2015" will return objects
   * with names like "eventtag June 2015", "eventtag April 2015", or simply
   * "eventtag 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "eventtag" will match objects with name "my eventtag", "eventtag 2015", or
   * simply "eventtag".
   * @opt_param string campaignId Select only event tags that belong to this
   * campaign.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param bool enabled Select only enabled event tags. When definitionsOnly
   * is set to true, only the specified advertiser or campaign's event tags'
   * enabledByDefault field is examined. When definitionsOnly is set to false, the
   * specified ad or specified campaign's parent advertiser's or parent campaign's
   * event tags' enabledByDefault and status fields are examined as well.
   * @opt_param string ids Select only event tags with these IDs.
   * @opt_param string advertiserId Select only event tags that belong to this
   * advertiser.
   * @opt_param string adId Select only event tags that belong to this ad.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string eventTagTypes Select only event tags with the specified
   * event tag types. Event tag types can be used to specify whether to use a
   * third-party pixel, a third-party JavaScript URL, or a third-party click-
   * through URL for either impression or click tracking.
   * @opt_param bool definitionsOnly Examine only the specified ad or campaign or
   * advertiser's event tags for matching selector criteria. When set to false,
   * the parent advertiser and parent campaign is examined as well. In addition,
   * when set to false, the status field is examined as well along with the
   * enabledByDefault field.
   * @return Google_Service_Dfareporting_EventTagsListResponse
   */
  public function listEventTags($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_EventTagsListResponse");
  }

  /**
   * Updates an existing event tag. This method supports patch semantics.
   * (eventTags.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Event tag ID.
   * @param Google_EventTag $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_EventTag
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_EventTag $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_EventTag");
  }

  /**
   * Updates an existing event tag. (eventTags.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_EventTag $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_EventTag
   */
  public function update($profileId, Google_Service_Dfareporting_EventTag $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_EventTag");
  }
}

/**
 * The "files" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $files = $dfareportingService->files;
 *  </code>
 */
class Google_Service_Dfareporting_Files_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a report file by its report ID and file ID. (files.get)
   *
   * @param string $reportId The ID of the report.
   * @param string $fileId The ID of the report file.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_DfareportingFile
   */
  public function get($reportId, $fileId, $optParams = array())
  {
    $params = array('reportId' => $reportId, 'fileId' => $fileId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_DfareportingFile");
  }

  /**
   * Lists files for a user profile. (files.listFiles)
   *
   * @param string $profileId The DFA profile ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sortField The field by which to sort the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken The value of the nextToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is 'DESCENDING'.
   * @opt_param string scope The scope that defines which results are returned,
   * default is 'MINE'.
   * @return Google_Service_Dfareporting_FileList
   */
  public function listFiles($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_FileList");
  }
}

/**
 * The "floodlightActivities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $floodlightActivities = $dfareportingService->floodlightActivities;
 *  </code>
 */
class Google_Service_Dfareporting_FloodlightActivities_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing floodlight activity. (floodlightActivities.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Generates a tag for a floodlight activity. (floodlightActivities.generatetag)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string floodlightActivityId Floodlight activity ID for which we
   * want to generate a tag.
   * @return Google_Service_Dfareporting_FloodlightActivitiesGenerateTagResponse
   */
  public function generatetag($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('generatetag', array($params), "Google_Service_Dfareporting_FloodlightActivitiesGenerateTagResponse");
  }

  /**
   * Gets one floodlight activity by ID. (floodlightActivities.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivity
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_FloodlightActivity");
  }

  /**
   * Inserts a new floodlight activity. (floodlightActivities.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivity
   */
  public function insert($profileId, Google_Service_Dfareporting_FloodlightActivity $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_FloodlightActivity");
  }

  /**
   * Retrieves a list of floodlight activities, possibly filtered.
   * (floodlightActivities.listFloodlightActivities)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string floodlightActivityGroupIds Select only floodlight
   * activities with the specified floodlight activity group IDs.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "floodlightactivity*2015" will return
   * objects with names like "floodlightactivity June 2015", "floodlightactivity
   * April 2015", or simply "floodlightactivity 2015". Most of the searches also
   * add wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "floodlightactivity" will match objects with name
   * "my floodlightactivity activity", "floodlightactivity 2015", or simply
   * "floodlightactivity".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string floodlightConfigurationId Select only floodlight activities
   * for the specified floodlight configuration ID. Must specify either ids,
   * advertiserId, or floodlightConfigurationId for a non-empty result.
   * @opt_param string ids Select only floodlight activities with the specified
   * IDs. Must specify either ids, advertiserId, or floodlightConfigurationId for
   * a non-empty result.
   * @opt_param string floodlightActivityGroupName Select only floodlight
   * activities with the specified floodlight activity group name.
   * @opt_param string advertiserId Select only floodlight activities for the
   * specified advertiser ID. Must specify either ids, advertiserId, or
   * floodlightConfigurationId for a non-empty result.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string tagString Select only floodlight activities with the
   * specified tag string.
   * @opt_param string floodlightActivityGroupTagString Select only floodlight
   * activities with the specified floodlight activity group tag string.
   * @opt_param string floodlightActivityGroupType Select only floodlight
   * activities with the specified floodlight activity group type.
   * @return Google_Service_Dfareporting_FloodlightActivitiesListResponse
   */
  public function listFloodlightActivities($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_FloodlightActivitiesListResponse");
  }

  /**
   * Updates an existing floodlight activity. This method supports patch
   * semantics. (floodlightActivities.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity ID.
   * @param Google_FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivity
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_FloodlightActivity $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_FloodlightActivity");
  }

  /**
   * Updates an existing floodlight activity. (floodlightActivities.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivity
   */
  public function update($profileId, Google_Service_Dfareporting_FloodlightActivity $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_FloodlightActivity");
  }
}

/**
 * The "floodlightActivityGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $floodlightActivityGroups = $dfareportingService->floodlightActivityGroups;
 *  </code>
 */
class Google_Service_Dfareporting_FloodlightActivityGroups_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing floodlight activity group.
   * (floodlightActivityGroups.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity Group ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one floodlight activity group by ID. (floodlightActivityGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity Group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivityGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_FloodlightActivityGroup");
  }

  /**
   * Inserts a new floodlight activity group. (floodlightActivityGroups.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_FloodlightActivityGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivityGroup
   */
  public function insert($profileId, Google_Service_Dfareporting_FloodlightActivityGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_FloodlightActivityGroup");
  }

  /**
   * Retrieves a list of floodlight activity groups, possibly filtered.
   * (floodlightActivityGroups.listFloodlightActivityGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "floodlightactivitygroup*2015" will
   * return objects with names like "floodlightactivitygroup June 2015",
   * "floodlightactivitygroup April 2015", or simply "floodlightactivitygroup
   * 2015". Most of the searches also add wildcards implicitly at the start and
   * the end of the search string. For example, a search string of
   * "floodlightactivitygroup" will match objects with name "my
   * floodlightactivitygroup activity", "floodlightactivitygroup 2015", or simply
   * "floodlightactivitygroup".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string floodlightConfigurationId Select only floodlight activity
   * groups with the specified floodlight configuration ID. Must specify either
   * advertiserId, or floodlightConfigurationId for a non-empty result.
   * @opt_param string ids Select only floodlight activity groups with the
   * specified IDs. Must specify either advertiserId or floodlightConfigurationId
   * for a non-empty result.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string advertiserId Select only floodlight activity groups with
   * the specified advertiser ID. Must specify either advertiserId or
   * floodlightConfigurationId for a non-empty result.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string type Select only floodlight activity groups with the
   * specified floodlight activity group type.
   * @return Google_Service_Dfareporting_FloodlightActivityGroupsListResponse
   */
  public function listFloodlightActivityGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_FloodlightActivityGroupsListResponse");
  }

  /**
   * Updates an existing floodlight activity group. This method supports patch
   * semantics. (floodlightActivityGroups.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity Group ID.
   * @param Google_FloodlightActivityGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivityGroup
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_FloodlightActivityGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_FloodlightActivityGroup");
  }

  /**
   * Updates an existing floodlight activity group.
   * (floodlightActivityGroups.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_FloodlightActivityGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightActivityGroup
   */
  public function update($profileId, Google_Service_Dfareporting_FloodlightActivityGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_FloodlightActivityGroup");
  }
}

/**
 * The "floodlightConfigurations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $floodlightConfigurations = $dfareportingService->floodlightConfigurations;
 *  </code>
 */
class Google_Service_Dfareporting_FloodlightConfigurations_Resource extends Google_Service_Resource
{

  /**
   * Gets one floodlight configuration by ID. (floodlightConfigurations.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight configuration ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightConfiguration
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_FloodlightConfiguration");
  }

  /**
   * Retrieves a list of floodlight configurations, possibly filtered.
   * (floodlightConfigurations.listFloodlightConfigurations)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ids Set of IDs of floodlight configurations to retrieve.
   * Required field; otherwise an empty list will be returned.
   * @return Google_Service_Dfareporting_FloodlightConfigurationsListResponse
   */
  public function listFloodlightConfigurations($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_FloodlightConfigurationsListResponse");
  }

  /**
   * Updates an existing floodlight configuration. This method supports patch
   * semantics. (floodlightConfigurations.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight configuration ID.
   * @param Google_FloodlightConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightConfiguration
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_FloodlightConfiguration $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_FloodlightConfiguration");
  }

  /**
   * Updates an existing floodlight configuration.
   * (floodlightConfigurations.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_FloodlightConfiguration $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_FloodlightConfiguration
   */
  public function update($profileId, Google_Service_Dfareporting_FloodlightConfiguration $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_FloodlightConfiguration");
  }
}

/**
 * The "inventoryItems" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $inventoryItems = $dfareportingService->inventoryItems;
 *  </code>
 */
class Google_Service_Dfareporting_InventoryItems_Resource extends Google_Service_Resource
{

  /**
   * Gets one inventory item by ID. (inventoryItems.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for order documents.
   * @param string $id Inventory item ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_InventoryItem
   */
  public function get($profileId, $projectId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_InventoryItem");
  }

  /**
   * Retrieves a list of inventory items, possibly filtered.
   * (inventoryItems.listInventoryItems)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for order documents.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderId Select only inventory items that belong to
   * specified orders.
   * @opt_param string ids Select only inventory items with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string siteId Select only inventory items that are associated with
   * these sites.
   * @opt_param bool inPlan Select only inventory items that are in plan.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_InventoryItemsListResponse
   */
  public function listInventoryItems($profileId, $projectId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_InventoryItemsListResponse");
  }
}

/**
 * The "landingPages" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $landingPages = $dfareportingService->landingPages;
 *  </code>
 */
class Google_Service_Dfareporting_LandingPages_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing campaign landing page. (landingPages.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param string $id Landing page ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $campaignId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one campaign landing page by ID. (landingPages.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param string $id Landing page ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_LandingPage
   */
  public function get($profileId, $campaignId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_LandingPage");
  }

  /**
   * Inserts a new landing page for the specified campaign. (landingPages.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param Google_LandingPage $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_LandingPage
   */
  public function insert($profileId, $campaignId, Google_Service_Dfareporting_LandingPage $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_LandingPage");
  }

  /**
   * Retrieves the list of landing pages for the specified campaign.
   * (landingPages.listLandingPages)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_LandingPagesListResponse
   */
  public function listLandingPages($profileId, $campaignId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_LandingPagesListResponse");
  }

  /**
   * Updates an existing campaign landing page. This method supports patch
   * semantics. (landingPages.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param string $id Landing page ID.
   * @param Google_LandingPage $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_LandingPage
   */
  public function patch($profileId, $campaignId, $id, Google_Service_Dfareporting_LandingPage $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_LandingPage");
  }

  /**
   * Updates an existing campaign landing page. (landingPages.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $campaignId Landing page campaign ID.
   * @param Google_LandingPage $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_LandingPage
   */
  public function update($profileId, $campaignId, Google_Service_Dfareporting_LandingPage $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'campaignId' => $campaignId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_LandingPage");
  }
}

/**
 * The "metros" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $metros = $dfareportingService->metros;
 *  </code>
 */
class Google_Service_Dfareporting_Metros_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of metros. (metros.listMetros)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_MetrosListResponse
   */
  public function listMetros($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_MetrosListResponse");
  }
}

/**
 * The "mobileCarriers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $mobileCarriers = $dfareportingService->mobileCarriers;
 *  </code>
 */
class Google_Service_Dfareporting_MobileCarriers_Resource extends Google_Service_Resource
{

  /**
   * Gets one mobile carrier by ID. (mobileCarriers.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Mobile carrier ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_MobileCarrier
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_MobileCarrier");
  }

  /**
   * Retrieves a list of mobile carriers. (mobileCarriers.listMobileCarriers)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_MobileCarriersListResponse
   */
  public function listMobileCarriers($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_MobileCarriersListResponse");
  }
}

/**
 * The "operatingSystemVersions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $operatingSystemVersions = $dfareportingService->operatingSystemVersions;
 *  </code>
 */
class Google_Service_Dfareporting_OperatingSystemVersions_Resource extends Google_Service_Resource
{

  /**
   * Gets one operating system version by ID. (operatingSystemVersions.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Operating system version ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_OperatingSystemVersion
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_OperatingSystemVersion");
  }

  /**
   * Retrieves a list of operating system versions.
   * (operatingSystemVersions.listOperatingSystemVersions)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_OperatingSystemVersionsListResponse
   */
  public function listOperatingSystemVersions($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_OperatingSystemVersionsListResponse");
  }
}

/**
 * The "operatingSystems" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $operatingSystems = $dfareportingService->operatingSystems;
 *  </code>
 */
class Google_Service_Dfareporting_OperatingSystems_Resource extends Google_Service_Resource
{

  /**
   * Gets one operating system by DART ID. (operatingSystems.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $dartId Operating system DART ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_OperatingSystem
   */
  public function get($profileId, $dartId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'dartId' => $dartId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_OperatingSystem");
  }

  /**
   * Retrieves a list of operating systems.
   * (operatingSystems.listOperatingSystems)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_OperatingSystemsListResponse
   */
  public function listOperatingSystems($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_OperatingSystemsListResponse");
  }
}

/**
 * The "orderDocuments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $orderDocuments = $dfareportingService->orderDocuments;
 *  </code>
 */
class Google_Service_Dfareporting_OrderDocuments_Resource extends Google_Service_Resource
{

  /**
   * Gets one order document by ID. (orderDocuments.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for order documents.
   * @param string $id Order document ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_OrderDocument
   */
  public function get($profileId, $projectId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_OrderDocument");
  }

  /**
   * Retrieves a list of order documents, possibly filtered.
   * (orderDocuments.listOrderDocuments)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for order documents.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderId Select only order documents for specified orders.
   * @opt_param string searchString Allows searching for order documents by name
   * or ID. Wildcards (*) are allowed. For example, "orderdocument*2015" will
   * return order documents with names like "orderdocument June 2015",
   * "orderdocument April 2015", or simply "orderdocument 2015". Most of the
   * searches also add wildcards implicitly at the start and the end of the search
   * string. For example, a search string of "orderdocument" will match order
   * documents with name "my orderdocument", "orderdocument 2015", or simply
   * "orderdocument".
   * @opt_param string ids Select only order documents with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string siteId Select only order documents that are associated with
   * these sites.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param bool approved Select only order documents that have been approved
   * by at least one user.
   * @return Google_Service_Dfareporting_OrderDocumentsListResponse
   */
  public function listOrderDocuments($profileId, $projectId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_OrderDocumentsListResponse");
  }
}

/**
 * The "orders" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $orders = $dfareportingService->orders;
 *  </code>
 */
class Google_Service_Dfareporting_Orders_Resource extends Google_Service_Resource
{

  /**
   * Gets one order by ID. (orders.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for orders.
   * @param string $id Order ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Order
   */
  public function get($profileId, $projectId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Order");
  }

  /**
   * Retrieves a list of orders, possibly filtered. (orders.listOrders)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $projectId Project ID for orders.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for orders by name or ID.
   * Wildcards (*) are allowed. For example, "order*2015" will return orders with
   * names like "order June 2015", "order April 2015", or simply "order 2015".
   * Most of the searches also add wildcards implicitly at the start and the end
   * of the search string. For example, a search string of "order" will match
   * orders with name "my order", "order 2015", or simply "order".
   * @opt_param string ids Select only orders with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string siteId Select only orders that are associated with these
   * site IDs.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string sortField Field by which to sort the list.
   * @return Google_Service_Dfareporting_OrdersListResponse
   */
  public function listOrders($profileId, $projectId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'projectId' => $projectId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_OrdersListResponse");
  }
}

/**
 * The "placementGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $placementGroups = $dfareportingService->placementGroups;
 *  </code>
 */
class Google_Service_Dfareporting_PlacementGroups_Resource extends Google_Service_Resource
{

  /**
   * Gets one placement group by ID. (placementGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_PlacementGroup");
  }

  /**
   * Inserts a new placement group. (placementGroups.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_PlacementGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementGroup
   */
  public function insert($profileId, Google_Service_Dfareporting_PlacementGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_PlacementGroup");
  }

  /**
   * Retrieves a list of placement groups, possibly filtered.
   * (placementGroups.listPlacementGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string placementStrategyIds Select only placement groups that are
   * associated with these placement strategies.
   * @opt_param bool archived Select only archived placements. Don't set this
   * field to select both archived and non-archived placements.
   * @opt_param string searchString Allows searching for placement groups by name
   * or ID. Wildcards (*) are allowed. For example, "placement*2015" will return
   * placement groups with names like "placement group June 2015", "placement
   * group May 2015", or simply "placements 2015". Most of the searches also add
   * wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "placementgroup" will match placement groups with
   * name "my placementgroup", "placementgroup 2015", or simply "placementgroup".
   * @opt_param string contentCategoryIds Select only placement groups that are
   * associated with these content categories.
   * @opt_param string directorySiteIds Select only placement groups that are
   * associated with these directory sites.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only placement groups that belong to
   * these advertisers.
   * @opt_param string ids Select only placement groups with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string placementGroupType Select only placement groups belonging
   * with this group type. A package is a simple group of placements that acts as
   * a single pricing point for a group of tags. A roadblock is a group of
   * placements that not only acts as a single pricing point but also assumes that
   * all the tags in it will be served at the same time. A roadblock requires one
   * of its assigned placements to be marked as primary for reporting.
   * @opt_param string pricingTypes Select only placement groups with these
   * pricing types.
   * @opt_param string siteIds Select only placement groups that are associated
   * with these sites.
   * @opt_param string campaignIds Select only placement groups that belong to
   * these campaigns.
   * @return Google_Service_Dfareporting_PlacementGroupsListResponse
   */
  public function listPlacementGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_PlacementGroupsListResponse");
  }

  /**
   * Updates an existing placement group. This method supports patch semantics.
   * (placementGroups.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement group ID.
   * @param Google_PlacementGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementGroup
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_PlacementGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_PlacementGroup");
  }

  /**
   * Updates an existing placement group. (placementGroups.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_PlacementGroup $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementGroup
   */
  public function update($profileId, Google_Service_Dfareporting_PlacementGroup $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_PlacementGroup");
  }
}

/**
 * The "placementStrategies" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $placementStrategies = $dfareportingService->placementStrategies;
 *  </code>
 */
class Google_Service_Dfareporting_PlacementStrategies_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing placement strategy. (placementStrategies.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement strategy ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one placement strategy by ID. (placementStrategies.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement strategy ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementStrategy
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_PlacementStrategy");
  }

  /**
   * Inserts a new placement strategy. (placementStrategies.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_PlacementStrategy $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementStrategy
   */
  public function insert($profileId, Google_Service_Dfareporting_PlacementStrategy $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_PlacementStrategy");
  }

  /**
   * Retrieves a list of placement strategies, possibly filtered.
   * (placementStrategies.listPlacementStrategies)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "placementstrategy*2015" will return
   * objects with names like "placementstrategy June 2015", "placementstrategy
   * April 2015", or simply "placementstrategy 2015". Most of the searches also
   * add wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "placementstrategy" will match objects with name
   * "my placementstrategy", "placementstrategy 2015", or simply
   * "placementstrategy".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only placement strategies with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_PlacementStrategiesListResponse
   */
  public function listPlacementStrategies($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_PlacementStrategiesListResponse");
  }

  /**
   * Updates an existing placement strategy. This method supports patch semantics.
   * (placementStrategies.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement strategy ID.
   * @param Google_PlacementStrategy $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementStrategy
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_PlacementStrategy $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_PlacementStrategy");
  }

  /**
   * Updates an existing placement strategy. (placementStrategies.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_PlacementStrategy $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlacementStrategy
   */
  public function update($profileId, Google_Service_Dfareporting_PlacementStrategy $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_PlacementStrategy");
  }
}

/**
 * The "placements" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $placements = $dfareportingService->placements;
 *  </code>
 */
class Google_Service_Dfareporting_Placements_Resource extends Google_Service_Resource
{

  /**
   * Generates tags for a placement. (placements.generatetags)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string tagFormats Tag formats to generate for these placements.
   * @opt_param string placementIds Generate tags for these placements.
   * @opt_param string campaignId Generate placements belonging to this campaign.
   * This is a required field.
   * @return Google_Service_Dfareporting_PlacementsGenerateTagsResponse
   */
  public function generatetags($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('generatetags', array($params), "Google_Service_Dfareporting_PlacementsGenerateTagsResponse");
  }

  /**
   * Gets one placement by ID. (placements.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Placement
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Placement");
  }

  /**
   * Inserts a new placement. (placements.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Placement $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Placement
   */
  public function insert($profileId, Google_Service_Dfareporting_Placement $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Placement");
  }

  /**
   * Retrieves a list of placements, possibly filtered.
   * (placements.listPlacements)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string placementStrategyIds Select only placements that are
   * associated with these placement strategies.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool archived Select only archived placements. Don't set this
   * field to select both archived and non-archived placements.
   * @opt_param string searchString Allows searching for placements by name or ID.
   * Wildcards (*) are allowed. For example, "placement*2015" will return
   * placements with names like "placement June 2015", "placement May 2015", or
   * simply "placements 2015". Most of the searches also add wildcards implicitly
   * at the start and the end of the search string. For example, a search string
   * of "placement" will match placements with name "my placement", "placement
   * 2015", or simply "placement".
   * @opt_param string contentCategoryIds Select only placements that are
   * associated with these content categories.
   * @opt_param string directorySiteIds Select only placements that are associated
   * with these directory sites.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only placements that belong to these
   * advertisers.
   * @opt_param string paymentSource Select only placements with this payment
   * source.
   * @opt_param string ids Select only placements with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string sizeIds Select only placements that are associated with
   * these sizes.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string compatibilities Select only placements that are associated
   * with these compatibilities. WEB and WEB_INTERSTITIAL refer to rendering
   * either on desktop or on mobile devices for regular or interstitial ads
   * respectively. APP and APP_INTERSTITIAL are for rendering in mobile
   * apps.IN_STREAM_VIDEO refers to rendering in in-stream video ads developed
   * with the VAST standard.
   * @opt_param string groupIds Select only placements that belong to these
   * placement groups.
   * @opt_param string pricingTypes Select only placements with these pricing
   * types.
   * @opt_param string siteIds Select only placements that are associated with
   * these sites.
   * @opt_param string campaignIds Select only placements that belong to these
   * campaigns.
   * @return Google_Service_Dfareporting_PlacementsListResponse
   */
  public function listPlacements($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_PlacementsListResponse");
  }

  /**
   * Updates an existing placement. This method supports patch semantics.
   * (placements.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Placement ID.
   * @param Google_Placement $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Placement
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Placement $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Placement");
  }

  /**
   * Updates an existing placement. (placements.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Placement $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Placement
   */
  public function update($profileId, Google_Service_Dfareporting_Placement $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Placement");
  }
}

/**
 * The "platformTypes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $platformTypes = $dfareportingService->platformTypes;
 *  </code>
 */
class Google_Service_Dfareporting_PlatformTypes_Resource extends Google_Service_Resource
{

  /**
   * Gets one platform type by ID. (platformTypes.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Platform type ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlatformType
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_PlatformType");
  }

  /**
   * Retrieves a list of platform types. (platformTypes.listPlatformTypes)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PlatformTypesListResponse
   */
  public function listPlatformTypes($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_PlatformTypesListResponse");
  }
}

/**
 * The "postalCodes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $postalCodes = $dfareportingService->postalCodes;
 *  </code>
 */
class Google_Service_Dfareporting_PostalCodes_Resource extends Google_Service_Resource
{

  /**
   * Gets one postal code by ID. (postalCodes.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $code Postal code ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PostalCode
   */
  public function get($profileId, $code, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'code' => $code);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_PostalCode");
  }

  /**
   * Retrieves a list of postal codes. (postalCodes.listPostalCodes)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_PostalCodesListResponse
   */
  public function listPostalCodes($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_PostalCodesListResponse");
  }
}

/**
 * The "projects" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $projects = $dfareportingService->projects;
 *  </code>
 */
class Google_Service_Dfareporting_Projects_Resource extends Google_Service_Resource
{

  /**
   * Gets one project by ID. (projects.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Project ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Project
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Project");
  }

  /**
   * Retrieves a list of projects, possibly filtered. (projects.listProjects)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for projects by name or ID.
   * Wildcards (*) are allowed. For example, "project*2015" will return projects
   * with names like "project June 2015", "project April 2015", or simply "project
   * 2015". Most of the searches also add wildcards implicitly at the start and
   * the end of the search string. For example, a search string of "project" will
   * match projects with name "my project", "project 2015", or simply "project".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string advertiserIds Select only projects with these advertiser
   * IDs.
   * @opt_param string ids Select only projects with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_ProjectsListResponse
   */
  public function listProjects($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_ProjectsListResponse");
  }
}

/**
 * The "regions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $regions = $dfareportingService->regions;
 *  </code>
 */
class Google_Service_Dfareporting_Regions_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of regions. (regions.listRegions)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RegionsListResponse
   */
  public function listRegions($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_RegionsListResponse");
  }
}

/**
 * The "remarketingListShares" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $remarketingListShares = $dfareportingService->remarketingListShares;
 *  </code>
 */
class Google_Service_Dfareporting_RemarketingListShares_Resource extends Google_Service_Resource
{

  /**
   * Gets one remarketing list share by remarketing list ID.
   * (remarketingListShares.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $remarketingListId Remarketing list ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingListShare
   */
  public function get($profileId, $remarketingListId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'remarketingListId' => $remarketingListId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_RemarketingListShare");
  }

  /**
   * Updates an existing remarketing list share. This method supports patch
   * semantics. (remarketingListShares.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $remarketingListId Remarketing list ID.
   * @param Google_RemarketingListShare $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingListShare
   */
  public function patch($profileId, $remarketingListId, Google_Service_Dfareporting_RemarketingListShare $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'remarketingListId' => $remarketingListId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_RemarketingListShare");
  }

  /**
   * Updates an existing remarketing list share. (remarketingListShares.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_RemarketingListShare $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingListShare
   */
  public function update($profileId, Google_Service_Dfareporting_RemarketingListShare $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_RemarketingListShare");
  }
}

/**
 * The "remarketingLists" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $remarketingLists = $dfareportingService->remarketingLists;
 *  </code>
 */
class Google_Service_Dfareporting_RemarketingLists_Resource extends Google_Service_Resource
{

  /**
   * Gets one remarketing list by ID. (remarketingLists.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Remarketing list ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingList
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_RemarketingList");
  }

  /**
   * Inserts a new remarketing list. (remarketingLists.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_RemarketingList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingList
   */
  public function insert($profileId, Google_Service_Dfareporting_RemarketingList $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_RemarketingList");
  }

  /**
   * Retrieves a list of remarketing lists, possibly filtered.
   * (remarketingLists.listRemarketingLists)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $advertiserId Select only remarketing lists owned by this
   * advertiser.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string name Allows searching for objects by name or ID. Wildcards
   * (*) are allowed. For example, "remarketing list*2015" will return objects
   * with names like "remarketing list June 2015", "remarketing list April 2015",
   * or simply "remarketing list 2015". Most of the searches also add wildcards
   * implicitly at the start and the end of the search string. For example, a
   * search string of "remarketing list" will match objects with name "my
   * remarketing list", "remarketing list 2015", or simply "remarketing list".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool active Select only active or only inactive remarketing lists.
   * @opt_param string floodlightActivityId Select only remarketing lists that
   * have this floodlight activity ID.
   * @return Google_Service_Dfareporting_RemarketingListsListResponse
   */
  public function listRemarketingLists($profileId, $advertiserId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'advertiserId' => $advertiserId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_RemarketingListsListResponse");
  }

  /**
   * Updates an existing remarketing list. This method supports patch semantics.
   * (remarketingLists.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Remarketing list ID.
   * @param Google_RemarketingList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingList
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_RemarketingList $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_RemarketingList");
  }

  /**
   * Updates an existing remarketing list. (remarketingLists.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_RemarketingList $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_RemarketingList
   */
  public function update($profileId, Google_Service_Dfareporting_RemarketingList $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_RemarketingList");
  }
}

/**
 * The "reports" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $reports = $dfareportingService->reports;
 *  </code>
 */
class Google_Service_Dfareporting_Reports_Resource extends Google_Service_Resource
{

  /**
   * Deletes a report by its ID. (reports.delete)
   *
   * @param string $profileId The DFA user profile ID.
   * @param string $reportId The ID of the report.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $reportId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves a report by its ID. (reports.get)
   *
   * @param string $profileId The DFA user profile ID.
   * @param string $reportId The ID of the report.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Report
   */
  public function get($profileId, $reportId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Report");
  }

  /**
   * Creates a report. (reports.insert)
   *
   * @param string $profileId The DFA user profile ID.
   * @param Google_Report $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Report
   */
  public function insert($profileId, Google_Service_Dfareporting_Report $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Report");
  }

  /**
   * Retrieves list of reports. (reports.listReports)
   *
   * @param string $profileId The DFA user profile ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sortField The field by which to sort the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken The value of the nextToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is 'DESCENDING'.
   * @opt_param string scope The scope that defines which results are returned,
   * default is 'MINE'.
   * @return Google_Service_Dfareporting_ReportList
   */
  public function listReports($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_ReportList");
  }

  /**
   * Updates a report. This method supports patch semantics. (reports.patch)
   *
   * @param string $profileId The DFA user profile ID.
   * @param string $reportId The ID of the report.
   * @param Google_Report $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Report
   */
  public function patch($profileId, $reportId, Google_Service_Dfareporting_Report $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Report");
  }

  /**
   * Runs a report. (reports.run)
   *
   * @param string $profileId The DFA profile ID.
   * @param string $reportId The ID of the report.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool synchronous If set and true, tries to run the report
   * synchronously.
   * @return Google_Service_Dfareporting_DfareportingFile
   */
  public function run($profileId, $reportId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId);
    $params = array_merge($params, $optParams);
    return $this->call('run', array($params), "Google_Service_Dfareporting_DfareportingFile");
  }

  /**
   * Updates a report. (reports.update)
   *
   * @param string $profileId The DFA user profile ID.
   * @param string $reportId The ID of the report.
   * @param Google_Report $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Report
   */
  public function update($profileId, $reportId, Google_Service_Dfareporting_Report $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Report");
  }
}

/**
 * The "compatibleFields" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $compatibleFields = $dfareportingService->compatibleFields;
 *  </code>
 */
class Google_Service_Dfareporting_ReportsCompatibleFields_Resource extends Google_Service_Resource
{

  /**
   * Returns the fields that are compatible to be selected in the respective
   * sections of a report criteria, given the fields already selected in the input
   * report and user permissions. (compatibleFields.query)
   *
   * @param string $profileId The DFA user profile ID.
   * @param Google_Report $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_CompatibleFields
   */
  public function query($profileId, Google_Service_Dfareporting_Report $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('query', array($params), "Google_Service_Dfareporting_CompatibleFields");
  }
}
/**
 * The "files" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $files = $dfareportingService->files;
 *  </code>
 */
class Google_Service_Dfareporting_ReportsFiles_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a report file. (files.get)
   *
   * @param string $profileId The DFA profile ID.
   * @param string $reportId The ID of the report.
   * @param string $fileId The ID of the report file.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_DfareportingFile
   */
  public function get($profileId, $reportId, $fileId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId, 'fileId' => $fileId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_DfareportingFile");
  }

  /**
   * Lists files for a report. (files.listReportsFiles)
   *
   * @param string $profileId The DFA profile ID.
   * @param string $reportId The ID of the parent report.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sortField The field by which to sort the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken The value of the nextToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is 'DESCENDING'.
   * @return Google_Service_Dfareporting_FileList
   */
  public function listReportsFiles($profileId, $reportId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'reportId' => $reportId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_FileList");
  }
}

/**
 * The "sites" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $sites = $dfareportingService->sites;
 *  </code>
 */
class Google_Service_Dfareporting_Sites_Resource extends Google_Service_Resource
{

  /**
   * Gets one site by ID. (sites.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Site ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Site
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Site");
  }

  /**
   * Inserts a new site. (sites.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Site $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Site
   */
  public function insert($profileId, Google_Service_Dfareporting_Site $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Site");
  }

  /**
   * Retrieves a list of sites, possibly filtered. (sites.listSites)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool acceptsInterstitialPlacements This search filter is no longer
   * supported and will have no effect on the results returned.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param string searchString Allows searching for objects by name, ID or
   * keyName. Wildcards (*) are allowed. For example, "site*2015" will return
   * objects with names like "site June 2015", "site April 2015", or simply "site
   * 2015". Most of the searches also add wildcards implicitly at the start and
   * the end of the search string. For example, a search string of "site" will
   * match objects with name "my site", "site 2015", or simply "site".
   * @opt_param string subaccountId Select only sites with this subaccount ID.
   * @opt_param string directorySiteIds Select only sites with these directory
   * site IDs.
   * @opt_param bool acceptsInStreamVideoPlacements This search filter is no
   * longer supported and will have no effect on the results returned.
   * @opt_param string ids Select only sites with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param bool acceptsPublisherPaidPlacements Select only sites that accept
   * publisher paid placements.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param bool adWordsSite Select only AdWords sites.
   * @opt_param bool unmappedSite Select only sites that have not been mapped to a
   * directory site.
   * @opt_param bool approved Select only approved sites.
   * @opt_param string campaignIds Select only sites with these campaign IDs.
   * @return Google_Service_Dfareporting_SitesListResponse
   */
  public function listSites($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_SitesListResponse");
  }

  /**
   * Updates an existing site. This method supports patch semantics. (sites.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Site ID.
   * @param Google_Site $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Site
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Site $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Site");
  }

  /**
   * Updates an existing site. (sites.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Site $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Site
   */
  public function update($profileId, Google_Service_Dfareporting_Site $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Site");
  }
}

/**
 * The "sizes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $sizes = $dfareportingService->sizes;
 *  </code>
 */
class Google_Service_Dfareporting_Sizes_Resource extends Google_Service_Resource
{

  /**
   * Gets one size by ID. (sizes.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Size ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Size
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Size");
  }

  /**
   * Inserts a new size. (sizes.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Size $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Size
   */
  public function insert($profileId, Google_Service_Dfareporting_Size $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Size");
  }

  /**
   * Retrieves a list of sizes, possibly filtered. (sizes.listSizes)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool iabStandard Select only IAB standard sizes.
   * @opt_param int width Select only sizes with this width.
   * @opt_param string ids Select only sizes with these IDs.
   * @opt_param int height Select only sizes with this height.
   * @return Google_Service_Dfareporting_SizesListResponse
   */
  public function listSizes($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_SizesListResponse");
  }
}

/**
 * The "subaccounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $subaccounts = $dfareportingService->subaccounts;
 *  </code>
 */
class Google_Service_Dfareporting_Subaccounts_Resource extends Google_Service_Resource
{

  /**
   * Gets one subaccount by ID. (subaccounts.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Subaccount ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Subaccount
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_Subaccount");
  }

  /**
   * Inserts a new subaccount. (subaccounts.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Subaccount $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Subaccount
   */
  public function insert($profileId, Google_Service_Dfareporting_Subaccount $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_Subaccount");
  }

  /**
   * Gets a list of subaccounts, possibly filtered. (subaccounts.listSubaccounts)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "subaccount*2015" will return objects
   * with names like "subaccount June 2015", "subaccount April 2015", or simply
   * "subaccount 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "subaccount" will match objects with name "my subaccount", "subaccount 2015",
   * or simply "subaccount".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only subaccounts with these IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @return Google_Service_Dfareporting_SubaccountsListResponse
   */
  public function listSubaccounts($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_SubaccountsListResponse");
  }

  /**
   * Updates an existing subaccount. This method supports patch semantics.
   * (subaccounts.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Subaccount ID.
   * @param Google_Subaccount $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Subaccount
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_Subaccount $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_Subaccount");
  }

  /**
   * Updates an existing subaccount. (subaccounts.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_Subaccount $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_Subaccount
   */
  public function update($profileId, Google_Service_Dfareporting_Subaccount $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_Subaccount");
  }
}

/**
 * The "targetableRemarketingLists" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $targetableRemarketingLists = $dfareportingService->targetableRemarketingLists;
 *  </code>
 */
class Google_Service_Dfareporting_TargetableRemarketingLists_Resource extends Google_Service_Resource
{

  /**
   * Gets one remarketing list by ID. (targetableRemarketingLists.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Remarketing list ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_TargetableRemarketingList
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_TargetableRemarketingList");
  }

  /**
   * Retrieves a list of targetable remarketing lists, possibly filtered.
   * (targetableRemarketingLists.listTargetableRemarketingLists)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $advertiserId Select only targetable remarketing lists
   * targetable by these advertisers.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string name Allows searching for objects by name or ID. Wildcards
   * (*) are allowed. For example, "remarketing list*2015" will return objects
   * with names like "remarketing list June 2015", "remarketing list April 2015",
   * or simply "remarketing list 2015". Most of the searches also add wildcards
   * implicitly at the start and the end of the search string. For example, a
   * search string of "remarketing list" will match objects with name "my
   * remarketing list", "remarketing list 2015", or simply "remarketing list".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool active Select only active or only inactive targetable
   * remarketing lists.
   * @return Google_Service_Dfareporting_TargetableRemarketingListsListResponse
   */
  public function listTargetableRemarketingLists($profileId, $advertiserId, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'advertiserId' => $advertiserId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_TargetableRemarketingListsListResponse");
  }
}

/**
 * The "userProfiles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $userProfiles = $dfareportingService->userProfiles;
 *  </code>
 */
class Google_Service_Dfareporting_UserProfiles_Resource extends Google_Service_Resource
{

  /**
   * Gets one user profile by ID. (userProfiles.get)
   *
   * @param string $profileId The user profile ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserProfile
   */
  public function get($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_UserProfile");
  }

  /**
   * Retrieves list of user profiles for a user. (userProfiles.listUserProfiles)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserProfileList
   */
  public function listUserProfiles($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_UserProfileList");
  }
}

/**
 * The "userRolePermissionGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $userRolePermissionGroups = $dfareportingService->userRolePermissionGroups;
 *  </code>
 */
class Google_Service_Dfareporting_UserRolePermissionGroups_Resource extends Google_Service_Resource
{

  /**
   * Gets one user role permission group by ID. (userRolePermissionGroups.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User role permission group ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRolePermissionGroup
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_UserRolePermissionGroup");
  }

  /**
   * Gets a list of all supported user role permission groups.
   * (userRolePermissionGroups.listUserRolePermissionGroups)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRolePermissionGroupsListResponse
   */
  public function listUserRolePermissionGroups($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_UserRolePermissionGroupsListResponse");
  }
}

/**
 * The "userRolePermissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $userRolePermissions = $dfareportingService->userRolePermissions;
 *  </code>
 */
class Google_Service_Dfareporting_UserRolePermissions_Resource extends Google_Service_Resource
{

  /**
   * Gets one user role permission by ID. (userRolePermissions.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User role permission ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRolePermission
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_UserRolePermission");
  }

  /**
   * Gets a list of user role permissions, possibly filtered.
   * (userRolePermissions.listUserRolePermissions)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ids Select only user role permissions with these IDs.
   * @return Google_Service_Dfareporting_UserRolePermissionsListResponse
   */
  public function listUserRolePermissions($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_UserRolePermissionsListResponse");
  }
}

/**
 * The "userRoles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google_Service_Dfareporting(...);
 *   $userRoles = $dfareportingService->userRoles;
 *  </code>
 */
class Google_Service_Dfareporting_UserRoles_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing user role. (userRoles.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User role ID.
   * @param array $optParams Optional parameters.
   */
  public function delete($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets one user role by ID. (userRoles.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User role ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRole
   */
  public function get($profileId, $id, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Dfareporting_UserRole");
  }

  /**
   * Inserts a new user role. (userRoles.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_UserRole $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRole
   */
  public function insert($profileId, Google_Service_Dfareporting_UserRole $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Dfareporting_UserRole");
  }

  /**
   * Retrieves a list of user roles, possibly filtered. (userRoles.listUserRoles)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "userrole*2015" will return objects
   * with names like "userrole June 2015", "userrole April 2015", or simply
   * "userrole 2015". Most of the searches also add wildcards implicitly at the
   * start and the end of the search string. For example, a search string of
   * "userrole" will match objects with name "my userrole", "userrole 2015", or
   * simply "userrole".
   * @opt_param string subaccountId Select only user roles that belong to this
   * subaccount.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string ids Select only user roles with the specified IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string sortOrder Order of sorted results, default is ASCENDING.
   * @opt_param bool accountUserRoleOnly Select only account level user roles not
   * associated with any specific subaccount.
   * @return Google_Service_Dfareporting_UserRolesListResponse
   */
  public function listUserRoles($profileId, $optParams = array())
  {
    $params = array('profileId' => $profileId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Dfareporting_UserRolesListResponse");
  }

  /**
   * Updates an existing user role. This method supports patch semantics.
   * (userRoles.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id User role ID.
   * @param Google_UserRole $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRole
   */
  public function patch($profileId, $id, Google_Service_Dfareporting_UserRole $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Dfareporting_UserRole");
  }

  /**
   * Updates an existing user role. (userRoles.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Google_UserRole $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Dfareporting_UserRole
   */
  public function update($profileId, Google_Service_Dfareporting_UserRole $postBody, $optParams = array())
  {
    $params = array('profileId' => $profileId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Dfareporting_UserRole");
  }
}




class Google_Service_Dfareporting_Account extends Google_Collection
{
  protected $collection_key = 'availablePermissionIds';
  protected $internal_gapi_mappings = array(
  );
  public $accountPermissionIds;
  public $accountProfile;
  public $active;
  public $activeAdsLimitTier;
  public $activeViewOptOut;
  public $availablePermissionIds;
  public $comscoreVceEnabled;
  public $countryId;
  public $currencyId;
  public $defaultCreativeSizeId;
  public $description;
  public $id;
  public $kind;
  public $locale;
  public $maximumImageSize;
  public $name;
  public $nielsenOcrEnabled;
  protected $reportsConfigurationType = 'Google_Service_Dfareporting_ReportsConfiguration';
  protected $reportsConfigurationDataType = '';
  public $teaserSizeLimit;


  public function setAccountPermissionIds($accountPermissionIds)
  {
    $this->accountPermissionIds = $accountPermissionIds;
  }
  public function getAccountPermissionIds()
  {
    return $this->accountPermissionIds;
  }
  public function setAccountProfile($accountProfile)
  {
    $this->accountProfile = $accountProfile;
  }
  public function getAccountProfile()
  {
    return $this->accountProfile;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setActiveAdsLimitTier($activeAdsLimitTier)
  {
    $this->activeAdsLimitTier = $activeAdsLimitTier;
  }
  public function getActiveAdsLimitTier()
  {
    return $this->activeAdsLimitTier;
  }
  public function setActiveViewOptOut($activeViewOptOut)
  {
    $this->activeViewOptOut = $activeViewOptOut;
  }
  public function getActiveViewOptOut()
  {
    return $this->activeViewOptOut;
  }
  public function setAvailablePermissionIds($availablePermissionIds)
  {
    $this->availablePermissionIds = $availablePermissionIds;
  }
  public function getAvailablePermissionIds()
  {
    return $this->availablePermissionIds;
  }
  public function setComscoreVceEnabled($comscoreVceEnabled)
  {
    $this->comscoreVceEnabled = $comscoreVceEnabled;
  }
  public function getComscoreVceEnabled()
  {
    return $this->comscoreVceEnabled;
  }
  public function setCountryId($countryId)
  {
    $this->countryId = $countryId;
  }
  public function getCountryId()
  {
    return $this->countryId;
  }
  public function setCurrencyId($currencyId)
  {
    $this->currencyId = $currencyId;
  }
  public function getCurrencyId()
  {
    return $this->currencyId;
  }
  public function setDefaultCreativeSizeId($defaultCreativeSizeId)
  {
    $this->defaultCreativeSizeId = $defaultCreativeSizeId;
  }
  public function getDefaultCreativeSizeId()
  {
    return $this->defaultCreativeSizeId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
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
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }
  public function getLocale()
  {
    return $this->locale;
  }
  public function setMaximumImageSize($maximumImageSize)
  {
    $this->maximumImageSize = $maximumImageSize;
  }
  public function getMaximumImageSize()
  {
    return $this->maximumImageSize;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNielsenOcrEnabled($nielsenOcrEnabled)
  {
    $this->nielsenOcrEnabled = $nielsenOcrEnabled;
  }
  public function getNielsenOcrEnabled()
  {
    return $this->nielsenOcrEnabled;
  }
  public function setReportsConfiguration(Google_Service_Dfareporting_ReportsConfiguration $reportsConfiguration)
  {
    $this->reportsConfiguration = $reportsConfiguration;
  }
  public function getReportsConfiguration()
  {
    return $this->reportsConfiguration;
  }
  public function setTeaserSizeLimit($teaserSizeLimit)
  {
    $this->teaserSizeLimit = $teaserSizeLimit;
  }
  public function getTeaserSizeLimit()
  {
    return $this->teaserSizeLimit;
  }
}

class Google_Service_Dfareporting_AccountActiveAdSummary extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $activeAds;
  public $activeAdsLimitTier;
  public $availableAds;
  public $kind;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActiveAds($activeAds)
  {
    $this->activeAds = $activeAds;
  }
  public function getActiveAds()
  {
    return $this->activeAds;
  }
  public function setActiveAdsLimitTier($activeAdsLimitTier)
  {
    $this->activeAdsLimitTier = $activeAdsLimitTier;
  }
  public function getActiveAdsLimitTier()
  {
    return $this->activeAdsLimitTier;
  }
  public function setAvailableAds($availableAds)
  {
    $this->availableAds = $availableAds;
  }
  public function getAvailableAds()
  {
    return $this->availableAds;
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

class Google_Service_Dfareporting_AccountPermission extends Google_Collection
{
  protected $collection_key = 'accountProfiles';
  protected $internal_gapi_mappings = array(
  );
  public $accountProfiles;
  public $id;
  public $kind;
  public $level;
  public $name;
  public $permissionGroupId;


  public function setAccountProfiles($accountProfiles)
  {
    $this->accountProfiles = $accountProfiles;
  }
  public function getAccountProfiles()
  {
    return $this->accountProfiles;
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
  public function setLevel($level)
  {
    $this->level = $level;
  }
  public function getLevel()
  {
    return $this->level;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPermissionGroupId($permissionGroupId)
  {
    $this->permissionGroupId = $permissionGroupId;
  }
  public function getPermissionGroupId()
  {
    return $this->permissionGroupId;
  }
}

class Google_Service_Dfareporting_AccountPermissionGroup extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_AccountPermissionGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'accountPermissionGroups';
  protected $internal_gapi_mappings = array(
  );
  protected $accountPermissionGroupsType = 'Google_Service_Dfareporting_AccountPermissionGroup';
  protected $accountPermissionGroupsDataType = 'array';
  public $kind;


  public function setAccountPermissionGroups($accountPermissionGroups)
  {
    $this->accountPermissionGroups = $accountPermissionGroups;
  }
  public function getAccountPermissionGroups()
  {
    return $this->accountPermissionGroups;
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

class Google_Service_Dfareporting_AccountPermissionsListResponse extends Google_Collection
{
  protected $collection_key = 'accountPermissions';
  protected $internal_gapi_mappings = array(
  );
  protected $accountPermissionsType = 'Google_Service_Dfareporting_AccountPermission';
  protected $accountPermissionsDataType = 'array';
  public $kind;


  public function setAccountPermissions($accountPermissions)
  {
    $this->accountPermissions = $accountPermissions;
  }
  public function getAccountPermissions()
  {
    return $this->accountPermissions;
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

class Google_Service_Dfareporting_AccountUserProfile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $active;
  protected $advertiserFilterType = 'Google_Service_Dfareporting_ObjectFilter';
  protected $advertiserFilterDataType = '';
  protected $campaignFilterType = 'Google_Service_Dfareporting_ObjectFilter';
  protected $campaignFilterDataType = '';
  public $comments;
  public $email;
  public $id;
  public $kind;
  public $locale;
  public $name;
  protected $siteFilterType = 'Google_Service_Dfareporting_ObjectFilter';
  protected $siteFilterDataType = '';
  public $subaccountId;
  public $traffickerType;
  public $userAccessType;
  protected $userRoleFilterType = 'Google_Service_Dfareporting_ObjectFilter';
  protected $userRoleFilterDataType = '';
  public $userRoleId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdvertiserFilter(Google_Service_Dfareporting_ObjectFilter $advertiserFilter)
  {
    $this->advertiserFilter = $advertiserFilter;
  }
  public function getAdvertiserFilter()
  {
    return $this->advertiserFilter;
  }
  public function setCampaignFilter(Google_Service_Dfareporting_ObjectFilter $campaignFilter)
  {
    $this->campaignFilter = $campaignFilter;
  }
  public function getCampaignFilter()
  {
    return $this->campaignFilter;
  }
  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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
  public function setLocale($locale)
  {
    $this->locale = $locale;
  }
  public function getLocale()
  {
    return $this->locale;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSiteFilter(Google_Service_Dfareporting_ObjectFilter $siteFilter)
  {
    $this->siteFilter = $siteFilter;
  }
  public function getSiteFilter()
  {
    return $this->siteFilter;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTraffickerType($traffickerType)
  {
    $this->traffickerType = $traffickerType;
  }
  public function getTraffickerType()
  {
    return $this->traffickerType;
  }
  public function setUserAccessType($userAccessType)
  {
    $this->userAccessType = $userAccessType;
  }
  public function getUserAccessType()
  {
    return $this->userAccessType;
  }
  public function setUserRoleFilter(Google_Service_Dfareporting_ObjectFilter $userRoleFilter)
  {
    $this->userRoleFilter = $userRoleFilter;
  }
  public function getUserRoleFilter()
  {
    return $this->userRoleFilter;
  }
  public function setUserRoleId($userRoleId)
  {
    $this->userRoleId = $userRoleId;
  }
  public function getUserRoleId()
  {
    return $this->userRoleId;
  }
}

class Google_Service_Dfareporting_AccountUserProfilesListResponse extends Google_Collection
{
  protected $collection_key = 'accountUserProfiles';
  protected $internal_gapi_mappings = array(
  );
  protected $accountUserProfilesType = 'Google_Service_Dfareporting_AccountUserProfile';
  protected $accountUserProfilesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setAccountUserProfiles($accountUserProfiles)
  {
    $this->accountUserProfiles = $accountUserProfiles;
  }
  public function getAccountUserProfiles()
  {
    return $this->accountUserProfiles;
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

class Google_Service_Dfareporting_AccountsListResponse extends Google_Collection
{
  protected $collection_key = 'accounts';
  protected $internal_gapi_mappings = array(
  );
  protected $accountsType = 'Google_Service_Dfareporting_Account';
  protected $accountsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setAccounts($accounts)
  {
    $this->accounts = $accounts;
  }
  public function getAccounts()
  {
    return $this->accounts;
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

class Google_Service_Dfareporting_Activities extends Google_Collection
{
  protected $collection_key = 'metricNames';
  protected $internal_gapi_mappings = array(
  );
  protected $filtersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $filtersDataType = 'array';
  public $kind;
  public $metricNames;


  public function setFilters($filters)
  {
    $this->filters = $filters;
  }
  public function getFilters()
  {
    return $this->filters;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
}

class Google_Service_Dfareporting_Ad extends Google_Collection
{
  protected $collection_key = 'placementAssignments';
  protected $internal_gapi_mappings = array(
        "remarketingListExpression" => "remarketing_list_expression",
  );
  public $accountId;
  public $active;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $archived;
  public $audienceSegmentId;
  public $campaignId;
  protected $campaignIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $campaignIdDimensionValueDataType = '';
  protected $clickThroughUrlType = 'Google_Service_Dfareporting_ClickThroughUrl';
  protected $clickThroughUrlDataType = '';
  protected $clickThroughUrlSuffixPropertiesType = 'Google_Service_Dfareporting_ClickThroughUrlSuffixProperties';
  protected $clickThroughUrlSuffixPropertiesDataType = '';
  public $comments;
  public $compatibility;
  protected $createInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $createInfoDataType = '';
  protected $creativeGroupAssignmentsType = 'Google_Service_Dfareporting_CreativeGroupAssignment';
  protected $creativeGroupAssignmentsDataType = 'array';
  protected $creativeRotationType = 'Google_Service_Dfareporting_CreativeRotation';
  protected $creativeRotationDataType = '';
  protected $dayPartTargetingType = 'Google_Service_Dfareporting_DayPartTargeting';
  protected $dayPartTargetingDataType = '';
  protected $defaultClickThroughEventTagPropertiesType = 'Google_Service_Dfareporting_DefaultClickThroughEventTagProperties';
  protected $defaultClickThroughEventTagPropertiesDataType = '';
  protected $deliveryScheduleType = 'Google_Service_Dfareporting_DeliverySchedule';
  protected $deliveryScheduleDataType = '';
  public $dynamicClickTracker;
  public $endTime;
  protected $eventTagOverridesType = 'Google_Service_Dfareporting_EventTagOverride';
  protected $eventTagOverridesDataType = 'array';
  protected $geoTargetingType = 'Google_Service_Dfareporting_GeoTargeting';
  protected $geoTargetingDataType = '';
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  protected $keyValueTargetingExpressionType = 'Google_Service_Dfareporting_KeyValueTargetingExpression';
  protected $keyValueTargetingExpressionDataType = '';
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $name;
  protected $placementAssignmentsType = 'Google_Service_Dfareporting_PlacementAssignment';
  protected $placementAssignmentsDataType = 'array';
  protected $remarketingListExpressionType = 'Google_Service_Dfareporting_ListTargetingExpression';
  protected $remarketingListExpressionDataType = '';
  protected $sizeType = 'Google_Service_Dfareporting_Size';
  protected $sizeDataType = '';
  public $sslCompliant;
  public $sslRequired;
  public $startTime;
  public $subaccountId;
  protected $technologyTargetingType = 'Google_Service_Dfareporting_TechnologyTargeting';
  protected $technologyTargetingDataType = '';
  public $type;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }
  public function getArchived()
  {
    return $this->archived;
  }
  public function setAudienceSegmentId($audienceSegmentId)
  {
    $this->audienceSegmentId = $audienceSegmentId;
  }
  public function getAudienceSegmentId()
  {
    return $this->audienceSegmentId;
  }
  public function setCampaignId($campaignId)
  {
    $this->campaignId = $campaignId;
  }
  public function getCampaignId()
  {
    return $this->campaignId;
  }
  public function setCampaignIdDimensionValue(Google_Service_Dfareporting_DimensionValue $campaignIdDimensionValue)
  {
    $this->campaignIdDimensionValue = $campaignIdDimensionValue;
  }
  public function getCampaignIdDimensionValue()
  {
    return $this->campaignIdDimensionValue;
  }
  public function setClickThroughUrl(Google_Service_Dfareporting_ClickThroughUrl $clickThroughUrl)
  {
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl()
  {
    return $this->clickThroughUrl;
  }
  public function setClickThroughUrlSuffixProperties(Google_Service_Dfareporting_ClickThroughUrlSuffixProperties $clickThroughUrlSuffixProperties)
  {
    $this->clickThroughUrlSuffixProperties = $clickThroughUrlSuffixProperties;
  }
  public function getClickThroughUrlSuffixProperties()
  {
    return $this->clickThroughUrlSuffixProperties;
  }
  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
  public function setCompatibility($compatibility)
  {
    $this->compatibility = $compatibility;
  }
  public function getCompatibility()
  {
    return $this->compatibility;
  }
  public function setCreateInfo(Google_Service_Dfareporting_LastModifiedInfo $createInfo)
  {
    $this->createInfo = $createInfo;
  }
  public function getCreateInfo()
  {
    return $this->createInfo;
  }
  public function setCreativeGroupAssignments($creativeGroupAssignments)
  {
    $this->creativeGroupAssignments = $creativeGroupAssignments;
  }
  public function getCreativeGroupAssignments()
  {
    return $this->creativeGroupAssignments;
  }
  public function setCreativeRotation(Google_Service_Dfareporting_CreativeRotation $creativeRotation)
  {
    $this->creativeRotation = $creativeRotation;
  }
  public function getCreativeRotation()
  {
    return $this->creativeRotation;
  }
  public function setDayPartTargeting(Google_Service_Dfareporting_DayPartTargeting $dayPartTargeting)
  {
    $this->dayPartTargeting = $dayPartTargeting;
  }
  public function getDayPartTargeting()
  {
    return $this->dayPartTargeting;
  }
  public function setDefaultClickThroughEventTagProperties(Google_Service_Dfareporting_DefaultClickThroughEventTagProperties $defaultClickThroughEventTagProperties)
  {
    $this->defaultClickThroughEventTagProperties = $defaultClickThroughEventTagProperties;
  }
  public function getDefaultClickThroughEventTagProperties()
  {
    return $this->defaultClickThroughEventTagProperties;
  }
  public function setDeliverySchedule(Google_Service_Dfareporting_DeliverySchedule $deliverySchedule)
  {
    $this->deliverySchedule = $deliverySchedule;
  }
  public function getDeliverySchedule()
  {
    return $this->deliverySchedule;
  }
  public function setDynamicClickTracker($dynamicClickTracker)
  {
    $this->dynamicClickTracker = $dynamicClickTracker;
  }
  public function getDynamicClickTracker()
  {
    return $this->dynamicClickTracker;
  }
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setEventTagOverrides($eventTagOverrides)
  {
    $this->eventTagOverrides = $eventTagOverrides;
  }
  public function getEventTagOverrides()
  {
    return $this->eventTagOverrides;
  }
  public function setGeoTargeting(Google_Service_Dfareporting_GeoTargeting $geoTargeting)
  {
    $this->geoTargeting = $geoTargeting;
  }
  public function getGeoTargeting()
  {
    return $this->geoTargeting;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKeyValueTargetingExpression(Google_Service_Dfareporting_KeyValueTargetingExpression $keyValueTargetingExpression)
  {
    $this->keyValueTargetingExpression = $keyValueTargetingExpression;
  }
  public function getKeyValueTargetingExpression()
  {
    return $this->keyValueTargetingExpression;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPlacementAssignments($placementAssignments)
  {
    $this->placementAssignments = $placementAssignments;
  }
  public function getPlacementAssignments()
  {
    return $this->placementAssignments;
  }
  public function setRemarketingListExpression(Google_Service_Dfareporting_ListTargetingExpression $remarketingListExpression)
  {
    $this->remarketingListExpression = $remarketingListExpression;
  }
  public function getRemarketingListExpression()
  {
    return $this->remarketingListExpression;
  }
  public function setSize(Google_Service_Dfareporting_Size $size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setSslRequired($sslRequired)
  {
    $this->sslRequired = $sslRequired;
  }
  public function getSslRequired()
  {
    return $this->sslRequired;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTechnologyTargeting(Google_Service_Dfareporting_TechnologyTargeting $technologyTargeting)
  {
    $this->technologyTargeting = $technologyTargeting;
  }
  public function getTechnologyTargeting()
  {
    return $this->technologyTargeting;
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

class Google_Service_Dfareporting_AdSlot extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $comment;
  public $compatibility;
  public $height;
  public $linkedPlacementId;
  public $name;
  public $paymentSourceType;
  public $primary;
  public $width;


  public function setComment($comment)
  {
    $this->comment = $comment;
  }
  public function getComment()
  {
    return $this->comment;
  }
  public function setCompatibility($compatibility)
  {
    $this->compatibility = $compatibility;
  }
  public function getCompatibility()
  {
    return $this->compatibility;
  }
  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setLinkedPlacementId($linkedPlacementId)
  {
    $this->linkedPlacementId = $linkedPlacementId;
  }
  public function getLinkedPlacementId()
  {
    return $this->linkedPlacementId;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPaymentSourceType($paymentSourceType)
  {
    $this->paymentSourceType = $paymentSourceType;
  }
  public function getPaymentSourceType()
  {
    return $this->paymentSourceType;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setWidth($width)
  {
    $this->width = $width;
  }
  public function getWidth()
  {
    return $this->width;
  }
}

class Google_Service_Dfareporting_AdsListResponse extends Google_Collection
{
  protected $collection_key = 'ads';
  protected $internal_gapi_mappings = array(
  );
  protected $adsType = 'Google_Service_Dfareporting_Ad';
  protected $adsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setAds($ads)
  {
    $this->ads = $ads;
  }
  public function getAds()
  {
    return $this->ads;
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

class Google_Service_Dfareporting_Advertiser extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserGroupId;
  public $clickThroughUrlSuffix;
  public $defaultClickThroughEventTagId;
  public $defaultEmail;
  public $floodlightConfigurationId;
  protected $floodlightConfigurationIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightConfigurationIdDimensionValueDataType = '';
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  public $name;
  public $originalFloodlightConfigurationId;
  public $status;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserGroupId($advertiserGroupId)
  {
    $this->advertiserGroupId = $advertiserGroupId;
  }
  public function getAdvertiserGroupId()
  {
    return $this->advertiserGroupId;
  }
  public function setClickThroughUrlSuffix($clickThroughUrlSuffix)
  {
    $this->clickThroughUrlSuffix = $clickThroughUrlSuffix;
  }
  public function getClickThroughUrlSuffix()
  {
    return $this->clickThroughUrlSuffix;
  }
  public function setDefaultClickThroughEventTagId($defaultClickThroughEventTagId)
  {
    $this->defaultClickThroughEventTagId = $defaultClickThroughEventTagId;
  }
  public function getDefaultClickThroughEventTagId()
  {
    return $this->defaultClickThroughEventTagId;
  }
  public function setDefaultEmail($defaultEmail)
  {
    $this->defaultEmail = $defaultEmail;
  }
  public function getDefaultEmail()
  {
    return $this->defaultEmail;
  }
  public function setFloodlightConfigurationId($floodlightConfigurationId)
  {
    $this->floodlightConfigurationId = $floodlightConfigurationId;
  }
  public function getFloodlightConfigurationId()
  {
    return $this->floodlightConfigurationId;
  }
  public function setFloodlightConfigurationIdDimensionValue(Google_Service_Dfareporting_DimensionValue $floodlightConfigurationIdDimensionValue)
  {
    $this->floodlightConfigurationIdDimensionValue = $floodlightConfigurationIdDimensionValue;
  }
  public function getFloodlightConfigurationIdDimensionValue()
  {
    return $this->floodlightConfigurationIdDimensionValue;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOriginalFloodlightConfigurationId($originalFloodlightConfigurationId)
  {
    $this->originalFloodlightConfigurationId = $originalFloodlightConfigurationId;
  }
  public function getOriginalFloodlightConfigurationId()
  {
    return $this->originalFloodlightConfigurationId;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_AdvertiserGroup extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $id;
  public $kind;
  public $name;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_AdvertiserGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'advertiserGroups';
  protected $internal_gapi_mappings = array(
  );
  protected $advertiserGroupsType = 'Google_Service_Dfareporting_AdvertiserGroup';
  protected $advertiserGroupsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setAdvertiserGroups($advertiserGroups)
  {
    $this->advertiserGroups = $advertiserGroups;
  }
  public function getAdvertiserGroups()
  {
    return $this->advertiserGroups;
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

class Google_Service_Dfareporting_AdvertisersListResponse extends Google_Collection
{
  protected $collection_key = 'advertisers';
  protected $internal_gapi_mappings = array(
  );
  protected $advertisersType = 'Google_Service_Dfareporting_Advertiser';
  protected $advertisersDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setAdvertisers($advertisers)
  {
    $this->advertisers = $advertisers;
  }
  public function getAdvertisers()
  {
    return $this->advertisers;
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

class Google_Service_Dfareporting_AudienceSegment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $allocation;
  public $id;
  public $name;


  public function setAllocation($allocation)
  {
    $this->allocation = $allocation;
  }
  public function getAllocation()
  {
    return $this->allocation;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
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

class Google_Service_Dfareporting_AudienceSegmentGroup extends Google_Collection
{
  protected $collection_key = 'audienceSegments';
  protected $internal_gapi_mappings = array(
  );
  protected $audienceSegmentsType = 'Google_Service_Dfareporting_AudienceSegment';
  protected $audienceSegmentsDataType = 'array';
  public $id;
  public $name;


  public function setAudienceSegments($audienceSegments)
  {
    $this->audienceSegments = $audienceSegments;
  }
  public function getAudienceSegments()
  {
    return $this->audienceSegments;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
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

class Google_Service_Dfareporting_Browser extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $browserVersionId;
  public $dartId;
  public $kind;
  public $majorVersion;
  public $minorVersion;
  public $name;


  public function setBrowserVersionId($browserVersionId)
  {
    $this->browserVersionId = $browserVersionId;
  }
  public function getBrowserVersionId()
  {
    return $this->browserVersionId;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMajorVersion($majorVersion)
  {
    $this->majorVersion = $majorVersion;
  }
  public function getMajorVersion()
  {
    return $this->majorVersion;
  }
  public function setMinorVersion($minorVersion)
  {
    $this->minorVersion = $minorVersion;
  }
  public function getMinorVersion()
  {
    return $this->minorVersion;
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

class Google_Service_Dfareporting_BrowsersListResponse extends Google_Collection
{
  protected $collection_key = 'browsers';
  protected $internal_gapi_mappings = array(
  );
  protected $browsersType = 'Google_Service_Dfareporting_Browser';
  protected $browsersDataType = 'array';
  public $kind;


  public function setBrowsers($browsers)
  {
    $this->browsers = $browsers;
  }
  public function getBrowsers()
  {
    return $this->browsers;
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

class Google_Service_Dfareporting_Campaign extends Google_Collection
{
  protected $collection_key = 'traffickerEmails';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  protected $additionalCreativeOptimizationConfigurationsType = 'Google_Service_Dfareporting_CreativeOptimizationConfiguration';
  protected $additionalCreativeOptimizationConfigurationsDataType = 'array';
  public $advertiserGroupId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $archived;
  protected $audienceSegmentGroupsType = 'Google_Service_Dfareporting_AudienceSegmentGroup';
  protected $audienceSegmentGroupsDataType = 'array';
  public $billingInvoiceCode;
  protected $clickThroughUrlSuffixPropertiesType = 'Google_Service_Dfareporting_ClickThroughUrlSuffixProperties';
  protected $clickThroughUrlSuffixPropertiesDataType = '';
  public $comment;
  public $comscoreVceEnabled;
  protected $createInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $createInfoDataType = '';
  public $creativeGroupIds;
  protected $creativeOptimizationConfigurationType = 'Google_Service_Dfareporting_CreativeOptimizationConfiguration';
  protected $creativeOptimizationConfigurationDataType = '';
  protected $defaultClickThroughEventTagPropertiesType = 'Google_Service_Dfareporting_DefaultClickThroughEventTagProperties';
  protected $defaultClickThroughEventTagPropertiesDataType = '';
  public $endDate;
  protected $eventTagOverridesType = 'Google_Service_Dfareporting_EventTagOverride';
  protected $eventTagOverridesDataType = 'array';
  public $externalId;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  public $name;
  public $nielsenOcrEnabled;
  public $startDate;
  public $subaccountId;
  public $traffickerEmails;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdditionalCreativeOptimizationConfigurations($additionalCreativeOptimizationConfigurations)
  {
    $this->additionalCreativeOptimizationConfigurations = $additionalCreativeOptimizationConfigurations;
  }
  public function getAdditionalCreativeOptimizationConfigurations()
  {
    return $this->additionalCreativeOptimizationConfigurations;
  }
  public function setAdvertiserGroupId($advertiserGroupId)
  {
    $this->advertiserGroupId = $advertiserGroupId;
  }
  public function getAdvertiserGroupId()
  {
    return $this->advertiserGroupId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }
  public function getArchived()
  {
    return $this->archived;
  }
  public function setAudienceSegmentGroups($audienceSegmentGroups)
  {
    $this->audienceSegmentGroups = $audienceSegmentGroups;
  }
  public function getAudienceSegmentGroups()
  {
    return $this->audienceSegmentGroups;
  }
  public function setBillingInvoiceCode($billingInvoiceCode)
  {
    $this->billingInvoiceCode = $billingInvoiceCode;
  }
  public function getBillingInvoiceCode()
  {
    return $this->billingInvoiceCode;
  }
  public function setClickThroughUrlSuffixProperties(Google_Service_Dfareporting_ClickThroughUrlSuffixProperties $clickThroughUrlSuffixProperties)
  {
    $this->clickThroughUrlSuffixProperties = $clickThroughUrlSuffixProperties;
  }
  public function getClickThroughUrlSuffixProperties()
  {
    return $this->clickThroughUrlSuffixProperties;
  }
  public function setComment($comment)
  {
    $this->comment = $comment;
  }
  public function getComment()
  {
    return $this->comment;
  }
  public function setComscoreVceEnabled($comscoreVceEnabled)
  {
    $this->comscoreVceEnabled = $comscoreVceEnabled;
  }
  public function getComscoreVceEnabled()
  {
    return $this->comscoreVceEnabled;
  }
  public function setCreateInfo(Google_Service_Dfareporting_LastModifiedInfo $createInfo)
  {
    $this->createInfo = $createInfo;
  }
  public function getCreateInfo()
  {
    return $this->createInfo;
  }
  public function setCreativeGroupIds($creativeGroupIds)
  {
    $this->creativeGroupIds = $creativeGroupIds;
  }
  public function getCreativeGroupIds()
  {
    return $this->creativeGroupIds;
  }
  public function setCreativeOptimizationConfiguration(Google_Service_Dfareporting_CreativeOptimizationConfiguration $creativeOptimizationConfiguration)
  {
    $this->creativeOptimizationConfiguration = $creativeOptimizationConfiguration;
  }
  public function getCreativeOptimizationConfiguration()
  {
    return $this->creativeOptimizationConfiguration;
  }
  public function setDefaultClickThroughEventTagProperties(Google_Service_Dfareporting_DefaultClickThroughEventTagProperties $defaultClickThroughEventTagProperties)
  {
    $this->defaultClickThroughEventTagProperties = $defaultClickThroughEventTagProperties;
  }
  public function getDefaultClickThroughEventTagProperties()
  {
    return $this->defaultClickThroughEventTagProperties;
  }
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setEventTagOverrides($eventTagOverrides)
  {
    $this->eventTagOverrides = $eventTagOverrides;
  }
  public function getEventTagOverrides()
  {
    return $this->eventTagOverrides;
  }
  public function setExternalId($externalId)
  {
    $this->externalId = $externalId;
  }
  public function getExternalId()
  {
    return $this->externalId;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNielsenOcrEnabled($nielsenOcrEnabled)
  {
    $this->nielsenOcrEnabled = $nielsenOcrEnabled;
  }
  public function getNielsenOcrEnabled()
  {
    return $this->nielsenOcrEnabled;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTraffickerEmails($traffickerEmails)
  {
    $this->traffickerEmails = $traffickerEmails;
  }
  public function getTraffickerEmails()
  {
    return $this->traffickerEmails;
  }
}

class Google_Service_Dfareporting_CampaignCreativeAssociation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creativeId;
  public $kind;


  public function setCreativeId($creativeId)
  {
    $this->creativeId = $creativeId;
  }
  public function getCreativeId()
  {
    return $this->creativeId;
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

class Google_Service_Dfareporting_CampaignCreativeAssociationsListResponse extends Google_Collection
{
  protected $collection_key = 'campaignCreativeAssociations';
  protected $internal_gapi_mappings = array(
  );
  protected $campaignCreativeAssociationsType = 'Google_Service_Dfareporting_CampaignCreativeAssociation';
  protected $campaignCreativeAssociationsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCampaignCreativeAssociations($campaignCreativeAssociations)
  {
    $this->campaignCreativeAssociations = $campaignCreativeAssociations;
  }
  public function getCampaignCreativeAssociations()
  {
    return $this->campaignCreativeAssociations;
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

class Google_Service_Dfareporting_CampaignsListResponse extends Google_Collection
{
  protected $collection_key = 'campaigns';
  protected $internal_gapi_mappings = array(
  );
  protected $campaignsType = 'Google_Service_Dfareporting_Campaign';
  protected $campaignsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCampaigns($campaigns)
  {
    $this->campaigns = $campaigns;
  }
  public function getCampaigns()
  {
    return $this->campaigns;
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

class Google_Service_Dfareporting_ChangeLog extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $action;
  public $changeTime;
  public $fieldName;
  public $id;
  public $kind;
  public $newValue;
  public $objectId;
  public $objectType;
  public $oldValue;
  public $subaccountId;
  public $transactionId;
  public $userProfileId;
  public $userProfileName;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAction($action)
  {
    $this->action = $action;
  }
  public function getAction()
  {
    return $this->action;
  }
  public function setChangeTime($changeTime)
  {
    $this->changeTime = $changeTime;
  }
  public function getChangeTime()
  {
    return $this->changeTime;
  }
  public function setFieldName($fieldName)
  {
    $this->fieldName = $fieldName;
  }
  public function getFieldName()
  {
    return $this->fieldName;
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
  public function setNewValue($newValue)
  {
    $this->newValue = $newValue;
  }
  public function getNewValue()
  {
    return $this->newValue;
  }
  public function setObjectId($objectId)
  {
    $this->objectId = $objectId;
  }
  public function getObjectId()
  {
    return $this->objectId;
  }
  public function setObjectType($objectType)
  {
    $this->objectType = $objectType;
  }
  public function getObjectType()
  {
    return $this->objectType;
  }
  public function setOldValue($oldValue)
  {
    $this->oldValue = $oldValue;
  }
  public function getOldValue()
  {
    return $this->oldValue;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTransactionId($transactionId)
  {
    $this->transactionId = $transactionId;
  }
  public function getTransactionId()
  {
    return $this->transactionId;
  }
  public function setUserProfileId($userProfileId)
  {
    $this->userProfileId = $userProfileId;
  }
  public function getUserProfileId()
  {
    return $this->userProfileId;
  }
  public function setUserProfileName($userProfileName)
  {
    $this->userProfileName = $userProfileName;
  }
  public function getUserProfileName()
  {
    return $this->userProfileName;
  }
}

class Google_Service_Dfareporting_ChangeLogsListResponse extends Google_Collection
{
  protected $collection_key = 'changeLogs';
  protected $internal_gapi_mappings = array(
  );
  protected $changeLogsType = 'Google_Service_Dfareporting_ChangeLog';
  protected $changeLogsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setChangeLogs($changeLogs)
  {
    $this->changeLogs = $changeLogs;
  }
  public function getChangeLogs()
  {
    return $this->changeLogs;
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

class Google_Service_Dfareporting_CitiesListResponse extends Google_Collection
{
  protected $collection_key = 'cities';
  protected $internal_gapi_mappings = array(
  );
  protected $citiesType = 'Google_Service_Dfareporting_City';
  protected $citiesDataType = 'array';
  public $kind;


  public function setCities($cities)
  {
    $this->cities = $cities;
  }
  public function getCities()
  {
    return $this->cities;
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

class Google_Service_Dfareporting_City extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $countryCode;
  public $countryDartId;
  public $dartId;
  public $kind;
  public $metroCode;
  public $metroDmaId;
  public $name;
  public $regionCode;
  public $regionDartId;


  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCountryDartId($countryDartId)
  {
    $this->countryDartId = $countryDartId;
  }
  public function getCountryDartId()
  {
    return $this->countryDartId;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetroCode($metroCode)
  {
    $this->metroCode = $metroCode;
  }
  public function getMetroCode()
  {
    return $this->metroCode;
  }
  public function setMetroDmaId($metroDmaId)
  {
    $this->metroDmaId = $metroDmaId;
  }
  public function getMetroDmaId()
  {
    return $this->metroDmaId;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setRegionCode($regionCode)
  {
    $this->regionCode = $regionCode;
  }
  public function getRegionCode()
  {
    return $this->regionCode;
  }
  public function setRegionDartId($regionDartId)
  {
    $this->regionDartId = $regionDartId;
  }
  public function getRegionDartId()
  {
    return $this->regionDartId;
  }
}

class Google_Service_Dfareporting_ClickTag extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $eventName;
  public $name;
  public $value;


  public function setEventName($eventName)
  {
    $this->eventName = $eventName;
  }
  public function getEventName()
  {
    return $this->eventName;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_Dfareporting_ClickThroughUrl extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customClickThroughUrl;
  public $defaultLandingPage;
  public $landingPageId;


  public function setCustomClickThroughUrl($customClickThroughUrl)
  {
    $this->customClickThroughUrl = $customClickThroughUrl;
  }
  public function getCustomClickThroughUrl()
  {
    return $this->customClickThroughUrl;
  }
  public function setDefaultLandingPage($defaultLandingPage)
  {
    $this->defaultLandingPage = $defaultLandingPage;
  }
  public function getDefaultLandingPage()
  {
    return $this->defaultLandingPage;
  }
  public function setLandingPageId($landingPageId)
  {
    $this->landingPageId = $landingPageId;
  }
  public function getLandingPageId()
  {
    return $this->landingPageId;
  }
}

class Google_Service_Dfareporting_ClickThroughUrlSuffixProperties extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $clickThroughUrlSuffix;
  public $overrideInheritedSuffix;


  public function setClickThroughUrlSuffix($clickThroughUrlSuffix)
  {
    $this->clickThroughUrlSuffix = $clickThroughUrlSuffix;
  }
  public function getClickThroughUrlSuffix()
  {
    return $this->clickThroughUrlSuffix;
  }
  public function setOverrideInheritedSuffix($overrideInheritedSuffix)
  {
    $this->overrideInheritedSuffix = $overrideInheritedSuffix;
  }
  public function getOverrideInheritedSuffix()
  {
    return $this->overrideInheritedSuffix;
  }
}

class Google_Service_Dfareporting_CompanionClickThroughOverride extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $clickThroughUrlType = 'Google_Service_Dfareporting_ClickThroughUrl';
  protected $clickThroughUrlDataType = '';
  public $creativeId;


  public function setClickThroughUrl(Google_Service_Dfareporting_ClickThroughUrl $clickThroughUrl)
  {
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl()
  {
    return $this->clickThroughUrl;
  }
  public function setCreativeId($creativeId)
  {
    $this->creativeId = $creativeId;
  }
  public function getCreativeId()
  {
    return $this->creativeId;
  }
}

class Google_Service_Dfareporting_CompatibleFields extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $crossDimensionReachReportCompatibleFieldsType = 'Google_Service_Dfareporting_CrossDimensionReachReportCompatibleFields';
  protected $crossDimensionReachReportCompatibleFieldsDataType = '';
  protected $floodlightReportCompatibleFieldsType = 'Google_Service_Dfareporting_FloodlightReportCompatibleFields';
  protected $floodlightReportCompatibleFieldsDataType = '';
  public $kind;
  protected $pathToConversionReportCompatibleFieldsType = 'Google_Service_Dfareporting_PathToConversionReportCompatibleFields';
  protected $pathToConversionReportCompatibleFieldsDataType = '';
  protected $reachReportCompatibleFieldsType = 'Google_Service_Dfareporting_ReachReportCompatibleFields';
  protected $reachReportCompatibleFieldsDataType = '';
  protected $reportCompatibleFieldsType = 'Google_Service_Dfareporting_ReportCompatibleFields';
  protected $reportCompatibleFieldsDataType = '';


  public function setCrossDimensionReachReportCompatibleFields(Google_Service_Dfareporting_CrossDimensionReachReportCompatibleFields $crossDimensionReachReportCompatibleFields)
  {
    $this->crossDimensionReachReportCompatibleFields = $crossDimensionReachReportCompatibleFields;
  }
  public function getCrossDimensionReachReportCompatibleFields()
  {
    return $this->crossDimensionReachReportCompatibleFields;
  }
  public function setFloodlightReportCompatibleFields(Google_Service_Dfareporting_FloodlightReportCompatibleFields $floodlightReportCompatibleFields)
  {
    $this->floodlightReportCompatibleFields = $floodlightReportCompatibleFields;
  }
  public function getFloodlightReportCompatibleFields()
  {
    return $this->floodlightReportCompatibleFields;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPathToConversionReportCompatibleFields(Google_Service_Dfareporting_PathToConversionReportCompatibleFields $pathToConversionReportCompatibleFields)
  {
    $this->pathToConversionReportCompatibleFields = $pathToConversionReportCompatibleFields;
  }
  public function getPathToConversionReportCompatibleFields()
  {
    return $this->pathToConversionReportCompatibleFields;
  }
  public function setReachReportCompatibleFields(Google_Service_Dfareporting_ReachReportCompatibleFields $reachReportCompatibleFields)
  {
    $this->reachReportCompatibleFields = $reachReportCompatibleFields;
  }
  public function getReachReportCompatibleFields()
  {
    return $this->reachReportCompatibleFields;
  }
  public function setReportCompatibleFields(Google_Service_Dfareporting_ReportCompatibleFields $reportCompatibleFields)
  {
    $this->reportCompatibleFields = $reportCompatibleFields;
  }
  public function getReportCompatibleFields()
  {
    return $this->reportCompatibleFields;
  }
}

class Google_Service_Dfareporting_ConnectionType extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_ConnectionTypesListResponse extends Google_Collection
{
  protected $collection_key = 'connectionTypes';
  protected $internal_gapi_mappings = array(
  );
  protected $connectionTypesType = 'Google_Service_Dfareporting_ConnectionType';
  protected $connectionTypesDataType = 'array';
  public $kind;


  public function setConnectionTypes($connectionTypes)
  {
    $this->connectionTypes = $connectionTypes;
  }
  public function getConnectionTypes()
  {
    return $this->connectionTypes;
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

class Google_Service_Dfareporting_ContentCategoriesListResponse extends Google_Collection
{
  protected $collection_key = 'contentCategories';
  protected $internal_gapi_mappings = array(
  );
  protected $contentCategoriesType = 'Google_Service_Dfareporting_ContentCategory';
  protected $contentCategoriesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setContentCategories($contentCategories)
  {
    $this->contentCategories = $contentCategories;
  }
  public function getContentCategories()
  {
    return $this->contentCategories;
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

class Google_Service_Dfareporting_ContentCategory extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $id;
  public $kind;
  public $name;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_CountriesListResponse extends Google_Collection
{
  protected $collection_key = 'countries';
  protected $internal_gapi_mappings = array(
  );
  protected $countriesType = 'Google_Service_Dfareporting_Country';
  protected $countriesDataType = 'array';
  public $kind;


  public function setCountries($countries)
  {
    $this->countries = $countries;
  }
  public function getCountries()
  {
    return $this->countries;
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

class Google_Service_Dfareporting_Country extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $countryCode;
  public $dartId;
  public $kind;
  public $name;
  public $sslEnabled;


  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSslEnabled($sslEnabled)
  {
    $this->sslEnabled = $sslEnabled;
  }
  public function getSslEnabled()
  {
    return $this->sslEnabled;
  }
}

class Google_Service_Dfareporting_Creative extends Google_Collection
{
  protected $collection_key = 'timerCustomEvents';
  protected $internal_gapi_mappings = array(
        "autoAdvanceImages" => "auto_advance_images",
  );
  public $accountId;
  public $active;
  public $adParameters;
  public $adTagKeys;
  public $advertiserId;
  public $allowScriptAccess;
  public $archived;
  public $artworkType;
  public $authoringTool;
  public $autoAdvanceImages;
  public $backgroundColor;
  public $backupImageClickThroughUrl;
  public $backupImageFeatures;
  public $backupImageReportingLabel;
  protected $backupImageTargetWindowType = 'Google_Service_Dfareporting_TargetWindow';
  protected $backupImageTargetWindowDataType = '';
  protected $clickTagsType = 'Google_Service_Dfareporting_ClickTag';
  protected $clickTagsDataType = 'array';
  public $commercialId;
  public $companionCreatives;
  public $compatibility;
  public $convertFlashToHtml5;
  protected $counterCustomEventsType = 'Google_Service_Dfareporting_CreativeCustomEvent';
  protected $counterCustomEventsDataType = 'array';
  protected $creativeAssetsType = 'Google_Service_Dfareporting_CreativeAsset';
  protected $creativeAssetsDataType = 'array';
  protected $creativeFieldAssignmentsType = 'Google_Service_Dfareporting_CreativeFieldAssignment';
  protected $creativeFieldAssignmentsDataType = 'array';
  public $customKeyValues;
  protected $exitCustomEventsType = 'Google_Service_Dfareporting_CreativeCustomEvent';
  protected $exitCustomEventsDataType = 'array';
  protected $fsCommandType = 'Google_Service_Dfareporting_FsCommand';
  protected $fsCommandDataType = '';
  public $htmlCode;
  public $htmlCodeLocked;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $latestTraffickedCreativeId;
  public $name;
  public $overrideCss;
  public $redirectUrl;
  public $renderingId;
  protected $renderingIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $renderingIdDimensionValueDataType = '';
  public $requiredFlashPluginVersion;
  public $requiredFlashVersion;
  protected $sizeType = 'Google_Service_Dfareporting_Size';
  protected $sizeDataType = '';
  public $skippable;
  public $sslCompliant;
  public $studioAdvertiserId;
  public $studioCreativeId;
  public $studioTraffickedCreativeId;
  public $subaccountId;
  public $thirdPartyBackupImageImpressionsUrl;
  public $thirdPartyRichMediaImpressionsUrl;
  protected $thirdPartyUrlsType = 'Google_Service_Dfareporting_ThirdPartyTrackingUrl';
  protected $thirdPartyUrlsDataType = 'array';
  protected $timerCustomEventsType = 'Google_Service_Dfareporting_CreativeCustomEvent';
  protected $timerCustomEventsDataType = 'array';
  public $totalFileSize;
  public $type;
  public $version;
  public $videoDescription;
  public $videoDuration;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdParameters($adParameters)
  {
    $this->adParameters = $adParameters;
  }
  public function getAdParameters()
  {
    return $this->adParameters;
  }
  public function setAdTagKeys($adTagKeys)
  {
    $this->adTagKeys = $adTagKeys;
  }
  public function getAdTagKeys()
  {
    return $this->adTagKeys;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAllowScriptAccess($allowScriptAccess)
  {
    $this->allowScriptAccess = $allowScriptAccess;
  }
  public function getAllowScriptAccess()
  {
    return $this->allowScriptAccess;
  }
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }
  public function getArchived()
  {
    return $this->archived;
  }
  public function setArtworkType($artworkType)
  {
    $this->artworkType = $artworkType;
  }
  public function getArtworkType()
  {
    return $this->artworkType;
  }
  public function setAuthoringTool($authoringTool)
  {
    $this->authoringTool = $authoringTool;
  }
  public function getAuthoringTool()
  {
    return $this->authoringTool;
  }
  public function setAutoAdvanceImages($autoAdvanceImages)
  {
    $this->autoAdvanceImages = $autoAdvanceImages;
  }
  public function getAutoAdvanceImages()
  {
    return $this->autoAdvanceImages;
  }
  public function setBackgroundColor($backgroundColor)
  {
    $this->backgroundColor = $backgroundColor;
  }
  public function getBackgroundColor()
  {
    return $this->backgroundColor;
  }
  public function setBackupImageClickThroughUrl($backupImageClickThroughUrl)
  {
    $this->backupImageClickThroughUrl = $backupImageClickThroughUrl;
  }
  public function getBackupImageClickThroughUrl()
  {
    return $this->backupImageClickThroughUrl;
  }
  public function setBackupImageFeatures($backupImageFeatures)
  {
    $this->backupImageFeatures = $backupImageFeatures;
  }
  public function getBackupImageFeatures()
  {
    return $this->backupImageFeatures;
  }
  public function setBackupImageReportingLabel($backupImageReportingLabel)
  {
    $this->backupImageReportingLabel = $backupImageReportingLabel;
  }
  public function getBackupImageReportingLabel()
  {
    return $this->backupImageReportingLabel;
  }
  public function setBackupImageTargetWindow(Google_Service_Dfareporting_TargetWindow $backupImageTargetWindow)
  {
    $this->backupImageTargetWindow = $backupImageTargetWindow;
  }
  public function getBackupImageTargetWindow()
  {
    return $this->backupImageTargetWindow;
  }
  public function setClickTags($clickTags)
  {
    $this->clickTags = $clickTags;
  }
  public function getClickTags()
  {
    return $this->clickTags;
  }
  public function setCommercialId($commercialId)
  {
    $this->commercialId = $commercialId;
  }
  public function getCommercialId()
  {
    return $this->commercialId;
  }
  public function setCompanionCreatives($companionCreatives)
  {
    $this->companionCreatives = $companionCreatives;
  }
  public function getCompanionCreatives()
  {
    return $this->companionCreatives;
  }
  public function setCompatibility($compatibility)
  {
    $this->compatibility = $compatibility;
  }
  public function getCompatibility()
  {
    return $this->compatibility;
  }
  public function setConvertFlashToHtml5($convertFlashToHtml5)
  {
    $this->convertFlashToHtml5 = $convertFlashToHtml5;
  }
  public function getConvertFlashToHtml5()
  {
    return $this->convertFlashToHtml5;
  }
  public function setCounterCustomEvents($counterCustomEvents)
  {
    $this->counterCustomEvents = $counterCustomEvents;
  }
  public function getCounterCustomEvents()
  {
    return $this->counterCustomEvents;
  }
  public function setCreativeAssets($creativeAssets)
  {
    $this->creativeAssets = $creativeAssets;
  }
  public function getCreativeAssets()
  {
    return $this->creativeAssets;
  }
  public function setCreativeFieldAssignments($creativeFieldAssignments)
  {
    $this->creativeFieldAssignments = $creativeFieldAssignments;
  }
  public function getCreativeFieldAssignments()
  {
    return $this->creativeFieldAssignments;
  }
  public function setCustomKeyValues($customKeyValues)
  {
    $this->customKeyValues = $customKeyValues;
  }
  public function getCustomKeyValues()
  {
    return $this->customKeyValues;
  }
  public function setExitCustomEvents($exitCustomEvents)
  {
    $this->exitCustomEvents = $exitCustomEvents;
  }
  public function getExitCustomEvents()
  {
    return $this->exitCustomEvents;
  }
  public function setFsCommand(Google_Service_Dfareporting_FsCommand $fsCommand)
  {
    $this->fsCommand = $fsCommand;
  }
  public function getFsCommand()
  {
    return $this->fsCommand;
  }
  public function setHtmlCode($htmlCode)
  {
    $this->htmlCode = $htmlCode;
  }
  public function getHtmlCode()
  {
    return $this->htmlCode;
  }
  public function setHtmlCodeLocked($htmlCodeLocked)
  {
    $this->htmlCodeLocked = $htmlCodeLocked;
  }
  public function getHtmlCodeLocked()
  {
    return $this->htmlCodeLocked;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setLatestTraffickedCreativeId($latestTraffickedCreativeId)
  {
    $this->latestTraffickedCreativeId = $latestTraffickedCreativeId;
  }
  public function getLatestTraffickedCreativeId()
  {
    return $this->latestTraffickedCreativeId;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOverrideCss($overrideCss)
  {
    $this->overrideCss = $overrideCss;
  }
  public function getOverrideCss()
  {
    return $this->overrideCss;
  }
  public function setRedirectUrl($redirectUrl)
  {
    $this->redirectUrl = $redirectUrl;
  }
  public function getRedirectUrl()
  {
    return $this->redirectUrl;
  }
  public function setRenderingId($renderingId)
  {
    $this->renderingId = $renderingId;
  }
  public function getRenderingId()
  {
    return $this->renderingId;
  }
  public function setRenderingIdDimensionValue(Google_Service_Dfareporting_DimensionValue $renderingIdDimensionValue)
  {
    $this->renderingIdDimensionValue = $renderingIdDimensionValue;
  }
  public function getRenderingIdDimensionValue()
  {
    return $this->renderingIdDimensionValue;
  }
  public function setRequiredFlashPluginVersion($requiredFlashPluginVersion)
  {
    $this->requiredFlashPluginVersion = $requiredFlashPluginVersion;
  }
  public function getRequiredFlashPluginVersion()
  {
    return $this->requiredFlashPluginVersion;
  }
  public function setRequiredFlashVersion($requiredFlashVersion)
  {
    $this->requiredFlashVersion = $requiredFlashVersion;
  }
  public function getRequiredFlashVersion()
  {
    return $this->requiredFlashVersion;
  }
  public function setSize(Google_Service_Dfareporting_Size $size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setSkippable($skippable)
  {
    $this->skippable = $skippable;
  }
  public function getSkippable()
  {
    return $this->skippable;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setStudioAdvertiserId($studioAdvertiserId)
  {
    $this->studioAdvertiserId = $studioAdvertiserId;
  }
  public function getStudioAdvertiserId()
  {
    return $this->studioAdvertiserId;
  }
  public function setStudioCreativeId($studioCreativeId)
  {
    $this->studioCreativeId = $studioCreativeId;
  }
  public function getStudioCreativeId()
  {
    return $this->studioCreativeId;
  }
  public function setStudioTraffickedCreativeId($studioTraffickedCreativeId)
  {
    $this->studioTraffickedCreativeId = $studioTraffickedCreativeId;
  }
  public function getStudioTraffickedCreativeId()
  {
    return $this->studioTraffickedCreativeId;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setThirdPartyBackupImageImpressionsUrl($thirdPartyBackupImageImpressionsUrl)
  {
    $this->thirdPartyBackupImageImpressionsUrl = $thirdPartyBackupImageImpressionsUrl;
  }
  public function getThirdPartyBackupImageImpressionsUrl()
  {
    return $this->thirdPartyBackupImageImpressionsUrl;
  }
  public function setThirdPartyRichMediaImpressionsUrl($thirdPartyRichMediaImpressionsUrl)
  {
    $this->thirdPartyRichMediaImpressionsUrl = $thirdPartyRichMediaImpressionsUrl;
  }
  public function getThirdPartyRichMediaImpressionsUrl()
  {
    return $this->thirdPartyRichMediaImpressionsUrl;
  }
  public function setThirdPartyUrls($thirdPartyUrls)
  {
    $this->thirdPartyUrls = $thirdPartyUrls;
  }
  public function getThirdPartyUrls()
  {
    return $this->thirdPartyUrls;
  }
  public function setTimerCustomEvents($timerCustomEvents)
  {
    $this->timerCustomEvents = $timerCustomEvents;
  }
  public function getTimerCustomEvents()
  {
    return $this->timerCustomEvents;
  }
  public function setTotalFileSize($totalFileSize)
  {
    $this->totalFileSize = $totalFileSize;
  }
  public function getTotalFileSize()
  {
    return $this->totalFileSize;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
  public function setVideoDescription($videoDescription)
  {
    $this->videoDescription = $videoDescription;
  }
  public function getVideoDescription()
  {
    return $this->videoDescription;
  }
  public function setVideoDuration($videoDuration)
  {
    $this->videoDuration = $videoDuration;
  }
  public function getVideoDuration()
  {
    return $this->videoDuration;
  }
}

class Google_Service_Dfareporting_CreativeAsset extends Google_Collection
{
  protected $collection_key = 'detectedFeatures';
  protected $internal_gapi_mappings = array(
  );
  public $actionScript3;
  public $active;
  public $alignment;
  public $artworkType;
  protected $assetIdentifierType = 'Google_Service_Dfareporting_CreativeAssetId';
  protected $assetIdentifierDataType = '';
  protected $backupImageExitType = 'Google_Service_Dfareporting_CreativeCustomEvent';
  protected $backupImageExitDataType = '';
  public $bitRate;
  public $childAssetType;
  protected $collapsedSizeType = 'Google_Service_Dfareporting_Size';
  protected $collapsedSizeDataType = '';
  public $customStartTimeValue;
  public $detectedFeatures;
  public $displayType;
  public $duration;
  public $durationType;
  protected $expandedDimensionType = 'Google_Service_Dfareporting_Size';
  protected $expandedDimensionDataType = '';
  public $fileSize;
  public $flashVersion;
  public $hideFlashObjects;
  public $hideSelectionBoxes;
  public $horizontallyLocked;
  public $id;
  public $mimeType;
  protected $offsetType = 'Google_Service_Dfareporting_OffsetPosition';
  protected $offsetDataType = '';
  public $originalBackup;
  protected $positionType = 'Google_Service_Dfareporting_OffsetPosition';
  protected $positionDataType = '';
  public $positionLeftUnit;
  public $positionTopUnit;
  public $progressiveServingUrl;
  public $pushdown;
  public $pushdownDuration;
  public $role;
  protected $sizeType = 'Google_Service_Dfareporting_Size';
  protected $sizeDataType = '';
  public $sslCompliant;
  public $startTimeType;
  public $streamingServingUrl;
  public $transparency;
  public $verticallyLocked;
  public $videoDuration;
  public $windowMode;
  public $zIndex;
  public $zipFilename;
  public $zipFilesize;


  public function setActionScript3($actionScript3)
  {
    $this->actionScript3 = $actionScript3;
  }
  public function getActionScript3()
  {
    return $this->actionScript3;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAlignment($alignment)
  {
    $this->alignment = $alignment;
  }
  public function getAlignment()
  {
    return $this->alignment;
  }
  public function setArtworkType($artworkType)
  {
    $this->artworkType = $artworkType;
  }
  public function getArtworkType()
  {
    return $this->artworkType;
  }
  public function setAssetIdentifier(Google_Service_Dfareporting_CreativeAssetId $assetIdentifier)
  {
    $this->assetIdentifier = $assetIdentifier;
  }
  public function getAssetIdentifier()
  {
    return $this->assetIdentifier;
  }
  public function setBackupImageExit(Google_Service_Dfareporting_CreativeCustomEvent $backupImageExit)
  {
    $this->backupImageExit = $backupImageExit;
  }
  public function getBackupImageExit()
  {
    return $this->backupImageExit;
  }
  public function setBitRate($bitRate)
  {
    $this->bitRate = $bitRate;
  }
  public function getBitRate()
  {
    return $this->bitRate;
  }
  public function setChildAssetType($childAssetType)
  {
    $this->childAssetType = $childAssetType;
  }
  public function getChildAssetType()
  {
    return $this->childAssetType;
  }
  public function setCollapsedSize(Google_Service_Dfareporting_Size $collapsedSize)
  {
    $this->collapsedSize = $collapsedSize;
  }
  public function getCollapsedSize()
  {
    return $this->collapsedSize;
  }
  public function setCustomStartTimeValue($customStartTimeValue)
  {
    $this->customStartTimeValue = $customStartTimeValue;
  }
  public function getCustomStartTimeValue()
  {
    return $this->customStartTimeValue;
  }
  public function setDetectedFeatures($detectedFeatures)
  {
    $this->detectedFeatures = $detectedFeatures;
  }
  public function getDetectedFeatures()
  {
    return $this->detectedFeatures;
  }
  public function setDisplayType($displayType)
  {
    $this->displayType = $displayType;
  }
  public function getDisplayType()
  {
    return $this->displayType;
  }
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setDurationType($durationType)
  {
    $this->durationType = $durationType;
  }
  public function getDurationType()
  {
    return $this->durationType;
  }
  public function setExpandedDimension(Google_Service_Dfareporting_Size $expandedDimension)
  {
    $this->expandedDimension = $expandedDimension;
  }
  public function getExpandedDimension()
  {
    return $this->expandedDimension;
  }
  public function setFileSize($fileSize)
  {
    $this->fileSize = $fileSize;
  }
  public function getFileSize()
  {
    return $this->fileSize;
  }
  public function setFlashVersion($flashVersion)
  {
    $this->flashVersion = $flashVersion;
  }
  public function getFlashVersion()
  {
    return $this->flashVersion;
  }
  public function setHideFlashObjects($hideFlashObjects)
  {
    $this->hideFlashObjects = $hideFlashObjects;
  }
  public function getHideFlashObjects()
  {
    return $this->hideFlashObjects;
  }
  public function setHideSelectionBoxes($hideSelectionBoxes)
  {
    $this->hideSelectionBoxes = $hideSelectionBoxes;
  }
  public function getHideSelectionBoxes()
  {
    return $this->hideSelectionBoxes;
  }
  public function setHorizontallyLocked($horizontallyLocked)
  {
    $this->horizontallyLocked = $horizontallyLocked;
  }
  public function getHorizontallyLocked()
  {
    return $this->horizontallyLocked;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setMimeType($mimeType)
  {
    $this->mimeType = $mimeType;
  }
  public function getMimeType()
  {
    return $this->mimeType;
  }
  public function setOffset(Google_Service_Dfareporting_OffsetPosition $offset)
  {
    $this->offset = $offset;
  }
  public function getOffset()
  {
    return $this->offset;
  }
  public function setOriginalBackup($originalBackup)
  {
    $this->originalBackup = $originalBackup;
  }
  public function getOriginalBackup()
  {
    return $this->originalBackup;
  }
  public function setPosition(Google_Service_Dfareporting_OffsetPosition $position)
  {
    $this->position = $position;
  }
  public function getPosition()
  {
    return $this->position;
  }
  public function setPositionLeftUnit($positionLeftUnit)
  {
    $this->positionLeftUnit = $positionLeftUnit;
  }
  public function getPositionLeftUnit()
  {
    return $this->positionLeftUnit;
  }
  public function setPositionTopUnit($positionTopUnit)
  {
    $this->positionTopUnit = $positionTopUnit;
  }
  public function getPositionTopUnit()
  {
    return $this->positionTopUnit;
  }
  public function setProgressiveServingUrl($progressiveServingUrl)
  {
    $this->progressiveServingUrl = $progressiveServingUrl;
  }
  public function getProgressiveServingUrl()
  {
    return $this->progressiveServingUrl;
  }
  public function setPushdown($pushdown)
  {
    $this->pushdown = $pushdown;
  }
  public function getPushdown()
  {
    return $this->pushdown;
  }
  public function setPushdownDuration($pushdownDuration)
  {
    $this->pushdownDuration = $pushdownDuration;
  }
  public function getPushdownDuration()
  {
    return $this->pushdownDuration;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  public function setSize(Google_Service_Dfareporting_Size $size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setStartTimeType($startTimeType)
  {
    $this->startTimeType = $startTimeType;
  }
  public function getStartTimeType()
  {
    return $this->startTimeType;
  }
  public function setStreamingServingUrl($streamingServingUrl)
  {
    $this->streamingServingUrl = $streamingServingUrl;
  }
  public function getStreamingServingUrl()
  {
    return $this->streamingServingUrl;
  }
  public function setTransparency($transparency)
  {
    $this->transparency = $transparency;
  }
  public function getTransparency()
  {
    return $this->transparency;
  }
  public function setVerticallyLocked($verticallyLocked)
  {
    $this->verticallyLocked = $verticallyLocked;
  }
  public function getVerticallyLocked()
  {
    return $this->verticallyLocked;
  }
  public function setVideoDuration($videoDuration)
  {
    $this->videoDuration = $videoDuration;
  }
  public function getVideoDuration()
  {
    return $this->videoDuration;
  }
  public function setWindowMode($windowMode)
  {
    $this->windowMode = $windowMode;
  }
  public function getWindowMode()
  {
    return $this->windowMode;
  }
  public function setZIndex($zIndex)
  {
    $this->zIndex = $zIndex;
  }
  public function getZIndex()
  {
    return $this->zIndex;
  }
  public function setZipFilename($zipFilename)
  {
    $this->zipFilename = $zipFilename;
  }
  public function getZipFilename()
  {
    return $this->zipFilename;
  }
  public function setZipFilesize($zipFilesize)
  {
    $this->zipFilesize = $zipFilesize;
  }
  public function getZipFilesize()
  {
    return $this->zipFilesize;
  }
}

class Google_Service_Dfareporting_CreativeAssetId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $type;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
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

class Google_Service_Dfareporting_CreativeAssetMetadata extends Google_Collection
{
  protected $collection_key = 'warnedValidationRules';
  protected $internal_gapi_mappings = array(
  );
  protected $assetIdentifierType = 'Google_Service_Dfareporting_CreativeAssetId';
  protected $assetIdentifierDataType = '';
  protected $clickTagsType = 'Google_Service_Dfareporting_ClickTag';
  protected $clickTagsDataType = 'array';
  public $detectedFeatures;
  public $kind;
  public $warnedValidationRules;


  public function setAssetIdentifier(Google_Service_Dfareporting_CreativeAssetId $assetIdentifier)
  {
    $this->assetIdentifier = $assetIdentifier;
  }
  public function getAssetIdentifier()
  {
    return $this->assetIdentifier;
  }
  public function setClickTags($clickTags)
  {
    $this->clickTags = $clickTags;
  }
  public function getClickTags()
  {
    return $this->clickTags;
  }
  public function setDetectedFeatures($detectedFeatures)
  {
    $this->detectedFeatures = $detectedFeatures;
  }
  public function getDetectedFeatures()
  {
    return $this->detectedFeatures;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setWarnedValidationRules($warnedValidationRules)
  {
    $this->warnedValidationRules = $warnedValidationRules;
  }
  public function getWarnedValidationRules()
  {
    return $this->warnedValidationRules;
  }
}

class Google_Service_Dfareporting_CreativeAssignment extends Google_Collection
{
  protected $collection_key = 'richMediaExitOverrides';
  protected $internal_gapi_mappings = array(
  );
  public $active;
  public $applyEventTags;
  protected $clickThroughUrlType = 'Google_Service_Dfareporting_ClickThroughUrl';
  protected $clickThroughUrlDataType = '';
  protected $companionCreativeOverridesType = 'Google_Service_Dfareporting_CompanionClickThroughOverride';
  protected $companionCreativeOverridesDataType = 'array';
  protected $creativeGroupAssignmentsType = 'Google_Service_Dfareporting_CreativeGroupAssignment';
  protected $creativeGroupAssignmentsDataType = 'array';
  public $creativeId;
  protected $creativeIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $creativeIdDimensionValueDataType = '';
  public $endTime;
  protected $richMediaExitOverridesType = 'Google_Service_Dfareporting_RichMediaExitOverride';
  protected $richMediaExitOverridesDataType = 'array';
  public $sequence;
  public $sslCompliant;
  public $startTime;
  public $weight;


  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setApplyEventTags($applyEventTags)
  {
    $this->applyEventTags = $applyEventTags;
  }
  public function getApplyEventTags()
  {
    return $this->applyEventTags;
  }
  public function setClickThroughUrl(Google_Service_Dfareporting_ClickThroughUrl $clickThroughUrl)
  {
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl()
  {
    return $this->clickThroughUrl;
  }
  public function setCompanionCreativeOverrides($companionCreativeOverrides)
  {
    $this->companionCreativeOverrides = $companionCreativeOverrides;
  }
  public function getCompanionCreativeOverrides()
  {
    return $this->companionCreativeOverrides;
  }
  public function setCreativeGroupAssignments($creativeGroupAssignments)
  {
    $this->creativeGroupAssignments = $creativeGroupAssignments;
  }
  public function getCreativeGroupAssignments()
  {
    return $this->creativeGroupAssignments;
  }
  public function setCreativeId($creativeId)
  {
    $this->creativeId = $creativeId;
  }
  public function getCreativeId()
  {
    return $this->creativeId;
  }
  public function setCreativeIdDimensionValue(Google_Service_Dfareporting_DimensionValue $creativeIdDimensionValue)
  {
    $this->creativeIdDimensionValue = $creativeIdDimensionValue;
  }
  public function getCreativeIdDimensionValue()
  {
    return $this->creativeIdDimensionValue;
  }
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setRichMediaExitOverrides($richMediaExitOverrides)
  {
    $this->richMediaExitOverrides = $richMediaExitOverrides;
  }
  public function getRichMediaExitOverrides()
  {
    return $this->richMediaExitOverrides;
  }
  public function setSequence($sequence)
  {
    $this->sequence = $sequence;
  }
  public function getSequence()
  {
    return $this->sequence;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
  public function setWeight($weight)
  {
    $this->weight = $weight;
  }
  public function getWeight()
  {
    return $this->weight;
  }
}

class Google_Service_Dfareporting_CreativeCustomEvent extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $active;
  public $advertiserCustomEventName;
  public $advertiserCustomEventType;
  public $artworkLabel;
  public $artworkType;
  public $exitUrl;
  public $id;
  protected $popupWindowPropertiesType = 'Google_Service_Dfareporting_PopupWindowProperties';
  protected $popupWindowPropertiesDataType = '';
  public $targetType;
  public $videoReportingId;


  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdvertiserCustomEventName($advertiserCustomEventName)
  {
    $this->advertiserCustomEventName = $advertiserCustomEventName;
  }
  public function getAdvertiserCustomEventName()
  {
    return $this->advertiserCustomEventName;
  }
  public function setAdvertiserCustomEventType($advertiserCustomEventType)
  {
    $this->advertiserCustomEventType = $advertiserCustomEventType;
  }
  public function getAdvertiserCustomEventType()
  {
    return $this->advertiserCustomEventType;
  }
  public function setArtworkLabel($artworkLabel)
  {
    $this->artworkLabel = $artworkLabel;
  }
  public function getArtworkLabel()
  {
    return $this->artworkLabel;
  }
  public function setArtworkType($artworkType)
  {
    $this->artworkType = $artworkType;
  }
  public function getArtworkType()
  {
    return $this->artworkType;
  }
  public function setExitUrl($exitUrl)
  {
    $this->exitUrl = $exitUrl;
  }
  public function getExitUrl()
  {
    return $this->exitUrl;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setPopupWindowProperties(Google_Service_Dfareporting_PopupWindowProperties $popupWindowProperties)
  {
    $this->popupWindowProperties = $popupWindowProperties;
  }
  public function getPopupWindowProperties()
  {
    return $this->popupWindowProperties;
  }
  public function setTargetType($targetType)
  {
    $this->targetType = $targetType;
  }
  public function getTargetType()
  {
    return $this->targetType;
  }
  public function setVideoReportingId($videoReportingId)
  {
    $this->videoReportingId = $videoReportingId;
  }
  public function getVideoReportingId()
  {
    return $this->videoReportingId;
  }
}

class Google_Service_Dfareporting_CreativeField extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $id;
  public $kind;
  public $name;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_CreativeFieldAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creativeFieldId;
  public $creativeFieldValueId;


  public function setCreativeFieldId($creativeFieldId)
  {
    $this->creativeFieldId = $creativeFieldId;
  }
  public function getCreativeFieldId()
  {
    return $this->creativeFieldId;
  }
  public function setCreativeFieldValueId($creativeFieldValueId)
  {
    $this->creativeFieldValueId = $creativeFieldValueId;
  }
  public function getCreativeFieldValueId()
  {
    return $this->creativeFieldValueId;
  }
}

class Google_Service_Dfareporting_CreativeFieldValue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $value;


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
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_Dfareporting_CreativeFieldValuesListResponse extends Google_Collection
{
  protected $collection_key = 'creativeFieldValues';
  protected $internal_gapi_mappings = array(
  );
  protected $creativeFieldValuesType = 'Google_Service_Dfareporting_CreativeFieldValue';
  protected $creativeFieldValuesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCreativeFieldValues($creativeFieldValues)
  {
    $this->creativeFieldValues = $creativeFieldValues;
  }
  public function getCreativeFieldValues()
  {
    return $this->creativeFieldValues;
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

class Google_Service_Dfareporting_CreativeFieldsListResponse extends Google_Collection
{
  protected $collection_key = 'creativeFields';
  protected $internal_gapi_mappings = array(
  );
  protected $creativeFieldsType = 'Google_Service_Dfareporting_CreativeField';
  protected $creativeFieldsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCreativeFields($creativeFields)
  {
    $this->creativeFields = $creativeFields;
  }
  public function getCreativeFields()
  {
    return $this->creativeFields;
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

class Google_Service_Dfareporting_CreativeGroup extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $groupNumber;
  public $id;
  public $kind;
  public $name;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setGroupNumber($groupNumber)
  {
    $this->groupNumber = $groupNumber;
  }
  public function getGroupNumber()
  {
    return $this->groupNumber;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_CreativeGroupAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creativeGroupId;
  public $creativeGroupNumber;


  public function setCreativeGroupId($creativeGroupId)
  {
    $this->creativeGroupId = $creativeGroupId;
  }
  public function getCreativeGroupId()
  {
    return $this->creativeGroupId;
  }
  public function setCreativeGroupNumber($creativeGroupNumber)
  {
    $this->creativeGroupNumber = $creativeGroupNumber;
  }
  public function getCreativeGroupNumber()
  {
    return $this->creativeGroupNumber;
  }
}

class Google_Service_Dfareporting_CreativeGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'creativeGroups';
  protected $internal_gapi_mappings = array(
  );
  protected $creativeGroupsType = 'Google_Service_Dfareporting_CreativeGroup';
  protected $creativeGroupsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCreativeGroups($creativeGroups)
  {
    $this->creativeGroups = $creativeGroups;
  }
  public function getCreativeGroups()
  {
    return $this->creativeGroups;
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

class Google_Service_Dfareporting_CreativeOptimizationConfiguration extends Google_Collection
{
  protected $collection_key = 'optimizationActivitys';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $name;
  protected $optimizationActivitysType = 'Google_Service_Dfareporting_OptimizationActivity';
  protected $optimizationActivitysDataType = 'array';
  public $optimizationModel;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOptimizationActivitys($optimizationActivitys)
  {
    $this->optimizationActivitys = $optimizationActivitys;
  }
  public function getOptimizationActivitys()
  {
    return $this->optimizationActivitys;
  }
  public function setOptimizationModel($optimizationModel)
  {
    $this->optimizationModel = $optimizationModel;
  }
  public function getOptimizationModel()
  {
    return $this->optimizationModel;
  }
}

class Google_Service_Dfareporting_CreativeRotation extends Google_Collection
{
  protected $collection_key = 'creativeAssignments';
  protected $internal_gapi_mappings = array(
  );
  protected $creativeAssignmentsType = 'Google_Service_Dfareporting_CreativeAssignment';
  protected $creativeAssignmentsDataType = 'array';
  public $creativeOptimizationConfigurationId;
  public $type;
  public $weightCalculationStrategy;


  public function setCreativeAssignments($creativeAssignments)
  {
    $this->creativeAssignments = $creativeAssignments;
  }
  public function getCreativeAssignments()
  {
    return $this->creativeAssignments;
  }
  public function setCreativeOptimizationConfigurationId($creativeOptimizationConfigurationId)
  {
    $this->creativeOptimizationConfigurationId = $creativeOptimizationConfigurationId;
  }
  public function getCreativeOptimizationConfigurationId()
  {
    return $this->creativeOptimizationConfigurationId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setWeightCalculationStrategy($weightCalculationStrategy)
  {
    $this->weightCalculationStrategy = $weightCalculationStrategy;
  }
  public function getWeightCalculationStrategy()
  {
    return $this->weightCalculationStrategy;
  }
}

class Google_Service_Dfareporting_CreativeSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $iFrameFooter;
  public $iFrameHeader;


  public function setIFrameFooter($iFrameFooter)
  {
    $this->iFrameFooter = $iFrameFooter;
  }
  public function getIFrameFooter()
  {
    return $this->iFrameFooter;
  }
  public function setIFrameHeader($iFrameHeader)
  {
    $this->iFrameHeader = $iFrameHeader;
  }
  public function getIFrameHeader()
  {
    return $this->iFrameHeader;
  }
}

class Google_Service_Dfareporting_CreativesListResponse extends Google_Collection
{
  protected $collection_key = 'creatives';
  protected $internal_gapi_mappings = array(
  );
  protected $creativesType = 'Google_Service_Dfareporting_Creative';
  protected $creativesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setCreatives($creatives)
  {
    $this->creatives = $creatives;
  }
  public function getCreatives()
  {
    return $this->creatives;
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

class Google_Service_Dfareporting_CrossDimensionReachReportCompatibleFields extends Google_Collection
{
  protected $collection_key = 'overlapMetrics';
  protected $internal_gapi_mappings = array(
  );
  protected $breakdownType = 'Google_Service_Dfareporting_Dimension';
  protected $breakdownDataType = 'array';
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionFiltersDataType = 'array';
  public $kind;
  protected $metricsType = 'Google_Service_Dfareporting_Metric';
  protected $metricsDataType = 'array';
  protected $overlapMetricsType = 'Google_Service_Dfareporting_Metric';
  protected $overlapMetricsDataType = 'array';


  public function setBreakdown($breakdown)
  {
    $this->breakdown = $breakdown;
  }
  public function getBreakdown()
  {
    return $this->breakdown;
  }
  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  public function setOverlapMetrics($overlapMetrics)
  {
    $this->overlapMetrics = $overlapMetrics;
  }
  public function getOverlapMetrics()
  {
    return $this->overlapMetrics;
  }
}

class Google_Service_Dfareporting_CustomRichMediaEvents extends Google_Collection
{
  protected $collection_key = 'filteredEventIds';
  protected $internal_gapi_mappings = array(
  );
  protected $filteredEventIdsType = 'Google_Service_Dfareporting_DimensionValue';
  protected $filteredEventIdsDataType = 'array';
  public $kind;


  public function setFilteredEventIds($filteredEventIds)
  {
    $this->filteredEventIds = $filteredEventIds;
  }
  public function getFilteredEventIds()
  {
    return $this->filteredEventIds;
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

class Google_Service_Dfareporting_DateRange extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endDate;
  public $kind;
  public $relativeDateRange;
  public $startDate;


  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRelativeDateRange($relativeDateRange)
  {
    $this->relativeDateRange = $relativeDateRange;
  }
  public function getRelativeDateRange()
  {
    return $this->relativeDateRange;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
}

class Google_Service_Dfareporting_DayPartTargeting extends Google_Collection
{
  protected $collection_key = 'hoursOfDay';
  protected $internal_gapi_mappings = array(
  );
  public $daysOfWeek;
  public $hoursOfDay;
  public $userLocalTime;


  public function setDaysOfWeek($daysOfWeek)
  {
    $this->daysOfWeek = $daysOfWeek;
  }
  public function getDaysOfWeek()
  {
    return $this->daysOfWeek;
  }
  public function setHoursOfDay($hoursOfDay)
  {
    $this->hoursOfDay = $hoursOfDay;
  }
  public function getHoursOfDay()
  {
    return $this->hoursOfDay;
  }
  public function setUserLocalTime($userLocalTime)
  {
    $this->userLocalTime = $userLocalTime;
  }
  public function getUserLocalTime()
  {
    return $this->userLocalTime;
  }
}

class Google_Service_Dfareporting_DefaultClickThroughEventTagProperties extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $defaultClickThroughEventTagId;
  public $overrideInheritedEventTag;


  public function setDefaultClickThroughEventTagId($defaultClickThroughEventTagId)
  {
    $this->defaultClickThroughEventTagId = $defaultClickThroughEventTagId;
  }
  public function getDefaultClickThroughEventTagId()
  {
    return $this->defaultClickThroughEventTagId;
  }
  public function setOverrideInheritedEventTag($overrideInheritedEventTag)
  {
    $this->overrideInheritedEventTag = $overrideInheritedEventTag;
  }
  public function getOverrideInheritedEventTag()
  {
    return $this->overrideInheritedEventTag;
  }
}

class Google_Service_Dfareporting_DeliverySchedule extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $frequencyCapType = 'Google_Service_Dfareporting_FrequencyCap';
  protected $frequencyCapDataType = '';
  public $hardCutoff;
  public $impressionRatio;
  public $priority;


  public function setFrequencyCap(Google_Service_Dfareporting_FrequencyCap $frequencyCap)
  {
    $this->frequencyCap = $frequencyCap;
  }
  public function getFrequencyCap()
  {
    return $this->frequencyCap;
  }
  public function setHardCutoff($hardCutoff)
  {
    $this->hardCutoff = $hardCutoff;
  }
  public function getHardCutoff()
  {
    return $this->hardCutoff;
  }
  public function setImpressionRatio($impressionRatio)
  {
    $this->impressionRatio = $impressionRatio;
  }
  public function getImpressionRatio()
  {
    return $this->impressionRatio;
  }
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  public function getPriority()
  {
    return $this->priority;
  }
}

class Google_Service_Dfareporting_DfareportingFile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  public $etag;
  public $fileName;
  public $format;
  public $id;
  public $kind;
  public $lastModifiedTime;
  public $reportId;
  public $status;
  protected $urlsType = 'Google_Service_Dfareporting_DfareportingFileUrls';
  protected $urlsDataType = '';


  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
  }
  public function getFileName()
  {
    return $this->fileName;
  }
  public function setFormat($format)
  {
    $this->format = $format;
  }
  public function getFormat()
  {
    return $this->format;
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
  public function setLastModifiedTime($lastModifiedTime)
  {
    $this->lastModifiedTime = $lastModifiedTime;
  }
  public function getLastModifiedTime()
  {
    return $this->lastModifiedTime;
  }
  public function setReportId($reportId)
  {
    $this->reportId = $reportId;
  }
  public function getReportId()
  {
    return $this->reportId;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setUrls(Google_Service_Dfareporting_DfareportingFileUrls $urls)
  {
    $this->urls = $urls;
  }
  public function getUrls()
  {
    return $this->urls;
  }
}

class Google_Service_Dfareporting_DfareportingFileUrls extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $apiUrl;
  public $browserUrl;


  public function setApiUrl($apiUrl)
  {
    $this->apiUrl = $apiUrl;
  }
  public function getApiUrl()
  {
    return $this->apiUrl;
  }
  public function setBrowserUrl($browserUrl)
  {
    $this->browserUrl = $browserUrl;
  }
  public function getBrowserUrl()
  {
    return $this->browserUrl;
  }
}

class Google_Service_Dfareporting_DfpSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "dfpNetworkCode" => "dfp_network_code",
        "dfpNetworkName" => "dfp_network_name",
  );
  public $dfpNetworkCode;
  public $dfpNetworkName;
  public $programmaticPlacementAccepted;
  public $pubPaidPlacementAccepted;
  public $publisherPortalOnly;


  public function setDfpNetworkCode($dfpNetworkCode)
  {
    $this->dfpNetworkCode = $dfpNetworkCode;
  }
  public function getDfpNetworkCode()
  {
    return $this->dfpNetworkCode;
  }
  public function setDfpNetworkName($dfpNetworkName)
  {
    $this->dfpNetworkName = $dfpNetworkName;
  }
  public function getDfpNetworkName()
  {
    return $this->dfpNetworkName;
  }
  public function setProgrammaticPlacementAccepted($programmaticPlacementAccepted)
  {
    $this->programmaticPlacementAccepted = $programmaticPlacementAccepted;
  }
  public function getProgrammaticPlacementAccepted()
  {
    return $this->programmaticPlacementAccepted;
  }
  public function setPubPaidPlacementAccepted($pubPaidPlacementAccepted)
  {
    $this->pubPaidPlacementAccepted = $pubPaidPlacementAccepted;
  }
  public function getPubPaidPlacementAccepted()
  {
    return $this->pubPaidPlacementAccepted;
  }
  public function setPublisherPortalOnly($publisherPortalOnly)
  {
    $this->publisherPortalOnly = $publisherPortalOnly;
  }
  public function getPublisherPortalOnly()
  {
    return $this->publisherPortalOnly;
  }
}

class Google_Service_Dfareporting_Dimension extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $name;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_Dfareporting_DimensionFilter extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dimensionName;
  public $kind;
  public $value;


  public function setDimensionName($dimensionName)
  {
    $this->dimensionName = $dimensionName;
  }
  public function getDimensionName()
  {
    return $this->dimensionName;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_Dfareporting_DimensionValue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dimensionName;
  public $etag;
  public $id;
  public $kind;
  public $matchType;
  public $value;


  public function setDimensionName($dimensionName)
  {
    $this->dimensionName = $dimensionName;
  }
  public function getDimensionName()
  {
    return $this->dimensionName;
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
  public function setMatchType($matchType)
  {
    $this->matchType = $matchType;
  }
  public function getMatchType()
  {
    return $this->matchType;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

class Google_Service_Dfareporting_DimensionValueList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Dfareporting_DimensionValue';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
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

class Google_Service_Dfareporting_DimensionValueRequest extends Google_Collection
{
  protected $collection_key = 'filters';
  protected $internal_gapi_mappings = array(
  );
  public $dimensionName;
  public $endDate;
  protected $filtersType = 'Google_Service_Dfareporting_DimensionFilter';
  protected $filtersDataType = 'array';
  public $kind;
  public $startDate;


  public function setDimensionName($dimensionName)
  {
    $this->dimensionName = $dimensionName;
  }
  public function getDimensionName()
  {
    return $this->dimensionName;
  }
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setFilters($filters)
  {
    $this->filters = $filters;
  }
  public function getFilters()
  {
    return $this->filters;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
}

class Google_Service_Dfareporting_DirectorySite extends Google_Collection
{
  protected $collection_key = 'interstitialTagFormats';
  protected $internal_gapi_mappings = array(
  );
  public $active;
  protected $contactAssignmentsType = 'Google_Service_Dfareporting_DirectorySiteContactAssignment';
  protected $contactAssignmentsDataType = 'array';
  public $countryId;
  public $currencyId;
  public $description;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $inpageTagFormats;
  public $interstitialTagFormats;
  public $kind;
  public $name;
  public $parentId;
  protected $settingsType = 'Google_Service_Dfareporting_DirectorySiteSettings';
  protected $settingsDataType = '';
  public $url;


  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setContactAssignments($contactAssignments)
  {
    $this->contactAssignments = $contactAssignments;
  }
  public function getContactAssignments()
  {
    return $this->contactAssignments;
  }
  public function setCountryId($countryId)
  {
    $this->countryId = $countryId;
  }
  public function getCountryId()
  {
    return $this->countryId;
  }
  public function setCurrencyId($currencyId)
  {
    $this->currencyId = $currencyId;
  }
  public function getCurrencyId()
  {
    return $this->currencyId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setInpageTagFormats($inpageTagFormats)
  {
    $this->inpageTagFormats = $inpageTagFormats;
  }
  public function getInpageTagFormats()
  {
    return $this->inpageTagFormats;
  }
  public function setInterstitialTagFormats($interstitialTagFormats)
  {
    $this->interstitialTagFormats = $interstitialTagFormats;
  }
  public function getInterstitialTagFormats()
  {
    return $this->interstitialTagFormats;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setParentId($parentId)
  {
    $this->parentId = $parentId;
  }
  public function getParentId()
  {
    return $this->parentId;
  }
  public function setSettings(Google_Service_Dfareporting_DirectorySiteSettings $settings)
  {
    $this->settings = $settings;
  }
  public function getSettings()
  {
    return $this->settings;
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

class Google_Service_Dfareporting_DirectorySiteContact extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $address;
  public $email;
  public $firstName;
  public $id;
  public $kind;
  public $lastName;
  public $phone;
  public $role;
  public $title;
  public $type;


  public function setAddress($address)
  {
    $this->address = $address;
  }
  public function getAddress()
  {
    return $this->address;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFirstName($firstName)
  {
    $this->firstName = $firstName;
  }
  public function getFirstName()
  {
    return $this->firstName;
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
  public function setLastName($lastName)
  {
    $this->lastName = $lastName;
  }
  public function getLastName()
  {
    return $this->lastName;
  }
  public function setPhone($phone)
  {
    $this->phone = $phone;
  }
  public function getPhone()
  {
    return $this->phone;
  }
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
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

class Google_Service_Dfareporting_DirectorySiteContactAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contactId;
  public $visibility;


  public function setContactId($contactId)
  {
    $this->contactId = $contactId;
  }
  public function getContactId()
  {
    return $this->contactId;
  }
  public function setVisibility($visibility)
  {
    $this->visibility = $visibility;
  }
  public function getVisibility()
  {
    return $this->visibility;
  }
}

class Google_Service_Dfareporting_DirectorySiteContactsListResponse extends Google_Collection
{
  protected $collection_key = 'directorySiteContacts';
  protected $internal_gapi_mappings = array(
  );
  protected $directorySiteContactsType = 'Google_Service_Dfareporting_DirectorySiteContact';
  protected $directorySiteContactsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setDirectorySiteContacts($directorySiteContacts)
  {
    $this->directorySiteContacts = $directorySiteContacts;
  }
  public function getDirectorySiteContacts()
  {
    return $this->directorySiteContacts;
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

class Google_Service_Dfareporting_DirectorySiteSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
        "dfpSettings" => "dfp_settings",
        "instreamVideoPlacementAccepted" => "instream_video_placement_accepted",
  );
  public $activeViewOptOut;
  protected $dfpSettingsType = 'Google_Service_Dfareporting_DfpSettings';
  protected $dfpSettingsDataType = '';
  public $instreamVideoPlacementAccepted;
  public $interstitialPlacementAccepted;
  public $nielsenOcrOptOut;
  public $verificationTagOptOut;
  public $videoActiveViewOptOut;


  public function setActiveViewOptOut($activeViewOptOut)
  {
    $this->activeViewOptOut = $activeViewOptOut;
  }
  public function getActiveViewOptOut()
  {
    return $this->activeViewOptOut;
  }
  public function setDfpSettings(Google_Service_Dfareporting_DfpSettings $dfpSettings)
  {
    $this->dfpSettings = $dfpSettings;
  }
  public function getDfpSettings()
  {
    return $this->dfpSettings;
  }
  public function setInstreamVideoPlacementAccepted($instreamVideoPlacementAccepted)
  {
    $this->instreamVideoPlacementAccepted = $instreamVideoPlacementAccepted;
  }
  public function getInstreamVideoPlacementAccepted()
  {
    return $this->instreamVideoPlacementAccepted;
  }
  public function setInterstitialPlacementAccepted($interstitialPlacementAccepted)
  {
    $this->interstitialPlacementAccepted = $interstitialPlacementAccepted;
  }
  public function getInterstitialPlacementAccepted()
  {
    return $this->interstitialPlacementAccepted;
  }
  public function setNielsenOcrOptOut($nielsenOcrOptOut)
  {
    $this->nielsenOcrOptOut = $nielsenOcrOptOut;
  }
  public function getNielsenOcrOptOut()
  {
    return $this->nielsenOcrOptOut;
  }
  public function setVerificationTagOptOut($verificationTagOptOut)
  {
    $this->verificationTagOptOut = $verificationTagOptOut;
  }
  public function getVerificationTagOptOut()
  {
    return $this->verificationTagOptOut;
  }
  public function setVideoActiveViewOptOut($videoActiveViewOptOut)
  {
    $this->videoActiveViewOptOut = $videoActiveViewOptOut;
  }
  public function getVideoActiveViewOptOut()
  {
    return $this->videoActiveViewOptOut;
  }
}

class Google_Service_Dfareporting_DirectorySitesListResponse extends Google_Collection
{
  protected $collection_key = 'directorySites';
  protected $internal_gapi_mappings = array(
  );
  protected $directorySitesType = 'Google_Service_Dfareporting_DirectorySite';
  protected $directorySitesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setDirectorySites($directorySites)
  {
    $this->directorySites = $directorySites;
  }
  public function getDirectorySites()
  {
    return $this->directorySites;
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

class Google_Service_Dfareporting_EventTag extends Google_Collection
{
  protected $collection_key = 'siteIds';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $campaignId;
  protected $campaignIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $campaignIdDimensionValueDataType = '';
  public $enabledByDefault;
  public $id;
  public $kind;
  public $name;
  public $siteFilterType;
  public $siteIds;
  public $sslCompliant;
  public $status;
  public $subaccountId;
  public $type;
  public $url;
  public $urlEscapeLevels;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setCampaignId($campaignId)
  {
    $this->campaignId = $campaignId;
  }
  public function getCampaignId()
  {
    return $this->campaignId;
  }
  public function setCampaignIdDimensionValue(Google_Service_Dfareporting_DimensionValue $campaignIdDimensionValue)
  {
    $this->campaignIdDimensionValue = $campaignIdDimensionValue;
  }
  public function getCampaignIdDimensionValue()
  {
    return $this->campaignIdDimensionValue;
  }
  public function setEnabledByDefault($enabledByDefault)
  {
    $this->enabledByDefault = $enabledByDefault;
  }
  public function getEnabledByDefault()
  {
    return $this->enabledByDefault;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSiteFilterType($siteFilterType)
  {
    $this->siteFilterType = $siteFilterType;
  }
  public function getSiteFilterType()
  {
    return $this->siteFilterType;
  }
  public function setSiteIds($siteIds)
  {
    $this->siteIds = $siteIds;
  }
  public function getSiteIds()
  {
    return $this->siteIds;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
  public function setUrlEscapeLevels($urlEscapeLevels)
  {
    $this->urlEscapeLevels = $urlEscapeLevels;
  }
  public function getUrlEscapeLevels()
  {
    return $this->urlEscapeLevels;
  }
}

class Google_Service_Dfareporting_EventTagOverride extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $enabled;
  public $id;


  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  public function getEnabled()
  {
    return $this->enabled;
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

class Google_Service_Dfareporting_EventTagsListResponse extends Google_Collection
{
  protected $collection_key = 'eventTags';
  protected $internal_gapi_mappings = array(
  );
  protected $eventTagsType = 'Google_Service_Dfareporting_EventTag';
  protected $eventTagsDataType = 'array';
  public $kind;


  public function setEventTags($eventTags)
  {
    $this->eventTags = $eventTags;
  }
  public function getEventTags()
  {
    return $this->eventTags;
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

class Google_Service_Dfareporting_FileList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Dfareporting_DfareportingFile';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
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

class Google_Service_Dfareporting_Flight extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endDate;
  public $rateOrCost;
  public $startDate;
  public $units;


  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setRateOrCost($rateOrCost)
  {
    $this->rateOrCost = $rateOrCost;
  }
  public function getRateOrCost()
  {
    return $this->rateOrCost;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setUnits($units)
  {
    $this->units = $units;
  }
  public function getUnits()
  {
    return $this->units;
  }
}

class Google_Service_Dfareporting_FloodlightActivitiesGenerateTagResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $floodlightActivityTag;
  public $kind;


  public function setFloodlightActivityTag($floodlightActivityTag)
  {
    $this->floodlightActivityTag = $floodlightActivityTag;
  }
  public function getFloodlightActivityTag()
  {
    return $this->floodlightActivityTag;
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

class Google_Service_Dfareporting_FloodlightActivitiesListResponse extends Google_Collection
{
  protected $collection_key = 'floodlightActivities';
  protected $internal_gapi_mappings = array(
  );
  protected $floodlightActivitiesType = 'Google_Service_Dfareporting_FloodlightActivity';
  protected $floodlightActivitiesDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setFloodlightActivities($floodlightActivities)
  {
    $this->floodlightActivities = $floodlightActivities;
  }
  public function getFloodlightActivities()
  {
    return $this->floodlightActivities;
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

class Google_Service_Dfareporting_FloodlightActivity extends Google_Collection
{
  protected $collection_key = 'userDefinedVariableTypes';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $cacheBustingType;
  public $countingMethod;
  protected $defaultTagsType = 'Google_Service_Dfareporting_FloodlightActivityDynamicTag';
  protected $defaultTagsDataType = 'array';
  public $expectedUrl;
  public $floodlightActivityGroupId;
  public $floodlightActivityGroupName;
  public $floodlightActivityGroupTagString;
  public $floodlightActivityGroupType;
  public $floodlightConfigurationId;
  protected $floodlightConfigurationIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightConfigurationIdDimensionValueDataType = '';
  public $hidden;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $imageTagEnabled;
  public $kind;
  public $name;
  public $notes;
  protected $publisherTagsType = 'Google_Service_Dfareporting_FloodlightActivityPublisherDynamicTag';
  protected $publisherTagsDataType = 'array';
  public $secure;
  public $sslCompliant;
  public $sslRequired;
  public $subaccountId;
  public $tagFormat;
  public $tagString;
  public $userDefinedVariableTypes;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setCacheBustingType($cacheBustingType)
  {
    $this->cacheBustingType = $cacheBustingType;
  }
  public function getCacheBustingType()
  {
    return $this->cacheBustingType;
  }
  public function setCountingMethod($countingMethod)
  {
    $this->countingMethod = $countingMethod;
  }
  public function getCountingMethod()
  {
    return $this->countingMethod;
  }
  public function setDefaultTags($defaultTags)
  {
    $this->defaultTags = $defaultTags;
  }
  public function getDefaultTags()
  {
    return $this->defaultTags;
  }
  public function setExpectedUrl($expectedUrl)
  {
    $this->expectedUrl = $expectedUrl;
  }
  public function getExpectedUrl()
  {
    return $this->expectedUrl;
  }
  public function setFloodlightActivityGroupId($floodlightActivityGroupId)
  {
    $this->floodlightActivityGroupId = $floodlightActivityGroupId;
  }
  public function getFloodlightActivityGroupId()
  {
    return $this->floodlightActivityGroupId;
  }
  public function setFloodlightActivityGroupName($floodlightActivityGroupName)
  {
    $this->floodlightActivityGroupName = $floodlightActivityGroupName;
  }
  public function getFloodlightActivityGroupName()
  {
    return $this->floodlightActivityGroupName;
  }
  public function setFloodlightActivityGroupTagString($floodlightActivityGroupTagString)
  {
    $this->floodlightActivityGroupTagString = $floodlightActivityGroupTagString;
  }
  public function getFloodlightActivityGroupTagString()
  {
    return $this->floodlightActivityGroupTagString;
  }
  public function setFloodlightActivityGroupType($floodlightActivityGroupType)
  {
    $this->floodlightActivityGroupType = $floodlightActivityGroupType;
  }
  public function getFloodlightActivityGroupType()
  {
    return $this->floodlightActivityGroupType;
  }
  public function setFloodlightConfigurationId($floodlightConfigurationId)
  {
    $this->floodlightConfigurationId = $floodlightConfigurationId;
  }
  public function getFloodlightConfigurationId()
  {
    return $this->floodlightConfigurationId;
  }
  public function setFloodlightConfigurationIdDimensionValue(Google_Service_Dfareporting_DimensionValue $floodlightConfigurationIdDimensionValue)
  {
    $this->floodlightConfigurationIdDimensionValue = $floodlightConfigurationIdDimensionValue;
  }
  public function getFloodlightConfigurationIdDimensionValue()
  {
    return $this->floodlightConfigurationIdDimensionValue;
  }
  public function setHidden($hidden)
  {
    $this->hidden = $hidden;
  }
  public function getHidden()
  {
    return $this->hidden;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setImageTagEnabled($imageTagEnabled)
  {
    $this->imageTagEnabled = $imageTagEnabled;
  }
  public function getImageTagEnabled()
  {
    return $this->imageTagEnabled;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setPublisherTags($publisherTags)
  {
    $this->publisherTags = $publisherTags;
  }
  public function getPublisherTags()
  {
    return $this->publisherTags;
  }
  public function setSecure($secure)
  {
    $this->secure = $secure;
  }
  public function getSecure()
  {
    return $this->secure;
  }
  public function setSslCompliant($sslCompliant)
  {
    $this->sslCompliant = $sslCompliant;
  }
  public function getSslCompliant()
  {
    return $this->sslCompliant;
  }
  public function setSslRequired($sslRequired)
  {
    $this->sslRequired = $sslRequired;
  }
  public function getSslRequired()
  {
    return $this->sslRequired;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTagFormat($tagFormat)
  {
    $this->tagFormat = $tagFormat;
  }
  public function getTagFormat()
  {
    return $this->tagFormat;
  }
  public function setTagString($tagString)
  {
    $this->tagString = $tagString;
  }
  public function getTagString()
  {
    return $this->tagString;
  }
  public function setUserDefinedVariableTypes($userDefinedVariableTypes)
  {
    $this->userDefinedVariableTypes = $userDefinedVariableTypes;
  }
  public function getUserDefinedVariableTypes()
  {
    return $this->userDefinedVariableTypes;
  }
}

class Google_Service_Dfareporting_FloodlightActivityDynamicTag extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $name;
  public $tag;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setTag($tag)
  {
    $this->tag = $tag;
  }
  public function getTag()
  {
    return $this->tag;
  }
}

class Google_Service_Dfareporting_FloodlightActivityGroup extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $floodlightConfigurationId;
  protected $floodlightConfigurationIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightConfigurationIdDimensionValueDataType = '';
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  public $name;
  public $subaccountId;
  public $tagString;
  public $type;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setFloodlightConfigurationId($floodlightConfigurationId)
  {
    $this->floodlightConfigurationId = $floodlightConfigurationId;
  }
  public function getFloodlightConfigurationId()
  {
    return $this->floodlightConfigurationId;
  }
  public function setFloodlightConfigurationIdDimensionValue(Google_Service_Dfareporting_DimensionValue $floodlightConfigurationIdDimensionValue)
  {
    $this->floodlightConfigurationIdDimensionValue = $floodlightConfigurationIdDimensionValue;
  }
  public function getFloodlightConfigurationIdDimensionValue()
  {
    return $this->floodlightConfigurationIdDimensionValue;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTagString($tagString)
  {
    $this->tagString = $tagString;
  }
  public function getTagString()
  {
    return $this->tagString;
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

class Google_Service_Dfareporting_FloodlightActivityGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'floodlightActivityGroups';
  protected $internal_gapi_mappings = array(
  );
  protected $floodlightActivityGroupsType = 'Google_Service_Dfareporting_FloodlightActivityGroup';
  protected $floodlightActivityGroupsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setFloodlightActivityGroups($floodlightActivityGroups)
  {
    $this->floodlightActivityGroups = $floodlightActivityGroups;
  }
  public function getFloodlightActivityGroups()
  {
    return $this->floodlightActivityGroups;
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

class Google_Service_Dfareporting_FloodlightActivityPublisherDynamicTag extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $clickThrough;
  public $directorySiteId;
  protected $dynamicTagType = 'Google_Service_Dfareporting_FloodlightActivityDynamicTag';
  protected $dynamicTagDataType = '';
  public $siteId;
  protected $siteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $siteIdDimensionValueDataType = '';
  public $viewThrough;


  public function setClickThrough($clickThrough)
  {
    $this->clickThrough = $clickThrough;
  }
  public function getClickThrough()
  {
    return $this->clickThrough;
  }
  public function setDirectorySiteId($directorySiteId)
  {
    $this->directorySiteId = $directorySiteId;
  }
  public function getDirectorySiteId()
  {
    return $this->directorySiteId;
  }
  public function setDynamicTag(Google_Service_Dfareporting_FloodlightActivityDynamicTag $dynamicTag)
  {
    $this->dynamicTag = $dynamicTag;
  }
  public function getDynamicTag()
  {
    return $this->dynamicTag;
  }
  public function setSiteId($siteId)
  {
    $this->siteId = $siteId;
  }
  public function getSiteId()
  {
    return $this->siteId;
  }
  public function setSiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $siteIdDimensionValue)
  {
    $this->siteIdDimensionValue = $siteIdDimensionValue;
  }
  public function getSiteIdDimensionValue()
  {
    return $this->siteIdDimensionValue;
  }
  public function setViewThrough($viewThrough)
  {
    $this->viewThrough = $viewThrough;
  }
  public function getViewThrough()
  {
    return $this->viewThrough;
  }
}

class Google_Service_Dfareporting_FloodlightConfiguration extends Google_Collection
{
  protected $collection_key = 'userDefinedVariableConfigurations';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $analyticsDataSharingEnabled;
  public $exposureToConversionEnabled;
  public $firstDayOfWeek;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  public $naturalSearchConversionAttributionOption;
  protected $omnitureSettingsType = 'Google_Service_Dfareporting_OmnitureSettings';
  protected $omnitureSettingsDataType = '';
  public $sslRequired;
  public $standardVariableTypes;
  public $subaccountId;
  protected $tagSettingsType = 'Google_Service_Dfareporting_TagSettings';
  protected $tagSettingsDataType = '';
  protected $userDefinedVariableConfigurationsType = 'Google_Service_Dfareporting_UserDefinedVariableConfiguration';
  protected $userDefinedVariableConfigurationsDataType = 'array';


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setAnalyticsDataSharingEnabled($analyticsDataSharingEnabled)
  {
    $this->analyticsDataSharingEnabled = $analyticsDataSharingEnabled;
  }
  public function getAnalyticsDataSharingEnabled()
  {
    return $this->analyticsDataSharingEnabled;
  }
  public function setExposureToConversionEnabled($exposureToConversionEnabled)
  {
    $this->exposureToConversionEnabled = $exposureToConversionEnabled;
  }
  public function getExposureToConversionEnabled()
  {
    return $this->exposureToConversionEnabled;
  }
  public function setFirstDayOfWeek($firstDayOfWeek)
  {
    $this->firstDayOfWeek = $firstDayOfWeek;
  }
  public function getFirstDayOfWeek()
  {
    return $this->firstDayOfWeek;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setNaturalSearchConversionAttributionOption($naturalSearchConversionAttributionOption)
  {
    $this->naturalSearchConversionAttributionOption = $naturalSearchConversionAttributionOption;
  }
  public function getNaturalSearchConversionAttributionOption()
  {
    return $this->naturalSearchConversionAttributionOption;
  }
  public function setOmnitureSettings(Google_Service_Dfareporting_OmnitureSettings $omnitureSettings)
  {
    $this->omnitureSettings = $omnitureSettings;
  }
  public function getOmnitureSettings()
  {
    return $this->omnitureSettings;
  }
  public function setSslRequired($sslRequired)
  {
    $this->sslRequired = $sslRequired;
  }
  public function getSslRequired()
  {
    return $this->sslRequired;
  }
  public function setStandardVariableTypes($standardVariableTypes)
  {
    $this->standardVariableTypes = $standardVariableTypes;
  }
  public function getStandardVariableTypes()
  {
    return $this->standardVariableTypes;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTagSettings(Google_Service_Dfareporting_TagSettings $tagSettings)
  {
    $this->tagSettings = $tagSettings;
  }
  public function getTagSettings()
  {
    return $this->tagSettings;
  }
  public function setUserDefinedVariableConfigurations($userDefinedVariableConfigurations)
  {
    $this->userDefinedVariableConfigurations = $userDefinedVariableConfigurations;
  }
  public function getUserDefinedVariableConfigurations()
  {
    return $this->userDefinedVariableConfigurations;
  }
}

class Google_Service_Dfareporting_FloodlightConfigurationsListResponse extends Google_Collection
{
  protected $collection_key = 'floodlightConfigurations';
  protected $internal_gapi_mappings = array(
  );
  protected $floodlightConfigurationsType = 'Google_Service_Dfareporting_FloodlightConfiguration';
  protected $floodlightConfigurationsDataType = 'array';
  public $kind;


  public function setFloodlightConfigurations($floodlightConfigurations)
  {
    $this->floodlightConfigurations = $floodlightConfigurations;
  }
  public function getFloodlightConfigurations()
  {
    return $this->floodlightConfigurations;
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

class Google_Service_Dfareporting_FloodlightReportCompatibleFields extends Google_Collection
{
  protected $collection_key = 'metrics';
  protected $internal_gapi_mappings = array(
  );
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionsDataType = 'array';
  public $kind;
  protected $metricsType = 'Google_Service_Dfareporting_Metric';
  protected $metricsDataType = 'array';


  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
}

class Google_Service_Dfareporting_FrequencyCap extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $duration;
  public $impressions;


  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  public function getDuration()
  {
    return $this->duration;
  }
  public function setImpressions($impressions)
  {
    $this->impressions = $impressions;
  }
  public function getImpressions()
  {
    return $this->impressions;
  }
}

class Google_Service_Dfareporting_FsCommand extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $left;
  public $positionOption;
  public $top;
  public $windowHeight;
  public $windowWidth;


  public function setLeft($left)
  {
    $this->left = $left;
  }
  public function getLeft()
  {
    return $this->left;
  }
  public function setPositionOption($positionOption)
  {
    $this->positionOption = $positionOption;
  }
  public function getPositionOption()
  {
    return $this->positionOption;
  }
  public function setTop($top)
  {
    $this->top = $top;
  }
  public function getTop()
  {
    return $this->top;
  }
  public function setWindowHeight($windowHeight)
  {
    $this->windowHeight = $windowHeight;
  }
  public function getWindowHeight()
  {
    return $this->windowHeight;
  }
  public function setWindowWidth($windowWidth)
  {
    $this->windowWidth = $windowWidth;
  }
  public function getWindowWidth()
  {
    return $this->windowWidth;
  }
}

class Google_Service_Dfareporting_GeoTargeting extends Google_Collection
{
  protected $collection_key = 'regions';
  protected $internal_gapi_mappings = array(
  );
  protected $citiesType = 'Google_Service_Dfareporting_City';
  protected $citiesDataType = 'array';
  protected $countriesType = 'Google_Service_Dfareporting_Country';
  protected $countriesDataType = 'array';
  public $excludeCountries;
  protected $metrosType = 'Google_Service_Dfareporting_Metro';
  protected $metrosDataType = 'array';
  protected $postalCodesType = 'Google_Service_Dfareporting_PostalCode';
  protected $postalCodesDataType = 'array';
  protected $regionsType = 'Google_Service_Dfareporting_Region';
  protected $regionsDataType = 'array';


  public function setCities($cities)
  {
    $this->cities = $cities;
  }
  public function getCities()
  {
    return $this->cities;
  }
  public function setCountries($countries)
  {
    $this->countries = $countries;
  }
  public function getCountries()
  {
    return $this->countries;
  }
  public function setExcludeCountries($excludeCountries)
  {
    $this->excludeCountries = $excludeCountries;
  }
  public function getExcludeCountries()
  {
    return $this->excludeCountries;
  }
  public function setMetros($metros)
  {
    $this->metros = $metros;
  }
  public function getMetros()
  {
    return $this->metros;
  }
  public function setPostalCodes($postalCodes)
  {
    $this->postalCodes = $postalCodes;
  }
  public function getPostalCodes()
  {
    return $this->postalCodes;
  }
  public function setRegions($regions)
  {
    $this->regions = $regions;
  }
  public function getRegions()
  {
    return $this->regions;
  }
}

class Google_Service_Dfareporting_InventoryItem extends Google_Collection
{
  protected $collection_key = 'adSlots';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  protected $adSlotsType = 'Google_Service_Dfareporting_AdSlot';
  protected $adSlotsDataType = 'array';
  public $advertiserId;
  public $contentCategoryId;
  public $estimatedClickThroughRate;
  public $estimatedConversionRate;
  public $id;
  public $inPlan;
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $name;
  public $negotiationChannelId;
  public $orderId;
  public $placementStrategyId;
  protected $pricingType = 'Google_Service_Dfareporting_Pricing';
  protected $pricingDataType = '';
  public $projectId;
  public $rfpId;
  public $siteId;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdSlots($adSlots)
  {
    $this->adSlots = $adSlots;
  }
  public function getAdSlots()
  {
    return $this->adSlots;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setContentCategoryId($contentCategoryId)
  {
    $this->contentCategoryId = $contentCategoryId;
  }
  public function getContentCategoryId()
  {
    return $this->contentCategoryId;
  }
  public function setEstimatedClickThroughRate($estimatedClickThroughRate)
  {
    $this->estimatedClickThroughRate = $estimatedClickThroughRate;
  }
  public function getEstimatedClickThroughRate()
  {
    return $this->estimatedClickThroughRate;
  }
  public function setEstimatedConversionRate($estimatedConversionRate)
  {
    $this->estimatedConversionRate = $estimatedConversionRate;
  }
  public function getEstimatedConversionRate()
  {
    return $this->estimatedConversionRate;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInPlan($inPlan)
  {
    $this->inPlan = $inPlan;
  }
  public function getInPlan()
  {
    return $this->inPlan;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNegotiationChannelId($negotiationChannelId)
  {
    $this->negotiationChannelId = $negotiationChannelId;
  }
  public function getNegotiationChannelId()
  {
    return $this->negotiationChannelId;
  }
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setPlacementStrategyId($placementStrategyId)
  {
    $this->placementStrategyId = $placementStrategyId;
  }
  public function getPlacementStrategyId()
  {
    return $this->placementStrategyId;
  }
  public function setPricing(Google_Service_Dfareporting_Pricing $pricing)
  {
    $this->pricing = $pricing;
  }
  public function getPricing()
  {
    return $this->pricing;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setRfpId($rfpId)
  {
    $this->rfpId = $rfpId;
  }
  public function getRfpId()
  {
    return $this->rfpId;
  }
  public function setSiteId($siteId)
  {
    $this->siteId = $siteId;
  }
  public function getSiteId()
  {
    return $this->siteId;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_InventoryItemsListResponse extends Google_Collection
{
  protected $collection_key = 'inventoryItems';
  protected $internal_gapi_mappings = array(
  );
  protected $inventoryItemsType = 'Google_Service_Dfareporting_InventoryItem';
  protected $inventoryItemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setInventoryItems($inventoryItems)
  {
    $this->inventoryItems = $inventoryItems;
  }
  public function getInventoryItems()
  {
    return $this->inventoryItems;
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

class Google_Service_Dfareporting_KeyValueTargetingExpression extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $expression;


  public function setExpression($expression)
  {
    $this->expression = $expression;
  }
  public function getExpression()
  {
    return $this->expression;
  }
}

class Google_Service_Dfareporting_LandingPage extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $default;
  public $id;
  public $kind;
  public $name;
  public $url;


  public function setDefault($default)
  {
    $this->default = $default;
  }
  public function getDefault()
  {
    return $this->default;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
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

class Google_Service_Dfareporting_LandingPagesListResponse extends Google_Collection
{
  protected $collection_key = 'landingPages';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $landingPagesType = 'Google_Service_Dfareporting_LandingPage';
  protected $landingPagesDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLandingPages($landingPages)
  {
    $this->landingPages = $landingPages;
  }
  public function getLandingPages()
  {
    return $this->landingPages;
  }
}

class Google_Service_Dfareporting_LastModifiedInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $time;


  public function setTime($time)
  {
    $this->time = $time;
  }
  public function getTime()
  {
    return $this->time;
  }
}

class Google_Service_Dfareporting_ListPopulationClause extends Google_Collection
{
  protected $collection_key = 'terms';
  protected $internal_gapi_mappings = array(
  );
  protected $termsType = 'Google_Service_Dfareporting_ListPopulationTerm';
  protected $termsDataType = 'array';


  public function setTerms($terms)
  {
    $this->terms = $terms;
  }
  public function getTerms()
  {
    return $this->terms;
  }
}

class Google_Service_Dfareporting_ListPopulationRule extends Google_Collection
{
  protected $collection_key = 'listPopulationClauses';
  protected $internal_gapi_mappings = array(
  );
  public $floodlightActivityId;
  public $floodlightActivityName;
  protected $listPopulationClausesType = 'Google_Service_Dfareporting_ListPopulationClause';
  protected $listPopulationClausesDataType = 'array';


  public function setFloodlightActivityId($floodlightActivityId)
  {
    $this->floodlightActivityId = $floodlightActivityId;
  }
  public function getFloodlightActivityId()
  {
    return $this->floodlightActivityId;
  }
  public function setFloodlightActivityName($floodlightActivityName)
  {
    $this->floodlightActivityName = $floodlightActivityName;
  }
  public function getFloodlightActivityName()
  {
    return $this->floodlightActivityName;
  }
  public function setListPopulationClauses($listPopulationClauses)
  {
    $this->listPopulationClauses = $listPopulationClauses;
  }
  public function getListPopulationClauses()
  {
    return $this->listPopulationClauses;
  }
}

class Google_Service_Dfareporting_ListPopulationTerm extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contains;
  public $negation;
  public $operator;
  public $remarketingListId;
  public $type;
  public $value;
  public $variableFriendlyName;
  public $variableName;


  public function setContains($contains)
  {
    $this->contains = $contains;
  }
  public function getContains()
  {
    return $this->contains;
  }
  public function setNegation($negation)
  {
    $this->negation = $negation;
  }
  public function getNegation()
  {
    return $this->negation;
  }
  public function setOperator($operator)
  {
    $this->operator = $operator;
  }
  public function getOperator()
  {
    return $this->operator;
  }
  public function setRemarketingListId($remarketingListId)
  {
    $this->remarketingListId = $remarketingListId;
  }
  public function getRemarketingListId()
  {
    return $this->remarketingListId;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
  public function setVariableFriendlyName($variableFriendlyName)
  {
    $this->variableFriendlyName = $variableFriendlyName;
  }
  public function getVariableFriendlyName()
  {
    return $this->variableFriendlyName;
  }
  public function setVariableName($variableName)
  {
    $this->variableName = $variableName;
  }
  public function getVariableName()
  {
    return $this->variableName;
  }
}

class Google_Service_Dfareporting_ListTargetingExpression extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $expression;


  public function setExpression($expression)
  {
    $this->expression = $expression;
  }
  public function getExpression()
  {
    return $this->expression;
  }
}

class Google_Service_Dfareporting_LookbackConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $clickDuration;
  public $postImpressionActivitiesDuration;


  public function setClickDuration($clickDuration)
  {
    $this->clickDuration = $clickDuration;
  }
  public function getClickDuration()
  {
    return $this->clickDuration;
  }
  public function setPostImpressionActivitiesDuration($postImpressionActivitiesDuration)
  {
    $this->postImpressionActivitiesDuration = $postImpressionActivitiesDuration;
  }
  public function getPostImpressionActivitiesDuration()
  {
    return $this->postImpressionActivitiesDuration;
  }
}

class Google_Service_Dfareporting_Metric extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $name;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
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

class Google_Service_Dfareporting_Metro extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $countryCode;
  public $countryDartId;
  public $dartId;
  public $dmaId;
  public $kind;
  public $metroCode;
  public $name;


  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCountryDartId($countryDartId)
  {
    $this->countryDartId = $countryDartId;
  }
  public function getCountryDartId()
  {
    return $this->countryDartId;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setDmaId($dmaId)
  {
    $this->dmaId = $dmaId;
  }
  public function getDmaId()
  {
    return $this->dmaId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetroCode($metroCode)
  {
    $this->metroCode = $metroCode;
  }
  public function getMetroCode()
  {
    return $this->metroCode;
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

class Google_Service_Dfareporting_MetrosListResponse extends Google_Collection
{
  protected $collection_key = 'metros';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $metrosType = 'Google_Service_Dfareporting_Metro';
  protected $metrosDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetros($metros)
  {
    $this->metros = $metros;
  }
  public function getMetros()
  {
    return $this->metros;
  }
}

class Google_Service_Dfareporting_MobileCarrier extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $countryCode;
  public $countryDartId;
  public $id;
  public $kind;
  public $name;


  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCountryDartId($countryDartId)
  {
    $this->countryDartId = $countryDartId;
  }
  public function getCountryDartId()
  {
    return $this->countryDartId;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_MobileCarriersListResponse extends Google_Collection
{
  protected $collection_key = 'mobileCarriers';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $mobileCarriersType = 'Google_Service_Dfareporting_MobileCarrier';
  protected $mobileCarriersDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMobileCarriers($mobileCarriers)
  {
    $this->mobileCarriers = $mobileCarriers;
  }
  public function getMobileCarriers()
  {
    return $this->mobileCarriers;
  }
}

class Google_Service_Dfareporting_ObjectFilter extends Google_Collection
{
  protected $collection_key = 'objectIds';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $objectIds;
  public $status;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setObjectIds($objectIds)
  {
    $this->objectIds = $objectIds;
  }
  public function getObjectIds()
  {
    return $this->objectIds;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_Dfareporting_OffsetPosition extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $left;
  public $top;


  public function setLeft($left)
  {
    $this->left = $left;
  }
  public function getLeft()
  {
    return $this->left;
  }
  public function setTop($top)
  {
    $this->top = $top;
  }
  public function getTop()
  {
    return $this->top;
  }
}

class Google_Service_Dfareporting_OmnitureSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $omnitureCostDataEnabled;
  public $omnitureIntegrationEnabled;


  public function setOmnitureCostDataEnabled($omnitureCostDataEnabled)
  {
    $this->omnitureCostDataEnabled = $omnitureCostDataEnabled;
  }
  public function getOmnitureCostDataEnabled()
  {
    return $this->omnitureCostDataEnabled;
  }
  public function setOmnitureIntegrationEnabled($omnitureIntegrationEnabled)
  {
    $this->omnitureIntegrationEnabled = $omnitureIntegrationEnabled;
  }
  public function getOmnitureIntegrationEnabled()
  {
    return $this->omnitureIntegrationEnabled;
  }
}

class Google_Service_Dfareporting_OperatingSystem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dartId;
  public $desktop;
  public $kind;
  public $mobile;
  public $name;


  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setDesktop($desktop)
  {
    $this->desktop = $desktop;
  }
  public function getDesktop()
  {
    return $this->desktop;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMobile($mobile)
  {
    $this->mobile = $mobile;
  }
  public function getMobile()
  {
    return $this->mobile;
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

class Google_Service_Dfareporting_OperatingSystemVersion extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $majorVersion;
  public $minorVersion;
  public $name;
  protected $operatingSystemType = 'Google_Service_Dfareporting_OperatingSystem';
  protected $operatingSystemDataType = '';


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
  public function setMajorVersion($majorVersion)
  {
    $this->majorVersion = $majorVersion;
  }
  public function getMajorVersion()
  {
    return $this->majorVersion;
  }
  public function setMinorVersion($minorVersion)
  {
    $this->minorVersion = $minorVersion;
  }
  public function getMinorVersion()
  {
    return $this->minorVersion;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOperatingSystem(Google_Service_Dfareporting_OperatingSystem $operatingSystem)
  {
    $this->operatingSystem = $operatingSystem;
  }
  public function getOperatingSystem()
  {
    return $this->operatingSystem;
  }
}

class Google_Service_Dfareporting_OperatingSystemVersionsListResponse extends Google_Collection
{
  protected $collection_key = 'operatingSystemVersions';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $operatingSystemVersionsType = 'Google_Service_Dfareporting_OperatingSystemVersion';
  protected $operatingSystemVersionsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOperatingSystemVersions($operatingSystemVersions)
  {
    $this->operatingSystemVersions = $operatingSystemVersions;
  }
  public function getOperatingSystemVersions()
  {
    return $this->operatingSystemVersions;
  }
}

class Google_Service_Dfareporting_OperatingSystemsListResponse extends Google_Collection
{
  protected $collection_key = 'operatingSystems';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $operatingSystemsType = 'Google_Service_Dfareporting_OperatingSystem';
  protected $operatingSystemsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOperatingSystems($operatingSystems)
  {
    $this->operatingSystems = $operatingSystems;
  }
  public function getOperatingSystems()
  {
    return $this->operatingSystems;
  }
}

class Google_Service_Dfareporting_OptimizationActivity extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $floodlightActivityId;
  protected $floodlightActivityIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightActivityIdDimensionValueDataType = '';
  public $weight;


  public function setFloodlightActivityId($floodlightActivityId)
  {
    $this->floodlightActivityId = $floodlightActivityId;
  }
  public function getFloodlightActivityId()
  {
    return $this->floodlightActivityId;
  }
  public function setFloodlightActivityIdDimensionValue(Google_Service_Dfareporting_DimensionValue $floodlightActivityIdDimensionValue)
  {
    $this->floodlightActivityIdDimensionValue = $floodlightActivityIdDimensionValue;
  }
  public function getFloodlightActivityIdDimensionValue()
  {
    return $this->floodlightActivityIdDimensionValue;
  }
  public function setWeight($weight)
  {
    $this->weight = $weight;
  }
  public function getWeight()
  {
    return $this->weight;
  }
}

class Google_Service_Dfareporting_Order extends Google_Collection
{
  protected $collection_key = 'siteNames';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  public $approverUserProfileIds;
  public $buyerInvoiceId;
  public $buyerOrganizationName;
  public $comments;
  protected $contactsType = 'Google_Service_Dfareporting_OrderContact';
  protected $contactsDataType = 'array';
  public $id;
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $name;
  public $notes;
  public $planningTermId;
  public $projectId;
  public $sellerOrderId;
  public $sellerOrganizationName;
  public $siteId;
  public $siteNames;
  public $subaccountId;
  public $termsAndConditions;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setApproverUserProfileIds($approverUserProfileIds)
  {
    $this->approverUserProfileIds = $approverUserProfileIds;
  }
  public function getApproverUserProfileIds()
  {
    return $this->approverUserProfileIds;
  }
  public function setBuyerInvoiceId($buyerInvoiceId)
  {
    $this->buyerInvoiceId = $buyerInvoiceId;
  }
  public function getBuyerInvoiceId()
  {
    return $this->buyerInvoiceId;
  }
  public function setBuyerOrganizationName($buyerOrganizationName)
  {
    $this->buyerOrganizationName = $buyerOrganizationName;
  }
  public function getBuyerOrganizationName()
  {
    return $this->buyerOrganizationName;
  }
  public function setComments($comments)
  {
    $this->comments = $comments;
  }
  public function getComments()
  {
    return $this->comments;
  }
  public function setContacts($contacts)
  {
    $this->contacts = $contacts;
  }
  public function getContacts()
  {
    return $this->contacts;
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
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setPlanningTermId($planningTermId)
  {
    $this->planningTermId = $planningTermId;
  }
  public function getPlanningTermId()
  {
    return $this->planningTermId;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setSellerOrderId($sellerOrderId)
  {
    $this->sellerOrderId = $sellerOrderId;
  }
  public function getSellerOrderId()
  {
    return $this->sellerOrderId;
  }
  public function setSellerOrganizationName($sellerOrganizationName)
  {
    $this->sellerOrganizationName = $sellerOrganizationName;
  }
  public function getSellerOrganizationName()
  {
    return $this->sellerOrganizationName;
  }
  public function setSiteId($siteId)
  {
    $this->siteId = $siteId;
  }
  public function getSiteId()
  {
    return $this->siteId;
  }
  public function setSiteNames($siteNames)
  {
    $this->siteNames = $siteNames;
  }
  public function getSiteNames()
  {
    return $this->siteNames;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTermsAndConditions($termsAndConditions)
  {
    $this->termsAndConditions = $termsAndConditions;
  }
  public function getTermsAndConditions()
  {
    return $this->termsAndConditions;
  }
}

class Google_Service_Dfareporting_OrderContact extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contactInfo;
  public $contactName;
  public $contactTitle;
  public $contactType;
  public $signatureUserProfileId;


  public function setContactInfo($contactInfo)
  {
    $this->contactInfo = $contactInfo;
  }
  public function getContactInfo()
  {
    return $this->contactInfo;
  }
  public function setContactName($contactName)
  {
    $this->contactName = $contactName;
  }
  public function getContactName()
  {
    return $this->contactName;
  }
  public function setContactTitle($contactTitle)
  {
    $this->contactTitle = $contactTitle;
  }
  public function getContactTitle()
  {
    return $this->contactTitle;
  }
  public function setContactType($contactType)
  {
    $this->contactType = $contactType;
  }
  public function getContactType()
  {
    return $this->contactType;
  }
  public function setSignatureUserProfileId($signatureUserProfileId)
  {
    $this->signatureUserProfileId = $signatureUserProfileId;
  }
  public function getSignatureUserProfileId()
  {
    return $this->signatureUserProfileId;
  }
}

class Google_Service_Dfareporting_OrderDocument extends Google_Collection
{
  protected $collection_key = 'approvedByUserProfileIds';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  public $amendedOrderDocumentId;
  public $approvedByUserProfileIds;
  public $cancelled;
  protected $createdInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $createdInfoDataType = '';
  public $effectiveDate;
  public $id;
  public $kind;
  public $orderId;
  public $projectId;
  public $signed;
  public $subaccountId;
  public $title;
  public $type;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAmendedOrderDocumentId($amendedOrderDocumentId)
  {
    $this->amendedOrderDocumentId = $amendedOrderDocumentId;
  }
  public function getAmendedOrderDocumentId()
  {
    return $this->amendedOrderDocumentId;
  }
  public function setApprovedByUserProfileIds($approvedByUserProfileIds)
  {
    $this->approvedByUserProfileIds = $approvedByUserProfileIds;
  }
  public function getApprovedByUserProfileIds()
  {
    return $this->approvedByUserProfileIds;
  }
  public function setCancelled($cancelled)
  {
    $this->cancelled = $cancelled;
  }
  public function getCancelled()
  {
    return $this->cancelled;
  }
  public function setCreatedInfo(Google_Service_Dfareporting_LastModifiedInfo $createdInfo)
  {
    $this->createdInfo = $createdInfo;
  }
  public function getCreatedInfo()
  {
    return $this->createdInfo;
  }
  public function setEffectiveDate($effectiveDate)
  {
    $this->effectiveDate = $effectiveDate;
  }
  public function getEffectiveDate()
  {
    return $this->effectiveDate;
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
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  public function getProjectId()
  {
    return $this->projectId;
  }
  public function setSigned($signed)
  {
    $this->signed = $signed;
  }
  public function getSigned()
  {
    return $this->signed;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
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

class Google_Service_Dfareporting_OrderDocumentsListResponse extends Google_Collection
{
  protected $collection_key = 'orderDocuments';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $orderDocumentsType = 'Google_Service_Dfareporting_OrderDocument';
  protected $orderDocumentsDataType = 'array';


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
  public function setOrderDocuments($orderDocuments)
  {
    $this->orderDocuments = $orderDocuments;
  }
  public function getOrderDocuments()
  {
    return $this->orderDocuments;
  }
}

class Google_Service_Dfareporting_OrdersListResponse extends Google_Collection
{
  protected $collection_key = 'orders';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $ordersType = 'Google_Service_Dfareporting_Order';
  protected $ordersDataType = 'array';


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
  public function setOrders($orders)
  {
    $this->orders = $orders;
  }
  public function getOrders()
  {
    return $this->orders;
  }
}

class Google_Service_Dfareporting_PathToConversionReportCompatibleFields extends Google_Collection
{
  protected $collection_key = 'perInteractionDimensions';
  protected $internal_gapi_mappings = array(
  );
  protected $conversionDimensionsType = 'Google_Service_Dfareporting_Dimension';
  protected $conversionDimensionsDataType = 'array';
  protected $customFloodlightVariablesType = 'Google_Service_Dfareporting_Dimension';
  protected $customFloodlightVariablesDataType = 'array';
  public $kind;
  protected $metricsType = 'Google_Service_Dfareporting_Metric';
  protected $metricsDataType = 'array';
  protected $perInteractionDimensionsType = 'Google_Service_Dfareporting_Dimension';
  protected $perInteractionDimensionsDataType = 'array';


  public function setConversionDimensions($conversionDimensions)
  {
    $this->conversionDimensions = $conversionDimensions;
  }
  public function getConversionDimensions()
  {
    return $this->conversionDimensions;
  }
  public function setCustomFloodlightVariables($customFloodlightVariables)
  {
    $this->customFloodlightVariables = $customFloodlightVariables;
  }
  public function getCustomFloodlightVariables()
  {
    return $this->customFloodlightVariables;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  public function setPerInteractionDimensions($perInteractionDimensions)
  {
    $this->perInteractionDimensions = $perInteractionDimensions;
  }
  public function getPerInteractionDimensions()
  {
    return $this->perInteractionDimensions;
  }
}

class Google_Service_Dfareporting_Placement extends Google_Collection
{
  protected $collection_key = 'tagFormats';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $archived;
  public $campaignId;
  protected $campaignIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $campaignIdDimensionValueDataType = '';
  public $comment;
  public $compatibility;
  public $contentCategoryId;
  protected $createInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $createInfoDataType = '';
  public $directorySiteId;
  protected $directorySiteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $directorySiteIdDimensionValueDataType = '';
  public $externalId;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $keyName;
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  public $name;
  public $paymentApproved;
  public $paymentSource;
  public $placementGroupId;
  protected $placementGroupIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $placementGroupIdDimensionValueDataType = '';
  public $placementStrategyId;
  protected $pricingScheduleType = 'Google_Service_Dfareporting_PricingSchedule';
  protected $pricingScheduleDataType = '';
  public $primary;
  protected $publisherUpdateInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $publisherUpdateInfoDataType = '';
  public $siteId;
  protected $siteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $siteIdDimensionValueDataType = '';
  protected $sizeType = 'Google_Service_Dfareporting_Size';
  protected $sizeDataType = '';
  public $sslRequired;
  public $status;
  public $subaccountId;
  public $tagFormats;
  protected $tagSettingType = 'Google_Service_Dfareporting_TagSetting';
  protected $tagSettingDataType = '';


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }
  public function getArchived()
  {
    return $this->archived;
  }
  public function setCampaignId($campaignId)
  {
    $this->campaignId = $campaignId;
  }
  public function getCampaignId()
  {
    return $this->campaignId;
  }
  public function setCampaignIdDimensionValue(Google_Service_Dfareporting_DimensionValue $campaignIdDimensionValue)
  {
    $this->campaignIdDimensionValue = $campaignIdDimensionValue;
  }
  public function getCampaignIdDimensionValue()
  {
    return $this->campaignIdDimensionValue;
  }
  public function setComment($comment)
  {
    $this->comment = $comment;
  }
  public function getComment()
  {
    return $this->comment;
  }
  public function setCompatibility($compatibility)
  {
    $this->compatibility = $compatibility;
  }
  public function getCompatibility()
  {
    return $this->compatibility;
  }
  public function setContentCategoryId($contentCategoryId)
  {
    $this->contentCategoryId = $contentCategoryId;
  }
  public function getContentCategoryId()
  {
    return $this->contentCategoryId;
  }
  public function setCreateInfo(Google_Service_Dfareporting_LastModifiedInfo $createInfo)
  {
    $this->createInfo = $createInfo;
  }
  public function getCreateInfo()
  {
    return $this->createInfo;
  }
  public function setDirectorySiteId($directorySiteId)
  {
    $this->directorySiteId = $directorySiteId;
  }
  public function getDirectorySiteId()
  {
    return $this->directorySiteId;
  }
  public function setDirectorySiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $directorySiteIdDimensionValue)
  {
    $this->directorySiteIdDimensionValue = $directorySiteIdDimensionValue;
  }
  public function getDirectorySiteIdDimensionValue()
  {
    return $this->directorySiteIdDimensionValue;
  }
  public function setExternalId($externalId)
  {
    $this->externalId = $externalId;
  }
  public function getExternalId()
  {
    return $this->externalId;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKeyName($keyName)
  {
    $this->keyName = $keyName;
  }
  public function getKeyName()
  {
    return $this->keyName;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPaymentApproved($paymentApproved)
  {
    $this->paymentApproved = $paymentApproved;
  }
  public function getPaymentApproved()
  {
    return $this->paymentApproved;
  }
  public function setPaymentSource($paymentSource)
  {
    $this->paymentSource = $paymentSource;
  }
  public function getPaymentSource()
  {
    return $this->paymentSource;
  }
  public function setPlacementGroupId($placementGroupId)
  {
    $this->placementGroupId = $placementGroupId;
  }
  public function getPlacementGroupId()
  {
    return $this->placementGroupId;
  }
  public function setPlacementGroupIdDimensionValue(Google_Service_Dfareporting_DimensionValue $placementGroupIdDimensionValue)
  {
    $this->placementGroupIdDimensionValue = $placementGroupIdDimensionValue;
  }
  public function getPlacementGroupIdDimensionValue()
  {
    return $this->placementGroupIdDimensionValue;
  }
  public function setPlacementStrategyId($placementStrategyId)
  {
    $this->placementStrategyId = $placementStrategyId;
  }
  public function getPlacementStrategyId()
  {
    return $this->placementStrategyId;
  }
  public function setPricingSchedule(Google_Service_Dfareporting_PricingSchedule $pricingSchedule)
  {
    $this->pricingSchedule = $pricingSchedule;
  }
  public function getPricingSchedule()
  {
    return $this->pricingSchedule;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setPublisherUpdateInfo(Google_Service_Dfareporting_LastModifiedInfo $publisherUpdateInfo)
  {
    $this->publisherUpdateInfo = $publisherUpdateInfo;
  }
  public function getPublisherUpdateInfo()
  {
    return $this->publisherUpdateInfo;
  }
  public function setSiteId($siteId)
  {
    $this->siteId = $siteId;
  }
  public function getSiteId()
  {
    return $this->siteId;
  }
  public function setSiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $siteIdDimensionValue)
  {
    $this->siteIdDimensionValue = $siteIdDimensionValue;
  }
  public function getSiteIdDimensionValue()
  {
    return $this->siteIdDimensionValue;
  }
  public function setSize(Google_Service_Dfareporting_Size $size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setSslRequired($sslRequired)
  {
    $this->sslRequired = $sslRequired;
  }
  public function getSslRequired()
  {
    return $this->sslRequired;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTagFormats($tagFormats)
  {
    $this->tagFormats = $tagFormats;
  }
  public function getTagFormats()
  {
    return $this->tagFormats;
  }
  public function setTagSetting(Google_Service_Dfareporting_TagSetting $tagSetting)
  {
    $this->tagSetting = $tagSetting;
  }
  public function getTagSetting()
  {
    return $this->tagSetting;
  }
}

class Google_Service_Dfareporting_PlacementAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $active;
  public $placementId;
  protected $placementIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $placementIdDimensionValueDataType = '';
  public $sslRequired;


  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setPlacementId($placementId)
  {
    $this->placementId = $placementId;
  }
  public function getPlacementId()
  {
    return $this->placementId;
  }
  public function setPlacementIdDimensionValue(Google_Service_Dfareporting_DimensionValue $placementIdDimensionValue)
  {
    $this->placementIdDimensionValue = $placementIdDimensionValue;
  }
  public function getPlacementIdDimensionValue()
  {
    return $this->placementIdDimensionValue;
  }
  public function setSslRequired($sslRequired)
  {
    $this->sslRequired = $sslRequired;
  }
  public function getSslRequired()
  {
    return $this->sslRequired;
  }
}

class Google_Service_Dfareporting_PlacementGroup extends Google_Collection
{
  protected $collection_key = 'childPlacementIds';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $archived;
  public $campaignId;
  protected $campaignIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $campaignIdDimensionValueDataType = '';
  public $childPlacementIds;
  public $comment;
  public $contentCategoryId;
  protected $createInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $createInfoDataType = '';
  public $directorySiteId;
  protected $directorySiteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $directorySiteIdDimensionValueDataType = '';
  public $externalId;
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $name;
  public $placementGroupType;
  public $placementStrategyId;
  protected $pricingScheduleType = 'Google_Service_Dfareporting_PricingSchedule';
  protected $pricingScheduleDataType = '';
  public $primaryPlacementId;
  protected $primaryPlacementIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $primaryPlacementIdDimensionValueDataType = '';
  protected $programmaticSettingType = 'Google_Service_Dfareporting_ProgrammaticSetting';
  protected $programmaticSettingDataType = '';
  public $siteId;
  protected $siteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $siteIdDimensionValueDataType = '';
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setArchived($archived)
  {
    $this->archived = $archived;
  }
  public function getArchived()
  {
    return $this->archived;
  }
  public function setCampaignId($campaignId)
  {
    $this->campaignId = $campaignId;
  }
  public function getCampaignId()
  {
    return $this->campaignId;
  }
  public function setCampaignIdDimensionValue(Google_Service_Dfareporting_DimensionValue $campaignIdDimensionValue)
  {
    $this->campaignIdDimensionValue = $campaignIdDimensionValue;
  }
  public function getCampaignIdDimensionValue()
  {
    return $this->campaignIdDimensionValue;
  }
  public function setChildPlacementIds($childPlacementIds)
  {
    $this->childPlacementIds = $childPlacementIds;
  }
  public function getChildPlacementIds()
  {
    return $this->childPlacementIds;
  }
  public function setComment($comment)
  {
    $this->comment = $comment;
  }
  public function getComment()
  {
    return $this->comment;
  }
  public function setContentCategoryId($contentCategoryId)
  {
    $this->contentCategoryId = $contentCategoryId;
  }
  public function getContentCategoryId()
  {
    return $this->contentCategoryId;
  }
  public function setCreateInfo(Google_Service_Dfareporting_LastModifiedInfo $createInfo)
  {
    $this->createInfo = $createInfo;
  }
  public function getCreateInfo()
  {
    return $this->createInfo;
  }
  public function setDirectorySiteId($directorySiteId)
  {
    $this->directorySiteId = $directorySiteId;
  }
  public function getDirectorySiteId()
  {
    return $this->directorySiteId;
  }
  public function setDirectorySiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $directorySiteIdDimensionValue)
  {
    $this->directorySiteIdDimensionValue = $directorySiteIdDimensionValue;
  }
  public function getDirectorySiteIdDimensionValue()
  {
    return $this->directorySiteIdDimensionValue;
  }
  public function setExternalId($externalId)
  {
    $this->externalId = $externalId;
  }
  public function getExternalId()
  {
    return $this->externalId;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPlacementGroupType($placementGroupType)
  {
    $this->placementGroupType = $placementGroupType;
  }
  public function getPlacementGroupType()
  {
    return $this->placementGroupType;
  }
  public function setPlacementStrategyId($placementStrategyId)
  {
    $this->placementStrategyId = $placementStrategyId;
  }
  public function getPlacementStrategyId()
  {
    return $this->placementStrategyId;
  }
  public function setPricingSchedule(Google_Service_Dfareporting_PricingSchedule $pricingSchedule)
  {
    $this->pricingSchedule = $pricingSchedule;
  }
  public function getPricingSchedule()
  {
    return $this->pricingSchedule;
  }
  public function setPrimaryPlacementId($primaryPlacementId)
  {
    $this->primaryPlacementId = $primaryPlacementId;
  }
  public function getPrimaryPlacementId()
  {
    return $this->primaryPlacementId;
  }
  public function setPrimaryPlacementIdDimensionValue(Google_Service_Dfareporting_DimensionValue $primaryPlacementIdDimensionValue)
  {
    $this->primaryPlacementIdDimensionValue = $primaryPlacementIdDimensionValue;
  }
  public function getPrimaryPlacementIdDimensionValue()
  {
    return $this->primaryPlacementIdDimensionValue;
  }
  public function setProgrammaticSetting(Google_Service_Dfareporting_ProgrammaticSetting $programmaticSetting)
  {
    $this->programmaticSetting = $programmaticSetting;
  }
  public function getProgrammaticSetting()
  {
    return $this->programmaticSetting;
  }
  public function setSiteId($siteId)
  {
    $this->siteId = $siteId;
  }
  public function getSiteId()
  {
    return $this->siteId;
  }
  public function setSiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $siteIdDimensionValue)
  {
    $this->siteIdDimensionValue = $siteIdDimensionValue;
  }
  public function getSiteIdDimensionValue()
  {
    return $this->siteIdDimensionValue;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_PlacementGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'placementGroups';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $placementGroupsType = 'Google_Service_Dfareporting_PlacementGroup';
  protected $placementGroupsDataType = 'array';


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
  public function setPlacementGroups($placementGroups)
  {
    $this->placementGroups = $placementGroups;
  }
  public function getPlacementGroups()
  {
    return $this->placementGroups;
  }
}

class Google_Service_Dfareporting_PlacementStrategiesListResponse extends Google_Collection
{
  protected $collection_key = 'placementStrategies';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $placementStrategiesType = 'Google_Service_Dfareporting_PlacementStrategy';
  protected $placementStrategiesDataType = 'array';


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
  public function setPlacementStrategies($placementStrategies)
  {
    $this->placementStrategies = $placementStrategies;
  }
  public function getPlacementStrategies()
  {
    return $this->placementStrategies;
  }
}

class Google_Service_Dfareporting_PlacementStrategy extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $id;
  public $kind;
  public $name;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_PlacementTag extends Google_Collection
{
  protected $collection_key = 'tagDatas';
  protected $internal_gapi_mappings = array(
  );
  public $placementId;
  protected $tagDatasType = 'Google_Service_Dfareporting_TagData';
  protected $tagDatasDataType = 'array';


  public function setPlacementId($placementId)
  {
    $this->placementId = $placementId;
  }
  public function getPlacementId()
  {
    return $this->placementId;
  }
  public function setTagDatas($tagDatas)
  {
    $this->tagDatas = $tagDatas;
  }
  public function getTagDatas()
  {
    return $this->tagDatas;
  }
}

class Google_Service_Dfareporting_PlacementsGenerateTagsResponse extends Google_Collection
{
  protected $collection_key = 'placementTags';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $placementTagsType = 'Google_Service_Dfareporting_PlacementTag';
  protected $placementTagsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPlacementTags($placementTags)
  {
    $this->placementTags = $placementTags;
  }
  public function getPlacementTags()
  {
    return $this->placementTags;
  }
}

class Google_Service_Dfareporting_PlacementsListResponse extends Google_Collection
{
  protected $collection_key = 'placements';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $placementsType = 'Google_Service_Dfareporting_Placement';
  protected $placementsDataType = 'array';


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
  public function setPlacements($placements)
  {
    $this->placements = $placements;
  }
  public function getPlacements()
  {
    return $this->placements;
  }
}

class Google_Service_Dfareporting_PlatformType extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_PlatformTypesListResponse extends Google_Collection
{
  protected $collection_key = 'platformTypes';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $platformTypesType = 'Google_Service_Dfareporting_PlatformType';
  protected $platformTypesDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPlatformTypes($platformTypes)
  {
    $this->platformTypes = $platformTypes;
  }
  public function getPlatformTypes()
  {
    return $this->platformTypes;
  }
}

class Google_Service_Dfareporting_PopupWindowProperties extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $dimensionType = 'Google_Service_Dfareporting_Size';
  protected $dimensionDataType = '';
  protected $offsetType = 'Google_Service_Dfareporting_OffsetPosition';
  protected $offsetDataType = '';
  public $positionType;
  public $showAddressBar;
  public $showMenuBar;
  public $showScrollBar;
  public $showStatusBar;
  public $showToolBar;
  public $title;


  public function setDimension(Google_Service_Dfareporting_Size $dimension)
  {
    $this->dimension = $dimension;
  }
  public function getDimension()
  {
    return $this->dimension;
  }
  public function setOffset(Google_Service_Dfareporting_OffsetPosition $offset)
  {
    $this->offset = $offset;
  }
  public function getOffset()
  {
    return $this->offset;
  }
  public function setPositionType($positionType)
  {
    $this->positionType = $positionType;
  }
  public function getPositionType()
  {
    return $this->positionType;
  }
  public function setShowAddressBar($showAddressBar)
  {
    $this->showAddressBar = $showAddressBar;
  }
  public function getShowAddressBar()
  {
    return $this->showAddressBar;
  }
  public function setShowMenuBar($showMenuBar)
  {
    $this->showMenuBar = $showMenuBar;
  }
  public function getShowMenuBar()
  {
    return $this->showMenuBar;
  }
  public function setShowScrollBar($showScrollBar)
  {
    $this->showScrollBar = $showScrollBar;
  }
  public function getShowScrollBar()
  {
    return $this->showScrollBar;
  }
  public function setShowStatusBar($showStatusBar)
  {
    $this->showStatusBar = $showStatusBar;
  }
  public function getShowStatusBar()
  {
    return $this->showStatusBar;
  }
  public function setShowToolBar($showToolBar)
  {
    $this->showToolBar = $showToolBar;
  }
  public function getShowToolBar()
  {
    return $this->showToolBar;
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

class Google_Service_Dfareporting_PostalCode extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $countryCode;
  public $countryDartId;
  public $id;
  public $kind;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCountryDartId($countryDartId)
  {
    $this->countryDartId = $countryDartId;
  }
  public function getCountryDartId()
  {
    return $this->countryDartId;
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
}

class Google_Service_Dfareporting_PostalCodesListResponse extends Google_Collection
{
  protected $collection_key = 'postalCodes';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $postalCodesType = 'Google_Service_Dfareporting_PostalCode';
  protected $postalCodesDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPostalCodes($postalCodes)
  {
    $this->postalCodes = $postalCodes;
  }
  public function getPostalCodes()
  {
    return $this->postalCodes;
  }
}

class Google_Service_Dfareporting_Pricing extends Google_Collection
{
  protected $collection_key = 'flights';
  protected $internal_gapi_mappings = array(
  );
  public $capCostType;
  public $endDate;
  protected $flightsType = 'Google_Service_Dfareporting_Flight';
  protected $flightsDataType = 'array';
  public $groupType;
  public $pricingType;
  public $startDate;


  public function setCapCostType($capCostType)
  {
    $this->capCostType = $capCostType;
  }
  public function getCapCostType()
  {
    return $this->capCostType;
  }
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setFlights($flights)
  {
    $this->flights = $flights;
  }
  public function getFlights()
  {
    return $this->flights;
  }
  public function setGroupType($groupType)
  {
    $this->groupType = $groupType;
  }
  public function getGroupType()
  {
    return $this->groupType;
  }
  public function setPricingType($pricingType)
  {
    $this->pricingType = $pricingType;
  }
  public function getPricingType()
  {
    return $this->pricingType;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
}

class Google_Service_Dfareporting_PricingSchedule extends Google_Collection
{
  protected $collection_key = 'pricingPeriods';
  protected $internal_gapi_mappings = array(
  );
  public $capCostOption;
  public $disregardOverdelivery;
  public $endDate;
  public $flighted;
  public $floodlightActivityId;
  protected $pricingPeriodsType = 'Google_Service_Dfareporting_PricingSchedulePricingPeriod';
  protected $pricingPeriodsDataType = 'array';
  public $pricingType;
  public $startDate;
  public $testingStartDate;


  public function setCapCostOption($capCostOption)
  {
    $this->capCostOption = $capCostOption;
  }
  public function getCapCostOption()
  {
    return $this->capCostOption;
  }
  public function setDisregardOverdelivery($disregardOverdelivery)
  {
    $this->disregardOverdelivery = $disregardOverdelivery;
  }
  public function getDisregardOverdelivery()
  {
    return $this->disregardOverdelivery;
  }
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setFlighted($flighted)
  {
    $this->flighted = $flighted;
  }
  public function getFlighted()
  {
    return $this->flighted;
  }
  public function setFloodlightActivityId($floodlightActivityId)
  {
    $this->floodlightActivityId = $floodlightActivityId;
  }
  public function getFloodlightActivityId()
  {
    return $this->floodlightActivityId;
  }
  public function setPricingPeriods($pricingPeriods)
  {
    $this->pricingPeriods = $pricingPeriods;
  }
  public function getPricingPeriods()
  {
    return $this->pricingPeriods;
  }
  public function setPricingType($pricingType)
  {
    $this->pricingType = $pricingType;
  }
  public function getPricingType()
  {
    return $this->pricingType;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setTestingStartDate($testingStartDate)
  {
    $this->testingStartDate = $testingStartDate;
  }
  public function getTestingStartDate()
  {
    return $this->testingStartDate;
  }
}

class Google_Service_Dfareporting_PricingSchedulePricingPeriod extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endDate;
  public $pricingComment;
  public $rateOrCostNanos;
  public $startDate;
  public $units;


  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setPricingComment($pricingComment)
  {
    $this->pricingComment = $pricingComment;
  }
  public function getPricingComment()
  {
    return $this->pricingComment;
  }
  public function setRateOrCostNanos($rateOrCostNanos)
  {
    $this->rateOrCostNanos = $rateOrCostNanos;
  }
  public function getRateOrCostNanos()
  {
    return $this->rateOrCostNanos;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setUnits($units)
  {
    $this->units = $units;
  }
  public function getUnits()
  {
    return $this->units;
  }
}

class Google_Service_Dfareporting_ProgrammaticSetting extends Google_Collection
{
  protected $collection_key = 'traffickerEmails';
  protected $internal_gapi_mappings = array(
  );
  public $adxDealIds;
  public $insertionOrderId;
  public $insertionOrderIdStatus;
  public $mediaCostNanos;
  public $programmatic;
  public $traffickerEmails;


  public function setAdxDealIds($adxDealIds)
  {
    $this->adxDealIds = $adxDealIds;
  }
  public function getAdxDealIds()
  {
    return $this->adxDealIds;
  }
  public function setInsertionOrderId($insertionOrderId)
  {
    $this->insertionOrderId = $insertionOrderId;
  }
  public function getInsertionOrderId()
  {
    return $this->insertionOrderId;
  }
  public function setInsertionOrderIdStatus($insertionOrderIdStatus)
  {
    $this->insertionOrderIdStatus = $insertionOrderIdStatus;
  }
  public function getInsertionOrderIdStatus()
  {
    return $this->insertionOrderIdStatus;
  }
  public function setMediaCostNanos($mediaCostNanos)
  {
    $this->mediaCostNanos = $mediaCostNanos;
  }
  public function getMediaCostNanos()
  {
    return $this->mediaCostNanos;
  }
  public function setProgrammatic($programmatic)
  {
    $this->programmatic = $programmatic;
  }
  public function getProgrammatic()
  {
    return $this->programmatic;
  }
  public function setTraffickerEmails($traffickerEmails)
  {
    $this->traffickerEmails = $traffickerEmails;
  }
  public function getTraffickerEmails()
  {
    return $this->traffickerEmails;
  }
}

class Google_Service_Dfareporting_Project extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $advertiserId;
  public $audienceAgeGroup;
  public $audienceGender;
  public $budget;
  public $clientBillingCode;
  public $clientName;
  public $endDate;
  public $id;
  public $kind;
  protected $lastModifiedInfoType = 'Google_Service_Dfareporting_LastModifiedInfo';
  protected $lastModifiedInfoDataType = '';
  public $name;
  public $overview;
  public $startDate;
  public $subaccountId;
  public $targetClicks;
  public $targetConversions;
  public $targetCpaNanos;
  public $targetCpcNanos;
  public $targetCpmNanos;
  public $targetImpressions;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAudienceAgeGroup($audienceAgeGroup)
  {
    $this->audienceAgeGroup = $audienceAgeGroup;
  }
  public function getAudienceAgeGroup()
  {
    return $this->audienceAgeGroup;
  }
  public function setAudienceGender($audienceGender)
  {
    $this->audienceGender = $audienceGender;
  }
  public function getAudienceGender()
  {
    return $this->audienceGender;
  }
  public function setBudget($budget)
  {
    $this->budget = $budget;
  }
  public function getBudget()
  {
    return $this->budget;
  }
  public function setClientBillingCode($clientBillingCode)
  {
    $this->clientBillingCode = $clientBillingCode;
  }
  public function getClientBillingCode()
  {
    return $this->clientBillingCode;
  }
  public function setClientName($clientName)
  {
    $this->clientName = $clientName;
  }
  public function getClientName()
  {
    return $this->clientName;
  }
  public function setEndDate($endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
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
  public function setLastModifiedInfo(Google_Service_Dfareporting_LastModifiedInfo $lastModifiedInfo)
  {
    $this->lastModifiedInfo = $lastModifiedInfo;
  }
  public function getLastModifiedInfo()
  {
    return $this->lastModifiedInfo;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOverview($overview)
  {
    $this->overview = $overview;
  }
  public function getOverview()
  {
    return $this->overview;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
  public function setTargetClicks($targetClicks)
  {
    $this->targetClicks = $targetClicks;
  }
  public function getTargetClicks()
  {
    return $this->targetClicks;
  }
  public function setTargetConversions($targetConversions)
  {
    $this->targetConversions = $targetConversions;
  }
  public function getTargetConversions()
  {
    return $this->targetConversions;
  }
  public function setTargetCpaNanos($targetCpaNanos)
  {
    $this->targetCpaNanos = $targetCpaNanos;
  }
  public function getTargetCpaNanos()
  {
    return $this->targetCpaNanos;
  }
  public function setTargetCpcNanos($targetCpcNanos)
  {
    $this->targetCpcNanos = $targetCpcNanos;
  }
  public function getTargetCpcNanos()
  {
    return $this->targetCpcNanos;
  }
  public function setTargetCpmNanos($targetCpmNanos)
  {
    $this->targetCpmNanos = $targetCpmNanos;
  }
  public function getTargetCpmNanos()
  {
    return $this->targetCpmNanos;
  }
  public function setTargetImpressions($targetImpressions)
  {
    $this->targetImpressions = $targetImpressions;
  }
  public function getTargetImpressions()
  {
    return $this->targetImpressions;
  }
}

class Google_Service_Dfareporting_ProjectsListResponse extends Google_Collection
{
  protected $collection_key = 'projects';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $projectsType = 'Google_Service_Dfareporting_Project';
  protected $projectsDataType = 'array';


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
  public function setProjects($projects)
  {
    $this->projects = $projects;
  }
  public function getProjects()
  {
    return $this->projects;
  }
}

class Google_Service_Dfareporting_ReachReportCompatibleFields extends Google_Collection
{
  protected $collection_key = 'reachByFrequencyMetrics';
  protected $internal_gapi_mappings = array(
  );
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionsDataType = 'array';
  public $kind;
  protected $metricsType = 'Google_Service_Dfareporting_Metric';
  protected $metricsDataType = 'array';
  protected $pivotedActivityMetricsType = 'Google_Service_Dfareporting_Metric';
  protected $pivotedActivityMetricsDataType = 'array';
  protected $reachByFrequencyMetricsType = 'Google_Service_Dfareporting_Metric';
  protected $reachByFrequencyMetricsDataType = 'array';


  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  public function setPivotedActivityMetrics($pivotedActivityMetrics)
  {
    $this->pivotedActivityMetrics = $pivotedActivityMetrics;
  }
  public function getPivotedActivityMetrics()
  {
    return $this->pivotedActivityMetrics;
  }
  public function setReachByFrequencyMetrics($reachByFrequencyMetrics)
  {
    $this->reachByFrequencyMetrics = $reachByFrequencyMetrics;
  }
  public function getReachByFrequencyMetrics()
  {
    return $this->reachByFrequencyMetrics;
  }
}

class Google_Service_Dfareporting_Recipient extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $deliveryType;
  public $email;
  public $kind;


  public function setDeliveryType($deliveryType)
  {
    $this->deliveryType = $deliveryType;
  }
  public function getDeliveryType()
  {
    return $this->deliveryType;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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

class Google_Service_Dfareporting_Region extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $countryCode;
  public $countryDartId;
  public $dartId;
  public $kind;
  public $name;
  public $regionCode;


  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCountryDartId($countryDartId)
  {
    $this->countryDartId = $countryDartId;
  }
  public function getCountryDartId()
  {
    return $this->countryDartId;
  }
  public function setDartId($dartId)
  {
    $this->dartId = $dartId;
  }
  public function getDartId()
  {
    return $this->dartId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setRegionCode($regionCode)
  {
    $this->regionCode = $regionCode;
  }
  public function getRegionCode()
  {
    return $this->regionCode;
  }
}

class Google_Service_Dfareporting_RegionsListResponse extends Google_Collection
{
  protected $collection_key = 'regions';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $regionsType = 'Google_Service_Dfareporting_Region';
  protected $regionsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRegions($regions)
  {
    $this->regions = $regions;
  }
  public function getRegions()
  {
    return $this->regions;
  }
}

class Google_Service_Dfareporting_RemarketingList extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $active;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $description;
  public $id;
  public $kind;
  public $lifeSpan;
  protected $listPopulationRuleType = 'Google_Service_Dfareporting_ListPopulationRule';
  protected $listPopulationRuleDataType = '';
  public $listSize;
  public $listSource;
  public $name;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
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
  public function setLifeSpan($lifeSpan)
  {
    $this->lifeSpan = $lifeSpan;
  }
  public function getLifeSpan()
  {
    return $this->lifeSpan;
  }
  public function setListPopulationRule(Google_Service_Dfareporting_ListPopulationRule $listPopulationRule)
  {
    $this->listPopulationRule = $listPopulationRule;
  }
  public function getListPopulationRule()
  {
    return $this->listPopulationRule;
  }
  public function setListSize($listSize)
  {
    $this->listSize = $listSize;
  }
  public function getListSize()
  {
    return $this->listSize;
  }
  public function setListSource($listSource)
  {
    $this->listSource = $listSource;
  }
  public function getListSource()
  {
    return $this->listSource;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_RemarketingListShare extends Google_Collection
{
  protected $collection_key = 'sharedAdvertiserIds';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $remarketingListId;
  public $sharedAccountIds;
  public $sharedAdvertiserIds;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRemarketingListId($remarketingListId)
  {
    $this->remarketingListId = $remarketingListId;
  }
  public function getRemarketingListId()
  {
    return $this->remarketingListId;
  }
  public function setSharedAccountIds($sharedAccountIds)
  {
    $this->sharedAccountIds = $sharedAccountIds;
  }
  public function getSharedAccountIds()
  {
    return $this->sharedAccountIds;
  }
  public function setSharedAdvertiserIds($sharedAdvertiserIds)
  {
    $this->sharedAdvertiserIds = $sharedAdvertiserIds;
  }
  public function getSharedAdvertiserIds()
  {
    return $this->sharedAdvertiserIds;
  }
}

class Google_Service_Dfareporting_RemarketingListsListResponse extends Google_Collection
{
  protected $collection_key = 'remarketingLists';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $remarketingListsType = 'Google_Service_Dfareporting_RemarketingList';
  protected $remarketingListsDataType = 'array';


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
  public function setRemarketingLists($remarketingLists)
  {
    $this->remarketingLists = $remarketingLists;
  }
  public function getRemarketingLists()
  {
    return $this->remarketingLists;
  }
}

class Google_Service_Dfareporting_Report extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  protected $criteriaType = 'Google_Service_Dfareporting_ReportCriteria';
  protected $criteriaDataType = '';
  protected $crossDimensionReachCriteriaType = 'Google_Service_Dfareporting_ReportCrossDimensionReachCriteria';
  protected $crossDimensionReachCriteriaDataType = '';
  protected $deliveryType = 'Google_Service_Dfareporting_ReportDelivery';
  protected $deliveryDataType = '';
  public $etag;
  public $fileName;
  protected $floodlightCriteriaType = 'Google_Service_Dfareporting_ReportFloodlightCriteria';
  protected $floodlightCriteriaDataType = '';
  public $format;
  public $id;
  public $kind;
  public $lastModifiedTime;
  public $name;
  public $ownerProfileId;
  protected $pathToConversionCriteriaType = 'Google_Service_Dfareporting_ReportPathToConversionCriteria';
  protected $pathToConversionCriteriaDataType = '';
  protected $reachCriteriaType = 'Google_Service_Dfareporting_ReportReachCriteria';
  protected $reachCriteriaDataType = '';
  protected $scheduleType = 'Google_Service_Dfareporting_ReportSchedule';
  protected $scheduleDataType = '';
  public $subAccountId;
  public $type;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setCriteria(Google_Service_Dfareporting_ReportCriteria $criteria)
  {
    $this->criteria = $criteria;
  }
  public function getCriteria()
  {
    return $this->criteria;
  }
  public function setCrossDimensionReachCriteria(Google_Service_Dfareporting_ReportCrossDimensionReachCriteria $crossDimensionReachCriteria)
  {
    $this->crossDimensionReachCriteria = $crossDimensionReachCriteria;
  }
  public function getCrossDimensionReachCriteria()
  {
    return $this->crossDimensionReachCriteria;
  }
  public function setDelivery(Google_Service_Dfareporting_ReportDelivery $delivery)
  {
    $this->delivery = $delivery;
  }
  public function getDelivery()
  {
    return $this->delivery;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFileName($fileName)
  {
    $this->fileName = $fileName;
  }
  public function getFileName()
  {
    return $this->fileName;
  }
  public function setFloodlightCriteria(Google_Service_Dfareporting_ReportFloodlightCriteria $floodlightCriteria)
  {
    $this->floodlightCriteria = $floodlightCriteria;
  }
  public function getFloodlightCriteria()
  {
    return $this->floodlightCriteria;
  }
  public function setFormat($format)
  {
    $this->format = $format;
  }
  public function getFormat()
  {
    return $this->format;
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
  public function setLastModifiedTime($lastModifiedTime)
  {
    $this->lastModifiedTime = $lastModifiedTime;
  }
  public function getLastModifiedTime()
  {
    return $this->lastModifiedTime;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOwnerProfileId($ownerProfileId)
  {
    $this->ownerProfileId = $ownerProfileId;
  }
  public function getOwnerProfileId()
  {
    return $this->ownerProfileId;
  }
  public function setPathToConversionCriteria(Google_Service_Dfareporting_ReportPathToConversionCriteria $pathToConversionCriteria)
  {
    $this->pathToConversionCriteria = $pathToConversionCriteria;
  }
  public function getPathToConversionCriteria()
  {
    return $this->pathToConversionCriteria;
  }
  public function setReachCriteria(Google_Service_Dfareporting_ReportReachCriteria $reachCriteria)
  {
    $this->reachCriteria = $reachCriteria;
  }
  public function getReachCriteria()
  {
    return $this->reachCriteria;
  }
  public function setSchedule(Google_Service_Dfareporting_ReportSchedule $schedule)
  {
    $this->schedule = $schedule;
  }
  public function getSchedule()
  {
    return $this->schedule;
  }
  public function setSubAccountId($subAccountId)
  {
    $this->subAccountId = $subAccountId;
  }
  public function getSubAccountId()
  {
    return $this->subAccountId;
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

class Google_Service_Dfareporting_ReportCompatibleFields extends Google_Collection
{
  protected $collection_key = 'pivotedActivityMetrics';
  protected $internal_gapi_mappings = array(
  );
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_Dimension';
  protected $dimensionsDataType = 'array';
  public $kind;
  protected $metricsType = 'Google_Service_Dfareporting_Metric';
  protected $metricsDataType = 'array';
  protected $pivotedActivityMetricsType = 'Google_Service_Dfareporting_Metric';
  protected $pivotedActivityMetricsDataType = 'array';


  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  public function setPivotedActivityMetrics($pivotedActivityMetrics)
  {
    $this->pivotedActivityMetrics = $pivotedActivityMetrics;
  }
  public function getPivotedActivityMetrics()
  {
    return $this->pivotedActivityMetrics;
  }
}

class Google_Service_Dfareporting_ReportCriteria extends Google_Collection
{
  protected $collection_key = 'metricNames';
  protected $internal_gapi_mappings = array(
  );
  protected $activitiesType = 'Google_Service_Dfareporting_Activities';
  protected $activitiesDataType = '';
  protected $customRichMediaEventsType = 'Google_Service_Dfareporting_CustomRichMediaEvents';
  protected $customRichMediaEventsDataType = '';
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_SortedDimension';
  protected $dimensionsDataType = 'array';
  public $metricNames;


  public function setActivities(Google_Service_Dfareporting_Activities $activities)
  {
    $this->activities = $activities;
  }
  public function getActivities()
  {
    return $this->activities;
  }
  public function setCustomRichMediaEvents(Google_Service_Dfareporting_CustomRichMediaEvents $customRichMediaEvents)
  {
    $this->customRichMediaEvents = $customRichMediaEvents;
  }
  public function getCustomRichMediaEvents()
  {
    return $this->customRichMediaEvents;
  }
  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
}

class Google_Service_Dfareporting_ReportCrossDimensionReachCriteria extends Google_Collection
{
  protected $collection_key = 'overlapMetricNames';
  protected $internal_gapi_mappings = array(
  );
  protected $breakdownType = 'Google_Service_Dfareporting_SortedDimension';
  protected $breakdownDataType = 'array';
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  public $dimension;
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $dimensionFiltersDataType = 'array';
  public $metricNames;
  public $overlapMetricNames;
  public $pivoted;


  public function setBreakdown($breakdown)
  {
    $this->breakdown = $breakdown;
  }
  public function getBreakdown()
  {
    return $this->breakdown;
  }
  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setDimension($dimension)
  {
    $this->dimension = $dimension;
  }
  public function getDimension()
  {
    return $this->dimension;
  }
  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
  public function setOverlapMetricNames($overlapMetricNames)
  {
    $this->overlapMetricNames = $overlapMetricNames;
  }
  public function getOverlapMetricNames()
  {
    return $this->overlapMetricNames;
  }
  public function setPivoted($pivoted)
  {
    $this->pivoted = $pivoted;
  }
  public function getPivoted()
  {
    return $this->pivoted;
  }
}

class Google_Service_Dfareporting_ReportDelivery extends Google_Collection
{
  protected $collection_key = 'recipients';
  protected $internal_gapi_mappings = array(
  );
  public $emailOwner;
  public $emailOwnerDeliveryType;
  public $message;
  protected $recipientsType = 'Google_Service_Dfareporting_Recipient';
  protected $recipientsDataType = 'array';


  public function setEmailOwner($emailOwner)
  {
    $this->emailOwner = $emailOwner;
  }
  public function getEmailOwner()
  {
    return $this->emailOwner;
  }
  public function setEmailOwnerDeliveryType($emailOwnerDeliveryType)
  {
    $this->emailOwnerDeliveryType = $emailOwnerDeliveryType;
  }
  public function getEmailOwnerDeliveryType()
  {
    return $this->emailOwnerDeliveryType;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
  }
  public function setRecipients($recipients)
  {
    $this->recipients = $recipients;
  }
  public function getRecipients()
  {
    return $this->recipients;
  }
}

class Google_Service_Dfareporting_ReportFloodlightCriteria extends Google_Collection
{
  protected $collection_key = 'metricNames';
  protected $internal_gapi_mappings = array(
  );
  protected $customRichMediaEventsType = 'Google_Service_Dfareporting_DimensionValue';
  protected $customRichMediaEventsDataType = 'array';
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_SortedDimension';
  protected $dimensionsDataType = 'array';
  protected $floodlightConfigIdType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightConfigIdDataType = '';
  public $metricNames;
  protected $reportPropertiesType = 'Google_Service_Dfareporting_ReportFloodlightCriteriaReportProperties';
  protected $reportPropertiesDataType = '';


  public function setCustomRichMediaEvents($customRichMediaEvents)
  {
    $this->customRichMediaEvents = $customRichMediaEvents;
  }
  public function getCustomRichMediaEvents()
  {
    return $this->customRichMediaEvents;
  }
  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setFloodlightConfigId(Google_Service_Dfareporting_DimensionValue $floodlightConfigId)
  {
    $this->floodlightConfigId = $floodlightConfigId;
  }
  public function getFloodlightConfigId()
  {
    return $this->floodlightConfigId;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
  public function setReportProperties(Google_Service_Dfareporting_ReportFloodlightCriteriaReportProperties $reportProperties)
  {
    $this->reportProperties = $reportProperties;
  }
  public function getReportProperties()
  {
    return $this->reportProperties;
  }
}

class Google_Service_Dfareporting_ReportFloodlightCriteriaReportProperties extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $includeAttributedIPConversions;
  public $includeUnattributedCookieConversions;
  public $includeUnattributedIPConversions;


  public function setIncludeAttributedIPConversions($includeAttributedIPConversions)
  {
    $this->includeAttributedIPConversions = $includeAttributedIPConversions;
  }
  public function getIncludeAttributedIPConversions()
  {
    return $this->includeAttributedIPConversions;
  }
  public function setIncludeUnattributedCookieConversions($includeUnattributedCookieConversions)
  {
    $this->includeUnattributedCookieConversions = $includeUnattributedCookieConversions;
  }
  public function getIncludeUnattributedCookieConversions()
  {
    return $this->includeUnattributedCookieConversions;
  }
  public function setIncludeUnattributedIPConversions($includeUnattributedIPConversions)
  {
    $this->includeUnattributedIPConversions = $includeUnattributedIPConversions;
  }
  public function getIncludeUnattributedIPConversions()
  {
    return $this->includeUnattributedIPConversions;
  }
}

class Google_Service_Dfareporting_ReportList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Dfareporting_Report';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
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

class Google_Service_Dfareporting_ReportPathToConversionCriteria extends Google_Collection
{
  protected $collection_key = 'perInteractionDimensions';
  protected $internal_gapi_mappings = array(
  );
  protected $activityFiltersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $activityFiltersDataType = 'array';
  protected $conversionDimensionsType = 'Google_Service_Dfareporting_SortedDimension';
  protected $conversionDimensionsDataType = 'array';
  protected $customFloodlightVariablesType = 'Google_Service_Dfareporting_SortedDimension';
  protected $customFloodlightVariablesDataType = 'array';
  protected $customRichMediaEventsType = 'Google_Service_Dfareporting_DimensionValue';
  protected $customRichMediaEventsDataType = 'array';
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  protected $floodlightConfigIdType = 'Google_Service_Dfareporting_DimensionValue';
  protected $floodlightConfigIdDataType = '';
  public $metricNames;
  protected $perInteractionDimensionsType = 'Google_Service_Dfareporting_SortedDimension';
  protected $perInteractionDimensionsDataType = 'array';
  protected $reportPropertiesType = 'Google_Service_Dfareporting_ReportPathToConversionCriteriaReportProperties';
  protected $reportPropertiesDataType = '';


  public function setActivityFilters($activityFilters)
  {
    $this->activityFilters = $activityFilters;
  }
  public function getActivityFilters()
  {
    return $this->activityFilters;
  }
  public function setConversionDimensions($conversionDimensions)
  {
    $this->conversionDimensions = $conversionDimensions;
  }
  public function getConversionDimensions()
  {
    return $this->conversionDimensions;
  }
  public function setCustomFloodlightVariables($customFloodlightVariables)
  {
    $this->customFloodlightVariables = $customFloodlightVariables;
  }
  public function getCustomFloodlightVariables()
  {
    return $this->customFloodlightVariables;
  }
  public function setCustomRichMediaEvents($customRichMediaEvents)
  {
    $this->customRichMediaEvents = $customRichMediaEvents;
  }
  public function getCustomRichMediaEvents()
  {
    return $this->customRichMediaEvents;
  }
  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setFloodlightConfigId(Google_Service_Dfareporting_DimensionValue $floodlightConfigId)
  {
    $this->floodlightConfigId = $floodlightConfigId;
  }
  public function getFloodlightConfigId()
  {
    return $this->floodlightConfigId;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
  public function setPerInteractionDimensions($perInteractionDimensions)
  {
    $this->perInteractionDimensions = $perInteractionDimensions;
  }
  public function getPerInteractionDimensions()
  {
    return $this->perInteractionDimensions;
  }
  public function setReportProperties(Google_Service_Dfareporting_ReportPathToConversionCriteriaReportProperties $reportProperties)
  {
    $this->reportProperties = $reportProperties;
  }
  public function getReportProperties()
  {
    return $this->reportProperties;
  }
}

class Google_Service_Dfareporting_ReportPathToConversionCriteriaReportProperties extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $clicksLookbackWindow;
  public $impressionsLookbackWindow;
  public $includeAttributedIPConversions;
  public $includeUnattributedCookieConversions;
  public $includeUnattributedIPConversions;
  public $maximumClickInteractions;
  public $maximumImpressionInteractions;
  public $maximumInteractionGap;
  public $pivotOnInteractionPath;


  public function setClicksLookbackWindow($clicksLookbackWindow)
  {
    $this->clicksLookbackWindow = $clicksLookbackWindow;
  }
  public function getClicksLookbackWindow()
  {
    return $this->clicksLookbackWindow;
  }
  public function setImpressionsLookbackWindow($impressionsLookbackWindow)
  {
    $this->impressionsLookbackWindow = $impressionsLookbackWindow;
  }
  public function getImpressionsLookbackWindow()
  {
    return $this->impressionsLookbackWindow;
  }
  public function setIncludeAttributedIPConversions($includeAttributedIPConversions)
  {
    $this->includeAttributedIPConversions = $includeAttributedIPConversions;
  }
  public function getIncludeAttributedIPConversions()
  {
    return $this->includeAttributedIPConversions;
  }
  public function setIncludeUnattributedCookieConversions($includeUnattributedCookieConversions)
  {
    $this->includeUnattributedCookieConversions = $includeUnattributedCookieConversions;
  }
  public function getIncludeUnattributedCookieConversions()
  {
    return $this->includeUnattributedCookieConversions;
  }
  public function setIncludeUnattributedIPConversions($includeUnattributedIPConversions)
  {
    $this->includeUnattributedIPConversions = $includeUnattributedIPConversions;
  }
  public function getIncludeUnattributedIPConversions()
  {
    return $this->includeUnattributedIPConversions;
  }
  public function setMaximumClickInteractions($maximumClickInteractions)
  {
    $this->maximumClickInteractions = $maximumClickInteractions;
  }
  public function getMaximumClickInteractions()
  {
    return $this->maximumClickInteractions;
  }
  public function setMaximumImpressionInteractions($maximumImpressionInteractions)
  {
    $this->maximumImpressionInteractions = $maximumImpressionInteractions;
  }
  public function getMaximumImpressionInteractions()
  {
    return $this->maximumImpressionInteractions;
  }
  public function setMaximumInteractionGap($maximumInteractionGap)
  {
    $this->maximumInteractionGap = $maximumInteractionGap;
  }
  public function getMaximumInteractionGap()
  {
    return $this->maximumInteractionGap;
  }
  public function setPivotOnInteractionPath($pivotOnInteractionPath)
  {
    $this->pivotOnInteractionPath = $pivotOnInteractionPath;
  }
  public function getPivotOnInteractionPath()
  {
    return $this->pivotOnInteractionPath;
  }
}

class Google_Service_Dfareporting_ReportReachCriteria extends Google_Collection
{
  protected $collection_key = 'reachByFrequencyMetricNames';
  protected $internal_gapi_mappings = array(
  );
  protected $activitiesType = 'Google_Service_Dfareporting_Activities';
  protected $activitiesDataType = '';
  protected $customRichMediaEventsType = 'Google_Service_Dfareporting_CustomRichMediaEvents';
  protected $customRichMediaEventsDataType = '';
  protected $dateRangeType = 'Google_Service_Dfareporting_DateRange';
  protected $dateRangeDataType = '';
  protected $dimensionFiltersType = 'Google_Service_Dfareporting_DimensionValue';
  protected $dimensionFiltersDataType = 'array';
  protected $dimensionsType = 'Google_Service_Dfareporting_SortedDimension';
  protected $dimensionsDataType = 'array';
  public $enableAllDimensionCombinations;
  public $metricNames;
  public $reachByFrequencyMetricNames;


  public function setActivities(Google_Service_Dfareporting_Activities $activities)
  {
    $this->activities = $activities;
  }
  public function getActivities()
  {
    return $this->activities;
  }
  public function setCustomRichMediaEvents(Google_Service_Dfareporting_CustomRichMediaEvents $customRichMediaEvents)
  {
    $this->customRichMediaEvents = $customRichMediaEvents;
  }
  public function getCustomRichMediaEvents()
  {
    return $this->customRichMediaEvents;
  }
  public function setDateRange(Google_Service_Dfareporting_DateRange $dateRange)
  {
    $this->dateRange = $dateRange;
  }
  public function getDateRange()
  {
    return $this->dateRange;
  }
  public function setDimensionFilters($dimensionFilters)
  {
    $this->dimensionFilters = $dimensionFilters;
  }
  public function getDimensionFilters()
  {
    return $this->dimensionFilters;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setEnableAllDimensionCombinations($enableAllDimensionCombinations)
  {
    $this->enableAllDimensionCombinations = $enableAllDimensionCombinations;
  }
  public function getEnableAllDimensionCombinations()
  {
    return $this->enableAllDimensionCombinations;
  }
  public function setMetricNames($metricNames)
  {
    $this->metricNames = $metricNames;
  }
  public function getMetricNames()
  {
    return $this->metricNames;
  }
  public function setReachByFrequencyMetricNames($reachByFrequencyMetricNames)
  {
    $this->reachByFrequencyMetricNames = $reachByFrequencyMetricNames;
  }
  public function getReachByFrequencyMetricNames()
  {
    return $this->reachByFrequencyMetricNames;
  }
}

class Google_Service_Dfareporting_ReportSchedule extends Google_Collection
{
  protected $collection_key = 'repeatsOnWeekDays';
  protected $internal_gapi_mappings = array(
  );
  public $active;
  public $every;
  public $expirationDate;
  public $repeats;
  public $repeatsOnWeekDays;
  public $runsOnDayOfMonth;
  public $startDate;


  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setEvery($every)
  {
    $this->every = $every;
  }
  public function getEvery()
  {
    return $this->every;
  }
  public function setExpirationDate($expirationDate)
  {
    $this->expirationDate = $expirationDate;
  }
  public function getExpirationDate()
  {
    return $this->expirationDate;
  }
  public function setRepeats($repeats)
  {
    $this->repeats = $repeats;
  }
  public function getRepeats()
  {
    return $this->repeats;
  }
  public function setRepeatsOnWeekDays($repeatsOnWeekDays)
  {
    $this->repeatsOnWeekDays = $repeatsOnWeekDays;
  }
  public function getRepeatsOnWeekDays()
  {
    return $this->repeatsOnWeekDays;
  }
  public function setRunsOnDayOfMonth($runsOnDayOfMonth)
  {
    $this->runsOnDayOfMonth = $runsOnDayOfMonth;
  }
  public function getRunsOnDayOfMonth()
  {
    return $this->runsOnDayOfMonth;
  }
  public function setStartDate($startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
}

class Google_Service_Dfareporting_ReportsConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $exposureToConversionEnabled;
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  public $reportGenerationTimeZoneId;


  public function setExposureToConversionEnabled($exposureToConversionEnabled)
  {
    $this->exposureToConversionEnabled = $exposureToConversionEnabled;
  }
  public function getExposureToConversionEnabled()
  {
    return $this->exposureToConversionEnabled;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setReportGenerationTimeZoneId($reportGenerationTimeZoneId)
  {
    $this->reportGenerationTimeZoneId = $reportGenerationTimeZoneId;
  }
  public function getReportGenerationTimeZoneId()
  {
    return $this->reportGenerationTimeZoneId;
  }
}

class Google_Service_Dfareporting_RichMediaExitOverride extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customExitUrl;
  public $exitId;
  public $useCustomExitUrl;


  public function setCustomExitUrl($customExitUrl)
  {
    $this->customExitUrl = $customExitUrl;
  }
  public function getCustomExitUrl()
  {
    return $this->customExitUrl;
  }
  public function setExitId($exitId)
  {
    $this->exitId = $exitId;
  }
  public function getExitId()
  {
    return $this->exitId;
  }
  public function setUseCustomExitUrl($useCustomExitUrl)
  {
    $this->useCustomExitUrl = $useCustomExitUrl;
  }
  public function getUseCustomExitUrl()
  {
    return $this->useCustomExitUrl;
  }
}

class Google_Service_Dfareporting_Site extends Google_Collection
{
  protected $collection_key = 'siteContacts';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $approved;
  public $directorySiteId;
  protected $directorySiteIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $directorySiteIdDimensionValueDataType = '';
  public $id;
  protected $idDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $idDimensionValueDataType = '';
  public $keyName;
  public $kind;
  public $name;
  protected $siteContactsType = 'Google_Service_Dfareporting_SiteContact';
  protected $siteContactsDataType = 'array';
  protected $siteSettingsType = 'Google_Service_Dfareporting_SiteSettings';
  protected $siteSettingsDataType = '';
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setApproved($approved)
  {
    $this->approved = $approved;
  }
  public function getApproved()
  {
    return $this->approved;
  }
  public function setDirectorySiteId($directorySiteId)
  {
    $this->directorySiteId = $directorySiteId;
  }
  public function getDirectorySiteId()
  {
    return $this->directorySiteId;
  }
  public function setDirectorySiteIdDimensionValue(Google_Service_Dfareporting_DimensionValue $directorySiteIdDimensionValue)
  {
    $this->directorySiteIdDimensionValue = $directorySiteIdDimensionValue;
  }
  public function getDirectorySiteIdDimensionValue()
  {
    return $this->directorySiteIdDimensionValue;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIdDimensionValue(Google_Service_Dfareporting_DimensionValue $idDimensionValue)
  {
    $this->idDimensionValue = $idDimensionValue;
  }
  public function getIdDimensionValue()
  {
    return $this->idDimensionValue;
  }
  public function setKeyName($keyName)
  {
    $this->keyName = $keyName;
  }
  public function getKeyName()
  {
    return $this->keyName;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSiteContacts($siteContacts)
  {
    $this->siteContacts = $siteContacts;
  }
  public function getSiteContacts()
  {
    return $this->siteContacts;
  }
  public function setSiteSettings(Google_Service_Dfareporting_SiteSettings $siteSettings)
  {
    $this->siteSettings = $siteSettings;
  }
  public function getSiteSettings()
  {
    return $this->siteSettings;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_SiteContact extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $address;
  public $contactType;
  public $email;
  public $firstName;
  public $id;
  public $lastName;
  public $phone;
  public $title;


  public function setAddress($address)
  {
    $this->address = $address;
  }
  public function getAddress()
  {
    return $this->address;
  }
  public function setContactType($contactType)
  {
    $this->contactType = $contactType;
  }
  public function getContactType()
  {
    return $this->contactType;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setFirstName($firstName)
  {
    $this->firstName = $firstName;
  }
  public function getFirstName()
  {
    return $this->firstName;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setLastName($lastName)
  {
    $this->lastName = $lastName;
  }
  public function getLastName()
  {
    return $this->lastName;
  }
  public function setPhone($phone)
  {
    $this->phone = $phone;
  }
  public function getPhone()
  {
    return $this->phone;
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

class Google_Service_Dfareporting_SiteSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $activeViewOptOut;
  protected $creativeSettingsType = 'Google_Service_Dfareporting_CreativeSettings';
  protected $creativeSettingsDataType = '';
  public $disableBrandSafeAds;
  public $disableNewCookie;
  protected $lookbackConfigurationType = 'Google_Service_Dfareporting_LookbackConfiguration';
  protected $lookbackConfigurationDataType = '';
  protected $tagSettingType = 'Google_Service_Dfareporting_TagSetting';
  protected $tagSettingDataType = '';


  public function setActiveViewOptOut($activeViewOptOut)
  {
    $this->activeViewOptOut = $activeViewOptOut;
  }
  public function getActiveViewOptOut()
  {
    return $this->activeViewOptOut;
  }
  public function setCreativeSettings(Google_Service_Dfareporting_CreativeSettings $creativeSettings)
  {
    $this->creativeSettings = $creativeSettings;
  }
  public function getCreativeSettings()
  {
    return $this->creativeSettings;
  }
  public function setDisableBrandSafeAds($disableBrandSafeAds)
  {
    $this->disableBrandSafeAds = $disableBrandSafeAds;
  }
  public function getDisableBrandSafeAds()
  {
    return $this->disableBrandSafeAds;
  }
  public function setDisableNewCookie($disableNewCookie)
  {
    $this->disableNewCookie = $disableNewCookie;
  }
  public function getDisableNewCookie()
  {
    return $this->disableNewCookie;
  }
  public function setLookbackConfiguration(Google_Service_Dfareporting_LookbackConfiguration $lookbackConfiguration)
  {
    $this->lookbackConfiguration = $lookbackConfiguration;
  }
  public function getLookbackConfiguration()
  {
    return $this->lookbackConfiguration;
  }
  public function setTagSetting(Google_Service_Dfareporting_TagSetting $tagSetting)
  {
    $this->tagSetting = $tagSetting;
  }
  public function getTagSetting()
  {
    return $this->tagSetting;
  }
}

class Google_Service_Dfareporting_SitesListResponse extends Google_Collection
{
  protected $collection_key = 'sites';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $sitesType = 'Google_Service_Dfareporting_Site';
  protected $sitesDataType = 'array';


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
  public function setSites($sites)
  {
    $this->sites = $sites;
  }
  public function getSites()
  {
    return $this->sites;
  }
}

class Google_Service_Dfareporting_Size extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $iab;
  public $id;
  public $kind;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setIab($iab)
  {
    $this->iab = $iab;
  }
  public function getIab()
  {
    return $this->iab;
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
  public function setWidth($width)
  {
    $this->width = $width;
  }
  public function getWidth()
  {
    return $this->width;
  }
}

class Google_Service_Dfareporting_SizesListResponse extends Google_Collection
{
  protected $collection_key = 'sizes';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $sizesType = 'Google_Service_Dfareporting_Size';
  protected $sizesDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSizes($sizes)
  {
    $this->sizes = $sizes;
  }
  public function getSizes()
  {
    return $this->sizes;
  }
}

class Google_Service_Dfareporting_SortedDimension extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $name;
  public $sortOrder;


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSortOrder($sortOrder)
  {
    $this->sortOrder = $sortOrder;
  }
  public function getSortOrder()
  {
    return $this->sortOrder;
  }
}

class Google_Service_Dfareporting_Subaccount extends Google_Collection
{
  protected $collection_key = 'availablePermissionIds';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $availablePermissionIds;
  public $id;
  public $kind;
  public $name;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAvailablePermissionIds($availablePermissionIds)
  {
    $this->availablePermissionIds = $availablePermissionIds;
  }
  public function getAvailablePermissionIds()
  {
    return $this->availablePermissionIds;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_SubaccountsListResponse extends Google_Collection
{
  protected $collection_key = 'subaccounts';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $subaccountsType = 'Google_Service_Dfareporting_Subaccount';
  protected $subaccountsDataType = 'array';


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
  public function setSubaccounts($subaccounts)
  {
    $this->subaccounts = $subaccounts;
  }
  public function getSubaccounts()
  {
    return $this->subaccounts;
  }
}

class Google_Service_Dfareporting_TagData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $adId;
  public $clickTag;
  public $creativeId;
  public $format;
  public $impressionTag;


  public function setAdId($adId)
  {
    $this->adId = $adId;
  }
  public function getAdId()
  {
    return $this->adId;
  }
  public function setClickTag($clickTag)
  {
    $this->clickTag = $clickTag;
  }
  public function getClickTag()
  {
    return $this->clickTag;
  }
  public function setCreativeId($creativeId)
  {
    $this->creativeId = $creativeId;
  }
  public function getCreativeId()
  {
    return $this->creativeId;
  }
  public function setFormat($format)
  {
    $this->format = $format;
  }
  public function getFormat()
  {
    return $this->format;
  }
  public function setImpressionTag($impressionTag)
  {
    $this->impressionTag = $impressionTag;
  }
  public function getImpressionTag()
  {
    return $this->impressionTag;
  }
}

class Google_Service_Dfareporting_TagSetting extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $additionalKeyValues;
  public $includeClickThroughUrls;
  public $includeClickTracking;
  public $keywordOption;


  public function setAdditionalKeyValues($additionalKeyValues)
  {
    $this->additionalKeyValues = $additionalKeyValues;
  }
  public function getAdditionalKeyValues()
  {
    return $this->additionalKeyValues;
  }
  public function setIncludeClickThroughUrls($includeClickThroughUrls)
  {
    $this->includeClickThroughUrls = $includeClickThroughUrls;
  }
  public function getIncludeClickThroughUrls()
  {
    return $this->includeClickThroughUrls;
  }
  public function setIncludeClickTracking($includeClickTracking)
  {
    $this->includeClickTracking = $includeClickTracking;
  }
  public function getIncludeClickTracking()
  {
    return $this->includeClickTracking;
  }
  public function setKeywordOption($keywordOption)
  {
    $this->keywordOption = $keywordOption;
  }
  public function getKeywordOption()
  {
    return $this->keywordOption;
  }
}

class Google_Service_Dfareporting_TagSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dynamicTagEnabled;
  public $imageTagEnabled;


  public function setDynamicTagEnabled($dynamicTagEnabled)
  {
    $this->dynamicTagEnabled = $dynamicTagEnabled;
  }
  public function getDynamicTagEnabled()
  {
    return $this->dynamicTagEnabled;
  }
  public function setImageTagEnabled($imageTagEnabled)
  {
    $this->imageTagEnabled = $imageTagEnabled;
  }
  public function getImageTagEnabled()
  {
    return $this->imageTagEnabled;
  }
}

class Google_Service_Dfareporting_TargetWindow extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customHtml;
  public $targetWindowOption;


  public function setCustomHtml($customHtml)
  {
    $this->customHtml = $customHtml;
  }
  public function getCustomHtml()
  {
    return $this->customHtml;
  }
  public function setTargetWindowOption($targetWindowOption)
  {
    $this->targetWindowOption = $targetWindowOption;
  }
  public function getTargetWindowOption()
  {
    return $this->targetWindowOption;
  }
}

class Google_Service_Dfareporting_TargetableRemarketingList extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $active;
  public $advertiserId;
  protected $advertiserIdDimensionValueType = 'Google_Service_Dfareporting_DimensionValue';
  protected $advertiserIdDimensionValueDataType = '';
  public $description;
  public $id;
  public $kind;
  public $lifeSpan;
  public $listSize;
  public $listSource;
  public $name;
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setActive($active)
  {
    $this->active = $active;
  }
  public function getActive()
  {
    return $this->active;
  }
  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
  }
  public function setAdvertiserIdDimensionValue(Google_Service_Dfareporting_DimensionValue $advertiserIdDimensionValue)
  {
    $this->advertiserIdDimensionValue = $advertiserIdDimensionValue;
  }
  public function getAdvertiserIdDimensionValue()
  {
    return $this->advertiserIdDimensionValue;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
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
  public function setLifeSpan($lifeSpan)
  {
    $this->lifeSpan = $lifeSpan;
  }
  public function getLifeSpan()
  {
    return $this->lifeSpan;
  }
  public function setListSize($listSize)
  {
    $this->listSize = $listSize;
  }
  public function getListSize()
  {
    return $this->listSize;
  }
  public function setListSource($listSource)
  {
    $this->listSource = $listSource;
  }
  public function getListSource()
  {
    return $this->listSource;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_TargetableRemarketingListsListResponse extends Google_Collection
{
  protected $collection_key = 'targetableRemarketingLists';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $targetableRemarketingListsType = 'Google_Service_Dfareporting_TargetableRemarketingList';
  protected $targetableRemarketingListsDataType = 'array';


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
  public function setTargetableRemarketingLists($targetableRemarketingLists)
  {
    $this->targetableRemarketingLists = $targetableRemarketingLists;
  }
  public function getTargetableRemarketingLists()
  {
    return $this->targetableRemarketingLists;
  }
}

class Google_Service_Dfareporting_TechnologyTargeting extends Google_Collection
{
  protected $collection_key = 'platformTypes';
  protected $internal_gapi_mappings = array(
  );
  protected $browsersType = 'Google_Service_Dfareporting_Browser';
  protected $browsersDataType = 'array';
  protected $connectionTypesType = 'Google_Service_Dfareporting_ConnectionType';
  protected $connectionTypesDataType = 'array';
  protected $mobileCarriersType = 'Google_Service_Dfareporting_MobileCarrier';
  protected $mobileCarriersDataType = 'array';
  protected $operatingSystemVersionsType = 'Google_Service_Dfareporting_OperatingSystemVersion';
  protected $operatingSystemVersionsDataType = 'array';
  protected $operatingSystemsType = 'Google_Service_Dfareporting_OperatingSystem';
  protected $operatingSystemsDataType = 'array';
  protected $platformTypesType = 'Google_Service_Dfareporting_PlatformType';
  protected $platformTypesDataType = 'array';


  public function setBrowsers($browsers)
  {
    $this->browsers = $browsers;
  }
  public function getBrowsers()
  {
    return $this->browsers;
  }
  public function setConnectionTypes($connectionTypes)
  {
    $this->connectionTypes = $connectionTypes;
  }
  public function getConnectionTypes()
  {
    return $this->connectionTypes;
  }
  public function setMobileCarriers($mobileCarriers)
  {
    $this->mobileCarriers = $mobileCarriers;
  }
  public function getMobileCarriers()
  {
    return $this->mobileCarriers;
  }
  public function setOperatingSystemVersions($operatingSystemVersions)
  {
    $this->operatingSystemVersions = $operatingSystemVersions;
  }
  public function getOperatingSystemVersions()
  {
    return $this->operatingSystemVersions;
  }
  public function setOperatingSystems($operatingSystems)
  {
    $this->operatingSystems = $operatingSystems;
  }
  public function getOperatingSystems()
  {
    return $this->operatingSystems;
  }
  public function setPlatformTypes($platformTypes)
  {
    $this->platformTypes = $platformTypes;
  }
  public function getPlatformTypes()
  {
    return $this->platformTypes;
  }
}

class Google_Service_Dfareporting_ThirdPartyTrackingUrl extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $thirdPartyUrlType;
  public $url;


  public function setThirdPartyUrlType($thirdPartyUrlType)
  {
    $this->thirdPartyUrlType = $thirdPartyUrlType;
  }
  public function getThirdPartyUrlType()
  {
    return $this->thirdPartyUrlType;
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

class Google_Service_Dfareporting_UserDefinedVariableConfiguration extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dataType;
  public $reportName;
  public $variableType;


  public function setDataType($dataType)
  {
    $this->dataType = $dataType;
  }
  public function getDataType()
  {
    return $this->dataType;
  }
  public function setReportName($reportName)
  {
    $this->reportName = $reportName;
  }
  public function getReportName()
  {
    return $this->reportName;
  }
  public function setVariableType($variableType)
  {
    $this->variableType = $variableType;
  }
  public function getVariableType()
  {
    return $this->variableType;
  }
}

class Google_Service_Dfareporting_UserProfile extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $accountName;
  public $etag;
  public $kind;
  public $profileId;
  public $subAccountId;
  public $subAccountName;
  public $userName;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setAccountName($accountName)
  {
    $this->accountName = $accountName;
  }
  public function getAccountName()
  {
    return $this->accountName;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setProfileId($profileId)
  {
    $this->profileId = $profileId;
  }
  public function getProfileId()
  {
    return $this->profileId;
  }
  public function setSubAccountId($subAccountId)
  {
    $this->subAccountId = $subAccountId;
  }
  public function getSubAccountId()
  {
    return $this->subAccountId;
  }
  public function setSubAccountName($subAccountName)
  {
    $this->subAccountName = $subAccountName;
  }
  public function getSubAccountName()
  {
    return $this->subAccountName;
  }
  public function setUserName($userName)
  {
    $this->userName = $userName;
  }
  public function getUserName()
  {
    return $this->userName;
  }
}

class Google_Service_Dfareporting_UserProfileList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Dfareporting_UserProfile';
  protected $itemsDataType = 'array';
  public $kind;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
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

class Google_Service_Dfareporting_UserRole extends Google_Collection
{
  protected $collection_key = 'permissions';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $defaultUserRole;
  public $id;
  public $kind;
  public $name;
  public $parentUserRoleId;
  protected $permissionsType = 'Google_Service_Dfareporting_UserRolePermission';
  protected $permissionsDataType = 'array';
  public $subaccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setDefaultUserRole($defaultUserRole)
  {
    $this->defaultUserRole = $defaultUserRole;
  }
  public function getDefaultUserRole()
  {
    return $this->defaultUserRole;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setParentUserRoleId($parentUserRoleId)
  {
    $this->parentUserRoleId = $parentUserRoleId;
  }
  public function getParentUserRoleId()
  {
    return $this->parentUserRoleId;
  }
  public function setPermissions($permissions)
  {
    $this->permissions = $permissions;
  }
  public function getPermissions()
  {
    return $this->permissions;
  }
  public function setSubaccountId($subaccountId)
  {
    $this->subaccountId = $subaccountId;
  }
  public function getSubaccountId()
  {
    return $this->subaccountId;
  }
}

class Google_Service_Dfareporting_UserRolePermission extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $availability;
  public $id;
  public $kind;
  public $name;
  public $permissionGroupId;


  public function setAvailability($availability)
  {
    $this->availability = $availability;
  }
  public function getAvailability()
  {
    return $this->availability;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPermissionGroupId($permissionGroupId)
  {
    $this->permissionGroupId = $permissionGroupId;
  }
  public function getPermissionGroupId()
  {
    return $this->permissionGroupId;
  }
}

class Google_Service_Dfareporting_UserRolePermissionGroup extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Dfareporting_UserRolePermissionGroupsListResponse extends Google_Collection
{
  protected $collection_key = 'userRolePermissionGroups';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $userRolePermissionGroupsType = 'Google_Service_Dfareporting_UserRolePermissionGroup';
  protected $userRolePermissionGroupsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setUserRolePermissionGroups($userRolePermissionGroups)
  {
    $this->userRolePermissionGroups = $userRolePermissionGroups;
  }
  public function getUserRolePermissionGroups()
  {
    return $this->userRolePermissionGroups;
  }
}

class Google_Service_Dfareporting_UserRolePermissionsListResponse extends Google_Collection
{
  protected $collection_key = 'userRolePermissions';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $userRolePermissionsType = 'Google_Service_Dfareporting_UserRolePermission';
  protected $userRolePermissionsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setUserRolePermissions($userRolePermissions)
  {
    $this->userRolePermissions = $userRolePermissions;
  }
  public function getUserRolePermissions()
  {
    return $this->userRolePermissions;
  }
}

class Google_Service_Dfareporting_UserRolesListResponse extends Google_Collection
{
  protected $collection_key = 'userRoles';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  public $nextPageToken;
  protected $userRolesType = 'Google_Service_Dfareporting_UserRole';
  protected $userRolesDataType = 'array';


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
  public function setUserRoles($userRoles)
  {
    $this->userRoles = $userRoles;
  }
  public function getUserRoles()
  {
    return $this->userRoles;
  }
}
