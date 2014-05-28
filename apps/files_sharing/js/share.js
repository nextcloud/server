/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	if (!OCA.Sharing) {
		OCA.Sharing = {};
	}
	OCA.Sharing.Util = {
		initialize: function() {
			if (!_.isUndefined(OC.Share) && !_.isUndefined(OCA.Files)) {
				// TODO: make a separate class for this or a hook or jQuery event ?
				if (OCA.Files.FileList) {
					var oldCreateRow = OCA.Files.FileList.prototype._createRow;
					OCA.Files.FileList.prototype._createRow = function(fileData) {
						var tr = oldCreateRow.apply(this, arguments);
						if (fileData.shareOwner) {
							tr.attr('data-share-owner', fileData.shareOwner);
							// user should always be able to rename a mount point
							if (fileData.isShareMountPoint) {
								tr.attr('data-permissions', fileData.permissions | OC.PERMISSION_UPDATE);
								tr.attr('data-reshare-permissions', fileData.permissions);
							}
						}
						return tr;
					};
				}

				// use delegate to catch the case with multiple file lists
				$('#content').delegate('#fileList', 'fileActionsReady',function(ev){
					// if no share action exists because the admin disabled sharing for this user
					// we create a share notification action to inform the user about files
					// shared with him otherwise we just update the existing share action.
					var fileList = ev.fileList;
					var $fileList = $(this);
					$fileList.find('[data-share-owner]').each(function() {
						var $tr = $(this);
						var permissions = $tr.data('permissions');
						if(permissions & OC.PERMISSION_SHARE) {
							OC.Share.markFileAsShared($tr, true);
						} else {
							// TODO: make this work like/with OC.Share.markFileAsShared()
							var shareNotification = '<a class="action action-share-notification permanent"' +
									' data-action="Share-Notification" href="#" original-title="">' +
									' <img class="svg" src="' + OC.imagePath('core', 'actions/share') + '"></img>';
							$tr.find('.fileactions').append(function() {
								var owner = $(this).closest('tr').attr('data-share-owner');
								var shareBy = t('files_sharing', 'Shared by {owner}', {owner: owner});
								var $result = $(shareNotification + '<span> ' + shareBy + '</span></span>');
								$result.on('click', function() {
									return false;
								});
								return $result;
							});
						}
					});

					if (!OCA.Sharing.sharesLoaded){
						OC.Share.loadIcons('file', fileList);
						// assume that we got all shares, so switching directories
						// will not invalidate that list
						OCA.Sharing.sharesLoaded = true;
					}
					else{
						OC.Share.updateIcons('file', fileList);
					}
				});

				OCA.Files.fileActions.register(
						'all',
						'Share',
						OC.PERMISSION_SHARE,
						OC.imagePath('core', 'actions/share'),
						function(filename, context) {

					var $tr = context.$file;
					var itemType = 'file';
					if ($tr.data('type') === 'dir') {
						itemType = 'folder';
					}
					var possiblePermissions = $tr.data('reshare-permissions');
					if (_.isUndefined(possiblePermissions)) {
						possiblePermissions = $tr.data('permissions');
					}

					var appendTo = $tr.find('td.filename');
					// Check if drop down is already visible for a different file
					if (OC.Share.droppedDown) {
						if ($tr.data('id') !== $('#dropdown').attr('data-item-source')) {
							OC.Share.hideDropDown(function () {
								$tr.addClass('mouseOver');
								OC.Share.showDropDown(itemType, $tr.data('id'), appendTo, true, possiblePermissions, filename);
							});
						} else {
							OC.Share.hideDropDown();
						}
					} else {
						$tr.addClass('mouseOver');
						OC.Share.showDropDown(itemType, $tr.data('id'), appendTo, true, possiblePermissions, filename);
					}
					$('#dropdown').on('sharesChanged', function(ev) {
						// note: we only update the data attribute because updateIcon()
						// is called automatically after this event
						var userShares = ev.itemShares[OC.Share.SHARE_TYPE_USER] || [];
						var groupShares = ev.itemShares[OC.Share.SHARE_TYPE_GROUP] || [];
						var linkShares = ev.itemShares[OC.Share.SHARE_TYPE_LINK] || [];
						var recipients = _.union(userShares, groupShares);
						if (linkShares.length > 0) {
							recipients.unshift(t('files_sharing', 'Public'));
						}
						// only update the recipients if they existed before
						// (some file lists don't have them)
						if (!_.isUndefined($tr.attr('data-share-recipients'))) {
							// FIXME: use display names from users, we currently only got user ids
							if (recipients.length) {
								$tr.attr('data-share-recipients', OCA.Sharing.Util.formatRecipients(recipients));
							}
							else {
								$tr.attr('data-share-recipients', '');
							}
						}
					});
				});
			}
		},

		/**
		 * Formats a recipient array to be displayed.
		 * The first four recipients will be shown and the
		 * other ones will be shown as "+x" where "x" is the number of
		 * remaining recipients.
		 *
		 * @param recipients recipients array
		 * @param count optional total recipients count (in case the array was shortened)
		 * @return formatted recipients display text
		 */
		formatRecipients: function(recipients, count) {
			var maxRecipients = 4;
			var text;
			if (!_.isNumber(count)) {
				count = recipients.length;
			}
			text = _.first(recipients, maxRecipients).join(', ');
			if (count > maxRecipients) {
				text += ', +' + (count - maxRecipients);
			}
			return text;
		}
	};
})();

$(document).ready(function() {
	OCA.Sharing.Util.initialize();
});

