__webpack_public_path__ = OC.linkTo('files_sharing', 'js/');
__webpack_nonce__ = btoa(OC.requestToken);

import './share'
import './sharetabview'
import './sharebreadcrumbview'

import './style/sharetabview.scss'
import './style/sharebreadcrumb.scss'

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
	typeString: t('files_sharing', 'file'),
	typeIconClass: 'icon-files-dark'
});

window.OCA.Sharing = OCA.Sharing;
