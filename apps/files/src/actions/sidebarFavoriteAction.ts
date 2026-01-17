/*
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import starOutlineSvg from '@mdi/svg/svg/star-outline.svg?raw'
import starSvg from '@mdi/svg/svg/star.svg?raw'
import { registerSidebarAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { favoriteNode } from './favoriteAction.ts'

/**
 * Register the favorite/unfavorite action in the sidebar
 */
export function registerSidebarFavoriteAction() {
	registerSidebarAction({
		id: 'files-favorite',
		order: 0,

		enabled({ node }) {
			return node.isDavResource && node.root.startsWith('/files/')
		},

		displayName({ node }) {
			if (node.attributes.favorite) {
				return t('files', 'Unfavorite')
			}
			return t('files', 'Favorite')
		},

		iconSvgInline({ node }) {
			if (node.attributes.favorite) {
				return starSvg
			}
			return starOutlineSvg
		},

		onClick({ node, view }) {
			favoriteNode(node, view, !node.attributes.favorite)
		},
	})
}
