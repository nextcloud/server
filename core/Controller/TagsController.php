<?php
/**
 * @copyright Copyright (c) 2016 Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ITagManager;

class TagsController extends Controller {

	/** @var ITagManager */
	private $tagManager;

	/** @var IL10N */
	private $l;

	public function __construct($appName,
								IRequest $request,
								ITagManager $tagManager,
								IL10N $l) {
		parent::__construct($appName, $request);

		$this->tagManager = $tagManager;
		$this->l = $l;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @return JSONResponse
	 */
	public function getTags($type) {
		$tagger = $this->tagManager->load($type);

		return new JSONResponse([
			'status' => 'success',
			'tags' => $tagger->getTags(),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @return JSONResponse
	 */
	public function getFavorites($type) {
		$tagger = $this->tagManager->load($type);
		return new JSONResponse([
			'status' => 'success',
			'ids' => $tagger->getFavorites(),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param string $tag
	 * @return JSONResponse
	 */
	public function getIdsForTag($type, $tag) {
		$tagger = $this->tagManager->load($type);
		return new JSONResponse([
			'status' => 'success',
			'ids' => $tagger->getIdsForTag($tag),
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param string $tag
	 * @return JSONResponse
	 */
	public function addTag($type, $tag) {
		$tagger = $this->tagManager->load($type);

		$id = $tagger->add(strip_tags($tag));
		if($id === false) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Tag already exists'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
			'id' => $id,
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param $tags
	 * @return JSONResponse
	 */
	public function deleteTags($type, $tags) {
		if(!is_array($tags)) {
			$tags = array($tags);
		}

		$tagger = $this->tagManager->load($type);

		if(!$tagger->delete($tags)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Error deleting tag(s)'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param int $id
	 * @param string $tag
	 * @return JSONResponse
	 */
	public function tagAs($type, $id, $tag) {
		$tagger = $this->tagManager->load($type);

		if(!$tagger->tagAs($id, $tag)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Error tagging'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param int $id
	 * @param string $tag
	 * @return JSONResponse
	 */
	public function unTag($type, $id, $tag) {
		$tagger = $this->tagManager->load($type);

		if(!$tagger->unTag($id, $tag)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Error untagging'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param int $id
	 * @return JSONResponse
	 */
	public function favorite($type, $id) {
		$tagger = $this->tagManager->load($type);

		if(!$tagger->addToFavorites($id)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Error favoriting'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
		]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $type
	 * @param int $id
	 * @return JSONResponse
	 */
	public function unFavorite($type, $id) {
		$tagger = $this->tagManager->load($type);

		if(!$tagger->removeFromFavorites($id)) {
			return new JSONResponse([
				'status' => 'error',
				'message' => $this->l->t('Error unfavoriting'),
			]);
		}

		return new JSONResponse([
			'status' => 'success',
		]);
	}
}
