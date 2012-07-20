<?php

/**
 * Parses the addressbook-query report request body.
 *
 * Whoever designed this format, and the CalDAV equivalent even more so,
 * has no feel for design.
 *
 * @package Sabre
 * @subpackage CardDAV
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_CardDAV_AddressBookQueryParser {

    const TEST_ANYOF = 'anyof';
    const TEST_ALLOF = 'allof';

    /**
     * List of requested properties the client wanted
     *
     * @var array
     */
    public $requestedProperties;

    /**
     * The number of results the client wants
     *
     * null means it wasn't specified, which in most cases means 'all results'.
     *
     * @var int|null
     */
    public $limit;

    /**
     * List of property filters.
     *
     * @var array
     */
    public $filters;

    /**
     * Either TEST_ANYOF or TEST_ALLOF
     *
     * @var string
     */
    public $test;

    /**
     * DOM Document
     *
     * @var DOMDocument
     */
    protected $dom;

    /**
     * DOM XPath object
     *
     * @var DOMXPath
     */
    protected $xpath;

    /**
     * Creates the parser
     *
     * @param DOMDocument $dom
     */
    public function __construct(DOMDocument $dom) {

        $this->dom = $dom;

        $this->xpath = new DOMXPath($dom);
        $this->xpath->registerNameSpace('card',Sabre_CardDAV_Plugin::NS_CARDDAV);

    }

    /**
     * Parses the request.
     *
     * @return void
     */
    public function parse() {

        $filterNode = null;

        $limit = $this->xpath->evaluate('number(/card:addressbook-query/card:limit/card:nresults)');
        if (is_nan($limit)) $limit = null;

        $filter = $this->xpath->query('/card:addressbook-query/card:filter');

        // According to the CardDAV spec there needs to be exactly 1 filter
        // element. However, KDE 4.8.2 contains a bug that will encode 0 filter
        // elements, so this is a workaround for that.
        //
        // See: https://bugs.kde.org/show_bug.cgi?id=300047
        if ($filter->length === 0) {
            $test = null;
            $filter = null;
        } elseif ($filter->length === 1) {
            $filter = $filter->item(0);
            $test = $this->xpath->evaluate('string(@test)', $filter);
        } else {
            throw new Sabre_DAV_Exception_BadRequest('Only one filter element is allowed');
        }

        if (!$test) $test = self::TEST_ANYOF;
        if ($test !== self::TEST_ANYOF && $test !== self::TEST_ALLOF) {
            throw new Sabre_DAV_Exception_BadRequest('The test attribute must either hold "anyof" or "allof"');
        }

        $propFilters = array();

        $propFilterNodes = $this->xpath->query('card:prop-filter', $filter);
        for($ii=0; $ii < $propFilterNodes->length; $ii++) {

            $propFilters[] = $this->parsePropFilterNode($propFilterNodes->item($ii));


        }

        $this->filters = $propFilters;
        $this->limit = $limit;
        $this->requestedProperties = array_keys(Sabre_DAV_XMLUtil::parseProperties($this->dom->firstChild));
        $this->test = $test;

    }

    /**
     * Parses the prop-filter xml element
     *
     * @param DOMElement $propFilterNode
     * @return array
     */
    protected function parsePropFilterNode(DOMElement $propFilterNode) {

        $propFilter = array();
        $propFilter['name'] = $propFilterNode->getAttribute('name');
        $propFilter['test'] = $propFilterNode->getAttribute('test');
        if (!$propFilter['test']) $propFilter['test'] = 'anyof';

        $propFilter['is-not-defined'] = $this->xpath->query('card:is-not-defined', $propFilterNode)->length>0;

        $paramFilterNodes = $this->xpath->query('card:param-filter', $propFilterNode);

        $propFilter['param-filters'] = array();


        for($ii=0;$ii<$paramFilterNodes->length;$ii++) {

            $propFilter['param-filters'][] = $this->parseParamFilterNode($paramFilterNodes->item($ii));

        }
        $propFilter['text-matches'] = array();
        $textMatchNodes = $this->xpath->query('card:text-match', $propFilterNode);

        for($ii=0;$ii<$textMatchNodes->length;$ii++) {

            $propFilter['text-matches'][] = $this->parseTextMatchNode($textMatchNodes->item($ii));

        }

        return $propFilter;

    }

    /**
     * Parses the param-filter element
     *
     * @param DOMElement $paramFilterNode
     * @return array
     */
    public function parseParamFilterNode(DOMElement $paramFilterNode) {

        $paramFilter = array();
        $paramFilter['name'] = $paramFilterNode->getAttribute('name');
        $paramFilter['is-not-defined'] = $this->xpath->query('card:is-not-defined', $paramFilterNode)->length>0;
        $paramFilter['text-match'] = null;

        $textMatch = $this->xpath->query('card:text-match', $paramFilterNode);
        if ($textMatch->length>0) {
            $paramFilter['text-match'] = $this->parseTextMatchNode($textMatch->item(0));
        }

        return $paramFilter;

    }

    /**
     * Text match
     *
     * @param DOMElement $textMatchNode
     * @return array
     */
    public function parseTextMatchNode(DOMElement $textMatchNode) {

        $matchType = $textMatchNode->getAttribute('match-type');
        if (!$matchType) $matchType = 'contains';

        if (!in_array($matchType, array('contains', 'equals', 'starts-with', 'ends-with'))) {
            throw new Sabre_DAV_Exception_BadRequest('Unknown match-type: ' . $matchType);
        }

        $negateCondition = $textMatchNode->getAttribute('negate-condition');
        $negateCondition = $negateCondition==='yes';
        $collation = $textMatchNode->getAttribute('collation');
        if (!$collation) $collation = 'i;unicode-casemap';

        return array(
            'negate-condition' => $negateCondition,
            'collation' => $collation,
            'match-type' => $matchType,
            'value' => $textMatchNode->nodeValue
        );


    }

}
