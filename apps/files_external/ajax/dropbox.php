<?php

require_once __DIR__ . '/../3rdparty/Dropbox/autoload.php';

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();
$l = \OC::$server->getL10N('files_external');

if (isset($_POST['app_key']) && isset($_POST['app_secret'])) {
	$oauth = new Dropbox_OAuth_Curl($_POST['app_key'], $_POST['app_secret']);
	if (isset($_POST['step'])) {
		switch ($_POST['step']) {
			case 1:
				try {
					if (isset($_POST['callback'])) {
						$callback = $_POST['callback'];
					} else {
						$callback = null;
					}
					$token = $oauth->getRequestToken();
					OCP\JSON::success(array('data' => array('url' => $oauth->getAuthorizeUrl($callback),
															'request_token' => $token['token'],
															'request_token_secret' => $token['token_secret'])));
				} catch (Exception $exception) {
					OCP\JSON::error(array('data' => array('message' =>
						$l->t('Fetching request tokens failed. Verify that your Dropbox app key and secret are correct.'))
						));
				}
				break;
			case 2:
				if (isset($_POST['request_token']) && isset($_POST['request_token_secret'])) {
					try {
						$oauth->setToken($_POST['request_token'], $_POST['request_token_secret']);
						$token = $oauth->getAccessToken();
						OCP\JSON::success(array('access_token' => $token['token'],
												'access_token_secret' => $token['token_secret']));
					} catch (Exception $exception) {
						OCP\JSON::error(array('data' => array('message' =>
							$l->t('Fetching access tokens failed. Verify that your Dropbox app key and secret are correct.'))
							));
					}
				}
				break;
		}
	}
} else {
	OCP\JSON::error(array('data' => array('message' => $l->t('Please provide a valid Dropbox app key and secret.'))));
}
