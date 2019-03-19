__webpack_public_path__ = OC.linkTo('files_sharing', 'js/dist/');
__webpack_nonce__ = btoa(OC.requestToken);

import './share'
import './sharetabview'
import './sharebreadcrumbview'

import './style/sharetabview.scss'
import './style/sharebreadcrumb.scss'

import './collaborationresourceshandler.js'

window.OCA.Sharing = OCA.Sharing;
