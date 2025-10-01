/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import Accessibility from './accessibility.js'
import * as AppConfig from './appconfig.js'
import Collaboration from './collaboration.js'
import * as Comments from './comments.js'
import Loader from './loader.js'
import Toast from './toast.js'
import * as WhatsNew from './whatsnew.js'

/** @namespace OCP */
export default {
	Accessibility,
	AppConfig,
	Collaboration,
	Comments,
	InitialState: {
		/**
		 * @deprecated 18.0.0 add https://www.npmjs.com/package/@nextcloud/initial-state to your app
		 */
		loadState,
	},
	Loader,
	/**
	 * @deprecated 19.0.0 use the `@nextcloud/dialogs` package instead
	 */
	Toast,
	WhatsNew,
}
