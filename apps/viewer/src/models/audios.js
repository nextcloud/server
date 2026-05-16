/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Audios from '../components/Audios.vue'

export default {
	id: 'audios',
	group: 'media',
	mimes: [
		'audio/aac',
		'audio/aacp',
		'audio/flac',
		'audio/mp4',
		'audio/mpeg',
		'audio/ogg',
		'audio/vorbis',
		'audio/wav',
		'audio/webm',
	],
	component: Audios,
}
