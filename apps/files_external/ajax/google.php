<?php
set_include_path(get_include_path().PATH_SEPARATOR.
	\OC_App::getAppPath('files_external').'/3rdparty/google-api-php-client/src');
require_once 'Google/Client.php';

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external');

if (isset($_POST['client_id']) && isset($_POST['client_secret']) && isset($_POST['redirect'])) {
	$client = new Google_Client();
	$client->setClientId($_POST['client_id']);
	$client->setClientSecret($_POST['client_secret']);
	$client->setRedirectUri($_POST['redirect']);
	$client->setScopes(array('https://www.googleapis.com/auth/drive'));
	$client->setAccessType('offline');
	if (isset($_POST['step'])) {
		$step = $_POST['step'];
		if ($step == 1) {
			try {
				$authUrl = $client->createAuthUrl();
				OCP\JSON::success(array('data' => array(
					'url' => $authUrl
				)));
			} catch (Exception $exception) {
				OCP\JSON::error(array('data' => array(
					'message' => $l->t('Step 1 failed. Exception: %s', array($exception->getMessage()))
				)));
			}
		} else if ($step == 2 && isset($_POST['code'])) {
			try {
				$token = $client->authenticate($_POST['code']);
				OCP\JSON::success(array('data' => array(
					'token' => $token
				)));
			} catch (Exception $exception) {
				OCP\JSON::error(array('data' => array(
					'message' => $l->t('Step 2 failed. Exception: %s', array($exception->getMessage()))
				)));
			}
		}
	}
}
