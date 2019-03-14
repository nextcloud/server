__webpack_public_path__ = OC.linkTo('files_sharing', 'js/dist/');
__webpack_nonce__ = btoa(OC.requestToken);

window.OCP.Collaboration.registerType('file', {
	action: () => {
		return new Promise((resolve, reject) => {
			OC.dialogs.filepicker('Link to a file', function (f) {
				const client = OC.Files.getClient();
				client.getFileInfo(f).then((status, fileInfo) => {
					resolve(fileInfo.id);
				}, () => {
					reject();
				});
			}, false);
		});
	},
	/** used in "Link to a {typeString}" */
	typeString: t('files_sharing', 'file'),
	typeIconClass: 'icon-files-dark'
});
