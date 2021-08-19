<?php

declare(strict_types=1);

namespace Sabre\DAVACL\Exception;

use Sabre\DAV;

/**
 * NeedPrivileges.
 *
 * The 403-need privileges is thrown when a user didn't have the appropriate
 * permissions to perform an operation
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class NeedPrivileges extends DAV\Exception\Forbidden
{
    /**
     * The relevant uri.
     *
     * @var string
     */
    protected $uri;

    /**
     * The privileges the user didn't have.
     *
     * @var array
     */
    protected $privileges;

    /**
     * Constructor.
     *
     * @param string $uri
     */
    public function __construct($uri, array $privileges)
    {
        $this->uri = $uri;
        $this->privileges = $privileges;

        parent::__construct('User did not have the required privileges ('.implode(',', $privileges).') for path "'.$uri.'"');
    }

    /**
     * Adds in extra information in the xml response.
     *
     * This method adds the {DAV:}need-privileges element as defined in rfc3744
     */
    public function serialize(DAV\Server $server, \DOMElement $errorNode)
    {
        $doc = $errorNode->ownerDocument;

        $np = $doc->createElementNS('DAV:', 'd:need-privileges');
        $errorNode->appendChild($np);

        foreach ($this->privileges as $privilege) {
            $resource = $doc->createElementNS('DAV:', 'd:resource');
            $np->appendChild($resource);

            $resource->appendChild($doc->createElementNS('DAV:', 'd:href', $server->getBaseUri().$this->uri));

            $priv = $doc->createElementNS('DAV:', 'd:privilege');
            $resource->appendChild($priv);

            preg_match('/^{([^}]*)}(.*)$/', $privilege, $privilegeParts);
            $priv->appendChild($doc->createElementNS($privilegeParts[1], 'd:'.$privilegeParts[2]));
        }
    }
}
