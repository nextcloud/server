// CSP config for webpack dynamic chunk loading
// eslint-disable-next-line
__webpack_nonce__ = btoa(OC.requestToken)

// Correct the root of the app for chunk loading
// OC.linkTo matches the apps folders
// eslint-disable-next-line
__webpack_public_path__ = OC.linkTo('files_sharing', 'js/dist/')

import '../js/app'
import '../js/sharedfilelist'
import '../js/sharetabview'
import '../js/share'
import '../js/sharebreadcrumbview'

window.OCP.Collaboration.registerType('files', {
	action: () => {
		return new Promise((resolve, reject) => {
			OC.dialogs.filepicker('Link to a file', function (f) {
				const client = OC.Files.getClient();
				client.getFileInfo(f).then((status, fileInfo) => {
					resolve(fileInfo.id)
				}, () => {
					reject()
				})
			}, false);
		})
	},
	link: (id) => OC.generateUrl('/f/') + id,
	icon: 'nav-icon-files',
	/** used in "Link to a {typeString}" */
	typeString: 'file'
});

window.OCA.Sharing = OCA.Sharing
