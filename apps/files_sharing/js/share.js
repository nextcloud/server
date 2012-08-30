$(document).ready(function() {

	if (typeof OC.Share !== 'undefined' && typeof FileActions !== 'undefined') {
		OC.Share.loadIcons('file');
		FileActions.register('all', 'Share', OC.PERMISSION_READ, function(filename) {
			// Return the correct sharing icon
			if (scanFiles.scanning) { return; } // workaround to prevent additional http request block scanning feedback
			if ($('#dir').val() == '/') {
				var item = $('#dir').val() + filename;
			} else {
				var item = $('#dir').val() + '/' + filename;
			}
			// Check if status is in cache
			if (OC.Share.statuses[item] === true) {
				return OC.imagePath('core', 'actions/public');
			} else if (OC.Share.statuses[item] === false) {
				return OC.imagePath('core', 'actions/shared');
			} else {
				var last = '';
				var path = OC.Share.dirname(item);
				// Search for possible parent folders that are shared
				while (path != last) {
					if (OC.Share.statuses[path] === true) {
						return OC.imagePath('core', 'actions/public');
					} else if (OC.Share.statuses[path] === false) {
						return OC.imagePath('core', 'actions/shared');
					}
					last = path;
					path = OC.Share.dirname(path);
				}
				return OC.imagePath('core', 'actions/share');
			}
		}, function(filename) {
			if ($('#dir').val() == '/') {
				var item = $('#dir').val() + filename;
			} else {
				var item = $('#dir').val() + '/' + filename;
			}
			var tr = $('tr').filterAttr('data-file', filename);
			if ($(tr).data('type') == 'dir') {
				var itemType = 'folder';
			} else {
				var itemType = 'file';
			}
			var possiblePermissions = $(tr).data('permissions');
			var appendTo = $(tr).find('td.filename');
			// Check if drop down is already visible for a different file
			if (OC.Share.droppedDown) {
				if (item != $('#dropdown').data('item')) {
					OC.Share.hideDropDown(function () {
						$(tr).addClass('mouseOver');
						OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions);
					});
				} else {
					OC.Share.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions);
			}
		});
	}

});