<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\DAV\Exception\ReportNotSupported;
use Sabre\DAV\Xml\Property\LocalHref;
use Sabre\DAVACL;
use Sabre\HTTP;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;
use Sabre\VObject;

/**
 * CardDAV plugin.
 *
 * The CardDAV plugin adds CardDAV functionality to the WebDAV server
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    /**
     * Url to the addressbooks.
     */
    const ADDRESSBOOK_ROOT = 'addressbooks';

    /**
     * xml namespace for CardDAV elements.
     */
    const NS_CARDDAV = 'urn:ietf:params:xml:ns:carddav';

    /**
     * Add urls to this property to have them automatically exposed as
     * 'directories' to the user.
     *
     * @var array
     */
    public $directories = [];

    /**
     * Server class.
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * The default PDO storage uses a MySQL MEDIUMBLOB for iCalendar data,
     * which can hold up to 2^24 = 16777216 bytes. This is plenty. We're
     * capping it to 10M here.
     */
    protected $maxResourceSize = 10000000;

    /**
     * Initializes the plugin.
     */
    public function initialize(DAV\Server $server)
    {
        /* Events */
        $server->on('propFind', [$this, 'propFindEarly']);
        $server->on('propFind', [$this, 'propFindLate'], 150);
        $server->on('report', [$this, 'report']);
        $server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel']);
        $server->on('beforeWriteContent', [$this, 'beforeWriteContent']);
        $server->on('beforeCreateFile', [$this, 'beforeCreateFile']);
        $server->on('afterMethod:GET', [$this, 'httpAfterGet']);

        $server->xml->namespaceMap[self::NS_CARDDAV] = 'card';

        $server->xml->elementMap['{'.self::NS_CARDDAV.'}addressbook-query'] = 'Sabre\\CardDAV\\Xml\\Request\\AddressBookQueryReport';
        $server->xml->elementMap['{'.self::NS_CARDDAV.'}addressbook-multiget'] = 'Sabre\\CardDAV\\Xml\\Request\\AddressBookMultiGetReport';

        /* Mapping Interfaces to {DAV:}resourcetype values */
        $server->resourceTypeMapping['Sabre\\CardDAV\\IAddressBook'] = '{'.self::NS_CARDDAV.'}addressbook';
        $server->resourceTypeMapping['Sabre\\CardDAV\\IDirectory'] = '{'.self::NS_CARDDAV.'}directory';

        /* Adding properties that may never be changed */
        $server->protectedProperties[] = '{'.self::NS_CARDDAV.'}supported-address-data';
        $server->protectedProperties[] = '{'.self::NS_CARDDAV.'}max-resource-size';
        $server->protectedProperties[] = '{'.self::NS_CARDDAV.'}addressbook-home-set';
        $server->protectedProperties[] = '{'.self::NS_CARDDAV.'}supported-collation-set';

        $server->xml->elementMap['{http://calendarserver.org/ns/}me-card'] = 'Sabre\\DAV\\Xml\\Property\\Href';

        $this->server = $server;
    }

    /**
     * Returns a list of supported features.
     *
     * This is used in the DAV: header in the OPTIONS and PROPFIND requests.
     *
     * @return array
     */
    public function getFeatures()
    {
        return ['addressbook'];
    }

    /**
     * Returns a list of reports this plugin supports.
     *
     * This will be used in the {DAV:}supported-report-set property.
     * Note that you still need to subscribe to the 'report' event to actually
     * implement them
     *
     * @param string $uri
     *
     * @return array
     */
    public function getSupportedReportSet($uri)
    {
        $node = $this->server->tree->getNodeForPath($uri);
        if ($node instanceof IAddressBook || $node instanceof ICard) {
            return [
                 '{'.self::NS_CARDDAV.'}addressbook-multiget',
                 '{'.self::NS_CARDDAV.'}addressbook-query',
            ];
        }

        return [];
    }

    /**
     * Adds all CardDAV-specific properties.
     */
    public function propFindEarly(DAV\PropFind $propFind, DAV\INode $node)
    {
        $ns = '{'.self::NS_CARDDAV.'}';

        if ($node instanceof IAddressBook) {
            $propFind->handle($ns.'max-resource-size', $this->maxResourceSize);
            $propFind->handle($ns.'supported-address-data', function () {
                return new Xml\Property\SupportedAddressData();
            });
            $propFind->handle($ns.'supported-collation-set', function () {
                return new Xml\Property\SupportedCollationSet();
            });
        }
        if ($node instanceof DAVACL\IPrincipal) {
            $path = $propFind->getPath();

            $propFind->handle('{'.self::NS_CARDDAV.'}addressbook-home-set', function () use ($path) {
                return new LocalHref($this->getAddressBookHomeForPrincipal($path).'/');
            });

            if ($this->directories) {
                $propFind->handle('{'.self::NS_CARDDAV.'}directory-gateway', function () {
                    return new LocalHref($this->directories);
                });
            }
        }

        if ($node instanceof ICard) {
            // The address-data property is not supposed to be a 'real'
            // property, but in large chunks of the spec it does act as such.
            // Therefore we simply expose it as a property.
            $propFind->handle('{'.self::NS_CARDDAV.'}address-data', function () use ($node) {
                $val = $node->get();
                if (is_resource($val)) {
                    $val = stream_get_contents($val);
                }

                return $val;
            });
        }
    }

    /**
     * This functions handles REPORT requests specific to CardDAV.
     *
     * @param string   $reportName
     * @param \DOMNode $dom
     * @param mixed    $path
     *
     * @return bool
     */
    public function report($reportName, $dom, $path)
    {
        switch ($reportName) {
            case '{'.self::NS_CARDDAV.'}addressbook-multiget':
                $this->server->transactionType = 'report-addressbook-multiget';
                $this->addressbookMultiGetReport($dom);

                return false;
            case '{'.self::NS_CARDDAV.'}addressbook-query':
                $this->server->transactionType = 'report-addressbook-query';
                $this->addressBookQueryReport($dom);

                return false;
            default:
                return;
        }
    }

    /**
     * Returns the addressbook home for a given principal.
     *
     * @param string $principal
     *
     * @return string
     */
    protected function getAddressbookHomeForPrincipal($principal)
    {
        list(, $principalId) = Uri\split($principal);

        return self::ADDRESSBOOK_ROOT.'/'.$principalId;
    }

    /**
     * This function handles the addressbook-multiget REPORT.
     *
     * This report is used by the client to fetch the content of a series
     * of urls. Effectively avoiding a lot of redundant requests.
     *
     * @param Xml\Request\AddressBookMultiGetReport $report
     */
    public function addressbookMultiGetReport($report)
    {
        $contentType = $report->contentType;
        $version = $report->version;
        if ($version) {
            $contentType .= '; version='.$version;
        }

        $vcardType = $this->negotiateVCard(
            $contentType
        );

        $propertyList = [];
        $paths = array_map(
            [$this->server, 'calculateUri'],
            $report->hrefs
        );
        foreach ($this->server->getPropertiesForMultiplePaths($paths, $report->properties) as $props) {
            if (isset($props['200']['{'.self::NS_CARDDAV.'}address-data'])) {
                $props['200']['{'.self::NS_CARDDAV.'}address-data'] = $this->convertVCard(
                    $props[200]['{'.self::NS_CARDDAV.'}address-data'],
                    $vcardType
                );
            }
            $propertyList[] = $props;
        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($propertyList, 'minimal' === $prefer['return']));
    }

    /**
     * This method is triggered before a file gets updated with new content.
     *
     * This plugin uses this method to ensure that Card nodes receive valid
     * vcard data.
     *
     * @param string   $path
     * @param resource $data
     * @param bool     $modified should be set to true, if this event handler
     *                           changed &$data
     */
    public function beforeWriteContent($path, DAV\IFile $node, &$data, &$modified)
    {
        if (!$node instanceof ICard) {
            return;
        }

        $this->validateVCard($data, $modified);
    }

    /**
     * This method is triggered before a new file is created.
     *
     * This plugin uses this method to ensure that Card nodes receive valid
     * vcard data.
     *
     * @param string   $path
     * @param resource $data
     * @param bool     $modified should be set to true, if this event handler
     *                           changed &$data
     */
    public function beforeCreateFile($path, &$data, DAV\ICollection $parentNode, &$modified)
    {
        if (!$parentNode instanceof IAddressBook) {
            return;
        }

        $this->validateVCard($data, $modified);
    }

    /**
     * Checks if the submitted iCalendar data is in fact, valid.
     *
     * An exception is thrown if it's not.
     *
     * @param resource|string $data
     * @param bool            $modified should be set to true, if this event handler
     *                                  changed &$data
     */
    protected function validateVCard(&$data, &$modified)
    {
        // If it's a stream, we convert it to a string first.
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }

        $before = $data;

        try {
            // If the data starts with a [, we can reasonably assume we're dealing
            // with a jCal object.
            if ('[' === substr($data, 0, 1)) {
                $vobj = VObject\Reader::readJson($data);

                // Converting $data back to iCalendar, as that's what we
                // technically support everywhere.
                $data = $vobj->serialize();
                $modified = true;
            } else {
                $vobj = VObject\Reader::read($data);
            }
        } catch (VObject\ParseException $e) {
            throw new DAV\Exception\UnsupportedMediaType('This resource only supports valid vCard or jCard data. Parse error: '.$e->getMessage());
        }

        if ('VCARD' !== $vobj->name) {
            throw new DAV\Exception\UnsupportedMediaType('This collection can only support vcard objects.');
        }

        $options = VObject\Node::PROFILE_CARDDAV;
        $prefer = $this->server->getHTTPPrefer();

        if ('strict' !== $prefer['handling']) {
            $options |= VObject\Node::REPAIR;
        }

        $messages = $vobj->validate($options);

        $highestLevel = 0;
        $warningMessage = null;

        // $messages contains a list of problems with the vcard, along with
        // their severity.
        foreach ($messages as $message) {
            if ($message['level'] > $highestLevel) {
                // Recording the highest reported error level.
                $highestLevel = $message['level'];
                $warningMessage = $message['message'];
            }

            switch ($message['level']) {
                case 1:
                    // Level 1 means that there was a problem, but it was repaired.
                    $modified = true;
                    break;
                case 2:
                    // Level 2 means a warning, but not critical
                    break;
                case 3:
                    // Level 3 means a critical error
                    throw new DAV\Exception\UnsupportedMediaType('Validation error in vCard: '.$message['message']);
            }
        }
        if ($warningMessage) {
            $this->server->httpResponse->setHeader(
                'X-Sabre-Ew-Gross',
                'vCard validation warning: '.$warningMessage
            );

            // Re-serializing object.
            $data = $vobj->serialize();
            if (!$modified && 0 !== strcmp($data, $before)) {
                // This ensures that the system does not send an ETag back.
                $modified = true;
            }
        }

        // Destroy circular references to PHP will GC the object.
        $vobj->destroy();
    }

    /**
     * This function handles the addressbook-query REPORT.
     *
     * This report is used by the client to filter an addressbook based on a
     * complex query.
     *
     * @param Xml\Request\AddressBookQueryReport $report
     */
    protected function addressbookQueryReport($report)
    {
        $depth = $this->server->getHTTPDepth(0);

        if (0 == $depth) {
            $candidateNodes = [
                $this->server->tree->getNodeForPath($this->server->getRequestUri()),
            ];
            if (!$candidateNodes[0] instanceof ICard) {
                throw new ReportNotSupported('The addressbook-query report is not supported on this url with Depth: 0');
            }
        } else {
            $candidateNodes = $this->server->tree->getChildren($this->server->getRequestUri());
        }

        $contentType = $report->contentType;
        if ($report->version) {
            $contentType .= '; version='.$report->version;
        }

        $vcardType = $this->negotiateVCard(
            $contentType
        );

        $validNodes = [];
        foreach ($candidateNodes as $node) {
            if (!$node instanceof ICard) {
                continue;
            }

            $blob = $node->get();
            if (is_resource($blob)) {
                $blob = stream_get_contents($blob);
            }

            if (!$this->validateFilters($blob, $report->filters, $report->test)) {
                continue;
            }

            $validNodes[] = $node;

            if ($report->limit && $report->limit <= count($validNodes)) {
                // We hit the maximum number of items, we can stop now.
                break;
            }
        }

        $result = [];
        foreach ($validNodes as $validNode) {
            if (0 == $depth) {
                $href = $this->server->getRequestUri();
            } else {
                $href = $this->server->getRequestUri().'/'.$validNode->getName();
            }

            list($props) = $this->server->getPropertiesForPath($href, $report->properties, 0);

            if (isset($props[200]['{'.self::NS_CARDDAV.'}address-data'])) {
                $props[200]['{'.self::NS_CARDDAV.'}address-data'] = $this->convertVCard(
                    $props[200]['{'.self::NS_CARDDAV.'}address-data'],
                    $vcardType,
                    $report->addressDataProperties
                );
            }
            $result[] = $props;
        }

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, 'minimal' === $prefer['return']));
    }

    /**
     * Validates if a vcard makes it throught a list of filters.
     *
     * @param string $vcardData
     * @param string $test      anyof or allof (which means OR or AND)
     *
     * @return bool
     */
    public function validateFilters($vcardData, array $filters, $test)
    {
        if (!$filters) {
            return true;
        }
        $vcard = VObject\Reader::read($vcardData);

        foreach ($filters as $filter) {
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

                $results = [];
                if ($filter['param-filters']) {
                    $results[] = $this->validateParamFilters($vProperties, $filter['param-filters'], $filter['test']);
                }
                if ($filter['text-matches']) {
                    $texts = [];
                    foreach ($vProperties as $vProperty) {
                        $texts[] = $vProperty->getValue();
                    }

                    $results[] = $this->validateTextMatches($texts, $filter['text-matches'], $filter['test']);
                }

                if (1 === count($results)) {
                    $success = $results[0];
                } else {
                    if ('anyof' === $filter['test']) {
                        $success = $results[0] || $results[1];
                    } else {
                        $success = $results[0] && $results[1];
                    }
                }
            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ('anyof' === $test && $success) {
                // Destroy circular references to PHP will GC the object.
                $vcard->destroy();

                return true;
            }
            if ('allof' === $test && !$success) {
                // Destroy circular references to PHP will GC the object.
                $vcard->destroy();

                return false;
            }
        } // foreach

        // Destroy circular references to PHP will GC the object.
        $vcard->destroy();

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return 'allof' === $test;
    }

    /**
     * Validates if a param-filter can be applied to a specific property.
     *
     * @todo currently we're only validating the first parameter of the passed
     *       property. Any subsequence parameters with the same name are
     *       ignored.
     *
     * @param string $test
     *
     * @return bool
     */
    protected function validateParamFilters(array $vProperties, array $filters, $test)
    {
        foreach ($filters as $filter) {
            $isDefined = false;
            foreach ($vProperties as $vProperty) {
                $isDefined = isset($vProperty[$filter['name']]);
                if ($isDefined) {
                    break;
                }
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
                foreach ($vProperties as $vProperty) {
                    // If we got all the way here, we'll need to validate the
                    // text-match filter.
                    if (isset($vProperty[$filter['name']])) {
                        $success = DAV\StringUtil::textMatch(
                            $vProperty[$filter['name']]->getValue(),
                            $filter['text-match']['value'],
                            $filter['text-match']['collation'],
                            $filter['text-match']['match-type']
                        );
                        if ($filter['text-match']['negate-condition']) {
                            $success = !$success;
                        }
                    }
                    if ($success) {
                        break;
                    }
                }
            } // else

            // There are two conditions where we can already determine whether
            // or not this filter succeeds.
            if ('anyof' === $test && $success) {
                return true;
            }
            if ('allof' === $test && !$success) {
                return false;
            }
        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return 'allof' === $test;
    }

    /**
     * Validates if a text-filter can be applied to a specific property.
     *
     * @param string $test
     *
     * @return bool
     */
    protected function validateTextMatches(array $texts, array $filters, $test)
    {
        foreach ($filters as $filter) {
            $success = false;
            foreach ($texts as $haystack) {
                $success = DAV\StringUtil::textMatch($haystack, $filter['value'], $filter['collation'], $filter['match-type']);
                if ($filter['negate-condition']) {
                    $success = !$success;
                }

                // Breaking on the first match
                if ($success) {
                    break;
                }
            }

            if ($success && 'anyof' === $test) {
                return true;
            }

            if (!$success && 'allof' == $test) {
                return false;
            }
        }

        // If we got all the way here, it means we haven't been able to
        // determine early if the test failed or not.
        //
        // This implies for 'anyof' that the test failed, and for 'allof' that
        // we succeeded. Sounds weird, but makes sense.
        return 'allof' === $test;
    }

    /**
     * This event is triggered when fetching properties.
     *
     * This event is scheduled late in the process, after most work for
     * propfind has been done.
     */
    public function propFindLate(DAV\PropFind $propFind, DAV\INode $node)
    {
        // If the request was made using the SOGO connector, we must rewrite
        // the content-type property. By default SabreDAV will send back
        // text/x-vcard; charset=utf-8, but for SOGO we must strip that last
        // part.
        if (false === strpos((string) $this->server->httpRequest->getHeader('User-Agent'), 'Thunderbird')) {
            return;
        }
        $contentType = $propFind->get('{DAV:}getcontenttype');
        if (null !== $contentType) {
            list($part) = explode(';', $contentType);
            if ('text/x-vcard' === $part || 'text/vcard' === $part) {
                $propFind->set('{DAV:}getcontenttype', 'text/x-vcard');
            }
        }
    }

    /**
     * This method is used to generate HTML output for the
     * Sabre\DAV\Browser\Plugin. This allows us to generate an interface users
     * can use to create new addressbooks.
     *
     * @param string $output
     *
     * @return bool
     */
    public function htmlActionsPanel(DAV\INode $node, &$output)
    {
        if (!$node instanceof AddressBookHome) {
            return;
        }

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new address book</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            <input type="hidden" name="resourceType" value="{DAV:}collection,{'.self::NS_CARDDAV.'}addressbook" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;
    }

    /**
     * This event is triggered after GET requests.
     *
     * This is used to transform data into jCal, if this was requested.
     */
    public function httpAfterGet(RequestInterface $request, ResponseInterface $response)
    {
        $contentType = $response->getHeader('Content-Type');
        if (null === $contentType || false === strpos($contentType, 'text/vcard')) {
            return;
        }

        $target = $this->negotiateVCard($request->getHeader('Accept'), $mimeType);

        $newBody = $this->convertVCard(
            $response->getBody(),
            $target
        );

        $response->setBody($newBody);
        $response->setHeader('Content-Type', $mimeType.'; charset=utf-8');
        $response->setHeader('Content-Length', strlen($newBody));
    }

    /**
     * This helper function performs the content-type negotiation for vcards.
     *
     * It will return one of the following strings:
     * 1. vcard3
     * 2. vcard4
     * 3. jcard
     *
     * It defaults to vcard3.
     *
     * @param string $input
     * @param string $mimeType
     *
     * @return string
     */
    protected function negotiateVCard($input, &$mimeType = null)
    {
        $result = HTTP\negotiateContentType(
            $input,
            [
                // Most often used mime-type. Version 3
                'text/x-vcard',
                // The correct standard mime-type. Defaults to version 3 as
                // well.
                'text/vcard',
                // vCard 4
                'text/vcard; version=4.0',
                // vCard 3
                'text/vcard; version=3.0',
                // jCard
                'application/vcard+json',
            ]
        );

        $mimeType = $result;
        switch ($result) {
            default:
            case 'text/x-vcard':
            case 'text/vcard':
            case 'text/vcard; version=3.0':
                $mimeType = 'text/vcard';

                return 'vcard3';
            case 'text/vcard; version=4.0':
                return 'vcard4';
            case 'application/vcard+json':
                return 'jcard';

        // @codeCoverageIgnoreStart
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Converts a vcard blob to a different version, or jcard.
     *
     * @param string|resource $data
     * @param string          $target
     * @param array           $propertiesFilter
     *
     * @return string
     */
    protected function convertVCard($data, $target, array $propertiesFilter = null)
    {
        if (is_resource($data)) {
            $data = stream_get_contents($data);
        }
        $input = VObject\Reader::read($data);
        if (!empty($propertiesFilter)) {
            $propertiesFilter = array_merge(['UID', 'VERSION', 'FN'], $propertiesFilter);
            $keys = array_unique(array_map(function ($child) {
                return $child->name;
            }, $input->children()));
            $keys = array_diff($keys, $propertiesFilter);
            foreach ($keys as $key) {
                unset($input->$key);
            }
            $data = $input->serialize();
        }
        $output = null;
        try {
            switch ($target) {
                default:
                case 'vcard3':
                    if (VObject\Document::VCARD30 === $input->getDocumentType()) {
                        // Do nothing
                        return $data;
                    }
                    $output = $input->convert(VObject\Document::VCARD30);

                    return $output->serialize();
                case 'vcard4':
                    if (VObject\Document::VCARD40 === $input->getDocumentType()) {
                        // Do nothing
                        return $data;
                    }
                    $output = $input->convert(VObject\Document::VCARD40);

                    return $output->serialize();
                case 'jcard':
                    $output = $input->convert(VObject\Document::VCARD40);

                    return json_encode($output);
            }
        } finally {
            // Destroy circular references to PHP will GC the object.
            $input->destroy();
            if (!is_null($output)) {
                $output->destroy();
            }
        }
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'carddav';
    }

    /**
     * Returns a bunch of meta-data about the plugin.
     *
     * Providing this information is optional, and is mainly displayed by the
     * Browser plugin.
     *
     * The description key in the returned array may contain html and will not
     * be sanitized.
     *
     * @return array
     */
    public function getPluginInfo()
    {
        return [
            'name' => $this->getPluginName(),
            'description' => 'Adds support for CardDAV (rfc6352)',
            'link' => 'http://sabre.io/dav/carddav/',
        ];
    }
}
