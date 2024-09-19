/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import videoTest from '../mixins/video'

describe('Open video1.mp4 in viewer', function() {
	videoTest('video1.mp4', 'video/mp4')
})
