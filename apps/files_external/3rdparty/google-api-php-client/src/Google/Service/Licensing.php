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
 * Service definition for Licensing (v1).
 *
 * <p>
 * Licensing API to view and manage license for your domain.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/google-apps/licensing/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Licensing extends Google_Service
{
  /** View and manage Google Apps licenses for your domain. */
  const APPS_LICENSING =
      "https://www.googleapis.com/auth/apps.licensing";

  public $licenseAssignments;
  

  /**
   * Constructs the internal representation of the Licensing service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'apps/licensing/v1/product/';
    $this->version = 'v1';
    $this->serviceName = 'licensing';

    $this->licenseAssignments = new Google_Service_Licensing_LicenseAssignments_Resource(
        $this,
        $this->serviceName,
        'licenseAssignments',
        array(
          'methods' => array(
            'delete' => array(
              'path' => '{productId}/sku/{skuId}/user/{userId}',
              'httpMethod' => 'DELETE',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'get' => array(
              'path' => '{productId}/sku/{skuId}/user/{userId}',
              'httpMethod' => 'GET',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'insert' => array(
              'path' => '{productId}/sku/{skuId}/user',
              'httpMethod' => 'POST',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'listForProduct' => array(
              'path' => '{productId}/users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
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
            ),'listForProductAndSku' => array(
              'path' => '{productId}/sku/{skuId}/users',
              'httpMethod' => 'GET',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'customerId' => array(
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
            ),'patch' => array(
              'path' => '{productId}/sku/{skuId}/user/{userId}',
              'httpMethod' => 'PATCH',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
              ),
            ),'update' => array(
              'path' => '{productId}/sku/{skuId}/user/{userId}',
              'httpMethod' => 'PUT',
              'parameters' => array(
                'productId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'skuId' => array(
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ),
                'userId' => array(
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
 * The "licenseAssignments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $licensingService = new Google_Service_Licensing(...);
 *   $licenseAssignments = $licensingService->licenseAssignments;
 *  </code>
 */
class Google_Service_Licensing_LicenseAssignments_Resource extends Google_Service_Resource
{

  /**
   * Revoke License. (licenseAssignments.delete)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku
   * @param string $userId email id or unique Id of the user
   * @param array $optParams Optional parameters.
   */
  public function delete($productId, $skuId, $userId, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('delete', array($params));
  }

  /**
   * Get license assignment of a particular product and sku for a user
   * (licenseAssignments.get)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku
   * @param string $userId email id or unique Id of the user
   * @param array $optParams Optional parameters.
   * @return Google_Service_Licensing_LicenseAssignment
   */
  public function get($productId, $skuId, $userId, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId);
    $params = array_merge($params, $optParams);
    return $this->call('get', array($params), "Google_Service_Licensing_LicenseAssignment");
  }

  /**
   * Assign License. (licenseAssignments.insert)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku
   * @param Google_LicenseAssignmentInsert $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Licensing_LicenseAssignment
   */
  public function insert($productId, $skuId, Google_Service_Licensing_LicenseAssignmentInsert $postBody, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('insert', array($params), "Google_Service_Licensing_LicenseAssignment");
  }

  /**
   * List license assignments for given product of the customer.
   * (licenseAssignments.listForProduct)
   *
   * @param string $productId Name for product
   * @param string $customerId CustomerId represents the customer for whom
   * licenseassignments are queried
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to fetch the next page.Optional. By default
   * server will return first page
   * @opt_param string maxResults Maximum number of campaigns to return at one
   * time. Must be positive. Optional. Default value is 100.
   * @return Google_Service_Licensing_LicenseAssignmentList
   */
  public function listForProduct($productId, $customerId, $optParams = array())
  {
    $params = array('productId' => $productId, 'customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('listForProduct', array($params), "Google_Service_Licensing_LicenseAssignmentList");
  }

  /**
   * List license assignments for given product and sku of the customer.
   * (licenseAssignments.listForProductAndSku)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku
   * @param string $customerId CustomerId represents the customer for whom
   * licenseassignments are queried
   * @param array $optParams Optional parameters.
   *
   * @opt_param string pageToken Token to fetch the next page.Optional. By default
   * server will return first page
   * @opt_param string maxResults Maximum number of campaigns to return at one
   * time. Must be positive. Optional. Default value is 100.
   * @return Google_Service_Licensing_LicenseAssignmentList
   */
  public function listForProductAndSku($productId, $skuId, $customerId, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'customerId' => $customerId);
    $params = array_merge($params, $optParams);
    return $this->call('listForProductAndSku', array($params), "Google_Service_Licensing_LicenseAssignmentList");
  }

  /**
   * Assign License. This method supports patch semantics.
   * (licenseAssignments.patch)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku for which license would be revoked
   * @param string $userId email id or unique Id of the user
   * @param Google_LicenseAssignment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Licensing_LicenseAssignment
   */
  public function patch($productId, $skuId, $userId, Google_Service_Licensing_LicenseAssignment $postBody, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('patch', array($params), "Google_Service_Licensing_LicenseAssignment");
  }

  /**
   * Assign License. (licenseAssignments.update)
   *
   * @param string $productId Name for product
   * @param string $skuId Name for sku for which license would be revoked
   * @param string $userId email id or unique Id of the user
   * @param Google_LicenseAssignment $postBody
   * @param array $optParams Optional parameters.
   * @return Google_Service_Licensing_LicenseAssignment
   */
  public function update($productId, $skuId, $userId, Google_Service_Licensing_LicenseAssignment $postBody, $optParams = array())
  {
    $params = array('productId' => $productId, 'skuId' => $skuId, 'userId' => $userId, 'postBody' => $postBody);
    $params = array_merge($params, $optParams);
    return $this->call('update', array($params), "Google_Service_Licensing_LicenseAssignment");
  }
}




class Google_Service_Licensing_LicenseAssignment extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $etags;
  public $kind;
  public $productId;
  public $selfLink;
  public $skuId;
  public $userId;


  public function setEtags($etags)
  {
    $this->etags = $etags;
  }
  public function getEtags()
  {
    return $this->etags;
  }
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
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  public function setSkuId($skuId)
  {
    $this->skuId = $skuId;
  }
  public function getSkuId()
  {
    return $this->skuId;
  }
  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
}

class Google_Service_Licensing_LicenseAssignmentInsert extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $userId;


  public function setUserId($userId)
  {
    $this->userId = $userId;
  }
  public function getUserId()
  {
    return $this->userId;
  }
}

class Google_Service_Licensing_LicenseAssignmentList extends Google_Collection
{
  protected $collection_key = 'items';
  protected $internal_gapi_mappings = array(
  );
  public $etag;
  protected $itemsType = 'Google_Service_Licensing_LicenseAssignment';
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
