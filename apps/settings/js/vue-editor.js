(window["webpackJsonpSettings"] = window["webpackJsonpSettings"] || []).push([["editor"],{

/***/ "./apps/text/src/EditorFactory.js":
/*!****************************************!*\
  !*** ./apps/text/src/EditorFactory.js ***!
  \****************************************/
/*! exports provided: default, markdownit, createEditor, createMarkdownSerializer, serializePlainText, loadSyntaxHighlight */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "markdownit", function() { return markdownit; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createEditor", function() { return createEditor; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "createMarkdownSerializer", function() { return createMarkdownSerializer; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "serializePlainText", function() { return serializePlainText; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "loadSyntaxHighlight", function() { return loadSyntaxHighlight; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _marks__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./marks */ "./apps/text/src/marks/index.js");
/* harmony import */ var _nodes__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./nodes */ "./apps/text/src/nodes/index.js");
!(function webpackMissingModule() { var e = new Error("Cannot find module 'markdown-it'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'markdown-it-task-lists'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-markdown'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _iterableToArrayLimit(arr, i) { if (typeof Symbol === "undefined" || !(Symbol.iterator in Object(arr))) return; var _arr = []; var _n = true; var _d = false; var _e = undefined; try { for (var _i = arr[Symbol.iterator](), _s; !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(n); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && Symbol.iterator in Object(iter)) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */








var loadSyntaxHighlight = /*#__PURE__*/function () {
  var _ref = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(language) {
    var languages, modules, i, lang;
    return regeneratorRuntime.wrap(function _callee$(_context) {
      while (1) {
        switch (_context.prev = _context.next) {
          case 0:
            languages = [language];
            modules = {};
            i = 0;

          case 3:
            if (!(i < languages.length)) {
              _context.next = 17;
              break;
            }

            _context.prev = 4;
            _context.next = 7;
            return !(function webpackMissingModule() { var e = new Error("Cannot find module 'undefined'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());

          case 7:
            lang = _context.sent;
            modules[languages[i]] = lang.default;
            _context.next = 14;
            break;

          case 11:
            _context.prev = 11;
            _context.t0 = _context["catch"](4);
            return _context.abrupt("return", undefined);

          case 14:
            i++;
            _context.next = 3;
            break;

          case 17:
            if (!(Object.keys(modules).length === 0 && modules.constructor === Object)) {
              _context.next = 19;
              break;
            }

            return _context.abrupt("return", undefined);

          case 19:
            return _context.abrupt("return", {
              languages: modules
            });

          case 20:
          case "end":
            return _context.stop();
        }
      }
    }, _callee, null, [[4, 11]]);
  }));

  return function loadSyntaxHighlight(_x) {
    return _ref.apply(this, arguments);
  };
}();

var createEditor = function createEditor(_ref2) {
  var content = _ref2.content,
      onInit = _ref2.onInit,
      onUpdate = _ref2.onUpdate,
      extensions = _ref2.extensions,
      enableRichEditing = _ref2.enableRichEditing,
      languages = _ref2.languages;
  var richEditingExtensions = [];

  if (enableRichEditing) {
    richEditingExtensions = [new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new _marks__WEBPACK_IMPORTED_MODULE_1__["Strong"](), new _marks__WEBPACK_IMPORTED_MODULE_1__["Italic"](), new _marks__WEBPACK_IMPORTED_MODULE_1__["Strike"](), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new _nodes__WEBPACK_IMPORTED_MODULE_2__["ListItem"](), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new _nodes__WEBPACK_IMPORTED_MODULE_2__["Image"](), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())({
      emptyNodeClass: 'is-empty',
      emptyNodeText: 'Add notes, lists or links …',
      showOnlyWhenEditable: true
    })];
  } else {
    richEditingExtensions = [new _nodes__WEBPACK_IMPORTED_MODULE_2__["PlainTextDocument"](), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(), new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(_objectSpread({}, languages))];
  }

  extensions = extensions || [];
  return new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())({
    content: content,
    onInit: onInit,
    onUpdate: onUpdate,
    extensions: [].concat(_toConsumableArray(richEditingExtensions), [new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())()]).concat(extensions),
    useBuiltInExtensions: enableRichEditing
  });
};

var markdownit = !(function webpackMissingModule() { var e = new Error("Cannot find module 'markdown-it'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())('commonmark', {
  html: false,
  breaks: false
}).enable('strikethrough').use(!(function webpackMissingModule() { var e = new Error("Cannot find module 'markdown-it-task-lists'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()), {
  enable: true,
  labelAfter: true
});

var SerializeException = function SerializeException(message) {
  this.message = message;
};

var createMarkdownSerializer = function createMarkdownSerializer(_nodes, _marks) {
  var nodes = Object.entries(_nodes).filter(function (_ref3) {
    var _ref4 = _slicedToArray(_ref3, 2),
        node = _ref4[1];

    return node.toMarkdown;
  }).reduce(function (items, _ref5) {
    var _ref6 = _slicedToArray(_ref5, 2),
        name = _ref6[0],
        toMarkdown = _ref6[1].toMarkdown;

    return _objectSpread({}, items, _defineProperty({}, name, toMarkdown));
  }, {});
  var marks = Object.entries(_marks).filter(function (_ref7) {
    var _ref8 = _slicedToArray(_ref7, 2),
        node = _ref8[1];

    return node.toMarkdown;
  }).reduce(function (items, _ref9) {
    var _ref10 = _slicedToArray(_ref9, 2),
        name = _ref10[0],
        toMarkdown = _ref10[1].toMarkdown;

    return _objectSpread({}, items, _defineProperty({}, name, toMarkdown));
  }, {});
  return {
    serializer: new !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-markdown'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(_objectSpread({}, !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-markdown'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()).nodes, {}, nodes), _objectSpread({}, !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-markdown'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()).marks, {}, marks)),
    serialize: function serialize(content, options) {
      return this.serializer.serialize(content, _objectSpread({}, options, {
        tightLists: true
      })).split('\\[').join('[').split('\\]').join(']');
    }
  };
};

var serializePlainText = function serializePlainText(tiptap) {
  var doc = tiptap.getJSON();

  if (doc.content.length !== 1 || typeof doc.content[0].content === 'undefined' || doc.content[0].content.length !== 1) {
    if (doc.content[0].type === 'code_block' && typeof doc.content[0].content === 'undefined') {
      return '';
    }

    throw new SerializeException('Failed to serialize document to plain text');
  }

  var codeBlock = doc.content[0].content[0];

  if (codeBlock.type !== 'text') {
    throw new SerializeException('Failed to serialize document to plain text');
  }

  return codeBlock.text;
};

/* harmony default export */ __webpack_exports__["default"] = (createEditor);


/***/ }),

/***/ "./apps/text/src/components/CollisionResolveDialog.vue":
/*!*************************************************************!*\
  !*** ./apps/text/src/components/CollisionResolveDialog.vue ***!
  \*************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true& */ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true&");
/* harmony import */ var _CollisionResolveDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CollisionResolveDialog.vue?vue&type=script&lang=js& */ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& */ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _CollisionResolveDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "a0d25866",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/text/src/components/CollisionResolveDialog.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js&":
/*!**************************************************************************************!*\
  !*** ./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js& ***!
  \**************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollisionResolveDialog.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&":
/*!***********************************************************************************************************************!*\
  !*** ./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& ***!
  \***********************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_style_index_0_id_a0d25866_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true&":
/*!********************************************************************************************************!*\
  !*** ./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true& ***!
  \********************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CollisionResolveDialog_vue_vue_type_template_id_a0d25866_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./apps/text/src/components/EditorWrapper.vue":
/*!****************************************************!*\
  !*** ./apps/text/src/components/EditorWrapper.vue ***!
  \****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true& */ "./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true&");
/* harmony import */ var _EditorWrapper_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./EditorWrapper.vue?vue&type=script&lang=js& */ "./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& */ "./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&");
/* harmony import */ var _EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./EditorWrapper.vue?vue&type=style&index=1&lang=scss& */ "./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");







/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _EditorWrapper_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "782c4aaa",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/text/src/components/EditorWrapper.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js&":
/*!*****************************************************************************!*\
  !*** ./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&":
/*!**************************************************************************************************************!*\
  !*** ./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& ***!
  \**************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_0_id_782c4aaa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&":
/*!**************************************************************************************!*\
  !*** ./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss& ***!
  \**************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=style&index=1&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true&":
/*!***********************************************************************************************!*\
  !*** ./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true& ***!
  \***********************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_EditorWrapper_vue_vue_type_template_id_782c4aaa_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./apps/text/src/components/ReadOnlyEditor.vue":
/*!*****************************************************!*\
  !*** ./apps/text/src/components/ReadOnlyEditor.vue ***!
  \*****************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ReadOnlyEditor.vue?vue&type=template&id=a279db0c& */ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c&");
/* harmony import */ var _ReadOnlyEditor_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ReadOnlyEditor.vue?vue&type=script&lang=js& */ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& */ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&");
/* harmony import */ var _ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& */ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");







/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _ReadOnlyEditor_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__["render"],
  _ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/text/src/components/ReadOnlyEditor.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js&":
/*!******************************************************************************!*\
  !*** ./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js& ***!
  \******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&":
/*!***************************************************************************************!*\
  !*** ./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& ***!
  \***************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&":
/*!***************************************************************************************!*\
  !*** ./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& ***!
  \***************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_style_index_1_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c&":
/*!************************************************************************************!*\
  !*** ./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c& ***!
  \************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=template&id=a279db0c& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ReadOnlyEditor_vue_vue_type_template_id_a279db0c___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./apps/text/src/extensions/Keymap.js":
/*!********************************************!*\
  !*** ./apps/text/src/extensions/Keymap.js ***!
  \********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Keymap; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */


var Keymap = /*#__PURE__*/function (_Extension) {
  _inherits(Keymap, _Extension);

  function Keymap() {
    _classCallCheck(this, Keymap);

    return _possibleConstructorReturn(this, _getPrototypeOf(Keymap).apply(this, arguments));
  }

  _createClass(Keymap, [{
    key: "keys",
    value: function keys(_ref) {
      var schema = _ref.schema;
      return this.options;
    }
  }, {
    key: "name",
    get: function get() {
      return 'save';
    }
  }]);

  return Keymap;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));



/***/ }),

/***/ "./apps/text/src/extensions/index.js":
/*!*******************************************!*\
  !*** ./apps/text/src/extensions/index.js ***!
  \*******************************************/
/*! exports provided: Keymap */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Keymap__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Keymap */ "./apps/text/src/extensions/Keymap.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Keymap", function() { return _Keymap__WEBPACK_IMPORTED_MODULE_0__["default"]; });

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



/***/ }),

/***/ "./apps/text/src/helpers/index.js":
/*!****************************************!*\
  !*** ./apps/text/src/helpers/index.js ***!
  \****************************************/
/*! exports provided: documentReady, endpointUrl, getRandomGuestName */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "documentReady", function() { return documentReady; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "endpointUrl", function() { return endpointUrl; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "getRandomGuestName", function() { return getRandomGuestName; });
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__);
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Callback that should be executed after the document is ready
 * @param callback
 */


var documentReady = function documentReady(callback) {
  var fn = function fn() {
    return setTimeout(callback, 0);
  };

  if (document.attachEvent ? document.readyState === 'complete' : document.readyState !== 'loading') {
    fn();
  } else {
    document.addEventListener('DOMContentLoaded', callback);
  }
};

var _baseUrl = Object(_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__["generateUrl"])('/apps/text');

var endpointUrl = function endpointUrl(endpoint) {
  var isPublic = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : false;

  if (isPublic) {
    return "".concat(_baseUrl, "/public/").concat(endpoint);
  }

  return "".concat(_baseUrl, "/").concat(endpoint);
};

var randomGuestNames = ['Artichoke', 'Arugula', 'Asparagus', 'Avocado', 'Bamboo Shoot', 'Bean Sprout', 'Bean', 'Beet', 'Belgian Endive', 'Bell Pepper', 'Bitter Melon', 'Bitter Gourd', 'Bok Choy', 'Broccoli', 'Brussels Sprout', 'Burdock Root', 'Cabbage', 'Calabash', 'Caper', 'Carrot', 'Cassava', 'Cauliflower', 'Celery', 'Celery Root', 'Celtuce', 'Chayote', 'Chinese Broccoli', 'Corn', 'Baby Corn', 'Cucumber', 'English Cucumber', 'Gherkin', 'Pickling Cucumber', 'Daikon Radish', 'Edamame', 'Eggplant', 'Elephant Garlic', 'Endive', 'Curly', 'Escarole', 'Fennel', 'Fiddlehead', 'Galangal', 'Garlic', 'Ginger', 'Grape Leave', 'Green Bean', 'Wax Bean', 'Green', 'Amaranth Leave', 'Beet Green', 'Collard Green', 'Dandelion Green', 'Kale', 'Kohlrabi Green', 'Mustard Green', 'Rapini', 'Spinach', 'Swiss Chard', 'Turnip Green', 'Hearts of Palm', 'Horseradish', 'Jerusalem Artichoke', 'Jícama', 'Kale', 'Curly', 'Lacinato', 'Ornamental', 'Kohlrabi', 'Leeks', 'Lemongrass', 'Lettuce', 'Butterhead', 'Iceberg', 'Leaf', 'Romaine', 'Lotus Root', 'Lotus Seed', 'Mushroom', 'Napa Cabbage', 'Nopales', 'Okra', 'Olive', 'Onion', 'Green Onion', 'Parsley', 'Parsley Root', 'Parsnip', 'Pepper', 'Plantain', 'Potato', 'Pumpkin', 'Purslane', 'Radicchio', 'Radish', 'Rutabaga', 'Shallots', 'Spinach', 'Squash', 'Sweet Potato', 'Swiss Chard', 'Taro', 'Tomatillo', 'Tomato', 'Turnip', 'Water Chestnut', 'Water Spinach', 'Watercress', 'Winter Melon', 'Yams', 'Zucchini'];

var getRandomGuestName = function getRandomGuestName() {
  return randomGuestNames[Math.floor(Math.random() * randomGuestNames.length)];
};



/***/ }),

/***/ "./apps/text/src/helpers/mappings.js":
/*!*******************************************!*\
  !*** ./apps/text/src/helpers/mappings.js ***!
  \*******************************************/
/*! exports provided: default, extensionHighlight */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "extensionHighlight", function() { return extensionHighlight; });
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
var extensionHighlight = {
  py: 'python',
  gyp: 'python',
  wsgi: 'python',
  htm: 'html',
  xhtml: 'html',
  erl: 'erlang',
  jsp: 'java',
  pl: 'perl',
  rss: 'xml',
  atom: 'xml',
  xsl: 'xml',
  plist: 'xml',
  rb: 'ruby',
  builder: 'ruby',
  gemspec: 'ruby',
  podspec: 'ruby',
  thor: 'ruby',
  diff: 'patch',
  hs: 'haskell',
  icl: 'haskell',
  php3: 'php',
  php4: 'php',
  php5: 'php',
  php6: 'php',
  sh: 'bash',
  zsh: 'bash',
  st: 'smalltalk',
  as: 'actionscript',
  apacheconf: 'apache',
  osacript: 'applescript',
  b: 'brainfuck',
  bf: 'brainfuck',
  clj: 'clojure',
  'cmake.in': 'cmake',
  coffee: 'coffeescript',
  cson: 'coffescript',
  iced: 'coffescript',
  c: 'cpp',
  'c++': 'cpp',
  'h++': 'cpp',
  hh: 'cpp',
  jinja: 'django',
  bat: 'dos',
  cmd: 'dos',
  fs: 'fsharp',
  hbs: 'handlebars',
  'html.hbs': 'handlebars',
  'html.handlebars': 'handlebars',
  'sublime_metrics': 'json',
  'sublime_session': 'json',
  'sublime-keymap': 'json',
  'sublime-mousemap': 'json',
  'sublime-project': 'json',
  'sublime-settings': 'json',
  'sublime-workspace': 'json',
  mk: 'makefile',
  mak: 'makefile',
  md: 'markdown',
  mkdown: 'markdown',
  mkd: 'markdown',
  nginxconf: 'nginx',
  m: 'objectivec',
  mm: 'objectivec',
  ml: 'ocaml',
  rs: 'rust',
  sci: 'scilab',
  vb: 'vbnet',
  vbs: 'vbscript'
};
/* harmony default export */ __webpack_exports__["default"] = (extensionHighlight);


/***/ }),

/***/ "./apps/text/src/marks/index.js":
/*!**************************************!*\
  !*** ./apps/text/src/marks/index.js ***!
  \**************************************/
/*! exports provided: Strong, Italic, Strike */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Strong", function() { return Strong; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Italic", function() { return Italic; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "Strike", function() { return Strike; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This file maps prosemirror mark names to tiptap classes,
 * so we can reuse the prosemirror-markdown default parser for now
 */

var Strong = /*#__PURE__*/function (_Bold) {
  _inherits(Strong, _Bold);

  function Strong() {
    _classCallCheck(this, Strong);

    return _possibleConstructorReturn(this, _getPrototypeOf(Strong).apply(this, arguments));
  }

  _createClass(Strong, [{
    key: "name",
    get: function get() {
      return 'strong';
    }
  }]);

  return Strong;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));

var Italic = /*#__PURE__*/function (_TipTapItalic) {
  _inherits(Italic, _TipTapItalic);

  function Italic() {
    _classCallCheck(this, Italic);

    return _possibleConstructorReturn(this, _getPrototypeOf(Italic).apply(this, arguments));
  }

  _createClass(Italic, [{
    key: "name",
    get: function get() {
      return 'em';
    }
  }]);

  return Italic;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));

var Strike = /*#__PURE__*/function (_TipTapStrike) {
  _inherits(Strike, _TipTapStrike);

  function Strike() {
    _classCallCheck(this, Strike);

    return _possibleConstructorReturn(this, _getPrototypeOf(Strike).apply(this, arguments));
  }

  _createClass(Strike, [{
    key: "schema",
    get: function get() {
      return {
        parseDOM: [{
          tag: 's'
        }, {
          tag: 'del'
        }, {
          tag: 'strike'
        }, {
          style: 'text-decoration',
          getAttrs: function getAttrs(value) {
            return value === 'line-through';
          }
        }],
        toDOM: function toDOM() {
          return ['s', 0];
        },
        toMarkdown: {
          open: '~~',
          close: '~~',
          mixable: true,
          expelEnclosingWhitespace: true
        }
      };
    }
  }]);

  return Strike;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));
/** Strike is currently unsupported by prosemirror-markdown */




/***/ }),

/***/ "./apps/text/src/mixins/isMobile.js":
/*!******************************************!*\
  !*** ./apps/text/src/mixins/isMobile.js ***!
  \******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
/* harmony default export */ __webpack_exports__["default"] = ({
  data: function data() {
    return {
      isMobile: this._isMobile()
    };
  },
  beforeMount: function beforeMount() {
    window.addEventListener('resize', this._onResize);
  },
  beforeDestroy: function beforeDestroy() {
    window.removeEventListener('resize', this._onResize);
  },
  methods: {
    _onResize: function _onResize() {
      // Update mobile mode
      this.isMobile = this._isMobile();
    },
    _isMobile: function _isMobile() {
      // check if content width is under 768px
      return document.documentElement.clientWidth < 768;
    }
  }
});

/***/ }),

/***/ "./apps/text/src/nodes/Image.js":
/*!**************************************!*\
  !*** ./apps/text/src/nodes/Image.js ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return Image; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _ImageView__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ImageView */ "./apps/text/src/nodes/ImageView.vue");
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _get(target, property, receiver) { if (typeof Reflect !== "undefined" && Reflect.get) { _get = Reflect.get; } else { _get = function _get(target, property, receiver) { var base = _superPropBase(target, property); if (!base) return; var desc = Object.getOwnPropertyDescriptor(base, property); if (desc.get) { return desc.get.call(receiver); } return desc.value; }; } return _get(target, property, receiver || target); }

function _superPropBase(object, property) { while (!Object.prototype.hasOwnProperty.call(object, property)) { object = _getPrototypeOf(object); if (object === null) break; } return object; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



var Image = /*#__PURE__*/function (_TiptapImage) {
  _inherits(Image, _TiptapImage);

  function Image() {
    _classCallCheck(this, Image);

    return _possibleConstructorReturn(this, _getPrototypeOf(Image).apply(this, arguments));
  }

  _createClass(Image, [{
    key: "view",
    get: function get() {
      return _ImageView__WEBPACK_IMPORTED_MODULE_1__["default"];
    }
  }, {
    key: "schema",
    get: function get() {
      return _objectSpread({}, _get(_getPrototypeOf(Image.prototype), "schema", this), {
        selectable: false
      });
    }
  }]);

  return Image;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));



/***/ }),

/***/ "./apps/text/src/nodes/ImageView.vue":
/*!*******************************************!*\
  !*** ./apps/text/src/nodes/ImageView.vue ***!
  \*******************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ImageView.vue?vue&type=template&id=38673bf4&scoped=true& */ "./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true&");
/* harmony import */ var _ImageView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ImageView.vue?vue&type=script&lang=js& */ "./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& */ "./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");






/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ImageView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"],
  _ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  "38673bf4",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/text/src/nodes/ImageView.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js&":
/*!********************************************************************!*\
  !*** ./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js& ***!
  \********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./ImageView.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&":
/*!*****************************************************************************************************!*\
  !*** ./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& ***!
  \*****************************************************************************************************/
/*! no static exports found */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-style-loader!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& */ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__);
/* harmony reexport (unknown) */ for(var __WEBPACK_IMPORT_KEY__ in _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__) if(__WEBPACK_IMPORT_KEY__ !== 'default') (function(key) { __webpack_require__.d(__webpack_exports__, key, function() { return _node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__[key]; }) }(__WEBPACK_IMPORT_KEY__));
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_vue_style_loader_index_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_style_index_0_id_38673bf4_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0___default.a); 

/***/ }),

/***/ "./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true&":
/*!**************************************************************************************!*\
  !*** ./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true& ***!
  \**************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./ImageView.vue?vue&type=template&id=38673bf4&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ImageView_vue_vue_type_template_id_38673bf4_scoped_true___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./apps/text/src/nodes/ListItem.js":
/*!*****************************************!*\
  !*** ./apps/text/src/nodes/ListItem.js ***!
  \*****************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return ListItem; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-commands'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-utils'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */




var TYPES = {
  BULLET: 0,
  CHECKBOX: 1
};

var getParentList = function getParentList(schema, selection) {
  return !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-utils'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(function (node) {
    return node.type === schema.nodes.list_item;
  })(selection);
};

var ListItem = /*#__PURE__*/function (_TiptapListItem) {
  _inherits(ListItem, _TiptapListItem);

  function ListItem() {
    _classCallCheck(this, ListItem);

    return _possibleConstructorReturn(this, _getPrototypeOf(ListItem).apply(this, arguments));
  }

  _createClass(ListItem, [{
    key: "commands",
    value: function commands(_ref) {
      var type = _ref.type,
          schema = _ref.schema;
      return {
        'bullet_list_item': function bullet_list_item() {
          return function (state, dispatch, view) {
            return !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-commands'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(schema.nodes.bullet_list, type)(state, dispatch, view);
          };
        },
        'todo_item': function todo_item() {
          return function (state, dispatch, view) {
            var schema = state.schema;
            var selection = state.selection;
            var $from = selection.$from;
            var $to = selection.$to;
            var range = $from.blockRange($to);
            var tr = state.tr;
            var parentList = getParentList(schema, selection);

            if (typeof parentList === 'undefined') {
              !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-commands'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(schema.nodes.bullet_list, type)(state, function (_transaction) {
                tr = _transaction;
              }, view);
              parentList = getParentList(schema, tr.selection);
            }

            if (!range || typeof parentList === 'undefined') {
              return false;
            }

            tr.setNodeMarkup(parentList.pos, schema.nodes.list_item, {
              type: parentList.node.attrs.type === TYPES.CHECKBOX ? TYPES.BULLET : TYPES.CHECKBOX
            });
            tr.scrollIntoView();

            if (dispatch) {
              dispatch(tr);
            }
          };
        }
      };
    }
  }, {
    key: "defaultOptions",
    get: function get() {
      return {
        nested: true
      };
    }
  }, {
    key: "schema",
    get: function get() {
      return {
        attrs: {
          done: {
            default: false
          },
          type: {
            default: TYPES.BULLET
          }
        },
        draggable: true,
        content: 'paragraph block*',
        toDOM: function toDOM(node) {
          if (node.attrs.type === TYPES.BULLET) {
            return ['li', 0];
          }

          var checkboxAttributes = {
            type: 'checkbox',
            class: 'checkbox'
          };

          if (node.attrs.done) {
            checkboxAttributes.checked = true;
          }

          return ['li', ['input', checkboxAttributes], ['label', {
            class: 'checkbox-label'
          }, ['div', {
            class: 'checkbox-wrapper'
          }, 0]]];
        },
        parseDOM: [{
          priority: 100,
          tag: 'li',
          getAttrs: function getAttrs(el) {
            var checkbox = el.querySelector('input[type=checkbox]');
            return {
              done: checkbox && checkbox.checked,
              type: checkbox ? TYPES.CHECKBOX : TYPES.BULLET
            };
          }
        }],
        toMarkdown: function toMarkdown(state, node) {
          if (node.attrs.type === TYPES.CHECKBOX) {
            state.write("[".concat(node.attrs.done ? 'x' : ' ', "] "));
          }

          state.renderContent(node);
        }
      };
    }
  }, {
    key: "plugins",
    get: function get() {
      return [new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())({
        props: {
          handleClick: function handleClick(view, pos, event) {
            var state = view.state;
            var schema = state.schema;
            var selection = state.selection;
            var $from = selection.$from;
            var $to = selection.$to;
            var range = $from.blockRange($to);

            if (!range) {
              return false;
            }

            var parentList = !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-utils'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(function (node) {
              return node.type === schema.nodes.list_item;
            })(selection);
            var isLabel = event.target.tagName.toLowerCase() === 'label';

            if (typeof parentList === 'undefined' || parentList.node.attrs.type !== TYPES.CHECKBOX || !isLabel) {
              return;
            }

            var tr = state.tr;
            tr.setNodeMarkup(parentList.pos, schema.nodes.list_item, {
              done: !parentList.node.attrs.done,
              type: TYPES.CHECKBOX
            });
            view.dispatch(tr);
          }
        }
      })];
    }
  }]);

  return ListItem;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));



/***/ }),

/***/ "./apps/text/src/nodes/PlainTextDocument.js":
/*!**************************************************!*\
  !*** ./apps/text/src/nodes/PlainTextDocument.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "default", function() { return PlainTextDocument; });
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-commands'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */



var PlainTextDocument = /*#__PURE__*/function (_Node) {
  _inherits(PlainTextDocument, _Node);

  function PlainTextDocument() {
    _classCallCheck(this, PlainTextDocument);

    return _possibleConstructorReturn(this, _getPrototypeOf(PlainTextDocument).apply(this, arguments));
  }

  _createClass(PlainTextDocument, [{
    key: "keys",
    value: function keys() {
      var _this = this;

      return {
        Tab: function Tab(state) {
          !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-commands'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())('\t')(state, _this.editor.view.dispatch, _this.editor.view);
          return true;
        }
      };
    }
  }, {
    key: "name",
    get: function get() {
      return 'doc';
    }
  }, {
    key: "schema",
    get: function get() {
      return {
        content: 'block'
      };
    }
  }]);

  return PlainTextDocument;
}(!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()));



/***/ }),

/***/ "./apps/text/src/nodes/index.js":
/*!**************************************!*\
  !*** ./apps/text/src/nodes/index.js ***!
  \**************************************/
/*! exports provided: Image, PlainTextDocument, ListItem */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Image__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Image */ "./apps/text/src/nodes/Image.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "Image", function() { return _Image__WEBPACK_IMPORTED_MODULE_0__["default"]; });

/* harmony import */ var _PlainTextDocument__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PlainTextDocument */ "./apps/text/src/nodes/PlainTextDocument.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "PlainTextDocument", function() { return _PlainTextDocument__WEBPACK_IMPORTED_MODULE_1__["default"]; });

/* harmony import */ var _ListItem__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ListItem */ "./apps/text/src/nodes/ListItem.js");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "ListItem", function() { return _ListItem__WEBPACK_IMPORTED_MODULE_2__["default"]; });

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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */





/***/ }),

/***/ "./apps/text/src/services/PollingBackend.js":
/*!**************************************************!*\
  !*** ./apps/text/src/services/PollingBackend.js ***!
  \**************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _helpers__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../helpers */ "./apps/text/src/helpers/index.js");
/* harmony import */ var _SyncService__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SyncService */ "./apps/text/src/services/SyncService.js");
!(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-collab'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

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




/**
 * Minimum inverval to refetch the document changes
 * @type {number}
 */

var FETCH_INTERVAL = 300;
/**
 * Maximum interval between refetches of document state if multiple users have joined
 * @type {number}
 */

var FETCH_INTERVAL_MAX = 5000;
/**
 * Interval to check for changes when there is only one user joined
 * @type {number}
 */

var FETCH_INTERVAL_SINGLE_EDITOR = 5000;
var MIN_PUSH_RETRY = 500;
var MAX_PUSH_RETRY = 10000;
/* Timeout after that a PUSH_FAILURE error is emitted */

var WARNING_PUSH_RETRY = 5000;
/* Maximum number of retries for fetching before emitting a connection error */

var MAX_RETRY_FETCH_COUNT = 5;
/* Timeout for sessions to be marked as disconnected */

var COLLABORATOR_DISCONNECT_TIME = 20;

var PollingBackend = /*#__PURE__*/function () {
  function PollingBackend(authority) {
    _classCallCheck(this, PollingBackend);

    /** @type SyncService */
    this._authority = authority;
    this.fetchInterval = FETCH_INTERVAL;
    this.retryTime = MIN_PUSH_RETRY;
    this.lock = false;
    this.fetchRetryCounter = 0;
  }

  _createClass(PollingBackend, [{
    key: "connect",
    value: function connect() {
      this.fetcher = setInterval(this._fetchSteps.bind(this), 0);
    }
  }, {
    key: "_isPublic",
    value: function _isPublic() {
      return !!this._authority.options.shareToken;
    }
  }, {
    key: "forceSave",
    value: function forceSave() {
      this._forcedSave = true;
      this.fetchSteps();
    }
  }, {
    key: "save",
    value: function save() {
      this._manualSave = true;
      this.fetchSteps();
    }
  }, {
    key: "fetchSteps",
    value: function fetchSteps() {
      this._fetchSteps();
    }
    /**
     * This method is only called though the timer
     */

  }, {
    key: "_fetchSteps",
    value: function _fetchSteps() {
      var _this = this;

      if (this.lock || !this.fetcher) {
        return;
      }

      this.lock = true;
      var autosaveContent;

      if (this._forcedSave || this._manualSave || !!(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-collab'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(this._authority.state) && this._authority._getVersion() !== this._authority.document.lastSavedVersion) {
        autosaveContent = this._authority._getContent();
      }

      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.post(Object(_helpers__WEBPACK_IMPORTED_MODULE_1__["endpointUrl"])('session/sync', this._isPublic()), {
        documentId: this._authority.document.id,
        sessionId: this._authority.session.id,
        sessionToken: this._authority.session.token,
        version: this._authority._getVersion(),
        autosaveContent: autosaveContent,
        force: !!this._forcedSave,
        manualSave: !!this._manualSave,
        token: this._authority.options.shareToken,
        filePath: this._authority.options.filePath
      }).then(function (response) {
        _this.fetchRetryCounter = 0;

        if (_this._authority.document.lastSavedVersion < response.data.document.lastSavedVersion) {
          console.debug('Saved document', response.data.document);

          _this._authority.emit('save', {
            document: response.data.document,
            sessions: response.data.sessions
          });
        }

        _this._authority.emit('change', {
          document: response.data.document,
          sessions: response.data.sessions
        });

        _this._authority.document = response.data.document;
        _this._authority.sessions = response.data.sessions;

        if (response.data.steps.length === 0) {
          _this.lock = false;

          if (response.data.sessions.filter(function (session) {
            return session.lastContact > Date.now() / 1000 - COLLABORATOR_DISCONNECT_TIME;
          }).length < 2) {
            _this.maximumRefetchTimer();
          } else {
            _this.increaseRefetchTimer();
          }

          _this._authority.emit('stateChange', {
            dirty: false
          });

          _this._authority.emit('stateChange', {
            initialLoading: true
          });

          return;
        }

        _this._authority._receiveSteps(response.data);

        _this.lock = false;
        _this._forcedSave = false;

        _this.resetRefetchTimer();
      }).catch(function (e) {
        _this.lock = false;

        if (!e.response || e.code === 'ECONNABORTED') {
          if (_this.fetchRetryCounter++ >= MAX_RETRY_FETCH_COUNT) {
            console.error('[PollingBackend:fetchSteps] Network error when fetching steps, emitting CONNECTION_FAILED');

            _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED, {});
          } else {
            console.error("[PollingBackend:fetchSteps] Network error when fetching steps, retry ".concat(_this.fetchRetryCounter));
          }
        } else if (e.response.status === 409 && e.response.data.document.currentVersion === _this._authority.document.currentVersion) {
          // Only emit conflict event if we have synced until the latest version
          console.error('Conflict during file save, please resolve');

          _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SAVE_COLLISSION, {
            outsideChange: e.response.data.outsideChange
          });
        } else if (e.response.status === 403) {
          _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED, {});
        } else if (e.response.status === 404) {
          _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SOURCE_NOT_FOUND, {});
        } else if (e.response.status === 503) {
          _this.increaseRefetchTimer();

          _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED, {
            retry: true
          });

          console.error('Failed to fetch steps due to unavailable service', e);
        } else {
          _this.increaseRefetchTimer();

          _this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED, {
            retry: false
          });

          console.error('Failed to fetch steps due to other reason', e);
        }
      });
      this._manualSave = false;
      this._forcedSave = false;
    }
  }, {
    key: "sendSteps",
    value: function sendSteps(_sendable) {
      var _this2 = this;

      this._authority.emit('stateChange', {
        dirty: true
      });

      if (this.lock) {
        setTimeout(function () {
          _this2._authority.sendSteps();
        }, 100);
        return;
      }

      this.lock = true;
      var sendable = typeof _sendable === 'function' ? _sendable() : _sendable;
      var steps = sendable.steps;
      _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.post(Object(_helpers__WEBPACK_IMPORTED_MODULE_1__["endpointUrl"])('session/push', !!this._authority.options.shareToken), {
        documentId: this._authority.document.id,
        sessionId: this._authority.session.id,
        sessionToken: this._authority.session.token,
        steps: steps.map(function (s) {
          return s.toJSON ? s.toJSON() : s;
        }) || [],
        version: sendable.version,
        token: this._authority.options.shareToken,
        filePath: this._authority.options.filePath
      }).then(function (response) {
        _this2.carefulRetryReset();

        _this2.lock = false;

        _this2.fetchSteps();
      }).catch(function (e) {
        console.error('failed to apply steps due to collission, retrying');
        _this2.lock = false;

        if (!e.response || e.code === 'ECONNABORTED') {
          _this2._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED, {});

          return;
        } else if (e.response.status === 403 && e.response.data.document.currentVersion === _this2._authority.document.currentVersion) {
          // Only emit conflict event if we have synced until the latest version
          _this2._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].PUSH_FAILURE, {});

          OC.Notification.showTemporary('Changes could not be sent yet');
        }

        _this2.fetchSteps();

        _this2.carefulRetry();
      });
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      clearInterval(this.fetcher);
      this.fetcher = 0;
    }
  }, {
    key: "resetRefetchTimer",
    value: function resetRefetchTimer() {
      if (this.fetcher === 0) {
        return;
      }

      this.fetchInterval = FETCH_INTERVAL;
      clearInterval(this.fetcher);
      this.fetcher = setInterval(this._fetchSteps.bind(this), this.fetchInterval);
    }
  }, {
    key: "increaseRefetchTimer",
    value: function increaseRefetchTimer() {
      if (this.fetcher === 0) {
        return;
      }

      this.fetchInterval = Math.min(this.fetchInterval * 2, FETCH_INTERVAL_MAX);
      clearInterval(this.fetcher);
      this.fetcher = setInterval(this._fetchSteps.bind(this), this.fetchInterval);
    }
  }, {
    key: "maximumRefetchTimer",
    value: function maximumRefetchTimer() {
      if (this.fetcher === 0) {
        return;
      }

      this.fetchInterval = FETCH_INTERVAL_SINGLE_EDITOR;
      clearInterval(this.fetcher);
      this.fetcher = setInterval(this._fetchSteps.bind(this), this.fetchInterval);
    }
  }, {
    key: "carefulRetry",
    value: function carefulRetry() {
      var newRetry = this.retryTime ? Math.min(this.retryTime * 2, MAX_PUSH_RETRY) : MIN_PUSH_RETRY;

      if (newRetry > WARNING_PUSH_RETRY && this.retryTime < WARNING_PUSH_RETRY) {
        OC.Notification.showTemporary('Changes could not be sent yet');

        this._authority.emit('error', _SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].PUSH_FAILURE, {});
      }

      this.retryTime = newRetry;
    }
  }, {
    key: "carefulRetryReset",
    value: function carefulRetryReset() {
      this.retryTime = MIN_PUSH_RETRY;
    }
  }]);

  return PollingBackend;
}();

/* harmony default export */ __webpack_exports__["default"] = (PollingBackend);

/***/ }),

/***/ "./apps/text/src/services/SyncService.js":
/*!***********************************************!*\
  !*** ./apps/text/src/services/SyncService.js ***!
  \***********************************************/
/*! exports provided: default, SyncService, ERROR_TYPE */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "SyncService", function() { return SyncService; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "ERROR_TYPE", function() { return ERROR_TYPE; });
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _PollingBackend__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PollingBackend */ "./apps/text/src/services/PollingBackend.js");
/* harmony import */ var _helpers__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./../helpers */ "./apps/text/src/helpers/index.js");
!(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-collab'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

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




var defaultOptions = {
  shareToken: null,
  forceRecreate: false,
  serialize: function serialize(document) {
    return document;
  }
};
var ERROR_TYPE = {
  /**
   * Failed to save collaborative document due to external change
   * collission needs to be resolved manually
   */
  SAVE_COLLISSION: 0,

  /**
   * Failed to push changes for MAX_REBASE_RETRY times
   */
  PUSH_FAILURE: 1,
  LOAD_ERROR: 2,
  CONNECTION_FAILED: 3,
  SOURCE_NOT_FOUND: 4
};

var SyncService = /*#__PURE__*/function () {
  function SyncService(options) {
    _classCallCheck(this, SyncService);

    this.eventHandlers = {
      /* Document state */
      opened: [],
      loaded: [],

      /* All initial steps fetched */
      fetched: [],

      /* received new steps */
      sync: [],

      /* state changed (dirty) */
      stateChange: [],

      /* error */
      error: [],

      /* Events for session and document meta data */
      change: [],

      /* Emitted after successful save */
      save: []
    };
    this.backend = new _PollingBackend__WEBPACK_IMPORTED_MODULE_1__["default"](this);
    this.options = Object.assign({}, defaultOptions, options);
    this.document = null;
    this.session = null;
    this.sessions = [];
    this.steps = [];
    this.stepClientIDs = [];
    return this;
  }

  _createClass(SyncService, [{
    key: "open",
    value: function () {
      var _open = _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee(_ref) {
        var _this2 = this;

        var fileId, filePath, initialSession, connectionData, response;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                fileId = _ref.fileId, filePath = _ref.filePath, initialSession = _ref.initialSession;
                connectionData = null;

                if (!(typeof initialSession === 'undefined')) {
                  _context.next = 16;
                  break;
                }

                _context.prev = 3;
                _context.next = 6;
                return this._openDocument({
                  fileId: fileId,
                  filePath: filePath
                });

              case 6:
                response = _context.sent;
                connectionData = response.data;
                _context.next = 14;
                break;

              case 10:
                _context.prev = 10;
                _context.t0 = _context["catch"](3);

                if (!_context.t0.response || _context.t0.code === 'ECONNABORTED') {
                  this.emit('error', ERROR_TYPE.CONNECTION_FAILED, {});
                } else {
                  this.emit('error', ERROR_TYPE.LOAD_ERROR, _context.t0.response.status);
                }

                throw _context.t0;

              case 14:
                _context.next = 17;
                break;

              case 16:
                connectionData = initialSession;

              case 17:
                this.document = connectionData.document;
                this.document.readOnly = connectionData.readOnly;
                this.session = connectionData.session;
                this.emit('opened', {
                  document: this.document,
                  session: this.session
                });
                return _context.abrupt("return", this._fetchDocument().then(function (_ref2) {
                  var data = _ref2.data;

                  _this2.emit('loaded', {
                    document: _this2.document,
                    session: _this2.session,
                    documentSource: '' + data
                  });
                }));

              case 22:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, this, [[3, 10]]);
      }));

      function open(_x) {
        return _open.apply(this, arguments);
      }

      return open;
    }()
  }, {
    key: "startSync",
    value: function startSync() {
      this.backend.connect();
    }
  }, {
    key: "_openDocument",
    value: function _openDocument(_ref3) {
      var fileId = _ref3.fileId,
          filePath = _ref3.filePath;
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.get(Object(_helpers__WEBPACK_IMPORTED_MODULE_2__["endpointUrl"])('session/create', !!this.options.shareToken), {
        params: {
          fileId: fileId,
          filePath: filePath,
          token: this.options.shareToken,
          guestName: this.options.guestName,
          forceRecreate: this.options.forceRecreate
        }
      });
    }
  }, {
    key: "_fetchDocument",
    value: function _fetchDocument() {
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.get(Object(_helpers__WEBPACK_IMPORTED_MODULE_2__["endpointUrl"])('session/fetch', !!this.options.shareToken), {
        transformResponse: [function (data) {
          return data;
        }],
        params: {
          documentId: this.document.id,
          sessionId: this.session.id,
          sessionToken: this.session.token,
          token: this.options.shareToken
        }
      });
    }
  }, {
    key: "updateSession",
    value: function updateSession(guestName) {
      var _this3 = this;

      if (!this.isPublic()) {
        return;
      }

      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.post(Object(_helpers__WEBPACK_IMPORTED_MODULE_2__["endpointUrl"])('session', !!this.options.shareToken), {
        documentId: this.document.id,
        sessionId: this.session.id,
        sessionToken: this.session.token,
        token: this.options.shareToken,
        guestName: guestName
      }).then(function (_ref4) {
        var data = _ref4.data;
        _this3.session = data;
        return data;
      }).catch(function (error) {
        console.error('Failed to update the session', error);
        return Promise.reject(error);
      });
    }
  }, {
    key: "sendSteps",
    value: function sendSteps(_sendable) {
      var sendable = _sendable || !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-collab'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(this.state);

      if (!sendable) {
        return;
      }

      return this.backend.sendSteps(sendable);
    }
  }, {
    key: "stepsSince",
    value: function stepsSince(version) {
      return {
        steps: this.steps.slice(version),
        clientIDs: this.stepClientIDs.slice(version)
      };
    }
  }, {
    key: "_receiveSteps",
    value: function _receiveSteps(_ref5) {
      var _this4 = this;

      var steps = _ref5.steps,
          document = _ref5.document;
      var newSteps = [];

      var _loop = function _loop(i) {
        var singleSteps = steps[i].data;

        if (!Array.isArray(singleSteps)) {
          console.error('Invalid step data, skipping step', steps[i]); // TODO: recover

          return "continue";
        }

        singleSteps.forEach(function (step) {
          _this4.steps.push(step);

          newSteps.push({
            step: step,
            clientID: steps[i].sessionId
          });
        });
      };

      for (var i = 0; i < steps.length; i++) {
        var _ret = _loop(i);

        if (_ret === "continue") continue;
      }

      this.emit('sync', {
        steps: newSteps,
        document: document
      });
      console.debug('receivedSteps', 'newVersion', this._getVersion());
    }
  }, {
    key: "_getVersion",
    value: function _getVersion() {
      if (this.state) {
        return !(function webpackMissingModule() { var e = new Error("Cannot find module 'prosemirror-collab'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())(this.state);
      }

      return 0;
    }
  }, {
    key: "_getDocument",
    value: function _getDocument() {
      if (this.state) {
        return this.state.doc;
      }
    }
  }, {
    key: "_getContent",
    value: function _getContent() {
      return this.options.serialize(this._getDocument());
    }
  }, {
    key: "save",
    value: function save() {
      if (this.backend.save) {
        this.backend.save();
      }
    }
  }, {
    key: "forceSave",
    value: function forceSave() {
      if (this.backend.forceSave) {
        this.backend.forceSave();
      }
    }
  }, {
    key: "close",
    value: function close() {
      var _this5 = this;

      var closed = false;
      return new Promise(function (resolve, reject) {
        _this5.on('save', function () {
          _this5._close().then(function () {
            closed = true;
            resolve();
          }).catch(function () {
            return resolve();
          });
        });

        setTimeout(function () {
          if (!closed) {
            _this5._close().then(function () {
              resolve();
            }).catch(function () {
              return resolve();
            });
          }
        }, 2000);

        _this5.save();
      });
    }
  }, {
    key: "_close",
    value: function _close() {
      if (this.document === null || this.session === null) {
        return Promise.resolve();
      }

      this.backend.disconnect();
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0___default.a.get(Object(_helpers__WEBPACK_IMPORTED_MODULE_2__["endpointUrl"])('session/close', !!this.options.shareToken), {
        params: {
          documentId: this.document.id,
          sessionId: this.session.id,
          sessionToken: this.session.token,
          token: this.options.shareToken
        }
      });
    }
  }, {
    key: "on",
    value: function on(event, callback, _this) {
      this.eventHandlers[event].push(callback.bind(_this));
      return this;
    }
  }, {
    key: "emit",
    value: function emit(event, data, additionalData) {
      if (typeof this.eventHandlers[event] !== 'undefined') {
        this.eventHandlers[event].forEach(function (callback) {
          callback(data, additionalData);
        });
      } else {
        console.error('Event not found', event);
      }
    }
  }, {
    key: "isPublic",
    value: function isPublic() {
      return !!this.options.shareToken;
    }
  }]);

  return SyncService;
}();

/* harmony default export */ __webpack_exports__["default"] = (SyncService);


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
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
//
//
//
//
//
//
//
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'CollisionResolveDialog'
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js&":
/*!***************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _services_SyncService__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./../services/SyncService */ "./apps/text/src/services/SyncService.js");
/* harmony import */ var _helpers__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./../helpers */ "./apps/text/src/helpers/index.js");
/* harmony import */ var _helpers_mappings__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../helpers/mappings */ "./apps/text/src/helpers/mappings.js");
/* harmony import */ var _EditorFactory__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./../EditorFactory */ "./apps/text/src/EditorFactory.js");
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var _extensions__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./../extensions */ "./apps/text/src/extensions/index.js");
/* harmony import */ var _mixins_isMobile__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./../mixins/isMobile */ "./apps/text/src/mixins/isMobile.js");
/* harmony import */ var _nextcloud_vue_dist_Directives_Tooltip__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Directives/Tooltip */ "./node_modules/@nextcloud/vue/dist/Directives/Tooltip.js");
/* harmony import */ var _nextcloud_vue_dist_Directives_Tooltip__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Directives_Tooltip__WEBPACK_IMPORTED_MODULE_9__);
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

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











var EDITOR_PUSH_DEBOUNCE = 200;
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'EditorWrapper',
  components: {
    EditorContent: !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }()),
    MenuBar: function MenuBar() {
      return __webpack_require__.e(/*! import() | editor-rich */ "editor-rich").then(__webpack_require__.bind(null, /*! ./MenuBar */ "./apps/text/src/components/MenuBar.vue"));
    },
    MenuBubble: function MenuBubble() {
      return __webpack_require__.e(/*! import() | editor-rich */ "editor-rich").then(__webpack_require__.bind(null, /*! ./MenuBubble */ "./apps/text/src/components/MenuBubble.vue"));
    },
    ReadOnlyEditor: function ReadOnlyEditor() {
      return __webpack_require__.e(/*! import() | editor */ "editor").then(__webpack_require__.bind(null, /*! ./ReadOnlyEditor */ "./apps/text/src/components/ReadOnlyEditor.vue"));
    },
    CollisionResolveDialog: function CollisionResolveDialog() {
      return __webpack_require__.e(/*! import() | editor */ "editor").then(__webpack_require__.bind(null, /*! ./CollisionResolveDialog */ "./apps/text/src/components/CollisionResolveDialog.vue"));
    },
    GuestNameDialog: function GuestNameDialog() {
      return Promise.all(/*! import() | editor-guest */[__webpack_require__.e("vendors-editor-collab-editor-guest"), __webpack_require__.e("editor-guest")]).then(__webpack_require__.bind(null, /*! ./GuestNameDialog */ "./apps/text/src/components/GuestNameDialog.vue"));
    },
    SessionList: function SessionList() {
      return Promise.all(/*! import() | editor-collab */[__webpack_require__.e("vendors-editor-collab-editor-guest"), __webpack_require__.e("editor-collab")]).then(__webpack_require__.bind(null, /*! ./SessionList */ "./apps/text/src/components/SessionList.vue"));
    }
  },
  directives: {
    Tooltip: _nextcloud_vue_dist_Directives_Tooltip__WEBPACK_IMPORTED_MODULE_9___default.a
  },
  mixins: [_mixins_isMobile__WEBPACK_IMPORTED_MODULE_8__["default"]],
  props: {
    initialSession: {
      type: Object,
      default: null
    },
    relativePath: {
      type: String,
      default: null
    },
    fileId: {
      type: Number,
      default: null
    },
    active: {
      type: Boolean,
      default: false
    },
    autofocus: {
      type: Boolean,
      default: true
    },
    shareToken: {
      type: String,
      default: null
    },
    mime: {
      type: String,
      default: null
    },
    autohide: {
      type: Boolean,
      default: false
    },
    isDirectEditing: {
      type: Boolean,
      default: false
    }
  },
  data: function data() {
    return {
      tiptap: null,

      /** @type SyncService */
      syncService: null,
      document: null,
      sessions: [],
      currentSession: null,
      filteredSessions: {},
      dirty: false,
      initialLoading: false,
      lastSavedString: '',
      syncError: null,
      hasConnectionIssue: false,
      readOnly: true,
      forceRecreate: false,
      saveStatusPolling: null
    };
  },
  computed: {
    lastSavedStatus: function lastSavedStatus() {
      var status = this.dirtyStateIndicator ? '*' : '';

      if (!this.isMobile) {
        status += this.lastSavedString;
      }

      return status;
    },
    lastSavedStatusClass: function lastSavedStatusClass() {
      return this.syncError && this.lastSavedString !== '' ? 'error' : '';
    },
    dirtyStateIndicator: function dirtyStateIndicator() {
      return this.hasUnpushedChanges || this.hasUnsavedChanges;
    },
    lastSavedStatusTooltip: function lastSavedStatusTooltip() {
      var message = t('text', 'Last saved {lastSaved}', {
        lastSaved: this.lastSavedString
      });

      if (this.hasSyncCollission) {
        message = t('text', 'The document has been changed outside of the editor. The changes cannot be applied.');
      }

      if (this.hasUnpushedChanges) {
        message += ' - ' + t('text', 'Unpushed changes');
      }

      if (this.hasUnsavedChanges) {
        message += ' - ' + t('text', 'Unsaved changes');
      }

      return {
        content: message,
        placement: 'bottom'
      };
    },
    hasSyncCollission: function hasSyncCollission() {
      return this.syncError && this.syncError.type === _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SAVE_COLLISSION;
    },
    hasUnpushedChanges: function hasUnpushedChanges() {
      return this.dirty;
    },
    hasUnsavedChanges: function hasUnsavedChanges() {
      return this.document && this.document.lastSavedVersion < this.document.currentVersion;
    },
    backendUrl: function backendUrl() {
      var _this = this;

      return function (endpoint) {
        return Object(_helpers__WEBPACK_IMPORTED_MODULE_3__["endpointUrl"])(endpoint, !!_this.shareToken);
      };
    },
    hasDocumentParameters: function hasDocumentParameters() {
      return this.fileId || this.shareToken || this.initialSession;
    },
    isPublic: function isPublic() {
      return this.isDirectEditing || document.getElementById('isPublic') && document.getElementById('isPublic').value === '1';
    },
    isRichEditor: function isRichEditor() {
      return this.mime === 'text/markdown';
    },
    fileExtension: function fileExtension() {
      return this.relativePath ? this.relativePath.split('/').pop().split('.').pop() : 'txt';
    }
  },
  watch: {
    lastSavedStatus: function lastSavedStatus() {
      this.$refs.menubar && this.$refs.menubar.redrawMenuBar();
    }
  },
  mounted: function mounted() {
    if (this.active && this.hasDocumentParameters) {
      this.initSession();
    }

    this.$parent.$emit('update:loaded', true);
  },
  created: function created() {
    var _this2 = this;

    this.saveStatusPolling = setInterval(function () {
      _this2.updateLastSavedStatus();
    }, 2000);
    document.addEventListener('keydown', this._keyUpHandler, true);
  },
  beforeDestroy: function beforeDestroy() {
    this.close();
  },
  methods: {
    close: function close() {
      var _this3 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                document.removeEventListener('keydown', _this3._keyUpHandler, true);
                clearInterval(_this3.saveStatusPolling);

                if (!(_this3.currentSession && _this3.syncService)) {
                  _context.next = 12;
                  break;
                }

                _context.prev = 3;
                _context.next = 6;
                return _this3.syncService.close();

              case 6:
                _this3.currentSession = null;
                _this3.syncService = null;
                _context.next = 12;
                break;

              case 10:
                _context.prev = 10;
                _context.t0 = _context["catch"](3);

              case 12:
                return _context.abrupt("return", true);

              case 13:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[3, 10]]);
      }))();
    },
    updateLastSavedStatus: function updateLastSavedStatus() {
      if (this.document) {
        this.lastSavedString = window.moment(this.document.lastSavedVersionTime * 1000).fromNow();
      }
    },
    initSession: function initSession() {
      var _this4 = this;

      if (!this.hasDocumentParameters) {
        this.$parent.$emit('error', 'No valid file provided');
        return;
      }

      var guestName = localStorage.getItem('nick') ? localStorage.getItem('nick') : Object(_helpers__WEBPACK_IMPORTED_MODULE_3__["getRandomGuestName"])();
      this.syncService = new _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["SyncService"]({
        shareToken: this.shareToken,
        filePath: this.relativePath,
        guestName: guestName,
        forceRecreate: this.forceRecreate,
        serialize: function serialize(document) {
          if (_this4.isRichEditor) {
            var markdown = Object(_EditorFactory__WEBPACK_IMPORTED_MODULE_5__["createMarkdownSerializer"])(_this4.tiptap.nodes, _this4.tiptap.marks).serialize(document);
            console.debug('serialized document', {
              markdown: markdown
            });
            return markdown;
          }

          var file = Object(_EditorFactory__WEBPACK_IMPORTED_MODULE_5__["serializePlainText"])(_this4.tiptap);
          console.debug('serialized document', {
            file: file
          });
          return file;
        }
      }).on('opened', function (_ref) {
        var document = _ref.document,
            session = _ref.session;
        _this4.currentSession = session;
        _this4.document = document;
        _this4.readOnly = document.readOnly;
        localStorage.setItem('nick', _this4.currentSession.guestName);
      }).on('change', function (_ref2) {
        var document = _ref2.document,
            sessions = _ref2.sessions;

        if (_this4.document.baseVersionEtag !== '' && document.baseVersionEtag !== _this4.document.baseVersionEtag) {
          _this4.resolveUseServerVersion();

          return;
        }

        _this4.updateSessions.bind(_this4)(sessions);

        _this4.document = document;
        _this4.syncError = null;

        _this4.tiptap.setOptions({
          editable: !_this4.readOnly
        });
      }).on('loaded', function (_ref3) {
        var documentSource = _ref3.documentSource;
        _this4.hasConnectionIssue = false;
        Object(_EditorFactory__WEBPACK_IMPORTED_MODULE_5__["loadSyntaxHighlight"])(_helpers_mappings__WEBPACK_IMPORTED_MODULE_4__["extensionHighlight"][_this4.fileExtension] ? _helpers_mappings__WEBPACK_IMPORTED_MODULE_4__["extensionHighlight"][_this4.fileExtension] : _this4.fileExtension).then(function (languages) {
          _this4.tiptap = Object(_EditorFactory__WEBPACK_IMPORTED_MODULE_5__["createEditor"])({
            content: _this4.isRichEditor ? _EditorFactory__WEBPACK_IMPORTED_MODULE_5__["markdownit"].render(documentSource) : '<pre>' + escape_html__WEBPACK_IMPORTED_MODULE_1___default()(documentSource) + '</pre>',
            onInit: function onInit(_ref4) {
              var state = _ref4.state;
              _this4.syncService.state = state;

              _this4.syncService.startSync();
            },
            onUpdate: function onUpdate(_ref5) {
              var state = _ref5.state;
              _this4.syncService.state = state;
            },
            extensions: [new !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap-extensions'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())({
              // the initial version we start with
              // version is an integer which is incremented with every change
              version: _this4.document.initialVersion,
              clientID: _this4.currentSession.id,
              // debounce changes so we can save some bandwidth
              debounce: EDITOR_PUSH_DEBOUNCE,
              onSendable: function onSendable(_ref6) {
                var sendable = _ref6.sendable;

                if (_this4.syncService) {
                  _this4.syncService.sendSteps();
                }
              }
            }), new _extensions__WEBPACK_IMPORTED_MODULE_7__["Keymap"]({
              'Ctrl-s': function CtrlS() {
                _this4.syncService.save();

                return true;
              }
            })],
            enableRichEditing: _this4.isRichEditor,
            languages: languages
          });

          _this4.tiptap.on('focus', function () {
            _this4.$emit('focus');
          });

          _this4.tiptap.on('blur', function () {
            _this4.$emit('blur');
          });

          _this4.syncService.state = _this4.tiptap.state;
        });
      }).on('sync', function (_ref7) {
        var steps = _ref7.steps,
            document = _ref7.document;
        _this4.hasConnectionIssue = false;

        try {
          _this4.tiptap.extensions.options.collaboration.update({
            version: document.currentVersion,
            steps: steps
          });

          _this4.syncService.state = _this4.tiptap.state;

          _this4.updateLastSavedStatus();
        } catch (e) {
          console.error('Failed to update steps in collaboration plugin', e); // TODO: we should recreate the editing session when this happens
        }

        _this4.document = document;
      }).on('error', function (error, data) {
        _this4.tiptap.setOptions({
          editable: false
        });

        if (error === _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SAVE_COLLISSION && (!_this4.syncError || _this4.syncError.type !== _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SAVE_COLLISSION)) {
          _this4.initialLoading = true;
          _this4.syncError = {
            type: error,
            data: data
          };
        }

        if (error === _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].CONNECTION_FAILED && !_this4.hasConnectionIssue) {
          _this4.hasConnectionIssue = true; // FIXME: ideally we just try to reconnect in the service, so we don't loose steps

          OC.Notification.showTemporary('Connection failed, reconnecting');

          if (data.retry !== false) {
            setTimeout(_this4.reconnect.bind(_this4), 5000);
          }
        }

        if (error === _services_SyncService__WEBPACK_IMPORTED_MODULE_2__["ERROR_TYPE"].SOURCE_NOT_FOUND) {
          _this4.initialLoading = false;

          _this4.$emit('close');

          _this4.$emit('error');
        }
      }).on('stateChange', function (state) {
        if (state.initialLoading && !_this4.initialLoading) {
          _this4.initialLoading = true;

          if (_this4.autofocus) {
            _this4.tiptap.focus('start');
          }

          _this4.$emit('ready');
        }

        if (state.hasOwnProperty('dirty')) {
          _this4.dirty = state.dirty;
        }
      });

      if (this.initialSession === null) {
        this.syncService.open({
          fileId: this.fileId,
          filePath: this.relativePath
        }).catch(function (e) {
          _this4.hasConnectionIssue = true;
        });
      } else {
        this.syncService.open({
          initialSession: this.initialSession
        }).catch(function (e) {
          _this4.hasConnectionIssue = true;
        });
      }

      this.forceRecreate = false;
    },
    resolveUseThisVersion: function resolveUseThisVersion() {
      this.syncService.forceSave();
      this.tiptap.setOptions({
        editable: !this.readOnly
      });
    },
    resolveUseServerVersion: function resolveUseServerVersion() {
      this.forceRecreate = true;
      this.reconnect();
    },
    reconnect: function reconnect() {
      var _this5 = this;

      if (this.syncService) {
        this.syncService.close().then(function () {
          _this5.syncService = null;

          _this5.tiptap.destroy();

          _this5.initSession();
        }).catch(function (e) {// Ignore issues closing the session since those might happen due to network issues
        });
      } else {
        this.syncService = null;
        this.tiptap.destroy();
        this.initSession();
      }
    },
    updateSessions: function updateSessions(sessions) {
      this.sessions = sessions.sort(function (a, b) {
        return b.lastContact - a.lastContact;
      });
      var currentSessionIds = this.sessions.map(function (session) {
        return session.userId;
      });
      var currentGuestIds = this.sessions.map(function (session) {
        return session.guestId;
      });
      var removedSessions = Object.keys(this.filteredSessions).filter(function (sessionId) {
        return !currentSessionIds.includes(sessionId) && !currentGuestIds.includes(sessionId);
      });

      for (var index in removedSessions) {
        vue__WEBPACK_IMPORTED_MODULE_0__["default"].delete(this.filteredSessions, removedSessions[index]);
      }

      for (var _index in this.sessions) {
        var session = this.sessions[_index];
        var sessionKey = session.displayName ? session.userId : session.id;

        if (this.filteredSessions[sessionKey]) {
          // update timestamp if relevant
          if (this.filteredSessions[sessionKey].lastContact < session.lastContact) {
            vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(this.filteredSessions[sessionKey], 'lastContact', session.lastContact);
          }
        } else {
          vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(this.filteredSessions, sessionKey, session);
        }

        if (session.id === this.currentSession.id) {
          vue__WEBPACK_IMPORTED_MODULE_0__["default"].set(this.filteredSessions[sessionKey], 'isCurrent', true);
        }
      }
    },
    _keyUpHandler: function _keyUpHandler(event) {
      var key = event.key || event.keyCode;

      if ((event.ctrlKey || event.metaKey) && !event.shiftKey && (key === 'f' || key === 70)) {
        event.stopPropagation();
        return true;
      }
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
!(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }());
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! escape-html */ "./node_modules/escape-html/index.js");
/* harmony import */ var escape_html__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(escape_html__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _EditorFactory__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../EditorFactory */ "./apps/text/src/EditorFactory.js");
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
  name: 'ReadOnlyEditor',
  components: {
    EditorContent: !(function webpackMissingModule() { var e = new Error("Cannot find module 'tiptap'"); e.code = 'MODULE_NOT_FOUND'; throw e; }())
  },
  props: {
    content: {
      type: String,
      required: true
    },
    isRichEditor: {
      type: Boolean,
      default: true
    }
  },
  data: function data() {
    return {
      editor: null
    };
  },
  mounted: function mounted() {
    this.editor = Object(_EditorFactory__WEBPACK_IMPORTED_MODULE_2__["createEditor"])({
      content: this.isRichEditor ? _EditorFactory__WEBPACK_IMPORTED_MODULE_2__["markdownit"].render(this.content) : '<pre>' + escape_html__WEBPACK_IMPORTED_MODULE_1___default()(this.content) + '</pre>',
      enableRichEditing: this.isRichEditor
    });
    this.editor.setOptions({
      editable: false
    });
  },
  beforeDestroy: function beforeDestroy() {
    this.editor.destroy();
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js&":
/*!******************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/nodes/ImageView.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
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
//
//
//
//
//
//
//
//
var imageMimes = ['image/png', 'image/jpeg', 'image/gif', 'image/x-xbitmap', 'image/bmp', 'image/svg+xml'];

var getQueryVariable = function getQueryVariable(src, variable) {
  var query = src.split('#')[1];

  if (typeof query === 'undefined') {
    return;
  }

  var vars = query.split('&');

  if (typeof vars === 'undefined') {
    return;
  }

  for (var i = 0; i < vars.length; i++) {
    var pair = vars[i].split('=');

    if (decodeURIComponent(pair[0]) === variable) {
      return decodeURIComponent(pair[1]);
    }
  }
};

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ImageView',
  props: ['node', 'updateAttrs', 'view'],
  // eslint-disable-line
  data: function data() {
    return {
      imageLoaded: false,
      loaded: false,
      failed: false
    };
  },
  computed: {
    mimeIcon: function mimeIcon() {
      var mime = getQueryVariable(this.src, 'mimetype');

      if (mime) {
        return {
          backgroundImage: 'url(' + window.OC.MimeType.getIconUrl(mime) + ')'
        };
      }

      return {};
    },
    isSupportedImage: function isSupportedImage() {
      var mime = getQueryVariable(this.src, 'mimetype');
      return typeof mime === 'undefined' || imageMimes.indexOf(mime) !== -1;
    },
    internalLinkOrImage: function internalLinkOrImage() {
      var fileId = getQueryVariable(this.src, 'fileId');

      if (fileId) {
        return OC.generateUrl('/f/' + fileId);
      }

      return this.src;
    },
    src: {
      get: function get() {
        return this.node.attrs.src;
      },
      set: function set(src) {
        this.updateAttrs({
          src: src
        });
      }
    },
    alt: {
      get: function get() {
        return this.node.attrs.alt ? this.node.attrs.alt : '';
      },
      set: function set(alt) {
        this.updateAttrs({
          alt: alt
        });
      }
    },
    t: function t() {
      return function (a, s) {
        return window.t(a, s);
      };
    }
  },
  beforeMount: function beforeMount() {
    var _this = this;

    if (!this.isSupportedImage) {
      // TODO check if hasPreview and render a file preview if available
      this.failed = true;
      this.imageLoaded = false;
      this.loaded = true;
      return;
    }

    var img = new Image();
    img.src = this.node.attrs.src;

    img.onload = function () {
      _this.imageLoaded = true;
    };

    img.onerror = function () {
      _this.failed = true;
      _this.imageLoaded = false;
      _this.loaded = true;
    };
  },
  methods: {
    updateAlt: function updateAlt() {
      this.alt = this.$refs.altInput.value;
    },
    onLoaded: function onLoaded() {
      this.loaded = true;
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, "#resolve-conflicts[data-v-a0d25866] {\n  display: flex;\n  position: fixed;\n  z-index: 10000;\n  bottom: 0;\n  max-width: 900px;\n  width: 100vw;\n  margin: auto;\n  padding: 20px 0;\n}\n#resolve-conflicts button[data-v-a0d25866] {\n    margin: auto;\n    box-shadow: 0 0 10px var(--color-box-shadow);\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, "#editor-container[data-v-782c4aaa] {\n  display: block;\n  width: 100%;\n  max-width: 100%;\n  height: 100%;\n  left: 0;\n  margin: 0 auto;\n  position: relative;\n  background-color: var(--color-main-background);\n}\n#editor-wrapper[data-v-782c4aaa] {\n  display: flex;\n  width: 100%;\n  height: 100%;\n  overflow: hidden;\n  position: absolute;\n}\n#editor-wrapper .ProseMirror[data-v-782c4aaa] {\n    margin-top: 0 !important;\n}\n#editor-wrapper.icon-loading #editor[data-v-782c4aaa] {\n    opacity: 0.3;\n}\n#editor[data-v-782c4aaa], .editor[data-v-782c4aaa] {\n  background: var(--color-main-background);\n  color: var(--color-main-text);\n  background-clip: padding-box;\n  border-radius: var(--border-radius);\n  padding: 0;\n  position: relative;\n  overflow-y: auto;\n  overflow-x: hidden;\n  width: 100%;\n}\n.msg.icon-error[data-v-782c4aaa] {\n  padding: 12px;\n  border-bottom: 1px solid var(--color-border);\n  padding-left: 30px;\n  background-position: 8px center;\n}\n.save-status[data-v-782c4aaa] {\n  padding: 9px;\n  text-overflow: ellipsis;\n  color: var(--color-text-lighter);\n}\n.save-status.error[data-v-782c4aaa] {\n    background-color: var(--color-error);\n    color: var(--color-main-background);\n    border-radius: 3px;\n}\n#editor-container #editor-wrapper.has-conflicts[data-v-782c4aaa] {\n  height: calc(100% - 50px);\n}\n#editor-container #editor-wrapper.has-conflicts #editor[data-v-782c4aaa], #editor-container #editor-wrapper.has-conflicts #read-only-editor[data-v-782c4aaa] {\n    width: 50%;\n    height: 100%;\n}\n#editor-session-list[data-v-782c4aaa] {\n  padding: 4px 16px 4px 4px;\n  display: flex;\n}\n#editor-session-list input[data-v-782c4aaa], #editor-session-list div[data-v-782c4aaa] {\n    vertical-align: middle;\n    margin-left: 3px;\n}\n.editor__content[data-v-782c4aaa] {\n  max-width: 670px;\n  margin: auto;\n  position: relative;\n}\n#body-public[data-v-782c4aaa] {\n  height: auto;\n}\n#files-public-content[data-v-782c4aaa] {\n  height: auto;\n}\n#files-public-content #editor-wrapper[data-v-782c4aaa] {\n    position: relative;\n}\n#files-public-content #editor-container[data-v-782c4aaa] {\n    top: 0;\n    width: 100%;\n}\n#files-public-content #editor-container #editor[data-v-782c4aaa] .menubar {\n      position: fixed;\n      top: 50px;\n      width: 100%;\n}\n#files-public-content #editor-container #editor[data-v-782c4aaa] {\n      padding-top: 50px;\n      overflow: auto;\n}\n#files-public-content #editor-container .has-conflicts #editor[data-v-782c4aaa] {\n      padding-top: 0px;\n}\n.ie #editor[data-v-782c4aaa] .menubar {\n  position: fixed;\n  top: 50px;\n  width: 100%;\n}\n.ie .editor__content[data-v-782c4aaa] .ProseMirror {\n  padding-top: 50px;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&":
/*!******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, ".modal-container #editor-container {\n  position: absolute;\n}\n.ProseMirror-hideselection *::selection {\n  background: transparent;\n}\n.ProseMirror-hideselection *::-moz-selection {\n  background: transparent;\n}\n.ProseMirror-hideselection {\n  caret-color: transparent;\n}\n.ProseMirror-selectednode {\n  outline: 2px solid #8cf;\n}\n\n/* Make sure li selections wrap around markers */\nli.ProseMirror-selectednode {\n  outline: none;\n}\nli.ProseMirror-selectednode:after {\n  content: \"\";\n  position: absolute;\n  left: -32px;\n  right: -2px;\n  top: -2px;\n  bottom: -2px;\n  border: 2px solid #8cf;\n  pointer-events: none;\n}\n.has-conflicts .ProseMirror-menubar,\n#editor-wrapper.icon-loading .ProseMirror-menubar {\n  display: none;\n}\n.ProseMirror-gapcursor {\n  display: none;\n  pointer-events: none;\n  position: absolute;\n}\n.ProseMirror-gapcursor:after {\n  content: \"\";\n  display: block;\n  position: absolute;\n  top: -2px;\n  width: 20px;\n  border-top: 1px solid var(--color-main-text);\n  animation: ProseMirror-cursor-blink 1.1s steps(2, start) infinite;\n}\n@keyframes ProseMirror-cursor-blink {\nto {\n    visibility: hidden;\n}\n}\n#editor-wrapper {\n  /* Document rendering styles */\n}\n#editor-wrapper div.ProseMirror {\n    margin-top: 44px;\n    height: 100%;\n    position: relative;\n    word-wrap: break-word;\n    white-space: pre-wrap;\n    -webkit-font-variant-ligatures: none;\n    font-variant-ligatures: none;\n    padding: 4px 8px 200px 14px;\n    line-height: 150%;\n    font-size: 14px;\n    outline: none;\n}\n#editor-wrapper div.ProseMirror[contenteditable=true], #editor-wrapper div.ProseMirror[contenteditable=false] {\n      border: none !important;\n      width: 100%;\n      background-color: transparent;\n      color: var(--color-main-text);\n      opacity: 1;\n      -webkit-user-select: text;\n      user-select: text;\n      font-size: 14px;\n}\n#editor-wrapper div.ProseMirror li label.checkbox-label {\n      width: 100%;\n      display: flex;\n      margin-top: 10px;\n      margin-bottom: 1em;\n}\n#editor-wrapper div.ProseMirror li label.checkbox-label:before {\n        position: relative;\n        top: 2px;\n}\n#editor-wrapper div.ProseMirror li label.checkbox-label div.checkbox-wrapper {\n        margin-bottom: -1em;\n        width: 100%;\n}\n#editor-wrapper div.ProseMirror li label.checkbox-label div.checkbox-wrapper > p {\n          margin-top: -1px;\n          margin-bottom: 0;\n          padding-bottom: 10px;\n}\n#editor-wrapper div.ProseMirror p:first-child,\n    #editor-wrapper div.ProseMirror h1:first-child,\n    #editor-wrapper div.ProseMirror h2:first-child,\n    #editor-wrapper div.ProseMirror h3:first-child,\n    #editor-wrapper div.ProseMirror h4:first-child,\n    #editor-wrapper div.ProseMirror h5:first-child,\n    #editor-wrapper div.ProseMirror h6:first-child {\n      margin-top: 10px;\n}\n#editor-wrapper div.ProseMirror a {\n      color: var(--color-primary-element);\n      text-decoration: underline;\n      padding: .5em 0;\n}\n#editor-wrapper div.ProseMirror p {\n      margin-bottom: 1em;\n      line-height: 150%;\n}\n#editor-wrapper div.ProseMirror em {\n      font-style: italic;\n}\n#editor-wrapper div.ProseMirror h1,\n    #editor-wrapper div.ProseMirror h2,\n    #editor-wrapper div.ProseMirror h3,\n    #editor-wrapper div.ProseMirror h4,\n    #editor-wrapper div.ProseMirror h5,\n    #editor-wrapper div.ProseMirror h6 {\n      font-weight: 600;\n      line-height: 120%;\n      margin-top: 24px;\n      margin-bottom: 12px;\n      color: var(--color-main-text);\n}\n#editor-wrapper div.ProseMirror h1 {\n      font-size: 36px;\n      margin-top: 48px;\n}\n#editor-wrapper div.ProseMirror h2 {\n      font-size: 28px;\n      margin-top: 48px;\n}\n#editor-wrapper div.ProseMirror h3 {\n      font-size: 24px;\n}\n#editor-wrapper div.ProseMirror h4 {\n      font-size: 21px;\n}\n#editor-wrapper div.ProseMirror h5 {\n      font-size: 17px;\n}\n#editor-wrapper div.ProseMirror h6 {\n      font-size: 14px;\n}\n#editor-wrapper div.ProseMirror img {\n      cursor: default;\n      max-width: 100%;\n}\n#editor-wrapper div.ProseMirror hr {\n      padding: 2px 0;\n      border: none;\n      margin: 1em 0;\n      width: 100%;\n}\n#editor-wrapper div.ProseMirror hr:after {\n      content: \"\";\n      display: block;\n      height: 1px;\n      background-color: var(--color-border-dark);\n      line-height: 2px;\n}\n#editor-wrapper div.ProseMirror pre {\n      white-space: pre-wrap;\n      background-color: var(--color-background-dark);\n      border-radius: var(--border-radius);\n      padding: 1em 1.3em;\n      margin-bottom: 1em;\n}\n#editor-wrapper div.ProseMirror p code {\n      background-color: var(--color-background-dark);\n      border-radius: var(--border-radius);\n      padding: .1em .3em;\n}\n#editor-wrapper div.ProseMirror li {\n      position: relative;\n}\n#editor-wrapper div.ProseMirror ul, #editor-wrapper div.ProseMirror ol {\n      padding-left: 10px;\n      margin-left: 10px;\n}\n#editor-wrapper div.ProseMirror ul li {\n      list-style-type: disc;\n}\n#editor-wrapper div.ProseMirror ul > li > ul > li {\n      list-style-type: circle;\n}\n#editor-wrapper div.ProseMirror ul > li > ul > li ul li {\n      list-style-type: square;\n}\n#editor-wrapper div.ProseMirror blockquote {\n      padding-left: 1em;\n      border-left: 4px solid var(--color-primary-element);\n      color: var(--color-text-maxcontrast);\n      margin-left: 0;\n      margin-right: 0;\n}\n#editor-wrapper .ProseMirror-focused .ProseMirror-gapcursor {\n    display: block;\n}\n#editor-wrapper .editor__content p.is-empty:first-child::before {\n    content: attr(data-empty-text);\n    float: left;\n    color: var(--color-text-maxcontrast);\n    pointer-events: none;\n    height: 0;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre {\n    background-color: var(--color-main-background);\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre::before {\n      content: attr(data-language);\n      text-transform: uppercase;\n      display: block;\n      text-align: right;\n      font-weight: bold;\n      font-size: 0.6rem;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-comment,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-quote {\n      color: #999999;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-variable,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-template-variable,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-attribute,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-tag,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-name,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-regexp,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-link,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-selector-id,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-selector-class {\n      color: #f2777a;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-number,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-meta,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-built_in,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-builtin-name,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-literal,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-type,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-params {\n      color: #f99157;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-string,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-symbol,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-bullet {\n      color: #99cc99;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-title,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-section {\n      color: #ffcc66;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-keyword,\n    #editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-selector-tag {\n      color: #6699cc;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-emphasis {\n      font-style: italic;\n}\n#editor-wrapper:not(.richEditor) .ProseMirror pre code .hljs-strong {\n      font-weight: 700;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, "#read-only-editor {\n  /* Document rendering styles */\n  overflow: scroll;\n}\n#read-only-editor div.ProseMirror {\n    margin-top: 44px;\n    height: 100%;\n    position: relative;\n    word-wrap: break-word;\n    white-space: pre-wrap;\n    -webkit-font-variant-ligatures: none;\n    font-variant-ligatures: none;\n    padding: 4px 8px 200px 14px;\n    line-height: 150%;\n    font-size: 14px;\n    outline: none;\n}\n#read-only-editor div.ProseMirror[contenteditable=true], #read-only-editor div.ProseMirror[contenteditable=false] {\n      border: none !important;\n      width: 100%;\n      background-color: transparent;\n      color: var(--color-main-text);\n      opacity: 1;\n      -webkit-user-select: text;\n      user-select: text;\n      font-size: 14px;\n}\n#read-only-editor div.ProseMirror li label.checkbox-label {\n      width: 100%;\n      display: flex;\n      margin-top: 10px;\n      margin-bottom: 1em;\n}\n#read-only-editor div.ProseMirror li label.checkbox-label:before {\n        position: relative;\n        top: 2px;\n}\n#read-only-editor div.ProseMirror li label.checkbox-label div.checkbox-wrapper {\n        margin-bottom: -1em;\n        width: 100%;\n}\n#read-only-editor div.ProseMirror li label.checkbox-label div.checkbox-wrapper > p {\n          margin-top: -1px;\n          margin-bottom: 0;\n          padding-bottom: 10px;\n}\n#read-only-editor div.ProseMirror p:first-child,\n    #read-only-editor div.ProseMirror h1:first-child,\n    #read-only-editor div.ProseMirror h2:first-child,\n    #read-only-editor div.ProseMirror h3:first-child,\n    #read-only-editor div.ProseMirror h4:first-child,\n    #read-only-editor div.ProseMirror h5:first-child,\n    #read-only-editor div.ProseMirror h6:first-child {\n      margin-top: 10px;\n}\n#read-only-editor div.ProseMirror a {\n      color: var(--color-primary-element);\n      text-decoration: underline;\n      padding: .5em 0;\n}\n#read-only-editor div.ProseMirror p {\n      margin-bottom: 1em;\n      line-height: 150%;\n}\n#read-only-editor div.ProseMirror em {\n      font-style: italic;\n}\n#read-only-editor div.ProseMirror h1,\n    #read-only-editor div.ProseMirror h2,\n    #read-only-editor div.ProseMirror h3,\n    #read-only-editor div.ProseMirror h4,\n    #read-only-editor div.ProseMirror h5,\n    #read-only-editor div.ProseMirror h6 {\n      font-weight: 600;\n      line-height: 120%;\n      margin-top: 24px;\n      margin-bottom: 12px;\n      color: var(--color-main-text);\n}\n#read-only-editor div.ProseMirror h1 {\n      font-size: 36px;\n      margin-top: 48px;\n}\n#read-only-editor div.ProseMirror h2 {\n      font-size: 28px;\n      margin-top: 48px;\n}\n#read-only-editor div.ProseMirror h3 {\n      font-size: 24px;\n}\n#read-only-editor div.ProseMirror h4 {\n      font-size: 21px;\n}\n#read-only-editor div.ProseMirror h5 {\n      font-size: 17px;\n}\n#read-only-editor div.ProseMirror h6 {\n      font-size: 14px;\n}\n#read-only-editor div.ProseMirror img {\n      cursor: default;\n      max-width: 100%;\n}\n#read-only-editor div.ProseMirror hr {\n      padding: 2px 0;\n      border: none;\n      margin: 1em 0;\n      width: 100%;\n}\n#read-only-editor div.ProseMirror hr:after {\n      content: \"\";\n      display: block;\n      height: 1px;\n      background-color: var(--color-border-dark);\n      line-height: 2px;\n}\n#read-only-editor div.ProseMirror pre {\n      white-space: pre-wrap;\n      background-color: var(--color-background-dark);\n      border-radius: var(--border-radius);\n      padding: 1em 1.3em;\n      margin-bottom: 1em;\n}\n#read-only-editor div.ProseMirror p code {\n      background-color: var(--color-background-dark);\n      border-radius: var(--border-radius);\n      padding: .1em .3em;\n}\n#read-only-editor div.ProseMirror li {\n      position: relative;\n}\n#read-only-editor div.ProseMirror ul, #read-only-editor div.ProseMirror ol {\n      padding-left: 10px;\n      margin-left: 10px;\n}\n#read-only-editor div.ProseMirror ul li {\n      list-style-type: disc;\n}\n#read-only-editor div.ProseMirror ul > li > ul > li {\n      list-style-type: circle;\n}\n#read-only-editor div.ProseMirror ul > li > ul > li ul li {\n      list-style-type: square;\n}\n#read-only-editor div.ProseMirror blockquote {\n      padding-left: 1em;\n      border-left: 4px solid var(--color-primary-element);\n      color: var(--color-text-maxcontrast);\n      margin-left: 0;\n      margin-right: 0;\n}\n#read-only-editor .ProseMirror-focused .ProseMirror-gapcursor {\n    display: block;\n}\n#read-only-editor .editor__content p.is-empty:first-child::before {\n    content: attr(data-empty-text);\n    float: left;\n    color: var(--color-text-maxcontrast);\n    pointer-events: none;\n    height: 0;\n}\n.thumbnailContainer #read-only-editor {\n  width: 100%;\n}\n.thumbnailContainer #read-only-editor .ProseMirror {\n    height: auto;\n    margin: 0 0 0 0;\n    padding: 0;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, "/* Document rendering styles */\ndiv.ProseMirror {\n  margin-top: 44px;\n  height: 100%;\n  position: relative;\n  word-wrap: break-word;\n  white-space: pre-wrap;\n  -webkit-font-variant-ligatures: none;\n  font-variant-ligatures: none;\n  padding: 4px 8px 200px 14px;\n  line-height: 150%;\n  font-size: 14px;\n  outline: none;\n}\ndiv.ProseMirror[contenteditable=true], div.ProseMirror[contenteditable=false] {\n    border: none !important;\n    width: 100%;\n    background-color: transparent;\n    color: var(--color-main-text);\n    opacity: 1;\n    -webkit-user-select: text;\n    user-select: text;\n    font-size: 14px;\n}\ndiv.ProseMirror li label.checkbox-label {\n    width: 100%;\n    display: flex;\n    margin-top: 10px;\n    margin-bottom: 1em;\n}\ndiv.ProseMirror li label.checkbox-label:before {\n      position: relative;\n      top: 2px;\n}\ndiv.ProseMirror li label.checkbox-label div.checkbox-wrapper {\n      margin-bottom: -1em;\n      width: 100%;\n}\ndiv.ProseMirror li label.checkbox-label div.checkbox-wrapper > p {\n        margin-top: -1px;\n        margin-bottom: 0;\n        padding-bottom: 10px;\n}\ndiv.ProseMirror p:first-child,\n  div.ProseMirror h1:first-child,\n  div.ProseMirror h2:first-child,\n  div.ProseMirror h3:first-child,\n  div.ProseMirror h4:first-child,\n  div.ProseMirror h5:first-child,\n  div.ProseMirror h6:first-child {\n    margin-top: 10px;\n}\ndiv.ProseMirror a {\n    color: var(--color-primary-element);\n    text-decoration: underline;\n    padding: .5em 0;\n}\ndiv.ProseMirror p {\n    margin-bottom: 1em;\n    line-height: 150%;\n}\ndiv.ProseMirror em {\n    font-style: italic;\n}\ndiv.ProseMirror h1,\n  div.ProseMirror h2,\n  div.ProseMirror h3,\n  div.ProseMirror h4,\n  div.ProseMirror h5,\n  div.ProseMirror h6 {\n    font-weight: 600;\n    line-height: 120%;\n    margin-top: 24px;\n    margin-bottom: 12px;\n    color: var(--color-main-text);\n}\ndiv.ProseMirror h1 {\n    font-size: 36px;\n    margin-top: 48px;\n}\ndiv.ProseMirror h2 {\n    font-size: 28px;\n    margin-top: 48px;\n}\ndiv.ProseMirror h3 {\n    font-size: 24px;\n}\ndiv.ProseMirror h4 {\n    font-size: 21px;\n}\ndiv.ProseMirror h5 {\n    font-size: 17px;\n}\ndiv.ProseMirror h6 {\n    font-size: 14px;\n}\ndiv.ProseMirror img {\n    cursor: default;\n    max-width: 100%;\n}\ndiv.ProseMirror hr {\n    padding: 2px 0;\n    border: none;\n    margin: 1em 0;\n    width: 100%;\n}\ndiv.ProseMirror hr:after {\n    content: \"\";\n    display: block;\n    height: 1px;\n    background-color: var(--color-border-dark);\n    line-height: 2px;\n}\ndiv.ProseMirror pre {\n    white-space: pre-wrap;\n    background-color: var(--color-background-dark);\n    border-radius: var(--border-radius);\n    padding: 1em 1.3em;\n    margin-bottom: 1em;\n}\ndiv.ProseMirror p code {\n    background-color: var(--color-background-dark);\n    border-radius: var(--border-radius);\n    padding: .1em .3em;\n}\ndiv.ProseMirror li {\n    position: relative;\n}\ndiv.ProseMirror ul, div.ProseMirror ol {\n    padding-left: 10px;\n    margin-left: 10px;\n}\ndiv.ProseMirror ul li {\n    list-style-type: disc;\n}\ndiv.ProseMirror ul > li > ul > li {\n    list-style-type: circle;\n}\ndiv.ProseMirror ul > li > ul > li ul li {\n    list-style-type: square;\n}\ndiv.ProseMirror blockquote {\n    padding-left: 1em;\n    border-left: 4px solid var(--color-primary-element);\n    color: var(--color-text-maxcontrast);\n    margin-left: 0;\n    margin-right: 0;\n}\n.ProseMirror-focused .ProseMirror-gapcursor {\n  display: block;\n}\n.editor__content p.is-empty:first-child::before {\n  content: attr(data-empty-text);\n  float: left;\n  color: var(--color-text-maxcontrast);\n  pointer-events: none;\n  height: 0;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// Imports
var ___CSS_LOADER_API_IMPORT___ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
exports = ___CSS_LOADER_API_IMPORT___(false);
// Module
exports.push([module.i, ".image[data-v-38673bf4] {\n  margin: 0;\n  padding: 0;\n}\n.image__caption[data-v-38673bf4] {\n  text-align: center;\n  color: var(--color-text-lighter);\n}\n.image__caption input[type='text'][data-v-38673bf4] {\n    width: 100%;\n    border: none;\n    text-align: center;\n}\n.icon-image[data-v-38673bf4] {\n  margin-top: 10px;\n  height: 32px;\n  padding: 20px;\n  background-size: contain;\n}\n.image__loading[data-v-38673bf4] {\n  height: 100px;\n}\n.image__placeholder .image__main[data-v-38673bf4] {\n  background-color: var(--color-background-dark);\n  text-align: center;\n  padding: 20px;\n  border-radius: var(--border-radius);\n}\n.image__placeholder .image__main .icon-image[data-v-38673bf4] {\n    opacity: 0.7;\n}\n.fade-enter-active[data-v-38673bf4] {\n  transition: opacity .3s ease-in-out;\n}\n.fade-enter-to[data-v-38673bf4] {\n  opacity: 1;\n}\n.fade-enter[data-v-38673bf4] {\n  opacity: 0;\n}\n", ""]);
// Exports
module.exports = exports;


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=template&id=a0d25866&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************/
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
  return _c(
    "div",
    {
      staticClass: "collision-resolve-dialog",
      attrs: { id: "resolve-conflicts" }
    },
    [
      _c(
        "button",
        {
          on: {
            click: function($event) {
              return _vm.$emit("resolveUseThisVersion")
            }
          }
        },
        [
          _vm._v(
            "\n\t\t" + _vm._s(_vm.t("text", "Use current version")) + "\n\t"
          )
        ]
      ),
      _vm._v(" "),
      _c(
        "button",
        {
          on: {
            click: function($event) {
              return _vm.$emit("resolveUseServerVersion")
            }
          }
        },
        [
          _vm._v(
            "\n\t\t" + _vm._s(_vm.t("text", "Use the saved version")) + "\n\t"
          )
        ]
      )
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=template&id=782c4aaa&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************/
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
  return _c(
    "div",
    { attrs: { id: "editor-container" } },
    [
      _vm.currentSession && _vm.active
        ? _c("div", [
            _vm.hasSyncCollission
              ? _c("p", { staticClass: "msg icon-error" }, [
                  _vm._v(
                    "\n\t\t\t" +
                      _vm._s(
                        _vm.t(
                          "text",
                          "The document has been changed outside of the editor. The changes cannot be applied."
                        )
                      ) +
                      "\n\t\t"
                  )
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.hasConnectionIssue
              ? _c("p", { staticClass: "msg icon-info" }, [
                  _vm._v(
                    "\n\t\t\t" +
                      _vm._s(
                        _vm.t(
                          "text",
                          "File could not be loaded. Please check your internet connection."
                        )
                      ) +
                      " "
                  ),
                  _c(
                    "a",
                    {
                      staticClass: "button primary",
                      on: { click: _vm.reconnect }
                    },
                    [_vm._v(_vm._s(_vm.t("text", "Retry")))]
                  )
                ])
              : _vm._e()
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.currentSession && _vm.active
        ? _c(
            "div",
            {
              class: {
                "has-conflicts": _vm.hasSyncCollission,
                "icon-loading": !_vm.initialLoading || _vm.hasConnectionIssue,
                richEditor: _vm.isRichEditor
              },
              attrs: { id: "editor-wrapper" }
            },
            [
              _c(
                "div",
                { attrs: { id: "editor" } },
                [
                  !_vm.syncError && !_vm.readOnly
                    ? _c(
                        "MenuBar",
                        {
                          ref: "menubar",
                          attrs: {
                            editor: _vm.tiptap,
                            "is-rich-editor": _vm.isRichEditor,
                            "is-public": _vm.isPublic,
                            autohide: _vm.autohide
                          }
                        },
                        [
                          _vm.currentSession && _vm.active
                            ? _c(
                                "div",
                                { attrs: { id: "editor-session-list" } },
                                [
                                  _c(
                                    "div",
                                    {
                                      directives: [
                                        {
                                          name: "tooltip",
                                          rawName: "v-tooltip",
                                          value: _vm.lastSavedStatusTooltip,
                                          expression: "lastSavedStatusTooltip"
                                        }
                                      ],
                                      staticClass: "save-status",
                                      class: _vm.lastSavedStatusClass
                                    },
                                    [
                                      _vm._v(
                                        "\n\t\t\t\t\t\t" +
                                          _vm._s(_vm.lastSavedStatus) +
                                          "\n\t\t\t\t\t"
                                      )
                                    ]
                                  ),
                                  _vm._v(" "),
                                  _c(
                                    "SessionList",
                                    {
                                      attrs: { sessions: _vm.filteredSessions }
                                    },
                                    [
                                      _vm.isPublic &&
                                      _vm.currentSession.guestName
                                        ? _c("GuestNameDialog", {
                                            attrs: {
                                              "sync-service": _vm.syncService
                                            }
                                          })
                                        : _vm._e()
                                    ],
                                    1
                                  )
                                ],
                                1
                              )
                            : _vm._e(),
                          _vm._v(" "),
                          _vm._t("header")
                        ],
                        2
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  _c(
                    "div",
                    [
                      !_vm.readOnly && _vm.isRichEditor
                        ? _c("MenuBubble", { attrs: { editor: _vm.tiptap } })
                        : _vm._e(),
                      _vm._v(" "),
                      _c("EditorContent", {
                        directives: [
                          {
                            name: "show",
                            rawName: "v-show",
                            value: _vm.initialLoading,
                            expression: "initialLoading"
                          }
                        ],
                        staticClass: "editor__content",
                        attrs: { editor: _vm.tiptap }
                      })
                    ],
                    1
                  )
                ],
                1
              ),
              _vm._v(" "),
              _vm.hasSyncCollission
                ? _c("ReadOnlyEditor", {
                    attrs: {
                      content: _vm.syncError.data.outsideChange,
                      "is-rich-editor": _vm.isRichEditor
                    }
                  })
                : _vm._e()
            ],
            1
          )
        : _vm._e(),
      _vm._v(" "),
      _vm.hasSyncCollission && !_vm.readOnly
        ? _c("CollisionResolveDialog", {
            on: {
              resolveUseThisVersion: _vm.resolveUseThisVersion,
              resolveUseServerVersion: _vm.resolveUseServerVersion
            }
          })
        : _vm._e()
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c&":
/*!******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=template&id=a279db0c& ***!
  \******************************************************************************************************************************************************************************************************************/
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
  return _vm.editor
    ? _c("EditorContent", {
        attrs: { id: "read-only-editor", editor: _vm.editor }
      })
    : _vm._e()
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/nodes/ImageView.vue?vue&type=template&id=38673bf4&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************/
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
  return _c(
    "div",
    {
      staticClass: "image",
      class: { "icon-loading": !_vm.loaded },
      attrs: { "data-src": _vm.src }
    },
    [
      _vm.imageLoaded && _vm.isSupportedImage
        ? _c(
            "div",
            [
              _c("transition", { attrs: { name: "fade" } }, [
                _c("img", {
                  directives: [
                    {
                      name: "show",
                      rawName: "v-show",
                      value: _vm.loaded,
                      expression: "loaded"
                    }
                  ],
                  staticClass: "image__main",
                  attrs: { src: _vm.src },
                  on: { load: _vm.onLoaded }
                })
              ]),
              _vm._v(" "),
              _c("transition", { attrs: { name: "fade" } }, [
                _c(
                  "div",
                  {
                    directives: [
                      {
                        name: "show",
                        rawName: "v-show",
                        value: _vm.loaded,
                        expression: "loaded"
                      }
                    ],
                    staticClass: "image__caption"
                  },
                  [
                    _c("input", {
                      ref: "altInput",
                      attrs: { type: "text" },
                      domProps: { value: _vm.alt },
                      on: {
                        keyup: function($event) {
                          if (
                            !$event.type.indexOf("key") &&
                            _vm._k(
                              $event.keyCode,
                              "enter",
                              13,
                              $event.key,
                              "Enter"
                            )
                          ) {
                            return null
                          }
                          return _vm.updateAlt()
                        }
                      }
                    })
                  ]
                )
              ])
            ],
            1
          )
        : _c(
            "div",
            { staticClass: "image__placeholder" },
            [
              _c("transition", { attrs: { name: "fade" } }, [
                _c(
                  "div",
                  {
                    directives: [
                      {
                        name: "show",
                        rawName: "v-show",
                        value: _vm.loaded,
                        expression: "loaded"
                      }
                    ],
                    staticClass: "image__main"
                  },
                  [
                    _c("div", {
                      staticClass: "icon-image",
                      style: _vm.mimeIcon
                    }),
                    _vm._v(" "),
                    _c("p", [
                      _c(
                        "a",
                        {
                          attrs: {
                            href: _vm.internalLinkOrImage,
                            target: "_blank"
                          }
                        },
                        [
                          _vm._v(
                            _vm._s(
                              _vm.isSupportedImage
                                ? _vm.t("text", "Show image")
                                : _vm.t("text", "Show file")
                            )
                          )
                        ]
                      )
                    ])
                  ]
                )
              ]),
              _c("transition", { attrs: { name: "fade" } }, [
                _c(
                  "div",
                  {
                    directives: [
                      {
                        name: "show",
                        rawName: "v-show",
                        value: _vm.loaded,
                        expression: "loaded"
                      }
                    ],
                    staticClass: "image__caption"
                  },
                  [
                    _c("input", {
                      ref: "altInput",
                      attrs: { type: "text" },
                      domProps: { value: _vm.alt },
                      on: {
                        keyup: function($event) {
                          if (
                            !$event.type.indexOf("key") &&
                            _vm._k(
                              $event.keyCode,
                              "enter",
                              13,
                              $event.key,
                              "Enter"
                            )
                          ) {
                            return null
                          }
                          return _vm.updateAlt()
                        }
                      }
                    })
                  ]
                )
              ])
            ],
            1
          )
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/CollisionResolveDialog.vue?vue&type=style&index=0&id=a0d25866&scoped=true&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("6e2092d3", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=0&id=782c4aaa&scoped=true&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("09dbb210", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./EditorWrapper.vue?vue&type=style&index=1&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/EditorWrapper.vue?vue&type=style&index=1&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("0a97a801", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=0&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("9655b5c4", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/components/ReadOnlyEditor.vue?vue&type=style&index=1&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("491394bd", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ }),

/***/ "./node_modules/vue-style-loader/index.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-style-loader!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib??vue-loader-options!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

// style-loader: Adds some css to the DOM by adding a <style> tag

// load the styles
var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib??vue-loader-options!./ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js?!./apps/text/src/nodes/ImageView.vue?vue&type=style&index=0&id=38673bf4&scoped=true&lang=scss&");
if(typeof content === 'string') content = [[module.i, content, '']];
if(content.locals) module.exports = content.locals;
// add the styles to the DOM
var add = __webpack_require__(/*! ../../../../node_modules/vue-style-loader/lib/addStylesClient.js */ "./node_modules/vue-style-loader/lib/addStylesClient.js").default
var update = add("718b5285", content, false, {});
// Hot Module Replacement
if(false) {}

/***/ })

}]);
//# sourceMappingURL=vue-editor.js.map?v=e8db6d8b389732baa316