/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { t } from '@nextcloud/l10n'

const productName = window.OC.theme.productName

export const textExistingFilesNotEncrypted = t('settings', 'For performance reasons, when you enable encryption on a {productName} server only new and changed files are encrypted.', { productName })
