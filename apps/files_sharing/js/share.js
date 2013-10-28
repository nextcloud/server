/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global OC, t, FileList, FileActions */
$(document).ready(function() {

	var disableSharing = $('#disableSharing').data('status'),
		sharesLoaded = false;

	if (typeof OC.Share !== 'undefined' && typeof FileActions !== 'undefined'  && !disableSharing) {
		var oldCreateRow = FileList._createRow;
		FileList._createRow = function(fileData) {
			var tr = oldCreateRow.apply(this, arguments);
			if (fileData.shareOwner) {
				tr.attr('data-share-owner', fileData.shareOwner);
			}
			return tr;
		};

		$('#fileList').on('fileActionsReady',function(){

			var allShared = $('#fileList').find('[data-share-owner] [data-Action="Share"]');
			allShared.addClass('permanent');
			allShared.find('span').text(function(){
				var $owner = $(this).closest('tr').attr('data-share-owner');
				return ' ' + t('files_sharing', 'Shared by {owner}', {owner: $owner});
			});

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
			var dir = $('#dir').val();
			var item = dir + '/' + filename;
			if (dir == '/') {
				item = dir + filename;
			}
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
