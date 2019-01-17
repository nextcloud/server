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

window.OCA.Sharing = OCA.Sharing
