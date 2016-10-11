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
 * Service definition for AdSenseHost (v4.1).
 *
 * <p>
 * Gives AdSense Hosts access to report generation, ad code generation, and
 * publisher management capabilities.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/adsense/host/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_AdSenseHost extends Google_Service
{
  /** View and manage your AdSense host data and associated accounts. */
  const ADSENSEHOST =
      "https://www.googleapis.com/auth/adsensehost";

  public $accounts;
  public $accounts_adclients;
  public $accounts_adunits;
  public $accounts_reports;
  public $adclients;
  public $associationsessions;
  public $customchannels;
  public $reports;
  public $urlchannels;
  

  /**
   * Constructs the internal representation of the AdSenseHost service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'adsensehost/v4.1/';
    $this->version = 'v4.1';
    $this->serviceName = 'adsensehost';

    $this->accounts = new Google_Service_AdSenseHost_Accounts_Resource(
        $this,
        $this->serviceName,
        'accounts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'accounts/{accountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'accounts',
              'httpMethod' => 'GET',
              'parameters' => array(
                'filterAdClientId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_adclients = new Google_Service_AdSenseHost_AccountsAdclients_Resource(
        $this,
        $this->serviceName,
        'adclients',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'accounts/{accountId}/adclients',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
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
    $this->accounts_adunits = new Google_Service_AdSenseHost_AccountsAdunits_Resource(
        $this,
        $this->serviceName,
        'adunits',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits/{adUnitId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adUnitId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits/{adUnitId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adUnitId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'getAdCode' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits/{adUnitId}/adcode',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adUnitId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'hostCustomChannelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits',
              'httpMethod' => 'POST',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'includeInactive' => array(
                  'location' => 'query',
                  'type' => 'boolean',
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
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adUnitId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'accounts/{accountId}/adclients/{adClientId}/adunits',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->accounts_reports = new Google_Service_AdSenseHost_AccountsReports_Resource(
        $this,
        $this->serviceName,
        'reports',
        array(
          'methods' => array(
            'generate' => array(
              'path' => 'accounts/{accountId}/reports',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'startDate' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'endDate' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'sort' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'locale' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'metric' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'startIndex' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'dimension' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->adclients = new Google_Service_AdSenseHost_Adclients_Resource(
        $this,
        $this->serviceName,
        'adclients',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'adclients/{adClientId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'adclients',
              'httpMethod' => 'GET',
              'parameters' => array(
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
    $this->associationsessions = new Google_Service_AdSenseHost_Associationsessions_Resource(
        $this,
        $this->serviceName,
        'associationsessions',
        array(
          'methods' => array(
            'start' => array(
              'path' => 'associationsessions/start',
              'httpMethod' => 'GET',
              'parameters' => array(
                'productCode' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                  'required' => true,
                ),
                'websiteUrl' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'websiteLocale' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'userLocale' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'verify' => array(
              'path' => 'associationsessions/verify',
              'httpMethod' => 'GET',
              'parameters' => array(
                'token' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->customchannels = new Google_Service_AdSenseHost_Customchannels_Resource(
        $this,
        $this->serviceName,
        'customchannels',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'adclients/{adClientId}/customchannels/{customChannelId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customChannelId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'adclients/{adClientId}/customchannels/{customChannelId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customChannelId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'adclients/{adClientId}/customchannels',
              'httpMethod' => 'POST',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'adclients/{adClientId}/customchannels',
              'httpMethod' => 'GET',
              'parameters' => array(
                'adClientId' => array(
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
              'path' => 'adclients/{adClientId}/customchannels',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customChannelId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'adclients/{adClientId}/customchannels',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->reports = new Google_Service_AdSenseHost_Reports_Resource(
        $this,
        $this->serviceName,
        'reports',
        array(
          'methods' => array(
            'generate' => array(
              'path' => 'reports',
              'httpMethod' => 'GET',
              'parameters' => array(
                'startDate' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'endDate' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'sort' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'locale' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'metric' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'maxResults' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'startIndex' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'dimension' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->urlchannels = new Google_Service_AdSenseHost_Urlchannels_Resource(
        $this,
        $this->serviceName,
        'urlchannels',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'adclients/{adClientId}/urlchannels/{urlChannelId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'urlChannelId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'adclients/{adClientId}/urlchannels',
              'httpMethod' => 'POST',
              'parameters' => array(
                'adClientId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'adclients/{adClientId}/urlchannels',
              'httpMethod' => 'GET',
              'parameters' => array(
                'adClientId' => array(
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
  }
}


/**
 * The "accounts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $accounts = $adsensehostService->accounts;
 *  </code>
 */
class Google_Service_AdSenseHost_Accounts_Resource extends Google_Service_Resource
{

  /**
   * Get information about the selected associated AdSense account. (accounts.get)
   *
   * @param string $accountId Account to get information about.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_Account
   */
  public function get($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdSenseHost_Account");
  }

  /**
   * List hosted accounts associated with this AdSense account by ad client id.
   * (accounts.listAccounts)
   *
   * @param string $filterAdClientId Ad clients to list accounts for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_Accounts
   */
  public function listAccounts($filterAdClientId, $optParams = array())
  {
    $params = array('filterAdClientId' => $filterAdClientId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_Accounts");
  }
}

/**
 * The "adclients" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $adclients = $adsensehostService->adclients;
 *  </code>
 */
class Google_Service_AdSenseHost_AccountsAdclients_Resource extends Google_Service_Resource
{

  /**
   * Get information about one of the ad clients in the specified publisher's
   * AdSense account. (adclients.get)
   *
   * @param string $accountId Account which contains the ad client.
   * @param string $adClientId Ad client to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdClient
   */
  public function get($accountId, $adClientId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdSenseHost_AdClient");
  }

  /**
   * List all hosted ad clients in the specified hosted account.
   * (adclients.listAccountsAdclients)
   *
   * @param string $accountId Account for which to list ad clients.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A continuation token, used to page through ad
   * clients. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response.
   * @opt_param string maxResults The maximum number of ad clients to include in
   * the response, used for paging.
   * @return Google_Service_AdSenseHost_AdClients
   */
  public function listAccountsAdclients($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_AdClients");
  }
}
/**
 * The "adunits" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $adunits = $adsensehostService->adunits;
 *  </code>
 */
class Google_Service_AdSenseHost_AccountsAdunits_Resource extends Google_Service_Resource
{

  /**
   * Delete the specified ad unit from the specified publisher AdSense account.
   * (adunits.delete)
   *
   * @param string $accountId Account which contains the ad unit.
   * @param string $adClientId Ad client for which to get ad unit.
   * @param string $adUnitId Ad unit to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdUnit
   */
  public function delete($accountId, $adClientId, $adUnitId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'adUnitId' => $adUnitId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AdSenseHost_AdUnit");
  }

  /**
   * Get the specified host ad unit in this AdSense account. (adunits.get)
   *
   * @param string $accountId Account which contains the ad unit.
   * @param string $adClientId Ad client for which to get ad unit.
   * @param string $adUnitId Ad unit to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdUnit
   */
  public function get($accountId, $adClientId, $adUnitId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'adUnitId' => $adUnitId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdSenseHost_AdUnit");
  }

  /**
   * Get ad code for the specified ad unit, attaching the specified host custom
   * channels. (adunits.getAdCode)
   *
   * @param string $accountId Account which contains the ad client.
   * @param string $adClientId Ad client with contains the ad unit.
   * @param string $adUnitId Ad unit to get the code for.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string hostCustomChannelId Host custom channel to attach to the ad
   * code.
   * @return Google_Service_AdSenseHost_AdCode
   */
  public function getAdCode($accountId, $adClientId, $adUnitId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'adUnitId' => $adUnitId);
    $params = array_merge($params, $optParams);
    return $this->call('getAdCode', array($params), "Google_Service_AdSenseHost_AdCode");
  }

  /**
   * Insert the supplied ad unit into the specified publisher AdSense account.
   * (adunits.insert)
   *
   * @param string $accountId Account which will contain the ad unit.
   * @param string $adClientId Ad client into which to insert the ad unit.
   * @param Google_AdUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdUnit
   */
  public function insert($accountId, $adClientId, Google_Service_AdSenseHost_AdUnit $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdSenseHost_AdUnit");
  }

  /**
   * List all ad units in the specified publisher's AdSense account.
   * (adunits.listAccountsAdunits)
   *
   * @param string $accountId Account which contains the ad client.
   * @param string $adClientId Ad client for which to list ad units.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool includeInactive Whether to include inactive ad units.
   * Default: true.
   * @opt_param string pageToken A continuation token, used to page through ad
   * units. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response.
   * @opt_param string maxResults The maximum number of ad units to include in the
   * response, used for paging.
   * @return Google_Service_AdSenseHost_AdUnits
   */
  public function listAccountsAdunits($accountId, $adClientId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_AdUnits");
  }

  /**
   * Update the supplied ad unit in the specified publisher AdSense account. This
   * method supports patch semantics. (adunits.patch)
   *
   * @param string $accountId Account which contains the ad client.
   * @param string $adClientId Ad client which contains the ad unit.
   * @param string $adUnitId Ad unit to get.
   * @param Google_AdUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdUnit
   */
  public function patch($accountId, $adClientId, $adUnitId, Google_Service_AdSenseHost_AdUnit $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'adUnitId' => $adUnitId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdSenseHost_AdUnit");
  }

  /**
   * Update the supplied ad unit in the specified publisher AdSense account.
   * (adunits.update)
   *
   * @param string $accountId Account which contains the ad client.
   * @param string $adClientId Ad client which contains the ad unit.
   * @param Google_AdUnit $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdUnit
   */
  public function update($accountId, $adClientId, Google_Service_AdSenseHost_AdUnit $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'adClientId' => $adClientId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdSenseHost_AdUnit");
  }
}
/**
 * The "reports" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $reports = $adsensehostService->reports;
 *  </code>
 */
class Google_Service_AdSenseHost_AccountsReports_Resource extends Google_Service_Resource
{

  /**
   * Generate an AdSense report based on the report request sent in the query
   * parameters. Returns the result as JSON; to retrieve output in CSV format
   * specify "alt=csv" as a query parameter. (reports.generate)
   *
   * @param string $accountId Hosted account upon which to report.
   * @param string $startDate Start of the date range to report on in "YYYY-MM-DD"
   * format, inclusive.
   * @param string $endDate End of the date range to report on in "YYYY-MM-DD"
   * format, inclusive.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sort The name of a dimension or metric to sort the
   * resulting report on, optionally prefixed with "+" to sort ascending or "-" to
   * sort descending. If no prefix is specified, the column is sorted ascending.
   * @opt_param string locale Optional locale to use for translating report output
   * to a local language. Defaults to "en_US" if not specified.
   * @opt_param string metric Numeric columns to include in the report.
   * @opt_param string maxResults The maximum number of rows of report data to
   * return.
   * @opt_param string filter Filters to be run on the report.
   * @opt_param string startIndex Index of the first row of report data to return.
   * @opt_param string dimension Dimensions to base the report on.
   * @return Google_Service_AdSenseHost_Report
   */
  public function generate($accountId, $startDate, $endDate, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'startDate' => $startDate, 'endDate' => $endDate);
    $params = array_merge($params, $optParams);
    return $this->call('generate', array($params), "Google_Service_AdSenseHost_Report");
  }
}

/**
 * The "adclients" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $adclients = $adsensehostService->adclients;
 *  </code>
 */
class Google_Service_AdSenseHost_Adclients_Resource extends Google_Service_Resource
{

  /**
   * Get information about one of the ad clients in the Host AdSense account.
   * (adclients.get)
   *
   * @param string $adClientId Ad client to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AdClient
   */
  public function get($adClientId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdSenseHost_AdClient");
  }

  /**
   * List all host ad clients in this AdSense account. (adclients.listAdclients)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A continuation token, used to page through ad
   * clients. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response.
   * @opt_param string maxResults The maximum number of ad clients to include in
   * the response, used for paging.
   * @return Google_Service_AdSenseHost_AdClients
   */
  public function listAdclients($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_AdClients");
  }
}

/**
 * The "associationsessions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $associationsessions = $adsensehostService->associationsessions;
 *  </code>
 */
class Google_Service_AdSenseHost_Associationsessions_Resource extends Google_Service_Resource
{

  /**
   * Create an association session for initiating an association with an AdSense
   * user. (associationsessions.start)
   *
   * @param string $productCode Products to associate with the user.
   * @param string $websiteUrl The URL of the user's hosted website.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string websiteLocale The locale of the user's hosted website.
   * @opt_param string userLocale The preferred locale of the user.
   * @return Google_Service_AdSenseHost_AssociationSession
   */
  public function start($productCode, $websiteUrl, $optParams = array())
  {
    $params = array('productCode' => $productCode, 'websiteUrl' => $websiteUrl);
    $params = array_merge($params, $optParams);
    return $this->call('start', array($params), "Google_Service_AdSenseHost_AssociationSession");
  }

  /**
   * Verify an association session after the association callback returns from
   * AdSense signup. (associationsessions.verify)
   *
   * @param string $token The token returned to the association callback URL.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_AssociationSession
   */
  public function verify($token, $optParams = array())
  {
    $params = array('token' => $token);
    $params = array_merge($params, $optParams);
    return $this->call('verify', array($params), "Google_Service_AdSenseHost_AssociationSession");
  }
}

/**
 * The "customchannels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $customchannels = $adsensehostService->customchannels;
 *  </code>
 */
class Google_Service_AdSenseHost_Customchannels_Resource extends Google_Service_Resource
{

  /**
   * Delete a specific custom channel from the host AdSense account.
   * (customchannels.delete)
   *
   * @param string $adClientId Ad client from which to delete the custom channel.
   * @param string $customChannelId Custom channel to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_CustomChannel
   */
  public function delete($adClientId, $customChannelId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'customChannelId' => $customChannelId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AdSenseHost_CustomChannel");
  }

  /**
   * Get a specific custom channel from the host AdSense account.
   * (customchannels.get)
   *
   * @param string $adClientId Ad client from which to get the custom channel.
   * @param string $customChannelId Custom channel to get.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_CustomChannel
   */
  public function get($adClientId, $customChannelId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'customChannelId' => $customChannelId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdSenseHost_CustomChannel");
  }

  /**
   * Add a new custom channel to the host AdSense account. (customchannels.insert)
   *
   * @param string $adClientId Ad client to which the new custom channel will be
   * added.
   * @param Google_CustomChannel $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_CustomChannel
   */
  public function insert($adClientId, Google_Service_AdSenseHost_CustomChannel $postBody, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdSenseHost_CustomChannel");
  }

  /**
   * List all host custom channels in this AdSense account.
   * (customchannels.listCustomchannels)
   *
   * @param string $adClientId Ad client for which to list custom channels.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A continuation token, used to page through custom
   * channels. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response.
   * @opt_param string maxResults The maximum number of custom channels to include
   * in the response, used for paging.
   * @return Google_Service_AdSenseHost_CustomChannels
   */
  public function listCustomchannels($adClientId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_CustomChannels");
  }

  /**
   * Update a custom channel in the host AdSense account. This method supports
   * patch semantics. (customchannels.patch)
   *
   * @param string $adClientId Ad client in which the custom channel will be
   * updated.
   * @param string $customChannelId Custom channel to get.
   * @param Google_CustomChannel $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_CustomChannel
   */
  public function patch($adClientId, $customChannelId, Google_Service_AdSenseHost_CustomChannel $postBody, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'customChannelId' => $customChannelId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdSenseHost_CustomChannel");
  }

  /**
   * Update a custom channel in the host AdSense account. (customchannels.update)
   *
   * @param string $adClientId Ad client in which the custom channel will be
   * updated.
   * @param Google_CustomChannel $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_CustomChannel
   */
  public function update($adClientId, Google_Service_AdSenseHost_CustomChannel $postBody, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdSenseHost_CustomChannel");
  }
}

/**
 * The "reports" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $reports = $adsensehostService->reports;
 *  </code>
 */
class Google_Service_AdSenseHost_Reports_Resource extends Google_Service_Resource
{

  /**
   * Generate an AdSense report based on the report request sent in the query
   * parameters. Returns the result as JSON; to retrieve output in CSV format
   * specify "alt=csv" as a query parameter. (reports.generate)
   *
   * @param string $startDate Start of the date range to report on in "YYYY-MM-DD"
   * format, inclusive.
   * @param string $endDate End of the date range to report on in "YYYY-MM-DD"
   * format, inclusive.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string sort The name of a dimension or metric to sort the
   * resulting report on, optionally prefixed with "+" to sort ascending or "-" to
   * sort descending. If no prefix is specified, the column is sorted ascending.
   * @opt_param string locale Optional locale to use for translating report output
   * to a local language. Defaults to "en_US" if not specified.
   * @opt_param string metric Numeric columns to include in the report.
   * @opt_param string maxResults The maximum number of rows of report data to
   * return.
   * @opt_param string filter Filters to be run on the report.
   * @opt_param string startIndex Index of the first row of report data to return.
   * @opt_param string dimension Dimensions to base the report on.
   * @return Google_Service_AdSenseHost_Report
   */
  public function generate($startDate, $endDate, $optParams = array())
  {
    $params = array('startDate' => $startDate, 'endDate' => $endDate);
    $params = array_merge($params, $optParams);
    return $this->call('generate', array($params), "Google_Service_AdSenseHost_Report");
  }
}

/**
 * The "urlchannels" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adsensehostService = new Google_Service_AdSenseHost(...);
 *   $urlchannels = $adsensehostService->urlchannels;
 *  </code>
 */
class Google_Service_AdSenseHost_Urlchannels_Resource extends Google_Service_Resource
{

  /**
   * Delete a URL channel from the host AdSense account. (urlchannels.delete)
   *
   * @param string $adClientId Ad client from which to delete the URL channel.
   * @param string $urlChannelId URL channel to delete.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_UrlChannel
   */
  public function delete($adClientId, $urlChannelId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'urlChannelId' => $urlChannelId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AdSenseHost_UrlChannel");
  }

  /**
   * Add a new URL channel to the host AdSense account. (urlchannels.insert)
   *
   * @param string $adClientId Ad client to which the new URL channel will be
   * added.
   * @param Google_UrlChannel $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdSenseHost_UrlChannel
   */
  public function insert($adClientId, Google_Service_AdSenseHost_UrlChannel $postBody, $optParams = array())
  {
    $params = array('adClientId' => $adClientId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdSenseHost_UrlChannel");
  }

  /**
   * List all host URL channels in the host AdSense account.
   * (urlchannels.listUrlchannels)
   *
   * @param string $adClientId Ad client for which to list URL channels.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A continuation token, used to page through URL
   * channels. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response.
   * @opt_param string maxResults The maximum number of URL channels to include in
   * the response, used for paging.
   * @return Google_Service_AdSenseHost_UrlChannels
   */
  public function listUrlchannels($adClientId, $optParams = array())
  {
    $params = array('adClientId' => $adClientId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdSenseHost_UrlChannels");
  }
}




class Google_Service_AdSenseHost_Account extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $name;
  public $status;


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
}

class Google_Service_AdSenseHost_Accounts extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_AdSenseHost_Account';
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

class Google_Service_AdSenseHost_AdClient extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $arcOptIn;
  public $id;
  public $kind;
  public $productCode;
  public $supportsReporting;


  public function setArcOptIn($arcOptIn)
  {
    $this->arcOptIn = $arcOptIn;
  }
  public function getArcOptIn()
  {
    return $this->arcOptIn;
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
  public function setProductCode($productCode)
  {
    $this->productCode = $productCode;
  }
  public function getProductCode()
  {
    return $this->productCode;
  }
  public function setSupportsReporting($supportsReporting)
  {
    $this->supportsReporting = $supportsReporting;
  }
  public function getSupportsReporting()
  {
    return $this->supportsReporting;
  }
}

class Google_Service_AdSenseHost_AdClients extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_AdSenseHost_AdClient';
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

class Google_Service_AdSenseHost_AdCode extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $adCode;
  public $kind;


  public function setAdCode($adCode)
  {
    $this->adCode = $adCode;
  }
  public function getAdCode()
  {
    return $this->adCode;
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

class Google_Service_AdSenseHost_AdStyle extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $colorsType = 'Google_Service_AdSenseHost_AdStyleColors';
  protected $colorsDataType = '';
  public $corners;
  protected $fontType = 'Google_Service_AdSenseHost_AdStyleFont';
  protected $fontDataType = '';
  public $kind;


  public function setColors(Google_Service_AdSenseHost_AdStyleColors $colors)
  {
    $this->colors = $colors;
  }
  public function getColors()
  {
    return $this->colors;
  }
  public function setCorners($corners)
  {
    $this->corners = $corners;
  }
  public function getCorners()
  {
    return $this->corners;
  }
  public function setFont(Google_Service_AdSenseHost_AdStyleFont $font)
  {
    $this->font = $font;
  }
  public function getFont()
  {
    return $this->font;
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

class Google_Service_AdSenseHost_AdStyleColors extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $background;
  public $border;
  public $text;
  public $title;
  public $url;


  public function setBackground($background)
  {
    $this->background = $background;
  }
  public function getBackground()
  {
    return $this->background;
  }
  public function setBorder($border)
  {
    $this->border = $border;
  }
  public function getBorder()
  {
    return $this->border;
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
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
  }
}

class Google_Service_AdSenseHost_AdStyleFont extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $family;
  public $size;


  public function setFamily($family)
  {
    $this->family = $family;
  }
  public function getFamily()
  {
    return $this->family;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
}

class Google_Service_AdSenseHost_AdUnit extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  protected $contentAdsSettingsType = 'Google_Service_AdSenseHost_AdUnitContentAdsSettings';
  protected $contentAdsSettingsDataType = '';
  protected $customStyleType = 'Google_Service_AdSenseHost_AdStyle';
  protected $customStyleDataType = '';
  public $id;
  public $kind;
  protected $mobileContentAdsSettingsType = 'Google_Service_AdSenseHost_AdUnitMobileContentAdsSettings';
  protected $mobileContentAdsSettingsDataType = '';
  public $name;
  public $status;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
  }
  public function setContentAdsSettings(Google_Service_AdSenseHost_AdUnitContentAdsSettings $contentAdsSettings)
  {
    $this->contentAdsSettings = $contentAdsSettings;
  }
  public function getContentAdsSettings()
  {
    return $this->contentAdsSettings;
  }
  public function setCustomStyle(Google_Service_AdSenseHost_AdStyle $customStyle)
  {
    $this->customStyle = $customStyle;
  }
  public function getCustomStyle()
  {
    return $this->customStyle;
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
  public function setMobileContentAdsSettings(Google_Service_AdSenseHost_AdUnitMobileContentAdsSettings $mobileContentAdsSettings)
  {
    $this->mobileContentAdsSettings = $mobileContentAdsSettings;
  }
  public function getMobileContentAdsSettings()
  {
    return $this->mobileContentAdsSettings;
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
}

class Google_Service_AdSenseHost_AdUnitContentAdsSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $backupOptionType = 'Google_Service_AdSenseHost_AdUnitContentAdsSettingsBackupOption';
  protected $backupOptionDataType = '';
  public $size;
  public $type;


  public function setBackupOption(Google_Service_AdSenseHost_AdUnitContentAdsSettingsBackupOption $backupOption)
  {
    $this->backupOption = $backupOption;
  }
  public function getBackupOption()
  {
    return $this->backupOption;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
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

class Google_Service_AdSenseHost_AdUnitContentAdsSettingsBackupOption extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $color;
  public $type;
  public $url;


  public function setColor($color)
  {
    $this->color = $color;
  }
  public function getColor()
  {
    return $this->color;
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
}

class Google_Service_AdSenseHost_AdUnitMobileContentAdsSettings extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $markupLanguage;
  public $scriptingLanguage;
  public $size;
  public $type;


  public function setMarkupLanguage($markupLanguage)
  {
    $this->markupLanguage = $markupLanguage;
  }
  public function getMarkupLanguage()
  {
    return $this->markupLanguage;
  }
  public function setScriptingLanguage($scriptingLanguage)
  {
    $this->scriptingLanguage = $scriptingLanguage;
  }
  public function getScriptingLanguage()
  {
    return $this->scriptingLanguage;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
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

class Google_Service_AdSenseHost_AdUnits extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_AdSenseHost_AdUnit';
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

class Google_Service_AdSenseHost_AssociationSession extends Google_Collection
{
  protected $collection_key = 'productCodes';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $id;
  public $kind;
  public $productCodes;
  public $redirectUrl;
  public $status;
  public $userLocale;
  public $websiteLocale;
  public $websiteUrl;


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
  public function setProductCodes($productCodes)
  {
    $this->productCodes = $productCodes;
  }
  public function getProductCodes()
  {
    return $this->productCodes;
  }
  public function setRedirectUrl($redirectUrl)
  {
    $this->redirectUrl = $redirectUrl;
  }
  public function getRedirectUrl()
  {
    return $this->redirectUrl;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setUserLocale($userLocale)
  {
    $this->userLocale = $userLocale;
  }
  public function getUserLocale()
  {
    return $this->userLocale;
  }
  public function setWebsiteLocale($websiteLocale)
  {
    $this->websiteLocale = $websiteLocale;
  }
  public function getWebsiteLocale()
  {
    return $this->websiteLocale;
  }
  public function setWebsiteUrl($websiteUrl)
  {
    $this->websiteUrl = $websiteUrl;
  }
  public function getWebsiteUrl()
  {
    return $this->websiteUrl;
  }
}

class Google_Service_AdSenseHost_CustomChannel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $code;
  public $id;
  public $kind;
  public $name;


  public function setCode($code)
  {
    $this->code = $code;
  }
  public function getCode()
  {
    return $this->code;
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

class Google_Service_AdSenseHost_CustomChannels extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_AdSenseHost_CustomChannel';
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

class Google_Service_AdSenseHost_Report extends Google_Collection
{
  protected $collection_key = 'warnings';
  protected $internal_gapi_mappings = array(
  );
  public $averages;
  protected $headersType = 'Google_Service_AdSenseHost_ReportHeaders';
  protected $headersDataType = 'array';
  public $kind;
  public $rows;
  public $totalMatchedRows;
  public $totals;
  public $warnings;


  public function setAverages($averages)
  {
    $this->averages = $averages;
  }
  public function getAverages()
  {
    return $this->averages;
  }
  public function setHeaders($headers)
  {
    $this->headers = $headers;
  }
  public function getHeaders()
  {
    return $this->headers;
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
  public function setTotalMatchedRows($totalMatchedRows)
  {
    $this->totalMatchedRows = $totalMatchedRows;
  }
  public function getTotalMatchedRows()
  {
    return $this->totalMatchedRows;
  }
  public function setTotals($totals)
  {
    $this->totals = $totals;
  }
  public function getTotals()
  {
    return $this->totals;
  }
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  public function getWarnings()
  {
    return $this->warnings;
  }
}

class Google_Service_AdSenseHost_ReportHeaders extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $currency;
  public $name;
  public $type;


  public function setCurrency($currency)
  {
    $this->currency = $currency;
  }
  public function getCurrency()
  {
    return $this->currency;
  }
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

class Google_Service_AdSenseHost_UrlChannel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $kind;
  public $urlPattern;


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
  public function setUrlPattern($urlPattern)
  {
    $this->urlPattern = $urlPattern;
  }
  public function getUrlPattern()
  {
    return $this->urlPattern;
  }
}

class Google_Service_AdSenseHost_UrlChannels extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_AdSenseHost_UrlChannel';
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
