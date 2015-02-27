<?php
/**
 * Copyright (c) 2013-2014 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Preview;

//.tiff
class TIFF extends Bitmap {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/image\/tiff/';
	}
}
