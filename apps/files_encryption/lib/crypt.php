<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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



// Todo:
//  - Crypt/decrypt button in the userinterface
//  - Setting if crypto should be on by default
//  - Add a setting "Don´t encrypt files larger than xx because of performance reasons"
//  - Transparent decrypt/encrypt in filesystem.php. Autodetect if a file is encrypted (.encrypted extension)
//  - Don't use a password directly as encryption key. but a key which is stored on the server and encrypted with the user password. -> password change faster
//  - IMPORTANT! Check if the block lenght of the encrypted data stays the same


require_once('Crypt_Blowfish/Blowfish.php');

/**
 * This class is for crypting and decrypting
 */
class OC_Crypt {
	static private $bf = null;

	public static function loginListener($params){
		self::init($params['uid'],$params['password']);
	}

	public static function init($login,$password) {
		$view=new OC_FilesystemView('/');
		if(!$view->file_exists('/'.$login)){
			$view->mkdir('/'.$login);
		}

		OC_FileProxy::$enabled=false;
		if(!$view->file_exists('/'.$login.'/encryption.key')){// does key exist?
			OC_Crypt::createkey($login,$password);
		}
		$key=$view->file_get_contents('/'.$login.'/encryption.key');
		OC_FileProxy::$enabled=true;
		$_SESSION['enckey']=OC_Crypt::decrypt($key, $password);
	}

	/**
	 * get the blowfish encryption handeler for a key
	 * @param string $key (optional)
	 * @return Crypt_Blowfish
	 *
	 * if the key is left out, the default handeler will be used
	 */
	public static function getBlowfish($key=''){
		if($key){
			return new Crypt_Blowfish($key);
		}else{
			if(!isset($_SESSION['enckey'])){
				return false;
			}
			if(!self::$bf){
				self::$bf=new Crypt_Blowfish($_SESSION['enckey']);
			}
			return self::$bf;
		}
	}

	public static function createkey($username,$passcode) {
		// generate a random key
		$key=mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999).mt_rand(10000,99999);

		// encrypt the key with the passcode of the user
		$enckey=OC_Crypt::encrypt($key,$passcode);

		// Write the file
		$proxyEnabled=OC_FileProxy::$enabled;
		OC_FileProxy::$enabled=false;
		$view=new OC_FilesystemView('/'.$username);
		$view->file_put_contents('/encryption.key',$enckey);
		OC_FileProxy::$enabled=$proxyEnabled;
	}

	public static function changekeypasscode($oldPassword, $newPassword) {
		if(OCP\User::isLoggedIn()){
			$username=OCP\USER::getUser();
			$view=new OC_FilesystemView('/'.$username);

			// read old key
			$key=$view->file_get_contents('/encryption.key');

			// decrypt key with old passcode
			$key=OC_Crypt::decrypt($key, $oldPassword);

			// encrypt again with new passcode
			$key=OC_Crypt::encrypt($key, $newPassword);

			// store the new key
			$view->file_put_contents('/encryption.key', $key );
		}
	}

	/**
	 * @brief encrypts an content
	 * @param $content the cleartext message you want to encrypt
	 * @param $key the encryption key (optional)
	 * @returns encrypted content
	 *
	 * This function encrypts an content
	 */
	public static function encrypt( $content, $key='') {
		$bf = self::getBlowfish($key);
		return $bf->encrypt($content);
	}

	/**
	* @brief decryption of an content
	* @param $content the cleartext message you want to decrypt
	* @param $key the encryption key (optional)
	* @returns cleartext content
	*
	* This function decrypts an content
	*/
	public static function decrypt( $content, $key='') {
		$bf = self::getBlowfish($key);
		$data=$bf->decrypt($content);
		return $data;
	}

	/**
	* @brief encryption of a file
	* @param string $source
	* @param string $target
	* @param string $key the decryption key
	*
	* This function encrypts a file
	*/
	public static function encryptFile( $source, $target, $key='') {
		$handleread  = fopen($source, "rb");
		if($handleread!=FALSE) {
			$handlewrite = fopen($target, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::encrypt( $content, $key);
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			fclose($handleread);
		}
	}


	/**
		* @brief decryption of a file
		* @param string $source
		* @param string $target
		* @param string $key the decryption key
		*
		* This function decrypts a file
		*/
	public static function decryptFile( $source, $target, $key='') {
		$handleread  = fopen($source, "rb");
		if($handleread!=FALSE) {
			$handlewrite = fopen($target, "wb");
			while (!feof($handleread)) {
				$content = fread($handleread, 8192);
				$enccontent=OC_CRYPT::decrypt( $content, $key);
				if(feof($handleread)){
					$enccontent=rtrim($enccontent, "\0");
				}
				fwrite($handlewrite, $enccontent);
			}
			fclose($handlewrite);
			fclose($handleread);
		}
	}
	
	/**
	 * encrypt data in 8192b sized blocks
	 */
	public static function blockEncrypt($data, $key=''){
		$result='';
		while(strlen($data)){
			$result.=self::encrypt(substr($data,0,8192),$key);
			$data=substr($data,8192);
		}
		return $result;
	}
	
	/**
	 * decrypt data in 8192b sized blocks
	 */
	public static function blockDecrypt($data, $key='',$maxLength=0){
		$result='';
		while(strlen($data)){
			$result.=self::decrypt(substr($data,0,8192),$key);
			$data=substr($data,8192);
		}
		if($maxLength>0){
			return substr($result,0,$maxLength);
		}else{
			return rtrim($result, "\0");
		}
	}
}
