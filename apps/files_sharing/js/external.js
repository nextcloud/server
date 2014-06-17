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
	var getParameterByName = function (query, name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\#&]" + name + "=([^&#]*)"),
			results = regex.exec(query);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};

	var addExternalShare = function (remote, token, owner, name, password) {
		return $.post(OC.generateUrl('apps/files_sharing/external'), {
			remote: remote,
			token: token,
			owner: owner,
			name: name,
			password: password
		});
	};

	var showAddExternalDialog = function (remote, token, owner, name, passwordProtected) {
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
			OC.dialogs.confirm(t('files_sharing', 'Add {name} from {owner}@{remote}', {name: name, owner: owner, remote: remoteClean})
				, 'Add Share', callback, true);
		} else {
			OC.dialogs.prompt(t('files_sharing', 'Add {name} from {owner}@{remote}', {name: name, owner: owner, remote: remoteClean})
				, 'Add Share', callback, true, 'Password', true);
		}
	};

	OCA.Sharing.showAddExternalDialog = function (hash) {
		var remote = getParameterByName(hash, 'remote');
		var owner = getParameterByName(hash, 'owner');
		var name = getParameterByName(hash, 'name');
		var token = getParameterByName(hash, 'token');
		var passwordProtected = parseInt(getParameterByName(hash, 'protected'), 10);

		if (remote && token && owner && name) {
			showAddExternalDialog(remote, token, owner, name, passwordProtected);
		}
	};
})();

$(document).ready(function () {
	// FIXME: HACK: do not init when running unit tests, need a better way
	if (!window.TESTING && OCA.Files) {// only run in the files app
		var hash = location.hash;
		location.hash = '';
		OCA.Sharing.showAddExternalDialog(hash);
	}
});
