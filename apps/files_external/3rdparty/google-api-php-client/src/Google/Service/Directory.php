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
 * Service definition for Directory (directory_v1).
 *
 * <p>
 * The Admin SDK Directory API lets you view and manage enterprise resources
 * such as users and groups, administrative notifications, security features,
 * and more.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/admin-sdk/directory/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Directory extends Google_Service
{
  /** View and manage customer related information. */
  const ADMIN_DIRECTORY_CUSTOMER =
      "https://www.googleapis.com/auth/admin.directory.customer";
  /** View customer related information. */
  const ADMIN_DIRECTORY_CUSTOMER_READONLY =
      "https://www.googleapis.com/auth/admin.directory.customer.readonly";
  /** View and manage your Chrome OS devices' metadata. */
  const ADMIN_DIRECTORY_DEVICE_CHROMEOS =
      "https://www.googleapis.com/auth/admin.directory.device.chromeos";
  /** View your Chrome OS devices' metadata. */
  const ADMIN_DIRECTORY_DEVICE_CHROMEOS_READONLY =
      "https://www.googleapis.com/auth/admin.directory.device.chromeos.readonly";
  /** View and manage your mobile devices' metadata. */
  const ADMIN_DIRECTORY_DEVICE_MOBILE =
      "https://www.googleapis.com/auth/admin.directory.device.mobile";
  /** Manage your mobile devices by performing administrative tasks. */
  const ADMIN_DIRECTORY_DEVICE_MOBILE_ACTION =
      "https://www.googleapis.com/auth/admin.directory.device.mobile.action";
  /** View your mobile devices' metadata. */
  const ADMIN_DIRECTORY_DEVICE_MOBILE_READONLY =
      "https://www.googleapis.com/auth/admin.directory.device.mobile.readonly";
  /** View and manage the provisioning of domains for your customers. */
  const ADMIN_DIRECTORY_DOMAIN =
      "https://www.googleapis.com/auth/admin.directory.domain";
  /** View domains related to your customers. */
  const ADMIN_DIRECTORY_DOMAIN_READONLY =
      "https://www.googleapis.com/auth/admin.directory.domain.readonly";
  /** View and manage the provisioning of groups on your domain. */
  const ADMIN_DIRECTORY_GROUP =
      "https://www.googleapis.com/auth/admin.directory.group";
  /** View and manage group subscriptions on your domain. */
  const ADMIN_DIRECTORY_GROUP_MEMBER =
      "https://www.googleapis.com/auth/admin.directory.group.member";
  /** View group subscriptions on your domain. */
  const ADMIN_DIRECTORY_GROUP_MEMBER_READONLY =
      "https://www.googleapis.com/auth/admin.directory.group.member.readonly";
  /** View groups on your domain. */
  const ADMIN_DIRECTORY_GROUP_READONLY =
      "https://www.googleapis.com/auth/admin.directory.group.readonly";
  /** View and manage notifications received on your domain. */
  const ADMIN_DIRECTORY_NOTIFICATIONS =
      "https://www.googleapis.com/auth/admin.directory.notifications";
  /** View and manage organization units on your domain. */
  const ADMIN_DIRECTORY_ORGUNIT =
      "https://www.googleapis.com/auth/admin.directory.orgunit";
  /** View organization units on your domain. */
  const ADMIN_DIRECTORY_ORGUNIT_READONLY =
      "https://www.googleapis.com/auth/admin.directory.orgunit.readonly";
  /** Manage delegated admin roles for your domain. */
  const ADMIN_DIRECTORY_ROLEMANAGEMENT =
      "https://www.googleapis.com/auth/admin.directory.rolemanagement";
  /** View delegated admin roles for your domain. */
  const ADMIN_DIRECTORY_ROLEMANAGEMENT_READONLY =
      "https://www.googleapis.com/auth/admin.directory.rolemanagement.readonly";
  /** View and manage the provisioning of users on your domain. */
  const ADMIN_DIRECTORY_USER =
      "https://www.googleapis.com/auth/admin.directory.user";
  /** View and manage user aliases on your domain. */
  const ADMIN_DIRECTORY_USER_ALIAS =
      "https://www.googleapis.com/auth/admin.directory.user.alias";
  /** View user aliases on your domain. */
  const ADMIN_DIRECTORY_USER_ALIAS_READONLY =
      "https://www.googleapis.com/auth/admin.directory.user.alias.readonly";
  /** View users on your domain. */
  const ADMIN_DIRECTORY_USER_READONLY =
      "https://www.googleapis.com/auth/admin.directory.user.readonly";
  /** Manage data access permissions for users on your domain. */
  const ADMIN_DIRECTORY_USER_SECURITY =
      "https://www.googleapis.com/auth/admin.directory.user.security";
  /** View and manage the provisioning of user schemas on your domain. */
  const ADMIN_DIRECTORY_USERSCHEMA =
      "https://www.googleapis.com/auth/admin.directory.userschema";
  /** View user schemas on your domain. */
  const ADMIN_DIRECTORY_USERSCHEMA_READONLY =
      "https://www.googleapis.com/auth/admin.directory.userschema.readonly";

  public $asps;
  public $channels;
  public $chromeosdevices;
  public $customers;
  public $domainAliases;
  public $domains;
  public $groups;
  public $groups_aliases;
  public $members;
  public $mobiledevices;
  public $notifications;
  public $orgunits;
  public $privileges;
  public $roleAssignments;
  public $roles;
  public $schemas;
  public $tokens;
  public $users;
  public $users_aliases;
  public $users_photos;
  public $verificationCodes;
  

  /**
   * Constructs the internal representation of the Directory service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'admin/directory/v1/';
    $this->version = 'directory_v1';
    $this->serviceName = 'admin';

    $this->asps = new Google_Service_Directory_Asps_Resource(
        $this,
        $this->serviceName,
        'asps',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/{userKey}/asps/{codeId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'codeId' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'users/{userKey}/asps/{codeId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'codeId' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'users/{userKey}/asps',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->channels = new Google_Service_Directory_Channels_Resource(
        $this,
        $this->serviceName,
        'channels',
        array(
          'methods' => array(
            'stop' => array(
              'path' => '/admin/directory_v1/channels/stop',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->chromeosdevices = new Google_Service_Directory_Chromeosdevices_Resource(
        $this,
        $this->serviceName,
        'chromeosdevices',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'customer/{customerId}/devices/chromeos/{deviceId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deviceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customerId}/devices/chromeos',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderBy' => array(
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
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'customer/{customerId}/devices/chromeos/{deviceId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deviceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'customer/{customerId}/devices/chromeos/{deviceId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'deviceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->customers = new Google_Service_Directory_Customers_Resource(
        $this,
        $this->serviceName,
        'customers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'customers/{customerKey}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'customers/{customerKey}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customerKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'customers/{customerKey}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customerKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->domainAliases = new Google_Service_Directory_DomainAliases_Resource(
        $this,
        $this->serviceName,
        'domainAliases',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customer}/domainaliases/{domainAliasName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'domainAliasName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customer}/domainaliases/{domainAliasName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'domainAliasName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customer}/domainaliases',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customer}/domainaliases',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'parentDomainName' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->domains = new Google_Service_Directory_Domains_Resource(
        $this,
        $this->serviceName,
        'domains',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customer}/domains/{domainName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'domainName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customer}/domains/{domainName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'domainName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customer}/domains',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customer}/domains',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->groups = new Google_Service_Directory_Groups_Resource(
        $this,
        $this->serviceName,
        'groups',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'groups/{groupKey}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'groups/{groupKey}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'groups',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'groups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'domain' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'userKey' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'groups/{groupKey}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'groups/{groupKey}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->groups_aliases = new Google_Service_Directory_GroupsAliases_Resource(
        $this,
        $this->serviceName,
        'aliases',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'groups/{groupKey}/aliases/{alias}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'alias' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'groups/{groupKey}/aliases',
              'httpMethod' => 'POST',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'groups/{groupKey}/aliases',
              'httpMethod' => 'GET',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->members = new Google_Service_Directory_Members_Resource(
        $this,
        $this->serviceName,
        'members',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'groups/{groupKey}/members/{memberKey}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'memberKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'groups/{groupKey}/members/{memberKey}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'memberKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'groups/{groupKey}/members',
              'httpMethod' => 'POST',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'groups/{groupKey}/members',
              'httpMethod' => 'GET',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'roles' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'patch' => array(
              'path' => 'groups/{groupKey}/members/{memberKey}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'memberKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'groups/{groupKey}/members/{memberKey}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'groupKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'memberKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->mobiledevices = new Google_Service_Directory_Mobiledevices_Resource(
        $this,
        $this->serviceName,
        'mobiledevices',
        array(
          'methods' => array(
            'action' => array(
              'path' => 'customer/{customerId}/devices/mobile/{resourceId}/action',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'resourceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'customer/{customerId}/devices/mobile/{resourceId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'resourceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customerId}/devices/mobile/{resourceId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'resourceId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customerId}/devices/mobile',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orderBy' => array(
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
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sortOrder' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->notifications = new Google_Service_Directory_Notifications_Resource(
        $this,
        $this->serviceName,
        'notifications',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customer}/notifications/{notificationId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'notificationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customer}/notifications/{notificationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'notificationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customer}/notifications',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
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
                'language' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'customer/{customer}/notifications/{notificationId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'notificationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'customer/{customer}/notifications/{notificationId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'notificationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->orgunits = new Google_Service_Directory_Orgunits_Resource(
        $this,
        $this->serviceName,
        'orgunits',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customerId}/orgunits{/orgUnitPath*}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orgUnitPath' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customerId}/orgunits{/orgUnitPath*}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orgUnitPath' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customerId}/orgunits',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customerId}/orgunits',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'type' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'orgUnitPath' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'patch' => array(
              'path' => 'customer/{customerId}/orgunits{/orgUnitPath*}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orgUnitPath' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'customer/{customerId}/orgunits{/orgUnitPath*}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'orgUnitPath' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->privileges = new Google_Service_Directory_Privileges_Resource(
        $this,
        $this->serviceName,
        'privileges',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'customer/{customer}/roles/ALL/privileges',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->roleAssignments = new Google_Service_Directory_RoleAssignments_Resource(
        $this,
        $this->serviceName,
        'roleAssignments',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customer}/roleassignments/{roleAssignmentId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleAssignmentId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customer}/roleassignments/{roleAssignmentId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleAssignmentId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customer}/roleassignments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customer}/roleassignments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userKey' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'roleId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->roles = new Google_Service_Directory_Roles_Resource(
        $this,
        $this->serviceName,
        'roles',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customer}/roles/{roleId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customer}/roles/{roleId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customer}/roles',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customer}/roles',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
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
            ),'patch' => array(
              'path' => 'customer/{customer}/roles/{roleId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'customer/{customer}/roles/{roleId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customer' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'roleId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->schemas = new Google_Service_Directory_Schemas_Resource(
        $this,
        $this->serviceName,
        'schemas',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'customer/{customerId}/schemas/{schemaKey}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'schemaKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'customer/{customerId}/schemas/{schemaKey}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'schemaKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'customer/{customerId}/schemas',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'customer/{customerId}/schemas',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'customer/{customerId}/schemas/{schemaKey}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'schemaKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'customer/{customerId}/schemas/{schemaKey}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'schemaKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->tokens = new Google_Service_Directory_Tokens_Resource(
        $this,
        $this->serviceName,
        'tokens',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/{userKey}/tokens/{clientId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'users/{userKey}/tokens/{clientId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'clientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'users/{userKey}/tokens',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->users = new Google_Service_Directory_Users_Resource(
        $this,
        $this->serviceName,
        'users',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/{userKey}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'users/{userKey}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'viewType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customFieldMask' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'users',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customer' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'orderBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'domain' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'showDeleted' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customFieldMask' => array(
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
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'viewType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'event' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'makeAdmin' => array(
              'path' => 'users/{userKey}/makeAdmin',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'users/{userKey}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'undelete' => array(
              'path' => 'users/{userKey}/undelete',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'users/{userKey}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'watch' => array(
              'path' => 'users/watch',
              'httpMethod' => 'POST',
              'parameters' => array(
                'customer' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'orderBy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'domain' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'projection' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'showDeleted' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'customFieldMask' => array(
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
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'viewType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'event' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->users_aliases = new Google_Service_Directory_UsersAliases_Resource(
        $this,
        $this->serviceName,
        'aliases',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/{userKey}/aliases/{alias}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'alias' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'users/{userKey}/aliases',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'users/{userKey}/aliases',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'event' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'watch' => array(
              'path' => 'users/{userKey}/aliases/watch',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'event' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->users_photos = new Google_Service_Directory_UsersPhotos_Resource(
        $this,
        $this->serviceName,
        'photos',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'users/{userKey}/photos/thumbnail',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'users/{userKey}/photos/thumbnail',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'users/{userKey}/photos/thumbnail',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'users/{userKey}/photos/thumbnail',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->verificationCodes = new Google_Service_Directory_VerificationCodes_Resource(
        $this,
        $this->serviceName,
        'verificationCodes',
        array(
          'methods' => array(
            'generate' => array(
              'path' => 'users/{userKey}/verificationCodes/generate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'invalidate' => array(
              'path' => 'users/{userKey}/verificationCodes/invalidate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'userKey' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'users/{userKey}/verificationCodes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'userKey' => array(
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
 * The "asps" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $asps = $adminService->asps;
 *  </code>
 */
class Google_Service_Directory_Asps_Resource extends Google_Service_Resource
{

  /**
   * Delete an ASP issued by a user. (asps.delete)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param int $codeId The unique ID of the ASP to be deleted.
   * @param array $optParams Optional parameters.
   */
  public function delete($userKey, $codeId, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'codeId' => $codeId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Get information about an ASP issued by a user. (asps.get)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param int $codeId The unique ID of the ASP.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Asp
   */
  public function get($userKey, $codeId, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'codeId' => $codeId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Asp");
  }

  /**
   * List the ASPs issued by a user. (asps.listAsps)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Asps
   */
  public function listAsps($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Asps");
  }
}

/**
 * The "channels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $channels = $adminService->channels;
 *  </code>
 */
class Google_Service_Directory_Channels_Resource extends Google_Service_Resource
{

  /**
   * Stop watching resources through this channel (channels.stop)
   *
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   */
  public function stop(Google_Service_Directory_Channel $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('stop', array($params));
  }
}

/**
 * The "chromeosdevices" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $chromeosdevices = $adminService->chromeosdevices;
 *  </code>
 */
class Google_Service_Directory_Chromeosdevices_Resource extends Google_Service_Resource
{

  /**
   * Retrieve Chrome OS Device (chromeosdevices.get)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $deviceId Immutable id of Chrome OS Device
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @return Google_Service_Directory_ChromeOsDevice
   */
  public function get($customerId, $deviceId, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'deviceId' => $deviceId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_ChromeOsDevice");
  }

  /**
   * Retrieve all Chrome OS Devices of a customer (paginated)
   * (chromeosdevices.listChromeosdevices)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderBy Column to use for sorting results
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @opt_param int maxResults Maximum number of results to return. Default is 100
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string sortOrder Whether to return results in ascending or
   * descending order. Only of use when orderBy is also used
   * @opt_param string query Search string in the format given at
   * http://support.google.com/chromeos/a/bin/answer.py?hl=en=1698333
   * @return Google_Service_Directory_ChromeOsDevices
   */
  public function listChromeosdevices($customerId, $optParams = array())
  {
    $params = array('customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_ChromeOsDevices");
  }

  /**
   * Update Chrome OS Device. This method supports patch semantics.
   * (chromeosdevices.patch)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $deviceId Immutable id of Chrome OS Device
   * @param Google_ChromeOsDevice $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @return Google_Service_Directory_ChromeOsDevice
   */
  public function patch($customerId, $deviceId, Google_Service_Directory_ChromeOsDevice $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'deviceId' => $deviceId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_ChromeOsDevice");
  }

  /**
   * Update Chrome OS Device (chromeosdevices.update)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $deviceId Immutable id of Chrome OS Device
   * @param Google_ChromeOsDevice $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @return Google_Service_Directory_ChromeOsDevice
   */
  public function update($customerId, $deviceId, Google_Service_Directory_ChromeOsDevice $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'deviceId' => $deviceId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_ChromeOsDevice");
  }
}

/**
 * The "customers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $customers = $adminService->customers;
 *  </code>
 */
class Google_Service_Directory_Customers_Resource extends Google_Service_Resource
{

  /**
   * Retrives a customer. (customers.get)
   *
   * @param string $customerKey Id of the customer to be retrieved
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Customer
   */
  public function get($customerKey, $optParams = array())
  {
    $params = array('customerKey' => $customerKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Customer");
  }

  /**
   * Updates a customer. This method supports patch semantics. (customers.patch)
   *
   * @param string $customerKey Id of the customer to be updated
   * @param Google_Customer $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Customer
   */
  public function patch($customerKey, Google_Service_Directory_Customer $postBody, $optParams = array())
  {
    $params = array('customerKey' => $customerKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Customer");
  }

  /**
   * Updates a customer. (customers.update)
   *
   * @param string $customerKey Id of the customer to be updated
   * @param Google_Customer $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Customer
   */
  public function update($customerKey, Google_Service_Directory_Customer $postBody, $optParams = array())
  {
    $params = array('customerKey' => $customerKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Customer");
  }
}

/**
 * The "domainAliases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $domainAliases = $adminService->domainAliases;
 *  </code>
 */
class Google_Service_Directory_DomainAliases_Resource extends Google_Service_Resource
{

  /**
   * Deletes a Domain Alias of the customer. (domainAliases.delete)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param string $domainAliasName Name of domain alias to be retrieved.
   * @param array $optParams Optional parameters.
   */
  public function delete($customer, $domainAliasName, $optParams = array())
  {
    $params = array('customer' => $customer, 'domainAliasName' => $domainAliasName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves a domain alias of the customer. (domainAliases.get)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param string $domainAliasName Name of domain alias to be retrieved.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_DomainAlias
   */
  public function get($customer, $domainAliasName, $optParams = array())
  {
    $params = array('customer' => $customer, 'domainAliasName' => $domainAliasName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_DomainAlias");
  }

  /**
   * Inserts a Domain alias of the customer. (domainAliases.insert)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param Google_DomainAlias $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_DomainAlias
   */
  public function insert($customer, Google_Service_Directory_DomainAlias $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_DomainAlias");
  }

  /**
   * Lists the domain aliases of the customer. (domainAliases.listDomainAliases)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string parentDomainName Name of the parent domain for which domain
   * aliases are to be fetched.
   * @return Google_Service_Directory_DomainAliases
   */
  public function listDomainAliases($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_DomainAliases");
  }
}

/**
 * The "domains" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $domains = $adminService->domains;
 *  </code>
 */
class Google_Service_Directory_Domains_Resource extends Google_Service_Resource
{

  /**
   * Deletes a domain of the customer. (domains.delete)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param string $domainName Name of domain to be deleted
   * @param array $optParams Optional parameters.
   */
  public function delete($customer, $domainName, $optParams = array())
  {
    $params = array('customer' => $customer, 'domainName' => $domainName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrives a domain of the customer. (domains.get)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param string $domainName Name of domain to be retrieved
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Domains
   */
  public function get($customer, $domainName, $optParams = array())
  {
    $params = array('customer' => $customer, 'domainName' => $domainName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Domains");
  }

  /**
   * Inserts a domain of the customer. (domains.insert)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param Google_Domains $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Domains
   */
  public function insert($customer, Google_Service_Directory_Domains $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Domains");
  }

  /**
   * Lists the domains of the customer. (domains.listDomains)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Domains2
   */
  public function listDomains($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Domains2");
  }
}

/**
 * The "groups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $groups = $adminService->groups;
 *  </code>
 */
class Google_Service_Directory_Groups_Resource extends Google_Service_Resource
{

  /**
   * Delete Group (groups.delete)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param array $optParams Optional parameters.
   */
  public function delete($groupKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve Group (groups.get)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Group
   */
  public function get($groupKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Group");
  }

  /**
   * Create Group (groups.insert)
   *
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Group
   */
  public function insert(Google_Service_Directory_Group $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Group");
  }

  /**
   * Retrieve all groups in a domain (paginated) (groups.listGroups)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customer Immutable id of the Google Apps account. In case
   * of multi-domain, to fetch all groups for a customer, fill this field instead
   * of domain.
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string domain Name of the domain. Fill this field to get groups
   * from only this domain. To return all groups in a multi-domain fill customer
   * field instead.
   * @opt_param int maxResults Maximum number of results to return. Default is 200
   * @opt_param string userKey Email or immutable Id of the user if only those
   * groups are to be listed, the given user is a member of. If Id, it should
   * match with id of user object
   * @return Google_Service_Directory_Groups
   */
  public function listGroups($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Groups");
  }

  /**
   * Update Group. This method supports patch semantics. (groups.patch)
   *
   * @param string $groupKey Email or immutable Id of the group. If Id, it should
   * match with id of group object
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Group
   */
  public function patch($groupKey, Google_Service_Directory_Group $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Group");
  }

  /**
   * Update Group (groups.update)
   *
   * @param string $groupKey Email or immutable Id of the group. If Id, it should
   * match with id of group object
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Group
   */
  public function update($groupKey, Google_Service_Directory_Group $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Group");
  }
}

/**
 * The "aliases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $aliases = $adminService->aliases;
 *  </code>
 */
class Google_Service_Directory_GroupsAliases_Resource extends Google_Service_Resource
{

  /**
   * Remove a alias for the group (aliases.delete)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param string $alias The alias to be removed
   * @param array $optParams Optional parameters.
   */
  public function delete($groupKey, $alias, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'alias' => $alias);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Add a alias for the group (aliases.insert)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param Google_Alias $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Alias
   */
  public function insert($groupKey, Google_Service_Directory_Alias $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Alias");
  }

  /**
   * List all aliases for a group (aliases.listGroupsAliases)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Aliases
   */
  public function listGroupsAliases($groupKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Aliases");
  }
}

/**
 * The "members" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $members = $adminService->members;
 *  </code>
 */
class Google_Service_Directory_Members_Resource extends Google_Service_Resource
{

  /**
   * Remove membership. (members.delete)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param string $memberKey Email or immutable Id of the member
   * @param array $optParams Optional parameters.
   */
  public function delete($groupKey, $memberKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'memberKey' => $memberKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve Group Member (members.get)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param string $memberKey Email or immutable Id of the member
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Member
   */
  public function get($groupKey, $memberKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'memberKey' => $memberKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Member");
  }

  /**
   * Add user to the specified group. (members.insert)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param Google_Member $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Member
   */
  public function insert($groupKey, Google_Service_Directory_Member $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Member");
  }

  /**
   * Retrieve all members in a group (paginated) (members.listMembers)
   *
   * @param string $groupKey Email or immutable Id of the group
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string roles Comma separated role values to filter list results
   * on.
   * @opt_param int maxResults Maximum number of results to return. Default is 200
   * @return Google_Service_Directory_Members
   */
  public function listMembers($groupKey, $optParams = array())
  {
    $params = array('groupKey' => $groupKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Members");
  }

  /**
   * Update membership of a user in the specified group. This method supports
   * patch semantics. (members.patch)
   *
   * @param string $groupKey Email or immutable Id of the group. If Id, it should
   * match with id of group object
   * @param string $memberKey Email or immutable Id of the user. If Id, it should
   * match with id of member object
   * @param Google_Member $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Member
   */
  public function patch($groupKey, $memberKey, Google_Service_Directory_Member $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'memberKey' => $memberKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Member");
  }

  /**
   * Update membership of a user in the specified group. (members.update)
   *
   * @param string $groupKey Email or immutable Id of the group. If Id, it should
   * match with id of group object
   * @param string $memberKey Email or immutable Id of the user. If Id, it should
   * match with id of member object
   * @param Google_Member $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Member
   */
  public function update($groupKey, $memberKey, Google_Service_Directory_Member $postBody, $optParams = array())
  {
    $params = array('groupKey' => $groupKey, 'memberKey' => $memberKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Member");
  }
}

/**
 * The "mobiledevices" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $mobiledevices = $adminService->mobiledevices;
 *  </code>
 */
class Google_Service_Directory_Mobiledevices_Resource extends Google_Service_Resource
{

  /**
   * Take action on Mobile Device (mobiledevices.action)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $resourceId Immutable id of Mobile Device
   * @param Google_MobileDeviceAction $postBody
   * @param array $optParams Optional parameters.
   */
  public function action($customerId, $resourceId, Google_Service_Directory_MobileDeviceAction $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'resourceId' => $resourceId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('action', array($params));
  }

  /**
   * Delete Mobile Device (mobiledevices.delete)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $resourceId Immutable id of Mobile Device
   * @param array $optParams Optional parameters.
   */
  public function delete($customerId, $resourceId, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'resourceId' => $resourceId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve Mobile Device (mobiledevices.get)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $resourceId Immutable id of Mobile Device
   * @param array $optParams Optional parameters.
   *
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @return Google_Service_Directory_MobileDevice
   */
  public function get($customerId, $resourceId, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'resourceId' => $resourceId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_MobileDevice");
  }

  /**
   * Retrieve all Mobile Devices of a customer (paginated)
   * (mobiledevices.listMobiledevices)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param array $optParams Optional parameters.
   *
   * @opt_param string orderBy Column to use for sorting results
   * @opt_param string projection Restrict information returned to a set of
   * selected fields.
   * @opt_param int maxResults Maximum number of results to return. Default is 100
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string sortOrder Whether to return results in ascending or
   * descending order. Only of use when orderBy is also used
   * @opt_param string query Search string in the format given at
   * http://support.google.com/a/bin/answer.py?hl=en=1408863#search
   * @return Google_Service_Directory_MobileDevices
   */
  public function listMobiledevices($customerId, $optParams = array())
  {
    $params = array('customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_MobileDevices");
  }
}

/**
 * The "notifications" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $notifications = $adminService->notifications;
 *  </code>
 */
class Google_Service_Directory_Notifications_Resource extends Google_Service_Resource
{

  /**
   * Deletes a notification (notifications.delete)
   *
   * @param string $customer The unique ID for the customer's Google account. The
   * customerId is also returned as part of the Users resource.
   * @param string $notificationId The unique ID of the notification.
   * @param array $optParams Optional parameters.
   */
  public function delete($customer, $notificationId, $optParams = array())
  {
    $params = array('customer' => $customer, 'notificationId' => $notificationId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves a notification. (notifications.get)
   *
   * @param string $customer The unique ID for the customer's Google account. The
   * customerId is also returned as part of the Users resource.
   * @param string $notificationId The unique ID of the notification.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Notification
   */
  public function get($customer, $notificationId, $optParams = array())
  {
    $params = array('customer' => $customer, 'notificationId' => $notificationId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Notification");
  }

  /**
   * Retrieves a list of notifications. (notifications.listNotifications)
   *
   * @param string $customer The unique ID for the customer's Google account.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken The token to specify the page of results to
   * retrieve.
   * @opt_param string maxResults Maximum number of notifications to return per
   * page. The default is 100.
   * @opt_param string language The ISO 639-1 code of the language notifications
   * are returned in. The default is English (en).
   * @return Google_Service_Directory_Notifications
   */
  public function listNotifications($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Notifications");
  }

  /**
   * Updates a notification. This method supports patch semantics.
   * (notifications.patch)
   *
   * @param string $customer The unique ID for the customer's Google account.
   * @param string $notificationId The unique ID of the notification.
   * @param Google_Notification $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Notification
   */
  public function patch($customer, $notificationId, Google_Service_Directory_Notification $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'notificationId' => $notificationId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Notification");
  }

  /**
   * Updates a notification. (notifications.update)
   *
   * @param string $customer The unique ID for the customer's Google account.
   * @param string $notificationId The unique ID of the notification.
   * @param Google_Notification $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Notification
   */
  public function update($customer, $notificationId, Google_Service_Directory_Notification $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'notificationId' => $notificationId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Notification");
  }
}

/**
 * The "orgunits" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $orgunits = $adminService->orgunits;
 *  </code>
 */
class Google_Service_Directory_Orgunits_Resource extends Google_Service_Resource
{

  /**
   * Remove Organization Unit (orgunits.delete)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $orgUnitPath Full path of the organization unit or its Id
   * @param array $optParams Optional parameters.
   */
  public function delete($customerId, $orgUnitPath, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'orgUnitPath' => $orgUnitPath);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve Organization Unit (orgunits.get)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $orgUnitPath Full path of the organization unit or its Id
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_OrgUnit
   */
  public function get($customerId, $orgUnitPath, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'orgUnitPath' => $orgUnitPath);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_OrgUnit");
  }

  /**
   * Add Organization Unit (orgunits.insert)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param Google_OrgUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_OrgUnit
   */
  public function insert($customerId, Google_Service_Directory_OrgUnit $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_OrgUnit");
  }

  /**
   * Retrieve all Organization Units (orgunits.listOrgunits)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param array $optParams Optional parameters.
   *
   * @opt_param string type Whether to return all sub-organizations or just
   * immediate children
   * @opt_param string orgUnitPath the URL-encoded organization unit's path or its
   * Id
   * @return Google_Service_Directory_OrgUnits
   */
  public function listOrgunits($customerId, $optParams = array())
  {
    $params = array('customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_OrgUnits");
  }

  /**
   * Update Organization Unit. This method supports patch semantics.
   * (orgunits.patch)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $orgUnitPath Full path of the organization unit or its Id
   * @param Google_OrgUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_OrgUnit
   */
  public function patch($customerId, $orgUnitPath, Google_Service_Directory_OrgUnit $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'orgUnitPath' => $orgUnitPath, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_OrgUnit");
  }

  /**
   * Update Organization Unit (orgunits.update)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $orgUnitPath Full path of the organization unit or its Id
   * @param Google_OrgUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_OrgUnit
   */
  public function update($customerId, $orgUnitPath, Google_Service_Directory_OrgUnit $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'orgUnitPath' => $orgUnitPath, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_OrgUnit");
  }
}

/**
 * The "privileges" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $privileges = $adminService->privileges;
 *  </code>
 */
class Google_Service_Directory_Privileges_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a paginated list of all privileges for a customer.
   * (privileges.listPrivileges)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Privileges
   */
  public function listPrivileges($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Privileges");
  }
}

/**
 * The "roleAssignments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $roleAssignments = $adminService->roleAssignments;
 *  </code>
 */
class Google_Service_Directory_RoleAssignments_Resource extends Google_Service_Resource
{

  /**
   * Deletes a role assignment. (roleAssignments.delete)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleAssignmentId Immutable ID of the role assignment.
   * @param array $optParams Optional parameters.
   */
  public function delete($customer, $roleAssignmentId, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleAssignmentId' => $roleAssignmentId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve a role assignment. (roleAssignments.get)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleAssignmentId Immutable ID of the role assignment.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_RoleAssignment
   */
  public function get($customer, $roleAssignmentId, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleAssignmentId' => $roleAssignmentId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_RoleAssignment");
  }

  /**
   * Creates a role assignment. (roleAssignments.insert)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param Google_RoleAssignment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_RoleAssignment
   */
  public function insert($customer, Google_Service_Directory_RoleAssignment $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_RoleAssignment");
  }

  /**
   * Retrieves a paginated list of all roleAssignments.
   * (roleAssignments.listRoleAssignments)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to specify the next page in the list.
   * @opt_param string userKey The user's primary email address, alias email
   * address, or unique user ID. If included in the request, returns role
   * assignments only for this user.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string roleId Immutable ID of a role. If included in the request,
   * returns only role assignments containing this role ID.
   * @return Google_Service_Directory_RoleAssignments
   */
  public function listRoleAssignments($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_RoleAssignments");
  }
}

/**
 * The "roles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $roles = $adminService->roles;
 *  </code>
 */
class Google_Service_Directory_Roles_Resource extends Google_Service_Resource
{

  /**
   * Deletes a role. (roles.delete)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleId Immutable ID of the role.
   * @param array $optParams Optional parameters.
   */
  public function delete($customer, $roleId, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleId' => $roleId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieves a role. (roles.get)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleId Immutable ID of the role.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Role
   */
  public function get($customer, $roleId, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleId' => $roleId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Role");
  }

  /**
   * Creates a role. (roles.insert)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param Google_Role $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Role
   */
  public function insert($customer, Google_Service_Directory_Role $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Role");
  }

  /**
   * Retrieves a paginated list of all the roles in a domain. (roles.listRoles)
   *
   * @param string $customer Immutable id of the Google Apps account.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to specify the next page in the list.
   * @opt_param int maxResults Maximum number of results to return.
   * @return Google_Service_Directory_Roles
   */
  public function listRoles($customer, $optParams = array())
  {
    $params = array('customer' => $customer);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Roles");
  }

  /**
   * Updates a role. This method supports patch semantics. (roles.patch)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleId Immutable ID of the role.
   * @param Google_Role $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Role
   */
  public function patch($customer, $roleId, Google_Service_Directory_Role $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleId' => $roleId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Role");
  }

  /**
   * Updates a role. (roles.update)
   *
   * @param string $customer Immutable ID of the Google Apps account.
   * @param string $roleId Immutable ID of the role.
   * @param Google_Role $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Role
   */
  public function update($customer, $roleId, Google_Service_Directory_Role $postBody, $optParams = array())
  {
    $params = array('customer' => $customer, 'roleId' => $roleId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Role");
  }
}

/**
 * The "schemas" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $schemas = $adminService->schemas;
 *  </code>
 */
class Google_Service_Directory_Schemas_Resource extends Google_Service_Resource
{

  /**
   * Delete schema (schemas.delete)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $schemaKey Name or immutable Id of the schema
   * @param array $optParams Optional parameters.
   */
  public function delete($customerId, $schemaKey, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'schemaKey' => $schemaKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve schema (schemas.get)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $schemaKey Name or immutable Id of the schema
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Schema
   */
  public function get($customerId, $schemaKey, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'schemaKey' => $schemaKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Schema");
  }

  /**
   * Create schema. (schemas.insert)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param Google_Schema $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Schema
   */
  public function insert($customerId, Google_Service_Directory_Schema $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Schema");
  }

  /**
   * Retrieve all schemas for a customer (schemas.listSchemas)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Schemas
   */
  public function listSchemas($customerId, $optParams = array())
  {
    $params = array('customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Schemas");
  }

  /**
   * Update schema. This method supports patch semantics. (schemas.patch)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $schemaKey Name or immutable Id of the schema.
   * @param Google_Schema $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Schema
   */
  public function patch($customerId, $schemaKey, Google_Service_Directory_Schema $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'schemaKey' => $schemaKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_Schema");
  }

  /**
   * Update schema (schemas.update)
   *
   * @param string $customerId Immutable id of the Google Apps account
   * @param string $schemaKey Name or immutable Id of the schema.
   * @param Google_Schema $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Schema
   */
  public function update($customerId, $schemaKey, Google_Service_Directory_Schema $postBody, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'schemaKey' => $schemaKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_Schema");
  }
}

/**
 * The "tokens" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $tokens = $adminService->tokens;
 *  </code>
 */
class Google_Service_Directory_Tokens_Resource extends Google_Service_Resource
{

  /**
   * Delete all access tokens issued by a user for an application. (tokens.delete)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param string $clientId The Client ID of the application the token is issued
   * to.
   * @param array $optParams Optional parameters.
   */
  public function delete($userKey, $clientId, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'clientId' => $clientId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Get information about an access token issued by a user. (tokens.get)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param string $clientId The Client ID of the application the token is issued
   * to.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Token
   */
  public function get($userKey, $clientId, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'clientId' => $clientId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_Token");
  }

  /**
   * Returns the set of tokens specified user has issued to 3rd party
   * applications. (tokens.listTokens)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Tokens
   */
  public function listTokens($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Tokens");
  }
}

/**
 * The "users" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $users = $adminService->users;
 *  </code>
 */
class Google_Service_Directory_Users_Resource extends Google_Service_Resource
{

  /**
   * Delete user (users.delete)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   */
  public function delete($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * retrieve user (users.get)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   *
   * @opt_param string viewType Whether to fetch the ADMIN_VIEW or DOMAIN_PUBLIC
   * view of the user.
   * @opt_param string customFieldMask Comma-separated list of schema names. All
   * fields from these schemas are fetched. This should only be set when
   * projection=custom.
   * @opt_param string projection What subset of fields to fetch for this user.
   * @return Google_Service_Directory_User
   */
  public function get($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_User");
  }

  /**
   * create user. (users.insert)
   *
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_User
   */
  public function insert(Google_Service_Directory_User $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_User");
  }

  /**
   * Retrieve either deleted users or all users in a domain (paginated)
   * (users.listUsers)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customer Immutable id of the Google Apps account. In case
   * of multi-domain, to fetch all users for a customer, fill this field instead
   * of domain.
   * @opt_param string orderBy Column to use for sorting results
   * @opt_param string domain Name of the domain. Fill this field to get users
   * from only this domain. To return all users in a multi-domain fill customer
   * field instead.
   * @opt_param string projection What subset of fields to fetch for this user.
   * @opt_param string showDeleted If set to true retrieves the list of deleted
   * users. Default is false
   * @opt_param string customFieldMask Comma-separated list of schema names. All
   * fields from these schemas are fetched. This should only be set when
   * projection=custom.
   * @opt_param int maxResults Maximum number of results to return. Default is
   * 100. Max allowed is 500
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string sortOrder Whether to return results in ascending or
   * descending order.
   * @opt_param string query Query string search. Should be of the form "".
   * Complete documentation is at https://developers.google.com/admin-
   * sdk/directory/v1/guides/search-users
   * @opt_param string viewType Whether to fetch the ADMIN_VIEW or DOMAIN_PUBLIC
   * view of the user.
   * @opt_param string event Event on which subscription is intended (if
   * subscribing)
   * @return Google_Service_Directory_Users
   */
  public function listUsers($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Users");
  }

  /**
   * change admin status of a user (users.makeAdmin)
   *
   * @param string $userKey Email or immutable Id of the user as admin
   * @param Google_UserMakeAdmin $postBody
   * @param array $optParams Optional parameters.
   */
  public function makeAdmin($userKey, Google_Service_Directory_UserMakeAdmin $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('makeAdmin', array($params));
  }

  /**
   * update user. This method supports patch semantics. (users.patch)
   *
   * @param string $userKey Email or immutable Id of the user. If Id, it should
   * match with id of user object
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_User
   */
  public function patch($userKey, Google_Service_Directory_User $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_User");
  }

  /**
   * Undelete a deleted user (users.undelete)
   *
   * @param string $userKey The immutable id of the user
   * @param Google_UserUndelete $postBody
   * @param array $optParams Optional parameters.
   */
  public function undelete($userKey, Google_Service_Directory_UserUndelete $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('undelete', array($params));
  }

  /**
   * update user (users.update)
   *
   * @param string $userKey Email or immutable Id of the user. If Id, it should
   * match with id of user object
   * @param Google_User $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_User
   */
  public function update($userKey, Google_Service_Directory_User $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_User");
  }

  /**
   * Watch for changes in users list (users.watch)
   *
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customer Immutable id of the Google Apps account. In case
   * of multi-domain, to fetch all users for a customer, fill this field instead
   * of domain.
   * @opt_param string orderBy Column to use for sorting results
   * @opt_param string domain Name of the domain. Fill this field to get users
   * from only this domain. To return all users in a multi-domain fill customer
   * field instead.
   * @opt_param string projection What subset of fields to fetch for this user.
   * @opt_param string showDeleted If set to true retrieves the list of deleted
   * users. Default is false
   * @opt_param string customFieldMask Comma-separated list of schema names. All
   * fields from these schemas are fetched. This should only be set when
   * projection=custom.
   * @opt_param int maxResults Maximum number of results to return. Default is
   * 100. Max allowed is 500
   * @opt_param string pageToken Token to specify next page in the list
   * @opt_param string sortOrder Whether to return results in ascending or
   * descending order.
   * @opt_param string query Query string search. Should be of the form "".
   * Complete documentation is at https://developers.google.com/admin-
   * sdk/directory/v1/guides/search-users
   * @opt_param string viewType Whether to fetch the ADMIN_VIEW or DOMAIN_PUBLIC
   * view of the user.
   * @opt_param string event Event on which subscription is intended (if
   * subscribing)
   * @return Google_Service_Directory_Channel
   */
  public function watch(Google_Service_Directory_Channel $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('watch', array($params), "Google_Service_Directory_Channel");
  }
}

/**
 * The "aliases" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $aliases = $adminService->aliases;
 *  </code>
 */
class Google_Service_Directory_UsersAliases_Resource extends Google_Service_Resource
{

  /**
   * Remove a alias for the user (aliases.delete)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param string $alias The alias to be removed
   * @param array $optParams Optional parameters.
   */
  public function delete($userKey, $alias, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'alias' => $alias);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Add a alias for the user (aliases.insert)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param Google_Alias $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_Alias
   */
  public function insert($userKey, Google_Service_Directory_Alias $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Directory_Alias");
  }

  /**
   * List all aliases for a user (aliases.listUsersAliases)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   *
   * @opt_param string event Event on which subscription is intended (if
   * subscribing)
   * @return Google_Service_Directory_Aliases
   */
  public function listUsersAliases($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_Aliases");
  }

  /**
   * Watch for changes in user aliases list (aliases.watch)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param Google_Channel $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string event Event on which subscription is intended (if
   * subscribing)
   * @return Google_Service_Directory_Channel
   */
  public function watch($userKey, Google_Service_Directory_Channel $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('watch', array($params), "Google_Service_Directory_Channel");
  }
}
/**
 * The "photos" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $photos = $adminService->photos;
 *  </code>
 */
class Google_Service_Directory_UsersPhotos_Resource extends Google_Service_Resource
{

  /**
   * Remove photos for the user (photos.delete)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   */
  public function delete($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Retrieve photo of a user (photos.get)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_UserPhoto
   */
  public function get($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Directory_UserPhoto");
  }

  /**
   * Add a photo for the user. This method supports patch semantics.
   * (photos.patch)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param Google_UserPhoto $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_UserPhoto
   */
  public function patch($userKey, Google_Service_Directory_UserPhoto $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Directory_UserPhoto");
  }

  /**
   * Add a photo for the user (photos.update)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param Google_UserPhoto $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_UserPhoto
   */
  public function update($userKey, Google_Service_Directory_UserPhoto $postBody, $optParams = array())
  {
    $params = array('userKey' => $userKey, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Directory_UserPhoto");
  }
}

/**
 * The "verificationCodes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adminService = new Google_Service_Directory(...);
 *   $verificationCodes = $adminService->verificationCodes;
 *  </code>
 */
class Google_Service_Directory_VerificationCodes_Resource extends Google_Service_Resource
{

  /**
   * Generate new backup verification codes for the user.
   * (verificationCodes.generate)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   */
  public function generate($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('generate', array($params));
  }

  /**
   * Invalidate the current backup verification codes for the user.
   * (verificationCodes.invalidate)
   *
   * @param string $userKey Email or immutable Id of the user
   * @param array $optParams Optional parameters.
   */
  public function invalidate($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('invalidate', array($params));
  }

  /**
   * Returns the current set of valid backup verification codes for the specified
   * user. (verificationCodes.listVerificationCodes)
   *
   * @param string $userKey Identifies the user in the API request. The value can
   * be the user's primary email address, alias email address, or unique user ID.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Directory_VerificationCodes
   */
  public function listVerificationCodes($userKey, $optParams = array())
  {
    $params = array('userKey' => $userKey);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Directory_VerificationCodes");
  }
}




class Google_Service_Directory_Alias extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alias;
  public $etag;
  public $id;
  public $kind;
  public $primaryEmail;


  public function setAlias($alias)
  {
    $this->alias = $alias;
  }
  public function getAlias()
  {
    return $this->alias;
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
  public function setPrimaryEmail($primaryEmail)
  {
    $this->primaryEmail = $primaryEmail;
  }
  public function getPrimaryEmail()
  {
    return $this->primaryEmail;
  }
}

class Google_Service_Directory_Aliases extends Google_Collection
{
  protected $collection_key = 'aliases';
  protected $internal_gapi_mappings = array(
  );
  protected $aliasesType = 'Google_Service_Directory_Alias';
  protected $aliasesDataType = 'array';
  public $etag;
  public $kind;


  public function setAliases($aliases)
  {
    $this->aliases = $aliases;
  }
  public function getAliases()
  {
    return $this->aliases;
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
}

class Google_Service_Directory_Asp extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $codeId;
  public $creationTime;
  public $etag;
  public $kind;
  public $lastTimeUsed;
  public $name;
  public $userKey;


  public function setCodeId($codeId)
  {
    $this->codeId = $codeId;
  }
  public function getCodeId()
  {
    return $this->codeId;
  }
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
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
  public function setLastTimeUsed($lastTimeUsed)
  {
    $this->lastTimeUsed = $lastTimeUsed;
  }
  public function getLastTimeUsed()
  {
    return $this->lastTimeUsed;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setUserKey($userKey)
  {
    $this->userKey = $userKey;
  }
  public function getUserKey()
  {
    return $this->userKey;
  }
}

class Google_Service_Directory_Asps extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_Asp';
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

class Google_Service_Directory_Channel extends Google_Model
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

class Google_Service_Directory_ChannelParams extends Google_Model
{
}

class Google_Service_Directory_ChromeOsDevice extends Google_Collection
{
  protected $collection_key = 'recentUsers';
  protected $internal_gapi_mappings = array(
  );
  protected $activeTimeRangesType = 'Google_Service_Directory_ChromeOsDeviceActiveTimeRanges';
  protected $activeTimeRangesDataType = 'array';
  public $annotatedAssetId;
  public $annotatedLocation;
  public $annotatedUser;
  public $bootMode;
  public $deviceId;
  public $etag;
  public $ethernetMacAddress;
  public $firmwareVersion;
  public $kind;
  public $lastEnrollmentTime;
  public $lastSync;
  public $macAddress;
  public $meid;
  public $model;
  public $notes;
  public $orderNumber;
  public $orgUnitPath;
  public $osVersion;
  public $platformVersion;
  protected $recentUsersType = 'Google_Service_Directory_ChromeOsDeviceRecentUsers';
  protected $recentUsersDataType = 'array';
  public $serialNumber;
  public $status;
  public $supportEndDate;
  public $willAutoRenew;


  public function setActiveTimeRanges($activeTimeRanges)
  {
    $this->activeTimeRanges = $activeTimeRanges;
  }
  public function getActiveTimeRanges()
  {
    return $this->activeTimeRanges;
  }
  public function setAnnotatedAssetId($annotatedAssetId)
  {
    $this->annotatedAssetId = $annotatedAssetId;
  }
  public function getAnnotatedAssetId()
  {
    return $this->annotatedAssetId;
  }
  public function setAnnotatedLocation($annotatedLocation)
  {
    $this->annotatedLocation = $annotatedLocation;
  }
  public function getAnnotatedLocation()
  {
    return $this->annotatedLocation;
  }
  public function setAnnotatedUser($annotatedUser)
  {
    $this->annotatedUser = $annotatedUser;
  }
  public function getAnnotatedUser()
  {
    return $this->annotatedUser;
  }
  public function setBootMode($bootMode)
  {
    $this->bootMode = $bootMode;
  }
  public function getBootMode()
  {
    return $this->bootMode;
  }
  public function setDeviceId($deviceId)
  {
    $this->deviceId = $deviceId;
  }
  public function getDeviceId()
  {
    return $this->deviceId;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setEthernetMacAddress($ethernetMacAddress)
  {
    $this->ethernetMacAddress = $ethernetMacAddress;
  }
  public function getEthernetMacAddress()
  {
    return $this->ethernetMacAddress;
  }
  public function setFirmwareVersion($firmwareVersion)
  {
    $this->firmwareVersion = $firmwareVersion;
  }
  public function getFirmwareVersion()
  {
    return $this->firmwareVersion;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastEnrollmentTime($lastEnrollmentTime)
  {
    $this->lastEnrollmentTime = $lastEnrollmentTime;
  }
  public function getLastEnrollmentTime()
  {
    return $this->lastEnrollmentTime;
  }
  public function setLastSync($lastSync)
  {
    $this->lastSync = $lastSync;
  }
  public function getLastSync()
  {
    return $this->lastSync;
  }
  public function setMacAddress($macAddress)
  {
    $this->macAddress = $macAddress;
  }
  public function getMacAddress()
  {
    return $this->macAddress;
  }
  public function setMeid($meid)
  {
    $this->meid = $meid;
  }
  public function getMeid()
  {
    return $this->meid;
  }
  public function setModel($model)
  {
    $this->model = $model;
  }
  public function getModel()
  {
    return $this->model;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setOrderNumber($orderNumber)
  {
    $this->orderNumber = $orderNumber;
  }
  public function getOrderNumber()
  {
    return $this->orderNumber;
  }
  public function setOrgUnitPath($orgUnitPath)
  {
    $this->orgUnitPath = $orgUnitPath;
  }
  public function getOrgUnitPath()
  {
    return $this->orgUnitPath;
  }
  public function setOsVersion($osVersion)
  {
    $this->osVersion = $osVersion;
  }
  public function getOsVersion()
  {
    return $this->osVersion;
  }
  public function setPlatformVersion($platformVersion)
  {
    $this->platformVersion = $platformVersion;
  }
  public function getPlatformVersion()
  {
    return $this->platformVersion;
  }
  public function setRecentUsers($recentUsers)
  {
    $this->recentUsers = $recentUsers;
  }
  public function getRecentUsers()
  {
    return $this->recentUsers;
  }
  public function setSerialNumber($serialNumber)
  {
    $this->serialNumber = $serialNumber;
  }
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSupportEndDate($supportEndDate)
  {
    $this->supportEndDate = $supportEndDate;
  }
  public function getSupportEndDate()
  {
    return $this->supportEndDate;
  }
  public function setWillAutoRenew($willAutoRenew)
  {
    $this->willAutoRenew = $willAutoRenew;
  }
  public function getWillAutoRenew()
  {
    return $this->willAutoRenew;
  }
}

class Google_Service_Directory_ChromeOsDeviceActiveTimeRanges extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $activeTime;
  public $date;


  public function setActiveTime($activeTime)
  {
    $this->activeTime = $activeTime;
  }
  public function getActiveTime()
  {
    return $this->activeTime;
  }
  public function setDate($date)
  {
    $this->date = $date;
  }
  public function getDate()
  {
    return $this->date;
  }
}

class Google_Service_Directory_ChromeOsDeviceRecentUsers extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $email;
  public $type;


  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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

class Google_Service_Directory_ChromeOsDevices extends Google_Collection
{
  protected $collection_key = 'chromeosdevices';
  protected $internal_gapi_mappings = array(
  );
  protected $chromeosdevicesType = 'Google_Service_Directory_ChromeOsDevice';
  protected $chromeosdevicesDataType = 'array';
  public $etag;
  public $kind;
  public $nextPageToken;


  public function setChromeosdevices($chromeosdevices)
  {
    $this->chromeosdevices = $chromeosdevices;
  }
  public function getChromeosdevices()
  {
    return $this->chromeosdevices;
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
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

class Google_Service_Directory_Customer extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $alternateEmail;
  public $customerCreationTime;
  public $customerDomain;
  public $etag;
  public $id;
  public $kind;
  public $language;
  public $phoneNumber;
  protected $postalAddressType = 'Google_Service_Directory_CustomerPostalAddress';
  protected $postalAddressDataType = '';


  public function setAlternateEmail($alternateEmail)
  {
    $this->alternateEmail = $alternateEmail;
  }
  public function getAlternateEmail()
  {
    return $this->alternateEmail;
  }
  public function setCustomerCreationTime($customerCreationTime)
  {
    $this->customerCreationTime = $customerCreationTime;
  }
  public function getCustomerCreationTime()
  {
    return $this->customerCreationTime;
  }
  public function setCustomerDomain($customerDomain)
  {
    $this->customerDomain = $customerDomain;
  }
  public function getCustomerDomain()
  {
    return $this->customerDomain;
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
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  public function getLanguage()
  {
    return $this->language;
  }
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }
  public function setPostalAddress(Google_Service_Directory_CustomerPostalAddress $postalAddress)
  {
    $this->postalAddress = $postalAddress;
  }
  public function getPostalAddress()
  {
    return $this->postalAddress;
  }
}

class Google_Service_Directory_CustomerPostalAddress extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $addressLine1;
  public $addressLine2;
  public $addressLine3;
  public $contactName;
  public $countryCode;
  public $locality;
  public $organizationName;
  public $postalCode;
  public $region;


  public function setAddressLine1($addressLine1)
  {
    $this->addressLine1 = $addressLine1;
  }
  public function getAddressLine1()
  {
    return $this->addressLine1;
  }
  public function setAddressLine2($addressLine2)
  {
    $this->addressLine2 = $addressLine2;
  }
  public function getAddressLine2()
  {
    return $this->addressLine2;
  }
  public function setAddressLine3($addressLine3)
  {
    $this->addressLine3 = $addressLine3;
  }
  public function getAddressLine3()
  {
    return $this->addressLine3;
  }
  public function setContactName($contactName)
  {
    $this->contactName = $contactName;
  }
  public function getContactName()
  {
    return $this->contactName;
  }
  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setLocality($locality)
  {
    $this->locality = $locality;
  }
  public function getLocality()
  {
    return $this->locality;
  }
  public function setOrganizationName($organizationName)
  {
    $this->organizationName = $organizationName;
  }
  public function getOrganizationName()
  {
    return $this->organizationName;
  }
  public function setPostalCode($postalCode)
  {
    $this->postalCode = $postalCode;
  }
  public function getPostalCode()
  {
    return $this->postalCode;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
}

class Google_Service_Directory_DomainAlias extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creationTime;
  public $domainAliasName;
  public $etag;
  public $kind;
  public $parentDomainName;
  public $verified;


  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setDomainAliasName($domainAliasName)
  {
    $this->domainAliasName = $domainAliasName;
  }
  public function getDomainAliasName()
  {
    return $this->domainAliasName;
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
  public function setParentDomainName($parentDomainName)
  {
    $this->parentDomainName = $parentDomainName;
  }
  public function getParentDomainName()
  {
    return $this->parentDomainName;
  }
  public function setVerified($verified)
  {
    $this->verified = $verified;
  }
  public function getVerified()
  {
    return $this->verified;
  }
}

class Google_Service_Directory_DomainAliases extends Google_Collection
{
  protected $collection_key = 'domainAliases';
  protected $internal_gapi_mappings = array(
  );
  protected $domainAliasesType = 'Google_Service_Directory_DomainAlias';
  protected $domainAliasesDataType = 'array';
  public $etag;
  public $kind;


  public function setDomainAliases($domainAliases)
  {
    $this->domainAliases = $domainAliases;
  }
  public function getDomainAliases()
  {
    return $this->domainAliases;
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
}

class Google_Service_Directory_Domains extends Google_Collection
{
  protected $collection_key = 'domainAliases';
  protected $internal_gapi_mappings = array(
  );
  public $creationTime;
  protected $domainAliasesType = 'Google_Service_Directory_DomainAlias';
  protected $domainAliasesDataType = 'array';
  public $domainName;
  public $etag;
  public $isPrimary;
  public $kind;
  public $verified;


  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setDomainAliases($domainAliases)
  {
    $this->domainAliases = $domainAliases;
  }
  public function getDomainAliases()
  {
    return $this->domainAliases;
  }
  public function setDomainName($domainName)
  {
    $this->domainName = $domainName;
  }
  public function getDomainName()
  {
    return $this->domainName;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setIsPrimary($isPrimary)
  {
    $this->isPrimary = $isPrimary;
  }
  public function getIsPrimary()
  {
    return $this->isPrimary;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setVerified($verified)
  {
    $this->verified = $verified;
  }
  public function getVerified()
  {
    return $this->verified;
  }
}

class Google_Service_Directory_Domains2 extends Google_Collection
{
  protected $collection_key = 'domains';
  protected $internal_gapi_mappings = array(
  );
  protected $domainsType = 'Google_Service_Directory_Domains';
  protected $domainsDataType = 'array';
  public $etag;
  public $kind;


  public function setDomains($domains)
  {
    $this->domains = $domains;
  }
  public function getDomains()
  {
    return $this->domains;
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
}

class Google_Service_Directory_Group extends Google_Collection
{
  protected $collection_key = 'nonEditableAliases';
  protected $internal_gapi_mappings = array(
  );
  public $adminCreated;
  public $aliases;
  public $description;
  public $directMembersCount;
  public $email;
  public $etag;
  public $id;
  public $kind;
  public $name;
  public $nonEditableAliases;


  public function setAdminCreated($adminCreated)
  {
    $this->adminCreated = $adminCreated;
  }
  public function getAdminCreated()
  {
    return $this->adminCreated;
  }
  public function setAliases($aliases)
  {
    $this->aliases = $aliases;
  }
  public function getAliases()
  {
    return $this->aliases;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDirectMembersCount($directMembersCount)
  {
    $this->directMembersCount = $directMembersCount;
  }
  public function getDirectMembersCount()
  {
    return $this->directMembersCount;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNonEditableAliases($nonEditableAliases)
  {
    $this->nonEditableAliases = $nonEditableAliases;
  }
  public function getNonEditableAliases()
  {
    return $this->nonEditableAliases;
  }
}

class Google_Service_Directory_Groups extends Google_Collection
{
  protected $collection_key = 'groups';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $groupsType = 'Google_Service_Directory_Group';
  protected $groupsDataType = 'array';
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
  public function setGroups($groups)
  {
    $this->groups = $groups;
  }
  public function getGroups()
  {
    return $this->groups;
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

class Google_Service_Directory_Member extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $email;
  public $etag;
  public $id;
  public $kind;
  public $role;
  public $type;


  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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
  public function setRole($role)
  {
    $this->role = $role;
  }
  public function getRole()
  {
    return $this->role;
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

class Google_Service_Directory_Members extends Google_Collection
{
  protected $collection_key = 'members';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  protected $membersType = 'Google_Service_Directory_Member';
  protected $membersDataType = 'array';
  public $nextPageToken;


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
  public function setMembers($members)
  {
    $this->members = $members;
  }
  public function getMembers()
  {
    return $this->members;
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

class Google_Service_Directory_MobileDevice extends Google_Collection
{
  protected $collection_key = 'otherAccountsInfo';
  protected $internal_gapi_mappings = array(
  );
  public $adbStatus;
  protected $applicationsType = 'Google_Service_Directory_MobileDeviceApplications';
  protected $applicationsDataType = 'array';
  public $basebandVersion;
  public $buildNumber;
  public $defaultLanguage;
  public $developerOptionsStatus;
  public $deviceCompromisedStatus;
  public $deviceId;
  public $email;
  public $etag;
  public $firstSync;
  public $hardwareId;
  public $imei;
  public $kernelVersion;
  public $kind;
  public $lastSync;
  public $managedAccountIsOnOwnerProfile;
  public $meid;
  public $model;
  public $name;
  public $networkOperator;
  public $os;
  public $otherAccountsInfo;
  public $resourceId;
  public $serialNumber;
  public $status;
  public $supportsWorkProfile;
  public $type;
  public $unknownSourcesStatus;
  public $userAgent;
  public $wifiMacAddress;


  public function setAdbStatus($adbStatus)
  {
    $this->adbStatus = $adbStatus;
  }
  public function getAdbStatus()
  {
    return $this->adbStatus;
  }
  public function setApplications($applications)
  {
    $this->applications = $applications;
  }
  public function getApplications()
  {
    return $this->applications;
  }
  public function setBasebandVersion($basebandVersion)
  {
    $this->basebandVersion = $basebandVersion;
  }
  public function getBasebandVersion()
  {
    return $this->basebandVersion;
  }
  public function setBuildNumber($buildNumber)
  {
    $this->buildNumber = $buildNumber;
  }
  public function getBuildNumber()
  {
    return $this->buildNumber;
  }
  public function setDefaultLanguage($defaultLanguage)
  {
    $this->defaultLanguage = $defaultLanguage;
  }
  public function getDefaultLanguage()
  {
    return $this->defaultLanguage;
  }
  public function setDeveloperOptionsStatus($developerOptionsStatus)
  {
    $this->developerOptionsStatus = $developerOptionsStatus;
  }
  public function getDeveloperOptionsStatus()
  {
    return $this->developerOptionsStatus;
  }
  public function setDeviceCompromisedStatus($deviceCompromisedStatus)
  {
    $this->deviceCompromisedStatus = $deviceCompromisedStatus;
  }
  public function getDeviceCompromisedStatus()
  {
    return $this->deviceCompromisedStatus;
  }
  public function setDeviceId($deviceId)
  {
    $this->deviceId = $deviceId;
  }
  public function getDeviceId()
  {
    return $this->deviceId;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFirstSync($firstSync)
  {
    $this->firstSync = $firstSync;
  }
  public function getFirstSync()
  {
    return $this->firstSync;
  }
  public function setHardwareId($hardwareId)
  {
    $this->hardwareId = $hardwareId;
  }
  public function getHardwareId()
  {
    return $this->hardwareId;
  }
  public function setImei($imei)
  {
    $this->imei = $imei;
  }
  public function getImei()
  {
    return $this->imei;
  }
  public function setKernelVersion($kernelVersion)
  {
    $this->kernelVersion = $kernelVersion;
  }
  public function getKernelVersion()
  {
    return $this->kernelVersion;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastSync($lastSync)
  {
    $this->lastSync = $lastSync;
  }
  public function getLastSync()
  {
    return $this->lastSync;
  }
  public function setManagedAccountIsOnOwnerProfile($managedAccountIsOnOwnerProfile)
  {
    $this->managedAccountIsOnOwnerProfile = $managedAccountIsOnOwnerProfile;
  }
  public function getManagedAccountIsOnOwnerProfile()
  {
    return $this->managedAccountIsOnOwnerProfile;
  }
  public function setMeid($meid)
  {
    $this->meid = $meid;
  }
  public function getMeid()
  {
    return $this->meid;
  }
  public function setModel($model)
  {
    $this->model = $model;
  }
  public function getModel()
  {
    return $this->model;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNetworkOperator($networkOperator)
  {
    $this->networkOperator = $networkOperator;
  }
  public function getNetworkOperator()
  {
    return $this->networkOperator;
  }
  public function setOs($os)
  {
    $this->os = $os;
  }
  public function getOs()
  {
    return $this->os;
  }
  public function setOtherAccountsInfo($otherAccountsInfo)
  {
    $this->otherAccountsInfo = $otherAccountsInfo;
  }
  public function getOtherAccountsInfo()
  {
    return $this->otherAccountsInfo;
  }
  public function setResourceId($resourceId)
  {
    $this->resourceId = $resourceId;
  }
  public function getResourceId()
  {
    return $this->resourceId;
  }
  public function setSerialNumber($serialNumber)
  {
    $this->serialNumber = $serialNumber;
  }
  public function getSerialNumber()
  {
    return $this->serialNumber;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setSupportsWorkProfile($supportsWorkProfile)
  {
    $this->supportsWorkProfile = $supportsWorkProfile;
  }
  public function getSupportsWorkProfile()
  {
    return $this->supportsWorkProfile;
  }
  public function setType($type)
  {
    $this->type = $type;
  }
  public function getType()
  {
    return $this->type;
  }
  public function setUnknownSourcesStatus($unknownSourcesStatus)
  {
    $this->unknownSourcesStatus = $unknownSourcesStatus;
  }
  public function getUnknownSourcesStatus()
  {
    return $this->unknownSourcesStatus;
  }
  public function setUserAgent($userAgent)
  {
    $this->userAgent = $userAgent;
  }
  public function getUserAgent()
  {
    return $this->userAgent;
  }
  public function setWifiMacAddress($wifiMacAddress)
  {
    $this->wifiMacAddress = $wifiMacAddress;
  }
  public function getWifiMacAddress()
  {
    return $this->wifiMacAddress;
  }
}

class Google_Service_Directory_MobileDeviceAction extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $action;


  public function setAction($action)
  {
    $this->action = $action;
  }
  public function getAction()
  {
    return $this->action;
  }
}

class Google_Service_Directory_MobileDeviceApplications extends Google_Collection
{
  protected $collection_key = 'permission';
  protected $internal_gapi_mappings = array(
  );
  public $displayName;
  public $packageName;
  public $permission;
  public $versionCode;
  public $versionName;


  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setPackageName($packageName)
  {
    $this->packageName = $packageName;
  }
  public function getPackageName()
  {
    return $this->packageName;
  }
  public function setPermission($permission)
  {
    $this->permission = $permission;
  }
  public function getPermission()
  {
    return $this->permission;
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

class Google_Service_Directory_MobileDevices extends Google_Collection
{
  protected $collection_key = 'mobiledevices';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  protected $mobiledevicesType = 'Google_Service_Directory_MobileDevice';
  protected $mobiledevicesDataType = 'array';
  public $nextPageToken;


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
  public function setMobiledevices($mobiledevices)
  {
    $this->mobiledevices = $mobiledevices;
  }
  public function getMobiledevices()
  {
    return $this->mobiledevices;
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

class Google_Service_Directory_Notification extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $body;
  public $etag;
  public $fromAddress;
  public $isUnread;
  public $kind;
  public $notificationId;
  public $sendTime;
  public $subject;


  public function setBody($body)
  {
    $this->body = $body;
  }
  public function getBody()
  {
    return $this->body;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFromAddress($fromAddress)
  {
    $this->fromAddress = $fromAddress;
  }
  public function getFromAddress()
  {
    return $this->fromAddress;
  }
  public function setIsUnread($isUnread)
  {
    $this->isUnread = $isUnread;
  }
  public function getIsUnread()
  {
    return $this->isUnread;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNotificationId($notificationId)
  {
    $this->notificationId = $notificationId;
  }
  public function getNotificationId()
  {
    return $this->notificationId;
  }
  public function setSendTime($sendTime)
  {
    $this->sendTime = $sendTime;
  }
  public function getSendTime()
  {
    return $this->sendTime;
  }
  public function setSubject($subject)
  {
    $this->subject = $subject;
  }
  public function getSubject()
  {
    return $this->subject;
  }
}

class Google_Service_Directory_Notifications extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_Notification';
  protected $itemsDataType = 'array';
  public $kind;
  public $nextPageToken;
  public $unreadNotificationsCount;


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
  public function setUnreadNotificationsCount($unreadNotificationsCount)
  {
    $this->unreadNotificationsCount = $unreadNotificationsCount;
  }
  public function getUnreadNotificationsCount()
  {
    return $this->unreadNotificationsCount;
  }
}

class Google_Service_Directory_OrgUnit extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $blockInheritance;
  public $description;
  public $etag;
  public $kind;
  public $name;
  public $orgUnitId;
  public $orgUnitPath;
  public $parentOrgUnitId;
  public $parentOrgUnitPath;


  public function setBlockInheritance($blockInheritance)
  {
    $this->blockInheritance = $blockInheritance;
  }
  public function getBlockInheritance()
  {
    return $this->blockInheritance;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOrgUnitId($orgUnitId)
  {
    $this->orgUnitId = $orgUnitId;
  }
  public function getOrgUnitId()
  {
    return $this->orgUnitId;
  }
  public function setOrgUnitPath($orgUnitPath)
  {
    $this->orgUnitPath = $orgUnitPath;
  }
  public function getOrgUnitPath()
  {
    return $this->orgUnitPath;
  }
  public function setParentOrgUnitId($parentOrgUnitId)
  {
    $this->parentOrgUnitId = $parentOrgUnitId;
  }
  public function getParentOrgUnitId()
  {
    return $this->parentOrgUnitId;
  }
  public function setParentOrgUnitPath($parentOrgUnitPath)
  {
    $this->parentOrgUnitPath = $parentOrgUnitPath;
  }
  public function getParentOrgUnitPath()
  {
    return $this->parentOrgUnitPath;
  }
}

class Google_Service_Directory_OrgUnits extends Google_Collection
{
  protected $collection_key = 'organizationUnits';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  protected $organizationUnitsType = 'Google_Service_Directory_OrgUnit';
  protected $organizationUnitsDataType = 'array';


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
  public function setOrganizationUnits($organizationUnits)
  {
    $this->organizationUnits = $organizationUnits;
  }
  public function getOrganizationUnits()
  {
    return $this->organizationUnits;
  }
}

class Google_Service_Directory_Privilege extends Google_Collection
{
  protected $collection_key = 'childPrivileges';
  protected $internal_gapi_mappings = array(
  );
  protected $childPrivilegesType = 'Google_Service_Directory_Privilege';
  protected $childPrivilegesDataType = 'array';
  public $etag;
  public $isOuScopable;
  public $kind;
  public $privilegeName;
  public $serviceId;
  public $serviceName;


  public function setChildPrivileges($childPrivileges)
  {
    $this->childPrivileges = $childPrivileges;
  }
  public function getChildPrivileges()
  {
    return $this->childPrivileges;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setIsOuScopable($isOuScopable)
  {
    $this->isOuScopable = $isOuScopable;
  }
  public function getIsOuScopable()
  {
    return $this->isOuScopable;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPrivilegeName($privilegeName)
  {
    $this->privilegeName = $privilegeName;
  }
  public function getPrivilegeName()
  {
    return $this->privilegeName;
  }
  public function setServiceId($serviceId)
  {
    $this->serviceId = $serviceId;
  }
  public function getServiceId()
  {
    return $this->serviceId;
  }
  public function setServiceName($serviceName)
  {
    $this->serviceName = $serviceName;
  }
  public function getServiceName()
  {
    return $this->serviceName;
  }
}

class Google_Service_Directory_Privileges extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_Privilege';
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

class Google_Service_Directory_Role extends Google_Collection
{
  protected $collection_key = 'rolePrivileges';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $isSuperAdminRole;
  public $isSystemRole;
  public $kind;
  public $roleDescription;
  public $roleId;
  public $roleName;
  protected $rolePrivilegesType = 'Google_Service_Directory_RoleRolePrivileges';
  protected $rolePrivilegesDataType = 'array';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setIsSuperAdminRole($isSuperAdminRole)
  {
    $this->isSuperAdminRole = $isSuperAdminRole;
  }
  public function getIsSuperAdminRole()
  {
    return $this->isSuperAdminRole;
  }
  public function setIsSystemRole($isSystemRole)
  {
    $this->isSystemRole = $isSystemRole;
  }
  public function getIsSystemRole()
  {
    return $this->isSystemRole;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRoleDescription($roleDescription)
  {
    $this->roleDescription = $roleDescription;
  }
  public function getRoleDescription()
  {
    return $this->roleDescription;
  }
  public function setRoleId($roleId)
  {
    $this->roleId = $roleId;
  }
  public function getRoleId()
  {
    return $this->roleId;
  }
  public function setRoleName($roleName)
  {
    $this->roleName = $roleName;
  }
  public function getRoleName()
  {
    return $this->roleName;
  }
  public function setRolePrivileges($rolePrivileges)
  {
    $this->rolePrivileges = $rolePrivileges;
  }
  public function getRolePrivileges()
  {
    return $this->rolePrivileges;
  }
}

class Google_Service_Directory_RoleAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $assignedTo;
  public $etag;
  public $kind;
  public $orgUnitId;
  public $roleAssignmentId;
  public $roleId;
  public $scopeType;


  public function setAssignedTo($assignedTo)
  {
    $this->assignedTo = $assignedTo;
  }
  public function getAssignedTo()
  {
    return $this->assignedTo;
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
  public function setOrgUnitId($orgUnitId)
  {
    $this->orgUnitId = $orgUnitId;
  }
  public function getOrgUnitId()
  {
    return $this->orgUnitId;
  }
  public function setRoleAssignmentId($roleAssignmentId)
  {
    $this->roleAssignmentId = $roleAssignmentId;
  }
  public function getRoleAssignmentId()
  {
    return $this->roleAssignmentId;
  }
  public function setRoleId($roleId)
  {
    $this->roleId = $roleId;
  }
  public function getRoleId()
  {
    return $this->roleId;
  }
  public function setScopeType($scopeType)
  {
    $this->scopeType = $scopeType;
  }
  public function getScopeType()
  {
    return $this->scopeType;
  }
}

class Google_Service_Directory_RoleAssignments extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_RoleAssignment';
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

class Google_Service_Directory_RoleRolePrivileges extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $privilegeName;
  public $serviceId;


  public function setPrivilegeName($privilegeName)
  {
    $this->privilegeName = $privilegeName;
  }
  public function getPrivilegeName()
  {
    return $this->privilegeName;
  }
  public function setServiceId($serviceId)
  {
    $this->serviceId = $serviceId;
  }
  public function getServiceId()
  {
    return $this->serviceId;
  }
}

class Google_Service_Directory_Roles extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_Role';
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

class Google_Service_Directory_Schema extends Google_Collection
{
  protected $collection_key = 'fields';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $fieldsType = 'Google_Service_Directory_SchemaFieldSpec';
  protected $fieldsDataType = 'array';
  public $kind;
  public $schemaId;
  public $schemaName;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFields($fields)
  {
    $this->fields = $fields;
  }
  public function getFields()
  {
    return $this->fields;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setSchemaId($schemaId)
  {
    $this->schemaId = $schemaId;
  }
  public function getSchemaId()
  {
    return $this->schemaId;
  }
  public function setSchemaName($schemaName)
  {
    $this->schemaName = $schemaName;
  }
  public function getSchemaName()
  {
    return $this->schemaName;
  }
}

class Google_Service_Directory_SchemaFieldSpec extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $fieldId;
  public $fieldName;
  public $fieldType;
  public $indexed;
  public $kind;
  public $multiValued;
  protected $numericIndexingSpecType = 'Google_Service_Directory_SchemaFieldSpecNumericIndexingSpec';
  protected $numericIndexingSpecDataType = '';
  public $readAccessType;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setFieldId($fieldId)
  {
    $this->fieldId = $fieldId;
  }
  public function getFieldId()
  {
    return $this->fieldId;
  }
  public function setFieldName($fieldName)
  {
    $this->fieldName = $fieldName;
  }
  public function getFieldName()
  {
    return $this->fieldName;
  }
  public function setFieldType($fieldType)
  {
    $this->fieldType = $fieldType;
  }
  public function getFieldType()
  {
    return $this->fieldType;
  }
  public function setIndexed($indexed)
  {
    $this->indexed = $indexed;
  }
  public function getIndexed()
  {
    return $this->indexed;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setMultiValued($multiValued)
  {
    $this->multiValued = $multiValued;
  }
  public function getMultiValued()
  {
    return $this->multiValued;
  }
  public function setNumericIndexingSpec(Google_Service_Directory_SchemaFieldSpecNumericIndexingSpec $numericIndexingSpec)
  {
    $this->numericIndexingSpec = $numericIndexingSpec;
  }
  public function getNumericIndexingSpec()
  {
    return $this->numericIndexingSpec;
  }
  public function setReadAccessType($readAccessType)
  {
    $this->readAccessType = $readAccessType;
  }
  public function getReadAccessType()
  {
    return $this->readAccessType;
  }
}

class Google_Service_Directory_SchemaFieldSpecNumericIndexingSpec extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $maxValue;
  public $minValue;


  public function setMaxValue($maxValue)
  {
    $this->maxValue = $maxValue;
  }
  public function getMaxValue()
  {
    return $this->maxValue;
  }
  public function setMinValue($minValue)
  {
    $this->minValue = $minValue;
  }
  public function getMinValue()
  {
    return $this->minValue;
  }
}

class Google_Service_Directory_Schemas extends Google_Collection
{
  protected $collection_key = 'schemas';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  protected $schemasType = 'Google_Service_Directory_Schema';
  protected $schemasDataType = 'array';


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
  public function setSchemas($schemas)
  {
    $this->schemas = $schemas;
  }
  public function getSchemas()
  {
    return $this->schemas;
  }
}

class Google_Service_Directory_Token extends Google_Collection
{
  protected $collection_key = 'scopes';
  protected $internal_gapi_mappings = array(
  );
  public $anonymous;
  public $clientId;
  public $displayText;
  public $etag;
  public $kind;
  public $nativeApp;
  public $scopes;
  public $userKey;


  public function setAnonymous($anonymous)
  {
    $this->anonymous = $anonymous;
  }
  public function getAnonymous()
  {
    return $this->anonymous;
  }
  public function setClientId($clientId)
  {
    $this->clientId = $clientId;
  }
  public function getClientId()
  {
    return $this->clientId;
  }
  public function setDisplayText($displayText)
  {
    $this->displayText = $displayText;
  }
  public function getDisplayText()
  {
    return $this->displayText;
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
  public function setNativeApp($nativeApp)
  {
    $this->nativeApp = $nativeApp;
  }
  public function getNativeApp()
  {
    return $this->nativeApp;
  }
  public function setScopes($scopes)
  {
    $this->scopes = $scopes;
  }
  public function getScopes()
  {
    return $this->scopes;
  }
  public function setUserKey($userKey)
  {
    $this->userKey = $userKey;
  }
  public function getUserKey()
  {
    return $this->userKey;
  }
}

class Google_Service_Directory_Tokens extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_Token';
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

class Google_Service_Directory_User extends Google_Collection
{
  protected $collection_key = 'nonEditableAliases';
  protected $internal_gapi_mappings = array(
  );
  public $addresses;
  public $agreedToTerms;
  public $aliases;
  public $changePasswordAtNextLogin;
  public $creationTime;
  public $customSchemas;
  public $customerId;
  public $deletionTime;
  public $emails;
  public $etag;
  public $externalIds;
  public $hashFunction;
  public $id;
  public $ims;
  public $includeInGlobalAddressList;
  public $ipWhitelisted;
  public $isAdmin;
  public $isDelegatedAdmin;
  public $isMailboxSetup;
  public $kind;
  public $lastLoginTime;
  protected $nameType = 'Google_Service_Directory_UserName';
  protected $nameDataType = '';
  public $nonEditableAliases;
  public $notes;
  public $orgUnitPath;
  public $organizations;
  public $password;
  public $phones;
  public $primaryEmail;
  public $relations;
  public $suspended;
  public $suspensionReason;
  public $thumbnailPhotoEtag;
  public $thumbnailPhotoUrl;
  public $websites;


  public function setAddresses($addresses)
  {
    $this->addresses = $addresses;
  }
  public function getAddresses()
  {
    return $this->addresses;
  }
  public function setAgreedToTerms($agreedToTerms)
  {
    $this->agreedToTerms = $agreedToTerms;
  }
  public function getAgreedToTerms()
  {
    return $this->agreedToTerms;
  }
  public function setAliases($aliases)
  {
    $this->aliases = $aliases;
  }
  public function getAliases()
  {
    return $this->aliases;
  }
  public function setChangePasswordAtNextLogin($changePasswordAtNextLogin)
  {
    $this->changePasswordAtNextLogin = $changePasswordAtNextLogin;
  }
  public function getChangePasswordAtNextLogin()
  {
    return $this->changePasswordAtNextLogin;
  }
  public function setCreationTime($creationTime)
  {
    $this->creationTime = $creationTime;
  }
  public function getCreationTime()
  {
    return $this->creationTime;
  }
  public function setCustomSchemas($customSchemas)
  {
    $this->customSchemas = $customSchemas;
  }
  public function getCustomSchemas()
  {
    return $this->customSchemas;
  }
  public function setCustomerId($customerId)
  {
    $this->customerId = $customerId;
  }
  public function getCustomerId()
  {
    return $this->customerId;
  }
  public function setDeletionTime($deletionTime)
  {
    $this->deletionTime = $deletionTime;
  }
  public function getDeletionTime()
  {
    return $this->deletionTime;
  }
  public function setEmails($emails)
  {
    $this->emails = $emails;
  }
  public function getEmails()
  {
    return $this->emails;
  }
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setExternalIds($externalIds)
  {
    $this->externalIds = $externalIds;
  }
  public function getExternalIds()
  {
    return $this->externalIds;
  }
  public function setHashFunction($hashFunction)
  {
    $this->hashFunction = $hashFunction;
  }
  public function getHashFunction()
  {
    return $this->hashFunction;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIms($ims)
  {
    $this->ims = $ims;
  }
  public function getIms()
  {
    return $this->ims;
  }
  public function setIncludeInGlobalAddressList($includeInGlobalAddressList)
  {
    $this->includeInGlobalAddressList = $includeInGlobalAddressList;
  }
  public function getIncludeInGlobalAddressList()
  {
    return $this->includeInGlobalAddressList;
  }
  public function setIpWhitelisted($ipWhitelisted)
  {
    $this->ipWhitelisted = $ipWhitelisted;
  }
  public function getIpWhitelisted()
  {
    return $this->ipWhitelisted;
  }
  public function setIsAdmin($isAdmin)
  {
    $this->isAdmin = $isAdmin;
  }
  public function getIsAdmin()
  {
    return $this->isAdmin;
  }
  public function setIsDelegatedAdmin($isDelegatedAdmin)
  {
    $this->isDelegatedAdmin = $isDelegatedAdmin;
  }
  public function getIsDelegatedAdmin()
  {
    return $this->isDelegatedAdmin;
  }
  public function setIsMailboxSetup($isMailboxSetup)
  {
    $this->isMailboxSetup = $isMailboxSetup;
  }
  public function getIsMailboxSetup()
  {
    return $this->isMailboxSetup;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastLoginTime($lastLoginTime)
  {
    $this->lastLoginTime = $lastLoginTime;
  }
  public function getLastLoginTime()
  {
    return $this->lastLoginTime;
  }
  public function setName(Google_Service_Directory_UserName $name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNonEditableAliases($nonEditableAliases)
  {
    $this->nonEditableAliases = $nonEditableAliases;
  }
  public function getNonEditableAliases()
  {
    return $this->nonEditableAliases;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setOrgUnitPath($orgUnitPath)
  {
    $this->orgUnitPath = $orgUnitPath;
  }
  public function getOrgUnitPath()
  {
    return $this->orgUnitPath;
  }
  public function setOrganizations($organizations)
  {
    $this->organizations = $organizations;
  }
  public function getOrganizations()
  {
    return $this->organizations;
  }
  public function setPassword($password)
  {
    $this->password = $password;
  }
  public function getPassword()
  {
    return $this->password;
  }
  public function setPhones($phones)
  {
    $this->phones = $phones;
  }
  public function getPhones()
  {
    return $this->phones;
  }
  public function setPrimaryEmail($primaryEmail)
  {
    $this->primaryEmail = $primaryEmail;
  }
  public function getPrimaryEmail()
  {
    return $this->primaryEmail;
  }
  public function setRelations($relations)
  {
    $this->relations = $relations;
  }
  public function getRelations()
  {
    return $this->relations;
  }
  public function setSuspended($suspended)
  {
    $this->suspended = $suspended;
  }
  public function getSuspended()
  {
    return $this->suspended;
  }
  public function setSuspensionReason($suspensionReason)
  {
    $this->suspensionReason = $suspensionReason;
  }
  public function getSuspensionReason()
  {
    return $this->suspensionReason;
  }
  public function setThumbnailPhotoEtag($thumbnailPhotoEtag)
  {
    $this->thumbnailPhotoEtag = $thumbnailPhotoEtag;
  }
  public function getThumbnailPhotoEtag()
  {
    return $this->thumbnailPhotoEtag;
  }
  public function setThumbnailPhotoUrl($thumbnailPhotoUrl)
  {
    $this->thumbnailPhotoUrl = $thumbnailPhotoUrl;
  }
  public function getThumbnailPhotoUrl()
  {
    return $this->thumbnailPhotoUrl;
  }
  public function setWebsites($websites)
  {
    $this->websites = $websites;
  }
  public function getWebsites()
  {
    return $this->websites;
  }
}

class Google_Service_Directory_UserAbout extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $contentType;
  public $value;


  public function setContentType($contentType)
  {
    $this->contentType = $contentType;
  }
  public function getContentType()
  {
    return $this->contentType;
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

class Google_Service_Directory_UserAddress extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $country;
  public $countryCode;
  public $customType;
  public $extendedAddress;
  public $formatted;
  public $locality;
  public $poBox;
  public $postalCode;
  public $primary;
  public $region;
  public $sourceIsStructured;
  public $streetAddress;
  public $type;


  public function setCountry($country)
  {
    $this->country = $country;
  }
  public function getCountry()
  {
    return $this->country;
  }
  public function setCountryCode($countryCode)
  {
    $this->countryCode = $countryCode;
  }
  public function getCountryCode()
  {
    return $this->countryCode;
  }
  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setExtendedAddress($extendedAddress)
  {
    $this->extendedAddress = $extendedAddress;
  }
  public function getExtendedAddress()
  {
    return $this->extendedAddress;
  }
  public function setFormatted($formatted)
  {
    $this->formatted = $formatted;
  }
  public function getFormatted()
  {
    return $this->formatted;
  }
  public function setLocality($locality)
  {
    $this->locality = $locality;
  }
  public function getLocality()
  {
    return $this->locality;
  }
  public function setPoBox($poBox)
  {
    $this->poBox = $poBox;
  }
  public function getPoBox()
  {
    return $this->poBox;
  }
  public function setPostalCode($postalCode)
  {
    $this->postalCode = $postalCode;
  }
  public function getPostalCode()
  {
    return $this->postalCode;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setSourceIsStructured($sourceIsStructured)
  {
    $this->sourceIsStructured = $sourceIsStructured;
  }
  public function getSourceIsStructured()
  {
    return $this->sourceIsStructured;
  }
  public function setStreetAddress($streetAddress)
  {
    $this->streetAddress = $streetAddress;
  }
  public function getStreetAddress()
  {
    return $this->streetAddress;
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

class Google_Service_Directory_UserCustomProperties extends Google_Model
{
}

class Google_Service_Directory_UserCustomSchemas extends Google_Model
{
}

class Google_Service_Directory_UserEmail extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $address;
  public $customType;
  public $primary;
  public $type;


  public function setAddress($address)
  {
    $this->address = $address;
  }
  public function getAddress()
  {
    return $this->address;
  }
  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
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

class Google_Service_Directory_UserExternalId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customType;
  public $type;
  public $value;


  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
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
}

class Google_Service_Directory_UserIm extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customProtocol;
  public $customType;
  public $im;
  public $primary;
  public $protocol;
  public $type;


  public function setCustomProtocol($customProtocol)
  {
    $this->customProtocol = $customProtocol;
  }
  public function getCustomProtocol()
  {
    return $this->customProtocol;
  }
  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setIm($im)
  {
    $this->im = $im;
  }
  public function getIm()
  {
    return $this->im;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setProtocol($protocol)
  {
    $this->protocol = $protocol;
  }
  public function getProtocol()
  {
    return $this->protocol;
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

class Google_Service_Directory_UserMakeAdmin extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $status;


  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_Directory_UserName extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $familyName;
  public $fullName;
  public $givenName;


  public function setFamilyName($familyName)
  {
    $this->familyName = $familyName;
  }
  public function getFamilyName()
  {
    return $this->familyName;
  }
  public function setFullName($fullName)
  {
    $this->fullName = $fullName;
  }
  public function getFullName()
  {
    return $this->fullName;
  }
  public function setGivenName($givenName)
  {
    $this->givenName = $givenName;
  }
  public function getGivenName()
  {
    return $this->givenName;
  }
}

class Google_Service_Directory_UserOrganization extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $costCenter;
  public $customType;
  public $department;
  public $description;
  public $domain;
  public $location;
  public $name;
  public $primary;
  public $symbol;
  public $title;
  public $type;


  public function setCostCenter($costCenter)
  {
    $this->costCenter = $costCenter;
  }
  public function getCostCenter()
  {
    return $this->costCenter;
  }
  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setDepartment($department)
  {
    $this->department = $department;
  }
  public function getDepartment()
  {
    return $this->department;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDomain($domain)
  {
    $this->domain = $domain;
  }
  public function getDomain()
  {
    return $this->domain;
  }
  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
  }
  public function setSymbol($symbol)
  {
    $this->symbol = $symbol;
  }
  public function getSymbol()
  {
    return $this->symbol;
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

class Google_Service_Directory_UserPhone extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customType;
  public $primary;
  public $type;
  public $value;


  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
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
}

class Google_Service_Directory_UserPhoto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $height;
  public $id;
  public $kind;
  public $mimeType;
  public $photoData;
  public $primaryEmail;
  public $width;


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
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
  public function setMimeType($mimeType)
  {
    $this->mimeType = $mimeType;
  }
  public function getMimeType()
  {
    return $this->mimeType;
  }
  public function setPhotoData($photoData)
  {
    $this->photoData = $photoData;
  }
  public function getPhotoData()
  {
    return $this->photoData;
  }
  public function setPrimaryEmail($primaryEmail)
  {
    $this->primaryEmail = $primaryEmail;
  }
  public function getPrimaryEmail()
  {
    return $this->primaryEmail;
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

class Google_Service_Directory_UserRelation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customType;
  public $type;
  public $value;


  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
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
}

class Google_Service_Directory_UserUndelete extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $orgUnitPath;


  public function setOrgUnitPath($orgUnitPath)
  {
    $this->orgUnitPath = $orgUnitPath;
  }
  public function getOrgUnitPath()
  {
    return $this->orgUnitPath;
  }
}

class Google_Service_Directory_UserWebsite extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $customType;
  public $primary;
  public $type;
  public $value;


  public function setCustomType($customType)
  {
    $this->customType = $customType;
  }
  public function getCustomType()
  {
    return $this->customType;
  }
  public function setPrimary($primary)
  {
    $this->primary = $primary;
  }
  public function getPrimary()
  {
    return $this->primary;
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
}

class Google_Service_Directory_Users extends Google_Collection
{
  protected $collection_key = 'users';
  protected $internal_gapi_mappings = array(
        "triggerEvent" => "trigger_event",
  );
  public $etag;
  public $kind;
  public $nextPageToken;
  public $triggerEvent;
  protected $usersType = 'Google_Service_Directory_User';
  protected $usersDataType = 'array';


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
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTriggerEvent($triggerEvent)
  {
    $this->triggerEvent = $triggerEvent;
  }
  public function getTriggerEvent()
  {
    return $this->triggerEvent;
  }
  public function setUsers($users)
  {
    $this->users = $users;
  }
  public function getUsers()
  {
    return $this->users;
  }
}

class Google_Service_Directory_VerificationCode extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $kind;
  public $userId;
  public $verificationCode;


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
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
  public function setVerificationCode($verificationCode)
  {
    $this->verificationCode = $verificationCode;
  }
  public function getVerificationCode()
  {
    return $this->verificationCode;
  }
}

class Google_Service_Directory_VerificationCodes extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Directory_VerificationCode';
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
