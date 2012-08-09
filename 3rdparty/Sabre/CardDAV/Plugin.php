<?php

/**
 * CardDAV plugin
 *
 * The CardDAV plugin adds CardDAV functionality to the WebDAV server
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Url to the addressbooks
     */
    const ADDRESSBOOK_ROOT = 'addressbooks';

    /**
     * xml namespace for CardDAV elements
     */
    const NS_CARDDAV = 'urn:ietf:params:xml:ns:carddav';

    /**
     * Add urls to this property to have them automatically exposed as
     * 'directories' to the user.
     *
     * @var array
     */
    public $directories = array();

    /**
     * Server class
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * Initializes the plugin
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        /* Events */
        $server->subscribeEvent('beforeGetProperties', array($this, 'beforeGetProperties'));
        $server->subscribeEvent('updateProperties', array($this, 'updateProperties'));
        $server->subscribeEvent('report', array($this,'report'));
        $server->subscribeEvent('onHTMLActionsPanel', array($this,'htmlActionsPanel'));
        $server->subscribeEvent('onBrowserPostAction', array($this,'browserPostAction'));

        /* Namespaces */
        $server->xmlNamespaces[self::NS_CARDDAV] = 'card';

        /* Mapping Interfaces to {DAV:}resourcetype values */
        $server->resourceTypeMapping['Sabre_CardDAV_IAddressBook'] = '{' . self::NS_CARDDAV . '}addressbook';
        $server->resourceTypeMapping['Sabre_CardDAV_IDirectory'] = '{' . self::NS_CARDDAV . '}directory';

        /* Adding properties that may never be changed */
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}supported-address-data';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}max-resource-size';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}addressbook-home-set';
        $server->protectedProperties[] = '{' . self::NS_CARDDAV . '}supported-collation-set';

        $server->propertyMap['{http://calendarserver.org/ns/}me-card'] = 'Sabre_DAV_Property_Href';

        $this->server = $server;

    }

    /**
     * Returns a list of supported features.
     *
     * This is used in the DAV: header in the OPTIONS and PROPFIND requests.
     *
     * @return array
     */
    public function getFeatures() {

        return array('addressbook');

    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     * @return array
     */
    public function getSupportedReportSet($uri) {

        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof Sabre_CardDAV_IAddressBook || $node instanceof Sabre_CardDAV_ICard) {
            return array(
                 '{' . self::NS_CARDDAV . '}addressbook-multiget',
                 '{' . self::NS_CARDDAV . '}addressbook-query',
            );
        }
        return array();

    }


    /**
     * Adds all CardDAV-specific properties
     *
     * @param string $path
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @return void
     */
    public function beforeGetProperties($path, Sabre_DAV_INode $node, array &$requestedProperties, array &$returnedProperties) {

        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            // calendar-home-set property
            $addHome = '{' . self::NS_CARDDAV . '}addressbook-home-set';
            if (in_array($addHome,$requestedProperties)) {
                $principalId = $node->getName();
                $addressbookHomePath = self::ADDRESSBOOK_ROOT . '/' . $principalId . '/';
                unset($requestedProperties[array_search($addHome, $requestedProperties)]);
                $returnedProperties[200][$addHome] = new Sabre_DAV_Property_Href($addressbookHomePath);
            }

            $directories = '{' . self::NS_CARDDAV . '}directory-gateway';
            if ($this->directories && in_array($directories, $requestedProperties)) {
                unset($requestedProperties[array_search($directories, $requestedProperties)]);
                $returnedProperties[200][$directories] = new Sabre_DAV_Property_HrefList($this->directories);
            }

        }

        if ($node instanceof Sabre_CardDAV_ICard) {

            // The address-data property is not supposed to be a 'real'
            // property, but in large chunks of the spec it does act as such.
            // Therefore we simply expose it as a property.
            $addressDataProp = '{' . self::NS_CARDDAV . '}address-data';
            if (in_array($addressDataProp, $requestedProperties)) {
                unset($requestedProperties[$addressDataProp]);
                $val = $node->get();
                if (is_resource($val))
                    $val = stream_get_contents($val);

                // Taking out \r to not screw up the xml output
                $returnedProperties[200][$addressDataProp] = str_replace("\r","", $val);
                // The stripping of \r breaks the Mail App in OSX Mountain Lion
                // this is fixed in master, but not backported. /Tanghus
                $returnedProperties[200][$addressDataProp] = $val;

            }
        }

        if ($node instanceof Sabre_CardDAV_UserAddressBooks) {

            $meCardProp = '{http://calendarserver.org/ns/}me-card';
            if (in_array($meCardProp, $requestedProperties)) {

                $props = $this->server->getProperties($node->getOwner(), array('{http://sabredav.org/ns}vcard-url'));
                if (isset($props['{http://sabredav.org/ns}vcard-url'])) {

                    $returnedProperties[200][$meCardProp] = new Sabre_DAV_Property_Href(
                        $props['{http://sabredav.org/ns}vcard-url']
                    );
                    $pos = array_search($meCardProp, $requestedProperties);
                    unset($requestedProperties[$pos]);

                }

            }

        }

    }

    /**
     * This event is triggered when a PROPPATCH method is executed
     *
     * @param array $mutations
     * @param array $result
     * @param Sabre_DAV_INode $node
     * @return void
     */
    public function updateProperties(&$mutations, &$result, $node) {

        if (!$node instanceof Sabre_CardDAV_UserAddressBooks) {
            return true;
        }

        $meCard = '{http://calendarserver.org/ns/}me-card';

        // The only property we care about
        if (!isset($mutations[$meCard]))
            return true;

        $value = $mutations[$meCard];
        unset($mutations[$meCard]);

        if ($value instanceof Sabre_DAV_Property_IHref) {
            $value = $value->getHref();
            $value = $this->server->calculateUri($value);
        } elseif (!is_null($value)) {
            $result[400][$meCard] = null;
            return false;
        }

        $innerResult = $this->server->updateProperties(
            $node->getOwner(),
            array(
                '{http://sabredav.org/ns}vcard-url' => $value,
            )
        );

        $closureResult = false;
        foreach($innerResult as $status => $props) {
            if (is_array($props) && array_key_exists('{http://sabredav.org/ns}vcard-url', $props)) {
                $result[$status][$meCard] = null;
                $closureResult = ($status>=200 && $status<300);
            }

        }

        return $result;

    }

    /**
     * This functions handles REPORT requests specific to CardDAV
     *
     * @param string $reportName
     * @param DOMNode $dom
     * @return bool
     */
    public function report($reportName,$dom) {

        switch($reportName) {
            case '{'.self::NS_CARDDAV.'}addressbook-multiget' :
                $this->addressbookMultiGetReport($dom);
                return false;
            case '{'.self::NS_CARDDAV.'}addressbook-query' :
                $this->addressBookQueryReport($dom);
                return false;
            default :
                return;

        }


    }

    /**
     * This function handles the addressbook-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param DOMNode $dom
     * @return void
     */
    public function addressbookMultiGetReport($dom) {

        $properties = array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild));

        $hrefElems = $dom->getElementsByTagNameNS('urn:DAV','href');
        $propertyList = array();

        foreach($hrefElems as $elem) {

            $uri = $this->server->calculateUri($elem->nodeValue);
            list($propertyList[]) = $this->server->getPropertiesForPath($uri,$properties);

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($propertyList));

    }

    /**
     * This function handles the addressbook-query REPORT
     *
     * This report is used by the client to filter an addressbook based on a
     * complex query.
     *
     * @param DOMNode $dom
     * @return void
     */
    protected function addressbookQueryReport($dom) {

        $query = new Sabre_CardDAV_AddressBookQueryParser($dom);
        $query->parse();

        $depth = $this->server->getHTTPDepth(0);

        if ($depth==0) {
            $candidateNodes = array(
                $this->server->tree->getNodeForPath($this->server->getRequestUri())
            );
        } else {
            $candidateNodes = $this->server->tree->getChildren($this->server->getRequestUri());
        }

        $validNodes = array();
        foreach($candidateNodes as $node) {

            if (!$node instanceof Sabre_CardDAV_ICard)
                continue;

            $blob = $node->get();
            if (is_resource($blob)) {
                $blob = stream_get_contents($blob);
            }

            if (!$this->validateFilters($blob, $query->filters, $query->test)) {
                continue;
            }

            $validNodes[] = $node;

            if ($query->limit && $query->limit <= count($validNodes)) {
                // We hit the maximum number of items, we can stop now.
                break;
            }

        }

        $result = array();
        foreach($validNodes as $validNode) {

            if ($depth==0) {
                $href = $this->server->getRequestUri();
            } else {
                $href = $this->server->getRequestUri() . '/' . $validNode->getName();
            }

            list($result[]) = $this->server->getPropertiesForPath($href, $query->requestedProperties, 0);

        }

        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendBody($this->server->generateMultiStatus($result));

    }

    /**
     * Validates if a vcard makes it throught a list of filters.
     *
     * @param string $vcardData
     * @param array $filters
     * @param string $test anyof or allof (which means OR or AND)
     * @return bool
     */
    public function validateFilters($vcardData, array $filters, $test) {

        $vcard = Sabre_VObject_Reader::read($vcardData);

        foreach($filters as $filter) {

            $isDefined = isset($vcard->{$filter['name']});
            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    $success = false;
                } else {
                    $success = true;
                }
            } elseif ((!$filter['param-filters'] && !$filter['text-matches']) || !$isDefined) {

                // We only need to check for existence
                $success = $isDefined;

            } else {

                $vProperties = $vcard->select($filter['name']);

                $results = array();
                if ($filter['param-filters']) {
                    $results[] = $this->validateParamFilters($vProperties, $filter['param-filters'], $filter['test']);
                }
                if ($filter['text-matches']) {
                    $texts = array();
                    foreach($vProperties as $vProperty)
                        $texts[] = $vProperty->value;

                    $results[] = $this->validateTextMatches($texts, $filter['text-matches'], $filter['test']);
                }

                if (count($results)===1) {
                    $success = $results[0];
                } else {
                    if ($filter['test'] === 'anyof') {
                        $success = $results[0] || $results[1];
                    } else {
                        $success = $results[0] && $results[1];
                    }
                }

            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ($test==='anyof' && $success) {
                return true;
            }
            if ($test==='allof' && !$success) {
                return false;
            }

        } // foreach

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * Validates if a param-filter can be applied to a specific property.
     *
     * @todo currently we're only validating the first parameter of the passed
     *       property. Any subsequence parameters with the same name are
     *       ignored.
     * @param array $vProperties
     * @param array $filters
     * @param string $test
     * @return bool
     */
    protected function validateParamFilters(array $vProperties, array $filters, $test) {

        foreach($filters as $filter) {

            $isDefined = false;
            foreach($vProperties as $vProperty) {
                $isDefined = isset($vProperty[$filter['name']]);
                if ($isDefined) break;
            }

            if ($filter['is-not-defined']) {
                if ($isDefined) {
                    $success = false;
                } else {
                    $success = true;
                }

            // If there's no text-match, we can just check for existence
            } elseif (!$filter['text-match'] || !$isDefined) {

                $success = $isDefined;

            } else {

                $success = false;
                foreach($vProperties as $vProperty) {
                    // If we got all the way here, we'll need to validate the
                    // text-match filter.
                    $success = Sabre_DAV_StringUtil::textMatch($vProperty[$filter['name']]->value, $filter['text-match']['value'], $filter['text-match']['collation'], $filter['text-match']['match-type']);
                    if ($success) break;
                }
                if ($filter['text-match']['negate-condition']) {
                    $success = !$success;
                }

            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ($test==='anyof' && $success) {
                return true;
            }
            if ($test==='allof' && !$success) {
                return false;
            }

        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * Validates if a text-filter can be applied to a specific property.
     *
     * @param array $texts
     * @param array $filters
     * @param string $test
     * @return bool
     */
    protected function validateTextMatches(array $texts, array $filters, $test) {

        foreach($filters as $filter) {

            $success = false;
            foreach($texts as $haystack) {
                $success = Sabre_DAV_StringUtil::textMatch($haystack, $filter['value'], $filter['collation'], $filter['match-type']);

                // Breaking on the first match
                if ($success) break;
            }
            if ($filter['negate-condition']) {
                $success = !$success;
            }

            if ($success && $test==='anyof')
                return true;

            if (!$success && $test=='allof')
                return false;


        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return $test==='allof';

    }

    /**
     * This method is used to generate HTML output for the
     * Sabre_DAV_Browser_Plugin. This allows us to generate an interface users
     * can use to create new calendars.
     *
     * @param Sabre_DAV_INode $node
     * @param string $output
     * @return bool
     */
    public function htmlActionsPanel(Sabre_DAV_INode $node, &$output) {

        if (!$node instanceof Sabre_CardDAV_UserAddressBooks)
            return;

        $output.= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new address book</h3>
            <input type="hidden" name="sabreAction" value="mkaddressbook" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;

    }

    /**
     * This method allows us to intercept the 'mkcalendar' sabreAction. This
     * action enables the user to create new calendars from the browser plugin.
     *
     * @param string $uri
     * @param string $action
     * @param array $postVars
     * @return bool
     */
    public function browserPostAction($uri, $action, array $postVars) {

        if ($action!=='mkaddressbook')
            return;

        $resourceType = array('{DAV:}collection','{urn:ietf:params:xml:ns:carddav}addressbook');
        $properties = array();
        if (isset($postVars['{DAV:}displayname'])) {
            $properties['{DAV:}displayname'] = $postVars['{DAV:}displayname'];
        }
        $this->server->createCollection($uri . '/' . $postVars['name'],$resourceType,$properties);
        return false;

    }

}
