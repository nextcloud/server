<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OC\Preview;

//.docx, .dotx, .xlsx, .xltx, .pptx, .potx, .ppsx
class MSOffice2007 extends Office {
	/**
	 * {@inheritDoc}
	 */
	public function getMimeType() {
		return '/application\/vnd.openxmlformats-officedocument.*/';
	}
}
