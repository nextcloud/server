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
/*global Ext runtime gui*/
runtime.loadClass("gui.XMLEdit");

function createXMLEdit(element, url) {
    var head = element.ownerDocument.getElementsByTagName("head")[0],
        xmlcss = element.ownerDocument.createElement("style"),
        xmledt;

    xmlcss.type = "text/css";
    head.appendChild(xmlcss);
    xmledt = new gui.XMLEdit(element, xmlcss);
    runtime.loadXML(url, function (err, xml) {
        if (xml.documentElement) {
            xmledt.setXML(xml);
        }
    });
}

function loadXML(url, panel, title) {
    title = title || url;
    var tab = panel.find('url', url),
        newTab;
    if (tab.length) {
        panel.setActiveTab(tab[0]);
        return;
    }
    newTab = new Ext.BoxComponent({
        title: title,
        tabTip: url,
        url: url,
        closable: true,
        autoEl: {
            tag: 'div'
        },
        region: 'center'
    });
    panel.add(newTab);
    panel.setActiveTab(newTab);

    createXMLEdit(newTab.el.dom, url);
}

Ext.onReady(function () {
    var tabpanel, tree, viewport, attributeEditor;

    Ext.QuickTips.init();

    tabpanel = new Ext.TabPanel({
        tbar: [  ],
        region: 'center'
    });

    attributeEditor = new Ext.grid.PropertyGrid({
        title: 'Attributes',
        region: 'east',
        width: 200,
        split: true,
        autoScroll: true,
        collapsible: true,
        rootVisible: false,
        enableTabScroll: true,
        defaults: {autoScroll: true}
    });

    tree = new Ext.tree.TreePanel({
        title: 'Documents',
        region: 'west',
        width: 200,
        split: true,
        autoScroll: true,
        collapsible: true,
        rootVisible: false,
        enableTabScroll: true,
        defaults: {autoScroll: true},
        collapsed: true,
        root: { nodeType: 'node' }
    });

    viewport = new Ext.Viewport({
        layout: 'border',
        items: [ tabpanel, tree, attributeEditor ]
    });

    // load the xml
    loadXML('requirements.xml', tabpanel);
    loadXML('../content.xml', tabpanel);
});
