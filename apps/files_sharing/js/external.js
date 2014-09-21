/*
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
	var addExternalShare = function (remote, token, owner, name, password) {
		return $.post(OC.generateUrl('apps/files_sharing/external'), {
			remote: remote,
			token: token,
			owner: owner,
			name: name,
			password: password
		});
	};

	/**
	 * Shows "add external share" dialog.
	 *
	 * @param {String} remote remote server URL
	 * @param {String} owner owner name
	 * @param {String} name name of the shared folder
	 * @param {String} token authentication token
	 * @param {bool} passwordProtected true if the share is password protected
	 */
	OCA.Sharing.showAddExternalDialog = function (remote, token, owner, name, passwordProtected) {
		var remoteClean = (remote.substr(0, 8) === 'https://') ? remote.substr(8) : remote.substr(7);
		var callback = function (add, password) {
			password = password || '';
			if (add) {
				addExternalShare(remote, token, owner, name, password).then(function (result) {
					if (result.status === 'error') {
						OC.Notification.show(result.data.message);
					} else {
						FileList.reload();
					}
				});
			}
		};
		if (!passwordProtected) {
			OC.dialogs.confirm(
				t(
					'files_sharing',
					'Do you want to add the remote share {name} from {owner}@{remote}?',
					{name: name, owner: owner, remote: remoteClean}
				),
				t('files_sharing','Remote share'),
				callback,
				true
			).then(this._adjustDialog);
		} else {
			OC.dialogs.prompt(
				t(
					'files_sharing',
					'Do you want to add the remote share {name} from {owner}@{remote}?',
					{name: name, owner: owner, remote: remoteClean}
				),
				t('files_sharing','Remote share'),
				callback,
				true,
				t('files_sharing','Remote share password'),
				true
			).then(this._adjustDialog);
		}
	};

	OCA.Sharing._adjustDialog = function() {
		var $dialog = $('.oc-dialog:visible');
		var $buttons = $dialog.find('button');
		// hack the buttons
		$dialog.find('.ui-icon').remove();
		$buttons.eq(0).text(t('core', 'Cancel'));
		$buttons.eq(1).text(t('files_sharing', 'Add remote share'));
	};
})();

$(document).ready(function () {
	// FIXME: HACK: do not init when running unit tests, need a better way
	if (!window.TESTING && OCA.Files) {// only run in the files app
		var params = OC.Util.History.parseUrlQuery();
		if (params.remote && params.token && params.owner && params.name) {
			// clear hash, it is unlikely that it contain any extra parameters
			location.hash = '';
			params.passwordProtected = parseInt(params.protected, 10) === 1;
			OCA.Sharing.showAddExternalDialog(
				params.remote,
				params.token,
				params.owner,
				params.name,
				params.passwordProtected
			);
		}
	}
});
