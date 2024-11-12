/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineAsyncComponent } from 'vue'
import { getBuilder } from '@nextcloud/browser-storage'
import { getGuestNickname, setGuestNickname } from '@nextcloud/auth'
import { getUploader } from '@nextcloud/upload'
import { spawnDialog } from '@nextcloud/dialogs'

import logger from './services/logger'

const storage = getBuilder('files_sharing').build()

/**
 * Setup file-request nickname header for the uploader
 * @param nickname The nickname
 */
function registerFileRequestHeader(nickname: string) {
	const uploader = getUploader()
	uploader.setCustomHeader('X-NC-Nickname', encodeURIComponent(nickname))
	logger.debug('Nickname header registered for uploader', { headers: uploader.customHeaders })
}

/**
 * Callback when a nickname was chosen
 * @param nickname The chosen nickname
 */
function onSetNickname(nickname: string): void {
	// Set the nickname
	setGuestNickname(nickname)
	// Set the dialog as shown
	storage.setItem('public-auth-prompt-shown', 'true')
	// Register header for uploader
	registerFileRequestHeader(nickname)
}

window.addEventListener('DOMContentLoaded', () => {
	const nickname = getGuestNickname() ?? ''
	const dialogShown = storage.getItem('public-auth-prompt-shown') !== null

	// If we don't have a nickname or the public auth prompt hasn't been shown yet, show it
	// We still show the prompt if the user has a nickname to double check
	if (!nickname || !dialogShown) {
		spawnDialog(
			defineAsyncComponent(() => import('./views/PublicAuthPrompt.vue')),
			{
				nickname,
			},
			onSetNickname as (...rest: unknown[]) => void,
		)
	} else {
		logger.debug('Public auth prompt already shown.', { nickname })
		registerFileRequestHeader(nickname)
	}
})
