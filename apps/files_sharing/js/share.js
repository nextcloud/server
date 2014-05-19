/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global FileList, FileActions */
$(document).ready(function() {

	var sharesLoaded = false;

	if (typeof OC.Share !== 'undefined' && typeof FileActions !== 'undefined') {
		// TODO: make a separate class for this or a hook or jQuery event ?
		var oldCreateRow = OCA.Files.FileList.prototype._createRow;
		OCA.Files.FileList.prototype._createRow = function(fileData) {
			var tr = oldCreateRow.apply(this, arguments);
			if (fileData.shareOwner) {
				tr.attr('data-share-owner', fileData.shareOwner);
			}
			return tr;
		};

		$('#fileList').on('fileActionsReady',function(){
			var $fileList = $(this);
			var allShared = $fileList.find('[data-share-owner] [data-Action="Share"]');
			allShared.addClass('permanent');
			allShared.find('span').text(function(){
				var $owner = $(this).closest('tr').attr('data-share-owner');
				return ' ' + t('files_sharing', 'Shared by {owner}', {owner: $owner});
			});

			// FIXME: these calls are also working on hard-coded
			// list selectors...
			if (!sharesLoaded){
				OC.Share.loadIcons('file');
				// assume that we got all shares, so switching directories
				// will not invalidate that list
				sharesLoaded = true;
			}
			else{
				OC.Share.updateIcons('file');
			}
		});

		FileActions.register('all', 'Share', OC.PERMISSION_READ, OC.imagePath('core', 'actions/share'), function(filename) {
			var tr = FileList.findFileEl(filename);
			var itemType = 'file';
			if ($(tr).data('type') == 'dir') {
				itemType = 'folder';
			}
			var possiblePermissions = $(tr).data('permissions');
			var appendTo = $(tr).find('td.filename');
			// Check if drop down is already visible for a different file
			if (OC.Share.droppedDown) {
				if ($(tr).data('id') != $('#dropdown').attr('data-item-source')) {
					OC.Share.hideDropDown(function () {
						$(tr).addClass('mouseOver');
						OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions, filename);
					});
				} else {
					OC.Share.hideDropDown();
				}
			} else {
				$(tr).addClass('mouseOver');
				OC.Share.showDropDown(itemType, $(tr).data('id'), appendTo, true, possiblePermissions, filename);
			}
		});
	}
});
