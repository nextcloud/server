/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { spawnDialog } from '@nextcloud/dialogs'
import { defineAsyncComponent } from 'vue'
import logger from './services/logger'

const nick = localStorage.getItem('nick')
const publicAuthPromptShown = localStorage.getItem('publicAuthPromptShown')

// If we don't have a nickname or the public auth prompt hasn't been shown yet, show it
// We still show the prompt if the user has a nickname to double check
if (!nick || !publicAuthPromptShown) {
	spawnDialog(
		defineAsyncComponent(() => import('./views/PublicAuthPrompt.vue')),
		{},
		() => localStorage.setItem('publicAuthPromptShown', 'true'),
	)
} else {
	logger.debug(`Public auth prompt already shown. Current nickname is '${nick}'`)
}
