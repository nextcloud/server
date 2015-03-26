<?php
/**
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Jakob Sack <mail@jakobsack.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Sam Tuke <mail@samtuke.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
class OC_Hook{
	public static $thrownExceptions = [];

	static private $registered = array();

	/**
	 * connects a function to a hook
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param string $slotclass class name of slot
	 * @param string $slotname name of slot
	 * @return bool
	 *
	 * This function makes it very easy to connect to use hooks.
	 *
	 * TODO: write example
	 */
	static public function connect( $signalclass, $signalname, $slotclass, $slotname ) {
		// If we're trying to connect to an emitting class that isn't
		// yet registered, register it
		if( !array_key_exists( $signalclass, self::$registered )) {
			self::$registered[$signalclass] = array();
		}
		// If we're trying to connect to an emitting method that isn't
		// yet registered, register it with the emitting class
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )) {
			self::$registered[$signalclass][$signalname] = array();
		}

		// dont connect hooks twice
		foreach (self::$registered[$signalclass][$signalname] as $hook) {
			if ($hook['class'] === $slotclass and $hook['name'] === $slotname) {
				return false;
			}
		}
		// Connect the hook handler to the requested emitter
		self::$registered[$signalclass][$signalname][] = array(
				"class" => $slotclass,
				"name" => $slotname
		);

		// No chance for failure ;-)
		return true;
	}

	/**
	 * emits a signal
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param mixed $params default: array() array with additional data
	 * @return bool true if slots exists or false if not
	 *
	 * Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	static public function emit( $signalclass, $signalname, $params = array()) {

		// Return false if no hook handlers are listening to this
		// emitting class
		if( !array_key_exists( $signalclass, self::$registered )) {
			return false;
		}

		// Return false if no hook handlers are listening to this
		// emitting method
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )) {
			return false;
		}

		// Call all slots
		foreach( self::$registered[$signalclass][$signalname] as $i ) {
			try {
				call_user_func( array( $i["class"], $i["name"] ), $params );
			} catch (Exception $e){
				self::$thrownExceptions[] = $e;
				OC_Log::write('hook',
					'error while running hook (' . $i["class"] . '::' . $i["name"] . '): '.$e->getMessage(),
					OC_Log::ERROR);
			}
		}

		// return true
		return true;
	}

	/**
	 * clear hooks
	 * @param string $signalclass
	 * @param string $signalname
	 */
	static public function clear($signalclass='', $signalname='') {
		if($signalclass) {
			if($signalname) {
				self::$registered[$signalclass][$signalname]=array();
			}else{
				self::$registered[$signalclass]=array();
			}
		}else{
			self::$registered=array();
		}
	}

	/**
	 * DO NOT USE!
	 * For unit tests ONLY!
	 */
	static public function getHooks() {
		return self::$registered;
	}
}
