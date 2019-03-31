/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/js/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./apps/files_versions/src/files_versions.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./apps/files_versions/src/css/versions.css":
/*!**************************************************!*\
  !*** ./apps/files_versions/src/css/versions.css ***!
  \**************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../../../node_modules/css-loader/dist/cjs.js!./versions.css */ "./node_modules/css-loader/dist/cjs.js!./apps/files_versions/src/css/versions.css");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./apps/files_versions/src/files_versions.js":
/*!***************************************************!*\
  !*** ./apps/files_versions/src/files_versions.js ***!
  \***************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _versionmodel__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./versionmodel */ "./apps/files_versions/src/versionmodel.js");
/* harmony import */ var _versionmodel__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_versionmodel__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _templates__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./templates */ "./apps/files_versions/src/templates.js");
/* harmony import */ var _templates__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_templates__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _versioncollection__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./versioncollection */ "./apps/files_versions/src/versioncollection.js");
/* harmony import */ var _versioncollection__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_versioncollection__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _versionstabview__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./versionstabview */ "./apps/files_versions/src/versionstabview.js");
/* harmony import */ var _versionstabview__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_versionstabview__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _filesplugin__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./filesplugin */ "./apps/files_versions/src/filesplugin.js");
/* harmony import */ var _filesplugin__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_filesplugin__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _css_versions_css__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./css/versions.css */ "./apps/files_versions/src/css/versions.css");
/* harmony import */ var _css_versions_css__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_css_versions_css__WEBPACK_IMPORTED_MODULE_5__);






window.OCA.Versions = OCA.Versions;

/***/ }),

/***/ "./apps/files_versions/src/filesplugin.js":
/*!************************************************!*\
  !*** ./apps/files_versions/src/filesplugin.js ***!
  \************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  OCA.Versions = OCA.Versions || {};
  /**
   * @namespace
   */

  OCA.Versions.Util = {
    /**
     * Initialize the versions plugin.
     *
     * @param {OCA.Files.FileList} fileList file list to be extended
     */
    attach: function attach(fileList) {
      if (fileList.id === 'trashbin' || fileList.id === 'files.public') {
        return;
      }

      fileList.registerTabView(new OCA.Versions.VersionsTabView('versionsTabView', {
        order: -10
      }));
    }
  };
})();

OC.Plugins.register('OCA.Files.FileList', OCA.Versions.Util);

/***/ }),

/***/ "./apps/files_versions/src/templates.js":
/*!**********************************************!*\
  !*** ./apps/files_versions/src/templates.js ***!
  \**********************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function () {
  var template = Handlebars.template,
      templates = OCA.Versions.Templates = OCA.Versions.Templates || {};
  templates['item'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "				<div class=\"version-details\">\n					<span class=\"size has-tooltip\" title=\"" + alias4((helper = (helper = helpers.altSize || (depth0 != null ? depth0.altSize : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "altSize",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.humanReadableSize || (depth0 != null ? depth0.humanReadableSize : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "humanReadableSize",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n				</div>\n";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "			<a href=\"#\" class=\"revertVersion\" title=\"" + alias4((helper = (helper = helpers.revertLabel || (depth0 != null ? depth0.revertLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "revertLabel",
        "hash": {},
        "data": data
      }) : helper)) + "\"><img src=\"" + alias4((helper = (helper = helpers.revertIconUrl || (depth0 != null ? depth0.revertIconUrl : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "revertIconUrl",
        "hash": {},
        "data": data
      }) : helper)) + "\" /></a>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          options,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression,
          alias5 = helpers.blockHelperMissing,
          buffer = "<li data-revision=\"" + alias4((helper = (helper = helpers.timestamp || (depth0 != null ? depth0.timestamp : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "timestamp",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n	<div>\n		<div class=\"preview-container\">\n			<img class=\"preview\" src=\"" + alias4((helper = (helper = helpers.previewUrl || (depth0 != null ? depth0.previewUrl : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "previewUrl",
        "hash": {},
        "data": data
      }) : helper)) + "\" width=\"44\" height=\"44\"/>\n		</div>\n		<div class=\"version-container\">\n			<div>\n				<a href=\"" + alias4((helper = (helper = helpers.downloadUrl || (depth0 != null ? depth0.downloadUrl : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "downloadUrl",
        "hash": {},
        "data": data
      }) : helper)) + "\" class=\"downloadVersion\" download=\"" + alias4((helper = (helper = helpers.downloadName || (depth0 != null ? depth0.downloadName : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "downloadName",
        "hash": {},
        "data": data
      }) : helper)) + "\"><img src=\"" + alias4((helper = (helper = helpers.downloadIconUrl || (depth0 != null ? depth0.downloadIconUrl : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "downloadIconUrl",
        "hash": {},
        "data": data
      }) : helper)) + "\" />\n					<span class=\"versiondate has-tooltip live-relative-timestamp\" data-timestamp=\"" + alias4((helper = (helper = helpers.millisecondsTimestamp || (depth0 != null ? depth0.millisecondsTimestamp : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "millisecondsTimestamp",
        "hash": {},
        "data": data
      }) : helper)) + "\" title=\"" + alias4((helper = (helper = helpers.formattedTimestamp || (depth0 != null ? depth0.formattedTimestamp : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "formattedTimestamp",
        "hash": {},
        "data": data
      }) : helper)) + "\">" + alias4((helper = (helper = helpers.relativeTimestamp || (depth0 != null ? depth0.relativeTimestamp : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "relativeTimestamp",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n				</a>\n			</div>\n";
      stack1 = (helper = (helper = helpers.hasDetails || (depth0 != null ? depth0.hasDetails : depth0)) != null ? helper : alias2, options = {
        "name": "hasDetails",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      }, _typeof(helper) === alias3 ? helper.call(alias1, options) : helper);

      if (!helpers.hasDetails) {
        stack1 = alias5.call(depth0, stack1, options);
      }

      if (stack1 != null) {
        buffer += stack1;
      }

      buffer += "		</div>\n";
      stack1 = (helper = (helper = helpers.canRevert || (depth0 != null ? depth0.canRevert : depth0)) != null ? helper : alias2, options = {
        "name": "canRevert",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.noop,
        "data": data
      }, _typeof(helper) === alias3 ? helper.call(alias1, options) : helper);

      if (!helpers.canRevert) {
        stack1 = alias5.call(depth0, stack1, options);
      }

      if (stack1 != null) {
        buffer += stack1;
      }

      return buffer + "	</div>\n</li>\n";
    },
    "useData": true
  });
  templates['template'] = template({
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<ul class=\"versions\"></ul>\n<div class=\"clear-float\"></div>\n<div class=\"empty hidden\">\n	<div class=\"emptycontent\">\n		<div class=\"icon-history\"></div>\n		<p>" + alias4((helper = (helper = helpers.emptyResultLabel || (depth0 != null ? depth0.emptyResultLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "emptyResultLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</p>\n	</div>\n</div>\n<input type=\"button\" class=\"showMoreVersions hidden\" value=\"" + alias4((helper = (helper = helpers.moreVersionsLabel || (depth0 != null ? depth0.moreVersionsLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "moreVersionsLabel",
        "hash": {},
        "data": data
      }) : helper)) + "\" name=\"show-more-versions\" id=\"show-more-versions\" />\n<div class=\"loading hidden\" style=\"height: 50px\"></div>\n";
    },
    "useData": true
  });
})();

/***/ }),

/***/ "./apps/files_versions/src/versioncollection.js":
/*!******************************************************!*\
  !*** ./apps/files_versions/src/versioncollection.js ***!
  \******************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  /**
   * @memberof OCA.Versions
   */
  var VersionCollection = OC.Backbone.Collection.extend({
    model: OCA.Versions.VersionModel,
    sync: OC.Backbone.davSync,

    /**
     * @var OCA.Files.FileInfoModel
     */
    _fileInfo: null,
    _currentUser: null,
    _client: null,
    setFileInfo: function setFileInfo(fileInfo) {
      this._fileInfo = fileInfo;
    },
    getFileInfo: function getFileInfo() {
      return this._fileInfo;
    },
    setCurrentUser: function setCurrentUser(user) {
      this._currentUser = user;
    },
    getCurrentUser: function getCurrentUser() {
      return this._currentUser || OC.getCurrentUser().uid;
    },
    setClient: function setClient(client) {
      this._client = client;
    },
    getClient: function getClient() {
      return this._client || new OC.Files.Client({
        host: OC.getHost(),
        root: OC.linkToRemoteBase('dav') + '/versions/' + this.getCurrentUser(),
        useHTTPS: OC.getProtocol() === 'https'
      });
    },
    url: function url() {
      return OC.linkToRemoteBase('dav') + '/versions/' + this.getCurrentUser() + '/versions/' + this._fileInfo.get('id');
    },
    parse: function parse(result) {
      var fullPath = this._fileInfo.getFullPath();

      var fileId = this._fileInfo.get('id');

      var name = this._fileInfo.get('name');

      var user = this.getCurrentUser();
      var client = this.getClient();
      return _.map(result, function (version) {
        version.fullPath = fullPath;
        version.fileId = fileId;
        version.name = name;
        version.timestamp = parseInt(moment(new Date(version.timestamp)).format('X'), 10);
        version.id = parseInt(OC.basename(version.href), 10);
        version.size = parseInt(version.size, 10);
        version.user = user;
        version.client = client;
        return version;
      });
    }
  });
  OCA.Versions = OCA.Versions || {};
  OCA.Versions.VersionCollection = VersionCollection;
})();

/***/ }),

/***/ "./apps/files_versions/src/versionmodel.js":
/*!*************************************************!*\
  !*** ./apps/files_versions/src/versionmodel.js ***!
  \*************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global moment */
(function () {
  /**
   * @memberof OCA.Versions
   */
  var VersionModel = OC.Backbone.Model.extend({
    sync: OC.Backbone.davSync,
    davProperties: {
      'size': '{DAV:}getcontentlength',
      'mimetype': '{DAV:}getcontenttype',
      'timestamp': '{DAV:}getlastmodified'
    },

    /**
     * Restores the original file to this revision
     */
    revert: function revert(options) {
      options = options ? _.clone(options) : {};
      var model = this;
      var client = this.get('client');
      return client.move('/versions/' + this.get('fileId') + '/' + this.get('id'), '/restore/target', true).done(function () {
        if (options.success) {
          options.success.call(options.context, model, {}, options);
        }

        model.trigger('revert', model, options);
      }).fail(function () {
        if (options.error) {
          options.error.call(options.context, model, {}, options);
        }

        model.trigger('error', model, {}, options);
      });
    },
    getFullPath: function getFullPath() {
      return this.get('fullPath');
    },
    getPreviewUrl: function getPreviewUrl() {
      var url = OC.generateUrl('/apps/files_versions/preview');
      var params = {
        file: this.get('fullPath'),
        version: this.get('timestamp')
      };
      return url + '?' + OC.buildQueryString(params);
    },
    getDownloadUrl: function getDownloadUrl() {
      return OC.linkToRemoteBase('dav') + '/versions/' + this.get('user') + '/versions/' + this.get('fileId') + '/' + this.get('id');
    }
  });
  OCA.Versions = OCA.Versions || {};
  OCA.Versions.VersionModel = VersionModel;
})();

/***/ }),

/***/ "./apps/files_versions/src/versionstabview.js":
/*!****************************************************!*\
  !*** ./apps/files_versions/src/versionstabview.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2015
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function () {
  /**
   * @memberof OCA.Versions
   */
  var VersionsTabView = OCA.Files.DetailTabView.extend(
  /** @lends OCA.Versions.VersionsTabView.prototype */
  {
    id: 'versionsTabView',
    className: 'tab versionsTabView',
    _template: null,
    $versionsContainer: null,
    events: {
      'click .revertVersion': '_onClickRevertVersion'
    },
    initialize: function initialize() {
      OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
      this.collection = new OCA.Versions.VersionCollection();
      this.collection.on('request', this._onRequest, this);
      this.collection.on('sync', this._onEndRequest, this);
      this.collection.on('update', this._onUpdate, this);
      this.collection.on('error', this._onError, this);
      this.collection.on('add', this._onAddModel, this);
    },
    getLabel: function getLabel() {
      return t('files_versions', 'Versions');
    },
    getIcon: function getIcon() {
      return 'icon-history';
    },
    nextPage: function nextPage() {
      if (this._loading) {
        return;
      }

      if (this.collection.getFileInfo() && this.collection.getFileInfo().isDirectory()) {
        return;
      }

      this.collection.fetch();
    },
    _onClickRevertVersion: function _onClickRevertVersion(ev) {
      var self = this;
      var $target = $(ev.target);
      var fileInfoModel = this.collection.getFileInfo();
      var revision;

      if (!$target.is('li')) {
        $target = $target.closest('li');
      }

      ev.preventDefault();
      revision = $target.attr('data-revision');
      var versionModel = this.collection.get(revision);
      versionModel.revert({
        success: function success() {
          // reset and re-fetch the updated collection
          self.$versionsContainer.empty();
          self.collection.setFileInfo(fileInfoModel);
          self.collection.reset([], {
            silent: true
          });
          self.collection.fetch();
          self.$el.find('.versions').removeClass('hidden'); // update original model

          fileInfoModel.trigger('busy', fileInfoModel, false);
          fileInfoModel.set({
            size: versionModel.get('size'),
            mtime: versionModel.get('timestamp') * 1000,
            // temp dummy, until we can do a PROPFIND
            etag: versionModel.get('id') + versionModel.get('timestamp')
          });
        },
        error: function error() {
          fileInfoModel.trigger('busy', fileInfoModel, false);
          self.$el.find('.versions').removeClass('hidden');

          self._toggleLoading(false);

          OC.Notification.show(t('files_version', 'Failed to revert {file} to revision {timestamp}.', {
            file: versionModel.getFullPath(),
            timestamp: OC.Util.formatDate(versionModel.get('timestamp') * 1000)
          }), {
            type: 'error'
          });
        }
      }); // spinner

      this._toggleLoading(true);

      fileInfoModel.trigger('busy', fileInfoModel, true);
    },
    _toggleLoading: function _toggleLoading(state) {
      this._loading = state;
      this.$el.find('.loading').toggleClass('hidden', !state);
    },
    _onRequest: function _onRequest() {
      this._toggleLoading(true);
    },
    _onEndRequest: function _onEndRequest() {
      this._toggleLoading(false);

      this.$el.find('.empty').toggleClass('hidden', !!this.collection.length);
    },
    _onAddModel: function _onAddModel(model) {
      var $el = $(this.itemTemplate(this._formatItem(model)));
      this.$versionsContainer.append($el);
      $el.find('.has-tooltip').tooltip();
    },
    template: function template(data) {
      return OCA.Versions.Templates['template'](data);
    },
    itemTemplate: function itemTemplate(data) {
      return OCA.Versions.Templates['item'](data);
    },
    setFileInfo: function setFileInfo(fileInfo) {
      if (fileInfo) {
        this.render();
        this.collection.setFileInfo(fileInfo);
        this.collection.reset([], {
          silent: true
        });
        this.nextPage();
      } else {
        this.render();
        this.collection.reset();
      }
    },
    _formatItem: function _formatItem(version) {
      var timestamp = version.get('timestamp') * 1000;
      var size = version.has('size') ? version.get('size') : 0;
      var preview = OC.MimeType.getIconUrl(version.get('mimetype'));
      var img = new Image();

      img.onload = function () {
        $('li[data-revision=' + version.get('timestamp') + '] .preview').attr('src', version.getPreviewUrl());
      };

      img.src = version.getPreviewUrl();
      return _.extend({
        versionId: version.get('id'),
        formattedTimestamp: OC.Util.formatDate(timestamp),
        relativeTimestamp: OC.Util.relativeModifiedDate(timestamp),
        millisecondsTimestamp: timestamp,
        humanReadableSize: OC.Util.humanFileSize(size, true),
        altSize: n('files', '%n byte', '%n bytes', size),
        hasDetails: version.has('size'),
        downloadUrl: version.getDownloadUrl(),
        downloadIconUrl: OC.imagePath('core', 'actions/download'),
        downloadName: version.get('name'),
        revertIconUrl: OC.imagePath('core', 'actions/history'),
        previewUrl: preview,
        revertLabel: t('files_versions', 'Restore'),
        canRevert: (this.collection.getFileInfo().get('permissions') & OC.PERMISSION_UPDATE) !== 0
      }, version.attributes);
    },

    /**
     * Renders this details view
     */
    render: function render() {
      this.$el.html(this.template({
        emptyResultLabel: t('files_versions', 'No other versions available')
      }));
      this.$el.find('.has-tooltip').tooltip();
      this.$versionsContainer = this.$el.find('ul.versions');
      this.delegateEvents();
    },

    /**
     * Returns true for files, false for folders.
     *
     * @return {bool} true for files, false for folders
     */
    canDisplay: function canDisplay(fileInfo) {
      if (!fileInfo) {
        return false;
      }

      return !fileInfo.isDirectory();
    }
  });
  OCA.Versions = OCA.Versions || {};
  OCA.Versions.VersionsTabView = VersionsTabView;
})();

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./apps/files_versions/src/css/versions.css":
/*!****************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./apps/files_versions/src/css/versions.css ***!
  \****************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, ".versionsTabView .clear-float {\n\tclear: both;\n}\n\n.versionsTabView li {\n\twidth: 100%;\n\tcursor: default;\n\theight: 56px;\n\tfloat: left;\n\tborder-bottom: 1px solid rgba(100,100,100,.1);\n}\n.versionsTabView li:last-child {\n\tborder-bottom: none;\n}\n\n.versionsTabView a,\n.versionsTabView div > span {\n\tvertical-align: middle;\n\topacity: .5;\n}\n\n.versionsTabView li a{\n\tpadding: 15px 10px 11px;\n}\n\n.versionsTabView a:hover,\n.versionsTabView a:focus {\n\topacity: 1;\n}\n\n.versionsTabView .preview-container {\n\tdisplay: inline-block;\n  vertical-align: top;\n}\n\n.versionsTabView img {\n\tcursor: pointer;\n\tpadding-right: 4px;\n}\n\n.versionsTabView img.preview {\n\tcursor: default;\n}\n\n.versionsTabView .version-container {\n\tdisplay: inline-block;\n}\n\n.versionsTabView .versiondate {\n\tmin-width: 100px;\n\tvertical-align: super;\n}\n\n.versionsTabView .version-details {\n\ttext-align: left;\n}\n\n.versionsTabView .version-details > span {\n\tpadding: 0 10px;\n}\n\n.versionsTabView .revertVersion {\n\tcursor: pointer;\n\tfloat: right;\n\tmargin-right: -10px;\n}\n", ""]);



/***/ }),

/***/ "./node_modules/css-loader/dist/runtime/api.js":
/*!*****************************************************!*\
  !*** ./node_modules/css-loader/dist/runtime/api.js ***!
  \*****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

"use strict";


/*
  MIT License http://www.opensource.org/licenses/mit-license.php
  Author Tobias Koppers @sokra
*/
// css base code, injected by the css-loader
module.exports = function (useSourceMap) {
  var list = []; // return the list of modules as css string

  list.toString = function toString() {
    return this.map(function (item) {
      var content = cssWithMappingToString(item, useSourceMap);

      if (item[2]) {
        return '@media ' + item[2] + '{' + content + '}';
      } else {
        return content;
      }
    }).join('');
  }; // import a list of modules into the list


  list.i = function (modules, mediaQuery) {
    if (typeof modules === 'string') {
      modules = [[null, modules, '']];
    }

    var alreadyImportedModules = {};

    for (var i = 0; i < this.length; i++) {
      var id = this[i][0];

      if (id != null) {
        alreadyImportedModules[id] = true;
      }
    }

    for (i = 0; i < modules.length; i++) {
      var item = modules[i]; // skip already imported module
      // this implementation is not 100% perfect for weird media query combinations
      // when a module is imported multiple times with different media queries.
      // I hope this will never occur (Hey this way we have smaller bundles)

      if (item[0] == null || !alreadyImportedModules[item[0]]) {
        if (mediaQuery && !item[2]) {
          item[2] = mediaQuery;
        } else if (mediaQuery) {
          item[2] = '(' + item[2] + ') and (' + mediaQuery + ')';
        }

        list.push(item);
      }
    }
  };

  return list;
};

function cssWithMappingToString(item, useSourceMap) {
  var content = item[1] || '';
  var cssMapping = item[3];

  if (!cssMapping) {
    return content;
  }

  if (useSourceMap && typeof btoa === 'function') {
    var sourceMapping = toComment(cssMapping);
    var sourceURLs = cssMapping.sources.map(function (source) {
      return '/*# sourceURL=' + cssMapping.sourceRoot + source + ' */';
    });
    return [content].concat(sourceURLs).concat([sourceMapping]).join('\n');
  }

  return [content].join('\n');
} // Adapted from convert-source-map (MIT)


function toComment(sourceMap) {
  // eslint-disable-next-line no-undef
  var base64 = btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap))));
  var data = 'sourceMappingURL=data:application/json;charset=utf-8;base64,' + base64;
  return '/*# ' + data + ' */';
}

/***/ }),

/***/ "./node_modules/style-loader/lib/addStyles.js":
/*!****************************************************!*\
  !*** ./node_modules/style-loader/lib/addStyles.js ***!
  \****************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

/*
	MIT License http://www.opensource.org/licenses/mit-license.php
	Author Tobias Koppers @sokra
*/

var stylesInDom = {};

var	memoize = function (fn) {
	var memo;

	return function () {
		if (typeof memo === "undefined") memo = fn.apply(this, arguments);
		return memo;
	};
};

var isOldIE = memoize(function () {
	// Test for IE <= 9 as proposed by Browserhacks
	// @see http://browserhacks.com/#hack-e71d8692f65334173fee715c222cb805
	// Tests for existence of standard globals is to allow style-loader
	// to operate correctly into non-standard environments
	// @see https://github.com/webpack-contrib/style-loader/issues/177
	return window && document && document.all && !window.atob;
});

var getTarget = function (target, parent) {
  if (parent){
    return parent.querySelector(target);
  }
  return document.querySelector(target);
};

var getElement = (function (fn) {
	var memo = {};

	return function(target, parent) {
                // If passing function in options, then use it for resolve "head" element.
                // Useful for Shadow Root style i.e
                // {
                //   insertInto: function () { return document.querySelector("#foo").shadowRoot }
                // }
                if (typeof target === 'function') {
                        return target();
                }
                if (typeof memo[target] === "undefined") {
			var styleTarget = getTarget.call(this, target, parent);
			// Special case to return head of iframe instead of iframe itself
			if (window.HTMLIFrameElement && styleTarget instanceof window.HTMLIFrameElement) {
				try {
					// This will throw an exception if access to iframe is blocked
					// due to cross-origin restrictions
					styleTarget = styleTarget.contentDocument.head;
				} catch(e) {
					styleTarget = null;
				}
			}
			memo[target] = styleTarget;
		}
		return memo[target]
	};
})();

var singleton = null;
var	singletonCounter = 0;
var	stylesInsertedAtTop = [];

var	fixUrls = __webpack_require__(/*! ./urls */ "./node_modules/style-loader/lib/urls.js");

module.exports = function(list, options) {
	if (typeof DEBUG !== "undefined" && DEBUG) {
		if (typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
	}

	options = options || {};

	options.attrs = typeof options.attrs === "object" ? options.attrs : {};

	// Force single-tag solution on IE6-9, which has a hard limit on the # of <style>
	// tags it will allow on a page
	if (!options.singleton && typeof options.singleton !== "boolean") options.singleton = isOldIE();

	// By default, add <style> tags to the <head> element
        if (!options.insertInto) options.insertInto = "head";

	// By default, add <style> tags to the bottom of the target
	if (!options.insertAt) options.insertAt = "bottom";

	var styles = listToStyles(list, options);

	addStylesToDom(styles, options);

	return function update (newList) {
		var mayRemove = [];

		for (var i = 0; i < styles.length; i++) {
			var item = styles[i];
			var domStyle = stylesInDom[item.id];

			domStyle.refs--;
			mayRemove.push(domStyle);
		}

		if(newList) {
			var newStyles = listToStyles(newList, options);
			addStylesToDom(newStyles, options);
		}

		for (var i = 0; i < mayRemove.length; i++) {
			var domStyle = mayRemove[i];

			if(domStyle.refs === 0) {
				for (var j = 0; j < domStyle.parts.length; j++) domStyle.parts[j]();

				delete stylesInDom[domStyle.id];
			}
		}
	};
};

function addStylesToDom (styles, options) {
	for (var i = 0; i < styles.length; i++) {
		var item = styles[i];
		var domStyle = stylesInDom[item.id];

		if(domStyle) {
			domStyle.refs++;

			for(var j = 0; j < domStyle.parts.length; j++) {
				domStyle.parts[j](item.parts[j]);
			}

			for(; j < item.parts.length; j++) {
				domStyle.parts.push(addStyle(item.parts[j], options));
			}
		} else {
			var parts = [];

			for(var j = 0; j < item.parts.length; j++) {
				parts.push(addStyle(item.parts[j], options));
			}

			stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
		}
	}
}

function listToStyles (list, options) {
	var styles = [];
	var newStyles = {};

	for (var i = 0; i < list.length; i++) {
		var item = list[i];
		var id = options.base ? item[0] + options.base : item[0];
		var css = item[1];
		var media = item[2];
		var sourceMap = item[3];
		var part = {css: css, media: media, sourceMap: sourceMap};

		if(!newStyles[id]) styles.push(newStyles[id] = {id: id, parts: [part]});
		else newStyles[id].parts.push(part);
	}

	return styles;
}

function insertStyleElement (options, style) {
	var target = getElement(options.insertInto)

	if (!target) {
		throw new Error("Couldn't find a style target. This probably means that the value for the 'insertInto' parameter is invalid.");
	}

	var lastStyleElementInsertedAtTop = stylesInsertedAtTop[stylesInsertedAtTop.length - 1];

	if (options.insertAt === "top") {
		if (!lastStyleElementInsertedAtTop) {
			target.insertBefore(style, target.firstChild);
		} else if (lastStyleElementInsertedAtTop.nextSibling) {
			target.insertBefore(style, lastStyleElementInsertedAtTop.nextSibling);
		} else {
			target.appendChild(style);
		}
		stylesInsertedAtTop.push(style);
	} else if (options.insertAt === "bottom") {
		target.appendChild(style);
	} else if (typeof options.insertAt === "object" && options.insertAt.before) {
		var nextSibling = getElement(options.insertAt.before, target);
		target.insertBefore(style, nextSibling);
	} else {
		throw new Error("[Style Loader]\n\n Invalid value for parameter 'insertAt' ('options.insertAt') found.\n Must be 'top', 'bottom', or Object.\n (https://github.com/webpack-contrib/style-loader#insertat)\n");
	}
}

function removeStyleElement (style) {
	if (style.parentNode === null) return false;
	style.parentNode.removeChild(style);

	var idx = stylesInsertedAtTop.indexOf(style);
	if(idx >= 0) {
		stylesInsertedAtTop.splice(idx, 1);
	}
}

function createStyleElement (options) {
	var style = document.createElement("style");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}

	if(options.attrs.nonce === undefined) {
		var nonce = getNonce();
		if (nonce) {
			options.attrs.nonce = nonce;
		}
	}

	addAttrs(style, options.attrs);
	insertStyleElement(options, style);

	return style;
}

function createLinkElement (options) {
	var link = document.createElement("link");

	if(options.attrs.type === undefined) {
		options.attrs.type = "text/css";
	}
	options.attrs.rel = "stylesheet";

	addAttrs(link, options.attrs);
	insertStyleElement(options, link);

	return link;
}

function addAttrs (el, attrs) {
	Object.keys(attrs).forEach(function (key) {
		el.setAttribute(key, attrs[key]);
	});
}

function getNonce() {
	if (false) {}

	return __webpack_require__.nc;
}

function addStyle (obj, options) {
	var style, update, remove, result;

	// If a transform function was defined, run it on the css
	if (options.transform && obj.css) {
	    result = typeof options.transform === 'function'
		 ? options.transform(obj.css) 
		 : options.transform.default(obj.css);

	    if (result) {
	    	// If transform returns a value, use that instead of the original css.
	    	// This allows running runtime transformations on the css.
	    	obj.css = result;
	    } else {
	    	// If the transform function returns a falsy value, don't add this css.
	    	// This allows conditional loading of css
	    	return function() {
	    		// noop
	    	};
	    }
	}

	if (options.singleton) {
		var styleIndex = singletonCounter++;

		style = singleton || (singleton = createStyleElement(options));

		update = applyToSingletonTag.bind(null, style, styleIndex, false);
		remove = applyToSingletonTag.bind(null, style, styleIndex, true);

	} else if (
		obj.sourceMap &&
		typeof URL === "function" &&
		typeof URL.createObjectURL === "function" &&
		typeof URL.revokeObjectURL === "function" &&
		typeof Blob === "function" &&
		typeof btoa === "function"
	) {
		style = createLinkElement(options);
		update = updateLink.bind(null, style, options);
		remove = function () {
			removeStyleElement(style);

			if(style.href) URL.revokeObjectURL(style.href);
		};
	} else {
		style = createStyleElement(options);
		update = applyToTag.bind(null, style);
		remove = function () {
			removeStyleElement(style);
		};
	}

	update(obj);

	return function updateStyle (newObj) {
		if (newObj) {
			if (
				newObj.css === obj.css &&
				newObj.media === obj.media &&
				newObj.sourceMap === obj.sourceMap
			) {
				return;
			}

			update(obj = newObj);
		} else {
			remove();
		}
	};
}

var replaceText = (function () {
	var textStore = [];

	return function (index, replacement) {
		textStore[index] = replacement;

		return textStore.filter(Boolean).join('\n');
	};
})();

function applyToSingletonTag (style, index, remove, obj) {
	var css = remove ? "" : obj.css;

	if (style.styleSheet) {
		style.styleSheet.cssText = replaceText(index, css);
	} else {
		var cssNode = document.createTextNode(css);
		var childNodes = style.childNodes;

		if (childNodes[index]) style.removeChild(childNodes[index]);

		if (childNodes.length) {
			style.insertBefore(cssNode, childNodes[index]);
		} else {
			style.appendChild(cssNode);
		}
	}
}

function applyToTag (style, obj) {
	var css = obj.css;
	var media = obj.media;

	if(media) {
		style.setAttribute("media", media)
	}

	if(style.styleSheet) {
		style.styleSheet.cssText = css;
	} else {
		while(style.firstChild) {
			style.removeChild(style.firstChild);
		}

		style.appendChild(document.createTextNode(css));
	}
}

function updateLink (link, options, obj) {
	var css = obj.css;
	var sourceMap = obj.sourceMap;

	/*
		If convertToAbsoluteUrls isn't defined, but sourcemaps are enabled
		and there is no publicPath defined then lets turn convertToAbsoluteUrls
		on by default.  Otherwise default to the convertToAbsoluteUrls option
		directly
	*/
	var autoFixUrls = options.convertToAbsoluteUrls === undefined && sourceMap;

	if (options.convertToAbsoluteUrls || autoFixUrls) {
		css = fixUrls(css);
	}

	if (sourceMap) {
		// http://stackoverflow.com/a/26603875
		css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(unescape(encodeURIComponent(JSON.stringify(sourceMap)))) + " */";
	}

	var blob = new Blob([css], { type: "text/css" });

	var oldSrc = link.href;

	link.href = URL.createObjectURL(blob);

	if(oldSrc) URL.revokeObjectURL(oldSrc);
}


/***/ }),

/***/ "./node_modules/style-loader/lib/urls.js":
/*!***********************************************!*\
  !*** ./node_modules/style-loader/lib/urls.js ***!
  \***********************************************/
/*! no static exports found */
/***/ (function(module, exports) {


/**
 * When source maps are enabled, `style-loader` uses a link element with a data-uri to
 * embed the css on the page. This breaks all relative urls because now they are relative to a
 * bundle instead of the current page.
 *
 * One solution is to only use full urls, but that may be impossible.
 *
 * Instead, this function "fixes" the relative urls to be absolute according to the current page location.
 *
 * A rudimentary test suite is located at `test/fixUrls.js` and can be run via the `npm test` command.
 *
 */

module.exports = function (css) {
  // get current location
  var location = typeof window !== "undefined" && window.location;

  if (!location) {
    throw new Error("fixUrls requires window.location");
  }

	// blank or null?
	if (!css || typeof css !== "string") {
	  return css;
  }

  var baseUrl = location.protocol + "//" + location.host;
  var currentDir = baseUrl + location.pathname.replace(/\/[^\/]*$/, "/");

	// convert each url(...)
	/*
	This regular expression is just a way to recursively match brackets within
	a string.

	 /url\s*\(  = Match on the word "url" with any whitespace after it and then a parens
	   (  = Start a capturing group
	     (?:  = Start a non-capturing group
	         [^)(]  = Match anything that isn't a parentheses
	         |  = OR
	         \(  = Match a start parentheses
	             (?:  = Start another non-capturing groups
	                 [^)(]+  = Match anything that isn't a parentheses
	                 |  = OR
	                 \(  = Match a start parentheses
	                     [^)(]*  = Match anything that isn't a parentheses
	                 \)  = Match a end parentheses
	             )  = End Group
              *\) = Match anything and then a close parens
          )  = Close non-capturing group
          *  = Match anything
       )  = Close capturing group
	 \)  = Match a close parens

	 /gi  = Get all matches, not the first.  Be case insensitive.
	 */
	var fixedCss = css.replace(/url\s*\(((?:[^)(]|\((?:[^)(]+|\([^)(]*\))*\))*)\)/gi, function(fullMatch, origUrl) {
		// strip quotes (if they exist)
		var unquotedOrigUrl = origUrl
			.trim()
			.replace(/^"(.*)"$/, function(o, $1){ return $1; })
			.replace(/^'(.*)'$/, function(o, $1){ return $1; });

		// already a full url? no change
		if (/^(#|data:|http:\/\/|https:\/\/|file:\/\/\/|\s*$)/i.test(unquotedOrigUrl)) {
		  return fullMatch;
		}

		// convert the url to a full url
		var newUrl;

		if (unquotedOrigUrl.indexOf("//") === 0) {
		  	//TODO: should we add protocol?
			newUrl = unquotedOrigUrl;
		} else if (unquotedOrigUrl.indexOf("/") === 0) {
			// path should be relative to the base url
			newUrl = baseUrl + unquotedOrigUrl; // already starts with '/'
		} else {
			// path should be relative to current directory
			newUrl = currentDir + unquotedOrigUrl.replace(/^\.\//, ""); // Strip leading './'
		}

		// send back the fixed url(...)
		return "url(" + JSON.stringify(newUrl) + ")";
	});

	// send back the fixed css
	return fixedCss;
};


/***/ })

/******/ });
//# sourceMappingURL=files_versions.js.map