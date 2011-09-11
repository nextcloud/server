<?php
/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * License: GNU AFFERO GENERAL PUBLIC LICENSE     *
 *                                                *
 * If you are not able to view the License,       *
 * <http://www.gnu.org/licenses/>                 *
 * please write to the Free Software Foundation.  *
 * Address:                                       *
 * 59 Temple Place, Suite 330, Boston,            *
 * MA 02111-1307  USA                             *
 *************************************************/

// Init owncloud
require_once('../../../lib/base.php');

$l=new OC_L10N('calendar');

// We send json data
header( "Content-Type: application/jsonrequest" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => $l->t("Authentication error") )));
	exit();
}

// Get data
if( isset( $_POST['timezone'] ) ){
	$timezone=$_POST['timezone'];
	OC_Preferences::setValue( OC_User::getUser(), 'calendar', 'timezone', $timezone );
	echo json_encode( array( "status" => "success", "data" => array( "message" => $l->t("Timezone changed") )));
}else{
	echo json_encode( array( "status" => "error", "data" => array( "message" => $l->t("Invalid request") )));
}

?>
