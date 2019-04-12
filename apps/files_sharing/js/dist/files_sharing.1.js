(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[1],{

/***/ "./apps/files_sharing/src/collaborationresources.js":
/*!**********************************************************!*\
  !*** ./apps/files_sharing/src/collaborationresources.js ***!
  \**********************************************************/
/*! exports provided: Vue, View */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Vue", function() { return vue__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! nextcloud-vue */ "./node_modules/nextcloud-vue/dist/ncvuecomponents.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var _views_CollaborationView__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./views/CollaborationView */ "./apps/files_sharing/src/views/CollaborationView.vue");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "View", function() { return _views_CollaborationView__WEBPACK_IMPORTED_MODULE_4__["default"]; });

/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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




vue__WEBPACK_IMPORTED_MODULE_0__["default"].prototype.t = t;
vue__WEBPACK_IMPORTED_MODULE_0__["default"].component('PopoverMenu', nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__["PopoverMenu"]);
vue__WEBPACK_IMPORTED_MODULE_0__["default"].directive('ClickOutside', vue_click_outside__WEBPACK_IMPORTED_MODULE_2___default.a);
vue__WEBPACK_IMPORTED_MODULE_0__["default"].directive('Tooltip', v_tooltip__WEBPACK_IMPORTED_MODULE_3__["VTooltip"]);



/***/ }),

/***/ "./apps/files_sharing/src/views/CollaborationView.vue":
/*!************************************************************!*\
  !*** ./apps/files_sharing/src/views/CollaborationView.vue ***!
  \************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CollaborationView.vue?vue&type=template&id=20578814& */ "./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814&");
/* harmony import */ var _CollaborationView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CollaborationView.vue?vue&type=script&lang=js& */ "./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _CollaborationView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__["render"],
  _CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/views/CollaborationView.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CollaborationView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib??ref--3!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollaborationView.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_ref_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CollaborationView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814&":
/*!*******************************************************************************************!*\
  !*** ./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814& ***!
  \*******************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollaborationView.vue?vue&type=template&id=20578814& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollaborationView_vue_vue_type_template_id_20578814___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?!./node_modules/vue-loader/lib/index.js?!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib??ref--3!./node_modules/vue-loader/lib??vue-loader-options!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! nextcloud-vue-collections */ "./node_modules/nextcloud-vue-collections/dist/nextcloud-vue-collections.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'CollaborationView',
  computed: {
    fileId: function fileId() {
      if (this.$root.model && this.$root.model.id) {
        return '' + this.$root.model.id;
      }

      return null;
    },
    filename: function filename() {
      if (this.$root.model && this.$root.model.name) {
        return '' + this.$root.model.name;
      }

      return '';
    }
  },
  components: {
    CollectionList: nextcloud_vue_collections__WEBPACK_IMPORTED_MODULE_0__["CollectionList"]
  }
});

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814&":
/*!*************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./apps/files_sharing/src/views/CollaborationView.vue?vue&type=template&id=20578814& ***!
  \*************************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _vm.fileId
    ? _c("collection-list", {
        attrs: { type: "file", id: _vm.fileId, name: _vm.filename }
      })
    : _vm._e()
}
var staticRenderFns = []
render._withStripped = true



/***/ })

}]);
//# sourceMappingURL=files_sharing.1.js.map