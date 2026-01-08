/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createStore } from 'vuex'
import predefinedStatuses from './predefinedStatuses.js'
import userBackupStatus from './userBackupStatus.js'
import userStatus from './userStatus.js'

export default createStore({
	modules: {
		predefinedStatuses,
		userStatus,
		userBackupStatus,
	},
	strict: true,
})
