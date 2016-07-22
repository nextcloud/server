<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
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

namespace OC\Core\Tags;

class Controller {
	protected static function getTagger($type) {
		\OC_JSON::checkLoggedIn();
		\OC_JSON::callCheck();

		try {
			$tagger = \OC::$server->getTagManager()->load($type);
			return $tagger;
		} catch(\Exception $e) {
			\OCP\Util::writeLog('core', __METHOD__ . ' Exception: ' . $e->getMessage(), \OCP\Util::ERROR);
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error loading tags')));
			exit;
		}
	}

	public static function getTags($args) {
		$tagger = self::getTagger($args['type']);
		\OC_JSON::success(array('tags'=> $tagger->getTags()));
	}

	public static function getFavorites($args) {
		$tagger = self::getTagger($args['type']);
		\OC_JSON::success(array('ids'=> $tagger->getFavorites()));
	}

	public static function getIdsForTag($args) {
		$tagger = self::getTagger($args['type']);
		\OC_JSON::success(array('ids'=> $tagger->getIdsForTag($_GET['tag'])));
	}

	public static function addTag($args) {
		$tagger = self::getTagger($args['type']);

		$id = $tagger->add(strip_tags($_POST['tag']));
		if($id === false) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Tag already exists')));
		} else {
			\OC_JSON::success(array('id'=> $id));
		}
	}

	public static function deleteTags($args) {
		$tags = $_POST['tags'];
		if(!is_array($tags)) {
			$tags = array($tags);
		}

		$tagger = self::getTagger($args['type']);

		if(!$tagger->delete($tags)) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error deleting tag(s)')));
		} else {
			\OC_JSON::success();
		}
	}

	public static function tagAs($args) {
		$tagger = self::getTagger($args['type']);

		if(!$tagger->tagAs($args['id'], $_POST['tag'])) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error tagging')));
		} else {
			\OC_JSON::success();
		}
	}

	public static function unTag($args) {
		$tagger = self::getTagger($args['type']);

		if(!$tagger->unTag($args['id'], $_POST['tag'])) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error untagging')));
		} else {
			\OC_JSON::success();
		}
	}

	public static function favorite($args) {
		$tagger = self::getTagger($args['type']);

		if(!$tagger->addToFavorites($args['id'])) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error favoriting')));
		} else {
			\OC_JSON::success();
		}
	}

	public static function unFavorite($args) {
		$tagger = self::getTagger($args['type']);

		if(!$tagger->removeFromFavorites($args['id'])) {
			$l = new \OC_L10n('core');
			\OC_JSON::error(array('message'=> $l->t('Error unfavoriting')));
		} else {
			\OC_JSON::success();
		}
	}

}
