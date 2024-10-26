/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import CommentsInstance from './services/CommentsInstance.js'

// Init Comments
if (window.OCA && !window.OCA.Comments) {
	Object.assign(window.OCA, { Comments: {} })
}

// Init Comments App view
Object.assign(window.OCA.Comments, { View: CommentsInstance })
console.debug('OCA.Comments.View initialized')
