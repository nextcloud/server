"use strict";
(globalThis["webpackChunknextcloud"] = globalThis["webpackChunknextcloud"] || []).push([["apps_files_sharing_src_views_SharingTab_vue"],{

/***/ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js":
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/lib/SharePermissionsToolBox.js ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ATOMIC_PERMISSIONS: () => (/* binding */ ATOMIC_PERMISSIONS),
/* harmony export */   BUNDLED_PERMISSIONS: () => (/* binding */ BUNDLED_PERMISSIONS),
/* harmony export */   addPermissions: () => (/* binding */ addPermissions),
/* harmony export */   canTogglePermissions: () => (/* binding */ canTogglePermissions),
/* harmony export */   hasPermissions: () => (/* binding */ hasPermissions),
/* harmony export */   permissionsSetIsValid: () => (/* binding */ permissionsSetIsValid),
/* harmony export */   subtractPermissions: () => (/* binding */ subtractPermissions),
/* harmony export */   togglePermissions: () => (/* binding */ togglePermissions)
/* harmony export */ });
/**
 * @copyright 2022 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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

const ATOMIC_PERMISSIONS = {
  NONE: 0,
  READ: 1,
  UPDATE: 2,
  CREATE: 4,
  DELETE: 8,
  SHARE: 16
};
const BUNDLED_PERMISSIONS = {
  READ_ONLY: ATOMIC_PERMISSIONS.READ,
  UPLOAD_AND_UPDATE: ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.DELETE,
  FILE_DROP: ATOMIC_PERMISSIONS.CREATE,
  ALL: ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.CREATE | ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.DELETE | ATOMIC_PERMISSIONS.SHARE,
  ALL_FILE: ATOMIC_PERMISSIONS.UPDATE | ATOMIC_PERMISSIONS.READ | ATOMIC_PERMISSIONS.SHARE
};

/**
 * Return whether a given permissions set contains some permissions.
 *
 * @param {number} initialPermissionSet - the permissions set.
 * @param {number} permissionsToCheck - the permissions to check.
 * @return {boolean}
 */
function hasPermissions(initialPermissionSet, permissionsToCheck) {
  return initialPermissionSet !== ATOMIC_PERMISSIONS.NONE && (initialPermissionSet & permissionsToCheck) === permissionsToCheck;
}

/**
 * Return whether a given permissions set is valid.
 *
 * @param {number} permissionsSet - the permissions set.
 *
 * @return {boolean}
 */
function permissionsSetIsValid(permissionsSet) {
  // Must have at least READ or CREATE permission.
  if (!hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.READ) && !hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.CREATE)) {
    return false;
  }

  // Must have READ permission if have UPDATE or DELETE.
  if (!hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.READ) && (hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.UPDATE) || hasPermissions(permissionsSet, ATOMIC_PERMISSIONS.DELETE))) {
    return false;
  }
  return true;
}

/**
 * Add some permissions to an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the initial permissions.
 * @param {number} permissionsToAdd - the permissions to add.
 *
 * @return {number}
 */
function addPermissions(initialPermissionSet, permissionsToAdd) {
  return initialPermissionSet | permissionsToAdd;
}

/**
 * Remove some permissions from an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the initial permissions.
 * @param {number} permissionsToSubtract - the permissions to remove.
 *
 * @return {number}
 */
function subtractPermissions(initialPermissionSet, permissionsToSubtract) {
  return initialPermissionSet & ~permissionsToSubtract;
}

/**
 * Toggle some permissions from  an initial set of permissions.
 *
 * @param {number} initialPermissionSet - the permissions set.
 * @param {number} permissionsToToggle - the permissions to toggle.
 *
 * @return {number}
 */
function togglePermissions(initialPermissionSet, permissionsToToggle) {
  if (hasPermissions(initialPermissionSet, permissionsToToggle)) {
    return subtractPermissions(initialPermissionSet, permissionsToToggle);
  } else {
    return addPermissions(initialPermissionSet, permissionsToToggle);
  }
}

/**
 * Return whether some given permissions can be toggled from a permission set.
 *
 * @param {number} permissionSet - the initial permissions set.
 * @param {number} permissionsToToggle - the permissions to toggle.
 *
 * @return {boolean}
 */
function canTogglePermissions(permissionSet, permissionsToToggle) {
  return permissionsSetIsValid(togglePermissions(permissionSet, permissionsToToggle));
}

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareDetails.js":
/*!*******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareDetails.js ***!
  \*******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  methods: {
    async openSharingDetails(shareRequestObject) {
      let share = {};
      // handle externalResults from OCA.Sharing.ShareSearch
      // TODO : Better name/interface for handler required
      // For example `externalAppCreateShareHook` with proper documentation
      if (shareRequestObject.handler) {
        if (this.suggestions) {
          shareRequestObject.suggestions = this.suggestions;
          shareRequestObject.fileInfo = this.fileInfo;
          shareRequestObject.query = this.query;
        }
        share = await shareRequestObject.handler(shareRequestObject);
        share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"](share);
      } else {
        share = this.mapShareRequestToShareObject(shareRequestObject);
      }
      const shareDetails = {
        fileInfo: this.fileInfo,
        share
      };
      this.$emit('open-sharing-details', shareDetails);
    },
    openShareDetailsForCustomSettings(share) {
      share.setCustomPermissions = true;
      this.openSharingDetails(share);
    },
    mapShareRequestToShareObject(shareRequestObject) {
      if (shareRequestObject.id) {
        return shareRequestObject;
      }
      const share = {
        attributes: [{
          enabled: true,
          key: 'download',
          scope: 'permissions'
        }],
        share_type: shareRequestObject.shareType,
        share_with: shareRequestObject.shareWith,
        is_no_user: shareRequestObject.isNoUser,
        user: shareRequestObject.shareWith,
        share_with_displayname: shareRequestObject.displayName,
        subtitle: shareRequestObject.subtitle,
        permissions: shareRequestObject.permissions,
        expiration: ''
      };
      return new _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"](share);
    }
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareRequests.js":
/*!********************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareRequests.js ***!
  \********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! url-search-params-polyfill */ "./node_modules/url-search-params-polyfill/index.js");
/* harmony import */ var url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(url_search_params_polyfill__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

// TODO: remove when ie not supported





const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  methods: {
    /**
     * Create a new share
     *
     * @param {object} data destructuring object
     * @param {string} data.path  path to the file/folder which should be shared
     * @param {number} data.shareType  0 = user; 1 = group; 3 = public link; 6 = federated cloud share
     * @param {string} data.shareWith  user/group id with which the file should be shared (optional for shareType > 1)
     * @param {boolean} [data.publicUpload]  allow public upload to a public shared folder
     * @param {string} [data.password]  password to protect public link Share with
     * @param {number} [data.permissions]  1 = read; 2 = update; 4 = create; 8 = delete; 16 = share; 31 = all (default: 31, for public shares: 1)
     * @param {boolean} [data.sendPasswordByTalk] send the password via a talk conversation
     * @param {string} [data.expireDate] expire the shareautomatically after
     * @param {string} [data.label] custom label
     * @param {string} [data.attributes] Share attributes encoded as json
     * @param data.note
     * @return {Share} the new share
     * @throws {Error}
     */
    async createShare({
      path,
      permissions,
      shareType,
      shareWith,
      publicUpload,
      password,
      sendPasswordByTalk,
      expireDate,
      label,
      note,
      attributes
    }) {
      try {
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].post(shareUrl, {
          path,
          permissions,
          shareType,
          shareWith,
          publicUpload,
          password,
          sendPasswordByTalk,
          expireDate,
          label,
          note,
          attributes
        });
        if (!request?.data?.ocs) {
          throw request;
        }
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_3__["default"](request.data.ocs.data);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:created', {
          share
        });
        return share;
      } catch (error) {
        console.error('Error while creating share', error);
        const errorMessage = error?.response?.data?.ocs?.meta?.message;
        OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error creating the share: {errorMessage}', {
          errorMessage
        }) : t('files_sharing', 'Error creating the share'), {
          type: 'error'
        });
        throw error;
      }
    },
    /**
     * Delete a share
     *
     * @param {number} id share id
     * @throws {Error}
     */
    async deleteShare(id) {
      try {
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].delete(shareUrl + `/${id}`);
        if (!request?.data?.ocs) {
          throw request;
        }
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:deleted', {
          id
        });
        return true;
      } catch (error) {
        console.error('Error while deleting share', error);
        const errorMessage = error?.response?.data?.ocs?.meta?.message;
        OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error deleting the share: {errorMessage}', {
          errorMessage
        }) : t('files_sharing', 'Error deleting the share'), {
          type: 'error'
        });
        throw error;
      }
    },
    /**
     * Update a share
     *
     * @param {number} id share id
     * @param {object} properties key-value object of the properties to update
     */
    async updateShare(id, properties) {
      try {
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].put(shareUrl + `/${id}`, properties);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_4__.emit)('files_sharing:share:updated', {
          id
        });
        if (!request?.data?.ocs) {
          throw request;
        } else {
          return request.data.ocs.data;
        }
      } catch (error) {
        console.error('Error while updating share', error);
        if (error.response.status !== 400) {
          const errorMessage = error?.response?.data?.ocs?.meta?.message;
          OC.Notification.showTemporary(errorMessage ? t('files_sharing', 'Error updating the share: {errorMessage}', {
            errorMessage
          }) : t('files_sharing', 'Error updating the share'), {
            type: 'error'
          });
        }
        const message = error.response.data.ocs.meta.message;
        throw new Error(message);
      }
    }
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/ShareTypes.js":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareTypes.js ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  data() {
    return {
      SHARE_TYPES: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type
    };
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/mixins/SharesMixin.js":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/SharesMixin.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _ShareRequests_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _ShareTypes_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Gary Kim <gary@garykim.dev>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Vincent Petry <vincent@nextcloud.com>
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



// eslint-disable-next-line import/no-unresolved, n/no-missing-import







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  mixins: [_ShareRequests_js__WEBPACK_IMPORTED_MODULE_5__["default"], _ShareTypes_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_4__["default"],
      default: null
    },
    isUnique: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_7__["default"](),
      // errors helpers
      errors: {},
      // component status toggles
      loading: false,
      saving: false,
      open: false,
      // concurrency management queue
      // we want one queue per share
      updateQueue: new p_queue__WEBPACK_IMPORTED_MODULE_2__["default"]({
        concurrency: 1
      }),
      /**
       * ! This allow vue to make the Share class state reactive
       * ! do not remove it ot you'll lose all reactivity here
       */
      reactiveState: this.share?.state
    };
  },
  computed: {
    /**
     * Does the current share have a note
     *
     * @return {boolean}
     */
    hasNote: {
      get() {
        return this.share.note !== '';
      },
      set(enabled) {
        this.share.note = enabled ? null // enabled but user did not changed the content yet
        : ''; // empty = no note = disabled
      }
    },

    dateTomorrow() {
      return new Date(new Date().setDate(new Date().getDate() + 1));
    },
    // Datepicker language
    lang() {
      const weekdaysShort = window.dayNamesShort ? window.dayNamesShort // provided by nextcloud
      : ['Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.'];
      const monthsShort = window.monthNamesShort ? window.monthNamesShort // provided by nextcloud
      : ['Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.'];
      const firstDayOfWeek = window.firstDay ? window.firstDay : 0;
      return {
        formatLocale: {
          firstDayOfWeek,
          monthsShort,
          weekdaysMin: weekdaysShort,
          weekdaysShort
        },
        monthFormat: 'MMM'
      };
    },
    isFolder() {
      return this.fileInfo.type === 'dir';
    },
    isPublicShare() {
      const shareType = this.share.shareType ?? this.share.type;
      return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType);
    },
    isShareOwner() {
      return this.share && this.share.owner === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid;
    },
    hasCustomPermissions() {
      const bundledPermissions = [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.ALL, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.READ_ONLY, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_8__.BUNDLED_PERMISSIONS.FILE_DROP];
      return !bundledPermissions.includes(this.share.permissions);
    }
  },
  methods: {
    /**
     * Check if a share is valid before
     * firing the request
     *
     * @param {Share} share the share to check
     * @return {boolean}
     */
    checkShare(share) {
      if (share.password) {
        if (typeof share.password !== 'string' || share.password.trim() === '') {
          return false;
        }
      }
      if (share.expirationDate) {
        const date = share.expirationDate;
        if (!date.isValid()) {
          return false;
        }
      }
      return true;
    },
    /**
     * @param {string} date a date with YYYY-MM-DD format
     * @return {Date} date
     */
    parseDateString(date) {
      if (!date) {
        return;
      }
      const regex = /([0-9]{4}-[0-9]{2}-[0-9]{2})/i;
      return new Date(date.match(regex)?.pop());
    },
    /**
     * @param {Date} date
     * @return {string} date a date with YYYY-MM-DD format
     */
    formatDateToString(date) {
      // Force utc time. Drop time information to be timezone-less
      const utcDate = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
      // Format to YYYY-MM-DD
      return utcDate.toISOString().split('T')[0];
    },
    /**
     * Save given value to expireDate and trigger queueUpdate
     *
     * @param {Date} date
     */
    onExpirationChange(date) {
      this.share.expireDate = this.formatDateToString(new Date(date));
    },
    /**
     * Uncheck expire date
     * We need this method because @update:checked
     * is ran simultaneously as @uncheck, so
     * so we cannot ensure data is up-to-date
     */
    onExpirationDisable() {
      this.share.expireDate = '';
    },
    /**
     * Note changed, let's save it to a different key
     *
     * @param {string} note the share note
     */
    onNoteChange(note) {
      this.$set(this.share, 'newNote', note.trim());
    },
    /**
     * When the note change, we trim, save and dispatch
     *
     */
    onNoteSubmit() {
      if (this.share.newNote) {
        this.share.note = this.share.newNote;
        this.$delete(this.share, 'newNote');
        this.queueUpdate('note');
      }
    },
    /**
     * Delete share button handler
     */
    async onDelete() {
      try {
        this.loading = true;
        this.open = false;
        await this.deleteShare(this.share.id);
        console.debug('Share deleted', this.share.id);
        const message = this.share.itemType === 'file' ? t('files_sharing', 'File "{path}" has been unshared', {
          path: this.share.path
        }) : t('files_sharing', 'Folder "{path}" has been unshared', {
          path: this.share.path
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(message);
        this.$emit('remove:share', this.share);
      } catch (error) {
        // re-open menu if error
        this.open = true;
      } finally {
        this.loading = false;
      }
    },
    /**
     * Send an update of the share to the queue
     *
     * @param {Array<string>} propertyNames the properties to sync
     */
    queueUpdate(...propertyNames) {
      if (propertyNames.length === 0) {
        // Nothing to update
        return;
      }
      if (this.share.id) {
        const properties = {};
        // force value to string because that is what our
        // share api controller accepts
        propertyNames.forEach(name => {
          if (typeof this.share[name] === 'object') {
            properties[name] = JSON.stringify(this.share[name]);
          } else {
            properties[name] = this.share[name].toString();
          }
        });
        this.updateQueue.add(async () => {
          this.saving = true;
          this.errors = {};
          try {
            const updatedShare = await this.updateShare(this.share.id, properties);
            if (propertyNames.indexOf('password') >= 0) {
              // reset password state after sync
              this.$delete(this.share, 'newPassword');

              // updates password expiration time after sync
              this.share.passwordExpirationTime = updatedShare.password_expiration_time;
            }

            // clear any previous errors
            this.$delete(this.errors, propertyNames[0]);
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(t('files_sharing', 'Share {propertyName} saved', {
              propertyName: propertyNames[0]
            }));
          } catch ({
            message
          }) {
            if (message && message !== '') {
              this.onSyncError(propertyNames[0], message);
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('files_sharing', message));
            }
          } finally {
            this.saving = false;
          }
        });
        return;
      }

      // This share does not exists on the server yet
      console.debug('Updated local share', this.share);
    },
    /**
     * Manage sync errors
     *
     * @param {string} property the errored property, e.g. 'password'
     * @param {string} message the error message
     */
    onSyncError(property, message) {
      // re-open menu if closed
      this.open = true;
      switch (property) {
        case 'password':
        case 'pending':
        case 'expireDate':
        case 'label':
        case 'note':
          {
            // show error
            this.$set(this.errors, property, message);
            let propertyEl = this.$refs[property];
            if (propertyEl) {
              if (propertyEl.$el) {
                propertyEl = propertyEl.$el;
              }
              // focus if there is a focusable action element
              const focusable = propertyEl.querySelector('.focusable');
              if (focusable) {
                focusable.focus();
              }
            }
            break;
          }
        case 'sendPasswordByTalk':
          {
            // show error
            this.$set(this.errors, property, message);

            // Restore previous state
            this.share.sendPasswordByTalk = !this.share.sendPasswordByTalk;
            break;
          }
      }
    },
    /**
     * Debounce queueUpdate to avoid requests spamming
     * more importantly for text data
     *
     * @param {string} property the property to sync
     */
    debounceQueueUpdate: debounce__WEBPACK_IMPORTED_MODULE_3___default()(function (property) {
      this.queueUpdate(property);
    }, 500)
  }
});

/***/ }),

/***/ "./apps/files_sharing/src/models/Share.js":
/*!************************************************!*\
  !*** ./apps/files_sharing/src/models/Share.js ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Share)
/* harmony export */ });
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return typeof key === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (typeof input !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (typeof res !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
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

class Share {
  /**
   * Create the share object
   *
   * @param {object} ocsData ocs request response
   */
  constructor(ocsData) {
    _defineProperty(this, "_share", void 0);
    if (ocsData.ocs && ocsData.ocs.data && ocsData.ocs.data[0]) {
      ocsData = ocsData.ocs.data[0];
    }

    // convert int into boolean
    ocsData.hide_download = !!ocsData.hide_download;
    ocsData.mail_send = !!ocsData.mail_send;
    if (ocsData.attributes) {
      try {
        ocsData.attributes = JSON.parse(ocsData.attributes);
      } catch (e) {
        console.warn('Could not parse share attributes returned by server: "' + ocsData.attributes + '"');
      }
    }
    ocsData.attributes = ocsData.attributes ?? [];

    // store state
    this._share = ocsData;
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
    return this._share;
  }

  /**
   * get the share id
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get id() {
    return this._share.id;
  }

  /**
   * Get the share type
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get type() {
    return this._share.share_type;
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
    return this._share.permissions;
  }

  /**
   * Get the share attributes
   *
   * @return {Array}
   * @readonly
   * @memberof Share
   */
  get attributes() {
    return this._share.attributes;
  }

  /**
   * Set the share permissions
   * See OC.PERMISSION_* variables
   *
   * @param {number} permissions valid permission, See OC.PERMISSION_* variables
   * @memberof Share
   */
  set permissions(permissions) {
    this._share.permissions = permissions;
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
    return this._share.uid_owner;
  }

  /**
   * Get the share owner's display name
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get ownerDisplayName() {
    return this._share.displayname_owner;
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
    return this._share.share_with;
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
    return this._share.share_with_displayname || this._share.share_with;
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
    return this._share.share_with_displayname_unique || this._share.share_with;
  }

  /**
   * Get the share with entity link
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithLink() {
    return this._share.share_with_link;
  }

  /**
   * Get the share with avatar if any
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get shareWithAvatar() {
    return this._share.share_with_avatar;
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
    return this._share.uid_file_owner;
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
    return this._share.displayname_file_owner || this._share.uid_file_owner;
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
    return this._share.stime;
  }

  /**
   * Get the expiration date
   *
   * @return {string} date with YYYY-MM-DD format
   * @readonly
   * @memberof Share
   */
  get expireDate() {
    return this._share.expiration;
  }

  /**
   * Set the expiration date
   *
   * @param {string} date the share expiration date with YYYY-MM-DD format
   * @memberof Share
   */
  set expireDate(date) {
    this._share.expiration = date;
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
    return this._share.token;
  }

  /**
   * Get the share note if any
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get note() {
    return this._share.note;
  }

  /**
   * Set the share note if any
   *
   * @param {string} note the note
   * @memberof Share
   */
  set note(note) {
    this._share.note = note;
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
    return this._share.label;
  }

  /**
   * Set the share label if any
   * Should only be set on link shares
   *
   * @param {string} label the label
   * @memberof Share
   */
  set label(label) {
    this._share.label = label;
  }

  /**
   * Have a mail been sent
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get mailSend() {
    return this._share.mail_send === true;
  }

  /**
   * Hide the download button on public page
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hideDownload() {
    return this._share.hide_download === true;
  }

  /**
   * Hide the download button on public page
   *
   * @param {boolean} state hide the button ?
   * @memberof Share
   */
  set hideDownload(state) {
    this._share.hide_download = state === true;
  }

  /**
   * Password protection of the share
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get password() {
    return this._share.password;
  }

  /**
   * Password protection of the share
   *
   * @param {string} password the share password
   * @memberof Share
   */
  set password(password) {
    this._share.password = password;
  }

  /**
   * Password expiration time
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get passwordExpirationTime() {
    return this._share.password_expiration_time;
  }

  /**
   * Password expiration time
   *
   * @param {string} password expiration time
   * @memberof Share
   */
  set passwordExpirationTime(passwordExpirationTime) {
    this._share.password_expiration_time = passwordExpirationTime;
  }

  /**
   * Password protection by Talk of the share
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get sendPasswordByTalk() {
    return this._share.send_password_by_talk;
  }

  /**
   * Password protection by Talk of the share
   *
   * @param {boolean} sendPasswordByTalk whether to send the password by Talk
   *        or not
   * @memberof Share
   */
  set sendPasswordByTalk(sendPasswordByTalk) {
    this._share.send_password_by_talk = sendPasswordByTalk;
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
    return this._share.path;
  }

  /**
   * Return the item type: file or folder
   *
   * @return {string} 'folder' or 'file'
   * @readonly
   * @memberof Share
   */
  get itemType() {
    return this._share.item_type;
  }

  /**
   * Get the shared item mimetype
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get mimetype() {
    return this._share.mimetype;
  }

  /**
   * Get the shared item id
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get fileSource() {
    return this._share.file_source;
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
    return this._share.file_target;
  }

  /**
   * Get the parent folder id if any
   *
   * @return {number}
   * @readonly
   * @memberof Share
   */
  get fileParent() {
    return this._share.file_parent;
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
    return !!(this.permissions & OC.PERMISSION_READ);
  }

  /**
   * Does this share have CREATE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasCreatePermission() {
    return !!(this.permissions & OC.PERMISSION_CREATE);
  }

  /**
   * Does this share have DELETE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasDeletePermission() {
    return !!(this.permissions & OC.PERMISSION_DELETE);
  }

  /**
   * Does this share have UPDATE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasUpdatePermission() {
    return !!(this.permissions & OC.PERMISSION_UPDATE);
  }

  /**
   * Does this share have SHARE permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasSharePermission() {
    return !!(this.permissions & OC.PERMISSION_SHARE);
  }

  /**
   * Does this share have download permissions
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get hasDownloadPermission() {
    for (const i in this._share.attributes) {
      const attr = this._share.attributes[i];
      if (attr.scope === 'permissions' && attr.key === 'download') {
        return attr.enabled;
      }
    }
    return true;
  }
  set hasDownloadPermission(enabled) {
    this.setAttribute('permissions', 'download', !!enabled);
  }
  setAttribute(scope, key, enabled) {
    const attrUpdate = {
      scope,
      key,
      enabled
    };

    // try and replace existing
    for (const i in this._share.attributes) {
      const attr = this._share.attributes[i];
      if (attr.scope === attrUpdate.scope && attr.key === attrUpdate.key) {
        this._share.attributes.splice(i, 1, attrUpdate);
        return;
      }
    }
    this._share.attributes.push(attrUpdate);
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
    return this._share.can_edit === true;
  }

  /**
   * Can the current user DELETE this share ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Share
   */
  get canDelete() {
    return this._share.can_delete === true;
  }

  /**
   * Top level accessible shared folder fileid for the current user
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get viaFileid() {
    return this._share.via_fileid;
  }

  /**
   * Top level accessible shared folder path for the current user
   *
   * @return {string}
   * @readonly
   * @memberof Share
   */
  get viaPath() {
    return this._share.via_path;
  }

  // TODO: SORT THOSE PROPERTIES

  get parent() {
    return this._share.parent;
  }
  get storageId() {
    return this._share.storage_id;
  }
  get storage() {
    return this._share.storage;
  }
  get itemSource() {
    return this._share.item_source;
  }
  get status() {
    return this._share.status;
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/services/ConfigService.js":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/services/ConfigService.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Config)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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


class Config {
  constructor() {
    this._shareConfig = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('files_sharing', 'shareConfig', {});
  }

  /**
   * Is public upload allowed on link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isPublicUploadEnabled() {
    return this._shareConfig.allowPublicUploads;
  }

  /**
   * Are link share allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isShareWithLinkAllowed() {
    return document.getElementById('allowShareWithLink') && document.getElementById('allowShareWithLink').value === 'yes';
  }

  /**
   * Get the federated sharing documentation link
   *
   * @return {string}
   * @readonly
   * @memberof Config
   */
  get federatedShareDocLink() {
    return OC.appConfig.core.federatedCloudShareDoc;
  }

  /**
   * Get the default link share expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultExpirationDate() {
    if (this.isDefaultExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate));
    }
    return null;
  }

  /**
   * Get the default internal expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultInternalExpirationDate() {
    if (this.isDefaultInternalExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultInternalExpireDate));
    }
    return null;
  }

  /**
   * Get the default remote expiration date
   *
   * @return {Date|null}
   * @readonly
   * @memberof Config
   */
  get defaultRemoteExpirationDateString() {
    if (this.isDefaultRemoteExpireDateEnabled) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultRemoteExpireDate));
    }
    return null;
  }

  /**
   * Are link shares password-enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get enforcePasswordForPublicLink() {
    return OC.appConfig.core.enforcePasswordForPublicLink === true;
  }

  /**
   * Is password asked by default on link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get enableLinkPasswordByDefault() {
    return OC.appConfig.core.enableLinkPasswordByDefault === true;
  }

  /**
   * Is link shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultExpireDateEnforced() {
    return OC.appConfig.core.defaultExpireDateEnforced === true;
  }

  /**
   * Is there a default expiration date for new link shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultExpireDateEnabled() {
    return OC.appConfig.core.defaultExpireDateEnabled === true;
  }

  /**
   * Is internal shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultInternalExpireDateEnforced() {
    return OC.appConfig.core.defaultInternalExpireDateEnforced === true;
  }

  /**
   * Is remote shares expiration enforced ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultRemoteExpireDateEnforced() {
    return OC.appConfig.core.defaultRemoteExpireDateEnforced === true;
  }

  /**
   * Is there a default expiration date for new internal shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultInternalExpireDateEnabled() {
    return OC.appConfig.core.defaultInternalExpireDateEnabled === true;
  }

  /**
   * Is there a default expiration date for new remote shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isDefaultRemoteExpireDateEnabled() {
    return OC.appConfig.core.defaultRemoteExpireDateEnabled === true;
  }

  /**
   * Are users on this server allowed to send shares to other servers ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isRemoteShareAllowed() {
    return OC.appConfig.core.remoteShareAllowed === true;
  }

  /**
   * Is sharing my mail (link share) enabled ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isMailShareAllowed() {
    const capabilities = OC.getCapabilities();
    // eslint-disable-next-line camelcase
    return capabilities?.files_sharing?.sharebymail !== undefined
    // eslint-disable-next-line camelcase
    && capabilities?.files_sharing?.public?.enabled === true;
  }

  /**
   * Get the default days to link shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultExpireDate() {
    return OC.appConfig.core.defaultExpireDate;
  }

  /**
   * Get the default days to internal shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultInternalExpireDate() {
    return OC.appConfig.core.defaultInternalExpireDate;
  }

  /**
   * Get the default days to remote shares expiration
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get defaultRemoteExpireDate() {
    return OC.appConfig.core.defaultRemoteExpireDate;
  }

  /**
   * Is resharing allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isResharingAllowed() {
    return OC.appConfig.core.resharingAllowed === true;
  }

  /**
   * Is password enforced for mail shares ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get isPasswordForMailSharesRequired() {
    return OC.getCapabilities().files_sharing.sharebymail === undefined ? false : OC.getCapabilities().files_sharing.sharebymail.password.enforced;
  }

  /**
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get shouldAlwaysShowUnique() {
    return OC.getCapabilities().files_sharing?.sharee?.always_show_unique === true;
  }

  /**
   * Is sharing with groups allowed ?
   *
   * @return {boolean}
   * @readonly
   * @memberof Config
   */
  get allowGroupSharing() {
    return OC.appConfig.core.allowGroupSharing === true;
  }

  /**
   * Get the maximum results of a share search
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get maxAutocompleteResults() {
    return parseInt(OC.config['sharing.maxAutocompleteResults'], 10) || 25;
  }

  /**
   * Get the minimal string length
   * to initiate a share search
   *
   * @return {number}
   * @readonly
   * @memberof Config
   */
  get minSearchStringLength() {
    return parseInt(OC.config['sharing.minSearchStringLength'], 10) || 0;
  }

  /**
   * Get the password policy config
   *
   * @return {object}
   * @readonly
   * @memberof Config
   */
  get passwordPolicy() {
    const capabilities = OC.getCapabilities();
    return capabilities.password_policy ? capabilities.password_policy : {};
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/utils/GeneratePassword.js":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/utils/GeneratePassword.js ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * @copyright Copyright (c) 2020 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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




const config = new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_1__["default"]();
// note: some chars removed on purpose to make them human friendly when read out
const passwordSet = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';

/**
 * Generate a valid policy password or
 * request a valid password if password_policy
 * is enabled
 *
 * @return {string} a valid password
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__() {
  // password policy is enabled, let's request a pass
  if (config.passwordPolicy.api && config.passwordPolicy.api.generate) {
    try {
      const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(config.passwordPolicy.api.generate);
      if (request.data.ocs.data.password) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)(t('files_sharing', 'Password created successfully'));
        return request.data.ocs.data.password;
      }
    } catch (error) {
      console.info('Error generating password from password_policy', error);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)(t('files_sharing', 'Error generating password from password policy'));
    }
  }
  const array = new Uint8Array(10);
  const ratio = passwordSet.length / 255;
  self.crypto.getRandomValues(array);
  let password = '';
  for (let i = 0; i < array.length; i++) {
    password += passwordSet.charAt(array[i] * ratio);
  }
  return password;
}

/***/ }),

/***/ "./apps/files_sharing/src/utils/SharedWithMe.js":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/utils/SharedWithMe.js ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   shareWithTitle: () => (/* binding */ shareWithTitle)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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


const shareWithTitle = function (share) {
  if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_GROUP) {
    return t('files_sharing', 'Shared with you and the group {group} by {owner}', {
      group: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_CIRCLE) {
    return t('files_sharing', 'Shared with you and {circle} by {owner}', {
      circle: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.Type.SHARE_TYPE_ROOM) {
    if (share.shareWithDisplayName) {
      return t('files_sharing', 'Shared with you and the conversation {conversation} by {owner}', {
        conversation: share.shareWithDisplayName,
        owner: share.ownerDisplayName
      }, undefined, {
        escape: false
      });
    } else {
      return t('files_sharing', 'Shared with you in a conversation by {owner}', {
        owner: share.ownerDisplayName
      }, undefined, {
        escape: false
      });
    }
  } else {
    return t('files_sharing', 'Shared with you by {owner}', {
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  }
};


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ExternalShareAction',
  props: {
    id: {
      type: String,
      required: true
    },
    action: {
      type: Object,
      default: () => ({})
    },
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_0__["default"],
      default: null
    }
  },
  computed: {
    data() {
      return this.action.data(this);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntry',
  components: {
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default()),
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_1___default()),
    QuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_5__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  data() {
    return {
      showDropdown: false
    };
  },
  computed: {
    title() {
      let title = this.share.shareWithDisplayName;
      if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
        title += ` (${t('files_sharing', 'group')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
        title += ` (${t('files_sharing', 'conversation')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE) {
        title += ` (${t('files_sharing', 'remote')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) {
        title += ` (${t('files_sharing', 'remote group')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GUEST) {
        title += ` (${t('files_sharing', 'guest')})`;
      }
      return title;
    },
    tooltip() {
      if (this.share.owner !== this.share.uidFileOwner) {
        const data = {
          // todo: strong or italic?
          // but the t function escape any html from the data :/
          user: this.share.shareWithDisplayName,
          owner: this.share.ownerDisplayName
        };
        if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
          return t('files_sharing', 'Shared with the group {user} by {owner}', data);
        } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
          return t('files_sharing', 'Shared with the conversation {user} by {owner}', data);
        }
        return t('files_sharing', 'Shared with {user} by {owner}', data);
      }
      return null;
    },
    /**
     * @return {boolean}
     */
    hasStatus() {
      if (this.share.type !== this.SHARE_TYPES.SHARE_TYPE_USER) {
        return false;
      }
      return typeof this.share.status === 'object' && !Array.isArray(this.share.status);
    }
  },
  methods: {
    /**
     * Save potential changed data on menu close
     */
    onMenuClose() {
      this.onNoteSubmit();
    },
    toggleQuickShareSelect() {
      this.showDropdown = !this.showDropdown;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");







// eslint-disable-next-line no-unused-vars



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInherited',
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcActionLink: (_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcActionText: (_nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default()),
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_7__["default"]],
  props: {
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_6__["default"],
      required: true
    }
  },
  computed: {
    viaFileTargetUrl() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/f/{fileid}', {
        fileid: this.share.viaFileid
      });
    },
    viaFolderName() {
      return (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_1__.basename)(this.share.viaPath);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInternal',
  components: {
    NcActionLink: (_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_2___default()),
    SharingEntrySimple: _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    }
  },
  data() {
    return {
      copied: false,
      copySuccess: false
    };
  },
  computed: {
    /**
     * Get the internal link to this file id
     *
     * @return {string}
     */
    internalLink() {
      return window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/f/') + this.fileInfo.id;
    },
    /**
     * Tooltip message
     *
     * @return {string}
     */
    copyLinkTooltip() {
      if (this.copied) {
        if (this.copySuccess) {
          return '';
        }
        return t('files_sharing', 'Cannot copy, please copy the link manually');
      }
      return t('files_sharing', 'Copy internal link to clipboard');
    },
    internalLinkSubtitle() {
      if (this.fileInfo.type === 'dir') {
        return t('files_sharing', 'Only works for users with access to this folder');
      }
      return t('files_sharing', 'Only works for users with access to this file');
    }
  },
  methods: {
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.internalLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link copied'));
        // focus and show the tooltip (note: cannot set ref on NcActionLink)
        this.$refs.shareEntrySimple.$refs.actionsComponent.$el.focus();
        this.copySuccess = true;
        this.copied = true;
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        console.error(error);
      } finally {
        setTimeout(() => {
          this.copySuccess = false;
          this.copied = false;
        }, 4000);
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionInput.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionLink.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionText.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionSeparator.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_8___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_8__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Tune.vue */ "./node_modules/vue-material-design-icons/Tune.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./ExternalShareAction.vue */ "./apps/files_sharing/src/components/ExternalShareAction.vue");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");


















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryLink',
  components: {
    ExternalShareAction: _ExternalShareAction_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcActions: (_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_8___default()),
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcActionInput: (_nextcloud_vue_dist_Components_NcActionInput_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcActionLink: (_nextcloud_vue_dist_Components_NcActionLink_js__WEBPACK_IMPORTED_MODULE_5___default()),
    NcActionText: (_nextcloud_vue_dist_Components_NcActionText_js__WEBPACK_IMPORTED_MODULE_6___default()),
    NcActionSeparator: (_nextcloud_vue_dist_Components_NcActionSeparator_js__WEBPACK_IMPORTED_MODULE_7___default()),
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_9___default()),
    Tune: vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    QuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_11__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_15__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_16__["default"]],
  props: {
    canReshare: {
      type: Boolean,
      default: true
    },
    index: {
      type: Number,
      default: null
    }
  },
  data() {
    return {
      showDropdown: false,
      copySuccess: true,
      copied: false,
      // Are we waiting for password/expiration date
      pending: false,
      ExternalLegacyLinkActions: OCA.Sharing.ExternalLinkActions.state,
      ExternalShareActions: OCA.Sharing.ExternalShareActions.state
    };
  },
  computed: {
    /**
     * Link share label
     *
     * @return {string}
     */
    title() {
      // if we have a valid existing share (not pending)
      if (this.share && this.share.id) {
        if (!this.isShareOwner && this.share.ownerDisplayName) {
          if (this.isEmailShareType) {
            return t('files_sharing', '{shareWith} by {initiator}', {
              shareWith: this.share.shareWith,
              initiator: this.share.ownerDisplayName
            });
          }
          return t('files_sharing', 'Shared via link by {initiator}', {
            initiator: this.share.ownerDisplayName
          });
        }
        if (this.share.label && this.share.label.trim() !== '') {
          if (this.isEmailShareType) {
            return t('files_sharing', 'Mail share ({label})', {
              label: this.share.label.trim()
            });
          }
          return t('files_sharing', 'Share link ({label})', {
            label: this.share.label.trim()
          });
        }
        if (this.isEmailShareType) {
          return this.share.shareWith;
        }
      }
      if (this.index > 1) {
        return t('files_sharing', 'Share link ({index})', {
          index: this.index
        });
      }
      return t('files_sharing', 'Share link');
    },
    /**
     * Show the email on a second line if a label is set for mail shares
     *
     * @return {string}
     */
    subtitle() {
      if (this.isEmailShareType && this.title !== this.share.shareWith) {
        return this.share.shareWith;
      }
      return null;
    },
    /**
     * Does the current share have an expiration date
     *
     * @return {boolean}
     */
    hasExpirationDate: {
      get() {
        return this.config.isDefaultExpireDateEnforced || !!this.share.expireDate;
      },
      set(enabled) {
        const defaultExpirationDate = this.config.defaultExpirationDate || new Date(new Date().setDate(new Date().getDate() + 1));
        this.share.expireDate = enabled ? this.formatDateToString(defaultExpirationDate) : '';
        console.debug('Expiration date status', enabled, this.share.expireDate);
      }
    },
    dateMaxEnforced() {
      if (this.config.isDefaultExpireDateEnforced) {
        return new Date(new Date().setDate(new Date().getDate() + this.config.defaultExpireDate));
      }
      return null;
    },
    /**
     * Is the current share password protected ?
     *
     * @return {boolean}
     */
    isPasswordProtected: {
      get() {
        return this.config.enforcePasswordForPublicLink || !!this.share.password;
      },
      async set(enabled) {
        // TODO: directly save after generation to make sure the share is always protected
        vue__WEBPACK_IMPORTED_MODULE_17__["default"].set(this.share, 'password', enabled ? await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_13__["default"])() : '');
        vue__WEBPACK_IMPORTED_MODULE_17__["default"].set(this.share, 'newPassword', this.share.password);
      }
    },
    passwordExpirationTime() {
      if (this.share.passwordExpirationTime === null) {
        return null;
      }
      const expirationTime = moment(this.share.passwordExpirationTime);
      if (expirationTime.diff(moment()) < 0) {
        return false;
      }
      return expirationTime.fromNow();
    },
    /**
     * Is Talk enabled?
     *
     * @return {boolean}
     */
    isTalkEnabled() {
      return OC.appswebroots.spreed !== undefined;
    },
    /**
     * Is it possible to protect the password by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalkAvailable() {
      return this.isPasswordProtected && this.isTalkEnabled;
    },
    /**
     * Is the current share password protected by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalk: {
      get() {
        return this.share.sendPasswordByTalk;
      },
      async set(enabled) {
        this.share.sendPasswordByTalk = enabled;
      }
    },
    /**
     * Is the current share an email share ?
     *
     * @return {boolean}
     */
    isEmailShareType() {
      return this.share ? this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL : false;
    },
    canTogglePasswordProtectedByTalkAvailable() {
      if (!this.isPasswordProtected) {
        // Makes no sense
        return false;
      } else if (this.isEmailShareType && !this.hasUnsavedPassword) {
        // For email shares we need a new password in order to enable or
        // disable
        return false;
      }

      // Anything else should be fine
      return true;
    },
    /**
     * Pending data.
     * If the share still doesn't have an id, it is not synced
     * Therefore this is still not valid and requires user input
     *
     * @return {boolean}
     */
    pendingPassword() {
      return this.config.enableLinkPasswordByDefault && this.share && !this.share.id;
    },
    pendingEnforcedPassword() {
      return this.config.enforcePasswordForPublicLink && this.share && !this.share.id;
    },
    pendingExpirationDate() {
      return this.config.isDefaultExpireDateEnforced && this.share && !this.share.id;
    },
    // if newPassword exists, but is empty, it means
    // the user deleted the original password
    hasUnsavedPassword() {
      return this.share.newPassword !== undefined;
    },
    /**
     * Return the public share link
     *
     * @return {string}
     */
    shareLink() {
      return window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/s/') + this.share.token;
    },
    /**
     * Tooltip message for actions button
     *
     * @return {string}
     */
    actionsTooltip() {
      return t('files_sharing', 'Actions for "{title}"', {
        title: this.title
      });
    },
    /**
     * Tooltip message for copy button
     *
     * @return {string}
     */
    copyLinkTooltip() {
      if (this.copied) {
        if (this.copySuccess) {
          return '';
        }
        return t('files_sharing', 'Cannot copy, please copy the link manually');
      }
      return t('files_sharing', 'Copy public link of "{title}" to clipboard', {
        title: this.title
      });
    },
    /**
     * External additionnai actions for the menu
     *
     * @deprecated use OCA.Sharing.ExternalShareActions
     * @return {Array}
     */
    externalLegacyLinkActions() {
      return this.ExternalLegacyLinkActions.actions;
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    externalLinkActions() {
      // filter only the registered actions for said link
      return this.ExternalShareActions.actions.filter(action => action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK) || action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_EMAIL));
    },
    isPasswordPolicyEnabled() {
      return typeof this.config.passwordPolicy === 'object';
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    }
  },
  methods: {
    /**
     * Create a new share link and append it to the list
     */
    async onNewLinkShare() {
      // do not run again if already loading
      if (this.loading) {
        return;
      }
      const shareDefaults = {
        share_type: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK
      };
      if (this.config.isDefaultExpireDateEnforced) {
        // default is empty string if not set
        // expiration is the share object key, not expireDate
        shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate);
      }

      // do not push yet if we need a password or an expiration date: show pending menu
      if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced) {
        this.pending = true;

        // if a share already exists, pushing it
        if (this.share && !this.share.id) {
          // if the share is valid, create it on the server
          if (this.checkShare(this.share)) {
            try {
              await this.pushNewLinkShare(this.share, true);
            } catch (e) {
              this.pending = false;
              console.error(e);
              return false;
            }
            return true;
          } else {
            this.open = true;
            OC.Notification.showTemporary(t('files_sharing', 'Error, please enter proper password and/or expiration date'));
            return false;
          }
        }

        // ELSE, show the pending popovermenu
        // if password default or enforced, pre-fill with random one
        if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
          shareDefaults.password = await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_13__["default"])();
        }

        // create share & close menu
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_14__["default"](shareDefaults);
        const component = await new Promise(resolve => {
          this.$emit('add:share', share, resolve);
        });

        // open the menu on the
        // freshly created share component
        this.open = false;
        this.pending = false;
        component.open = true;

        // Nothing is enforced, creating share directly
      } else {
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_14__["default"](shareDefaults);
        await this.pushNewLinkShare(share);
      }
    },
    /**
     * Push a new link share to the server
     * And update or append to the list
     * accordingly
     *
     * @param {Share} share the new share
     * @param {boolean} [update] do we update the current share ?
     */
    async pushNewLinkShare(share, update) {
      try {
        // do nothing if we're already pending creation
        if (this.loading) {
          return true;
        }
        this.loading = true;
        this.errors = {};
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');
        const options = {
          path,
          shareType: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.Type.SHARE_TYPE_LINK,
          password: share.password,
          expireDate: share.expireDate,
          attributes: JSON.stringify(this.fileInfo.shareAttributes)
          // we do not allow setting the publicUpload
          // before the share creation.
          // Todo: We also need to fix the createShare method in
          // lib/Controller/ShareAPIController.php to allow file drop
          // (currently not supported on create, only update)
        };

        console.debug('Creating link share with options', options);
        const newShare = await this.createShare(options);
        this.open = false;
        console.debug('Link share created', newShare);

        // if share already exists, copy link directly on next tick
        let component;
        if (update) {
          component = await new Promise(resolve => {
            this.$emit('update:share', newShare, resolve);
          });
        } else {
          // adding new share to the array and copying link to clipboard
          // using promise so that we can copy link in the same click function
          // and avoid firefox copy permissions issue
          component = await new Promise(resolve => {
            this.$emit('add:share', newShare, resolve);
          });
        }

        // Execute the copy link method
        // freshly created share component
        // ! somehow does not works on firefox !
        if (!this.config.enforcePasswordForPublicLink) {
          // Only copy the link when the password was not forced,
          // otherwise the user needs to copy/paste the password before finishing the share.
          component.copyLink();
        }
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link share created'));
      } catch (data) {
        const message = data?.response?.data?.ocs?.meta?.message;
        if (!message) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('files_sharing', 'Error while creating the share'));
          console.error(data);
          return;
        }
        if (message.match(/password/i)) {
          this.onSyncError('password', message);
        } else if (message.match(/date/i)) {
          this.onSyncError('expireDate', message);
        } else {
          this.onSyncError('pending', message);
        }
        throw data;
      } finally {
        this.loading = false;
      }
    },
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.shareLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(t('files_sharing', 'Link copied'));
        // focus and show the tooltip
        this.$refs.copyButton.$el.focus();
        this.copySuccess = true;
        this.copied = true;
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        console.error(error);
      } finally {
        setTimeout(() => {
          this.copySuccess = false;
          this.copied = false;
        }, 4000);
      }
    },
    /**
     * Update newPassword values
     * of share. If password is set but not newPassword
     * then the user did not changed the password
     * If both co-exists, the password have changed and
     * we show it in plain text.
     * Then on submit (or menu close), we sync it.
     *
     * @param {string} password the changed password
     */
    onPasswordChange(password) {
      this.$set(this.share, 'newPassword', password);
    },
    /**
     * Uncheck password protection
     * We need this method because @update:checked
     * is ran simultaneously as @uncheck, so we
     * cannot ensure data is up-to-date
     */
    onPasswordDisable() {
      this.share.password = '';

      // reset password state after sync
      this.$delete(this.share, 'newPassword');

      // only update if valid share.
      if (this.share.id) {
        this.queueUpdate('password');
      }
    },
    /**
     * Menu have been closed or password has been submitted.
     * The only property that does not get
     * synced automatically is the password
     * So let's check if we have an unsaved
     * password.
     * expireDate is saved on datepicker pick
     * or close.
     */
    onPasswordSubmit() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
        this.queueUpdate('password');
      }
    },
    /**
     * Update the password along with "sendPasswordByTalk".
     *
     * If the password was modified the new password is sent; otherwise
     * updating a mail share would fail, as in that case it is required that
     * a new password is set when enabling or disabling
     * "sendPasswordByTalk".
     */
    onPasswordProtectedByTalkChange() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
      }
      this.queueUpdate('sendPasswordByTalk', 'password');
    },
    /**
     * Save potential changed data on menu close
     */
    onMenuClose() {
      this.onPasswordSubmit();
      this.onNoteSubmit();
    },
    /**
     * Cancel the share creation
     * Used in the pending popover
     */
    onCancel() {
      // this.share already exists at this point,
      // but is incomplete as not pushed to server
      // YET. We can safely delete the share :)
      this.$emit('remove:share', this.share);
    },
    toggleQuickShareSelect() {
      this.showDropdown = !this.showDropdown;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-material-design-icons/TriangleSmallDown.vue */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* harmony import */ var focus_trap__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! focus-trap */ "./node_modules/focus-trap/dist/focus-trap.esm.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  components: {
    DropdownIcon: vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_1__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__["default"], _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    share: {
      type: Object,
      required: true
    },
    toggle: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      selectedOption: '',
      showDropdown: this.toggle,
      focusTrap: null
    };
  },
  computed: {
    canViewText() {
      return t('files_sharing', 'View only');
    },
    canEditText() {
      return t('files_sharing', 'Can edit');
    },
    fileDropText() {
      return t('files_sharing', 'File drop');
    },
    customPermissionsText() {
      return t('files_sharing', 'Custom permissions');
    },
    preSelectedOption() {
      // We remove the share permission for the comparison as it is not relevant for bundled permissions.
      if ((this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.ATOMIC_PERMISSIONS.SHARE) === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.READ_ONLY) {
        return this.canViewText;
      } else if (this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.ALL || this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.ALL_FILE) {
        return this.canEditText;
      } else if ((this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.ATOMIC_PERMISSIONS.SHARE) === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.FILE_DROP) {
        return this.fileDropText;
      }
      return this.customPermissionsText;
    },
    options() {
      const options = [this.canViewText, this.canEditText];
      if (this.supportsFileDrop) {
        options.push(this.fileDropText);
      }
      options.push(this.customPermissionsText);
      return options;
    },
    supportsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        const shareType = this.share.type ?? this.share.shareType;
        return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType);
      }
      return false;
    },
    dropDownPermissionValue() {
      switch (this.selectedOption) {
        case this.canEditText:
          return this.isFolder ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.ALL : _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.ALL_FILE;
        case this.fileDropText:
          return _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.FILE_DROP;
        case this.customPermissionsText:
          return 'custom';
        case this.canViewText:
        default:
          return _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_4__.BUNDLED_PERMISSIONS.READ_ONLY;
      }
    },
    dropdownId() {
      // Generate a unique ID for ARIA attributes
      return `dropdown-${Math.random().toString(36).substr(2, 9)}`;
    }
  },
  watch: {
    toggle(toggleValue) {
      this.showDropdown = toggleValue;
    }
  },
  mounted() {
    this.initializeComponent();
    window.addEventListener('click', this.handleClickOutside);
  },
  beforeDestroy() {
    // Remove the global click event listener to prevent memory leaks
    window.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    toggleDropdown() {
      this.showDropdown = !this.showDropdown;
      if (this.showDropdown) {
        this.$nextTick(() => {
          this.useFocusTrap();
        });
      } else {
        this.clearFocusTrap();
      }
    },
    closeDropdown() {
      this.clearFocusTrap();
      this.showDropdown = false;
    },
    selectOption(option) {
      this.selectedOption = option;
      if (option === this.customPermissionsText) {
        this.$emit('open-sharing-details');
      } else {
        this.share.permissions = this.dropDownPermissionValue;
        this.queueUpdate('permissions');
      }
      this.showDropdown = false;
    },
    initializeComponent() {
      this.selectedOption = this.preSelectedOption;
    },
    handleClickOutside(event) {
      const dropdownContainer = this.$refs.quickShareDropdownContainer;
      if (dropdownContainer && !dropdownContainer.contains(event.target)) {
        this.showDropdown = false;
      }
    },
    useFocusTrap() {
      // Create global stack if undefined
      // Use in with trapStack to avoid conflicting traps
      Object.assign(window, {
        _nc_focus_trap: window._nc_focus_trap || []
      });
      const dropdownElement = this.$refs.quickShareDropdown;
      this.focusTrap = (0,focus_trap__WEBPACK_IMPORTED_MODULE_5__.createFocusTrap)(dropdownElement, {
        allowOutsideClick: true,
        trapStack: window._nc_focus_trap
      });
      this.focusTrap.activate();
    },
    clearFocusTrap() {
      this.focusTrap?.deactivate();
      this.focusTrap = null;
    },
    shiftFocusForward() {
      const currentElement = document.activeElement;
      let nextElement = currentElement.nextElementSibling;
      if (!nextElement) {
        nextElement = this.$refs.quickShareDropdown.firstElementChild;
      }
      nextElement.focus();
    },
    shiftFocusBackward() {
      const currentElement = document.activeElement;
      let previousElement = currentElement.previousElementSibling;
      if (!previousElement) {
        previousElement = this.$refs.quickShareDropdown.lastElementChild;
      }
      previousElement.focus();
    },
    handleArrowUp() {
      this.shiftFocusBackward();
    },
    handleArrowDown() {
      this.shiftFocusForward();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActions.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0__);

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntrySimple',
  components: {
    NcActions: (_nextcloud_vue_dist_Components_NcActions_js__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    title: {
      type: String,
      default: '',
      required: true
    },
    subtitle: {
      type: String,
      default: ''
    },
    isUnique: {
      type: Boolean,
      default: true
    },
    ariaExpanded: {
      type: Boolean,
      default: null
    }
  },
  computed: {
    ariaExpandedValue() {
      if (this.ariaExpanded === null) {
        return this.ariaExpanded;
      }
      return this.ariaExpanded ? 'true' : 'false';
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.es.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcSelect.js */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");











/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInput',
  components: {
    NcSelect: (_nextcloud_vue_dist_Components_NcSelect_js__WEBPACK_IMPORTED_MODULE_4___default())
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_9__["default"], _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_8__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_10__["default"]],
  props: {
    shares: {
      type: Array,
      default: () => [],
      required: true
    },
    linkShares: {
      type: Array,
      default: () => [],
      required: true
    },
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    reshare: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"],
      default: null
    },
    canReshare: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__["default"](),
      loading: false,
      query: '',
      recommendations: [],
      ShareSearch: OCA.Sharing.ShareSearch.state,
      suggestions: [],
      value: null
    };
  },
  computed: {
    /**
     * Implement ShareSearch
     * allows external appas to inject new
     * results into the autocomplete dropdown
     * Used for the guests app
     *
     * @return {Array}
     */
    externalResults() {
      return this.ShareSearch.results;
    },
    inputPlaceholder() {
      const allowRemoteSharing = this.config.isRemoteShareAllowed;
      if (!this.canReshare) {
        return t('files_sharing', 'Resharing is not allowed');
      }
      // We can always search with email addresses for users too
      if (!allowRemoteSharing) {
        return t('files_sharing', 'Name or email …');
      }
      return t('files_sharing', 'Name, email, or Federated Cloud ID …');
    },
    isValidQuery() {
      return this.query && this.query.trim() !== '' && this.query.length > this.config.minSearchStringLength;
    },
    options() {
      if (this.isValidQuery) {
        return this.suggestions;
      }
      return this.recommendations;
    },
    noResultText() {
      if (this.loading) {
        return t('files_sharing', 'Searching …');
      }
      return t('files_sharing', 'No elements found.');
    }
  },
  mounted() {
    this.getRecommendations();
  },
  methods: {
    async asyncFind(query) {
      // save current query to check if we display
      // recommendations or search results
      this.query = query.trim();
      if (this.isValidQuery) {
        // start loading now to have proper ux feedback
        // during the debounce
        this.loading = true;
        await this.debounceGetSuggestions(query);
      }
    },
    /**
     * Get suggestions
     *
     * @param {string} search the search query
     * @param {boolean} [lookup] search on lookup server
     */
    async getSuggestions(search, lookup = false) {
      this.loading = true;
      if (OC.getCapabilities().files_sharing.sharee.query_lookup_default === true) {
        lookup = true;
      }
      const shareType = [this.SHARE_TYPES.SHARE_TYPE_USER, this.SHARE_TYPES.SHARE_TYPE_GROUP, this.SHARE_TYPES.SHARE_TYPE_REMOTE, this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP, this.SHARE_TYPES.SHARE_TYPE_CIRCLE, this.SHARE_TYPES.SHARE_TYPE_ROOM, this.SHARE_TYPES.SHARE_TYPE_GUEST, this.SHARE_TYPES.SHARE_TYPE_DECK, this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH];
      if (OC.getCapabilities().files_sharing.public.enabled === true) {
        shareType.push(this.SHARE_TYPES.SHARE_TYPE_EMAIL);
      }
      let request = null;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/sharees'), {
          params: {
            format: 'json',
            itemType: this.fileInfo.type === 'dir' ? 'folder' : 'file',
            search,
            lookup,
            perPage: this.config.maxAutocompleteResults,
            shareType
          }
        });
      } catch (error) {
        console.error('Error fetching suggestions', error);
        return;
      }
      const data = request.data.ocs.data;
      const exact = request.data.ocs.data.exact;
      data.exact = []; // removing exact from general results

      // flatten array of arrays
      const rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), []);
      const rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), []);

      // remove invalid data and format to user-select layout
      const exactSuggestions = this.filterOutExistingShares(rawExactSuggestions).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);
      const suggestions = this.filterOutExistingShares(rawSuggestions).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);

      // lookup clickable entry
      // show if enabled and not already requested
      const lookupEntry = [];
      if (data.lookupEnabled && !lookup) {
        lookupEntry.push({
          id: 'global-lookup',
          isNoUser: true,
          displayName: t('files_sharing', 'Search globally'),
          lookup: true
        });
      }

      // if there is a condition specified, filter it
      const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this));
      const allSuggestions = exactSuggestions.concat(suggestions).concat(externalResults).concat(lookupEntry);

      // Count occurrences of display names in order to provide a distinguishable description if needed
      const nameCounts = allSuggestions.reduce((nameCounts, result) => {
        if (!result.displayName) {
          return nameCounts;
        }
        if (!nameCounts[result.displayName]) {
          nameCounts[result.displayName] = 0;
        }
        nameCounts[result.displayName]++;
        return nameCounts;
      }, {});
      this.suggestions = allSuggestions.map(item => {
        // Make sure that items with duplicate displayName get the shareWith applied as a description
        if (nameCounts[item.displayName] > 1 && !item.desc) {
          return {
            ...item,
            desc: item.shareWithDisplayNameUnique
          };
        }
        return item;
      });
      this.loading = false;
      console.info('suggestions', this.suggestions);
    },
    /**
     * Debounce getSuggestions
     *
     * @param {...*} args the arguments
     */
    debounceGetSuggestions: debounce__WEBPACK_IMPORTED_MODULE_3___default()(function (...args) {
      this.getSuggestions(...args);
    }, 300),
    /**
     * Get the sharing recommendations
     */
    async getRecommendations() {
      this.loading = true;
      let request = null;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/sharees_recommended'), {
          params: {
            format: 'json',
            itemType: this.fileInfo.type
          }
        });
      } catch (error) {
        console.error('Error fetching recommendations', error);
        return;
      }

      // Add external results from the OCA.Sharing.ShareSearch api
      const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this));

      // flatten array of arrays
      const rawRecommendations = Object.values(request.data.ocs.data.exact).reduce((arr, elem) => arr.concat(elem), []);

      // remove invalid data and format to user-select layout
      this.recommendations = this.filterOutExistingShares(rawRecommendations).map(share => this.formatForMultiselect(share)).concat(externalResults);
      this.loading = false;
      console.info('recommendations', this.recommendations);
    },
    /**
     * Filter out existing shares from
     * the provided shares search results
     *
     * @param {object[]} shares the array of shares object
     * @return {object[]}
     */
    filterOutExistingShares(shares) {
      return shares.reduce((arr, share) => {
        // only check proper objects
        if (typeof share !== 'object') {
          return arr;
        }
        try {
          if (share.value.shareType === this.SHARE_TYPES.SHARE_TYPE_USER) {
            // filter out current user
            if (share.value.shareWith === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_1__.getCurrentUser)().uid) {
              return arr;
            }

            // filter out the owner of the share
            if (this.reshare && share.value.shareWith === this.reshare.owner) {
              return arr;
            }
          }

          // filter out existing mail shares
          if (share.value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
            const emails = this.linkShares.map(elem => elem.shareWith);
            if (emails.indexOf(share.value.shareWith.trim()) !== -1) {
              return arr;
            }
          } else {
            // filter out existing shares
            // creating an object of uid => type
            const sharesObj = this.shares.reduce((obj, elem) => {
              obj[elem.shareWith] = elem.type;
              return obj;
            }, {});

            // if shareWith is the same and the share type too, ignore it
            const key = share.value.shareWith.trim();
            if (key in sharesObj && sharesObj[key] === share.value.shareType) {
              return arr;
            }
          }

          // ALL GOOD
          // let's add the suggestion
          arr.push(share);
        } catch {
          return arr;
        }
        return arr;
      }, []);
    },
    /**
     * Get the icon based on the share type
     *
     * @param {number} type the share type
     * @return {string} the icon class
     */
    shareTypeToIcon(type) {
      switch (type) {
        case this.SHARE_TYPES.SHARE_TYPE_GUEST:
          // default is a user, other icons are here to differentiate
          // themselves from it, so let's not display the user icon
          // case this.SHARE_TYPES.SHARE_TYPE_REMOTE:
          // case this.SHARE_TYPES.SHARE_TYPE_USER:
          return {
            icon: 'icon-user',
            iconTitle: t('files_sharing', 'Guest')
          };
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
        case this.SHARE_TYPES.SHARE_TYPE_GROUP:
          return {
            icon: 'icon-group',
            iconTitle: t('files_sharing', 'Group')
          };
        case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
          return {
            icon: 'icon-mail',
            iconTitle: t('files_sharing', 'Email')
          };
        case this.SHARE_TYPES.SHARE_TYPE_CIRCLE:
          return {
            icon: 'icon-circle',
            iconTitle: t('files_sharing', 'Circle')
          };
        case this.SHARE_TYPES.SHARE_TYPE_ROOM:
          return {
            icon: 'icon-room',
            iconTitle: t('files_sharing', 'Talk conversation')
          };
        case this.SHARE_TYPES.SHARE_TYPE_DECK:
          return {
            icon: 'icon-deck',
            iconTitle: t('files_sharing', 'Deck board')
          };
        case this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH:
          return {
            icon: 'icon-sciencemesh',
            iconTitle: t('files_sharing', 'ScienceMesh')
          };
        default:
          return {};
      }
    },
    /**
     * Format shares for the multiselect options
     *
     * @param {object} result select entry item
     * @return {object}
     */
    formatForMultiselect(result) {
      let subtitle;
      if (result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_USER && this.config.shouldAlwaysShowUnique) {
        subtitle = result.shareWithDisplayNameUnique ?? '';
      } else if ((result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_REMOTE || result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) && result.value.server) {
        subtitle = t('files_sharing', 'on {server}', {
          server: result.value.server
        });
      } else if (result.value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
        subtitle = result.value.shareWith;
      } else {
        subtitle = result.shareWithDescription ?? '';
      }
      return {
        shareWith: result.value.shareWith,
        shareType: result.value.shareType,
        user: result.uuid || result.value.shareWith,
        isNoUser: result.value.shareType !== this.SHARE_TYPES.SHARE_TYPE_USER,
        displayName: result.name || result.label,
        subtitle,
        shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
        ...this.shareTypeToIcon(result.value.shareType)
      };
    },
    /**
     * Process the new share request
     *
     * @param {object} value the multiselect option
     */
    async addShare(value) {
      // Clear the displayed selection
      this.value = null;
      if (value.lookup) {
        await this.getSuggestions(this.query, true);
        this.$nextTick(() => {
          // open the dropdown again
          this.$refs.select.$children[0].open = true;
        });
        return true;
      }

      // handle externalResults from OCA.Sharing.ShareSearch
      if (value.handler) {
        const share = await value.handler(this);
        this.$emit('add:share', new _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"](share));
        return true;
      }
      this.loading = true;
      console.debug('Adding a new share from the input for', value);
      try {
        let password = null;
        if (this.config.enforcePasswordForPublicLink && value.shareType === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
          password = await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_6__["default"])();
        }
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');
        const share = await this.createShare({
          path,
          shareType: value.shareType,
          shareWith: value.shareWith,
          password,
          permissions: this.fileInfo.sharePermissions & OC.getCapabilities().files_sharing.default_permissions,
          attributes: JSON.stringify(this.fileInfo.shareAttributes)
        });

        // If we had a password, we need to show it to the user as it was generated
        if (password) {
          share.newPassword = password;
          // Wait for the newly added share
          const component = await new Promise(resolve => {
            this.$emit('add:share', share, resolve);
          });

          // open the menu on the
          // freshly created share component
          component.open = true;
        } else {
          // Else we just add it normally
          this.$emit('add:share', share);
        }
        await this.getRecommendations();
      } catch (error) {
        this.$nextTick(() => {
          // open the dropdown again on error
          this.$refs.select.$children[0].open = true;
        });
        this.query = value.shareWith;
        console.error('Error while adding new share', error);
      } finally {
        this.loading = false;
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcInputField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcInputField.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcDatetimePicker_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDatetimePicker.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDatetimePicker.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDatetimePicker_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcDatetimePicker_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTimePickerNative.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTimePickerNative.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/CircleOutline.vue */ "./node_modules/vue-material-design-icons/CircleOutline.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/Pencil.vue */ "./node_modules/vue-material-design-icons/Pencil.vue");
/* harmony import */ var vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/Email.vue */ "./node_modules/vue-material-design-icons/Email.vue");
/* harmony import */ var vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/Link.vue */ "./node_modules/vue-material-design-icons/Link.vue");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/ShareCircle.vue */ "./node_modules/vue-material-design-icons/ShareCircle.vue");
/* harmony import */ var vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/AccountCircleOutline.vue */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue");
/* harmony import */ var vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue-material-design-icons/Eye.vue */ "./node_modules/vue-material-design-icons/Eye.vue");
/* harmony import */ var vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/Upload.vue */ "./node_modules/vue-material-design-icons/Upload.vue");
/* harmony import */ var vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/MenuDown.vue */ "./node_modules/vue-material-design-icons/MenuDown.vue");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var _utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../utils/GeneratePassword.js */ "./apps/files_sharing/src/utils/GeneratePassword.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
























/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingDetailsTab',
  components: {
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default()),
    NcButton: (_nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_0___default()),
    NcInputField: (_nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_1___default()),
    NcDatetimePicker: (_nextcloud_vue_dist_Components_NcDatetimePicker_js__WEBPACK_IMPORTED_MODULE_3___default()),
    NcDateTimePickerNative: (_nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_4___default()),
    NcCheckboxRadioSwitch: (_nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_5___default()),
    CloseIcon: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    CircleIcon: vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    EditIcon: vue_material_design_icons_Pencil_vue__WEBPACK_IMPORTED_MODULE_8__["default"],
    LinkIcon: vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    GroupIcon: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    ShareIcon: vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    UserIcon: vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    UploadIcon: vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    ViewIcon: vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    MenuDownIcon: vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_17__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_21__["default"], _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_20__["default"], _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_22__["default"]],
  props: {
    shareRequestValue: {
      type: Object,
      required: false
    },
    fileInfo: {
      type: Object,
      required: true
    },
    share: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      writeNoteToRecipientIsChecked: false,
      sharingPermission: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.ALL.toString(),
      revertSharingPermission: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.ALL.toString(),
      setCustomPermissions: false,
      passwordError: false,
      advancedSectionAccordionExpanded: false,
      bundledPermissions: _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS,
      isFirstComponentLoad: true,
      test: false
    };
  },
  computed: {
    title() {
      let title = t('files_sharing', 'Share with ');
      if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_USER) {
        title = title + this.share.shareWithDisplayName;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_LINK) {
        title = t('files_sharing', 'Share link');
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
        title += ` (${t('files_sharing', 'group')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
        title += ` (${t('files_sharing', 'conversation')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE) {
        title += ` (${t('files_sharing', 'remote')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) {
        title += ` (${t('files_sharing', 'remote group')})`;
      } else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GUEST) {
        title += ` (${t('files_sharing', 'guest')})`;
      }
      return title;
    },
    /**
     * Can the sharee edit the shared file ?
     */
    canEdit: {
      get() {
        return this.share.hasUpdatePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isEditChecked: checked
        });
      }
    },
    /**
     * Can the sharee create the shared file ?
     */
    canCreate: {
      get() {
        return this.share.hasCreatePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isCreateChecked: checked
        });
      }
    },
    /**
     * Can the sharee delete the shared file ?
     */
    canDelete: {
      get() {
        return this.share.hasDeletePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isDeleteChecked: checked
        });
      }
    },
    /**
     * Can the sharee reshare the file ?
     */
    canReshare: {
      get() {
        return this.share.hasSharePermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isReshareChecked: checked
        });
      }
    },
    /**
     * Can the sharee download files or only view them ?
     */
    canDownload: {
      get() {
        return this.share.hasDownloadPermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isDownloadChecked: checked
        });
      }
    },
    /**
     * Is this share readable
     * Needed for some federated shares that might have been added from file drop links
     */
    hasRead: {
      get() {
        return this.share.hasReadPermission;
      },
      set(checked) {
        this.updateAtomicPermissions({
          isReadChecked: checked
        });
      }
    },
    /**
     * Does the current share have an expiration date
     *
     * @return {boolean}
     */
    hasExpirationDate: {
      get() {
        return !!this.share.expireDate || this.config.isDefaultInternalExpireDateEnforced;
      },
      set(enabled) {
        this.share.expireDate = enabled ? this.formatDateToString(this.defaultExpiryDate) : '';
      }
    },
    /**
     * Is the current share password protected ?
     *
     * @return {boolean}
     */
    isPasswordProtected: {
      get() {
        return this.config.enforcePasswordForPublicLink || !!this.share.password;
      },
      async set(enabled) {
        // TODO: directly save after generation to make sure the share is always protected
        this.share.password = enabled ? await (0,_utils_GeneratePassword_js__WEBPACK_IMPORTED_MODULE_18__["default"])() : '';
        this.$set(this.share, 'newPassword', this.share.password);
      }
    },
    /**
     * Is the current share a folder ?
     *
     * @return {boolean}
     */
    isFolder() {
      return this.fileInfo.type === 'dir';
    },
    dateMaxEnforced() {
      if (!this.isRemote && this.config.isDefaultInternalExpireDateEnforced) {
        return new Date(new Date().setDate(new Date().getDate() + 1 + this.config.defaultInternalExpireDate));
      } else if (this.config.isDefaultRemoteExpireDateEnforced) {
        return new Date(new Date().setDate(new Date().getDate() + 1 + this.config.defaultRemoteExpireDate));
      }
      return null;
    },
    /**
     * @return {boolean}
     */
    isSetDownloadButtonVisible() {
      const allowedMimetypes = [
      // Office documents
      'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.oasis.opendocument.text', 'application/vnd.oasis.opendocument.spreadsheet', 'application/vnd.oasis.opendocument.presentation'];
      return this.isFolder || allowedMimetypes.includes(this.fileInfo.mimetype);
    },
    isPasswordEnforced() {
      return this.isPublicShare && this.config.enforcePasswordForPublicLink;
    },
    isExpiryDateEnforced() {
      return this.config.isDefaultInternalExpireDateEnforced;
    },
    defaultExpiryDate() {
      if ((this.isGroupShare || this.isUserShare) && this.config.isDefaultInternalExpireDateEnabled) {
        return new Date(this.config.defaultInternalExpirationDate);
      } else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
        return new Date(this.config.defaultRemoteExpireDateEnabled);
      } else if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
        return new Date(this.config.defaultExpirationDate);
      }
      return new Date(new Date().setDate(new Date().getDate() + 1));
    },
    isUserShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_USER;
    },
    isGroupShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP;
    },
    isRemoteShare() {
      return this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP || this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE;
    },
    isNewShare() {
      return this.share.id === null || this.share.id === undefined;
    },
    allowsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
          return true;
        }
      }
      return false;
    },
    hasFileDropPermissions() {
      return this.share.permissions === this.bundledPermissions.FILE_DROP;
    },
    shareButtonText() {
      if (this.isNewShare) {
        return t('file_sharing', 'Save share');
      }
      return t('file_sharing', 'Update share');
    },
    /**
     * Can the sharer set whether the sharee can edit the file ?
     *
     * @return {boolean}
     */
    canSetEdit() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_UPDATE || this.canEdit;
    },
    /**
     * Can the sharer set whether the sharee can create the file ?
     *
     * @return {boolean}
     */
    canSetCreate() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_CREATE || this.canCreate;
    },
    /**
     * Can the sharer set whether the sharee can delete the file ?
     *
     * @return {boolean}
     */
    canSetDelete() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_DELETE || this.canDelete;
    },
    /**
     * Can the sharer set whether the sharee can reshare the file ?
     *
     * @return {boolean}
     */
    canSetReshare() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.sharePermissions & OC.PERMISSION_SHARE || this.canReshare;
    },
    /**
     * Can the sharer set whether the sharee can download the file ?
     *
     * @return {boolean}
     */
    canSetDownload() {
      // If the owner revoked the permission after the resharer granted it
      // the share still has the permission, and the resharer is still
      // allowed to revoke it too (but not to grant it again).
      return this.fileInfo.canDownload() || this.canDownload;
    },
    // if newPassword exists, but is empty, it means
    // the user deleted the original password
    hasUnsavedPassword() {
      return this.share.newPassword !== undefined;
    },
    passwordExpirationTime() {
      if (this.share.passwordExpirationTime === null) {
        return null;
      }
      const expirationTime = moment(this.share.passwordExpirationTime);
      if (expirationTime.diff(moment()) < 0) {
        return false;
      }
      return expirationTime.fromNow();
    },
    /**
     * Is Talk enabled?
     *
     * @return {boolean}
     */
    isTalkEnabled() {
      return OC.appswebroots.spreed !== undefined;
    },
    /**
     * Is it possible to protect the password by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalkAvailable() {
      return this.isPasswordProtected && this.isTalkEnabled;
    },
    /**
     * Is the current share password protected by Talk?
     *
     * @return {boolean}
     */
    isPasswordProtectedByTalk: {
      get() {
        return this.share.sendPasswordByTalk;
      },
      async set(enabled) {
        this.share.sendPasswordByTalk = enabled;
      }
    },
    /**
     * Is the current share an email share ?
     *
     * @return {boolean}
     */
    isEmailShareType() {
      return this.share ? this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL : false;
    },
    canTogglePasswordProtectedByTalkAvailable() {
      if (!this.isPublicShare || !this.isPasswordProtected) {
        // Makes no sense
        return false;
      } else if (this.isEmailShareType && !this.hasUnsavedPassword) {
        // For email shares we need a new password in order to enable or
        // disable
        return false;
      }

      // Anything else should be fine
      return true;
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    },
    customPermissionsList() {
      const perms = [];
      if ((0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.hasPermissions)(this.share.permissions, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.READ)) {
        perms.push('read');
      }
      if ((0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.hasPermissions)(this.share.permissions, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.CREATE)) {
        perms.push('create');
      }
      if ((0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.hasPermissions)(this.share.permissions, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.UPDATE)) {
        perms.push('update');
      }
      if ((0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.hasPermissions)(this.share.permissions, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.DELETE)) {
        perms.push('delete');
      }
      if ((0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.hasPermissions)(this.share.permissions, _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.SHARE)) {
        perms.push('share');
      }
      if (this.share.hasDownloadPermission) {
        perms.push('download');
      }
      const capitalizeFirstAndJoin = array => array.map((item, index) => index === 0 ? item[0].toUpperCase() + item.substring(1) : item).join(', ');
      return capitalizeFirstAndJoin(perms);
    }
  },
  watch: {
    setCustomPermissions(isChecked) {
      if (isChecked) {
        this.sharingPermission = 'custom';
      } else {
        this.sharingPermission = this.revertSharingPermission;
      }
    }
  },
  beforeMount() {
    this.initializePermissions();
    this.initializeAttributes();
    console.debug('shareSentIn', this.share);
    console.debug('config', this.config);
  },
  methods: {
    updateAtomicPermissions({
      isReadChecked = this.hasRead,
      isEditChecked = this.canEdit,
      isCreateChecked = this.canCreate,
      isDeleteChecked = this.canDelete,
      isReshareChecked = this.canReshare,
      isDownloadChecked = this.canDownload
    } = {}) {
      // calc permissions if checked
      const permissions = 0 | (isReadChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.READ : 0) | (isCreateChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.CREATE : 0) | (isDeleteChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.DELETE : 0) | (isEditChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.UPDATE : 0) | (isReshareChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.ATOMIC_PERMISSIONS.SHARE : 0);
      this.share.permissions = permissions;
      if (this.share.hasDownloadPermission !== isDownloadChecked) {
        this.$set(this.share, 'hasDownloadPermission', isDownloadChecked);
      }
    },
    expandCustomPermissions() {
      if (!this.advancedSectionAccordionExpanded) {
        this.advancedSectionAccordionExpanded = true;
      }
      this.toggleCustomPermissions();
    },
    toggleCustomPermissions(selectedPermission) {
      const isCustomPermissions = this.sharingPermission === 'custom';
      this.revertSharingPermission = !isCustomPermissions ? selectedPermission : 'custom';
      this.setCustomPermissions = isCustomPermissions;
    },
    initializeAttributes() {
      if (this.isNewShare) return;
      let hasAdvancedAttributes = false;
      if (this.isValidShareAttribute(this.share.note)) {
        this.writeNoteToRecipientIsChecked = true;
        hasAdvancedAttributes = true;
      }
      if (this.isValidShareAttribute(this.share.password)) {
        hasAdvancedAttributes = true;
      }
      if (this.isValidShareAttribute(this.share.expireDate)) {
        hasAdvancedAttributes = true;
      }
      if (this.isValidShareAttribute(this.share.label)) {
        hasAdvancedAttributes = true;
      }
      if (hasAdvancedAttributes) {
        this.advancedSectionAccordionExpanded = true;
      }
    },
    initializePermissions() {
      if (this.share.share_type) {
        this.share.type = this.share.share_type;
      }
      // shareType 0 (USER_SHARE) would evaluate to zero
      // Hence the use of hasOwnProperty
      if ('shareType' in this.share) {
        this.share.type = this.share.shareType;
      }
      if (this.isNewShare) {
        if (this.isPublicShare) {
          this.sharingPermission = _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.READ_ONLY.toString();
        } else {
          this.sharingPermission = _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.ALL.toString();
        }
      } else {
        if (this.hasCustomPermissions || this.share.setCustomPermissions) {
          this.sharingPermission = 'custom';
          this.advancedSectionAccordionExpanded = true;
          this.setCustomPermissions = true;
        } else {
          this.sharingPermission = this.share.permissions.toString();
        }
      }
    },
    async saveShare() {
      const permissionsAndAttributes = ['permissions', 'attributes', 'note', 'expireDate'];
      const publicShareAttributes = ['label', 'password', 'hideDownload'];
      if (this.isPublicShare) {
        permissionsAndAttributes.push(...publicShareAttributes);
      }
      const sharePermissionsSet = parseInt(this.sharingPermission);
      if (this.setCustomPermissions) {
        this.updateAtomicPermissions();
      } else {
        this.share.permissions = sharePermissionsSet;
      }
      if (!this.isFolder && this.share.permissions === _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.ALL) {
        // It's not possible to create an existing file.
        this.share.permissions = _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_23__.BUNDLED_PERMISSIONS.ALL_FILE;
      }
      if (!this.writeNoteToRecipientIsChecked) {
        this.share.note = '';
      }
      if (this.isPasswordProtected) {
        if (this.isValidShareAttribute(this.share.newPassword)) {
          this.share.password = this.share.newPassword;
          this.$delete(this.share, 'newPassword');
        } else {
          if (this.isPasswordEnforced) {
            this.passwordError = true;
            return;
          }
        }
      } else {
        this.share.password = '';
      }
      if (!this.hasExpirationDate) {
        this.share.expireDate = '';
      }
      if (this.isNewShare) {
        const incomingShare = {
          permissions: this.share.permissions,
          shareType: this.share.type,
          shareWith: this.share.shareWith,
          attributes: this.share.attributes,
          note: this.share.note,
          fileInfo: this.fileInfo
        };
        if (this.hasExpirationDate) {
          incomingShare.expireDate = this.share.expireDate;
        }
        if (this.isPasswordProtected) {
          incomingShare.password = this.share.password;
        }
        const share = await this.addShare(incomingShare, this.fileInfo);
        this.share = share;
        this.$emit('add:share', this.share);
      } else {
        this.queueUpdate(...permissionsAndAttributes);
      }
      this.$emit('close-sharing-details');
    },
    /**
     * Process the new share request
     *
     * @param {Share} share incoming share object
     * @param {object} fileInfo file data
     */
    async addShare(share, fileInfo) {
      console.debug('Adding a new share from the input for', share);
      try {
        const path = (fileInfo.path + '/' + fileInfo.name).replace('//', '/');
        const resultingShare = await this.createShare({
          path,
          shareType: share.shareType,
          shareWith: share.shareWith,
          permissions: share.permissions,
          attributes: JSON.stringify(fileInfo.shareAttributes),
          ...(share.note ? {
            note: share.note
          } : {}),
          ...(share.password ? {
            password: share.password
          } : {}),
          ...(share.expireDate ? {
            expireDate: share.expireDate
          } : {})
        });
        return resultingShare;
      } catch (error) {
        console.error('Error while adding new share', error);
      } finally {
        // this.loading = false // No loader here yet
      }
    },
    async removeShare() {
      await this.onDelete();
      this.$emit('close-sharing-details');
    },
    /**
     * Update newPassword values
     * of share. If password is set but not newPassword
     * then the user did not changed the password
     * If both co-exists, the password have changed and
     * we show it in plain text.
     * Then on submit (or menu close), we sync it.
     *
     * @param {string} password the changed password
     */
    onPasswordChange(password) {
      this.passwordError = !this.isValidShareAttribute(password);
      this.$set(this.share, 'newPassword', password);
    },
    /**
     * Update the password along with "sendPasswordByTalk".
     *
     * If the password was modified the new password is sent; otherwise
     * updating a mail share would fail, as in that case it is required that
     * a new password is set when enabling or disabling
     * "sendPasswordByTalk".
     */
    onPasswordProtectedByTalkChange() {
      if (this.hasUnsavedPassword) {
        this.share.password = this.share.newPassword.trim();
      }
      this.queueUpdate('sendPasswordByTalk', 'password');
    },
    isValidShareAttribute(value) {
      if ([null, undefined].includes(value)) {
        return false;
      }
      if (!(value.trim().length > 0)) {
        return false;
      }
      return true;
    },
    getShareTypeIcon(type) {
      switch (type) {
        case this.SHARE_TYPES.SHARE_TYPE_LINK:
          return vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_10__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_GUEST:
          return vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
        case this.SHARE_TYPES.SHARE_TYPE_GROUP:
          return vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_11__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
          return vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_9__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_CIRCLE:
          return vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_6__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_ROOM:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_12__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_DECK:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_12__["default"];
        case this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_12__["default"];
        default:
          return null;
        // Or a default icon component if needed
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcActionButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/SharingEntryInherited.vue */ "./apps/files_sharing/src/components/SharingEntryInherited.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInherited',
  components: {
    NcActionButton: (_nextcloud_vue_dist_Components_NcActionButton_js__WEBPACK_IMPORTED_MODULE_1___default()),
    SharingEntryInherited: _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    }
  },
  data() {
    return {
      loaded: false,
      loading: false,
      showInheritedShares: false,
      shares: []
    };
  },
  computed: {
    showInheritedSharesIcon() {
      if (this.loading) {
        return 'icon-loading-small';
      }
      if (this.showInheritedShares) {
        return 'icon-triangle-n';
      }
      return 'icon-triangle-s';
    },
    mainTitle() {
      return t('files_sharing', 'Others with access');
    },
    subTitle() {
      return this.showInheritedShares && this.shares.length === 0 ? t('files_sharing', 'No other users with access found') : '';
    },
    toggleTooltip() {
      return this.fileInfo.type === 'dir' ? t('files_sharing', 'Toggle list of others with access to this directory') : t('files_sharing', 'Toggle list of others with access to this file');
    },
    fullPath() {
      const path = `${this.fileInfo.path}/${this.fileInfo.name}`;
      return path.replace('//', '/');
    }
  },
  watch: {
    fileInfo() {
      this.resetState();
    }
  },
  methods: {
    /**
     * Toggle the list view and fetch/reset the state
     */
    toggleInheritedShares() {
      this.showInheritedShares = !this.showInheritedShares;
      if (this.showInheritedShares) {
        this.fetchInheritedShares();
      } else {
        this.resetState();
      }
    },
    /**
     * Fetch the Inherited Shares array
     */
    async fetchInheritedShares() {
      this.loading = true;
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateOcsUrl)('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', {
          path: this.fullPath
        });
        const shares = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_2__["default"].get(url);
        this.shares = shares.data.ocs.data.map(share => new _models_Share_js__WEBPACK_IMPORTED_MODULE_3__["default"](share)).sort((a, b) => b.createdTime - a.createdTime);
        console.info(this.shares);
        this.loaded = true;
      } catch (error) {
        OC.Notification.showTemporary(t('files_sharing', 'Unable to fetch inherited shares'), {
          type: 'error'
        });
      } finally {
        this.loading = false;
      }
    },
    /**
     * Reset current component state
     */
    resetState() {
      this.loaded = false;
      this.loading = false;
      this.showInheritedShares = false;
      this.shares = [];
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      const index = this.shares.findIndex(item => item === share);
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.splice(index, 1);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/SharingEntryLink.vue */ "./apps/files_sharing/src/components/SharingEntryLink.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
// eslint-disable-next-line no-unused-vars




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingLinkList',
  components: {
    SharingEntryLink: _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    shares: {
      type: Array,
      default: () => [],
      required: true
    },
    canReshare: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      canLinkShare: OC.getCapabilities().files_sharing.public.enabled
    };
  },
  computed: {
    /**
     * Do we have link shares?
     * Using this to still show the `new link share`
     * button regardless of mail shares
     *
     * @return {Array}
     */
    hasLinkShares() {
      return this.shares.filter(share => share.type === this.SHARE_TYPES.SHARE_TYPE_LINK).length > 0;
    },
    /**
     * Do we have any link or email shares?
     *
     * @return {boolean}
     */
    hasShares() {
      return this.shares.length > 0;
    }
  },
  methods: {
    /**
     * Add a new share into the link shares list
     * and return the newly created share component
     *
     * @param {Share} share the share to add to the array
     * @param {Function} resolve a function to run after the share is added and its component initialized
     */
    addShare(share, resolve) {
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.unshift(share);
      this.awaitForShare(share, resolve);
    },
    /**
     * Await for next tick and render after the list updated
     * Then resolve with the matched vue component of the
     * provided share object
     *
     * @param {Share} share newly created share
     * @param {Function} resolve a function to execute after
     */
    awaitForShare(share, resolve) {
      this.$nextTick(() => {
        const newShare = this.$children.find(component => component.share === share);
        if (newShare) {
          resolve(newShare);
        }
      });
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      const index = this.shares.findIndex(item => item === share);
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.splice(index, 1);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js&":
/*!***********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../components/SharingEntry.vue */ "./apps/files_sharing/src/components/SharingEntry.vue");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
// eslint-disable-next-line no-unused-vars



/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingList',
  components: {
    SharingEntry: _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_1__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_2__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    shares: {
      type: Array,
      default: () => [],
      required: true
    }
  },
  computed: {
    hasShares() {
      return this.shares.length === 0;
    },
    isUnique() {
      return share => {
        return [...this.shares].filter(item => {
          return share.type === this.SHARE_TYPES.SHARE_TYPE_USER && share.shareWithDisplayName === item.shareWithDisplayName;
        }).length <= 1;
      };
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! nextcloud-vue-collections */ "./node_modules/nextcloud-vue-collections/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.es.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.es.mjs");
/* harmony import */ var _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/ConfigService.js */ "./apps/files_sharing/src/services/ConfigService.js");
/* harmony import */ var _utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/SharedWithMe.js */ "./apps/files_sharing/src/utils/SharedWithMe.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.js");
/* harmony import */ var _mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/ShareTypes.js */ "./apps/files_sharing/src/mixins/ShareTypes.js");
/* harmony import */ var _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../components/SharingEntryInternal.vue */ "./apps/files_sharing/src/components/SharingEntryInternal.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../components/SharingInput.vue */ "./apps/files_sharing/src/components/SharingInput.vue");
/* harmony import */ var _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./SharingInherited.vue */ "./apps/files_sharing/src/views/SharingInherited.vue");
/* harmony import */ var _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ./SharingLinkList.vue */ "./apps/files_sharing/src/views/SharingLinkList.vue");
/* harmony import */ var _SharingList_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./SharingList.vue */ "./apps/files_sharing/src/views/SharingList.vue");
/* harmony import */ var _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./SharingDetailsTab.vue */ "./apps/files_sharing/src/views/SharingDetailsTab.vue");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
















/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingTab',
  components: {
    NcAvatar: (_nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_2___default()),
    CollectionList: nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__.CollectionList,
    SharingEntryInternal: _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    SharingInherited: _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    SharingInput: _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    SharingLinkList: _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    SharingList: _SharingList_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    SharingDetailsTab: _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_15__["default"]
  },
  mixins: [_mixins_ShareTypes_js__WEBPACK_IMPORTED_MODULE_8__["default"]],
  data() {
    return {
      config: new _services_ConfigService_js__WEBPACK_IMPORTED_MODULE_5__["default"](),
      deleteEvent: null,
      error: '',
      expirationInterval: null,
      loading: true,
      fileInfo: null,
      // reshare Share object
      reshare: null,
      sharedWithMe: {},
      shares: [],
      linkShares: [],
      sections: OCA.Sharing.ShareTabSections.getSections(),
      projectsEnabled: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('core', 'projects_enabled', false),
      showSharingDetailsView: false,
      shareDetailsData: {}
    };
  },
  computed: {
    /**
     * Is this share shared with me?
     *
     * @return {boolean}
     */
    isSharedWithMe() {
      return Object.keys(this.sharedWithMe).length > 0;
    },
    canReshare() {
      return !!(this.fileInfo.permissions & OC.PERMISSION_SHARE) || !!(this.reshare && this.reshare.hasSharePermission && this.config.isResharingAllowed);
    }
  },
  methods: {
    /**
     * Update current fileInfo and fetch new data
     *
     * @param {object} fileInfo the current file FileInfo
     */
    async update(fileInfo) {
      this.fileInfo = fileInfo;
      this.resetState();
      this.getShares();
    },
    /**
     * Get the existing shares infos
     */
    async getShares() {
      try {
        this.loading = true;

        // init params
        const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
        const format = 'json';
        // TODO: replace with proper getFUllpath implementation of our own FileInfo model
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');

        // fetch shares
        const fetchShares = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(shareUrl, {
          params: {
            format,
            path,
            reshares: true
          }
        });
        const fetchSharedWithMe = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(shareUrl, {
          params: {
            format,
            path,
            shared_with_me: true
          }
        });

        // wait for data
        const [shares, sharedWithMe] = await Promise.all([fetchShares, fetchSharedWithMe]);
        this.loading = false;

        // process results
        this.processSharedWithMe(sharedWithMe);
        this.processShares(shares);
      } catch (error) {
        if (error.response.data?.ocs?.meta?.message) {
          this.error = error.response.data.ocs.meta.message;
        } else {
          this.error = t('files_sharing', 'Unable to load the shares list');
        }
        this.loading = false;
        console.error('Error loading the shares list', error);
      }
    },
    /**
     * Reset the current view to its default state
     */
    resetState() {
      clearInterval(this.expirationInterval);
      this.loading = true;
      this.error = '';
      this.sharedWithMe = {};
      this.shares = [];
      this.linkShares = [];
      this.showSharingDetailsView = false;
      this.shareDetailsData = {};
    },
    /**
     * Update sharedWithMe.subtitle with the appropriate
     * expiration time left
     *
     * @param {Share} share the sharedWith Share object
     */
    updateExpirationSubtitle(share) {
      const expiration = moment(share.expireDate).unix();
      this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'Expires {relativetime}', {
        relativetime: OC.Util.relativeModifiedDate(expiration * 1000)
      }));

      // share have expired
      if (moment().unix() > expiration) {
        clearInterval(this.expirationInterval);
        // TODO: clear ui if share is expired
        this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'this share just expired.'));
      }
    },
    /**
     * Process the current shares data
     * and init shares[]
     *
     * @param {object} share the share ocs api request data
     * @param {object} share.data the request data
     */
    processShares({
      data
    }) {
      if (data.ocs && data.ocs.data && data.ocs.data.length > 0) {
        // create Share objects and sort by newest
        const shares = data.ocs.data.map(share => new _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"](share)).sort((a, b) => b.createdTime - a.createdTime);
        this.linkShares = shares.filter(share => share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL);
        this.shares = shares.filter(share => share.type !== this.SHARE_TYPES.SHARE_TYPE_LINK && share.type !== this.SHARE_TYPES.SHARE_TYPE_EMAIL);
        console.debug('Processed', this.linkShares.length, 'link share(s)');
        console.debug('Processed', this.shares.length, 'share(s)');
      }
    },
    /**
     * Process the sharedWithMe share data
     * and init sharedWithMe
     *
     * @param {object} share the share ocs api request data
     * @param {object} share.data the request data
     */
    processSharedWithMe({
      data
    }) {
      if (data.ocs && data.ocs.data && data.ocs.data[0]) {
        const share = new _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"](data);
        const title = (0,_utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_6__.shareWithTitle)(share);
        const displayName = share.ownerDisplayName;
        const user = share.owner;
        this.sharedWithMe = {
          displayName,
          title,
          user
        };
        this.reshare = share;

        // If we have an expiration date, use it as subtitle
        // Refresh the status every 10s and clear if expired
        if (share.expireDate && moment(share.expireDate).unix() > moment().unix()) {
          // first update
          this.updateExpirationSubtitle(share);
          // interval update
          this.expirationInterval = setInterval(this.updateExpirationSubtitle, 10000, share);
        }
      } else if (this.fileInfo && this.fileInfo.shareOwnerId !== undefined ? this.fileInfo.shareOwnerId !== OC.currentUser : false) {
        // Fallback to compare owner and current user.
        this.sharedWithMe = {
          displayName: this.fileInfo.shareOwner,
          title: t('files_sharing', 'Shared with you by {owner}', {
            owner: this.fileInfo.shareOwner
          }, undefined, {
            escape: false
          }),
          user: this.fileInfo.shareOwnerId
        };
      }
    },
    /**
     * Add a new share into the shares list
     * and return the newly created share component
     *
     * @param {Share} share the share to add to the array
     * @param {Function} [resolve] a function to run after the share is added and its component initialized
     */
    addShare(share, resolve = () => {}) {
      // only catching share type MAIL as link shares are added differently
      // meaning: not from the ShareInput
      if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
        this.linkShares.unshift(share);
      } else {
        this.shares.unshift(share);
      }
      this.awaitForShare(share, resolve);
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      // Get reference for this.linkShares or this.shares
      const shareList = share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL || share.type === this.SHARE_TYPES.SHARE_TYPE_LINK ? this.linkShares : this.shares;
      const index = shareList.findIndex(item => item.id === share.id);
      if (index !== -1) {
        shareList.splice(index, 1);
      }
    },
    /**
     * Await for next tick and render after the list updated
     * Then resolve with the matched vue component of the
     * provided share object
     *
     * @param {Share} share newly created share
     * @param {Function} resolve a function to execute after
     */
    awaitForShare(share, resolve) {
      let listComponent = this.$refs.shareList;
      // Only mail shares comes from the input, link shares
      // are managed internally in the SharingLinkList component
      if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
        listComponent = this.$refs.linkShareList;
      }
      this.$nextTick(() => {
        const newShare = listComponent.$children.find(component => component.share === share);
        if (newShare) {
          resolve(newShare);
        }
      });
    },
    toggleShareDetailsView(eventData) {
      if (eventData) {
        this.shareDetailsData = eventData;
      }
      this.showSharingDetailsView = !this.showSharingDetailsView;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.data.is, _vm._g(_vm._b({
    tag: "Component"
  }, "Component", _vm.data, false), _vm.action.handlers), [_vm._v("\n\t" + _vm._s(_vm.data.text) + "\n")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry"
  }, [_c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": _vm.share.type !== _vm.SHARE_TYPES.SHARE_TYPE_USER,
      user: _vm.share.shareWith,
      "display-name": _vm.share.shareWithDisplayName,
      "menu-position": "left",
      url: _vm.share.shareWithAvatar
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__summary",
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.toggleQuickShareSelect.apply(null, arguments);
      }
    }
  }, [_c(_vm.share.shareWithLink ? "a" : "div", {
    tag: "component",
    staticClass: "sharing-entry__summary__desc",
    attrs: {
      title: _vm.tooltip,
      "aria-label": _vm.tooltip,
      href: _vm.share.shareWithLink
    }
  }, [_c("span", [_vm._v(_vm._s(_vm.title) + "\n\t\t\t\t"), !_vm.isUnique ? _c("span", {
    staticClass: "sharing-entry__summary__desc-unique"
  }, [_vm._v(" (" + _vm._s(_vm.share.shareWithDisplayNameUnique) + ")")]) : _vm._e(), _vm._v(" "), _vm.hasStatus && _vm.share.status.message ? _c("small", [_vm._v("(" + _vm._s(_vm.share.status.message) + ")")]) : _vm._e()])]), _vm._v(" "), _c("QuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo,
      toggle: _vm.showDropdown
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  })], 1), _vm._v(" "), _c("NcButton", {
    staticClass: "sharing-entry__action",
    attrs: {
      "aria-label": _vm.t("files_sharing", "Open Sharing Details"),
      type: "tertiary-no-background"
    },
    on: {
      click: function ($event) {
        return _vm.openSharingDetails(_vm.share);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DotsHorizontalIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("SharingEntrySimple", {
    key: _vm.share.id,
    staticClass: "sharing-entry__inherited",
    attrs: {
      title: _vm.share.shareWithDisplayName
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("NcAvatar", {
          staticClass: "sharing-entry__avatar",
          attrs: {
            user: _vm.share.shareWith,
            "display-name": _vm.share.shareWithDisplayName
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionText", {
    attrs: {
      icon: "icon-user"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Added by {initiator}", {
    initiator: _vm.share.ownerDisplayName
  })) + "\n\t")]), _vm._v(" "), _vm.share.viaPath && _vm.share.viaFileid ? _c("NcActionLink", {
    attrs: {
      icon: "icon-folder",
      href: _vm.viaFileTargetUrl
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Via “{folder}”", {
    folder: _vm.viaFolderName
  })) + "\n\t")]) : _vm._e(), _vm._v(" "), _vm.share.canDelete ? _c("NcActionButton", {
    attrs: {
      icon: "icon-close"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.onDelete.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "Unshare")) + "\n\t")]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", [_c("SharingEntrySimple", {
    ref: "shareEntrySimple",
    staticClass: "sharing-entry__internal",
    attrs: {
      title: _vm.t("files_sharing", "Internal link"),
      subtitle: _vm.internalLinkSubtitle
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("div", {
          staticClass: "avatar-external icon-external-white"
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionLink", {
    attrs: {
      href: _vm.internalLink,
      "aria-label": _vm.copyLinkTooltip,
      title: _vm.copyLinkTooltip,
      target: "_blank",
      icon: _vm.copied && _vm.copySuccess ? "icon-checkmark-color" : "icon-clippy"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.copyLink.apply(null, arguments);
      }
    }
  })], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry sharing-entry__link",
    class: {
      "sharing-entry--share": _vm.share
    }
  }, [_c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": true,
      "icon-class": _vm.isEmailShareType ? "avatar-link-share icon-mail-white" : "avatar-link-share icon-public-white"
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__summary"
  }, [_c("div", {
    staticClass: "sharing-entry__desc",
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.toggleQuickShareSelect.apply(null, arguments);
      }
    }
  }, [_c("span", {
    staticClass: "sharing-entry__title",
    attrs: {
      title: _vm.title
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.title) + "\n\t\t\t")]), _vm._v(" "), _vm.subtitle ? _c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.subtitle) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share && _vm.share.permissions !== undefined ? _c("QuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo,
      toggle: _vm.showDropdown
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  }) : _vm._e()], 1), _vm._v(" "), _vm.share && !_vm.isEmailShareType && _vm.share.token ? _c("NcActions", {
    ref: "copyButton",
    staticClass: "sharing-entry__copy"
  }, [_c("NcActionLink", {
    attrs: {
      href: _vm.shareLink,
      target: "_blank",
      title: _vm.copyLinkTooltip,
      "aria-label": _vm.copyLinkTooltip,
      icon: _vm.copied && _vm.copySuccess ? "icon-checkmark-color" : "icon-clippy"
    },
    on: {
      click: function ($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.copyLink.apply(null, arguments);
      }
    }
  })], 1) : _vm._e()], 1), _vm._v(" "), !_vm.pending && (_vm.pendingPassword || _vm.pendingEnforcedPassword || _vm.pendingExpirationDate) ? _c("NcActions", {
    staticClass: "sharing-entry__actions",
    attrs: {
      "aria-label": _vm.actionsTooltip,
      "menu-align": "right",
      open: _vm.open
    },
    on: {
      "update:open": function ($event) {
        _vm.open = $event;
      },
      close: _vm.onNewLinkShare
    }
  }, [_vm.errors.pending ? _c("NcActionText", {
    class: {
      error: _vm.errors.pending
    },
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.errors.pending) + "\n\t\t")]) : _c("NcActionText", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Please enter the following required information before creating the share")) + "\n\t\t")]), _vm._v(" "), _vm.pendingEnforcedPassword ? _c("NcActionText", {
    attrs: {
      icon: "icon-password"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Password protection (enforced)")) + "\n\t\t")]) : _vm.pendingPassword ? _c("NcActionCheckbox", {
    staticClass: "share-link-password-checkbox",
    attrs: {
      checked: _vm.isPasswordProtected,
      disabled: _vm.config.enforcePasswordForPublicLink || _vm.saving
    },
    on: {
      "update:checked": function ($event) {
        _vm.isPasswordProtected = $event;
      },
      uncheck: _vm.onPasswordDisable
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Password protection")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingEnforcedPassword || _vm.share.password ? _c("NcActionInput", {
    staticClass: "share-link-password",
    attrs: {
      value: _vm.share.password,
      disabled: _vm.saving,
      required: _vm.config.enableLinkPasswordByDefault || _vm.config.enforcePasswordForPublicLink,
      minlength: _vm.isPasswordPolicyEnabled && _vm.config.passwordPolicy.minLength,
      icon: "",
      autocomplete: "new-password"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.share, "password", $event);
      },
      submit: _vm.onNewLinkShare
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Enter a password")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingExpirationDate ? _c("NcActionText", {
    attrs: {
      icon: "icon-calendar-dark"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Expiration date (enforced)")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingExpirationDate ? _c("NcActionInput", {
    staticClass: "share-link-expire-date",
    attrs: {
      disabled: _vm.saving,
      "is-native-picker": true,
      "hide-label": true,
      value: new Date(_vm.share.expireDate),
      type: "date",
      min: _vm.dateTomorrow,
      max: _vm.dateMaxEnforced
    },
    on: {
      input: _vm.onExpirationChange
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Enter a date")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: "icon-checkmark"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Create share")) + "\n\t\t")]), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: "icon-close"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onCancel.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Cancel")) + "\n\t\t")])], 1) : !_vm.loading ? _c("NcActions", {
    staticClass: "sharing-entry__actions",
    attrs: {
      "aria-label": _vm.actionsTooltip,
      "menu-align": "right",
      open: _vm.open
    },
    on: {
      "update:open": function ($event) {
        _vm.open = $event;
      },
      close: _vm.onMenuClose
    }
  }, [_vm.share ? [_vm.share.canEdit && _vm.canReshare ? [_c("NcActionButton", {
    attrs: {
      disabled: _vm.saving
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.openSharingDetails.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("Tune")];
      },
      proxy: true
    }], null, false, 961531849)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Customize link")) + "\n\t\t\t\t")])] : _vm._e(), _vm._v(" "), _c("NcActionSeparator"), _vm._v(" "), _vm._l(_vm.externalLinkActions, function (action) {
    return _c("ExternalShareAction", {
      key: action.id,
      attrs: {
        id: action.id,
        action: action,
        "file-info": _vm.fileInfo,
        share: _vm.share
      }
    });
  }), _vm._v(" "), _vm._l(_vm.externalLegacyLinkActions, function ({
    icon,
    url,
    name
  }, index) {
    return _c("NcActionLink", {
      key: index,
      attrs: {
        href: url(_vm.shareLink),
        icon: icon,
        target: "_blank"
      }
    }, [_vm._v("\n\t\t\t\t" + _vm._s(name) + "\n\t\t\t")]);
  }), _vm._v(" "), !_vm.isEmailShareType && _vm.canReshare ? _c("NcActionButton", {
    staticClass: "new-share-link",
    attrs: {
      icon: "icon-add"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Add another link")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share.canDelete ? _c("NcActionButton", {
    attrs: {
      icon: "icon-close",
      disabled: _vm.saving
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.onDelete.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Unshare")) + "\n\t\t\t")]) : _vm._e()] : _vm.canReshare ? _c("NcActionButton", {
    staticClass: "new-share-link",
    attrs: {
      title: _vm.t("files_sharing", "Create a new share link"),
      "aria-label": _vm.t("files_sharing", "Create a new share link"),
      icon: _vm.loading ? "icon-loading-small" : "icon-add"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    }
  }) : _vm._e()], 2) : _c("div", {
    staticClass: "icon-loading-small sharing-entry__loading"
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    ref: "quickShareDropdownContainer",
    class: {
      active: _vm.showDropdown,
      "share-select": true
    }
  }, [_c("span", {
    staticClass: "trigger-text",
    attrs: {
      id: _vm.dropdownId,
      "aria-expanded": _vm.showDropdown,
      "aria-haspopup": true,
      "aria-label": "Quick share options dropdown"
    },
    on: {
      click: _vm.toggleDropdown
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.selectedOption) + "\n\t\t"), _c("DropdownIcon", {
    attrs: {
      size: 15
    }
  })], 1), _vm._v(" "), _vm.showDropdown ? _c("div", {
    ref: "quickShareDropdown",
    staticClass: "share-select-dropdown",
    attrs: {
      "aria-labelledby": _vm.dropdownId,
      tabindex: "0"
    },
    on: {
      keydown: [function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "down", 40, $event.key, ["Down", "ArrowDown"])) return null;
        return _vm.handleArrowDown.apply(null, arguments);
      }, function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "up", 38, $event.key, ["Up", "ArrowUp"])) return null;
        return _vm.handleArrowUp.apply(null, arguments);
      }, function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "esc", 27, $event.key, ["Esc", "Escape"])) return null;
        return _vm.closeDropdown.apply(null, arguments);
      }]
    }
  }, _vm._l(_vm.options, function (option) {
    return _c("button", {
      key: option,
      class: {
        "dropdown-item": true,
        selected: option === _vm.selectedOption
      },
      attrs: {
        "aria-selected": option === _vm.selectedOption
      },
      on: {
        click: function ($event) {
          return _vm.selectOption(option);
        }
      }
    }, [_vm._v("\n\t\t\t" + _vm._s(option) + "\n\t\t")]);
  }), 0) : _vm._e()]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("li", {
    staticClass: "sharing-entry"
  }, [_vm._t("avatar"), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__desc"
  }, [_c("span", {
    staticClass: "sharing-entry__title"
  }, [_vm._v(_vm._s(_vm.title))]), _vm._v(" "), _vm.subtitle ? _c("p", [_vm._v("\n\t\t\t" + _vm._s(_vm.subtitle) + "\n\t\t")]) : _vm._e()]), _vm._v(" "), _vm.$slots["default"] ? _c("NcActions", {
    ref: "actionsComponent",
    staticClass: "sharing-entry__actions",
    attrs: {
      "menu-align": "right",
      "aria-expanded": _vm.ariaExpandedValue
    }
  }, [_vm._t("default")], 2) : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c&":
/*!****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharing-search"
  }, [_c("label", {
    attrs: {
      for: "sharing-search-input"
    }
  }, [_vm._v(_vm._s(_vm.t("files_sharing", "Search for share recipients")))]), _vm._v(" "), _c("NcSelect", {
    ref: "select",
    staticClass: "sharing-search__input",
    attrs: {
      "input-id": "sharing-search-input",
      disabled: !_vm.canReshare,
      loading: _vm.loading,
      filterable: false,
      placeholder: _vm.inputPlaceholder,
      "clear-search-on-blur": () => false,
      "user-select": true,
      options: _vm.options
    },
    on: {
      search: _vm.asyncFind,
      "option:selected": _vm.openSharingDetails
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function ({
        search
      }) {
        return [_vm._v("\n\t\t\t" + _vm._s(search ? _vm.noResultText : _vm.t("files_sharing", "No recommendations. Start typing.")) + "\n\t\t")];
      }
    }]),
    model: {
      value: _vm.value,
      callback: function ($$v) {
        _vm.value = $$v;
      },
      expression: "value"
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharingTabDetailsView"
  }, [_c("div", {
    staticClass: "sharingTabDetailsView__header"
  }, [_c("span", [_vm.isUserShare ? _c("NcAvatar", {
    staticClass: "sharing-entry__avatar",
    attrs: {
      "is-no-user": _vm.share.shareType !== _vm.SHARE_TYPES.SHARE_TYPE_USER,
      user: _vm.share.shareWith,
      "display-name": _vm.share.shareWithDisplayName,
      "menu-position": "left",
      url: _vm.share.shareWithAvatar
    }
  }) : _vm._e(), _vm._v(" "), _c(_vm.getShareTypeIcon(_vm.share.type), {
    tag: "component",
    attrs: {
      size: 32
    }
  })], 1), _vm._v(" "), _c("span", [_c("h1", [_vm._v(_vm._s(_vm.title))])])]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__quick-permissions"
  }, [_c("div", [_c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.READ_ONLY.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("ViewIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "View only")) + "\n\t\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.ALL.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("EditIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow upload and editing")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.allowsFileDrop ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      checked: _vm.sharingPermission,
      value: _vm.bundledPermissions.FILE_DROP.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.toggleCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("UploadIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1083194048)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "File drop")) + "\n\t\t\t\t"), _c("small", [_vm._v(_vm._s(_vm.t("files_sharing", "Upload only")))])]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      checked: _vm.sharingPermission,
      value: "custom",
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:checked": [function ($event) {
        _vm.sharingPermission = $event;
      }, _vm.expandCustomPermissions]
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DotsHorizontalIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Custom permissions")) + "\n\t\t\t\t"), _c("small", [_vm._v(_vm._s(_vm.t("files_sharing", _vm.customPermissionsList)))])])], 1)]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__advanced-control"
  }, [_c("NcButton", {
    attrs: {
      type: "tertiary",
      alignment: "end-reverse"
    },
    on: {
      click: function ($event) {
        _vm.advancedSectionAccordionExpanded = !_vm.advancedSectionAccordionExpanded;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("MenuDownIcon")];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Advanced settings")) + "\n\t\t\t")])], 1), _vm._v(" "), _vm.advancedSectionAccordionExpanded ? _c("div", {
    staticClass: "sharingTabDetailsView__advanced"
  }, [_c("section", [_vm.isPublicShare ? _c("NcInputField", {
    attrs: {
      value: _vm.share.label,
      type: "text",
      label: _vm.t("file_sharing", "Share label")
    },
    on: {
      "update:value": function ($event) {
        return _vm.$set(_vm.share, "label", $event);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? [_c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.isPasswordProtected,
      disabled: _vm.isPasswordEnforced
    },
    on: {
      "update:checked": function ($event) {
        _vm.isPasswordProtected = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Set password")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.isPasswordProtected ? _c("NcInputField", {
    attrs: {
      type: _vm.hasUnsavedPassword ? "text" : "password",
      value: _vm.hasUnsavedPassword ? _vm.share.newPassword : "***************",
      error: _vm.passwordError,
      required: _vm.isPasswordEnforced,
      label: _vm.t("file_sharing", "Password")
    },
    on: {
      "update:value": _vm.onPasswordChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isEmailShareType && _vm.passwordExpirationTime ? _c("span", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expires {passwordExpirationTime}", {
    passwordExpirationTime: _vm.passwordExpirationTime
  })) + "\n\t\t\t\t")]) : _vm.isEmailShareType && _vm.passwordExpirationTime !== null ? _c("span", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expired")) + "\n\t\t\t\t")]) : _vm._e()] : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.hasExpirationDate,
      disabled: _vm.isExpiryDateEnforced
    },
    on: {
      "update:checked": function ($event) {
        _vm.hasExpirationDate = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.isExpiryDateEnforced ? _vm.t("files_sharing", "Expiration date (enforced)") : _vm.t("files_sharing", "Set expiration date")) + "\n\t\t\t")]), _vm._v(" "), _vm.hasExpirationDate ? _c("NcDateTimePickerNative", {
    attrs: {
      id: "share-date-picker",
      value: new Date(_vm.share.expireDate),
      min: _vm.dateTomorrow,
      max: _vm.dateMaxEnforced,
      "hide-label": true,
      disabled: _vm.isExpiryDateEnforced,
      placeholder: _vm.t("file_sharing", "Expiration date"),
      type: "date"
    },
    on: {
      input: _vm.onExpirationChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: _vm.canChangeHideDownload,
      checked: _vm.share.hideDownload
    },
    on: {
      "update:checked": [function ($event) {
        return _vm.$set(_vm.share, "hideDownload", $event);
      }, function ($event) {
        return _vm.queueUpdate("hideDownload");
      }]
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Hide download")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.canTogglePasswordProtectedByTalkAvailable ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.isPasswordProtectedByTalk
    },
    on: {
      "update:checked": [function ($event) {
        _vm.isPasswordProtectedByTalk = $event;
      }, _vm.onPasswordProtectedByTalkChange]
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Video verification")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.writeNoteToRecipientIsChecked
    },
    on: {
      "update:checked": function ($event) {
        _vm.writeNoteToRecipientIsChecked = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Note to recipient")) + "\n\t\t\t")]), _vm._v(" "), _vm.writeNoteToRecipientIsChecked ? [_c("textarea", {
    domProps: {
      value: _vm.share.note
    },
    on: {
      input: function ($event) {
        _vm.share.note = $event.target.value;
      }
    }
  })] : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      checked: _vm.setCustomPermissions
    },
    on: {
      "update:checked": function ($event) {
        _vm.setCustomPermissions = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Custom permissions")) + "\n\t\t\t")]), _vm._v(" "), _vm.setCustomPermissions ? _c("section", {
    staticClass: "custom-permissions-group"
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.allowsFileDrop && _vm.share.type === _vm.SHARE_TYPES.SHARE_TYPE_LINK,
      checked: _vm.hasRead
    },
    on: {
      "update:checked": function ($event) {
        _vm.hasRead = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Read")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.isFolder ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetCreate,
      checked: _vm.canCreate
    },
    on: {
      "update:checked": function ($event) {
        _vm.canCreate = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Create")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetEdit,
      checked: _vm.canEdit
    },
    on: {
      "update:checked": function ($event) {
        _vm.canEdit = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Update")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.config.isResharingAllowed && _vm.share.type !== _vm.SHARE_TYPES.SHARE_TYPE_LINK ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetReshare,
      checked: _vm.canReshare
    },
    on: {
      "update:checked": function ($event) {
        _vm.canReshare = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Share")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), !_vm.isPublicShare ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDownload,
      checked: _vm.canDownload
    },
    on: {
      "update:checked": function ($event) {
        _vm.canDownload = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Download")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDelete,
      checked: _vm.canDelete
    },
    on: {
      "update:checked": function ($event) {
        _vm.canDelete = $event;
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Delete")) + "\n\t\t\t\t")])], 1) : _vm._e()], 2)]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__delete"
  }, [!_vm.isNewShare ? _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("files_sharing", "Delete share"),
      disabled: false,
      readonly: false,
      type: "tertiary"
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.removeShare.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 16
          }
        })];
      },
      proxy: true
    }], null, false, 2746485232)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Delete share")) + "\n\t\t")]) : _vm._e()], 1), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__footer"
  }, [_c("div", {
    staticClass: "button-group"
  }, [_c("NcButton", {
    on: {
      click: function ($event) {
        return _vm.$emit("close-sharing-details");
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("file_sharing", "Cancel")) + "\n\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      type: "primary"
    },
    on: {
      click: _vm.saveShare
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.shareButtonText) + "\n\t\t\t")])], 1)])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", {
    attrs: {
      id: "sharing-inherited-shares"
    }
  }, [_c("SharingEntrySimple", {
    staticClass: "sharing-entry__inherited",
    attrs: {
      title: _vm.mainTitle,
      subtitle: _vm.subTitle,
      "aria-expanded": _vm.showInheritedShares
    },
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("div", {
          staticClass: "avatar-shared icon-more-white"
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: _vm.showInheritedSharesIcon,
      "aria-label": _vm.toggleTooltip,
      title: _vm.toggleTooltip
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.toggleInheritedShares.apply(null, arguments);
      }
    }
  })], 1), _vm._v(" "), _vm._l(_vm.shares, function (share) {
    return _c("SharingEntryInherited", {
      key: share.id,
      attrs: {
        "file-info": _vm.fileInfo,
        share: share
      },
      on: {
        "remove:share": _vm.removeShare
      }
    });
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84&":
/*!**************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _vm.canLinkShare ? _c("ul", {
    staticClass: "sharing-link-list"
  }, [!_vm.hasLinkShares && _vm.canReshare ? _c("SharingEntryLink", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo
    },
    on: {
      "add:share": _vm.addShare
    }
  }) : _vm._e(), _vm._v(" "), _vm.hasShares ? _vm._l(_vm.shares, function (share, index) {
    return _c("SharingEntryLink", {
      key: share.id,
      attrs: {
        index: _vm.shares.length > 1 ? index + 1 : null,
        "can-reshare": _vm.canReshare,
        share: _vm.shares[index],
        "file-info": _vm.fileInfo
      },
      on: {
        "update:share": [function ($event) {
          return _vm.$set(_vm.shares, index, $event);
        }, function ($event) {
          return _vm.awaitForShare(...arguments);
        }],
        "add:share": function ($event) {
          return _vm.addShare(...arguments);
        },
        "remove:share": _vm.removeShare,
        "open-sharing-details": function ($event) {
          return _vm.openSharingDetails(share);
        }
      }
    });
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", {
    staticClass: "sharing-sharee-list"
  }, _vm._l(_vm.shares, function (share) {
    return _c("SharingEntry", {
      key: share.id,
      attrs: {
        "file-info": _vm.fileInfo,
        share: share,
        "is-unique": _vm.isUnique(share)
      },
      on: {
        "open-sharing-details": function ($event) {
          return _vm.openSharingDetails(share);
        }
      }
    });
  }), 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    class: {
      "icon-loading": _vm.loading
    }
  }, [_vm.error ? _c("div", {
    staticClass: "emptycontent",
    class: {
      emptyContentWithSections: _vm.sections.length > 0
    }
  }, [_c("div", {
    staticClass: "icon icon-error"
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.error))])]) : _vm._e(), _vm._v(" "), !_vm.showSharingDetailsView ? [_c("div", {
    staticClass: "sharingTab__content"
  }, [_vm.isSharedWithMe ? _c("SharingEntrySimple", _vm._b({
    staticClass: "sharing-entry__reshare",
    scopedSlots: _vm._u([{
      key: "avatar",
      fn: function () {
        return [_c("NcAvatar", {
          staticClass: "sharing-entry__avatar",
          attrs: {
            user: _vm.sharedWithMe.user,
            "display-name": _vm.sharedWithMe.displayName
          }
        })];
      },
      proxy: true
    }], null, false, 3197855346)
  }, "SharingEntrySimple", _vm.sharedWithMe, false)) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingInput", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      "link-shares": _vm.linkShares,
      reshare: _vm.reshare,
      shares: _vm.shares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingLinkList", {
    ref: "linkShareList",
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      shares: _vm.linkShares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingList", {
    ref: "shareList",
    attrs: {
      shares: _vm.shares,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), _vm.canReshare && !_vm.loading ? _c("SharingInherited", {
    attrs: {
      "file-info": _vm.fileInfo
    }
  }) : _vm._e(), _vm._v(" "), _c("SharingEntryInternal", {
    attrs: {
      "file-info": _vm.fileInfo
    }
  }), _vm._v(" "), _vm.projectsEnabled && _vm.fileInfo ? _c("CollectionList", {
    attrs: {
      id: `${_vm.fileInfo.id}`,
      type: "file",
      name: _vm.fileInfo.name
    }
  }) : _vm._e()], 1), _vm._v(" "), _vm._l(_vm.sections, function (section, index) {
    return _c("div", {
      key: index,
      ref: "section-" + index,
      refInFor: true,
      staticClass: "sharingTab__additionalContent"
    }, [_c(section(_vm.$refs["section-" + index], _vm.fileInfo), {
      tag: "component",
      attrs: {
        "file-info": _vm.fileInfo
      }
    })], 1);
  })] : _c("div", [_c("SharingDetailsTab", {
    attrs: {
      "file-info": _vm.shareDetailsData.fileInfo,
      share: _vm.shareDetailsData.share
    },
    on: {
      "close-sharing-details": _vm.toggleShareDetailsView,
      "add:share": _vm.addShare,
      "remove:share": _vm.removeShare
    }
  })], 1)], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-61240f7a] {
  display: flex;
  align-items: center;
  height: 44px;
}
.sharing-entry__summary[data-v-61240f7a] {
  padding: 8px;
  padding-left: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  width: 80%;
  min-width: 80%;
}
.sharing-entry__summary__desc[data-v-61240f7a] {
  display: inline-block;
  padding-bottom: 0;
  line-height: 1.2em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sharing-entry__summary__desc p[data-v-61240f7a],
.sharing-entry__summary__desc small[data-v-61240f7a] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__summary__desc-unique[data-v-61240f7a] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-06bd31b0] {
  display: flex;
  align-items: center;
  height: 44px;
}
.sharing-entry__desc[data-v-06bd31b0] {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 8px;
  padding-left: 10px;
  line-height: 1.2em;
}
.sharing-entry__desc p[data-v-06bd31b0] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__actions[data-v-06bd31b0] {
  margin-left: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry__internal .avatar-external[data-v-f55cfc52] {
  width: 32px;
  height: 32px;
  line-height: 32px;
  font-size: 18px;
  background-color: var(--color-text-maxcontrast);
  border-radius: 50%;
  flex-shrink: 0;
}
.sharing-entry__internal .icon-checkmark-color[data-v-f55cfc52] {
  opacity: 1;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-7a675594] {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.sharing-entry__summary[data-v-7a675594] {
  padding: 8px;
  padding-left: 10px;
  display: flex;
  justify-content: space-between;
  width: 80%;
  min-width: 80%;
}
.sharing-entry__summary__desc[data-v-7a675594] {
  display: flex;
  flex-direction: column;
  line-height: 1.2em;
}
.sharing-entry__summary__desc p[data-v-7a675594] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__summary__desc__title[data-v-7a675594] {
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.sharing-entry:not(.sharing-entry--share) .sharing-entry__actions .new-share-link[data-v-7a675594] {
  border-top: 1px solid var(--color-border);
}
.sharing-entry[data-v-7a675594] .avatar-link-share {
  background-color: var(--color-primary-element);
}
.sharing-entry .sharing-entry__action--public-upload[data-v-7a675594] {
  border-bottom: 1px solid var(--color-border);
}
.sharing-entry__loading[data-v-7a675594] {
  width: 44px;
  height: 44px;
  margin: 0;
  padding: 14px;
  margin-left: auto;
}
.sharing-entry .action-item ~ .action-item[data-v-7a675594],
.sharing-entry .action-item ~ .sharing-entry__loading[data-v-7a675594] {
  margin-left: 0;
}
.sharing-entry .icon-checkmark-color[data-v-7a675594] {
  opacity: 1;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.share-select[data-v-62b9dbb0] {
  position: relative;
  cursor: pointer;
  /* Optional: Add a transition effect for smoother dropdown animation */
}
.share-select .trigger-text[data-v-62b9dbb0] {
  display: flex;
  flex-direction: row;
  align-items: center;
  font-size: 12.5px;
  gap: 2px;
  color: var(--color-primary-element);
}
.share-select .share-select-dropdown[data-v-62b9dbb0] {
  position: absolute;
  display: flex;
  flex-direction: column;
  top: 100%;
  left: 0;
  background-color: var(--color-main-background);
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  padding: 4px 0;
  z-index: 1;
}
.share-select .share-select-dropdown .dropdown-item[data-v-62b9dbb0] {
  padding: 8px;
  font-size: 12px;
  background: none;
  border: none;
  border-radius: 0;
  font: inherit;
  cursor: pointer;
  color: inherit;
  outline: none;
  width: 100%;
  white-space: nowrap;
  text-align: left;
}
.share-select .share-select-dropdown .dropdown-item[data-v-62b9dbb0]:hover {
  background-color: #f2f2f2;
}
.share-select .share-select-dropdown .dropdown-item.selected[data-v-62b9dbb0] {
  background-color: #f0f0f0;
}
.share-select .share-select-dropdown[data-v-62b9dbb0] {
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.3s ease;
}
.share-select.active .share-select-dropdown[data-v-62b9dbb0] {
  max-height: 200px;
  /* Adjust the value to your desired height */
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry[data-v-354542cc] {
  display: flex;
  align-items: center;
  min-height: 44px;
}
.sharing-entry__desc[data-v-354542cc] {
  padding: 8px;
  padding-left: 10px;
  line-height: 1.2em;
  position: relative;
  flex: 1 1;
  min-width: 0;
}
.sharing-entry__desc p[data-v-354542cc] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__title[data-v-354542cc] {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: inherit;
}
.sharing-entry__actions[data-v-354542cc] {
  margin-left: auto !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-search {
  display: flex;
  flex-direction: column;
  margin-bottom: 4px;
}
.sharing-search label[for=sharing-search-input] {
  margin-bottom: 2px;
}
.sharing-search__input {
  width: 100%;
  margin: 10px 0;
}
.vs__dropdown-menu span[lookup] .avatardiv {
  background-image: var(--icon-search-white);
  background-repeat: no-repeat;
  background-position: center;
  background-color: var(--color-text-maxcontrast) !important;
}
.vs__dropdown-menu span[lookup] .avatardiv div {
  display: none;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharingTabDetailsView[data-v-b968620e] {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  width: 96%;
  margin: 0 auto;
}
.sharingTabDetailsView__header[data-v-b968620e] {
  display: flex;
  align-items: center;
  box-sizing: border-box;
  margin: 0.2em;
}
.sharingTabDetailsView__header span[data-v-b968620e] {
  display: flex;
  align-items: center;
}
.sharingTabDetailsView__header span h1[data-v-b968620e] {
  font-size: 15px;
  padding-left: 0.3em;
}
.sharingTabDetailsView__quick-permissions[data-v-b968620e] {
  display: flex;
  justify-content: center;
  margin-bottom: 0.2em;
  width: 100%;
  margin: 0 auto;
  border-radius: 0;
}
.sharingTabDetailsView__quick-permissions div[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__quick-permissions div span span[data-v-b968620e]:nth-child(1) {
  align-items: center;
  justify-content: center;
  color: var(--color-primary-element);
  padding: 0.1em;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] label span {
  display: flex;
  flex-direction: column;
}
.sharingTabDetailsView__advanced-control[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__advanced-control button[data-v-b968620e] {
  margin-top: 0.5em;
}
.sharingTabDetailsView__advanced[data-v-b968620e] {
  width: 100%;
  margin-bottom: 0.5em;
  text-align: left;
  padding-left: 0;
}
.sharingTabDetailsView__advanced section[data-v-b968620e] {
  /*
             The following style is applied out of the component's scope
             to remove padding from the label.checkbox-radio-switch__label,
             which is used to group radio checkbox items. The use of ::v-deep
             ensures that the padding is modified without being affected by
             the component's scoping.
             Without this achieving left alignment for the checkboxes would not
             be possible.
           */
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e],
.sharingTabDetailsView__advanced section div.mx-datepicker[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e] {
  height: 80px;
}
.sharingTabDetailsView__advanced section span[data-v-b968620e] label {
  padding-left: 0 !important;
  background-color: initial !important;
  border: none !important;
}
.sharingTabDetailsView__advanced section section.custom-permissions-group[data-v-b968620e] {
  padding-left: 1.5em;
}
.sharingTabDetailsView__delete > button[data-v-b968620e]:first-child {
  color: rgb(223, 7, 7);
}
.sharingTabDetailsView__footer[data-v-b968620e] {
  width: 100%;
  display: flex;
  position: sticky;
  bottom: 0;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-start;
}
.sharingTabDetailsView__footer .button-group[data-v-b968620e] {
  display: flex;
  justify-content: space-between;
  width: 100%;
  margin-top: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e] {
  margin-left: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e]:first-child {
  margin-left: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.sharing-entry__inherited .avatar-shared[data-v-3f1bda78] {
  width: 32px;
  height: 32px;
  line-height: 32px;
  font-size: 18px;
  background-color: var(--color-text-maxcontrast);
  border-radius: 50%;
  flex-shrink: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.emptyContentWithSections[data-v-0f81577f] {
  margin: 1rem auto;
}
.sharingTab__content[data-v-0f81577f] {
  padding: 0 6px;
}
.sharingTab__additionalContent[data-v-0f81577f] {
  margin: 44px 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue":
/*!*******************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ExternalShareAction.vue?vue&type=template&id=27835356& */ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356&");
/* harmony import */ var _ExternalShareAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ExternalShareAction.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ExternalShareAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__.render,
  _ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/ExternalShareAction.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true& */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true&");
/* harmony import */ var _SharingEntry_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntry_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "61240f7a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntry.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue":
/*!*********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true&");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "06bd31b0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue":
/*!********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true&");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInternal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f55cfc52",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInternal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue":
/*!****************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true&");
/* harmony import */ var _SharingEntryLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7a675594",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryLink.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue":
/*!****************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue ***!
  \****************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true&");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "62b9dbb0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue":
/*!******************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true& */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true&");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntrySimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "354542cc",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntrySimple.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=template&id=39161a5c& */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c&");
/* harmony import */ var _SharingInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/SharingInput.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true& */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true&");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingDetailsTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b968620e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingDetailsTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true& */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true&");
/* harmony import */ var _SharingInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3f1bda78",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=template&id=dd248c84& */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84&");
/* harmony import */ var _SharingLinkList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingLinkList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingLinkList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingList.vue?vue&type=template&id=698e26a4& */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4&");
/* harmony import */ var _SharingList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingList.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true& */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true&");
/* harmony import */ var _SharingTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js&");
/* harmony import */ var _SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0f81577f",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/SharingTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js&":
/*!********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ExternalShareAction.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js&":
/*!***********************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js& ***!
  \***********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js&");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356&":
/*!**************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356& ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ExternalShareAction_vue_vue_type_template_id_27835356___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ExternalShareAction.vue?vue&type=template&id=27835356& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ExternalShareAction.vue?vue&type=template&id=27835356&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true& ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true&":
/*!****************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true& ***!
  \****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true&":
/*!***************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true& ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true&":
/*!***********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true& ***!
  \***********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true&":
/*!***********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true& ***!
  \***********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true&":
/*!*************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true& ***!
  \*************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c&":
/*!*******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c& ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=template&id=39161a5c& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true& ***!
  \*******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true& ***!
  \******************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84&":
/*!*****************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84& ***!
  \*****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=template&id=dd248c84& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4&":
/*!*************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4& ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=template&id=698e26a4& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true&":
/*!************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true& ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&":
/*!**********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&":
/*!******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&":
/*!**************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&":
/*!****************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&":
/*!**********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& ***!
  \**********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&":
/*!**********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&":
/*!***************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss&");


/***/ })

}]);
//# sourceMappingURL=apps_files_sharing_src_views_SharingTab_vue-apps_files_sharing_src_views_SharingTab_vue.js.map?v=c1c4a84d16dee5dd5436