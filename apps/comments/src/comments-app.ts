/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import logger from './logger.js'
import CommentsInstance from './services/CommentsInstance.js'

// Init Comments
if (window.OCA && !window.OCA.Comments) {
	Object.assign(window.OCA, { Comments: {} })
}

// Init Comments App view
Object.assign(window.OCA.Comments, { View: CommentsInstance })
logger.debug('OCA.Comments.View initialized')
