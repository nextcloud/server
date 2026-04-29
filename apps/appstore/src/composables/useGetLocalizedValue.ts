/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { ILocalizedValue } from '../constants/AppDiscoverTypes.ts'

import { getLanguage } from '@nextcloud/l10n'
import {
	type Ref,

	computed,
} from 'vue'

/**
 * Helper to get the localized value for the current users language
 *
 * @param dict The dictionary to get the value from
 * @param language The language to use
 */
const getLocalizedValue = <T>(dict: ILocalizedValue<T>, language: string) => dict[language] ?? dict[language.split('_')[0]] ?? dict.en ?? null

/**
 * Get the localized value of the dictionary provided
 *
 * @param dict Dictionary
 * @return String or null if invalid dictionary
 */
export function useLocalizedValue<T>(dict: Ref<ILocalizedValue<T | undefined> | undefined | null>) {
	/**
	 * Language of the current user
	 */
	const language = getLanguage()

	return computed(() => !dict?.value ? null : getLocalizedValue<T>(dict.value as ILocalizedValue<T>, language))
}
