/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Uploader } from '@nextcloud/upload'
import type { UploaderStore } from '../types.ts'

import { getUploader } from '@nextcloud/upload'
import { defineStore } from 'pinia'

let uploader: Uploader

/**
 *
 * @param args
 */
export function useUploaderStore(...args) {
	// Only init on runtime
	uploader = getUploader()

	const store = defineStore('uploader', {
		state: () => ({
			queue: uploader.queue,
		} as UploaderStore),
	})

	return store(...args)
}
