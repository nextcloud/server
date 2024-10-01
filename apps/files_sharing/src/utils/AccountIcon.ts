/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'

const isDarkMode = window?.matchMedia?.('(prefers-color-scheme: dark)')?.matches === true
	|| document.querySelector('[data-themes*=dark]') !== null

export const generateAvatarSvg = (userId: string, isGuest = false) => {
	const url = isDarkMode ? '/avatar/{userId}/32/dark' : '/avatar/{userId}/32'
	const avatarUrl = generateUrl(isGuest ? url : url + '?guestFallback=true', { userId })
	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg" class="sharing-status__avatar">
		<image href="${avatarUrl}" height="32" width="32" />
	</svg>`
}
