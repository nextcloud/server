<?php

declare(strict_types=1);

namespace Sabre\CalDAV;

use Sabre\DAV;
use Sabre\DAV\PropPatch;
use Sabre\DAVACL;

/**
 * This object represents a CalDAV calendar.
 *
 * A calendar can contain multiple TODO and or Events. These are represented
 * as \Sabre\CalDAV\CalendarObject objects.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Calendar implements ICalendar, DAV\IProperties, DAV\Sync\ISyncCollection, DAV\IMultiGet
{
    use DAVACL\ACLTrait;

    /**
     * This is an array with calendar information.
     *
     * @var array
     */
    protected $calendarInfo;

    /**
     * CalDAV backend.
     *
     * @var Backend\BackendInterface
     */
    protected $caldavBackend;

    /**
     * Constructor.
     *
     * @param array $calendarInfo
     */
    public function __construct(Backend\BackendInterface $caldavBackend, $calendarInfo)
    {
        $this->caldavBackend = $caldavBackend;
        $this->calendarInfo = $calendarInfo;
    }

    /**
     * Returns the name of the calendar.
     *
     * @return string
     */
    public function getName()
    {
        return $this->calendarInfo['uri'];
    }

    /**
     * Updates properties on this node.
     *
     * This method received a PropPatch object, which contains all the
     * information about the update.
     *
     * To update specific properties, call the 'handle' method on this object.
     * Read the PropPatch documentation for more information.
     */
    public function propPatch(PropPatch $propPatch)
    {
        return $this->caldavBackend->updateCalendar($this->calendarInfo['id'], $propPatch);
    }

    /**
     * Returns the list of properties.
     *
     * @param array $requestedProperties
     *
     * @return array
     */
    public function getProperties($requestedProperties)
    {
        $response = [];

        foreach ($this->calendarInfo as $propName => $propValue) {
            if (!is_null($propValue) && '{' === $propName[0]) {
                $response[$propName] = $this->calendarInfo[$propName];
            }
        }

        return $response;
    }

    /**
     * Returns a calendar object.
     *
     * The contained calendar objects are for example Events or Todo's.
     *
     * @param string $name
     *
     * @return \Sabre\CalDAV\ICalendarObject
     */
    public function getChild($name)
    {
        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);

        if (!$obj) {
            throw new DAV\Exception\NotFound('Calendar object not found');
        }
        $obj['acl'] = $this->getChildACL();

        return new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
    }

    /**
     * Returns the full list of calendar objects.
     *
     * @return array
     */
    public function getChildren()
    {
        $objs = $this->caldavBackend->getCalendarObjects($this->calendarInfo['id']);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
        }

        return $children;
    }

    /**
     * This method receives a list of paths in it's first argument.
     * It must return an array with Node objects.
     *
     * If any children are not found, you do not have to return them.
     *
     * @param string[] $paths
     *
     * @return array
     */
    public function getMultipleChildren(array $paths)
    {
        $objs = $this->caldavBackend->getMultipleCalendarObjects($this->calendarInfo['id'], $paths);
        $children = [];
        foreach ($objs as $obj) {
            $obj['acl'] = $this->getChildACL();
            $children[] = new CalendarObject($this->caldavBackend, $this->calendarInfo, $obj);
        }

        return $children;
    }

    /**
     * Checks if a child-node exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function childExists($name)
    {
        $obj = $this->caldavBackend->getCalendarObject($this->calendarInfo['id'], $name);
        if (!$obj) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Creates a new directory.
     *
     * We actually block this, as subdirectories are not allowed in calendars.
     *
     * @param string $name
     */
    public function createDirectory($name)
    {
        throw new DAV\Exception\MethodNotAllowed('Creating collections in calendar objects is not allowed');
    }

    /**
     * Creates a new file.
     *
     * The contents of the new file must be a valid ICalendar string.
     *
     * @param string   $name
     * @param resource $calendarData
     *
     * @return string|null
     */
    public function createFile($name, $calendarData = null)
    {
        if (is_resource($calendarData)) {
            $calendarData = stream_get_contents($calendarData);
        }

        return $this->caldavBackend->createCalendarObject($this->calendarInfo['id'], $name, $calendarData);
    }

    /**
     * Deletes the calendar.
     */
    public function delete()
    {
        $this->caldavBackend->deleteCalendar($this->calendarInfo['id']);
    }

    /**
     * Renames the calendar. Note that most calendars use the
     * {DAV:}displayname to display a name to display a name.
     *
     * @param string $newName
     */
    public function setName($newName)
    {
        throw new DAV\Exception\MethodNotAllowed('Renaming calendars is not yet supported');
    }

    /**
     * Returns the last modification date as a unix timestamp.
     */
    public function getLastModified()
    {
        return null;
    }

    /**
     * Returns the owner principal.
     *
     * This must be a url to a principal, or null if there's no owner
     *
     * @return string|null
     */
    public function getOwner()
    {
        return $this->calendarInfo['principaluri'];
    }

    /**
     * Returns a list of ACE's for this node.
     *
     * Each ACE has the following properties:
     *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
     *     currently the only supported privileges
     *   * 'principal', a url to the principal who owns the node
     *   * 'protected' (optional), indicating that this ACE is not allowed to
     *      be updated.
     *
     * @return array
     */
    public function getACL()
    {
        $acl = [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-read',
                'protected' => true,
            ],
            [
                'privilege' => '{'.Plugin::NS_CALDAV.'}read-free-busy',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
        if (empty($this->calendarInfo['{http://sabredav.org/ns}read-only'])) {
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ];
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ];
        }

        return $acl;
    }

    /**
     * This method returns the ACL's for calendar objects in this calendar.
     * The result of this method automatically gets passed to the
     * calendar-object nodes in the calendar.
     *
     * @return array
     */
    public function getChildACL()
    {
        $acl = [
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],

            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}read',
                'principal' => $this->getOwner().'/calendar-proxy-read',
                'protected' => true,
            ],
        ];
        if (empty($this->calendarInfo['{http://sabredav.org/ns}read-only'])) {
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner(),
                'protected' => true,
            ];
            $acl[] = [
                'privilege' => '{DAV:}write',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ];
        }

        return $acl;
    }

    /**
     * Performs a calendar-query on the contents of this calendar.
     *
     * The calendar-query is defined in RFC4791 : CalDAV. Using the
     * calendar-query it is possible for a client to request a specific set of
     * object, based on contents of iCalendar properties, date-ranges and
     * iCalendar component types (VTODO, VEVENT).
     *
     * This method should just return a list of (relative) urls that match this
     * query.
     *
     * The list of filters are specified as an array. The exact array is
     * documented by Sabre\CalDAV\CalendarQueryParser.
     *
     * @return array
     */
    public function calendarQuery(array $filters)
    {
        return $this->caldavBackend->calendarQuery($this->calendarInfo['id'], $filters);
    }

    /**
     * This method returns the current sync-token for this collection.
     * This can be any string.
     *
     * If null is returned from this function, the plugin assumes there's no
     * sync information available.
     *
     * @return string|null
     */
    public function getSyncToken()
    {
        if (
            $this->caldavBackend instanceof Backend\SyncSupport &&
            isset($this->calendarInfo['{DAV:}sync-token'])
        ) {
            return $this->calendarInfo['{DAV:}sync-token'];
        }
        if (
            $this->caldavBackend instanceof Backend\SyncSupport &&
            isset($this->calendarInfo['{http://sabredav.org/ns}sync-token'])
        ) {
            return $this->calendarInfo['{http://sabredav.org/ns}sync-token'];
        }
    }

    /**
     * The getChanges method returns all the changes that have happened, since
     * the specified syncToken and the current collection.
     *
     * This function should return an array, such as the following:
     *
     * [
     *   'syncToken' => 'The current synctoken',
     *   'added'   => [
     *      'new.txt',
     *   ],
     *   'modified'   => [
     *      'modified.txt',
     *   ],
     *   'deleted' => [
     *      'foo.php.bak',
     *      'old.txt'
     *   ]
     * ];
     *
     * The syncToken property should reflect the *current* syncToken of the
     * collection, as reported getSyncToken(). This is needed here too, to
     * ensure the operation is atomic.
     *
     * If the syncToken is specified as null, this is an initial sync, and all
     * members should be reported.
     *
     * The modified property is an array of nodenames that have changed since
     * the last token.
     *
     * The deleted property is an array with nodenames, that have been deleted
     * from collection.
     *
     * The second argument is basically the 'depth' of the report. If it's 1,
     * you only have to report changes that happened only directly in immediate
     * descendants. If it's 2, it should also include changes from the nodes
     * below the child collections. (grandchildren)
     *
     * The third (optional) argument allows a client to specify how many
     * results should be returned at most. If the limit is not specified, it
     * should be treated as infinite.
     *
     * If the limit (infinite or not) is higher than you're willing to return,
     * you should throw a Sabre\DAV\Exception\TooMuchMatches() exception.
     *
     * If the syncToken is expired (due to data cleanup) or unknown, you must
     * return null.
     *
     * The limit is 'suggestive'. You are free to ignore it.
     *
     * @param string $syncToken
     * @param int    $syncLevel
     * @param int    $limit
     *
     * @return array
     */
    public function getChanges($syncToken, $syncLevel, $limit = null)
    {
        if (!$this->caldavBackend instanceof Backend\SyncSupport) {
            return null;
        }

        return $this->caldavBackend->getChangesForCalendar(
            $this->calendarInfo['id'],
            $syncToken,
            $syncLevel,
            $limit
        );
    }
}
