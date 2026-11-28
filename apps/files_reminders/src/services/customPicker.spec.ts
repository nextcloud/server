/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { File } from '@nextcloud/files'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { expect, test, vi } from 'vitest'
import SetCustomReminderModal from '../components/SetCustomReminderModal.vue'
import { pickCustomDate } from './customPicker.ts'

vi.mock('@nextcloud/vue/functions/dialog', () => ({
	spawnDialog: vi.fn(async () => undefined),
}))

test('pickCustomDate opens the custom reminder dialog for the node', async () => {
	const node = new File({ owner: 'admin', mime: 'text/plain', source: 'http://nextcloud.local/remote.php/dav/files/admin/a.txt', root: '/files/admin' })

	await pickCustomDate(node)

	expect(spawnDialog).toHaveBeenCalledOnce()
	expect(spawnDialog).toHaveBeenCalledWith(SetCustomReminderModal, { node })
})
