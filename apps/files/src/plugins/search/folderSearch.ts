/**
 * @copyright Copyright (c) 2024 Fon E. Noel NFEBE <opensource@nfebe.com>
 *
 * @author Fon E. Noel NFEBE <opensource@nfebe.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

import type { Node } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { getFilePickerBuilder } from '@nextcloud/dialogs';
import { imagePath } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import logger from '../../logger'
import '@nextcloud/dialogs/style.css'

/**
 * Initialize the unified search plugin.
 */
function init() {
	const OCA = window.OCA
	if (!OCA.UnifiedSearch) {
		return;
	}

	logger.info('Initializing unified search plugin: folder search from files app');
	OCA.UnifiedSearch.registerFilterAction({
		id: 'files',
		appId: 'files',
		label: t('files', 'In folder'),
		icon: imagePath('files', 'app.svg'),
		callback: () => {
			const filepicker = getFilePickerBuilder('Pick plain text files')
				.addMimeTypeFilter('httpd/unix-directory')
				.allowDirectories(true)
				.addButton({
					label: 'Pick',
					callback: (nodes: Node[]) => {
						logger.info('Folder picked', { folder: nodes[0] })
						const folder = nodes[0]
						emit('nextcloud:unified-search:add-filter', {
							id: 'files',
							payload: folder,
							filterUpdateText: t('files', 'Search in folder: {folder}', { folder: folder.basename }),
							filterParams: { path: folder.path },
						})
					},
				})
				.build()
			filepicker.pick()
		},
	})
}

document.addEventListener('DOMContentLoaded', init);
