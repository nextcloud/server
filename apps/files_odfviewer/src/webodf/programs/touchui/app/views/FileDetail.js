/*global Ext, app, runtime, xmldom, odf*/
runtime.loadClass("xmldom.XPath");
runtime.loadClass("odf.Style2CSS");
Ext.define('WebODFApp.view.FileDetail', (function () {
    "use strict";
    var panel,
        style2CSS = new odf.Style2CSS(),
        xpath = new xmldom.XPath(),
        fileDetail,
        title,
        image,
        list,
        emptyImageUrl = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAAXNSR0IArs4c6QAAAAtJREFUCB1jYGAAAAADAAFPSAqvAAAAAElFTkSuQmCC";
    function getTitle(body) {
        var ps,
            title;
        ps = xpath.getODFElementsWithXPath(body,
                ".//text:h", style2CSS.namespaceResolver);
        title = "";
        if (ps && ps.length) {
            title = ps[0].nodeValue;
        } else {
            ps = xpath.getODFElementsWithXPath(body,
                ".//text:p", style2CSS.namespaceResolver);
            if (ps && ps.length) {
                title = ps[0].nodeValue;
            }
        }
        return title;
    }
    function metaToJSON(body, meta) {
        var json = [],
            title = body && getTitle(body),
            e = meta && meta.firstChild,
            name;
        if (title) {
            json.push({name: "title", value: title});
        }
        while (e) {
            if (e.nodeType === 1 && e.textContent) {
                if (e.localName === "user-defined") {
                    name = e.getAttributeNS(
                        "urn:oasis:names:tc:opendocument:xmlns:meta:1.0",
                        "name"
                    );
                } else {
                    name = e.localName;
                }
                json.push({
                    name: name,
                    value: e.textContent
                });
            }
            e = e.nextSibling;
        }
        return json;
    }
    return {
        extend: 'Ext.Panel',
        xtype: 'filedetail',
        config: {
            title: 'File details',
            layout: 'vbox',
            items: [{
                id: "title",
                dock: 'top',
                xtype: 'toolbar'
            }, {
                id: 'details',
                xtype: 'container',
                layout: 'hbox',
                flex: 1,
                items: [{
                    id: 'thumbnail',
                    xtype: 'image',
                    width: 256,
                    maxWidth: "50%"
                }, {
                    id: 'metalist',
                    xtype: 'list',
                    store: {
                        fields: ["name", "value"],
                        data: []
                    },
                    itemTpl: "{name}: {value}",
                    flex: 1
                }]
            }],
            listeners: {
                initialize: function () {
                    fileDetail = this.query("#details")[0];
                    title = this.query("#title")[0];
                    image = fileDetail.query('#thumbnail')[0];
                    list = fileDetail.query('#metalist')[0];
                }
            }
        },
        updateRecord: function (record) {
            if (record) {
                fileDetail.setMasked({
                    xtype: 'loadmask',
                    message: 'Loading...'
                });
                title.setTitle(record.get('fileName'));
            }
        },
        canvasListener: function (odfcanvas) {
            var view = this,
                odfcontainer = odfcanvas.odfContainer(),
                part = odfcontainer.getPart("Thumbnails/thumbnail.png"),
                metajson = [];
            metajson = metaToJSON(odfcontainer.rootElement.body,
                    odfcontainer.rootElement.meta);
            part.onstatereadychange = function (part) {
                image.setSrc(part.url || emptyImageUrl);
            };
            part.load();
            list.getStore().setData(metajson);
            fileDetail.unmask();
        }
    };
}()));
