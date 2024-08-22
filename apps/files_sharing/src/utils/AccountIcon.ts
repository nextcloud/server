/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { generateUrl } from '@nextcloud/router'

const isDarkMode = () => {
	return window?.matchMedia?.('(prefers-color-scheme: dark)')?.matches === true
		|| document.querySelector('[data-themes*=dark]') !== null
}

export const generateAvatarSvg = (userId: string, isGuest = false) => {
	// normal avatar url: /avatar/{userId}/32?guestFallback=true
	// dark avatar url: /avatar/{userId}/32/dark?guestFallback=true
	// guest avatar url: /avatar/guest/{userId}/32
	// guest dark avatar url: /avatar/guest/{userId}/32/dark
	const basePath = isGuest ? `/avatar/guest/${userId}` : `/avatar/${userId}`
	const darkModePath = isDarkMode() ? '/dark' : ''
	const guestFallback = isGuest ? '' : '?guestFallback=true'

	const url = `${basePath}/32${darkModePath}${guestFallback}`
	const avatarUrl = generateUrl(url, { userId })

	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg" class="sharing-status__avatar">
		<image href="${avatarUrl}" height="32" width="32" />
	</svg>`
}
