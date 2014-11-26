<?php
/**
 * ownCloud
 *
 * @author Bjoern Schiessle, Michael Gapczynski
 * @copyright 2012 Michael Gapczynski <mtgap@owncloud.com>
 *            2014 Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * Interface for share backends that share content that is dependent on files.
 * Extends the Share_Backend interface.
 */
interface Share_Backend_File_Dependent extends Share_Backend {
	/**
	 * Get the file path of the item
	 * @param string $itemSource
	 * @param string $uidOwner User that is the owner of shared item
	 * @return string|false
	 */
	public function getFilePath($itemSource, $uidOwner);

}
