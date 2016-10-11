/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

function Upload(fileSelector) {
	if ($.support.xhrFileUpload) {
		return new XHRUpload(fileSelector.target.files);
	} else {
		return new FormUpload(fileSelector);
	}
}
Upload.target = OC.filePath('files', 'ajax', 'upload.php');
