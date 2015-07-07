<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OC\App\CodeChecker;

class PrivateCheck implements ICheck {
	/** @var ICheck */
	protected $check;

	/**
	 * @param ICheck $check
	 */
	public function __construct(ICheck $check) {
		$this->check = $check;
	}

	/**
	 * @return string
	 */
	public function getDescription() {
		$innerDescription = $this->check->getDescription();
		return 'private' . (($innerDescription === '') ? '' : ' ' . $innerDescription);
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return array_merge([
			// classes replaced by the public api
			'OC_API' => '6.0.0',
			'OC_App' => '6.0.0',
			'OC_AppConfig' => '6.0.0',
			'OC_Avatar' => '6.0.0',
			'OC_BackgroundJob' => '6.0.0',
			'OC_Config' => '6.0.0',
			'OC_DB' => '6.0.0',
			'OC_Files' => '6.0.0',
			'OC_Helper' => '6.0.0',
			'OC_Hook' => '6.0.0',
			'OC_Image' => '6.0.0',
			'OC_JSON' => '6.0.0',
			'OC_L10N' => '6.0.0',
			'OC_Log' => '6.0.0',
			'OC_Mail' => '6.0.0',
			'OC_Preferences' => '6.0.0',
			'OC_Search_Provider' => '6.0.0',
			'OC_Search_Result' => '6.0.0',
			'OC_Request' => '6.0.0',
			'OC_Response' => '6.0.0',
			'OC_Template' => '6.0.0',
			'OC_User' => '6.0.0',
			'OC_Util' => '6.0.0',
		], $this->check->getClasses());
	}

	/**
	 * @return array
	 */
	public function getConstants() {
		return $this->check->getConstants();
	}

	/**
	 * @return array
	 */
	public function getFunctions() {
		return $this->check->getFunctions();
	}

	/**
	 * @return array
	 */
	public function getMethods() {
		return $this->check->getMethods();
	}

	/**
	 * @return bool
	 */
	public function checkStrongComparisons() {
		return $this->check->checkStrongComparisons();
	}
}
