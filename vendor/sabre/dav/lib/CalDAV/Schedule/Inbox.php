<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Schedule;

use Sabre\CalDAV;
use Sabre\CalDAV\Backend;
use Sabre\DAV;
use Sabre\DAVACL;
use Sabre\VObject;

/**
 * The CalDAV scheduling inbox.
 *
 * @copyright Copyright (C) fruux GmbH (https://fruux.com/)
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class Inbox extends DAV\Collection implements IInbox
{
    use DAVACL\ACLTrait;

    /**
     * CalDAV backend.
     *
     * @var Backend\BackendInterface
     */
    protected $caldavBackend;

    /**
     * The principal Uri.
     *
     * @var string
     */
    protected $principalUri;

    /**
     * Constructor.
     *
     * @param string $principalUri
     */
    public function __construct(Backend\SchedulingSupport $caldavBackend, $principalUri)
    {
        $this->caldavBackend = $caldavBackend;
        $this->principalUri = $principalUri;
    }

    /**
     * Returns the name of the node.
     *
     * This is used to generate the url.
     *
     * @return string
     */
    public function getName()
    {
        return 'inbox';
    }

    /**
     * Returns an array with all the child nodes.
     *
     * @return \Sabre\DAV\INode[]
     */
    public function getChildren()
    {
        $objs = $this->caldavBackend->getSchedulingObjects($this->principalUri);
        $children = [];
        foreach ($objs as $obj) {
            //$obj['acl'] = $this->getACL();
            $obj['principaluri'] = $this->principalUri;
            $children[] = new SchedulingObject($this->caldavBackend, $obj);
        }

        return $children;
    }

    /**
     * Creates a new file in the directory.
     *
     * Data will either be supplied as a stream resource, or in certain cases
     * as a string. Keep in mind that you may have to support either.
     *
     * After successful creation of the file, you may choose to return the ETag
     * of the new file here.
     *
     * The returned ETag must be surrounded by double-quotes (The quotes should
     * be part of the actual string).
     *
     * If you cannot accurately determine the ETag, you should not return it.
     * If you don't store the file exactly as-is (you're transforming it
     * somehow) you should also not return an ETag.
     *
     * This means that if a subsequent GET to this new file does not exactly
     * return the same contents of what was submitted here, you are strongly
     * recommended to omit the ETag.
     *
     * @param string          $name Name of the file
     * @param resource|string $data Initial payload
     *
     * @return string|null
     */
    public function createFile($name, $data = null)
    {
        $this->caldavBackend->createSchedulingObject($this->principalUri, $name, $data);
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
        return $this->principalUri;
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
        return [
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}write-properties',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => $this->getOwner(),
                'protected' => true,
            ],
            [
                'privilege' => '{DAV:}unbind',
                'principal' => $this->getOwner().'/calendar-proxy-write',
                'protected' => true,
            ],
            [
                'privilege' => '{'.CalDAV\Plugin::NS_CALDAV.'}schedule-deliver',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
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
     * documented by \Sabre\CalDAV\CalendarQueryParser.
     *
     * @return array
     */
    public function calendarQuery(array $filters)
    {
        $result = [];
        $validator = new CalDAV\CalendarQueryValidator();

        $objects = $this->caldavBackend->getSchedulingObjects($this->principalUri);
        foreach ($objects as $object) {
            $vObject = VObject\Reader::read($object['calendardata']);
            if ($validator->validate($vObject, $filters)) {
                $result[] = $object['uri'];
            }

            // Destroy circular references to PHP will GC the object.
            $vObject->destroy();
        }

        return $result;
    }
}
