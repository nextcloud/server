/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/theming/src/admin-settings.js":
/*!********************************************!*\
  !*** ./apps/theming/src/admin-settings.js ***!
  \********************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _AdminTheming_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AdminTheming.vue */ "./apps/theming/src/AdminTheming.vue");
/* harmony import */ var _helpers_refreshStyles_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./helpers/refreshStyles.js */ "./apps/theming/src/helpers/refreshStyles.js");
/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */




vue__WEBPACK_IMPORTED_MODULE_2__["default"].prototype.OC = OC;
vue__WEBPACK_IMPORTED_MODULE_2__["default"].prototype.t = t;
var View = vue__WEBPACK_IMPORTED_MODULE_2__["default"].extend(_AdminTheming_vue__WEBPACK_IMPORTED_MODULE_0__["default"]);
var theming = new View();
theming.$mount('#admin-theming');
theming.$on('update:theming', _helpers_refreshStyles_js__WEBPACK_IMPORTED_MODULE_1__.refreshStyles);

/***/ }),

/***/ "./apps/theming/src/helpers/refreshStyles.js":
/*!***************************************************!*\
  !*** ./apps/theming/src/helpers/refreshStyles.js ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "refreshStyles": function() { return /* binding */ refreshStyles; }
/* harmony export */ });
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }
/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

var refreshStyles = function refreshStyles() {
  // Refresh server-side generated theming CSS
  _toConsumableArray(document.head.querySelectorAll('link.theme')).forEach(function (theme) {
    var url = new URL(theme.href);
    url.searchParams.set('v', Date.now());
    var newTheme = theme.cloneNode();
    newTheme.href = url.toString();
    newTheme.onload = function () {
      return theme.remove();
    };
    document.head.append(newTheme);
  });
};

/***/ }),

/***/ "./apps/theming/src/mixins/admin/FieldMixin.js":
/*!*****************************************************!*\
  !*** ./apps/theming/src/mixins/admin/FieldMixin.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

var styleRefreshFields = ['color', 'logo', 'background', 'logoheader', 'favicon', 'disable-user-theming'];
/* harmony default export */ __webpack_exports__["default"] = ({
  emits: ['update:theming'],
  data: function data() {
    return {
      showSuccess: false,
      errorMessage: ''
    };
  },
  computed: {
    id: function id() {
      return "admin-theming-".concat(this.name);
    }
  },
  methods: {
    reset: function reset() {
      this.showSuccess = false;
      this.errorMessage = '';
    },
    handleSuccess: function handleSuccess() {
      var _this = this;
      this.showSuccess = true;
      setTimeout(function () {
        _this.showSuccess = false;
      }, 2000);
      if (styleRefreshFields.includes(this.name)) {
        this.$emit('update:theming');
      }
    }
  }
});

/***/ }),

/***/ "./apps/theming/src/mixins/admin/TextValueMixin.js":
/*!*********************************************************!*\
  !*** ./apps/theming/src/mixins/admin/TextValueMixin.js ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _FieldMixin_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FieldMixin.js */ "./apps/theming/src/mixins/admin/FieldMixin.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }
/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */




/* harmony default export */ __webpack_exports__["default"] = ({
  mixins: [_FieldMixin_js__WEBPACK_IMPORTED_MODULE_2__["default"]],
  watch: {
    value: function value(_value) {
      this.localValue = _value;
    }
  },
  data: function data() {
    return {
      localValue: this.value
    };
  },
  methods: {
    save: function save() {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var url, valueToPost, _e$response$data$data;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this.reset();
                url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/theming/ajax/updateStylesheet'); // Convert boolean to string as server expects string value
                valueToPost = _this.localValue === true ? 'yes' : _this.localValue === false ? 'no' : _this.localValue;
                _context.prev = 3;
                _context.next = 6;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
                  setting: _this.name,
                  value: valueToPost
                });
              case 6:
                _this.$emit('update:value', _this.localValue);
                _this.handleSuccess();
                _context.next = 13;
                break;
              case 10:
                _context.prev = 10;
                _context.t0 = _context["catch"](3);
                _this.errorMessage = (_e$response$data$data = _context.t0.response.data.data) === null || _e$response$data$data === void 0 ? void 0 : _e$response$data$data.message;
              case 13:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[3, 10]]);
      }))();
    },
    undo: function undo() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var url, _e$response$data$data2;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this2.reset();
                url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/theming/ajax/undoChanges');
                _context2.prev = 2;
                _context2.next = 5;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
                  setting: _this2.name
                });
              case 5:
                _this2.$emit('update:value', _this2.defaultValue);
                _this2.handleSuccess();
                _context2.next = 12;
                break;
              case 9:
                _context2.prev = 9;
                _context2.t0 = _context2["catch"](2);
                _this2.errorMessage = (_e$response$data$data2 = _context2.t0.response.data.data) === null || _e$response$data$data2 === void 0 ? void 0 : _e$response$data$data2.message;
              case 12:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[2, 9]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var _components_admin_CheckboxField_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./components/admin/CheckboxField.vue */ "./apps/theming/src/components/admin/CheckboxField.vue");
/* harmony import */ var _components_admin_ColorPickerField_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/admin/ColorPickerField.vue */ "./apps/theming/src/components/admin/ColorPickerField.vue");
/* harmony import */ var _components_admin_FileInputField_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./components/admin/FileInputField.vue */ "./apps/theming/src/components/admin/FileInputField.vue");
/* harmony import */ var _components_admin_TextField_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./components/admin/TextField.vue */ "./apps/theming/src/components/admin/TextField.vue");






var _loadState = (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('theming', 'adminThemingParameters'),
  backgroundMime = _loadState.backgroundMime,
  canThemeIcons = _loadState.canThemeIcons,
  color = _loadState.color,
  docUrl = _loadState.docUrl,
  docUrlIcons = _loadState.docUrlIcons,
  faviconMime = _loadState.faviconMime,
  isThemable = _loadState.isThemable,
  legalNoticeUrl = _loadState.legalNoticeUrl,
  logoheaderMime = _loadState.logoheaderMime,
  logoMime = _loadState.logoMime,
  name = _loadState.name,
  notThemableErrorMessage = _loadState.notThemableErrorMessage,
  privacyPolicyUrl = _loadState.privacyPolicyUrl,
  slogan = _loadState.slogan,
  url = _loadState.url,
  userThemingDisabled = _loadState.userThemingDisabled;
var textFields = [{
  name: 'name',
  value: name,
  defaultValue: 'Nextcloud',
  type: 'text',
  displayName: t('theming', 'Name'),
  placeholder: t('theming', 'Name'),
  maxlength: 250
}, {
  name: 'url',
  value: url,
  defaultValue: 'https://nextcloud.com',
  type: 'url',
  displayName: t('theming', 'Web link'),
  placeholder: 'https://…',
  maxlength: 500
}, {
  name: 'slogan',
  value: slogan,
  defaultValue: t('theming', 'a safe home for all your data'),
  type: 'text',
  displayName: t('theming', 'Slogan'),
  placeholder: t('theming', 'Slogan'),
  maxlength: 500
}];
var colorPickerField = {
  name: 'color',
  value: color,
  defaultValue: '#0082c9',
  displayName: t('theming', 'Color')
};
var fileInputFields = [{
  name: 'logo',
  mimeName: 'logoMime',
  mimeValue: logoMime,
  defaultMimeValue: '',
  displayName: t('theming', 'Logo'),
  ariaLabel: t('theming', 'Upload new logo')
}, {
  name: 'background',
  mimeName: 'backgroundMime',
  mimeValue: backgroundMime,
  defaultMimeValue: '',
  displayName: t('theming', 'Background and login image'),
  ariaLabel: t('theming', 'Upload new background and login image')
}];
var advancedTextFields = [{
  name: 'imprintUrl',
  value: legalNoticeUrl,
  defaultValue: '',
  type: 'url',
  displayName: t('theming', 'Legal notice link'),
  placeholder: 'https://…',
  maxlength: 500
}, {
  name: 'privacyUrl',
  value: privacyPolicyUrl,
  defaultValue: '',
  type: 'url',
  displayName: t('theming', 'Privacy policy link'),
  placeholder: 'https://…',
  maxlength: 500
}];
var advancedFileInputFields = [{
  name: 'logoheader',
  mimeName: 'logoheaderMime',
  mimeValue: logoheaderMime,
  defaultMimeValue: '',
  displayName: t('theming', 'Header logo'),
  ariaLabel: t('theming', 'Upload new header logo')
}, {
  name: 'favicon',
  mimeName: 'faviconMime',
  mimeValue: faviconMime,
  defaultMimeValue: '',
  displayName: t('theming', 'Favicon'),
  ariaLabel: t('theming', 'Upload new favicon')
}];
var userThemingField = {
  name: 'disable-user-theming',
  value: userThemingDisabled,
  defaultValue: false,
  displayName: t('theming', 'User settings'),
  label: t('theming', 'Disable user theming'),
  description: t('theming', 'Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.')
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AdminTheming',
  components: {
    CheckboxField: _components_admin_CheckboxField_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    ColorPickerField: _components_admin_ColorPickerField_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    FileInputField: _components_admin_FileInputField_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcNoteCard,
    NcSettingsSection: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcSettingsSection,
    TextField: _components_admin_TextField_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  emits: ['update:theming'],
  data: function data() {
    return {
      textFields: textFields,
      colorPickerField: colorPickerField,
      fileInputFields: fileInputFields,
      advancedTextFields: advancedTextFields,
      advancedFileInputFields: advancedFileInputFields,
      userThemingField: userThemingField,
      canThemeIcons: canThemeIcons,
      docUrl: docUrl,
      docUrlIcons: docUrlIcons,
      isThemable: isThemable,
      notThemableErrorMessage: notThemableErrorMessage
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var _mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../mixins/admin/TextValueMixin.js */ "./apps/theming/src/mixins/admin/TextValueMixin.js");


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'CheckboxField',
  components: {
    NcCheckboxRadioSwitch: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcCheckboxRadioSwitch,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcNoteCard
  },
  mixins: [_mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_1__["default"]],
  props: {
    name: {
      type: String,
      required: true
    },
    value: {
      type: Boolean,
      required: true
    },
    defaultValue: {
      type: Boolean,
      required: true
    },
    displayName: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    description: {
      type: String,
      required: true
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! debounce */ "./node_modules/debounce/index.js");
/* harmony import */ var debounce__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(debounce__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var vue_material_design_icons_UndoVariant_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/UndoVariant.vue */ "./node_modules/vue-material-design-icons/UndoVariant.vue");
/* harmony import */ var _mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../mixins/admin/TextValueMixin.js */ "./apps/theming/src/mixins/admin/TextValueMixin.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ColorPickerField',
  components: {
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcButton,
    NcColorPicker: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcColorPicker,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__.NcNoteCard,
    Undo: vue_material_design_icons_UndoVariant_vue__WEBPACK_IMPORTED_MODULE_2__["default"]
  },
  mixins: [_mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_3__["default"]],
  props: {
    name: {
      type: String,
      required: true
    },
    value: {
      type: String,
      required: true
    },
    defaultValue: {
      type: String,
      required: true
    },
    displayName: {
      type: String,
      required: true
    }
  },
  methods: {
    debounceSave: (0,debounce__WEBPACK_IMPORTED_MODULE_0__.debounce)( /*#__PURE__*/_asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
      return regeneratorRuntime.wrap(function _callee$(_context) {
        while (1) {
          switch (_context.prev = _context.next) {
            case 0:
              _context.next = 2;
              return this.save();
            case 2:
            case "end":
              return _context.stop();
          }
        }
      }, _callee, this);
    })), 200)
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Delete.vue */ "./node_modules/vue-material-design-icons/Delete.vue");
/* harmony import */ var vue_material_design_icons_UndoVariant_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/UndoVariant.vue */ "./node_modules/vue-material-design-icons/UndoVariant.vue");
/* harmony import */ var vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue-material-design-icons/Upload.vue */ "./node_modules/vue-material-design-icons/Upload.vue");
/* harmony import */ var _mixins_admin_FieldMixin_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../mixins/admin/FieldMixin.js */ "./apps/theming/src/mixins/admin/FieldMixin.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }







/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'FileInputField',
  components: {
    Delete: vue_material_design_icons_Delete_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcButton,
    NcLoadingIcon: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcLoadingIcon,
    NcNoteCard: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.NcNoteCard,
    Undo: vue_material_design_icons_UndoVariant_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    Upload: vue_material_design_icons_Upload_vue__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_admin_FieldMixin_js__WEBPACK_IMPORTED_MODULE_6__["default"]],
  props: {
    name: {
      type: String,
      required: true
    },
    mimeName: {
      type: String,
      required: true
    },
    mimeValue: {
      type: String,
      required: true
    },
    defaultMimeValue: {
      type: String,
      required: true
    },
    displayName: {
      type: String,
      required: true
    },
    ariaLabel: {
      type: String,
      required: true
    }
  },
  data: function data() {
    return {
      showLoading: false
    };
  },
  computed: {
    showReset: function showReset() {
      return this.mimeValue !== this.defaultMimeValue;
    },
    showRemove: function showRemove() {
      if (this.name === 'background') {
        if (this.mimeValue.startsWith('image/')) {
          return true;
        }
        if (this.mimeValue === this.defaultMimeValue) {
          return true;
        }
      }
      return false;
    }
  },
  methods: {
    activateLocalFilePicker: function activateLocalFilePicker() {
      this.reset();
      // Set to null so that selecting the same file will trigger the change event
      this.$refs.input.value = null;
      this.$refs.input.click();
    },
    onChange: function onChange(e) {
      var _this = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var file, formData, url, _e$response$data$data;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                file = e.target.files[0];
                formData = new FormData();
                formData.append('key', _this.name);
                formData.append('image', file);
                url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/theming/ajax/uploadImage');
                _context.prev = 5;
                _this.showLoading = true;
                _context.next = 9;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, formData);
              case 9:
                _this.showLoading = false;
                _this.$emit('update:mime-value', file.type);
                _this.handleSuccess();
                _context.next = 18;
                break;
              case 14:
                _context.prev = 14;
                _context.t0 = _context["catch"](5);
                _this.showLoading = false;
                _this.errorMessage = (_e$response$data$data = _context.t0.response.data.data) === null || _e$response$data$data === void 0 ? void 0 : _e$response$data$data.message;
              case 18:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[5, 14]]);
      }))();
    },
    undo: function undo() {
      var _this2 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var url, _e$response$data$data2;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _this2.reset();
                url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/theming/ajax/undoChanges');
                _context2.prev = 2;
                _context2.next = 5;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
                  setting: _this2.mimeName
                });
              case 5:
                _this2.$emit('update:mime-value', _this2.defaultMimeValue);
                _this2.handleSuccess();
                _context2.next = 12;
                break;
              case 9:
                _context2.prev = 9;
                _context2.t0 = _context2["catch"](2);
                _this2.errorMessage = (_e$response$data$data2 = _context2.t0.response.data.data) === null || _e$response$data$data2 === void 0 ? void 0 : _e$response$data$data2.message;
              case 12:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[2, 9]]);
      }))();
    },
    removeBackground: function removeBackground() {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var url, _e$response$data$data3;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                _this3.reset();
                url = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/apps/theming/ajax/updateStylesheet');
                _context3.prev = 2;
                _context3.next = 5;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(url, {
                  setting: _this3.mimeName,
                  value: 'backgroundColor'
                });
              case 5:
                _this3.$emit('update:mime-value', 'backgroundColor');
                _this3.handleSuccess();
                _context3.next = 12;
                break;
              case 9:
                _context3.prev = 9;
                _context3.t0 = _context3["catch"](2);
                _this3.errorMessage = (_e$response$data$data3 = _context3.t0.response.data.data) === null || _e$response$data$data3 === void 0 ? void 0 : _e$response$data$data3.message;
              case 12:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[2, 9]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js&":
/*!**************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");
/* harmony import */ var _mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../mixins/admin/TextValueMixin.js */ "./apps/theming/src/mixins/admin/TextValueMixin.js");


/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'TextField',
  components: {
    NcTextField: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcTextField
  },
  mixins: [_mixins_admin_TextValueMixin_js__WEBPACK_IMPORTED_MODULE_1__["default"]],
  props: {
    name: {
      type: String,
      required: true
    },
    value: {
      type: String,
      required: true
    },
    defaultValue: {
      type: String,
      required: true
    },
    type: {
      type: String,
      required: true
    },
    displayName: {
      type: String,
      required: true
    },
    placeholder: {
      type: String,
      required: true
    },
    maxlength: {
      type: Number,
      required: true
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("section", [_c("NcSettingsSection", {
    attrs: {
      title: _vm.t("theming", "Theming"),
      description: _vm.t("theming", "Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users."),
      "doc-url": _vm.docUrl,
      "data-admin-theming-settings": ""
    }
  }, [_c("div", {
    staticClass: "admin-theming"
  }, [!_vm.isThemable ? _c("NcNoteCard", {
    attrs: {
      type: "error",
      "show-alert": true
    }
  }, [_c("p", [_vm._v(_vm._s(_vm.notThemableErrorMessage))])]) : _vm._e(), _vm._v(" "), _vm._l(_vm.textFields, function (field) {
    return _c("TextField", {
      key: field.name,
      attrs: {
        "data-admin-theming-setting-field": field.name,
        "default-value": field.defaultValue,
        "display-name": field.displayName,
        maxlength: field.maxlength,
        name: field.name,
        placeholder: field.placeholder,
        type: field.type,
        value: field.value
      },
      on: {
        "update:value": function updateValue($event) {
          return _vm.$set(field, "value", $event);
        },
        "update:theming": function updateTheming($event) {
          return _vm.$emit("update:theming");
        }
      }
    });
  }), _vm._v(" "), _c("ColorPickerField", {
    attrs: {
      name: _vm.colorPickerField.name,
      "default-value": _vm.colorPickerField.defaultValue,
      "display-name": _vm.colorPickerField.displayName,
      value: _vm.colorPickerField.value,
      "data-admin-theming-setting-primary-color": ""
    },
    on: {
      "update:value": function updateValue($event) {
        return _vm.$set(_vm.colorPickerField, "value", $event);
      },
      "update:theming": function updateTheming($event) {
        return _vm.$emit("update:theming");
      }
    }
  }), _vm._v(" "), _vm._l(_vm.fileInputFields, function (field) {
    return _c("FileInputField", {
      key: field.name,
      attrs: {
        "aria-label": field.ariaLabel,
        "data-admin-theming-setting-file": field.name,
        "default-mime-value": field.defaultMimeValue,
        "display-name": field.displayName,
        "mime-name": field.mimeName,
        "mime-value": field.mimeValue,
        name: field.name
      },
      on: {
        "update:mimeValue": function updateMimeValue($event) {
          return _vm.$set(field, "mimeValue", $event);
        },
        "update:mime-value": function updateMimeValue($event) {
          return _vm.$set(field, "mimeValue", $event);
        },
        "update:theming": function updateTheming($event) {
          return _vm.$emit("update:theming");
        }
      }
    });
  }), _vm._v(" "), _c("div", {
    staticClass: "admin-theming__preview",
    attrs: {
      "data-admin-theming-preview": ""
    }
  }, [_c("div", {
    staticClass: "admin-theming__preview-logo",
    attrs: {
      "data-admin-theming-preview-logo": ""
    }
  })])], 2)]), _vm._v(" "), _c("NcSettingsSection", {
    attrs: {
      title: _vm.t("theming", "Advanced options")
    }
  }, [_c("div", {
    staticClass: "admin-theming-advanced"
  }, [_vm._l(_vm.advancedTextFields, function (field) {
    return _c("TextField", {
      key: field.name,
      attrs: {
        name: field.name,
        value: field.value,
        "default-value": field.defaultValue,
        type: field.type,
        "display-name": field.displayName,
        placeholder: field.placeholder,
        maxlength: field.maxlength
      },
      on: {
        "update:value": function updateValue($event) {
          return _vm.$set(field, "value", $event);
        },
        "update:theming": function updateTheming($event) {
          return _vm.$emit("update:theming");
        }
      }
    });
  }), _vm._v(" "), _vm._l(_vm.advancedFileInputFields, function (field) {
    return _c("FileInputField", {
      key: field.name,
      attrs: {
        name: field.name,
        "mime-name": field.mimeName,
        "mime-value": field.mimeValue,
        "default-mime-value": field.defaultMimeValue,
        "display-name": field.displayName,
        "aria-label": field.ariaLabel
      },
      on: {
        "update:mimeValue": function updateMimeValue($event) {
          return _vm.$set(field, "mimeValue", $event);
        },
        "update:mime-value": function updateMimeValue($event) {
          return _vm.$set(field, "mimeValue", $event);
        },
        "update:theming": function updateTheming($event) {
          return _vm.$emit("update:theming");
        }
      }
    });
  }), _vm._v(" "), _c("CheckboxField", {
    attrs: {
      name: _vm.userThemingField.name,
      value: _vm.userThemingField.value,
      "default-value": _vm.userThemingField.defaultValue,
      "display-name": _vm.userThemingField.displayName,
      label: _vm.userThemingField.label,
      description: _vm.userThemingField.description,
      "data-admin-theming-setting-disable-user-theming": ""
    },
    on: {
      "update:theming": function updateTheming($event) {
        return _vm.$emit("update:theming");
      }
    }
  }), _vm._v(" "), !_vm.canThemeIcons ? _c("a", {
    attrs: {
      href: _vm.docUrlIcons,
      rel: "noreferrer noopener"
    }
  }, [_c("em", [_vm._v(_vm._s(_vm.t("theming", "Install the ImageMagick PHP extension with support for SVG images to automatically generate favicons based on the uploaded logo and color.")))])]) : _vm._e()], 2)])], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "field"
  }, [_c("label", {
    attrs: {
      for: _vm.id
    }
  }, [_vm._v(_vm._s(_vm.displayName))]), _vm._v(" "), _c("div", {
    staticClass: "field__row"
  }, [_c("NcCheckboxRadioSwitch", {
    attrs: {
      id: _vm.id,
      type: "switch",
      checked: _vm.localValue
    },
    on: {
      "update:checked": [function ($event) {
        _vm.localValue = $event;
      }, _vm.save]
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.label) + "\n\t\t")])], 1), _vm._v(" "), _c("p", {
    staticClass: "field__description"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.description) + "\n\t")]), _vm._v(" "), _vm.errorMessage ? _c("NcNoteCard", {
    attrs: {
      type: "error",
      "show-alert": true
    }
  }, [_c("p", [_vm._v(_vm._s(_vm.errorMessage))])]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "field"
  }, [_c("label", {
    attrs: {
      for: _vm.id
    }
  }, [_vm._v(_vm._s(_vm.displayName))]), _vm._v(" "), _c("div", {
    staticClass: "field__row"
  }, [_c("NcColorPicker", {
    attrs: {
      value: _vm.localValue,
      "advanced-fields": true
    },
    on: {
      "update:value": [function ($event) {
        _vm.localValue = $event;
      }, _vm.debounceSave]
    }
  }, [_c("NcButton", {
    staticClass: "field__button",
    attrs: {
      id: _vm.id,
      type: "primary",
      "aria-label": _vm.t("theming", "Select a custom color"),
      "data-admin-theming-setting-primary-color-picker": ""
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.value) + "\n\t\t\t")])], 1), _vm._v(" "), _vm.value !== _vm.defaultValue ? _c("NcButton", {
    attrs: {
      type: "tertiary",
      "aria-label": _vm.t("theming", "Reset to default"),
      "data-admin-theming-setting-primary-color-reset": ""
    },
    on: {
      click: _vm.undo
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Undo", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 33666776)
  }) : _vm._e()], 1), _vm._v(" "), _vm.errorMessage ? _c("NcNoteCard", {
    attrs: {
      type: "error",
      "show-alert": true
    }
  }, [_c("p", [_vm._v(_vm._s(_vm.errorMessage))])]) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "field"
  }, [_c("label", {
    attrs: {
      for: _vm.id
    }
  }, [_vm._v(_vm._s(_vm.displayName))]), _vm._v(" "), _c("div", {
    staticClass: "field__row"
  }, [_c("NcButton", {
    attrs: {
      id: _vm.id,
      type: "secondary",
      "aria-label": _vm.ariaLabel,
      "data-admin-theming-setting-file-picker": ""
    },
    on: {
      click: _vm.activateLocalFilePicker
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Upload", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("theming", "Upload")) + "\n\t\t")]), _vm._v(" "), _vm.showReset ? _c("NcButton", {
    attrs: {
      type: "tertiary",
      "aria-label": _vm.t("theming", "Reset to default"),
      "data-admin-theming-setting-file-reset": ""
    },
    on: {
      click: _vm.undo
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Undo", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 33666776)
  }) : _vm._e(), _vm._v(" "), _vm.showRemove ? _c("NcButton", {
    attrs: {
      type: "tertiary",
      "aria-label": _vm.t("theming", "Remove background image"),
      "data-admin-theming-setting-file-remove": ""
    },
    on: {
      click: _vm.removeBackground
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function fn() {
        return [_c("Delete", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 2705356561)
  }) : _vm._e(), _vm._v(" "), _vm.showLoading ? _c("NcLoadingIcon", {
    staticClass: "field__loading-icon",
    attrs: {
      size: 20
    }
  }) : _vm._e()], 1), _vm._v(" "), (_vm.name === "logoheader" || _vm.name === "favicon") && _vm.mimeValue !== _vm.defaultMimeValue ? _c("div", {
    staticClass: "field__preview",
    class: {
      "field__preview--logoheader": _vm.name === "logoheader",
      "field__preview--favicon": _vm.name === "favicon"
    }
  }) : _vm._e(), _vm._v(" "), _vm.errorMessage ? _c("NcNoteCard", {
    attrs: {
      type: "error",
      "show-alert": true
    }
  }, [_c("p", [_vm._v(_vm._s(_vm.errorMessage))])]) : _vm._e(), _vm._v(" "), _c("input", {
    ref: "input",
    attrs: {
      type: "file"
    },
    on: {
      change: _vm.onChange
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "field"
  }, [_c("NcTextField", {
    attrs: {
      value: _vm.localValue,
      label: _vm.displayName,
      "label-visible": true,
      placeholder: _vm.placeholder,
      type: _vm.type,
      maxlength: _vm.maxlength,
      spellcheck: false,
      success: _vm.showSuccess,
      error: Boolean(_vm.errorMessage),
      "helper-text": _vm.errorMessage,
      "show-trailing-button": _vm.value !== _vm.defaultValue,
      "trailing-button-icon": "undo"
    },
    on: {
      "update:value": function updateValue($event) {
        _vm.localValue = $event;
      },
      "trailing-button-click": _vm.undo,
      keydown: function keydown($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        return _vm.save.apply(null, arguments);
      },
      blur: _vm.save
    }
  })], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/css-loader/dist/runtime/getUrl.js */ "./node_modules/css-loader/dist/runtime/getUrl.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2__);
// Imports



var ___CSS_LOADER_URL_IMPORT_0___ = new URL(/* asset import */ __webpack_require__(/*! ../../../core/img/logo/logo.svg */ "./core/img/logo/logo.svg"), __webpack_require__.b);
var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
var ___CSS_LOADER_URL_REPLACEMENT_0___ = _node_modules_css_loader_dist_runtime_getUrl_js__WEBPACK_IMPORTED_MODULE_2___default()(___CSS_LOADER_URL_IMPORT_0___);
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".admin-theming[data-v-c42c152c],\n.admin-theming-advanced[data-v-c42c152c] {\n  display: flex;\n  flex-direction: column;\n  gap: 8px 0;\n}\n.admin-theming__preview[data-v-c42c152c] {\n  width: 230px;\n  height: 140px;\n  background-size: cover;\n  background-position: center;\n  text-align: center;\n  margin-top: 10px;\n  /* This is basically https://github.com/nextcloud/server/blob/master/core/css/guest.css\n     But without the user variables. That way the admin can preview the render as guest*/\n  /* As guest, there is no user color color-background-plain */\n  background-color: var(--color-primary-default, #0082c9);\n  /* As guest, there is no user background (--image-background)\n  1. Empty background if defined\n  2. Else default background\n  3. Finally default gradient (should not happened, the background is always defined anyway) */\n  background-image: var(--image-background-plain, var(--image-background-default, linear-gradient(40deg, #0082c9 0%, #30b6ff 100%)));\n}\n.admin-theming__preview-logo[data-v-c42c152c] {\n  width: 20%;\n  height: 20%;\n  margin-top: 20px;\n  display: inline-block;\n  background-size: contain;\n  background-position: center;\n  background-repeat: no-repeat;\n  background-image: var(--image-logo, url(" + ___CSS_LOADER_URL_REPLACEMENT_0___ + "));\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/**\n * @copyright 2022 Christopher Ng <chrng8@gmail.com>\n *\n * @author Christopher Ng <chrng8@gmail.com>\n *\n * @license AGPL-3.0-or-later\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n.field[data-v-2f632a9e] {\n  display: flex;\n  flex-direction: column;\n  gap: 4px 0;\n}\n.field__row[data-v-2f632a9e] {\n  display: flex;\n  gap: 0 4px;\n}\n.field__description[data-v-2f632a9e] {\n  color: var(--color-text-maxcontrast);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/**\n * @copyright 2022 Christopher Ng <chrng8@gmail.com>\n *\n * @author Christopher Ng <chrng8@gmail.com>\n *\n * @license AGPL-3.0-or-later\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n.field[data-v-041d23df] {\n  display: flex;\n  flex-direction: column;\n  gap: 4px 0;\n}\n.field__row[data-v-041d23df] {\n  display: flex;\n  gap: 0 4px;\n}\n.field__button[data-v-041d23df] {\n  width: 230px !important;\n  border-radius: var(--border-radius-large) !important;\n  background-color: var(--color-primary-default) !important;\n}\n.field__button[data-v-041d23df]:hover {\n  background-color: var(--color-primary-element-default-hover) !important;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "/**\n * @copyright 2022 Christopher Ng <chrng8@gmail.com>\n *\n * @author Christopher Ng <chrng8@gmail.com>\n *\n * @license AGPL-3.0-or-later\n *\n * This program is free software: you can redistribute it and/or modify\n * it under the terms of the GNU Affero General Public License as\n * published by the Free Software Foundation, either version 3 of the\n * License, or (at your option) any later version.\n *\n * This program is distributed in the hope that it will be useful,\n * but WITHOUT ANY WARRANTY; without even the implied warranty of\n * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the\n * GNU Affero General Public License for more details.\n *\n * You should have received a copy of the GNU Affero General Public License\n * along with this program. If not, see <http://www.gnu.org/licenses/>.\n *\n */\n.field[data-v-1856abbc] {\n  display: flex;\n  flex-direction: column;\n  gap: 4px 0;\n}\n.field__row[data-v-1856abbc] {\n  display: flex;\n  gap: 0 4px;\n}\n.field__loading-icon[data-v-1856abbc] {\n  width: 44px;\n  height: 44px;\n}\n.field__preview[data-v-1856abbc] {\n  width: 70px;\n  height: 70px;\n  background-size: contain;\n  background-position: center;\n  background-repeat: no-repeat;\n  margin: 10px 0;\n}\n.field__preview--logoheader[data-v-1856abbc] {\n  background-image: var(--image-logoheader);\n}\n.field__preview--favicon[data-v-1856abbc] {\n  background-image: var(--image-favicon);\n}\ninput[type=file][data-v-1856abbc] {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".field[data-v-495c4cf2] {\n  max-width: 400px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/theming/src/AdminTheming.vue":
/*!*******************************************!*\
  !*** ./apps/theming/src/AdminTheming.vue ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true& */ "./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true&");
/* harmony import */ var _AdminTheming_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AdminTheming.vue?vue&type=script&lang=js& */ "./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js&");
/* harmony import */ var _AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& */ "./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AdminTheming_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "c42c152c",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/theming/src/AdminTheming.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/theming/src/components/admin/CheckboxField.vue":
/*!*************************************************************!*\
  !*** ./apps/theming/src/components/admin/CheckboxField.vue ***!
  \*************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true& */ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true&");
/* harmony import */ var _CheckboxField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CheckboxField.vue?vue&type=script&lang=js& */ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js&");
/* harmony import */ var _CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& */ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _CheckboxField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2f632a9e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/theming/src/components/admin/CheckboxField.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/theming/src/components/admin/ColorPickerField.vue":
/*!****************************************************************!*\
  !*** ./apps/theming/src/components/admin/ColorPickerField.vue ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true& */ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true&");
/* harmony import */ var _ColorPickerField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ColorPickerField.vue?vue&type=script&lang=js& */ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js&");
/* harmony import */ var _ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& */ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ColorPickerField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "041d23df",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/theming/src/components/admin/ColorPickerField.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/theming/src/components/admin/FileInputField.vue":
/*!**************************************************************!*\
  !*** ./apps/theming/src/components/admin/FileInputField.vue ***!
  \**************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./FileInputField.vue?vue&type=template&id=1856abbc&scoped=true& */ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true&");
/* harmony import */ var _FileInputField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./FileInputField.vue?vue&type=script&lang=js& */ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js&");
/* harmony import */ var _FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& */ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _FileInputField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "1856abbc",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/theming/src/components/admin/FileInputField.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/theming/src/components/admin/TextField.vue":
/*!*********************************************************!*\
  !*** ./apps/theming/src/components/admin/TextField.vue ***!
  \*********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./TextField.vue?vue&type=template&id=495c4cf2&scoped=true& */ "./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true&");
/* harmony import */ var _TextField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./TextField.vue?vue&type=script&lang=js& */ "./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js&");
/* harmony import */ var _TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& */ "./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _TextField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "495c4cf2",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/theming/src/components/admin/TextField.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js&":
/*!********************************************************************!*\
  !*** ./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js& ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AdminTheming.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CheckboxField.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ColorPickerField.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js&":
/*!***************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileInputField.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js&":
/*!**********************************************************************************!*\
  !*** ./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TextField.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true&":
/*!**************************************************************************************!*\
  !*** ./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true& ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_template_id_c42c152c_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib/index.js!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=template&id=c42c152c&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true&":
/*!********************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true& ***!
  \********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_template_id_2f632a9e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=template&id=2f632a9e&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true&":
/*!***********************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true& ***!
  \***********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_template_id_041d23df_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=template&id=041d23df&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true&":
/*!*********************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true& ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_template_id_1856abbc_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileInputField.vue?vue&type=template&id=1856abbc&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=template&id=1856abbc&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true&":
/*!****************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true& ***!
  \****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_template_id_495c4cf2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TextField.vue?vue&type=template&id=495c4cf2&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=template&id=495c4cf2&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AdminTheming_vue_vue_type_style_index_0_id_c42c152c_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/style-loader/dist/cjs.js!../../../node_modules/css-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../node_modules/sass-loader/dist/cjs.js!../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/AdminTheming.vue?vue&type=style&index=0&id=c42c152c&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&":
/*!***********************************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CheckboxField_vue_vue_type_style_index_0_id_2f632a9e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/CheckboxField.vue?vue&type=style&index=0&id=2f632a9e&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&":
/*!**************************************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ColorPickerField_vue_vue_type_style_index_0_id_041d23df_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/ColorPickerField.vue?vue&type=style&index=0&id=041d23df&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&":
/*!************************************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& ***!
  \************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_FileInputField_vue_vue_type_style_index_0_id_1856abbc_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/FileInputField.vue?vue&type=style&index=0&id=1856abbc&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&":
/*!*******************************************************************************************************************!*\
  !*** ./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_TextField_vue_vue_type_style_index_0_id_495c4cf2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/theming/src/components/admin/TextField.vue?vue&type=style&index=0&id=495c4cf2&lang=scss&scoped=true&");


/***/ }),

/***/ "./core/img/logo/logo.svg":
/*!********************************!*\
  !*** ./core/img/logo/logo.svg ***!
  \********************************/
/***/ (function(module) {

module.exports = "data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjU2IiBoZWlnaHQ9IjEyOCIgdmVyc2lvbj0iMS4xIiB2aWV3Qm94PSIwIDAgMjU2IDEyOCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cGF0aCBkPSJtMTI4IDdjLTI1Ljg3MSAwLTQ3LjgxNyAxNy40ODUtNTQuNzEzIDQxLjIwOS01Ljk3OTUtMTIuNDYxLTE4LjY0Mi0yMS4yMDktMzMuMjg3LTIxLjIwOS0yMC4zMDQgMC0zNyAxNi42OTYtMzcgMzdzMTYuNjk2IDM3IDM3IDM3YzE0LjY0NSAwIDI3LjMwOC04Ljc0ODEgMzMuMjg3LTIxLjIwOSA2Ljg5NTcgMjMuNzI0IDI4Ljg0MiA0MS4yMDkgNTQuNzEzIDQxLjIwOXM0Ny44MTctMTcuNDg1IDU0LjcxMy00MS4yMDljNS45Nzk1IDEyLjQ2MSAxOC42NDIgMjEuMjA5IDMzLjI4NyAyMS4yMDkgMjAuMzA0IDAgMzctMTYuNjk2IDM3LTM3cy0xNi42OTYtMzctMzctMzdjLTE0LjY0NSAwLTI3LjMwOCA4Ljc0ODEtMzMuMjg3IDIxLjIwOS02Ljg5NTctMjMuNzI0LTI4Ljg0Mi00MS4yMDktNTQuNzEzLTQxLjIwOXptMCAyMmMxOS40NiAwIDM1IDE1LjU0IDM1IDM1cy0xNS41NCAzNS0zNSAzNS0zNS0xNS41NC0zNS0zNSAxNS41NC0zNSAzNS0zNXptLTg4IDIwYzguNDE0NiAwIDE1IDYuNTg1NCAxNSAxNXMtNi41ODU0IDE1LTE1IDE1LTE1LTYuNTg1NC0xNS0xNSA2LjU4NTQtMTUgMTUtMTV6bTE3NiAwYzguNDE0NiAwIDE1IDYuNTg1NCAxNSAxNXMtNi41ODU0IDE1LTE1IDE1LTE1LTYuNTg1NC0xNS0xNSA2LjU4NTQtMTUgMTUtMTV6IiBjb2xvcj0iIzAwMDAwMCIgZmlsbD0iI2ZmZiIgc3R5bGU9Ii1pbmtzY2FwZS1zdHJva2U6bm9uZSIvPjwvc3ZnPgo=";

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
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
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
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"theming-admin-theming": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
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
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/theming/src/admin-settings.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=theming-admin-theming.js.map?v=6adddb325235f03f72da