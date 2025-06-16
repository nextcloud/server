"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e"],{

/***/ "./node_modules/@nextcloud/dialogs/dist/chunks/FilePicker-CsU6FfAP.mjs":
/*!*****************************************************************************!*\
  !*** ./node_modules/@nextcloud/dialogs/dist/chunks/FilePicker-CsU6FfAP.mjs ***!
  \*****************************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ FilePicker)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_24__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./_plugin-vue2_normalizer-jrlE7CJU.mjs */ "./node_modules/@nextcloud/dialogs/dist/chunks/_plugin-vue2_normalizer-jrlE7CJU.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/components/NcCheckboxRadioSwitch */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/sharing/public */ "./node_modules/@nextcloud/sharing/dist/public.mjs");
/* harmony import */ var _vueuse_core__WEBPACK_IMPORTED_MODULE_25__ = __webpack_require__(/*! @vueuse/core */ "./node_modules/@vueuse/shared/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/components/NcDateTime */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTime.mjs");
/* harmony import */ var _mdi_js__WEBPACK_IMPORTED_MODULE_27__ = __webpack_require__(/*! @mdi/js */ "./node_modules/@mdi/js/mdi.js");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var p_queue__WEBPACK_IMPORTED_MODULE_26__ = __webpack_require__(/*! p-queue */ "./node_modules/p-queue/dist/index.js");
/* harmony import */ var _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/components/NcIconSvgWrapper */ "./node_modules/@nextcloud/vue/dist/Components/NcIconSvgWrapper.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/components/NcActions */ "./node_modules/@nextcloud/vue/dist/Components/NcActions.mjs");
/* harmony import */ var _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! @nextcloud/vue/components/NcActionInput */ "./node_modules/@nextcloud/vue/dist/Components/NcActionInput.mjs");
/* harmony import */ var _nextcloud_vue_components_NcBreadcrumbs__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! @nextcloud/vue/components/NcBreadcrumbs */ "./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumbs.mjs");
/* harmony import */ var _nextcloud_vue_components_NcBreadcrumb__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! @nextcloud/vue/components/NcBreadcrumb */ "./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumb.mjs");
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! @nextcloud/vue/components/NcSelect */ "./node_modules/@nextcloud/vue/dist/Components/NcSelect.mjs");
/* harmony import */ var _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! @nextcloud/vue/components/NcTextField */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var vue_frag__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! vue-frag */ "./node_modules/vue-frag/dist/frag.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_20__ = __webpack_require__(/*! @nextcloud/vue/components/NcDialog */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_21__ = __webpack_require__(/*! @nextcloud/vue/components/NcEmptyContent */ "./node_modules/@nextcloud/vue/dist/Components/NcEmptyContent.mjs");
/* harmony import */ var cancelable_promise__WEBPACK_IMPORTED_MODULE_22__ = __webpack_require__(/*! cancelable-promise */ "./node_modules/cancelable-promise/umd/CancelablePromise.js");
/* harmony import */ var path__WEBPACK_IMPORTED_MODULE_23__ = __webpack_require__(/*! path */ "./node_modules/path/path.js");




























const _sfc_main$e = {
  name: "FileIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$e = function render() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon file-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M13,9V3.5L18.5,9M6,2C4.89,2 4,2.89 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2H6Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$e = [];
var __component__$e = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$e,
  _sfc_render$e,
  _sfc_staticRenderFns$e,
  false,
  null,
  null
);
const IconFile = __component__$e.exports;
const useFilesSettings = () => {
  const filesUserState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)("files", "config", null);
  const showHiddenFiles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(filesUserState?.show_hidden ?? true);
  const sortFavoritesFirst = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(filesUserState?.sort_favorites_first ?? true);
  const cropImagePreviews = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(filesUserState?.crop_image_previews ?? true);
  (0,vue__WEBPACK_IMPORTED_MODULE_24__.onMounted)(async () => {
    if (!(0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_6__.isPublicShare)()) {
      try {
        const { data } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)("/apps/files/api/v1/configs"));
        showHiddenFiles.value = data?.data?.show_hidden ?? false;
        sortFavoritesFirst.value = data?.data?.sort_favorites_first ?? true;
        cropImagePreviews.value = data?.data?.crop_image_previews ?? true;
      } catch (error) {
        console.error("Could not load files settings", error);
        (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.l)((0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Could not load files settings"));
      }
    } else {
      console.debug("Skip loading files settings - currently on public share");
    }
  });
  return {
    showHiddenFiles,
    sortFavoritesFirst,
    cropImagePreviews
  };
};
const useFilesViews = (currentView) => {
  const convertOrder = (order2) => order2 === "asc" ? "ascending" : order2 === "desc" ? "descending" : "none";
  const filesViewsState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_4__.loadState)("files", "viewConfigs", null);
  const filesViewConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)({
    sortBy: filesViewsState?.files?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.files?.sorting_direction ?? "asc")
  });
  const recentViewConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)({
    sortBy: filesViewsState?.recent?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.recent?.sorting_direction ?? "asc")
  });
  const favoritesViewConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)({
    sortBy: filesViewsState?.favorites?.sorting_mode ?? "basename",
    order: convertOrder(filesViewsState?.favorites?.sorting_direction ?? "asc")
  });
  (0,vue__WEBPACK_IMPORTED_MODULE_24__.onMounted)(async () => {
    if (!(0,_nextcloud_sharing_public__WEBPACK_IMPORTED_MODULE_6__.isPublicShare)()) {
      try {
        const { data } = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_7__["default"].get((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)("/apps/files/api/v1/views"));
        filesViewConfig.value = {
          sortBy: data?.data?.files?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.files?.sorting_direction)
        };
        favoritesViewConfig.value = {
          sortBy: data?.data?.favorites?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.favorites?.sorting_direction)
        };
        recentViewConfig.value = {
          sortBy: data?.data?.recent?.sorting_mode ?? "basename",
          order: convertOrder(data?.data?.recent?.sorting_direction)
        };
      } catch (error) {
        console.error("Could not load files views", error);
        (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.l)((0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Could not load files views"));
      }
    } else {
      console.debug("Skip loading files views - currently on public share");
    }
  });
  const currentConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_25__.toValue)(currentView || "files") === "files" ? filesViewConfig.value : (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_25__.toValue)(currentView) === "recent" ? recentViewConfig.value : favoritesViewConfig.value);
  const sortBy = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => currentConfig.value.sortBy);
  const order = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => currentConfig.value.order);
  return {
    filesViewConfig,
    favoritesViewConfig,
    recentViewConfig,
    currentConfig,
    sortBy,
    order
  };
};
const _sfc_main$d = {
  name: "MenuUpIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$d = function render2() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon menu-up-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M7,15L12,10L17,15H7Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$d = [];
var __component__$d = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$d,
  _sfc_render$d,
  _sfc_staticRenderFns$d,
  false,
  null,
  null
);
const IconSortAscending = __component__$d.exports;
const _sfc_main$c = {
  name: "MenuDownIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$c = function render3() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon menu-down-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M7,10L12,15L17,10H7Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$c = [];
var __component__$c = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$c,
  _sfc_render$c,
  _sfc_staticRenderFns$c,
  false,
  null,
  null
);
const IconSortDescending = __component__$c.exports;
const fileListIconStylesModule = {
  "file-picker__file-icon": "_file-picker__file-icon_3v9zx_9",
  "file-picker__file-icon--primary": "_file-picker__file-icon--primary_3v9zx_21",
  "file-picker__file-icon-overlay": "_file-picker__file-icon-overlay_3v9zx_25"
};
const _sfc_main$b = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  __name: "LoadingTableRow",
  props: {
    showCheckbox: { type: Boolean }
  },
  setup(__props) {
    return { __sfc: true, fileListIconStyles: fileListIconStylesModule };
  }
});
var _sfc_render$b = function render4() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c("tr", { staticClass: "file-picker__row loading-row", attrs: { "aria-hidden": "true" } }, [_vm.showCheckbox ? _c("td", { staticClass: "row-checkbox" }, [_c("span")]) : _vm._e(), _c("td", { staticClass: "row-name" }, [_c("div", { staticClass: "row-wrapper" }, [_c("span", { class: _setup.fileListIconStyles["file-picker__file-icon"] }), _c("span")])]), _vm._m(0), _vm._m(1)]);
};
var _sfc_staticRenderFns$b = [function() {
  var _vm = this, _c = _vm._self._c;
  _vm._self._setupProxy;
  return _c("td", { staticClass: "row-size" }, [_c("span")]);
}, function() {
  var _vm = this, _c = _vm._self._c;
  _vm._self._setupProxy;
  return _c("td", { staticClass: "row-modified" }, [_c("span")]);
}];
var __component__$b = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$b,
  _sfc_render$b,
  _sfc_staticRenderFns$b,
  false,
  null,
  "15187afc"
);
const LoadingTableRow = __component__$b.exports;
const queue = new p_queue__WEBPACK_IMPORTED_MODULE_26__["default"]({ concurrency: 5 });
function preloadImage(url) {
  const { resolve, promise } = Promise.withResolvers();
  queue.add(() => {
    const image = new Image();
    image.onerror = () => resolve(false);
    image.onload = () => resolve(true);
    image.src = url;
    return promise;
  });
  return promise;
}
function getPreviewURL(node, options = {}) {
  options = { size: 32, cropPreview: false, mimeFallback: true, ...options };
  try {
    const previewUrl = node.attributes?.previewUrl || (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_5__.generateUrl)("/core/preview?fileId={fileid}", {
      fileid: node.fileid
    });
    let url;
    try {
      url = new URL(previewUrl);
    } catch (e) {
      url = new URL(previewUrl, window.location.origin);
    }
    url.searchParams.set("x", `${options.size}`);
    url.searchParams.set("y", `${options.size}`);
    url.searchParams.set("mimeFallback", `${options.mimeFallback}`);
    url.searchParams.set("a", options.cropPreview === true ? "0" : "1");
    url.searchParams.set("c", `${node.attributes.etag}`);
    return url;
  } catch (e) {
    return null;
  }
}
const usePreviewURL = (node, options) => {
  const previewURL = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(null);
  const previewLoaded = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(false);
  (0,vue__WEBPACK_IMPORTED_MODULE_24__.watchEffect)(() => {
    previewLoaded.value = false;
    previewURL.value = getPreviewURL((0,_vueuse_core__WEBPACK_IMPORTED_MODULE_25__.toValue)(node), (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_25__.toValue)(options || {}));
    if (previewURL.value && (0,_vueuse_core__WEBPACK_IMPORTED_MODULE_25__.toValue)(node).type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File) {
      preloadImage(previewURL.value.href).then((success) => {
        previewLoaded.value = success;
      });
    }
  });
  return {
    previewURL,
    previewLoaded
  };
};
const _sfc_main$a = {
  name: "FolderIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$a = function render5() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon folder-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M10,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V8C22,6.89 21.1,6 20,6H12L10,4Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$a = [];
var __component__$a = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$a,
  _sfc_render$a,
  _sfc_staticRenderFns$a,
  false,
  null,
  null
);
const IconFolder = __component__$a.exports;
const __default__$1 = {
  name: "FilePreview"
};
const _sfc_main$9 = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  ...__default__$1,
  props: {
    node: null,
    cropImagePreviews: { type: Boolean }
  },
  setup(__props) {
    const props = __props;
    const fileListIconStyles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(fileListIconStylesModule);
    const {
      previewURL,
      previewLoaded
    } = usePreviewURL((0,vue__WEBPACK_IMPORTED_MODULE_24__.toRef)(props, "node"), (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => ({ cropPreview: props.cropImagePreviews })));
    const isFile = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.File);
    const folderDecorationIcon = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => {
      if (props.node.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder) {
        return null;
      }
      if (props.node.attributes?.["is-encrypted"] === 1) {
        return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiLock;
      }
      if (props.node.attributes?.["is-tag"]) {
        return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiTag;
      }
      const shareTypes = Object.values(props.node.attributes?.["share-types"] || {}).flat();
      if (shareTypes.some((type) => type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_9__.ShareType.Link || type === _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_9__.ShareType.Email)) {
        return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiLink;
      }
      if (shareTypes.length > 0) {
        return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiAccountPlus;
      }
      switch (props.node.attributes?.["mount-type"]) {
        case "external":
        case "external-session":
          return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiNetwork;
        case "group":
          return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiGroup;
        case "shared":
          return _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiAccountPlus;
      }
      return null;
    });
    return { __sfc: true, fileListIconStyles, props, previewURL, previewLoaded, isFile, folderDecorationIcon, NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_10__["default"], IconFile, IconFolder };
  }
});
var _sfc_render$9 = function render6() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c("div", { class: _setup.fileListIconStyles["file-picker__file-icon"], style: _setup.previewLoaded ? { backgroundImage: `url(${_setup.previewURL})` } : void 0 }, [!_setup.previewLoaded ? [_setup.isFile ? _c(_setup.IconFile, { attrs: { "size": 32 } }) : [_setup.folderDecorationIcon ? _c(_setup.NcIconSvgWrapper, { class: _setup.fileListIconStyles["file-picker__file-icon-overlay"], attrs: { "inline": "", "path": _setup.folderDecorationIcon, "size": 16 } }) : _vm._e(), _c(_setup.IconFolder, { class: _setup.fileListIconStyles["file-picker__file-icon--primary"], attrs: { "size": 32 } })]] : _vm._e()], 2);
};
var _sfc_staticRenderFns$9 = [];
var __component__$9 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$9,
  _sfc_render$9,
  _sfc_staticRenderFns$9,
  false,
  null,
  null
);
const FilePreview = __component__$9.exports;
const _sfc_main$8 = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  __name: "FileListRow",
  props: {
    allowPickDirectory: { type: Boolean },
    selected: { type: Boolean },
    showCheckbox: { type: Boolean },
    canPick: { type: Boolean },
    node: null,
    cropImagePreviews: { type: Boolean }
  },
  emits: ["update:selected", "enter-directory"],
  setup(__props, { emit: emit2 }) {
    const props = __props;
    const displayName = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.node.attributes?.displayName || props.node.basename.slice(0, props.node.extension ? -props.node.extension.length : void 0));
    const fileExtension = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.node.extension);
    const isDirectory = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.node.type === _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder);
    const isPickable = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.canPick && (props.allowPickDirectory || !isDirectory.value));
    function toggleSelected() {
      emit2("update:selected", !props.selected);
    }
    function handleClick() {
      if (isDirectory.value) {
        emit2("enter-directory", props.node);
      } else {
        toggleSelected();
      }
    }
    function handleKeyDown(event) {
      if (event.key === "Enter") {
        handleClick();
      }
    }
    return { __sfc: true, props, emit: emit2, displayName, fileExtension, isDirectory, isPickable, toggleSelected, handleClick, handleKeyDown, formatFileSize: _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.formatFileSize, NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_3__["default"], NcDateTime: _nextcloud_vue_components_NcDateTime__WEBPACK_IMPORTED_MODULE_8__["default"], t: _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t, FilePreview };
  }
});
var _sfc_render$8 = function render7() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c("tr", _vm._g({ class: ["file-picker__row", {
    "file-picker__row--selected": _vm.selected && !_vm.showCheckbox
  }], attrs: { "tabindex": _vm.showCheckbox && !_setup.isDirectory ? void 0 : 0, "aria-selected": !_setup.isPickable ? void 0 : _vm.selected, "data-filename": _vm.node.basename, "data-testid": "file-list-row" } }, {
    click: _setup.handleClick,
    /* same as tabindex -> if we hide the checkbox or this is a directory we need keyboard access to enter the directory or select the node */
    ...!_vm.showCheckbox || _setup.isDirectory ? { keydown: _setup.handleKeyDown } : {}
  }), [_vm.showCheckbox ? _c("td", { staticClass: "row-checkbox", on: { "click": function($event) {
    $event.stopPropagation();
    return (() => {
    }).apply(null, arguments);
  } } }, [_c(_setup.NcCheckboxRadioSwitch, { attrs: { "aria-label": _setup.t("Select the row for {nodename}", { nodename: _setup.displayName }), "disabled": !_setup.isPickable, "data-testid": "row-checkbox", "model-value": _vm.selected }, on: { "update:model-value": _setup.toggleSelected } })], 1) : _vm._e(), _c("td", { staticClass: "row-name" }, [_c("div", { staticClass: "file-picker__name-container", attrs: { "data-testid": "row-name" } }, [_c(_setup.FilePreview, { attrs: { "node": _vm.node, "crop-image-previews": _vm.cropImagePreviews } }), _c("div", { staticClass: "file-picker__file-name", attrs: { "title": _setup.displayName }, domProps: { "textContent": _vm._s(_setup.displayName) } }), _c("div", { staticClass: "file-picker__file-extension", domProps: { "textContent": _vm._s(_setup.fileExtension) } })], 1)]), _c("td", { staticClass: "row-size" }, [_vm._v(" " + _vm._s(_setup.formatFileSize(_vm.node.size || 0)) + " ")]), _c("td", { staticClass: "row-modified" }, [_c(_setup.NcDateTime, { attrs: { "timestamp": _vm.node.mtime, "ignore-seconds": true } })], 1)]);
};
var _sfc_staticRenderFns$8 = [];
var __component__$8 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$8,
  _sfc_render$8,
  _sfc_staticRenderFns$8,
  false,
  null,
  "4892c2a0"
);
const FileListRow = __component__$8.exports;
const _sfc_main$7 = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  __name: "FileList",
  props: {
    currentView: null,
    multiselect: { type: Boolean },
    allowPickDirectory: { type: Boolean },
    loading: { type: Boolean },
    files: null,
    selectedFiles: null,
    path: null
  },
  emits: ["update:path", "update:selectedFiles"],
  setup(__props, { emit: emit2 }) {
    const props = __props;
    const customSortingConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)();
    const { currentConfig: filesAppSorting } = useFilesViews(props.currentView);
    const sortingConfig = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => customSortingConfig.value ?? filesAppSorting.value);
    const sortByName = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => sortingConfig.value.sortBy === "basename" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    const sortBySize = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => sortingConfig.value.sortBy === "size" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    const sortByModified = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => sortingConfig.value.sortBy === "mtime" ? sortingConfig.value.order === "none" ? void 0 : sortingConfig.value.order : void 0);
    const toggleSorting = (sortBy) => {
      if (sortingConfig.value.sortBy === sortBy) {
        if (sortingConfig.value.order === "ascending") {
          customSortingConfig.value = { sortBy: sortingConfig.value.sortBy, order: "descending" };
        } else {
          customSortingConfig.value = { sortBy: sortingConfig.value.sortBy, order: "ascending" };
        }
      } else {
        customSortingConfig.value = { sortBy, order: "ascending" };
      }
    };
    const { sortFavoritesFirst, cropImagePreviews } = useFilesSettings();
    const sortedFiles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => {
      return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.sortNodes)(props.files, {
        sortFoldersFirst: true,
        sortFavoritesFirst: sortFavoritesFirst.value,
        sortingOrder: sortingConfig.value.order === "descending" ? "desc" : "asc",
        sortingMode: sortingConfig.value.sortBy
      });
    });
    const selectableFiles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => props.files.filter((file) => props.allowPickDirectory || file.type !== _nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.FileType.Folder));
    const allSelected = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => !props.loading && props.selectedFiles.length > 0 && props.selectedFiles.length >= selectableFiles.value.length);
    function onSelectAll() {
      if (props.selectedFiles.length < selectableFiles.value.length) {
        emit2("update:selectedFiles", selectableFiles.value);
      } else {
        emit2("update:selectedFiles", []);
      }
    }
    function onNodeSelected(file) {
      if (props.selectedFiles.includes(file)) {
        emit2("update:selectedFiles", props.selectedFiles.filter((f) => f.path !== file.path));
      } else {
        if (props.multiselect) {
          emit2("update:selectedFiles", [...props.selectedFiles, file]);
        } else {
          emit2("update:selectedFiles", [file]);
        }
      }
    }
    function onChangeDirectory(dir) {
      emit2("update:path", dir.path);
    }
    const skeletonNumber = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(4);
    const fileContainer = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)();
    {
      const resize = () => (0,vue__WEBPACK_IMPORTED_MODULE_24__.nextTick)(() => {
        const nodes = fileContainer.value?.parentElement?.children || [];
        let height = fileContainer.value?.parentElement?.clientHeight || 450;
        for (let index = 0; index < nodes.length; index++) {
          if (!fileContainer.value?.isSameNode(nodes[index])) {
            height -= nodes[index].clientHeight;
          }
        }
        skeletonNumber.value = Math.max(1, Math.floor((height - 50) / 50));
      });
      (0,vue__WEBPACK_IMPORTED_MODULE_24__.onMounted)(() => {
        window.addEventListener("resize", resize);
        resize();
      });
      (0,vue__WEBPACK_IMPORTED_MODULE_24__.onUnmounted)(() => {
        window.removeEventListener("resize", resize);
      });
    }
    return { __sfc: true, props, emit: emit2, customSortingConfig, filesAppSorting, sortingConfig, sortByName, sortBySize, sortByModified, toggleSorting, sortFavoritesFirst, cropImagePreviews, sortedFiles, selectableFiles, allSelected, onSelectAll, onNodeSelected, onChangeDirectory, skeletonNumber, fileContainer, NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__["default"], NcCheckboxRadioSwitch: _nextcloud_vue_components_NcCheckboxRadioSwitch__WEBPACK_IMPORTED_MODULE_3__["default"], t: _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t, IconSortAscending, IconSortDescending, LoadingTableRow, FileListRow };
  }
});
var _sfc_render$7 = function render8() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c("div", { ref: "fileContainer", staticClass: "file-picker__files" }, [_c("table", [_c("thead", [_c("tr", [_vm.multiselect ? _c("th", { staticClass: "row-checkbox" }, [_c("span", { staticClass: "hidden-visually" }, [_vm._v(" " + _vm._s(_setup.t("Select entry")) + " ")]), _vm.multiselect ? _c(_setup.NcCheckboxRadioSwitch, { attrs: { "aria-label": _setup.t("Select all entries"), "data-testid": "select-all-checkbox", "model-value": _setup.allSelected }, on: { "update:model-value": _setup.onSelectAll } }) : _vm._e()], 1) : _vm._e(), _c("th", { staticClass: "row-name", attrs: { "aria-sort": _setup.sortByName } }, [_c("div", { staticClass: "header-wrapper" }, [_c("span", { staticClass: "file-picker__header-preview" }), _c(_setup.NcButton, { attrs: { "wide": true, "type": "tertiary", "data-test": "file-picker_sort-name" }, on: { "click": function($event) {
    return _setup.toggleSorting("basename");
  } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_setup.sortByName === "ascending" ? _c(_setup.IconSortAscending, { attrs: { "size": 20 } }) : _setup.sortByName === "descending" ? _c(_setup.IconSortDescending, { attrs: { "size": 20 } }) : _c("span", { staticStyle: { "width": "44px" } })];
  }, proxy: true }]) }, [_vm._v(" " + _vm._s(_setup.t("Name")) + " ")])], 1)]), _c("th", { staticClass: "row-size", attrs: { "aria-sort": _setup.sortBySize } }, [_c(_setup.NcButton, { attrs: { "wide": true, "type": "tertiary" }, on: { "click": function($event) {
    return _setup.toggleSorting("size");
  } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_setup.sortBySize === "ascending" ? _c(_setup.IconSortAscending, { attrs: { "size": 20 } }) : _setup.sortBySize === "descending" ? _c(_setup.IconSortDescending, { attrs: { "size": 20 } }) : _c("span", { staticStyle: { "width": "44px" } })];
  }, proxy: true }]) }, [_vm._v(" " + _vm._s(_setup.t("Size")) + " ")])], 1), _c("th", { staticClass: "row-modified", attrs: { "aria-sort": _setup.sortByModified } }, [_c(_setup.NcButton, { attrs: { "wide": true, "type": "tertiary" }, on: { "click": function($event) {
    return _setup.toggleSorting("mtime");
  } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_setup.sortByModified === "ascending" ? _c(_setup.IconSortAscending, { attrs: { "size": 20 } }) : _setup.sortByModified === "descending" ? _c(_setup.IconSortDescending, { attrs: { "size": 20 } }) : _c("span", { staticStyle: { "width": "44px" } })];
  }, proxy: true }]) }, [_vm._v(" " + _vm._s(_setup.t("Modified")) + " ")])], 1)])]), _c("tbody", [_vm.loading ? _vm._l(_setup.skeletonNumber, function(index) {
    return _c(_setup.LoadingTableRow, { key: index, attrs: { "show-checkbox": _vm.multiselect } });
  }) : _vm._l(_setup.sortedFiles, function(file) {
    return _c(_setup.FileListRow, { key: file.fileid || file.path, attrs: { "allow-pick-directory": _vm.allowPickDirectory, "show-checkbox": _vm.multiselect, "can-pick": _vm.multiselect || _vm.selectedFiles.length === 0 || _vm.selectedFiles.includes(file), "selected": _vm.selectedFiles.includes(file), "node": file, "crop-image-previews": _setup.cropImagePreviews }, on: { "update:selected": function($event) {
      return _setup.onNodeSelected(file);
    }, "enter-directory": _setup.onChangeDirectory } });
  })], 2)])]);
};
var _sfc_staticRenderFns$7 = [];
var __component__$7 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$7,
  _sfc_render$7,
  _sfc_staticRenderFns$7,
  false,
  null,
  "4f5d2a56"
);
const FileList = __component__$7.exports;
const _sfc_main$6 = {
  name: "HomeIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$6 = function render9() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon home-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M10,20V14H14V20H19V12H22L12,3L2,12H5V20H10Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$6 = [];
var __component__$6 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$6,
  _sfc_render$6,
  _sfc_staticRenderFns$6,
  false,
  null,
  null
);
const IconHome = __component__$6.exports;
const _sfc_main$5 = {
  name: "PlusIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$5 = function render10() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon plus-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$5 = [];
var __component__$5 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$5,
  _sfc_render$5,
  _sfc_staticRenderFns$5,
  false,
  null,
  null
);
const IconPlus = __component__$5.exports;
const _sfc_main$4 = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  __name: "FilePickerBreadcrumbs",
  props: {
    path: null,
    showMenu: { type: Boolean }
  },
  emits: ["update:path", "create-node"],
  setup(__props, { emit: emit2 }) {
    const props = __props;
    const actionsOpen = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(false);
    const newNodeName = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)("");
    const nameInput = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)();
    function validateInput() {
      const name = newNodeName.value.trim();
      const input = nameInput.value?.$el?.querySelector("input");
      let validity = "";
      if (name.length === 0) {
        validity = (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Folder name cannot be empty.");
      } else if (name.includes("/")) {
        validity = (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)('"/" is not allowed inside a folder name.');
      } else if (["..", "."].includes(name)) {
        validity = (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)('"{name}" is an invalid folder name.', { name });
      } else if (window.OC.config?.blacklist_files_regex && name.match(window.OC.config?.blacklist_files_regex)) {
        validity = (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)('"{name}" is not an allowed folder name', { name });
      }
      if (input) {
        input.setCustomValidity(validity);
      }
      return validity === "";
    }
    const onSubmit = function() {
      const name = newNodeName.value.trim();
      if (validateInput()) {
        actionsOpen.value = false;
        emit2("create-node", name);
        newNodeName.value = "";
      }
    };
    const pathElements = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(
      () => props.path.split("/").filter((v) => v !== "").map((v, i, elements) => ({
        name: v,
        path: "/" + elements.slice(0, i + 1).join("/")
      }))
    );
    return { __sfc: true, props, emit: emit2, actionsOpen, newNodeName, nameInput, validateInput, onSubmit, pathElements, IconFolder, IconHome, IconPlus, NcActions: _nextcloud_vue_components_NcActions__WEBPACK_IMPORTED_MODULE_11__["default"], NcActionInput: _nextcloud_vue_components_NcActionInput__WEBPACK_IMPORTED_MODULE_12__["default"], NcBreadcrumbs: _nextcloud_vue_components_NcBreadcrumbs__WEBPACK_IMPORTED_MODULE_13__["default"], NcBreadcrumb: _nextcloud_vue_components_NcBreadcrumb__WEBPACK_IMPORTED_MODULE_14__["default"], t: _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t };
  }
});
var _sfc_render$4 = function render11() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c(_setup.NcBreadcrumbs, { staticClass: "file-picker__breadcrumbs", scopedSlots: _vm._u([{ key: "default", fn: function() {
    return [_c(_setup.NcBreadcrumb, { attrs: { "name": _setup.t("All files"), "title": _setup.t("Home") }, on: { "click": function($event) {
      return _setup.emit("update:path", "/");
    } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
      return [_c(_setup.IconHome, { attrs: { "size": 20 } })];
    }, proxy: true }]) }), _vm._l(_setup.pathElements, function(dir) {
      return _c(_setup.NcBreadcrumb, { key: dir.path, attrs: { "name": dir.name, "title": dir.path }, on: { "click": function($event) {
        return _setup.emit("update:path", dir.path);
      } } });
    })];
  }, proxy: true }, _vm.showMenu ? { key: "actions", fn: function() {
    return [_c(_setup.NcActions, { attrs: { "open": _setup.actionsOpen, "aria-label": _setup.t("Create directory"), "force-menu": true, "force-name": true, "menu-name": _setup.t("New"), "type": "secondary" }, on: { "update:open": function($event) {
      _setup.actionsOpen = $event;
    }, "close": function($event) {
      _setup.newNodeName = "";
    } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
      return [_c(_setup.IconPlus, { attrs: { "size": 20 } })];
    }, proxy: true }], null, false, 2971667417) }, [_c(_setup.NcActionInput, { ref: "nameInput", attrs: { "value": _setup.newNodeName, "label": _setup.t("New folder"), "placeholder": _setup.t("New folder name") }, on: { "update:value": function($event) {
      _setup.newNodeName = $event;
    }, "submit": _setup.onSubmit, "update:model-value": _setup.validateInput }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
      return [_c(_setup.IconFolder, { attrs: { "size": 20 } })];
    }, proxy: true }], null, false, 1614167509) })], 1)];
  }, proxy: true } : null], null, true) });
};
var _sfc_staticRenderFns$4 = [];
var __component__$4 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$4,
  _sfc_render$4,
  _sfc_staticRenderFns$4,
  false,
  null,
  "ec4d392b"
);
const FilePickerBreadcrumbs = __component__$4.exports;
const _sfc_main$3 = {
  name: "CloseIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$3 = function render12() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon close-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$3 = [];
var __component__$3 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$3,
  _sfc_render$3,
  _sfc_staticRenderFns$3,
  false,
  null,
  null
);
const IconClose = __component__$3.exports;
const _sfc_main$2 = {
  name: "MagnifyIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
var _sfc_render$2 = function render13() {
  var _vm = this, _c = _vm._self._c;
  return _c("span", _vm._b({ staticClass: "material-design-icon magnify-icon", attrs: { "aria-hidden": _vm.title ? null : "true", "aria-label": _vm.title, "role": "img" }, on: { "click": function($event) {
    return _vm.$emit("click", $event);
  } } }, "span", _vm.$attrs, false), [_c("svg", { staticClass: "material-design-icon__svg", attrs: { "fill": _vm.fillColor, "width": _vm.size, "height": _vm.size, "viewBox": "0 0 24 24" } }, [_c("path", { attrs: { "d": "M9.5,3A6.5,6.5 0 0,1 16,9.5C16,11.11 15.41,12.59 14.44,13.73L14.71,14H15.5L20.5,19L19,20.5L14,15.5V14.71L13.73,14.44C12.59,15.41 11.11,16 9.5,16A6.5,6.5 0 0,1 3,9.5A6.5,6.5 0 0,1 9.5,3M9.5,5C7,5 5,7 5,9.5C5,12 7,14 9.5,14C12,14 14,12 14,9.5C14,7 12,5 9.5,5Z" } }, [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()])])]);
};
var _sfc_staticRenderFns$2 = [];
var __component__$2 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$2,
  _sfc_render$2,
  _sfc_staticRenderFns$2,
  false,
  null,
  null
);
const IconMagnify = __component__$2.exports;
const useViews = (isAnonymous) => {
  const allViews = [
    {
      id: "files",
      label: (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("All files"),
      icon: _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiFolder
    },
    {
      id: "recent",
      label: (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Recent"),
      icon: _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiClock
    },
    {
      id: "favorites",
      label: (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Favorites"),
      icon: _mdi_js__WEBPACK_IMPORTED_MODULE_27__.mdiStar
    }
  ];
  const availableViews = isAnonymous.value ? allViews.filter(({ id }) => id === "files") : allViews;
  return {
    allViews,
    availableViews
  };
};
const _sfc_main$1 = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  __name: "FilePickerNavigation",
  props: {
    currentView: null,
    filterString: null,
    isCollapsed: { type: Boolean },
    disabledNavigation: { type: Boolean }
  },
  emits: ["update:currentView", "update:filterString"],
  setup(__props, { emit: emit2 }) {
    const props = __props;
    const { availableViews } = useViews((0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)((0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_15__.getCurrentUser)() === null));
    const currentViewObject = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => availableViews.filter((v) => v.id === props.currentView)[0] ?? availableViews[0]);
    const updateFilterValue = (value) => emit2("update:filterString", value);
    return { __sfc: true, props, emit: emit2, availableViews, currentViewObject, updateFilterValue, IconClose, IconMagnify, NcButton: _nextcloud_vue_components_NcButton__WEBPACK_IMPORTED_MODULE_2__["default"], NcIconSvgWrapper: _nextcloud_vue_components_NcIconSvgWrapper__WEBPACK_IMPORTED_MODULE_10__["default"], NcSelect: _nextcloud_vue_components_NcSelect__WEBPACK_IMPORTED_MODULE_16__["default"], NcTextField: _nextcloud_vue_components_NcTextField__WEBPACK_IMPORTED_MODULE_17__["default"], Fragment: vue_frag__WEBPACK_IMPORTED_MODULE_18__.Fragment, t: _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t };
  }
});
var _sfc_render$1 = function render14() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c(_setup.Fragment, [_c(_setup.NcTextField, { staticClass: "file-picker__filter-input", attrs: { "label": _setup.t("Filter file list"), "show-trailing-button": !!_vm.filterString, "model-value": _vm.filterString }, on: { "update:model-value": _setup.updateFilterValue, "trailing-button-click": function($event) {
    return _setup.updateFilterValue("");
  } }, scopedSlots: _vm._u([{ key: "trailing-button-icon", fn: function() {
    return [_c(_setup.IconClose, { attrs: { "size": 16 } })];
  }, proxy: true }]) }, [_c(_setup.IconMagnify, { attrs: { "size": 16 } })], 1), _setup.availableViews.length > 1 && !_vm.disabledNavigation ? [!_vm.isCollapsed ? _c("ul", { staticClass: "file-picker__side" }, _vm._l(_setup.availableViews, function(view) {
    return _c("li", { key: view.id }, [_c(_setup.NcButton, { attrs: { "type": _vm.currentView === view.id ? "primary" : "tertiary", "wide": true }, on: { "click": function($event) {
      return _vm.$emit("update:currentView", view.id);
    } }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
      return [_c(_setup.NcIconSvgWrapper, { attrs: { "path": view.icon, "size": 20 } })];
    }, proxy: true }], null, true) }, [_vm._v(" " + _vm._s(view.label) + " ")])], 1);
  }), 0) : _c(_setup.NcSelect, { attrs: { "aria-label": _setup.t("Current view selector"), "clearable": false, "searchable": false, "options": _setup.availableViews, "model-value": _setup.currentViewObject }, on: { "update:model-value": function($event) {
    return _setup.emit("update:currentView", $event.id);
  } } })] : _vm._e()], 2);
};
var _sfc_staticRenderFns$1 = [];
var __component__$1 = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main$1,
  _sfc_render$1,
  _sfc_staticRenderFns$1,
  false,
  null,
  "f5975252"
);
const FilePickerNavigation = __component__$1.exports;
function getRecentNodes(client) {
  const controller = new AbortController();
  const lastTwoWeek = Math.round(Date.now() / 1e3) - 60 * 60 * 24 * 14;
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_22__.CancelablePromise(async (resolve, reject, onCancel) => {
    onCancel(() => controller.abort());
    try {
      const { data } = await client.search("/", {
        signal: controller.signal,
        details: true,
        data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetRecentSearch)(lastTwoWeek)
      });
      const nodes = data.results.map((result) => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davResultToNode)(result));
      resolve(nodes);
    } catch (error) {
      reject(error);
    }
  });
}
function getNodes(client, directoryPath) {
  const controller = new AbortController();
  return new cancelable_promise__WEBPACK_IMPORTED_MODULE_22__.CancelablePromise(async (resolve, reject, onCancel) => {
    onCancel(() => controller.abort());
    try {
      const results = await client.getDirectoryContents((0,path__WEBPACK_IMPORTED_MODULE_23__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath, directoryPath), {
        signal: controller.signal,
        details: true,
        includeSelf: true,
        data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetDefaultPropfind)()
      });
      const nodes = results.data.map((result) => (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davResultToNode)(result));
      resolve({
        contents: nodes.filter(({ path }) => path !== directoryPath),
        folder: nodes.find(({ path }) => path === directoryPath)
      });
    } catch (error) {
      reject(error);
    }
  });
}
async function getFile(client, path) {
  const { data } = await client.stat((0,path__WEBPACK_IMPORTED_MODULE_23__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath, path), {
    details: true,
    data: (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetDefaultPropfind)()
  });
  return (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davResultToNode)(data);
}
const useDAVFiles = function(currentView, currentPath) {
  const client = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davGetClient)();
  const files = (0,vue__WEBPACK_IMPORTED_MODULE_24__.shallowRef)([]);
  const folder = (0,vue__WEBPACK_IMPORTED_MODULE_24__.shallowRef)(null);
  const isLoading = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(true);
  const promise = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(null);
  async function createDirectory(name) {
    const path = (0,path__WEBPACK_IMPORTED_MODULE_23__.join)(currentPath.value, name);
    await client.createDirectory((0,path__WEBPACK_IMPORTED_MODULE_23__.join)(_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.davRootPath, path));
    const directory = await getFile(client, path);
    files.value = [...files.value, directory];
    return directory;
  }
  async function loadDAVFiles() {
    if (promise.value) {
      promise.value.cancel();
    }
    isLoading.value = true;
    if (currentView.value === "favorites") {
      promise.value = (0,_nextcloud_files__WEBPACK_IMPORTED_MODULE_1__.getFavoriteNodes)(client, currentPath.value);
    } else if (currentView.value === "recent") {
      promise.value = getRecentNodes(client);
    } else {
      promise.value = getNodes(client, currentPath.value);
    }
    const content = await promise.value;
    if ("folder" in content) {
      folder.value = content.folder;
      files.value = content.contents;
    } else {
      folder.value = null;
      files.value = content;
    }
    promise.value = null;
    isLoading.value = false;
  }
  (0,vue__WEBPACK_IMPORTED_MODULE_24__.watch)([currentView, currentPath], () => loadDAVFiles());
  (0,vue__WEBPACK_IMPORTED_MODULE_24__.onMounted)(() => loadDAVFiles());
  return {
    isLoading,
    files,
    folder,
    loadFiles: loadDAVFiles,
    createDirectory
  };
};
const useMimeFilter = function(allowedMIMETypes) {
  const splittedTypes = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => allowedMIMETypes.value.map((filter) => filter.split("/")));
  const isSupportedMimeType = (mime) => {
    const mimeTypeArray = mime.split("/");
    return splittedTypes.value.some(
      ([type, subtype]) => (
        // check mime type matches or is wildcard
        (mimeTypeArray[0] === type || type === "*") && (mimeTypeArray[1] === subtype || subtype === "*")
      )
    );
  };
  return {
    isSupportedMimeType
  };
};
const __default__ = {
  name: "FilePicker"
};
const _sfc_main = /* @__PURE__ */ (0,vue__WEBPACK_IMPORTED_MODULE_24__.defineComponent)({
  ...__default__,
  props: {
    buttons: null,
    name: null,
    allowPickDirectory: { type: Boolean, default: false },
    disabledNavigation: { type: Boolean, default: false },
    container: { default: "body" },
    filterFn: { default: void 0 },
    mimetypeFilter: { default: () => [] },
    multiselect: { type: Boolean, default: true },
    path: { default: void 0 }
  },
  emits: ["close"],
  setup(__props, { emit: emit$1 }) {
    const props = __props;
    const isOpen = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(true);
    const dialogButtons = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => {
      const nodes = selectedFiles.value.length === 0 && props.allowPickDirectory && currentFolder.value ? [currentFolder.value] : selectedFiles.value;
      const buttons = typeof props.buttons === "function" ? props.buttons(nodes, currentPath.value, currentView.value) : props.buttons;
      return buttons.map((button) => ({
        ...button,
        disabled: button.disabled || isLoading.value,
        callback: () => {
          isHandlingCallback = true;
          handleButtonClick(button.callback, nodes);
        }
      }));
    });
    let isHandlingCallback = false;
    const handleButtonClick = async (callback, nodes) => {
      callback(nodes);
      emit$1("close", nodes);
      isHandlingCallback = false;
    };
    const currentView = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)("files");
    const viewHeadline = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => currentView.value === "favorites" ? (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Favorites") : currentView.value === "recent" ? (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Recent") : "");
    const selectedFiles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.shallowRef)([]);
    const savedPath = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)(window?.sessionStorage.getItem("NC.FilePicker.LastPath") || "/");
    const navigatedPath = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)("");
    (0,vue__WEBPACK_IMPORTED_MODULE_24__.watch)([navigatedPath], () => {
      if (props.path === void 0 && navigatedPath.value) {
        window.sessionStorage.setItem("NC.FilePicker.LastPath", navigatedPath.value);
      }
      selectedFiles.value = [];
    });
    const currentPath = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)({
      get: () => {
        return currentView.value === "files" ? navigatedPath.value || props.path || savedPath.value : "/";
      },
      set: (path) => {
        navigatedPath.value = path;
      }
    });
    const filterString = (0,vue__WEBPACK_IMPORTED_MODULE_24__.ref)("");
    const { isSupportedMimeType } = useMimeFilter((0,vue__WEBPACK_IMPORTED_MODULE_24__.toRef)(props, "mimetypeFilter"));
    const {
      files,
      folder: currentFolder,
      isLoading,
      loadFiles,
      createDirectory
    } = useDAVFiles(currentView, currentPath);
    (0,vue__WEBPACK_IMPORTED_MODULE_24__.onMounted)(() => loadFiles());
    const { showHiddenFiles } = useFilesSettings();
    const filteredFiles = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => {
      let filtered = files.value;
      if (!showHiddenFiles.value) {
        filtered = filtered.filter((file) => !file.basename.startsWith("."));
      }
      if (props.mimetypeFilter.length > 0) {
        filtered = filtered.filter((file) => file.type === "folder" || file.mime && isSupportedMimeType(file.mime));
      }
      if (filterString.value) {
        filtered = filtered.filter((file) => file.basename.toLowerCase().includes(filterString.value.toLowerCase()));
      }
      if (props.filterFn) {
        filtered = filtered.filter((f) => props.filterFn(f));
      }
      return filtered;
    });
    const noFilesDescription = (0,vue__WEBPACK_IMPORTED_MODULE_24__.computed)(() => {
      if (currentView.value === "files") {
        return (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Upload some content or sync with your devices!");
      } else if (currentView.value === "recent") {
        return (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Files and folders you recently modified will show up here.");
      } else {
        return (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Files and folders you mark as favorite will show up here.");
      }
    });
    const onCreateFolder = async (name) => {
      try {
        const folder = await createDirectory(name);
        navigatedPath.value = folder.path;
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_19__.emit)("files:node:created", files.value.filter((file) => file.basename === name)[0]);
      } catch (error) {
        console.warn("Could not create new folder", { name, error });
        (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.l)((0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t)("Could not create the new folder"));
      }
    };
    const handleClose = (open) => {
      if (!open && !isHandlingCallback) {
        emit$1("close");
      }
    };
    return { __sfc: true, props, emit: emit$1, isOpen, dialogButtons, isHandlingCallback, handleButtonClick, currentView, viewHeadline, selectedFiles, savedPath, navigatedPath, currentPath, filterString, isSupportedMimeType, files, currentFolder, isLoading, loadFiles, createDirectory, showHiddenFiles, filteredFiles, noFilesDescription, onCreateFolder, handleClose, IconFile, FileList, FilePickerBreadcrumbs, FilePickerNavigation, NcDialog: _nextcloud_vue_components_NcDialog__WEBPACK_IMPORTED_MODULE_20__["default"], NcEmptyContent: _nextcloud_vue_components_NcEmptyContent__WEBPACK_IMPORTED_MODULE_21__["default"], t: _plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.t };
  }
});
var _sfc_render = function render15() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c(_setup.NcDialog, { attrs: { "open": _setup.isOpen, "container": _vm.container, "buttons": _setup.dialogButtons, "name": _vm.name, "size": "large", "content-classes": "file-picker__content", "dialog-classes": "file-picker", "navigation-classes": "file-picker__navigation" }, on: { "update:open": [function($event) {
    _setup.isOpen = $event;
  }, _setup.handleClose] }, scopedSlots: _vm._u([{ key: "navigation", fn: function({ isCollapsed }) {
    return [_c(_setup.FilePickerNavigation, { attrs: { "current-view": _setup.currentView, "filter-string": _setup.filterString, "is-collapsed": isCollapsed, "disabled-navigation": _vm.disabledNavigation }, on: { "update:currentView": function($event) {
      _setup.currentView = $event;
    }, "update:current-view": function($event) {
      _setup.currentView = $event;
    }, "update:filterString": function($event) {
      _setup.filterString = $event;
    }, "update:filter-string": function($event) {
      _setup.filterString = $event;
    } } })];
  } }]) }, [_c("div", { staticClass: "file-picker__main" }, [_setup.currentView === "files" ? _c(_setup.FilePickerBreadcrumbs, { attrs: { "path": _setup.currentPath, "show-menu": _vm.allowPickDirectory }, on: { "update:path": function($event) {
    _setup.currentPath = $event;
  }, "create-node": _setup.onCreateFolder } }) : _c("div", { staticClass: "file-picker__view" }, [_c("h3", [_vm._v(_vm._s(_setup.viewHeadline))])]), _setup.isLoading || _setup.filteredFiles.length > 0 ? _c(_setup.FileList, { attrs: { "path": _setup.currentPath, "selected-files": _setup.selectedFiles, "allow-pick-directory": _vm.allowPickDirectory, "current-view": _setup.currentView, "files": _setup.filteredFiles, "multiselect": _vm.multiselect, "loading": _setup.isLoading, "name": _setup.viewHeadline }, on: { "update:path": [function($event) {
    _setup.currentPath = $event;
  }, function($event) {
    _setup.currentView = "files";
  }], "update:selectedFiles": function($event) {
    _setup.selectedFiles = $event;
  }, "update:selected-files": function($event) {
    _setup.selectedFiles = $event;
  } } }) : _setup.filterString ? _c(_setup.NcEmptyContent, { attrs: { "name": _setup.t("No matching files"), "description": _setup.t("No files matching your filter were found.") }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_c(_setup.IconFile)];
  }, proxy: true }]) }) : _c(_setup.NcEmptyContent, { attrs: { "name": _setup.t("No files in here"), "description": _setup.noFilesDescription }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_c(_setup.IconFile)];
  }, proxy: true }]) })], 1)]);
};
var _sfc_staticRenderFns = [];
var __component__ = /* @__PURE__ */ (0,_plugin_vue2_normalizer_jrlE7CJU_mjs__WEBPACK_IMPORTED_MODULE_0__.n)(
  _sfc_main,
  _sfc_render,
  _sfc_staticRenderFns,
  false,
  null,
  "552cc2f5"
);
const FilePicker = __component__.exports;

//# sourceMappingURL=FilePicker-CsU6FfAP.mjs.map


/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumb.mjs":
/*!**********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumb.mjs ***!
  \**********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcBreadcrumb_Cu1XtrUo_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcBreadcrumb_Cu1XtrUo_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcBreadcrumb-Cu1XtrUo.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcBreadcrumb-Cu1XtrUo.mjs");


//# sourceMappingURL=NcBreadcrumb.mjs.map


/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumbs.mjs":
/*!***********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcBreadcrumbs.mjs ***!
  \***********************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* reexport safe */ _chunks_NcBreadcrumbs_B1LdRe5_mjs__WEBPACK_IMPORTED_MODULE_0__.N)
/* harmony export */ });
/* harmony import */ var _chunks_NcBreadcrumbs_B1LdRe5_mjs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../chunks/NcBreadcrumbs-B1LdRe5_.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcBreadcrumbs-B1LdRe5_.mjs");


//# sourceMappingURL=NcBreadcrumbs.mjs.map


/***/ }),

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M15.4%2016.6L10.8%2012l4.6-4.6L14%206l-6%206%206%206%201.4-1.4z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M18.4%207.4L17%206l-6%206%206%206%201.4-1.4-4.6-4.6%204.6-4.6m-6%200L11%206l-6%206%206%206%201.4-1.4L7.8%2012l4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M5.6%207.4L7%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6m6%200L13%206l6%206-6%206-1.4-1.4%204.6-4.6-4.6-4.6z%27/%3e%3c/svg%3e";

/***/ }),

/***/ "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module) => {

module.exports = "data:image/svg+xml,%3c%21--%20-%20SPDX-FileCopyrightText:%202020%20Google%20Inc.%20-%20SPDX-License-Identifier:%20Apache-2.0%20--%3e%3csvg%20xmlns=%27http://www.w3.org/2000/svg%27%20width=%2724%27%20height=%2724%27%20fill=%27%23222%27%3e%3cpath%20d=%27M8.6%2016.6l4.6-4.6-4.6-4.6L10%206l6%206-6%206-1.4-1.4z%27/%3e%3c/svg%3e";

/***/ })

}]);
//# sourceMappingURL=data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e-data_image_svg_xml_3c_21--_20-_20SPDX-FileCopyrightText_202020_20Google_20Inc_20-_20SPDX-Lice-391a6e.js.map?v=87f84948225387ac2eec