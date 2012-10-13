<?php

/**
 * SabreDAV ACL Plugin
 *
 * This plugin provides funcitonality to enforce ACL permissions.
 * ACL is defined in RFC3744.
 *
 * In addition it also provides support for the {DAV:}current-user-principal
 * property, defined in RFC5397 and the {DAV:}expand-property report, as
 * defined in RFC3253.
 *
 * @package Sabre
 * @subpackage DAVACL
 * @copyright Copyright (C) 2007-2012 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/)
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAVACL_Plugin extends Sabre_DAV_ServerPlugin {

    /**
     * Recursion constants
     *
     * This only checks the base node
     */
    const R_PARENT = 1;

    /**
     * Recursion constants
     *
     * This checks every node in the tree
     */
    const R_RECURSIVE = 2;

    /**
     * Recursion constants
     *
     * This checks every parentnode in the tree, but not leaf-nodes.
     */
    const R_RECURSIVEPARENTS = 3;

    /**
     * Reference to server object.
     *
     * @var Sabre_DAV_Server
     */
    protected $server;

    /**
     * List of urls containing principal collections.
     * Modify this if your principals are located elsewhere.
     *
     * @var array
     */
    public $principalCollectionSet = array(
        'principals',
    );

    /**
     * By default ACL is only enforced for nodes that have ACL support (the
     * ones that implement Sabre_DAVACL_IACL). For any other node, access is
     * always granted.
     *
     * To override this behaviour you can turn this setting off. This is useful
     * if you plan to fully support ACL in the entire tree.
     *
     * @var bool
     */
    public $allowAccessToNodesWithoutACL = true;

    /**
     * By default nodes that are inaccessible by the user, can still be seen
     * in directory listings (PROPFIND on parent with Depth: 1)
     *
     * In certain cases it's desirable to hide inaccessible nodes. Setting this
     * to true will cause these nodes to be hidden from directory listings.
     *
     * @var bool
     */
    public $hideNodesFromListings = false;

    /**
     * This string is prepended to the username of the currently logged in
     * user. This allows the plugin to determine the principal path based on
     * the username.
     *
     * @var string
     */
    public $defaultUsernamePath = 'principals';

    /**
     * This list of properties are the properties a client can search on using
     * the {DAV:}principal-property-search report.
     *
     * The keys are the property names, values are descriptions.
     *
     * @var array
     */
    public $principalSearchPropertySet = array(
        '{DAV:}displayname' => 'Display name',
        '{http://sabredav.org/ns}email-address' => 'Email address',
    );

    /**
     * Any principal uri's added here, will automatically be added to the list 
     * of ACL's. They will effectively receive {DAV:}all privileges, as a 
     * protected privilege.
     * 
     * @var array 
     */
    public $adminPrincipals = array();

    /**
     * Returns a list of features added by this plugin.
     *
     * This list is used in the response of a HTTP OPTIONS request.
     *
     * @return array
     */
    public function getFeatures() {

        return array('access-control');

    }

    /**
     * Returns a list of available methods for a given url
     *
     * @param string $uri
     * @return array
     */
    public function getMethods($uri) {

        return array('ACL');

    }

    /**
     * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using Sabre_DAV_Server::getPlugin
     *
     * @return string
     */
    public function getPluginName() {

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
     * @return array
     */
    public function getSupportedReportSet($uri) {

        return array(
            '{DAV:}expand-property',
            '{DAV:}principal-property-search',
            '{DAV:}principal-search-property-set',
        );

    }


    /**
     * Checks if the current user has the specified privilege(s).
     *
     * You can specify a single privilege, or a list of privileges.
     * This method will throw an exception if the privilege is not available
     * and return true otherwise.
     *
     * @param string $uri
     * @param array|string $privileges
     * @param int $recursion
     * @param bool $throwExceptions if set to false, this method won't through exceptions.
     * @throws Sabre_DAVACL_Exception_NeedPrivileges
     * @return bool
     */
    public function checkPrivileges($uri, $privileges, $recursion = self::R_PARENT, $throwExceptions = true) {

        if (!is_array($privileges)) $privileges = array($privileges);

        $acl = $this->getCurrentUserPrivilegeSet($uri);

        if (is_null($acl)) {
            if ($this->allowAccessToNodesWithoutACL) {
                return true;
            } else {
                if ($throwExceptions)
                    throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$privileges);
                else
                    return false;

            }
        }

        $failed = array();
        foreach($privileges as $priv) {

            if (!in_array($priv, $acl)) {
                $failed[] = $priv;
            }

        }

        if ($failed) {
            if ($throwExceptions)
                throw new Sabre_DAVACL_Exception_NeedPrivileges($uri,$failed);
            else
                return false;
        }
        return true;

    }

    /**
     * Returns the standard users' principal.
     *
     * This is one authorative principal url for the current user.
     * This method will return null if the user wasn't logged in.
     *
     * @return string|null
     */
    public function getCurrentUserPrincipal() {

        $authPlugin = $this->server->getPlugin('auth');
        if (is_null($authPlugin)) return null;

        $userName = $authPlugin->getCurrentUser();
        if (!$userName) return null;

        return $this->defaultUsernamePath . '/' . $userName;

    }

    /**
     * Returns a list of principals that's associated to the current
     * user, either directly or through group membership.
     *
     * @return array
     */
    public function getCurrentUserPrincipals() {

        $currentUser = $this->getCurrentUserPrincipal();

        if (is_null($currentUser)) return array();

        $check = array($currentUser);
        $principals = array($currentUser);

        while(count($check)) {

            $principal = array_shift($check);

            $node = $this->server->tree->getNodeForPath($principal);
            if ($node instanceof Sabre_DAVACL_IPrincipal) {
                foreach($node->getGroupMembership() as $groupMember) {

                    if (!in_array($groupMember, $principals)) {

                        $check[] = $groupMember;
                        $principals[] = $groupMember;

                    }

                }

            }

        }

        return $principals;

    }

    /**
     * Returns the supported privilege structure for this ACL plugin.
     *
     * See RFC3744 for more details. Currently we default on a simple,
     * standard structure.
     *
     * You can either get the list of privileges by a uri (path) or by
     * specifying a Node.
     *
     * @param string|Sabre_DAV_INode $node
     * @return array
     */
    public function getSupportedPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        if ($node instanceof Sabre_DAVACL_IACL) {
            $result = $node->getSupportedPrivilegeSet();

            if ($result)
                return $result;
        }

        return self::getDefaultSupportedPrivilegeSet();

    }

    /**
     * Returns a fairly standard set of privileges, which may be useful for
     * other systems to use as a basis.
     *
     * @return array
     */
    static function getDefaultSupportedPrivilegeSet() {

        return array(
            'privilege'  => '{DAV:}all',
            'abstract'   => true,
            'aggregates' => array(
                array(
                    'privilege'  => '{DAV:}read',
                    'aggregates' => array(
                        array(
                            'privilege' => '{DAV:}read-acl',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}read-current-user-privilege-set',
                            'abstract'  => true,
                        ),
                    ),
                ), // {DAV:}read
                array(
                    'privilege'  => '{DAV:}write',
                    'aggregates' => array(
                        array(
                            'privilege' => '{DAV:}write-acl',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}write-properties',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}write-content',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}bind',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}unbind',
                            'abstract'  => true,
                        ),
                        array(
                            'privilege' => '{DAV:}unlock',
                            'abstract'  => true,
                        ),
                    ),
                ), // {DAV:}write
            ),
        ); // {DAV:}all

    }

    /**
     * Returns the supported privilege set as a flat list
     *
     * This is much easier to parse.
     *
     * The returned list will be index by privilege name.
     * The value is a struct containing the following properties:
     *   - aggregates
     *   - abstract
     *   - concrete
     *
     * @param string|Sabre_DAV_INode $node
     * @return array
     */
    final public function getFlatPrivilegeSet($node) {

        $privs = $this->getSupportedPrivilegeSet($node);

        $flat = array();
        $this->getFPSTraverse($privs, null, $flat);

        return $flat;

    }

    /**
     * Traverses the privilege set tree for reordering
     *
     * This function is solely used by getFlatPrivilegeSet, and would have been
     * a closure if it wasn't for the fact I need to support PHP 5.2.
     *
     * @param array $priv
     * @param $concrete
     * @param array $flat
     * @return void
     */
    final private function getFPSTraverse($priv, $concrete, &$flat) {

        $myPriv = array(
            'privilege' => $priv['privilege'],
            'abstract' => isset($priv['abstract']) && $priv['abstract'],
            'aggregates' => array(),
            'concrete' => isset($priv['abstract']) && $priv['abstract']?$concrete:$priv['privilege'],
        );

        if (isset($priv['aggregates']))
            foreach($priv['aggregates'] as $subPriv) $myPriv['aggregates'][] = $subPriv['privilege'];

        $flat[$priv['privilege']] = $myPriv;

        if (isset($priv['aggregates'])) {

            foreach($priv['aggregates'] as $subPriv) {

                $this->getFPSTraverse($subPriv, $myPriv['concrete'], $flat);

            }

        }

    }

    /**
     * Returns the full ACL list.
     *
     * Either a uri or a Sabre_DAV_INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|Sabre_DAV_INode $node
     * @return array
     */
    public function getACL($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }
        if (!$node instanceof Sabre_DAVACL_IACL) {
            return null;
        }
        $acl = $node->getACL();
        foreach($this->adminPrincipals as $adminPrincipal) {
            $acl[] = array(
                'principal' => $adminPrincipal,
                'privilege' => '{DAV:}all',
                'protected' => true,
            );
        }
        return $acl;

    }

    /**
     * Returns a list of privileges the current user has
     * on a particular node.
     *
     * Either a uri or a Sabre_DAV_INode may be passed.
     *
     * null will be returned if the node doesn't support ACLs.
     *
     * @param string|Sabre_DAV_INode $node
     * @return array
     */
    public function getCurrentUserPrivilegeSet($node) {

        if (is_string($node)) {
            $node = $this->server->tree->getNodeForPath($node);
        }

        $acl = $this->getACL($node);

        if (is_null($acl)) return null;

        $principals = $this->getCurrentUserPrincipals();

        $collected = array();

        foreach($acl as $ace) {

            $principal = $ace['principal'];

            switch($principal) {

                case '{DAV:}owner' :
                    $owner = $node->getOwner();
                    if ($owner && in_array($owner, $principals)) {
                        $collected[] = $ace;
                    }
                    break;


                // 'all' matches for every user
                case '{DAV:}all' :

                // 'authenticated' matched for every user that's logged in.
                // Since it's not possible to use ACL while not being logged
                // in, this is also always true.
                case '{DAV:}authenticated' :
                    $collected[] = $ace;
                    break;

                // 'unauthenticated' can never occur either, so we simply
                // ignore these.
                case '{DAV:}unauthenticated' :
                    break;

                default :
                    if (in_array($ace['principal'], $principals)) {
                        $collected[] = $ace;
                    }
                    break;

            }



        }

        // Now we deduct all aggregated privileges.
        $flat = $this->getFlatPrivilegeSet($node);

        $collected2 = array();
        while(count($collected)) {

            $current = array_pop($collected);
            $collected2[] = $current['privilege'];

            foreach($flat[$current['privilege']]['aggregates'] as $subPriv) {
                $collected2[] = $subPriv;
                $collected[] = $flat[$subPriv];
            }

        }

        return array_values(array_unique($collected2));

    }

    /**
     * Principal property search
     *
     * This method can search for principals matching certain values in
     * properties.
     *
     * This method will return a list of properties for the matched properties.
     *
     * @param array $searchProperties    The properties to search on. This is a
     *                                   key-value list. The keys are property
     *                                   names, and the values the strings to
     *                                   match them on.
     * @param array $requestedProperties This is the list of properties to
     *                                   return for every match.
     * @param string $collectionUri      The principal collection to search on.
     *                                   If this is ommitted, the standard
     *                                   principal collection-set will be used.
     * @return array     This method returns an array structure similar to
     *                  Sabre_DAV_Server::getPropertiesForPath. Returned
     *                  properties are index by a HTTP status code.
     *
     */
    public function principalSearch(array $searchProperties, array $requestedProperties, $collectionUri = null) {

        if (!is_null($collectionUri)) {
            $uris = array($collectionUri);
        } else {
            $uris = $this->principalCollectionSet;
        }

        $lookupResults = array();
        foreach($uris as $uri) {

            $principalCollection = $this->server->tree->getNodeForPath($uri);
            if (!$principalCollection instanceof Sabre_DAVACL_AbstractPrincipalCollection) {
                // Not a principal collection, we're simply going to ignore
                // this.
                continue;
            }

            $results = $principalCollection->searchPrincipals($searchProperties);
            foreach($results as $result) {
                $lookupResults[] = rtrim($uri,'/') . '/' . $result;
            }

        }

        $matches = array();

        foreach($lookupResults as $lookupResult) {

            list($matches[]) = $this->server->getPropertiesForPath($lookupResult, $requestedProperties, 0);

        }

        return $matches;

    }

    /**
     * Sets up the plugin
     *
     * This method is automatically called by the server class.
     *
     * @param Sabre_DAV_Server $server
     * @return void
     */
    public function initialize(Sabre_DAV_Server $server) {

        $this->server = $server;
        $server->subscribeEvent('beforeGetProperties',array($this,'beforeGetProperties'));

        $server->subscribeEvent('beforeMethod', array($this,'beforeMethod'),20);
        $server->subscribeEvent('beforeBind', array($this,'beforeBind'),20);
        $server->subscribeEvent('beforeUnbind', array($this,'beforeUnbind'),20);
        $server->subscribeEvent('updateProperties',array($this,'updateProperties'));
        $server->subscribeEvent('beforeUnlock', array($this,'beforeUnlock'),20);
        $server->subscribeEvent('report',array($this,'report'));
        $server->subscribeEvent('unknownMethod', array($this, 'unknownMethod'));

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
        $server->resourceTypeMapping['Sabre_DAVACL_IPrincipal'] = '{DAV:}principal';

        // Mapping the group-member-set property to the HrefList property
        // class.
        $server->propertyMap['{DAV:}group-member-set'] = 'Sabre_DAV_Property_HrefList';

    }


    /* {{{ Event handlers */

    /**
     * Triggered before any method is handled
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function beforeMethod($method, $uri) {

        $exists = $this->server->tree->nodeExists($uri);

        // If the node doesn't exists, none of these checks apply
        if (!$exists) return;

        switch($method) {

            case 'GET' :
            case 'HEAD' :
            case 'OPTIONS' :
                // For these 3 we only need to know if the node is readable.
                $this->checkPrivileges($uri,'{DAV:}read');
                break;

            case 'PUT' :
            case 'LOCK' :
            case 'UNLOCK' :
                // This method requires the write-content priv if the node
                // already exists, and bind on the parent if the node is being
                // created.
                // The bind privilege is handled in the beforeBind event.
                $this->checkPrivileges($uri,'{DAV:}write-content');
                break;


            case 'PROPPATCH' :
                $this->checkPrivileges($uri,'{DAV:}write-properties');
                break;

            case 'ACL' :
                $this->checkPrivileges($uri,'{DAV:}write-acl');
                break;

            case 'COPY' :
            case 'MOVE' :
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
                $this->checkPrivileges($uri,'{DAV:}read',self::R_RECURSIVE);

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
     * @return void
     */
    public function beforeBind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}bind');

    }

    /**
     * Triggered before a node is deleted
     *
     * This allows us to check permissions for any operation that will delete
     * an existing node.
     *
     * @param string $uri
     * @return void
     */
    public function beforeUnbind($uri) {

        list($parentUri,$nodeName) = Sabre_DAV_URLUtil::splitPath($uri);
        $this->checkPrivileges($parentUri,'{DAV:}unbind',self::R_RECURSIVEPARENTS);

    }

    /**
     * Triggered before a node is unlocked.
     *
     * @param string $uri
     * @param Sabre_DAV_Locks_LockInfo $lock
     * @TODO: not yet implemented
     * @return void
     */
    public function beforeUnlock($uri, Sabre_DAV_Locks_LockInfo $lock) {


    }

    /**
     * Triggered before properties are looked up in specific nodes.
     *
     * @param string $uri
     * @param Sabre_DAV_INode $node
     * @param array $requestedProperties
     * @param array $returnedProperties
     * @TODO really should be broken into multiple methods, or even a class.
     * @return void
     */
    public function beforeGetProperties($uri, Sabre_DAV_INode $node, &$requestedProperties, &$returnedProperties) {

        // Checking the read permission
        if (!$this->checkPrivileges($uri,'{DAV:}read',self::R_PARENT,false)) {

            // User is not allowed to read properties
            if ($this->hideNodesFromListings) {
                return false;
            }

            // Marking all requested properties as '403'.
            foreach($requestedProperties as $key=>$requestedProperty) {
                unset($requestedProperties[$key]);
                $returnedProperties[403][$requestedProperty] = null;
            }
            return;

        }

        /* Adding principal properties */
        if ($node instanceof Sabre_DAVACL_IPrincipal) {

            if (false !== ($index = array_search('{DAV:}alternate-URI-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}alternate-URI-set'] = new Sabre_DAV_Property_HrefList($node->getAlternateUriSet());

            }
            if (false !== ($index = array_search('{DAV:}principal-URL', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}principal-URL'] = new Sabre_DAV_Property_Href($node->getPrincipalUrl() . '/');

            }
            if (false !== ($index = array_search('{DAV:}group-member-set', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-member-set'] = new Sabre_DAV_Property_HrefList($node->getGroupMemberSet());

            }
            if (false !== ($index = array_search('{DAV:}group-membership', $requestedProperties))) {

                unset($requestedProperties[$index]);
                $returnedProperties[200]['{DAV:}group-membership'] = new Sabre_DAV_Property_HrefList($node->getGroupMembership());

            }

            if (false !== ($index = array_search('{DAV:}displayname', $requestedProperties))) {

                $returnedProperties[200]['{DAV:}displayname'] = $node->getDisplayName();

            }

        }
        if (false !== ($index = array_search('{DAV:}principal-collection-set', $requestedProperties))) {

            unset($requestedProperties[$index]);
            $val = $this->principalCollectionSet;
            // Ensuring all collections end with a slash
            foreach($val as $k=>$v) $val[$k] = $v . '/';
            $returnedProperties[200]['{DAV:}principal-collection-set'] = new Sabre_DAV_Property_HrefList($val);

        }
        if (false !== ($index = array_search('{DAV:}current-user-principal', $requestedProperties))) {

            unset($requestedProperties[$index]);
            if ($url = $this->getCurrentUserPrincipal()) {
                $returnedProperties[200]['{DAV:}current-user-principal'] = new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::HREF, $url . '/');
            } else {
                $returnedProperties[200]['{DAV:}current-user-principal'] = new Sabre_DAVACL_Property_Principal(Sabre_DAVACL_Property_Principal::UNAUTHENTICATED);
            }

        }
        if (false !== ($index = array_search('{DAV:}supported-privilege-set', $requestedProperties))) {

            unset($requestedProperties[$index]);
            $returnedProperties[200]['{DAV:}supported-privilege-set'] = new Sabre_DAVACL_Property_SupportedPrivilegeSet($this->getSupportedPrivilegeSet($node));

        }
        if (false !== ($index = array_search('{DAV:}current-user-privilege-set', $requestedProperties))) {

            if (!$this->checkPrivileges($uri, '{DAV:}read-current-user-privilege-set', self::R_PARENT, false)) {
                $returnedProperties[403]['{DAV:}current-user-privilege-set'] = null;
                unset($requestedProperties[$index]);
            } else {
                $val = $this->getCurrentUserPrivilegeSet($node);
                if (!is_null($val)) {
                    unset($requestedProperties[$index]);
                    $returnedProperties[200]['{DAV:}current-user-privilege-set'] = new Sabre_DAVACL_Property_CurrentUserPrivilegeSet($val);
                }
            }

        }

        /* The ACL property contains all the permissions */
        if (false !== ($index = array_search('{DAV:}acl', $requestedProperties))) {

            if (!$this->checkPrivileges($uri, '{DAV:}read-acl', self::R_PARENT, false)) {

                unset($requestedProperties[$index]);
                $returnedProperties[403]['{DAV:}acl'] = null;

            } else {

                $acl = $this->getACL($node);
                if (!is_null($acl)) {
                    unset($requestedProperties[$index]);
                    $returnedProperties[200]['{DAV:}acl'] = new Sabre_DAVACL_Property_Acl($this->getACL($node));
                }

            }

        }

        /* The acl-restrictions property contains information on how privileges
         * must behave.
         */
        if (false !== ($index = array_search('{DAV:}acl-restrictions', $requestedProperties))) {
            unset($requestedProperties[$index]);
            $returnedProperties[200]['{DAV:}acl-restrictions'] = new Sabre_DAVACL_Property_AclRestrictions();
        }

    }

    /**
     * This method intercepts PROPPATCH methods and make sure the
     * group-member-set is updated correctly.
     *
     * @param array $propertyDelta
     * @param array $result
     * @param Sabre_DAV_INode $node
     * @return bool
     */
    public function updateProperties(&$propertyDelta, &$result, Sabre_DAV_INode $node) {

        if (!array_key_exists('{DAV:}group-member-set', $propertyDelta))
            return;

        if (is_null($propertyDelta['{DAV:}group-member-set'])) {
            $memberSet = array();
        } elseif ($propertyDelta['{DAV:}group-member-set'] instanceof Sabre_DAV_Property_HrefList) {
            $memberSet = $propertyDelta['{DAV:}group-member-set']->getHrefs();
        } else {
            throw new Sabre_DAV_Exception('The group-member-set property MUST be an instance of Sabre_DAV_Property_HrefList or null');
        }

        if (!($node instanceof Sabre_DAVACL_IPrincipal)) {
            $result[403]['{DAV:}group-member-set'] = null;
            unset($propertyDelta['{DAV:}group-member-set']);

            // Returning false will stop the updateProperties process
            return false;
        }

        $node->setGroupMemberSet($memberSet);

        $result[200]['{DAV:}group-member-set'] = null;
        unset($propertyDelta['{DAV:}group-member-set']);

    }

    /**
     * This method handels HTTP REPORT requests
     *
     * @param string $reportName
     * @param DOMNode $dom
     * @return bool
     */
    public function report($reportName, $dom) {

        switch($reportName) {

            case '{DAV:}principal-property-search' :
                $this->principalPropertySearchReport($dom);
                return false;
            case '{DAV:}principal-search-property-set' :
                $this->principalSearchPropertySetReport($dom);
                return false;
            case '{DAV:}expand-property' :
                $this->expandPropertyReport($dom);
                return false;

        }

    }

    /**
     * This event is triggered for any HTTP method that is not known by the
     * webserver.
     *
     * @param string $method
     * @param string $uri
     * @return bool
     */
    public function unknownMethod($method, $uri) {

        if ($method!=='ACL') return;

        $this->httpACL($uri);
        return false;

    }

    /**
     * This method is responsible for handling the 'ACL' event.
     *
     * @param string $uri
     * @return void
     */
    public function httpACL($uri) {

        $body = $this->server->httpRequest->getBody(true);
        $dom = Sabre_DAV_XMLUtil::loadDOMDocument($body);

        $newAcl =
            Sabre_DAVACL_Property_Acl::unserialize($dom->firstChild)
            ->getPrivileges();

        // Normalizing urls
        foreach($newAcl as $k=>$newAce) {
            $newAcl[$k]['principal'] = $this->server->calculateUri($newAce['principal']);
        }

        $node = $this->server->tree->getNodeForPath($uri);

        if (!($node instanceof Sabre_DAVACL_IACL)) {
            throw new Sabre_DAV_Exception_MethodNotAllowed('This node does not support the ACL method');
        }

        $oldAcl = $this->getACL($node);

        $supportedPrivileges = $this->getFlatPrivilegeSet($node);

        /* Checking if protected principals from the existing principal set are
           not overwritten. */
        foreach($oldAcl as $oldAce) {

            if (!isset($oldAce['protected']) || !$oldAce['protected']) continue;

            $found = false;
            foreach($newAcl as $newAce) {
                if (
                    $newAce['privilege'] === $oldAce['privilege'] &&
                    $newAce['principal'] === $oldAce['principal'] &&
                    $newAce['protected']
                )
                $found = true;
            }

            if (!$found)
                throw new Sabre_DAVACL_Exception_AceConflict('This resource contained a protected {DAV:}ace, but this privilege did not occur in the ACL request');

        }

        foreach($newAcl as $newAce) {

            // Do we recognize the privilege
            if (!isset($supportedPrivileges[$newAce['privilege']])) {
                throw new Sabre_DAVACL_Exception_NotSupportedPrivilege('The privilege you specified (' . $newAce['privilege'] . ') is not recognized by this server');
            }

            if ($supportedPrivileges[$newAce['privilege']]['abstract']) {
                throw new Sabre_DAVACL_Exception_NoAbstract('The privilege you specified (' . $newAce['privilege'] . ') is an abstract privilege');
            }

            // Looking up the principal
            try {
                $principal = $this->server->tree->getNodeForPath($newAce['principal']);
            } catch (Sabre_DAV_Exception_NotFound $e) {
                throw new Sabre_DAVACL_Exception_NotRecognizedPrincipal('The specified principal (' . $newAce['principal'] . ') does not exist');
            }
            if (!($principal instanceof Sabre_DAVACL_IPrincipal)) {
                throw new Sabre_DAVACL_Exception_NotRecognizedPrincipal('The specified uri (' . $newAce['principal'] . ') is not a principal');
            }

        }
        $node->setACL($newAcl);

    }

    /* }}} */

    /* Reports {{{ */

    /**
     * The expand-property report is defined in RFC3253 section 3-8.
     *
     * This report is very similar to a standard PROPFIND. The difference is
     * that it has the additional ability to look at properties containing a
     * {DAV:}href element, follow that property and grab additional elements
     * there.
     *
     * Other rfc's, such as ACL rely on this report, so it made sense to put
     * it in this plugin.
     *
     * @param DOMElement $dom
     * @return void
     */
    protected function expandPropertyReport($dom) {

        $requestedProperties = $this->parseExpandPropertyReportRequest($dom->firstChild->firstChild);
        $depth = $this->server->getHTTPDepth(0);
        $requestUri = $this->server->getRequestUri();

        $result = $this->expandProperties($requestUri,$requestedProperties,$depth);

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $multiStatus = $dom->createElement('d:multistatus');
        $dom->appendChild($multiStatus);

        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $multiStatus->setAttribute('xmlns:' . $prefix,$namespace);

        }

        foreach($result as $response) {
            $response->serialize($this->server, $multiStatus);
        }

        $xml = $dom->saveXML();
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

    }

    /**
     * This method is used by expandPropertyReport to parse
     * out the entire HTTP request.
     *
     * @param DOMElement $node
     * @return array
     */
    protected function parseExpandPropertyReportRequest($node) {

        $requestedProperties = array();
        do {

            if (Sabre_DAV_XMLUtil::toClarkNotation($node)!=='{DAV:}property') continue;

            if ($node->firstChild) {

                $children = $this->parseExpandPropertyReportRequest($node->firstChild);

            } else {

                $children = array();

            }

            $namespace = $node->getAttribute('namespace');
            if (!$namespace) $namespace = 'DAV:';

            $propName = '{'.$namespace.'}' . $node->getAttribute('name');
            $requestedProperties[$propName] = $children;

        } while ($node = $node->nextSibling);

        return $requestedProperties;

    }

    /**
     * This method expands all the properties and returns
     * a list with property values
     *
     * @param array $path
     * @param array $requestedProperties the list of required properties
     * @param int $depth
     * @return array
     */
    protected function expandProperties($path, array $requestedProperties, $depth) {

        $foundProperties = $this->server->getPropertiesForPath($path, array_keys($requestedProperties), $depth);

        $result = array();

        foreach($foundProperties as $node) {

            foreach($requestedProperties as $propertyName=>$childRequestedProperties) {

                // We're only traversing if sub-properties were requested
                if(count($childRequestedProperties)===0) continue;

                // We only have to do the expansion if the property was found
                // and it contains an href element.
                if (!array_key_exists($propertyName,$node[200])) continue;

                if ($node[200][$propertyName] instanceof Sabre_DAV_Property_IHref) {
                    $hrefs = array($node[200][$propertyName]->getHref());
                } elseif ($node[200][$propertyName] instanceof Sabre_DAV_Property_HrefList) {
                    $hrefs = $node[200][$propertyName]->getHrefs();
                }

                $childProps = array();
                foreach($hrefs as $href) {
                    $childProps = array_merge($childProps, $this->expandProperties($href, $childRequestedProperties, 0));
                }
                $node[200][$propertyName] = new Sabre_DAV_Property_ResponseList($childProps);

            }
            $result[] = new Sabre_DAV_Property_Response($path, $node);

        }

        return $result;

    }

    /**
     * principalSearchPropertySetReport
     *
     * This method responsible for handing the
     * {DAV:}principal-search-property-set report. This report returns a list
     * of properties the client may search on, using the
     * {DAV:}principal-property-search report.
     *
     * @param DOMDocument $dom
     * @return void
     */
    protected function principalSearchPropertySetReport(DOMDocument $dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }

        if ($dom->firstChild->hasChildNodes())
            throw new Sabre_DAV_Exception_BadRequest('The principal-search-property-set report element is not allowed to have child elements');

        $dom = new DOMDocument('1.0','utf-8');
        $dom->formatOutput = true;
        $root = $dom->createElement('d:principal-search-property-set');
        $dom->appendChild($root);
        // Adding in default namespaces
        foreach($this->server->xmlNamespaces as $namespace=>$prefix) {

            $root->setAttribute('xmlns:' . $prefix,$namespace);

        }

        $nsList = $this->server->xmlNamespaces;

        foreach($this->principalSearchPropertySet as $propertyName=>$description) {

            $psp = $dom->createElement('d:principal-search-property');
            $root->appendChild($psp);

            $prop = $dom->createElement('d:prop');
            $psp->appendChild($prop);

            $propName = null;
            preg_match('/^{([^}]*)}(.*)$/',$propertyName,$propName);

            $currentProperty = $dom->createElement($nsList[$propName[1]] . ':' . $propName[2]);
            $prop->appendChild($currentProperty);

            $descriptionElem = $dom->createElement('d:description');
            $descriptionElem->setAttribute('xml:lang','en');
            $descriptionElem->appendChild($dom->createTextNode($description));
            $psp->appendChild($descriptionElem);


        }

        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(200);
        $this->server->httpResponse->sendBody($dom->saveXML());

    }

    /**
     * principalPropertySearchReport
     *
     * This method is responsible for handing the
     * {DAV:}principal-property-search report. This report can be used for
     * clients to search for groups of principals, based on the value of one
     * or more properties.
     *
     * @param DOMDocument $dom
     * @return void
     */
    protected function principalPropertySearchReport(DOMDocument $dom) {

        list($searchProperties, $requestedProperties, $applyToPrincipalCollectionSet) = $this->parsePrincipalPropertySearchReportRequest($dom);

        $uri = null;
        if (!$applyToPrincipalCollectionSet) {
            $uri = $this->server->getRequestUri();
        }
        $result = $this->principalSearch($searchProperties, $requestedProperties, $uri);

        $xml = $this->server->generateMultiStatus($result);
        $this->server->httpResponse->setHeader('Content-Type','application/xml; charset=utf-8');
        $this->server->httpResponse->sendStatus(207);
        $this->server->httpResponse->sendBody($xml);

    }

    /**
     * parsePrincipalPropertySearchReportRequest
     *
     * This method parses the request body from a
     * {DAV:}principal-property-search report.
     *
     * This method returns an array with two elements:
     *  1. an array with properties to search on, and their values
     *  2. a list of propertyvalues that should be returned for the request.
     *
     * @param DOMDocument $dom
     * @return array
     */
    protected function parsePrincipalPropertySearchReportRequest($dom) {

        $httpDepth = $this->server->getHTTPDepth(0);
        if ($httpDepth!==0) {
            throw new Sabre_DAV_Exception_BadRequest('This report is only defined when Depth: 0');
        }

        $searchProperties = array();

        $applyToPrincipalCollectionSet = false;

        // Parsing the search request
        foreach($dom->firstChild->childNodes as $searchNode) {

            if (Sabre_DAV_XMLUtil::toClarkNotation($searchNode) == '{DAV:}apply-to-principal-collection-set') {
                $applyToPrincipalCollectionSet = true;
            }

            if (Sabre_DAV_XMLUtil::toClarkNotation($searchNode)!=='{DAV:}property-search')
                continue;

            $propertyName = null;
            $propertyValue = null;

            foreach($searchNode->childNodes as $childNode) {

                switch(Sabre_DAV_XMLUtil::toClarkNotation($childNode)) {

                    case '{DAV:}prop' :
                        $property = Sabre_DAV_XMLUtil::parseProperties($searchNode);
                        reset($property);
                        $propertyName = key($property);
                        break;

                    case '{DAV:}match' :
                        $propertyValue = $childNode->textContent;
                        break;

                }


            }

            if (is_null($propertyName) || is_null($propertyValue))
                throw new Sabre_DAV_Exception_BadRequest('Invalid search request. propertyname: ' . $propertyName . '. propertvvalue: ' . $propertyValue);

            $searchProperties[$propertyName] = $propertyValue;

        }

        return array($searchProperties, array_keys(Sabre_DAV_XMLUtil::parseProperties($dom->firstChild)), $applyToPrincipalCollectionSet);

    }


    /* }}} */

}
