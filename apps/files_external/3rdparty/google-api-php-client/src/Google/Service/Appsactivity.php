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
 * Service definition for Appsactivity (v1).
 *
 * <p>
 * Provides a historical view of activity.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/google-apps/activity/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Appsactivity extends Google_Service
{
  /** View the activity history of your Google Apps. */
  const ACTIVITY =
      "https://www.googleapis.com/auth/activity";
  /** View and manage the files in your Google Drive. */
  const DRIVE =
      "https://www.googleapis.com/auth/drive";
  /** View and manage metadata of files in your Google Drive. */
  const DRIVE_METADATA =
      "https://www.googleapis.com/auth/drive.metadata";
  /** View metadata for files in your Google Drive. */
  const DRIVE_METADATA_READONLY =
      "https://www.googleapis.com/auth/drive.metadata.readonly";
  /** View the files in your Google Drive. */
  const DRIVE_READONLY =
      "https://www.googleapis.com/auth/drive.readonly";

  public $activities;
  

  /**
   * Constructs the internal representation of the Appsactivity service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'appsactivity/v1/';
    $this->version = 'v1';
    $this->serviceName = 'appsactivity';

    $this->activities = new Google_Service_Appsactivity_Activities_Resource(
        $this,
        $this->serviceName,
        'activities',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'activities',
              'httpMethod' => 'GET',
              'parameters' => array(
                'drive.ancestorId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'groupingStrategy' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'drive.fileId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'source' => array(
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
 * The "activities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appsactivityService = new Google_Service_Appsactivity(...);
 *   $activities = $appsactivityService->activities;
 *  </code>
 */
class Google_Service_Appsactivity_Activities_Resource extends Google_Service_Resource
{

  /**
   * Returns a list of activities visible to the current logged in user. Visible
   * activities are determined by the visiblity settings of the object that was
   * acted on, e.g. Drive files a user can see. An activity is a record of past
   * events. Multiple events may be merged if they are similar. A request is
   * scoped to activities from a given Google service using the source parameter.
   * (activities.listActivities)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string drive.ancestorId Identifies the Drive folder containing the
   * items for which to return activities.
   * @opt_param int pageSize The maximum number of events to return on a page. The
   * response includes a continuation token if there are more events.
   * @opt_param string pageToken A token to retrieve a specific page of results.
   * @opt_param string userId Indicates the user to return activity for. Use the
   * special value me to indicate the currently authenticated user.
   * @opt_param string groupingStrategy Indicates the strategy to use when
   * grouping singleEvents items in the associated combinedEvent object.
   * @opt_param string drive.fileId Identifies the Drive item to return activities
   * for.
   * @opt_param string source The Google service from which to return activities.
   * Possible values of source are: - drive.google.com
   * @return Google_Service_Appsactivity_ListActivitiesResponse
   */
  public function listActivities($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Appsactivity_ListActivitiesResponse");
  }
}




class Google_Service_Appsactivity_Activity extends Google_Collection
{
  protected $collection_key = 'singleEvents';
  protected $internal_gapi_mappings = array(
  );
  protected $combinedEventType = 'Google_Service_Appsactivity_Event';
  protected $combinedEventDataType = '';
  protected $singleEventsType = 'Google_Service_Appsactivity_Event';
  protected $singleEventsDataType = 'array';


  public function setCombinedEvent(Google_Service_Appsactivity_Event $combinedEvent)
  {
    $this->combinedEvent = $combinedEvent;
  }
  public function getCombinedEvent()
  {
    return $this->combinedEvent;
  }
  public function setSingleEvents($singleEvents)
  {
    $this->singleEvents = $singleEvents;
  }
  public function getSingleEvents()
  {
    return $this->singleEvents;
  }
}

class Google_Service_Appsactivity_Event extends Google_Collection
{
  protected $collection_key = 'permissionChanges';
  protected $internal_gapi_mappings = array(
  );
  public $additionalEventTypes;
  public $eventTimeMillis;
  public $fromUserDeletion;
  protected $moveType = 'Google_Service_Appsactivity_Move';
  protected $moveDataType = '';
  protected $permissionChangesType = 'Google_Service_Appsactivity_PermissionChange';
  protected $permissionChangesDataType = 'array';
  public $primaryEventType;
  protected $renameType = 'Google_Service_Appsactivity_Rename';
  protected $renameDataType = '';
  protected $targetType = 'Google_Service_Appsactivity_Target';
  protected $targetDataType = '';
  protected $userType = 'Google_Service_Appsactivity_User';
  protected $userDataType = '';


  public function setAdditionalEventTypes($additionalEventTypes)
  {
    $this->additionalEventTypes = $additionalEventTypes;
  }
  public function getAdditionalEventTypes()
  {
    return $this->additionalEventTypes;
  }
  public function setEventTimeMillis($eventTimeMillis)
  {
    $this->eventTimeMillis = $eventTimeMillis;
  }
  public function getEventTimeMillis()
  {
    return $this->eventTimeMillis;
  }
  public function setFromUserDeletion($fromUserDeletion)
  {
    $this->fromUserDeletion = $fromUserDeletion;
  }
  public function getFromUserDeletion()
  {
    return $this->fromUserDeletion;
  }
  public function setMove(Google_Service_Appsactivity_Move $move)
  {
    $this->move = $move;
  }
  public function getMove()
  {
    return $this->move;
  }
  public function setPermissionChanges($permissionChanges)
  {
    $this->permissionChanges = $permissionChanges;
  }
  public function getPermissionChanges()
  {
    return $this->permissionChanges;
  }
  public function setPrimaryEventType($primaryEventType)
  {
    $this->primaryEventType = $primaryEventType;
  }
  public function getPrimaryEventType()
  {
    return $this->primaryEventType;
  }
  public function setRename(Google_Service_Appsactivity_Rename $rename)
  {
    $this->rename = $rename;
  }
  public function getRename()
  {
    return $this->rename;
  }
  public function setTarget(Google_Service_Appsactivity_Target $target)
  {
    $this->target = $target;
  }
  public function getTarget()
  {
    return $this->target;
  }
  public function setUser(Google_Service_Appsactivity_User $user)
  {
    $this->user = $user;
  }
  public function getUser()
  {
    return $this->user;
  }
}

class Google_Service_Appsactivity_ListActivitiesResponse extends Google_Collection
{
  protected $collection_key = 'activities';
  protected $internal_gapi_mappings = array(
  );
  protected $activitiesType = 'Google_Service_Appsactivity_Activity';
  protected $activitiesDataType = 'array';
  public $nextPageToken;


  public function setActivities($activities)
  {
    $this->activities = $activities;
  }
  public function getActivities()
  {
    return $this->activities;
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

class Google_Service_Appsactivity_Move extends Google_Collection
{
  protected $collection_key = 'removedParents';
  protected $internal_gapi_mappings = array(
  );
  protected $addedParentsType = 'Google_Service_Appsactivity_Parent';
  protected $addedParentsDataType = 'array';
  protected $removedParentsType = 'Google_Service_Appsactivity_Parent';
  protected $removedParentsDataType = 'array';


  public function setAddedParents($addedParents)
  {
    $this->addedParents = $addedParents;
  }
  public function getAddedParents()
  {
    return $this->addedParents;
  }
  public function setRemovedParents($removedParents)
  {
    $this->removedParents = $removedParents;
  }
  public function getRemovedParents()
  {
    return $this->removedParents;
  }
}

class Google_Service_Appsactivity_Parent extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $isRoot;
  public $title;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIsRoot($isRoot)
  {
    $this->isRoot = $isRoot;
  }
  public function getIsRoot()
  {
    return $this->isRoot;
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

class Google_Service_Appsactivity_Permission extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $permissionId;
  public $role;
  public $type;
  protected $userType = 'Google_Service_Appsactivity_User';
  protected $userDataType = '';
  public $withLink;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPermissionId($permissionId)
  {
    $this->permissionId = $permissionId;
  }
  public function getPermissionId()
  {
    return $this->permissionId;
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
  public function setUser(Google_Service_Appsactivity_User $user)
  {
    $this->user = $user;
  }
  public function getUser()
  {
    return $this->user;
  }
  public function setWithLink($withLink)
  {
    $this->withLink = $withLink;
  }
  public function getWithLink()
  {
    return $this->withLink;
  }
}

class Google_Service_Appsactivity_PermissionChange extends Google_Collection
{
  protected $collection_key = 'removedPermissions';
  protected $internal_gapi_mappings = array(
  );
  protected $addedPermissionsType = 'Google_Service_Appsactivity_Permission';
  protected $addedPermissionsDataType = 'array';
  protected $removedPermissionsType = 'Google_Service_Appsactivity_Permission';
  protected $removedPermissionsDataType = 'array';


  public function setAddedPermissions($addedPermissions)
  {
    $this->addedPermissions = $addedPermissions;
  }
  public function getAddedPermissions()
  {
    return $this->addedPermissions;
  }
  public function setRemovedPermissions($removedPermissions)
  {
    $this->removedPermissions = $removedPermissions;
  }
  public function getRemovedPermissions()
  {
    return $this->removedPermissions;
  }
}

class Google_Service_Appsactivity_Photo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $url;


  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_Appsactivity_Rename extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $newTitle;
  public $oldTitle;


  public function setNewTitle($newTitle)
  {
    $this->newTitle = $newTitle;
  }
  public function getNewTitle()
  {
    return $this->newTitle;
  }
  public function setOldTitle($oldTitle)
  {
    $this->oldTitle = $oldTitle;
  }
  public function getOldTitle()
  {
    return $this->oldTitle;
  }
}

class Google_Service_Appsactivity_Target extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $mimeType;
  public $name;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Appsactivity_User extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  protected $photoType = 'Google_Service_Appsactivity_Photo';
  protected $photoDataType = '';


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setPhoto(Google_Service_Appsactivity_Photo $photo)
  {
    $this->photo = $photo;
  }
  public function getPhoto()
  {
    return $this->photo;
  }
}
