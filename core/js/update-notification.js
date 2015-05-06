/**
 * Copyright (c) 2015 ownCloud Inc
 *
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/**
 * this gets only loaded if an update is available and then shows a temporary notification
 */
$(document).ready(function(){
	var head = $('html > head'),
		version = head.data('update-version'),
		docLink = head.data('update-link'),
		text = t('core', '{version} is available. Get more information on how to update.', {version: version}),
		element = $('<a>').attr('href', docLink).text(text);

	OC.Notification.showTemporary(
		element,
		{
			isHTML: true
		}
	);
});

