<?php
/**
 * @copyright Copyright (c) 2017 Arne Hamann <kontakt+github@arne.email>
 *
 * @author Arne Hamann <kontakt+github@arne.email>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OC;

use OCP\Defaults;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IGpg;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCP\Util;

use gnupg;


class Gpg implements IGpg{
	/** @var gnupg */
	private $gpg = null;
	/** @var IConfig */
	private $config;
	/** @var ILogger */
	private $logger;
	/** @var Defaults */
	private $defaults;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var IL10N */
	private $l10n;
	/** string $uid */
	private $uid = '';
	/**
	 * Gpg constructor.
	 *
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param Defaults $defaults
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
	 * @param string $uid = ''
	 */
	public function __construct(IConfig $config,
								ILogger $logger,
								Defaults $defaults,
								IURLGenerator $urlGenerator,
								IL10N $l10n) {
		$this->config = $config;
		$this->logger = $logger;
		$this->defaults = $defaults;
		$this->urlGenerator = $urlGenerator;
		$this->l10n = $l10n;
		$home = $this->config->getSystemValue("datadirectory");

		putenv('GNUPGHOME='.$home.'.gnupg');

		$this->gpg = new gnupg();
		$this->gpg -> setarmor(1);
		$this->gpg->setsignmode(gnupg::SIG_MODE_DETACH);
		$debugMode = $this->config->getSystemValue('debug', false);
		if ($debugMode) {
			$this->gpg->seterrormode(gnupg::ERROR_WARNING);
		}
	}

	/**
	 * Combination of gnupg_addencryptkey and gnupg_encrypt
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the encryption keys
	 * @return string
	 */
	public function encrypt(array $fingerprints,  $plaintext ) {
		$debugMode = $this->config->getSystemValue('debug', false);
		foreach ($fingerprints as $fingerprint){
			$this->gpg->addencryptkey($fingerprint);
		}
		$gpg_text = $this->gpg->encrypt($plaintext);
		$this->gpg->clearencryptkeys();
		if($debugMode) {
			$encrypt_fingerprints_text = '';
			foreach ($fingerprints as $encrypt_fingerprint) {
				$encrypt_fingerprints_text = $encrypt_fingerprints_text . "," . $encrypt_fingerprint;
			}
			$this->logger->debug("GPG encrypted plain message: with encrypt keys:" . $encrypt_fingerprints_text . " to gpg text", ['app' => 'core']);
		}
		return $gpg_text;
	}


	/**
	 * Combination of gnupg_addsignkey gnupg_addencryptkey and gnupg_encryptsign
	 *
	 * @param string $plaintext
	 * @param array $encrypt_fingerprints fingerprints of the encryption keys
	 * @param array $sign_fingerprints fingerprints of the sign keys
	 * @return string
	 */
	public function encryptsign(array $encrypt_fingerprints, array $sign_fingerprints,  $plaintext){
		$debugMode = $this->config->getSystemValue('debug', false);
		foreach ($encrypt_fingerprints as $fingerprint){
			$this->gpg->addencryptkey($fingerprint);
		}
		foreach ($sign_fingerprints as $fingerprint){
			$this->gpg->addsignkey($fingerprint);
		}
		$gpg_text = $this->gpg->encryptsign($plaintext);
		$this->gpg->clearencryptkeys();
		$this->gpg->clearsignkeys();


		if($debugMode) {
			$sign_fingerprints_text = '';
			foreach ($sign_fingerprints as $sign_fingerprint) {
				$sign_fingerprints_text = $sign_fingerprints_text.",".$sign_fingerprint;
			}
			$encrypt_fingerprints_text = '';
			foreach ($encrypt_fingerprints as $encrypt_fingerprint) {
				$encrypt_fingerprints_text = $encrypt_fingerprints_text . "," . $encrypt_fingerprint;
			}
			$this->logger->debug("GPG encryptsigned plain message: with encrypt keys:" . $encrypt_fingerprints_text . " with sign Keys:" . $sign_fingerprints_text . " to gpg text", ['app' => 'core']);
		}

		return $gpg_text;
	}

	/**
	 * Combination of gnupg_addsignkey and gnupg_sign
	 *
	 * @param string $plaintext
	 * @param array $fingerprints fingerprints of the sign keys
	 * @return string
	 */
	public function sign(array $fingerprints,  $plaintext){
		$debugMode = $this->config->getSystemValue('debug', false);
		foreach ($fingerprints as $fingerprint){
			$this->gpg->addsignkey($fingerprint);
		}
		$gpg_text = $this->gpg->sign($plaintext);

		$this->gpg->clearsignkeys();
		$sign_fingerprints_text = '';
		if ($debugMode) {
			foreach ($fingerprints as $sign_fingerprint) {
				$sign_fingerprints_text = $sign_fingerprints_text . "," . $sign_fingerprint;
			}
			$this->logger->debug("GPG signed plain message: with sign keys:" . $sign_fingerprints_text, ['app' => 'core']);
		}
		return $gpg_text;
	}


	/**
	 * Mapper for gnupg_import,
	 * with expect that only one key per email can be added.
	 *
	 * @param string $keydata
	 * @return array
	 */
	public function import($keydata) {
		return $this->gpg->import($keydata);
	}

	/**
	 * Mapper for gnupg_export,
	 * exports the public key for finterprint.
	 *
	 * @param string $fingerprint
	 * @return string
	 */
	public function export($fingerprint) {
		return $this->gpg->export($fingerprint);
	}


	/**
	 * Mapper for gnupg_keyinfo
	 *
	 * @param string $pattern
	 * @return array
	 */
	public function keyinfo($pattern) {
		return $this->gpg->keyinfo($pattern);
	}

	/**
	 * Mapper for gnupg_deletekey
	 *
	 * Deletes the key from the keyring. If allowsecret is not set or FALSE it will fail on deleting secret keys.

	 * @param string $fingerprint of the key
	 * @param bool $allowsecret
	 * @return bool
	 */
	public function deletekey($fingerprint, $allowsecret=FALSE  ){
		return $this->gpg->deletekey($fingerprint,$allowsecret);
	}

	/**
	 * Returns the fingerprint of the first public key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPublicKeyFromEmail($email){
		$fingerprint = '';
		$keys = $this->gpg->keyinfo($email);
		if(sizeof($keys)> 0) {
			foreach($keys as $key){
				if (!$key['disabled'] and !$key['expired'] and !$key['revoked']) {
					return $key['subkeys'][0]['fingerprint'];
				}
			}
		}
		return $fingerprint;
	}

	/**
	 * Returns the fingerprint of the first privat key matching the email.
	 *
	 * @param string $email
	 * @return string
	 */
	public function getPrivatKeyFromEmail($email){
		$fingerprint = '';
		$keys = $this->gpg->keyinfo($email);
		if(sizeof($keys)> 0) {
			foreach($keys as $key){
				if (!$key['disabled'] and !$key['expired'] and !$key['revoked'] and $key['is_secret']) {
					return $key['subkeys'][0]['fingerprint'];
				}
			}
		}
		return $fingerprint;
	}

	/**
	 * generate a new Key Pair, if no parameter given the key is for the server is generated
	 *
	 * @param string $email = ''
	 * @param string $name = ''
	 * @param string $commend = ''
	 */
	public function generateKey($email = '', $name = '', $commend = ''){
		$debugMode = $this->config->getSystemValue('debug', false);
		if ($email === '') {
			/* otherwise setUser(X); generateKey(); Would generate a server key for User X); */
			if ($this->uid === '') {
				$email = \OCP\Util::getDefaultEmailAddress($this->defaults->getName());
				$name = $this->defaults->getName();
				$commend = $this->defaults->getSlogan();
			}
		}


		$home = $this->config->getSystemValue("datadirectory");

		if ($debugMode) {
			$this->logger->debug("Generate server key for email:".$email, ['app' => 'core']);
		}

		//generate Keys

		putenv('GNUPGHOME='.$home.'.gnupg');
		$email = escapeshellcmd($email);
		$name = escapeshellcmd($name);
		$commend = escapeshellcmd($commend);
		$foo = system(
			<<<EFF
cat >foo <<EOF
Key-Type: default
Subkey-Type: default
Name-Real: {$name}
Name-Comment: {$commend}
Name-Email: {$email}
Expire-Date: 0
%no-protection
EFF
		,$out1);


		$timestamp_before = time();
		$foo = exec("gpg --batch --gen-key foo 2>&1",$out2);
		$timestamp_after = time();
		if ($debugMode) {
			$this->logger->debug("gpg --batch --gen-key foo:\n" . print_r($out2,TRUE)."\n This took ".($timestamp_after-$timestamp_before)."seconds.", ['app' => 'core']);
		}
		$foo = system("rm foo",$out);



		$keys = $this->gpg->keyinfo($email);
		$fingerprint = "";
		foreach ($keys as $key) {
			if ($key["subkeys"][0]["timestamp"] >= $timestamp_before AND $key["subkeys"][0]["timestamp"] <= $timestamp_after) {
				if ($debugMode){
					$this->logger->debug("Found new server key:" .$key["subkeys"][0]["fingerprint"], ['app' => 'core']);
				}
				$fingerprint = $key['subkeys'][0]['fingerprint'];
				$timestamp_before = $key["subkeys"][0]["timestamp"];
			}
		}


		if ($fingerprint === "") {
			$this->logger->warning("No server GPG key found so no signed emails are possible", ['app' => 'core']);
		}
		if ($debugMode){
			$this->logger->debug("Saved server key fingerprint:".$fingerprint." to system config", ['app' => 'core']);
		}
		$this->config->setSystemValue("GpgServerKey",$fingerprint);
	}

	/**
	 * Change the GPG home from nextcloud-data/.gnupg to user-home/.gnugp
	 * Takes an empty string to reset it to nextcloud-data
	 *
	 * @param string $uid
	 * @return $this
	 */
	public function setUser(string $uid) {
		// TODO: Implement setUser() method.
		$this->uid = $uid;
		$home = \OC::$server->getUserManager()->get($uid)->getHome();
		if ($home === null ) {
			$home = $this->config->getSystemValue("datadirectory");
		}
		putenv('GNUPGHOME='.$home.'.gnupg');
		$this->gpg = new gnupg();
		$this->gpg->setarmor(1);
		$this->gpg->setsignmode(gnupg::SIG_MODE_DETACH);
		$debugMode = $this->config->getSystemValue('debug', false);
		if ($debugMode) {
			$this->gpg->seterrormode(gnupg::ERROR_WARNING);
			$this->logger->debug("All gpg keys:" . print_r($this->gpg->keyinfo(""),TRUE ), ['app' => 'core']);
		}
		return $this;
	}
}