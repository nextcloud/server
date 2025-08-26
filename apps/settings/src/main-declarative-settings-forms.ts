/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ComponentInstance } from 'vue'

import { loadState } from '@nextcloud/initial-state'
import { t, n } from '@nextcloud/l10n'
import Vue from 'vue'
import DeclarativeSection from './components/DeclarativeSettings/DeclarativeSection.vue'
import logger from './logger'

interface DeclarativeFormField {
	id: string,
	title: string,
	description: string,
	type: string,
	placeholder: string,
	label: string,
	options: Array<unknown>|null,
	value: unknown,
	default: unknown,
	sensitive: boolean,
}

interface DeclarativeForm {
	id: number,
	priority: number,
	section_type: string,
	section_id: string,
	storage_type: string,
	title: string,
	description: string,
	doc_url: string,
	app: string,
	fields: Array<DeclarativeFormField>,
}

const forms = loadState<DeclarativeForm[]>('settings', 'declarative-settings-forms', [])

/**
 * @param forms The forms to render
 */
function renderDeclarativeSettingsSections(forms: Array<DeclarativeForm>): ComponentInstance[] {
	Vue.mixin({ methods: { t, n } })
	const DeclarativeSettingsSection = Vue.extend(DeclarativeSection as never)

	return forms.map((form) => {
		const el = `#${form.app}_${form.id}`
		return new DeclarativeSettingsSection({
			el,
			propsData: {
				form,
			},
		})
	})
}

document.addEventListener('DOMContentLoaded', () => {
	logger.debug('Loaded declarative forms', { forms })
	renderDeclarativeSettingsSections(forms)
})
