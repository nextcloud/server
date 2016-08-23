<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files\Settings;

use bantu\IniGetWrapper\IniGetWrapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {

	/** @var IniGetWrapper */
	private $iniWrapper;

	/** @var IRequest */
	private $request;

	public function __construct(IniGetWrapper $iniWrapper, IRequest $request) {
		$this->iniWrapper = $iniWrapper;
		$this->request = $request;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm() {
		$htaccessWorking  = (getenv('htaccessWorking') == 'true');
		$htaccessWritable = is_writable(\OC::$SERVERROOT.'/.htaccess');
		$userIniWritable  = is_writable(\OC::$SERVERROOT.'/.user.ini');

		$upload_max_filesize = $this->iniWrapper->getBytes('upload_max_filesize');
		$post_max_size = $this->iniWrapper->getBytes('post_max_size');
		$maxUploadFilesize = Util::humanFileSize(min($upload_max_filesize, $post_max_size));

		$parameters = [
			'uploadChangable'              => (($htaccessWorking and $htaccessWritable) or $userIniWritable ),
			'uploadMaxFilesize'            => $maxUploadFilesize,
			// max possible makes only sense on a 32 bit system
			'displayMaxPossibleUploadSize' => PHP_INT_SIZE === 4,
			'maxPossibleUploadSize'        => Util::humanFileSize(PHP_INT_MAX),
		];

		return new TemplateResponse('files', 'admin', $parameters, '');
	}

	/**
	 * @return string the section ID, e.g. 'sharing'
	 */
	public function getSection() {
		return 'additional';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 * the admin section. The forms are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority() {
		return 5;
	}

}
