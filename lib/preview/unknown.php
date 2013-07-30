<?php
/**
 * Copyright (c) 2013 Frank Karlitschek frank@owncloud.org
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

class Unknown extends Provider {

	public function getMimeType() {
		return '/.*/';
	}

	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview) {
		$mimetype = $fileview->getMimeType($path);
		if(substr_count($mimetype, '/')) {
			list($type, $subtype) = explode('/', $mimetype);
		}

		$iconsRoot = \OC::$SERVERROOT . '/core/img/filetypes/';

		if(isset($type)){
			$icons = array($mimetype, $type, 'text');
		}else{
			$icons = array($mimetype, 'text');
		}
		foreach($icons as $icon) {
			$icon = str_replace('/', '-', $icon);

			$iconPath = $iconsRoot . $icon . '.png';

			if(file_exists($iconPath)) {
				return new \OC_Image($iconPath);
			}
		}
		return false;
	}
}

\OC\Preview::registerProvider('OC\Preview\Unknown');