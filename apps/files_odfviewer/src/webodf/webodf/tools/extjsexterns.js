/**
 * Copyright (C) 2011 KO GmbH <jos.van.den.oever@kogmbh.com>
 * @licstart
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU Affero General Public License
 * (GNU AGPL) as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.  The code is distributed
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU AGPL for more details.
 *
 * As additional permission under GNU AGPL version 3 section 7, you
 * may distribute non-source (e.g., minimized or compacted) forms of
 * that code without the copy of the GNU GPL normally required by
 * section 4, provided you include this license notice and a URL
 * through which recipients can access the Corresponding Source.
 *
 * As a special exception to the AGPL, any HTML file which merely makes function
 * calls to this code, and for that purpose includes it by reference shall be
 * deemed a separate work for copyright law purposes. In addition, the copyright
 * holders of this code give you permission to combine this code with free
 * software libraries that are released under the GNU LGPL. You may copy and
 * distribute such a system following the terms of the GNU AGPL for this code
 * and the LGPL for the libraries. If you modify this code, you may extend this
 * exception to your version of the code, but you are not obligated to do so.
 * If you do not wish to do so, delete this exception statement from your
 * version.
 *
 * This license applies to this entire compilation.
 * @licend
 * @source: http://www.webodf.org/
 * @source: http://gitorious.org/odfkit/webodf/
 */
/*global Packages HTMLStyleElement window XMLHttpRequest HTMLStyleElement Document*/
/*jslint nomen: false */
var Ext = {};
Ext.data = {};
/**
 * @constructor
 */
Ext.data.Model = function (settings) {};
/**
 * @param {!string} fieldName
 * @return {!string}
 */
Ext.data.Model.prototype.get = function (fieldName) {};
/**
 * @return {!boolean}
 */
Ext.data.Model.isExpanded = function () {};
/**
 * @constructor
 */
Ext.data.NodeInterface = function () {};
/**
 * @param {!string} attribute
 * @param {*} value
 * @param {boolean=} deep
 * @return {Ext.data.NodeInterface}
 */
Ext.data.NodeInterface.prototype.findChild = function (attribute, value, deep) {};
/**
 * @param {!Ext.data.NodeInterface|!Object} node
 * @return {!Ext.data.NodeInterface}
 */
Ext.data.NodeInterface.prototype.appendChild = function (node) {};
/**
 * @param {!string} id
 * @return {Ext.Component}
 */
Ext.getCmp = function (id) {};
Ext.tree = {};
/**
 * @constructor
 */
Ext.tree.Panel = function (settings) {};
/**
 * @return {!Ext.data.NodeInterface}
 */
Ext.tree.Panel.prototype.getRootNode = function () {};
Ext.component = {};
/**
 * @constructor
 * @extends {Ext.Component}
 */
Ext.component.Component = function (settings) {};
/**
 * @return {!Ext.Element}
 */
Ext.component.Component.prototype.getEl = function () {};
/**
 * @constructor
 */
Ext.Button = function (settings) {};
/**
 * @constructor
 */
Ext.Component = function (settings) {};
/**
 * @type {Object}
 */
Ext.Component.prototype.superclass = {};
/**
 * @type {!Ext.Element}
 */
Ext.Component.prototype.el;
/**
 * @constructor
 */
Ext.Element = function (settings) {};
/**
 * @constructor
 */
Ext.Panel = function (settings) {};
/**
 * @type {!Element}
 */
Ext.Element.prototype.dom;
Ext.QuickTips = {};
/**
 * @return {undefined}
 */
Ext.QuickTips.init = function () {};
/**
 * @constructor
 */
Ext.Slider = function (settings) {};
Ext.util = {};
/**
 * @constructor
 */
Ext.util.MixedCollection = function () {};
/**
 * @param {!Function} f
 */
Ext.util.MixedCollection.prototype.findBy = function (f) {};
Ext.tab = {};
/**
 * @constructor
 */
Ext.tab.Panel = function (settings) {};
/**
 * @param {!Object} component
 * @return {undefined}
 */
Ext.tab.Panel.prototype.add = function (component) {};
/**
 * @return {!Ext.Component}
 */
Ext.tab.Panel.prototype.getActiveTab = function () {};
/**
 * @param {!Ext.Component} tab
 * @return {undefined}
 */
Ext.tab.Panel.prototype.setActiveTab = function (tab) {};
/**
 * @type {!Ext.util.MixedCollection}
 */
Ext.tab.Panel.prototype.items;
/**
 * @constructor
 */
Ext.Toolbar = function (settings) {};
/**
 * @constructor
 */
Ext.Toolbar.TextItem = function (text) {};
/**
 * @constructor
 */
Ext.Viewport = function (settings) {};
Ext.onReady = function (callback) {};
