/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { useHotKey } from '@nextcloud/vue/dist/Composables/useHotKey.js'

import { action as manageTagAction } from '../files_actions/bulkSystemTagsAction.ts'
import { executeAction } from '../../../files/src/utils/actionUtils.ts'
import logger from '../logger.ts'

/**
 * This register the hotkeys for the Files app.
 * As much as possible, we try to have all the hotkeys in one place.
 * Please make sure to add tests for the hotkeys after adding a new one.
 */
export const registerHotkeys = function() {
	// t opens the tag management dialog
	useHotKey('t', () => executeAction(manageTagAction), {
		stop: true,
		prevent: true,
	})

	logger.debug('Hotkeys registered')
}
