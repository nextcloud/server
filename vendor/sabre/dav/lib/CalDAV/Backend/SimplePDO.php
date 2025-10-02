<?php

declare(strict_types=1);

namespace Sabre\CalDAV\Backend;

use Sabre\CalDAV;
use Sabre\DAV;

/**
 * Simple PDO CalDAV backend.
 *
 * This class is basically the most minimum example to get a caldav backend up
 * and running. This class uses the following schema (MySQL example):
 *
 * CREATE TABLE simple_calendars (
 *    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *    uri VARBINARY(200) NOT NULL,
 *    principaluri VARBINARY(200) NOT NULL
 * );
 *
 * CREATE TABLE simple_calendarobjects (
 *    id INT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *    calendarid INT UNSIGNED NOT NULL,
 *    uri VARBINARY(200) NOT NULL,
 *    calendardata MEDIUMBLOB
 * )
 *
 * To make this class work, you absolutely need to have the PropertyStorage
 * plugin enabled.
 *
 * @copyright Copyright (C) 2007-2015 fruux GmbH (https://fruux.com/).
 * @author Evert Pot (http://evertpot.com/)
 * @license http://sabre.io/license/ Modified BSD License
 */
class SimplePDO extends AbstractBackend
{
    /**
     * pdo.
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Creates the backend.
     */
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns a list of calendars for a principal.
     *
     * Every project is an array with the following keys:
     *  * id, a unique id that will be used by other functions to modify the
     *    calendar. This can be the same as the uri or a database key.
     *  * uri. This is just the 'base uri' or 'filename' of the calendar.
     *  * principaluri. The owner of the calendar. Almost always the same as
     *    principalUri passed to this method.
     *
     * Furthermore it can contain webdav properties in clark notation. A very
     * common one is '{DAV:}displayname'.
     *
     * Many clients also require:
     * {urn:ietf:params:xml:ns:caldav}supported-calendar-component-set
     * For this property, you can just return an instance of
     * Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet.
     *
     * If you return {http://sabredav.org/ns}read-only and set the value to 1,
     * ACL will automatically be put in read-only mode.
     *
     * @param string $principalUri
     *
     * @return array
     */
    public function getCalendarsForUser($principalUri)
    {
        // Making fields a comma-delimited list
        $stmt = $this->pdo->prepare('SELECT id, uri FROM simple_calendars WHERE principaluri = ? ORDER BY id ASC');
        $stmt->execute([$principalUri]);

        $calendars = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $calendars[] = [
                'id' => $row['id'],
                'uri' => $row['uri'],
                'principaluri' => $principalUri,
            ];
        }

        return $calendars;
    }

    /**
     * Creates a new calendar for a principal.
     *
     * If the creation was a success, an id must be returned that can be used
     * to reference this calendar in other methods, such as updateCalendar.
     *
     * @param string $principalUri
     * @param string $calendarUri
     *
     * @return string
     */
    public function createCalendar($principalUri, $calendarUri, array $properties)
    {
        $stmt = $this->pdo->prepare('INSERT INTO simple_calendars (principaluri, uri) VALUES (?, ?)');
        $stmt->execute([$principalUri, $calendarUri]);

        return $this->pdo->lastInsertId();
    }

    /**
     * Delete a calendar and all it's objects.
     *
     * @param string $calendarId
     */
    public function deleteCalendar($calendarId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM simple_calendarobjects WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $stmt = $this->pdo->prepare('DELETE FROM simple_calendars WHERE id = ?');
        $stmt->execute([$calendarId]);
    }

    /**
     * Returns all calendar objects within a calendar.
     *
     * Every item contains an array with the following keys:
     *   * calendardata - The iCalendar-compatible calendar data
     *   * uri - a unique key which will be used to construct the uri. This can
     *     be any arbitrary string, but making sure it ends with '.ics' is a
     *     good idea. This is only the basename, or filename, not the full
     *     path.
     *   * lastmodified - a timestamp of the last modification time
     *   * etag - An arbitrary string, surrounded by double-quotes. (e.g.:
     *   '  "abcdef"')
     *   * size - The size of the calendar objects, in bytes.
     *   * component - optional, a string containing the type of object, such
     *     as 'vevent' or 'vtodo'. If specified, this will be used to populate
     *     the Content-Type header.
     *
     * Note that the etag is optional, but it's highly encouraged to return for
     * speed reasons.
     *
     * The calendardata is also optional. If it's not returned
     * 'getCalendarObject' will be called later, which *is* expected to return
     * calendardata.
     *
     * If neither etag or size are specified, the calendardata will be
     * used/fetched to determine these numbers. If both are specified the
     * amount of times this is needed is reduced by a great degree.
     *
     * @param string $calendarId
     *
     * @return array
     */
    public function getCalendarObjects($calendarId)
    {
        $stmt = $this->pdo->prepare('SELECT id, uri, calendardata FROM simple_calendarobjects WHERE calendarid = ?');
        $stmt->execute([$calendarId]);

        $result = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $result[] = [
                'id' => $row['id'],
                'uri' => $row['uri'],
                'etag' => '"'.md5($row['calendardata']).'"',
                'calendarid' => $calendarId,
                'size' => strlen($row['calendardata']),
                'calendardata' => $row['calendardata'],
            ];
        }

        return $result;
    }

    /**
     * Returns information from a single calendar object, based on it's object
     * uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * The returned array must have the same keys as getCalendarObjects. The
     * 'calendardata' object is required here though, while it's not required
     * for getCalendarObjects.
     *
     * This method must return null if the object did not exist.
     *
     * @param string $calendarId
     * @param string $objectUri
     *
     * @return array|null
     */
    public function getCalendarObject($calendarId, $objectUri)
    {
        $stmt = $this->pdo->prepare('SELECT id, uri, calendardata FROM simple_calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'id' => $row['id'],
            'uri' => $row['uri'],
            'etag' => '"'.md5($row['calendardata']).'"',
            'calendarid' => $calendarId,
            'size' => strlen($row['calendardata']),
            'calendardata' => $row['calendardata'],
         ];
    }

    /**
     * Creates a new calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function createCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $stmt = $this->pdo->prepare('INSERT INTO simple_calendarobjects (calendarid, uri, calendardata) VALUES (?,?,?)');
        $stmt->execute([
            $calendarId,
            $objectUri,
            $calendarData,
        ]);

        return '"'.md5($calendarData).'"';
    }

    /**
     * Updates an existing calendarobject, based on it's uri.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * It is possible return an etag from this function, which will be used in
     * the response to this PUT request. Note that the ETag must be surrounded
     * by double-quotes.
     *
     * However, you should only really return this ETag if you don't mangle the
     * calendar-data. If the result of a subsequent GET to this object is not
     * the exact same as this request body, you should omit the ETag.
     *
     * @param mixed  $calendarId
     * @param string $objectUri
     * @param string $calendarData
     *
     * @return string|null
     */
    public function updateCalendarObject($calendarId, $objectUri, $calendarData)
    {
        $stmt = $this->pdo->prepare('UPDATE simple_calendarobjects SET calendardata = ? WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarData, $calendarId, $objectUri]);

        return '"'.md5($calendarData).'"';
    }

    /**
     * Deletes an existing calendar object.
     *
     * The object uri is only the basename, or filename and not a full path.
     *
     * @param string $calendarId
     * @param string $objectUri
     */
    public function deleteCalendarObject($calendarId, $objectUri)
    {
        $stmt = $this->pdo->prepare('DELETE FROM simple_calendarobjects WHERE calendarid = ? AND uri = ?');
        $stmt->execute([$calendarId, $objectUri]);
    }
}
