<?php

require_once('../lib/base.php');
oc_require( 'template.php' );
if( !OC_USER::isLoggedIn()){
    header( "Location: ".OC_HELPER::linkTo( "index.php" ));
    exit();
}

$settings = array();

// Do the work ...
if( $_POST["submit"] )
{
    if( $_POST["newpassword"] != $_POST["newpasswordconfirm"] ){
        // Say "Passwords not equal"
    }
    else{
        if( OC_USER::checkPassword( $_SESSION["username"], $_POST["password"] )){
            // Set password
            OC_USER::setPassord( $_SESSION["username"], $_POST["newpassword"] );
        }
        else{
            // Say "old password bad"
        }
    }
}

$tmpl = new OC_TEMPLATE( "settings", "index", "user" );
$tmpl->assign( "settings", $settings );
$tmpl->printPage();

?>
