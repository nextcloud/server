"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_files_sharing_src_models_Share_ts-apps_files_sharing_src_utils_GeneratePassword_ts-node_-14f1ab"],{

/***/ "./apps/files_sharing/src/models/Share.ts":
/*!************************************************!*\
  !*** ./apps/files_sharing/src/models/Share.ts ***!
  \************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ Share)
/* harmony export */ });
/* harmony import */ var _services_SharingService__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../services/SharingService */ "./apps/files_sharing/src/services/SharingService.ts");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
    if (ocsData.attributes && typeof ocsData.attributes === 'string') {
      try {
        ocsData.attributes = JSON.parse(ocsData.attributes);
      } catch (e) {
        console.warn('Could not parse share attributes returned by server', ocsData.attributes);
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
   */
  get state() {
    return this._share;
  }
  /**
   * get the share id
   */
  get id() {
    return this._share.id;
  }
  /**
   * Get the share type
   */
  get type() {
    return this._share.share_type;
  }
  /**
   * Get the share permissions
   * See window.OC.PERMISSION_* variables
   */
  get permissions() {
    return this._share.permissions;
  }
  /**
   * Get the share attributes
   */
  get attributes() {
    return this._share.attributes;
  }
  /**
   * Set the share permissions
   * See window.OC.PERMISSION_* variables
   */
  set permissions(permissions) {
    this._share.permissions = permissions;
  }
  // SHARE OWNER --------------------------------------------------
  /**
   * Get the share owner uid
   */
  get owner() {
    return this._share.uid_owner;
  }
  /**
   * Get the share owner's display name
   */
  get ownerDisplayName() {
    return this._share.displayname_owner;
  }
  // SHARED WITH --------------------------------------------------
  /**
   * Get the share with entity uid
   */
  get shareWith() {
    return this._share.share_with;
  }
  /**
   * Get the share with entity display name
   * fallback to its uid if none
   */
  get shareWithDisplayName() {
    return this._share.share_with_displayname || this._share.share_with;
  }
  /**
   * Unique display name in case of multiple
   * duplicates results with the same name.
   */
  get shareWithDisplayNameUnique() {
    return this._share.share_with_displayname_unique || this._share.share_with;
  }
  /**
   * Get the share with entity link
   */
  get shareWithLink() {
    return this._share.share_with_link;
  }
  /**
   * Get the share with avatar if any
   */
  get shareWithAvatar() {
    return this._share.share_with_avatar;
  }
  // SHARED FILE OR FOLDER OWNER ----------------------------------
  /**
   * Get the shared item owner uid
   */
  get uidFileOwner() {
    return this._share.uid_file_owner;
  }
  /**
   * Get the shared item display name
   * fallback to its uid if none
   */
  get displaynameFileOwner() {
    return this._share.displayname_file_owner || this._share.uid_file_owner;
  }
  // TIME DATA ----------------------------------------------------
  /**
   * Get the share creation timestamp
   */
  get createdTime() {
    return this._share.stime;
  }
  /**
   * Get the expiration date
   * @return {string} date with YYYY-MM-DD format
   */
  get expireDate() {
    return this._share.expiration;
  }
  /**
   * Set the expiration date
   * @param {string} date the share expiration date with YYYY-MM-DD format
   */
  set expireDate(date) {
    this._share.expiration = date;
  }
  // EXTRA DATA ---------------------------------------------------
  /**
   * Get the public share token
   */
  get token() {
    return this._share.token;
  }
  /**
   * Get the share note if any
   */
  get note() {
    return this._share.note;
  }
  /**
   * Set the share note if any
   */
  set note(note) {
    this._share.note = note;
  }
  /**
   * Get the share label if any
   * Should only exist on link shares
   */
  get label() {
    return this._share.label ?? '';
  }
  /**
   * Set the share label if any
   * Should only be set on link shares
   */
  set label(label) {
    this._share.label = label;
  }
  /**
   * Have a mail been sent
   */
  get mailSend() {
    return this._share.mail_send === true;
  }
  /**
   * Hide the download button on public page
   */
  get hideDownload() {
    return this._share.hide_download === true;
  }
  /**
   * Hide the download button on public page
   */
  set hideDownload(state) {
    this._share.hide_download = state === true;
  }
  /**
   * Password protection of the share
   */
  get password() {
    return this._share.password;
  }
  /**
   * Password protection of the share
   */
  set password(password) {
    this._share.password = password;
  }
  /**
   * Password expiration time
   * @return {string} date with YYYY-MM-DD format
   */
  get passwordExpirationTime() {
    return this._share.password_expiration_time;
  }
  /**
   * Password expiration time
   * @param {string} passwordExpirationTime date with YYYY-MM-DD format
   */
  set passwordExpirationTime(passwordExpirationTime) {
    this._share.password_expiration_time = passwordExpirationTime;
  }
  /**
   * Password protection by Talk of the share
   */
  get sendPasswordByTalk() {
    return this._share.send_password_by_talk;
  }
  /**
   * Password protection by Talk of the share
   *
   * @param {boolean} sendPasswordByTalk whether to send the password by Talk or not
   */
  set sendPasswordByTalk(sendPasswordByTalk) {
    this._share.send_password_by_talk = sendPasswordByTalk;
  }
  // SHARED ITEM DATA ---------------------------------------------
  /**
   * Get the shared item absolute full path
   */
  get path() {
    return this._share.path;
  }
  /**
   * Return the item type: file or folder
   * @return {string} 'folder' | 'file'
   */
  get itemType() {
    return this._share.item_type;
  }
  /**
   * Get the shared item mimetype
   */
  get mimetype() {
    return this._share.mimetype;
  }
  /**
   * Get the shared item id
       */
  get fileSource() {
    return this._share.file_source;
  }
  /**
   * Get the target path on the receiving end
   * e.g the file /xxx/aaa will be shared in
   * the receiving root as /aaa, the fileTarget is /aaa
   */
  get fileTarget() {
    return this._share.file_target;
  }
  /**
   * Get the parent folder id if any
   */
  get fileParent() {
    return this._share.file_parent;
  }
  // PERMISSIONS Shortcuts
  /**
   * Does this share have READ permissions
   */
  get hasReadPermission() {
    return !!(this.permissions & window.OC.PERMISSION_READ);
  }
  /**
   * Does this share have CREATE permissions
   */
  get hasCreatePermission() {
    return !!(this.permissions & window.OC.PERMISSION_CREATE);
  }
  /**
   * Does this share have DELETE permissions
   */
  get hasDeletePermission() {
    return !!(this.permissions & window.OC.PERMISSION_DELETE);
  }
  /**
   * Does this share have UPDATE permissions
   */
  get hasUpdatePermission() {
    return !!(this.permissions & window.OC.PERMISSION_UPDATE);
  }
  /**
   * Does this share have SHARE permissions
   */
  get hasSharePermission() {
    return !!(this.permissions & window.OC.PERMISSION_SHARE);
  }
  /**
   * Does this share have download permissions
   */
  get hasDownloadPermission() {
    const hasDisabledDownload = attribute => {
      return attribute.scope === 'permissions' && attribute.key === 'download' && attribute.value === false;
    };
    return this.attributes.some(hasDisabledDownload);
  }
  /**
   * Is this mail share a file request ?
   */
  get isFileRequest() {
    return (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_0__.isFileRequest)(JSON.stringify(this.attributes));
  }
  set hasDownloadPermission(enabled) {
    this.setAttribute('permissions', 'download', !!enabled);
  }
  setAttribute(scope, key, value) {
    const attrUpdate = {
      scope,
      key,
      value
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
   */
  get canEdit() {
    return this._share.can_edit === true;
  }
  /**
   * Can the current user DELETE this share ?
   */
  get canDelete() {
    return this._share.can_delete === true;
  }
  /**
   * Top level accessible shared folder fileid for the current user
   */
  get viaFileid() {
    return this._share.via_fileid;
  }
  /**
   * Top level accessible shared folder path for the current user
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

/***/ "./apps/files_sharing/src/utils/GeneratePassword.ts":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/utils/GeneratePassword.ts ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* export default binding */ __WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/ConfigService.ts */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* provided dependency */ var console = __webpack_require__(/*! ./node_modules/console-browserify/index.js */ "./node_modules/console-browserify/index.js");
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




const config = new _services_ConfigService_ts__WEBPACK_IMPORTED_MODULE_1__["default"]();
// note: some chars removed on purpose to make them human friendly when read out
const passwordSet = 'abcdefgijkmnopqrstwxyzABCDEFGHJKLMNPQRSTWXYZ23456789';
/**
 * Generate a valid policy password or
 * request a valid password if password_policy
 * is enabled
 */
/* harmony default export */ async function __WEBPACK_DEFAULT_EXPORT__() {
  let verbose = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  // password policy is enabled, let's request a pass
  if (config.passwordPolicy.api && config.passwordPolicy.api.generate) {
    try {
      const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(config.passwordPolicy.api.generate);
      if (request.data.ocs.data.password) {
        if (verbose) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'Password created successfully'));
        }
        return request.data.ocs.data.password;
      }
    } catch (error) {
      console.info('Error generating password from password_policy', error);
      if (verbose) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translate)('files_sharing', 'Error generating password from password policy'));
      }
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

/***/ "./node_modules/vue-material-design-icons/Lock.vue":
/*!*********************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Lock.vue?vue&type=template&id=0e7c8452 */ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452");
/* harmony import */ var _Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Lock.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.render,
  _Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Lock.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js":
/*!********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js ***!
  \********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "LockIcon",
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


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js":
/*!*********************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Lock.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452":
/*!***************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Lock_vue_vue_type_template_id_0e7c8452__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Lock.vue?vue&type=template&id=0e7c8452 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452":
/*!*******************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Lock.vue?vue&type=template&id=0e7c8452 ***!
  \*******************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
        staticClass: "material-design-icon lock-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
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
                d: "M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z",
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



/***/ })

}]);
//# sourceMappingURL=apps_files_sharing_src_models_Share_ts-apps_files_sharing_src_utils_GeneratePassword_ts-node_-14f1ab-apps_files_sharing_src_models_Share_ts-apps_files_sharing_src_utils_GeneratePassword_ts-node_-14f1ab.js.map?v=5ae79bb73c6694564f23