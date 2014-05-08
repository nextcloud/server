/*
 * Copyright (c) 2014
 *
 * @author Vincent Petry
 * @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

if (!OCA.Files) {
	OCA.Files = {};
}

$(document).ready(function() {
	var nav = new OCA.Files.Navigation($('#app-navigation ul'));

	nav.setSelectedItem('files');

	// TODO: init file list, actions and others
});

