/**
 *
 */
import * as AppConfig from './appconfig'
import * as Comments from './comments'
import Loader from './loader'
import { loadState } from '@nextcloud/initial-state'
import Collaboration from './collaboration'
import Toast from './toast'
import * as WhatsNew from './whatsnew'

/** @namespace OCP */
export default {
	AppConfig,
	Collaboration,
	Comments,
	InitialState: {
		/**
		 * @deprecated 18.0.0 add https://www.npmjs.com/package/@nextcloud/initial-state to your app
		 */
		loadState
	},
	Loader,
	Toast,
	WhatsNew
}
