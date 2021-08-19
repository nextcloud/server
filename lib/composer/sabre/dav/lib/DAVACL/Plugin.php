<?php

declare(strict_types=1);

namespace Sabre\DAVACL;

use Sabre\DAV;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;
use Sabre\DAV\Xml\Property\Href;
use Sabre\DAVACL\Exception\NeedPrivileges;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Uri;

/**
 * SabreDAV ACL Plugin.
 *
 * This plugin provides functionality to enforce ACL permissions.
 * ACL is defined in RFC3744.
 *
 * In addition it also provides support for the {DAV:}current-user-principal
 * property, defined in RFC5397 and the {DAV:}expand-property report, as
 * defined in RFC3253.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Plugin extends DAV\ServerPlugin
{
    /**
     * Recursion constants.
     *
     * This only checks the base node
     */
    const R_PARENT = 1;

    /**
     * Recursion constants.
     *
     * This checks every node in the tree
     */
    const R_RECURSIVE = 2;

    /**
     * Recursion constants.
     *
     * This checks every parentnode in the tree, but not leaf-nodes.
     */
    const R_RECURSIVEPARENTS = 3;

    /**
     * Reference to server object.
     *
     * @var DAV\Server
     */
    protected $server;

    /**
     * List of urls containing principal collections.
     * Modify this if your principals are located elsewhere.
     *
     * @var array
     */
    public $principalCollectionSet = [
        'principals',
    ];

    /**
     * By default nodes that are inaccessible by the user, can still be seen
     * in directory listings (PROPFIND on parent with Depth: 1).
     *
     * In certain cases it's desirable to hide inaccessible nodes. Setting this
     * to true will cause these nodes to be hidden from directory listings.
     *
     * @var bool
     */
    public $hideNodesFromListings = false;

    /**
     * This list of properties are the properties a client can search on using
     * the {DAV:}principal-property-search report.
     *
     * The keys are the property names, values are descriptions.
     *
     * @var array
     */
    public $principalSearchPropertySet = [
        '{DAV:}displayname' => 'Display name',
        '{http://sabredav.org/ns}email-address' => 'Email address',
    ];

    /**
     * Any principal uri's added here, will automatically be added to the list
     * of ACL's. They will effectively receive {DAV:}all privileges, as a
     * protected privilege.
     *
     * @var array
     */
    public $adminPrincipals = [];

    /**
     * The ACL plugin allows privileges to be assigned to users that are not
     * logged in. To facilitate that, it modifies the auth plugin's behavior
     * to only require login when a privileged operation was denied.
     *
     * Unauthenticated access can be considered a security concern, so it's
     * possible to turn this feature off to harden the server's security.
     *
     * @var bool
     */
    public $allowUnauthenticatedAccess = true;

    /**
     * Returns a list of features added by this plugin.
     *
     * This list is used in the response of a HTTP OPTIONS request.
     *
     * @return array
     */
    public function getFeatures()
    {
        return ['access-control', 'calendarserver-principal-property-search'];
    }

    /**
     * Returns a list of available methods for a given url.
     *
     * @param string $uri
     *
     * @return array
     */
    public function getMethods($uri)
    {
        return ['ACL'];
    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre\DAV\Server::getPlugin
     *
     * @return string
     */
    public function getPluginName()
    {
        return 'acl';
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
        return [
            '{DAV:}expand-property',
            '{DAV:}principal-match',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        ];
    }

    /**
     * Checks if the current user has the specified privilege(s).
     *
     * You can specify a single privilege, or a list of privileges.
     * This method will throw an exception if the privilege is not available
     * and return true otherwise.
     *
     * @param string       $uri
     * @param array|string $privileges
     * @param int          $recursion
     * @param bool         $throwExceptions if set to false, this method won't throw exceptions
     *
     * @throws NeedPrivileges
     * @throws NotAuthenticated
     *
     * @return bool
     */
    public function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true)
    {
        if (!is_array($privileges)) {
            $privileges = [$privileges];
        }

        $acl = $this->getCurrentUserPrivilegeSet($uri);

        $failed = [];
        foreach ($privileges as $priv) {
            if (!in_array($priv, $acl)) {
                $failed[] = $priv;
            }
        }

        if ($failed) {
            if ($this->allowUnauthenticatedAccess && is_null($this->getCurrentUserPrincipal())) {
                // We are not authenticated. Kicking in the Auth plugin.
                $authPlugin = $this->server->getPlugin('auth');
                $reasons = $authPlugin->getLoginFailedReasons();
                $authPlugin->challenge(
                    $this->server->httpRequest,
                    $this->server->httpResponse
                );
                throw new NotAuthenticated(implode(', ', $reasons).'. Login was needed for privilege: '.implode(', ', $failed).' on '.$uri);
            }
            if ($throwExceptions) {
                throw new NeedPrivileges($uri, $failed);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the standard users' principal.
     *
     * This is one authoritative principal url for the current user.
     * This method will return null if the user wasn't logged in.
     *
     * @return string|null
     */
    public function getCurrentUserPrincipal()
    {
        /** @var $authPlugin \Sabre\DAV\Auth\Plugin */
        $authPlugin = $this->server->getPlugin('auth');
        if (!$authPlugin) {
            return null;
        }

        return $authPlugin->getCurrentPrincipal();
    }

    /**
     * Returns a list of principals that's associated to the current
     * user, either directly or through group membership.
     *
     * @return array
     */
    public function getCurrentUserPrincipals()
    {
        $currentUser = $this->getCurrentUserPrincipal();

        if (is_null($currentUser)) {
            return [];
        }

        return array_merge(
            [$currentUser],
            $this->getPrincipalMembership($currentUser)
        );
    }

    /**
     * Sets the default ACL rules.
     *
     * These rules are used for all nodes that don't implement the IACL interface.
     */
    public function setDefaultAcl(array $acl)
    {
        $this->defaultAcl = $acl;
    }

    /**
     * Returns the default ACL rules.
     *
     * These rules are used for all nodes that don't implement the IACL interface.
     *
     * @return array
     */
    public function getDefaultAcl()
    {
        return $this->defaultAcl;
    }

    /**
     * The default ACL rules.
     *
     * These rules are used for nodes that don't implement IACL. These default
     * set of rules allow anyone to do anything, as long as they are
     * authenticated.
     *
     * @var array
     */
    protected $defaultAcl = [
        [
            'principal' => '{DAV:}authenticated',
            'protected' => true,
            'privilege' => '{DAV:}all',
        ],
    ];

    /**
     * This array holds a cache for all the principals that are associated with
     * a single principal.
     *
     * @var array
     */
    protected $principalMembershipCache = [];

    /**
     * Returns all the principal groups the specified principal is a member of.
     *
     * @param string $mainPrincipal
     *
     * @return array
     */
    public function getPrincipalMembership($mainPrincipal)
    {
        // First check our cache
        if (isset($this->principalMembershipCache[$mainPrincipal])) {
            return $this->principalMembershipCache[$mainPrincipal];
        }

        $check = [$mainPrincipal];
        $principals = [];

        while (count($check)) {
            $principal = array_shift($check);

            $node = $this->server->tree->getNodeForPath($principal);
            if ($node instanceof IPrincipal) {
                foreach ($node->getGroupMembership() as $groupMember) {
                    if (!in_array($groupMember, $principals)) {
                        $check[] = $groupMember;
                        $principals[] = $groupMember;
                    }
                }
            }
        }

        // Store the result in the cache
        $this->principalMembershipCache[$mainPrincipal] = $principals;

        return $principals;
    }

    /**
     * Find out of a principal equals another principal.
     *
     * This is a quick way to find out whether a principal URI is part of a
     * group, or any subgroups.
     *
     * The first argument is the principal URI you want to check against. For
     * example the principal group, and the second argument is the principal of
     * which you want to find out of it is the same as the first principal, or
     * in a member of the first principal's group or subgroups.
     *
     * So the arguments are not interchangeable. If principal A is in group B,
     * passing 'B', 'A' will yield true, but 'A', 'B' is false.
     *
     * If the second argument is not passed, we will use the current user
     * principal.
     *
     * @param string $checkPrincipal
     * @param string $currentPrincipal
     *
     * @return bool
     */
    public function principalMatchesPrincipal($checkPrincipal, $currentPrincipal = null)
    {
        if (is_null($currentPrincipal)) {
            $currentPrincipal = $this->getCurrentUserPrincipal();
        }
        if ($currentPrincipal === $checkPrincipal) {
            return true;
        }
        if (is_null($currentPrincipal)) {
            return false;
        }

        return in_array(
            $checkPrincipal,
            $this->getPrincipalMembership($currentPrincipal)
        );
    }

    /**
     * Returns a tree of supported privileges for a resource.
     *
     * The returned array structure should be in this form:
     *
     * [
     *    [
     *       'privilege' => '{DAV:}read',
     *       'abstract'  => false,
     *       'aggregates' => []
     *    ]
     * ]
     *
     * Privileges can be nested using "aggregates". Doing so means that
     * if you assign someone the aggregating privilege, all the
     * sub-privileges will automatically be granted.
     *
     * Marking a privilege as abstract means that the privilege cannot be
     * directly assigned, but must be assigned via the parent privilege.
     *
     * So a more complex version might look like this:
     *
     * [
     *    [
     *       'privilege' => '{DAV:}read',
     *       'abstract'  => false,
     *       'aggregates' => [
     *          [
     *              'privilege'  => '{DAV:}read-acl',
     *              'abstract'   => false,
     *              'aggregates' => [],
     *          ]
     *       ]
     *    ]
     * ]
     *
     * @param string|INode $node
     *
     * @return array
     */
    public function getSupportedPrivilegeSet($node)
    {
        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $supportedPrivileges = null;
        if ($node instanceof IACL) {
            $supportedPrivileges = $node->getSupportedPrivilegeSet();
        }

        if (is_null($supportedPrivileges)) {
            // Default
            $supportedPrivileges = [
                '{DAV:}read' => [
                    'abstract' => false,
                    'aggregates' => [
                        '{DAV:}read-acl' => [
                            'abstract' => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}read-current-user-privilege-set' => [
                            'abstract' => false,
                            'aggregates' => [],
                        ],
                    ],
                ],
                '{DAV:}write' => [
                    'abstract' => false,
                    'aggregates' => [
                        '{DAV:}write-properties' => [
                            'abstract' => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}write-content' => [
                            'abstract' => false,
                            'aggregates' => [],
                        ],
                        '{DAV:}unlock' => [
                            'abstract' => false,
                            'aggregates' => [],
                        ],
                    ],
                ],
            ];
            if ($node instanceof DAV\ICollection) {
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}bind'] = [
                    'abstract' => false,
                    'aggregates' => [],
                ];
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}unbind'] = [
                    'abstract' => false,
                    'aggregates' => [],
                ];
            }
            if ($node instanceof IACL) {
                $supportedPrivileges['{DAV:}write']['aggregates']['{DAV:}write-acl'] = [
                    'abstract' => false,
                    'aggregates' => [],
                ];
            }
        }

        $this->server->emit(
            'getSupportedPrivilegeSet',
            [$node, &$supportedPrivileges]
        );

        return $supportedPrivileges;
    }

    /**
     * Returns the supported privilege set as a flat list.
     *
     * This is much easier to parse.
     *
     * The returned list will be index by privilege name.
     * The value is a struct containing the following properties:
     *   - aggregates
     *   - abstract
     *   - concrete
     *
     * @param string|INode $node
     *
     * @return array
     */
    final public function getFlatPrivilegeSet($node)
    {
        $privs = [
            'abstract' => false,
            'aggregates' => $this->getSupportedPrivilegeSet($node),
        ];

        $fpsTraverse = null;
        $fpsTraverse = function ($privName, $privInfo, $concrete, &$flat) use (&$fpsTraverse) {
            $myPriv = [
                'privilege' => $privName,
                'abstract' => isset($privInfo['abstract']) && $privInfo['abstract'],
                'aggregates' => [],
                'concrete' => isset($privInfo['abstract']) && $privInfo['abstract'] ? $concrete : $privName,
            ];

            if (isset($privInfo['aggregates'])) {
                foreach ($privInfo['aggregates'] as $subPrivName => $subPrivInfo) {
                    $myPriv['aggregates'][] = $subPrivName;
                }
            }

            $flat[$privName] = $myPriv;

            if (isset($privInfo['aggregates'])) {
                foreach ($privInfo['aggregates'] as $subPrivName => $subPrivInfo) {
                    $fpsTraverse($subPrivName, $subPrivInfo, $myPriv['concrete'], $flat);
                }
            }
        };

        $flat = [];
        $fpsTraverse('{DAV:}all', $privs, null, $flat);

        return $flat;
    }

    /**
     * Returns the full ACL list.
     *
     * Either a uri or a INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|DAV\INode $node
     *
     * @return array
     */
    public function getAcl($node)
    {
        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }
        if (!$node instanceof IACL) {
            return $this->getDefaultAcl();
        }
        $acl = $node->getACL();
        foreach ($this->adminPrincipals as $adminPrincipal) {
            $acl[] = [
                'principal' => $adminPrincipal,
                'privilege' => '{DAV:}all',
                'protected' => true,
            ];
        }

        return $acl;
    }

    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * Either a uri or a DAV\INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|DAV\INode $node
     *
     * @return array
     */
    public function getCurrentUserPrivilegeSet($node)
    {
        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = $this->getACL($node);

        $collected = [];

        $isAuthenticated = null !== $this->getCurrentUserPrincipal();

        foreach ($acl as $ace) {
            $principal = $ace['principal'];

            switch ($principal) {
                case '{DAV:}owner':
                    $owner = $node->getOwner();
                    if ($owner && $this->principalMatchesPrincipal($owner)) {
                        $collected[] = $ace;
                    }
                    break;

                // 'all' matches for every user
                case '{DAV:}all':
                    $collected[] = $ace;
                    break;

                case '{DAV:}authenticated':
                    // Authenticated users only
                    if ($isAuthenticated) {
                        $collected[] = $ace;
                    }
                    break;

                case '{DAV:}unauthenticated':
                    // Unauthenticated users only
                    if (!$isAuthenticated) {
                        $collected[] = $ace;
                    }
                    break;

                default:
                    if ($this->principalMatchesPrincipal($ace['principal'])) {
                        $collected[] = $ace;
                    }
                    break;
            }
        }

        // Now we deduct all aggregated privileges.
        $flat = $this->getFlatPrivilegeSet($node);

        $collected2 = [];
        while (count($collected)) {
            $current = array_pop($collected);
            $collected2[] = $current['privilege'];

            if (!isset($flat[$current['privilege']])) {
                // Ignoring privileges that are not in the supported-privileges list.
                $this->server->getLogger()->debug('A node has the "'.$current['privilege'].'" in its ACL list, but this privilege was not reported in the supportedPrivilegeSet list. This will be ignored.');
                continue;
            }
            foreach ($flat[$current['privilege']]['aggregates'] as $subPriv) {
                $collected2[] = $subPriv;
                $collected[] = $flat[$subPriv];
            }
        }

        return array_values(array_unique($collected2));
    }

    /**
     * Returns a principal based on its uri.
     *
     * Returns null if the principal could not be found.
     *
     * @param string $uri
     *
     * @return string|null
     */
    public function getPrincipalByUri($uri)
    {
        $result = null;
        $collections = $this->principalCollectionSet;
        foreach ($collections as $collection) {
            try {
                $principalCollection = $this->server->tree->getNodeForPath($collection);
            } catch (NotFound $e) {
                // Ignore and move on
                continue;
            }

            if (!$principalCollection instanceof IPrincipalCollection) {
                // Not a principal collection, we're simply going to ignore
                // this.
                continue;
            }

            $result = $principalCollection->findByUri($uri);
            if ($result) {
                return $result;
            }
        }
    }

    /**
     * Principal property search.
     *
     * This method can search for principals matching certain values in
     * properties.
     *
     * This method will return a list of properties for the matched properties.
     *
     * @param array  $searchProperties    The properties to search on. This is a
     *                                    key-value list. The keys are property
     *                                    names, and the values the strings to
     *                                    match them on.
     * @param array  $requestedProperties this is the list of properties to
     *                                    return for every match
     * @param string $collectionUri       the principal collection to search on.
     *                                    If this is ommitted, the standard
     *                                    principal collection-set will be used
     * @param string $test                "allof" to use AND to search the
     *                                    properties. 'anyof' for OR.
     *
     * @return array This method returns an array structure similar to
     *               Sabre\DAV\Server::getPropertiesForPath. Returned
     *               properties are index by a HTTP status code.
     */
    public function principalSearch(array $searchProperties, array $requestedProperties, $collectionUri = null, $test = 'allof')
    {
        if (!is_null($collectionUri)) {
            $uris = [$collectionUri];
        } else {
            $uris = $this->principalCollectionSet;
        }

        $lookupResults = [];
        foreach ($uris as $uri) {
            $principalCollection = $this->server->tree->getNodeForPath($uri);
            if (!$principalCollection instanceof IPrincipalCollection) {
                // Not a principal collection, we're simply going to ignore
                // this.
                continue;
            }

            $results = $principalCollection->searchPrincipals($searchProperties, $test);
            foreach ($results as $result) {
                $lookupResults[] = rtrim($uri, '/').'/'.$result;
            }
        }

        $matches = [];

        foreach ($lookupResults as $lookupResult) {
            list($matches[]) = $this->server->getPropertiesForPath($lookupResult, $requestedProperties, 0);
        }

        return $matches;
    }

    /**
     * Sets up the plugin.
     *
     * This method is automatically called by the server class.
     */
    public function initialize(DAV\Server $server)
    {
        if ($this->allowUnauthenticatedAccess) {
            $authPlugin = $server->getPlugin('auth');
            if (!$authPlugin) {
                throw new \Exception('The Auth plugin must be loaded before the ACL plugin if you want to allow unauthenticated access.');
            }
            $authPlugin->autoRequireLogin = false;
        }

        $this->server = $server;
        $server->on('propFind', [$this, 'propFind'], 20);
        $server->on('beforeMethod:*', [$this, 'beforeMethod'], 20);
        $server->on('beforeBind', [$this, 'beforeBind'], 20);
        $server->on('beforeUnbind', [$this, 'beforeUnbind'], 20);
        $server->on('propPatch', [$this, 'propPatch']);
        $server->on('beforeUnlock', [$this, 'beforeUnlock'], 20);
        $server->on('report', [$this, 'report']);
        $server->on('method:ACL', [$this, 'httpAcl']);
        $server->on('onHTMLActionsPanel', [$this, 'htmlActionsPanel']);
        $server->on('getPrincipalByUri', function ($principal, &$uri) {
            $uri = $this->getPrincipalByUri($principal);

            // Break event chain
            if ($uri) {
                return false;
            }
        });

        array_push($server->protectedProperties,
            '{DAV:}alternate-URI-set',
            '{DAV:}principal-URL',
            '{DAV:}group-membership',
            '{DAV:}principal-collection-set',
            '{DAV:}current-user-principal',
            '{DAV:}supported-privilege-set',
            '{DAV:}current-user-privilege-set',
            '{DAV:}acl',
            '{DAV:}acl-restrictions',
            '{DAV:}inherited-acl-set',
            '{DAV:}owner',
            '{DAV:}group'
        );

        // Automatically mapping nodes implementing IPrincipal to the
        // {DAV:}principal resourcetype.
        $server->resourceTypeMapping['Sabre\\DAVACL\\IPrincipal'] = '{DAV:}principal';

        // Mapping the group-member-set property to the HrefList property
        // class.
        $server->xml->elementMap['{DAV:}group-member-set'] = 'Sabre\\DAV\\Xml\\Property\\Href';
        $server->xml->elementMap['{DAV:}acl'] = 'Sabre\\DAVACL\\Xml\\Property\\Acl';
        $server->xml->elementMap['{DAV:}acl-principal-prop-set'] = 'Sabre\\DAVACL\\Xml\\Request\\AclPrincipalPropSetReport';
        $server->xml->elementMap['{DAV:}expand-property'] = 'Sabre\\DAVACL\\Xml\\Request\\ExpandPropertyReport';
        $server->xml->elementMap['{DAV:}principal-property-search'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalPropertySearchReport';
        $server->xml->elementMap['{DAV:}principal-search-property-set'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalSearchPropertySetReport';
        $server->xml->elementMap['{DAV:}principal-match'] = 'Sabre\\DAVACL\\Xml\\Request\\PrincipalMatchReport';
    }

    /* {{{ Event handlers */

    /**
     * Triggered before any method is handled.
     */
    public function beforeMethod(RequestInterface $request, ResponseInterface $response)
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        $exists = $this->server->tree->nodeExists($path);

        // If the node doesn't exists, none of these checks apply
        if (!$exists) {
            return;
        }

        switch ($method) {
            case 'GET':
            case 'HEAD':
            case 'OPTIONS':
                // For these 3 we only need to know if the node is readable.
                $this->checkPrivileges($path, '{DAV:}read');
                break;

            case 'PUT':
            case 'LOCK':
                // This method requires the write-content priv if the node
                // already exists, and bind on the parent if the node is being
                // created.
                // The bind privilege is handled in the beforeBind event.
                $this->checkPrivileges($path, '{DAV:}write-content');
                break;

            case 'UNLOCK':
                // Unlock is always allowed at the moment.
                break;

            case 'PROPPATCH':
                $this->checkPrivileges($path, '{DAV:}write-properties');
                break;

            case 'ACL':
                $this->checkPrivileges($path, '{DAV:}write-acl');
                break;

            case 'COPY':
            case 'MOVE':
                // Copy requires read privileges on the entire source tree.
                // If the target exists write-content normally needs to be
                // checked, however, we're deleting the node beforehand and
                // creating a new one after, so this is handled by the
                // beforeUnbind event.
                //
                // The creation of the new node is handled by the beforeBind
                // event.
                //
                // If MOVE is used beforeUnbind will also be used to check if
                // the sourcenode can be deleted.
                $this->checkPrivileges($path, '{DAV:}read', self::R_RECURSIVE);
                break;
        }
    }

    /**
     * Triggered before a new node is created.
     *
     * This allows us to check permissions for any operation that creates a
     * new node, such as PUT, MKCOL, MKCALENDAR, LOCK, COPY and MOVE.
     *
     * @param string $uri
     */
    public function beforeBind($uri)
    {
        list($parentUri) = Uri\split($uri);
        $this->checkPrivileges($parentUri, '{DAV:}bind');
    }

    /**
     * Triggered before a node is deleted.
     *
     * This allows us to check permissions for any operation that will delete
     * an existing node.
     *
     * @param string $uri
     */
    public function beforeUnbind($uri)
    {
        list($parentUri) = Uri\split($uri);
        $this->checkPrivileges($parentUri, '{DAV:}unbind', self::R_RECURSIVEPARENTS);
    }

    /**
     * Triggered before a node is unlocked.
     *
     * @param string $uri
     * @TODO: not yet implemented
     */
    public function beforeUnlock($uri, DAV\Locks\LockInfo $lock)
    {
    }

    /**
     * Triggered before properties are looked up in specific nodes.
     *
     * @TODO really should be broken into multiple methods, or even a class.
     *
     * @return bool
     */
    public function propFind(DAV\PropFind $propFind, DAV\INode $node)
    {
        $path = $propFind->getPath();

        // Checking the read permission
        if (!$this->checkPrivileges($path, '{DAV:}read', self::R_PARENT, false)) {
            // User is not allowed to read properties

            // Returning false causes the property-fetching system to pretend
            // that the node does not exist, and will cause it to be hidden
            // from listings such as PROPFIND or the browser plugin.
            if ($this->hideNodesFromListings) {
                return false;
            }

            // Otherwise we simply mark every property as 403.
            foreach ($propFind->getRequestedProperties() as $requestedProperty) {
                $propFind->set($requestedProperty, null, 403);
            }

            return;
        }

        /* Adding principal properties */
        if ($node instanceof IPrincipal) {
            $propFind->handle('{DAV:}alternate-URI-set', function () use ($node) {
                return new Href($node->getAlternateUriSet());
            });
            $propFind->handle('{DAV:}principal-URL', function () use ($node) {
                return new Href($node->getPrincipalUrl().'/');
            });
            $propFind->handle('{DAV:}group-member-set', function () use ($node) {
                $members = $node->getGroupMemberSet();
                foreach ($members as $k => $member) {
                    $members[$k] = rtrim($member, '/').'/';
                }

                return new Href($members);
            });
            $propFind->handle('{DAV:}group-membership', function () use ($node) {
                $members = $node->getGroupMembership();
                foreach ($members as $k => $member) {
                    $members[$k] = rtrim($member, '/').'/';
                }

                return new Href($members);
            });
            $propFind->handle('{DAV:}displayname', [$node, 'getDisplayName']);
        }

        $propFind->handle('{DAV:}principal-collection-set', function () {
            $val = $this->principalCollectionSet;
            // Ensuring all collections end with a slash
            foreach ($val as $k => $v) {
                $val[$k] = $v.'/';
            }

            return new Href($val);
        });
        $propFind->handle('{DAV:}current-user-principal', function () {
            if ($url = $this->getCurrentUserPrincipal()) {
                return new Xml\Property\Principal(Xml\Property\Principal::HREF, $url.'/');
            } else {
                return new Xml\Property\Principal(Xml\Property\Principal::UNAUTHENTICATED);
            }
        });
        $propFind->handle('{DAV:}supported-privilege-set', function () use ($node) {
            return new Xml\Property\SupportedPrivilegeSet($this->getSupportedPrivilegeSet($node));
        });
        $propFind->handle('{DAV:}current-user-privilege-set', function () use ($node, $propFind, $path) {
            if (!$this->checkPrivileges($path, '{DAV:}read-current-user-privilege-set', self::R_PARENT, false)) {
                $propFind->set('{DAV:}current-user-privilege-set', null, 403);
            } else {
                $val = $this->getCurrentUserPrivilegeSet($node);

                return new Xml\Property\CurrentUserPrivilegeSet($val);
            }
        });
        $propFind->handle('{DAV:}acl', function () use ($node, $propFind, $path) {
            /* The ACL property contains all the permissions */
            if (!$this->checkPrivileges($path, '{DAV:}read-acl', self::R_PARENT, false)) {
                $propFind->set('{DAV:}acl', null, 403);
            } else {
                $acl = $this->getACL($node);

                return new Xml\Property\Acl($this->getACL($node));
            }
        });
        $propFind->handle('{DAV:}acl-restrictions', function () {
            return new Xml\Property\AclRestrictions();
        });

        /* Adding ACL properties */
        if ($node instanceof IACL) {
            $propFind->handle('{DAV:}owner', function () use ($node) {
                return new Href($node->getOwner().'/');
            });
        }
    }

    /**
     * This method intercepts PROPPATCH methods and make sure the
     * group-member-set is updated correctly.
     *
     * @param string $path
     */
    public function propPatch($path, DAV\PropPatch $propPatch)
    {
        $propPatch->handle('{DAV:}group-member-set', function ($value) use ($path) {
            if (is_null($value)) {
                $memberSet = [];
            } elseif ($value instanceof Href) {
                $memberSet = array_map(
                    [$this->server, 'calculateUri'],
                    $value->getHrefs()
                );
            } else {
                throw new DAV\Exception('The group-member-set property MUST be an instance of Sabre\DAV\Property\HrefList or null');
            }
            $node = $this->server->tree->getNodeForPath($path);
            if (!($node instanceof IPrincipal)) {
                // Fail
                return false;
            }

            $node->setGroupMemberSet($memberSet);
            // We must also clear our cache, just in case

            $this->principalMembershipCache = [];

            return true;
        });
    }

    /**
     * This method handles HTTP REPORT requests.
     *
     * @param string $reportName
     * @param mixed  $report
     * @param mixed  $path
     *
     * @return bool
     */
    public function report($reportName, $report, $path)
    {
        switch ($reportName) {
            case '{DAV:}principal-property-search':
                $this->server->transactionType = 'report-principal-property-search';
                $this->principalPropertySearchReport($path, $report);

                return false;
            case '{DAV:}principal-search-property-set':
                $this->server->transactionType = 'report-principal-search-property-set';
                $this->principalSearchPropertySetReport($path, $report);

                return false;
            case '{DAV:}expand-property':
                $this->server->transactionType = 'report-expand-property';
                $this->expandPropertyReport($path, $report);

                return false;
            case '{DAV:}principal-match':
                $this->server->transactionType = 'report-principal-match';
                $this->principalMatchReport($path, $report);

                return false;
            case '{DAV:}acl-principal-prop-set':
                $this->server->transactionType = 'acl-principal-prop-set';
                $this->aclPrincipalPropSetReport($path, $report);

                return false;
        }
    }

    /**
     * This method is responsible for handling the 'ACL' event.
     *
     * @return bool
     */
    public function httpAcl(RequestInterface $request, ResponseInterface $response)
    {
        $path = $request->getPath();
        $body = $request->getBodyAsString();

        if (!$body) {
            throw new DAV\Exception\BadRequest('XML body expected in ACL request');
        }

        $acl = $this->server->xml->expect('{DAV:}acl', $body);
        $newAcl = $acl->getPrivileges();

        // Normalizing urls
        foreach ($newAcl as $k => $newAce) {
            $newAcl[$k]['principal'] = $this->server->calculateUri($newAce['principal']);
        }
        $node = $this->server->tree->getNodeForPath($path);

        if (!$node instanceof IACL) {
            throw new DAV\Exception\MethodNotAllowed('This node does not support the ACL method');
        }

        $oldAcl = $this->getACL($node);

        $supportedPrivileges = $this->getFlatPrivilegeSet($node);

        /* Checking if protected principals from the existing principal set are
           not overwritten. */
        foreach ($oldAcl as $oldAce) {
            if (!isset($oldAce['protected']) || !$oldAce['protected']) {
                continue;
            }

            $found = false;
            foreach ($newAcl as $newAce) {
                if (
                    $newAce['privilege'] === $oldAce['privilege'] &&
                    $newAce['principal'] === $oldAce['principal'] &&
                    $newAce['protected']
                ) {
                    $found = true;
                }
            }

            if (!$found) {
                throw new Exception\AceConflict('This resource contained a protected {DAV:}ace, but this privilege did not occur in the ACL request');
            }
        }

        foreach ($newAcl as $newAce) {
            // Do we recognize the privilege
            if (!isset($supportedPrivileges[$newAce['privilege']])) {
                throw new Exception\NotSupportedPrivilege('The privilege you specified ('.$newAce['privilege'].') is not recognized by this server');
            }

            if ($supportedPrivileges[$newAce['privilege']]['abstract']) {
                throw new Exception\NoAbstract('The privilege you specified ('.$newAce['privilege'].') is an abstract privilege');
            }

            // Looking up the principal
            try {
                $principal = $this->server->tree->getNodeForPath($newAce['principal']);
            } catch (NotFound $e) {
                throw new Exception\NotRecognizedPrincipal('The specified principal ('.$newAce['principal'].') does not exist');
            }
            if (!($principal instanceof IPrincipal)) {
                throw new Exception\NotRecognizedPrincipal('The specified uri ('.$newAce['principal'].') is not a principal');
            }
        }
        $node->setACL($newAcl);

        $response->setStatus(200);

        // Breaking the event chain, because we handled this method.
        return false;
    }

    /* }}} */

    /* Reports {{{ */

    /**
     * The principal-match report is defined in RFC3744, section 9.3.
     *
     * This report allows a client to figure out based on the current user,
     * or a principal URL, the principal URL and principal URLs of groups that
     * principal belongs to.
     *
     * @param string $path
     */
    protected function principalMatchReport($path, Xml\Request\PrincipalMatchReport $report)
    {
        $depth = $this->server->getHTTPDepth(0);
        if (0 !== $depth) {
            throw new BadRequest('The principal-match report is only defined on Depth: 0');
        }

        $currentPrincipals = $this->getCurrentUserPrincipals();

        $result = [];

        if (Xml\Request\PrincipalMatchReport::SELF === $report->type) {
            // Finding all principals under the request uri that match the
            // current principal.
            foreach ($currentPrincipals as $currentPrincipal) {
                if ($currentPrincipal === $path || 0 === strpos($currentPrincipal, $path.'/')) {
                    $result[] = $currentPrincipal;
                }
            }
        } else {
            // We need to find all resources that have a property that matches
            // one of the current principals.
            $candidates = $this->server->getPropertiesForPath(
                $path,
                [$report->principalProperty],
                1
            );

            foreach ($candidates as $candidate) {
                if (!isset($candidate[200][$report->principalProperty])) {
                    continue;
                }

                $hrefs = $candidate[200][$report->principalProperty];

                if (!$hrefs instanceof Href) {
                    continue;
                }

                foreach ($hrefs->getHrefs() as $href) {
                    if (in_array(trim($href, '/'), $currentPrincipals)) {
                        $result[] = $candidate['href'];
                        continue 2;
                    }
                }
            }
        }

        $responses = [];

        foreach ($result as $item) {
            $properties = [];

            if ($report->properties) {
                $foo = $this->server->getPropertiesForPath($item, $report->properties);
                $foo = $foo[0];
                $item = $foo['href'];
                unset($foo['href']);
                $properties = $foo;
            }

            $responses[] = new DAV\Xml\Element\Response(
                $item,
                $properties,
                '200'
            );
        }

        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setBody(
            $this->server->xml->write(
                '{DAV:}multistatus',
                $responses,
                $this->server->getBaseUri()
            )
        );
    }

    /**
     * The expand-property report is defined in RFC3253 section 3.8.
     *
     * This report is very similar to a standard PROPFIND. The difference is
     * that it has the additional ability to look at properties containing a
     * {DAV:}href element, follow that property and grab additional elements
     * there.
     *
     * Other rfc's, such as ACL rely on this report, so it made sense to put
     * it in this plugin.
     *
     * @param string                           $path
     * @param Xml\Request\ExpandPropertyReport $report
     */
    protected function expandPropertyReport($path, $report)
    {
        $depth = $this->server->getHTTPDepth(0);

        $result = $this->expandProperties($path, $report->properties, $depth);

        $xml = $this->server->xml->write(
            '{DAV:}multistatus',
            new DAV\Xml\Response\MultiStatus($result),
            $this->server->getBaseUri()
        );
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setBody($xml);
    }

    /**
     * This method expands all the properties and returns
     * a list with property values.
     *
     * @param array $path
     * @param array $requestedProperties the list of required properties
     * @param int   $depth
     *
     * @return array
     */
    protected function expandProperties($path, array $requestedProperties, $depth)
    {
        $foundProperties = $this->server->getPropertiesForPath($path, array_keys($requestedProperties), $depth);

        $result = [];

        foreach ($foundProperties as $node) {
            foreach ($requestedProperties as $propertyName => $childRequestedProperties) {
                // We're only traversing if sub-properties were requested
                if (!is_array($childRequestedProperties) || 0 === count($childRequestedProperties)) {
                    continue;
                }

                // We only have to do the expansion if the property was found
                // and it contains an href element.
                if (!array_key_exists($propertyName, $node[200])) {
                    continue;
                }

                if (!$node[200][$propertyName] instanceof DAV\Xml\Property\Href) {
                    continue;
                }

                $childHrefs = $node[200][$propertyName]->getHrefs();
                $childProps = [];

                foreach ($childHrefs as $href) {
                    // Gathering the result of the children
                    $childProps[] = [
                        'name' => '{DAV:}response',
                        'value' => $this->expandProperties($href, $childRequestedProperties, 0)[0],
                    ];
                }

                // Replacing the property with its expanded form.
                $node[200][$propertyName] = $childProps;
            }
            $result[] = new DAV\Xml\Element\Response($node['href'], $node);
        }

        return $result;
    }

    /**
     * principalSearchPropertySetReport.
     *
     * This method responsible for handing the
     * {DAV:}principal-search-property-set report. This report returns a list
     * of properties the client may search on, using the
     * {DAV:}principal-property-search report.
     *
     * @param string                                       $path
     * @param Xml\Request\PrincipalSearchPropertySetReport $report
     */
    protected function principalSearchPropertySetReport($path, $report)
    {
        $httpDepth = $this->server->getHTTPDepth(0);
        if (0 !== $httpDepth) {
            throw new DAV\Exception\BadRequest('This report is only defined when Depth: 0');
        }

        $writer = $this->server->xml->getWriter();
        $writer->openMemory();
        $writer->startDocument();

        $writer->startElement('{DAV:}principal-search-property-set');

        foreach ($this->principalSearchPropertySet as $propertyName => $description) {
            $writer->startElement('{DAV:}principal-search-property');
            $writer->startElement('{DAV:}prop');

            $writer->writeElement($propertyName);

            $writer->endElement(); // prop

            if ($description) {
                $writer->write([[
                    'name' => '{DAV:}description',
                    'value' => $description,
                    'attributes' => ['xml:lang' => 'en'],
                ]]);
            }

            $writer->endElement(); // principal-search-property
        }

        $writer->endElement(); // principal-search-property-set

        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setStatus(200);
        $this->server->httpResponse->setBody($writer->outputMemory());
    }

    /**
     * principalPropertySearchReport.
     *
     * This method is responsible for handing the
     * {DAV:}principal-property-search report. This report can be used for
     * clients to search for groups of principals, based on the value of one
     * or more properties.
     *
     * @param string $path
     */
    protected function principalPropertySearchReport($path, Xml\Request\PrincipalPropertySearchReport $report)
    {
        if ($report->applyToPrincipalCollectionSet) {
            $path = null;
        }
        if (0 !== $this->server->getHttpDepth('0')) {
            throw new BadRequest('Depth must be 0');
        }
        $result = $this->principalSearch(
            $report->searchProperties,
            $report->properties,
            $path,
            $report->test
        );

        $prefer = $this->server->getHTTPPrefer();

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
        $this->server->httpResponse->setBody($this->server->generateMultiStatus($result, 'minimal' === $prefer['return']));
    }

    /**
     * aclPrincipalPropSet REPORT.
     *
     * This method is responsible for handling the {DAV:}acl-principal-prop-set
     * REPORT, as defined in:
     *
     * https://tools.ietf.org/html/rfc3744#section-9.2
     *
     * This REPORT allows a user to quickly fetch information about all
     * principals specified in the access control list. Most commonly this
     * is used to for example generate a UI with ACL rules, allowing you
     * to show names for principals for every entry.
     *
     * @param string $path
     */
    protected function aclPrincipalPropSetReport($path, Xml\Request\AclPrincipalPropSetReport $report)
    {
        if (0 !== $this->server->getHTTPDepth(0)) {
            throw new BadRequest('The {DAV:}acl-principal-prop-set REPORT only supports Depth 0');
        }

        // Fetching ACL rules for the given path. We're using the property
        // API and not the local getACL, because it will ensure that all
        // business rules and restrictions are applied.
        $acl = $this->server->getProperties($path, '{DAV:}acl');

        if (!$acl || !isset($acl['{DAV:}acl'])) {
            throw new Forbidden('Could not fetch ACL rules for this path');
        }

        $principals = [];
        foreach ($acl['{DAV:}acl']->getPrivileges() as $ace) {
            if ('{' === $ace['principal'][0]) {
                // It's not a principal, it's one of the special rules such as {DAV:}authenticated
                continue;
            }

            $principals[] = $ace['principal'];
        }

        $properties = $this->server->getPropertiesForMultiplePaths(
            $principals,
            $report->properties
        );

        $this->server->httpResponse->setStatus(207);
        $this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
        $this->server->httpResponse->setBody(
            $this->server->generateMultiStatus($properties)
        );
    }

    /* }}} */

    /**
     * This method is used to generate HTML output for the
     * DAV\Browser\Plugin. This allows us to generate an interface users
     * can use to create new calendars.
     *
     * @param string $output
     *
     * @return bool
     */
    public function htmlActionsPanel(DAV\INode $node, &$output)
    {
        if (!$node instanceof PrincipalCollection) {
            return;
        }

        $output .= '<tr><td colspan="2"><form method="post" action="">
            <h3>Create new principal</h3>
            <input type="hidden" name="sabreAction" value="mkcol" />
            <input type="hidden" name="resourceType" value="{DAV:}principal" />
            <label>Name (uri):</label> <input type="text" name="name" /><br />
            <label>Display name:</label> <input type="text" name="{DAV:}displayname" /><br />
            <label>Email address:</label> <input type="text" name="{http://sabredav*DOT*org/ns}email-address" /><br />
            <input type="submit" value="create" />
            </form>
            </td></tr>';

        return false;
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
            'description' => 'Adds support for WebDAV ACL (rfc3744)',
            'link' => 'http://sabre.io/dav/acl/',
        ];
    }
}
