<?php
class OC_APP{
	static private $init = false;
	static private $apps = array();

	/**
	 *
	 */
	public static function loadApps(){
		global $SERVERROOT;

		// Get all appinfo
		$dir = opendir( $SERVERROOT );
		while( false !== ( $filename = readdir( $dir ))){
			if( substr( $filename, 0, 1 ) != '.' ){
				if( file_exists( "$SERVERROOT/$filename/appinfo/app.php" )){
					oc_require( "$filename/appinfo/app.php" );
				}
			}
		}
		closedir( $dir );

		// return
		return true;
	}

	/**
	 *
	 */
	public static function register( $data = array()){
		OC_APP::$apps[] = $data;
	}

	/**
	 *
	 */
	public static function getApps(){
		return OC_APP::$apps;
	}

}
?>
