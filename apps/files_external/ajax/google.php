<?php

require_once 'Google/common.inc.php';

OCP\JSON::checkAppEnabled('files_external');
OCP\JSON::checkLoggedIn();
OCP\JSON::callCheck();

$consumer = new OAuthConsumer('anonymous', 'anonymous');
$sigMethod = new OAuthSignatureMethod_HMAC_SHA1();
if (isset($_POST['step'])) {
	switch ($_POST['step']) {
		case 1:
			if (isset($_POST['callback'])) {
				$callback = $_POST['callback'];
			} else {
				$callback = null;
			}
			$scope = 'https://docs.google.com/feeds/'
					.' https://docs.googleusercontent.com/'
					.' https://spreadsheets.google.com/feeds/';
			$url = 'https://www.google.com/accounts/OAuthGetRequestToken?scope='.urlencode($scope);
			$params = array('scope' => $scope, 'oauth_callback' => $callback);
			$request = OAuthRequest::from_consumer_and_token($consumer, null, 'GET', $url, $params);
			$request->sign_request($sigMethod, $consumer, null);
			$response = send_signed_request('GET', $url, array($request->to_header()), null, false);
			$token = array();
			parse_str($response, $token);
			if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
				$authUrl = 'https://www.google.com/accounts/OAuthAuthorizeToken?oauth_token='.$token['oauth_token'];
				OCP\JSON::success(array('data' => array('url' => $authUrl,
														'request_token' => $token['oauth_token'],
														'request_token_secret' => $token['oauth_token_secret'])));
			} else {
				OCP\JSON::error(array('data' => array(
					'message' => 'Fetching request tokens failed. Error: '.$response
					)));
			}
			break;
		case 2:
			if (isset($_POST['oauth_verifier'])
				&& isset($_POST['request_token'])
				&& isset($_POST['request_token_secret'])
			) {
				$token = new OAuthToken($_POST['request_token'], $_POST['request_token_secret']);
				$url = 'https://www.google.com/accounts/OAuthGetAccessToken';
				$request = OAuthRequest::from_consumer_and_token($consumer, $token, 'GET', $url,
																 array('oauth_verifier' => $_POST['oauth_verifier']));
				$request->sign_request($sigMethod, $consumer, $token);
				$response = send_signed_request('GET', $url, array($request->to_header()), null, false);
				$token = array();
				parse_str($response, $token);
				if (isset($token['oauth_token']) && isset($token['oauth_token_secret'])) {
					OCP\JSON::success(array('access_token' => $token['oauth_token'],
											'access_token_secret' => $token['oauth_token_secret']));
				} else {
					OCP\JSON::error(array('data' => array(
						'message' => 'Fetching access tokens failed. Error: '.$response
						)));
				}
			}
			break;
	}
}
