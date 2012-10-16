<?php

/**
 * This class manages the hooks. It basically provides two functions: adding
 * slots and emitting signals.
 */
class OC_Hook{
	static private $registered = array();

	/**
	 * @brief connects a function to a hook
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
		// Create the data structure
		if( !array_key_exists( $signalclass, self::$registered )) {
			self::$registered[$signalclass] = array();
		}
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )) {
			self::$registered[$signalclass][$signalname] = array();
		}

		// register hook
		self::$registered[$signalclass][$signalname][] = array(
		  "class" => $slotclass,
		  "name" => $slotname );

		// No chance for failure ;-)
		return true;
	}

	/**
	 * @brief emits a signal
	 * @param string $signalclass class name of emitter
	 * @param string $signalname name of signal
	 * @param array $params defautl: array() array with additional data
	 * @return bool, true if slots exists or false if not
	 *
	 * Emits a signal. To get data from the slot use references!
	 *
	 * TODO: write example
	 */
	static public function emit( $signalclass, $signalname, $params = array()) {
		// Return false if there are no slots
		if( !array_key_exists( $signalclass, self::$registered )) {
			return false;
		}
		if( !array_key_exists( $signalname, self::$registered[$signalclass] )) {
			return false;
		}

		// Call all slots
		foreach( self::$registered[$signalclass][$signalname] as $i ) {
			call_user_func( array( $i["class"], $i["name"] ), $params );
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
}
