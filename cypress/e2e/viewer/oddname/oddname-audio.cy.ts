/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import runTest from '../mixins/oddname.ts'

for (const [file, type] of [
	['audio.mp3', 'audio/mpeg'],
	['audio.ogg', 'audio/ogg'],
]) {
	runTest(file, type)
}
