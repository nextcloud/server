<?php

OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

if(!\OCP\App::isEnabled('files_sharing')){
	\OC_Response::setStatus(410); // GONE
}

// Get data
if ( isset( $_GET['theme'] ) && isset( $_GET['template'] ) ) {

	$template = new \OCA\Files_Sharing\MailTemplate( $_GET['theme'], $_GET['template'] );
	try {
		$template->renderContent();
	} catch (\OCP\Files\NotPermittedException $ex) {
		\OC_Response::setStatus(403); // forbidden
	}
	exit();
}
\OC_Response::setStatus(404); // not found
