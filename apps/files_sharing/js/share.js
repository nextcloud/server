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
		if (OCA.Files.FileList) {
			var oldCreateRow = OCA.Files.FileList.prototype._createRow;
			OCA.Files.FileList.prototype._createRow = function(fileData) {
				var tr = oldCreateRow.apply(this, arguments);
				if (fileData.shareOwner) {
					tr.attr('data-share-owner', fileData.shareOwner);
				}
				return tr;
			};
		}

		$('#fileList').on('fileActionsReady',function(){
			// if no share action exists because the admin disabled sharing for this user
			// we create a share notification action to inform the user about files
			// shared with him otherwise we just update the existing share action.
			var allShared;
			if (oc_appconfig.core.sharingDisabledForUser) {
				var $fileList = $(this);
				allShared = $fileList.find('[data-share-owner]');
				var shareNotification = '<a class="action action-share-notification permanent"' +
						' data-action="Share-Notification" href="#" original-title="">' +
						' <img class="svg" src="' + OC.imagePath('core', 'actions/share') + '"></img>';
				$(allShared).find('.fileactions').append(function() {
					var owner = $(this).closest('tr').attr('data-share-owner');
					var shareBy = t('files_sharing', 'Shared by {owner}', {owner: owner});
					return shareNotification + '<span> ' + shareBy + '</span></span>';
				});
			} else {
				allShared = $fileList.find('[data-share-owner] [data-Action="Share"]');
				allShared.addClass('permanent');
				allShared.find('span').text(function(){
					var $owner = $(this).closest('tr').attr('data-share-owner');
					return ' ' + t('files_sharing', 'Shared by {owner}', {owner: $owner});
				});
			}

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

		FileActions.register('all', 'Share', OC.PERMISSION_SHARE, OC.imagePath('core', 'actions/share'), function(filename) {
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
