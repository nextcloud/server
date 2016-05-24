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
 * Service definition for YouTubeAnalytics (v1).
 *
 * <p>
 * Retrieve your YouTube Analytics reports.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="http://developers.google.com/youtube/analytics/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_YouTubeAnalytics extends Google_Service
{
  /** Manage your YouTube account. */
  const YOUTUBE =
      "https://www.googleapis.com/auth/youtube";
  /** View your YouTube account. */
  const YOUTUBE_READONLY =
      "https://www.googleapis.com/auth/youtube.readonly";
  /** View and manage your assets and associated content on YouTube. */
  const YOUTUBEPARTNER =
      "https://www.googleapis.com/auth/youtubepartner";
  /** View monetary and non-monetary YouTube Analytics reports for your YouTube content. */
  const YT_ANALYTICS_MONETARY_READONLY =
      "https://www.googleapis.com/auth/yt-analytics-monetary.readonly";
  /** View YouTube Analytics reports for your YouTube content. */
  const YT_ANALYTICS_READONLY =
      "https://www.googleapis.com/auth/yt-analytics.readonly";

  public $batchReportDefinitions;
  public $batchReports;
  public $groupItems;
  public $groups;
  public $reports;
  

  /**
   * Constructs the internal representation of the YouTubeAnalytics service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'youtube/analytics/v1/';
    $this->version = 'v1';
    $this->serviceName = 'youtubeAnalytics';

    $this->batchReportDefinitions = new Google_Service_YouTubeAnalytics_BatchReportDefinitions_Resource(
        $this,
        $this->serviceName,
        'batchReportDefinitions',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'batchReportDefinitions',
              'httpMethod' => 'GET',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->batchReports = new Google_Service_YouTubeAnalytics_BatchReports_Resource(
        $this,
        $this->serviceName,
        'batchReports',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'batchReports',
              'httpMethod' => 'GET',
              'parameters' => array(
                'batchReportDefinitionId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->groupItems = new Google_Service_YouTubeAnalytics_GroupItems_Resource(
        $this,
        $this->serviceName,
        'groupItems',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'groupItems',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'groupItems',
              'httpMethod' => 'POST',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'groupItems',
              'httpMethod' => 'GET',
              'parameters' => array(
                'groupId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->groups = new Google_Service_YouTubeAnalytics_Groups_Resource(
        $this,
        $this->serviceName,
        'groups',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'groups',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'insert' => array(
              'path' => 'groups',
              'httpMethod' => 'POST',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'list' => array(
              'path' => 'groups',
              'httpMethod' => 'GET',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'id' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mine' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
              ),
            ),'update' => array(
              'path' => 'groups',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'onBehalfOfContentOwner' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->reports = new Google_Service_YouTubeAnalytics_Reports_Resource(
        $this,
        $this->serviceName,
        'reports',
        array(
          'methods' => array(
            'query' => array(
              'path' => 'reports',
              'httpMethod' => 'GET',
              'parameters' => array(
                'ids' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'start-date' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'end-date' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'metrics' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'max-results' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'sort' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'dimensions' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'start-index' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'currency' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'filters' => array(
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
 * The "batchReportDefinitions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $batchReportDefinitions = $youtubeAnalyticsService->batchReportDefinitions;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_BatchReportDefinitions_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of available batch report definitions.
   * (batchReportDefinitions.listBatchReportDefinitions)
   *
   * @param string $onBehalfOfContentOwner The onBehalfOfContentOwner parameter
   * identifies the content owner that the user is acting on behalf of.
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTubeAnalytics_BatchReportDefinitionList
   */
  public function listBatchReportDefinitions($onBehalfOfContentOwner, $optParams = array())
  {
    $params = array('onBehalfOfContentOwner' => $onBehalfOfContentOwner);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTubeAnalytics_BatchReportDefinitionList");
  }
}

/**
 * The "batchReports" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $batchReports = $youtubeAnalyticsService->batchReports;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_BatchReports_Resource extends Google_Service_Resource
{

  /**
   * Retrieves a list of processed batch reports. (batchReports.listBatchReports)
   *
   * @param string $batchReportDefinitionId The batchReportDefinitionId parameter
   * specifies the ID of the batch reportort definition for which you are
   * retrieving reports.
   * @param string $onBehalfOfContentOwner The onBehalfOfContentOwner parameter
   * identifies the content owner that the user is acting on behalf of.
   * @param array $optParams Optional parameters.
   * @return Google_Service_YouTubeAnalytics_BatchReportList
   */
  public function listBatchReports($batchReportDefinitionId, $onBehalfOfContentOwner, $optParams = array())
  {
    $params = array('batchReportDefinitionId' => $batchReportDefinitionId, 'onBehalfOfContentOwner' => $onBehalfOfContentOwner);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTubeAnalytics_BatchReportList");
  }
}

/**
 * The "groupItems" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $groupItems = $youtubeAnalyticsService->groupItems;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_GroupItems_Resource extends Google_Service_Resource
{

  /**
   * Removes an item from a group. (groupItems.delete)
   *
   * @param string $id The id parameter specifies the YouTube group item ID for
   * the group that is being deleted.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a group item. (groupItems.insert)
   *
   * @param Google_GroupItem $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTubeAnalytics_GroupItem
   */
  public function insert(Google_Service_YouTubeAnalytics_GroupItem $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTubeAnalytics_GroupItem");
  }

  /**
   * Returns a collection of group items that match the API request parameters.
   * (groupItems.listGroupItems)
   *
   * @param string $groupId The id parameter specifies the unique ID of the group
   * for which you want to retrieve group items.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTubeAnalytics_GroupItemListResponse
   */
  public function listGroupItems($groupId, $optParams = array())
  {
    $params = array('groupId' => $groupId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTubeAnalytics_GroupItemListResponse");
  }
}

/**
 * The "groups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $groups = $youtubeAnalyticsService->groups;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_Groups_Resource extends Google_Service_Resource
{

  /**
   * Deletes a group. (groups.delete)
   *
   * @param string $id The id parameter specifies the YouTube group ID for the
   * group that is being deleted.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   */
  public function delete($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Creates a group. (groups.insert)
   *
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTubeAnalytics_Group
   */
  public function insert(Google_Service_YouTubeAnalytics_Group $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_YouTubeAnalytics_Group");
  }

  /**
   * Returns a collection of groups that match the API request parameters. For
   * example, you can retrieve all groups that the authenticated user owns, or you
   * can retrieve one or more groups by their unique IDs. (groups.listGroups)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @opt_param string id The id parameter specifies a comma-separated list of the
   * YouTube group ID(s) for the resource(s) that are being retrieved. In a group
   * resource, the id property specifies the group's YouTube group ID.
   * @opt_param bool mine Set this parameter's value to true to instruct the API
   * to only return groups owned by the authenticated user.
   * @return Google_Service_YouTubeAnalytics_GroupListResponse
   */
  public function listGroups($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_YouTubeAnalytics_GroupListResponse");
  }

  /**
   * Modifies a group. For example, you could change a group's title.
   * (groups.update)
   *
   * @param Google_Group $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string onBehalfOfContentOwner Note: This parameter is intended
   * exclusively for YouTube content partners.
   *
   * The onBehalfOfContentOwner parameter indicates that the request's
   * authorization credentials identify a YouTube CMS user who is acting on behalf
   * of the content owner specified in the parameter value. This parameter is
   * intended for YouTube content partners that own and manage many different
   * YouTube channels. It allows content owners to authenticate once and get
   * access to all their video and channel data, without having to provide
   * authentication credentials for each individual channel. The CMS account that
   * the user authenticates with must be linked to the specified YouTube content
   * owner.
   * @return Google_Service_YouTubeAnalytics_Group
   */
  public function update(Google_Service_YouTubeAnalytics_Group $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_YouTubeAnalytics_Group");
  }
}

/**
 * The "reports" collection of methods.
 * Typical usage is:
 *  <code>
 *   $youtubeAnalyticsService = new Google_Service_YouTubeAnalytics(...);
 *   $reports = $youtubeAnalyticsService->reports;
 *  </code>
 */
class Google_Service_YouTubeAnalytics_Reports_Resource extends Google_Service_Resource
{

  /**
   * Retrieve your YouTube Analytics reports. (reports.query)
   *
   * @param string $ids Identifies the YouTube channel or content owner for which
   * you are retrieving YouTube Analytics data. - To request data for a YouTube
   * user, set the ids parameter value to channel==CHANNEL_ID, where CHANNEL_ID
   * specifies the unique YouTube channel ID. - To request data for a YouTube CMS
   * content owner, set the ids parameter value to contentOwner==OWNER_NAME, where
   * OWNER_NAME is the CMS name of the content owner.
   * @param string $startDate The start date for fetching YouTube Analytics data.
   * The value should be in YYYY-MM-DD format.
   * @param string $endDate The end date for fetching YouTube Analytics data. The
   * value should be in YYYY-MM-DD format.
   * @param string $metrics A comma-separated list of YouTube Analytics metrics,
   * such as views or likes,dislikes. See the Available Reports document for a
   * list of the reports that you can retrieve and the metrics available in each
   * report, and see the Metrics document for definitions of those metrics.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int max-results The maximum number of rows to include in the
   * response.
   * @opt_param string sort A comma-separated list of dimensions or metrics that
   * determine the sort order for YouTube Analytics data. By default the sort
   * order is ascending. The '-' prefix causes descending sort order.
   * @opt_param string dimensions A comma-separated list of YouTube Analytics
   * dimensions, such as views or ageGroup,gender. See the Available Reports
   * document for a list of the reports that you can retrieve and the dimensions
   * used for those reports. Also see the Dimensions document for definitions of
   * those dimensions.
   * @opt_param int start-index An index of the first entity to retrieve. Use this
   * parameter as a pagination mechanism along with the max-results parameter
   * (one-based, inclusive).
   * @opt_param string currency The currency to which financial metrics should be
   * converted. The default is US Dollar (USD). If the result contains no
   * financial metrics, this flag will be ignored. Responds with an error if the
   * specified currency is not recognized.
   * @opt_param string filters A list of filters that should be applied when
   * retrieving YouTube Analytics data. The Available Reports document identifies
   * the dimensions that can be used to filter each report, and the Dimensions
   * document defines those dimensions. If a request uses multiple filters, join
   * them together with a semicolon (;), and the returned result table will
   * satisfy both filters. For example, a filters parameter value of
   * video==dMH0bHeiRNg;country==IT restricts the result set to include data for
   * the given video in Italy.
   * @return Google_Service_YouTubeAnalytics_ResultTable
   */
  public function query($ids, $startDate, $endDate, $metrics, $optParams = array())
  {
    $params = array('ids' => $ids, 'start-date' => $startDate, 'end-date' => $endDate, 'metrics' => $metrics);
    $params = array_merge($params, $optParams);
    return $this->call('query', array($params), "Google_Service_YouTubeAnalytics_ResultTable");
  }
}




class Google_Service_YouTubeAnalytics_BatchReport extends Google_Collection
{
  protected $collection_key = 'outputs';
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  protected $outputsType = 'Google_Service_YouTubeAnalytics_BatchReportOutputs';
  protected $outputsDataType = 'array';
  public $reportId;
  protected $timeSpanType = 'Google_Service_YouTubeAnalytics_BatchReportTimeSpan';
  protected $timeSpanDataType = '';
  public $timeUpdated;


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
  public function setOutputs($outputs)
  {
    $this->outputs = $outputs;
  }
  public function getOutputs()
  {
    return $this->outputs;
  }
  public function setReportId($reportId)
  {
    $this->reportId = $reportId;
  }
  public function getReportId()
  {
    return $this->reportId;
  }
  public function setTimeSpan(Google_Service_YouTubeAnalytics_BatchReportTimeSpan $timeSpan)
  {
    $this->timeSpan = $timeSpan;
  }
  public function getTimeSpan()
  {
    return $this->timeSpan;
  }
  public function setTimeUpdated($timeUpdated)
  {
    $this->timeUpdated = $timeUpdated;
  }
  public function getTimeUpdated()
  {
    return $this->timeUpdated;
  }
}

class Google_Service_YouTubeAnalytics_BatchReportDefinition extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;
  public $status;
  public $type;


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
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
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

class Google_Service_YouTubeAnalytics_BatchReportDefinitionList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_YouTubeAnalytics_BatchReportDefinition';
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

class Google_Service_YouTubeAnalytics_BatchReportList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_YouTubeAnalytics_BatchReport';
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

class Google_Service_YouTubeAnalytics_BatchReportOutputs extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $downloadUrl;
  public $format;
  public $type;


  public function setDownloadUrl($downloadUrl)
  {
    $this->downloadUrl = $downloadUrl;
  }
  public function getDownloadUrl()
  {
    return $this->downloadUrl;
  }
  public function setFormat($format)
  {
    $this->format = $format;
  }
  public function getFormat()
  {
    return $this->format;
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

class Google_Service_YouTubeAnalytics_BatchReportTimeSpan extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $endTime;
  public $startTime;


  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  public function getEndTime()
  {
    return $this->endTime;
  }
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  public function getStartTime()
  {
    return $this->startTime;
  }
}

class Google_Service_YouTubeAnalytics_Group extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $contentDetailsType = 'Google_Service_YouTubeAnalytics_GroupContentDetails';
  protected $contentDetailsDataType = '';
  public $etag;
  public $id;
  public $kind;
  protected $snippetType = 'Google_Service_YouTubeAnalytics_GroupSnippet';
  protected $snippetDataType = '';


  public function setContentDetails(Google_Service_YouTubeAnalytics_GroupContentDetails $contentDetails)
  {
    $this->contentDetails = $contentDetails;
  }
  public function getContentDetails()
  {
    return $this->contentDetails;
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
  public function setSnippet(Google_Service_YouTubeAnalytics_GroupSnippet $snippet)
  {
    $this->snippet = $snippet;
  }
  public function getSnippet()
  {
    return $this->snippet;
  }
}

class Google_Service_YouTubeAnalytics_GroupContentDetails extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $itemCount;
  public $itemType;


  public function setItemCount($itemCount)
  {
    $this->itemCount = $itemCount;
  }
  public function getItemCount()
  {
    return $this->itemCount;
  }
  public function setItemType($itemType)
  {
    $this->itemType = $itemType;
  }
  public function getItemType()
  {
    return $this->itemType;
  }
}

class Google_Service_YouTubeAnalytics_GroupItem extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  public $groupId;
  public $id;
  public $kind;
  protected $resourceType = 'Google_Service_YouTubeAnalytics_GroupItemResource';
  protected $resourceDataType = '';


  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  public function getEtag()
  {
    return $this->etag;
  }
  public function setGroupId($groupId)
  {
    $this->groupId = $groupId;
  }
  public function getGroupId()
  {
    return $this->groupId;
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
  public function setResource(Google_Service_YouTubeAnalytics_GroupItemResource $resource)
  {
    $this->resource = $resource;
  }
  public function getResource()
  {
    return $this->resource;
  }
}

class Google_Service_YouTubeAnalytics_GroupItemListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_YouTubeAnalytics_GroupItem';
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

class Google_Service_YouTubeAnalytics_GroupItemResource extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;


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

class Google_Service_YouTubeAnalytics_GroupListResponse extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_YouTubeAnalytics_Group';
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

class Google_Service_YouTubeAnalytics_GroupSnippet extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $publishedAt;
  public $title;


  public function setPublishedAt($publishedAt)
  {
    $this->publishedAt = $publishedAt;
  }
  public function getPublishedAt()
  {
    return $this->publishedAt;
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

class Google_Service_YouTubeAnalytics_ResultTable extends Google_Collection
{
  protected $collection_key = 'rows';
  protected $internal_gapi_mappings = array(
  );
  protected $columnHeadersType = 'Google_Service_YouTubeAnalytics_ResultTableColumnHeaders';
  protected $columnHeadersDataType = 'array';
  public $kind;
  public $rows;


  public function setColumnHeaders($columnHeaders)
  {
    $this->columnHeaders = $columnHeaders;
  }
  public function getColumnHeaders()
  {
    return $this->columnHeaders;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setRows($rows)
  {
    $this->rows = $rows;
  }
  public function getRows()
  {
    return $this->rows;
  }
}

class Google_Service_YouTubeAnalytics_ResultTableColumnHeaders extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $columnType;
  public $dataType;
  public $name;


  public function setColumnType($columnType)
  {
    $this->columnType = $columnType;
  }
  public function getColumnType()
  {
    return $this->columnType;
  }
  public function setDataType($dataType)
  {
    $this->dataType = $dataType;
  }
  public function getDataType()
  {
    return $this->dataType;
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
