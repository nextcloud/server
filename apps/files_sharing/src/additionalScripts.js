/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCSPNonce } from '@nextcloud/auth'

import './collaborationresourceshandler.js'

__webpack_nonce__ = getCSPNonce()

window.OCA.Sharing = OCA.Sharing
