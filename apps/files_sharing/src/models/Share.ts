/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ShareType } from '@nextcloud/sharing'
import type { ShareAttribute } from '../sharing'
import { isFileRequest } from '../services/SharingService'

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

		if (ocsData.attributes && typeof ocsData.attributes === 'string') {
			try {
				ocsData.attributes = JSON.parse(ocsData.attributes)
			} catch (e) {
				console.warn('Could not parse share attributes returned by server', ocsData.attributes)
			}
		}
		ocsData.attributes = ocsData.attributes ?? []

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
	 */
	get state() {
		return this._share
	}

	/**
	 * get the share id
	 */
	get id(): number {
		return this._share.id
	}

	/**
	 * Get the share type
	 */
	get type(): ShareType {
		return this._share.share_type
	}

	/**
	 * Get the share permissions
	 * See window.OC.PERMISSION_* variables
	 */
	get permissions(): number {
		return this._share.permissions
	}

	/**
	 * Get the share attributes
	 */
	get attributes(): Array<ShareAttribute> {
		return this._share.attributes
	}

	/**
	 * Set the share permissions
	 * See window.OC.PERMISSION_* variables
	 */
	set permissions(permissions: number) {
		this._share.permissions = permissions
	}

	// SHARE OWNER --------------------------------------------------
	/**
	 * Get the share owner uid
	 */
	get owner(): string {
		return this._share.uid_owner
	}

	/**
	 * Get the share owner's display name
	 */
	get ownerDisplayName(): string {
		return this._share.displayname_owner
	}

	// SHARED WITH --------------------------------------------------
	/**
	 * Get the share with entity uid
	 */
	get shareWith(): string {
		return this._share.share_with
	}

	/**
	 * Get the share with entity display name
	 * fallback to its uid if none
	 */
	get shareWithDisplayName(): string {
		return this._share.share_with_displayname
			|| this._share.share_with
	}

	/**
	 * Unique display name in case of multiple
	 * duplicates results with the same name.
	 */
	get shareWithDisplayNameUnique(): string {
		return this._share.share_with_displayname_unique
			|| this._share.share_with
	}

	/**
	 * Get the share with entity link
	 */
	get shareWithLink(): string {
		return this._share.share_with_link
	}

	/**
	 * Get the share with avatar if any
	 */
	get shareWithAvatar(): string {
		return this._share.share_with_avatar
	}

	// SHARED FILE OR FOLDER OWNER ----------------------------------
	/**
	 * Get the shared item owner uid
	 */
	get uidFileOwner(): string {
		return this._share.uid_file_owner
	}

	/**
	 * Get the shared item display name
	 * fallback to its uid if none
	 */
	get displaynameFileOwner(): string {
		return this._share.displayname_file_owner
			|| this._share.uid_file_owner
	}

	// TIME DATA ----------------------------------------------------
	/**
	 * Get the share creation timestamp
	 */
	get createdTime(): number {
		return this._share.stime
	}

	/**
	 * Get the expiration date
	 * @return {string} date with YYYY-MM-DD format
	 */
	get expireDate(): string {
		return this._share.expiration
	}

	/**
	 * Set the expiration date
	 * @param {string} date the share expiration date with YYYY-MM-DD format
	 */
	set expireDate(date: string) {
		this._share.expiration = date
	}

	// EXTRA DATA ---------------------------------------------------
	/**
	 * Get the public share token
	 */
	get token(): string {
		return this._share.token
	}

	/**
	 * Set the public share token
	 */
	set token(token: string) {
		this._share.token = token
	}

	/**
	 * Get the share note if any
	 */
	get note(): string {
		return this._share.note
	}

	/**
	 * Set the share note if any
	 */
	set note(note: string) {
		this._share.note = note
	}

	/**
	 * Get the share label if any
	 * Should only exist on link shares
	 */
	get label(): string {
		return this._share.label ?? ''
	}

	/**
	 * Set the share label if any
	 * Should only be set on link shares
	 */
	set label(label: string) {
		this._share.label = label
	}

	/**
	 * Have a mail been sent
	 */
	get mailSend(): boolean {
		return this._share.mail_send === true
	}

	/**
	 * Hide the download button on public page
	 */
	get hideDownload(): boolean {
		return this._share.hide_download === true
	}

	/**
	 * Hide the download button on public page
	 */
	set hideDownload(state: boolean) {
		this._share.hide_download = state === true
	}

	/**
	 * Password protection of the share
	 */
	get password():string {
		return this._share.password
	}

	/**
	 * Password protection of the share
	 */
	set password(password: string) {
		this._share.password = password
	}

	/**
	 * Password expiration time
	 * @return {string} date with YYYY-MM-DD format
	 */
	get passwordExpirationTime(): string {
		return this._share.password_expiration_time
	}

	/**
	 * Password expiration time
	 * @param {string} passwordExpirationTime date with YYYY-MM-DD format
	 */
	set passwordExpirationTime(passwordExpirationTime: string) {
		this._share.password_expiration_time = passwordExpirationTime
	}

	/**
	 * Password protection by Talk of the share
	 */
	get sendPasswordByTalk(): boolean {
		return this._share.send_password_by_talk
	}

	/**
	 * Password protection by Talk of the share
	 *
	 * @param {boolean} sendPasswordByTalk whether to send the password by Talk or not
	 */
	set sendPasswordByTalk(sendPasswordByTalk: boolean) {
		this._share.send_password_by_talk = sendPasswordByTalk
	}

	// SHARED ITEM DATA ---------------------------------------------
	/**
	 * Get the shared item absolute full path
	 */
	get path(): string {
		return this._share.path
	}

	/**
	 * Return the item type: file or folder
	 * @return {string} 'folder' | 'file'
	 */
	get itemType(): string {
		return this._share.item_type
	}

	/**
	 * Get the shared item mimetype
	 */
	get mimetype(): string {
		return this._share.mimetype
	}

	/**
	 * Get the shared item id
	 */
	get fileSource(): number {
		return this._share.file_source
	}

	/**
	 * Get the target path on the receiving end
	 * e.g the file /xxx/aaa will be shared in
	 * the receiving root as /aaa, the fileTarget is /aaa
	 */
	get fileTarget(): string {
		return this._share.file_target
	}

	/**
	 * Get the parent folder id if any
	 */
	get fileParent(): number {
		return this._share.file_parent
	}

	// PERMISSIONS Shortcuts

	/**
	 * Does this share have READ permissions
	 */
	get hasReadPermission(): boolean {
		return !!((this.permissions & window.OC.PERMISSION_READ))
	}

	/**
	 * Does this share have CREATE permissions
	 */
	get hasCreatePermission(): boolean {
		return !!((this.permissions & window.OC.PERMISSION_CREATE))
	}

	/**
	 * Does this share have DELETE permissions
	 */
	get hasDeletePermission(): boolean {
		return !!((this.permissions & window.OC.PERMISSION_DELETE))
	}

	/**
	 * Does this share have UPDATE permissions
	 */
	get hasUpdatePermission(): boolean {
		return !!((this.permissions & window.OC.PERMISSION_UPDATE))
	}

	/**
	 * Does this share have SHARE permissions
	 */
	get hasSharePermission(): boolean {
		return !!((this.permissions & window.OC.PERMISSION_SHARE))
	}

	/**
	 * Does this share have download permissions
	 */
	get hasDownloadPermission(): boolean {
		const hasDisabledDownload = (attribute) => {
			return attribute.scope === 'permissions' && attribute.key === 'download' && attribute.value === false
		}
		return this.attributes.some(hasDisabledDownload)
	}

	/**
	 * Is this mail share a file request ?
	 */
	get isFileRequest(): boolean {
		return isFileRequest(JSON.stringify(this.attributes))
	}

	set hasDownloadPermission(enabled) {
		this.setAttribute('permissions', 'download', !!enabled)
	}

	setAttribute(scope, key, value) {
		const attrUpdate = {
			scope,
			key,
			value,
		}

		// try and replace existing
		for (const i in this._share.attributes) {
			const attr = this._share.attributes[i]
			if (attr.scope === attrUpdate.scope && attr.key === attrUpdate.key) {
				this._share.attributes.splice(i, 1, attrUpdate)
				return
			}
		}

		this._share.attributes.push(attrUpdate)
	}

	// PERMISSIONS Shortcuts for the CURRENT USER
	// ! the permissions above are the share settings,
	// ! meaning the permissions for the recipient
	/**
	 * Can the current user EDIT this share ?
	 */
	get canEdit(): boolean {
		return this._share.can_edit === true
	}

	/**
	 * Can the current user DELETE this share ?
	 */
	get canDelete(): boolean {
		return this._share.can_delete === true
	}

	/**
	 * Top level accessible shared folder fileid for the current user
	 */
	get viaFileid(): string {
		return this._share.via_fileid
	}

	/**
	 * Top level accessible shared folder path for the current user
	 */
	get viaPath(): string {
		return this._share.via_path
	}

	// TODO: SORT THOSE PROPERTIES

	get parent() {
		return this._share.parent
	}

	get storageId(): string {
		return this._share.storage_id
	}

	get storage(): number {
		return this._share.storage
	}

	get itemSource(): number {
		return this._share.item_source
	}

	get status() {
		return this._share.status
	}

}
