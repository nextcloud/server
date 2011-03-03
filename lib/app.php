<?php
class OC_APP{
	static private $init = false;
	static private $apps = array();

	/**
	 *
	 */
	public static function init(){
		// Get all appinfo
		$dir = opendir( $SERVERROOT );
		while( false !== ( $filename = readdir( $dir ))){
			if( substr( $filename, 0, 1 ) != '.' ){
				if( file_exists( "$SERVERROOT/$filename/appinfo.php" )){
					oc_require( "$filename/appinfo.php" );
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
	public static function list(){
		return OC_APP::$apps[];
	}

}
?>
