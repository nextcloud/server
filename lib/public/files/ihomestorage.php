<?php
/**
 * ownCloud
 *
 * @author Robin Appelman
 * @copyright 2012 Robin Appelman icewind@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Public interface of ownCloud for apps to use.
 * Files/Storage interface
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP\Files;

interface IHomeStorage {

}
