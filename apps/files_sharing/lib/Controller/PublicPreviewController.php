<?php
/**
 * @copyright Copyright (c) 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Files_Sharing\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\Constants;
use OCP\Files\Folder;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use OCP\IRequest;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager as ShareManager;

class PublicPreviewController extends Controller {

	/** @var ShareManager */
	private $shareManager;

	/** @var IPreview */
	private $previewManager;

	public function __construct($appName,
								IRequest $request,
								ShareManager $shareManger,
								IPreview $previewManager) {
		parent::__construct($appName, $request);

		$this->shareManager = $shareManger;
		$this->previewManager = $previewManager;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * @param string $file
	 * @param int $x
	 * @param int $y
	 * @param string $t
	 * @param bool $a
	 * @return DataResponse|FileDisplayResponse
	 */
	public function getPreview(
		$file = '',
		$x = 32,
		$y = 32,
		$t = '',
		$a = false
	) {

		if ($t === '' || $x === 0 || $y === 0) {
			return new DataResponse([], Http::STATUS_BAD_REQUEST);
		}

		try {
			$share = $this->shareManager->getShareByToken($t);
		} catch (ShareNotFound $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}

		if (($share->getPermissions() & Constants::PERMISSION_READ) === 0) {
			return new DataResponse([], Http::STATUS_FORBIDDEN);
		}

		try {
			$node = $share->getNode();
			if ($node instanceof Folder) {
				$file = $node->get($file);
			} else {
				$file = $node;
			}

			$f = $this->previewManager->getPreview($file, $x, $y, !$a);
			return new FileDisplayResponse($f, Http::STATUS_OK, ['Content-Type' => $f->getMimeType()]);
		} catch (NotFoundException $e) {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
	}
}
