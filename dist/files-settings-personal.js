/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/main-settings-personal.ts"
/*!**************************************************!*\
  !*** ./apps/files/src/main-settings-personal.ts ***!
  \**************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_PersonalSettings_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/PersonalSettings.vue */ "./apps/files/src/components/PersonalSettings.vue");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */




__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
vue__WEBPACK_IMPORTED_MODULE_2__["default"].prototype.t = _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t;
const View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_components_PersonalSettings_vue__WEBPACK_IMPORTED_MODULE_3__["default"]);
const instance = new View();
instance.$mount('#files-personal-settings');

/***/ },

/***/ "./apps/files/src/utils/logger.ts"
/*!****************************************!*\
  !*** ./apps/files/src/utils/logger.ts ***!
  \****************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   logger: () => (/* binding */ logger)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const logger = (0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files').detectUser().build();

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts"
/*!*********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts ***!
  \*********************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.mjs");
/* harmony import */ var _TransferOwnershipDialogue_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./TransferOwnershipDialogue.vue */ "./apps/files/src/components/TransferOwnershipDialogue.vue");




/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'PersonalSettings',
  setup(__props) {
    return {
      __sfc: true,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
      NcSettingsSection: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcSettingsSection,
      TransferOwnershipDialogue: _TransferOwnershipDialogue_vue__WEBPACK_IMPORTED_MODULE_3__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts"
/*!******************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts ***!
  \******************************************************************************************************************************************************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/capabilities */ "./node_modules/@nextcloud/capabilities/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/shared/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcFormBox__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcFormBox */ "./node_modules/@nextcloud/vue/dist/Components/NcFormBox.mjs");
/* harmony import */ var _nextcloud_vue_components_NcFormBoxButton__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcFormBoxButton */ "./node_modules/@nextcloud/vue/dist/Components/NcFormBoxButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcFormGroup__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcFormGroup */ "./node_modules/@nextcloud/vue/dist/Components/NcFormGroup.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelectUsers__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelectUsers */ "./node_modules/@nextcloud/vue/dist/Components/NcSelectUsers.mjs");
/* harmony import */ var _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ../utils/logger.ts */ "./apps/files/src/utils/logger.ts");

















// eslint-disable-next-line @typescript-eslint/no-explicit-any
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'TransferOwnershipDialogue',
  setup(__props) {
    const minSearchStringLength = (0,_nextcloud_capabilities__WEBPACK_IMPORTED_MODULE_4__.getCapabilities)().files_sharing.sharee.minSearchStringLength;
    const picker = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.getFilePickerBuilder)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Choose a file or folder to transfer')).allowDirectories().setMultiSelect(false).setButtonFactory(([node]) => {
      const canPick = !!node?.path && node.path !== '/' && node.owner === (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getCurrentUser)().uid;
      return [{
        label: canPick ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Transfer "{path}"', {
          path: node.displayname
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Select file or folder'),
        callback: () => {},
        disabled: !canPick,
        variant: 'primary'
      }];
    }).build();
    const nodeForTransfer = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const loadingUsers = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)(false);
    const selectedUser = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)();
    const userSuggestions = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    const canSubmit = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => !!nodeForTransfer.value && !!selectedUser.value);
    const submitButtonText = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      if (!nodeForTransfer.value || !selectedUser.value) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Transfer');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Transfer {path} to {userid}', {
        path: nodeForTransfer.value.displayname,
        userid: selectedUser.value.displayName
      });
    });
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.onBeforeMount)(() => searchUsers(''));
    /**
     * Open the file picker to choose a file or folder for which the ownership should be transferred.
     */
    async function chooseNodeForTransfer() {
      try {
        const [node] = await picker.pickNodes();
        nodeForTransfer.value = node;
      } catch (error) {
        if (error instanceof _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.FilePickerClosed) {
          _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.debug('Selecting object for transfer aborted', {
            error
          });
          return;
        }
        nodeForTransfer.value = undefined;
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.error('Error while opening file picker for transfer ownership', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Error while opening file picker for transfer ownership'));
        return;
      }
    }
    const searchUsersDebounced = (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_8__.useDebounceFn)(searchUsers, 500);
    /**
     * Handle the user search input and fetch matching users from the server.
     *
     * @param query - The search string entered by the user
     */
    async function searchUsers(query) {
      query = query.trim();
      if (query.length < minSearchStringLength) {
        return;
      }
      loadingUsers.value = true;
      try {
        const response = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_7__.generateOcsUrl)('apps/files_sharing/api/v1/sharees'), {
          params: {
            format: 'json',
            itemType: 'file',
            search: query,
            perPage: 20,
            lookup: false
          }
        });
        const data = [...response.data.ocs.data.exact.users, ...response.data.ocs.data.users];
        userSuggestions.value = data.map(user => ({
          displayName: user.label,
          id: user.value.shareWith,
          user: user.value.shareWith,
          subname: user.shareWithDisplayNameUnique
        }));
      } catch (error) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.error('could not fetch users', {
          error
        });
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Error while searching for users'));
      } finally {
        loadingUsers.value = false;
      }
    }
    /**
     * Handle submit of the ownership transfer.
     */
    async function submit() {
      if (!canSubmit.value) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.warn('ignoring form submit');
      }
      const requestParameters = {
        path: nodeForTransfer.value?.path,
        recipient: selectedUser.value?.user
      };
      _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.debug('submit transfer ownership form', {
        requestParameters
      });
      try {
        const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_7__.generateOcsUrl)('apps/files/api/v1/transferownership');
        const {
          data
        } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].post(url, requestParameters);
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.info('Transfer ownership request sent', {
          data
        });
        nodeForTransfer.value = undefined;
        selectedUser.value = undefined;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Ownership transfer request sent'));
      } catch (error) {
        _utils_logger_ts__WEBPACK_IMPORTED_MODULE_15__.logger.error('Could not send ownership transfer request', {
          error
        });
        if ((0,_nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__.isAxiosError)(error) && error.response?.status === 403) {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Cannot transfer ownership of a file or folder you do not own'));
        } else {
          (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_5__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t)('files', 'Error while sending ownership transfer request'));
        }
      }
    }
    return {
      __sfc: true,
      minSearchStringLength,
      picker,
      nodeForTransfer,
      loadingUsers,
      selectedUser,
      userSuggestions,
      canSubmit,
      submitButtonText,
      chooseNodeForTransfer,
      searchUsersDebounced,
      searchUsers,
      submit,
      mdiFolderOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_1__.mdiFolderOutline,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_6__.t,
      NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_9__["default"],
      NcFormBox: _nextcloud_vue_components_NcFormBox__WEBPACK_IMPORTED_MODULE_10__["default"],
      NcFormBoxButton: _nextcloud_vue_components_NcFormBoxButton__WEBPACK_IMPORTED_MODULE_11__["default"],
      NcFormGroup: _nextcloud_vue_components_NcFormGroup__WEBPACK_IMPORTED_MODULE_12__["default"],
      NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_13__["default"],
      NcSelectUsers: _nextcloud_vue_components_NcSelectUsers__WEBPACK_IMPORTED_MODULE_14__["default"]
    };
  }
}));

/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a"
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
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
  return _c(_setup.NcSettingsSection, {
    attrs: {
      name: _setup.t("files", "Files")
    }
  }, [_c(_setup.TransferOwnershipDialogue)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136"
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136 ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("form", {
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _setup.submit.apply(null, arguments);
      }
    }
  }, [_c(_setup.NcFormGroup, {
    class: _vm.$style.transferOwnership__group,
    attrs: {
      label: _setup.t("files", "Transfer ownership of a file or folder")
    }
  }, [_c(_setup.NcFormBox, {
    scopedSlots: _vm._u([{
      key: "default",
      fn: function ({
        itemClass
      }) {
        return [_c(_setup.NcFormBoxButton, {
          attrs: {
            "inverted-accent": "",
            label: _setup.t("files", "File or folder to transfer"),
            description: _setup.nodeForTransfer?.displayname ?? _setup.t("files", "No file or folder selected")
          },
          on: {
            click: _setup.chooseNodeForTransfer
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_c(_setup.NcIconSvgWrapper, {
                attrs: {
                  path: _setup.mdiFolderOutline
                }
              })];
            },
            proxy: true
          }], null, true)
        }), _vm._v(" "), _c("div", {
          class: [itemClass, _vm.$style.transferOwnership__newOwner]
        }, [_c(_setup.NcSelectUsers, {
          class: _vm.$style.transferOwnership__newOwnerSelect,
          attrs: {
            "input-label": _setup.t("files", "New owner"),
            loading: _setup.loadingUsers,
            options: _setup.userSuggestions
          },
          on: {
            search: _setup.searchUsersDebounced
          },
          model: {
            value: _setup.selectedUser,
            callback: function ($$v) {
              _setup.selectedUser = $$v;
            },
            expression: "selectedUser"
          }
        })], 1), _vm._v(" "), _c(_setup.NcButton, {
          attrs: {
            disabled: !_setup.canSubmit,
            type: "submit",
            variant: "primary",
            wide: ""
          }
        }, [_vm._v("\n\t\t\t\t" + _vm._s(_setup.submitButtonText) + "\n\t\t\t")])];
      }
    }])
  }), _vm._v(" "), _c("p", {
    staticClass: "hidden-visually",
    attrs: {
      "aria-live": "polite"
    }
  }, [!_setup.nodeForTransfer ? _c("span", [_vm._v(_vm._s(_setup.t("files", "You need to select a file or folder to transfer ownership.")))]) : _vm._e(), _vm._v(" "), !_setup.selectedUser ? _c("span", [_vm._v(_vm._s(_setup.t("files", "You need to select a new owner for the file or folder.")))]) : _vm._e()])], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ },

/***/ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `
._transferOwnership__group_lSDrv {
	max-width: 512px;
}
._transferOwnership__newOwner_qBBCY {
	background-color: var(--color-primary-element-light);
	padding-block: var(--default-grid-baseline);
	padding-inline: calc(var(--border-radius-element) + var(--default-grid-baseline));
}
._transferOwnership__newOwnerSelect_Mt32B {
	width: 100%;
}
._transferOwnership__newOwner_qBBCY .vs--open .vs__dropdown-toggle {
	background-color: var(--color-main-background);
}
`, ""]);
// Exports
___CSS_LOADER_EXPORT___.locals = {
	"transferOwnership__group": `_transferOwnership__group_lSDrv`,
	"transferOwnership__newOwner": `_transferOwnership__newOwner_qBBCY`,
	"transferOwnership__newOwnerSelect": `_transferOwnership__newOwnerSelect_Mt32B`
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ },

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css"
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css */ "./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ },

/***/ "./apps/files/src/components/PersonalSettings.vue"
/*!********************************************************!*\
  !*** ./apps/files/src/components/PersonalSettings.vue ***!
  \********************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PersonalSettings.vue?vue&type=template&id=1cf5236a */ "./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a");
/* harmony import */ var _PersonalSettings_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PersonalSettings.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _PersonalSettings_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__.render,
  _PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/PersonalSettings.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files/src/components/TransferOwnershipDialogue.vue"
/*!*****************************************************************!*\
  !*** ./apps/files/src/components/TransferOwnershipDialogue.vue ***!
  \*****************************************************************/
(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136 */ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136");
/* harmony import */ var _TransferOwnershipDialogue_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css */ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");
/* module decorator */ module = __webpack_require__.hmd(module);



;

var cssModules = {}
var disposed = false

function injectStyles (context) {
  if (disposed) return
  
        cssModules["$style"] = (_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"].locals || _TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_2__["default"])
        Object.defineProperty(this, "$style", {
          configurable: true,
          get: function () {
            return cssModules["$style"]
          }
        })
      
}


  module.hot && 0



        module.hot && 0

/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _TransferOwnershipDialogue_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__.render,
  _TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  injectStyles,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/TransferOwnershipDialogue.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ },

/***/ "./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts"
/*!*******************************************************************************************!*\
  !*** ./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts ***!
  \*******************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalSettings_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PersonalSettings.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalSettings_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts"
/*!****************************************************************************************************!*\
  !*** ./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts ***!
  \****************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-6.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_6_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ },

/***/ "./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a"
/*!**************************************************************************************!*\
  !*** ./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a ***!
  \**************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_PersonalSettings_vue_vue_type_template_id_1cf5236a__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PersonalSettings.vue?vue&type=template&id=1cf5236a */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/PersonalSettings.vue?vue&type=template&id=1cf5236a");


/***/ },

/***/ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136"
/*!***********************************************************************************************!*\
  !*** ./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136 ***!
  \***********************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_template_id_76fb0136__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=template&id=76fb0136");


/***/ },

/***/ "./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css"
/*!*************************************************************************************************************************!*\
  !*** ./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css ***!
  \*************************************************************************************************************************/
(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__["default"])
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_clonedRuleSet_3_use_1_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TransferOwnershipDialogue_vue_vue_type_style_index_0_id_76fb0136_module_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js??clonedRuleSet-3.use[1]!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/TransferOwnershipDialogue.vue?vue&type=style&index=0&id=76fb0136&module=true&lang=css");
 

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e"
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e"
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ },

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e"
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
(module) {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcFormBoxButton.mjs"
/*!*************************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcFormBoxButton.mjs ***!
  \*************************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcFormBoxButton_BQi11xQX_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcFormBoxButton_BQi11xQX_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcFormBoxButton-BQi11xQX.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcFormBoxButton-BQi11xQX.mjs");


//# sourceMappingURL=NcFormBoxButton.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcFormGroup.mjs"
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcFormGroup.mjs ***!
  \*********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcFormGroup_DblLoFMf_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcFormGroup_DblLoFMf_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcFormGroup-DblLoFMf.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcFormGroup-DblLoFMf.mjs");


//# sourceMappingURL=NcFormGroup.mjs.map


/***/ },

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcSelectUsers.mjs"
/*!***********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcSelectUsers.mjs ***!
  \***********************************************************************/
(__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcSelectUsers_BRDGLoPt_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcSelectUsers_BRDGLoPt_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcSelectUsers-BRDGLoPt.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcSelectUsers-BRDGLoPt.mjs");


//# sourceMappingURL=NcSelectUsers.mjs.map


/***/ }

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		if (!(moduleId in __webpack_modules__)) {
/******/ 			delete __webpack_module_cache__[moduleId];
/******/ 			var e = new Error("Cannot find module '" + moduleId + "'");
/******/ 			e.code = 'MODULE_NOT_FOUND';
/******/ 			throw e;
/******/ 		}
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	(() => {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = (result, chunkIds, fn, priority) => {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var [chunkIds, fn, priority] = deferred[i];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every((key) => (__webpack_require__.O[key](chunkIds[j])))) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/ensure chunk */
/******/ 	(() => {
/******/ 		__webpack_require__.f = {};
/******/ 		// This file contains only the entry chunk.
/******/ 		// The chunk loading function for additional chunks
/******/ 		__webpack_require__.e = (chunkId) => {
/******/ 			return Promise.all(Object.keys(__webpack_require__.f).reduce((promises, key) => {
/******/ 				__webpack_require__.f[key](chunkId, promises);
/******/ 				return promises;
/******/ 			}, []));
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/get javascript chunk filename */
/******/ 	(() => {
/******/ 		// This function allow to reference async chunks
/******/ 		__webpack_require__.u = (chunkId) => {
/******/ 			// return url for filenames based on template
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_preview-BIbJGxXF_mjs-node_modules_nextcloud_dialog-7546cc":"dce7060a8340cecf2ca5","node_modules_nextcloud_dialogs_dist_chunks_ConflictPicker-CWBf0soh_mjs":"01a2e7bc2c49db839239","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcTextField_index_mjs":"50270bc67122ae47dfd0","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-C1yRZfLt_mjs":"a3985d66705012167d75","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-7_GNN76e_mjs":"b2479474dfc9749ea9d0","node_modules_nextcloud_vue_dist_Components_NcColorPicker_mjs":"cc9a80a105a480079016","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"21fc91c563f5cd8d04c3","node_modules_rehype-highlight_index_js":"625f8818e33c457c3744","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcColorPicker_index_mjs":"63766ea64d27a6d8d6cc","node_modules_nextcloud_dialogs_node_modules_nextcloud_vue_dist_components_NcDateTimePicker_in-952ddb":"bd2fc411cc830fe12f0b"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/harmony module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.hmd = (module) => {
/******/ 			module = Object.create(module);
/******/ 			if (!module.children) module.children = [];
/******/ 			Object.defineProperty(module, 'exports', {
/******/ 				enumerable: true,
/******/ 				set: () => {
/******/ 					throw new Error('ES Modules may not assign module.exports or exports.*, Use ESM export syntax, instead: ' + module.id);
/******/ 				}
/******/ 			});
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/load script */
/******/ 	(() => {
/******/ 		var inProgress = {};
/******/ 		var dataWebpackPrefix = "nextcloud-ui-legacy:";
/******/ 		// loadScript function to load a script via script tag
/******/ 		__webpack_require__.l = (url, done, key, chunkId) => {
/******/ 			if(inProgress[url]) { inProgress[url].push(done); return; }
/******/ 			var script, needAttach;
/******/ 			if(key !== undefined) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				for(var i = 0; i < scripts.length; i++) {
/******/ 					var s = scripts[i];
/******/ 					if(s.getAttribute("src") == url || s.getAttribute("data-webpack") == dataWebpackPrefix + key) { script = s; break; }
/******/ 				}
/******/ 			}
/******/ 			if(!script) {
/******/ 				needAttach = true;
/******/ 				script = document.createElement('script');
/******/ 		
/******/ 				script.charset = 'utf-8';
/******/ 				if (__webpack_require__.nc) {
/******/ 					script.setAttribute("nonce", __webpack_require__.nc);
/******/ 				}
/******/ 				script.setAttribute("data-webpack", dataWebpackPrefix + key);
/******/ 		
/******/ 				script.src = url;
/******/ 			}
/******/ 			inProgress[url] = [done];
/******/ 			var onScriptComplete = (prev, event) => {
/******/ 				// avoid mem leaks in IE.
/******/ 				script.onerror = script.onload = null;
/******/ 				clearTimeout(timeout);
/******/ 				var doneFns = inProgress[url];
/******/ 				delete inProgress[url];
/******/ 				script.parentNode && script.parentNode.removeChild(script);
/******/ 				doneFns && doneFns.forEach((fn) => (fn(event)));
/******/ 				if(prev) return prev(event);
/******/ 			}
/******/ 			var timeout = setTimeout(onScriptComplete.bind(null, undefined, { type: 'timeout', target: script }), 120000);
/******/ 			script.onerror = onScriptComplete.bind(null, script.onerror);
/******/ 			script.onload = onScriptComplete.bind(null, script.onload);
/******/ 			needAttach && document.head.appendChild(script);
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	(() => {
/******/ 		__webpack_require__.nmd = (module) => {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/publicPath */
/******/ 	(() => {
/******/ 		var scriptUrl;
/******/ 		if (globalThis.importScripts) scriptUrl = globalThis.location + "";
/******/ 		var document = globalThis.document;
/******/ 		if (!scriptUrl && document) {
/******/ 			if (document.currentScript && document.currentScript.tagName.toUpperCase() === 'SCRIPT')
/******/ 				scriptUrl = document.currentScript.src;
/******/ 			if (!scriptUrl) {
/******/ 				var scripts = document.getElementsByTagName("script");
/******/ 				if(scripts.length) {
/******/ 					var i = scripts.length - 1;
/******/ 					while (i > -1 && (!scriptUrl || !/^http(s?):/.test(scriptUrl))) scriptUrl = scripts[i--].src;
/******/ 				}
/******/ 			}
/******/ 		}
/******/ 		// When supporting browsers where an automatic publicPath is not supported you must specify an output.publicPath manually via configuration
/******/ 		// or pass an empty string ("") and set the __webpack_public_path__ variable from your code to use your own logic.
/******/ 		if (!scriptUrl) throw new Error("Automatic publicPath is not supported in this browser");
/******/ 		scriptUrl = scriptUrl.replace(/^blob:/, "").replace(/#.*$/, "").replace(/\?.*$/, "").replace(/\/[^\/]+$/, "/");
/******/ 		__webpack_require__.p = scriptUrl;
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	(() => {
/******/ 		__webpack_require__.b = (typeof document !== 'undefined' && document.baseURI) || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files-settings-personal": 0
/******/ 		};
/******/ 		
/******/ 		__webpack_require__.f.j = (chunkId, promises) => {
/******/ 				// JSONP chunk loading for javascript
/******/ 				var installedChunkData = __webpack_require__.o(installedChunks, chunkId) ? installedChunks[chunkId] : undefined;
/******/ 				if(installedChunkData !== 0) { // 0 means "already installed".
/******/ 		
/******/ 					// a Promise means "currently loading".
/******/ 					if(installedChunkData) {
/******/ 						promises.push(installedChunkData[2]);
/******/ 					} else {
/******/ 						if(true) { // all chunks have JS
/******/ 							// setup Promise in chunk cache
/******/ 							var promise = new Promise((resolve, reject) => (installedChunkData = installedChunks[chunkId] = [resolve, reject]));
/******/ 							promises.push(installedChunkData[2] = promise);
/******/ 		
/******/ 							// start chunk loading
/******/ 							var url = __webpack_require__.p + __webpack_require__.u(chunkId);
/******/ 							// create error before stack unwound to get useful stacktrace later
/******/ 							var error = new Error();
/******/ 							var loadingEnded = (event) => {
/******/ 								if(__webpack_require__.o(installedChunks, chunkId)) {
/******/ 									installedChunkData = installedChunks[chunkId];
/******/ 									if(installedChunkData !== 0) installedChunks[chunkId] = undefined;
/******/ 									if(installedChunkData) {
/******/ 										var errorType = event && (event.type === 'load' ? 'missing' : event.type);
/******/ 										var realSrc = event && event.target && event.target.src;
/******/ 										error.message = 'Loading chunk ' + chunkId + ' failed.\n(' + errorType + ': ' + realSrc + ')';
/******/ 										error.name = 'ChunkLoadError';
/******/ 										error.type = errorType;
/******/ 										error.request = realSrc;
/******/ 										installedChunkData[1](error);
/******/ 									}
/******/ 								}
/******/ 							};
/******/ 							__webpack_require__.l(url, loadingEnded, "chunk-" + chunkId, chunkId);
/******/ 						}
/******/ 					}
/******/ 				}
/******/ 		};
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = (chunkId) => (installedChunks[chunkId] === 0);
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = (parentChunkLoadingFunction, data) => {
/******/ 			var [chunkIds, moreModules, runtime] = data;
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some((id) => (installedChunks[id] !== 0))) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = globalThis["webpackChunknextcloud_ui_legacy"] = globalThis["webpackChunknextcloud_ui_legacy"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	(() => {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files/src/main-settings-personal.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files-settings-personal.js.map?v=a05a1bcb0d910cec9d88