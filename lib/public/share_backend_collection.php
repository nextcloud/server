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
 * Interface for collections of of items implemented by another share backend.
 * Extends the Share_Backend interface.
 */
interface Share_Backend_Collection extends Share_Backend {
	/**
	 * Get the sources of the children of the item
	 * @param string $itemSource
	 * @return array Returns an array of children each inside an array with the keys: source, target, and file_path if applicable
	 */
	public function getChildren($itemSource);
}
