"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_files_external_src_views_CredentialsDialog_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts":
/*!***************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts ***!
  \***************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDialog.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");






/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_5__.defineComponent)({
  name: 'CredentialsDialog',
  components: {
    NcDialog: _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcTextField: _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcPasswordField: _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_3__["default"]
  },
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t
    };
  },
  data() {
    return {
      login: '',
      password: ''
    };
  },
  computed: {
    dialogButtons() {
      return [{
        label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files_external', 'Submit'),
        type: 'primary',
        nativeType: 'submit'
      }];
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b":
/*!****************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b ***!
  \****************************************************************************************************************************************************************************************************************************************************************************/
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
  return _c("NcDialog", {
    staticClass: "external-storage-auth",
    attrs: {
      buttons: _vm.dialogButtons,
      "close-on-click-outside": "",
      "data-cy-external-storage-auth": "",
      "is-form": "",
      name: _vm.t("files_external", "Storage credentials"),
      "out-transition": ""
    },
    on: {
      submit: function ($event) {
        return _vm.$emit("close", {
          login: _vm.login,
          password: _vm.password
        });
      },
      "update:open": function ($event) {
        return _vm.$emit("close");
      }
    }
  }, [_c("NcNoteCard", {
    staticClass: "external-storage-auth__header",
    attrs: {
      text: _vm.t("files_external", "To access the storage, you need to provide the authentication credentials."),
      type: "info"
    }
  }), _vm._v(" "), _c("NcTextField", {
    ref: "login",
    staticClass: "external-storage-auth__login",
    attrs: {
      "data-cy-external-storage-auth-dialog-login": "",
      label: _vm.t("files_external", "Login"),
      placeholder: _vm.t("files_external", "Enter the storage login"),
      minlength: "2",
      name: "login",
      required: "",
      value: _vm.login
    },
    on: {
      "update:value": function ($event) {
        _vm.login = $event;
      }
    }
  }), _vm._v(" "), _c("NcPasswordField", {
    ref: "password",
    staticClass: "external-storage-auth__password",
    attrs: {
      "data-cy-external-storage-auth-dialog-password": "",
      label: _vm.t("files_external", "Password"),
      placeholder: _vm.t("files_external", "Enter the storage password"),
      name: "password",
      required: "",
      value: _vm.password
    },
    on: {
      "update:value": function ($event) {
        _vm.password = $event;
      }
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./apps/files_external/src/views/CredentialsDialog.vue":
/*!*************************************************************!*\
  !*** ./apps/files_external/src/views/CredentialsDialog.vue ***!
  \*************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CredentialsDialog.vue?vue&type=template&id=7a0bbd5b */ "./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b");
/* harmony import */ var _CredentialsDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CredentialsDialog.vue?vue&type=script&lang=ts */ "./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CredentialsDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__.render,
  _CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_external/src/views/CredentialsDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts":
/*!*************************************************************************************!*\
  !*** ./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts ***!
  \*************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_CredentialsDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CredentialsDialog.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_CredentialsDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b":
/*!*******************************************************************************************!*\
  !*** ./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b ***!
  \*******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CredentialsDialog_vue_vue_type_template_id_7a0bbd5b__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CredentialsDialog.vue?vue&type=template&id=7a0bbd5b */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_external/src/views/CredentialsDialog.vue?vue&type=template&id=7a0bbd5b");


/***/ })

}]);
//# sourceMappingURL=apps_files_external_src_views_CredentialsDialog_vue-apps_files_external_src_views_CredentialsDialog_vue.js.map?v=31738d2504cab578ce0c