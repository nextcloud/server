/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import audioTest from '../mixins/audio'

describe('Open audio.mp3 in viewer', function() {
	audioTest('audio.mp3', 'audio/mpeg')
})
