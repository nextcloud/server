/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import type { ILocalizedValue } from '../constants/AppDiscoverTypes'

import { getLanguage } from '@nextcloud/l10n'
import { computed, type Ref } from 'vue'

/**
 * Helper to get the localized value for the current users language
 * @param dict The dictionary to get the value from
 * @param language The language to use
 */
const getLocalizedValue = <T, >(dict: ILocalizedValue<T>, language: string) => dict[language] ?? dict[language.split('_')[0]] ?? dict.en ?? null

/**
 * Get the localized value of the dictionary provided
 * @param dict Dictionary
 * @return String or null if invalid dictionary
 */
export const useLocalizedValue = <T, >(dict: Ref<ILocalizedValue<T|undefined>|undefined|null>) => {
	/**
	 * Language of the current user
	 */
	const language = getLanguage()

	return computed(() => !dict?.value ? null : getLocalizedValue<T>(dict.value as ILocalizedValue<T>, language))
}
