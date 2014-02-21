<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

if(!\OCP\App::isEnabled('files_sharing')){
	\OCP\Response::setStatus(410); // GONE
}

$l=OC_L10N::get('core');

// post data
if ( isset( $_POST['theme'] ) && isset( $_POST['template'] ) ) {

	$template = new \OCA\Files_Sharing\MailTemplate( $_POST['theme'], $_POST['template'] );
	try {
		$template->reset();
		\OC_Response::setStatus(200); // ok
	} catch (\OCP\Files\NotPermittedException $ex) {
		\OC_Response::setStatus(403); // forbidden
	}
	exit();
}
\OC_Response::setStatus(404); // not found
