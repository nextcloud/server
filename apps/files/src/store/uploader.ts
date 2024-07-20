/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Uploader } from '@nextcloud/upload'
import type { UploaderStore } from '../types'

import { defineStore } from 'pinia'
import { getUploader } from '@nextcloud/upload'

let uploader: Uploader

export const useUploaderStore = function(...args) {
	// Only init on runtime
	uploader = getUploader()

	const store = defineStore('uploader', {
		state: () => ({
			queue: uploader.queue,
		} as UploaderStore),
	})

	return store(...args)
}
