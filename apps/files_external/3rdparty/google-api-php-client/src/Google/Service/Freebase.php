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
 * Service definition for Freebase (v1).
 *
 * <p>
 * Find Freebase entities using textual queries and other constraints.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/freebase/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Google_Service_Freebase extends Google_Service
{



  private $base_methods;

  /**
   * Constructs the internal representation of the Freebase service.
   *
   * @param Google_Client $client
   */
  public function __construct(Google_Client $client)
  {
    parent::__construct($client);
    $this->rootUrl = 'https://www.googleapis.com/';
    $this->servicePath = 'freebase/v1/';
    $this->version = 'v1';
    $this->serviceName = 'freebase';

    $this->base_methods = new Google_Service_Resource(
        $this,
        $this->serviceName,
        '',
        array(
          'methods' => array(
            'reconcile' => array(
              'path' => 'reconcile',
              'httpMethod' => 'GET',
              'parameters' => array(
                'lang' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'confidence' => array(
                  'location' => 'query',
                  'type' => 'number',
                ),
                'name' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'kind' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'prop' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'limit' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
              ),
            ),'search' => array(
              'path' => 'search',
              'httpMethod' => 'GET',
              'parameters' => array(
                'domain' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'help' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'query' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'scoring' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'cursor' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'prefixed' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'exact' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'mid' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'encode' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'type' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'as_of_time' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'stemmed' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'format' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'spell' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'with' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'lang' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'indent' => array(
                  'location' => 'query',
                  'type' => 'boolean',
                ),
                'filter' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'callback' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'without' => array(
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ),
                'limit' => array(
                  'location' => 'query',
                  'type' => 'integer',
                ),
                'output' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
                'mql_output' => array(
                  'location' => 'query',
                  'type' => 'string',
                ),
              ),
            ),
          )
        )
    );
  }
  /**
   * Reconcile entities to Freebase open data. (reconcile)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string lang Languages for names and values. First language is used
   * for display. Default is 'en'.
   * @opt_param float confidence Required confidence for a candidate to match.
   * Must be between .5 and 1.0
   * @opt_param string name Name of entity.
   * @opt_param string kind Classifications of entity e.g. type, category, title.
   * @opt_param string prop Property values for entity formatted as :
   * @opt_param int limit Maximum number of candidates to return.
   * @return Google_Service_Freebase_ReconcileGet
   */
  public function reconcile($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->base_methods->call('reconcile', array($params), "Google_Service_Freebase_ReconcileGet");
  }
  /**
   * Search Freebase open data. (search)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string domain Restrict to topics with this Freebase domain id.
   * @opt_param string help The keyword to request help on.
   * @opt_param string query Query term to search for.
   * @opt_param string scoring Relevance scoring algorithm to use.
   * @opt_param int cursor The cursor value to use for the next page of results.
   * @opt_param bool prefixed Prefix match against names and aliases.
   * @opt_param bool exact Query on exact name and keys only.
   * @opt_param string mid A mid to use instead of a query.
   * @opt_param string encode The encoding of the response. You can use this
   * parameter to enable html encoding.
   * @opt_param string type Restrict to topics with this Freebase type id.
   * @opt_param string as_of_time A mql as_of_time value to use with mql_output
   * queries.
   * @opt_param bool stemmed Query on stemmed names and aliases. May not be used
   * with prefixed.
   * @opt_param string format Structural format of the json response.
   * @opt_param string spell Request 'did you mean' suggestions
   * @opt_param string with A rule to match against.
   * @opt_param string lang The code of the language to run the query with.
   * Default is 'en'.
   * @opt_param bool indent Whether to indent the json results or not.
   * @opt_param string filter A filter to apply to the query.
   * @opt_param string callback JS method name for JSONP callbacks.
   * @opt_param string without A rule to not match against.
   * @opt_param int limit Maximum number of results to return.
   * @opt_param string output An output expression to request data from matches.
   * @opt_param string mql_output The MQL query to run againist the results to
   * extract more data.
   */
  public function search($optParams = array())
  {
    $params = array();
    $params = array_merge($params, $optParams);
    return $this->base_methods->call('search', array($params));
  }
}





class Google_Service_Freebase_ReconcileCandidate extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $confidence;
  public $lang;
  public $mid;
  public $name;
  protected $notableType = 'Google_Service_Freebase_ReconcileCandidateNotable';
  protected $notableDataType = '';


  public function setConfidence($confidence)
  {
    $this->confidence = $confidence;
  }
  public function getConfidence()
  {
    return $this->confidence;
  }
  public function setLang($lang)
  {
    $this->lang = $lang;
  }
  public function getLang()
  {
    return $this->lang;
  }
  public function setMid($mid)
  {
    $this->mid = $mid;
  }
  public function getMid()
  {
    return $this->mid;
  }
  public function setName($name)
  {
    $this->name = $name;
  }
  public function getName()
  {
    return $this->name;
  }
  public function setNotable(Google_Service_Freebase_ReconcileCandidateNotable $notable)
  {
    $this->notable = $notable;
  }
  public function getNotable()
  {
    return $this->notable;
  }
}

class Google_Service_Freebase_ReconcileCandidateNotable extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $id;
  public $name;


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

class Google_Service_Freebase_ReconcileGet extends Google_Collection
{
  protected $collection_key = 'warning';
  protected $internal_gapi_mappings = array(
  );
  protected $candidateType = 'Google_Service_Freebase_ReconcileCandidate';
  protected $candidateDataType = 'array';
  protected $costsType = 'Google_Service_Freebase_ReconcileGetCosts';
  protected $costsDataType = '';
  protected $matchType = 'Google_Service_Freebase_ReconcileCandidate';
  protected $matchDataType = '';
  protected $warningType = 'Google_Service_Freebase_ReconcileGetWarning';
  protected $warningDataType = 'array';


  public function setCandidate($candidate)
  {
    $this->candidate = $candidate;
  }
  public function getCandidate()
  {
    return $this->candidate;
  }
  public function setCosts(Google_Service_Freebase_ReconcileGetCosts $costs)
  {
    $this->costs = $costs;
  }
  public function getCosts()
  {
    return $this->costs;
  }
  public function setMatch(Google_Service_Freebase_ReconcileCandidate $match)
  {
    $this->match = $match;
  }
  public function getMatch()
  {
    return $this->match;
  }
  public function setWarning($warning)
  {
    $this->warning = $warning;
  }
  public function getWarning()
  {
    return $this->warning;
  }
}

class Google_Service_Freebase_ReconcileGetCosts extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $hits;
  public $ms;


  public function setHits($hits)
  {
    $this->hits = $hits;
  }
  public function getHits()
  {
    return $this->hits;
  }
  public function setMs($ms)
  {
    $this->ms = $ms;
  }
  public function getMs()
  {
    return $this->ms;
  }
}

class Google_Service_Freebase_ReconcileGetWarning extends Google_Model
{
  protected $internal_gapi_mappings = array(
  );
  public $location;
  public $message;
  public $reason;


  public function setLocation($location)
  {
    $this->location = $location;
  }
  public function getLocation()
  {
    return $this->location;
  }
  public function setMessage($message)
  {
    $this->message = $message;
  }
  public function getMessage()
  {
    return $this->message;
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
