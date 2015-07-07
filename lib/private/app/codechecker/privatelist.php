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

use OC\Hooks\BasicEmitter;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

class PrivateList implements ICheckList {
	/**
	 * @return string
	 */
	public function getDescription() {
		return 'private';
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return [
			// classes replaced by the public api
			'OC_API',
			'OC_App',
			'OC_AppConfig',
			'OC_Avatar',
			'OC_BackgroundJob',
			'OC_Config',
			'OC_DB',
			'OC_Files',
			'OC_Helper',
			'OC_Hook',
			'OC_Image',
			'OC_JSON',
			'OC_L10N',
			'OC_Log',
			'OC_Mail',
			'OC_Preferences',
			'OC_Search_Provider',
			'OC_Search_Result',
			'OC_Request',
			'OC_Response',
			'OC_Template',
			'OC_User',
			'OC_Util',
		];
	}

	/**
	 * @return array
	 */
	public function getConstants() {
		return [];
	}

	/**
	 * @return array
	 */
	public function getFunctions() {
		return [];
	}

	/**
	 * @return array
	 */
	public function getMethods() {
		return [];
	}

	/**
	 * @return bool
	 */
	public function checkStrongComparisons() {
		return false;
	}
}
