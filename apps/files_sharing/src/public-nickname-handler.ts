/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getBuilder } from '@nextcloud/browser-storage'
import { getGuestNickname, type NextcloudUser } from '@nextcloud/auth'
import { getUploader } from '@nextcloud/upload'
import { loadState } from '@nextcloud/initial-state'
import { showGuestUserPrompt } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

import logger from './services/logger'
import { subscribe } from '@nextcloud/event-bus'

const storage = getBuilder('files_sharing').build()

// Setup file-request nickname header for the uploader
const registerFileRequestHeader = (nickname: string) => {
	const uploader = getUploader()
	uploader.setCustomHeader('X-NC-Nickname', encodeURIComponent(nickname))
	logger.debug('Nickname header registered for uploader', { headers: uploader.customHeaders })
}

// Callback when a nickname was chosen
const onUserInfoChanged = (guest: NextcloudUser) => {
	logger.debug('User info changed', { guest })
	registerFileRequestHeader(guest.displayName ?? '')
}

// Monitor nickname changes
subscribe('user:info:changed', onUserInfoChanged)

window.addEventListener('DOMContentLoaded', () => {
	const nickname = getGuestNickname() ?? ''
	const dialogShown = storage.getItem('public-auth-prompt-shown') !== null

	// Check if a nickname is mandatory
	const isFileRequest = loadState('files_sharing', 'isFileRequest', false)

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

	// If this is a file request, then we need a nickname
	if (isFileRequest) {
		// If we don't have a nickname or the public auth prompt hasn't been shown yet, show it
		// We still show the prompt if the user has a nickname to double check
		if (!nickname || !dialogShown) {
			logger.debug('Showing public auth prompt.', { nickname })
			showGuestUserPrompt(options)
		}
		return
	}

	if (!dialogShown && !nickname) {
		logger.debug('Public auth prompt not shown yet but nickname is not mandatory.', { nickname })
		return
	}

	// Else, we just register the nickname header if any.
	logger.debug('Public auth prompt already shown.', { nickname })
	registerFileRequestHeader(nickname)
})
