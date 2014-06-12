$(document).ready(function () {
	var getParameterByName = function (query, name) {
		name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
		var regex = new RegExp("[\\#&]" + name + "=([^&#]*)"),
			results = regex.exec(query);
		return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
	};

	var addExternalShare = function (remote, token, owner, name, password) {
		return $.post(OC.generateUrl('apps/files_sharing/external'), {
			remote  : remote,
			token   : token,
			owner   : owner,
			name    : name,
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
			OC.dialogs.confirm('Add ' + name + ' from ' + owner + '@' + remoteClean, 'Add Share', callback, true);
		} else {
			OC.dialogs.prompt('Add ' + name + ' from ' + owner + '@' + remoteClean, 'Add Share', callback, true, 'Password', true);
		}
	};

	if (OCA.Files) {// only run in the files app
		var hash = location.hash;
		location.hash = '';
		var remote = getParameterByName(hash, 'remote');
		var owner = getParameterByName(hash, 'owner');
		var name = getParameterByName(hash, 'name');
		var token = getParameterByName(hash, 'token');
		var passwordProtected = parseInt(getParameterByName(hash, 'protected'), 10);

		if (remote && token && owner && name) {
			showAddExternalDialog(remote, token, owner, name, passwordProtected);
		}
	}
});
