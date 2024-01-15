/**
 * @copyright 2023 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue, { type ComponentInstance } from 'vue'
import { subscribe } from '@nextcloud/event-bus'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import SetReminderActionsComponent from './components/SetReminderActions.vue'

import { getReminder } from './services/reminderService.js'
import { logger } from './shared/logger.js'

import type { FileAttributes } from './shared/types.js'

interface FileContext {
	[key: string]: any
	$file: JQuery<HTMLTableRowElement>
	fileInfoModel: {
		[key: string]: any
		attributes: FileAttributes
	}
}

interface EventPayload {
	el: HTMLDivElement
	context: FileContext
}

const handleOpen = async (payload: EventPayload) => {
	const fileId = payload.context.fileInfoModel.attributes.id
	const menuEl = payload.context.$file[0].querySelector('.fileactions .action-menu') as HTMLLinkElement
	const linkEl = payload.el.querySelector('.action-setreminder-container .action-setreminder') as HTMLLinkElement

	let dueDate: null | Date = null
	let error: null | any = null
	try {
		dueDate = (await getReminder(fileId)).dueDate
	} catch (e) {
		error = e
		logger.error(`Failed to load reminder for file with id: ${fileId}`, { error })
	}

	linkEl.addEventListener('click', (_event) => {
		if (error) {
			showError(t('files_reminders', 'Failed to load reminder'))
			throw Error()
		}

		const mountPoint = document.createElement('div')
		const SetReminderActions = Vue.extend(SetReminderActionsComponent)

		const origDisplay = menuEl.style.display
		menuEl.style.display = 'none'
		menuEl.insertAdjacentElement('afterend', mountPoint)

		const propsData = {
			file: payload.context.fileInfoModel.attributes,
			dueDate,
		}
		const actions = (new SetReminderActions({ propsData }) as ComponentInstance)
			.$mount(mountPoint)

		const cleanUp = () => {
			actions.$destroy() // destroy popper
			actions.$el.remove() // remove action menu button
			menuEl.style.display = origDisplay
		}

		actions.$once('back', () => {
			cleanUp()
			menuEl.click() // reopen original actions menu
		})

		actions.$once('close', () => {
			cleanUp()
		})
	}, {
		once: true,
	})
}

subscribe('files:action-menu:opened', handleOpen)
