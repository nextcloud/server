/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCanonicalLocale, getLanguage } from '@nextcloud/l10n'

export const naturalCollator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{
		numeric: true,
		usage: 'sort',
	},
)
