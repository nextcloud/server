/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import { NcCustomPickerRenderResult, registerCustomPickerElement, registerWidget } from '@nextcloud/vue/components/NcRichText'
import FileReferencePickerElement from './views/FileReferencePickerElement.vue'
import FileWidget from './views/ReferenceFileWidget.vue'

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
