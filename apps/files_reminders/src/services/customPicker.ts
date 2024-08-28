/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Node } from '@nextcloud/files'
import Vue from 'vue'

import SetCustomReminderModal from '../components/SetCustomReminderModal.vue'

const View = Vue.extend(SetCustomReminderModal)
const mount = document.createElement('div')
mount.id = 'set-custom-reminder-modal'
document.body.appendChild(mount)

// Create a new Vue instance and mount it to our modal container
const CustomReminderModal = new View({
	name: 'SetCustomReminderModal',
	el: mount,
})

export const pickCustomDate = (node: Node): Promise<void> => {
	CustomReminderModal.open(node)

	// Wait for the modal to close
	return new Promise((resolve) => {
		CustomReminderModal.$once('close', resolve)
	})
}
