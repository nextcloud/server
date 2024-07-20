/**
 * SPDX-FileCopyrightText: 2013-2014 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

function Upload(fileSelector) {
	if ($.support.xhrFileUpload) {
		return new XHRUpload(fileSelector.target.files);
	} else {
		return new FormUpload(fileSelector);
	}
}
Upload.target = OC.filePath('files', 'ajax', 'upload.php');
