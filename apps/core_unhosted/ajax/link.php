<?php
// We send json data
header( "Content-Type: application/jsonrequest" );
header("Access-Control-Allow-Origin: https://myfavouritesandwich.org");
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Methods: OPTIONS, POST');

try {
	if($_POST['secret'] && $_POST['userAddress'] && $_POST['dataScope'] && $_POST['secret']=='XRlc2FuZHdpY2gub3JnIiwiZW1haWwiOiJhc2RmYXNkZkB1b') {
		// Init owncloud
		require_once('../../../lib/base.php');
		require_once('../lib_unhosted.php');

		$ownCloudDetails = array(
			'url' => 'https://myfavouritesandwich.org:444/',
			'usr' => $_POST['userAddress'],//this is not necessarily the case, you could also use one owncloud user and many user addresses on it
			'pwd' => OC_User::generatePassword(),
			);
		$storage = array(
			'dataScope' => $_POST['dataScope'],
			'storageType' => 'http://unhosted.org/spec/dav/0.1',
			'davUrl' => 'https://myfavouritesandwich.org:444/apps/unhosted_web/compat.php/'.$ownCloudDetails['usr'].'/unhosted/',
			'userAddress' => $_POST['userAddress'],//here, it refers to the user sent to DAV in the basic auth
			);
		if(OC_User::userExists($ownCloudDetails['usr'])){
			$message = 'account reopened';
			$result = OC_User::setPassword($ownCloudDetails['usr'], $ownCloudDetails['pwd']);
		} else {
			$message = 'account created';
			$result = OC_User::createUser($ownCloudDetails['usr'], $ownCloudDetails['pwd']);
		}
		if($result) {
			$storage['davToken'] = OC_UnhostedWeb::createDataScope(
				'https://myfavouritesandwich.org/',
				$ownCloudDetails['usr'], $storage['dataScope']);
			echo json_encode(array('ownCloudDetails' => $ownCloudDetails, 'storage' => $storage));
			exit();
		} else {
			echo json_encode( array( "status" => "error", "data" => "couldn't ", "ownCloudDetails" => $ownCloudDetails));
			exit();
		}
	} else {
		echo json_encode( array( "status" => "error", "data" => "post not ok"));
	}	
} catch(Exception $e) {
	echo json_encode( array( "status" => "error", "data" => $e));
}
echo json_encode( array( "status" => "error", "data" => array( "message" => "Computer says 'no'" )));
?>
