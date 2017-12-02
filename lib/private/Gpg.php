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
	/**
	 * Gpg constructor.
	 *
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param Defaults $defaults
	 * @param IURLGenerator $urlGenerator
	 * @param IL10N $l10n
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
		putenv('GNUPGHOME='.$this->config->getSystemValue("datadirectory").'.gnupg');
		$this->gpg = new gnupg();
		$this->gpg -> setarmor(1);
		$this->gpg->setsignmode(gnupg::SIG_MODE_DETACH);
		$debugMode = $this->config->getSystemValue('debug', false);
		if ($debugMode) {
			$this->gpg->seterrormode(gnupg::ERROR_WARNING);
			$this->logger->debug("All gpg Keys:" . print_r($this->gpg->keyinfo("")), ['app' => 'core']);
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
			$this->logger->debug("GPG encrypted plain message: with encrypt Keys:" . $encrypt_fingerprints_text . " to gpg text", ['app' => 'core']);
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
			$this->logger->debug("GPG encryptsigned plain message: with encrypt Keys:" . $encrypt_fingerprints_text . " with sign Keys:" . $sign_fingerprints_text . " to gpg text", ['app' => 'core']);
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
			$this->logger->debug("GPG signed plain message: with sign Keys:" . $sign_fingerprints_text . " to gpg text", ['app' => 'core']);
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
					return $key[0]['subkeys'][0]['fingerprint'];
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
					return $key[0]['subkeys'][0]['fingerprint'];
				}
			}
		}
		return $fingerprint;
	}

/*	public function setup(){
		//generate Keys
		putenv('GNUPGHOME="'.$this->config->getSystemValue("datadirectory").'/.gnupg"');
		passthru("cat >foo <<EOF
     %echo Generating a basic OpenPGP key
     %echo Generating a default key
     Key-Type: default
     Subkey-Type: default
     Name-Real: Nextcloud "./*FIXME Name aus Theme lesen/"
     Name-Comment: with stupid passphrase"./*FIXME Slogan aus Theme lesen/"
     Name-Email: ".$this->config->getSystemValue("mail_from_address")."
     Expire-Date: 0
     Passphrase: abc
     %no-protection
     # Do a commit here, so that we can later print \"done\" :-)
     %commit
     %echo done
EOF");
		system("gpg --batch --gen-key foo");
		$fingerprint=$this->import(openssl_pkey_new())[fingerprint];
		$this->config->setSystemValue("GpgServerKey",$fingerprint);
	}*/
}