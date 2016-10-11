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
 * Service definition for Proximitybeacon (v1beta1).
 *
 * <p>
 * This API provides services to register, manage, index, and search beacons.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/beacons/proximity/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Proximitybeacon extends Google_Service
{


  public $beaconinfo;
  public $beacons;
  public $beacons_attachments;
  public $beacons_diagnostics;
  public $namespaces;
  

  /**
   * Constructs the internal representation of the Proximitybeacon service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://proximitybeacon.googleapis.com/';
    $this->servicePath = '';
    $this->version = 'v1beta1';
    $this->serviceName = 'proximitybeacon';

    $this->beaconinfo = new Google_Service_Proximitybeacon_Beaconinfo_Resource(
        $this,
        $this->serviceName,
        'beaconinfo',
        array(
          'methods' => array(
            'getforobserved' => array(
              'path' => 'v1beta1/beaconinfo:getforobserved',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->beacons = new Google_Service_Proximitybeacon_Beacons_Resource(
        $this,
        $this->serviceName,
        'beacons',
        array(
          'methods' => array(
            'activate' => array(
              'path' => 'v1beta1/{+beaconName}:activate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'deactivate' => array(
              'path' => 'v1beta1/{+beaconName}:deactivate',
              'httpMethod' => 'POST',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'decommission' => array(
              'path' => 'v1beta1/{+beaconName}:decommission',
              'httpMethod' => 'POST',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'v1beta1/{+beaconName}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1beta1/beacons',
              'httpMethod' => 'GET',
              'parameters' => array(
                'q' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'register' => array(
              'path' => 'v1beta1/beacons:register',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'update' => array(
              'path' => 'v1beta1/{+beaconName}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->beacons_attachments = new Google_Service_Proximitybeacon_BeaconsAttachments_Resource(
        $this,
        $this->serviceName,
        'attachments',
        array(
          'methods' => array(
            'batchDelete' => array(
              'path' => 'v1beta1/{+beaconName}/attachments:batchDelete',
              'httpMethod' => 'POST',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'namespacedType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'create' => array(
              'path' => 'v1beta1/{+beaconName}/attachments',
              'httpMethod' => 'POST',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'delete' => array(
              'path' => 'v1beta1/{+attachmentName}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'attachmentName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'v1beta1/{+beaconName}/attachments',
              'httpMethod' => 'GET',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'namespacedType' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->beacons_diagnostics = new Google_Service_Proximitybeacon_BeaconsDiagnostics_Resource(
        $this,
        $this->serviceName,
        'diagnostics',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1beta1/{+beaconName}/diagnostics',
              'httpMethod' => 'GET',
              'parameters' => array(
                'beaconName' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'pageToken' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'alertFilter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'pageSize' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),
          )
        )
    );
    $this->namespaces = new Google_Service_Proximitybeacon_Namespaces_Resource(
        $this,
        $this->serviceName,
        'namespaces',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'v1beta1/namespaces',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
  }
}


/**
 * The "beaconinfo" collection of methods.
 * Typical usage is:
 *  <code>
 *   $proximitybeaconService = new Google_Service_Proximitybeacon(...);
 *   $beaconinfo = $proximitybeaconService->beaconinfo;
 *  </code>
 */
class Google_Service_Proximitybeacon_Beaconinfo_Resource extends Google_Service_Resource
{

  /**
   * Given one or more beacon observations, returns any beacon information and
   * attachments accessible to your application. (beaconinfo.getforobserved)
   *
   * @param Google_GetInfoForObservedBeaconsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_GetInfoForObservedBeaconsResponse
   */
  public function getforobserved(Google_Service_Proximitybeacon_GetInfoForObservedBeaconsRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('getforobserved', array($params), "Google_Service_Proximitybeacon_GetInfoForObservedBeaconsResponse");
  }
}

/**
 * The "beacons" collection of methods.
 * Typical usage is:
 *  <code>
 *   $proximitybeaconService = new Google_Service_Proximitybeacon(...);
 *   $beacons = $proximitybeaconService->beacons;
 *  </code>
 */
class Google_Service_Proximitybeacon_Beacons_Resource extends Google_Service_Resource
{

  /**
   * (Re)activates a beacon. A beacon that is active will return information and
   * attachment data when queried via `beaconinfo.getforobserved`. Calling this
   * method on an already active beacon will do nothing (but will return a
   * successful response code). (beacons.activate)
   *
   * @param string $beaconName The beacon to activate. Required.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Empty
   */
  public function activate($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('activate', array($params), "Google_Service_Proximitybeacon_Empty");
  }

  /**
   * Deactivates a beacon. Once deactivated, the API will not return information
   * nor attachment data for the beacon when queried via
   * `beaconinfo.getforobserved`. Calling this method on an already inactive
   * beacon will do nothing (but will return a successful response code).
   * (beacons.deactivate)
   *
   * @param string $beaconName The beacon name of this beacon.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Empty
   */
  public function deactivate($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('deactivate', array($params), "Google_Service_Proximitybeacon_Empty");
  }

  /**
   * Decommissions the specified beacon in the service. This beacon will no longer
   * be returned from `beaconinfo.getforobserved`. This operation is permanent --
   * you will not be able to re-register a beacon with this ID again.
   * (beacons.decommission)
   *
   * @param string $beaconName Beacon that should be decommissioned. Required.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Empty
   */
  public function decommission($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('decommission', array($params), "Google_Service_Proximitybeacon_Empty");
  }

  /**
   * Returns detailed information about the specified beacon. (beacons.get)
   *
   * @param string $beaconName Beacon that is requested.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Beacon
   */
  public function get($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Proximitybeacon_Beacon");
  }

  /**
   * Searches the beacon registry for beacons that match the given search
   * criteria. Only those beacons that the client has permission to list will be
   * returned. (beacons.listBeacons)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string q Filter query string that supports the following field
   * filters: * `description:""` For example: `description:"Room 3"` Returns
   * beacons whose description matches tokens in the string "Room 3" (not
   * necessarily that exact string). The string must be double-quoted. * `status:`
   * For example: `status:active` Returns beacons whose status matches the given
   * value. Values must be one of the Beacon.Status enum values (case
   * insensitive). Accepts multiple filters which will be combined with OR logic.
   * * `stability:` For example: `stability:mobile` Returns beacons whose expected
   * stability matches the given value. Values must be one of the Beacon.Stability
   * enum values (case insensitive). Accepts multiple filters which will be
   * combined with OR logic. * `place_id:""` For example:
   * `place_id:"ChIJVSZzVR8FdkgRXGmmm6SslKw="` Returns beacons explicitly
   * registered at the given place, expressed as a Place ID obtained from [Google
   * Places API](/places/place-id). Does not match places inside the given place.
   * Does not consider the beacon's actual location (which may be different from
   * its registered place). Accepts multiple filters that will be combined with OR
   * logic. The place ID must be double-quoted. * `registration_time[|=]` For
   * example: `registration_time>=1433116800` Returns beacons whose registration
   * time matches the given filter. Supports the operators: , =. Timestamp must be
   * expressed as an integer number of seconds since midnight January 1, 1970 UTC.
   * Accepts at most two filters that will be combined with AND logic, to support
   * "between" semantics. If more than two are supplied, the latter ones are
   * ignored. * `lat: lng: radius:` For example: `lat:51.1232343 lng:-1.093852
   * radius:1000` Returns beacons whose registered location is within the given
   * circle. When any of these fields are given, all are required. Latitude and
   * longitude must be decimal degrees between -90.0 and 90.0 and between -180.0
   * and 180.0 respectively. Radius must be an integer number of meters less than
   * 1,000,000 (1000 km). * `property:"="` For example: `property:"battery-
   * type=CR2032"` Returns beacons which have a property of the given name and
   * value. Supports multiple filters which will be combined with OR logic. The
   * entire name=value string must be double-quoted as one string. *
   * `attachment_type:""` For example: `attachment_type:"my-namespace/my-type"`
   * Returns beacons having at least one attachment of the given namespaced type.
   * Supports "any within this namespace" via the partial wildcard syntax: "my-
   * namespace". Supports multiple filters which will be combined with OR logic.
   * The string must be double-quoted. Multiple filters on the same field are
   * combined with OR logic (except registration_time which is combined with AND
   * logic). Multiple filters on different fields are combined with AND logic.
   * Filters should be separated by spaces. As with any HTTP query string
   * parameter, the whole filter expression must be URL-encoded. Example REST
   * request: `GET
   * /v1beta1/beacons?q=status:active%20lat:51.123%20lng:-1.095%20radius:1000`
   * @opt_param string pageToken A pagination token obtained from a previous
   * request to list beacons.
   * @opt_param int pageSize The maximum number of records to return for this
   * request, up to a server-defined upper limit.
   * @return Google_Service_Proximitybeacon_ListBeaconsResponse
   */
  public function listBeacons($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Proximitybeacon_ListBeaconsResponse");
  }

  /**
   * Registers a previously unregistered beacon given its `advertisedId`. These
   * IDs are unique within the system. An ID can be registered only once.
   * (beacons.register)
   *
   * @param Google_Beacon $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Beacon
   */
  public function register(Google_Service_Proximitybeacon_Beacon $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('register', array($params), "Google_Service_Proximitybeacon_Beacon");
  }

  /**
   * Updates the information about the specified beacon. **Any field that you do
   * not populate in the submitted beacon will be permanently erased**, so you
   * should follow the "read, modify, write" pattern to avoid inadvertently
   * destroying data. Changes to the beacon status via this method will be
   * silently ignored. To update beacon status, use the separate methods on this
   * API for (de)activation and decommissioning. (beacons.update)
   *
   * @param string $beaconName Resource name of this beacon. A beacon name has the
   * format "beacons/N!beaconId" where the beaconId is the base16 ID broadcast by
   * the beacon and N is a code for the beacon's type. Possible values are `3` for
   * Eddystone, `1` for iBeacon, or `5` for AltBeacon. This field must be left
   * empty when registering. After reading a beacon, clients can use the name for
   * future operations.
   * @param Google_Beacon $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Beacon
   */
  public function update($beaconName, Google_Service_Proximitybeacon_Beacon $postBody, $optParams = array())
  {
    $params = array('beaconName' => $beaconName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Proximitybeacon_Beacon");
  }
}

/**
 * The "attachments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $proximitybeaconService = new Google_Service_Proximitybeacon(...);
 *   $attachments = $proximitybeaconService->attachments;
 *  </code>
 */
class Google_Service_Proximitybeacon_BeaconsAttachments_Resource extends Google_Service_Resource
{

  /**
   * Deletes multiple attachments on a given beacon. This operation is permanent
   * and cannot be undone. You can optionally specify `namespacedType` to choose
   * which attachments should be deleted. If you do not specify `namespacedType`,
   * all your attachments on the given beacon will be deleted. You also may
   * explicitly specify `*` to delete all. (attachments.batchDelete)
   *
   * @param string $beaconName The beacon whose attachments are to be deleted.
   * Required.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string namespacedType Specifies the namespace and type of
   * attachments to delete in `namespace/type` format. Accepts `*` to specify "all
   * types in all namespaces". Optional.
   * @return Google_Service_Proximitybeacon_DeleteAttachmentsResponse
   */
  public function batchDelete($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('batchDelete', array($params), "Google_Service_Proximitybeacon_DeleteAttachmentsResponse");
  }

  /**
   * Associates the given data with the specified beacon. Attachment data must
   * contain two parts: - A namespaced type.  - The actual attachment data itself.
   * The namespaced type consists of two parts, the namespace and the type. The
   * namespace must be one of the values returned by the `namespaces` endpoint,
   * while the type can be a string of any characters except for the forward slash
   * (`/`) up to 100 characters in length. Attachment data can be up to 1024 bytes
   * long. (attachments.create)
   *
   * @param string $beaconName The beacon on which the attachment should be
   * created. Required.
   * @param Google_BeaconAttachment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_BeaconAttachment
   */
  public function create($beaconName, Google_Service_Proximitybeacon_BeaconAttachment $postBody, $optParams = array())
  {
    $params = array('beaconName' => $beaconName, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('create', array($params), "Google_Service_Proximitybeacon_BeaconAttachment");
  }

  /**
   * Deletes the specified attachment for the given beacon. Each attachment has a
   * unique attachment name (`attachmentName`) which is returned when you fetch
   * the attachment data via this API. You specify this with the delete request to
   * control which attachment is removed. This operation cannot be undone.
   * (attachments.delete)
   *
   * @param string $attachmentName The attachment name (`attachmentName`) of the
   * attachment to remove. For example:
   * `beacons/3!893737abc9/attachments/c5e937-af0-494-959-ec49d12738` Required.
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_Empty
   */
  public function delete($attachmentName, $optParams = array())
  {
    $params = array('attachmentName' => $attachmentName);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_Proximitybeacon_Empty");
  }

  /**
   * Returns the attachments for the specified beacon that match the specified
   * namespaced-type pattern. To control which namespaced types are returned, you
   * add the `namespacedType` query parameter to the request. You must either use
   * `*`, to return all attachments, or the namespace must be one of the ones
   * returned from the `namespaces` endpoint. (attachments.listBeaconsAttachments)
   *
   * @param string $beaconName The beacon whose attachments are to be fetched.
   * Required.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string namespacedType Specifies the namespace and type of
   * attachment to include in response in namespace/type format. Accepts `*` to
   * specify "all types in all namespaces".
   * @return Google_Service_Proximitybeacon_ListBeaconAttachmentsResponse
   */
  public function listBeaconsAttachments($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Proximitybeacon_ListBeaconAttachmentsResponse");
  }
}
/**
 * The "diagnostics" collection of methods.
 * Typical usage is:
 *  <code>
 *   $proximitybeaconService = new Google_Service_Proximitybeacon(...);
 *   $diagnostics = $proximitybeaconService->diagnostics;
 *  </code>
 */
class Google_Service_Proximitybeacon_BeaconsDiagnostics_Resource extends Google_Service_Resource
{

  /**
   * List the diagnostics for a single beacon. You can also list diagnostics for
   * all the beacons owned by your Google Developers Console project by using the
   * beacon name `beacons/-`. (diagnostics.listBeaconsDiagnostics)
   *
   * @param string $beaconName Beacon that the diagnostics are for.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Requests results that occur after the
   * `page_token`, obtained from the response to a previous request. Optional.
   * @opt_param string alertFilter Requests only beacons that have the given
   * alert. For example, to find beacons that have low batteries use
   * `alert_filter=LOW_BATTERY`.
   * @opt_param int pageSize Specifies the maximum number of results to return.
   * Defaults to 10. Maximum 1000. Optional.
   * @return Google_Service_Proximitybeacon_ListDiagnosticsResponse
   */
  public function listBeaconsDiagnostics($beaconName, $optParams = array())
  {
    $params = array('beaconName' => $beaconName);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Proximitybeacon_ListDiagnosticsResponse");
  }
}

/**
 * The "namespaces" collection of methods.
 * Typical usage is:
 *  <code>
 *   $proximitybeaconService = new Google_Service_Proximitybeacon(...);
 *   $namespaces = $proximitybeaconService->namespaces;
 *  </code>
 */
class Google_Service_Proximitybeacon_Namespaces_Resource extends Google_Service_Resource
{

  /**
   * Lists all attachment namespaces owned by your Google Developers Console
   * project. Attachment data associated with a beacon must include a namespaced
   * type, and the namespace must be owned by your project.
   * (namespaces.listNamespaces)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_Proximitybeacon_ListNamespacesResponse
   */
  public function listNamespaces($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_Proximitybeacon_ListNamespacesResponse");
  }
}




class Google_Service_Proximitybeacon_AdvertisedId extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $type;


  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
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

class Google_Service_Proximitybeacon_AttachmentInfo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $data;
  public $namespacedType;


  public function setData($data)
  {
    $this->data = $data;
  }
  public function getData()
  {
    return $this->data;
  }
  public function setNamespacedType($namespacedType)
  {
    $this->namespacedType = $namespacedType;
  }
  public function getNamespacedType()
  {
    return $this->namespacedType;
  }
}

class Google_Service_Proximitybeacon_Beacon extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $advertisedIdType = 'Google_Service_Proximitybeacon_AdvertisedId';
  protected $advertisedIdDataType = '';
  public $beaconName;
  public $description;
  public $expectedStability;
  protected $indoorLevelType = 'Google_Service_Proximitybeacon_IndoorLevel';
  protected $indoorLevelDataType = '';
  protected $latLngType = 'Google_Service_Proximitybeacon_LatLng';
  protected $latLngDataType = '';
  public $placeId;
  public $properties;
  public $status;


  public function setAdvertisedId(Google_Service_Proximitybeacon_AdvertisedId $advertisedId)
  {
    $this->advertisedId = $advertisedId;
  }
  public function getAdvertisedId()
  {
    return $this->advertisedId;
  }
  public function setBeaconName($beaconName)
  {
    $this->beaconName = $beaconName;
  }
  public function getBeaconName()
  {
    return $this->beaconName;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setExpectedStability($expectedStability)
  {
    $this->expectedStability = $expectedStability;
  }
  public function getExpectedStability()
  {
    return $this->expectedStability;
  }
  public function setIndoorLevel(Google_Service_Proximitybeacon_IndoorLevel $indoorLevel)
  {
    $this->indoorLevel = $indoorLevel;
  }
  public function getIndoorLevel()
  {
    return $this->indoorLevel;
  }
  public function setLatLng(Google_Service_Proximitybeacon_LatLng $latLng)
  {
    $this->latLng = $latLng;
  }
  public function getLatLng()
  {
    return $this->latLng;
  }
  public function setPlaceId($placeId)
  {
    $this->placeId = $placeId;
  }
  public function getPlaceId()
  {
    return $this->placeId;
  }
  public function setProperties($properties)
  {
    $this->properties = $properties;
  }
  public function getProperties()
  {
    return $this->properties;
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

class Google_Service_Proximitybeacon_BeaconAttachment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $attachmentName;
  public $data;
  public $namespacedType;


  public function setAttachmentName($attachmentName)
  {
    $this->attachmentName = $attachmentName;
  }
  public function getAttachmentName()
  {
    return $this->attachmentName;
  }
  public function setData($data)
  {
    $this->data = $data;
  }
  public function getData()
  {
    return $this->data;
  }
  public function setNamespacedType($namespacedType)
  {
    $this->namespacedType = $namespacedType;
  }
  public function getNamespacedType()
  {
    return $this->namespacedType;
  }
}

class Google_Service_Proximitybeacon_BeaconInfo extends Google_Collection
{
  protected $collection_key = 'attachments';
  protected $internal_gapi_mappings = array(
  );
  protected $advertisedIdType = 'Google_Service_Proximitybeacon_AdvertisedId';
  protected $advertisedIdDataType = '';
  protected $attachmentsType = 'Google_Service_Proximitybeacon_AttachmentInfo';
  protected $attachmentsDataType = 'array';
  public $beaconName;
  public $description;


  public function setAdvertisedId(Google_Service_Proximitybeacon_AdvertisedId $advertisedId)
  {
    $this->advertisedId = $advertisedId;
  }
  public function getAdvertisedId()
  {
    return $this->advertisedId;
  }
  public function setAttachments($attachments)
  {
    $this->attachments = $attachments;
  }
  public function getAttachments()
  {
    return $this->attachments;
  }
  public function setBeaconName($beaconName)
  {
    $this->beaconName = $beaconName;
  }
  public function getBeaconName()
  {
    return $this->beaconName;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
}

class Google_Service_Proximitybeacon_BeaconProperties extends Google_Model
{
}

class Google_Service_Proximitybeacon_Date extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $day;
  public $month;
  public $year;


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
  public function setYear($year)
  {
    $this->year = $year;
  }
  public function getYear()
  {
    return $this->year;
  }
}

class Google_Service_Proximitybeacon_DeleteAttachmentsResponse extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $numDeleted;


  public function setNumDeleted($numDeleted)
  {
    $this->numDeleted = $numDeleted;
  }
  public function getNumDeleted()
  {
    return $this->numDeleted;
  }
}

class Google_Service_Proximitybeacon_Diagnostics extends Google_Collection
{
  protected $collection_key = 'alerts';
  protected $internal_gapi_mappings = array(
  );
  public $alerts;
  public $beaconName;
  protected $estimatedLowBatteryDateType = 'Google_Service_Proximitybeacon_Date';
  protected $estimatedLowBatteryDateDataType = '';


  public function setAlerts($alerts)
  {
    $this->alerts = $alerts;
  }
  public function getAlerts()
  {
    return $this->alerts;
  }
  public function setBeaconName($beaconName)
  {
    $this->beaconName = $beaconName;
  }
  public function getBeaconName()
  {
    return $this->beaconName;
  }
  public function setEstimatedLowBatteryDate(Google_Service_Proximitybeacon_Date $estimatedLowBatteryDate)
  {
    $this->estimatedLowBatteryDate = $estimatedLowBatteryDate;
  }
  public function getEstimatedLowBatteryDate()
  {
    return $this->estimatedLowBatteryDate;
  }
}

class Google_Service_Proximitybeacon_Empty extends Google_Model
{
}

class Google_Service_Proximitybeacon_GetInfoForObservedBeaconsRequest extends Google_Collection
{
  protected $collection_key = 'observations';
  protected $internal_gapi_mappings = array(
  );
  public $namespacedTypes;
  protected $observationsType = 'Google_Service_Proximitybeacon_Observation';
  protected $observationsDataType = 'array';


  public function setNamespacedTypes($namespacedTypes)
  {
    $this->namespacedTypes = $namespacedTypes;
  }
  public function getNamespacedTypes()
  {
    return $this->namespacedTypes;
  }
  public function setObservations($observations)
  {
    $this->observations = $observations;
  }
  public function getObservations()
  {
    return $this->observations;
  }
}

class Google_Service_Proximitybeacon_GetInfoForObservedBeaconsResponse extends Google_Collection
{
  protected $collection_key = 'beacons';
  protected $internal_gapi_mappings = array(
  );
  protected $beaconsType = 'Google_Service_Proximitybeacon_BeaconInfo';
  protected $beaconsDataType = 'array';


  public function setBeacons($beacons)
  {
    $this->beacons = $beacons;
  }
  public function getBeacons()
  {
    return $this->beacons;
  }
}

class Google_Service_Proximitybeacon_IndoorLevel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $name;


  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
}

class Google_Service_Proximitybeacon_LatLng extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $latitude;
  public $longitude;


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
}

class Google_Service_Proximitybeacon_ListBeaconAttachmentsResponse extends Google_Collection
{
  protected $collection_key = 'attachments';
  protected $internal_gapi_mappings = array(
  );
  protected $attachmentsType = 'Google_Service_Proximitybeacon_BeaconAttachment';
  protected $attachmentsDataType = 'array';


  public function setAttachments($attachments)
  {
    $this->attachments = $attachments;
  }
  public function getAttachments()
  {
    return $this->attachments;
  }
}

class Google_Service_Proximitybeacon_ListBeaconsResponse extends Google_Collection
{
  protected $collection_key = 'beacons';
  protected $internal_gapi_mappings = array(
  );
  protected $beaconsType = 'Google_Service_Proximitybeacon_Beacon';
  protected $beaconsDataType = 'array';
  public $nextPageToken;
  public $totalCount;


  public function setBeacons($beacons)
  {
    $this->beacons = $beacons;
  }
  public function getBeacons()
  {
    return $this->beacons;
  }
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  public function setTotalCount($totalCount)
  {
    $this->totalCount = $totalCount;
  }
  public function getTotalCount()
  {
    return $this->totalCount;
  }
}

class Google_Service_Proximitybeacon_ListDiagnosticsResponse extends Google_Collection
{
  protected $collection_key = 'diagnostics';
  protected $internal_gapi_mappings = array(
  );
  protected $diagnosticsType = 'Google_Service_Proximitybeacon_Diagnostics';
  protected $diagnosticsDataType = 'array';
  public $nextPageToken;


  public function setDiagnostics($diagnostics)
  {
    $this->diagnostics = $diagnostics;
  }
  public function getDiagnostics()
  {
    return $this->diagnostics;
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

class Google_Service_Proximitybeacon_ListNamespacesResponse extends Google_Collection
{
  protected $collection_key = 'namespaces';
  protected $internal_gapi_mappings = array(
  );
  protected $namespacesType = 'Google_Service_Proximitybeacon_ProximitybeaconNamespace';
  protected $namespacesDataType = 'array';


  public function setNamespaces($namespaces)
  {
    $this->namespaces = $namespaces;
  }
  public function getNamespaces()
  {
    return $this->namespaces;
  }
}

class Google_Service_Proximitybeacon_Observation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $advertisedIdType = 'Google_Service_Proximitybeacon_AdvertisedId';
  protected $advertisedIdDataType = '';
  public $telemetry;
  public $timestampMs;


  public function setAdvertisedId(Google_Service_Proximitybeacon_AdvertisedId $advertisedId)
  {
    $this->advertisedId = $advertisedId;
  }
  public function getAdvertisedId()
  {
    return $this->advertisedId;
  }
  public function setTelemetry($telemetry)
  {
    $this->telemetry = $telemetry;
  }
  public function getTelemetry()
  {
    return $this->telemetry;
  }
  public function setTimestampMs($timestampMs)
  {
    $this->timestampMs = $timestampMs;
  }
  public function getTimestampMs()
  {
    return $this->timestampMs;
  }
}

class Google_Service_Proximitybeacon_ProximitybeaconNamespace extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $namespaceName;
  public $servingVisibility;


  public function setNamespaceName($namespaceName)
  {
    $this->namespaceName = $namespaceName;
  }
  public function getNamespaceName()
  {
    return $this->namespaceName;
  }
  public function setServingVisibility($servingVisibility)
  {
    $this->servingVisibility = $servingVisibility;
  }
  public function getServingVisibility()
  {
    return $this->servingVisibility;
  }
}
