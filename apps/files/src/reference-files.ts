/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App } from 'vue'

import { t } from '@nextcloud/l10n'
import { createApp } from 'vue'
import { NcCustomPickerRenderResult, registerCustomPickerElement, registerWidget } from '@nextcloud/vue/components/NcRichText'
import FileReferencePickerElement from './views/FileReferencePickerElement.vue'
import FileWidget from './views/ReferenceFileWidget.vue'

registerWidget('file', (el, { richObjectType, richObject, accessible, interactive }) => {
	const app = createApp(FileWidget, {
		richObjectType,
		richObject,
		accessible,
		interactive,
	})
	app.mixin({ methods: { t } })
	app.mount(el)
}, () => {}, { hasInteractiveView: true })

registerCustomPickerElement('files', (el, { providerId, accessible }) => {
	const app = createApp(FileReferencePickerElement, {
		providerId,
		accessible,
	})
	app.mixin({ methods: { t } })
	app.mount(el)
	return new NcCustomPickerRenderResult(el, app)
}, (el, renderResult) => {
	(renderResult.object as App).unmount()
})
