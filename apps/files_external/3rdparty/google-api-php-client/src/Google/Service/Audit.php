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
 * Service definition for Audit (v1).
 *
 * <p>
 * Lets you access user activities in your enterprise made through various
 * applications.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/google-apps/admin-audit/get_started" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Audit extends Google_Service
{


  public $activities;
  

  /**
   * Constructs the internal representation of the Audit service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->servicePath = 'apps/reporting/audit/v1/';
    $this->version = 'v1';
    $this->serviceName = 'audit';

    $this->activities = new Google_Service_Audit_Activities_Resource(
        $this,
        $this->serviceName,
        'activities',
        array(
          'methods' => array(
            'list' => array(
              'path' => '{customerId}/{applicationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'customerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'applicationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'actorEmail' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'actorApplicationId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'actorIpAddress' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'caller' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'eventName' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'startTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'endTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'continuationToken' => array(
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
 *   $auditService = new Google_Service_Audit(...);
 *   $activities = $auditService->activities;
 *  </code>
 */
class Google_Service_Audit_Activities_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of activities for a specific customer and application.
   * (activities.listActivities)
   *
   * @param string $customerId Represents the customer who is the owner of target
   * object on which action was performed.
   * @param string $applicationId Application ID of the application on which the
   * event was performed.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string actorEmail Email address of the user who performed the
   * action.
   * @opt_param string actorApplicationId Application ID of the application which
   * interacted on behalf of the user while performing the event.
   * @opt_param string actorIpAddress IP Address of host where the event was
   * performed. Supports both IPv4 and IPv6 addresses.
   * @opt_param string caller Type of the caller.
   * @opt_param int maxResults Number of activity records to be shown in each
   * page.
   * @opt_param string eventName Name of the event being queried.
   * @opt_param string startTime Return events which occured at or after this
   * time.
   * @opt_param string endTime Return events which occured at or before this time.
   * @opt_param string continuationToken Next page URL.
   * @return Google_Service_Audit_Activities
   */
  public function listActivities($customerId, $applicationId, $optParams = array())
  {
    $params = array('customerId' => $customerId, 'applicationId' => $applicationId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Audit_Activities");
  }
}




class Google_Service_Audit_Activities extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_Audit_Activity';
  protected $itemsDataType = 'array';
  public $kind;
  public $next;


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
  public function setNext($next)
  {
    $this->next = $next;
  }
  public function getNext()
  {
    return $this->next;
  }
}

class Google_Service_Audit_Activity extends Google_Collection
{
  protected $collection_key = 'events';
  protected $internal_gapi_mappings = array(
  );
  protected $actorType = 'Google_Service_Audit_ActivityActor';
  protected $actorDataType = '';
  protected $eventsType = 'Google_Service_Audit_ActivityEvents';
  protected $eventsDataType = 'array';
  protected $idType = 'Google_Service_Audit_ActivityId';
  protected $idDataType = '';
  public $ipAddress;
  public $kind;
  public $ownerDomain;


  public function setActor(Google_Service_Audit_ActivityActor $actor)
  {
    $this->actor = $actor;
  }
  public function getActor()
  {
    return $this->actor;
  }
  public function setEvents($events)
  {
    $this->events = $events;
  }
  public function getEvents()
  {
    return $this->events;
  }
  public function setId(Google_Service_Audit_ActivityId $id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setIpAddress($ipAddress)
  {
    $this->ipAddress = $ipAddress;
  }
  public function getIpAddress()
  {
    return $this->ipAddress;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOwnerDomain($ownerDomain)
  {
    $this->ownerDomain = $ownerDomain;
  }
  public function getOwnerDomain()
  {
    return $this->ownerDomain;
  }
}

class Google_Service_Audit_ActivityActor extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $applicationId;
  public $callerType;
  public $email;
  public $key;


  public function setApplicationId($applicationId)
  {
    $this->applicationId = $applicationId;
  }
  public function getApplicationId()
  {
    return $this->applicationId;
  }
  public function setCallerType($callerType)
  {
    $this->callerType = $callerType;
  }
  public function getCallerType()
  {
    return $this->callerType;
  }
  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
  }
  public function setKey($key)
  {
    $this->key = $key;
  }
  public function getKey()
  {
    return $this->key;
  }
}

class Google_Service_Audit_ActivityEvents extends Google_Collection
{
  protected $collection_key = 'parameters';
  protected $internal_gapi_mappings = array(
  );
  public $eventType;
  public $name;
  protected $parametersType = 'Google_Service_Audit_ActivityEventsParameters';
  protected $parametersDataType = 'array';


  public function setEventType($eventType)
  {
    $this->eventType = $eventType;
  }
  public function getEventType()
  {
    return $this->eventType;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  public function getParameters()
  {
    return $this->parameters;
  }
}

class Google_Service_Audit_ActivityEventsParameters extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;
  public $value;


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

class Google_Service_Audit_ActivityId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $applicationId;
  public $customerId;
  public $time;
  public $uniqQualifier;


  public function setApplicationId($applicationId)
  {
    $this->applicationId = $applicationId;
  }
  public function getApplicationId()
  {
    return $this->applicationId;
  }
  public function setCustomerId($customerId)
  {
    $this->customerId = $customerId;
  }
  public function getCustomerId()
  {
    return $this->customerId;
  }
  public function setTime($time)
  {
    $this->time = $time;
  }
  public function getTime()
  {
    return $this->time;
  }
  public function setUniqQualifier($uniqQualifier)
  {
    $this->uniqQualifier = $uniqQualifier;
  }
  public function getUniqQualifier()
  {
    return $this->uniqQualifier;
  }
}
