/*global Ext, app, filestore */
Ext.define("WebODFApp.view.FilesList", {
    extend: "Ext.Panel",
    xtype: 'fileslist',
    config: {
        layout: 'fit',
        items: [{
            xtype: 'list',
            store: 'FileStore',
/*
            store: {
                fields: ['fileName', 'fullPath'],
                grouper: {
                    groupFn: function (record) {
                        "use strict";
                        return record.get('fileName')[0].toUpperCase();
                    }
                },
                data: [
                    {fileName: 'Cowper', fullPath: '-'},
                    {fileName: 'Everett', fullPath: '-'},
                    {fileName: 'University', fullPath: '-'},
                    {fileName: 'Forest', fullPath: '-'}
                ]
            },
*/
/*
            listeners: {
                'itemtap': function (view, number, item) {
                    "use strict";
                    var record = view.getStore().getAt(number);
                    if (record) {
                        Ext.app.dispatch({
                            controller: 'Files', //app.controllers.files,
                            action: 'show',
                            id: record.getId()
                        });
                    }
                }
            },
*/
            itemTpl: '{fileName}<br/><span style="font-size:x-small">{fullPath}</span>',
            grouped: true,
            indexBar: true
        }]
    }
});
