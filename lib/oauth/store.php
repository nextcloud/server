<?php
/**
* ownCloud
*
* @author Michael Gapczynski
* @author Tom Needham
* @copyright 2012 Michael Gapczynski mtgap@owncloud.com
* @copyright 2012 Tom Needham tom@owncloud.com 
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Affero General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

class OC_OAuth_Store {

	function lookup_consumer($consumer_key) {
		$query = OC_DB::prepare("SELECT `key`, `secret`, `callback` FROM `*PREFIX*oauth_consumers` WHERE `key` = ?");
		$results = $query->execute(array($consumer_key));
		if($results->numRows()==0){
			return NULL;
		} else {
			$details = $results->fetchRow();
			$callback = !empty($details['callback']) ? $details['callback'] : NULL;
			return new OAuthConsumer($details['key'], $details['secret'], $callback);
		}
	}

	function lookup_token($consumer, $token_type, $token) {
		$query = OC_DB::prepare("SELECT `key`, `secret`, `type` FROM `*PREFIX*oauth_tokens` WHERE `consumer_key` = ? AND `key` = ? AND `type` = ?");
		$results = $query->execute(array($consumer->key, $token->key, $token_type));
		if($results->numRows()==0){
			return NULL;
		} else {
			$token = $results->fetchRow();
			return new OAuthToken($token['key'], $token['secret']);
		}
	}

	function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		$query = OC_DB::prepare("INSERT INTO `*PREFIX*oauth_nonce` (`consumer_key`, `token`, `timestamp`, `nonce`) VALUES (?, ?, ?, ?)");
		$affectedrows = $query->exec(array($consumer->key, $token->key, $timestamp, $nonce));
		// Delete all timestamps older than the one passed
		$query = OC_DB::prepare("DELETE FROM `*PREFIX*oauth_nonce` WHERE `consumer_key` = ? AND `token` = ? AND `timestamp` < ?");
		$query->execute(array($consumer->key, $token->key, $timestamp - self::MAX_TIMESTAMP_DIFFERENCE));
		return $result;
	}

	function new_token($consumer, $token_type, $scope = null) {
		$key = md5(time());
		$secret = time() + time();
		$token = new OAuthToken($key, md5(md5($secret)));
		$query = OC_DB::prepare("INSERT INTO `*PREFIX*oauth_tokens` (`consumer_key`, `key`, `secret`, `type`, `scope`, `timestamp`) VALUES (?, ?, ?, ?, ?, ?)");
		$result = $query->execute(array($consumer->key, $key, $secret, $token_type, $scope, time()));
		return $token;
	}

	function new_request_token($consumer, $scope, $callback = null) {
		return $this->new_token($consumer, 'request', $scope);
	}

	function authorise_request_token($token, $consumer, $uid) {
		$query = OC_DB::prepare("UPDATE `*PREFIX*oauth_tokens` SET uid = ? WHERE `consumer_key` = ? AND `key` = ? AND `type` = ?");
		$query->execute(array($uid, $consumer->key, $token->key, 'request'));
		// TODO Return oauth_verifier
	}

	function new_access_token($token, $consumer, $verifier = null) {
		$query = OC_DB::prepare("SELECT `timestamp`, `scope` FROM `*PREFIX*oauth_tokens` WHERE `consumer_key` = ? AND `key` = ? AND `type` = ?");
		$result = $query->execute(array($consumer->key, $token->key, 'request'))->fetchRow();
		if (isset($result['timestamp'])) {
			if ($timestamp + self::MAX_REQUEST_TOKEN_TTL < time()) {
				return false;
			}
			$accessToken = $this->new_token($consumer, 'access', $result['scope']);
		}
		// Delete request token
		$query = OC_DB::prepare("DELETE FROM `*PREFIX*oauth_tokens` WHERE `key` = ? AND `type` = ?");
		$query->execute(array($token->key, 'request'));
		return $accessToken;
	}

}