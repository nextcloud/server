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
/******/ 	__webpack_require__.p = "";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = "./core/js/systemtags/merged-systemtags.js");
/******/ })
/************************************************************************/
/******/ ({

/***/ "./core/css/systemtags.scss":
/*!**********************************!*\
  !*** ./core/css/systemtags.scss ***!
  \**********************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {


var content = __webpack_require__(/*! !../../node_modules/css-loader/dist/cjs.js!../../node_modules/sass-loader/lib/loader.js!./systemtags.scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./core/css/systemtags.scss");

if(typeof content === 'string') content = [[module.i, content, '']];

var transform;
var insertInto;



var options = {"hmr":true}

options.transform = transform
options.insertInto = undefined;

var update = __webpack_require__(/*! ../../node_modules/style-loader/lib/addStyles.js */ "./node_modules/style-loader/lib/addStyles.js")(content, options);

if(content.locals) module.exports = content.locals;

if(false) {}

/***/ }),

/***/ "./core/js/systemtags/merged-systemtags.js":
/*!*************************************************!*\
  !*** ./core/js/systemtags/merged-systemtags.js ***!
  \*************************************************/
/*! no exports provided */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _systemtags_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./systemtags.js */ "./core/js/systemtags/systemtags.js");
/* harmony import */ var _systemtags_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_systemtags_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _templates_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./templates.js */ "./core/js/systemtags/templates.js");
/* harmony import */ var _templates_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_templates_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _systemtagmodel_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./systemtagmodel.js */ "./core/js/systemtags/systemtagmodel.js");
/* harmony import */ var _systemtagmodel_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_systemtagmodel_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _systemtagsmappingcollection_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./systemtagsmappingcollection.js */ "./core/js/systemtags/systemtagsmappingcollection.js");
/* harmony import */ var _systemtagsmappingcollection_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_systemtagsmappingcollection_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _systemtagscollection_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./systemtagscollection.js */ "./core/js/systemtags/systemtagscollection.js");
/* harmony import */ var _systemtagscollection_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_systemtagscollection_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _systemtagsinputfield_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./systemtagsinputfield.js */ "./core/js/systemtags/systemtagsinputfield.js");
/* harmony import */ var _systemtagsinputfield_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_systemtagsinputfield_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _css_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ../../css/systemtags.scss */ "./core/css/systemtags.scss");
/* harmony import */ var _css_systemtags_scss__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_css_systemtags_scss__WEBPACK_IMPORTED_MODULE_6__);








/***/ }),

/***/ "./core/js/systemtags/systemtagmodel.js":
/*!**********************************************!*\
  !*** ./core/js/systemtags/systemtagmodel.js ***!
  \**********************************************/
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
(function (OC) {
  _.extend(OC.Files.Client, {
    PROPERTY_FILEID: '{' + OC.Files.Client.NS_OWNCLOUD + '}id',
    PROPERTY_CAN_ASSIGN: '{' + OC.Files.Client.NS_OWNCLOUD + '}can-assign',
    PROPERTY_DISPLAYNAME: '{' + OC.Files.Client.NS_OWNCLOUD + '}display-name',
    PROPERTY_USERVISIBLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-visible',
    PROPERTY_USERASSIGNABLE: '{' + OC.Files.Client.NS_OWNCLOUD + '}user-assignable'
  });
  /**
   * @class OCA.SystemTags.SystemTagsCollection
   * @classdesc
   *
   * System tag
   *
   */


  var SystemTagModel = OC.Backbone.Model.extend(
  /** @lends OCA.SystemTags.SystemTagModel.prototype */
  {
    sync: OC.Backbone.davSync,
    defaults: {
      userVisible: true,
      userAssignable: true,
      canAssign: true
    },
    davProperties: {
      'id': OC.Files.Client.PROPERTY_FILEID,
      'name': OC.Files.Client.PROPERTY_DISPLAYNAME,
      'userVisible': OC.Files.Client.PROPERTY_USERVISIBLE,
      'userAssignable': OC.Files.Client.PROPERTY_USERASSIGNABLE,
      // read-only, effective permissions computed by the server,
      'canAssign': OC.Files.Client.PROPERTY_CAN_ASSIGN
    },
    parse: function parse(data) {
      return {
        id: data.id,
        name: data.name,
        userVisible: data.userVisible === true || data.userVisible === 'true',
        userAssignable: data.userAssignable === true || data.userAssignable === 'true',
        canAssign: data.canAssign === true || data.canAssign === 'true'
      };
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagModel = SystemTagModel;
})(OC);

/***/ }),

/***/ "./core/js/systemtags/systemtags.js":
/*!******************************************!*\
  !*** ./core/js/systemtags/systemtags.js ***!
  \******************************************/
/*! no static exports found */
/***/ (function(module, exports) {

/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */
(function (OC) {
  /**
   * @namespace
   */
  OC.SystemTags = {
    /**
     *
     * @param {OC.SystemTags.SystemTagModel|Object|String} tag
     * @return {jQuery}
     */
    getDescriptiveTag: function getDescriptiveTag(tag) {
      if (_.isUndefined(tag.name) && !_.isUndefined(tag.toJSON)) {
        tag = tag.toJSON();
      }

      if (_.isUndefined(tag.name)) {
        return $('<span>').addClass('non-existing-tag').text(t('core', 'Non-existing tag #{tag}', {
          tag: tag
        }));
      }

      var $span = $('<span>');
      $span.append(escapeHTML(tag.name));
      var scope;

      if (!tag.userAssignable) {
        scope = t('core', 'restricted');
      }

      if (!tag.userVisible) {
        // invisible also implicitly means not assignable
        scope = t('core', 'invisible');
      }

      if (scope) {
        var $tag = $('<em>').text(' ' + t('core', '({scope})', {
          scope: scope
        }));
        $span.append($tag);
      }

      return $span;
    }
  };
})(OC);

/***/ }),

/***/ "./core/js/systemtags/systemtagscollection.js":
/*!****************************************************!*\
  !*** ./core/js/systemtags/systemtagscollection.js ***!
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
(function (OC) {
  function filterFunction(model, term) {
    return model.get('name').substr(0, term.length).toLowerCase() === term.toLowerCase();
  }
  /**
   * @class OCA.SystemTags.SystemTagsCollection
   * @classdesc
   *
   * Collection of tags assigned to a file
   *
   */


  var SystemTagsCollection = OC.Backbone.Collection.extend(
  /** @lends OC.SystemTags.SystemTagsCollection.prototype */
  {
    sync: OC.Backbone.davSync,
    model: OC.SystemTags.SystemTagModel,
    url: function url() {
      return OC.linkToRemote('dav') + '/systemtags/';
    },
    filterByName: function filterByName(name) {
      return this.filter(function (model) {
        return filterFunction(model, name);
      });
    },
    reset: function reset() {
      this.fetched = false;
      return OC.Backbone.Collection.prototype.reset.apply(this, arguments);
    },

    /**
     * Lazy fetch.
     * Only fetches once, subsequent calls will directly call the success handler.
     *
     * @param options
     * @param [options.force] true to force fetch even if cached entries exist
     *
     * @see Backbone.Collection#fetch
     */
    fetch: function fetch(options) {
      var self = this;
      options = options || {};

      if (this.fetched || options.force) {
        // directly call handler
        if (options.success) {
          options.success(this, null, options);
        } // trigger sync event


        this.trigger('sync', this, null, options);
        return Promise.resolve();
      }

      var success = options.success;
      options = _.extend({}, options);

      options.success = function () {
        self.fetched = true;

        if (success) {
          return success.apply(this, arguments);
        }
      };

      return OC.Backbone.Collection.prototype.fetch.call(this, options);
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsCollection = SystemTagsCollection;
  /**
   * @type OC.SystemTags.SystemTagsCollection
   */

  OC.SystemTags.collection = new OC.SystemTags.SystemTagsCollection();
})(OC);

/***/ }),

/***/ "./core/js/systemtags/systemtagsinputfield.js":
/*!****************************************************!*\
  !*** ./core/js/systemtags/systemtagsinputfield.js ***!
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

/* global Handlebars */
(function (OC) {
  /**
   * @class OC.SystemTags.SystemTagsInputField
   * @classdesc
   *
   * Displays a file's system tags
   *
   */
  var SystemTagsInputField = OC.Backbone.View.extend(
  /** @lends OC.SystemTags.SystemTagsInputField.prototype */
  {
    _rendered: false,
    _newTag: null,
    _lastUsedTags: [],
    className: 'systemTagsInputFieldContainer',
    template: function template(data) {
      return '<input class="systemTagsInputField" type="hidden" name="tags" value=""/>';
    },

    /**
     * Creates a new SystemTagsInputField
     *
     * @param {Object} [options]
     * @param {string} [options.objectType=files] object type for which tags are assigned to
     * @param {bool} [options.multiple=false] whether to allow selecting multiple tags
     * @param {bool} [options.allowActions=true] whether tags can be renamed/delete within the dropdown
     * @param {bool} [options.allowCreate=true] whether new tags can be created
     * @param {bool} [options.isAdmin=true] whether the user is an administrator
     * @param {Function} options.initSelection function to convert selection to data
     */
    initialize: function initialize(options) {
      options = options || {};
      this._multiple = !!options.multiple;
      this._allowActions = _.isUndefined(options.allowActions) || !!options.allowActions;
      this._allowCreate = _.isUndefined(options.allowCreate) || !!options.allowCreate;
      this._isAdmin = !!options.isAdmin;

      if (_.isFunction(options.initSelection)) {
        this._initSelection = options.initSelection;
      }

      this.collection = options.collection || OC.SystemTags.collection;
      var self = this;
      this.collection.on('change:name remove', function () {
        // refresh selection
        _.defer(self._refreshSelection);
      });

      _.defer(_.bind(this._getLastUsedTags, this));

      _.bindAll(this, '_refreshSelection', '_onClickRenameTag', '_onClickDeleteTag', '_onSelectTag', '_onDeselectTag', '_onSubmitRenameTag');
    },
    _getLastUsedTags: function _getLastUsedTags() {
      var self = this;
      $.ajax({
        type: 'GET',
        url: OC.generateUrl('/apps/systemtags/lastused'),
        success: function success(response) {
          self._lastUsedTags = response;
        }
      });
    },

    /**
     * Refreshes the selection, triggering a call to
     * select2's initSelection
     */
    _refreshSelection: function _refreshSelection() {
      this.$tagsField.select2('val', this.$tagsField.val());
    },

    /**
     * Event handler whenever the user clicked the "rename" action.
     * This will display the rename field.
     */
    _onClickRenameTag: function _onClickRenameTag(ev) {
      var $item = $(ev.target).closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      var tagModel = this.collection.get(tagId);
      var oldName = tagModel.get('name');
      var $renameForm = $(OC.SystemTags.Templates['result_form']({
        cid: this.cid,
        name: oldName,
        deleteTooltip: t('core', 'Delete'),
        renameLabel: t('core', 'Rename'),
        isAdmin: this._isAdmin
      }));
      $item.find('.label').after($renameForm);
      $item.find('.label, .systemtags-actions').addClass('hidden');
      $item.closest('.select2-result').addClass('has-form');
      $renameForm.find('[title]').tooltip({
        placement: 'bottom',
        container: 'body'
      });
      $renameForm.find('input').focus().selectRange(0, oldName.length);
      return false;
    },

    /**
     * Event handler whenever the rename form has been submitted after
     * the user entered a new tag name.
     * This will submit the change to the server. 
     *
     * @param {Object} ev event
     */
    _onSubmitRenameTag: function _onSubmitRenameTag(ev) {
      ev.preventDefault();
      var $form = $(ev.target);
      var $item = $form.closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      var tagModel = this.collection.get(tagId);
      var newName = $(ev.target).find('input').val().trim();

      if (newName && newName !== tagModel.get('name')) {
        tagModel.save({
          'name': newName
        }); // TODO: spinner, and only change text after finished saving

        $item.find('.label').text(newName);
      }

      $item.find('.label, .systemtags-actions').removeClass('hidden');
      $form.remove();
      $item.closest('.select2-result').removeClass('has-form');
    },

    /**
     * Event handler whenever a tag must be deleted
     *
     * @param {Object} ev event
     */
    _onClickDeleteTag: function _onClickDeleteTag(ev) {
      var $item = $(ev.target).closest('.systemtags-item');
      var tagId = $item.attr('data-id');
      this.collection.get(tagId).destroy();
      $(ev.target).tooltip('hide');
      $item.closest('.select2-result').remove(); // TODO: spinner

      return false;
    },
    _addToSelect2Selection: function _addToSelect2Selection(selection) {
      var data = this.$tagsField.select2('data');
      data.push(selection);
      this.$tagsField.select2('data', data);
    },

    /**
     * Event handler whenever a tag is selected.
     * Also called whenever tag creation is requested through the dummy tag object.
     *
     * @param {Object} e event
     */
    _onSelectTag: function _onSelectTag(e) {
      var self = this;
      var tag;

      if (e.object && e.object.isNew) {
        // newly created tag, check if existing
        // create a new tag
        tag = this.collection.create({
          name: e.object.name.trim(),
          userVisible: true,
          userAssignable: true,
          canAssign: true
        }, {
          success: function success(model) {
            self._addToSelect2Selection(model.toJSON());

            self._lastUsedTags.unshift(model.id);

            self.trigger('select', model);
          },
          error: function error(model, xhr) {
            if (xhr.status === 409) {
              // re-fetch collection to get the missing tag
              self.collection.reset();
              self.collection.fetch({
                success: function success(collection) {
                  // find the tag in the collection
                  var model = collection.where({
                    name: e.object.name.trim(),
                    userVisible: true,
                    userAssignable: true
                  });

                  if (model.length) {
                    model = model[0]; // the tag already exists or was already assigned,
                    // add it to the list anyway

                    self._addToSelect2Selection(model.toJSON());

                    self.trigger('select', model);
                  }
                }
              });
            }
          }
        });
        this.$tagsField.select2('close');
        e.preventDefault();
        return false;
      } else {
        tag = this.collection.get(e.object.id);

        this._lastUsedTags.unshift(tag.id);
      }

      this._newTag = null;
      this.trigger('select', tag);
    },

    /**
     * Event handler whenever a tag gets deselected.
     *
     * @param {Object} e event
     */
    _onDeselectTag: function _onDeselectTag(e) {
      this.trigger('deselect', e.choice.id);
    },

    /**
     * Autocomplete function for dropdown results
     *
     * @param {Object} query select2 query object
     */
    _queryTagsAutocomplete: function _queryTagsAutocomplete(query) {
      var self = this;
      this.collection.fetch({
        success: function success(collection) {
          var tagModels = collection.filterByName(query.term.trim());

          if (!self._isAdmin) {
            tagModels = _.filter(tagModels, function (tagModel) {
              return tagModel.get('canAssign');
            });
          }

          query.callback({
            results: _.invoke(tagModels, 'toJSON')
          });
        }
      });
    },
    _preventDefault: function _preventDefault(e) {
      e.stopPropagation();
    },

    /**
     * Formats a single dropdown result
     *
     * @param {Object} data data to format
     * @return {string} HTML markup
     */
    _formatDropDownResult: function _formatDropDownResult(data) {
      return OC.SystemTags.Templates['result'](_.extend({
        renameTooltip: t('core', 'Rename'),
        allowActions: this._allowActions,
        tagMarkup: this._isAdmin ? OC.SystemTags.getDescriptiveTag(data)[0].innerHTML : null,
        isAdmin: this._isAdmin
      }, data));
    },

    /**
     * Formats a single selection item
     *
     * @param {Object} data data to format
     * @return {string} HTML markup
     */
    _formatSelection: function _formatSelection(data) {
      return OC.SystemTags.Templates['selection'](_.extend({
        tagMarkup: this._isAdmin ? OC.SystemTags.getDescriptiveTag(data)[0].innerHTML : null,
        isAdmin: this._isAdmin
      }, data));
    },

    /**
     * Create new dummy choice for select2 when the user
     * types an arbitrary string
     *
     * @param {string} term entered term
     * @return {Object} dummy tag
     */
    _createSearchChoice: function _createSearchChoice(term) {
      term = term.trim();

      if (this.collection.filter(function (entry) {
        return entry.get('name') === term;
      }).length) {
        return;
      }

      if (!this._newTag) {
        this._newTag = {
          id: -1,
          name: term,
          userAssignable: true,
          userVisible: true,
          canAssign: true,
          isNew: true
        };
      } else {
        this._newTag.name = term;
      }

      return this._newTag;
    },
    _initSelection: function _initSelection(element, callback) {
      var self = this;
      var ids = $(element).val().split(',');

      function modelToSelection(model) {
        var data = model.toJSON();

        if (!self._isAdmin && !data.canAssign) {
          // lock static tags for non-admins
          data.locked = true;
        }

        return data;
      }

      function findSelectedObjects(ids) {
        var selectedModels = self.collection.filter(function (model) {
          return ids.indexOf(model.id) >= 0 && (self._isAdmin || model.get('userVisible'));
        });
        return _.map(selectedModels, modelToSelection);
      }

      this.collection.fetch({
        success: function success() {
          callback(findSelectedObjects(ids));
        }
      });
    },

    /**
     * Renders this details view
     */
    render: function render() {
      var self = this;
      this.$el.html(this.template());
      this.$el.find('[title]').tooltip({
        placement: 'bottom'
      });
      this.$tagsField = this.$el.find('[name=tags]');
      this.$tagsField.select2({
        placeholder: t('core', 'Collaborative tags'),
        containerCssClass: 'systemtags-select2-container',
        dropdownCssClass: 'systemtags-select2-dropdown',
        closeOnSelect: false,
        allowClear: false,
        multiple: this._multiple,
        toggleSelect: this._multiple,
        query: _.bind(this._queryTagsAutocomplete, this),
        id: function id(tag) {
          return tag.id;
        },
        initSelection: _.bind(this._initSelection, this),
        formatResult: _.bind(this._formatDropDownResult, this),
        formatSelection: _.bind(this._formatSelection, this),
        createSearchChoice: this._allowCreate ? _.bind(this._createSearchChoice, this) : undefined,
        sortResults: function sortResults(results) {
          var selectedItems = _.pluck(self.$tagsField.select2('data'), 'id');

          results.sort(function (a, b) {
            var aSelected = selectedItems.indexOf(a.id) >= 0;
            var bSelected = selectedItems.indexOf(b.id) >= 0;

            if (aSelected === bSelected) {
              var aLastUsed = self._lastUsedTags.indexOf(a.id);

              var bLastUsed = self._lastUsedTags.indexOf(b.id);

              if (aLastUsed !== bLastUsed) {
                if (bLastUsed === -1) {
                  return -1;
                }

                if (aLastUsed === -1) {
                  return 1;
                }

                return aLastUsed < bLastUsed ? -1 : 1;
              } // Both not found


              return OC.Util.naturalSortCompare(a.name, b.name);
            }

            if (aSelected && !bSelected) {
              return -1;
            }

            return 1;
          });
          return results;
        },
        formatNoMatches: function formatNoMatches() {
          return t('core', 'No tags found');
        }
      }).on('select2-selecting', this._onSelectTag).on('select2-removing', this._onDeselectTag);
      var $dropDown = this.$tagsField.select2('dropdown'); // register events for inside the dropdown

      $dropDown.on('mouseup', '.rename', this._onClickRenameTag);
      $dropDown.on('mouseup', '.delete', this._onClickDeleteTag);
      $dropDown.on('mouseup', '.select2-result-selectable.has-form', this._preventDefault);
      $dropDown.on('submit', '.systemtags-rename-form', this._onSubmitRenameTag);
      this.delegateEvents();
    },
    remove: function remove() {
      if (this.$tagsField) {
        this.$tagsField.select2('destroy');
      }
    },
    getValues: function getValues() {
      this.$tagsField.select2('val');
    },
    setValues: function setValues(values) {
      this.$tagsField.select2('val', values);
    },
    setData: function setData(data) {
      this.$tagsField.select2('data', data);
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsInputField = SystemTagsInputField;
})(OC);

/***/ }),

/***/ "./core/js/systemtags/systemtagsmappingcollection.js":
/*!***********************************************************!*\
  !*** ./core/js/systemtags/systemtagsmappingcollection.js ***!
  \***********************************************************/
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
(function (OC) {
  /**
   * @class OC.SystemTags.SystemTagsMappingCollection
   * @classdesc
   *
   * Collection of tags assigned to a an object
   *
   */
  var SystemTagsMappingCollection = OC.Backbone.Collection.extend(
  /** @lends OC.SystemTags.SystemTagsMappingCollection.prototype */
  {
    sync: OC.Backbone.davSync,

    /**
     * Use PUT instead of PROPPATCH
     */
    usePUT: true,

    /**
     * Id of the file for which to filter activities by
     *
     * @var int
     */
    _objectId: null,

    /**
     * Type of the object to filter by
     *
     * @var string
     */
    _objectType: 'files',
    model: OC.SystemTags.SystemTagModel,
    url: function url() {
      return OC.linkToRemote('dav') + '/systemtags-relations/' + this._objectType + '/' + this._objectId;
    },

    /**
     * Sets the object id to filter by or null for all.
     *
     * @param {int} objectId file id or null
     */
    setObjectId: function setObjectId(objectId) {
      this._objectId = objectId;
    },

    /**
     * Sets the object type to filter by or null for all.
     *
     * @param {int} objectType file id or null
     */
    setObjectType: function setObjectType(objectType) {
      this._objectType = objectType;
    },
    initialize: function initialize(models, options) {
      options = options || {};

      if (!_.isUndefined(options.objectId)) {
        this._objectId = options.objectId;
      }

      if (!_.isUndefined(options.objectType)) {
        this._objectType = options.objectType;
      }
    },
    getTagIds: function getTagIds() {
      return this.map(function (model) {
        return model.id;
      });
    }
  });
  OC.SystemTags = OC.SystemTags || {};
  OC.SystemTags.SystemTagsMappingCollection = SystemTagsMappingCollection;
})(OC);

/***/ }),

/***/ "./core/js/systemtags/templates.js":
/*!*****************************************!*\
  !*** ./core/js/systemtags/templates.js ***!
  \*****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

(function () {
  var template = Handlebars.template,
      templates = OC.SystemTags.Templates = OC.SystemTags.Templates || {};
  templates['result'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      return " new-item";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      var stack1, helper;
      return "		<span class=\"label\">" + ((stack1 = (helper = (helper = helpers.tagMarkup || (depth0 != null ? depth0.tagMarkup : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "tagMarkup",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + "</span>\n";
    },
    "5": function _(container, depth0, helpers, partials, data) {
      var helper;
      return "		<span class=\"label\">" + container.escapeExpression((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "name",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n";
    },
    "7": function _(container, depth0, helpers, partials, data) {
      var helper;
      return "		<span class=\"systemtags-actions\">\n			<a href=\"#\" class=\"rename icon icon-rename\" title=\"" + container.escapeExpression((helper = (helper = helpers.renameTooltip || (depth0 != null ? depth0.renameTooltip : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "renameTooltip",
        "hash": {},
        "data": data
      }) : helper)) + "\"></a>\n		</span>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          options,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          buffer = "<span class=\"systemtags-item" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isNew : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "\" data-id=\"" + container.escapeExpression((helper = (helper = helpers.id || (depth0 != null ? depth0.id : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "id",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n<span class=\"checkmark icon icon-checkmark\"></span>\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isAdmin : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(3, data, 0),
        "inverse": container.program(5, data, 0),
        "data": data
      })) != null ? stack1 : "");
      stack1 = (helper = (helper = helpers.allowActions || (depth0 != null ? depth0.allowActions : depth0)) != null ? helper : alias2, options = {
        "name": "allowActions",
        "hash": {},
        "fn": container.program(7, data, 0),
        "inverse": container.noop,
        "data": data
      }, _typeof(helper) === alias3 ? helper.call(alias1, options) : helper);

      if (!helpers.allowActions) {
        stack1 = helpers.blockHelperMissing.call(depth0, stack1, options);
      }

      if (stack1 != null) {
        buffer += stack1;
      }

      return buffer + "</span>\n";
    },
    "useData": true
  });
  templates['result_form'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var helper;
      return "		<a href=\"#\" class=\"delete icon icon-delete\" title=\"" + container.escapeExpression((helper = (helper = helpers.deleteTooltip || (depth0 != null ? depth0.deleteTooltip : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "deleteTooltip",
        "hash": {},
        "data": data
      }) : helper)) + "\"></a>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1,
          helper,
          alias1 = depth0 != null ? depth0 : container.nullContext || {},
          alias2 = helpers.helperMissing,
          alias3 = "function",
          alias4 = container.escapeExpression;
      return "<form class=\"systemtags-rename-form\">\n	 <label class=\"hidden-visually\" for=\"" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-rename-input\">" + alias4((helper = (helper = helpers.renameLabel || (depth0 != null ? depth0.renameLabel : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "renameLabel",
        "hash": {},
        "data": data
      }) : helper)) + "</label>\n	<input id=\"" + alias4((helper = (helper = helpers.cid || (depth0 != null ? depth0.cid : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "cid",
        "hash": {},
        "data": data
      }) : helper)) + "-rename-input\" type=\"text\" value=\"" + alias4((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : alias2, _typeof(helper) === alias3 ? helper.call(alias1, {
        "name": "name",
        "hash": {},
        "data": data
      }) : helper)) + "\">\n" + ((stack1 = helpers["if"].call(alias1, depth0 != null ? depth0.isAdmin : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.noop,
        "data": data
      })) != null ? stack1 : "") + "</form>\n";
    },
    "useData": true
  });
  templates['selection'] = template({
    "1": function _(container, depth0, helpers, partials, data) {
      var stack1, helper;
      return "	<span class=\"label\">" + ((stack1 = (helper = (helper = helpers.tagMarkup || (depth0 != null ? depth0.tagMarkup : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "tagMarkup",
        "hash": {},
        "data": data
      }) : helper)) != null ? stack1 : "") + "</span>\n";
    },
    "3": function _(container, depth0, helpers, partials, data) {
      var helper;
      return "	<span class=\"label\">" + container.escapeExpression((helper = (helper = helpers.name || (depth0 != null ? depth0.name : depth0)) != null ? helper : helpers.helperMissing, typeof helper === "function" ? helper.call(depth0 != null ? depth0 : container.nullContext || {}, {
        "name": "name",
        "hash": {},
        "data": data
      }) : helper)) + "</span>\n";
    },
    "compiler": [7, ">= 4.0.0"],
    "main": function main(container, depth0, helpers, partials, data) {
      var stack1;
      return (stack1 = helpers["if"].call(depth0 != null ? depth0 : container.nullContext || {}, depth0 != null ? depth0.isAdmin : depth0, {
        "name": "if",
        "hash": {},
        "fn": container.program(1, data, 0),
        "inverse": container.program(3, data, 0),
        "data": data
      })) != null ? stack1 : "";
    },
    "useData": true
  });
})();

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./core/css/systemtags.scss":
/*!*****************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/sass-loader/lib/loader.js!./core/css/systemtags.scss ***!
  \*****************************************************************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

exports = module.exports = __webpack_require__(/*! ../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js")(false);
// Module
exports.push([module.i, "@charset \"UTF-8\";\n/**\n * @copyright Copyright (c) 2016, John Molakvoæ <skjnldsv@protonmail.com>\n * @copyright Copyright (c) 2016, Robin Appelman <robin@icewind.nl>\n * @copyright Copyright (c) 2016, Jan-Christoph Borchardt <hey@jancborchardt.net>\n * @copyright Copyright (c) 2016, Vincent Petry <pvince81@owncloud.com>\n * @copyright Copyright (c) 2016, Erik Pellikka <erik@pellikka.org>\n * @copyright Copyright (c) 2015, Vincent Petry <pvince81@owncloud.com>\n *\n * @license GNU AGPL version 3 or any later version\n *\n */\n.systemtags-select2-dropdown .select2-result-label .checkmark {\n  visibility: hidden;\n  margin-left: -5px;\n  margin-right: 5px;\n  padding: 4px; }\n\n.systemtags-select2-dropdown .select2-result-label .new-item .systemtags-actions {\n  display: none; }\n\n.systemtags-select2-dropdown .select2-selected .select2-result-label .checkmark {\n  visibility: visible; }\n\n.systemtags-select2-dropdown .select2-result-label .icon {\n  display: inline-block;\n  opacity: .5; }\n  .systemtags-select2-dropdown .select2-result-label .icon.rename {\n    padding: 4px; }\n\n.systemtags-select2-dropdown .systemtags-actions {\n  position: absolute;\n  right: 5px; }\n\n.systemtags-select2-dropdown .systemtags-rename-form {\n  display: inline-block;\n  width: calc(100% - 20px);\n  top: -6px;\n  position: relative; }\n  .systemtags-select2-dropdown .systemtags-rename-form input {\n    display: inline-block;\n    height: 30px;\n    width: calc(100% - 40px); }\n\n.systemtags-select2-dropdown .label {\n  width: 85%;\n  display: inline-block;\n  overflow: hidden;\n  text-overflow: ellipsis; }\n  .systemtags-select2-dropdown .label.hidden {\n    display: none; }\n\n.systemtags-select2-dropdown span {\n  line-height: 25px; }\n\n.systemtags-select2-dropdown .systemtags-item {\n  display: inline-block;\n  height: 25px;\n  width: 100%; }\n\n.systemtags-select2-dropdown .select2-result-label {\n  height: 25px; }\n\n.systemtags-select2-container {\n  width: 100%; }\n  .systemtags-select2-container .select2-choices .select2-search-choice.select2-locked .label {\n    opacity: 0.5; }\n\n#select2-drop.systemtags-select2-dropdown .select2-results li.select2-result {\n  padding: 5px; }\n", ""]);



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
//# sourceMappingURL=systemtags.js.map