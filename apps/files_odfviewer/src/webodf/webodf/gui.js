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
/*global Ext:true, runtime:true, core:true, listFiles:true*/
runtime.loadClass("core.Zip");
runtime.loadClass("core.Base64");

/**
 * @param {Ext.data.Model} node
 * @return {undefined}
 */
function addThumbnail(node) {
    "use strict";
    var url = node.get('id'), zip;
/*
    zip = new core.Zip(url, function (err, zipobject) {
        zip = zipobject;
        if (err) {
            return;
        }
        zip.load('Thumbnails/thumbnail.png', function (err, data) {
            if (data === null) {
                return;
            }
            var url = 'data:;base64,' +
                    (new core.Base64()).convertUTF8ArrayToBase64(data),
                el, spans, i, s;
            el = node.getUI().getEl();
            if (el) {
                spans = el.getElementsByTagName('span');
                for (i = 0; i < spans.length; i += 1) {
                    s = spans.item(i);
                    if (s.getAttribute('qtip')) {
                        s.setAttribute('qtip', node.attributes.qtip);
                    }
                }
            } else {
                node.attributes.qtip += '<br/><img src="' + url + '"/>';
            }
        });
    });
*/
}

/**
 * @param {!string} url
 * @param {!Ext.tab.Panel} panel
 * @param {!string} title
 * @return {undefined}
 */
function loadODF(url, panel, title) {
    "use strict";
    var tab = panel.items.findBy(function (item) {
            return item.url === url;
        }),
        newTab;
    if (tab) {
        panel.setActiveTab(tab);
        return;
    }
    newTab = Ext.create('Ext.container.Container', {
        title: title,
        tabTip: url,
        url: url,
        closable: true,
        autoEl: {
            tag: 'iframe',
            name: url,
            src: 'odf.html#' + url,
            frameBorder: 0,
            style: {
                border: '0 none'
            }
        },
        region: 'center'
    });
    panel.add(newTab);
    panel.setActiveTab(newTab);
}

Ext.onReady(function () {
    "use strict";
    var editButton, tabpanel, slider, tree, viewport;

    Ext.QuickTips.init();

    /**
     * @param {!Ext.Button} button
     * @param {!boolean} pressed
     * @return {undefined}
     */
    function editToggle(button, pressed) {
        var tab = tabpanel.getActiveTab(), o, odfcanvas = "odfcanvas";
        if (!tab) {
            return;
        }
        tab.el.dom.contentDocument[odfcanvas].setEditable(pressed);
    }

    /**
     * @param {!Object} slider
     * @param {!number} zoomlevel
     * @param {!Object} thumb
     * @return {undefined}
     */
    function setZoom(slider, zoomlevel, thumb) {
        var tab = tabpanel.getActiveTab(),
            body;
        if (!tab) {
            return;
        }
        body = tab.el.dom.contentDocument.body;
        zoomlevel = Math.pow(10, zoomlevel / 10.0);
        body.style.zoom = zoomlevel;
        body.style.MozTransform = 'scale(' + zoomlevel + ')';
    }

    /**
     * @param {!Ext.data.NodeInterface} root
     * @param {!string} uri
     * @return {!Ext.data.NodeInterface}
     */
    function getParentNode(root, uri) {
        var parts = uri.split('/'),
            node = root,
            id = parts[0],
            i,
            n;
        for (i = 1; i < parts.length - 1; i += 1) {
            n = node.findChild('text', parts[i], false);
            id += '/' + parts[i];
            if (!n) {
                n = {
                    id: id,
                    text: parts[i],
                    qtip: uri,
                    cls: 'folder'
                };
                n = node.appendChild(n);
            }
            node = n;
        }
        return node;
    }

    /**
     * @param {!Array.<!string>} directories
     * @param {!Array.<!string>} files
     * @return {undefined}
     */
    function listFilesCallback(directories, files) {
        var root = tree.getRootNode(),
            i,
            f,
            parentNode,
            qtip,
            node;
        for (i = 0; i < files.length; i += 1) {
            f = files[i];
            parentNode = getParentNode(root, f);
            qtip = f;
            node = parentNode.appendChild({
                id: f,
                qtip: qtip,
                text: f.substr(f.lastIndexOf('/') + 1),
                cls: 'file',
                leaf: true,
                editable: false
            });
            f = /**@type{!Ext.data.Model}*/(node);
            addThumbnail(f);
        }
    }

    /**
     * @return {undefined}
     */
    function listFilesDoneCallback() {
    }

    editButton = new Ext.Button({
        enableToggle: true,
        text: 'Editable',
        listeners: { toggle: { fn: editToggle } }
    });

    slider = new Ext.Slider({
        width: 300,
        minValue: -5,
        maxValue: 5,
        values: [0],
        listeners: { changecomplete: { fn: setZoom } }
    });

    tabpanel = Ext.create('Ext.tab.Panel', {
        tbar: [ 'Zoom: ', slider, editButton ],
        region: 'center'
    });

    tree = Ext.create('Ext.tree.Panel', {
        title: 'Documents',
        region: 'west',
        width: 200,
        split: true,
        autoScroll: true,
        collapsible: true,
        rootVisible: false,
        enableTabScroll: true,
        defaults: {autoScroll: true},
        listeners: {
            itemclick: function (view, rec) {
                if (rec.get('cls') === 'file') {
                    loadODF(rec.get('id'), tabpanel, rec.get('text'));
                } else if (rec.get('cls') === 'folder') {
                    if (rec.isExpanded()) {
                        rec.collapse();
                    } else {
                        rec.expand();
                    }
                }
            }
        },
        root: { nodeType: 'node' }
    });

    viewport = new Ext.Viewport({
        layout: 'border',
        items: [ tabpanel, tree ]
    });

    // put data in the tree
    listFiles('./demodocs/', /\.od[tps]$/i, listFilesCallback,
            listFilesDoneCallback);
});
