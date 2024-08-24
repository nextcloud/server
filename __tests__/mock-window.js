/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { beforeEach } from 'vitest'

window.OC = { ...window.OC }
window.OCA = { ...window.OCA }
window.OCP = { ...window.OCP }

beforeEach(() => {
	window.location = new URL('http://nextcloud.local')
})
