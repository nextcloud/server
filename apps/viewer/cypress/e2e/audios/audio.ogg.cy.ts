/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import audioTest from '../mixins/audio'

describe('Open audio.ogg in viewer', function() {
	audioTest('audio.ogg', 'audio/ogg')
})
