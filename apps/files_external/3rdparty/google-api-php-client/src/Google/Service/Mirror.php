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
 * Service definition for Mirror (v1).
 *
 * <p>
 * API for interacting with Glass users via the timeline.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/glass" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Mirror extends Google_Service
{
  /** View your location. */
  const GLASS_LOCATION =
      "https://www.googleapis.com/auth/glass.location";
  /** View and manage your Glass timeline. */
  const GLASS_TIMELINE =
      "https://www.googleapis.com/auth/glass.timeline";

  public $accounts;
  public $contacts;
  public $locations;
  public $settings;
  public $subscriptions;
  public $timeline;
  public $timeline_attachments;
  

  /**
   * Constructs the internal representation of the Mirror service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'mirror/v1/';
    $this->version = 'v1';
    $this->serviceName = 'mirror';

    $this->accounts = new Google_Service_Mirror_Accounts_Resource(
        $this,
        $this->serviceName,
        'accounts',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'accounts/{userToken}/{accountType}/{accountName}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userToken' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'accountType' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'accountName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->contacts = new Google_Service_Mirror_Contacts_Resource(
        $this,
        $this->serviceName,
        'contacts',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'contacts/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'contacts/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'contacts',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'contacts',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),'patch' => array(
              'path' => 'contacts/{id}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'contacts/{id}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->locations = new Google_Service_Mirror_Locations_Resource(
        $this,
        $this->serviceName,
        'locations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'locations/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'locations',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->settings = new Google_Service_Mirror_Settings_Resource(
        $this,
        $this->serviceName,
        'settings',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'settings/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->subscriptions = new Google_Service_Mirror_Subscriptions_Resource(
        $this,
        $this->serviceName,
        'subscriptions',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'subscriptions/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'subscriptions',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),'update' => array(
              'path' => 'subscriptions/{id}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->timeline = new Google_Service_Mirror_Timeline_Resource(
        $this,
        $this->serviceName,
        'timeline',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'timeline/{id}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'timeline/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'timeline',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'timeline',
              'httpMethod' => 'GET',
              'parameters' => array(
                'orderBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'includeDeleted' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sourceItemId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pinnedOnly' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'bundleId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'timeline/{id}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'timeline/{id}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->timeline_attachments = new Google_Service_Mirror_TimelineAttachments_Resource(
        $this,
        $this->serviceName,
        'attachments',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'timeline/{itemId}/attachments/{attachmentId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'itemId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'attachmentId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'timeline/{itemId}/attachments/{attachmentId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'itemId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'attachmentId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'timeline/{itemId}/attachments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'itemId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'timeline/{itemId}/attachments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'itemId' => array(
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
 * The "accounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $accounts = $mirrorService->accounts;
 *  </code>
 */
class Google_Service_Mirror_Accounts_Resource extends Google_Service_Resource
{

  /**
   * Inserts a new account for a user (accounts.insert)
   *
   * @param string $userToken The ID for the user.
   * @param string $accountType Account type to be passed to Android Account
   * Manager.
   * @param string $accountName The name of the account to be passed to the
   * Android Account Manager.
   * @param Google_Account $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Account
   */
  public function insert($userToken, $accountType, $accountName, Google_Service_Mirror_Account $postBody, $optParams = array())
  {
    $params = array('userToken' => $userToken, 'accountType' => $accountType, 'accountName' => $accountName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Mirror_Account");
  }
}

/**
 * The "contacts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $contacts = $mirrorService->contacts;
 *  </code>
 */
class Google_Service_Mirror_Contacts_Resource extends Google_Service_Resource
{

  /**
   * Deletes a contact. (contacts.delete)
   *
   * @param string $id The ID of the contact.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets a single contact by ID. (contacts.get)
   *
   * @param string $id The ID of the contact.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Contact
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Mirror_Contact");
  }

  /**
   * Inserts a new contact. (contacts.insert)
   *
   * @param Google_Contact $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Contact
   */
  public function insert(Google_Service_Mirror_Contact $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Mirror_Contact");
  }

  /**
   * Retrieves a list of contacts for the authenticated user.
   * (contacts.listContacts)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_ContactsListResponse
   */
  public function listContacts($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Mirror_ContactsListResponse");
  }

  /**
   * Updates a contact in place. This method supports patch semantics.
   * (contacts.patch)
   *
   * @param string $id The ID of the contact.
   * @param Google_Contact $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Contact
   */
  public function patch($id, Google_Service_Mirror_Contact $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Mirror_Contact");
  }

  /**
   * Updates a contact in place. (contacts.update)
   *
   * @param string $id The ID of the contact.
   * @param Google_Contact $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Contact
   */
  public function update($id, Google_Service_Mirror_Contact $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Mirror_Contact");
  }
}

/**
 * The "locations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $locations = $mirrorService->locations;
 *  </code>
 */
class Google_Service_Mirror_Locations_Resource extends Google_Service_Resource
{

  /**
   * Gets a single location by ID. (locations.get)
   *
   * @param string $id The ID of the location or latest for the last known
   * location.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Location
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Mirror_Location");
  }

  /**
   * Retrieves a list of locations for the user. (locations.listLocations)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_LocationsListResponse
   */
  public function listLocations($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Mirror_LocationsListResponse");
  }
}

/**
 * The "settings" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $settings = $mirrorService->settings;
 *  </code>
 */
class Google_Service_Mirror_Settings_Resource extends Google_Service_Resource
{

  /**
   * Gets a single setting by ID. (settings.get)
   *
   * @param string $id The ID of the setting. The following IDs are valid: -
   * locale - The key to the user’s language/locale (BCP 47 identifier) that
   * Glassware should use to render localized content.  - timezone - The key to
   * the user’s current time zone region as defined in the tz database. Example:
   * America/Los_Angeles.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Setting
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Mirror_Setting");
  }
}

/**
 * The "subscriptions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $subscriptions = $mirrorService->subscriptions;
 *  </code>
 */
class Google_Service_Mirror_Subscriptions_Resource extends Google_Service_Resource
{

  /**
   * Deletes a subscription. (subscriptions.delete)
   *
   * @param string $id The ID of the subscription.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a new subscription. (subscriptions.insert)
   *
   * @param Google_Subscription $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Subscription
   */
  public function insert(Google_Service_Mirror_Subscription $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Mirror_Subscription");
  }

  /**
   * Retrieves a list of subscriptions for the authenticated user and service.
   * (subscriptions.listSubscriptions)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_SubscriptionsListResponse
   */
  public function listSubscriptions($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Mirror_SubscriptionsListResponse");
  }

  /**
   * Updates an existing subscription in place. (subscriptions.update)
   *
   * @param string $id The ID of the subscription.
   * @param Google_Subscription $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Subscription
   */
  public function update($id, Google_Service_Mirror_Subscription $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Mirror_Subscription");
  }
}

/**
 * The "timeline" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $timeline = $mirrorService->timeline;
 *  </code>
 */
class Google_Service_Mirror_Timeline_Resource extends Google_Service_Resource
{

  /**
   * Deletes a timeline item. (timeline.delete)
   *
   * @param string $id The ID of the timeline item.
   * @param array $optParams Optional parameters.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets a single timeline item by ID. (timeline.get)
   *
   * @param string $id The ID of the timeline item.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_TimelineItem
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Mirror_TimelineItem");
  }

  /**
   * Inserts a new item into the timeline. (timeline.insert)
   *
   * @param Google_TimelineItem $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_TimelineItem
   */
  public function insert(Google_Service_Mirror_TimelineItem $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Mirror_TimelineItem");
  }

  /**
   * Retrieves a list of timeline items for the authenticated user.
   * (timeline.listTimeline)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderBy Controls the order in which timeline items are
   * returned.
   * @opt_param bool includeDeleted If true, tombstone records for deleted items
   * will be returned.
   * @opt_param string maxResults The maximum number of items to include in the
   * response, used for paging.
   * @opt_param string pageToken Token for the page of results to return.
   * @opt_param string sourceItemId If provided, only items with the given
   * sourceItemId will be returned.
   * @opt_param bool pinnedOnly If true, only pinned items will be returned.
   * @opt_param string bundleId If provided, only items with the given bundleId
   * will be returned.
   * @return Google_Service_Mirror_TimelineListResponse
   */
  public function listTimeline($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Mirror_TimelineListResponse");
  }

  /**
   * Updates a timeline item in place. This method supports patch semantics.
   * (timeline.patch)
   *
   * @param string $id The ID of the timeline item.
   * @param Google_TimelineItem $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_TimelineItem
   */
  public function patch($id, Google_Service_Mirror_TimelineItem $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Mirror_TimelineItem");
  }

  /**
   * Updates a timeline item in place. (timeline.update)
   *
   * @param string $id The ID of the timeline item.
   * @param Google_TimelineItem $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_TimelineItem
   */
  public function update($id, Google_Service_Mirror_TimelineItem $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Mirror_TimelineItem");
  }
}

/**
 * The "attachments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $mirrorService = new Google_Service_Mirror(...);
 *   $attachments = $mirrorService->attachments;
 *  </code>
 */
class Google_Service_Mirror_TimelineAttachments_Resource extends Google_Service_Resource
{

  /**
   * Deletes an attachment from a timeline item. (attachments.delete)
   *
   * @param string $itemId The ID of the timeline item the attachment belongs to.
   * @param string $attachmentId The ID of the attachment.
   * @param array $optParams Optional parameters.
   */
  public function delete($itemId, $attachmentId, $optParams = array())
  {
    $params = array('itemId' => $itemId, 'attachmentId' => $attachmentId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves an attachment on a timeline item by item ID and attachment ID.
   * (attachments.get)
   *
   * @param string $itemId The ID of the timeline item the attachment belongs to.
   * @param string $attachmentId The ID of the attachment.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Attachment
   */
  public function get($itemId, $attachmentId, $optParams = array())
  {
    $params = array('itemId' => $itemId, 'attachmentId' => $attachmentId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Mirror_Attachment");
  }

  /**
   * Adds a new attachment to a timeline item. (attachments.insert)
   *
   * @param string $itemId The ID of the timeline item the attachment belongs to.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_Attachment
   */
  public function insert($itemId, $optParams = array())
  {
    $params = array('itemId' => $itemId);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Mirror_Attachment");
  }

  /**
   * Returns a list of attachments for a timeline item.
   * (attachments.listTimelineAttachments)
   *
   * @param string $itemId The ID of the timeline item whose attachments should be
   * listed.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Mirror_AttachmentsListResponse
   */
  public function listTimelineAttachments($itemId, $optParams = array())
  {
    $params = array('itemId' => $itemId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Mirror_AttachmentsListResponse");
  }
}




class Google_Service_Mirror_Account extends Google_Collection
{
  protected $collection_key = 'userData';
  protected $internal_gapi_mappings = array(
  );
  protected $authTokensType = 'Google_Service_Mirror_AuthToken';
  protected $authTokensDataType = 'array';
  public $features;
  public $password;
  protected $userDataType = 'Google_Service_Mirror_UserData';
  protected $userDataDataType = 'array';


  public function setAuthTokens($authTokens)
  {
    $this->authTokens = $authTokens;
  }
  public function getAuthTokens()
  {
    return $this->authTokens;
  }
  public function setFeatures($features)
  {
    $this->features = $features;
  }
  public function getFeatures()
  {
    return $this->features;
  }
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
  }
  public function setUserData($userData)
  {
    $this->userData = $userData;
  }
  public function getUserData()
  {
    return $this->userData;
  }
}

class Google_Service_Mirror_Attachment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contentType;
  public $contentUrl;
  public $id;
  public $isProcessingContent;


  public function setContentType($contentType)
  {
    $this->contentType = $contentType;
  }
  public function getContentType()
  {
    return $this->contentType;
  }
  public function setContentUrl($contentUrl)
  {
    $this->contentUrl = $contentUrl;
  }
  public function getContentUrl()
  {
    return $this->contentUrl;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIsProcessingContent($isProcessingContent)
  {
    $this->isProcessingContent = $isProcessingContent;
  }
  public function getIsProcessingContent()
  {
    return $this->isProcessingContent;
  }
}

class Google_Service_Mirror_AttachmentsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Mirror_Attachment';
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

class Google_Service_Mirror_AuthToken extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $authToken;
  public $type;


  public function setAuthToken($authToken)
  {
    $this->authToken = $authToken;
  }
  public function getAuthToken()
  {
    return $this->authToken;
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

class Google_Service_Mirror_Command extends Google_Model
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

class Google_Service_Mirror_Contact extends Google_Collection
{
  protected $collection_key = 'sharingFeatures';
  protected $internal_gapi_mappings = array(
  );
  protected $acceptCommandsType = 'Google_Service_Mirror_Command';
  protected $acceptCommandsDataType = 'array';
  public $acceptTypes;
  public $displayName;
  public $id;
  public $imageUrls;
  public $kind;
  public $phoneNumber;
  public $priority;
  public $sharingFeatures;
  public $source;
  public $speakableName;
  public $type;


  public function setAcceptCommands($acceptCommands)
  {
    $this->acceptCommands = $acceptCommands;
  }
  public function getAcceptCommands()
  {
    return $this->acceptCommands;
  }
  public function setAcceptTypes($acceptTypes)
  {
    $this->acceptTypes = $acceptTypes;
  }
  public function getAcceptTypes()
  {
    return $this->acceptTypes;
  }
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setImageUrls($imageUrls)
  {
    $this->imageUrls = $imageUrls;
  }
  public function getImageUrls()
  {
    return $this->imageUrls;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  public function getPriority()
  {
    return $this->priority;
  }
  public function setSharingFeatures($sharingFeatures)
  {
    $this->sharingFeatures = $sharingFeatures;
  }
  public function getSharingFeatures()
  {
    return $this->sharingFeatures;
  }
  public function setSource($source)
  {
    $this->source = $source;
  }
  public function getSource()
  {
    return $this->source;
  }
  public function setSpeakableName($speakableName)
  {
    $this->speakableName = $speakableName;
  }
  public function getSpeakableName()
  {
    return $this->speakableName;
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

class Google_Service_Mirror_ContactsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Mirror_Contact';
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

class Google_Service_Mirror_Location extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accuracy;
  public $address;
  public $displayName;
  public $id;
  public $kind;
  public $latitude;
  public $longitude;
  public $timestamp;


  public function setAccuracy($accuracy)
  {
    $this->accuracy = $accuracy;
  }
  public function getAccuracy()
  {
    return $this->accuracy;
  }
  public function setAddress($address)
  {
    $this->address = $address;
  }
  public function getAddress()
  {
    return $this->address;
  }
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
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
  public function setLatitude($latitude)
  {
    $this->latitude = $latitude;
  }
  public function getLatitude()
  {
    return $this->latitude;
  }
  public function setLongitude($longitude)
  {
    $this->longitude = $longitude;
  }
  public function getLongitude()
  {
    return $this->longitude;
  }
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }
  public function getTimestamp()
  {
    return $this->timestamp;
  }
}

class Google_Service_Mirror_LocationsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Mirror_Location';
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

class Google_Service_Mirror_MenuItem extends Google_Collection
{
  protected $collection_key = 'values';
  protected $internal_gapi_mappings = array(
        "contextualCommand" => "contextual_command",
  );
  public $action;
  public $contextualCommand;
  public $id;
  public $payload;
  public $removeWhenSelected;
  protected $valuesType = 'Google_Service_Mirror_MenuValue';
  protected $valuesDataType = 'array';


  public function setAction($action)
  {
    $this->action = $action;
  }
  public function getAction()
  {
    return $this->action;
  }
  public function setContextualCommand($contextualCommand)
  {
    $this->contextualCommand = $contextualCommand;
  }
  public function getContextualCommand()
  {
    return $this->contextualCommand;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setPayload($payload)
  {
    $this->payload = $payload;
  }
  public function getPayload()
  {
    return $this->payload;
  }
  public function setRemoveWhenSelected($removeWhenSelected)
  {
    $this->removeWhenSelected = $removeWhenSelected;
  }
  public function getRemoveWhenSelected()
  {
    return $this->removeWhenSelected;
  }
  public function setValues($values)
  {
    $this->values = $values;
  }
  public function getValues()
  {
    return $this->values;
  }
}

class Google_Service_Mirror_MenuValue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $displayName;
  public $iconUrl;
  public $state;


  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setIconUrl($iconUrl)
  {
    $this->iconUrl = $iconUrl;
  }
  public function getIconUrl()
  {
    return $this->iconUrl;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
}

class Google_Service_Mirror_Notification extends Google_Collection
{
  protected $collection_key = 'userActions';
  protected $internal_gapi_mappings = array(
  );
  public $collection;
  public $itemId;
  public $operation;
  protected $userActionsType = 'Google_Service_Mirror_UserAction';
  protected $userActionsDataType = 'array';
  public $userToken;
  public $verifyToken;


  public function setCollection($collection)
  {
    $this->collection = $collection;
  }
  public function getCollection()
  {
    return $this->collection;
  }
  public function setItemId($itemId)
  {
    $this->itemId = $itemId;
  }
  public function getItemId()
  {
    return $this->itemId;
  }
  public function setOperation($operation)
  {
    $this->operation = $operation;
  }
  public function getOperation()
  {
    return $this->operation;
  }
  public function setUserActions($userActions)
  {
    $this->userActions = $userActions;
  }
  public function getUserActions()
  {
    return $this->userActions;
  }
  public function setUserToken($userToken)
  {
    $this->userToken = $userToken;
  }
  public function getUserToken()
  {
    return $this->userToken;
  }
  public function setVerifyToken($verifyToken)
  {
    $this->verifyToken = $verifyToken;
  }
  public function getVerifyToken()
  {
    return $this->verifyToken;
  }
}

class Google_Service_Mirror_NotificationConfig extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $deliveryTime;
  public $level;


  public function setDeliveryTime($deliveryTime)
  {
    $this->deliveryTime = $deliveryTime;
  }
  public function getDeliveryTime()
  {
    return $this->deliveryTime;
  }
  public function setLevel($level)
  {
    $this->level = $level;
  }
  public function getLevel()
  {
    return $this->level;
  }
}

class Google_Service_Mirror_Setting extends Google_Model
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

class Google_Service_Mirror_Subscription extends Google_Collection
{
  protected $collection_key = 'operation';
  protected $internal_gapi_mappings = array(
  );
  public $callbackUrl;
  public $collection;
  public $id;
  public $kind;
  protected $notificationType = 'Google_Service_Mirror_Notification';
  protected $notificationDataType = '';
  public $operation;
  public $updated;
  public $userToken;
  public $verifyToken;


  public function setCallbackUrl($callbackUrl)
  {
    $this->callbackUrl = $callbackUrl;
  }
  public function getCallbackUrl()
  {
    return $this->callbackUrl;
  }
  public function setCollection($collection)
  {
    $this->collection = $collection;
  }
  public function getCollection()
  {
    return $this->collection;
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
  public function setNotification(Google_Service_Mirror_Notification $notification)
  {
    $this->notification = $notification;
  }
  public function getNotification()
  {
    return $this->notification;
  }
  public function setOperation($operation)
  {
    $this->operation = $operation;
  }
  public function getOperation()
  {
    return $this->operation;
  }
  public function setUpdated($updated)
  {
    $this->updated = $updated;
  }
  public function getUpdated()
  {
    return $this->updated;
  }
  public function setUserToken($userToken)
  {
    $this->userToken = $userToken;
  }
  public function getUserToken()
  {
    return $this->userToken;
  }
  public function setVerifyToken($verifyToken)
  {
    $this->verifyToken = $verifyToken;
  }
  public function getVerifyToken()
  {
    return $this->verifyToken;
  }
}

class Google_Service_Mirror_SubscriptionsListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Mirror_Subscription';
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

class Google_Service_Mirror_TimelineItem extends Google_Collection
{
  protected $collection_key = 'recipients';
  protected $internal_gapi_mappings = array(
  );
  protected $attachmentsType = 'Google_Service_Mirror_Attachment';
  protected $attachmentsDataType = 'array';
  public $bundleId;
  public $canonicalUrl;
  public $created;
  protected $creatorType = 'Google_Service_Mirror_Contact';
  protected $creatorDataType = '';
  public $displayTime;
  public $etag;
  public $html;
  public $id;
  public $inReplyTo;
  public $isBundleCover;
  public $isDeleted;
  public $isPinned;
  public $kind;
  protected $locationType = 'Google_Service_Mirror_Location';
  protected $locationDataType = '';
  protected $menuItemsType = 'Google_Service_Mirror_MenuItem';
  protected $menuItemsDataType = 'array';
  protected $notificationType = 'Google_Service_Mirror_NotificationConfig';
  protected $notificationDataType = '';
  public $pinScore;
  protected $recipientsType = 'Google_Service_Mirror_Contact';
  protected $recipientsDataType = 'array';
  public $selfLink;
  public $sourceItemId;
  public $speakableText;
  public $speakableType;
  public $text;
  public $title;
  public $updated;


  public function setAttachments($attachments)
  {
    $this->attachments = $attachments;
  }
  public function getAttachments()
  {
    return $this->attachments;
  }
  public function setBundleId($bundleId)
  {
    $this->bundleId = $bundleId;
  }
  public function getBundleId()
  {
    return $this->bundleId;
  }
  public function setCanonicalUrl($canonicalUrl)
  {
    $this->canonicalUrl = $canonicalUrl;
  }
  public function getCanonicalUrl()
  {
    return $this->canonicalUrl;
  }
  public function setCreated($created)
  {
    $this->created = $created;
  }
  public function getCreated()
  {
    return $this->created;
  }
  public function setCreator(Google_Service_Mirror_Contact $creator)
  {
    $this->creator = $creator;
  }
  public function getCreator()
  {
    return $this->creator;
  }
  public function setDisplayTime($displayTime)
  {
    $this->displayTime = $displayTime;
  }
  public function getDisplayTime()
  {
    return $this->displayTime;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setHtml($html)
  {
    $this->html = $html;
  }
  public function getHtml()
  {
    return $this->html;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setInReplyTo($inReplyTo)
  {
    $this->inReplyTo = $inReplyTo;
  }
  public function getInReplyTo()
  {
    return $this->inReplyTo;
  }
  public function setIsBundleCover($isBundleCover)
  {
    $this->isBundleCover = $isBundleCover;
  }
  public function getIsBundleCover()
  {
    return $this->isBundleCover;
  }
  public function setIsDeleted($isDeleted)
  {
    $this->isDeleted = $isDeleted;
  }
  public function getIsDeleted()
  {
    return $this->isDeleted;
  }
  public function setIsPinned($isPinned)
  {
    $this->isPinned = $isPinned;
  }
  public function getIsPinned()
  {
    return $this->isPinned;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLocation(Google_Service_Mirror_Location $location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMenuItems($menuItems)
  {
    $this->menuItems = $menuItems;
  }
  public function getMenuItems()
  {
    return $this->menuItems;
  }
  public function setNotification(Google_Service_Mirror_NotificationConfig $notification)
  {
    $this->notification = $notification;
  }
  public function getNotification()
  {
    return $this->notification;
  }
  public function setPinScore($pinScore)
  {
    $this->pinScore = $pinScore;
  }
  public function getPinScore()
  {
    return $this->pinScore;
  }
  public function setRecipients($recipients)
  {
    $this->recipients = $recipients;
  }
  public function getRecipients()
  {
    return $this->recipients;
  }
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSourceItemId($sourceItemId)
  {
    $this->sourceItemId = $sourceItemId;
  }
  public function getSourceItemId()
  {
    return $this->sourceItemId;
  }
  public function setSpeakableText($speakableText)
  {
    $this->speakableText = $speakableText;
  }
  public function getSpeakableText()
  {
    return $this->speakableText;
  }
  public function setSpeakableType($speakableType)
  {
    $this->speakableType = $speakableType;
  }
  public function getSpeakableType()
  {
    return $this->speakableType;
  }
  public function setText($text)
  {
    $this->text = $text;
  }
  public function getText()
  {
    return $this->text;
  }
  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function getTitle()
  {
    return $this->title;
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

class Google_Service_Mirror_TimelineListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Mirror_TimelineItem';
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

class Google_Service_Mirror_UserAction extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $payload;
  public $type;


  public function setPayload($payload)
  {
    $this->payload = $payload;
  }
  public function getPayload()
  {
    return $this->payload;
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

class Google_Service_Mirror_UserData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $key;
  public $value;


  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
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
