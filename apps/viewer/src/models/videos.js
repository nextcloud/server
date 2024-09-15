/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Videos from '../components/Videos.vue'

export default {
	id: 'videos',
	group: 'media',
	mimes: [
		'video/mpeg',
		'video/ogg',
		'video/webm',
		'video/mp4',
		'video/x-m4v',
		'video/x-flv',
		'video/quicktime',
	],
	mimesAliases: {
		'video/x-matroska': 'video/webm',
	},
	component: Videos,
}
