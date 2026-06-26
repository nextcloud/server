/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { NcCustomPickerRenderResult, registerCustomPickerElement, registerWidget } from '@nextcloud/vue/components/NcRichText'

registerWidget('profile_widget', async (el, { richObjectType, richObject, accessible }) => {
	const { createApp } = await import('vue')
	const { default: ProfilePickerReferenceWidget } = await import('./views/ProfilePickerReferenceWidget.vue')

	const app = createApp(
		ProfilePickerReferenceWidget,
		{
			richObjectType,
			richObject,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)
}, () => {}, { hasInteractiveView: false })

registerCustomPickerElement('profile_picker', async (el, { providerId, accessible }) => {
	const { createApp } = await import('vue')
	const { default: ProfilesCustomPicker } = await import('./components/ProfilesCustomPicker.vue')

	const app = createApp(
		ProfilesCustomPicker,
		{
			providerId,
			accessible,
		},
	)
	app.mixin({ methods: { t, n } })
	app.mount(el)

	return new NcCustomPickerRenderResult(el, app)
}, (el, renderResult) => {
	renderResult.object.unmount()
}, 'normal')
