<?php

declare(strict_types=1);

namespace Sabre\DAV\Sync;

use Sabre\DAV;
use Sabre\DAV\Xml\Request\SyncCollectionReport;
use Sabre\HTTP\RequestInterface;

/**
 * This plugin all WebDAV-sync capabilities to the Server.
 *
 * WebDAV-sync is defined by rfc6578
 *
 * The sync capabilities only work with collections that implement
 * Sabre\DAV\Sync\ISyncCollection.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    /**
     * Reference to server object.
     *
     * @var DAV\Server
     */
    protected $server;

    const SYNCTOKEN_PREFIX = 'http://sabre.io/ns/sync/';

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using \Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'sync';
    }

    /**
     * Initializes the plugin.
     *
     * This is when the plugin registers it's hooks.
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;
        $server->xml->elementMap['{DAV:}sync-collection'] = 'Sabre\\DAV\\Xml\\Request\\SyncCollectionReport';

        $self = $this;

        $server->on('report', function ($reportName, $dom, $uri) use ($self) {
            if ('{DAV:}sync-collection' === $reportName) {
                $this->server->transactionType = 'report-sync-collection';
                $self->syncCollection($uri, $dom);

                return false;
            }
        });

        $server->on('propFind', [$this, 'propFind']);
        $server->on('validateTokens', [$this, 'validateTokens']);
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
        if ($node instanceof ISyncCollection && $node->getSyncToken()) {
            return [
                '{DAV:}sync-collection',
            ];
        }

        return [];
    }

    /**
     * This method handles the {DAV:}sync-collection HTTP REPORT.
     *
     * @param string $uri
     */
    public function syncCollection($uri, SyncCollectionReport $report)
    {
        // Getting the data
        $node = $this->server->tree->getNodeForPath($uri);
        if (!$node instanceof ISyncCollection) {
            throw new DAV\Exception\ReportNotSupported('The {DAV:}sync-collection REPORT is not supported on this url.');
        }
        $token = $node->getSyncToken();
        if (!$token) {
            throw new DAV\Exception\ReportNotSupported('No sync information is available at this node');
        }

        $syncToken = $report->syncToken;
        if (!is_null($syncToken)) {
            // Sync-token must start with our prefix
            if (self::SYNCTOKEN_PREFIX !== substr($syncToken, 0, strlen(self::SYNCTOKEN_PREFIX))) {
                throw new DAV\Exception\InvalidSyncToken('Invalid or unknown sync token');
            }

            $syncToken = substr($syncToken, strlen(self::SYNCTOKEN_PREFIX));
        }
        $changeInfo = $node->getChanges($syncToken, $report->syncLevel, $report->limit);

        if (is_null($changeInfo)) {
            throw new DAV\Exception\InvalidSyncToken('Invalid or unknown sync token');
        }

        // Encoding the response
        $this->sendSyncCollectionResponse(
            $changeInfo['syncToken'],
            $uri,
            $changeInfo['added'],
            $changeInfo['modified'],
            $changeInfo['deleted'],
            $report->properties
        );
    }

    /**
     * Sends the response to a sync-collection request.
     *
     * @param string $syncToken
     * @param string $collectionUrl
     */
    protected function sendSyncCollectionResponse($syncToken, $collectionUrl, array $added, array $modified, array $deleted, array $properties)
    {
        $fullPaths = [];

        // Pre-fetching children, if this is possible.
        foreach (array_merge($added, $modified) as $item) {
            $fullPath = $collectionUrl.'/'.$item;
            $fullPaths[] = $fullPath;
        }

        $responses = [];
        foreach ($this->server->getPropertiesForMultiplePaths($fullPaths, $properties) as $fullPath => $props) {
            // The 'Property_Response' class is responsible for generating a
            // single {DAV:}response xml element.
            $responses[] = new DAV\Xml\Element\Response($fullPath, $props);
        }

        // Deleted items also show up as 'responses'. They have no properties,
        // and a single {DAV:}status element set as 'HTTP/1.1 404 Not Found'.
        foreach ($deleted as $item) {
            $fullPath = $collectionUrl.'/'.$item;
            $responses[] = new DAV\Xml\Element\Response($fullPath, [], 404);
        }
        $multiStatus = new DAV\Xml\Response\MultiStatus($responses, self::SYNCTOKEN_PREFIX.$syncToken);

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody(
            $this->server->xml->write('{DAV:}multistatus', $multiStatus, $this->server->getBaseUri())
        );
    }

    /**
     * This method is triggered whenever properties are requested for a node.
     * We intercept this to see if we must return a {DAV:}sync-token.
     */
    public function propFind(DAV\PropFind $propFind, DAV\INode $node)
    {
        $propFind->handle('{DAV:}sync-token', function () use ($node) {
            if (!$node instanceof ISyncCollection || !$token = $node->getSyncToken()) {
                return;
            }

            return self::SYNCTOKEN_PREFIX.$token;
        });
    }

    /**
     * The validateTokens event is triggered before every request.
     *
     * It's a moment where this plugin can check all the supplied lock tokens
     * in the If: header, and check if they are valid.
     *
     * @param array $conditions
     */
    public function validateTokens(RequestInterface $request, &$conditions)
    {
        foreach ($conditions as $kk => $condition) {
            foreach ($condition['tokens'] as $ii => $token) {
                // Sync-tokens must always start with our designated prefix.
                if (self::SYNCTOKEN_PREFIX !== substr($token['token'], 0, strlen(self::SYNCTOKEN_PREFIX))) {
                    continue;
                }

                // Checking if the token is a match.
                $node = $this->server->tree->getNodeForPath($condition['uri']);

                if (
                    $node instanceof ISyncCollection &&
                    $node->getSyncToken() == substr($token['token'], strlen(self::SYNCTOKEN_PREFIX))
                ) {
                    $conditions[$kk]['tokens'][$ii]['validToken'] = true;
                }
            }
        }
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
            'description' => 'Adds support for WebDAV Collection Sync (rfc6578)',
            'link' => 'http://sabre.io/dav/sync/',
        ];
    }
}
