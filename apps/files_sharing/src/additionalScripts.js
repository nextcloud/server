/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import './share.js'
import './sharebreadcrumbview.js'
import './style/sharebreadcrumb.scss'
import './collaborationresourceshandler.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(OC.requestToken)

window.OCA.Sharing = OCA.Sharing
