"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_settings_src_components_AppStoreDiscover_CarouselType_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts":
/*!**************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts ***!
  \**************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _common_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./common.ts */ "./apps/settings/src/components/AppStoreDiscover/common.ts");
/* harmony import */ var _composables_useGetLocalizedValue_ts__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../composables/useGetLocalizedValue.ts */ "./apps/settings/src/composables/useGetLocalizedValue.ts");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcIconSvgWrapper.js */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _PostType_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./PostType.vue */ "./apps/settings/src/components/AppStoreDiscover/PostType.vue");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_6__.defineComponent)({
  name: 'CarouselType',
  components: {
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcIconSvgWrapper: _nextcloud_vue_dist_Components_NcIconSvgWrapper_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    PostType: _PostType_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    ..._common_ts__WEBPACK_IMPORTED_MODULE_1__.commonAppDiscoverProps,
    /**
     * The content of the carousel
     */
    content: {
      type: Array,
      required: true
    }
  },
  setup(props) {
    const translatedHeadline = (0,_composables_useGetLocalizedValue_ts__WEBPACK_IMPORTED_MODULE_2__.useLocalizedValue)((0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => props.headline));
    const currentIndex = (0,vue__WEBPACK_IMPORTED_MODULE_6__.ref)(Math.min(1, props.content.length - 1));
    const shownElement = (0,vue__WEBPACK_IMPORTED_MODULE_6__.ref)(props.content[currentIndex.value]);
    const hasNext = (0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => currentIndex.value < props.content.length - 1);
    const hasPrevious = (0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => currentIndex.value > 0);
    const internalId = (0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => {
      var _props$id;
      return (_props$id = props.id) !== null && _props$id !== void 0 ? _props$id : (Math.random() + 1).toString(36).substring(7);
    });
    const headingId = (0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => "".concat(internalId.value, "-h"));
    const transitionName = (0,vue__WEBPACK_IMPORTED_MODULE_6__.ref)('slide-in');
    (0,vue__WEBPACK_IMPORTED_MODULE_6__.watch)(() => currentIndex.value, (o, n) => {
      if (o < n) {
        transitionName.value = 'slide-in';
      } else {
        transitionName.value = 'slide-out';
      }
      // Wait next tick
      (0,vue__WEBPACK_IMPORTED_MODULE_6__.nextTick)(() => {
        shownElement.value = props.content[currentIndex.value];
      });
    });
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate,
      internalId,
      headingId,
      hasNext,
      hasPrevious,
      currentIndex,
      shownElement,
      transitionName,
      translatedHeadline,
      mdiChevronLeft: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiChevronLeft,
      mdiChevronRight: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiChevronRight,
      mdiCircleOutline: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiCircleOutline,
      mdiCircleSlice8: _mdi_js__WEBPACK_IMPORTED_MODULE_7__.mdiCircleSlice8
    };
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm$shownElement$id;
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("section", {
    attrs: {
      "aria-roledescription": _vm.t("settings", "Carousel"),
      "aria-labelledby": _vm.headingId ? "".concat(_vm.headingId) : undefined
    }
  }, [_vm.headline ? _c("h3", {
    attrs: {
      id: _vm.headingId
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.translatedHeadline) + "\n\t")]) : _vm._e(), _vm._v(" "), _c("div", {
    staticClass: "app-discover-carousel__wrapper"
  }, [_c("div", {
    staticClass: "app-discover-carousel__button-wrapper"
  }, [_c("NcButton", {
    staticClass: "app-discover-carousel__button app-discover-carousel__button--previous",
    attrs: {
      type: "tertiary-no-background",
      "aria-label": _vm.t("settings", "Previous slide"),
      disabled: !_vm.hasPrevious
    },
    on: {
      click: function ($event) {
        _vm.currentIndex -= 1;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiChevronLeft
          }
        })];
      },
      proxy: true
    }])
  })], 1), _vm._v(" "), _c("Transition", {
    attrs: {
      name: _vm.transitionName,
      mode: "out-in"
    }
  }, [_c("PostType", _vm._b({
    key: (_vm$shownElement$id = _vm.shownElement.id) !== null && _vm$shownElement$id !== void 0 ? _vm$shownElement$id : _vm.currentIndex,
    attrs: {
      "aria-labelledby": "".concat(_vm.internalId, "-tab-").concat(_vm.currentIndex),
      "dom-id": "".concat(_vm.internalId, "-tabpanel-").concat(_vm.currentIndex),
      inline: "",
      role: "tabpanel"
    }
  }, "PostType", _vm.shownElement, false))], 1), _vm._v(" "), _c("div", {
    staticClass: "app-discover-carousel__button-wrapper"
  }, [_c("NcButton", {
    staticClass: "app-discover-carousel__button app-discover-carousel__button--next",
    attrs: {
      type: "tertiary-no-background",
      "aria-label": _vm.t("settings", "Next slide"),
      disabled: !_vm.hasNext
    },
    on: {
      click: function ($event) {
        _vm.currentIndex += 1;
      }
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("NcIconSvgWrapper", {
          attrs: {
            path: _vm.mdiChevronRight
          }
        })];
      },
      proxy: true
    }])
  })], 1)], 1), _vm._v(" "), _c("div", {
    staticClass: "app-discover-carousel__tabs",
    attrs: {
      role: "tablist",
      "aria-label": _vm.t("settings", "Choose slide to display")
    }
  }, _vm._l(_vm.content.length, function (index) {
    return _c("NcButton", {
      key: index,
      attrs: {
        id: "".concat(_vm.internalId, "-tab-").concat(index),
        "aria-label": _vm.t("settings", "{index} of {total}", {
          index,
          total: _vm.content.length
        }),
        "aria-controls": "".concat(_vm.internalId, "-tabpanel-").concat(index),
        "aria-selected": "".concat(_vm.currentIndex === index - 1),
        role: "tab",
        type: "tertiary-no-background"
      },
      on: {
        click: function ($event) {
          _vm.currentIndex = index - 1;
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c("NcIconSvgWrapper", {
            attrs: {
              path: _vm.currentIndex === index - 1 ? _vm.mdiCircleSlice8 : _vm.mdiCircleOutline
            }
          })];
        },
        proxy: true
      }], null, true)
    });
  }), 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
___CSS_LOADER_EXPORT___.push([module.id, `h3[data-v-3065566f] {
  font-size: 24px;
  font-weight: 600;
  margin-block: 0 1em;
}
.app-discover-carousel__wrapper[data-v-3065566f] {
  display: flex;
}
.app-discover-carousel__button[data-v-3065566f] {
  color: var(--color-text-maxcontrast);
  position: absolute;
  top: calc(50% - 22px);
}
.app-discover-carousel__button-wrapper[data-v-3065566f] {
  position: relative;
}
.app-discover-carousel__button--next[data-v-3065566f] {
  right: -54px;
}
.app-discover-carousel__button--previous[data-v-3065566f] {
  left: -54px;
}
.app-discover-carousel__tabs[data-v-3065566f] {
  display: flex;
  flex-direction: row;
  justify-content: center;
}
.app-discover-carousel__tabs > *[data-v-3065566f] {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
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
.slide-in-enter-active,
.slide-in-leave-active,
.slide-out-enter-active,
.slide-out-leave-active {
  transition: all .4s ease-out;
}
.slide-in-leave-to,
.slide-out-enter {
  opacity: 0;
  transform: translateX(50%);
}
.slide-in-enter,
.slide-out-leave-to {
  opacity: 0;
  transform: translateX(-50%);
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue":
/*!************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/CarouselType.vue ***!
  \************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CarouselType.vue?vue&type=template&id=3065566f&scoped=true */ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true");
/* harmony import */ var _CarouselType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CarouselType.vue?vue&type=script&lang=ts */ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts");
/* harmony import */ var _CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss */ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss");
/* harmony import */ var _CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css */ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;



/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _CarouselType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3065566f",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AppStoreDiscover/CarouselType.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true":
/*!******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true ***!
  \******************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_template_id_3065566f_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=template&id=3065566f&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=template&id=3065566f&scoped=true");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss":
/*!*********************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss ***!
  \*********************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_0_id_3065566f_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=0&id=3065566f&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css":
/*!********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css ***!
  \********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CarouselType_vue_vue_type_style_index_1_id_3065566f_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AppStoreDiscover/CarouselType.vue?vue&type=style&index=1&id=3065566f&lang=css");


/***/ })

}]);
//# sourceMappingURL=apps_settings_src_components_AppStoreDiscover_CarouselType_vue-apps_settings_src_components_AppStoreDiscover_CarouselType_vue.js.map?v=60364cb47ffeb0065c91