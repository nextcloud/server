/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { DeclarativeForm } from './types.ts'

import { loadState } from '@nextcloud/initial-state'
import { createApp } from 'vue'
import DeclarativeSection from './DeclarativeSection.vue'
import logger from '../../logger.ts'

const forms = loadState<DeclarativeForm[]>('settings', 'declarative-settings-forms', [])
logger.debug('Loaded declarative forms', { forms })

for (const form of forms) {
	// ids come from the apps and may not be valid CSS selectors
	const el = document.getElementById(`${form.app}_${form.id}`)
	if (el) {
		createApp(DeclarativeSection, { form }).mount(el)
	}
}
