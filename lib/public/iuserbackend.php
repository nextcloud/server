<?php
/**
 * Copyright (c) 2014 Morris Jobke <hey@morrisjobke.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * Public interface of ownCloud for apps to use.
 * User Interface version 2
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

interface IUserBackend {

	/**
	 * Backend name to be shown in user management
	 * @return string the name of the backend to be shown
	 */
	public function getBackendName();

}
