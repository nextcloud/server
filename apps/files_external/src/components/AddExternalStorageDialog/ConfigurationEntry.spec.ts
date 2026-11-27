/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IConfigurationOption } from '../../types.ts'

import { cleanup, render } from '@testing-library/vue'
import { beforeEach, describe, expect, it } from 'vitest'
import ConfigurationEntry from './ConfigurationEntry.vue'
import { ConfigurationFlag, ConfigurationType } from '../../types.ts'

/**
 * Render a configuration entry for an option of the given type
 *
 * @param type - The configuration option type
 */
function renderEntry(type: IConfigurationOption['type']) {
	return render(ConfigurationEntry, {
		props: {
			configKey: 'option',
			configOption: { type, flags: ConfigurationFlag.Optional, value: 'Option' },
		},
	})
}

describe('ConfigurationEntry.vue', () => {
	beforeEach(cleanup)

	it.each([
		ConfigurationType.String,
		ConfigurationType.Password,
	])('disables browser autofill for option type %s', (type) => {
		const component = renderEntry(type)

		expect(component.getByLabelText('Option')).toHaveAttribute('autocomplete', 'off')
	})
})
