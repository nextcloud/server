<?php
/**
 * Copyright (c) 2011 Jakob Sack <mail@jakobsack.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
/*
 *
 * The following SQL statement is just a help for developers and will not be
 * executed!
 *
 * CREATE TABLE calendar_objects (
 *     id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *     calendarid INTEGER UNSIGNED NOT NULL,
 *     objecttype VARCHAR(40) NOT NULL,
 *     startdate DATETIME,
 *     enddate DATETIME,
 *     repeating INT(1),
 *     summary VARCHAR(255),
 *     calendardata TEXT,
 *     uri VARCHAR(100),
 *     lastmodified INT(11)
 * );
 *
 * CREATE TABLE calendar_calendars (
 *     id INTEGER UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
 *     userid VARCHAR(255),
 *     displayname VARCHAR(100),
 *     uri VARCHAR(100),
 *     active INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     ctag INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     calendarorder INTEGER UNSIGNED NOT NULL DEFAULT '0',
 *     calendarcolor VARCHAR(10),
 *     timezone TEXT,
 *     components VARCHAR(20)
 * );
 */

/**
 * This class manages our calendars
 */
class OC_Calendar_Calendar{
	/**
	 * @brief Returns the list of calendars for a specific user.
	 * @param string $uid User ID
	 * @param boolean $active Only return calendars with this $active state, default(=null) is don't care
	 * @return array
	 */
	public static function allCalendars($uid, $active=null){
		$values = array($uid);
		$active_where = '';
		if (!is_null($active) && $active){
			$active_where = ' AND active = ?';
			$values[] = $active;
		}
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE userid = ?' . $active_where );
		$result = $stmt->execute($values);

		$calendars = array();
		while( $row = $result->fetchRow()){
			$calendars[] = $row;
		}

		return $calendars;
	}

	/**
	 * @brief Returns the list of calendars for a principal (DAV term of user)
	 * @param string $principaluri
	 * @return array
	 */
	public static function allCalendarsWherePrincipalURIIs($principaluri){
		$uid = self::extractUserID($principaluri);
		return self::allCalendars($uid);
	}

	/**
	 * @brief Gets the data of one calendar
	 * @param integer $id
	 * @return associative array
	 */
	public static function find($id){
		$stmt = OCP\DB::prepare( 'SELECT * FROM *PREFIX*calendar_calendars WHERE id = ?' );
		$result = $stmt->execute(array($id));

		return $result->fetchRow();
	}

	/**
	 * @brief Creates a new calendar
	 * @param string $userid
	 * @param string $name
	 * @param string $components Default: "VEVENT,VTODO,VJOURNAL"
	 * @param string $timezone Default: null
	 * @param integer $order Default: 1
	 * @param string $color Default: null, format: '#RRGGBB(AA)'
	 * @return insertid
	 */
	public static function addCalendar($userid,$name,$components='VEVENT,VTODO,VJOURNAL',$timezone=null,$order=0,$color=null){
		$all = self::allCalendars($userid);
		$uris = array();
		foreach($all as $i){
			$uris[] = $i['uri'];
		}

		$uri = self::createURI($name, $uris );

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*calendar_calendars (userid,displayname,uri,ctag,calendarorder,calendarcolor,timezone,components) VALUES(?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,1,$order,$color,$timezone,$components));

		return OCP\DB::insertid('*PREFIX*calendar_calendars');
	}

	/**
	 * @brief Creates a new calendar from the data sabredav provides
	 * @param string $principaluri
	 * @param string $uri
	 * @param string $name
	 * @param string $components
	 * @param string $timezone
	 * @param integer $order
	 * @param string $color format: '#RRGGBB(AA)'
	 * @return insertid
	 */
	public static function addCalendarFromDAVData($principaluri,$uri,$name,$components,$timezone,$order,$color){
		$userid = self::extractUserID($principaluri);

		$stmt = OCP\DB::prepare( 'INSERT INTO *PREFIX*calendar_calendars (userid,displayname,uri,ctag,calendarorder,calendarcolor,timezone,components) VALUES(?,?,?,?,?,?,?,?)' );
		$result = $stmt->execute(array($userid,$name,$uri,1,$order,$color,$timezone,$components));

		return OCP\DB::insertid('*PREFIX*calendar_calendars');
	}

	/**
	 * @brief Edits a calendar
	 * @param integer $id
	 * @param string $name Default: null
	 * @param string $components Default: null
	 * @param string $timezone Default: null
	 * @param integer $order Default: null
	 * @param string $color Default: null, format: '#RRGGBB(AA)'
	 * @return boolean
	 *
	 * Values not null will be set
	 */
	public static function editCalendar($id,$name=null,$components=null,$timezone=null,$order=null,$color=null){
		// Need these ones for checking uri
		$calendar = self::find($id);

		// Keep old stuff
		if(is_null($name)) $name = $calendar['displayname'];
		if(is_null($components)) $components = $calendar['components'];
		if(is_null($timezone)) $timezone = $calendar['timezone'];
		if(is_null($order)) $order = $calendar['calendarorder'];
		if(is_null($color)) $color = $calendar['calendarcolor'];

		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET displayname=?,calendarorder=?,calendarcolor=?,timezone=?,components=?,ctag=ctag+1 WHERE id=?' );
		$result = $stmt->execute(array($name,$order,$color,$timezone,$components,$id));

		return true;
	}

	/**
	 * @brief Sets a calendar (in)active
	 * @param integer $id
	 * @param boolean $active
	 * @return boolean
	 */
	public static function setCalendarActive($id,$active){
		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET active = ? WHERE id = ?' );
		$stmt->execute(array($active, $id));

		return true;
	}

	/**
	 * @brief Updates ctag for calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function touchCalendar($id){
		$stmt = OCP\DB::prepare( 'UPDATE *PREFIX*calendar_calendars SET ctag = ctag + 1 WHERE id = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief removes a calendar
	 * @param integer $id
	 * @return boolean
	 */
	public static function deleteCalendar($id){
		$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*calendar_calendars WHERE id = ?' );
		$stmt->execute(array($id));

		$stmt = OCP\DB::prepare( 'DELETE FROM *PREFIX*calendar_objects WHERE calendarid = ?' );
		$stmt->execute(array($id));

		return true;
	}

	/**
	 * @brief Creates a URI for Calendar
	 * @param string $name name of the calendar
	 * @param array  $existing existing calendar URIs
	 * @return string uri
	 */
	public static function createURI($name,$existing){
		$name = strtolower($name);
		$newname = $name;
		$i = 1;
		while(in_array($newname,$existing)){
			$newname = $name.$i;
			$i = $i + 1;
		}
		return $newname;
	}

	/**
	 * @brief gets the userid from a principal path
	 * @return string
	 */
	public static function extractUserID($principaluri){
		list($prefix,$userid) = Sabre_DAV_URLUtil::splitPath($principaluri);
		return $userid;
	}
	public static function getCalendarColorOptions(){
		return array(
			'#ff0000', // "Red"
			'#b3dc6c', // "Green"
			'#ffff00', // "Yellow"
			'#808000', // "Olive"
			'#ffa500', // "Orange"
			'#ff7f50', // "Coral"
			'#ee82ee', // "Violet"
			'#9fc6e7', // "light blue"
		);
	}

	public static function getEventSourceInfo($calendar){
		return array(
			'url' => OCP\Util::linkTo('calendar', 'ajax/events.php').'?calendar_id='.$calendar['id'],
			'backgroundColor' => $calendar['calendarcolor'],
			'borderColor' => '#888',
			'textColor' => 'black',
			'cache' => true,
		);
	}
}
