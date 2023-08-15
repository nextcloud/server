/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'
import { translate as t } from '@nextcloud/l10n'

import { registerWidget, registerCustomPickerElement, NcCustomPickerRenderResult } from '@nextcloud/vue/dist/Components/NcRichText.js'

import FileWidget from './views/ReferenceFileWidget.vue'
import FileReferencePickerElement from './views/FileReferencePickerElement.vue'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

Vue.mixin({
	methods: {
		t,
	},
})

registerWidget('file', (el, { richObjectType, richObject, accessible }) => {
	const Widget = Vue.extend(FileWidget)
	new Widget({
		propsData: {
			richObjectType,
			richObject,
			accessible,
		},
	}).$mount(el)
})

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
