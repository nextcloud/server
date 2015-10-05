<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Encryption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Interface IEncryptionModule
 *
 * @package OCP\Encryption
 * @since 8.1.0
 */
interface IEncryptionModule {

	/**
	 * @return string defining the technical unique id
	 * @since 8.1.0
	 */
	public function getId();

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return string
	 * @since 8.1.0
	 */
	public function getDisplayName();

	/**
	 * start receiving chunks from a file. This is the place where you can
	 * perform some initial step before starting encrypting/decrypting the
	 * chunks
	 *
	 * @param string $path to the file
	 * @param string $user who read/write the file (null for public access)
	 * @param string $mode php stream open mode
	 * @param array $header contains the header data read from the file
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 *
	 * $return array $header contain data as key-value pairs which should be
	 *                       written to the header, in case of a write operation
	 *                       or if no additional data is needed return a empty array
	 * @since 8.1.0
	 */
	public function begin($path, $user, $mode, array $header, array $accessList);

	/**
	 * last chunk received. This is the place where you can perform some final
	 * operation and return some remaining data if something is left in your
	 * buffer.
	 *
	 * @param string $path to the file
	 * @return string remained data which should be written to the file in case
	 *                of a write operation
	 * @since 8.1.0
	 */
	public function end($path);

	/**
	 * encrypt data
	 *
	 * @param string $data you want to encrypt
	 * @return mixed encrypted data
	 * @since 8.1.0
	 */
	public function encrypt($data);

	/**
	 * decrypt data
	 *
	 * @param string $data you want to decrypt
	 * @return mixed decrypted data
	 * @since 8.1.0
	 */
	public function decrypt($data);

	/**
	 * update encrypted file, e.g. give additional users access to the file
	 *
	 * @param string $path path to the file which should be updated
	 * @param string $uid of the user who performs the operation
	 * @param array $accessList who has access to the file contains the key 'users' and 'public'
	 * @return boolean
	 * @since 8.1.0
	 */
	public function update($path, $uid, array $accessList);

	/**
	 * should the file be encrypted or not
	 *
	 * @param string $path
	 * @return boolean
	 * @since 8.1.0
	 */
	public function shouldEncrypt($path);

	/**
	 * get size of the unencrypted payload per block.
	 * ownCloud read/write files with a block size of 8192 byte
	 *
	 * @return integer
	 * @since 8.1.0
	 */
	public function getUnencryptedBlockSize();

	/**
	 * check if the encryption module is able to read the file,
	 * e.g. if all encryption keys exists
	 *
	 * @param string $path
	 * @param string $uid user for whom we want to check if he can read the file
	 * @return boolean
	 * @since 8.1.0
	 */
	public function isReadable($path, $uid);

	/**
	 * Initial encryption of all files
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output write some status information to the terminal during encryption
	 * @since 8.2.0
	 */
	public function encryptAll(InputInterface $input, OutputInterface $output);

	/**
	 * prepare encryption module to decrypt all files
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output write some status information to the terminal during encryption
	 * @param $user (optional) for which the files should be decrypted, default = all users
	 * @return bool return false on failure or if it isn't supported by the module
	 * @since 8.2.0
	 */
	public function prepareDecryptAll(InputInterface $input, OutputInterface $output, $user = '');

}
