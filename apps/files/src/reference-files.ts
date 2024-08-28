/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { translate as t } from '@nextcloud/l10n'

import { registerWidget, registerCustomPickerElement, NcCustomPickerRenderResult } from '@nextcloud/vue/dist/Components/NcRichText.js'

import FileWidget from './views/ReferenceFileWidget.vue'
import FileReferencePickerElement from './views/FileReferencePickerElement.vue'

Vue.mixin({
	methods: {
		t,
	},
})

registerWidget('file', (el, { richObjectType, richObject, accessible, interactive }) => {
	const Widget = Vue.extend(FileWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
			interactive,
		},
	}).$mount(el)
}, () => {}, { hasInteractiveView: true })

registerCustomPickerElement('files', (el, { providerId, accessible }) => {
	const Element = Vue.extend(FileReferencePickerElement)
	const vueElement = new Element({
		propsData: {
			providerId,
			accessible,
		},
	}).$mount(el)
	return new NcCustomPickerRenderResult(vueElement.$el, vueElement)
}, (el, renderResult) => {
	renderResult.object.$destroy()
})
