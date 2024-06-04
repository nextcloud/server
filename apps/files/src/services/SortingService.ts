/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { getCanonicalLocale, getLanguage } from '@nextcloud/l10n'

type IdentifierFn<T> = (v: T) => unknown
type SortingOrder = 'asc'|'desc'

/**
 * Helper to create string representation
 * @param value Value to stringify
 */
function stringify(value: unknown) {
	// The default representation of Date is not sortable because of the weekday names in front of it
	if (value instanceof Date) {
		return value.toISOString()
	}
	return String(value)
}

/**
 * Natural order a collection
 * You can define identifiers as callback functions, that get the element and return the value to sort.
 *
 * @param collection The collection to order
 * @param identifiers An array of identifiers to use, by default the identity of the element is used
 * @param orders Array of orders, by default all identifiers are sorted ascening
 */
export function orderBy<T>(collection: readonly T[], identifiers?: IdentifierFn<T>[], orders?: SortingOrder[]): T[] {
	// If not identifiers are set we use the identity of the value
	identifiers = identifiers ?? [(value) => value]
	// By default sort the collection ascending
	orders = orders ?? []
	const sorting = identifiers.map((_, index) => (orders[index] ?? 'asc') === 'asc' ? 1 : -1)

	const collator = Intl.Collator(
		[getLanguage(), getCanonicalLocale()],
		{
			// handle 10 as ten and not as one-zero
			numeric: true,
			usage: 'sort',
		},
	)

	return [...collection].sort((a, b) => {
		for (const [index, identifier] of identifiers.entries()) {
			// Get the local compare of stringified value a and b
			const value = collator.compare(stringify(identifier(a)), stringify(identifier(b)))
			// If they do not match return the order
			if (value !== 0) {
				return value * sorting[index]
			}
			// If they match we need to continue with the next identifier
		}
		// If all are equal we need to return equality
		return 0
	})
}
