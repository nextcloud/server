<?php

declare(strict_types=1);

namespace Sabre\CardDAV;

use Sabre\DAV;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject;

/**
 * VCF Exporter.
 *
 * This plugin adds the ability to export entire address books as .vcf files.
 * This is useful for clients that don't support CardDAV yet. They often do
 * support vcf files.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @author Thomas Tanghus (http://tanghus.net/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class VCFExportPlugin extends DAV\ServerPlugin
{
    /**
     * Reference to Server class.
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * Initializes the plugin and registers event handlers.
     */
    public function initialize(DAV\Server $server)
    {
        $this->server = $server;
        $this->server->on('method:GET', [$this, 'httpGet'], 90);
        $server->on('browserButtonActions', function ($path, $node, &$actions) {
            if ($node instanceof IAddressBook) {
                $actions .= '<a href="'.htmlspecialchars($path, ENT_QUOTES, 'UTF-8').'?export"><span class="oi" data-glyph="book"></span></a>';
            }
        });
    }

    /**
     * Intercepts GET requests on addressbook urls ending with ?export.
     *
     * @return bool
     */
    public function httpGet(RequestInterface $request, ResponseInterface $response)
    {
        $queryParams = $request->getQueryParameters();
        if (!array_key_exists('export', $queryParams)) {
            return;
        }

        $path = $request->getPath();

        $node = $this->server->tree->getNodeForPath($path);

        if (!($node instanceof IAddressBook)) {
            return;
        }

        $this->server->transactionType = 'get-addressbook-export';

        // Checking ACL, if available.
        if ($aclPlugin = $this->server->getPlugin('acl')) {
            $aclPlugin->checkPrivileges($path, '{DAV:}read');
        }

        $nodes = $this->server->getPropertiesForPath($path, [
            '{'.Plugin::NS_CARDDAV.'}address-data',
        ], 1);

        $format = 'text/directory';

        $output = null;
        $filenameExtension = null;

        switch ($format) {
            case 'text/directory':
                $output = $this->generateVCF($nodes);
                $filenameExtension = '.vcf';
                break;
        }

        $filename = preg_replace(
            '/[^a-zA-Z0-9-_ ]/um',
            '',
            $node->getName()
        );
        $filename .= '-'.date('Y-m-d').$filenameExtension;

        $response->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->setHeader('Content-Type', $format);

        $response->setStatus(200);
        $response->setBody($output);

        // Returning false to break the event chain
        return false;
    }

    /**
     * Merges all vcard objects, and builds one big vcf export.
     *
     * @return string
     */
    public function generateVCF(array $nodes)
    {
        $output = '';

        foreach ($nodes as $node) {
            if (!isset($node[200]['{'.Plugin::NS_CARDDAV.'}address-data'])) {
                continue;
            }
            $nodeData = $node[200]['{'.Plugin::NS_CARDDAV.'}address-data'];

            // Parsing this node so VObject can clean up the output.
            $vcard = VObject\Reader::read($nodeData);
            $output .= $vcard->serialize();

            // Destroy circular references to PHP will GC the object.
            $vcard->destroy();
        }

        return $output;
    }

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
        return 'vcf-export';
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
            'description' => 'Adds the ability to export CardDAV addressbooks as a single vCard file.',
            'link' => 'http://sabre.io/dav/vcf-export-plugin/',
        ];
    }
}
