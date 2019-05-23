/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

export default class Share {

	#share;

	/**
	 * Create the share object
	 */
	constructor(ocsData) {
		if (ocsData.ocs && ocsData.ocs.data && ocsData.ocs.data[0]) {
			ocsData = ocsData.ocs.data[0]
		}
		this.#share = ocsData
	}

	/**
	 * get the share id
	 * 
	 * @returns {int}
	 * @readonly
	 * @memberof Share
	 */
	get id() {
		return this.#share.id
	}

	/**
	 * Get the share type
	 *
	 * @returns {}
	 * @readonly
	 * @memberof Share
	 */
	get type() {
		return this.#share.share_type
	}

	/**
	 * Get the share permissions
	 * See OC.PERMISSION_* variables
	 * 
	 * @returns {int}
	 * @readonly
	 * @memberof Share
	 */
	get permissions() {
		return this.#share.permissions
	}

	// SHARE OWNER --------------------------------------------------
	/**
	 * Get the share owner uid
	 *
	 * @returns {string}
	 * @readonly
	 * @memberof Share
	 */
	get owner() {
		return this.#share.uid_owner
	}

	/**
	 * Get the share owner's display name
	 *
	 * @returns {string}
	 * @readonly
	 * @memberof Share
	 */
	get ownerDisplayName() {
		return this.#share.displayname_owner
	}

	// SHARED WITH --------------------------------------------------
	/**
	 * Get the share with entity uid
	 *
	 * @returns {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWith() {
		return this.#share.share_with
	}

	/**
	 * Get the share with entity display name
	 * fallback to its uid if none
	 *
	 * @returns {string}
	 * @readonly
	 * @memberof Share
	 */
	get shareWithDisplayName() {
		return this.#share.share_with_displayname
			|| this.#share.share_with
	}

	// SHARED FILE OR FOLDER OWNER ----------------------------------
	/**
	 * Get the shared item owner uid
	 *
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get uidFileOwner() {
		return this.#share.uid_file_owner
	}

	/**
	 * Get the shared item display name
	 * fallback to its uid if none
	 *
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get displaynameFileOwner() {
		return this.#share.displayname_file_owner
			|| this.#share.uid_file_owner
	}

	// TIME DATA ----------------------------------------------------
	/**
	 * Get the share creation timestamp
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Share
	 */
	get createdTime() {
		return this.#share.stime
	}

	/**
	 * Get the expiration date as a string format
	 * 
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get expiration() {
		return this.#share.expiration
	}

	// EXTRA DATA ---------------------------------------------------
	/**
	 * Get the public share token
	 * 
	 * @returns {String} the token
	 * @readonly
	 * @memberof Share
	 */
	get token() {
		return this.#share.token
	}

	/**
	 * Get the share note if any
	 * 
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get note() {
		return this.#share.note
	}

	/**
	 * Have a mail been sent
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get mailSend() {
		return this.#share.mail_send === 1
	}

	/**
	 * Hide download on public page
	 *
	 * @returns {boolean}
	 * @readonly
	 * @memberof Share
	 */
	get hideDownload() {
		return this.#share.hide_download === 1
	}

	// SHARED ITEM DATA ---------------------------------------------
	/**
	 * Get the shared item absolute full path
	 * 
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get path() {
		return this.#share.path
	}

	/**
	 * Return the item type: file or folder
	 *
	 * @returns {String} 'folder' or 'file'
	 * @readonly
	 * @memberof Share
	 */
	get itemType() {
		return this.#share.item_type
	}

	/**
	 * Get the shared item mimetype
	 *
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get mimetype() {
		return this.#share.mimetype
	}

	/**
	 * Get the shared item id
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Share
	 */
	get fileSource() {
		return this.#share.file_source
	}

	/**
	 * Get the target path on the receiving end
	 * e.g the file /xxx/aaa will be shared in
	 * the receiving root as /aaa, the fileTarget is /aaa
	 *
	 * @returns {String}
	 * @readonly
	 * @memberof Share
	 */
	get fileTarget() {
		return this.#share.file_target
	}

	/**
	 * Get the parent folder id if any
	 *
	 * @returns {int}
	 * @readonly
	 * @memberof Share
	 */
	get fileParent() {
		return this.#share.file_parent
	}





	// TODO: SORT THOSE PROPERTIES
	get label() {
		return this.#share.label
	}

	get parent() {
		return this.#share.parent
	}

	get storage_id() {
		return this.#share.storage_id
	}

	get storage() {
		return this.#share.storage
	}

	get item_source() {
		return this.#share.item_source
	}

}