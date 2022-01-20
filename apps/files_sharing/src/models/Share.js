/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Gary Kim <gary@garykim.dev>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

export default class Share {

	_share

	/**
	 * Create the share object
	 *
	 * @param {object} ocsData ocs request response
	 */
	constructor(ocsData) {
		if (ocsData.ocs && ocsData.ocs.data && ocsData.ocs.data[0]) {
			ocsData = ocsData.ocs.data[0]
		}

		// convert int into boolean
		ocsData.hide_download = !!ocsData.hide_download
		ocsData.mail_send = !!ocsData.mail_send

		// store state
		this._share = ocsData
	}

	/**
	 * Get the share state
	 * ! used for reactivity purpose
	 * Do not remove. It allow vuejs to
	 * inject its watchers into the #share
	 * state and make the whole class reactive
	 *
	 * @return {object} the share raw state
	 * @readonly
	 * @memberof Sidebar
	 */
	get state() {
		return this._share
	}

	/**
	 * get the share id
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get id() {
		return this._share.id
	}

	/**
	 * Get the share type
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get type() {
		return this._share.share_type
	}

	/**
	 * Get the share permissions
	 * See OC.PERMISSION_* variables
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get permissions() {
		return this._share.permissions
	}

	/**
	 * Set the share permissions
	 * See OC.PERMISSION_* variables
	 *
	 * @param {number} permissions valid permission, See OC.PERMISSION_* variables
	 * @memberof Share
	 */
	set permissions(permissions) {
		this._share.permissions = permissions
	}

	// SHARE OWNER --------------------------------------------------
	/**
	 * Get the share owner uid
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get owner() {
		return this._share.uid_owner
	}

	/**
	 * Get the share owner's display name
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get ownerDisplayName() {
		return this._share.displayname_owner
	}

	// SHARED WITH --------------------------------------------------
	/**
	 * Get the share with entity uid
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWith() {
		return this._share.share_with
	}

	/**
	 * Get the share with entity display name
	 * fallback to its uid if none
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWithDisplayName() {
		return this._share.share_with_displayname
			|| this._share.share_with
	}

	/**
	 * Unique display name in case of multiple
	 * duplicates results with the same name.
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWithDisplayNameUnique() {
		return this._share.share_with_displayname_unique
			|| this._share.share_with
	}

	/**
	 * Get the share with entity link
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWithLink() {
		return this._share.share_with_link
	}

	/**
	 * Get the share with avatar if any
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWithAvatar() {
		return this._share.share_with_avatar
	}

	// SHARED FILE OR FOLDER OWNER ----------------------------------
	/**
	 * Get the shared item owner uid
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get uidFileOwner() {
		return this._share.uid_file_owner
	}

	/**
	 * Get the shared item display name
	 * fallback to its uid if none
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get displaynameFileOwner() {
		return this._share.displayname_file_owner
			|| this._share.uid_file_owner
	}

	// TIME DATA ----------------------------------------------------
	/**
	 * Get the share creation timestamp
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get createdTime() {
		return this._share.stime
	}

	/**
	 * Get the expiration date as a string format
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get expireDate() {
		return this._share.expiration
	}

	/**
	 * Set the expiration date as a string format
	 * e.g. YYYY-MM-DD
	 *
	 * @param {string} date the share expiration date
	 * @memberof Share
	 */
	set expireDate(date) {
		this._share.expiration = date
	}

	// EXTRA DATA ---------------------------------------------------
	/**
	 * Get the public share token
	 *
	 * @return {string} the token
	 * @readonly
	 * @memberof Share
	 */
	get token() {
		return this._share.token
	}

	/**
	 * Get the share note if any
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get note() {
		return this._share.note
	}

	/**
	 * Set the share note if any
	 *
	 * @param {string} note the note
	 * @memberof Share
	 */
	set note(note) {
		this._share.note = note
	}

	/**
	 * Get the share label if any
	 * Should only exist on link shares
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get label() {
		return this._share.label
	}

	/**
	 * Set the share label if any
	 * Should only be set on link shares
	 *
	 * @param {string} label the label
	 * @memberof Share
	 */
	set label(label) {
		this._share.label = label
	}

	/**
	 * Have a mail been sent
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get mailSend() {
		return this._share.mail_send === true
	}

	/**
	 * Hide the download button on public page
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hideDownload() {
		return this._share.hide_download === true
	}

	/**
	 * Hide the download button on public page
	 *
	 * @param {boolean} state hide the button ?
	 * @memberof Share
	 */
	set hideDownload(state) {
		this._share.hide_download = state === true
	}

	/**
	 * Password protection of the share
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get password() {
		return this._share.password
	}

	/**
	 * Password protection of the share
	 *
	 * @param {string} password the share password
	 * @memberof Share
	 */
	set password(password) {
		this._share.password = password
	}

	/**
	 * Password protection by Talk of the share
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get sendPasswordByTalk() {
		return this._share.send_password_by_talk
	}

	/**
	 * Password protection by Talk of the share
	 *
	 * @param {boolean} sendPasswordByTalk whether to send the password by Talk
	 *        or not
	 * @memberof Share
	 */
	set sendPasswordByTalk(sendPasswordByTalk) {
		this._share.send_password_by_talk = sendPasswordByTalk
	}

	// SHARED ITEM DATA ---------------------------------------------
	/**
	 * Get the shared item absolute full path
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get path() {
		return this._share.path
	}

	/**
	 * Return the item type: file or folder
	 *
	 * @return {string} 'folder' or 'file'
	 * @readonly
	 * @memberof Share
	 */
	get itemType() {
		return this._share.item_type
	}

	/**
	 * Get the shared item mimetype
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get mimetype() {
		return this._share.mimetype
	}

	/**
	 * Get the shared item id
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get fileSource() {
		return this._share.file_source
	}

	/**
	 * Get the target path on the receiving end
	 * e.g the file /xxx/aaa will be shared in
	 * the receiving root as /aaa, the fileTarget is /aaa
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get fileTarget() {
		return this._share.file_target
	}

	/**
	 * Get the parent folder id if any
	 *
	 * @return {number}
	 * @readonly
	 * @memberof Share
	 */
	get fileParent() {
		return this._share.file_parent
	}

	// PERMISSIONS Shortcuts

	/**
	 * Does this share have READ permissions
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hasReadPermission() {
		return !!((this.permissions & OC.PERMISSION_READ))
	}

	/**
	 * Does this share have CREATE permissions
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hasCreatePermission() {
		return !!((this.permissions & OC.PERMISSION_CREATE))
	}

	/**
	 * Does this share have DELETE permissions
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hasDeletePermission() {
		return !!((this.permissions & OC.PERMISSION_DELETE))
	}

	/**
	 * Does this share have UPDATE permissions
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hasUpdatePermission() {
		return !!((this.permissions & OC.PERMISSION_UPDATE))
	}

	/**
	 * Does this share have SHARE permissions
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hasSharePermission() {
		return !!((this.permissions & OC.PERMISSION_SHARE))
	}

	// PERMISSIONS Shortcuts for the CURRENT USER
	// ! the permissions above are the share settings,
	// ! meaning the permissions for the recipient
	/**
	 * Can the current user EDIT this share ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get canEdit() {
		return this._share.can_edit === true
	}

	/**
	 * Can the current user DELETE this share ?
	 *
	 * @return {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get canDelete() {
		return this._share.can_delete === true
	}

	/**
	 * Top level accessible shared folder fileid for the current user
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get viaFileid() {
		return this._share.via_fileid
	}

	/**
	 * Top level accessible shared folder path for the current user
	 *
	 * @return {string}
	 * @readonly
	 * @memberof Share
	 */
	get viaPath() {
		return this._share.via_path
	}

	// TODO: SORT THOSE PROPERTIES

	get parent() {
		return this._share.parent
	}

	get storageId() {
		return this._share.storage_id
	}

	get storage() {
		return this._share.storage
	}

	get itemSource() {
		return this._share.item_source
	}

	get status() {
		return this._share.status
	}

}
