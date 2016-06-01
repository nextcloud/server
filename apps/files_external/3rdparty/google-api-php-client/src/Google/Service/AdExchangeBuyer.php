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
 * Service definition for AdExchangeBuyer (v1.4).
 *
 * <p>
 * Accesses your bidding-account information, submits creatives for validation,
 * finds available direct deals, and retrieves performance reports.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/ad-exchange/buyer-rest" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_AdExchangeBuyer extends Google_Service
{
  /** Manage your Ad Exchange buyer account configuration. */
  const ADEXCHANGE_BUYER =
      "https://www.googleapis.com/auth/adexchange.buyer";

  public $accounts;
  public $billingInfo;
  public $budget;
  public $clientaccess;
  public $creatives;
  public $deals;
  public $marketplacedeals;
  public $marketplacenotes;
  public $marketplaceoffers;
  public $marketplaceorders;
  public $negotiationrounds;
  public $negotiations;
  public $offers;
  public $performanceReport;
  public $pretargetingConfig;
  

  /**
   * Constructs the internal representation of the AdExchangeBuyer service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'adexchangebuyer/v1.4/';
    $this->version = 'v1.4';
    $this->serviceName = 'adexchangebuyer';

    $this->accounts = new Google_Service_AdExchangeBuyer_Accounts_Resource(
        $this,
        $this->serviceName,
        'accounts',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'accounts/{id}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'accounts',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),'patch' => array(
              'path' => 'accounts/{id}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'accounts/{id}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'id' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->billingInfo = new Google_Service_AdExchangeBuyer_BillingInfo_Resource(
        $this,
        $this->serviceName,
        'billingInfo',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'billinginfo/{accountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'billinginfo',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->budget = new Google_Service_AdExchangeBuyer_Budget_Resource(
        $this,
        $this->serviceName,
        'budget',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'billinginfo/{accountId}/{billingId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'billingId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'billinginfo/{accountId}/{billingId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'billingId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'billinginfo/{accountId}/{billingId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'billingId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->clientaccess = new Google_Service_AdExchangeBuyer_Clientaccess_Resource(
        $this,
        $this->serviceName,
        'clientaccess',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'clientAccess/{clientAccountId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sponsorAccountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'clientAccess/{clientAccountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sponsorAccountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'clientAccess',
              'httpMethod' => 'POST',
              'parameters' => array(
                'clientAccountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'sponsorAccountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'list' => array(
              'path' => 'clientAccess',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),'patch' => array(
              'path' => 'clientAccess/{clientAccountId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sponsorAccountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'clientAccess/{clientAccountId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'clientAccountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'sponsorAccountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->creatives = new Google_Service_AdExchangeBuyer_Creatives_Resource(
        $this,
        $this->serviceName,
        'creatives',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'creatives/{accountId}/{buyerCreativeId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'integer',
                  'required' => true,
                ),
                'buyerCreativeId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'creatives',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'creatives',
              'httpMethod' => 'GET',
              'parameters' => array(
                'openAuctionStatusFilter' => array(
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
                'buyerCreativeId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'dealsStatusFilter' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'accountId' => array(
                  'location' => 'query',
                  'type' => 'integer',
                  'repeated' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->deals = new Google_Service_AdExchangeBuyer_Deals_Resource(
        $this,
        $this->serviceName,
        'deals',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'deals/{dealId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'dealId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->marketplacedeals = new Google_Service_AdExchangeBuyer_Marketplacedeals_Resource(
        $this,
        $this->serviceName,
        'marketplacedeals',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'marketplaceOrders/{orderId}/deals/delete',
              'httpMethod' => 'POST',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'marketplaceOrders/{orderId}/deals/insert',
              'httpMethod' => 'POST',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'marketplaceOrders/{orderId}/deals',
              'httpMethod' => 'GET',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'marketplaceOrders/{orderId}/deals/update',
              'httpMethod' => 'POST',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->marketplacenotes = new Google_Service_AdExchangeBuyer_Marketplacenotes_Resource(
        $this,
        $this->serviceName,
        'marketplacenotes',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'marketplaceOrders/{orderId}/notes/insert',
              'httpMethod' => 'POST',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'marketplaceOrders/{orderId}/notes',
              'httpMethod' => 'GET',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->marketplaceoffers = new Google_Service_AdExchangeBuyer_Marketplaceoffers_Resource(
        $this,
        $this->serviceName,
        'marketplaceoffers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'marketplaceOffers/{offerId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'offerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'search' => array(
              'path' => 'marketplaceOffers/search',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pqlQuery' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
    $this->marketplaceorders = new Google_Service_AdExchangeBuyer_Marketplaceorders_Resource(
        $this,
        $this->serviceName,
        'marketplaceorders',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'marketplaceOrders/{orderId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'marketplaceOrders/insert',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'patch' => array(
              'path' => 'marketplaceOrders/{orderId}/{revisionNumber}/{updateAction}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'revisionNumber' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'updateAction' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'search' => array(
              'path' => 'marketplaceOrders/search',
              'httpMethod' => 'GET',
              'parameters' => array(
                'pqlQuery' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),'update' => array(
              'path' => 'marketplaceOrders/{orderId}/{revisionNumber}/{updateAction}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'orderId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'revisionNumber' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'updateAction' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->negotiationrounds = new Google_Service_AdExchangeBuyer_Negotiationrounds_Resource(
        $this,
        $this->serviceName,
        'negotiationrounds',
        array(
          'methods' => array(
            'insert' => array(
              'path' => 'negotiations/{negotiationId}/negotiationrounds',
              'httpMethod' => 'POST',
              'parameters' => array(
                'negotiationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),
          )
        )
    );
    $this->negotiations = new Google_Service_AdExchangeBuyer_Negotiations_Resource(
        $this,
        $this->serviceName,
        'negotiations',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'negotiations/{negotiationId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'negotiationId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'negotiations',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'negotiations',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->offers = new Google_Service_AdExchangeBuyer_Offers_Resource(
        $this,
        $this->serviceName,
        'offers',
        array(
          'methods' => array(
            'get' => array(
              'path' => 'offers/{offerId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'offerId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'offers',
              'httpMethod' => 'POST',
              'parameters' => array(),
            ),'list' => array(
              'path' => 'offers',
              'httpMethod' => 'GET',
              'parameters' => array(),
            ),
          )
        )
    );
    $this->performanceReport = new Google_Service_AdExchangeBuyer_PerformanceReport_Resource(
        $this,
        $this->serviceName,
        'performanceReport',
        array(
          'methods' => array(
            'list' => array(
              'path' => 'performancereport',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'endDateTime' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'required' => true,
                ),
                'startDateTime' => array(
                  'location' => 'query',
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
    $this->pretargetingConfig = new Google_Service_AdExchangeBuyer_PretargetingConfig_Resource(
        $this,
        $this->serviceName,
        'pretargetingConfig',
        array(
          'methods' => array(
            'delete' => array(
              'path' => 'pretargetingconfigs/{accountId}/{configId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'configId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => 'pretargetingconfigs/{accountId}/{configId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'configId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => 'pretargetingconfigs/{accountId}',
              'httpMethod' => 'POST',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'list' => array(
              'path' => 'pretargetingconfigs/{accountId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'patch' => array(
              'path' => 'pretargetingconfigs/{accountId}/{configId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'configId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => 'pretargetingconfigs/{accountId}/{configId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'accountId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'configId' => array(
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
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $accounts = $adexchangebuyerService->accounts;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Accounts_Resource extends Google_Service_Resource
{

  /**
   * Gets one account by ID. (accounts.get)
   *
   * @param int $id The account id
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Account
   */
  public function get($id, $optParams = array())
  {
    $params = array('id' => $id);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_Account");
  }

  /**
   * Retrieves the authenticated user's list of accounts. (accounts.listAccounts)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_AccountsList
   */
  public function listAccounts($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_AccountsList");
  }

  /**
   * Updates an existing account. This method supports patch semantics.
   * (accounts.patch)
   *
   * @param int $id The account id
   * @param Google_Account $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Account
   */
  public function patch($id, Google_Service_AdExchangeBuyer_Account $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdExchangeBuyer_Account");
  }

  /**
   * Updates an existing account. (accounts.update)
   *
   * @param int $id The account id
   * @param Google_Account $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Account
   */
  public function update($id, Google_Service_AdExchangeBuyer_Account $postBody, $optParams = array())
  {
    $params = array('id' => $id, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_Account");
  }
}

/**
 * The "billingInfo" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $billingInfo = $adexchangebuyerService->billingInfo;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_BillingInfo_Resource extends Google_Service_Resource
{

  /**
   * Returns the billing information for one account specified by account ID.
   * (billingInfo.get)
   *
   * @param int $accountId The account id.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_BillingInfo
   */
  public function get($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_BillingInfo");
  }

  /**
   * Retrieves a list of billing information for all accounts of the authenticated
   * user. (billingInfo.listBillingInfo)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_BillingInfoList
   */
  public function listBillingInfo($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_BillingInfoList");
  }
}

/**
 * The "budget" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $budget = $adexchangebuyerService->budget;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Budget_Resource extends Google_Service_Resource
{

  /**
   * Returns the budget information for the adgroup specified by the accountId and
   * billingId. (budget.get)
   *
   * @param string $accountId The account id to get the budget information for.
   * @param string $billingId The billing id to get the budget information for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Budget
   */
  public function get($accountId, $billingId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'billingId' => $billingId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_Budget");
  }

  /**
   * Updates the budget amount for the budget of the adgroup specified by the
   * accountId and billingId, with the budget amount in the request. This method
   * supports patch semantics. (budget.patch)
   *
   * @param string $accountId The account id associated with the budget being
   * updated.
   * @param string $billingId The billing id associated with the budget being
   * updated.
   * @param Google_Budget $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Budget
   */
  public function patch($accountId, $billingId, Google_Service_AdExchangeBuyer_Budget $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'billingId' => $billingId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdExchangeBuyer_Budget");
  }

  /**
   * Updates the budget amount for the budget of the adgroup specified by the
   * accountId and billingId, with the budget amount in the request.
   * (budget.update)
   *
   * @param string $accountId The account id associated with the budget being
   * updated.
   * @param string $billingId The billing id associated with the budget being
   * updated.
   * @param Google_Budget $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Budget
   */
  public function update($accountId, $billingId, Google_Service_AdExchangeBuyer_Budget $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'billingId' => $billingId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_Budget");
  }
}

/**
 * The "clientaccess" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $clientaccess = $adexchangebuyerService->clientaccess;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Clientaccess_Resource extends Google_Service_Resource
{

  /**
   * (clientaccess.delete)
   *
   * @param string $clientAccountId
   * @param int $sponsorAccountId
   * @param array $optParams Optional parameters.
   */
  public function delete($clientAccountId, $sponsorAccountId, $optParams = array())
  {
    $params = array('clientAccountId' => $clientAccountId, 'sponsorAccountId' => $sponsorAccountId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * (clientaccess.get)
   *
   * @param string $clientAccountId
   * @param int $sponsorAccountId
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_ClientAccessCapabilities
   */
  public function get($clientAccountId, $sponsorAccountId, $optParams = array())
  {
    $params = array('clientAccountId' => $clientAccountId, 'sponsorAccountId' => $sponsorAccountId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_ClientAccessCapabilities");
  }

  /**
   * (clientaccess.insert)
   *
   * @param Google_ClientAccessCapabilities $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string clientAccountId
   * @opt_param int sponsorAccountId
   * @return Google_Service_AdExchangeBuyer_ClientAccessCapabilities
   */
  public function insert(Google_Service_AdExchangeBuyer_ClientAccessCapabilities $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_ClientAccessCapabilities");
  }

  /**
   * (clientaccess.listClientaccess)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_ListClientAccessCapabilitiesResponse
   */
  public function listClientaccess($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_ListClientAccessCapabilitiesResponse");
  }

  /**
   * (clientaccess.patch)
   *
   * @param string $clientAccountId
   * @param int $sponsorAccountId
   * @param Google_ClientAccessCapabilities $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_ClientAccessCapabilities
   */
  public function patch($clientAccountId, $sponsorAccountId, Google_Service_AdExchangeBuyer_ClientAccessCapabilities $postBody, $optParams = array())
  {
    $params = array('clientAccountId' => $clientAccountId, 'sponsorAccountId' => $sponsorAccountId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdExchangeBuyer_ClientAccessCapabilities");
  }

  /**
   * (clientaccess.update)
   *
   * @param string $clientAccountId
   * @param int $sponsorAccountId
   * @param Google_ClientAccessCapabilities $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_ClientAccessCapabilities
   */
  public function update($clientAccountId, $sponsorAccountId, Google_Service_AdExchangeBuyer_ClientAccessCapabilities $postBody, $optParams = array())
  {
    $params = array('clientAccountId' => $clientAccountId, 'sponsorAccountId' => $sponsorAccountId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_ClientAccessCapabilities");
  }
}

/**
 * The "creatives" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $creatives = $adexchangebuyerService->creatives;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Creatives_Resource extends Google_Service_Resource
{

  /**
   * Gets the status for a single creative. A creative will be available 30-40
   * minutes after submission. (creatives.get)
   *
   * @param int $accountId The id for the account that will serve this creative.
   * @param string $buyerCreativeId The buyer-specific id for this creative.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Creative
   */
  public function get($accountId, $buyerCreativeId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'buyerCreativeId' => $buyerCreativeId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_Creative");
  }

  /**
   * Submit a new creative. (creatives.insert)
   *
   * @param Google_Creative $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_Creative
   */
  public function insert(Google_Service_AdExchangeBuyer_Creative $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_Creative");
  }

  /**
   * Retrieves a list of the authenticated user's active creatives. A creative
   * will be available 30-40 minutes after submission. (creatives.listCreatives)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string openAuctionStatusFilter When specified, only creatives
   * having the given open auction status are returned.
   * @opt_param string maxResults Maximum number of entries returned on one result
   * page. If not set, the default is 100. Optional.
   * @opt_param string pageToken A continuation token, used to page through ad
   * clients. To retrieve the next page, set this parameter to the value of
   * "nextPageToken" from the previous response. Optional.
   * @opt_param string buyerCreativeId When specified, only creatives for the
   * given buyer creative ids are returned.
   * @opt_param string dealsStatusFilter When specified, only creatives having the
   * given direct deals status are returned.
   * @opt_param int accountId When specified, only creatives for the given account
   * ids are returned.
   * @return Google_Service_AdExchangeBuyer_CreativesList
   */
  public function listCreatives($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_CreativesList");
  }
}

/**
 * The "deals" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $deals = $adexchangebuyerService->deals;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Deals_Resource extends Google_Service_Resource
{

  /**
   * Gets the requested deal. (deals.get)
   *
   * @param string $dealId
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_NegotiationDto
   */
  public function get($dealId, $optParams = array())
  {
    $params = array('dealId' => $dealId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_NegotiationDto");
  }
}

/**
 * The "marketplacedeals" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $marketplacedeals = $adexchangebuyerService->marketplacedeals;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Marketplacedeals_Resource extends Google_Service_Resource
{

  /**
   * Delete the specified deals from the order (marketplacedeals.delete)
   *
   * @param string $orderId The orderId to delete deals from.
   * @param Google_DeleteOrderDealsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_DeleteOrderDealsResponse
   */
  public function delete($orderId, Google_Service_AdExchangeBuyer_DeleteOrderDealsRequest $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params), "Google_Service_AdExchangeBuyer_DeleteOrderDealsResponse");
  }

  /**
   * Add new deals for the specified order (marketplacedeals.insert)
   *
   * @param string $orderId OrderId for which deals need to be added.
   * @param Google_AddOrderDealsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_AddOrderDealsResponse
   */
  public function insert($orderId, Google_Service_AdExchangeBuyer_AddOrderDealsRequest $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_AddOrderDealsResponse");
  }

  /**
   * List all the deals for a given order (marketplacedeals.listMarketplacedeals)
   *
   * @param string $orderId The orderId to get deals for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_GetOrderDealsResponse
   */
  public function listMarketplacedeals($orderId, $optParams = array())
  {
    $params = array('orderId' => $orderId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_GetOrderDealsResponse");
  }

  /**
   * Replaces all the deals in the order with the passed in deals
   * (marketplacedeals.update)
   *
   * @param string $orderId The orderId to edit deals on.
   * @param Google_EditAllOrderDealsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_EditAllOrderDealsResponse
   */
  public function update($orderId, Google_Service_AdExchangeBuyer_EditAllOrderDealsRequest $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_EditAllOrderDealsResponse");
  }
}

/**
 * The "marketplacenotes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $marketplacenotes = $adexchangebuyerService->marketplacenotes;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Marketplacenotes_Resource extends Google_Service_Resource
{

  /**
   * Add notes to the order (marketplacenotes.insert)
   *
   * @param string $orderId The orderId to add notes for.
   * @param Google_AddOrderNotesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_AddOrderNotesResponse
   */
  public function insert($orderId, Google_Service_AdExchangeBuyer_AddOrderNotesRequest $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_AddOrderNotesResponse");
  }

  /**
   * Get all the notes associated with an order
   * (marketplacenotes.listMarketplacenotes)
   *
   * @param string $orderId The orderId to get notes for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_GetOrderNotesResponse
   */
  public function listMarketplacenotes($orderId, $optParams = array())
  {
    $params = array('orderId' => $orderId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_GetOrderNotesResponse");
  }
}

/**
 * The "marketplaceoffers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $marketplaceoffers = $adexchangebuyerService->marketplaceoffers;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Marketplaceoffers_Resource extends Google_Service_Resource
{

  /**
   * Gets the requested negotiation. (marketplaceoffers.get)
   *
   * @param string $offerId The offerId for the offer to get the head revision
   * for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_MarketplaceOffer
   */
  public function get($offerId, $optParams = array())
  {
    $params = array('offerId' => $offerId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_MarketplaceOffer");
  }

  /**
   * Gets the requested negotiation. (marketplaceoffers.search)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pqlQuery The pql query used to query for offers.
   * @return Google_Service_AdExchangeBuyer_GetOffersResponse
   */
  public function search($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('search', array($params), "Google_Service_AdExchangeBuyer_GetOffersResponse");
  }
}

/**
 * The "marketplaceorders" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $marketplaceorders = $adexchangebuyerService->marketplaceorders;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Marketplaceorders_Resource extends Google_Service_Resource
{

  /**
   * Get an order given its id (marketplaceorders.get)
   *
   * @param string $orderId Id of the order to retrieve.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_MarketplaceOrder
   */
  public function get($orderId, $optParams = array())
  {
    $params = array('orderId' => $orderId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_MarketplaceOrder");
  }

  /**
   * Create the given list of orders (marketplaceorders.insert)
   *
   * @param Google_CreateOrdersRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_CreateOrdersResponse
   */
  public function insert(Google_Service_AdExchangeBuyer_CreateOrdersRequest $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_CreateOrdersResponse");
  }

  /**
   * Update the given order. This method supports patch semantics.
   * (marketplaceorders.patch)
   *
   * @param string $orderId The order id to update.
   * @param string $revisionNumber The last known revision number to update. If
   * the head revision in the marketplace database has since changed, an error
   * will be thrown. The caller should then fetch the lastest order at head
   * revision and retry the update at that revision.
   * @param string $updateAction The proposed action to take on the order.
   * @param Google_MarketplaceOrder $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_MarketplaceOrder
   */
  public function patch($orderId, $revisionNumber, $updateAction, Google_Service_AdExchangeBuyer_MarketplaceOrder $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'revisionNumber' => $revisionNumber, 'updateAction' => $updateAction, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdExchangeBuyer_MarketplaceOrder");
  }

  /**
   * Search for orders using pql query (marketplaceorders.search)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pqlQuery Query string to retrieve specific orders.
   * @return Google_Service_AdExchangeBuyer_GetOrdersResponse
   */
  public function search($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('search', array($params), "Google_Service_AdExchangeBuyer_GetOrdersResponse");
  }

  /**
   * Update the given order (marketplaceorders.update)
   *
   * @param string $orderId The order id to update.
   * @param string $revisionNumber The last known revision number to update. If
   * the head revision in the marketplace database has since changed, an error
   * will be thrown. The caller should then fetch the lastest order at head
   * revision and retry the update at that revision.
   * @param string $updateAction The proposed action to take on the order.
   * @param Google_MarketplaceOrder $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_MarketplaceOrder
   */
  public function update($orderId, $revisionNumber, $updateAction, Google_Service_AdExchangeBuyer_MarketplaceOrder $postBody, $optParams = array())
  {
    $params = array('orderId' => $orderId, 'revisionNumber' => $revisionNumber, 'updateAction' => $updateAction, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_MarketplaceOrder");
  }
}

/**
 * The "negotiationrounds" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $negotiationrounds = $adexchangebuyerService->negotiationrounds;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Negotiationrounds_Resource extends Google_Service_Resource
{

  /**
   * Adds the requested negotiationRound to the requested negotiation.
   * (negotiationrounds.insert)
   *
   * @param string $negotiationId
   * @param Google_NegotiationRoundDto $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_NegotiationRoundDto
   */
  public function insert($negotiationId, Google_Service_AdExchangeBuyer_NegotiationRoundDto $postBody, $optParams = array())
  {
    $params = array('negotiationId' => $negotiationId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_NegotiationRoundDto");
  }
}

/**
 * The "negotiations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $negotiations = $adexchangebuyerService->negotiations;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Negotiations_Resource extends Google_Service_Resource
{

  /**
   * Gets the requested negotiation. (negotiations.get)
   *
   * @param string $negotiationId
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_NegotiationDto
   */
  public function get($negotiationId, $optParams = array())
  {
    $params = array('negotiationId' => $negotiationId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_NegotiationDto");
  }

  /**
   * Creates or updates the requested negotiation. (negotiations.insert)
   *
   * @param Google_NegotiationDto $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_NegotiationDto
   */
  public function insert(Google_Service_AdExchangeBuyer_NegotiationDto $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_NegotiationDto");
  }

  /**
   * Lists all negotiations the authenticated user has access to.
   * (negotiations.listNegotiations)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_GetNegotiationsResponse
   */
  public function listNegotiations($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_GetNegotiationsResponse");
  }
}

/**
 * The "offers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $offers = $adexchangebuyerService->offers;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_Offers_Resource extends Google_Service_Resource
{

  /**
   * Gets the requested offer. (offers.get)
   *
   * @param string $offerId
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_OfferDto
   */
  public function get($offerId, $optParams = array())
  {
    $params = array('offerId' => $offerId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_OfferDto");
  }

  /**
   * Creates or updates the requested offer. (offers.insert)
   *
   * @param Google_OfferDto $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_OfferDto
   */
  public function insert(Google_Service_AdExchangeBuyer_OfferDto $postBody, $optParams = array())
  {
    $params = array('postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_OfferDto");
  }

  /**
   * Lists all offers the authenticated user has access to. (offers.listOffers)
   *
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_ListOffersResponse
   */
  public function listOffers($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_ListOffersResponse");
  }
}

/**
 * The "performanceReport" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $performanceReport = $adexchangebuyerService->performanceReport;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_PerformanceReport_Resource extends Google_Service_Resource
{

  /**
   * Retrieves the authenticated user's list of performance metrics.
   * (performanceReport.listPerformanceReport)
   *
   * @param string $accountId The account id to get the reports.
   * @param string $endDateTime The end time of the report in ISO 8601 timestamp
   * format using UTC.
   * @param string $startDateTime The start time of the report in ISO 8601
   * timestamp format using UTC.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken A continuation token, used to page through
   * performance reports. To retrieve the next page, set this parameter to the
   * value of "nextPageToken" from the previous response. Optional.
   * @opt_param string maxResults Maximum number of entries returned on one result
   * page. If not set, the default is 100. Optional.
   * @return Google_Service_AdExchangeBuyer_PerformanceReportList
   */
  public function listPerformanceReport($accountId, $endDateTime, $startDateTime, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'endDateTime' => $endDateTime, 'startDateTime' => $startDateTime);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_PerformanceReportList");
  }
}

/**
 * The "pretargetingConfig" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyerService = new Google_Service_AdExchangeBuyer(...);
 *   $pretargetingConfig = $adexchangebuyerService->pretargetingConfig;
 *  </code>
 */
class Google_Service_AdExchangeBuyer_PretargetingConfig_Resource extends Google_Service_Resource
{

  /**
   * Deletes an existing pretargeting config. (pretargetingConfig.delete)
   *
   * @param string $accountId The account id to delete the pretargeting config
   * for.
   * @param string $configId The specific id of the configuration to delete.
   * @param array $optParams Optional parameters.
   */
  public function delete($accountId, $configId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'configId' => $configId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Gets a specific pretargeting configuration (pretargetingConfig.get)
   *
   * @param string $accountId The account id to get the pretargeting config for.
   * @param string $configId The specific id of the configuration to retrieve.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_PretargetingConfig
   */
  public function get($accountId, $configId, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'configId' => $configId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_AdExchangeBuyer_PretargetingConfig");
  }

  /**
   * Inserts a new pretargeting configuration. (pretargetingConfig.insert)
   *
   * @param string $accountId The account id to insert the pretargeting config
   * for.
   * @param Google_PretargetingConfig $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_PretargetingConfig
   */
  public function insert($accountId, Google_Service_AdExchangeBuyer_PretargetingConfig $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_AdExchangeBuyer_PretargetingConfig");
  }

  /**
   * Retrieves a list of the authenticated user's pretargeting configurations.
   * (pretargetingConfig.listPretargetingConfig)
   *
   * @param string $accountId The account id to get the pretargeting configs for.
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_PretargetingConfigList
   */
  public function listPretargetingConfig($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyer_PretargetingConfigList");
  }

  /**
   * Updates an existing pretargeting config. This method supports patch
   * semantics. (pretargetingConfig.patch)
   *
   * @param string $accountId The account id to update the pretargeting config
   * for.
   * @param string $configId The specific id of the configuration to update.
   * @param Google_PretargetingConfig $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_PretargetingConfig
   */
  public function patch($accountId, $configId, Google_Service_AdExchangeBuyer_PretargetingConfig $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'configId' => $configId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_AdExchangeBuyer_PretargetingConfig");
  }

  /**
   * Updates an existing pretargeting config. (pretargetingConfig.update)
   *
   * @param string $accountId The account id to update the pretargeting config
   * for.
   * @param string $configId The specific id of the configuration to update.
   * @param Google_PretargetingConfig $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_AdExchangeBuyer_PretargetingConfig
   */
  public function update($accountId, $configId, Google_Service_AdExchangeBuyer_PretargetingConfig $postBody, $optParams = array())
  {
    $params = array('accountId' => $accountId, 'configId' => $configId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_AdExchangeBuyer_PretargetingConfig");
  }
}




class Google_Service_AdExchangeBuyer_Account extends Google_Collection
{
  protected $collection_key = 'bidderLocation';
  protected $internal_gapi_mappings = array(
  );
  protected $bidderLocationType = 'Google_Service_AdExchangeBuyer_AccountBidderLocation';
  protected $bidderLocationDataType = 'array';
  public $cookieMatchingNid;
  public $cookieMatchingUrl;
  public $id;
  public $kind;
  public $maximumActiveCreatives;
  public $maximumTotalQps;
  public $numberActiveCreatives;


  public function setBidderLocation($bidderLocation)
  {
    $this->bidderLocation = $bidderLocation;
  }
  public function getBidderLocation()
  {
    return $this->bidderLocation;
  }
  public function setCookieMatchingNid($cookieMatchingNid)
  {
    $this->cookieMatchingNid = $cookieMatchingNid;
  }
  public function getCookieMatchingNid()
  {
    return $this->cookieMatchingNid;
  }
  public function setCookieMatchingUrl($cookieMatchingUrl)
  {
    $this->cookieMatchingUrl = $cookieMatchingUrl;
  }
  public function getCookieMatchingUrl()
  {
    return $this->cookieMatchingUrl;
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
  public function setMaximumActiveCreatives($maximumActiveCreatives)
  {
    $this->maximumActiveCreatives = $maximumActiveCreatives;
  }
  public function getMaximumActiveCreatives()
  {
    return $this->maximumActiveCreatives;
  }
  public function setMaximumTotalQps($maximumTotalQps)
  {
    $this->maximumTotalQps = $maximumTotalQps;
  }
  public function getMaximumTotalQps()
  {
    return $this->maximumTotalQps;
  }
  public function setNumberActiveCreatives($numberActiveCreatives)
  {
    $this->numberActiveCreatives = $numberActiveCreatives;
  }
  public function getNumberActiveCreatives()
  {
    return $this->numberActiveCreatives;
  }
}

class Google_Service_AdExchangeBuyer_AccountBidderLocation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $maximumQps;
  public $region;
  public $url;


  public function setMaximumQps($maximumQps)
  {
    $this->maximumQps = $maximumQps;
  }
  public function getMaximumQps()
  {
    return $this->maximumQps;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
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

class Google_Service_AdExchangeBuyer_AccountsList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_AdExchangeBuyer_Account';
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

class Google_Service_AdExchangeBuyer_AdSize extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
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

class Google_Service_AdExchangeBuyer_AdSlotDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $channelCode;
  public $channelId;
  public $description;
  public $name;
  public $size;
  public $webPropertyId;


  public function setChannelCode($channelCode)
  {
    $this->channelCode = $channelCode;
  }
  public function getChannelCode()
  {
    return $this->channelCode;
  }
  public function setChannelId($channelId)
  {
    $this->channelId = $channelId;
  }
  public function getChannelId()
  {
    return $this->channelId;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setSize($size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
  public function setWebPropertyId($webPropertyId)
  {
    $this->webPropertyId = $webPropertyId;
  }
  public function getWebPropertyId()
  {
    return $this->webPropertyId;
  }
}

class Google_Service_AdExchangeBuyer_AddOrderDealsRequest extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';
  public $orderRevisionNumber;
  public $updateAction;


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
  }
  public function setUpdateAction($updateAction)
  {
    $this->updateAction = $updateAction;
  }
  public function getUpdateAction()
  {
    return $this->updateAction;
  }
}

class Google_Service_AdExchangeBuyer_AddOrderDealsResponse extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';
  public $orderRevisionNumber;


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
  }
}

class Google_Service_AdExchangeBuyer_AddOrderNotesRequest extends Google_Collection
{
  protected $collection_key = 'notes';
  protected $internal_gapi_mappings = array(
  );
  protected $notesType = 'Google_Service_AdExchangeBuyer_MarketplaceNote';
  protected $notesDataType = 'array';


  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
}

class Google_Service_AdExchangeBuyer_AddOrderNotesResponse extends Google_Collection
{
  protected $collection_key = 'notes';
  protected $internal_gapi_mappings = array(
  );
  protected $notesType = 'Google_Service_AdExchangeBuyer_MarketplaceNote';
  protected $notesDataType = 'array';


  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
}

class Google_Service_AdExchangeBuyer_AdvertiserDto extends Google_Collection
{
  protected $collection_key = 'brands';
  protected $internal_gapi_mappings = array(
  );
  protected $brandsType = 'Google_Service_AdExchangeBuyer_BrandDto';
  protected $brandsDataType = 'array';
  public $id;
  public $name;
  public $status;


  public function setBrands($brands)
  {
    $this->brands = $brands;
  }
  public function getBrands()
  {
    return $this->brands;
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
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
}

class Google_Service_AdExchangeBuyer_AudienceSegment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  public $id;
  public $name;
  public $numCookies;


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
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNumCookies($numCookies)
  {
    $this->numCookies = $numCookies;
  }
  public function getNumCookies()
  {
    return $this->numCookies;
  }
}

class Google_Service_AdExchangeBuyer_BillingInfo extends Google_Collection
{
  protected $collection_key = 'billingId';
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $accountName;
  public $billingId;
  public $kind;


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
  public function setBillingId($billingId)
  {
    $this->billingId = $billingId;
  }
  public function getBillingId()
  {
    return $this->billingId;
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

class Google_Service_AdExchangeBuyer_BillingInfoList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_AdExchangeBuyer_BillingInfo';
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

class Google_Service_AdExchangeBuyer_BrandDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $advertiserId;
  public $id;
  public $name;


  public function setAdvertiserId($advertiserId)
  {
    $this->advertiserId = $advertiserId;
  }
  public function getAdvertiserId()
  {
    return $this->advertiserId;
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

class Google_Service_AdExchangeBuyer_Budget extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $billingId;
  public $budgetAmount;
  public $currencyCode;
  public $id;
  public $kind;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setBillingId($billingId)
  {
    $this->billingId = $billingId;
  }
  public function getBillingId()
  {
    return $this->billingId;
  }
  public function setBudgetAmount($budgetAmount)
  {
    $this->budgetAmount = $budgetAmount;
  }
  public function getBudgetAmount()
  {
    return $this->budgetAmount;
  }
  public function setCurrencyCode($currencyCode)
  {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode()
  {
    return $this->currencyCode;
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

class Google_Service_AdExchangeBuyer_Buyer extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
}

class Google_Service_AdExchangeBuyer_BuyerDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $customerId;
  public $displayName;
  public $enabledForInterestTargetingDeals;
  public $enabledForPreferredDeals;
  public $id;
  public $sponsorAccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setCustomerId($customerId)
  {
    $this->customerId = $customerId;
  }
  public function getCustomerId()
  {
    return $this->customerId;
  }
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  public function getDisplayName()
  {
    return $this->displayName;
  }
  public function setEnabledForInterestTargetingDeals($enabledForInterestTargetingDeals)
  {
    $this->enabledForInterestTargetingDeals = $enabledForInterestTargetingDeals;
  }
  public function getEnabledForInterestTargetingDeals()
  {
    return $this->enabledForInterestTargetingDeals;
  }
  public function setEnabledForPreferredDeals($enabledForPreferredDeals)
  {
    $this->enabledForPreferredDeals = $enabledForPreferredDeals;
  }
  public function getEnabledForPreferredDeals()
  {
    return $this->enabledForPreferredDeals;
  }
  public function setId($id)
  {
    $this->id = $id;
  }
  public function getId()
  {
    return $this->id;
  }
  public function setSponsorAccountId($sponsorAccountId)
  {
    $this->sponsorAccountId = $sponsorAccountId;
  }
  public function getSponsorAccountId()
  {
    return $this->sponsorAccountId;
  }
}

class Google_Service_AdExchangeBuyer_ClientAccessCapabilities extends Google_Collection
{
  protected $collection_key = 'capabilities';
  protected $internal_gapi_mappings = array(
  );
  public $capabilities;
  public $clientAccountId;


  public function setCapabilities($capabilities)
  {
    $this->capabilities = $capabilities;
  }
  public function getCapabilities()
  {
    return $this->capabilities;
  }
  public function setClientAccountId($clientAccountId)
  {
    $this->clientAccountId = $clientAccountId;
  }
  public function getClientAccountId()
  {
    return $this->clientAccountId;
  }
}

class Google_Service_AdExchangeBuyer_ContactInformation extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $email;
  public $name;


  public function setEmail($email)
  {
    $this->email = $email;
  }
  public function getEmail()
  {
    return $this->email;
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

class Google_Service_AdExchangeBuyer_CreateOrdersRequest extends Google_Collection
{
  protected $collection_key = 'orders';
  protected $internal_gapi_mappings = array(
  );
  protected $ordersType = 'Google_Service_AdExchangeBuyer_MarketplaceOrder';
  protected $ordersDataType = 'array';
  public $webPropertyCode;


  public function setOrders($orders)
  {
    $this->orders = $orders;
  }
  public function getOrders()
  {
    return $this->orders;
  }
  public function setWebPropertyCode($webPropertyCode)
  {
    $this->webPropertyCode = $webPropertyCode;
  }
  public function getWebPropertyCode()
  {
    return $this->webPropertyCode;
  }
}

class Google_Service_AdExchangeBuyer_CreateOrdersResponse extends Google_Collection
{
  protected $collection_key = 'orders';
  protected $internal_gapi_mappings = array(
  );
  protected $ordersType = 'Google_Service_AdExchangeBuyer_MarketplaceOrder';
  protected $ordersDataType = 'array';


  public function setOrders($orders)
  {
    $this->orders = $orders;
  }
  public function getOrders()
  {
    return $this->orders;
  }
}

class Google_Service_AdExchangeBuyer_Creative extends Google_Collection
{
  protected $collection_key = 'vendorType';
  protected $internal_gapi_mappings = array(
        "hTMLSnippet" => "HTMLSnippet",
        "apiUploadTimestamp" => "api_upload_timestamp",
  );
  public $hTMLSnippet;
  public $accountId;
  public $advertiserId;
  public $advertiserName;
  public $agencyId;
  public $apiUploadTimestamp;
  public $attribute;
  public $buyerCreativeId;
  public $clickThroughUrl;
  protected $correctionsType = 'Google_Service_AdExchangeBuyer_CreativeCorrections';
  protected $correctionsDataType = 'array';
  public $dealsStatus;
  protected $filteringReasonsType = 'Google_Service_AdExchangeBuyer_CreativeFilteringReasons';
  protected $filteringReasonsDataType = '';
  public $height;
  public $impressionTrackingUrl;
  public $kind;
  protected $nativeAdType = 'Google_Service_AdExchangeBuyer_CreativeNativeAd';
  protected $nativeAdDataType = '';
  public $openAuctionStatus;
  public $productCategories;
  public $restrictedCategories;
  public $sensitiveCategories;
  protected $servingRestrictionsType = 'Google_Service_AdExchangeBuyer_CreativeServingRestrictions';
  protected $servingRestrictionsDataType = 'array';
  public $vendorType;
  public $version;
  public $videoURL;
  public $width;


  public function setHTMLSnippet($hTMLSnippet)
  {
    $this->hTMLSnippet = $hTMLSnippet;
  }
  public function getHTMLSnippet()
  {
    return $this->hTMLSnippet;
  }
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
  public function setAdvertiserName($advertiserName)
  {
    $this->advertiserName = $advertiserName;
  }
  public function getAdvertiserName()
  {
    return $this->advertiserName;
  }
  public function setAgencyId($agencyId)
  {
    $this->agencyId = $agencyId;
  }
  public function getAgencyId()
  {
    return $this->agencyId;
  }
  public function setApiUploadTimestamp($apiUploadTimestamp)
  {
    $this->apiUploadTimestamp = $apiUploadTimestamp;
  }
  public function getApiUploadTimestamp()
  {
    return $this->apiUploadTimestamp;
  }
  public function setAttribute($attribute)
  {
    $this->attribute = $attribute;
  }
  public function getAttribute()
  {
    return $this->attribute;
  }
  public function setBuyerCreativeId($buyerCreativeId)
  {
    $this->buyerCreativeId = $buyerCreativeId;
  }
  public function getBuyerCreativeId()
  {
    return $this->buyerCreativeId;
  }
  public function setClickThroughUrl($clickThroughUrl)
  {
    $this->clickThroughUrl = $clickThroughUrl;
  }
  public function getClickThroughUrl()
  {
    return $this->clickThroughUrl;
  }
  public function setCorrections($corrections)
  {
    $this->corrections = $corrections;
  }
  public function getCorrections()
  {
    return $this->corrections;
  }
  public function setDealsStatus($dealsStatus)
  {
    $this->dealsStatus = $dealsStatus;
  }
  public function getDealsStatus()
  {
    return $this->dealsStatus;
  }
  public function setFilteringReasons(Google_Service_AdExchangeBuyer_CreativeFilteringReasons $filteringReasons)
  {
    $this->filteringReasons = $filteringReasons;
  }
  public function getFilteringReasons()
  {
    return $this->filteringReasons;
  }
  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setImpressionTrackingUrl($impressionTrackingUrl)
  {
    $this->impressionTrackingUrl = $impressionTrackingUrl;
  }
  public function getImpressionTrackingUrl()
  {
    return $this->impressionTrackingUrl;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNativeAd(Google_Service_AdExchangeBuyer_CreativeNativeAd $nativeAd)
  {
    $this->nativeAd = $nativeAd;
  }
  public function getNativeAd()
  {
    return $this->nativeAd;
  }
  public function setOpenAuctionStatus($openAuctionStatus)
  {
    $this->openAuctionStatus = $openAuctionStatus;
  }
  public function getOpenAuctionStatus()
  {
    return $this->openAuctionStatus;
  }
  public function setProductCategories($productCategories)
  {
    $this->productCategories = $productCategories;
  }
  public function getProductCategories()
  {
    return $this->productCategories;
  }
  public function setRestrictedCategories($restrictedCategories)
  {
    $this->restrictedCategories = $restrictedCategories;
  }
  public function getRestrictedCategories()
  {
    return $this->restrictedCategories;
  }
  public function setSensitiveCategories($sensitiveCategories)
  {
    $this->sensitiveCategories = $sensitiveCategories;
  }
  public function getSensitiveCategories()
  {
    return $this->sensitiveCategories;
  }
  public function setServingRestrictions($servingRestrictions)
  {
    $this->servingRestrictions = $servingRestrictions;
  }
  public function getServingRestrictions()
  {
    return $this->servingRestrictions;
  }
  public function setVendorType($vendorType)
  {
    $this->vendorType = $vendorType;
  }
  public function getVendorType()
  {
    return $this->vendorType;
  }
  public function setVersion($version)
  {
    $this->version = $version;
  }
  public function getVersion()
  {
    return $this->version;
  }
  public function setVideoURL($videoURL)
  {
    $this->videoURL = $videoURL;
  }
  public function getVideoURL()
  {
    return $this->videoURL;
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

class Google_Service_AdExchangeBuyer_CreativeCorrections extends Google_Collection
{
  protected $collection_key = 'details';
  protected $internal_gapi_mappings = array(
  );
  public $details;
  public $reason;


  public function setDetails($details)
  {
    $this->details = $details;
  }
  public function getDetails()
  {
    return $this->details;
  }
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
}

class Google_Service_AdExchangeBuyer_CreativeFilteringReasons extends Google_Collection
{
  protected $collection_key = 'reasons';
  protected $internal_gapi_mappings = array(
  );
  public $date;
  protected $reasonsType = 'Google_Service_AdExchangeBuyer_CreativeFilteringReasonsReasons';
  protected $reasonsDataType = 'array';


  public function setDate($date)
  {
    $this->date = $date;
  }
  public function getDate()
  {
    return $this->date;
  }
  public function setReasons($reasons)
  {
    $this->reasons = $reasons;
  }
  public function getReasons()
  {
    return $this->reasons;
  }
}

class Google_Service_AdExchangeBuyer_CreativeFilteringReasonsReasons extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $filteringCount;
  public $filteringStatus;


  public function setFilteringCount($filteringCount)
  {
    $this->filteringCount = $filteringCount;
  }
  public function getFilteringCount()
  {
    return $this->filteringCount;
  }
  public function setFilteringStatus($filteringStatus)
  {
    $this->filteringStatus = $filteringStatus;
  }
  public function getFilteringStatus()
  {
    return $this->filteringStatus;
  }
}

class Google_Service_AdExchangeBuyer_CreativeNativeAd extends Google_Collection
{
  protected $collection_key = 'impressionTrackingUrl';
  protected $internal_gapi_mappings = array(
  );
  public $advertiser;
  protected $appIconType = 'Google_Service_AdExchangeBuyer_CreativeNativeAdAppIcon';
  protected $appIconDataType = '';
  public $body;
  public $callToAction;
  public $clickTrackingUrl;
  public $headline;
  protected $imageType = 'Google_Service_AdExchangeBuyer_CreativeNativeAdImage';
  protected $imageDataType = '';
  public $impressionTrackingUrl;
  protected $logoType = 'Google_Service_AdExchangeBuyer_CreativeNativeAdLogo';
  protected $logoDataType = '';
  public $price;
  public $starRating;
  public $store;


  public function setAdvertiser($advertiser)
  {
    $this->advertiser = $advertiser;
  }
  public function getAdvertiser()
  {
    return $this->advertiser;
  }
  public function setAppIcon(Google_Service_AdExchangeBuyer_CreativeNativeAdAppIcon $appIcon)
  {
    $this->appIcon = $appIcon;
  }
  public function getAppIcon()
  {
    return $this->appIcon;
  }
  public function setBody($body)
  {
    $this->body = $body;
  }
  public function getBody()
  {
    return $this->body;
  }
  public function setCallToAction($callToAction)
  {
    $this->callToAction = $callToAction;
  }
  public function getCallToAction()
  {
    return $this->callToAction;
  }
  public function setClickTrackingUrl($clickTrackingUrl)
  {
    $this->clickTrackingUrl = $clickTrackingUrl;
  }
  public function getClickTrackingUrl()
  {
    return $this->clickTrackingUrl;
  }
  public function setHeadline($headline)
  {
    $this->headline = $headline;
  }
  public function getHeadline()
  {
    return $this->headline;
  }
  public function setImage(Google_Service_AdExchangeBuyer_CreativeNativeAdImage $image)
  {
    $this->image = $image;
  }
  public function getImage()
  {
    return $this->image;
  }
  public function setImpressionTrackingUrl($impressionTrackingUrl)
  {
    $this->impressionTrackingUrl = $impressionTrackingUrl;
  }
  public function getImpressionTrackingUrl()
  {
    return $this->impressionTrackingUrl;
  }
  public function setLogo(Google_Service_AdExchangeBuyer_CreativeNativeAdLogo $logo)
  {
    $this->logo = $logo;
  }
  public function getLogo()
  {
    return $this->logo;
  }
  public function setPrice($price)
  {
    $this->price = $price;
  }
  public function getPrice()
  {
    return $this->price;
  }
  public function setStarRating($starRating)
  {
    $this->starRating = $starRating;
  }
  public function getStarRating()
  {
    return $this->starRating;
  }
  public function setStore($store)
  {
    $this->store = $store;
  }
  public function getStore()
  {
    return $this->store;
  }
}

class Google_Service_AdExchangeBuyer_CreativeNativeAdAppIcon extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $url;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
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

class Google_Service_AdExchangeBuyer_CreativeNativeAdImage extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $url;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
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

class Google_Service_AdExchangeBuyer_CreativeNativeAdLogo extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $url;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
  }
  public function setUrl($url)
  {
    $this->url = $url;
  }
  public function getUrl()
  {
    return $this->url;
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

class Google_Service_AdExchangeBuyer_CreativeServingRestrictions extends Google_Collection
{
  protected $collection_key = 'disapprovalReasons';
  protected $internal_gapi_mappings = array(
  );
  protected $contextsType = 'Google_Service_AdExchangeBuyer_CreativeServingRestrictionsContexts';
  protected $contextsDataType = 'array';
  protected $disapprovalReasonsType = 'Google_Service_AdExchangeBuyer_CreativeServingRestrictionsDisapprovalReasons';
  protected $disapprovalReasonsDataType = 'array';
  public $reason;


  public function setContexts($contexts)
  {
    $this->contexts = $contexts;
  }
  public function getContexts()
  {
    return $this->contexts;
  }
  public function setDisapprovalReasons($disapprovalReasons)
  {
    $this->disapprovalReasons = $disapprovalReasons;
  }
  public function getDisapprovalReasons()
  {
    return $this->disapprovalReasons;
  }
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
}

class Google_Service_AdExchangeBuyer_CreativeServingRestrictionsContexts extends Google_Collection
{
  protected $collection_key = 'platform';
  protected $internal_gapi_mappings = array(
  );
  public $auctionType;
  public $contextType;
  public $geoCriteriaId;
  public $platform;


  public function setAuctionType($auctionType)
  {
    $this->auctionType = $auctionType;
  }
  public function getAuctionType()
  {
    return $this->auctionType;
  }
  public function setContextType($contextType)
  {
    $this->contextType = $contextType;
  }
  public function getContextType()
  {
    return $this->contextType;
  }
  public function setGeoCriteriaId($geoCriteriaId)
  {
    $this->geoCriteriaId = $geoCriteriaId;
  }
  public function getGeoCriteriaId()
  {
    return $this->geoCriteriaId;
  }
  public function setPlatform($platform)
  {
    $this->platform = $platform;
  }
  public function getPlatform()
  {
    return $this->platform;
  }
}

class Google_Service_AdExchangeBuyer_CreativeServingRestrictionsDisapprovalReasons extends Google_Collection
{
  protected $collection_key = 'details';
  protected $internal_gapi_mappings = array(
  );
  public $details;
  public $reason;


  public function setDetails($details)
  {
    $this->details = $details;
  }
  public function getDetails()
  {
    return $this->details;
  }
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  public function getReason()
  {
    return $this->reason;
  }
}

class Google_Service_AdExchangeBuyer_CreativesList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_AdExchangeBuyer_Creative';
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

class Google_Service_AdExchangeBuyer_DateTime extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $day;
  public $hour;
  public $minute;
  public $month;
  public $second;
  public $timeZoneId;
  public $year;


  public function setDay($day)
  {
    $this->day = $day;
  }
  public function getDay()
  {
    return $this->day;
  }
  public function setHour($hour)
  {
    $this->hour = $hour;
  }
  public function getHour()
  {
    return $this->hour;
  }
  public function setMinute($minute)
  {
    $this->minute = $minute;
  }
  public function getMinute()
  {
    return $this->minute;
  }
  public function setMonth($month)
  {
    $this->month = $month;
  }
  public function getMonth()
  {
    return $this->month;
  }
  public function setSecond($second)
  {
    $this->second = $second;
  }
  public function getSecond()
  {
    return $this->second;
  }
  public function setTimeZoneId($timeZoneId)
  {
    $this->timeZoneId = $timeZoneId;
  }
  public function getTimeZoneId()
  {
    return $this->timeZoneId;
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

class Google_Service_AdExchangeBuyer_DealPartyDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $buyerType = 'Google_Service_AdExchangeBuyer_BuyerDto';
  protected $buyerDataType = '';
  public $buyerSellerRole;
  public $customerId;
  public $name;
  protected $webPropertyType = 'Google_Service_AdExchangeBuyer_WebPropertyDto';
  protected $webPropertyDataType = '';


  public function setBuyer(Google_Service_AdExchangeBuyer_BuyerDto $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setBuyerSellerRole($buyerSellerRole)
  {
    $this->buyerSellerRole = $buyerSellerRole;
  }
  public function getBuyerSellerRole()
  {
    return $this->buyerSellerRole;
  }
  public function setCustomerId($customerId)
  {
    $this->customerId = $customerId;
  }
  public function getCustomerId()
  {
    return $this->customerId;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setWebProperty(Google_Service_AdExchangeBuyer_WebPropertyDto $webProperty)
  {
    $this->webProperty = $webProperty;
  }
  public function getWebProperty()
  {
    return $this->webProperty;
  }
}

class Google_Service_AdExchangeBuyer_DealTerms extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $description;
  protected $guaranteedFixedPriceTermsType = 'Google_Service_AdExchangeBuyer_DealTermsGuaranteedFixedPriceTerms';
  protected $guaranteedFixedPriceTermsDataType = '';
  protected $nonGuaranteedAuctionTermsType = 'Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedAuctionTerms';
  protected $nonGuaranteedAuctionTermsDataType = '';
  protected $nonGuaranteedFixedPriceTermsType = 'Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedFixedPriceTerms';
  protected $nonGuaranteedFixedPriceTermsDataType = '';


  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setGuaranteedFixedPriceTerms(Google_Service_AdExchangeBuyer_DealTermsGuaranteedFixedPriceTerms $guaranteedFixedPriceTerms)
  {
    $this->guaranteedFixedPriceTerms = $guaranteedFixedPriceTerms;
  }
  public function getGuaranteedFixedPriceTerms()
  {
    return $this->guaranteedFixedPriceTerms;
  }
  public function setNonGuaranteedAuctionTerms(Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedAuctionTerms $nonGuaranteedAuctionTerms)
  {
    $this->nonGuaranteedAuctionTerms = $nonGuaranteedAuctionTerms;
  }
  public function getNonGuaranteedAuctionTerms()
  {
    return $this->nonGuaranteedAuctionTerms;
  }
  public function setNonGuaranteedFixedPriceTerms(Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedFixedPriceTerms $nonGuaranteedFixedPriceTerms)
  {
    $this->nonGuaranteedFixedPriceTerms = $nonGuaranteedFixedPriceTerms;
  }
  public function getNonGuaranteedFixedPriceTerms()
  {
    return $this->nonGuaranteedFixedPriceTerms;
  }
}

class Google_Service_AdExchangeBuyer_DealTermsGuaranteedFixedPriceTerms extends Google_Collection
{
  protected $collection_key = 'fixedPrices';
  protected $internal_gapi_mappings = array(
  );
  protected $fixedPricesType = 'Google_Service_AdExchangeBuyer_PricePerBuyer';
  protected $fixedPricesDataType = 'array';
  public $guaranteedImpressions;
  public $guaranteedLooks;


  public function setFixedPrices($fixedPrices)
  {
    $this->fixedPrices = $fixedPrices;
  }
  public function getFixedPrices()
  {
    return $this->fixedPrices;
  }
  public function setGuaranteedImpressions($guaranteedImpressions)
  {
    $this->guaranteedImpressions = $guaranteedImpressions;
  }
  public function getGuaranteedImpressions()
  {
    return $this->guaranteedImpressions;
  }
  public function setGuaranteedLooks($guaranteedLooks)
  {
    $this->guaranteedLooks = $guaranteedLooks;
  }
  public function getGuaranteedLooks()
  {
    return $this->guaranteedLooks;
  }
}

class Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedAuctionTerms extends Google_Collection
{
  protected $collection_key = 'reservePricePerBuyers';
  protected $internal_gapi_mappings = array(
  );
  public $privateAuctionId;
  protected $reservePricePerBuyersType = 'Google_Service_AdExchangeBuyer_PricePerBuyer';
  protected $reservePricePerBuyersDataType = 'array';


  public function setPrivateAuctionId($privateAuctionId)
  {
    $this->privateAuctionId = $privateAuctionId;
  }
  public function getPrivateAuctionId()
  {
    return $this->privateAuctionId;
  }
  public function setReservePricePerBuyers($reservePricePerBuyers)
  {
    $this->reservePricePerBuyers = $reservePricePerBuyers;
  }
  public function getReservePricePerBuyers()
  {
    return $this->reservePricePerBuyers;
  }
}

class Google_Service_AdExchangeBuyer_DealTermsNonGuaranteedFixedPriceTerms extends Google_Collection
{
  protected $collection_key = 'fixedPrices';
  protected $internal_gapi_mappings = array(
  );
  protected $fixedPricesType = 'Google_Service_AdExchangeBuyer_PricePerBuyer';
  protected $fixedPricesDataType = 'array';


  public function setFixedPrices($fixedPrices)
  {
    $this->fixedPrices = $fixedPrices;
  }
  public function getFixedPrices()
  {
    return $this->fixedPrices;
  }
}

class Google_Service_AdExchangeBuyer_DeleteOrderDealsRequest extends Google_Collection
{
  protected $collection_key = 'dealIds';
  protected $internal_gapi_mappings = array(
  );
  public $dealIds;
  public $orderRevisionNumber;
  public $updateAction;


  public function setDealIds($dealIds)
  {
    $this->dealIds = $dealIds;
  }
  public function getDealIds()
  {
    return $this->dealIds;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
  }
  public function setUpdateAction($updateAction)
  {
    $this->updateAction = $updateAction;
  }
  public function getUpdateAction()
  {
    return $this->updateAction;
  }
}

class Google_Service_AdExchangeBuyer_DeleteOrderDealsResponse extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';
  public $orderRevisionNumber;


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
  }
}

class Google_Service_AdExchangeBuyer_DeliveryControl extends Google_Collection
{
  protected $collection_key = 'frequencyCaps';
  protected $internal_gapi_mappings = array(
  );
  public $deliveryRateType;
  protected $frequencyCapsType = 'Google_Service_AdExchangeBuyer_DeliveryControlFrequencyCap';
  protected $frequencyCapsDataType = 'array';


  public function setDeliveryRateType($deliveryRateType)
  {
    $this->deliveryRateType = $deliveryRateType;
  }
  public function getDeliveryRateType()
  {
    return $this->deliveryRateType;
  }
  public function setFrequencyCaps($frequencyCaps)
  {
    $this->frequencyCaps = $frequencyCaps;
  }
  public function getFrequencyCaps()
  {
    return $this->frequencyCaps;
  }
}

class Google_Service_AdExchangeBuyer_DeliveryControlFrequencyCap extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $maxImpressions;
  public $numTimeUnits;
  public $timeUnitType;


  public function setMaxImpressions($maxImpressions)
  {
    $this->maxImpressions = $maxImpressions;
  }
  public function getMaxImpressions()
  {
    return $this->maxImpressions;
  }
  public function setNumTimeUnits($numTimeUnits)
  {
    $this->numTimeUnits = $numTimeUnits;
  }
  public function getNumTimeUnits()
  {
    return $this->numTimeUnits;
  }
  public function setTimeUnitType($timeUnitType)
  {
    $this->timeUnitType = $timeUnitType;
  }
  public function getTimeUnitType()
  {
    return $this->timeUnitType;
  }
}

class Google_Service_AdExchangeBuyer_EditAllOrderDealsRequest extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';
  protected $orderType = 'Google_Service_AdExchangeBuyer_MarketplaceOrder';
  protected $orderDataType = '';
  public $orderRevisionNumber;
  public $updateAction;


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
  public function setOrder(Google_Service_AdExchangeBuyer_MarketplaceOrder $order)
  {
    $this->order = $order;
  }
  public function getOrder()
  {
    return $this->order;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
  }
  public function setUpdateAction($updateAction)
  {
    $this->updateAction = $updateAction;
  }
  public function getUpdateAction()
  {
    return $this->updateAction;
  }
}

class Google_Service_AdExchangeBuyer_EditAllOrderDealsResponse extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
}

class Google_Service_AdExchangeBuyer_EditHistoryDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $createdByLoginName;
  public $createdTimeStamp;
  public $lastUpdateTimeStamp;
  public $lastUpdatedByLoginName;


  public function setCreatedByLoginName($createdByLoginName)
  {
    $this->createdByLoginName = $createdByLoginName;
  }
  public function getCreatedByLoginName()
  {
    return $this->createdByLoginName;
  }
  public function setCreatedTimeStamp($createdTimeStamp)
  {
    $this->createdTimeStamp = $createdTimeStamp;
  }
  public function getCreatedTimeStamp()
  {
    return $this->createdTimeStamp;
  }
  public function setLastUpdateTimeStamp($lastUpdateTimeStamp)
  {
    $this->lastUpdateTimeStamp = $lastUpdateTimeStamp;
  }
  public function getLastUpdateTimeStamp()
  {
    return $this->lastUpdateTimeStamp;
  }
  public function setLastUpdatedByLoginName($lastUpdatedByLoginName)
  {
    $this->lastUpdatedByLoginName = $lastUpdatedByLoginName;
  }
  public function getLastUpdatedByLoginName()
  {
    return $this->lastUpdatedByLoginName;
  }
}

class Google_Service_AdExchangeBuyer_GetFinalizedNegotiationByExternalDealIdRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $includePrivateAuctions;


  public function setIncludePrivateAuctions($includePrivateAuctions)
  {
    $this->includePrivateAuctions = $includePrivateAuctions;
  }
  public function getIncludePrivateAuctions()
  {
    return $this->includePrivateAuctions;
  }
}

class Google_Service_AdExchangeBuyer_GetNegotiationByIdRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $includePrivateAuctions;


  public function setIncludePrivateAuctions($includePrivateAuctions)
  {
    $this->includePrivateAuctions = $includePrivateAuctions;
  }
  public function getIncludePrivateAuctions()
  {
    return $this->includePrivateAuctions;
  }
}

class Google_Service_AdExchangeBuyer_GetNegotiationsRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $finalized;
  public $includePrivateAuctions;
  public $sinceTimestampMillis;


  public function setFinalized($finalized)
  {
    $this->finalized = $finalized;
  }
  public function getFinalized()
  {
    return $this->finalized;
  }
  public function setIncludePrivateAuctions($includePrivateAuctions)
  {
    $this->includePrivateAuctions = $includePrivateAuctions;
  }
  public function getIncludePrivateAuctions()
  {
    return $this->includePrivateAuctions;
  }
  public function setSinceTimestampMillis($sinceTimestampMillis)
  {
    $this->sinceTimestampMillis = $sinceTimestampMillis;
  }
  public function getSinceTimestampMillis()
  {
    return $this->sinceTimestampMillis;
  }
}

class Google_Service_AdExchangeBuyer_GetNegotiationsResponse extends Google_Collection
{
  protected $collection_key = 'negotiations';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $negotiationsType = 'Google_Service_AdExchangeBuyer_NegotiationDto';
  protected $negotiationsDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNegotiations($negotiations)
  {
    $this->negotiations = $negotiations;
  }
  public function getNegotiations()
  {
    return $this->negotiations;
  }
}

class Google_Service_AdExchangeBuyer_GetOffersResponse extends Google_Collection
{
  protected $collection_key = 'offers';
  protected $internal_gapi_mappings = array(
  );
  protected $offersType = 'Google_Service_AdExchangeBuyer_MarketplaceOffer';
  protected $offersDataType = 'array';


  public function setOffers($offers)
  {
    $this->offers = $offers;
  }
  public function getOffers()
  {
    return $this->offers;
  }
}

class Google_Service_AdExchangeBuyer_GetOrderDealsResponse extends Google_Collection
{
  protected $collection_key = 'deals';
  protected $internal_gapi_mappings = array(
  );
  protected $dealsType = 'Google_Service_AdExchangeBuyer_MarketplaceDeal';
  protected $dealsDataType = 'array';


  public function setDeals($deals)
  {
    $this->deals = $deals;
  }
  public function getDeals()
  {
    return $this->deals;
  }
}

class Google_Service_AdExchangeBuyer_GetOrderNotesResponse extends Google_Collection
{
  protected $collection_key = 'notes';
  protected $internal_gapi_mappings = array(
  );
  protected $notesType = 'Google_Service_AdExchangeBuyer_MarketplaceNote';
  protected $notesDataType = 'array';


  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
}

class Google_Service_AdExchangeBuyer_GetOrdersResponse extends Google_Collection
{
  protected $collection_key = 'orders';
  protected $internal_gapi_mappings = array(
  );
  protected $ordersType = 'Google_Service_AdExchangeBuyer_MarketplaceOrder';
  protected $ordersDataType = 'array';


  public function setOrders($orders)
  {
    $this->orders = $orders;
  }
  public function getOrders()
  {
    return $this->orders;
  }
}

class Google_Service_AdExchangeBuyer_InventorySegmentTargeting extends Google_Collection
{
  protected $collection_key = 'positiveXfpPlacements';
  protected $internal_gapi_mappings = array(
  );
  protected $negativeAdSizesType = 'Google_Service_AdExchangeBuyer_AdSize';
  protected $negativeAdSizesDataType = 'array';
  public $negativeAdTypeSegments;
  public $negativeAudienceSegments;
  public $negativeDeviceCategories;
  public $negativeIcmBrands;
  public $negativeIcmInterests;
  public $negativeInventorySlots;
  protected $negativeKeyValuesType = 'Google_Service_AdExchangeBuyer_RuleKeyValuePair';
  protected $negativeKeyValuesDataType = 'array';
  public $negativeLocations;
  public $negativeMobileApps;
  public $negativeOperatingSystemVersions;
  public $negativeOperatingSystems;
  public $negativeSiteUrls;
  public $negativeSizes;
  public $negativeVideoAdPositionSegments;
  public $negativeVideoDurationSegments;
  public $negativeXfpAdSlots;
  public $negativeXfpPlacements;
  protected $positiveAdSizesType = 'Google_Service_AdExchangeBuyer_AdSize';
  protected $positiveAdSizesDataType = 'array';
  public $positiveAdTypeSegments;
  public $positiveAudienceSegments;
  public $positiveDeviceCategories;
  public $positiveIcmBrands;
  public $positiveIcmInterests;
  public $positiveInventorySlots;
  protected $positiveKeyValuesType = 'Google_Service_AdExchangeBuyer_RuleKeyValuePair';
  protected $positiveKeyValuesDataType = 'array';
  public $positiveLocations;
  public $positiveMobileApps;
  public $positiveOperatingSystemVersions;
  public $positiveOperatingSystems;
  public $positiveSiteUrls;
  public $positiveSizes;
  public $positiveVideoAdPositionSegments;
  public $positiveVideoDurationSegments;
  public $positiveXfpAdSlots;
  public $positiveXfpPlacements;


  public function setNegativeAdSizes($negativeAdSizes)
  {
    $this->negativeAdSizes = $negativeAdSizes;
  }
  public function getNegativeAdSizes()
  {
    return $this->negativeAdSizes;
  }
  public function setNegativeAdTypeSegments($negativeAdTypeSegments)
  {
    $this->negativeAdTypeSegments = $negativeAdTypeSegments;
  }
  public function getNegativeAdTypeSegments()
  {
    return $this->negativeAdTypeSegments;
  }
  public function setNegativeAudienceSegments($negativeAudienceSegments)
  {
    $this->negativeAudienceSegments = $negativeAudienceSegments;
  }
  public function getNegativeAudienceSegments()
  {
    return $this->negativeAudienceSegments;
  }
  public function setNegativeDeviceCategories($negativeDeviceCategories)
  {
    $this->negativeDeviceCategories = $negativeDeviceCategories;
  }
  public function getNegativeDeviceCategories()
  {
    return $this->negativeDeviceCategories;
  }
  public function setNegativeIcmBrands($negativeIcmBrands)
  {
    $this->negativeIcmBrands = $negativeIcmBrands;
  }
  public function getNegativeIcmBrands()
  {
    return $this->negativeIcmBrands;
  }
  public function setNegativeIcmInterests($negativeIcmInterests)
  {
    $this->negativeIcmInterests = $negativeIcmInterests;
  }
  public function getNegativeIcmInterests()
  {
    return $this->negativeIcmInterests;
  }
  public function setNegativeInventorySlots($negativeInventorySlots)
  {
    $this->negativeInventorySlots = $negativeInventorySlots;
  }
  public function getNegativeInventorySlots()
  {
    return $this->negativeInventorySlots;
  }
  public function setNegativeKeyValues($negativeKeyValues)
  {
    $this->negativeKeyValues = $negativeKeyValues;
  }
  public function getNegativeKeyValues()
  {
    return $this->negativeKeyValues;
  }
  public function setNegativeLocations($negativeLocations)
  {
    $this->negativeLocations = $negativeLocations;
  }
  public function getNegativeLocations()
  {
    return $this->negativeLocations;
  }
  public function setNegativeMobileApps($negativeMobileApps)
  {
    $this->negativeMobileApps = $negativeMobileApps;
  }
  public function getNegativeMobileApps()
  {
    return $this->negativeMobileApps;
  }
  public function setNegativeOperatingSystemVersions($negativeOperatingSystemVersions)
  {
    $this->negativeOperatingSystemVersions = $negativeOperatingSystemVersions;
  }
  public function getNegativeOperatingSystemVersions()
  {
    return $this->negativeOperatingSystemVersions;
  }
  public function setNegativeOperatingSystems($negativeOperatingSystems)
  {
    $this->negativeOperatingSystems = $negativeOperatingSystems;
  }
  public function getNegativeOperatingSystems()
  {
    return $this->negativeOperatingSystems;
  }
  public function setNegativeSiteUrls($negativeSiteUrls)
  {
    $this->negativeSiteUrls = $negativeSiteUrls;
  }
  public function getNegativeSiteUrls()
  {
    return $this->negativeSiteUrls;
  }
  public function setNegativeSizes($negativeSizes)
  {
    $this->negativeSizes = $negativeSizes;
  }
  public function getNegativeSizes()
  {
    return $this->negativeSizes;
  }
  public function setNegativeVideoAdPositionSegments($negativeVideoAdPositionSegments)
  {
    $this->negativeVideoAdPositionSegments = $negativeVideoAdPositionSegments;
  }
  public function getNegativeVideoAdPositionSegments()
  {
    return $this->negativeVideoAdPositionSegments;
  }
  public function setNegativeVideoDurationSegments($negativeVideoDurationSegments)
  {
    $this->negativeVideoDurationSegments = $negativeVideoDurationSegments;
  }
  public function getNegativeVideoDurationSegments()
  {
    return $this->negativeVideoDurationSegments;
  }
  public function setNegativeXfpAdSlots($negativeXfpAdSlots)
  {
    $this->negativeXfpAdSlots = $negativeXfpAdSlots;
  }
  public function getNegativeXfpAdSlots()
  {
    return $this->negativeXfpAdSlots;
  }
  public function setNegativeXfpPlacements($negativeXfpPlacements)
  {
    $this->negativeXfpPlacements = $negativeXfpPlacements;
  }
  public function getNegativeXfpPlacements()
  {
    return $this->negativeXfpPlacements;
  }
  public function setPositiveAdSizes($positiveAdSizes)
  {
    $this->positiveAdSizes = $positiveAdSizes;
  }
  public function getPositiveAdSizes()
  {
    return $this->positiveAdSizes;
  }
  public function setPositiveAdTypeSegments($positiveAdTypeSegments)
  {
    $this->positiveAdTypeSegments = $positiveAdTypeSegments;
  }
  public function getPositiveAdTypeSegments()
  {
    return $this->positiveAdTypeSegments;
  }
  public function setPositiveAudienceSegments($positiveAudienceSegments)
  {
    $this->positiveAudienceSegments = $positiveAudienceSegments;
  }
  public function getPositiveAudienceSegments()
  {
    return $this->positiveAudienceSegments;
  }
  public function setPositiveDeviceCategories($positiveDeviceCategories)
  {
    $this->positiveDeviceCategories = $positiveDeviceCategories;
  }
  public function getPositiveDeviceCategories()
  {
    return $this->positiveDeviceCategories;
  }
  public function setPositiveIcmBrands($positiveIcmBrands)
  {
    $this->positiveIcmBrands = $positiveIcmBrands;
  }
  public function getPositiveIcmBrands()
  {
    return $this->positiveIcmBrands;
  }
  public function setPositiveIcmInterests($positiveIcmInterests)
  {
    $this->positiveIcmInterests = $positiveIcmInterests;
  }
  public function getPositiveIcmInterests()
  {
    return $this->positiveIcmInterests;
  }
  public function setPositiveInventorySlots($positiveInventorySlots)
  {
    $this->positiveInventorySlots = $positiveInventorySlots;
  }
  public function getPositiveInventorySlots()
  {
    return $this->positiveInventorySlots;
  }
  public function setPositiveKeyValues($positiveKeyValues)
  {
    $this->positiveKeyValues = $positiveKeyValues;
  }
  public function getPositiveKeyValues()
  {
    return $this->positiveKeyValues;
  }
  public function setPositiveLocations($positiveLocations)
  {
    $this->positiveLocations = $positiveLocations;
  }
  public function getPositiveLocations()
  {
    return $this->positiveLocations;
  }
  public function setPositiveMobileApps($positiveMobileApps)
  {
    $this->positiveMobileApps = $positiveMobileApps;
  }
  public function getPositiveMobileApps()
  {
    return $this->positiveMobileApps;
  }
  public function setPositiveOperatingSystemVersions($positiveOperatingSystemVersions)
  {
    $this->positiveOperatingSystemVersions = $positiveOperatingSystemVersions;
  }
  public function getPositiveOperatingSystemVersions()
  {
    return $this->positiveOperatingSystemVersions;
  }
  public function setPositiveOperatingSystems($positiveOperatingSystems)
  {
    $this->positiveOperatingSystems = $positiveOperatingSystems;
  }
  public function getPositiveOperatingSystems()
  {
    return $this->positiveOperatingSystems;
  }
  public function setPositiveSiteUrls($positiveSiteUrls)
  {
    $this->positiveSiteUrls = $positiveSiteUrls;
  }
  public function getPositiveSiteUrls()
  {
    return $this->positiveSiteUrls;
  }
  public function setPositiveSizes($positiveSizes)
  {
    $this->positiveSizes = $positiveSizes;
  }
  public function getPositiveSizes()
  {
    return $this->positiveSizes;
  }
  public function setPositiveVideoAdPositionSegments($positiveVideoAdPositionSegments)
  {
    $this->positiveVideoAdPositionSegments = $positiveVideoAdPositionSegments;
  }
  public function getPositiveVideoAdPositionSegments()
  {
    return $this->positiveVideoAdPositionSegments;
  }
  public function setPositiveVideoDurationSegments($positiveVideoDurationSegments)
  {
    $this->positiveVideoDurationSegments = $positiveVideoDurationSegments;
  }
  public function getPositiveVideoDurationSegments()
  {
    return $this->positiveVideoDurationSegments;
  }
  public function setPositiveXfpAdSlots($positiveXfpAdSlots)
  {
    $this->positiveXfpAdSlots = $positiveXfpAdSlots;
  }
  public function getPositiveXfpAdSlots()
  {
    return $this->positiveXfpAdSlots;
  }
  public function setPositiveXfpPlacements($positiveXfpPlacements)
  {
    $this->positiveXfpPlacements = $positiveXfpPlacements;
  }
  public function getPositiveXfpPlacements()
  {
    return $this->positiveXfpPlacements;
  }
}

class Google_Service_AdExchangeBuyer_ListClientAccessCapabilitiesRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $sponsorAccountId;


  public function setSponsorAccountId($sponsorAccountId)
  {
    $this->sponsorAccountId = $sponsorAccountId;
  }
  public function getSponsorAccountId()
  {
    return $this->sponsorAccountId;
  }
}

class Google_Service_AdExchangeBuyer_ListClientAccessCapabilitiesResponse extends Google_Collection
{
  protected $collection_key = 'clientAccessPermissions';
  protected $internal_gapi_mappings = array(
  );
  protected $clientAccessPermissionsType = 'Google_Service_AdExchangeBuyer_ClientAccessCapabilities';
  protected $clientAccessPermissionsDataType = 'array';


  public function setClientAccessPermissions($clientAccessPermissions)
  {
    $this->clientAccessPermissions = $clientAccessPermissions;
  }
  public function getClientAccessPermissions()
  {
    return $this->clientAccessPermissions;
  }
}

class Google_Service_AdExchangeBuyer_ListOffersRequest extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $sinceTimestampMillis;


  public function setSinceTimestampMillis($sinceTimestampMillis)
  {
    $this->sinceTimestampMillis = $sinceTimestampMillis;
  }
  public function getSinceTimestampMillis()
  {
    return $this->sinceTimestampMillis;
  }
}

class Google_Service_AdExchangeBuyer_ListOffersResponse extends Google_Collection
{
  protected $collection_key = 'offers';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $offersType = 'Google_Service_AdExchangeBuyer_OfferDto';
  protected $offersDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setOffers($offers)
  {
    $this->offers = $offers;
  }
  public function getOffers()
  {
    return $this->offers;
  }
}

class Google_Service_AdExchangeBuyer_MarketplaceDeal extends Google_Collection
{
  protected $collection_key = 'sharedTargetings';
  protected $internal_gapi_mappings = array(
  );
  protected $buyerPrivateDataType = 'Google_Service_AdExchangeBuyer_PrivateData';
  protected $buyerPrivateDataDataType = '';
  public $creationTimeMs;
  public $dealId;
  protected $deliveryControlType = 'Google_Service_AdExchangeBuyer_DeliveryControl';
  protected $deliveryControlDataType = '';
  public $externalDealId;
  public $flightEndTimeMs;
  public $flightStartTimeMs;
  public $inventoryDescription;
  public $kind;
  public $lastUpdateTimeMs;
  public $name;
  public $offerId;
  public $offerRevisionNumber;
  public $orderId;
  protected $sellerContactsType = 'Google_Service_AdExchangeBuyer_ContactInformation';
  protected $sellerContactsDataType = 'array';
  protected $sharedTargetingsType = 'Google_Service_AdExchangeBuyer_SharedTargeting';
  protected $sharedTargetingsDataType = 'array';
  public $syndicationProduct;
  protected $termsType = 'Google_Service_AdExchangeBuyer_DealTerms';
  protected $termsDataType = '';
  public $webPropertyCode;


  public function setBuyerPrivateData(Google_Service_AdExchangeBuyer_PrivateData $buyerPrivateData)
  {
    $this->buyerPrivateData = $buyerPrivateData;
  }
  public function getBuyerPrivateData()
  {
    return $this->buyerPrivateData;
  }
  public function setCreationTimeMs($creationTimeMs)
  {
    $this->creationTimeMs = $creationTimeMs;
  }
  public function getCreationTimeMs()
  {
    return $this->creationTimeMs;
  }
  public function setDealId($dealId)
  {
    $this->dealId = $dealId;
  }
  public function getDealId()
  {
    return $this->dealId;
  }
  public function setDeliveryControl(Google_Service_AdExchangeBuyer_DeliveryControl $deliveryControl)
  {
    $this->deliveryControl = $deliveryControl;
  }
  public function getDeliveryControl()
  {
    return $this->deliveryControl;
  }
  public function setExternalDealId($externalDealId)
  {
    $this->externalDealId = $externalDealId;
  }
  public function getExternalDealId()
  {
    return $this->externalDealId;
  }
  public function setFlightEndTimeMs($flightEndTimeMs)
  {
    $this->flightEndTimeMs = $flightEndTimeMs;
  }
  public function getFlightEndTimeMs()
  {
    return $this->flightEndTimeMs;
  }
  public function setFlightStartTimeMs($flightStartTimeMs)
  {
    $this->flightStartTimeMs = $flightStartTimeMs;
  }
  public function getFlightStartTimeMs()
  {
    return $this->flightStartTimeMs;
  }
  public function setInventoryDescription($inventoryDescription)
  {
    $this->inventoryDescription = $inventoryDescription;
  }
  public function getInventoryDescription()
  {
    return $this->inventoryDescription;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLastUpdateTimeMs($lastUpdateTimeMs)
  {
    $this->lastUpdateTimeMs = $lastUpdateTimeMs;
  }
  public function getLastUpdateTimeMs()
  {
    return $this->lastUpdateTimeMs;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOfferId($offerId)
  {
    $this->offerId = $offerId;
  }
  public function getOfferId()
  {
    return $this->offerId;
  }
  public function setOfferRevisionNumber($offerRevisionNumber)
  {
    $this->offerRevisionNumber = $offerRevisionNumber;
  }
  public function getOfferRevisionNumber()
  {
    return $this->offerRevisionNumber;
  }
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setSellerContacts($sellerContacts)
  {
    $this->sellerContacts = $sellerContacts;
  }
  public function getSellerContacts()
  {
    return $this->sellerContacts;
  }
  public function setSharedTargetings($sharedTargetings)
  {
    $this->sharedTargetings = $sharedTargetings;
  }
  public function getSharedTargetings()
  {
    return $this->sharedTargetings;
  }
  public function setSyndicationProduct($syndicationProduct)
  {
    $this->syndicationProduct = $syndicationProduct;
  }
  public function getSyndicationProduct()
  {
    return $this->syndicationProduct;
  }
  public function setTerms(Google_Service_AdExchangeBuyer_DealTerms $terms)
  {
    $this->terms = $terms;
  }
  public function getTerms()
  {
    return $this->terms;
  }
  public function setWebPropertyCode($webPropertyCode)
  {
    $this->webPropertyCode = $webPropertyCode;
  }
  public function getWebPropertyCode()
  {
    return $this->webPropertyCode;
  }
}

class Google_Service_AdExchangeBuyer_MarketplaceDealParty extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $buyerType = 'Google_Service_AdExchangeBuyer_Buyer';
  protected $buyerDataType = '';
  protected $sellerType = 'Google_Service_AdExchangeBuyer_Seller';
  protected $sellerDataType = '';


  public function setBuyer(Google_Service_AdExchangeBuyer_Buyer $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setSeller(Google_Service_AdExchangeBuyer_Seller $seller)
  {
    $this->seller = $seller;
  }
  public function getSeller()
  {
    return $this->seller;
  }
}

class Google_Service_AdExchangeBuyer_MarketplaceLabel extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $createTimeMs;
  protected $deprecatedMarketplaceDealPartyType = 'Google_Service_AdExchangeBuyer_MarketplaceDealParty';
  protected $deprecatedMarketplaceDealPartyDataType = '';
  public $label;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setCreateTimeMs($createTimeMs)
  {
    $this->createTimeMs = $createTimeMs;
  }
  public function getCreateTimeMs()
  {
    return $this->createTimeMs;
  }
  public function setDeprecatedMarketplaceDealParty(Google_Service_AdExchangeBuyer_MarketplaceDealParty $deprecatedMarketplaceDealParty)
  {
    $this->deprecatedMarketplaceDealParty = $deprecatedMarketplaceDealParty;
  }
  public function getDeprecatedMarketplaceDealParty()
  {
    return $this->deprecatedMarketplaceDealParty;
  }
  public function setLabel($label)
  {
    $this->label = $label;
  }
  public function getLabel()
  {
    return $this->label;
  }
}

class Google_Service_AdExchangeBuyer_MarketplaceNote extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $creatorRole;
  public $dealId;
  public $kind;
  public $note;
  public $noteId;
  public $orderId;
  public $orderRevisionNumber;
  public $timestampMs;


  public function setCreatorRole($creatorRole)
  {
    $this->creatorRole = $creatorRole;
  }
  public function getCreatorRole()
  {
    return $this->creatorRole;
  }
  public function setDealId($dealId)
  {
    $this->dealId = $dealId;
  }
  public function getDealId()
  {
    return $this->dealId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNote($note)
  {
    $this->note = $note;
  }
  public function getNote()
  {
    return $this->note;
  }
  public function setNoteId($noteId)
  {
    $this->noteId = $noteId;
  }
  public function getNoteId()
  {
    return $this->noteId;
  }
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setOrderRevisionNumber($orderRevisionNumber)
  {
    $this->orderRevisionNumber = $orderRevisionNumber;
  }
  public function getOrderRevisionNumber()
  {
    return $this->orderRevisionNumber;
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

class Google_Service_AdExchangeBuyer_MarketplaceOffer extends Google_Collection
{
  protected $collection_key = 'sharedTargetings';
  protected $internal_gapi_mappings = array(
  );
  public $creationTimeMs;
  protected $creatorContactsType = 'Google_Service_AdExchangeBuyer_ContactInformation';
  protected $creatorContactsDataType = 'array';
  public $flightEndTimeMs;
  public $flightStartTimeMs;
  public $hasCreatorSignedOff;
  public $kind;
  protected $labelsType = 'Google_Service_AdExchangeBuyer_MarketplaceLabel';
  protected $labelsDataType = 'array';
  public $lastUpdateTimeMs;
  public $name;
  public $offerId;
  public $revisionNumber;
  protected $sellerType = 'Google_Service_AdExchangeBuyer_Seller';
  protected $sellerDataType = '';
  protected $sharedTargetingsType = 'Google_Service_AdExchangeBuyer_SharedTargeting';
  protected $sharedTargetingsDataType = 'array';
  public $state;
  public $syndicationProduct;
  protected $termsType = 'Google_Service_AdExchangeBuyer_DealTerms';
  protected $termsDataType = '';
  public $webPropertyCode;


  public function setCreationTimeMs($creationTimeMs)
  {
    $this->creationTimeMs = $creationTimeMs;
  }
  public function getCreationTimeMs()
  {
    return $this->creationTimeMs;
  }
  public function setCreatorContacts($creatorContacts)
  {
    $this->creatorContacts = $creatorContacts;
  }
  public function getCreatorContacts()
  {
    return $this->creatorContacts;
  }
  public function setFlightEndTimeMs($flightEndTimeMs)
  {
    $this->flightEndTimeMs = $flightEndTimeMs;
  }
  public function getFlightEndTimeMs()
  {
    return $this->flightEndTimeMs;
  }
  public function setFlightStartTimeMs($flightStartTimeMs)
  {
    $this->flightStartTimeMs = $flightStartTimeMs;
  }
  public function getFlightStartTimeMs()
  {
    return $this->flightStartTimeMs;
  }
  public function setHasCreatorSignedOff($hasCreatorSignedOff)
  {
    $this->hasCreatorSignedOff = $hasCreatorSignedOff;
  }
  public function getHasCreatorSignedOff()
  {
    return $this->hasCreatorSignedOff;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setLastUpdateTimeMs($lastUpdateTimeMs)
  {
    $this->lastUpdateTimeMs = $lastUpdateTimeMs;
  }
  public function getLastUpdateTimeMs()
  {
    return $this->lastUpdateTimeMs;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOfferId($offerId)
  {
    $this->offerId = $offerId;
  }
  public function getOfferId()
  {
    return $this->offerId;
  }
  public function setRevisionNumber($revisionNumber)
  {
    $this->revisionNumber = $revisionNumber;
  }
  public function getRevisionNumber()
  {
    return $this->revisionNumber;
  }
  public function setSeller(Google_Service_AdExchangeBuyer_Seller $seller)
  {
    $this->seller = $seller;
  }
  public function getSeller()
  {
    return $this->seller;
  }
  public function setSharedTargetings($sharedTargetings)
  {
    $this->sharedTargetings = $sharedTargetings;
  }
  public function getSharedTargetings()
  {
    return $this->sharedTargetings;
  }
  public function setState($state)
  {
    $this->state = $state;
  }
  public function getState()
  {
    return $this->state;
  }
  public function setSyndicationProduct($syndicationProduct)
  {
    $this->syndicationProduct = $syndicationProduct;
  }
  public function getSyndicationProduct()
  {
    return $this->syndicationProduct;
  }
  public function setTerms(Google_Service_AdExchangeBuyer_DealTerms $terms)
  {
    $this->terms = $terms;
  }
  public function getTerms()
  {
    return $this->terms;
  }
  public function setWebPropertyCode($webPropertyCode)
  {
    $this->webPropertyCode = $webPropertyCode;
  }
  public function getWebPropertyCode()
  {
    return $this->webPropertyCode;
  }
}

class Google_Service_AdExchangeBuyer_MarketplaceOrder extends Google_Collection
{
  protected $collection_key = 'sellerContacts';
  protected $internal_gapi_mappings = array(
  );
  protected $billedBuyerType = 'Google_Service_AdExchangeBuyer_Buyer';
  protected $billedBuyerDataType = '';
  protected $buyerType = 'Google_Service_AdExchangeBuyer_Buyer';
  protected $buyerDataType = '';
  protected $buyerContactsType = 'Google_Service_AdExchangeBuyer_ContactInformation';
  protected $buyerContactsDataType = 'array';
  protected $buyerPrivateDataType = 'Google_Service_AdExchangeBuyer_PrivateData';
  protected $buyerPrivateDataDataType = '';
  public $hasBuyerSignedOff;
  public $hasSellerSignedOff;
  public $isRenegotiating;
  public $isSetupComplete;
  public $kind;
  protected $labelsType = 'Google_Service_AdExchangeBuyer_MarketplaceLabel';
  protected $labelsDataType = 'array';
  public $lastUpdaterOrCommentorRole;
  public $lastUpdaterRole;
  public $name;
  public $orderId;
  public $orderState;
  public $originatorRole;
  public $revisionNumber;
  public $revisionTimeMs;
  protected $sellerType = 'Google_Service_AdExchangeBuyer_Seller';
  protected $sellerDataType = '';
  protected $sellerContactsType = 'Google_Service_AdExchangeBuyer_ContactInformation';
  protected $sellerContactsDataType = 'array';


  public function setBilledBuyer(Google_Service_AdExchangeBuyer_Buyer $billedBuyer)
  {
    $this->billedBuyer = $billedBuyer;
  }
  public function getBilledBuyer()
  {
    return $this->billedBuyer;
  }
  public function setBuyer(Google_Service_AdExchangeBuyer_Buyer $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setBuyerContacts($buyerContacts)
  {
    $this->buyerContacts = $buyerContacts;
  }
  public function getBuyerContacts()
  {
    return $this->buyerContacts;
  }
  public function setBuyerPrivateData(Google_Service_AdExchangeBuyer_PrivateData $buyerPrivateData)
  {
    $this->buyerPrivateData = $buyerPrivateData;
  }
  public function getBuyerPrivateData()
  {
    return $this->buyerPrivateData;
  }
  public function setHasBuyerSignedOff($hasBuyerSignedOff)
  {
    $this->hasBuyerSignedOff = $hasBuyerSignedOff;
  }
  public function getHasBuyerSignedOff()
  {
    return $this->hasBuyerSignedOff;
  }
  public function setHasSellerSignedOff($hasSellerSignedOff)
  {
    $this->hasSellerSignedOff = $hasSellerSignedOff;
  }
  public function getHasSellerSignedOff()
  {
    return $this->hasSellerSignedOff;
  }
  public function setIsRenegotiating($isRenegotiating)
  {
    $this->isRenegotiating = $isRenegotiating;
  }
  public function getIsRenegotiating()
  {
    return $this->isRenegotiating;
  }
  public function setIsSetupComplete($isSetupComplete)
  {
    $this->isSetupComplete = $isSetupComplete;
  }
  public function getIsSetupComplete()
  {
    return $this->isSetupComplete;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  public function getLabels()
  {
    return $this->labels;
  }
  public function setLastUpdaterOrCommentorRole($lastUpdaterOrCommentorRole)
  {
    $this->lastUpdaterOrCommentorRole = $lastUpdaterOrCommentorRole;
  }
  public function getLastUpdaterOrCommentorRole()
  {
    return $this->lastUpdaterOrCommentorRole;
  }
  public function setLastUpdaterRole($lastUpdaterRole)
  {
    $this->lastUpdaterRole = $lastUpdaterRole;
  }
  public function getLastUpdaterRole()
  {
    return $this->lastUpdaterRole;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setOrderId($orderId)
  {
    $this->orderId = $orderId;
  }
  public function getOrderId()
  {
    return $this->orderId;
  }
  public function setOrderState($orderState)
  {
    $this->orderState = $orderState;
  }
  public function getOrderState()
  {
    return $this->orderState;
  }
  public function setOriginatorRole($originatorRole)
  {
    $this->originatorRole = $originatorRole;
  }
  public function getOriginatorRole()
  {
    return $this->originatorRole;
  }
  public function setRevisionNumber($revisionNumber)
  {
    $this->revisionNumber = $revisionNumber;
  }
  public function getRevisionNumber()
  {
    return $this->revisionNumber;
  }
  public function setRevisionTimeMs($revisionTimeMs)
  {
    $this->revisionTimeMs = $revisionTimeMs;
  }
  public function getRevisionTimeMs()
  {
    return $this->revisionTimeMs;
  }
  public function setSeller(Google_Service_AdExchangeBuyer_Seller $seller)
  {
    $this->seller = $seller;
  }
  public function getSeller()
  {
    return $this->seller;
  }
  public function setSellerContacts($sellerContacts)
  {
    $this->sellerContacts = $sellerContacts;
  }
  public function getSellerContacts()
  {
    return $this->sellerContacts;
  }
}

class Google_Service_AdExchangeBuyer_MoneyDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $currencyCode;
  public $micros;


  public function setCurrencyCode($currencyCode)
  {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode()
  {
    return $this->currencyCode;
  }
  public function setMicros($micros)
  {
    $this->micros = $micros;
  }
  public function getMicros()
  {
    return $this->micros;
  }
}

class Google_Service_AdExchangeBuyer_NegotiationDto extends Google_Collection
{
  protected $collection_key = 'sellerEmailContacts';
  protected $internal_gapi_mappings = array(
  );
  protected $billedBuyerType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $billedBuyerDataType = '';
  protected $buyerType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $buyerDataType = '';
  public $buyerEmailContacts;
  public $dealType;
  public $externalDealId;
  public $kind;
  public $labelNames;
  public $negotiationId;
  protected $negotiationRoundsType = 'Google_Service_AdExchangeBuyer_NegotiationRoundDto';
  protected $negotiationRoundsDataType = 'array';
  public $negotiationState;
  public $offerId;
  protected $sellerType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $sellerDataType = '';
  public $sellerEmailContacts;
  protected $statsType = 'Google_Service_AdExchangeBuyer_StatsDto';
  protected $statsDataType = '';
  public $status;


  public function setBilledBuyer(Google_Service_AdExchangeBuyer_DealPartyDto $billedBuyer)
  {
    $this->billedBuyer = $billedBuyer;
  }
  public function getBilledBuyer()
  {
    return $this->billedBuyer;
  }
  public function setBuyer(Google_Service_AdExchangeBuyer_DealPartyDto $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setBuyerEmailContacts($buyerEmailContacts)
  {
    $this->buyerEmailContacts = $buyerEmailContacts;
  }
  public function getBuyerEmailContacts()
  {
    return $this->buyerEmailContacts;
  }
  public function setDealType($dealType)
  {
    $this->dealType = $dealType;
  }
  public function getDealType()
  {
    return $this->dealType;
  }
  public function setExternalDealId($externalDealId)
  {
    $this->externalDealId = $externalDealId;
  }
  public function getExternalDealId()
  {
    return $this->externalDealId;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLabelNames($labelNames)
  {
    $this->labelNames = $labelNames;
  }
  public function getLabelNames()
  {
    return $this->labelNames;
  }
  public function setNegotiationId($negotiationId)
  {
    $this->negotiationId = $negotiationId;
  }
  public function getNegotiationId()
  {
    return $this->negotiationId;
  }
  public function setNegotiationRounds($negotiationRounds)
  {
    $this->negotiationRounds = $negotiationRounds;
  }
  public function getNegotiationRounds()
  {
    return $this->negotiationRounds;
  }
  public function setNegotiationState($negotiationState)
  {
    $this->negotiationState = $negotiationState;
  }
  public function getNegotiationState()
  {
    return $this->negotiationState;
  }
  public function setOfferId($offerId)
  {
    $this->offerId = $offerId;
  }
  public function getOfferId()
  {
    return $this->offerId;
  }
  public function setSeller(Google_Service_AdExchangeBuyer_DealPartyDto $seller)
  {
    $this->seller = $seller;
  }
  public function getSeller()
  {
    return $this->seller;
  }
  public function setSellerEmailContacts($sellerEmailContacts)
  {
    $this->sellerEmailContacts = $sellerEmailContacts;
  }
  public function getSellerEmailContacts()
  {
    return $this->sellerEmailContacts;
  }
  public function setStats(Google_Service_AdExchangeBuyer_StatsDto $stats)
  {
    $this->stats = $stats;
  }
  public function getStats()
  {
    return $this->stats;
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

class Google_Service_AdExchangeBuyer_NegotiationRoundDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $action;
  public $dbmPartnerId;
  protected $editHistoryType = 'Google_Service_AdExchangeBuyer_EditHistoryDto';
  protected $editHistoryDataType = '';
  public $kind;
  public $negotiationId;
  public $notes;
  public $originatorRole;
  public $roundNumber;
  protected $termsType = 'Google_Service_AdExchangeBuyer_TermsDto';
  protected $termsDataType = '';


  public function setAction($action)
  {
    $this->action = $action;
  }
  public function getAction()
  {
    return $this->action;
  }
  public function setDbmPartnerId($dbmPartnerId)
  {
    $this->dbmPartnerId = $dbmPartnerId;
  }
  public function getDbmPartnerId()
  {
    return $this->dbmPartnerId;
  }
  public function setEditHistory(Google_Service_AdExchangeBuyer_EditHistoryDto $editHistory)
  {
    $this->editHistory = $editHistory;
  }
  public function getEditHistory()
  {
    return $this->editHistory;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setNegotiationId($negotiationId)
  {
    $this->negotiationId = $negotiationId;
  }
  public function getNegotiationId()
  {
    return $this->negotiationId;
  }
  public function setNotes($notes)
  {
    $this->notes = $notes;
  }
  public function getNotes()
  {
    return $this->notes;
  }
  public function setOriginatorRole($originatorRole)
  {
    $this->originatorRole = $originatorRole;
  }
  public function getOriginatorRole()
  {
    return $this->originatorRole;
  }
  public function setRoundNumber($roundNumber)
  {
    $this->roundNumber = $roundNumber;
  }
  public function getRoundNumber()
  {
    return $this->roundNumber;
  }
  public function setTerms(Google_Service_AdExchangeBuyer_TermsDto $terms)
  {
    $this->terms = $terms;
  }
  public function getTerms()
  {
    return $this->terms;
  }
}

class Google_Service_AdExchangeBuyer_OfferDto extends Google_Collection
{
  protected $collection_key = 'openToDealParties';
  protected $internal_gapi_mappings = array(
  );
  public $anonymous;
  protected $billedBuyerType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $billedBuyerDataType = '';
  protected $closedToDealPartiesType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $closedToDealPartiesDataType = 'array';
  protected $creatorType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $creatorDataType = '';
  public $emailContacts;
  public $isOpen;
  public $kind;
  public $labelNames;
  public $offerId;
  public $offerState;
  protected $openToDealPartiesType = 'Google_Service_AdExchangeBuyer_DealPartyDto';
  protected $openToDealPartiesDataType = 'array';
  public $pointOfContact;
  public $status;
  protected $termsType = 'Google_Service_AdExchangeBuyer_TermsDto';
  protected $termsDataType = '';


  public function setAnonymous($anonymous)
  {
    $this->anonymous = $anonymous;
  }
  public function getAnonymous()
  {
    return $this->anonymous;
  }
  public function setBilledBuyer(Google_Service_AdExchangeBuyer_DealPartyDto $billedBuyer)
  {
    $this->billedBuyer = $billedBuyer;
  }
  public function getBilledBuyer()
  {
    return $this->billedBuyer;
  }
  public function setClosedToDealParties($closedToDealParties)
  {
    $this->closedToDealParties = $closedToDealParties;
  }
  public function getClosedToDealParties()
  {
    return $this->closedToDealParties;
  }
  public function setCreator(Google_Service_AdExchangeBuyer_DealPartyDto $creator)
  {
    $this->creator = $creator;
  }
  public function getCreator()
  {
    return $this->creator;
  }
  public function setEmailContacts($emailContacts)
  {
    $this->emailContacts = $emailContacts;
  }
  public function getEmailContacts()
  {
    return $this->emailContacts;
  }
  public function setIsOpen($isOpen)
  {
    $this->isOpen = $isOpen;
  }
  public function getIsOpen()
  {
    return $this->isOpen;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLabelNames($labelNames)
  {
    $this->labelNames = $labelNames;
  }
  public function getLabelNames()
  {
    return $this->labelNames;
  }
  public function setOfferId($offerId)
  {
    $this->offerId = $offerId;
  }
  public function getOfferId()
  {
    return $this->offerId;
  }
  public function setOfferState($offerState)
  {
    $this->offerState = $offerState;
  }
  public function getOfferState()
  {
    return $this->offerState;
  }
  public function setOpenToDealParties($openToDealParties)
  {
    $this->openToDealParties = $openToDealParties;
  }
  public function getOpenToDealParties()
  {
    return $this->openToDealParties;
  }
  public function setPointOfContact($pointOfContact)
  {
    $this->pointOfContact = $pointOfContact;
  }
  public function getPointOfContact()
  {
    return $this->pointOfContact;
  }
  public function setStatus($status)
  {
    $this->status = $status;
  }
  public function getStatus()
  {
    return $this->status;
  }
  public function setTerms(Google_Service_AdExchangeBuyer_TermsDto $terms)
  {
    $this->terms = $terms;
  }
  public function getTerms()
  {
    return $this->terms;
  }
}

class Google_Service_AdExchangeBuyer_PerformanceReport extends Google_Collection
{
  protected $collection_key = 'hostedMatchStatusRate';
  protected $internal_gapi_mappings = array(
  );
  public $bidRate;
  public $bidRequestRate;
  public $calloutStatusRate;
  public $cookieMatcherStatusRate;
  public $creativeStatusRate;
  public $filteredBidRate;
  public $hostedMatchStatusRate;
  public $inventoryMatchRate;
  public $kind;
  public $latency50thPercentile;
  public $latency85thPercentile;
  public $latency95thPercentile;
  public $noQuotaInRegion;
  public $outOfQuota;
  public $pixelMatchRequests;
  public $pixelMatchResponses;
  public $quotaConfiguredLimit;
  public $quotaThrottledLimit;
  public $region;
  public $successfulRequestRate;
  public $timestamp;
  public $unsuccessfulRequestRate;


  public function setBidRate($bidRate)
  {
    $this->bidRate = $bidRate;
  }
  public function getBidRate()
  {
    return $this->bidRate;
  }
  public function setBidRequestRate($bidRequestRate)
  {
    $this->bidRequestRate = $bidRequestRate;
  }
  public function getBidRequestRate()
  {
    return $this->bidRequestRate;
  }
  public function setCalloutStatusRate($calloutStatusRate)
  {
    $this->calloutStatusRate = $calloutStatusRate;
  }
  public function getCalloutStatusRate()
  {
    return $this->calloutStatusRate;
  }
  public function setCookieMatcherStatusRate($cookieMatcherStatusRate)
  {
    $this->cookieMatcherStatusRate = $cookieMatcherStatusRate;
  }
  public function getCookieMatcherStatusRate()
  {
    return $this->cookieMatcherStatusRate;
  }
  public function setCreativeStatusRate($creativeStatusRate)
  {
    $this->creativeStatusRate = $creativeStatusRate;
  }
  public function getCreativeStatusRate()
  {
    return $this->creativeStatusRate;
  }
  public function setFilteredBidRate($filteredBidRate)
  {
    $this->filteredBidRate = $filteredBidRate;
  }
  public function getFilteredBidRate()
  {
    return $this->filteredBidRate;
  }
  public function setHostedMatchStatusRate($hostedMatchStatusRate)
  {
    $this->hostedMatchStatusRate = $hostedMatchStatusRate;
  }
  public function getHostedMatchStatusRate()
  {
    return $this->hostedMatchStatusRate;
  }
  public function setInventoryMatchRate($inventoryMatchRate)
  {
    $this->inventoryMatchRate = $inventoryMatchRate;
  }
  public function getInventoryMatchRate()
  {
    return $this->inventoryMatchRate;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLatency50thPercentile($latency50thPercentile)
  {
    $this->latency50thPercentile = $latency50thPercentile;
  }
  public function getLatency50thPercentile()
  {
    return $this->latency50thPercentile;
  }
  public function setLatency85thPercentile($latency85thPercentile)
  {
    $this->latency85thPercentile = $latency85thPercentile;
  }
  public function getLatency85thPercentile()
  {
    return $this->latency85thPercentile;
  }
  public function setLatency95thPercentile($latency95thPercentile)
  {
    $this->latency95thPercentile = $latency95thPercentile;
  }
  public function getLatency95thPercentile()
  {
    return $this->latency95thPercentile;
  }
  public function setNoQuotaInRegion($noQuotaInRegion)
  {
    $this->noQuotaInRegion = $noQuotaInRegion;
  }
  public function getNoQuotaInRegion()
  {
    return $this->noQuotaInRegion;
  }
  public function setOutOfQuota($outOfQuota)
  {
    $this->outOfQuota = $outOfQuota;
  }
  public function getOutOfQuota()
  {
    return $this->outOfQuota;
  }
  public function setPixelMatchRequests($pixelMatchRequests)
  {
    $this->pixelMatchRequests = $pixelMatchRequests;
  }
  public function getPixelMatchRequests()
  {
    return $this->pixelMatchRequests;
  }
  public function setPixelMatchResponses($pixelMatchResponses)
  {
    $this->pixelMatchResponses = $pixelMatchResponses;
  }
  public function getPixelMatchResponses()
  {
    return $this->pixelMatchResponses;
  }
  public function setQuotaConfiguredLimit($quotaConfiguredLimit)
  {
    $this->quotaConfiguredLimit = $quotaConfiguredLimit;
  }
  public function getQuotaConfiguredLimit()
  {
    return $this->quotaConfiguredLimit;
  }
  public function setQuotaThrottledLimit($quotaThrottledLimit)
  {
    $this->quotaThrottledLimit = $quotaThrottledLimit;
  }
  public function getQuotaThrottledLimit()
  {
    return $this->quotaThrottledLimit;
  }
  public function setRegion($region)
  {
    $this->region = $region;
  }
  public function getRegion()
  {
    return $this->region;
  }
  public function setSuccessfulRequestRate($successfulRequestRate)
  {
    $this->successfulRequestRate = $successfulRequestRate;
  }
  public function getSuccessfulRequestRate()
  {
    return $this->successfulRequestRate;
  }
  public function setTimestamp($timestamp)
  {
    $this->timestamp = $timestamp;
  }
  public function getTimestamp()
  {
    return $this->timestamp;
  }
  public function setUnsuccessfulRequestRate($unsuccessfulRequestRate)
  {
    $this->unsuccessfulRequestRate = $unsuccessfulRequestRate;
  }
  public function getUnsuccessfulRequestRate()
  {
    return $this->unsuccessfulRequestRate;
  }
}

class Google_Service_AdExchangeBuyer_PerformanceReportList extends Google_Collection
{
  protected $collection_key = 'performanceReport';
  protected $internal_gapi_mappings = array(
  );
  public $kind;
  protected $performanceReportType = 'Google_Service_AdExchangeBuyer_PerformanceReport';
  protected $performanceReportDataType = 'array';


  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setPerformanceReport($performanceReport)
  {
    $this->performanceReport = $performanceReport;
  }
  public function getPerformanceReport()
  {
    return $this->performanceReport;
  }
}

class Google_Service_AdExchangeBuyer_PretargetingConfig extends Google_Collection
{
  protected $collection_key = 'verticals';
  protected $internal_gapi_mappings = array(
  );
  public $billingId;
  public $configId;
  public $configName;
  public $creativeType;
  protected $dimensionsType = 'Google_Service_AdExchangeBuyer_PretargetingConfigDimensions';
  protected $dimensionsDataType = 'array';
  public $excludedContentLabels;
  public $excludedGeoCriteriaIds;
  protected $excludedPlacementsType = 'Google_Service_AdExchangeBuyer_PretargetingConfigExcludedPlacements';
  protected $excludedPlacementsDataType = 'array';
  public $excludedUserLists;
  public $excludedVerticals;
  public $geoCriteriaIds;
  public $isActive;
  public $kind;
  public $languages;
  public $mobileCarriers;
  public $mobileDevices;
  public $mobileOperatingSystemVersions;
  protected $placementsType = 'Google_Service_AdExchangeBuyer_PretargetingConfigPlacements';
  protected $placementsDataType = 'array';
  public $platforms;
  public $supportedCreativeAttributes;
  public $userLists;
  public $vendorTypes;
  public $verticals;


  public function setBillingId($billingId)
  {
    $this->billingId = $billingId;
  }
  public function getBillingId()
  {
    return $this->billingId;
  }
  public function setConfigId($configId)
  {
    $this->configId = $configId;
  }
  public function getConfigId()
  {
    return $this->configId;
  }
  public function setConfigName($configName)
  {
    $this->configName = $configName;
  }
  public function getConfigName()
  {
    return $this->configName;
  }
  public function setCreativeType($creativeType)
  {
    $this->creativeType = $creativeType;
  }
  public function getCreativeType()
  {
    return $this->creativeType;
  }
  public function setDimensions($dimensions)
  {
    $this->dimensions = $dimensions;
  }
  public function getDimensions()
  {
    return $this->dimensions;
  }
  public function setExcludedContentLabels($excludedContentLabels)
  {
    $this->excludedContentLabels = $excludedContentLabels;
  }
  public function getExcludedContentLabels()
  {
    return $this->excludedContentLabels;
  }
  public function setExcludedGeoCriteriaIds($excludedGeoCriteriaIds)
  {
    $this->excludedGeoCriteriaIds = $excludedGeoCriteriaIds;
  }
  public function getExcludedGeoCriteriaIds()
  {
    return $this->excludedGeoCriteriaIds;
  }
  public function setExcludedPlacements($excludedPlacements)
  {
    $this->excludedPlacements = $excludedPlacements;
  }
  public function getExcludedPlacements()
  {
    return $this->excludedPlacements;
  }
  public function setExcludedUserLists($excludedUserLists)
  {
    $this->excludedUserLists = $excludedUserLists;
  }
  public function getExcludedUserLists()
  {
    return $this->excludedUserLists;
  }
  public function setExcludedVerticals($excludedVerticals)
  {
    $this->excludedVerticals = $excludedVerticals;
  }
  public function getExcludedVerticals()
  {
    return $this->excludedVerticals;
  }
  public function setGeoCriteriaIds($geoCriteriaIds)
  {
    $this->geoCriteriaIds = $geoCriteriaIds;
  }
  public function getGeoCriteriaIds()
  {
    return $this->geoCriteriaIds;
  }
  public function setIsActive($isActive)
  {
    $this->isActive = $isActive;
  }
  public function getIsActive()
  {
    return $this->isActive;
  }
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  public function getKind()
  {
    return $this->kind;
  }
  public function setLanguages($languages)
  {
    $this->languages = $languages;
  }
  public function getLanguages()
  {
    return $this->languages;
  }
  public function setMobileCarriers($mobileCarriers)
  {
    $this->mobileCarriers = $mobileCarriers;
  }
  public function getMobileCarriers()
  {
    return $this->mobileCarriers;
  }
  public function setMobileDevices($mobileDevices)
  {
    $this->mobileDevices = $mobileDevices;
  }
  public function getMobileDevices()
  {
    return $this->mobileDevices;
  }
  public function setMobileOperatingSystemVersions($mobileOperatingSystemVersions)
  {
    $this->mobileOperatingSystemVersions = $mobileOperatingSystemVersions;
  }
  public function getMobileOperatingSystemVersions()
  {
    return $this->mobileOperatingSystemVersions;
  }
  public function setPlacements($placements)
  {
    $this->placements = $placements;
  }
  public function getPlacements()
  {
    return $this->placements;
  }
  public function setPlatforms($platforms)
  {
    $this->platforms = $platforms;
  }
  public function getPlatforms()
  {
    return $this->platforms;
  }
  public function setSupportedCreativeAttributes($supportedCreativeAttributes)
  {
    $this->supportedCreativeAttributes = $supportedCreativeAttributes;
  }
  public function getSupportedCreativeAttributes()
  {
    return $this->supportedCreativeAttributes;
  }
  public function setUserLists($userLists)
  {
    $this->userLists = $userLists;
  }
  public function getUserLists()
  {
    return $this->userLists;
  }
  public function setVendorTypes($vendorTypes)
  {
    $this->vendorTypes = $vendorTypes;
  }
  public function getVendorTypes()
  {
    return $this->vendorTypes;
  }
  public function setVerticals($verticals)
  {
    $this->verticals = $verticals;
  }
  public function getVerticals()
  {
    return $this->verticals;
  }
}

class Google_Service_AdExchangeBuyer_PretargetingConfigDimensions extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
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

class Google_Service_AdExchangeBuyer_PretargetingConfigExcludedPlacements extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $token;
  public $type;


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

class Google_Service_AdExchangeBuyer_PretargetingConfigList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  protected $itemsType = 'Google_Service_AdExchangeBuyer_PretargetingConfig';
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

class Google_Service_AdExchangeBuyer_PretargetingConfigPlacements extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $token;
  public $type;


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

class Google_Service_AdExchangeBuyer_Price extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $amountMicros;
  public $currencyCode;


  public function setAmountMicros($amountMicros)
  {
    $this->amountMicros = $amountMicros;
  }
  public function getAmountMicros()
  {
    return $this->amountMicros;
  }
  public function setCurrencyCode($currencyCode)
  {
    $this->currencyCode = $currencyCode;
  }
  public function getCurrencyCode()
  {
    return $this->currencyCode;
  }
}

class Google_Service_AdExchangeBuyer_PricePerBuyer extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $buyerType = 'Google_Service_AdExchangeBuyer_Buyer';
  protected $buyerDataType = '';
  protected $priceType = 'Google_Service_AdExchangeBuyer_Price';
  protected $priceDataType = '';


  public function setBuyer(Google_Service_AdExchangeBuyer_Buyer $buyer)
  {
    $this->buyer = $buyer;
  }
  public function getBuyer()
  {
    return $this->buyer;
  }
  public function setPrice(Google_Service_AdExchangeBuyer_Price $price)
  {
    $this->price = $price;
  }
  public function getPrice()
  {
    return $this->price;
  }
}

class Google_Service_AdExchangeBuyer_PrivateData extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $referenceId;
  public $referencePayload;


  public function setReferenceId($referenceId)
  {
    $this->referenceId = $referenceId;
  }
  public function getReferenceId()
  {
    return $this->referenceId;
  }
  public function setReferencePayload($referencePayload)
  {
    $this->referencePayload = $referencePayload;
  }
  public function getReferencePayload()
  {
    return $this->referencePayload;
  }
}

class Google_Service_AdExchangeBuyer_RuleKeyValuePair extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $keyName;
  public $value;


  public function setKeyName($keyName)
  {
    $this->keyName = $keyName;
  }
  public function getKeyName()
  {
    return $this->keyName;
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

class Google_Service_AdExchangeBuyer_Seller extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $accountId;
  public $subAccountId;


  public function setAccountId($accountId)
  {
    $this->accountId = $accountId;
  }
  public function getAccountId()
  {
    return $this->accountId;
  }
  public function setSubAccountId($subAccountId)
  {
    $this->subAccountId = $subAccountId;
  }
  public function getSubAccountId()
  {
    return $this->subAccountId;
  }
}

class Google_Service_AdExchangeBuyer_SharedTargeting extends Google_Collection
{
  protected $collection_key = 'inclusions';
  protected $internal_gapi_mappings = array(
  );
  protected $exclusionsType = 'Google_Service_AdExchangeBuyer_TargetingValue';
  protected $exclusionsDataType = 'array';
  protected $inclusionsType = 'Google_Service_AdExchangeBuyer_TargetingValue';
  protected $inclusionsDataType = 'array';
  public $key;


  public function setExclusions($exclusions)
  {
    $this->exclusions = $exclusions;
  }
  public function getExclusions()
  {
    return $this->exclusions;
  }
  public function setInclusions($inclusions)
  {
    $this->inclusions = $inclusions;
  }
  public function getInclusions()
  {
    return $this->inclusions;
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

class Google_Service_AdExchangeBuyer_StatsDto extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $bids;
  public $goodBids;
  public $impressions;
  public $requests;
  protected $revenueType = 'Google_Service_AdExchangeBuyer_MoneyDto';
  protected $revenueDataType = '';
  protected $spendType = 'Google_Service_AdExchangeBuyer_MoneyDto';
  protected $spendDataType = '';


  public function setBids($bids)
  {
    $this->bids = $bids;
  }
  public function getBids()
  {
    return $this->bids;
  }
  public function setGoodBids($goodBids)
  {
    $this->goodBids = $goodBids;
  }
  public function getGoodBids()
  {
    return $this->goodBids;
  }
  public function setImpressions($impressions)
  {
    $this->impressions = $impressions;
  }
  public function getImpressions()
  {
    return $this->impressions;
  }
  public function setRequests($requests)
  {
    $this->requests = $requests;
  }
  public function getRequests()
  {
    return $this->requests;
  }
  public function setRevenue(Google_Service_AdExchangeBuyer_MoneyDto $revenue)
  {
    $this->revenue = $revenue;
  }
  public function getRevenue()
  {
    return $this->revenue;
  }
  public function setSpend(Google_Service_AdExchangeBuyer_MoneyDto $spend)
  {
    $this->spend = $spend;
  }
  public function getSpend()
  {
    return $this->spend;
  }
}

class Google_Service_AdExchangeBuyer_TargetingValue extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  protected $creativeSizeValueType = 'Google_Service_AdExchangeBuyer_TargetingValueCreativeSize';
  protected $creativeSizeValueDataType = '';
  protected $dayPartTargetingValueType = 'Google_Service_AdExchangeBuyer_TargetingValueDayPartTargeting';
  protected $dayPartTargetingValueDataType = '';
  public $longValue;
  public $stringValue;


  public function setCreativeSizeValue(Google_Service_AdExchangeBuyer_TargetingValueCreativeSize $creativeSizeValue)
  {
    $this->creativeSizeValue = $creativeSizeValue;
  }
  public function getCreativeSizeValue()
  {
    return $this->creativeSizeValue;
  }
  public function setDayPartTargetingValue(Google_Service_AdExchangeBuyer_TargetingValueDayPartTargeting $dayPartTargetingValue)
  {
    $this->dayPartTargetingValue = $dayPartTargetingValue;
  }
  public function getDayPartTargetingValue()
  {
    return $this->dayPartTargetingValue;
  }
  public function setLongValue($longValue)
  {
    $this->longValue = $longValue;
  }
  public function getLongValue()
  {
    return $this->longValue;
  }
  public function setStringValue($stringValue)
  {
    $this->stringValue = $stringValue;
  }
  public function getStringValue()
  {
    return $this->stringValue;
  }
}

class Google_Service_AdExchangeBuyer_TargetingValueCreativeSize extends Google_Collection
{
  protected $collection_key = 'companionSizes';
  protected $internal_gapi_mappings = array(
  );
  protected $companionSizesType = 'Google_Service_AdExchangeBuyer_TargetingValueSize';
  protected $companionSizesDataType = 'array';
  public $creativeSizeType;
  protected $sizeType = 'Google_Service_AdExchangeBuyer_TargetingValueSize';
  protected $sizeDataType = '';


  public function setCompanionSizes($companionSizes)
  {
    $this->companionSizes = $companionSizes;
  }
  public function getCompanionSizes()
  {
    return $this->companionSizes;
  }
  public function setCreativeSizeType($creativeSizeType)
  {
    $this->creativeSizeType = $creativeSizeType;
  }
  public function getCreativeSizeType()
  {
    return $this->creativeSizeType;
  }
  public function setSize(Google_Service_AdExchangeBuyer_TargetingValueSize $size)
  {
    $this->size = $size;
  }
  public function getSize()
  {
    return $this->size;
  }
}

class Google_Service_AdExchangeBuyer_TargetingValueDayPartTargeting extends Google_Collection
{
  protected $collection_key = 'dayParts';
  protected $internal_gapi_mappings = array(
  );
  protected $dayPartsType = 'Google_Service_AdExchangeBuyer_TargetingValueDayPartTargetingDayPart';
  protected $dayPartsDataType = 'array';
  public $timeZoneType;


  public function setDayParts($dayParts)
  {
    $this->dayParts = $dayParts;
  }
  public function getDayParts()
  {
    return $this->dayParts;
  }
  public function setTimeZoneType($timeZoneType)
  {
    $this->timeZoneType = $timeZoneType;
  }
  public function getTimeZoneType()
  {
    return $this->timeZoneType;
  }
}

class Google_Service_AdExchangeBuyer_TargetingValueDayPartTargetingDayPart extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $dayOfWeek;
  public $endHour;
  public $endMinute;
  public $startHour;
  public $startMinute;


  public function setDayOfWeek($dayOfWeek)
  {
    $this->dayOfWeek = $dayOfWeek;
  }
  public function getDayOfWeek()
  {
    return $this->dayOfWeek;
  }
  public function setEndHour($endHour)
  {
    $this->endHour = $endHour;
  }
  public function getEndHour()
  {
    return $this->endHour;
  }
  public function setEndMinute($endMinute)
  {
    $this->endMinute = $endMinute;
  }
  public function getEndMinute()
  {
    return $this->endMinute;
  }
  public function setStartHour($startHour)
  {
    $this->startHour = $startHour;
  }
  public function getStartHour()
  {
    return $this->startHour;
  }
  public function setStartMinute($startMinute)
  {
    $this->startMinute = $startMinute;
  }
  public function getStartMinute()
  {
    return $this->startMinute;
  }
}

class Google_Service_AdExchangeBuyer_TargetingValueSize extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $height;
  public $width;


  public function setHeight($height)
  {
    $this->height = $height;
  }
  public function getHeight()
  {
    return $this->height;
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

class Google_Service_AdExchangeBuyer_TermsDto extends Google_Collection
{
  protected $collection_key = 'urls';
  protected $internal_gapi_mappings = array(
  );
  protected $adSlotsType = 'Google_Service_AdExchangeBuyer_AdSlotDto';
  protected $adSlotsDataType = 'array';
  protected $advertisersType = 'Google_Service_AdExchangeBuyer_AdvertiserDto';
  protected $advertisersDataType = 'array';
  protected $audienceSegmentType = 'Google_Service_AdExchangeBuyer_AudienceSegment';
  protected $audienceSegmentDataType = '';
  public $audienceSegmentDescription;
  public $billingTerms;
  public $buyerBillingType;
  protected $cpmType = 'Google_Service_AdExchangeBuyer_MoneyDto';
  protected $cpmDataType = '';
  public $creativeBlockingLevel;
  public $creativeReviewPolicy;
  protected $dealPremiumType = 'Google_Service_AdExchangeBuyer_MoneyDto';
  protected $dealPremiumDataType = '';
  public $description;
  public $descriptiveName;
  protected $endDateType = 'Google_Service_AdExchangeBuyer_DateTime';
  protected $endDateDataType = '';
  public $estimatedImpressionsPerDay;
  protected $estimatedSpendType = 'Google_Service_AdExchangeBuyer_MoneyDto';
  protected $estimatedSpendDataType = '';
  public $finalizeAutomatically;
  protected $inventorySegmentTargetingType = 'Google_Service_AdExchangeBuyer_InventorySegmentTargeting';
  protected $inventorySegmentTargetingDataType = '';
  public $isReservation;
  public $minimumSpendMicros;
  public $minimumTrueLooks;
  public $monetizerType;
  public $semiTransparent;
  protected $startDateType = 'Google_Service_AdExchangeBuyer_DateTime';
  protected $startDateDataType = '';
  public $targetByDealId;
  public $targetingAllAdSlots;
  public $termsAttributes;
  public $urls;


  public function setAdSlots($adSlots)
  {
    $this->adSlots = $adSlots;
  }
  public function getAdSlots()
  {
    return $this->adSlots;
  }
  public function setAdvertisers($advertisers)
  {
    $this->advertisers = $advertisers;
  }
  public function getAdvertisers()
  {
    return $this->advertisers;
  }
  public function setAudienceSegment(Google_Service_AdExchangeBuyer_AudienceSegment $audienceSegment)
  {
    $this->audienceSegment = $audienceSegment;
  }
  public function getAudienceSegment()
  {
    return $this->audienceSegment;
  }
  public function setAudienceSegmentDescription($audienceSegmentDescription)
  {
    $this->audienceSegmentDescription = $audienceSegmentDescription;
  }
  public function getAudienceSegmentDescription()
  {
    return $this->audienceSegmentDescription;
  }
  public function setBillingTerms($billingTerms)
  {
    $this->billingTerms = $billingTerms;
  }
  public function getBillingTerms()
  {
    return $this->billingTerms;
  }
  public function setBuyerBillingType($buyerBillingType)
  {
    $this->buyerBillingType = $buyerBillingType;
  }
  public function getBuyerBillingType()
  {
    return $this->buyerBillingType;
  }
  public function setCpm(Google_Service_AdExchangeBuyer_MoneyDto $cpm)
  {
    $this->cpm = $cpm;
  }
  public function getCpm()
  {
    return $this->cpm;
  }
  public function setCreativeBlockingLevel($creativeBlockingLevel)
  {
    $this->creativeBlockingLevel = $creativeBlockingLevel;
  }
  public function getCreativeBlockingLevel()
  {
    return $this->creativeBlockingLevel;
  }
  public function setCreativeReviewPolicy($creativeReviewPolicy)
  {
    $this->creativeReviewPolicy = $creativeReviewPolicy;
  }
  public function getCreativeReviewPolicy()
  {
    return $this->creativeReviewPolicy;
  }
  public function setDealPremium(Google_Service_AdExchangeBuyer_MoneyDto $dealPremium)
  {
    $this->dealPremium = $dealPremium;
  }
  public function getDealPremium()
  {
    return $this->dealPremium;
  }
  public function setDescription($description)
  {
    $this->description = $description;
  }
  public function getDescription()
  {
    return $this->description;
  }
  public function setDescriptiveName($descriptiveName)
  {
    $this->descriptiveName = $descriptiveName;
  }
  public function getDescriptiveName()
  {
    return $this->descriptiveName;
  }
  public function setEndDate(Google_Service_AdExchangeBuyer_DateTime $endDate)
  {
    $this->endDate = $endDate;
  }
  public function getEndDate()
  {
    return $this->endDate;
  }
  public function setEstimatedImpressionsPerDay($estimatedImpressionsPerDay)
  {
    $this->estimatedImpressionsPerDay = $estimatedImpressionsPerDay;
  }
  public function getEstimatedImpressionsPerDay()
  {
    return $this->estimatedImpressionsPerDay;
  }
  public function setEstimatedSpend(Google_Service_AdExchangeBuyer_MoneyDto $estimatedSpend)
  {
    $this->estimatedSpend = $estimatedSpend;
  }
  public function getEstimatedSpend()
  {
    return $this->estimatedSpend;
  }
  public function setFinalizeAutomatically($finalizeAutomatically)
  {
    $this->finalizeAutomatically = $finalizeAutomatically;
  }
  public function getFinalizeAutomatically()
  {
    return $this->finalizeAutomatically;
  }
  public function setInventorySegmentTargeting(Google_Service_AdExchangeBuyer_InventorySegmentTargeting $inventorySegmentTargeting)
  {
    $this->inventorySegmentTargeting = $inventorySegmentTargeting;
  }
  public function getInventorySegmentTargeting()
  {
    return $this->inventorySegmentTargeting;
  }
  public function setIsReservation($isReservation)
  {
    $this->isReservation = $isReservation;
  }
  public function getIsReservation()
  {
    return $this->isReservation;
  }
  public function setMinimumSpendMicros($minimumSpendMicros)
  {
    $this->minimumSpendMicros = $minimumSpendMicros;
  }
  public function getMinimumSpendMicros()
  {
    return $this->minimumSpendMicros;
  }
  public function setMinimumTrueLooks($minimumTrueLooks)
  {
    $this->minimumTrueLooks = $minimumTrueLooks;
  }
  public function getMinimumTrueLooks()
  {
    return $this->minimumTrueLooks;
  }
  public function setMonetizerType($monetizerType)
  {
    $this->monetizerType = $monetizerType;
  }
  public function getMonetizerType()
  {
    return $this->monetizerType;
  }
  public function setSemiTransparent($semiTransparent)
  {
    $this->semiTransparent = $semiTransparent;
  }
  public function getSemiTransparent()
  {
    return $this->semiTransparent;
  }
  public function setStartDate(Google_Service_AdExchangeBuyer_DateTime $startDate)
  {
    $this->startDate = $startDate;
  }
  public function getStartDate()
  {
    return $this->startDate;
  }
  public function setTargetByDealId($targetByDealId)
  {
    $this->targetByDealId = $targetByDealId;
  }
  public function getTargetByDealId()
  {
    return $this->targetByDealId;
  }
  public function setTargetingAllAdSlots($targetingAllAdSlots)
  {
    $this->targetingAllAdSlots = $targetingAllAdSlots;
  }
  public function getTargetingAllAdSlots()
  {
    return $this->targetingAllAdSlots;
  }
  public function setTermsAttributes($termsAttributes)
  {
    $this->termsAttributes = $termsAttributes;
  }
  public function getTermsAttributes()
  {
    return $this->termsAttributes;
  }
  public function setUrls($urls)
  {
    $this->urls = $urls;
  }
  public function getUrls()
  {
    return $this->urls;
  }
}

class Google_Service_AdExchangeBuyer_WebPropertyDto extends Google_Collection
{
  protected $collection_key = 'siteUrls';
  protected $internal_gapi_mappings = array(
  );
  public $allowInterestTargetedAds;
  public $enabledForPreferredDeals;
  public $id;
  public $name;
  public $propertyCode;
  public $siteUrls;
  public $syndicationProduct;


  public function setAllowInterestTargetedAds($allowInterestTargetedAds)
  {
    $this->allowInterestTargetedAds = $allowInterestTargetedAds;
  }
  public function getAllowInterestTargetedAds()
  {
    return $this->allowInterestTargetedAds;
  }
  public function setEnabledForPreferredDeals($enabledForPreferredDeals)
  {
    $this->enabledForPreferredDeals = $enabledForPreferredDeals;
  }
  public function getEnabledForPreferredDeals()
  {
    return $this->enabledForPreferredDeals;
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
  public function setPropertyCode($propertyCode)
  {
    $this->propertyCode = $propertyCode;
  }
  public function getPropertyCode()
  {
    return $this->propertyCode;
  }
  public function setSiteUrls($siteUrls)
  {
    $this->siteUrls = $siteUrls;
  }
  public function getSiteUrls()
  {
    return $this->siteUrls;
  }
  public function setSyndicationProduct($syndicationProduct)
  {
    $this->syndicationProduct = $syndicationProduct;
  }
  public function getSyndicationProduct()
  {
    return $this->syndicationProduct;
  }
}
