/******/ (() => { // webpackBootstrap
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/systemtags/src/admin.ts":
/*!**************************************!*\
  !*** ./apps/systemtags/src/admin.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _views_SystemTagsSection_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./views/SystemTagsSection.vue */ "./apps/systemtags/src/views/SystemTagsSection.vue");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



__webpack_require__.nc = (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_0__.getCSPNonce)();
const SystemTagsSectionView = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_views_SystemTagsSection_vue__WEBPACK_IMPORTED_MODULE_1__["default"]);
new SystemTagsSectionView().$mount('#vue-admin-systemtags');

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagForm.vue":
/*!**********************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagForm.vue ***!
  \**********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true */ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true");
/* harmony import */ var _SystemTagForm_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SystemTagForm.vue?vue&type=script&lang=ts */ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts");
/* harmony import */ var _SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true */ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SystemTagForm_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "5e6ae519",
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/systemtags/src/components/SystemTagForm.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts":
/*!**********************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagForm.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true":
/*!*******************************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true ***!
  \*******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true":
/*!****************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_template_id_5e6ae519_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true");


/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagsCreationControl.vue":
/*!**********************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagsCreationControl.vue ***!
  \**********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SystemTagsCreationControl.vue?vue&type=template&id=ab380480 */ "./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480");
/* harmony import */ var _SystemTagsCreationControl_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SystemTagsCreationControl.vue?vue&type=script&lang=ts */ "./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SystemTagsCreationControl_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__.render,
  _SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/systemtags/src/components/SystemTagsCreationControl.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts":
/*!**********************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsCreationControl_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagsCreationControl.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsCreationControl_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480":
/*!****************************************************************************************************!*\
  !*** ./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480 ***!
  \****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsCreationControl_vue_vue_type_template_id_ab380480__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagsCreationControl.vue?vue&type=template&id=ab380480 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480");


/***/ }),

/***/ "./apps/systemtags/src/logger.ts":
/*!***************************************!*\
  !*** ./apps/systemtags/src/logger.ts ***!
  \***************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/logger */ "./node_modules/@nextcloud/logger/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,_nextcloud_logger__WEBPACK_IMPORTED_MODULE_0__.getLoggerBuilder)().setApp('systemtags').detectUser().build());

/***/ }),

/***/ "./apps/systemtags/src/services/api.ts":
/*!*********************************************!*\
  !*** ./apps/systemtags/src/services/api.ts ***!
  \*********************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   createTag: () => (/* binding */ createTag),
/* harmony export */   deleteTag: () => (/* binding */ deleteTag),
/* harmony export */   fetchLastUsedTagIds: () => (/* binding */ fetchLastUsedTagIds),
/* harmony export */   fetchTag: () => (/* binding */ fetchTag),
/* harmony export */   fetchTags: () => (/* binding */ fetchTags),
/* harmony export */   fetchTagsPayload: () => (/* binding */ fetchTagsPayload),
/* harmony export */   getTagObjects: () => (/* binding */ getTagObjects),
/* harmony export */   setTagObjects: () => (/* binding */ setTagObjects),
/* harmony export */   updateSystemTagsAdminRestriction: () => (/* binding */ updateSystemTagsAdminRestriction),
/* harmony export */   updateTag: () => (/* binding */ updateTag)
/* harmony export */ });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _davClient_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./davClient.js */ "./apps/systemtags/src/services/davClient.ts");
/* harmony import */ var _utils__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../utils */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../logger.ts */ "./apps/systemtags/src/logger.ts");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */








const fetchTagsPayload = `<?xml version="1.0"?>
<d:propfind xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
	<d:prop>
		<oc:id />
		<oc:display-name />
		<oc:user-visible />
		<oc:user-assignable />
		<oc:can-assign />
		<d:getetag />
		<nc:color />
	</d:prop>
</d:propfind>`;
const fetchTags = async () => {
  const path = '/systemtags';
  try {
    const {
      data: tags
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.getDirectoryContents(path, {
      data: fetchTagsPayload,
      details: true,
      glob: '/systemtags/*' // Filter out first empty tag
    });
    return (0,_utils__WEBPACK_IMPORTED_MODULE_4__.parseTags)(tags);
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load tags'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load tags'));
  }
};
const fetchTag = async tagId => {
  const path = '/systemtags/' + tagId;
  try {
    const {
      data: tag
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.stat(path, {
      data: fetchTagsPayload,
      details: true
    });
    return (0,_utils__WEBPACK_IMPORTED_MODULE_4__.parseTags)([tag])[0];
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load tag'));
  }
};
const fetchLastUsedTagIds = async () => {
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/systemtags/lastused');
  try {
    const {
      data: lastUsedTagIds
    } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].get(url);
    return lastUsedTagIds.map(Number);
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load last used tags'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to load last used tags'));
  }
};
/**
 * Create a tag and return the Id of the newly created tag.
 *
 * @param tag The tag to create
 */
const createTag = async tag => {
  const path = '/systemtags';
  const tagToPost = (0,_utils__WEBPACK_IMPORTED_MODULE_4__.formatTag)(tag);
  try {
    const {
      headers
    } = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
      method: 'POST',
      data: tagToPost
    });
    const contentLocation = headers.get('content-location');
    if (contentLocation) {
      (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_6__.emit)('systemtags:tag:created', tag);
      return (0,_utils__WEBPACK_IMPORTED_MODULE_4__.parseIdFromLocation)(contentLocation);
    }
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Missing "Content-Location" header'));
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Missing "Content-Location" header'));
  } catch (error) {
    if (error?.response?.status === 409) {
      _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'A tag with the same name already exists'), {
        error
      });
      throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'A tag with the same name already exists'));
    }
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to create tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to create tag'));
  }
};
const updateTag = async tag => {
  const path = '/systemtags/' + tag.id;
  const data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<oc:display-name>${tag.displayName}</oc:display-name>
				<oc:user-visible>${tag.userVisible}</oc:user-visible>
				<oc:user-assignable>${tag.userAssignable}</oc:user-assignable>
				<nc:color>${tag?.color || null}</nc:color>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
      method: 'PROPPATCH',
      data
    });
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_6__.emit)('systemtags:tag:updated', tag);
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to update tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to update tag'));
  }
};
const deleteTag = async tag => {
  const path = '/systemtags/' + tag.id;
  try {
    await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.deleteFile(path);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_6__.emit)('systemtags:tag:deleted', tag);
  } catch (error) {
    _logger_ts__WEBPACK_IMPORTED_MODULE_5__["default"].error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to delete tag'), {
      error
    });
    throw new Error((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Failed to delete tag'));
  }
};
const getTagObjects = async function (tag, type) {
  const path = `/systemtags/${tag.id}/${type}`;
  const data = `<?xml version="1.0"?>
	<d:propfind xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:prop>
			<nc:object-ids />
			<d:getetag />
		</d:prop>
	</d:propfind>`;
  const response = await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.stat(path, {
    data,
    details: true
  });
  const etag = response?.data?.props?.getetag || '""';
  const objects = Object.values(response?.data?.props?.['object-ids'] || []).flat();
  return {
    etag,
    objects
  };
};
/**
 * Set the objects for a tag.
 * Warning: This will overwrite the existing objects.
 * @param tag The tag to set the objects for
 * @param type The type of the objects
 * @param objectIds The objects to set
 * @param etag Strongly recommended to avoid conflict and data loss.
 */
const setTagObjects = async function (tag, type, objectIds) {
  let etag = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : '';
  const path = `/systemtags/${tag.id}/${type}`;
  let data = `<?xml version="1.0"?>
	<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
		<d:set>
			<d:prop>
				<nc:object-ids>${objectIds.map(_ref => {
    let {
      id,
      type
    } = _ref;
    return `<nc:object-id><nc:id>${id}</nc:id><nc:type>${type}</nc:type></nc:object-id>`;
  }).join('')}</nc:object-ids>
			</d:prop>
		</d:set>
	</d:propertyupdate>`;
  if (objectIds.length === 0) {
    data = `<?xml version="1.0"?>
		<d:propertyupdate xmlns:d="DAV:" xmlns:nc="http://nextcloud.org/ns">
			<d:remove>
				<d:prop>
					<nc:object-ids />
				</d:prop>
			</d:remove>
		</d:propertyupdate>`;
  }
  await _davClient_js__WEBPACK_IMPORTED_MODULE_3__.davClient.customRequest(path, {
    method: 'PROPPATCH',
    data,
    headers: {
      'if-match': etag
    }
  });
};
const updateSystemTagsAdminRestriction = async isAllowed => {
  // Convert to string for compatibility
  const isAllowedString = isAllowed ? '1' : '0';
  const url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('/apps/provisioning_api/api/v1/config/apps/{appId}/{key}', {
    appId: 'systemtags',
    key: 'restrict_creation_to_admin'
  });
  await (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_7__.confirmPassword)();
  const res = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
    value: isAllowedString
  });
  return res.data;
};

/***/ }),

/***/ "./apps/systemtags/src/services/davClient.ts":
/*!***************************************************!*\
  !*** ./apps/systemtags/src/services/davClient.ts ***!
  \***************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   davClient: () => (/* binding */ davClient)
/* harmony export */ });
/* harmony import */ var webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! webdav/dist/node/index.js */ "./node_modules/webdav/dist/node/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */



// init webdav client
const rootUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateRemoteUrl)('dav');
const davClient = (0,webdav_dist_node_index_js__WEBPACK_IMPORTED_MODULE_0__.createClient)(rootUrl);
// set CSRF token header
const setHeaders = token => {
  davClient.setHeaders({
    // Add this so the server knows it is an request from the browser
    'X-Requested-With': 'XMLHttpRequest',
    // Inject user auth
    requesttoken: token ?? ''
  });
};
// refresh headers when request token changes
(0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.onRequestTokenUpdate)(setHeaders);
setHeaders((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_2__.getRequestToken)());

/***/ }),

/***/ "./apps/systemtags/src/utils.ts":
/*!**************************************!*\
  !*** ./apps/systemtags/src/utils.ts ***!
  \**************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   defaultBaseTag: () => (/* binding */ defaultBaseTag),
/* harmony export */   formatTag: () => (/* binding */ formatTag),
/* harmony export */   getNodeSystemTags: () => (/* binding */ getNodeSystemTags),
/* harmony export */   parseIdFromLocation: () => (/* binding */ parseIdFromLocation),
/* harmony export */   parseTags: () => (/* binding */ parseTags),
/* harmony export */   setNodeSystemTags: () => (/* binding */ setNodeSystemTags)
/* harmony export */ });
/* harmony import */ var camelcase__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! camelcase */ "./node_modules/camelcase/index.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */


const defaultBaseTag = {
  userVisible: true,
  userAssignable: true,
  canAssign: true
};
const parseTags = tags => {
  return tags.map(_ref => {
    let {
      props
    } = _ref;
    return Object.fromEntries(Object.entries(props).map(_ref2 => {
      let [key, value] = _ref2;
      return [(0,camelcase__WEBPACK_IMPORTED_MODULE_0__["default"])(key), (0,camelcase__WEBPACK_IMPORTED_MODULE_0__["default"])(key) === 'displayName' ? String(value) : value];
    }));
  });
};
/**
 * Parse id from `Content-Location` header
 * @param url URL to parse
 */
const parseIdFromLocation = url => {
  const queryPos = url.indexOf('?');
  if (queryPos > 0) {
    url = url.substring(0, queryPos);
  }
  const parts = url.split('/');
  let result;
  do {
    result = parts[parts.length - 1];
    parts.pop();
    // note: first result can be empty when there is a trailing slash,
    // so we take the part before that
  } while (!result && parts.length > 0);
  return Number(result);
};
const formatTag = initialTag => {
  if ('name' in initialTag && !('displayName' in initialTag)) {
    return {
      ...initialTag
    };
  }
  const tag = {
    ...initialTag
  };
  tag.name = tag.displayName;
  delete tag.displayName;
  return tag;
};
const getNodeSystemTags = function (node) {
  const attribute = node.attributes?.['system-tags']?.['system-tag'];
  if (attribute === undefined) {
    return [];
  }
  // if there is only one tag it is a single string or prop object
  // if there are multiple then its an array - so we flatten it to be always an array of string or prop objects
  return [attribute].flat().map(tag => typeof tag === 'string'
  // its a plain text prop (the tag name) without prop attributes
  ? tag
  // its a prop object with attributes, the tag name is in the 'text' attribute
  : tag.text);
};
const setNodeSystemTags = function (node, tags) {
  vue__WEBPACK_IMPORTED_MODULE_1__["default"].set(node.attributes, 'system-tags', {
    'system-tag': tags
  });
};

/***/ }),

/***/ "./apps/systemtags/src/views/SystemTagsSection.vue":
/*!*********************************************************!*\
  !*** ./apps/systemtags/src/views/SystemTagsSection.vue ***!
  \*********************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SystemTagsSection.vue?vue&type=template&id=32a39e09 */ "./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09");
/* harmony import */ var _SystemTagsSection_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SystemTagsSection.vue?vue&type=script&lang=ts */ "./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _SystemTagsSection_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__.render,
  _SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) // removed by dead control flow
{ var api; }
component.options.__file = "apps/systemtags/src/views/SystemTagsSection.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts":
/*!*********************************************************************************!*\
  !*** ./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts ***!
  \*********************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsSection_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagsSection.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsSection_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09":
/*!***************************************************************************************!*\
  !*** ./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09 ***!
  \***************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagsSection_vue_vue_type_template_id_32a39e09__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagsSection.vue?vue&type=template&id=32a39e09 */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09");


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts":
/*!************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=script&lang=ts ***!
  \************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelectTags__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelectTags */ "./node_modules/@nextcloud/vue/dist/Components/NcSelectTags.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _utils_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ../utils.js */ "./apps/systemtags/src/utils.ts");
/* harmony import */ var _services_api_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../services/api.js */ "./apps/systemtags/src/services/api.ts");
/* eslint-disable */










var TagLevel;
(function (TagLevel) {
  TagLevel["Public"] = "Public";
  TagLevel["Restricted"] = "Restricted";
  TagLevel["Invisible"] = "Invisible";
})(TagLevel || (TagLevel = {}));
const tagLevelOptions = [{
  id: TagLevel.Public,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Public')
}, {
  id: TagLevel.Restricted,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Restricted')
}, {
  id: TagLevel.Invisible,
  label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Invisible')
}];
const getTagLevel = (userVisible, userAssignable) => {
  const matchLevel = {
    [[true, true].join(',')]: TagLevel.Public,
    [[true, false].join(',')]: TagLevel.Restricted,
    [[false, false].join(',')]: TagLevel.Invisible
  };
  return matchLevel[[userVisible, userAssignable].join(',')];
};
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_9__["default"].extend({
  name: 'SystemTagForm',
  components: {
    NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcSelectTags: _nextcloud_vue_components_NcSelectTags__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    tags: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      tagLevelOptions,
      selectedTag: null,
      errorMessage: '',
      tagName: '',
      tagLevel: TagLevel.Public
    };
  },
  watch: {
    selectedTag(tag) {
      this.tagName = tag ? tag.displayName : '';
      this.tagLevel = tag ? getTagLevel(tag.userVisible, tag.userAssignable) : TagLevel.Public;
    }
  },
  computed: {
    isCreating() {
      return this.selectedTag === null;
    },
    isCreateDisabled() {
      return this.tagName === '';
    },
    isUpdateDisabled() {
      return this.tagName === '' || this.selectedTag?.displayName === this.tagName && getTagLevel(this.selectedTag?.userVisible, this.selectedTag?.userAssignable) === this.tagLevel;
    },
    isResetDisabled() {
      if (this.isCreating) {
        return this.tagName === '' && this.tagLevel === TagLevel.Public;
      }
      return this.selectedTag === null;
    },
    userVisible() {
      const matchLevel = {
        [TagLevel.Public]: true,
        [TagLevel.Restricted]: true,
        [TagLevel.Invisible]: false
      };
      return matchLevel[this.tagLevel];
    },
    userAssignable() {
      const matchLevel = {
        [TagLevel.Public]: true,
        [TagLevel.Restricted]: false,
        [TagLevel.Invisible]: false
      };
      return matchLevel[this.tagLevel];
    },
    tagProperties() {
      return {
        displayName: this.tagName,
        userVisible: this.userVisible,
        userAssignable: this.userAssignable
      };
    }
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate,
    async handleSubmit() {
      if (this.isCreating) {
        await this.create();
        return;
      }
      await this.update();
    },
    async create() {
      const tag = {
        ..._utils_js__WEBPACK_IMPORTED_MODULE_7__.defaultBaseTag,
        ...this.tagProperties
      };
      this.loading = true;
      try {
        const id = await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_8__.createTag)(tag);
        const createdTag = {
          ...tag,
          id
        };
        this.$emit('tag:created', createdTag);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Created tag'));
        this.reset();
      } catch (error) {
        this.errorMessage = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Failed to create tag');
      }
      this.loading = false;
    },
    async update() {
      if (this.selectedTag === null) {
        return;
      }
      const tag = {
        ...this.selectedTag,
        ...this.tagProperties
      };
      this.loading = true;
      try {
        await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_8__.updateTag)(tag);
        this.selectedTag = tag;
        this.$emit('tag:updated', tag);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Updated tag'));
        this.$refs.tagNameInput?.focus();
      } catch (error) {
        this.errorMessage = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Failed to update tag');
      }
      this.loading = false;
    },
    async handleDelete() {
      if (this.selectedTag === null) {
        return;
      }
      this.loading = true;
      try {
        await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_8__.deleteTag)(this.selectedTag);
        this.$emit('tag:deleted', this.selectedTag);
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_6__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Deleted tag'));
        this.reset();
      } catch (error) {
        this.errorMessage = (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.translate)('systemtags', 'Failed to delete tag');
      }
      this.loading = false;
    },
    reset() {
      this.selectedTag = null;
      this.errorMessage = '';
      this.tagName = '';
      this.tagLevel = TagLevel.Public;
      this.$refs.tagNameInput?.focus();
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts":
/*!************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=script&lang=ts ***!
  \************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _services_api_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/api.js */ "./apps/systemtags/src/services/api.ts");
/* harmony import */ var _logger_ts__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../logger.ts */ "./apps/systemtags/src/logger.ts");
/* harmony import */ var _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: 'SystemTagsCreationControl',
  components: {
    NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t
    };
  },
  data() {
    return {
      // By default, system tags creation is not restricted to admins
      systemTagsCreationRestrictedToAdmin: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('systemtags', 'restrictSystemTagsCreationToAdmin', false)
    };
  },
  methods: {
    async updateSystemTagsDefault(isRestricted) {
      try {
        const responseData = await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_3__.updateSystemTagsAdminRestriction)(isRestricted);
        console.debug('updateSystemTagsDefault', responseData);
        this.handleResponse({
          isRestricted,
          status: responseData.ocs?.meta?.status
        });
      } catch (e) {
        this.handleResponse({
          errorMessage: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'Unable to update setting'),
          error: e
        });
      }
    },
    handleResponse(_ref) {
      let {
        isRestricted,
        status,
        errorMessage,
        error
      } = _ref;
      if (status === 'ok') {
        this.systemTagsCreationRestrictedToAdmin = isRestricted;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showSuccess)(isRestricted ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'System tag creation is now restricted to administrators') : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.t)('systemtags', 'System tag creation is now allowed for everybody'));
        return;
      }
      if (errorMessage) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_1__.showError)(errorMessage);
        _logger_ts__WEBPACK_IMPORTED_MODULE_4__["default"].error(errorMessage, error);
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts":
/*!***********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=script&lang=ts ***!
  \***********************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/components/NcLoadingIcon */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/components/NcSettingsSection */ "./node_modules/@nextcloud/vue/dist/Components/NcSettingsSection.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _components_SystemTagForm_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../components/SystemTagForm.vue */ "./apps/systemtags/src/components/SystemTagForm.vue");
/* harmony import */ var _components_SystemTagsCreationControl_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../components/SystemTagsCreationControl.vue */ "./apps/systemtags/src/components/SystemTagsCreationControl.vue");
/* harmony import */ var _services_api_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../services/api.js */ "./apps/systemtags/src/services/api.ts");
/* eslint-disable */








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (vue__WEBPACK_IMPORTED_MODULE_7__["default"].extend({
  name: 'SystemTagsSection',
  components: {
    NcLoadingIcon: _nextcloud_vue_components_NcLoadingIcon__WEBPACK_IMPORTED_MODULE_0__["default"],
    NcSettingsSection: _nextcloud_vue_components_NcSettingsSection__WEBPACK_IMPORTED_MODULE_1__["default"],
    SystemTagForm: _components_SystemTagForm_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    SystemTagsCreationControl: _components_SystemTagsCreationControl_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  data() {
    return {
      loadingTags: false,
      tags: []
    };
  },
  async created() {
    this.loadingTags = true;
    try {
      this.tags = await (0,_services_api_js__WEBPACK_IMPORTED_MODULE_6__.fetchTags)();
    } catch (error) {
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_3__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate)('systemtags', 'Failed to load tags'));
    }
    this.loadingTags = false;
  },
  methods: {
    t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_2__.translate,
    handleCreate(tag) {
      this.tags.unshift(tag);
    },
    handleUpdate(tag) {
      const tagIndex = this.tags.findIndex(currTag => currTag.id === tag.id);
      this.tags.splice(tagIndex, 1);
      this.tags.unshift(tag);
    },
    handleDelete(tag) {
      const tagIndex = this.tags.findIndex(currTag => currTag.id === tag.id);
      this.tags.splice(tagIndex, 1);
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=template&id=5e6ae519&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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
    staticClass: "system-tag-form",
    attrs: {
      disabled: _vm.loading,
      "aria-labelledby": "system-tag-form-heading"
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        return _vm.handleSubmit.apply(null, arguments);
      },
      reset: _vm.reset
    }
  }, [_c("h4", {
    attrs: {
      id: "system-tag-form-heading"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("systemtags", "Create or edit tags")) + "\n\t")]), _vm._v(" "), _c("div", {
    staticClass: "system-tag-form__group"
  }, [_c("label", {
    attrs: {
      for: "system-tags-input"
    }
  }, [_vm._v(_vm._s(_vm.t("systemtags", "Search for a tag to edit")))]), _vm._v(" "), _c("NcSelectTags", {
    attrs: {
      "input-id": "system-tags-input",
      placeholder: _vm.t("systemtags", "Collaborative tags …"),
      "fetch-tags": false,
      options: _vm.tags,
      multiple: false,
      passthru: ""
    },
    scopedSlots: _vm._u([{
      key: "no-options",
      fn: function () {
        return [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("systemtags", "No tags to select")) + "\n\t\t\t")];
      },
      proxy: true
    }]),
    model: {
      value: _vm.selectedTag,
      callback: function ($$v) {
        _vm.selectedTag = $$v;
      },
      expression: "selectedTag"
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "system-tag-form__group"
  }, [_c("label", {
    attrs: {
      for: "system-tag-name"
    }
  }, [_vm._v(_vm._s(_vm.t("systemtags", "Tag name")))]), _vm._v(" "), _c("NcTextField", {
    ref: "tagNameInput",
    attrs: {
      id: "system-tag-name",
      value: _vm.tagName,
      error: Boolean(_vm.errorMessage),
      "helper-text": _vm.errorMessage,
      "label-outside": ""
    },
    on: {
      "update:value": function ($event) {
        _vm.tagName = $event;
      }
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "system-tag-form__group"
  }, [_c("label", {
    attrs: {
      for: "system-tag-level"
    }
  }, [_vm._v(_vm._s(_vm.t("systemtags", "Tag level")))]), _vm._v(" "), _c("NcSelect", {
    attrs: {
      "input-id": "system-tag-level",
      options: _vm.tagLevelOptions,
      reduce: level => level.id,
      clearable: false,
      disabled: _vm.loading
    },
    model: {
      value: _vm.tagLevel,
      callback: function ($$v) {
        _vm.tagLevel = $$v;
      },
      expression: "tagLevel"
    }
  })], 1), _vm._v(" "), _c("div", {
    staticClass: "system-tag-form__row"
  }, [_vm.isCreating ? _c("NcButton", {
    attrs: {
      "native-type": "submit",
      disabled: _vm.isCreateDisabled || _vm.loading
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("systemtags", "Create")) + "\n\t\t")]) : [_c("NcButton", {
    attrs: {
      "native-type": "submit",
      disabled: _vm.isUpdateDisabled || _vm.loading
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("systemtags", "Update")) + "\n\t\t\t")]), _vm._v(" "), _c("NcButton", {
    attrs: {
      disabled: _vm.loading
    },
    on: {
      click: _vm.handleDelete
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("systemtags", "Delete")) + "\n\t\t\t")])], _vm._v(" "), _c("NcButton", {
    attrs: {
      "native-type": "reset",
      disabled: _vm.isResetDisabled || _vm.loading
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("systemtags", "Reset")) + "\n\t\t")]), _vm._v(" "), _vm.loading ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("systemtags", "Loading …"),
      size: 32
    }
  }) : _vm._e()], 2)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagsCreationControl.vue?vue&type=template&id=ab380480 ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    attrs: {
      id: "system-tags-creation-control"
    }
  }, [_c("h4", {
    staticClass: "inlineblock"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("systemtags", "System tag management")) + "\n\t")]), _vm._v(" "), _c("p", {
    staticClass: "settings-hint"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("systemtags", "If enabled, only administrators can create and edit tags. Accounts can still assign and remove them from files.")) + "\n\t")]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    attrs: {
      type: "switch",
      checked: _vm.systemTagsCreationRestrictedToAdmin
    },
    on: {
      "update:checked": [function ($event) {
        _vm.systemTagsCreationRestrictedToAdmin = $event;
      }, _vm.updateSystemTagsDefault]
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("systemtags", "Restrict tag creation and editing to administrators")) + "\n\t")])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09":
/*!************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/views/SystemTagsSection.vue?vue&type=template&id=32a39e09 ***!
  \************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("NcSettingsSection", {
    attrs: {
      name: _vm.t("systemtags", "Collaborative tags"),
      description: _vm.t("systemtags", "Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.")
    }
  }, [_c("SystemTagsCreationControl"), _vm._v(" "), _vm.loadingTags ? _c("NcLoadingIcon", {
    attrs: {
      name: _vm.t("systemtags", "Loading collaborative tags …"),
      size: 32
    }
  }) : _c("SystemTagForm", {
    attrs: {
      tags: _vm.tags
    },
    on: {
      "tag:created": _vm.handleCreate,
      "tag:updated": _vm.handleUpdate,
      "tag:deleted": _vm.handleDelete
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

"use strict";
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
___CSS_LOADER_EXPORT___.push([module.id, `.system-tag-form[data-v-5e6ae519] {
  display: flex;
  flex-direction: column;
  max-width: 400px;
  gap: 8px 0;
}
.system-tag-form__group[data-v-5e6ae519] {
  display: flex;
  flex-direction: column;
}
.system-tag-form__row[data-v-5e6ae519] {
  margin-top: 8px;
  display: flex;
  gap: 0 4px;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

"use strict";
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/systemtags/src/components/SystemTagForm.vue?vue&type=style&index=0&id=5e6ae519&lang=scss&scoped=true");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());
options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SystemTagForm_vue_vue_type_style_index_0_id_5e6ae519_lang_scss_scoped_true__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "?0cc0":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?19e6":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?3e83":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?4f7e":
/*!********************************!*\
  !*** ./util.inspect (ignored) ***!
  \********************************/
/***/ (() => {

/* (ignored) */

/***/ }),

/***/ "?aeb7":
/*!**********************!*\
  !*** util (ignored) ***!
  \**********************/
/***/ (() => {

/* (ignored) */

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
/******/ 			return "" + chunkId + "-" + chunkId + ".js?v=" + {"node_modules_nextcloud_dialogs_dist_chunks_index-BC-7VPxC_mjs":"0a21f85fb5edb886fad0","node_modules_nextcloud_dialogs_dist_chunks_PublicAuthPrompt-BSFsDqYB_mjs":"5414d4143400c9b713c3","data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e":"87f84948225387ac2eec"}[chunkId] + "";
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
/******/ 			"systemtags-admin": 0
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
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], () => (__webpack_require__("./apps/systemtags/src/admin.ts")))
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=systemtags-admin.js.map?v=9bfec5e00661b2e95a9f