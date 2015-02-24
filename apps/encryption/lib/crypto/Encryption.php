<?php
  /**
 * @author Clark Tomlinson  <fallen013@gmail.com>
 * @since 3/6/15, 2:28 PM
 * @link http:/www.clarkt.com
 * @copyright Clark Tomlinson © 2015
 * 
 */

namespace OCA\Encryption\Crypto;


use OCP\Encryption\IEncryptionModule;

class Encryption extends Crypt implements IEncryptionModule {

  /**
   * @return string defining the technical unique id
   */
  public function getId() {
    // TODO: Implement getId() method.
  }

  /**
   * In comparison to getKey() this function returns a human readable (maybe translated) name
   *
   * @return string
   */
  public function getDisplayName() {
    // TODO: Implement getDisplayName() method.
  }

  /**
   * start receiving chunks from a file. This is the place where you can
   * perform some initial step before starting encrypting/decrypting the
   * chunks
   *
   * @param string $path to the file
   * @param array $header contains the header data read from the file
   * @param array $accessList who has access to the file contains the key 'users' and 'public'
   *
   * $return array $header contain data as key-value pairs which should be
   *                       written to the header, in case of a write operation
   *                       or if no additional data is needed return a empty array
   */
  public function begin($path, $header, $accessList) {
    // TODO: Implement begin() method.
  }

  /**
   * last chunk received. This is the place where you can perform some final
   * operation and return some remaining data if something is left in your
   * buffer.
   *
   * @param string $path to the file
   * @return string remained data which should be written to the file in case
   *                of a write operation
   */
  public function end($path) {
    // TODO: Implement end() method.
  }

  /**
   * encrypt data
   *
   * @param string $data you want to encrypt
   * @return mixed encrypted data
   */
  public function encrypt($data) {
    // Todo: xxx Update Signature and usages
    $this->symmetricEncryptFileContent($data);
  }

  /**
   * decrypt data
   *
   * @param string $data you want to decrypt
   * @param string $user decrypt as user (null for public access)
   * @return mixed decrypted data
   */
  public function decrypt($data, $user) {
    // Todo: xxx Update Usages?
    $this->symmetricDecryptFileContent($data, $user);
  }

  /**
   * update encrypted file, e.g. give additional users access to the file
   *
   * @param string $path path to the file which should be updated
   * @param array $accessList who has access to the file contains the key 'users' and 'public'
   * @return boolean
   */
  public function update($path, $accessList) {
    // TODO: Implement update() method.
  }

  /**
   * should the file be encrypted or not
   *
   * @param string $path
   * @return boolean
   */
  public function shouldEncrypt($path) {
    // TODO: Implement shouldEncrypt() method.
  }

  /**
   * calculate unencrypted size
   *
   * @param string $path to file
   * @return integer unencrypted size
   */
  public function calculateUnencryptedSize($path) {
    // TODO: Implement calculateUnencryptedSize() method.
  }
}
