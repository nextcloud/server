/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import videoTest from '../mixins/video'

describe('Open video.mkv in viewer', function() {
	videoTest('video.mkv', 'image/mkv')
})
