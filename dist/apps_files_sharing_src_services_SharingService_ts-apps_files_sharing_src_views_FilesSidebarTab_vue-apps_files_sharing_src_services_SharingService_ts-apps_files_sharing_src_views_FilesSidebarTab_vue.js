"use strict";
(globalThis["webpackChunknextcloud_ui_legacy"] = globalThis["webpackChunknextcloud_ui_legacy"] || []).push([["apps_files_sharing_src_services_SharingService_ts-apps_files_sharing_src_views_FilesSidebarTab_vue"],{

/***/ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js"
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/lib/SharePermissionsToolBox.js ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ATOMIC_PERMISSIONS: () => (/* binding */ ATOMIC_PERMISSIONS),
/* harmony export */   addPermissions: () => (/* binding */ addPermissions),
/* harmony export */   canTogglePermissions: () => (/* binding */ canTogglePermissions),
/* harmony export */   getBundledPermissions: () => (/* binding */ getBundledPermissions),
/* harmony export */   hasPermissions: () => (/* binding */ hasPermissions),
/* harmony export */   permissionsSetIsValid: () => (/* binding */ permissionsSetIsValid),
/* harmony export */   subtractPermissions: () => (/* binding */ subtractPermissions),
/* harmony export */   togglePermissions: () => (/* binding */ togglePermissions)
/* harmony export */ });
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
 * Get bundled permissions based on config.
 *
 * @param {boolean} excludeShare - Whether to exclude SHARE permission from ALL and ALL_FILE bundles.
 * @return {object}
 */
function getBundledPermissions(excludeShare = false) {
  if (excludeShare) {
    return {
      ...BUNDLED_PERMISSIONS,
      ALL: BUNDLED_PERMISSIONS.ALL & ~ATOMIC_PERMISSIONS.SHARE,
      ALL_FILE: BUNDLED_PERMISSIONS.ALL_FILE & ~ATOMIC_PERMISSIONS.SHARE
    };
  }
  return BUNDLED_PERMISSIONS;
}

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

/***/ },

/***/ "./apps/files_sharing/src/mixins/ShareDetails.js"
/*!*******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareDetails.js ***!
  \*******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/ConfigService.ts */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  methods: {
    async openSharingDetails(shareRequestObject) {
      let share;
      // handle externalResults from OCA.Sharing.ShareSearch
      // TODO : Better name/interface for handler required
      // For example `externalAppCreateShareHook` with proper documentation
      if (shareRequestObject.handler) {
        const handlerInput = {};
        if (this.suggestions) {
          handlerInput.suggestions = this.suggestions;
          handlerInput.fileInfo = this.fileInfo;
          handlerInput.query = this.query;
        }
        const externalShareRequestObject = await shareRequestObject.handler(handlerInput);
        share = this.mapShareRequestToShareObject(externalShareRequestObject);
      } else {
        share = this.mapShareRequestToShareObject(shareRequestObject);
      }
      if (this.fileInfo.type !== 'dir') {
        const originalPermissions = share.permissions;
        const strippedPermissions = originalPermissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_0__.ATOMIC_PERMISSIONS.CREATE & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_0__.ATOMIC_PERMISSIONS.DELETE;
        if (originalPermissions !== strippedPermissions) {
          _services_logger_ts__WEBPACK_IMPORTED_MODULE_3__["default"].debug('Removed create/delete permissions from file share (only valid for folders)');
          share.permissions = strippedPermissions;
        }
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
          value: true,
          key: 'download',
          scope: 'permissions'
        }],
        hideDownload: false,
        share_type: shareRequestObject.shareType,
        share_with: shareRequestObject.shareWith,
        is_no_user: shareRequestObject.isNoUser,
        user: shareRequestObject.shareWith,
        share_with_displayname: shareRequestObject.displayName,
        subtitle: shareRequestObject.subtitle,
        permissions: shareRequestObject.permissions ?? new _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_2__["default"]().defaultPermissions,
        expiration: ''
      };
      return new _models_Share_ts__WEBPACK_IMPORTED_MODULE_1__["default"](share);
    }
  }
});

/***/ },

/***/ "./apps/files_sharing/src/mixins/ShareRequests.js"
/*!********************************************************!*\
  !*** ./apps/files_sharing/src/mixins/ShareRequests.js ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */







const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
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
     * @param {string} [data.expireDate] expire the share automatically after
     * @param {string} [data.label] custom label
     * @param {string} [data.attributes] Share attributes encoded as json
     * @param {string} data.note custom note to recipient
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
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(shareUrl, {
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
        const share = new _models_Share_ts__WEBPACK_IMPORTED_MODULE_4__["default"](request.data.ocs.data);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files_sharing:share:created', {
          share
        });
        return share;
      } catch (error) {
        const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error creating the share');
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(errorMessage);
        throw new Error(errorMessage, {
          cause: error
        });
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
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].delete(shareUrl + `/${id}`);
        if (!request?.data?.ocs) {
          throw request;
        }
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files_sharing:share:deleted', {
          id
        });
        return true;
      } catch (error) {
        const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error deleting the share');
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(errorMessage);
        throw new Error(errorMessage, {
          cause: error
        });
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
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(shareUrl + `/${id}`, properties);
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files_sharing:share:updated', {
          id
        });
        if (!request?.data?.ocs) {
          throw request;
        } else {
          return request.data.ocs.data;
        }
      } catch (error) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error('Error while updating share', {
          error
        });
        const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error updating the share');
        // the error will be shown in apps/files_sharing/src/mixins/SharesMixin.js
        throw new Error(errorMessage, {
          cause: error
        });
      }
    }
  }
});

/**
 * Handle an error response from the server and show a notification with the error message if possible
 *
 * @param {unknown} error - The received error
 * @return {string|undefined} the error message if it could be extracted from the response, otherwise undefined
 */
function getErrorMessage(error) {
  if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__.isAxiosError)(error) && error.response.data?.ocs) {
    /** @type {import('@nextcloud/typings/ocs').OCSResponse} */
    const response = error.response.data;
    if (response.ocs.meta?.message) {
      return response.ocs.meta.message;
    }
  }
}

/***/ },

/***/ "./apps/files_sharing/src/mixins/SharesMixin.js"
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/mixins/SharesMixin.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _files_src_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../../files/src/services/WebdavClient.ts */ "./apps/files/src/services/WebdavClient.ts");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../services/ConfigService.ts */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../utils/GeneratePassword.ts */ "./apps/files_sharing/src/utils/GeneratePassword.ts");
/* harmony import */ var _ShareRequests_js__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! ./ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */














/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  mixins: [_ShareRequests_js__WEBPACK_IMPORTED_MODULE_12__["default"]],
  props: {
    fileInfo: {
      type: Object,
      default: () => {},
      required: true
    },
    share: {
      type: _models_Share_ts__WEBPACK_IMPORTED_MODULE_8__["default"],
      default: null
    },
    isUnique: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      config: new _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_9__["default"](),
      node: null,
      ShareType: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType,
      // errors helpers
      errors: {},
      // component status toggles
      loading: false,
      saving: false,
      open: false,
      /** @type {boolean | undefined} */
      passwordProtectedState: undefined,
      // concurrency management queue
      // we want one queue per share
      updateQueue: new p_queue__WEBPACK_IMPORTED_MODULE_5__["default"]({
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
    path() {
      return (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');
    },
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
      const weekdaysShort = window.dayNamesShort ? window.dayNamesShort // provided by Nextcloud
      : ['Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.'];
      const monthsShort = window.monthNamesShort ? window.monthNamesShort // provided by Nextcloud
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
    isNewShare() {
      return !this.share.id;
    },
    isFolder() {
      return this.fileInfo.type === 'dir';
    },
    isPublicShare() {
      const shareType = this.share.shareType ?? this.share.type;
      return [_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Link, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Email].includes(shareType);
    },
    isRemoteShare() {
      return this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.RemoteGroup || this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Remote;
    },
    isShareOwner() {
      return this.share && this.share.owner === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid;
    },
    isExpiryDateEnforced() {
      if (this.isPublicShare) {
        return this.config.isDefaultExpireDateEnforced;
      }
      if (this.isRemoteShare) {
        return this.config.isDefaultRemoteExpireDateEnforced;
      }
      return this.config.isDefaultInternalExpireDateEnforced;
    },
    hasCustomPermissions() {
      const basePermissions = (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_7__.getBundledPermissions)(true);
      const bundledPermissions = [basePermissions.ALL, basePermissions.ALL_FILE, basePermissions.READ_ONLY, basePermissions.FILE_DROP];
      const permissionsWithoutShare = this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_7__.ATOMIC_PERMISSIONS.SHARE;
      return !bundledPermissions.includes(permissionsWithoutShare);
    },
    maxExpirationDateEnforced() {
      if (this.isExpiryDateEnforced) {
        if (this.isPublicShare) {
          return this.config.defaultExpirationDate;
        }
        if (this.isRemoteShare) {
          return this.config.defaultRemoteExpirationDateString;
        }
        // If it get's here then it must be an internal share
        return this.config.defaultInternalExpirationDate;
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
        if (this.config.enforcePasswordForPublicLink) {
          return true;
        }
        if (this.passwordProtectedState !== undefined) {
          return this.passwordProtectedState;
        }
        return typeof this.share.newPassword === 'string' || typeof this.share.password === 'string';
      },
      async set(enabled) {
        if (enabled) {
          this.passwordProtectedState = true;
          const generatedPassword = await (0,_utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_11__["default"])(true);
          if (!this.share.newPassword) {
            this.$set(this.share, 'newPassword', generatedPassword);
          }
        } else {
          this.passwordProtectedState = false;
          this.$set(this.share, 'newPassword', '');
        }
      }
    }
  },
  methods: {
    /**
     * Fetch WebDAV node
     *
     * @return {Node}
     */
    async getNode() {
      const node = {
        path: this.path
      };
      try {
        this.node = await (0,_files_src_services_WebdavClient_ts__WEBPACK_IMPORTED_MODULE_6__.fetchNode)(node.path);
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].info('Fetched node:', {
          node: this.node
        });
      } catch (error) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error('Error:', error);
      }
    },
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
      if (share.newPassword) {
        if (typeof share.newPassword !== 'string') {
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
     * @param {Date} date the date to format
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
      if (!date) {
        this.share.expireDate = null;
        this.$set(this.share, 'expireDate', null);
        return;
      }
      const parsedDate = date instanceof Date ? date : new Date(date);
      this.share.expireDate = this.formatDateToString(parsedDate);
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
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].debug('Share deleted', {
          shareId: this.share.id
        });
        const message = this.share.itemType === 'file' ? t('files_sharing', 'File "{path}" has been unshared', {
          path: this.share.path
        }) : t('files_sharing', 'Folder "{path}" has been unshared', {
          path: this.share.path
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(message);
        this.$emit('remove:share', this.share);
        await this.getNode();
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_2__.emit)('files:node:updated', this.node);
      } catch {
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
        for (const name of propertyNames) {
          if (name === 'password') {
            if (this.share.newPassword !== undefined) {
              properties[name] = this.share.newPassword;
            }
            continue;
          }
          if (this.share[name] === null || this.share[name] === undefined) {
            properties[name] = '';
          } else if (typeof this.share[name] === 'object') {
            properties[name] = JSON.stringify(this.share[name]);
          } else {
            properties[name] = this.share[name].toString();
          }
        }
        return this.updateQueue.add(async () => {
          this.saving = true;
          this.errors = {};
          try {
            const updatedShare = await this.updateShare(this.share.id, properties);
            if (propertyNames.includes('password')) {
              // reset password state after sync
              this.share.password = this.share.newPassword || undefined;
              this.$delete(this.share, 'newPassword');

              // updates password expiration time after sync
              this.share.passwordExpirationTime = updatedShare.password_expiration_time;
            }

            // clear any previous errors
            for (const property of propertyNames) {
              this.$delete(this.errors, property);
            }
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(this.updateSuccessMessage(propertyNames));
          } catch (error) {
            _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].error('Could not update share', {
              error,
              share: this.share,
              propertyNames
            });
            const {
              message
            } = error;
            if (message && message !== '') {
              for (const property of propertyNames) {
                this.onSyncError(property, message);
              }
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(message);
            } else {
              // We do not have information what happened, but we should still inform the user
              (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(t('files_sharing', 'Could not update share'));
            }
          } finally {
            this.saving = false;
          }
        });
      }

      // This share does not exists on the server yet
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_10__["default"].debug('Updated local share', {
        share: this.share
      });
    },
    /**
     * @param {string[]} names Properties changed
     */
    updateSuccessMessage(names) {
      if (names.length !== 1) {
        return t('files_sharing', 'Share saved');
      }
      switch (names[0]) {
        case 'expireDate':
          return t('files_sharing', 'Share expiry date saved');
        case 'hideDownload':
          return t('files_sharing', 'Share hide-download state saved');
        case 'label':
          return t('files_sharing', 'Share label saved');
        case 'note':
          return t('files_sharing', 'Share note for recipient saved');
        case 'password':
          return t('files_sharing', 'Share password saved');
        case 'permissions':
          return t('files_sharing', 'Share permissions saved');
        default:
          return t('files_sharing', 'Share saved');
      }
    },
    /**
     * Manage sync errors
     *
     * @param {string} property the errored property, e.g. 'password'
     * @param {string} message the error message
     */
    onSyncError(property, message) {
      if (property === 'password' && this.share.newPassword !== undefined) {
        if (this.share.newPassword === this.share.password) {
          this.share.password = '';
        }
        this.$delete(this.share, 'newPassword');
      }

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
    debounceQueueUpdate: (0,debounce__WEBPACK_IMPORTED_MODULE_4__["default"])(function (property) {
      this.queueUpdate(property);
    }, 500)
  }
});

/***/ },

/***/ "./apps/files_sharing/src/utils/SharedWithMe.js"
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/utils/SharedWithMe.js ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   shareWithTitle: () => (/* binding */ shareWithTitle)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



/**
 *
 * @param share
 */
function shareWithTitle(share) {
  if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Group) {
    return t('files_sharing', 'Shared with you and the group {group} by {owner}', {
      group: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Team) {
    return t('files_sharing', 'Shared with you and {circle} by {owner}', {
      circle: share.shareWithDisplayName,
      owner: share.ownerDisplayName
    }, undefined, {
      escape: false
    });
  } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Room) {
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
}


/***/ },

/***/ "./apps/files/src/services/WebdavClient.ts"
/*!*************************************************!*\
  !*** ./apps/files/src/services/WebdavClient.ts ***!
  \*************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   client: () => (/* binding */ client),
/* harmony export */   fetchNode: () => (/* binding */ fetchNode)
/* harmony export */ });
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/*!
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const client = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getClient)();
/**
 * Fetches a node from the given path
 *
 * @param path - The path to fetch the node from
 */
async function fetchNode(path) {
  const propfindPayload = (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getDefaultPropfind)();
  const result = await client.stat(`${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.getRootPath)()}${path}`, {
    details: true,
    data: propfindPayload
  });
  return (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_0__.resultToNode)(result.data);
}

/***/ },

/***/ "./apps/files_sharing/src/services/ConfigService.ts"
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/services/ConfigService.ts ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Config)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


class Config {
  constructor() {
    _defineProperty(this, "_capabilities", void 0);
    this._capabilities = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)();
  }
  /**
   * Get default share permissions, if any
   */
  get defaultPermissions() {
    return this._capabilities.files_sharing?.default_permissions;
  }
  /**
   * Should SHARE permission be excluded from "Allow editing" bundled permissions
   */
  get excludeReshareFromEdit() {
    return this._capabilities.files_sharing?.exclude_reshare_from_edit === true;
  }
  /**
   * Is public upload allowed on link shares ?
   * This covers File request and Full upload/edit option.
   */
  get isPublicUploadEnabled() {
    return this._capabilities.files_sharing?.public?.upload === true;
  }
  /**
   * Get the federated sharing documentation link
   */
  get federatedShareDocLink() {
    return window.OC.appConfig.core.federatedCloudShareDoc;
  }
  /**
   * Get the default link share expiration date
   */
  get defaultExpirationDate() {
    if (this.isDefaultExpireDateEnabled && this.defaultExpireDate !== null) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultExpireDate));
    }
    return null;
  }
  /**
   * Get the default internal expiration date
   */
  get defaultInternalExpirationDate() {
    if (this.isDefaultInternalExpireDateEnabled && this.defaultInternalExpireDate !== null) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultInternalExpireDate));
    }
    return null;
  }
  /**
   * Get the default remote expiration date
   */
  get defaultRemoteExpirationDateString() {
    if (this.isDefaultRemoteExpireDateEnabled && this.defaultRemoteExpireDate !== null) {
      return new Date(new Date().setDate(new Date().getDate() + this.defaultRemoteExpireDate));
    }
    return null;
  }
  /**
   * Are link shares password-enforced ?
   */
  get enforcePasswordForPublicLink() {
    return window.OC.appConfig.core.enforcePasswordForPublicLink === true;
  }
  /**
   * Is password asked by default on link shares ?
   */
  get enableLinkPasswordByDefault() {
    return window.OC.appConfig.core.enableLinkPasswordByDefault === true;
  }
  /**
   * Is link shares expiration enforced ?
   */
  get isDefaultExpireDateEnforced() {
    return window.OC.appConfig.core.defaultExpireDateEnforced === true;
  }
  /**
   * Is there a default expiration date for new link shares ?
   */
  get isDefaultExpireDateEnabled() {
    return window.OC.appConfig.core.defaultExpireDateEnabled === true;
  }
  /**
   * Is internal shares expiration enforced ?
   */
  get isDefaultInternalExpireDateEnforced() {
    return window.OC.appConfig.core.defaultInternalExpireDateEnforced === true;
  }
  /**
   * Is there a default expiration date for new internal shares ?
   */
  get isDefaultInternalExpireDateEnabled() {
    return window.OC.appConfig.core.defaultInternalExpireDateEnabled === true;
  }
  /**
   * Is remote shares expiration enforced ?
   */
  get isDefaultRemoteExpireDateEnforced() {
    return window.OC.appConfig.core.defaultRemoteExpireDateEnforced === true;
  }
  /**
   * Is there a default expiration date for new remote shares ?
   */
  get isDefaultRemoteExpireDateEnabled() {
    return window.OC.appConfig.core.defaultRemoteExpireDateEnabled === true;
  }
  /**
   * Are users on this server allowed to send shares to other servers ?
   */
  get isRemoteShareAllowed() {
    return window.OC.appConfig.core.remoteShareAllowed === true;
  }
  /**
   * Is federation enabled ?
   */
  get isFederationEnabled() {
    return this._capabilities?.files_sharing?.federation?.outgoing === true;
  }
  /**
   * Is public sharing enabled ?
   */
  get isPublicShareAllowed() {
    return this._capabilities?.files_sharing?.public?.enabled === true;
  }
  /**
   * Is sharing my mail (link share) enabled ?
   */
  get isMailShareAllowed() {
    return this._capabilities?.files_sharing?.sharebymail?.enabled === true && this.isPublicShareAllowed === true;
  }
  /**
   * Get the default days to link shares expiration
   */
  get defaultExpireDate() {
    return window.OC.appConfig.core.defaultExpireDate;
  }
  /**
   * Get the default days to internal shares expiration
   */
  get defaultInternalExpireDate() {
    return window.OC.appConfig.core.defaultInternalExpireDate;
  }
  /**
   * Get the default days to remote shares expiration
   */
  get defaultRemoteExpireDate() {
    return window.OC.appConfig.core.defaultRemoteExpireDate;
  }
  /**
   * Is resharing allowed ?
   */
  get isResharingAllowed() {
    return window.OC.appConfig.core.resharingAllowed === true;
  }
  /**
   * Is password enforced for mail shares ?
   */
  get isPasswordForMailSharesRequired() {
    return this._capabilities.files_sharing?.sharebymail?.password?.enforced === true;
  }
  /**
   * Always show the email or userid unique sharee label if enabled by the admin
   */
  get shouldAlwaysShowUnique() {
    return this._capabilities.files_sharing?.sharee?.always_show_unique === true;
  }
  /**
   * Is sharing with groups allowed ?
   */
  get allowGroupSharing() {
    return window.OC.appConfig.core.allowGroupSharing === true;
  }
  /**
   * Get the maximum results of a share search
   */
  get maxAutocompleteResults() {
    return parseInt(window.OC.config['sharing.maxAutocompleteResults'], 10) || 25;
  }
  /**
   * Get the minimal string length
   * to initiate a share search
   */
  get minSearchStringLength() {
    return parseInt(window.OC.config['sharing.minSearchStringLength'], 10) || 0;
  }
  /**
   * Get the password policy configuration
   */
  get passwordPolicy() {
    return this._capabilities?.password_policy || {};
  }
  /**
   * Returns true if custom tokens are allowed
   */
  get allowCustomTokens() {
    return this._capabilities?.files_sharing?.public?.custom_tokens;
  }
  /**
   * Show federated shares as internal shares
   *
   * @return
   */
  get showFederatedSharesAsInternal() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'showFederatedSharesAsInternal', false);
  }
  /**
   * Show federated shares to trusted servers as internal shares
   *
   * @return
   */
  get showFederatedSharesToTrustedServersAsInternal() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'showFederatedSharesToTrustedServersAsInternal', false);
  }
  /**
   * Show the external share ui
   */
  get showExternalSharing() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'showExternalSharing', true);
  }
}

/***/ },

/***/ "./apps/files_sharing/src/services/FileInfo.ts"
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/services/FileInfo.ts ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Convert Node to legacy file info
 *
 * @param node - The Node to convert
 */
/* harmony default export */ function __WEBPACK_DEFAULT_EXPORT__(node) {
  const rawFileInfo = {
    id: node.fileid,
    path: node.dirname,
    name: node.basename,
    mtime: node.mtime?.getTime(),
    etag: node.attributes.etag,
    size: node.size,
    hasPreview: node.attributes.hasPreview,
    isEncrypted: node.attributes.isEncrypted === 1,
    isFavourited: node.attributes.favorite === 1,
    mimetype: node.mime,
    permissions: node.permissions,
    mountType: node.attributes['mount-type'],
    sharePermissions: node.attributes['share-permissions'],
    shareAttributes: JSON.parse(node.attributes['share-attributes'] || '[]'),
    type: node.type === 'file' ? 'file' : 'dir',
    attributes: node.attributes
  };
  // TODO remove when no more legacy backbone is used
  const fileInfo = {
    ...rawFileInfo,
    node,
    get(key) {
      return this[key];
    },
    isDirectory() {
      return this.mimetype === 'httpd/unix-directory';
    },
    canEdit() {
      return Boolean(this.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.UPDATE);
    },
    canDownload() {
      for (const i in this.shareAttributes) {
        const attr = this.shareAttributes[i];
        if (attr.scope === 'permissions' && attr.key === 'download') {
          return attr.value === true;
        }
      }
      return true;
    }
  };
  return fileInfo;
}

/***/ },

/***/ "./apps/files_sharing/src/services/SharingService.ts"
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/services/SharingService.ts ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   isFileRequest: () => (/* binding */ isFileRequest)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// TODO: Fix this instead of disabling ESLint!!!
/* eslint-disable @typescript-eslint/no-explicit-any */






const headers = {
  'Content-Type': 'application/json'
};
/**
 *
 * @param ocsEntry
 */
async function ocsEntryToNode(ocsEntry) {
  try {
    // Federated share handling
    if (ocsEntry?.remote_id !== undefined) {
      if (!ocsEntry.mimetype) {
        const mime = (await __webpack_require__.e(/*! import() */ "node_modules_mime_dist_src_index_js").then(__webpack_require__.bind(__webpack_require__, /*! mime */ "./node_modules/mime/dist/src/index.js"))).default;
        // This won't catch files without an extension, but this is the best we can do
        ocsEntry.mimetype = mime.getType(ocsEntry.name);
      }
      const type = ocsEntry.type === 'dir' ? 'folder' : ocsEntry.type;
      ocsEntry.item_type = type || (ocsEntry.mimetype ? 'file' : 'folder');
      // different naming for remote shares
      ocsEntry.item_mtime = ocsEntry.mtime;
      ocsEntry.file_target = ocsEntry.file_target || ocsEntry.mountpoint;
      if (ocsEntry.file_target.includes('TemporaryMountPointName')) {
        ocsEntry.file_target = ocsEntry.name;
      }
      // If the share is not accepted yet we don't know which permissions it will have
      if (!ocsEntry.accepted) {
        // Need to set permissions to NONE for federated shares
        ocsEntry.item_permissions = _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.NONE;
        ocsEntry.permissions = _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.NONE;
      }
      ocsEntry.uid_owner = ocsEntry.owner;
      // TODO: have the real display name stored somewhere
      ocsEntry.displayname_owner = ocsEntry.owner;
    }
    const isFolder = ocsEntry?.item_type === 'folder';
    const hasPreview = ocsEntry?.has_preview === true;
    const Node = isFolder ? _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Folder : _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.File;
    // If this is an external share that is not yet accepted,
    // we don't have an id. We can fallback to the row id temporarily
    // local shares (this server) use `file_source`, but remote shares (federated) use `file_id`
    const fileid = ocsEntry.file_source || ocsEntry.file_id || ocsEntry.id;
    // Generate path and strip double slashes
    const path = ocsEntry.path || ocsEntry.file_target || ocsEntry.name;
    const source = `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRemoteURL)()}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)()}/${path.replace(/^\/+/, '')}`;
    let mtime = ocsEntry.item_mtime ? new Date(ocsEntry.item_mtime * 1000) : undefined;
    // Prefer share time if more recent than item mtime
    if (ocsEntry?.stime > (ocsEntry?.item_mtime || 0)) {
      mtime = new Date(ocsEntry.stime * 1000);
    }
    let sharees;
    if ('share_with' in ocsEntry) {
      sharees = {
        sharee: {
          id: ocsEntry.share_with,
          'display-name': ocsEntry.share_with_displayname || ocsEntry.share_with,
          type: ocsEntry.share_type
        }
      };
    }
    return new Node({
      id: fileid,
      source,
      owner: ocsEntry?.uid_owner,
      mime: ocsEntry?.mimetype || 'application/octet-stream',
      mtime,
      size: ocsEntry?.item_size ?? undefined,
      permissions: ocsEntry?.item_permissions || ocsEntry?.permissions,
      root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)(),
      attributes: {
        ...ocsEntry,
        'has-preview': hasPreview,
        'hide-download': ocsEntry?.hide_download === 1,
        // Also check the sharingStatusAction.ts code
        'owner-id': ocsEntry?.uid_owner,
        'owner-display-name': ocsEntry?.displayname_owner,
        'share-types': ocsEntry?.share_type,
        'share-attributes': ocsEntry?.attributes || '[]',
        sharees,
        favorite: ocsEntry?.tags?.includes(window.OC.TAG_FAVORITE) ? 1 : 0
      }
    });
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error('Error while parsing OCS entry', {
      error
    });
    return null;
  }
}
/**
 *
 * @param shareWithMe
 */
function getShares(shareWithMe = false) {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, {
    headers,
    params: {
      shared_with_me: shareWithMe,
      include_tags: true
    }
  });
}
/**
 *
 */
function getSharedWithYou() {
  return getShares(true);
}
/**
 *
 */
function getSharedWithOthers() {
  return getShares();
}
/**
 *
 */
function getRemoteShares() {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('apps/files_sharing/api/v1/remote_shares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
}
/**
 *
 */
function getPendingShares() {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('apps/files_sharing/api/v1/shares/pending');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
}
/**
 *
 */
function getRemotePendingShares() {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('apps/files_sharing/api/v1/remote_shares/pending');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
}
/**
 *
 */
function getDeletedShares() {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_4__.generateOcsUrl)('apps/files_sharing/api/v1/deletedshares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
}
/**
 * Check if a file request is enabled
 *
 * @param attributes the share attributes json-encoded array
 */
function isFileRequest(attributes = '[]') {
  const isFileRequest = attribute => {
    return attribute.scope === 'fileRequest' && attribute.key === 'enabled' && attribute.value === true;
  };
  try {
    const attributesArray = JSON.parse(attributes);
    return attributesArray.some(isFileRequest);
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error('Error while parsing share attributes', {
      error
    });
    return false;
  }
}
/**
 * Group an array of objects (here Nodes) by a key
 * and return an array of arrays of them.
 *
 * @param nodes Nodes to group
 * @param key The attribute to group by
 */
function groupBy(nodes, key) {
  return Object.values(nodes.reduce(function (acc, curr) {
    (acc[curr[key]] = acc[curr[key]] || []).push(curr);
    return acc;
  }, {}));
}
/**
 *
 * @param sharedWithYou
 * @param sharedWithOthers
 * @param pendingShares
 * @param deletedshares
 * @param filterTypes
 */
async function getContents(sharedWithYou = true, sharedWithOthers = true, pendingShares = false, deletedshares = false, filterTypes = []) {
  const promises = [];
  if (sharedWithYou) {
    promises.push(getSharedWithYou(), getRemoteShares());
  }
  if (sharedWithOthers) {
    promises.push(getSharedWithOthers());
  }
  if (pendingShares) {
    promises.push(getPendingShares(), getRemotePendingShares());
  }
  if (deletedshares) {
    promises.push(getDeletedShares());
  }
  const responses = await Promise.all(promises);
  const data = responses.map(response => response.data.ocs.data).flat();
  let contents = (await Promise.all(data.map(ocsEntryToNode))).filter(node => node !== null);
  if (filterTypes.length > 0) {
    contents = contents.filter(node => filterTypes.includes(node.attributes?.share_type));
  }
  // Merge duplicate shares and group their attributes
  // Also check the sharingStatusAction.ts code
  contents = groupBy(contents, 'source').map(nodes => {
    const node = nodes[0];
    node.attributes['share-types'] = nodes.map(node => node.attributes['share-types']);
    return node;
  });
  return {
    folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Folder({
      id: 0,
      source: `${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRemoteURL)()}${(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)()}`,
      owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null,
      root: (0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_3__.getRootPath)()
    }),
    contents
  };
}

/***/ },

/***/ "./apps/files_sharing/src/services/TokenService.ts"
/*!*********************************************************!*\
  !*** ./apps/files_sharing/src/services/TokenService.ts ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   generateToken: () => (/* binding */ generateToken)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 *
 */
async function generateToken() {
  const {
    data
  } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/files_sharing/api/v1/token'));
  return data.ocs.data.token;
}

/***/ },

/***/ "./apps/files_sharing/src/utils/generateUrl.ts"
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/utils/generateUrl.ts ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   generateFileUrl: () => (/* binding */ generateFileUrl)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


/**
 * @param fileid - The file ID to generate the direct file link for
 */
function generateFileUrl(fileid) {
  const baseURL = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.getBaseUrl)();
  const {
    globalscale
  } = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)();
  if (globalscale?.token) {
    return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/gf/{token}/{fileid}', {
      token: globalscale.token,
      fileid
    }, {
      baseURL
    });
  }
  return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/f/{fileid}', {
    fileid
  }, {
    baseURL
  });
}

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'SidebarTabExternalAction',
  props: {
    action: {
      type: Object,
      required: true
    },
    node: {
      type: Object,
      required: true
    },
    share: {
      type: Object,
      required: true
    }
  },
  setup(__props, {
    expose
  }) {
    const props = __props;
    expose({
      save
    });
    const actionElement = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const savingCallback = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watchEffect)(() => {
      if (!actionElement.value) {
        return;
      }
      // This seems to be only needed in Vue 2 as the .prop modifier does not really work on the vue 2 version of web components
      // TODO: Remove with Vue 3
      actionElement.value.node = (0,vue__WEBPACK_IMPORTED_MODULE_0__.toRaw)(props.node);
      actionElement.value.onSave = onSave;
      actionElement.value.share = (0,vue__WEBPACK_IMPORTED_MODULE_0__.toRaw)(props.share);
    });
    /**
     * The share is reset thus save the state of the component.
     */
    async function save() {
      await savingCallback.value?.();
    }
    /**
     * Vue does not allow to call methods on wrapped web components
     * so we need to pass it per callback.
     *
     * @param callback - The callback to be called on save
     */
    function onSave(callback) {
      savingCallback.value = callback;
    }
    return {
      __sfc: true,
      props,
      actionElement,
      savingCallback,
      save,
      onSave
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'SidebarTabExternalSection',
  props: {
    node: {
      type: Object,
      required: true
    },
    section: {
      type: Object,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    // TOOD: Remove with Vue 3
    const sectionElement = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watchEffect)(() => {
      if (sectionElement.value) {
        sectionElement.value.node = props.node;
      }
    });
    return {
      __sfc: true,
      props,
      sectionElement
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");


/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'SidebarTabExternalSectionLegacy',
  props: {
    fileInfo: {
      type: Object,
      required: true
    },
    sectionCallback: {
      type: Function,
      required: true
    }
  },
  setup(__props) {
    const props = __props;
    const component = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => props.sectionCallback(undefined, props.fileInfo));
    return {
      __sfc: true,
      props,
      component
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts"
/*!***********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts ***!
  \***********************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _SharingTab_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingTab.vue */ "./apps/files_sharing/src/views/SharingTab.vue");
/* harmony import */ var _services_FileInfo_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/FileInfo.ts */ "./apps/files_sharing/src/services/FileInfo.ts");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FilesSidebarTab',
  props: {
    node: {
      type: null,
      required: false
    },
    active: {
      type: Boolean,
      required: false
    },
    folder: {
      type: null,
      required: false
    },
    view: {
      type: null,
      required: false
    }
  },
  setup(__props) {
    const props = __props;
    const fileInfo = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => props.node && (0,_services_FileInfo_ts__WEBPACK_IMPORTED_MODULE_2__["default"])(props.node));
    return {
      __sfc: true,
      props,
      fileInfo,
      SharingTab: _SharingTab_vue__WEBPACK_IMPORTED_MODULE_1__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTime */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _nextcloud_vue_components_NcPopover__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcPopover */ "./node_modules/@nextcloud/vue/dist/Components/NcPopover.mjs");
/* harmony import */ var vue_material_design_icons_ClockOutline_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/ClockOutline.vue */ "./node_modules/vue-material-design-icons/ClockOutline.vue");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'ShareExpiryTime',
  components: {
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcPopover: _nextcloud_vue_components_NcPopover__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcDateTime: _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_1__["default"],
    ClockIcon: vue_material_design_icons_ClockOutline_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    share: {
      type: Object,
      required: true
    }
  },
  computed: {
    expiryTime() {
      return this.share?.expireDate ? new Date(this.share.expireDate).getTime() : null;
    },
    timeFormat() {
      return {
        dateStyle: 'full',
        timeStyle: 'short'
      };
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var _ShareExpiryTime_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./ShareExpiryTime.vue */ "./apps/files_sharing/src/components/ShareExpiryTime.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntry',
  components: {
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_1__["default"],
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_3__["default"],
    ShareExpiryTime: _ShareExpiryTime_vue__WEBPACK_IMPORTED_MODULE_5__["default"],
    SharingEntryQuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_8__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_7__["default"]],
  computed: {
    title() {
      let title = this.share.shareWithDisplayName;
      const showAsInternal = this.config.showFederatedSharesAsInternal || this.share.isTrustedServer && this.config.showFederatedSharesToTrustedServersAsInternal;
      if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Group || this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.RemoteGroup && showAsInternal) {
        title += ` (${t('files_sharing', 'group')})`;
      } else if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Room) {
        title += ` (${t('files_sharing', 'conversation')})`;
      } else if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Remote && !showAsInternal) {
        title += ` (${t('files_sharing', 'remote')})`;
      } else if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.RemoteGroup) {
        title += ` (${t('files_sharing', 'remote group')})`;
      } else if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Guest) {
        title += ` (${t('files_sharing', 'guest')})`;
      }
      if (!this.isShareOwner && this.share.ownerDisplayName) {
        title += ' ' + t('files_sharing', 'by {initiator}', {
          initiator: this.share.ownerDisplayName
        });
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
        if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Group) {
          return t('files_sharing', 'Shared with the group {user} by {owner}', data);
        } else if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.Room) {
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
      if (this.share.type !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_0__.ShareType.User) {
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
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/paths */ "./node_modules/@nextcloud/paths/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionLink__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionLink */ "./node_modules/@nextcloud/vue/dist/Components/NcActionLink.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionText */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _models_Share_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../models/Share.js */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _utils_generateUrl_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../utils/generateUrl.js */ "./apps/files_sharing/src/utils/generateUrl.ts");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInherited',
  components: {
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcActionLink: _nextcloud_vue_components_NcActionLink__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcActionText: _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_4__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  props: {
    share: {
      type: _models_Share_js__WEBPACK_IMPORTED_MODULE_7__["default"],
      required: true
    }
  },
  computed: {
    viaFileTargetUrl() {
      return (0,_utils_generateUrl_js__WEBPACK_IMPORTED_MODULE_8__.generateFileUrl)(this.share.viaFileid);
    },
    viaFolderName() {
      return (0,_nextcloud_paths__WEBPACK_IMPORTED_MODULE_0__.basename)(this.share.viaPath);
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Check.vue */ "./node_modules/vue-material-design-icons/Check.vue");
/* harmony import */ var vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/ContentCopy.vue */ "./node_modules/vue-material-design-icons/ContentCopy.vue");
/* harmony import */ var _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _utils_generateUrl_ts__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../utils/generateUrl.ts */ "./apps/files_sharing/src/utils/generateUrl.ts");







/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryInternal',
  components: {
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_1__["default"],
    SharingEntrySimple: _SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    CheckIcon: vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    ClipboardIcon: vue_material_design_icons_ContentCopy_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
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
      return (0,_utils_generateUrl_ts__WEBPACK_IMPORTED_MODULE_6__.generateFileUrl)(this.fileInfo.id);
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
      return t('files_sharing', 'Copy internal link');
    },
    internalLinkSubtitle() {
      return t('files_sharing', 'For people who already have access');
    }
  },
  methods: {
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.internalLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showSuccess)(t('files_sharing', 'Link copied'));
        this.$refs.shareEntrySimple.$refs.actionsComponent.$el.focus();
        this.copySuccess = true;
        this.copied = true;
      } catch (error) {
        this.copySuccess = false;
        this.copied = true;
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error(error);
      } finally {
        setTimeout(() => {
          this.copySuccess = false;
          this.copied = false;
        }, 4000);
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @chenfengyuan/vue-qrcode */ "./node_modules/@chenfengyuan/vue-qrcode/dist/vue-qrcode.js");
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/sharing/ui */ "./node_modules/@nextcloud/sharing/dist/ui/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionCheckbox__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionCheckbox */ "./node_modules/@nextcloud/vue/dist/Components/NcActionCheckbox.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionSeparator */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionText */ "./node_modules/@nextcloud/vue/dist/Components/NcActionText.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var vue_material_design_icons_CalendarBlankOutline_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! vue-material-design-icons/CalendarBlankOutline.vue */ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue");
/* harmony import */ var vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue-material-design-icons/CheckBold.vue */ "./node_modules/vue-material-design-icons/CheckBold.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_Exclamation_vue__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! vue-material-design-icons/Exclamation.vue */ "./node_modules/vue-material-design-icons/Exclamation.vue");
/* harmony import */ var vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! vue-material-design-icons/LockOutline.vue */ "./node_modules/vue-material-design-icons/LockOutline.vue");
/* harmony import */ var vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! vue-material-design-icons/Plus.vue */ "./node_modules/vue-material-design-icons/Plus.vue");
/* harmony import */ var vue_material_design_icons_Qrcode_vue__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! vue-material-design-icons/Qrcode.vue */ "./node_modules/vue-material-design-icons/Qrcode.vue");
/* harmony import */ var vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! vue-material-design-icons/Tune.vue */ "./node_modules/vue-material-design-icons/Tune.vue");
/* harmony import */ var _ShareExpiryTime_vue__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! ./ShareExpiryTime.vue */ "./apps/files_sharing/src/components/ShareExpiryTime.vue");
/* harmony import */ var _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue");
/* harmony import */ var _SidebarTabExternal_SidebarTabExternalActionLegacy_vue__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ./SidebarTabExternal/SidebarTabExternalActionLegacy.vue */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ../utils/GeneratePassword.ts */ "./apps/files_sharing/src/utils/GeneratePassword.ts");




































/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryLink',
  components: {
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcActionCheckbox: _nextcloud_vue_components_NcActionCheckbox__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcActionInput: _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcActionText: _nextcloud_vue_components_NcActionText__WEBPACK_IMPORTED_MODULE_15__["default"],
    NcActionSeparator: _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_14__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_16__["default"],
    NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_17__["default"],
    NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_18__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_19__["default"],
    VueQrcode: (_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0___default()),
    Tune: vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_27__["default"],
    IconCalendarBlank: vue_material_design_icons_CalendarBlankOutline_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
    IconQr: vue_material_design_icons_Qrcode_vue__WEBPACK_IMPORTED_MODULE_26__["default"],
    ErrorIcon: vue_material_design_icons_Exclamation_vue__WEBPACK_IMPORTED_MODULE_23__["default"],
    LockIcon: vue_material_design_icons_LockOutline_vue__WEBPACK_IMPORTED_MODULE_24__["default"],
    CheckIcon: vue_material_design_icons_CheckBold_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    CloseIcon: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    PlusIcon: vue_material_design_icons_Plus_vue__WEBPACK_IMPORTED_MODULE_25__["default"],
    SharingEntryQuickShareSelect: _SharingEntryQuickShareSelect_vue__WEBPACK_IMPORTED_MODULE_29__["default"],
    ShareExpiryTime: _ShareExpiryTime_vue__WEBPACK_IMPORTED_MODULE_28__["default"],
    SidebarTabExternalActionLegacy: _SidebarTabExternal_SidebarTabExternalActionLegacy_vue__WEBPACK_IMPORTED_MODULE_30__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_32__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_31__["default"]],
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
  setup() {
    return {
      mdiCheck: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiCheck,
      mdiContentCopy: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiContentCopy
    };
  },
  data() {
    return {
      shareCreationComplete: false,
      copySuccess: false,
      defaultExpirationDateEnabled: false,
      // Are we waiting for password/expiration date
      pending: false,
      ExternalShareActions: OCA.Sharing.ExternalShareActions.state,
      externalShareActions: (0,_nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_8__.getSidebarInlineActions)(),
      // tracks whether modal should be opened or not
      showQRCode: false
    };
  },
  computed: {
    /**
     * Link share label
     *
     * @return {string}
     */
    title() {
      const l10nOptions = {
        escape: false /* no escape as this string is already escaped by Vue */
      };

      // if we have a valid existing share (not pending)
      if (this.share && this.share.id) {
        if (!this.isShareOwner && this.share.ownerDisplayName) {
          if (this.isEmailShareType) {
            return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', '{shareWith} by {initiator}', {
              shareWith: this.share.shareWith,
              initiator: this.share.ownerDisplayName
            }, l10nOptions);
          }
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Shared via link by {initiator}', {
            initiator: this.share.ownerDisplayName
          }, l10nOptions);
        }
        if (this.share.label && this.share.label.trim() !== '') {
          if (this.isEmailShareType) {
            if (this.isFileRequest) {
              return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'File request ({label})', {
                label: this.share.label.trim()
              }, l10nOptions);
            }
            return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Mail share ({label})', {
              label: this.share.label.trim()
            }, l10nOptions);
          }
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Share link ({label})', {
            label: this.share.label.trim()
          }, l10nOptions);
        }
        if (this.isEmailShareType) {
          if (!this.share.shareWith || this.share.shareWith.trim() === '') {
            return this.isFileRequest ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'File request') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Mail share');
          }
          return this.share.shareWith;
        }
        if (this.index === null) {
          return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Share link');
        }
      }
      if (this.index >= 1) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Share link ({index})', {
          index: this.index
        });
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Create public link');
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
    passwordExpirationTime() {
      if (this.share.passwordExpirationTime === null) {
        return null;
      }
      const expirationTime = (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])(this.share.passwordExpirationTime);
      if (expirationTime.diff((0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])()) < 0) {
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
      return this.share ? this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Email : false;
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
    pendingDataIsMissing() {
      return this.pendingPassword || this.pendingEnforcedPassword || this.pendingDefaultExpirationDate || this.pendingEnforcedExpirationDate;
    },
    pendingPassword() {
      return this.config.enableLinkPasswordByDefault && this.isPendingShare;
    },
    pendingEnforcedPassword() {
      return this.config.enforcePasswordForPublicLink && this.isPendingShare;
    },
    pendingEnforcedExpirationDate() {
      return this.config.isDefaultExpireDateEnforced && this.isPendingShare;
    },
    pendingDefaultExpirationDate() {
      return (this.config.defaultExpirationDate instanceof Date || !isNaN(new Date(this.config.defaultExpirationDate).getTime())) && this.isPendingShare;
    },
    isPendingShare() {
      return !!(this.share && !this.share.id);
    },
    sharePolicyHasEnforcedProperties() {
      return this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced;
    },
    enforcedPropertiesMissing() {
      // Ensure share exist and the share policy has required properties
      if (!this.sharePolicyHasEnforcedProperties) {
        return false;
      }
      if (!this.share) {
        // if no share, we can't tell if properties are missing or not so we assume properties are missing
        return true;
      }

      // If share has ID, then this is an incoming link share created from the existing link share
      // Hence assume required properties
      if (this.share.id) {
        return true;
      }
      // Check if either password or expiration date is missing and enforced
      const isPasswordMissing = this.config.enforcePasswordForPublicLink && !this.share.newPassword;
      const isExpireDateMissing = this.config.isDefaultExpireDateEnforced && !this.share.expireDate;
      return isPasswordMissing || isExpireDateMissing;
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
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateUrl)('/s/{token}', {
        token: this.share.token
      }, {
        baseURL: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.getBaseUrl)()
      });
    },
    /**
     * Tooltip message for actions button
     *
     * @return {string}
     */
    actionsTooltip() {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Actions for "{title}"', {
        title: this.title
      });
    },
    /**
     * @return {string}
     */
    copyLinkLabel() {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Copy public link of "{title}"', {
        title: this.title
      });
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    externalLegacyShareActions() {
      const filterValidAction = action => (action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Link) || action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Email)) && !action.advanced;
      // filter only the registered actions for said link
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].error('external legacy actions', {
        ExternalShareActions: this.ExternalShareActions
      });
      return this.ExternalShareActions.actions.filter(filterValidAction);
    },
    /**
     * Additional actions for the menu
     *
     * @return {import('@nextcloud/sharing/ui').ISidebarInlineAction[]}
     */
    sortedExternalShareActions() {
      return this.externalShareActions.filter(action => action.enabled((0,vue__WEBPACK_IMPORTED_MODULE_9__.toRaw)(this.share), (0,vue__WEBPACK_IMPORTED_MODULE_9__.toRaw)(this.fileInfo.node))).sort((a, b) => a.order - b.order);
    },
    isPasswordPolicyEnabled() {
      return typeof this.config.passwordPolicy === 'object';
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.scope === 'permissions' && shareAttribute.key === 'download' && shareAttribute.value === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    },
    isFileRequest() {
      return this.share.isFileRequest;
    }
  },
  mounted() {
    this.defaultExpirationDateEnabled = this.config.defaultExpirationDate instanceof Date;
    if (this.share && this.isNewShare) {
      this.share.expireDate = this.defaultExpirationDateEnabled ? this.formatDateToString(this.config.defaultExpirationDate) : '';
    }
  },
  methods: {
    /**
     * Check if the share requires review
     *
     * @param {boolean} shareReviewComplete if the share was reviewed
     * @return {boolean}
     */
    shareRequiresReview(shareReviewComplete) {
      // If a user clicks 'Create share' it means they have reviewed the share
      if (shareReviewComplete) {
        return false;
      }
      return this.defaultExpirationDateEnabled || this.config.enableLinkPasswordByDefault;
    },
    /**
     * Create a new share link and append it to the list
     *
     * @param {boolean} shareReviewComplete if the share was reviewed
     */
    async onNewLinkShare(shareReviewComplete = false) {
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('onNewLinkShare called (with this.share)', this.share);
      // do not run again if already loading
      if (this.loading) {
        return;
      }
      const shareDefaults = {
        share_type: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Link
      };
      if (this.config.isDefaultExpireDateEnforced) {
        // default is empty string if not set
        // expiration is the share object key, not expireDate
        shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate);
      }
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Missing required properties?', this.enforcedPropertiesMissing);
      // Do not push yet if we need a password or an expiration date: show pending menu
      // A share would require a review for example is default expiration date is set but not enforced, this allows
      // the user to review the share and remove the expiration date if they don't want it
      if (this.sharePolicyHasEnforcedProperties && this.enforcedPropertiesMissing || this.shareRequiresReview(shareReviewComplete === true)) {
        this.pending = true;
        this.shareCreationComplete = false;
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].info('Share policy requires a review or has mandated properties (password, expirationDate)...');
        const share = new _models_Share_ts__WEBPACK_IMPORTED_MODULE_33__["default"](shareDefaults);
        // if password default or enforced, pre-fill with random one
        if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
          this.$set(share, 'newPassword', await (0,_utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_35__["default"])(true));
        }
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
        // if a share already exists, pushing it
        if (this.share && !this.share.id) {
          // if the share is valid, create it on the server
          if (this.checkShare(this.share)) {
            try {
              _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].info('Sending existing share to server', this.share);
              await this.pushNewLinkShare(this.share, true);
              this.shareCreationComplete = true;
              _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].info('Share created on server', this.share);
            } catch (e) {
              this.pending = false;
              _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].error('Error creating share', e);
              return false;
            }
            return true;
          } else {
            this.open = true;
            (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Error, please enter proper password and/or expiration date'));
            return false;
          }
        }
        const share = new _models_Share_ts__WEBPACK_IMPORTED_MODULE_33__["default"](shareDefaults);
        await this.pushNewLinkShare(share);
        this.shareCreationComplete = true;
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
          shareType: _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Link,
          password: share.newPassword,
          expireDate: share.expireDate ?? '',
          attributes: JSON.stringify(this.fileInfo.shareAttributes)
          // we do not allow setting the publicUpload
          // before the share creation.
          // Todo: We also need to fix the createShare method in
          // lib/Controller/ShareAPIController.php to allow file requests
          // (currently not supported on create, only update)
        };
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Creating link share with options', {
          options
        });
        const newShare = await this.createShare(options);
        this.open = false;
        this.shareCreationComplete = true;
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Link share created', {
          newShare
        });
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
        await this.getNode();
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_3__.emit)('files:node:updated', this.node);

        // Execute the copy link method
        // freshly created share component
        // ! somehow does not works on firefox !
        if (!this.config.enforcePasswordForPublicLink) {
          // Only copy the link when the password was not forced,
          // otherwise the user needs to copy/paste the password before finishing the share.
          component.copyLink();
        }
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Link share created'));
      } catch (data) {
        const message = data?.response?.data?.ocs?.meta?.message;
        if (!message) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Error while creating the share'));
          _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].error('Error while creating the share', {
            error: data
          });
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
        this.shareCreationComplete = true;
      }
    },
    async copyLink() {
      try {
        await navigator.clipboard.writeText(this.shareLink);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Link copied'));
        // focus and show the tooltip
        this.$refs.copyButton.$el.focus();
      } catch (error) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Failed to automatically copy share link', {
          error
        });
        window.prompt((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_4__.t)('files_sharing', 'Your browser does not support copying, please copy the link manually:'), this.shareLink);
      } finally {
        this.copySuccess = true;
        setTimeout(() => {
          this.copySuccess = false;
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
     * We need this method because @update:modelValue
     * is ran simultaneously as @uncheck, so we
     * cannot ensure data is up-to-date
     */
    onPasswordDisable() {
      // reset password state after sync
      this.$set(this.share, 'newPassword', '');

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
        this.share.newPassword = this.share.newPassword.trim();
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
        this.share.newPassword = this.share.newPassword.trim();
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
     * @param {boolean} enabled True if expiration is enabled
     */
    onExpirationDateToggleUpdate(enabled) {
      this.share.expireDate = enabled ? this.formatDateToString(this.config.defaultExpirationDate) : '';
    },
    expirationDateChanged(event) {
      const value = event?.target?.value;
      const isValid = !!value && !isNaN(new Date(value).getTime());
      this.defaultExpirationDateEnabled = isValid;
    },
    /**
     * Cancel the share creation
     * Used in the pending popover
     */
    onCancel() {
      // this.share already exists at this point,
      // but is incomplete as not pushed to server
      // YET. We can safely delete the share :)
      if (!this.shareCreationComplete) {
        this.$emit('remove:share', this.share);
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var vue_material_design_icons_EyeOutline_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/EyeOutline.vue */ "./node_modules/vue-material-design-icons/EyeOutline.vue");
/* harmony import */ var vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/PencilOutline.vue */ "./node_modules/vue-material-design-icons/PencilOutline.vue");
/* harmony import */ var vue_material_design_icons_TrayArrowUp_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/TrayArrowUp.vue */ "./node_modules/vue-material-design-icons/TrayArrowUp.vue");
/* harmony import */ var vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/TriangleSmallDown.vue */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue");
/* harmony import */ var vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! vue-material-design-icons/Tune.vue */ "./node_modules/vue-material-design-icons/Tune.vue");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntryQuickShareSelect',
  components: {
    DropdownIcon: vue_material_design_icons_TriangleSmallDown_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  mixins: [_mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_11__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_10__["default"]],
  props: {
    share: {
      type: Object,
      required: true
    }
  },
  emits: ['open-sharing-details'],
  data() {
    return {
      selectedOption: ''
    };
  },
  computed: {
    ariaLabel() {
      return t('files_sharing', 'Quick share options, the current selected is "{selectedOption}"', {
        selectedOption: this.selectedOption
      });
    },
    canViewText() {
      return t('files_sharing', 'View only');
    },
    canEditText() {
      return t('files_sharing', 'Can edit');
    },
    fileDropText() {
      return t('files_sharing', 'File request');
    },
    customPermissionsText() {
      return t('files_sharing', 'Custom permissions');
    },
    bundledPermissions() {
      return (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_9__.getBundledPermissions)(this.config.excludeReshareFromEdit);
    },
    preSelectedOption() {
      // We remove the share permission for the comparison as it is not relevant for bundled permissions.
      const permissionsWithoutShare = this.share.permissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_9__.ATOMIC_PERMISSIONS.SHARE;
      const basePermissions = (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_9__.getBundledPermissions)(true);
      if (permissionsWithoutShare === basePermissions.READ_ONLY) {
        return this.canViewText;
      } else if (permissionsWithoutShare === basePermissions.ALL || permissionsWithoutShare === basePermissions.ALL_FILE) {
        return this.canEditText;
      } else if (permissionsWithoutShare === basePermissions.FILE_DROP) {
        return this.fileDropText;
      }
      return this.customPermissionsText;
    },
    options() {
      const options = [{
        label: this.canViewText,
        icon: vue_material_design_icons_EyeOutline_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
      }, {
        label: this.canEditText,
        icon: vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
      }];
      if (this.supportsFileDrop) {
        options.push({
          label: this.fileDropText,
          icon: vue_material_design_icons_TrayArrowUp_vue__WEBPACK_IMPORTED_MODULE_6__["default"]
        });
      }
      options.push({
        label: this.customPermissionsText,
        icon: vue_material_design_icons_Tune_vue__WEBPACK_IMPORTED_MODULE_8__["default"]
      });
      return options;
    },
    supportsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        const shareType = this.share.type ?? this.share.shareType;
        return [_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Link, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.Email].includes(shareType);
      }
      return false;
    },
    dropDownPermissionValue() {
      switch (this.selectedOption) {
        case this.canEditText:
          return this.isFolder ? this.bundledPermissions.ALL : this.bundledPermissions.ALL_FILE;
        case this.fileDropText:
          return this.bundledPermissions.FILE_DROP;
        case this.customPermissionsText:
          return 'custom';
        case this.canViewText:
        default:
          return this.bundledPermissions.READ_ONLY;
      }
    }
  },
  created() {
    this.selectedOption = this.preSelectedOption;
  },
  mounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('update:share', share => {
      if (share.id === this.share.id) {
        this.share.permissions = share.permissions;
        this.selectedOption = this.preSelectedOption;
      }
    });
  },
  unmounted() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('update:share');
  },
  methods: {
    selectOption(optionLabel) {
      this.selectedOption = optionLabel;
      if (optionLabel === this.customPermissionsText) {
        this.$emit('open-sharing-details');
      } else {
        this.share.permissions = this.dropDownPermissionValue;
        this.queueUpdate('permissions');
        // TODO: Add a focus method to NcActions or configurable returnFocus enabling to NcActionButton with closeAfterClick
        this.$refs.quickShareActions.$refs.menuButton.$el.focus();
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingEntrySimple',
  components: {
    NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    title: {
      type: String,
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../services/ConfigService.ts */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInput',
  components: {
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  mixins: [_mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_8__["default"], _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_7__["default"]],
  props: {
    shares: {
      type: Array,
      required: true
    },
    linkShares: {
      type: Array,
      required: true
    },
    fileInfo: {
      type: Object,
      required: true
    },
    reshare: {
      type: _models_Share_ts__WEBPACK_IMPORTED_MODULE_9__["default"],
      default: null
    },
    canReshare: {
      type: Boolean,
      required: true
    },
    isExternal: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: ''
    }
  },
  setup() {
    return {
      shareInputId: `share-input-${Math.random().toString(36).slice(2, 7)}`
    };
  },
  data() {
    return {
      config: new _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_10__["default"](),
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
      if (this.placeholder) {
        return this.placeholder;
      }

      // We can always search with email addresses for users too
      if (!allowRemoteSharing) {
        return t('files_sharing', 'Name or email …');
      }
      return t('files_sharing', 'Name, email, or Federated Cloud ID …');
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
        return t('files_sharing', 'Searching …');
      }
      return t('files_sharing', 'No elements found.');
    }
  },
  mounted() {
    if (!this.isExternal) {
      // We can only recommend users, groups etc for internal shares
      this.getRecommendations();
    }
  },
  methods: {
    onSelected(option) {
      this.value = null; // Reset selected option
      this.openSharingDetails(option);
    },
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
      if ((0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing.sharee.query_lookup_default === true) {
        lookup = true;
      }
      const remoteTypes = [_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Remote, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup];
      const shareType = [];
      const showFederatedAsInternal = this.config.showFederatedSharesAsInternal || this.config.showFederatedSharesToTrustedServersAsInternal;

      // For internal users, add remote types if config says to show them as internal
      const shouldAddRemoteTypes = !this.isExternal && showFederatedAsInternal
      // For external users, add them if config *doesn't* say to show them as internal
      || this.isExternal && !showFederatedAsInternal
      // Edge case: federated-to-trusted is a separate "add" trigger for external users
      || this.isExternal && this.config.showFederatedSharesToTrustedServersAsInternal;
      if (this.isExternal) {
        if ((0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)().files_sharing.public.enabled === true) {
          shareType.push(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email);
        }
      } else {
        shareType.push(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Group, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Team, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Room, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Guest, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Deck, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.ScienceMesh);
      }
      if (shouldAddRemoteTypes) {
        shareType.push(...remoteTypes);
      }
      let request;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateOcsUrl)('apps/files_sharing/api/v1/sharees'), {
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
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].error('Error fetching suggestions', {
          error
        });
        return;
      }
      const {
        exact,
        ...data
      } = request.data.ocs.data;
      // flatten array of arrays
      const rawExactSuggestions = Object.values(exact).flat();
      const rawSuggestions = Object.values(data).flat();

      // remove invalid data and format to user-select layout
      const exactSuggestions = this.filterOutExistingShares(rawExactSuggestions).filter(result => this.filterByTrustedServer(result)).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);
      const suggestions = this.filterOutExistingShares(rawSuggestions).filter(result => this.filterByTrustedServer(result)).map(share => this.formatForMultiselect(share))
      // sort by type so we can get user&groups first...
      .sort((a, b) => a.shareType - b.shareType);

      // lookup clickable entry
      // show if enabled and not already requested
      const lookupEntry = [];
      if (data.lookupEnabled && !lookup) {
        lookupEntry.push({
          id: 'global-lookup',
          isNoUser: true,
          displayName: t('files_sharing', 'Search everywhere'),
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
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].debug('sharing suggestions', {
        suggestions: this.suggestions
      });
    },
    /**
     * Debounce getSuggestions
     *
     * @param {...*} args the arguments
     */
    debounceGetSuggestions: (0,debounce__WEBPACK_IMPORTED_MODULE_5__["default"])(function (...args) {
      this.getSuggestions(...args);
    }, 300),
    /**
     * Get the sharing recommendations
     */
    async getRecommendations() {
      this.loading = true;
      let request;
      try {
        request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateOcsUrl)('apps/files_sharing/api/v1/sharees_recommended'), {
          params: {
            format: 'json',
            itemType: this.fileInfo.type
          }
        });
      } catch (error) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].error('Error fetching recommendations', {
          error
        });
        return;
      }

      // Add external results from the OCA.Sharing.ShareSearch api
      const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this));

      // flatten array of arrays
      const rawRecommendations = Object.values(request.data.ocs.data.exact).reduce((arr, elem) => arr.concat(elem), []);

      // remove invalid data and format to user-select layout
      this.recommendations = this.filterOutExistingShares(rawRecommendations).filter(result => this.filterByTrustedServer(result)).map(share => this.formatForMultiselect(share)).concat(externalResults);
      this.loading = false;
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_11__["default"].debug('sharing recommendations', {
        recommendations: this.recommendations
      });
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
          if (share.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User) {
            // filter out current user
            if (share.value.shareWith === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid) {
              return arr;
            }

            // filter out the owner of the share
            if (this.reshare && share.value.shareWith === this.reshare.owner) {
              return arr;
            }
          }

          // filter out existing mail shares
          if (share.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email) {
            // When sharing internally, we don't want to suggest email addresses
            // that the user previously created shares to
            if (!this.isExternal) {
              return arr;
            }
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
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Guest:
          // default is a user, other icons are here to differentiate
          // themselves from it, so let's not display the user icon
          // case ShareType.Remote:
          // case ShareType.User:
          return {
            icon: 'icon-user',
            iconTitle: t('files_sharing', 'Guest')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup:
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Group:
          return {
            icon: 'icon-group',
            iconTitle: t('files_sharing', 'Group')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email:
          return {
            icon: 'icon-mail',
            iconTitle: t('files_sharing', 'Email')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Team:
          return {
            icon: 'icon-teams',
            iconTitle: t('files_sharing', 'Team')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Room:
          return {
            icon: 'icon-room',
            iconTitle: t('files_sharing', 'Talk conversation')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Deck:
          return {
            icon: 'icon-deck',
            iconTitle: t('files_sharing', 'Deck board')
          };
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Sciencemesh:
          return {
            icon: 'icon-sciencemesh',
            iconTitle: t('files_sharing', 'ScienceMesh')
          };
        default:
          return {};
      }
    },
    /**
     * Filter suggestion results based on trusted server configuration
     *
     * @param {object} result The raw suggestion result from API
     * @return {boolean} Whether to include this result in suggestions
     */
    filterByTrustedServer(result) {
      const isRemoteEntity = result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Remote || result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup;
      if (isRemoteEntity && this.config.showFederatedSharesToTrustedServersAsInternal && !this.isExternal) {
        return result.value.isTrustedServer === true;
      }
      return true;
    },
    /**
     * Format shares for the multiselect options
     *
     * @param {object} result select entry item
     * @return {object}
     */
    formatForMultiselect(result) {
      let subname;
      let displayName = result.name || result.label;
      if (result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User && this.config.shouldAlwaysShowUnique) {
        subname = result.shareWithDisplayNameUnique ?? '';
      } else if (result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email) {
        subname = result.value.shareWith;
      } else if (result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Remote || result.value.shareType === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup) {
        if (this.config.showFederatedSharesAsInternal) {
          subname = result.extra?.email?.value ?? '';
          displayName = result.extra?.name?.value ?? displayName;
        } else if (result.value.server) {
          subname = t('files_sharing', 'on {server}', {
            server: result.value.server
          });
        }
      } else {
        subname = result.shareWithDescription ?? '';
      }
      return {
        shareWith: result.value.shareWith,
        shareType: result.value.shareType,
        user: result.uuid || result.value.shareWith,
        isNoUser: result.value.shareType !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User,
        displayName,
        subname,
        shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
        ...this.shareTypeToIcon(result.value.shareType)
      };
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SidebarTabExternalActionLegacy',
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
      required: true
    },
    share: {
      type: _models_Share_ts__WEBPACK_IMPORTED_MODULE_0__["default"],
      default: null
    }
  },
  computed: {
    data() {
      return this.action.data(this);
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing/ui */ "./node_modules/@nextcloud/sharing/dist/ui/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTimePickerNative__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTimePickerNative */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTimePickerNative.mjs");
/* harmony import */ var _nextcloud_vue_components_NcInputField__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcInputField */ "./node_modules/@nextcloud/vue/dist/Components/NcInputField.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcPasswordField */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextArea__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextArea */ "./node_modules/@nextcloud/vue/dist/Components/NcTextArea.mjs");
/* harmony import */ var vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! vue-material-design-icons/AccountCircleOutline.vue */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue");
/* harmony import */ var vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! vue-material-design-icons/AccountGroup.vue */ "./node_modules/vue-material-design-icons/AccountGroup.vue");
/* harmony import */ var vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! vue-material-design-icons/CircleOutline.vue */ "./node_modules/vue-material-design-icons/CircleOutline.vue");
/* harmony import */ var vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-material-design-icons/Close.vue */ "./node_modules/vue-material-design-icons/Close.vue");
/* harmony import */ var vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue-material-design-icons/DotsHorizontal.vue */ "./node_modules/vue-material-design-icons/DotsHorizontal.vue");
/* harmony import */ var vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! vue-material-design-icons/Email.vue */ "./node_modules/vue-material-design-icons/Email.vue");
/* harmony import */ var vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! vue-material-design-icons/Eye.vue */ "./node_modules/vue-material-design-icons/Eye.vue");
/* harmony import */ var vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! vue-material-design-icons/Link.vue */ "./node_modules/vue-material-design-icons/Link.vue");
/* harmony import */ var vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! vue-material-design-icons/MenuDown.vue */ "./node_modules/vue-material-design-icons/MenuDown.vue");
/* harmony import */ var vue_material_design_icons_MenuUp_vue__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! vue-material-design-icons/MenuUp.vue */ "./node_modules/vue-material-design-icons/MenuUp.vue");
/* harmony import */ var vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! vue-material-design-icons/PencilOutline.vue */ "./node_modules/vue-material-design-icons/PencilOutline.vue");
/* harmony import */ var vue_material_design_icons_Refresh_vue__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! vue-material-design-icons/Refresh.vue */ "./node_modules/vue-material-design-icons/Refresh.vue");
/* harmony import */ var vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! vue-material-design-icons/ShareCircle.vue */ "./node_modules/vue-material-design-icons/ShareCircle.vue");
/* harmony import */ var vue_material_design_icons_TrayArrowUp_vue__WEBPACK_IMPORTED_MODULE_28__ = __webpack_require__(/*! vue-material-design-icons/TrayArrowUp.vue */ "./node_modules/vue-material-design-icons/TrayArrowUp.vue");
/* harmony import */ var _components_SidebarTabExternal_SidebarTabExternalAction_vue__WEBPACK_IMPORTED_MODULE_29__ = __webpack_require__(/*! ../components/SidebarTabExternal/SidebarTabExternalAction.vue */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue");
/* harmony import */ var _components_SidebarTabExternal_SidebarTabExternalActionLegacy_vue__WEBPACK_IMPORTED_MODULE_30__ = __webpack_require__(/*! ../components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue");
/* harmony import */ var _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__ = __webpack_require__(/*! ../lib/SharePermissionsToolBox.js */ "./apps/files_sharing/src/lib/SharePermissionsToolBox.js");
/* harmony import */ var _mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_32__ = __webpack_require__(/*! ../mixins/ShareRequests.js */ "./apps/files_sharing/src/mixins/ShareRequests.js");
/* harmony import */ var _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_33__ = __webpack_require__(/*! ../mixins/SharesMixin.js */ "./apps/files_sharing/src/mixins/SharesMixin.js");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _services_TokenService_ts__WEBPACK_IMPORTED_MODULE_35__ = __webpack_require__(/*! ../services/TokenService.ts */ "./apps/files_sharing/src/services/TokenService.ts");
/* harmony import */ var _utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_36__ = __webpack_require__(/*! ../utils/GeneratePassword.ts */ "./apps/files_sharing/src/utils/GeneratePassword.ts");






































/** @typedef {import('../models/Share.js').default} Share */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingDetailsTab',
  components: {
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcDateTimePickerNative: _nextcloud_vue_components_NcDateTimePickerNative__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcInputField: _nextcloud_vue_components_NcInputField__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcPasswordField: _nextcloud_vue_components_NcPasswordField__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcTextArea: _nextcloud_vue_components_NcTextArea__WEBPACK_IMPORTED_MODULE_14__["default"],
    CloseIcon: vue_material_design_icons_Close_vue__WEBPACK_IMPORTED_MODULE_18__["default"],
    CircleIcon: vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    EditIcon: vue_material_design_icons_PencilOutline_vue__WEBPACK_IMPORTED_MODULE_25__["default"],
    LinkIcon: vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    GroupIcon: vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    ShareIcon: vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_27__["default"],
    UserIcon: vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    UploadIcon: vue_material_design_icons_TrayArrowUp_vue__WEBPACK_IMPORTED_MODULE_28__["default"],
    ViewIcon: vue_material_design_icons_Eye_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    MenuDownIcon: vue_material_design_icons_MenuDown_vue__WEBPACK_IMPORTED_MODULE_23__["default"],
    MenuUpIcon: vue_material_design_icons_MenuUp_vue__WEBPACK_IMPORTED_MODULE_24__["default"],
    DotsHorizontalIcon: vue_material_design_icons_DotsHorizontal_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    Refresh: vue_material_design_icons_Refresh_vue__WEBPACK_IMPORTED_MODULE_26__["default"],
    SidebarTabExternalAction: _components_SidebarTabExternal_SidebarTabExternalAction_vue__WEBPACK_IMPORTED_MODULE_29__["default"],
    SidebarTabExternalActionLegacy: _components_SidebarTabExternal_SidebarTabExternalActionLegacy_vue__WEBPACK_IMPORTED_MODULE_30__["default"]
  },
  mixins: [_mixins_ShareRequests_js__WEBPACK_IMPORTED_MODULE_32__["default"], _mixins_SharesMixin_js__WEBPACK_IMPORTED_MODULE_33__["default"]],
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
      sharingPermission: (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.getBundledPermissions)().ALL.toString(),
      revertSharingPermission: (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.getBundledPermissions)().ALL.toString(),
      setCustomPermissions: false,
      passwordError: false,
      advancedSectionAccordionExpanded: false,
      isFirstComponentLoad: true,
      test: false,
      creating: false,
      initialToken: this.share.token,
      loadingToken: false,
      externalShareActions: (0,_nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_5__.getSidebarActions)(),
      // legacy
      ExternalShareActions: OCA.Sharing.ExternalShareActions.state
    };
  },
  computed: {
    title() {
      switch (this.share.type) {
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User:
          return t('files_sharing', 'Share with {user}', {
            user: this.share.shareWithDisplayName
          });
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email:
          return t('files_sharing', 'Share with email {email}', {
            email: this.share.shareWith
          });
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link:
          return t('files_sharing', 'Share link');
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Group:
          return t('files_sharing', 'Share with group');
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Room:
          return t('files_sharing', 'Share in conversation');
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Remote:
          {
            const [user, server] = this.share.shareWith.split('@');
            if (this.config.showFederatedSharesAsInternal) {
              return t('files_sharing', 'Share with {user}', {
                user
              });
            }
            return t('files_sharing', 'Share with {user} on remote server {server}', {
              user,
              server
            });
          }
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup:
          return t('files_sharing', 'Share with remote group');
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Guest:
          return t('files_sharing', 'Share with guest');
        default:
          {
            if (this.share.id) {
              // Share already exists
              return t('files_sharing', 'Update share');
            } else {
              return t('files_sharing', 'Create share');
            }
          }
      }
    },
    bundledPermissions() {
      return (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.getBundledPermissions)(this.config.excludeReshareFromEdit);
    },
    allPermissions() {
      return this.isFolder ? this.bundledPermissions.ALL.toString() : this.bundledPermissions.ALL_FILE.toString();
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
     * Change the default view for public shares from "list" to "grid"
     */
    showInGridView: {
      get() {
        return this.getShareAttribute('config', 'grid_view', false);
      },
      /** @param {boolean} value If the default view should be changed to "grid" */
      set(value) {
        this.setShareAttribute('config', 'grid_view', value);
      }
    },
    /**
     * Can the sharee download files or only view them ?
     */
    canDownload: {
      get() {
        return this.getShareAttribute('permissions', 'download', true);
      },
      set(checked) {
        this.setShareAttribute('permissions', 'download', checked);
      }
    },
    /**
     * Is this share readable
     * Needed for some federated shares that might have been added from file requests links
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
        return this.isValidShareAttribute(this.share.expireDate);
      },
      set(enabled) {
        this.share.expireDate = enabled ? this.formatDateToString(this.defaultExpiryDate) : '';
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
      return this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.User;
    },
    isGroupShare() {
      return this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Group;
    },
    allowsFileDrop() {
      if (this.isFolder && this.config.isPublicUploadEnabled) {
        if (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link || this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email) {
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
        return t('files_sharing', 'Save share');
      }
      return t('files_sharing', 'Update share');
    },
    resharingIsPossible() {
      return this.config.isResharingAllowed && this.share.type !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link && this.share.type !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email;
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
    canRemoveReadPermission() {
      return this.allowsFileDrop && (this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link || this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email);
    },
    // if newPassword exists, but is empty, it means
    // the user deleted the original password
    hasUnsavedPassword() {
      return this.share.newPassword !== undefined;
    },
    passwordExpirationTime() {
      if (!this.isValidShareAttribute(this.share.passwordExpirationTime)) {
        return null;
      }
      const expirationTime = (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_3__["default"])(this.share.passwordExpirationTime);
      if (expirationTime.diff((0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_3__["default"])()) < 0) {
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
      return this.share ? this.share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email : false;
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

      // Is Talk enabled?
      return OC.appswebroots.spreed !== undefined;
    },
    canChangeHideDownload() {
      const hasDisabledDownload = shareAttribute => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.value === false;
      return this.fileInfo.shareAttributes.some(hasDisabledDownload);
    },
    customPermissionsList() {
      // Key order will be different, because ATOMIC_PERMISSIONS are numbers
      const translatedPermissions = {
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.READ]: this.t('files_sharing', 'Read'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.CREATE]: this.t('files_sharing', 'Create'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.UPDATE]: this.t('files_sharing', 'Edit'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.SHARE]: this.t('files_sharing', 'Share'),
        [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.DELETE]: this.t('files_sharing', 'Delete')
      };
      const permissionsList = [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.READ, ...(this.isFolder ? [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.CREATE] : []), _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.UPDATE, ...(this.resharingIsPossible ? [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.SHARE] : []), ...(this.isFolder ? [_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.DELETE] : [])];
      return permissionsList.filter(permission => (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.hasPermissions)(this.share.permissions, permission)).map((permission, index) => index === 0 ? translatedPermissions[permission] : translatedPermissions[permission].toLocaleLowerCase((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.getLanguage)())).join(', ');
    },
    advancedControlExpandedValue() {
      return this.advancedSectionAccordionExpanded ? 'true' : 'false';
    },
    errorPasswordLabel() {
      if (this.passwordError) {
        return t('files_sharing', 'Password field cannot be empty');
      }
      return undefined;
    },
    passwordHint() {
      if (this.isNewShare || this.hasUnsavedPassword) {
        return undefined;
      }
      return t('files_sharing', 'Replace current password');
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    sortedExternalShareActions() {
      return this.externalShareActions.filter(action => action.enabled((0,vue__WEBPACK_IMPORTED_MODULE_6__.toRaw)(this.share), (0,vue__WEBPACK_IMPORTED_MODULE_6__.toRaw)(this.fileInfo.node))).sort((a, b) => a.order - b.order);
    },
    /**
     * Additional actions for the menu
     *
     * @return {Array}
     */
    externalLegacyShareActions() {
      const filterValidAction = action => (action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link) || action.shareType.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email)) && action.advanced;
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('legacy details tab', {
        ExternalShareActions: this.ExternalShareActions
      });
      // filter only the advanced registered actions for said link
      return this.ExternalShareActions.actions.filter(filterValidAction);
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
    _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Share object received', {
      share: this.share
    });
    _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Configuration object received', {
      config: this.config
    });
  },
  mounted() {
    this.$refs.quickPermissions?.querySelector('input:checked')?.focus();
  },
  methods: {
    /**
     * Set a share attribute on the current share
     *
     * @param {string} scope The attribute scope
     * @param {string} key The attribute key
     * @param {boolean} value The value
     */
    setShareAttribute(scope, key, value) {
      if (!this.share.attributes) {
        this.$set(this.share, 'attributes', []);
      }
      const attribute = this.share.attributes.find(attr => attr.scope === scope || attr.key === key);
      if (attribute) {
        attribute.value = value;
      } else {
        this.share.attributes.push({
          scope,
          key,
          value
        });
      }
    },
    /**
     * Get the value of a share attribute
     *
     * @param {string} scope The attribute scope
     * @param {string} key The attribute key
     * @param {undefined|boolean} fallback The fallback to return if not found
     */
    getShareAttribute(scope, key, fallback = undefined) {
      const attribute = this.share.attributes?.find(attr => attr.scope === scope && attr.key === key);
      return attribute?.value ?? fallback;
    },
    async generateNewToken() {
      if (this.loadingToken) {
        return;
      }
      this.loadingToken = true;
      try {
        this.share.token = await (0,_services_TokenService_ts__WEBPACK_IMPORTED_MODULE_35__.generateToken)();
      } catch {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(t('files_sharing', 'Failed to generate a new token'));
      }
      this.loadingToken = false;
    },
    cancel() {
      this.share.token = this.initialToken;
      this.$emit('close-sharing-details');
    },
    updateAtomicPermissions({
      isReadChecked = this.hasRead,
      isEditChecked = this.canEdit,
      isCreateChecked = this.canCreate,
      isDeleteChecked = this.canDelete,
      isReshareChecked = this.canReshare
    } = {}) {
      // calc permissions if checked

      if (!this.isFolder && (isCreateChecked || isDeleteChecked)) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Ignoring create/delete permissions for file share — only available for folders');
        isCreateChecked = false;
        isDeleteChecked = false;
      }
      const permissions = 0 | (isReadChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.READ : 0) | (isCreateChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.CREATE : 0) | (isDeleteChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.DELETE : 0) | (isEditChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.UPDATE : 0) | (isReshareChecked ? _lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.SHARE : 0);
      this.share.permissions = permissions;
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
    async initializeAttributes() {
      if (this.isNewShare) {
        if ((this.config.enableLinkPasswordByDefault || this.isPasswordEnforced) && this.isPublicShare) {
          this.passwordProtectedState = true;
          const generatedPassword = await (0,_utils_GeneratePassword_ts__WEBPACK_IMPORTED_MODULE_36__["default"])(true);
          if (!this.share.newPassword) {
            this.$set(this.share, 'newPassword', generatedPassword);
          }
          this.advancedSectionAccordionExpanded = true;
        }
        /* Set default expiration dates if configured */
        if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
          this.share.expireDate = this.config.defaultExpirationDate.toDateString();
        } else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
          this.share.expireDate = this.config.defaultRemoteExpirationDateString.toDateString();
        } else if (this.config.isDefaultInternalExpireDateEnabled) {
          this.share.expireDate = this.config.defaultInternalExpirationDate.toDateString();
        }
        if (this.isValidShareAttribute(this.share.expireDate)) {
          this.advancedSectionAccordionExpanded = true;
        }
        return;
      }

      // If there is an enforced expiry date, then existing shares created before enforcement
      // have no expiry date, hence we set it here.
      if (!this.isValidShareAttribute(this.share.expireDate) && this.isExpiryDateEnforced) {
        this.hasExpirationDate = true;
      }
      if (this.isValidShareAttribute(this.share.password) || this.isValidShareAttribute(this.share.expireDate) || this.isValidShareAttribute(this.share.label)) {
        this.advancedSectionAccordionExpanded = true;
      }
      if (this.isValidShareAttribute(this.share.note)) {
        this.writeNoteToRecipientIsChecked = true;
        this.advancedSectionAccordionExpanded = true;
      }
    },
    handleShareType() {
      if ('shareType' in this.share) {
        this.share.type = this.share.shareType;
      } else if (this.share.share_type) {
        this.share.type = this.share.share_type;
      }
    },
    handleDefaultPermissions() {
      if (this.isNewShare) {
        const defaultPermissions = this.config.defaultPermissions;
        const permissionsWithoutShare = defaultPermissions & ~_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.ATOMIC_PERMISSIONS.SHARE;
        const basePermissions = (0,_lib_SharePermissionsToolBox_js__WEBPACK_IMPORTED_MODULE_31__.getBundledPermissions)(true);
        if (permissionsWithoutShare === basePermissions.READ_ONLY || permissionsWithoutShare === basePermissions.ALL || permissionsWithoutShare === basePermissions.ALL_FILE) {
          this.sharingPermission = permissionsWithoutShare.toString();
        } else {
          this.sharingPermission = 'custom';
          this.share.permissions = defaultPermissions;
          this.advancedSectionAccordionExpanded = true;
          this.setCustomPermissions = true;
        }
      }
      // Read permission required for share creation
      if (!this.canRemoveReadPermission) {
        this.hasRead = true;
      }
    },
    handleCustomPermissions() {
      if (!this.isNewShare && (this.hasCustomPermissions || this.share.setCustomPermissions)) {
        this.sharingPermission = 'custom';
        this.advancedSectionAccordionExpanded = true;
        this.setCustomPermissions = true;
      } else if (this.share.permissions) {
        this.sharingPermission = this.share.permissions.toString();
      }
    },
    initializePermissions() {
      this.handleShareType();
      this.handleDefaultPermissions();
      this.handleCustomPermissions();
    },
    async saveShare() {
      const permissionsAndAttributes = ['permissions', 'attributes', 'note', 'expireDate'];
      const publicShareAttributes = ['label', 'hideDownload'];
      // Only include password if it's being actively changed
      if (this.hasUnsavedPassword) {
        publicShareAttributes.push('password');
      }
      if (this.config.allowCustomTokens) {
        publicShareAttributes.push('token');
      }
      if (this.isPublicShare) {
        permissionsAndAttributes.push(...publicShareAttributes);
      }
      const sharePermissionsSet = parseInt(this.sharingPermission);
      if (this.setCustomPermissions) {
        this.updateAtomicPermissions();
      } else {
        this.share.permissions = sharePermissionsSet;
      }
      if (!this.isFolder && this.share.permissions === this.bundledPermissions.ALL) {
        // It's not possible to create an existing file.
        this.share.permissions = this.bundledPermissions.ALL_FILE;
      }
      if (!this.writeNoteToRecipientIsChecked) {
        this.share.note = '';
      }
      if (this.isPasswordProtected) {
        if (this.isPublicShare && this.isNewShare && !this.isValidShareAttribute(this.share.newPassword)) {
          this.passwordError = true;
          return;
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
        incomingShare.expireDate = this.hasExpirationDate ? this.share.expireDate : '';
        if (this.isPasswordProtected) {
          incomingShare.password = this.share.newPassword;
        }
        let share;
        try {
          this.creating = true;
          share = await this.addShare(incomingShare);
        } catch {
          this.creating = false;
          // Error is already handled by ShareRequests mixin
          return;
        }

        // ugly hack to make code work - we need the id to be set but at the same time we need to keep values we want to update
        this.share._share.id = share.id;
        await this.queueUpdate(...permissionsAndAttributes);
        // Also a ugly hack to update the updated permissions
        for (const prop of permissionsAndAttributes) {
          if (prop in share && prop in this.share) {
            try {
              share[prop] = this.share[prop];
            } catch {
              share._share[prop] = this.share[prop];
            }
          }
        }
        this.share = share;
        this.creating = false;
        this.$emit('add:share', this.share);
      } else {
        // Let's update after creation as some attrs are only available after creation
        await this.queueUpdate(...permissionsAndAttributes);
        this.$emit('update:share', this.share);
      }
      await this.getNode();
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:updated', this.node);
      if (this.$refs.externalShareActions?.length > 0) {
        /** @type {import('vue').ComponentPublicInstance<SidebarTabExternalAction>[]} */
        const actions = this.$refs.externalShareActions;
        await Promise.allSettled(actions.map(action => action.save()));
      }
      if (this.$refs.externalLinkActions?.length > 0) {
        await Promise.allSettled(this.$refs.externalLinkActions.map(action => {
          if (typeof action.$children.at(0)?.onSave !== 'function') {
            return Promise.resolve();
          }
          return action.$children.at(0)?.onSave?.();
        }));
      }
      this.$emit('close-sharing-details');
    },
    /**
     * Process the new share request
     *
     * @param {Share} share incoming share object
     */
    async addShare(share) {
      _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].debug('Adding a new share from the input for', {
        share
      });
      const path = this.path;
      try {
        const resultingShare = await this.createShare({
          path,
          shareType: share.shareType,
          shareWith: share.shareWith,
          permissions: share.permissions,
          expireDate: share.expireDate,
          attributes: JSON.stringify(share.attributes),
          ...(share.note ? {
            note: share.note
          } : {}),
          ...(share.password ? {
            password: share.password
          } : {})
        });
        return resultingShare;
      } catch (error) {
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_34__["default"].error('Error while adding new share', {
          error
        });
        throw error;
      } finally {
        // this.loading = false // No loader here yet
      }
    },
    async removeShare() {
      await this.onDelete();
      await this.getNode();
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_1__.emit)('files:node:updated', this.node);
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
      if (password === '') {
        this.$delete(this.share, 'newPassword');
        this.passwordError = this.isNewShare && this.isPasswordEnforced;
        return;
      }
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
      if (this.isEmailShareType || this.hasUnsavedPassword) {
        this.queueUpdate('sendPasswordByTalk', 'password');
      } else {
        this.queueUpdate('sendPasswordByTalk');
      }
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
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Link:
          return vue_material_design_icons_Link_vue__WEBPACK_IMPORTED_MODULE_22__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Guest:
          return vue_material_design_icons_AccountCircleOutline_vue__WEBPACK_IMPORTED_MODULE_15__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup:
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Group:
          return vue_material_design_icons_AccountGroup_vue__WEBPACK_IMPORTED_MODULE_16__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Email:
          return vue_material_design_icons_Email_vue__WEBPACK_IMPORTED_MODULE_20__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Team:
          return vue_material_design_icons_CircleOutline_vue__WEBPACK_IMPORTED_MODULE_17__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Room:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_27__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.Deck:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_27__["default"];
        case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.ScienceMesh:
          return vue_material_design_icons_ShareCircle_vue__WEBPACK_IMPORTED_MODULE_27__["default"];
        default:
          return null;
        // Or a default icon component if needed
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SharingEntryInherited.vue */ "./apps/files_sharing/src/components/SharingEntryInherited.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingInherited',
  components: {
    NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_2__["default"],
    SharingEntryInherited: _components_SharingEntryInherited_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    fileInfo: {
      type: Object,
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
      return this.showInheritedShares && this.shares.length === 0 ? t('files_sharing', 'No other accounts with access found') : '';
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
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', {
          path: this.fullPath
        });
        const shares = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
        this.shares = shares.data.ocs.data.map(share => new _models_Share_ts__WEBPACK_IMPORTED_MODULE_5__["default"](share)).sort((a, b) => b.createdTime - a.createdTime);
        this.loaded = true;
      } catch {
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
      this.shares.splice(index, 1);
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../components/SharingEntryLink.vue */ "./apps/files_sharing/src/components/SharingEntryLink.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");






/** @typedef {import('../models/Share.js').default} Share */
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingLinkList',
  components: {
    SharingEntryLink: _components_SharingEntryLink_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  mixins: [_mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_4__["default"]],
  props: {
    fileInfo: {
      type: Object,
      required: true
    },
    shares: {
      type: Array,
      required: true
    },
    canReshare: {
      type: Boolean,
      required: true
    }
  },
  data() {
    return {
      canLinkShare: (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_0__.getCapabilities)().files_sharing.public.enabled
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
      return this.shares.filter(share => share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.Link).length > 0;
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
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
    /**
     * Add a new share into the link shares list
     * and return the newly created share component
     *
     * @param {Share} share the share to add to the array
     * @param {Function} resolve a function to run after the share is added and its component initialized
     */
    addShare(share, resolve) {
      // eslint-disable-next-line vue/no-mutating-props
      this.shares.push(share);
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

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../components/SharingEntry.vue */ "./apps/files_sharing/src/components/SharingEntry.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingList',
  components: {
    SharingEntry: _components_SharingEntry_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  mixins: [_mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    fileInfo: {
      type: Object,
      required: true
    },
    shares: {
      type: Array,
      required: true
    }
  },
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t
    };
  },
  computed: {
    hasShares() {
      return this.shares.length === 0;
    },
    isUnique() {
      return share => {
        return [...this.shares].filter(item => {
          return share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_1__.ShareType.User && share.shareWithDisplayName === item.shareWithDisplayName;
        }).length <= 1;
      };
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js"
/*!*********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.js");
/* harmony import */ var _nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/sharing/ui */ "./node_modules/@nextcloud/sharing/dist/ui/index.js");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCollectionList__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcCollectionList */ "./node_modules/@nextcloud/vue/dist/Components/NcCollectionList.mjs");
/* harmony import */ var _nextcloud_vue_components_NcPopover__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcPopover */ "./node_modules/@nextcloud/vue/dist/Components/NcPopover.mjs");
/* harmony import */ var vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! vue-material-design-icons/InformationOutline.vue */ "./node_modules/vue-material-design-icons/InformationOutline.vue");
/* harmony import */ var _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ../components/SharingEntryInternal.vue */ "./apps/files_sharing/src/components/SharingEntryInternal.vue");
/* harmony import */ var _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../components/SharingEntrySimple.vue */ "./apps/files_sharing/src/components/SharingEntrySimple.vue");
/* harmony import */ var _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ../components/SharingInput.vue */ "./apps/files_sharing/src/components/SharingInput.vue");
/* harmony import */ var _components_SidebarTabExternal_SidebarTabExternalSection_vue__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../components/SidebarTabExternal/SidebarTabExternalSection.vue */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue");
/* harmony import */ var _components_SidebarTabExternal_SidebarTabExternalSectionLegacy_vue__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue");
/* harmony import */ var _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! ./SharingDetailsTab.vue */ "./apps/files_sharing/src/views/SharingDetailsTab.vue");
/* harmony import */ var _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! ./SharingInherited.vue */ "./apps/files_sharing/src/views/SharingInherited.vue");
/* harmony import */ var _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! ./SharingLinkList.vue */ "./apps/files_sharing/src/views/SharingLinkList.vue");
/* harmony import */ var _SharingList_vue__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! ./SharingList.vue */ "./apps/files_sharing/src/views/SharingList.vue");
/* harmony import */ var _mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! ../mixins/ShareDetails.js */ "./apps/files_sharing/src/mixins/ShareDetails.js");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! ../services/ConfigService.ts */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _services_logger_ts__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! ../services/logger.ts */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! ../utils/SharedWithMe.js */ "./apps/files_sharing/src/utils/SharedWithMe.js");




























const productName = window.OC.theme.productName;
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SharingTab',
  components: {
    InfoIcon: vue_material_design_icons_InformationOutline_vue__WEBPACK_IMPORTED_MODULE_13__["default"],
    NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcCollectionList: _nextcloud_vue_components_NcCollectionList__WEBPACK_IMPORTED_MODULE_11__["default"],
    NcPopover: _nextcloud_vue_components_NcPopover__WEBPACK_IMPORTED_MODULE_12__["default"],
    SharingEntryInternal: _components_SharingEntryInternal_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    SharingEntrySimple: _components_SharingEntrySimple_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    SharingInherited: _SharingInherited_vue__WEBPACK_IMPORTED_MODULE_20__["default"],
    SharingInput: _components_SharingInput_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    SharingLinkList: _SharingLinkList_vue__WEBPACK_IMPORTED_MODULE_21__["default"],
    SharingList: _SharingList_vue__WEBPACK_IMPORTED_MODULE_22__["default"],
    SharingDetailsTab: _SharingDetailsTab_vue__WEBPACK_IMPORTED_MODULE_19__["default"],
    SidebarTabExternalSection: _components_SidebarTabExternal_SidebarTabExternalSection_vue__WEBPACK_IMPORTED_MODULE_17__["default"],
    SidebarTabExternalSectionLegacy: _components_SidebarTabExternal_SidebarTabExternalSectionLegacy_vue__WEBPACK_IMPORTED_MODULE_18__["default"]
  },
  mixins: [_mixins_ShareDetails_js__WEBPACK_IMPORTED_MODULE_23__["default"]],
  props: {
    fileInfo: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      config: new _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_25__["default"](),
      deleteEvent: null,
      error: '',
      expirationInterval: null,
      loading: true,
      // reshare Share object
      reshare: null,
      sharedWithMe: {},
      shares: [],
      linkShares: [],
      externalShares: [],
      legacySections: OCA.Sharing.ShareTabSections.getSections(),
      sections: (0,_nextcloud_sharing_ui__WEBPACK_IMPORTED_MODULE_8__.getSidebarSections)(),
      projectsEnabled: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)('core', 'projects_enabled', false),
      showSharingDetailsView: false,
      shareDetailsData: {},
      returnFocusElement: null,
      internalSharesHelpText: t('files_sharing', 'Share files within your organization. Recipients who can already view the file can also use this link for easy access.'),
      externalSharesHelpText: t('files_sharing', 'Share files with others outside your organization via public links and email addresses. You can also share to {productName} accounts on other instances using their federated cloud ID.', {
        productName
      }),
      additionalSharesHelpText: t('files_sharing', 'Shares from apps or other sources which are not included in internal or external shares.')
    };
  },
  computed: {
    /**
     * Are any sections registered by other apps.
     *
     * @return {boolean}
     */
    hasExternalSections() {
      return this.sections.length > 0 || this.legacySections.length > 0;
    },
    sortedExternalSections() {
      return this.sections.filter(section => section.enabled(this.fileInfo.node)).sort((a, b) => a.order - b.order);
    },
    /**
     * Is this share shared with me?
     *
     * @return {boolean}
     */
    isSharedWithMe() {
      return !!this.sharedWithMe?.user;
    },
    /**
     * Is link sharing allowed for the current user?
     *
     * @return {boolean}
     */
    isLinkSharingAllowed() {
      const currentUser = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)();
      if (!currentUser) {
        return false;
      }
      const capabilities = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_2__.getCapabilities)();
      const publicSharing = capabilities.files_sharing?.public || {};
      return publicSharing.enabled === true;
    },
    canReshare() {
      return !!(this.fileInfo.permissions & OC.PERMISSION_SHARE) || !!(this.reshare && this.reshare.hasSharePermission && this.config.isResharingAllowed);
    },
    internalShareInputPlaceholder() {
      return this.config.showFederatedSharesAsInternal && this.config.isFederationEnabled
      // TRANSLATORS: Type as in with a keyboard
      ? t('files_sharing', 'Type names, teams, federated cloud IDs')
      // TRANSLATORS: Type as in with a keyboard
      : t('files_sharing', 'Type names or teams');
    },
    externalShareInputPlaceholder() {
      if (!this.isLinkSharingAllowed) {
        // TRANSLATORS: Type as in with a keyboard
        return this.config.isFederationEnabled ? t('files_sharing', 'Type a federated cloud ID') : '';
      }
      return !this.config.showFederatedSharesAsInternal && !this.config.isFederationEnabled
      // TRANSLATORS: Type as in with a keyboard
      ? t('files_sharing', 'Type an email')
      // TRANSLATORS: Type as in with a keyboard
      : t('files_sharing', 'Type an email or federated cloud ID');
    }
  },
  watch: {
    fileInfo: {
      immediate: true,
      handler(newValue, oldValue) {
        if (oldValue?.id === undefined || oldValue?.id !== newValue?.id) {
          this.resetState();
          this.getShares();
        }
      }
    }
  },
  methods: {
    /**
     * Get the existing shares infos
     */
    async getShares() {
      try {
        this.loading = true;

        // init params
        const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_6__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
        const format = 'json';
        // TODO: replace with proper getFUllpath implementation of our own FileInfo model
        const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/');

        // fetch shares
        const fetchShares = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(shareUrl, {
          params: {
            format,
            path,
            reshares: true
          }
        });
        const fetchSharedWithMe = _nextcloud_axios__WEBPACK_IMPORTED_MODULE_1__["default"].get(shareUrl, {
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
        if (error?.response?.data?.ocs?.meta?.message) {
          this.error = error.response.data.ocs.meta.message;
        } else {
          this.error = t('files_sharing', 'Unable to load the shares list');
        }
        this.loading = false;
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_26__["default"].error('Error loading the shares list', error);
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
      this.externalShares = [];
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
      const expiration = (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])(share.expireDate).unix();
      this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'Expires {relativetime}', {
        relativetime: (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])(expiration * 1000).fromNow()
      }));

      // share have expired
      if ((0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])().unix() > expiration) {
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
        const shares = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_3__.orderBy)(data.ocs.data.map(share => new _models_Share_ts__WEBPACK_IMPORTED_MODULE_24__["default"](share)), [
        // First order by the "share with" label
        share => share.shareWithDisplayName,
        // Then by the label
        share => share.label,
        // And last resort order by createdTime
        share => share.createdTime]);
        for (const share of shares) {
          const shareList = this.findShareListByShare(share);
          shareList.push(share);
        }
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_26__["default"].debug(`Processed ${this.linkShares.length} link share(s)`);
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_26__["default"].debug(`Processed ${this.shares.length} share(s)`);
        _services_logger_ts__WEBPACK_IMPORTED_MODULE_26__["default"].debug(`Processed ${this.externalShares.length} external share(s)`);
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
        const share = new _models_Share_ts__WEBPACK_IMPORTED_MODULE_24__["default"](data);
        const title = (0,_utils_SharedWithMe_js__WEBPACK_IMPORTED_MODULE_27__.shareWithTitle)(share);
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
        if (share.expireDate && (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])(share.expireDate).unix() > (0,_nextcloud_moment__WEBPACK_IMPORTED_MODULE_5__["default"])().unix()) {
          // first update
          this.updateExpirationSubtitle(share);
          // interval update
          this.expirationInterval = setInterval(this.updateExpirationSubtitle, 10000, share);
        }
      } else if (this.fileInfo && this.fileInfo.shareOwnerId !== undefined ? this.fileInfo.shareOwnerId !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)().uid : false) {
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
      const shareList = this.findShareListByShare(share);
      shareList.unshift(share);
      this.awaitForShare(share, resolve);
    },
    /**
     * Remove a share from the shares list
     *
     * @param {Share} share the share to remove
     */
    removeShare(share) {
      this.removeShareFromList(this.findShareListByShare(share), share);
    },
    findShareListByShare(share) {
      if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Remote || share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.RemoteGroup) {
        if (this.config.showFederatedSharesToTrustedServersAsInternal) {
          return share.isTrustedServer ? this.shares : this.externalShares;
        } else if (this.config.showFederatedSharesAsInternal) {
          return this.shares;
        } else {
          return this.externalShares;
        }
      } else if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Email || share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Link) {
        return this.linkShares;
      } else {
        return this.shares;
      }
    },
    removeShareFromList(shareList, share) {
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
      this.$nextTick(() => {
        let listComponent = this.$refs.shareList;
        // Only mail shares comes from the input, link shares
        // are managed internally in the SharingLinkList component
        if (share.type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_7__.ShareType.Email) {
          listComponent = this.$refs.linkShareList;
        }
        const newShare = listComponent.$children.find(component => component.share === share);
        if (newShare) {
          resolve(newShare);
        }
      });
    },
    toggleShareDetailsView(eventData) {
      if (!this.showSharingDetailsView) {
        const isAction = Array.from(document.activeElement.classList).some(className => className.startsWith('action-'));
        if (isAction) {
          const menuId = document.activeElement.closest('[role="menu"]')?.id;
          this.returnFocusElement = document.querySelector(`[aria-controls="${menuId}"]`);
        } else {
          this.returnFocusElement = document.activeElement;
        }
      }
      if (eventData) {
        this.shareDetailsData = eventData;
      }
      this.showSharingDetailsView = !this.showSharingDetailsView;
      if (!this.showSharingDetailsView) {
        this.$nextTick(() => {
          // Wait for next tick as the element must be visible to be focused
          this.returnFocusElement?.focus();
          this.returnFocusElement = null;
        });
      }
    }
  }
});

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "share-expiry-time"
  }, [_c("NcPopover", {
    attrs: {
      "popup-role": "dialog"
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function () {
        return [_vm.expiryTime ? _c("NcButton", {
          staticClass: "hint-icon",
          attrs: {
            variant: "tertiary",
            "aria-label": _vm.t("files_sharing", "Share expiration: {date}", {
              date: new Date(_vm.expiryTime).toLocaleString()
            })
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("ClockIcon", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 3754271979)
        }) : _vm._e()];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("h3", {
    staticClass: "hint-heading"
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Share Expiration")) + "\n\t\t")]), _vm._v(" "), _vm.expiryTime ? _c("p", {
    staticClass: "hint-body"
  }, [_c("NcDateTime", {
    attrs: {
      timestamp: _vm.expiryTime,
      format: _vm.timeFormat,
      "relative-time": false
    }
  }), _vm._v(" ("), _c("NcDateTime", {
    attrs: {
      timestamp: _vm.expiryTime
    }
  }), _vm._v(")\n\t\t")], 1) : _vm._e()])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      "is-no-user": _vm.share.type !== _vm.ShareType.User,
      user: _vm.share.shareWith,
      "display-name": _vm.share.shareWithDisplayName,
      "menu-position": "left",
      url: _vm.share.shareWithAvatar
    }
  }), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__summary"
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
  }, [_vm._v("\n\t\t\t\t\t(" + _vm._s(_vm.share.shareWithDisplayNameUnique) + ")\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.hasStatus && _vm.share.status.message ? _c("small", [_vm._v("(" + _vm._s(_vm.share.status.message) + ")")]) : _vm._e()])]), _vm._v(" "), _c("SharingEntryQuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  })], 1), _vm._v(" "), _vm.share && _vm.share.expireDate ? _c("ShareExpiryTime", {
    attrs: {
      share: _vm.share
    }
  }) : _vm._e(), _vm._v(" "), _vm.share.canEdit ? _c("NcButton", {
    staticClass: "sharing-entry__action",
    attrs: {
      "data-cy-files-sharing-share-actions": "",
      "aria-label": _vm.t("files_sharing", "Open Sharing Details"),
      variant: "tertiary"
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
    }], null, false, 1700783217)
  }) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true"
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
  }, [_vm._v(" "), _c("NcActionButton", {
    attrs: {
      title: _vm.copyLinkTooltip,
      "aria-label": _vm.copyLinkTooltip
    },
    on: {
      click: _vm.copyLink
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm.copied && _vm.copySuccess ? _c("CheckIcon", {
          staticClass: "icon-checkmark-color",
          attrs: {
            size: 20
          }
        }) : _c("ClipboardIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  })], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    staticClass: "sharing-entry__desc"
  }, [_c("span", {
    staticClass: "sharing-entry__title",
    attrs: {
      title: _vm.title
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.title) + "\n\t\t\t")]), _vm._v(" "), _vm.subtitle ? _c("p", [_vm._v("\n\t\t\t\t" + _vm._s(_vm.subtitle) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share && _vm.share.permissions !== undefined ? _c("SharingEntryQuickShareSelect", {
    attrs: {
      share: _vm.share,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": function ($event) {
        return _vm.openShareDetailsForCustomSettings(_vm.share);
      }
    }
  }) : _vm._e()], 1), _vm._v(" "), _c("div", {
    staticClass: "sharing-entry__actions"
  }, [_vm.share && _vm.share.expireDate ? _c("ShareExpiryTime", {
    attrs: {
      share: _vm.share
    }
  }) : _vm._e(), _vm._v(" "), _c("div", [_vm.share && (!_vm.isEmailShareType || _vm.isFileRequest) && _vm.share.token ? _c("NcActions", {
    ref: "copyButton",
    staticClass: "sharing-entry__copy"
  }, [_c("NcActionButton", {
    attrs: {
      "aria-label": _vm.copyLinkLabel,
      title: _vm.copySuccess ? _vm.t("files_sharing", "Successfully copied public link") : undefined,
      href: _vm.shareLink
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.copyLink.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          staticClass: "sharing-entry__copy-icon",
          class: {
            "sharing-entry__copy-icon--success": _vm.copySuccess
          },
          attrs: {
            path: _vm.copySuccess ? _vm.mdiCheck : _vm.mdiContentCopy
          }
        })];
      },
      proxy: true
    }], null, false, 1728815133)
  })], 1) : _vm._e()], 1)], 1)]), _vm._v(" "), !_vm.pending && _vm.pendingDataIsMissing ? _c("NcActions", {
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
      close: _vm.onCancel
    }
  }, [_vm.errors.pending ? _c("NcActionText", {
    staticClass: "error",
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("ErrorIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1966124155)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.errors.pending) + "\n\t\t")]) : _c("NcActionText", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Please enter the following required information before creating the share")) + "\n\t\t")]), _vm._v(" "), _vm.pendingPassword ? _c("NcActionCheckbox", {
    staticClass: "share-link-password-checkbox",
    attrs: {
      disabled: _vm.config.enforcePasswordForPublicLink || _vm.saving
    },
    on: {
      uncheck: _vm.onPasswordDisable
    },
    model: {
      value: _vm.isPasswordProtected,
      callback: function ($$v) {
        _vm.isPasswordProtected = $$v;
      },
      expression: "isPasswordProtected"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.config.enforcePasswordForPublicLink ? _vm.t("files_sharing", "Password protection (enforced)") : _vm.t("files_sharing", "Password protection")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.pendingEnforcedPassword || _vm.isPasswordProtected ? _c("NcActionInput", {
    staticClass: "share-link-password",
    attrs: {
      label: _vm.t("files_sharing", "Enter a password"),
      disabled: _vm.saving,
      required: _vm.config.enableLinkPasswordByDefault || _vm.config.enforcePasswordForPublicLink,
      minlength: _vm.isPasswordPolicyEnabled && _vm.config.passwordPolicy.minLength,
      autocomplete: "new-password"
    },
    on: {
      submit: function ($event) {
        return _vm.onNewLinkShare(true);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("LockIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2056568168),
    model: {
      value: _vm.share.newPassword,
      callback: function ($$v) {
        _vm.$set(_vm.share, "newPassword", $$v);
      },
      expression: "share.newPassword"
    }
  }) : _vm._e(), _vm._v(" "), _vm.pendingDefaultExpirationDate ? _c("NcActionCheckbox", {
    staticClass: "share-link-expiration-date-checkbox",
    attrs: {
      disabled: _vm.pendingEnforcedExpirationDate || _vm.saving
    },
    on: {
      "update:model-value": _vm.onExpirationDateToggleUpdate
    },
    model: {
      value: _vm.defaultExpirationDateEnabled,
      callback: function ($$v) {
        _vm.defaultExpirationDateEnabled = $$v;
      },
      expression: "defaultExpirationDateEnabled"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.config.isDefaultExpireDateEnforced ? _vm.t("files_sharing", "Enable link expiration (enforced)") : _vm.t("files_sharing", "Enable link expiration")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), (_vm.pendingDefaultExpirationDate || _vm.pendingEnforcedExpirationDate) && _vm.defaultExpirationDateEnabled ? _c("NcActionInput", {
    staticClass: "share-link-expire-date",
    attrs: {
      "data-cy-files-sharing-expiration-date-input": "",
      label: _vm.pendingEnforcedExpirationDate ? _vm.t("files_sharing", "Enter expiration date (enforced)") : _vm.t("files_sharing", "Enter expiration date"),
      disabled: _vm.saving,
      "is-native-picker": true,
      "hide-label": true,
      "model-value": new Date(_vm.share.expireDate),
      type: "date",
      min: _vm.dateTomorrow,
      max: _vm.maxExpirationDateEnforced
    },
    on: {
      "update:model-value": _vm.onExpirationChange,
      change: _vm.expirationDateChanged
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconCalendarBlank", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 3418578971)
  }) : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      disabled: _vm.pendingEnforcedPassword && !_vm.share.newPassword
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare(true);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CheckIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2630571749)
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Create share")) + "\n\t\t")]), _vm._v(" "), _c("NcActionButton", {
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onCancel.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2428343285)
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
      disabled: _vm.saving,
      "close-after-click": true
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
        return [_c("Tune", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1300586850)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Customize link")) + "\n\t\t\t\t")])] : _vm._e(), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      "close-after-click": true
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        _vm.showQRCode = true;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconQr", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1082198240)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Generate QR code")) + "\n\t\t\t")]), _vm._v(" "), _c("NcActionSeparator"), _vm._v(" "), _vm._l(_vm.sortedExternalShareActions, function (action) {
    return _c("NcActionButton", {
      key: action.id,
      on: {
        click: function ($event) {
          return action.exec(_vm.share, _vm.fileInfo.node);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c("NcIconSvgWrapper", {
            attrs: {
              svg: action.iconSvg
            }
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t\t\t" + _vm._s(action.label(_vm.share, _vm.fileInfo.node)) + "\n\t\t\t")]);
  }), _vm._v(" "), _vm._l(_vm.externalLegacyShareActions, function (action) {
    return _c("SidebarTabExternalActionLegacy", {
      key: action.id,
      attrs: {
        id: action.id,
        action: action,
        "file-info": _vm.fileInfo,
        share: _vm.share
      }
    });
  }), _vm._v(" "), !_vm.isEmailShareType && _vm.canReshare ? _c("NcActionButton", {
    staticClass: "new-share-link",
    on: {
      click: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return _vm.onNewLinkShare.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("PlusIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2953566425)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Add another link")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.share.canDelete ? _c("NcActionButton", {
    attrs: {
      disabled: _vm.saving
    },
    on: {
      click: function ($event) {
        $event.preventDefault();
        return _vm.onDelete.apply(null, arguments);
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("CloseIcon", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2428343285)
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
  }) : _vm._e()], 2) : _c("NcLoadingIcon", {
    staticClass: "sharing-entry__loading"
  }), _vm._v(" "), _vm.showQRCode ? _c("NcDialog", {
    attrs: {
      size: "normal",
      open: _vm.showQRCode,
      name: _vm.title,
      "close-on-click-outside": true
    },
    on: {
      "update:open": function ($event) {
        _vm.showQRCode = $event;
      },
      close: function ($event) {
        _vm.showQRCode = false;
      }
    }
  }, [_c("div", {
    staticClass: "qr-code-dialog"
  }, [_c("VueQrcode", {
    staticClass: "qr-code-dialog__img",
    attrs: {
      tag: "img",
      value: _vm.shareLink
    }
  })], 1)]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("NcActions", {
    ref: "quickShareActions",
    staticClass: "share-select",
    attrs: {
      "menu-name": _vm.selectedOption,
      "aria-label": _vm.ariaLabel,
      variant: "tertiary-no-background",
      disabled: !_vm.share.canEdit,
      "force-name": ""
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("DropdownIcon", {
          attrs: {
            size: 15
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _vm._l(_vm.options, function (option) {
    return _c("NcActionButton", {
      key: option.label,
      attrs: {
        type: "radio",
        "model-value": option.label === _vm.selectedOption,
        "close-after-click": ""
      },
      on: {
        click: function ($event) {
          return _vm.selectOption(option.label);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c(option.icon, {
            tag: "component"
          })];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(option.label) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c"
/*!***************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c ***!
  \***************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    staticClass: "hidden-visually",
    attrs: {
      for: _vm.shareInputId
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.isExternal ? _vm.t("files_sharing", "Enter external recipients") : _vm.t("files_sharing", "Search for internal recipients")) + "\n\t")]), _vm._v(" "), _c("NcSelect", {
    ref: "select",
    staticClass: "sharing-search__input",
    attrs: {
      "input-id": _vm.shareInputId,
      disabled: !_vm.canReshare,
      loading: _vm.loading,
      filterable: false,
      placeholder: _vm.inputPlaceholder,
      "clear-search-on-blur": () => false,
      "user-select": true,
      options: _vm.options,
      "label-outside": true
    },
    on: {
      search: _vm.asyncFind,
      "option:selected": _vm.onSelected
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function ({
        search
      }) {
        return [_vm._v("\n\t\t\t" + _vm._s(search ? _vm.noResultText : _vm.placeholder) + "\n\t\t")];
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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1 ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_vm.action.element, {
    key: _vm.action.id,
    ref: "actionElement",
    tag: "component",
    domProps: {
      share: _vm.share,
      node: _vm.node,
      onSave: _setup.onSave
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c(_vm.data.is, _vm._g(_vm._b({
    tag: "component"
  }, "component", _vm.data, false), _vm.action.handlers), [_vm._v("\n\t" + _vm._s(_vm.data.text) + "\n")]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_vm.section.element, {
    ref: "sectionElement",
    tag: "component",
    domProps: {
      node: _vm.node
    }
  });
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", {
    staticClass: "sharing-tab-external-section-legacy"
  }, [_c(_setup.component, {
    tag: "component",
    attrs: {
      "file-info": _vm.fileInfo
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c"
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _setup.fileInfo ? _c(_setup.SharingTab, {
    attrs: {
      "file-info": _setup.fileInfo
    }
  }) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true"
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      "is-no-user": _vm.share.shareType !== _vm.ShareType.User,
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
    staticClass: "sharingTabDetailsView__wrapper"
  }, [_c("div", {
    ref: "quickPermissions",
    staticClass: "sharingTabDetailsView__quick-permissions"
  }, [_c("div", [_c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "read-only",
      value: _vm.bundledPermissions.READ_ONLY.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:modelValue": _vm.toggleCustomPermissions
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
    }]),
    model: {
      value: _vm.sharingPermission,
      callback: function ($$v) {
        _vm.sharingPermission = $$v;
      },
      expression: "sharingPermission"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "View only")) + "\n\t\t\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "upload-edit",
      value: _vm.allPermissions,
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:modelValue": _vm.toggleCustomPermissions
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
    }]),
    model: {
      value: _vm.sharingPermission,
      callback: function ($$v) {
        _vm.sharingPermission = $$v;
      },
      expression: "sharingPermission"
    }
  }, [_vm.allowsFileDrop ? [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow upload and editing")) + "\n\t\t\t\t\t")] : [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow editing")) + "\n\t\t\t\t\t")]], 2), _vm._v(" "), _vm.allowsFileDrop ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      "data-cy-files-sharing-share-permissions-bundle": "file-drop",
      "button-variant": true,
      value: _vm.bundledPermissions.FILE_DROP.toString(),
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:modelValue": _vm.toggleCustomPermissions
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
    }], null, false, 1083194048),
    model: {
      value: _vm.sharingPermission,
      callback: function ($$v) {
        _vm.sharingPermission = $$v;
      },
      expression: "sharingPermission"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "File request")) + "\n\t\t\t\t\t"), _c("small", {
    staticClass: "subline"
  }, [_vm._v(_vm._s(_vm.t("files_sharing", "Upload only")))])]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      "button-variant": true,
      "data-cy-files-sharing-share-permissions-bundle": "custom",
      value: "custom",
      name: "sharing_permission_radio",
      type: "radio",
      "button-variant-grouped": "vertical"
    },
    on: {
      "update:modelValue": _vm.expandCustomPermissions
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
    }]),
    model: {
      value: _vm.sharingPermission,
      callback: function ($$v) {
        _vm.sharingPermission = $$v;
      },
      expression: "sharingPermission"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Custom permissions")) + "\n\t\t\t\t\t"), _c("small", {
    staticClass: "subline"
  }, [_vm._v(_vm._s(_vm.customPermissionsList))])])], 1)]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__advanced-control"
  }, [_c("NcButton", {
    attrs: {
      id: "advancedSectionAccordionAdvancedControl",
      variant: "tertiary",
      alignment: "end-reverse",
      "aria-controls": "advancedSectionAccordionAdvanced",
      "aria-expanded": _vm.advancedControlExpandedValue
    },
    on: {
      click: function ($event) {
        _vm.advancedSectionAccordionExpanded = !_vm.advancedSectionAccordionExpanded;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [!_vm.advancedSectionAccordionExpanded ? _c("MenuDownIcon") : _c("MenuUpIcon")];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Advanced settings")) + "\n\t\t\t\t")])], 1), _vm._v(" "), _vm.advancedSectionAccordionExpanded ? _c("div", {
    staticClass: "sharingTabDetailsView__advanced",
    attrs: {
      id: "advancedSectionAccordionAdvanced",
      "aria-labelledby": "advancedSectionAccordionAdvancedControl",
      role: "region"
    }
  }, [_c("section", [_vm.isPublicShare ? _c("NcInputField", {
    staticClass: "sharingTabDetailsView__label",
    attrs: {
      autocomplete: "off",
      label: _vm.t("files_sharing", "Share label")
    },
    model: {
      value: _vm.share.label,
      callback: function ($$v) {
        _vm.$set(_vm.share, "label", $$v);
      },
      expression: "share.label"
    }
  }) : _vm._e(), _vm._v(" "), _vm.config.allowCustomTokens && _vm.isPublicShare && !_vm.isNewShare ? _c("NcInputField", {
    attrs: {
      autocomplete: "off",
      label: _vm.t("files_sharing", "Share link token"),
      "helper-text": _vm.t("files_sharing", "Set the public share link token to something easy to remember or generate a new token. It is not recommended to use a guessable token for shares which contain sensitive information."),
      "show-trailing-button": "",
      "trailing-button-label": _vm.loadingToken ? _vm.t("files_sharing", "Generating…") : _vm.t("files_sharing", "Generate new token")
    },
    on: {
      "trailing-button-click": _vm.generateNewToken
    },
    scopedSlots: _vm._u([{
      key: "trailing-button-icon",
      fn: function () {
        return [_vm.loadingToken ? _c("NcLoadingIcon") : _c("Refresh", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 4228062821),
    model: {
      value: _vm.share.token,
      callback: function ($$v) {
        _vm.$set(_vm.share, "token", $$v);
      },
      expression: "share.token"
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? [_c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: _vm.isPasswordEnforced
    },
    model: {
      value: _vm.isPasswordProtected,
      callback: function ($$v) {
        _vm.isPasswordProtected = $$v;
      },
      expression: "isPasswordProtected"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Set password")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.isPasswordProtected ? _c("NcPasswordField", {
    attrs: {
      autocomplete: "new-password",
      "model-value": _vm.share.newPassword ?? "",
      error: _vm.passwordError,
      "helper-text": _vm.errorPasswordLabel || _vm.passwordHint,
      required: _vm.isPasswordEnforced && _vm.isNewShare,
      label: _vm.t("files_sharing", "Password")
    },
    on: {
      "update:value": _vm.onPasswordChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isEmailShareType && _vm.passwordExpirationTime ? _c("span", {
    attrs: {
      icon: "icon-info"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expires {passwordExpirationTime}", {
    passwordExpirationTime: _vm.passwordExpirationTime
  })) + "\n\t\t\t\t\t")]) : _vm.isEmailShareType && _vm.passwordExpirationTime !== null ? _c("span", {
    attrs: {
      icon: "icon-error"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Password expired")) + "\n\t\t\t\t\t")]) : _vm._e()] : _vm._e(), _vm._v(" "), _vm.canTogglePasswordProtectedByTalkAvailable ? _c("NcCheckboxRadioSwitch", {
    on: {
      "update:modelValue": _vm.onPasswordProtectedByTalkChange
    },
    model: {
      value: _vm.isPasswordProtectedByTalk,
      callback: function ($$v) {
        _vm.isPasswordProtectedByTalk = $$v;
      },
      expression: "isPasswordProtectedByTalk"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Video verification")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: _vm.isExpiryDateEnforced
    },
    model: {
      value: _vm.hasExpirationDate,
      callback: function ($$v) {
        _vm.hasExpirationDate = $$v;
      },
      expression: "hasExpirationDate"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.isExpiryDateEnforced ? _vm.t("files_sharing", "Expiration date (enforced)") : _vm.t("files_sharing", "Set expiration date")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.hasExpirationDate ? _c("NcDateTimePickerNative", {
    attrs: {
      id: "share-date-picker",
      "model-value": new Date(_vm.share.expireDate ?? _vm.dateTomorrow),
      min: _vm.dateTomorrow,
      max: _vm.maxExpirationDateEnforced,
      "hide-label": "",
      label: _vm.t("files_sharing", "Expiration date"),
      placeholder: _vm.t("files_sharing", "Expiration date"),
      type: "date"
    },
    on: {
      input: _vm.onExpirationChange
    }
  }) : _vm._e(), _vm._v(" "), _vm.isPublicShare ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: _vm.canChangeHideDownload
    },
    on: {
      "update:modelValue": function ($event) {
        return _vm.queueUpdate("hideDownload");
      }
    },
    model: {
      value: _vm.share.hideDownload,
      callback: function ($$v) {
        _vm.$set(_vm.share, "hideDownload", $$v);
      },
      expression: "share.hideDownload"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Hide download")) + "\n\t\t\t\t")]) : _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDownload,
      "data-cy-files-sharing-share-permissions-checkbox": "download"
    },
    model: {
      value: _vm.canDownload,
      callback: function ($$v) {
        _vm.canDownload = $$v;
      },
      expression: "canDownload"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Allow download and sync")) + "\n\t\t\t\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    model: {
      value: _vm.writeNoteToRecipientIsChecked,
      callback: function ($$v) {
        _vm.writeNoteToRecipientIsChecked = $$v;
      },
      expression: "writeNoteToRecipientIsChecked"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Note to recipient")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.writeNoteToRecipientIsChecked ? [_c("NcTextArea", {
    attrs: {
      label: _vm.t("files_sharing", "Note to recipient"),
      placeholder: _vm.t("files_sharing", "Enter a note for the share recipient")
    },
    model: {
      value: _vm.share.note,
      callback: function ($$v) {
        _vm.$set(_vm.share, "note", $$v);
      },
      expression: "share.note"
    }
  })] : _vm._e(), _vm._v(" "), _vm.isPublicShare && _vm.isFolder ? _c("NcCheckboxRadioSwitch", {
    model: {
      value: _vm.showInGridView,
      callback: function ($$v) {
        _vm.showInGridView = $$v;
      },
      expression: "showInGridView"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Show files in grid view")) + "\n\t\t\t\t")]) : _vm._e(), _vm._v(" "), _vm._l(_vm.sortedExternalShareActions, function (action) {
    return _c("SidebarTabExternalAction", {
      key: action.id,
      ref: "externalShareActions",
      refInFor: true,
      attrs: {
        action: action,
        node: _vm.fileInfo.node /* TODO: Fix once we have proper Node API */,
        share: _vm.share
      }
    });
  }), _vm._v(" "), _vm._l(_vm.externalLegacyShareActions, function (action) {
    return _c("SidebarTabExternalActionLegacy", {
      key: action.id,
      ref: "externalLinkActions",
      refInFor: true,
      attrs: {
        id: action.id,
        action: action,
        "file-info": _vm.fileInfo,
        share: _vm.share
      }
    });
  }), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    model: {
      value: _vm.setCustomPermissions,
      callback: function ($$v) {
        _vm.setCustomPermissions = $$v;
      },
      expression: "setCustomPermissions"
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Custom permissions")) + "\n\t\t\t\t")]), _vm._v(" "), _vm.setCustomPermissions ? _c("section", {
    staticClass: "custom-permissions-group"
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canRemoveReadPermission,
      "data-cy-files-sharing-share-permissions-checkbox": "read"
    },
    model: {
      value: _vm.hasRead,
      callback: function ($$v) {
        _vm.hasRead = $$v;
      },
      expression: "hasRead"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Read")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.isFolder ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetCreate,
      "data-cy-files-sharing-share-permissions-checkbox": "create"
    },
    model: {
      value: _vm.canCreate,
      callback: function ($$v) {
        _vm.canCreate = $$v;
      },
      expression: "canCreate"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Create")) + "\n\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetEdit,
      "data-cy-files-sharing-share-permissions-checkbox": "update"
    },
    model: {
      value: _vm.canEdit,
      callback: function ($$v) {
        _vm.canEdit = $$v;
      },
      expression: "canEdit"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Edit")) + "\n\t\t\t\t\t")]), _vm._v(" "), _vm.resharingIsPossible ? _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetReshare,
      "data-cy-files-sharing-share-permissions-checkbox": "share"
    },
    model: {
      value: _vm.canReshare,
      callback: function ($$v) {
        _vm.canReshare = $$v;
      },
      expression: "canReshare"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Share")) + "\n\t\t\t\t\t")]) : _vm._e(), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      disabled: !_vm.canSetDelete,
      "data-cy-files-sharing-share-permissions-checkbox": "delete"
    },
    model: {
      value: _vm.canDelete,
      callback: function ($$v) {
        _vm.canDelete = $$v;
      },
      expression: "canDelete"
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Delete")) + "\n\t\t\t\t\t")])], 1) : _vm._e()], 2)]) : _vm._e()]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__footer"
  }, [_c("div", {
    staticClass: "button-group"
  }, [_c("NcButton", {
    attrs: {
      "data-cy-files-sharing-share-editor-action": "cancel"
    },
    on: {
      click: _vm.cancel
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Cancel")) + "\n\t\t\t")]), _vm._v(" "), _c("div", {
    staticClass: "sharingTabDetailsView__delete"
  }, [!_vm.isNewShare ? _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("files_sharing", "Delete share"),
      disabled: false,
      readonly: false,
      variant: "tertiary"
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
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2428343285)
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("files_sharing", "Delete share")) + "\n\t\t\t\t")]) : _vm._e()], 1), _vm._v(" "), _c("NcButton", {
    attrs: {
      variant: "primary",
      "data-cy-files-sharing-share-editor-action": "save",
      disabled: _vm.creating
    },
    on: {
      click: _vm.saveShare
    },
    scopedSlots: _vm._u([_vm.creating ? {
      key: "icon",
      fn: function () {
        return [_c("NcLoadingIcon")];
      },
      proxy: true
    } : null], null, true)
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.shareButtonText) + "\n\t\t\t\t")])], 1)])]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84"
/*!*************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84 ***!
  \*************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _vm.canLinkShare ? _c("ul", {
    staticClass: "sharing-link-list",
    attrs: {
      "aria-label": _vm.t("files_sharing", "Link shares")
    }
  }, [_vm.hasShares ? _vm._l(_vm.shares, function (share, index) {
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
  }) : _vm._e(), _vm._v(" "), !_vm.hasLinkShares && _vm.canReshare ? _c("SharingEntryLink", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo
    },
    on: {
      "add:share": _vm.addShare
    }
  }) : _vm._e()], 2) : _vm._e();
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4"
/*!*********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4 ***!
  \*********************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("ul", {
    staticClass: "sharing-sharee-list",
    attrs: {
      "aria-label": _vm.t("files_sharing", "Shares")
    }
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


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "sharingTab",
    class: {
      "icon-loading": _vm.loading
    }
  }, [_vm.error ? _c("div", {
    staticClass: "emptycontent",
    class: {
      emptyContentWithSections: _vm.hasExternalSections
    }
  }, [_c("div", {
    staticClass: "icon icon-error"
  }), _vm._v(" "), _c("h2", [_vm._v(_vm._s(_vm.error))])]) : _vm._e(), _vm._v(" "), _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.showSharingDetailsView,
      expression: "!showSharingDetailsView"
    }],
    staticClass: "sharingTab__content"
  }, [_vm.isSharedWithMe ? _c("ul", [_c("SharingEntrySimple", _vm._b({
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
  }, "SharingEntrySimple", _vm.sharedWithMe, false))], 1) : _vm._e(), _vm._v(" "), _c("section", [_c("div", {
    staticClass: "section-header"
  }, [_c("h4", [_vm._v(_vm._s(_vm.t("files_sharing", "Internal shares")))]), _vm._v(" "), _c("NcPopover", {
    attrs: {
      "popup-role": "dialog"
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function () {
        return [_c("NcButton", {
          staticClass: "hint-icon",
          attrs: {
            variant: "tertiary-no-background",
            "aria-label": _vm.t("files_sharing", "Internal shares explanation")
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("InfoIcon", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }])
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _c("p", {
    staticClass: "hint-body"
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.internalSharesHelpText) + "\n\t\t\t\t\t")])])], 1), _vm._v(" "), !_vm.loading ? _c("SharingInput", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      "link-shares": _vm.linkShares,
      reshare: _vm.reshare,
      shares: _vm.shares,
      placeholder: _vm.internalShareInputPlaceholder
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
  })], 1), _vm._v(" "), _vm.config.showExternalSharing ? _c("section", [_c("div", {
    staticClass: "section-header"
  }, [_c("h4", [_vm._v(_vm._s(_vm.t("files_sharing", "External shares")))]), _vm._v(" "), _c("NcPopover", {
    attrs: {
      "popup-role": "dialog"
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function () {
        return [_c("NcButton", {
          staticClass: "hint-icon",
          attrs: {
            variant: "tertiary-no-background",
            "aria-label": _vm.t("files_sharing", "External shares explanation")
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("InfoIcon", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 915383693)
        })];
      },
      proxy: true
    }], null, false, 4045083138)
  }, [_vm._v(" "), _c("p", {
    staticClass: "hint-body"
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.externalSharesHelpText) + "\n\t\t\t\t\t")])])], 1), _vm._v(" "), !_vm.loading ? _c("SharingInput", {
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      "link-shares": _vm.linkShares,
      "is-external": true,
      placeholder: _vm.externalShareInputPlaceholder,
      reshare: _vm.reshare,
      shares: _vm.shares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading ? _c("SharingList", {
    attrs: {
      shares: _vm.externalShares,
      "file-info": _vm.fileInfo
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e(), _vm._v(" "), !_vm.loading && _vm.isLinkSharingAllowed ? _c("SharingLinkList", {
    ref: "linkShareList",
    attrs: {
      "can-reshare": _vm.canReshare,
      "file-info": _vm.fileInfo,
      shares: _vm.linkShares
    },
    on: {
      "open-sharing-details": _vm.toggleShareDetailsView
    }
  }) : _vm._e()], 1) : _vm._e(), _vm._v(" "), _vm.hasExternalSections && !_vm.showSharingDetailsView ? _c("section", [_c("div", {
    staticClass: "section-header"
  }, [_c("h4", [_vm._v(_vm._s(_vm.t("files_sharing", "Additional shares")))]), _vm._v(" "), _c("NcPopover", {
    attrs: {
      "popup-role": "dialog"
    },
    scopedSlots: _vm._u([{
      key: "trigger",
      fn: function () {
        return [_c("NcButton", {
          staticClass: "hint-icon",
          attrs: {
            variant: "tertiary-no-background",
            "aria-label": _vm.t("files_sharing", "Additional shares explanation")
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c("InfoIcon", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 915383693)
        })];
      },
      proxy: true
    }], null, false, 880248230)
  }, [_vm._v(" "), _c("p", {
    staticClass: "hint-body"
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.additionalSharesHelpText) + "\n\t\t\t\t\t")])])], 1), _vm._v(" "), _vm._l(_vm.sortedExternalSections, function (section) {
    return _c("SidebarTabExternalSection", {
      key: section.id,
      staticClass: "sharingTab__additionalContent",
      attrs: {
        section: section,
        node: _vm.fileInfo.node /* TODO: Fix once we have proper Node API */
      }
    });
  }), _vm._v(" "), _vm._l(_vm.legacySections, function (section, index) {
    return _c("SidebarTabExternalSectionLegacy", {
      key: index,
      staticClass: "sharingTab__additionalContent",
      attrs: {
        "file-info": _vm.fileInfo,
        "section-callback": section
      }
    });
  }), _vm._v(" "), _vm.projectsEnabled ? _c("div", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.showSharingDetailsView && _vm.fileInfo,
      expression: "!showSharingDetailsView && fileInfo"
    }],
    staticClass: "sharingTab__additionalContent"
  }, [_c("NcCollectionList", {
    attrs: {
      id: `${_vm.fileInfo.id}`,
      type: "file",
      name: _vm.fileInfo.name
    }
  })], 1) : _vm._e()], 2) : _vm._e()]), _vm._v(" "), _vm.showSharingDetailsView ? _c("SharingDetailsTab", {
    attrs: {
      "file-info": _vm.shareDetailsData.fileInfo,
      share: _vm.shareDetailsData.share
    },
    on: {
      "close-sharing-details": _vm.toggleShareDetailsView,
      "add:share": _vm.addShare,
      "remove:share": _vm.removeShare
    }
  }) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
___CSS_LOADER_EXPORT___.push([module.id, `.share-expiry-time[data-v-4b00d08b] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.share-expiry-time .hint-icon[data-v-4b00d08b] {
  padding: 0;
  margin: 0;
  width: 24px;
  height: 24px;
}
.hint-heading[data-v-4b00d08b] {
  text-align: center;
  font-size: 1rem;
  margin-top: 8px;
  padding-bottom: 8px;
  margin-bottom: 0;
  border-bottom: 1px solid var(--color-border);
}
.hint-body[data-v-4b00d08b] {
  padding: var(--border-radius-element);
  max-width: 300px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  padding-inline-start: 10px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: flex-start;
  flex: 1 0;
  min-width: 0;
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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  padding-inline-start: 10px;
  line-height: 1.2em;
}
.sharing-entry__desc p[data-v-06bd31b0] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__actions[data-v-06bd31b0] {
  margin-inline-start: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  color: var(--color-border-success);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  padding-inline-start: 10px;
  display: flex;
  justify-content: space-between;
  flex: 1 0;
  min-width: 0;
}
.sharing-entry__desc[data-v-7a675594] {
  display: flex;
  flex-direction: column;
  line-height: 1.2em;
}
.sharing-entry__desc p[data-v-7a675594] {
  color: var(--color-text-maxcontrast);
}
.sharing-entry__desc__title[data-v-7a675594] {
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
}
.sharing-entry__actions[data-v-7a675594] {
  display: flex;
  align-items: center;
  margin-inline-start: auto;
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
  margin-inline-start: auto;
}
.sharing-entry .action-item ~ .action-item[data-v-7a675594],
.sharing-entry .action-item ~ .sharing-entry__loading[data-v-7a675594] {
  margin-inline-start: 0;
}
.sharing-entry__copy-icon--success[data-v-7a675594] {
  color: var(--color-border-success);
}
.qr-code-dialog[data-v-7a675594] {
  display: flex;
  width: 100%;
  justify-content: center;
}
.qr-code-dialog__img[data-v-7a675594] {
  width: 100%;
  height: auto;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  display: block;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle {
  color: var(--color-primary-element) !important;
  font-size: 12.5px !important;
  height: auto !important;
  min-height: auto !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__text {
  font-weight: normal !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__icon {
  height: 24px !important;
  min-height: 24px !important;
  width: 24px !important;
  min-width: 24px !important;
}
.share-select[data-v-62b9dbb0] .action-item__menutoggle .button-vue__wrapper {
  flex-direction: row-reverse !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  padding-inline-start: 10px;
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
  margin-inline-start: auto !important;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.vs__dropdown-menu span[lookup] .avatardiv .avatardiv__initials-wrapper {
  display: none;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
  width: 100%;
  margin: 0 auto;
  position: relative;
  height: 100%;
  overflow: hidden;
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
  padding-inline-start: 0.3em;
}
.sharingTabDetailsView__wrapper[data-v-b968620e] {
  position: relative;
  overflow: scroll;
  flex-shrink: 1;
  padding: 4px;
  padding-inline-end: 12px;
}
.sharingTabDetailsView__quick-permissions[data-v-b968620e] {
  display: flex;
  justify-content: center;
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
  padding: 0.1em;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] label span {
  display: flex;
  flex-direction: column;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] {
  /* Target component based style in NcCheckboxRadioSwitch slot content*/
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] span.checkbox-content__text.checkbox-radio-switch__text {
  flex-wrap: wrap;
}
.sharingTabDetailsView__quick-permissions div span[data-v-b968620e] span.checkbox-content__text.checkbox-radio-switch__text .subline {
  display: block;
  flex-basis: 100%;
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
  text-align: start;
  padding-inline-start: 0;
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e],
.sharingTabDetailsView__advanced section div.mx-datepicker[data-v-b968620e] {
  width: 100%;
}
.sharingTabDetailsView__advanced section textarea[data-v-b968620e] {
  height: 80px;
  margin: 0;
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
.sharingTabDetailsView__advanced section span[data-v-b968620e] label {
  padding-inline-start: 0 !important;
  background-color: initial !important;
  border: none !important;
}
.sharingTabDetailsView__advanced section section.custom-permissions-group[data-v-b968620e] {
  padding-inline-start: 1.5em;
}
.sharingTabDetailsView__label[data-v-b968620e] {
  padding-block-end: 6px;
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
  background: linear-gradient(to bottom, rgba(255, 255, 255, 0), var(--color-main-background));
}
.sharingTabDetailsView__footer .button-group[data-v-b968620e] {
  display: flex;
  justify-content: space-between;
  width: 100%;
  margin-top: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e] {
  margin-inline-start: 16px;
}
.sharingTabDetailsView__footer .button-group button[data-v-b968620e]:first-child {
  margin-inline-start: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true"
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

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
.sharingTab[data-v-0f81577f] {
  position: relative;
  height: 100%;
}
.sharingTab__content[data-v-0f81577f] {
  padding: 0 6px;
}
.sharingTab__content section[data-v-0f81577f] {
  padding-bottom: 16px;
}
.sharingTab__content section .section-header[data-v-0f81577f] {
  margin-top: 2px;
  margin-bottom: 2px;
  display: flex;
  align-items: center;
  padding-bottom: 4px;
}
.sharingTab__content section .section-header h4[data-v-0f81577f] {
  margin: 0;
  font-size: 16px;
}
.sharingTab__content section .section-header .visually-hidden[data-v-0f81577f] {
  display: none;
}
.sharingTab__content section .section-header .hint-icon[data-v-0f81577f] {
  color: var(--color-primary-element);
}
.sharingTab__content > section[data-v-0f81577f]:not(:last-child) {
  border-bottom: 2px solid var(--color-border);
}
.sharingTab__additionalContent[data-v-0f81577f] {
  margin: var(--default-clickable-area) 0;
}
.hint-body[data-v-0f81577f] {
  max-width: 300px;
  padding: var(--border-radius-element);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.sharing-tab-external-section-legacy[data-v-4091a843] {
	width: 100%;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss"
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true"
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true"
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true"
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true"
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss"
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css"
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./apps/files_sharing/src/components/ShareExpiryTime.vue"
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/components/ShareExpiryTime.vue ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true */ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true");
/* harmony import */ var _ShareExpiryTime_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ShareExpiryTime.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js");
/* harmony import */ var _ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss */ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ShareExpiryTime_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "4b00d08b",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/ShareExpiryTime.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntry.vue"
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true");
/* harmony import */ var _SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "61240f7a",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntry.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue"
/*!*********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue ***!
  \*********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "06bd31b0",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue"
/*!********************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue ***!
  \********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f55cfc52",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryInternal.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue"
/*!****************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true");
/* harmony import */ var _SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "7a675594",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryLink.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue"
/*!****************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue ***!
  \****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "62b9dbb0",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue"
/*!******************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "354542cc",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingEntrySimple.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SharingInput.vue"
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=template&id=39161a5c */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c");
/* harmony import */ var _SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SharingInput.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue"
/*!*******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1 */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1");
/* harmony import */ var _SidebarTabExternalAction_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTabExternalAction_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue"
/*!*************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a");
/* harmony import */ var _SidebarTabExternalActionLegacy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTabExternalActionLegacy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue"
/*!********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c");
/* harmony import */ var _SidebarTabExternalSection_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SidebarTabExternalSection_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue"
/*!**************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue ***!
  \**************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true");
/* harmony import */ var _SidebarTabExternalSectionLegacy_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true");
/* harmony import */ var _SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css */ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SidebarTabExternalSectionLegacy_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "4091a843",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/FilesSidebarTab.vue"
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/views/FilesSidebarTab.vue ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FilesSidebarTab.vue?vue&type=template&id=df86510c */ "./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c");
/* harmony import */ var _FilesSidebarTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts */ "./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _FilesSidebarTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__.render,
  _FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/FilesSidebarTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue"
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "b968620e",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/SharingDetailsTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/SharingInherited.vue"
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true");
/* harmony import */ var _SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3f1bda78",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/SharingInherited.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue"
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=template&id=dd248c84 */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84");
/* harmony import */ var _SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingLinkList.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/SharingLinkList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/SharingList.vue"
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue ***!
  \******************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingList.vue?vue&type=template&id=698e26a4 */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4");
/* harmony import */ var _SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingList.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/SharingList.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files_sharing/src/views/SharingTab.vue"
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue ***!
  \*****************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true");
/* harmony import */ var _SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=script&lang=js */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js");
/* harmony import */ var _SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "0f81577f",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/views/SharingTab.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue"
/*!*************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue ***!
  \*************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AccountCircleOutline.vue?vue&type=template&id=4f5873d1 */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1");
/* harmony import */ var _AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AccountCircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.render,
  _AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/AccountCircleOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "AccountCircleOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountGroup.vue"
/*!*****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountGroup.vue ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AccountGroup.vue?vue&type=template&id=a701ed04 */ "./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04");
/* harmony import */ var _AccountGroup_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AccountGroup.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AccountGroup_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__.render,
  _AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/AccountGroup.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "AccountGroupIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue"
/*!*************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CalendarBlankOutline.vue ***!
  \*************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CalendarBlankOutline.vue?vue&type=template&id=cdde9a50 */ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50");
/* harmony import */ var _CalendarBlankOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CalendarBlankOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CalendarBlankOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__.render,
  _CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/CalendarBlankOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js"
/*!************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js ***!
  \************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CalendarBlankOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue"
/*!**************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue ***!
  \**************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CheckBold.vue?vue&type=template&id=486b2cb1 */ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1");
/* harmony import */ var _CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CheckBold.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.render,
  _CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/CheckBold.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CheckBoldIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue"
/*!******************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue ***!
  \******************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CircleOutline.vue?vue&type=template&id=ad0ef454 */ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454");
/* harmony import */ var _CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.render,
  _CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/CircleOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js"
/*!*****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "CircleOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/ClockOutline.vue"
/*!*****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClockOutline.vue ***!
  \*****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ClockOutline.vue?vue&type=template&id=11dfbf00 */ "./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00");
/* harmony import */ var _ClockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ClockOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ClockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__.render,
  _ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/ClockOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js"
/*!****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ClockOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/ContentCopy.vue"
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ContentCopy.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ContentCopy.vue?vue&type=template&id=64e26d12 */ "./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12");
/* harmony import */ var _ContentCopy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ContentCopy.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ContentCopy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__.render,
  _ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/ContentCopy.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ContentCopyIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Email.vue"
/*!**********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue ***!
  \**********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Email.vue?vue&type=template&id=503121c0 */ "./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0");
/* harmony import */ var _Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Email.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.render,
  _Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Email.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js"
/*!*********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EmailIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue"
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Exclamation.vue?vue&type=template&id=34aa771e */ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e");
/* harmony import */ var _Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Exclamation.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.render,
  _Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Exclamation.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ExclamationIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Eye.vue"
/*!********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Eye.vue?vue&type=template&id=6cfe2635 */ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635");
/* harmony import */ var _Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Eye.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.render,
  _Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Eye.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js"
/*!*******************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js ***!
  \*******************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EyeIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue"
/*!***************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue ***!
  \***************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./EyeOutline.vue?vue&type=template&id=7b68237d */ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d");
/* harmony import */ var _EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EyeOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.render,
  _EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/EyeOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js"
/*!**************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js ***!
  \**************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "EyeOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOutline.vue"
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOutline.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./LockOutline.vue?vue&type=template&id=4c3b119b */ "./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b");
/* harmony import */ var _LockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./LockOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _LockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__.render,
  _LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/LockOutline.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LockOutlineIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Plus.vue"
/*!*********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Plus.vue ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Plus.vue?vue&type=template&id=18bbb6c6 */ "./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6");
/* harmony import */ var _Plus_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Plus.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Plus_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__.render,
  _Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Plus.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "PlusIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue"
/*!***********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue ***!
  \***********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Qrcode.vue?vue&type=template&id=ff95848c */ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c");
/* harmony import */ var _Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Qrcode.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.render,
  _Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Qrcode.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js"
/*!**********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "QrcodeIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Refresh.vue"
/*!************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue ***!
  \************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Refresh.vue?vue&type=template&id=10301842 */ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842");
/* harmony import */ var _Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Refresh.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.render,
  _Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Refresh.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js"
/*!***********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "RefreshIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue"
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ShareCircle.vue?vue&type=template&id=5c5332da */ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da");
/* harmony import */ var _ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ShareCircle.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.render,
  _ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/ShareCircle.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ShareCircleIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/TrayArrowUp.vue"
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrayArrowUp.vue ***!
  \****************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TrayArrowUp.vue?vue&type=template&id=17a665f2 */ "./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2");
/* harmony import */ var _TrayArrowUp_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TrayArrowUp.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _TrayArrowUp_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__.render,
  _TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/TrayArrowUp.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js"
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TrayArrowUpIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue"
/*!**********************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue ***!
  \**********************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TriangleSmallDown.vue?vue&type=template&id=7ca50825 */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825");
/* harmony import */ var _TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TriangleSmallDown.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.render,
  _TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/TriangleSmallDown.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js"
/*!*********************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TriangleSmallDownIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./node_modules/vue-material-design-icons/Tune.vue"
/*!*********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue ***!
  \*********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Tune.vue?vue&type=template&id=f0bd6bb8 */ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8");
/* harmony import */ var _Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Tune.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.render,
  _Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "node_modules/vue-material-design-icons/Tune.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js"
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "TuneIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true"
/*!******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true ***!
  \******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalAction_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=script&lang=ts&setup=true");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalAction_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true"
/*!*******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true ***!
  \*******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSection_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=script&lang=ts&setup=true");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSection_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true"
/*!*************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true ***!
  \*************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=script&lang=ts&setup=true");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_script_lang_ts_setup_true__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts"
/*!*********************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FilesSidebarTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FilesSidebarTab_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js"
/*!***************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ShareExpiryTime.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js"
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js"
/*!*********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js"
/*!********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js"
/*!****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js"
/*!******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js"
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js"
/*!*************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalActionLegacy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalActionLegacy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js"
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js"
/*!***********************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js"
/*!**********************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js ***!
  \**********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js"
/*!******************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js ***!
  \******************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js"
/*!*****************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js ***!
  \*****************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=script&lang=js */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true"
/*!*********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true ***!
  \*********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_template_id_4b00d08b_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=template&id=4b00d08b&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true"
/*!******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_template_id_61240f7a_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=template&id=61240f7a&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true"
/*!***************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true ***!
  \***************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_template_id_06bd31b0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=template&id=06bd31b0&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true"
/*!**************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true ***!
  \**************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_template_id_f55cfc52_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=template&id=f55cfc52&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true"
/*!**********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true ***!
  \**********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_template_id_7a675594_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=template&id=7a675594&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true"
/*!**********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true ***!
  \**********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_template_id_62b9dbb0_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=template&id=62b9dbb0&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true"
/*!************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true ***!
  \************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_template_id_354542cc_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=template&id=354542cc&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c"
/*!******************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_template_id_39161a5c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=template&id=39161a5c */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=template&id=39161a5c");


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1"
/*!*************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1 ***!
  \*************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalAction_vue_vue_type_template_id_48d43ed1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalAction.vue?vue&type=template&id=48d43ed1");


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a"
/*!*******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a ***!
  \*******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalActionLegacy_vue_vue_type_template_id_41fa337a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalActionLegacy.vue?vue&type=template&id=41fa337a");


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c"
/*!**************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c ***!
  \**************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSection_vue_vue_type_template_id_cf9d834c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSection.vue?vue&type=template&id=cf9d834c");


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true"
/*!********************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true ***!
  \********************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_template_id_4091a843_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=template&id=4091a843&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c"
/*!****************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FilesSidebarTab_vue_vue_type_template_id_df86510c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FilesSidebarTab.vue?vue&type=template&id=df86510c */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/FilesSidebarTab.vue?vue&type=template&id=df86510c");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true"
/*!******************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true ***!
  \******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_template_id_b968620e_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=template&id=b968620e&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true"
/*!*****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true ***!
  \*****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_template_id_3f1bda78_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=template&id=3f1bda78&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84"
/*!****************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84 ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingLinkList_vue_vue_type_template_id_dd248c84__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingLinkList.vue?vue&type=template&id=dd248c84 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingLinkList.vue?vue&type=template&id=dd248c84");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4"
/*!************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4 ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingList_vue_vue_type_template_id_698e26a4__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingList.vue?vue&type=template&id=698e26a4 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingList.vue?vue&type=template&id=698e26a4");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true"
/*!***********************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_template_id_0f81577f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=template&id=0f81577f&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=template&id=0f81577f&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss"
/*!************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss ***!
  \************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ShareExpiryTime_vue_vue_type_style_index_0_id_4b00d08b_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/ShareExpiryTime.vue?vue&type=style&index=0&id=4b00d08b&scoped=true&lang=scss");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true"
/*!*********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntry_vue_vue_type_style_index_0_id_61240f7a_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntry.vue?vue&type=style&index=0&id=61240f7a&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true"
/*!******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true ***!
  \******************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInherited_vue_vue_type_style_index_0_id_06bd31b0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInherited.vue?vue&type=style&index=0&id=06bd31b0&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true"
/*!*****************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true ***!
  \*****************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryInternal_vue_vue_type_style_index_0_id_f55cfc52_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryInternal.vue?vue&type=style&index=0&id=f55cfc52&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true"
/*!*************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true ***!
  \*************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryLink_vue_vue_type_style_index_0_id_7a675594_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryLink.vue?vue&type=style&index=0&id=7a675594&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true"
/*!*************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true ***!
  \*************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntryQuickShareSelect_vue_vue_type_style_index_0_id_62b9dbb0_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntryQuickShareSelect.vue?vue&type=style&index=0&id=62b9dbb0&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true"
/*!***************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true ***!
  \***************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingEntrySimple_vue_vue_type_style_index_0_id_354542cc_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingEntrySimple.vue?vue&type=style&index=0&id=354542cc&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss"
/*!*********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss ***!
  \*********************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInput_vue_vue_type_style_index_0_id_39161a5c_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SharingInput.vue?vue&type=style&index=0&id=39161a5c&lang=scss");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true"
/*!*********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true ***!
  \*********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingDetailsTab_vue_vue_type_style_index_0_id_b968620e_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingDetailsTab.vue?vue&type=style&index=0&id=b968620e&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true"
/*!********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true ***!
  \********************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingInherited_vue_vue_type_style_index_0_id_3f1bda78_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingInherited.vue?vue&type=style&index=0&id=3f1bda78&lang=scss&scoped=true");


/***/ },

/***/ "./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss"
/*!**************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss ***!
  \**************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SharingTab_vue_vue_type_style_index_0_id_0f81577f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/views/SharingTab.vue?vue&type=style&index=0&id=0f81577f&scoped=true&lang=scss");


/***/ },

/***/ "./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css"
/*!**********************************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css ***!
  \**********************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SidebarTabExternalSectionLegacy_vue_vue_type_style_index_0_id_4091a843_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/SidebarTabExternal/SidebarTabExternalSectionLegacy.vue?vue&type=style&index=0&id=4091a843&scoped=true&lang=css");


/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js"
/*!*************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./AccountCircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js"
/*!*****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_AccountGroup_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./AccountGroup.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_AccountGroup_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js"
/*!*************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js ***!
  \*************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_CalendarBlankOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./CalendarBlankOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_CalendarBlankOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js"
/*!**************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./CheckBold.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js"
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./CircleOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js"
/*!*****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ClockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ClockOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ClockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ContentCopy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ContentCopy.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ContentCopy_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js"
/*!**********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js ***!
  \**********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Exclamation.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js"
/*!********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js ***!
  \********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Eye.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js"
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./EyeOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_LockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./LockOutline.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_LockOutline_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js"
/*!*********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Plus_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Plus.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Plus_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js"
/*!***********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js ***!
  \***********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Qrcode.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js"
/*!************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Refresh.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ShareCircle.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_TrayArrowUp_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./TrayArrowUp.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_TrayArrowUp_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./TriangleSmallDown.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js"
/*!*********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Tune.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1"
/*!*******************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1 ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountCircleOutline_vue_vue_type_template_id_4f5873d1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./AccountCircleOutline.vue?vue&type=template&id=4f5873d1 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1");


/***/ },

/***/ "./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04"
/*!***********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04 ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AccountGroup_vue_vue_type_template_id_a701ed04__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./AccountGroup.vue?vue&type=template&id=a701ed04 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04");


/***/ },

/***/ "./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50"
/*!*******************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50 ***!
  \*******************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CalendarBlankOutline_vue_vue_type_template_id_cdde9a50__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./CalendarBlankOutline.vue?vue&type=template&id=cdde9a50 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50");


/***/ },

/***/ "./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1"
/*!********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1 ***!
  \********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CheckBold_vue_vue_type_template_id_486b2cb1__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./CheckBold.vue?vue&type=template&id=486b2cb1 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1");


/***/ },

/***/ "./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454"
/*!************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454 ***!
  \************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_CircleOutline_vue_vue_type_template_id_ad0ef454__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./CircleOutline.vue?vue&type=template&id=ad0ef454 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454");


/***/ },

/***/ "./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00"
/*!***********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00 ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClockOutline_vue_vue_type_template_id_11dfbf00__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ClockOutline.vue?vue&type=template&id=11dfbf00 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00");


/***/ },

/***/ "./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12 ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ContentCopy_vue_vue_type_template_id_64e26d12__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ContentCopy.vue?vue&type=template&id=64e26d12 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0"
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0 ***!
  \****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Email_vue_vue_type_template_id_503121c0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Email.vue?vue&type=template&id=503121c0 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Exclamation_vue_vue_type_template_id_34aa771e__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Exclamation.vue?vue&type=template&id=34aa771e */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635"
/*!**************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635 ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Eye_vue_vue_type_template_id_6cfe2635__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Eye.vue?vue&type=template&id=6cfe2635 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635");


/***/ },

/***/ "./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d"
/*!*********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d ***!
  \*********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_EyeOutline_vue_vue_type_template_id_7b68237d__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./EyeOutline.vue?vue&type=template&id=7b68237d */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d");


/***/ },

/***/ "./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_LockOutline_vue_vue_type_template_id_4c3b119b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./LockOutline.vue?vue&type=template&id=4c3b119b */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6"
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6 ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Plus_vue_vue_type_template_id_18bbb6c6__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Plus.vue?vue&type=template&id=18bbb6c6 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c"
/*!*****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c ***!
  \*****************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Qrcode_vue_vue_type_template_id_ff95848c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Qrcode.vue?vue&type=template&id=ff95848c */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842"
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842 ***!
  \******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Refresh_vue_vue_type_template_id_10301842__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Refresh.vue?vue&type=template&id=10301842 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842");


/***/ },

/***/ "./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ShareCircle_vue_vue_type_template_id_5c5332da__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ShareCircle.vue?vue&type=template&id=5c5332da */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da");


/***/ },

/***/ "./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2"
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2 ***!
  \**********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TrayArrowUp_vue_vue_type_template_id_17a665f2__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./TrayArrowUp.vue?vue&type=template&id=17a665f2 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2");


/***/ },

/***/ "./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825"
/*!****************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825 ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_TriangleSmallDown_vue_vue_type_template_id_7ca50825__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./TriangleSmallDown.vue?vue&type=template&id=7ca50825 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825");


/***/ },

/***/ "./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8"
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8 ***!
  \***************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Tune_vue_vue_type_template_id_f0bd6bb8__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Tune.vue?vue&type=template&id=f0bd6bb8 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8");


/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1"
/*!***********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountCircleOutline.vue?vue&type=template&id=4f5873d1 ***!
  \***********************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon account-circle-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M7.07,18.28C7.5,17.38 10.12,16.5 12,16.5C13.88,16.5 16.5,17.38 16.93,18.28C15.57,19.36 13.86,20 12,20C10.14,20 8.43,19.36 7.07,18.28M18.36,16.83C16.93,15.09 13.46,14.5 12,14.5C10.54,14.5 7.07,15.09 5.64,16.83C4.62,15.5 4,13.82 4,12C4,7.59 7.59,4 12,4C16.41,4 20,7.59 20,12C20,13.82 19.38,15.5 18.36,16.83M12,6C10.06,6 8.5,7.56 8.5,9.5C8.5,11.44 10.06,13 12,13C13.94,13 15.5,11.44 15.5,9.5C15.5,7.56 13.94,6 12,6M12,11A1.5,1.5 0 0,1 10.5,9.5A1.5,1.5 0 0,1 12,8A1.5,1.5 0 0,1 13.5,9.5A1.5,1.5 0 0,1 12,11Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04"
/*!***************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AccountGroup.vue?vue&type=template&id=a701ed04 ***!
  \***************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon account-group-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50"
/*!***********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CalendarBlankOutline.vue?vue&type=template&id=cdde9a50 ***!
  \***********************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon calendar-blank-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M19 3H18V1H16V3H8V1H6V3H5C3.89 3 3 3.9 3 5V19C3 20.11 3.9 21 5 21H19C20.11 21 21 20.11 21 19V5C21 3.9 20.11 3 19 3M19 19H5V9H19V19M19 7H5V5H19V7Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1"
/*!************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CheckBold.vue?vue&type=template&id=486b2cb1 ***!
  \************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon check-bold-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M9,20.42L2.79,14.21L5.62,11.38L9,14.77L18.88,4.88L21.71,7.71L9,20.42Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454"
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/CircleOutline.vue?vue&type=template&id=ad0ef454 ***!
  \****************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon circle-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00"
/*!***************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClockOutline.vue?vue&type=template&id=11dfbf00 ***!
  \***************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon clock-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22C6.47,22 2,17.5 2,12A10,10 0 0,1 12,2M12.5,7V12.25L17,14.92L16.25,16.15L11,13V7H12.5Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ContentCopy.vue?vue&type=template&id=64e26d12 ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon content-copy-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M19,21H8V7H19M19,5H8A2,2 0 0,0 6,7V21A2,2 0 0,0 8,23H19A2,2 0 0,0 21,21V7A2,2 0 0,0 19,5M16,1H4A2,2 0 0,0 2,3V17H4V3H16V1Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0"
/*!********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Email.vue?vue&type=template&id=503121c0 ***!
  \********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon email-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Exclamation.vue?vue&type=template&id=34aa771e ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon exclamation-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M 11,4L 13,4L 13,15L 11,15L 11,4 Z M 13,18L 13,20L 11,20L 11,18L 13,18 Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635"
/*!******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Eye.vue?vue&type=template&id=6cfe2635 ***!
  \******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon eye-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d"
/*!*************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/EyeOutline.vue?vue&type=template&id=7b68237d ***!
  \*************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon eye-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9M12,4.5C17,4.5 21.27,7.61 23,12C21.27,16.39 17,19.5 12,19.5C7,19.5 2.73,16.39 1,12C2.73,7.61 7,4.5 12,4.5M3.18,12C4.83,15.36 8.24,17.5 12,17.5C15.76,17.5 19.17,15.36 20.82,12C19.17,8.64 15.76,6.5 12,6.5C8.24,6.5 4.83,8.64 3.18,12Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/LockOutline.vue?vue&type=template&id=4c3b119b ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon lock-outline-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12,17C10.89,17 10,16.1 10,15C10,13.89 10.89,13 12,13A2,2 0 0,1 14,15A2,2 0 0,1 12,17M18,20V10H6V20H18M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6C4.89,22 4,21.1 4,20V10C4,8.89 4.89,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Plus.vue?vue&type=template&id=18bbb6c6 ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon plus-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            { attrs: { d: "M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" } },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c"
/*!*********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Qrcode.vue?vue&type=template&id=ff95848c ***!
  \*********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon qrcode-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M3,11H5V13H3V11M11,5H13V9H11V5M9,11H13V15H11V13H9V11M15,11H17V13H19V11H21V13H19V15H21V19H19V21H17V19H13V21H11V17H15V15H17V13H15V11M19,19V15H17V19H19M15,3H21V9H15V3M17,5V7H19V5H17M3,3H9V9H3V3M5,5V7H7V5H5M3,15H9V21H3V15M5,17V19H7V17H5Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842"
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Refresh.vue?vue&type=template&id=10301842 ***!
  \**********************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon refresh-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ShareCircle.vue?vue&type=template&id=5c5332da ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon share-circle-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M12 2C6.5 2 2 6.5 2 12S6.5 22 12 22 22 17.5 22 12 17.5 2 12 2M14 16V13C10.39 13 7.81 14.43 6 17C6.72 13.33 8.94 9.73 14 9V6L19 11L14 16Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2"
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TrayArrowUp.vue?vue&type=template&id=17a665f2 ***!
  \**************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon tray-arrow-up-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M2 12H4V17H20V12H22V17C22 18.11 21.11 19 20 19H4C2.9 19 2 18.11 2 17V12M12 2L6.46 7.46L7.88 8.88L11 5.75V15H13V5.75L16.13 8.88L17.55 7.45L12 2Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825"
/*!********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/TriangleSmallDown.vue?vue&type=template&id=7ca50825 ***!
  \********************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon triangle-small-down-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c("path", { attrs: { d: "M8 9H16L12 16" } }, [
            _vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e(),
          ]),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8"
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Tune.vue?vue&type=template&id=f0bd6bb8 ***!
  \*******************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon tune-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : "true",
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M3,17V19H9V17H3M3,5V7H13V5H3M13,21V19H21V17H13V15H11V21H13M7,9V11H3V13H7V15H9V9H7M21,13V11H11V13H21M15,9H17V7H21V5H17V3H15V9Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ },

/***/ "./node_modules/@nextcloud/sharing/dist/ui/index.js"
/*!**********************************************************!*\
  !*** ./node_modules/@nextcloud/sharing/dist/ui/index.js ***!
  \**********************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getSidebarActions: () => (/* reexport safe */ _sidebar_action_js__WEBPACK_IMPORTED_MODULE_0__.getSidebarActions),
/* harmony export */   getSidebarInlineActions: () => (/* reexport safe */ _sidebar_action_js__WEBPACK_IMPORTED_MODULE_0__.getSidebarInlineActions),
/* harmony export */   getSidebarSections: () => (/* reexport safe */ _sidebar_section_js__WEBPACK_IMPORTED_MODULE_1__.getSidebarSections),
/* harmony export */   registerSidebarAction: () => (/* reexport safe */ _sidebar_action_js__WEBPACK_IMPORTED_MODULE_0__.registerSidebarAction),
/* harmony export */   registerSidebarInlineAction: () => (/* reexport safe */ _sidebar_action_js__WEBPACK_IMPORTED_MODULE_0__.registerSidebarInlineAction),
/* harmony export */   registerSidebarSection: () => (/* reexport safe */ _sidebar_section_js__WEBPACK_IMPORTED_MODULE_1__.registerSidebarSection)
/* harmony export */ });
/* harmony import */ var _sidebar_action_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./sidebar-action.js */ "./node_modules/@nextcloud/sharing/dist/ui/sidebar-action.js");
/* harmony import */ var _sidebar_section_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./sidebar-section.js */ "./node_modules/@nextcloud/sharing/dist/ui/sidebar-section.js");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */




/***/ },

/***/ "./node_modules/@nextcloud/sharing/dist/ui/sidebar-action.js"
/*!*******************************************************************!*\
  !*** ./node_modules/@nextcloud/sharing/dist/ui/sidebar-action.js ***!
  \*******************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getSidebarActions: () => (/* binding */ getSidebarActions),
/* harmony export */   getSidebarInlineActions: () => (/* binding */ getSidebarInlineActions),
/* harmony export */   registerSidebarAction: () => (/* binding */ registerSidebarAction),
/* harmony export */   registerSidebarInlineAction: () => (/* binding */ registerSidebarInlineAction)
/* harmony export */ });
/* harmony import */ var is_svg__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! is-svg */ "./node_modules/is-svg/index.js");
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

/**
 * Register a new sidebar action
 *
 * @param action - The action to register
 */
function registerSidebarAction(action) {
    if (!action.id) {
        throw new Error('Sidebar actions must have an id');
    }
    if (!action.element || !action.element.startsWith('oca_') || !window.customElements.get(action.element)) {
        throw new Error('Sidebar actions must provide a registered custom web component identifier');
    }
    if (typeof action.order !== 'number') {
        throw new Error('Sidebar actions must have the order property');
    }
    if (typeof action.enabled !== 'function') {
        throw new Error('Sidebar actions must implement the "enabled" method');
    }
    window._nc_files_sharing_sidebar_actions ??= new Map();
    if (window._nc_files_sharing_sidebar_actions.has(action.id)) {
        throw new Error(`Sidebar action with id "${action.id}" is already registered`);
    }
    window._nc_files_sharing_sidebar_actions.set(action.id, action);
}
/**
 * Register a new sidebar action
 *
 * @param action - The action to register
 */
function registerSidebarInlineAction(action) {
    if (!action.id) {
        throw new Error('Sidebar actions must have an id');
    }
    if (typeof action.order !== 'number') {
        throw new Error('Sidebar actions must have the "order" property');
    }
    if (typeof action.iconSvg !== 'string' || !(0,is_svg__WEBPACK_IMPORTED_MODULE_0__["default"])(action.iconSvg)) {
        throw new Error('Sidebar actions must have the "iconSvg" property');
    }
    if (typeof action.label !== 'function') {
        throw new Error('Sidebar actions must implement the "label" method');
    }
    if (typeof action.exec !== 'function') {
        throw new Error('Sidebar actions must implement the "exec" method');
    }
    if (typeof action.enabled !== 'function') {
        throw new Error('Sidebar actions must implement the "enabled" method');
    }
    window._nc_files_sharing_sidebar_inline_actions ??= new Map();
    if (window._nc_files_sharing_sidebar_inline_actions.has(action.id)) {
        throw new Error(`Sidebar action with id "${action.id}" is already registered`);
    }
    window._nc_files_sharing_sidebar_inline_actions.set(action.id, action);
}
/**
 * Get all registered sidebar actions
 */
function getSidebarActions() {
    return [...(window._nc_files_sharing_sidebar_actions?.values() ?? [])];
}
/**
 * Get all registered sidebar inline actions
 */
function getSidebarInlineActions() {
    return [...(window._nc_files_sharing_sidebar_inline_actions?.values() ?? [])];
}


/***/ },

/***/ "./node_modules/@nextcloud/sharing/dist/ui/sidebar-section.js"
/*!********************************************************************!*\
  !*** ./node_modules/@nextcloud/sharing/dist/ui/sidebar-section.js ***!
  \********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getSidebarSections: () => (/* binding */ getSidebarSections),
/* harmony export */   registerSidebarSection: () => (/* binding */ registerSidebarSection)
/* harmony export */ });
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
/**
 * Register a new sidebar section inside the files sharing sidebar tab.
 *
 * @param section - The section to register
 */
function registerSidebarSection(section) {
    if (!section.id) {
        throw new Error('Sidebar sections must have an id');
    }
    if (!section.element || !section.element.startsWith('oca_') || !window.customElements.get(section.element)) {
        throw new Error('Sidebar sections must provide a registered custom web component identifier');
    }
    if (typeof section.order !== 'number') {
        throw new Error('Sidebar sections must have the order property');
    }
    if (typeof section.enabled !== 'function') {
        throw new Error('Sidebar sections must implement the enabled method');
    }
    window._nc_files_sharing_sidebar_sections ??= new Map();
    if (window._nc_files_sharing_sidebar_sections.has(section.id)) {
        throw new Error(`Sidebar section with id "${section.id}" is already registered`);
    }
    window._nc_files_sharing_sidebar_sections.set(section.id, section);
}
/**
 * Get all registered sidebar sections for the files sharing sidebar tab.
 */
function getSidebarSections() {
    return [...(window._nc_files_sharing_sidebar_sections?.values() ?? [])];
}


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcActionCheckbox.mjs"
/*!**************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcActionCheckbox.mjs ***!
  \**************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcActionCheckbox_23CmleUh_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcActionCheckbox_23CmleUh_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcActionCheckbox-23CmleUh.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcActionCheckbox-23CmleUh.mjs");


//# sourceMappingURL=NcActionCheckbox.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcCollectionList.mjs"
/*!**************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcCollectionList.mjs ***!
  \**************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcCollectionList_C_LCG1Ob_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcCollectionList_C_LCG1Ob_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcCollectionList-C-LCG1Ob.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcCollectionList-C-LCG1Ob.mjs");


//# sourceMappingURL=NcCollectionList.mjs.map


/***/ }

}]);
//# sourceMappingURL=apps_files_sharing_src_services_SharingService_ts-apps_files_sharing_src_views_FilesSidebarTab_vue-apps_files_sharing_src_services_SharingService_ts-apps_files_sharing_src_views_FilesSidebarTab_vue.js.map?v=cf40f36a30b9294d085a