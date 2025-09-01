/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/files/src/actions/sidebarAction.ts":
/*!*************************************************!*\
  !*** ./apps/files/src/actions/sidebarAction.ts ***!
  \*************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_DETAILS: () => (/* binding */ ACTION_DETAILS),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/information-outline.svg?raw */ "./node_modules/@mdi/svg/svg/information-outline.svg?raw");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/files/src/logger.ts");





const ACTION_DETAILS = 'details';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: ACTION_DETAILS,
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files', 'Details'),
  iconSvgInline: () => _mdi_svg_svg_information_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  // Sidebar currently supports user folder only, /files/USER
  enabled: nodes => {
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_2__.isPublicShare)()) {
      return false;
    }
    // Only works on single node
    if (nodes.length !== 1) {
      return false;
    }
    if (!nodes[0]) {
      return false;
    }
    // Only work if the sidebar is available
    if (!window?.OCA?.Files?.Sidebar) {
      return false;
    }
    return (nodes[0].root?.startsWith('/files/') && nodes[0].permissions !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Permission.NONE) ?? false;
  },
  async exec(node, view, dir) {
    try {
      // If the sidebar is already open for the current file, do nothing
      if (window.OCA.Files.Sidebar.file === node.path) {
        _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].debug('Sidebar already open for this file', {
          node
        });
        return null;
      }
      // Open sidebar and set active tab to sharing by default
      window.OCA.Files.Sidebar.setActiveTab('sharing');
      // TODO: migrate Sidebar to use a Node instead
      await window.OCA.Files.Sidebar.open(node.path);
      // Silently update current fileid
      window.OCP?.Files?.Router?.goToRoute(null, {
        view: view.id,
        fileid: String(node.fileid)
      }, {
        ...window.OCP.Files.Router.query,
        dir,
        opendetails: 'true'
      }, true);
      return null;
    } catch (error) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].error('Error while opening sidebar', {
        error
      });
      return false;
    }
  },
  order: -50
});

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue":
/*!*********************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=template&id=5c291778&scoped=true */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true");
/* harmony import */ var _FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=script&setup=true&lang=ts */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5c291778",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files/src/components/FileListFilter/FileListFilter.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts":
/*!********************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");


/***/ }),

/***/ "./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true":
/*!***************************************************************************************************************!*\
  !*** ./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_template_id_5c291778_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=template&id=5c291778&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true");


/***/ }),

/***/ "./apps/files/src/logger.ts":
/*!**********************************!*\
  !*** ./apps/files/src/logger.ts ***!
  \**********************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files').detectUser().build());

/***/ }),

/***/ "./apps/files_sharing/src/components/FileListFilterAccount.vue":
/*!*********************************************************************!*\
  !*** ./apps/files_sharing/src/components/FileListFilterAccount.vue ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true */ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true");
/* harmony import */ var _FileListFilterAccount_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts */ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts");
/* harmony import */ var _FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss */ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileListFilterAccount_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "40ba7127",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/files_sharing/src/components/FileListFilterAccount.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts":
/*!********************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts ***!
  \********************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_script_setup_true_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss":
/*!******************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss ***!
  \******************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true":
/*!***************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true ***!
  \***************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_template_id_40ba7127_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/files_actions/acceptShareAction.ts":
/*!*******************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/acceptShareAction.ts ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/check.svg?raw */ "./node_modules/@mdi/svg/svg/check.svg?raw");
/* harmony import */ var _files_views_shares__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../files_views/shares */ "./apps/files_sharing/src/files_views/shares.ts");







const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileAction({
  id: 'accept-share',
  displayName: nodes => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translatePlural)('files_sharing', 'Accept share', 'Accept shares', nodes.length),
  iconSvgInline: () => _mdi_svg_svg_check_svg_raw__WEBPACK_IMPORTED_MODULE_5__,
  enabled: (nodes, view) => nodes.length > 0 && view.id === _files_views_shares__WEBPACK_IMPORTED_MODULE_6__.pendingSharesViewId,
  async exec(node) {
    try {
      const isRemote = !!node.attributes.remote;
      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/{shareBase}/pending/{id}', {
        shareBase: isRemote ? 'remote_shares' : 'shares',
        id: node.attributes.id
      });
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_4__["default"].post(url);
      // Remove from current view
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node);
      return true;
    } catch (error) {
      return false;
    }
  },
  async execBatch(nodes, view, dir) {
    return Promise.all(nodes.map(node => this.exec(node, view, dir)));
  },
  order: 1,
  inline: () => true
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)(action);

/***/ }),

/***/ "./apps/files_sharing/src/files_actions/openInFilesAction.ts":
/*!*******************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/openInFilesAction.ts ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _files_views_shares__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../files_views/shares */ "./apps/files_sharing/src/files_views/shares.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileAction({
  id: 'files_sharing:open-in-files',
  displayName: () => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate)('files_sharing', 'Open in Files'),
  iconSvgInline: () => '',
  enabled: (nodes, view) => [_files_views_shares__WEBPACK_IMPORTED_MODULE_2__.sharesViewId, _files_views_shares__WEBPACK_IMPORTED_MODULE_2__.sharedWithYouViewId, _files_views_shares__WEBPACK_IMPORTED_MODULE_2__.sharedWithOthersViewId, _files_views_shares__WEBPACK_IMPORTED_MODULE_2__.sharingByLinksViewId
  // Deleted and pending shares are not
  // accessible in the files app.
  ].includes(view.id),
  async exec(node) {
    const isFolder = node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.FileType.Folder;
    window.OCP.Files.Router.goToRoute(null,
    // use default route
    {
      view: 'files',
      fileid: String(node.fileid)
    }, {
      // If this node is a folder open the folder in files
      dir: isFolder ? node.path : node.dirname,
      // otherwise if this is a file, we should open it
      openfile: isFolder ? undefined : 'true'
    });
    return null;
  },
  // Before openFolderAction
  order: -1000,
  default: _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.DefaultType.HIDDEN
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileAction)(action);

/***/ }),

/***/ "./apps/files_sharing/src/files_actions/rejectShareAction.ts":
/*!*******************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/rejectShareAction.ts ***!
  \*******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _files_views_shares__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../files_views/shares */ "./apps/files_sharing/src/files_views/shares.ts");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/close.svg?raw */ "./node_modules/@mdi/svg/svg/close.svg?raw");








const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.FileAction({
  id: 'reject-share',
  displayName: nodes => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translatePlural)('files_sharing', 'Reject share', 'Reject shares', nodes.length),
  iconSvgInline: () => _mdi_svg_svg_close_svg_raw__WEBPACK_IMPORTED_MODULE_7__,
  enabled: (nodes, view) => {
    if (view.id !== _files_views_shares__WEBPACK_IMPORTED_MODULE_5__.pendingSharesViewId) {
      return false;
    }
    if (nodes.length === 0) {
      return false;
    }
    // disable rejecting group shares from the pending list because they anyway
    // land back into that same list after rejecting them
    if (nodes.some(node => node.attributes.remote_id && node.attributes.share_type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_4__.ShareType.RemoteGroup)) {
      return false;
    }
    return true;
  },
  async exec(node) {
    try {
      const isRemote = !!node.attributes.remote;
      const shareBase = isRemote ? 'remote_shares' : 'shares';
      const id = node.attributes.id;
      let url;
      if (node.attributes.accepted === 0) {
        url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/{shareBase}/pending/{id}', {
          shareBase,
          id
        });
      } else {
        url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/{shareBase}/{id}', {
          shareBase,
          id
        });
      }
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].delete(url);
      // Remove from current view
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node);
      return true;
    } catch (error) {
      return false;
    }
  },
  async execBatch(nodes, view, dir) {
    return Promise.all(nodes.map(node => this.exec(node, view, dir)));
  },
  order: 2,
  inline: () => true
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.registerFileAction)(action);

/***/ }),

/***/ "./apps/files_sharing/src/files_actions/restoreShareAction.ts":
/*!********************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/restoreShareAction.ts ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_arrow_u_left_top_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/arrow-u-left-top.svg?raw */ "./node_modules/@mdi/svg/svg/arrow-u-left-top.svg?raw");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _files_views_shares__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../files_views/shares */ "./apps/files_sharing/src/files_views/shares.ts");







const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: 'restore-share',
  displayName: nodes => (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.translatePlural)('files_sharing', 'Restore share', 'Restore shares', nodes.length),
  iconSvgInline: () => _mdi_svg_svg_arrow_u_left_top_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
  enabled: (nodes, view) => nodes.length > 0 && view.id === _files_views_shares__WEBPACK_IMPORTED_MODULE_6__.deletedSharesViewId,
  async exec(node) {
    try {
      const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/deletedshares/{id}', {
        id: node.attributes.id
      });
      await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_5__["default"].post(url);
      // Remove from current view
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files:node:deleted', node);
      return true;
    } catch (error) {
      return false;
    }
  },
  async execBatch(nodes, view, dir) {
    return Promise.all(nodes.map(node => this.exec(node, view, dir)));
  },
  order: 1,
  inline: () => true
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileAction)(action);

/***/ }),

/***/ "./apps/files_sharing/src/files_actions/sharingStatusAction.scss":
/*!***********************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/sharingStatusAction.scss ***!
  \***********************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/sass-loader/dist/cjs.js!./sharingStatusAction.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/files_actions/sharingStatusAction.scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_sass_loader_dist_cjs_js_sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files_sharing/src/files_actions/sharingStatusAction.ts":
/*!*********************************************************************!*\
  !*** ./apps/files_sharing/src/files_actions/sharingStatusAction.ts ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   ACTION_SHARING_STATUS: () => (/* binding */ ACTION_SHARING_STATUS),
/* harmony export */   action: () => (/* binding */ action)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _mdi_svg_svg_account_group_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/account-group-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-group-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_account_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/account-plus-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-plus-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _core_img_apps_circles_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../../../core/img/apps/circles.svg?raw */ "./core/img/apps/circles.svg?raw");
/* harmony import */ var _files_src_actions_sidebarAction__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../../files/src/actions/sidebarAction */ "./apps/files/src/actions/sidebarAction.ts");
/* harmony import */ var _utils_AccountIcon__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../utils/AccountIcon */ "./apps/files_sharing/src/utils/AccountIcon.ts");
/* harmony import */ var _sharingStatusAction_scss__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! ./sharingStatusAction.scss */ "./apps/files_sharing/src/files_actions/sharingStatusAction.scss");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */













const isExternal = node => {
  return node.attributes?.['is-federated'] ?? false;
};
const ACTION_SHARING_STATUS = 'sharing-status';
const action = new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileAction({
  id: ACTION_SHARING_STATUS,
  displayName(nodes) {
    const node = nodes[0];
    const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat();
    if (shareTypes.length > 0 || node.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || isExternal(node)) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared');
    }
    return '';
  },
  title(nodes) {
    const node = nodes[0];
    if (node.owner && (node.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || isExternal(node))) {
      const ownerDisplayName = node?.attributes?.['owner-display-name'];
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared by {ownerDisplayName}', {
        ownerDisplayName
      });
    }
    const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat();
    if (shareTypes.length > 1) {
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared multiple times with different people');
    }
    const sharees = node.attributes.sharees?.sharee;
    if (!sharees) {
      // No sharees so just show the default message to create a new share
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Sharing options');
    }
    const sharee = [sharees].flat()[0]; // the property is sometimes weirdly normalized, so we need to compensate
    switch (sharee.type) {
      case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.User:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared with {user}', {
          user: sharee['display-name']
        });
      case _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Group:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared with group {group}', {
          group: sharee['display-name'] ?? sharee.id
        });
      default:
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Shared with others');
    }
  },
  iconSvgInline(nodes) {
    const node = nodes[0];
    const shareTypes = Object.values(node?.attributes?.['share-types'] || {}).flat();
    // Mixed share types
    if (Array.isArray(node.attributes?.['share-types']) && node.attributes?.['share-types'].length > 1) {
      return _mdi_svg_svg_account_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__;
    }
    // Link shares
    if (shareTypes.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Link) || shareTypes.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Email)) {
      return _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_7__;
    }
    // Group shares
    if (shareTypes.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Group) || shareTypes.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.RemoteGroup)) {
      return _mdi_svg_svg_account_group_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__;
    }
    // Circle shares
    if (shareTypes.includes(_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Team)) {
      return _core_img_apps_circles_svg_raw__WEBPACK_IMPORTED_MODULE_8__;
    }
    if (node.owner && (node.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || isExternal(node))) {
      return (0,_utils_AccountIcon__WEBPACK_IMPORTED_MODULE_10__.generateAvatarSvg)(node.owner, isExternal(node));
    }
    return _mdi_svg_svg_account_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__;
  },
  enabled(nodes) {
    if (nodes.length !== 1) {
      return false;
    }
    // Do not leak information about users to public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_4__.isPublicShare)()) {
      return false;
    }
    const node = nodes[0];
    const shareTypes = node.attributes?.['share-types'];
    const isMixed = Array.isArray(shareTypes) && shareTypes.length > 0;
    // If the node is shared multiple times with
    // different share types to the current user
    if (isMixed) {
      return true;
    }
    // If the node is shared by someone else
    if (node.owner !== (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || isExternal(node)) {
      return true;
    }
    // You need share permissions to share this file
    // and read permissions to see the sidebar
    return (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.SHARE) !== 0 && (node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ) !== 0;
  },
  async exec(node, view, dir) {
    // You need read permissions to see the sidebar
    if ((node.permissions & _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.READ) !== 0) {
      window.OCA?.Files?.Sidebar?.setActiveTab?.('sharing');
      _files_src_actions_sidebarAction__WEBPACK_IMPORTED_MODULE_9__.action.exec(node, view, dir);
      return null;
    }
    // Should not happen as the enabled check should prevent this
    // leaving it here for safety or in case someone calls this action directly
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_12__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'You do not have enough permissions to share this file.'));
    return null;
  },
  inline: () => true
});
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileAction)(action);

/***/ }),

/***/ "./apps/files_sharing/src/files_filters/AccountFilter.ts":
/*!***************************************************************!*\
  !*** ./apps/files_sharing/src/files_filters/AccountFilter.ts ***!
  \***************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   registerAccountFilter: () => (/* binding */ registerAccountFilter)
/* harmony export */ });
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _components_FileListFilterAccount_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/FileListFilterAccount.vue */ "./apps/files_sharing/src/components/FileListFilterAccount.vue");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
function _defineProperty(e, r, t) { return (r = _toPropertyKey(r)) in e ? Object.defineProperty(e, r, { value: t, enumerable: !0, configurable: !0, writable: !0 }) : e[r] = t, e; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == typeof i ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != typeof t || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != typeof i) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }






/**
 * File list filter to filter by owner / sharee
 */
class AccountFilter extends _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileListFilter {
  constructor() {
    super('files_sharing:account', 100);
    _defineProperty(this, "availableAccounts", void 0);
    _defineProperty(this, "currentInstance", void 0);
    _defineProperty(this, "filterAccounts", void 0);
    this.availableAccounts = [];
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('files:list:updated', _ref => {
      let {
        contents
      } = _ref;
      this.updateAvailableAccounts(contents);
    });
  }
  mount(el) {
    if (this.currentInstance) {
      this.currentInstance.$destroy();
    }
    const View = vue__WEBPACK_IMPORTED_MODULE_3__["default"].extend(_components_FileListFilterAccount_vue__WEBPACK_IMPORTED_MODULE_4__["default"]);
    this.currentInstance = new View({
      el
    }).$on('update:accounts', accounts => this.setAccounts(accounts)).$mount();
    this.currentInstance.setAvailableAccounts(this.availableAccounts);
  }
  filter(nodes) {
    if (!this.filterAccounts || this.filterAccounts.length === 0) {
      return nodes;
    }
    const userIds = this.filterAccounts.map(_ref2 => {
      let {
        uid
      } = _ref2;
      return uid;
    });
    // Filter if the owner of the node is in the list of filtered accounts
    return nodes.filter(node => {
      const sharees = node.attributes.sharees?.sharee;
      // If the node provides no information lets keep it
      if (!node.owner && !sharees) {
        return true;
      }
      // if the owner matches
      if (node.owner && userIds.includes(node.owner)) {
        return true;
      }
      // Or any of the sharees (if only one share this will be an object, otherwise an array. So using `.flat()` to make it always an array)
      if (sharees && [sharees].flat().some(_ref3 => {
        let {
          id
        } = _ref3;
        return userIds.includes(id);
      })) {
        return true;
      }
      // Not a valid node for the current filter
      return false;
    });
  }
  reset() {
    this.currentInstance?.resetFilter();
  }
  /**
   * Set accounts that should be filtered.
   *
   * @param accounts - Account to filter or undefined if inactive.
   */
  setAccounts(accounts) {
    this.filterAccounts = accounts;
    let chips = [];
    if (this.filterAccounts && this.filterAccounts.length > 0) {
      chips = this.filterAccounts.map(_ref4 => {
        let {
          displayName,
          uid
        } = _ref4;
        return {
          text: displayName,
          user: uid,
          onclick: () => this.currentInstance?.toggleAccount(uid)
        };
      });
    }
    this.updateChips(chips);
    this.filterUpdated();
  }
  /**
   * Update the accounts owning nodes or have nodes shared to them.
   *
   * @param nodes - The current content of the file list.
   */
  updateAvailableAccounts(nodes) {
    const available = new Map();
    for (const node of nodes) {
      const owner = node.owner;
      if (owner && !available.has(owner)) {
        available.set(owner, {
          uid: owner,
          displayName: node.attributes['owner-display-name'] ?? node.owner
        });
      }
      // ensure sharees is an array (if only one share then it is just an object)
      const sharees = [node.attributes.sharees?.sharee].flat().filter(Boolean);
      for (const sharee of [sharees].flat()) {
        // Skip link shares and other without user
        if (sharee.id === '') {
          continue;
        }
        if (sharee.type !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.User && sharee.type !== _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.Remote) {
          continue;
        }
        // Add if not already added
        if (!available.has(sharee.id)) {
          available.set(sharee.id, {
            uid: sharee.id,
            displayName: sharee['display-name']
          });
        }
      }
    }
    this.availableAccounts = [...available.values()];
    if (this.currentInstance) {
      this.currentInstance.setAvailableAccounts(this.availableAccounts);
    }
  }
}
/**
 * Register the file list filter by owner or sharees
 */
function registerAccountFilter() {
  if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__.isPublicShare)()) {
    // We do not show the filter on public pages - it makes no sense
    return;
  }
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.registerFileListFilter)(new AccountFilter());
}

/***/ }),

/***/ "./apps/files_sharing/src/files_headers/noteToRecipient.ts":
/*!*****************************************************************!*\
  !*** ./apps/files_sharing/src/files_headers/noteToRecipient.ts ***!
  \*****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ registerNoteToRecipient)
/* harmony export */ });
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");


/**
 * Register the  "note to recipient" as a files list header
 */
function registerNoteToRecipient() {
  let FilesHeaderNoteToRecipient;
  let instance;
  (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.registerFileListHeaders)(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.Header({
    id: 'note-to-recipient',
    order: 0,
    // Always if there is a note
    enabled: folder => Boolean(folder.attributes.note),
    // Update the root folder if needed
    updated: folder => {
      if (instance) {
        instance.updateFolder(folder);
      }
    },
    // render simply spawns the component
    render: async (el, folder) => {
      if (FilesHeaderNoteToRecipient === undefined) {
        const {
          default: component
        } = await Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_sharing_src_views_FilesHeaderNoteToRecipient_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../views/FilesHeaderNoteToRecipient.vue */ "./apps/files_sharing/src/views/FilesHeaderNoteToRecipient.vue"));
        FilesHeaderNoteToRecipient = vue__WEBPACK_IMPORTED_MODULE_1__["default"].extend(component);
      }
      instance = new FilesHeaderNoteToRecipient().$mount(el);
      instance.updateFolder(folder);
    }
  }));
}

/***/ }),

/***/ "./apps/files_sharing/src/files_newMenu/newFileRequest.ts":
/*!****************************************************************!*\
  !*** ./apps/files_sharing/src/files_newMenu/newFileRequest.ts ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   EntryId: () => (/* binding */ EntryId),
/* harmony export */   entry: () => (/* binding */ entry)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_file_upload_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/file-upload-outline.svg?raw */ "./node_modules/@mdi/svg/svg/file-upload-outline.svg?raw");
/* harmony import */ var _services_ConfigService__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../services/ConfigService */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");






const sharingConfig = new _services_ConfigService__WEBPACK_IMPORTED_MODULE_4__["default"]();
const NewFileRequestDialogVue = (0,vue__WEBPACK_IMPORTED_MODULE_0__.defineAsyncComponent)(() => Promise.all(/*! import() */[__webpack_require__.e("core-common"), __webpack_require__.e("apps_files_sharing_src_models_Share_ts-apps_files_sharing_src_utils_GeneratePassword_ts"), __webpack_require__.e("apps_files_sharing_src_components_NewFileRequestDialog_vue")]).then(__webpack_require__.bind(__webpack_require__, /*! ../components/NewFileRequestDialog.vue */ "./apps/files_sharing/src/components/NewFileRequestDialog.vue")));
const EntryId = 'file-request';
const entry = {
  id: EntryId,
  displayName: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('files_sharing', 'Create file request'),
  iconSvgInline: _mdi_svg_svg_file_upload_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
  order: 10,
  enabled() {
    // not on public shares
    if ((0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_5__.isPublicShare)()) {
      return false;
    }
    if (!sharingConfig.isPublicUploadEnabled) {
      return false;
    }
    // We will check for the folder permission on the dialog
    return sharingConfig.isPublicShareAllowed;
  },
  async handler(context, content) {
    (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.spawnDialog)(NewFileRequestDialogVue, {
      context,
      content
    });
  }
};

/***/ }),

/***/ "./apps/files_sharing/src/files_views/shares.ts":
/*!******************************************************!*\
  !*** ./apps/files_sharing/src/files_views/shares.ts ***!
  \******************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__),
/* harmony export */   deletedSharesViewId: () => (/* binding */ deletedSharesViewId),
/* harmony export */   fileRequestViewId: () => (/* binding */ fileRequestViewId),
/* harmony export */   pendingSharesViewId: () => (/* binding */ pendingSharesViewId),
/* harmony export */   sharedWithOthersViewId: () => (/* binding */ sharedWithOthersViewId),
/* harmony export */   sharedWithYouViewId: () => (/* binding */ sharedWithYouViewId),
/* harmony export */   sharesViewId: () => (/* binding */ sharesViewId),
/* harmony export */   sharingByLinksViewId: () => (/* binding */ sharingByLinksViewId)
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _mdi_svg_svg_account_clock_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @mdi/svg/svg/account-clock-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-clock-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_account_group_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @mdi/svg/svg/account-group-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-group-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_account_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @mdi/svg/svg/account-plus-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-plus-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @mdi/svg/svg/account-outline.svg?raw */ "./node_modules/@mdi/svg/svg/account-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/svg/svg/trash-can-outline.svg?raw */ "./node_modules/@mdi/svg/svg/trash-can-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_file_upload_outline_svg_raw__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @mdi/svg/svg/file-upload-outline.svg?raw */ "./node_modules/@mdi/svg/svg/file-upload-outline.svg?raw");
/* harmony import */ var _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @mdi/svg/svg/link.svg?raw */ "./node_modules/@mdi/svg/svg/link.svg?raw");
/* harmony import */ var _services_SharingService__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ../services/SharingService */ "./apps/files_sharing/src/services/SharingService.ts");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */












const sharesViewId = 'shareoverview';
const sharedWithYouViewId = 'sharingin';
const sharedWithOthersViewId = 'sharingout';
const sharingByLinksViewId = 'sharinglinks';
const deletedSharesViewId = 'deletedshares';
const pendingSharesViewId = 'pendingshares';
const fileRequestViewId = 'filerequest';
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (() => {
  const Navigation = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getNavigation)();
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: sharesViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shares'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Overview of shared files.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No shares'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Files and folders you shared or have been shared with you will show up here'),
    icon: _mdi_svg_svg_account_plus_outline_svg_raw__WEBPACK_IMPORTED_MODULE_5__,
    order: 20,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)()
  }));
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: sharedWithYouViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shared with you'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of files that are shared with you.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Nothing shared with you yet'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Files and folders others shared with you will show up here'),
    icon: _mdi_svg_svg_account_outline_svg_raw__WEBPACK_IMPORTED_MODULE_6__,
    order: 1,
    parent: sharesViewId,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(true, false, false, false)
  }));
  // Don't show this view if the user has no storage quota
  const storageStats = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_11__.loadState)('files', 'storageStats', {
    quota: -1
  });
  if (storageStats.quota !== 0) {
    Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
      id: sharedWithOthersViewId,
      name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shared with others'),
      caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of files that you shared with others.'),
      emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Nothing shared yet'),
      emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Files and folders you shared will show up here'),
      icon: _mdi_svg_svg_account_group_outline_svg_raw__WEBPACK_IMPORTED_MODULE_4__,
      order: 2,
      parent: sharesViewId,
      columns: [],
      getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(false, true, false, false)
    }));
  }
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: sharingByLinksViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shared by link'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of files that are shared by link.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No shared links'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Files and folders you shared by link will show up here'),
    icon: _mdi_svg_svg_link_svg_raw__WEBPACK_IMPORTED_MODULE_9__,
    order: 3,
    parent: sharesViewId,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(false, true, false, false, [_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.Link])
  }));
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: fileRequestViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'File requests'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of file requests.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No file requests'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'File requests you have created will show up here'),
    icon: _mdi_svg_svg_file_upload_outline_svg_raw__WEBPACK_IMPORTED_MODULE_8__,
    order: 4,
    parent: sharesViewId,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(false, true, false, false, [_nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.Link, _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_2__.ShareType.Email]).then(_ref => {
      let {
        folder,
        contents
      } = _ref;
      return {
        folder,
        contents: contents.filter(node => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.isFileRequest)(node.attributes?.['share-attributes'] || []))
      };
    })
  }));
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: deletedSharesViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Deleted shares'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of shares you left.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No deleted shares'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shares you have left will show up here'),
    icon: _mdi_svg_svg_trash_can_outline_svg_raw__WEBPACK_IMPORTED_MODULE_7__,
    order: 5,
    parent: sharesViewId,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(false, false, false, true)
  }));
  Navigation.register(new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.View({
    id: pendingSharesViewId,
    name: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Pending shares'),
    caption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'List of unapproved shares.'),
    emptyTitle: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'No pending shares'),
    emptyCaption: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('files_sharing', 'Shares you have received but not approved will show up here'),
    icon: _mdi_svg_svg_account_clock_outline_svg_raw__WEBPACK_IMPORTED_MODULE_3__,
    order: 6,
    parent: sharesViewId,
    columns: [],
    getContents: () => (0,_services_SharingService__WEBPACK_IMPORTED_MODULE_10__.getContents)(false, false, true, false)
  }));
});

/***/ }),

/***/ "./apps/files_sharing/src/init.ts":
/*!****************************************!*\
  !*** ./apps/files_sharing/src/init.ts ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files/dav */ "./node_modules/@nextcloud/files/dist/dav.mjs");
/* harmony import */ var _files_filters_AccountFilter__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./files_filters/AccountFilter */ "./apps/files_sharing/src/files_filters/AccountFilter.ts");
/* harmony import */ var _files_newMenu_newFileRequest__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./files_newMenu/newFileRequest */ "./apps/files_sharing/src/files_newMenu/newFileRequest.ts");
/* harmony import */ var _files_headers_noteToRecipient__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./files_headers/noteToRecipient */ "./apps/files_sharing/src/files_headers/noteToRecipient.ts");
/* harmony import */ var _files_views_shares__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./files_views/shares */ "./apps/files_sharing/src/files_views/shares.ts");
/* harmony import */ var _files_actions_acceptShareAction__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./files_actions/acceptShareAction */ "./apps/files_sharing/src/files_actions/acceptShareAction.ts");
/* harmony import */ var _files_actions_openInFilesAction__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./files_actions/openInFilesAction */ "./apps/files_sharing/src/files_actions/openInFilesAction.ts");
/* harmony import */ var _files_actions_rejectShareAction__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./files_actions/rejectShareAction */ "./apps/files_sharing/src/files_actions/rejectShareAction.ts");
/* harmony import */ var _files_actions_restoreShareAction__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ./files_actions/restoreShareAction */ "./apps/files_sharing/src/files_actions/restoreShareAction.ts");
/* harmony import */ var _files_actions_sharingStatusAction__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! ./files_actions/sharingStatusAction */ "./apps/files_sharing/src/files_actions/sharingStatusAction.ts");
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */











(0,_files_views_shares__WEBPACK_IMPORTED_MODULE_5__["default"])();
(0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_0__.addNewFileMenuEntry)(_files_newMenu_newFileRequest__WEBPACK_IMPORTED_MODULE_3__.entry);
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:note', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:sharees', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:hide-download', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('nc:share-attributes', {
  nc: 'http://nextcloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('oc:share-types', {
  oc: 'http://owncloud.org/ns'
});
(0,_nextcloud_files_dav__WEBPACK_IMPORTED_MODULE_1__.registerDavProperty)('ocs:share-permissions', {
  ocs: 'http://open-collaboration-services.org/ns'
});
(0,_files_filters_AccountFilter__WEBPACK_IMPORTED_MODULE_2__.registerAccountFilter)();
// Add "note to recipient" message
(0,_files_headers_noteToRecipient__WEBPACK_IMPORTED_MODULE_4__["default"])();

/***/ }),

/***/ "./apps/files_sharing/src/services/ConfigService.ts":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/services/ConfigService.ts ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
    // eslint-disable-next-line camelcase
    return this._capabilities?.files_sharing?.sharebymail?.enabled === true
    // eslint-disable-next-line camelcase
    && this.isPublicShareAllowed === true;
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
   * @return {boolean}
   */
  get showFederatedSharesAsInternal() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'showFederatedSharesAsInternal', false);
  }
  /**
   * Show federated shares to trusted servers as internal shares
   * @return {boolean}
   */
  get showFederatedSharesToTrustedServersAsInternal() {
    return (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_1__.loadState)('files_sharing', 'showFederatedSharesToTrustedServersAsInternal', false);
  }
}

/***/ }),

/***/ "./apps/files_sharing/src/services/SharingService.ts":
/*!***********************************************************!*\
  !*** ./apps/files_sharing/src/services/SharingService.ts ***!
  \***********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   getContents: () => (/* binding */ getContents),
/* harmony export */   isFileRequest: () => (/* binding */ isFileRequest)
/* harmony export */ });
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _logger__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./logger */ "./apps/files_sharing/src/services/logger.ts");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
// TODO: Fix this instead of disabling ESLint!!!
/* eslint-disable @typescript-eslint/no-explicit-any */





const headers = {
  'Content-Type': 'application/json'
};
const ocsEntryToNode = async function (ocsEntry) {
  try {
    // Federated share handling
    if (ocsEntry?.remote_id !== undefined) {
      if (!ocsEntry.mimetype) {
        const mime = (await __webpack_require__.e(/*! import() */ "node_modules_mime_dist_src_index_js").then(__webpack_require__.bind(__webpack_require__, /*! mime */ "./node_modules/mime/dist/src/index.js"))).default;
        // This won't catch files without an extension, but this is the best we can do
        ocsEntry.mimetype = mime.getType(ocsEntry.name);
      }
      ocsEntry.item_type = ocsEntry.type || (ocsEntry.mimetype ? 'file' : 'folder');
      // different naming for remote shares
      ocsEntry.item_mtime = ocsEntry.mtime;
      ocsEntry.file_target = ocsEntry.file_target || ocsEntry.mountpoint;
      if (ocsEntry.file_target.includes('TemporaryMountPointName')) {
        ocsEntry.file_target = ocsEntry.name;
      }
      // If the share is not accepted yet we don't know which permissions it will have
      if (!ocsEntry.accepted) {
        // Need to set permissions to NONE for federated shares
        ocsEntry.item_permissions = _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE;
        ocsEntry.permissions = _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Permission.NONE;
      }
      ocsEntry.uid_owner = ocsEntry.owner;
      // TODO: have the real display name stored somewhere
      ocsEntry.displayname_owner = ocsEntry.owner;
    }
    const isFolder = ocsEntry?.item_type === 'folder';
    const hasPreview = ocsEntry?.has_preview === true;
    const Node = isFolder ? _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder : _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.File;
    // If this is an external share that is not yet accepted,
    // we don't have an id. We can fallback to the row id temporarily
    // local shares (this server) use `file_source`, but remote shares (federated) use `file_id`
    const fileid = ocsEntry.file_source || ocsEntry.file_id || ocsEntry.id;
    // Generate path and strip double slashes
    const path = ocsEntry.path || ocsEntry.file_target || ocsEntry.name;
    const source = `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath}/${path.replace(/^\/+/, '')}`;
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
      size: ocsEntry?.item_size,
      permissions: ocsEntry?.item_permissions || ocsEntry?.permissions,
      root: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath,
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
    _logger__WEBPACK_IMPORTED_MODULE_4__["default"].error('Error while parsing OCS entry', {
      error
    });
    return null;
  }
};
const getShares = function () {
  let shareWithMe = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : false;
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url, {
    headers,
    params: {
      shared_with_me: shareWithMe,
      include_tags: true
    }
  });
};
const getSharedWithYou = function () {
  return getShares(true);
};
const getSharedWithOthers = function () {
  return getShares();
};
const getRemoteShares = function () {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/remote_shares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
};
const getPendingShares = function () {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/shares/pending');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
};
const getRemotePendingShares = function () {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/remote_shares/pending');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
};
const getDeletedShares = function () {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_2__.generateOcsUrl)('apps/files_sharing/api/v1/deletedshares');
  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_3__["default"].get(url, {
    headers,
    params: {
      include_tags: true
    }
  });
};
/**
 * Check if a file request is enabled
 * @param attributes the share attributes json-encoded array
 */
const isFileRequest = function () {
  let attributes = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '[]';
  const isFileRequest = attribute => {
    return attribute.scope === 'fileRequest' && attribute.key === 'enabled' && attribute.value === true;
  };
  try {
    const attributesArray = JSON.parse(attributes);
    return attributesArray.some(isFileRequest);
  } catch (error) {
    _logger__WEBPACK_IMPORTED_MODULE_4__["default"].error('Error while parsing share attributes', {
      error
    });
    return false;
  }
};
/**
 * Group an array of objects (here Nodes) by a key
 * and return an array of arrays of them.
 * @param nodes Nodes to group
 * @param key The attribute to group by
 */
const groupBy = function (nodes, key) {
  return Object.values(nodes.reduce(function (acc, curr) {
    (acc[curr[key]] = acc[curr[key]] || []).push(curr);
    return acc;
  }, {}));
};
const getContents = async function () {
  let sharedWithYou = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : true;
  let sharedWithOthers = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : true;
  let pendingShares = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
  let deletedshares = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
  let filterTypes = arguments.length > 4 && arguments[4] !== undefined ? arguments[4] : [];
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
    folder: new _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.Folder({
      id: 0,
      source: `${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRemoteURL}${_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath}`,
      owner: (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCurrentUser)()?.uid || null
    }),
    contents
  };
};

/***/ }),

/***/ "./apps/files_sharing/src/services/logger.ts":
/*!***************************************************!*\
  !*** ./apps/files_sharing/src/services/logger.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('files_sharing').detectUser().build());

/***/ }),

/***/ "./apps/files_sharing/src/utils/AccountIcon.ts":
/*!*****************************************************!*\
  !*** ./apps/files_sharing/src/utils/AccountIcon.ts ***!
  \*****************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   generateAvatarSvg: () => (/* binding */ generateAvatarSvg)
/* harmony export */ });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const isDarkMode = () => {
  return window?.matchMedia?.('(prefers-color-scheme: dark)')?.matches === true || document.querySelector('[data-themes*=dark]') !== null;
};
const generateAvatarSvg = function (userId) {
  let isGuest = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;
  // normal avatar url: /avatar/{userId}/32?guestFallback=true
  // dark avatar url: /avatar/{userId}/32/dark?guestFallback=true
  // guest avatar url: /avatar/guest/{userId}/32
  // guest dark avatar url: /avatar/guest/{userId}/32/dark
  const basePath = isGuest ? `/avatar/guest/${userId}` : `/avatar/${userId}`;
  const darkModePath = isDarkMode() ? '/dark' : '';
  const guestFallback = isGuest ? '' : '?guestFallback=true';
  const url = `${basePath}/32${darkModePath}${guestFallback}`;
  const avatarUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)(url, {
    userId
  });
  return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg" class="sharing-status__avatar">
		<image href="${avatarUrl}" height="32" width="32" />
	</svg>`;
};

/***/ }),

/***/ "./core/img/apps/circles.svg?raw":
/*!***************************************!*\
  !*** ./core/img/apps/circles.svg?raw ***!
  \***************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><path d=\"M12,5.5A3.5,3.5 0 0,1 15.5,9A3.5,3.5 0 0,1 12,12.5A3.5,3.5 0 0,1 8.5,9A3.5,3.5 0 0,1 12,5.5M5,8C5.56,8 6.08,8.15 6.53,8.42C6.38,9.85 6.8,11.27 7.66,12.38C7.16,13.34 6.16,14 5,14A3,3 0 0,1 2,11A3,3 0 0,1 5,8M19,8A3,3 0 0,1 22,11A3,3 0 0,1 19,14C17.84,14 16.84,13.34 16.34,12.38C17.2,11.27 17.62,9.85 17.47,8.42C17.92,8.15 18.44,8 19,8M5.5,18.25C5.5,16.18 8.41,14.5 12,14.5C15.59,14.5 18.5,16.18 18.5,18.25V20H5.5V18.25M0,20V18.5C0,17.11 1.89,15.94 4.45,15.6C3.86,16.28 3.5,17.22 3.5,18.25V20H0M24,20H20.5V18.25C20.5,17.22 20.14,16.28 19.55,15.6C22.11,15.94 24,17.11 24,18.5V20Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/account-clock-outline.svg?raw":
/*!*****************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-clock-outline.svg?raw ***!
  \*****************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-clock-outline\" viewBox=\"0 0 24 24\"><path d=\"M16,14H17.5V16.82L19.94,18.23L19.19,19.53L16,17.69V14M17,12A5,5 0 0,0 12,17A5,5 0 0,0 17,22A5,5 0 0,0 22,17A5,5 0 0,0 17,12M17,10A7,7 0 0,1 24,17A7,7 0 0,1 17,24C14.21,24 11.8,22.36 10.67,20H1V17C1,14.34 6.33,13 9,13C9.6,13 10.34,13.07 11.12,13.2C12.36,11.28 14.53,10 17,10M10,17C10,16.3 10.1,15.62 10.29,15C9.87,14.93 9.43,14.9 9,14.9C6.03,14.9 2.9,16.36 2.9,17V18.1H10.09C10.03,17.74 10,17.37 10,17M9,4A4,4 0 0,1 13,8A4,4 0 0,1 9,12A4,4 0 0,1 5,8A4,4 0 0,1 9,4M9,5.9A2.1,2.1 0 0,0 6.9,8A2.1,2.1 0 0,0 9,10.1A2.1,2.1 0 0,0 11.1,8A2.1,2.1 0 0,0 9,5.9Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/account-group-outline.svg?raw":
/*!*****************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-group-outline.svg?raw ***!
  \*****************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-group-outline\" viewBox=\"0 0 24 24\"><path d=\"M12,5A3.5,3.5 0 0,0 8.5,8.5A3.5,3.5 0 0,0 12,12A3.5,3.5 0 0,0 15.5,8.5A3.5,3.5 0 0,0 12,5M12,7A1.5,1.5 0 0,1 13.5,8.5A1.5,1.5 0 0,1 12,10A1.5,1.5 0 0,1 10.5,8.5A1.5,1.5 0 0,1 12,7M5.5,8A2.5,2.5 0 0,0 3,10.5C3,11.44 3.53,12.25 4.29,12.68C4.65,12.88 5.06,13 5.5,13C5.94,13 6.35,12.88 6.71,12.68C7.08,12.47 7.39,12.17 7.62,11.81C6.89,10.86 6.5,9.7 6.5,8.5C6.5,8.41 6.5,8.31 6.5,8.22C6.2,8.08 5.86,8 5.5,8M18.5,8C18.14,8 17.8,8.08 17.5,8.22C17.5,8.31 17.5,8.41 17.5,8.5C17.5,9.7 17.11,10.86 16.38,11.81C16.5,12 16.63,12.15 16.78,12.3C16.94,12.45 17.1,12.58 17.29,12.68C17.65,12.88 18.06,13 18.5,13C18.94,13 19.35,12.88 19.71,12.68C20.47,12.25 21,11.44 21,10.5A2.5,2.5 0 0,0 18.5,8M12,14C9.66,14 5,15.17 5,17.5V19H19V17.5C19,15.17 14.34,14 12,14M4.71,14.55C2.78,14.78 0,15.76 0,17.5V19H3V17.07C3,16.06 3.69,15.22 4.71,14.55M19.29,14.55C20.31,15.22 21,16.06 21,17.07V19H24V17.5C24,15.76 21.22,14.78 19.29,14.55M12,16C13.53,16 15.24,16.5 16.23,17H7.77C8.76,16.5 10.47,16 12,16Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/account-outline.svg?raw":
/*!***********************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-outline.svg?raw ***!
  \***********************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-outline\" viewBox=\"0 0 24 24\"><path d=\"M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,6A2,2 0 0,0 10,8A2,2 0 0,0 12,10A2,2 0 0,0 14,8A2,2 0 0,0 12,6M12,13C14.67,13 20,14.33 20,17V20H4V17C4,14.33 9.33,13 12,13M12,14.9C9.03,14.9 5.9,16.36 5.9,17V18.1H18.1V17C18.1,16.36 14.97,14.9 12,14.9Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/account-plus-outline.svg?raw":
/*!****************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/account-plus-outline.svg?raw ***!
  \****************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-account-plus-outline\" viewBox=\"0 0 24 24\"><path d=\"M15,4A4,4 0 0,0 11,8A4,4 0 0,0 15,12A4,4 0 0,0 19,8A4,4 0 0,0 15,4M15,5.9C16.16,5.9 17.1,6.84 17.1,8C17.1,9.16 16.16,10.1 15,10.1A2.1,2.1 0 0,1 12.9,8A2.1,2.1 0 0,1 15,5.9M4,7V10H1V12H4V15H6V12H9V10H6V7H4M15,13C12.33,13 7,14.33 7,17V20H23V17C23,14.33 17.67,13 15,13M15,14.9C17.97,14.9 21.1,16.36 21.1,17V18.1H8.9V17C8.9,16.36 12,14.9 15,14.9Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/arrow-u-left-top.svg?raw":
/*!************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/arrow-u-left-top.svg?raw ***!
  \************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-arrow-u-left-top\" viewBox=\"0 0 24 24\"><path d=\"M20 13.5C20 17.09 17.09 20 13.5 20H6V18H13.5C16 18 18 16 18 13.5S16 9 13.5 9H7.83L10.91 12.09L9.5 13.5L4 8L9.5 2.5L10.92 3.91L7.83 7H13.5C17.09 7 20 9.91 20 13.5Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/file-upload-outline.svg?raw":
/*!***************************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/file-upload-outline.svg?raw ***!
  \***************************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-file-upload-outline\" viewBox=\"0 0 24 24\"><path d=\"M14,2L20,8V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V4A2,2 0 0,1 6,2H14M18,20V9H13V4H6V20H18M12,12L16,16H13.5V19H10.5V16H8L12,12Z\" /></svg>";

/***/ }),

/***/ "./node_modules/@mdi/svg/svg/link.svg?raw":
/*!************************************************!*\
  !*** ./node_modules/@mdi/svg/svg/link.svg?raw ***!
  \************************************************/
/***/ ((module) => {

module.exports = "<svg xmlns=\"http://www.w3.org/2000/svg\" id=\"mdi-link\" viewBox=\"0 0 24 24\"><path d=\"M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z\" /></svg>";

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=script&setup=true&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionSeparator */ "./node_modules/@nextcloud/vue/dist/Components/NcActionSeparator.mjs");





/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilter',
  props: {
    isActive: {
      type: Boolean,
      required: true
    },
    filterName: {
      type: String,
      required: true
    }
  },
  emits: ["reset-filter"],
  setup(__props) {
    return {
      __sfc: true,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t,
      NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_2__["default"],
      NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_3__["default"],
      NcActionSeparator: _nextcloud_vue_components_NcActionSeparator__WEBPACK_IMPORTED_MODULE_4__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=script&setup=true&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _files_src_components_FileListFilter_FileListFilter_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../files/src/components/FileListFilter/FileListFilter.vue */ "./apps/files/src/components/FileListFilter/FileListFilter.vue");
/* harmony import */ var _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionButton */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/components/NcAvatar */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");









/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (/*#__PURE__*/(0,vue__WEBPACK_IMPORTED_MODULE_0__.defineComponent)({
  __name: 'FileListFilterAccount',
  emits: ["update:accounts"],
  setup(__props, _ref) {
    let {
      expose,
      emit
    } = _ref;
    const accountFilter = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)('');
    const availableAccounts = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    const selectedAccounts = (0,vue__WEBPACK_IMPORTED_MODULE_0__.ref)([]);
    /**
     * Currently shown accounts (filtered)
     */
    const shownAccounts = (0,vue__WEBPACK_IMPORTED_MODULE_0__.computed)(() => {
      if (!accountFilter.value) {
        return availableAccounts.value;
      }
      const queryParts = accountFilter.value.toLocaleLowerCase().trim().split(' ');
      return availableAccounts.value.filter(account => queryParts.every(part => account.user.toLocaleLowerCase().includes(part) || account.displayName.toLocaleLowerCase().includes(part)));
    });
    /**
     * Toggle an account as selected
     * @param accountId The account to toggle
     */
    function toggleAccount(accountId) {
      const account = availableAccounts.value.find(_ref2 => {
        let {
          id
        } = _ref2;
        return id === accountId;
      });
      if (account && selectedAccounts.value.includes(account)) {
        selectedAccounts.value = selectedAccounts.value.filter(_ref3 => {
          let {
            id
          } = _ref3;
          return id !== accountId;
        });
      } else {
        if (account) {
          selectedAccounts.value = [...selectedAccounts.value, account];
        }
      }
    }
    // Watch selected account, on change we emit the new account data to the filter instance
    (0,vue__WEBPACK_IMPORTED_MODULE_0__.watch)(selectedAccounts, () => {
      // Emit selected accounts as account data
      const accounts = selectedAccounts.value.map(_ref4 => {
        let {
          id: uid,
          displayName
        } = _ref4;
        return {
          uid,
          displayName
        };
      });
      emit('update:accounts', accounts);
    });
    /**
     * Reset this filter
     */
    function resetFilter() {
      selectedAccounts.value = [];
      accountFilter.value = '';
    }
    /**
     * Update list of available accounts in current view.
     *
     * @param accounts - Accounts to use
     */
    function setAvailableAccounts(accounts) {
      availableAccounts.value = accounts.map(_ref5 => {
        let {
          uid,
          displayName
        } = _ref5;
        return {
          displayName,
          id: uid,
          user: uid
        };
      });
    }
    expose({
      resetFilter,
      setAvailableAccounts,
      toggleAccount
    });
    return {
      __sfc: true,
      emit,
      accountFilter,
      availableAccounts,
      selectedAccounts,
      shownAccounts,
      toggleAccount,
      resetFilter,
      setAvailableAccounts,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.translate,
      mdiAccountMultipleOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_2__.mdiAccountMultipleOutline,
      FileListFilter: _files_src_components_FileListFilter_FileListFilter_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
      NcActionButton: _nextcloud_vue_components_NcActionButton__WEBPACK_IMPORTED_MODULE_4__["default"],
      NcActionInput: _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_5__["default"],
      NcAvatar: _nextcloud_vue_components_NcAvatar__WEBPACK_IMPORTED_MODULE_6__["default"],
      NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_7__["default"]
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=template&id=5c291778&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.NcActions, {
    attrs: {
      "force-menu": "",
      type: _vm.isActive ? "secondary" : "tertiary",
      "menu-name": _vm.filterName
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_vm._t("icon")];
      },
      proxy: true
    }], null, true)
  }, [_vm._v(" "), _vm._t("default"), _vm._v(" "), _vm.isActive ? [_c(_setup.NcActionSeparator), _vm._v(" "), _c(_setup.NcActionButton, {
    staticClass: "files-list-filter__clear-button",
    attrs: {
      "close-after-click": ""
    },
    on: {
      click: function ($event) {
        return _vm.$emit("reset-filter");
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_setup.t("files", "Clear filter")) + "\n\t\t")])] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=template&id=40ba7127&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c(_setup.FileListFilter, {
    staticClass: "file-list-filter-accounts",
    attrs: {
      "is-active": _setup.selectedAccounts.length > 0,
      "filter-name": _setup.t("files_sharing", "People")
    },
    on: {
      "reset-filter": _setup.resetFilter
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c(_setup.NcIconSvgWrapper, {
          attrs: {
            path: _setup.mdiAccountMultipleOutline
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v(" "), _setup.availableAccounts.length > 1 ? _c(_setup.NcActionInput, {
    attrs: {
      label: _setup.t("files_sharing", "Filter accounts"),
      "label-outside": false,
      "show-trailing-button": false,
      type: "search",
      value: _setup.accountFilter
    },
    on: {
      "update:value": function ($event) {
        _setup.accountFilter = $event;
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm._l(_setup.shownAccounts, function (account) {
    return _c(_setup.NcActionButton, {
      key: account.id,
      staticClass: "file-list-filter-accounts__item",
      attrs: {
        type: "radio",
        "model-value": _setup.selectedAccounts.includes(account),
        value: account.id
      },
      on: {
        click: function ($event) {
          return _setup.toggleAccount(account.id);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c(_setup.NcAvatar, _vm._b({
            staticClass: "file-list-filter-accounts__avatar",
            attrs: {
              size: 24,
              "disable-menu": "",
              "show-user-status": false
            }
          }, "NcAvatar", account, false))];
        },
        proxy: true
      }], null, true)
    }, [_vm._v("\n\t\t" + _vm._s(account.displayName) + "\n\t")]);
  })], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/files_actions/sharingStatusAction.scss":
/*!****************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/dist/cjs.js!./apps/files_sharing/src/files_actions/sharingStatusAction.scss ***!
  \****************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
.action-items > .files-list__row-action-sharing-status {
  direction: rtl;
  padding-inline-end: 0 !important;
}

svg.sharing-status__avatar {
  height: 32px !important;
  width: 32px !important;
  max-height: 32px !important;
  max-width: 32px !important;
  border-radius: 32px;
  overflow: hidden;
}

.files-list__row-action-sharing-status .button-vue__text {
  color: var(--color-primary-element);
}
.files-list__row-action-sharing-status .button-vue__icon {
  color: var(--color-primary-element);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss ***!
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
___CSS_LOADER_EXPORT___.push([module.id, `.file-list-filter-accounts__item[data-v-40ba7127] {
  min-width: 250px;
}
.file-list-filter-accounts__avatar[data-v-40ba7127] {
  margin: calc((var(--default-clickable-area) - 24px) / 2);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

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
.files-list-filter__clear-button[data-v-5c291778] .action-button__text {
	color: var(--color-error-text);
}
[data-v-5c291778] .button-vue {
	font-weight: normal !important;
*[data-v-5c291778] {
		font-weight: normal !important;
}
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss ***!
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/FileListFilterAccount.vue?vue&type=style&index=0&id=40ba7127&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilterAccount_vue_vue_type_style_index_0_id_40ba7127_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files/src/components/FileListFilter/FileListFilter.vue?vue&type=style&index=0&id=5c291778&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileListFilter_vue_vue_type_style_index_0_id_5c291778_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ })

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
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"2fcef36253529e5f48bc","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"f3a3966faa81f9b81fa8","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-cc29b1":"9fa10a9863e5b78deec8","apps_files_sharing_src_models_Share_ts-apps_files_sharing_src_utils_GeneratePassword_ts":"fdd3d746f857f7a26f8d","apps_files_sharing_src_components_NewFileRequestDialog_vue":"13776a81a7f2f9271f0e","apps_files_sharing_src_views_FilesHeaderNoteToRecipient_vue":"6c0cc9feb7cebba8e6ed","node_modules_mime_dist_src_index_js":"81767346a574e7848085","node_modules_nextcloud_dialogs_dist_chunks_FilePicker-CsU6FfAP_mjs":"8bce3ebf3ef868f175e5"}[chunkId] + "";
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	(() => {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
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
/******/ 		var dataWebpackPrefix = "nextcloud:";
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
/******/ 				script.timeout = 120;
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
/******/ 		if (__webpack_require__.g.importScripts) scriptUrl = __webpack_require__.g.location + "";
/******/ 		var document = __webpack_require__.g.document;
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
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"files_sharing-init": 0
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
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
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
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/files_sharing/src/init.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=files_sharing-init.js.map?v=22ba7b671a9d847c1d2b