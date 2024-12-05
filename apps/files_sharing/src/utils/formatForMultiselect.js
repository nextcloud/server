import { ShareType } from '@nextcloud/sharing'
import { t } from '@nextcloud/l10n'

/**
 * Get the icon based on the share type
 *
 * @param {number} type the share type
 * @return {string} the icon class
 */
const shareTypeToIcon = (type) => {
	switch (type) {
	case ShareType.Guest:
		// default is a user, other icons are here to differentiate
		// themselves from it, so let's not display the user icon
		// case ShareType.Remote:
		// case ShareType.User:
		return {
			icon: 'icon-user',
			iconTitle: t('files_sharing', 'Guest'),
		}
	case ShareType.RemoteGroup:
	case ShareType.Group:
		return {
			icon: 'icon-group',
			iconTitle: t('files_sharing', 'Group'),
		}
	case ShareType.Email:
		return {
			icon: 'icon-mail',
			iconTitle: t('files_sharing', 'Email'),
		}
	case ShareType.Team:
		return {
			icon: 'icon-teams',
			iconTitle: t('files_sharing', 'Team'),
		}
	case ShareType.Room:
		return {
			icon: 'icon-room',
			iconTitle: t('files_sharing', 'Talk conversation'),
		}
	case ShareType.Deck:
		return {
			icon: 'icon-deck',
			iconTitle: t('files_sharing', 'Deck board'),
		}
	case ShareType.Sciencemesh:
		return {
			icon: 'icon-sciencemesh',
			iconTitle: t('files_sharing', 'ScienceMesh'),
		}
	default:
		return {}
	}
}

/**
 * Format shares for the multiselect options
 *
 * @param {object} result select entry item
 * @param {boolean} shouldAlwaysShowUnique always show unique names
 * @return {object}
 */
export default (result, shouldAlwaysShowUnique = false) => {
	let subname
	if (result.value.shareType === ShareType.User && shouldAlwaysShowUnique) {
		subname = result.shareWithDisplayNameUnique ?? ''
	} else if ((result.value.shareType === ShareType.Remote
			|| result.value.shareType === ShareType.RemoteGroup
	) && result.value.server) {
		subname = t('files_sharing', 'on {server}', { server: result.value.server })
	} else if (result.value.shareType === ShareType.Email) {
		subname = result.value.shareWith
	} else {
		subname = result.shareWithDescription ?? ''
	}

	return {
		shareWith: result.value.shareWith,
		shareType: result.value.shareType,
		user: result.uuid || result.value.shareWith,
		isNoUser: result.value.shareType !== ShareType.User,
		displayName: result.name || result.label,
		subname,
		shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
		...shareTypeToIcon(result.value.shareType),
	}
}
