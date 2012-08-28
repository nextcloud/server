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
/*global runtime: true, document: true, odf: true, window: true, Ext: true*/
/**
 * @type {odf.OdfCanvas}
 */
var odfcanvas;

/**
 * @return {undefined}
 */
function fixExtJSCSS() {
    "use strict";
    // look through all stylesheets to change the selector
    // ".x-viewport, .x-viewport body"
    // to
    // ".x-viewport, .x-viewport > body"
    // The normal selector os not specific enough, office|body is also affected
    // by it. To avoid this, the selector is changed so that is only applies to
    // a director parent child relationship with '>'
    var i, cssRules, j, rule;
    for (i = 0; i < document.styleSheets.length; i += 1) {
        cssRules = document.styleSheets[i].cssRules;
        for (j = 0; j < cssRules.length; j += 1) {
            rule = cssRules[j];
            if (rule.selectorText === ".x-viewport, .x-viewport body") {
                rule = rule.cssText.replace(".x-viewport, .x-viewport body",
                        ".x-viewport, .x-viewport > body");
                document.styleSheets[i].deleteRule(j);
                document.styleSheets[i].insertRule(rule, j);
                return;
            }
        }
    }
}
/**
 * @return {undefined}
 */
function updateStyleComboBox() {
    "use strict";
    var paragraphStylesBox = document.getElementById("paragraphStyleBox");
}
/**
 * @param {!Element} odfelement
 * @return {undefined}
 */
function initCanvas(odfelement) {
    "use strict";
    runtime.loadClass("odf.OdfCanvas");
    // if the url has a fragment (#...), try to load the file it represents
    var location = String(document.location),
        pos = location.indexOf('#');
//    odfelement.style.overflow = 'auto';
    odfelement.style.height = '100%';
    odfcanvas = new odf.OdfCanvas(odfelement);
    if (pos === -1 || !window) {
        return;
    }
    location = location.substr(pos + 1);
    odfcanvas.onstatereadychange = function () {
        /*
        updateStyleComboBox();
        odfcanvas.save(function (err) {
            alert(err);
        });
        */
    };
    odfcanvas.load(location);
    odfcanvas.addListener("selectionchange", function (element, selection) {
        var formatting = odfcanvas.getFormatting(),
            completelyBold = formatting.isCompletelyBold(selection),
            alignment = formatting.getAlignment(selection);
        runtime.log("selection changed " + completelyBold + " " + alignment);
    });
}
/**
 * @return {undefined}
 */
function save() {
    "use strict";
    odfcanvas.odfContainer().save(function (err) {
        if (err) {
            runtime.log(err);
        }
    });
}
Ext.ODFEditor = Ext.extend(Ext.component.Component, {
    buttonTips : {
        bold : {
            title: 'Bold (Ctrl+B)',
            text: 'Make the selected text bold.',
            cls: 'x-html-editor-tip'
        },
        italic : {
            title: 'Italic (Ctrl+I)',
            text: 'Make the selected text italic.',
            cls: 'x-html-editor-tip'
        },
        underline : {
            title: 'Underline (Ctrl+U)',
            text: 'Underline the selected text.',
            cls: 'x-html-editor-tip'
        },
        increasefontsize : {
            title: 'Grow Text',
            text: 'Increase the font size.',
            cls: 'x-html-editor-tip'
        },
        decreasefontsize : {
            title: 'Shrink Text',
            text: 'Decrease the font size.',
            cls: 'x-html-editor-tip'
        },
        backcolor : {
            title: 'Text Highlight Color',
            text: 'Change the background color of the selected text.',
            cls: 'x-html-editor-tip'
        },
        forecolor : {
            title: 'Font Color',
            text: 'Change the color of the selected text.',
            cls: 'x-html-editor-tip'
        },
        justifyleft : {
            title: 'Align Text Left',
            text: 'Align text to the left.',
            cls: 'x-html-editor-tip'
        },
        justifycenter : {
            title: 'Center Text',
            text: 'Center text in the editor.',
            cls: 'x-html-editor-tip'
        },
        justifyright : {
            title: 'Align Text Right',
            text: 'Align text to the right.',
            cls: 'x-html-editor-tip'
        },
        insertunorderedlist : {
            title: 'Bullet List',
            text: 'Start a bulleted list.',
            cls: 'x-html-editor-tip'
        },
        insertorderedlist : {
            title: 'Numbered List',
            text: 'Start a numbered list.',
            cls: 'x-html-editor-tip'
        },
        createlink : {
            title: 'Hyperlink',
            text: 'Make the selected text a hyperlink.',
            cls: 'x-html-editor-tip'
        },
        sourceedit : {
            title: 'Source Edit',
            text: 'Switch to source editing mode.',
            cls: 'x-html-editor-tip'
        },
        save : {
            title: 'Save (Ctrl+S)',
            text: 'Save the document.',
            cls: 'x-html-editor-tip'
        }
    }
});


var ODFEditor = Ext.extend(Ext.Panel, {
    initComponent: function () {
        "use strict";
        var me = this,
            statusMessage = new Ext.Toolbar.TextItem('');
        function buttonHandler(button, event) {
        }
        me.defaults = {
        };
        me.initialConfig = Ext.apply({
        }, me.initialConfig);
        me.items = [{
            xtype: 'box',
            id: 'canvas',
            autoEl: {
                tag: 'div',
                frameBorder: 0,
                style: {
                    border: '0 none'
                }
            },
            autoScroll: true,
            scroll: true
        }];
        me.tbar = {
            xtype: 'toolbar',
            items: [{
                xtype: 'button',
                icon: 'extjs/examples/shared/icons/save.gif',
                handler: buttonHandler,
                cls: 'x-btn-icon'
            }, {
                xtype: 'tbseparator'
            }, {
                tag: 'select',
                //html: this.createFontOptions()
                cls: 'x-font-select'
            }, {
                xtype: 'buttongroup',
                cls: 'x-html-editor-tb',
                frame: false,
                items: [{
                    xtype: 'button',
                    iconCls: 'x-edit-bold',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-italic',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-underline',
                    cls: 'x-btn-icon'
                }, {
                    itemId: 'forecolor',
                    cls: 'x-btn-icon',
                    iconCls: 'x-edit-forecolor',
                    menu: { xtype: 'colormenu' }
                }, {
                    itemId: 'backcolor',
                    cls: 'x-btn-icon',
                    iconCls: 'x-edit-backcolor',
                    menu: { xtype: 'colormenu' }
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-justifyleft',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-justifycenter',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-justifyright',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-insertorderedlist',
                    cls: 'x-btn-icon'
                }, {
                    xtype: 'button',
                    iconCls: 'x-edit-insertunorderedlist',
                    cls: 'x-btn-icon'
                }]
            }, {
                xtype: 'tbfill'
            },
            statusMessage
            ]
        };
/*
        me.bbar = {
            xtype: 'toolbar',
            items: [ {xtype: "tbfill" }, statusMessage ]
        };
*/
        ODFEditor.superclass.initComponent.call(this);
    }
});
Ext.onReady(function () {
    "use strict";
    var canvas, viewport;

    Ext.QuickTips.init();

    canvas = new ODFEditor({
        region: 'center'
    });

    viewport = new Ext.Viewport({
        layout: 'border',
        items: [ canvas ]
    });

    fixExtJSCSS();
    initCanvas(Ext.getCmp('canvas').el.dom);
});
