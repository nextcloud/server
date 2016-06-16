<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
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


namespace OCA\Theming\Controller;


use OCA\Theming\Template;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;

/**
 * Class ThemingController
 *
 * handle ajax requests to update the theme
 *
 * @package OCA\Theming\Controller
 */
class ThemingController extends Controller {
	
	/** @var Template */
	private $template;

	/** @var IL10N */
	private $l;

	/**
	 * ThemingController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param Template $template
	 * @param IL10N $l
	 */
	public function __construct(
		$appName,
		IRequest $request,
		Template $template,
		IL10N $l
	) {
		parent::__construct($appName, $request);
		
		$this->template = $template;
		$this->l = $l;
	}

	/**
	 * @param $setting
	 * @param $value
	 * @return DataResponse
	 * @internal param string $color
	 */
	public function updateStylesheet($setting, $value) {
		$this->template->set($setting, $value);
		return new DataResponse(
			[
				'data' =>
					[
						'message' => $this->l->t('Saved')
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * update Nextcloud logo
	 *
	 * @return DataResponse
	 */
	public function updateLogo() {
		$newLogo = $this->request->getUploadedFile('uploadlogo');
		if (empty($newLogo)) {
			return new DataResponse(
				[
					'data' => [
						'message' => $this->l->t('No logo uploaded')
					]
				],
				Http::STATUS_UNPROCESSABLE_ENTITY);
		}
		$this->template->set('logoName', $newLogo['name']);
		rename($newLogo['tmp_name'], \OC::$SERVERROOT . '/themes/theming-app/core/img/' . $newLogo['name']);
		
		return new DataResponse(
			[
				'data' =>
					[
						'name' => $newLogo['name'],
						'message' => $this->l->t('Saved')
					],
				'status' => 'success'
			]
		);
	}

	/**
	 * revert setting to default value
	 *
	 * @param string $setting setting which should be reverted
	 * @return DataResponse
	 */
	public function undo($setting) {
		$value = $this->template->undo($setting);
		return new DataResponse(
			[
				'data' =>
					[
						'value' => $value,
						'message' => $this->l->t('Saved')
					],
				'status' => 'success'
			]
		);
	}
}
