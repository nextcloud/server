
__webpack_nonce__ = btoa(OC.requestToken);
__webpack_public_path__ = OC.linkTo('files_sharing', 'js/dist/');

import '../js/app';
import '../js/sharedfilelist';
import '../js/sharetabview';
import '../js/share';
import '../js/sharebreadcrumbview';

window.OCP.Collaboration.registerType('files', {
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
	typeString: t('files_sharing', 'file')
});

window.OCA.Sharing = OCA.Sharing;
