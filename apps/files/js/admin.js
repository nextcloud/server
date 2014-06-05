/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

function switchPublicFolder() {
	var publicEnable = $('#publicEnable').is(':checked');
	// find all radiobuttons of that group
	var sharingaimGroup = $('input:radio[name=sharingaim]');
	$.each(sharingaimGroup, function(index, sharingaimItem) {
		// set all buttons to the correct state
		sharingaimItem.disabled = !publicEnable;
	});
}

$(document).ready(function() {
	// Execute the function after loading DOM tree
	switchPublicFolder();
	$('#publicEnable').click(function() {
		// To get rid of onClick()
		switchPublicFolder();
	});
});
