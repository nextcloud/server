/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import AudioOutlineSvg from '@mdi/svg/svg/music-note-outline.svg?raw'
import { t } from '@nextcloud/l10n'
import { defineCustomElement } from 'vue'
import Audios from '../components/Audios.vue'
import { registerHandler } from '../api_package/index.ts'
import { logger } from '../services/logger.ts'

const browserSupportedMimes = [
	'audio/aac',
	'audio/aacp',
	'audio/flac',
	'audio/mp4',
	'audio/mpeg',
	'audio/ogg',
	'audio/vorbis',
	'audio/wav',
	'audio/webm',
]

export const tagname = 'oca-viewer-audio'

/**
 * Register the audio custom element.
 */
export function registerAudioCustomElement() {
	const AudioElement = defineCustomElement(Audios, {
		shadowRoot: false,
	})

	// Register the custom element.
	window.customElements.define(tagname, AudioElement)
}

/**
 * Register the audio handler.
 */
export function registerAudioHandler() {
	registerHandler({
		id: 'audios',
		displayName: t('viewer', 'Audio player'),
		tagname,

		iconSvgInline: AudioOutlineSvg,

		group: 'media',

		enabled: (nodes) => {
			if (nodes.length === 0) {
				return false
			}

			return nodes.every((node) => {
				// Always allow browser supported mimes
				if (browserSupportedMimes.includes(node.mime)) {
					return true
				}

				return false
			})
		},
	})
	logger.info('Audio handler registered', { tagname })
}
