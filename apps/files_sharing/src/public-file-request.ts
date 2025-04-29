/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineAsyncComponent } from 'vue'
import { getBuilder } from '@nextcloud/browser-storage'
import { getGuestNickname } from '@nextcloud/auth'
import { getUploader } from '@nextcloud/upload'
import { loadState } from '@nextcloud/initial-state'
import { spawnDialog } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

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
	// Register header for uploader
	registerFileRequestHeader(nickname)
}

window.addEventListener('DOMContentLoaded', () => {
	const nickname = getGuestNickname() ?? ''
	const dialogShown = storage.getItem('public-auth-prompt-shown') !== null

	const owner = loadState('files_sharing', 'owner', '')
	const ownerDisplayName = loadState('files_sharing', 'ownerDisplayName', '')
	const label = loadState('files_sharing', 'label', '')
	const filename = loadState('files_sharing', 'filename', '')

	// If the owner provided a custom label, use it instead of the filename
	const folder = label || filename

	const options = {
		nickname,
		notice: t('files_sharing', 'To upload files to {folder}, you need to provide your name first.', { folder }),
		subtitle: undefined as string | undefined,
		title: t('files_sharing', 'Upload files to {folder}', { folder }),
	}

	// If the guest already has a nickname, we just make them double check
	if (nickname) {
		options.notice = t('files_sharing', 'Please confirm your name to upload files to {folder}', { folder })
	}

	// If the account owner set their name as public,
	// we show it in the subtitle
	if (owner) {
		options.subtitle = t('files_sharing', '{ownerDisplayName} shared a folder with you.', { ownerDisplayName })
	}

	// If we don't have a nickname or the public auth prompt hasn't been shown yet, show it
	// We still show the prompt if the user has a nickname to double check
	if (!nickname || !dialogShown) {
		spawnDialog(
			defineAsyncComponent(() => import('./views/PublicAuthPrompt.vue')),
			options,
			onSetNickname as (...rest: unknown[]) => void,
		)
	} else {
		logger.debug('Public auth prompt already shown.', { nickname })
		registerFileRequestHeader(nickname)
	}
})
