<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
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

namespace Test\App\CodeChecker\Mock;

use OC\App\CodeChecker\ICheck;

class TestList implements ICheck {
	/** @var ICheck */
	protected $check;

	/**
	 * @param ICheck $check
	 */
	public function __construct(ICheck $check) {
		$this->check = $check;
	}

	/**
	 * @param int $errorCode
	 * @param string $errorObject
	 * @return string
	 */
	public function getDescription($errorCode, $errorObject) {
		return 'testing';
	}

	/**
	 * @return array E.g.: `'ClassName' => 'oc version',`
	 */
	public function getClasses() {
		return [
			// Deprecated classes
			'OCP\AppFramework\IApi' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'ClassName::CONSTANT_NAME' => 'oc version',`
	 */
	public function getConstants() {
		return [
			// Deprecated constants
			'OCP\NamespaceName\ClassName::CONSTANT_NAME' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'functionName' => 'oc version',`
	 */
	public function getFunctions() {
		return [
			// Deprecated functions
			'OCP\NamespaceName\ClassName::functionName' => '8.0.0',
		];
	}

	/**
	 * @return array E.g.: `'ClassName::methodName' => 'oc version',`
	 */
	public function getMethods() {
		return [
			// Deprecated methods
			'OCP\NamespaceName\ClassName::methodName' => '8.0.0',
		];
	}

	/**
	 * @return bool
	 */
	public function checkStrongComparisons() {
		return true;
	}
}
