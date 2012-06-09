/*global Ext*/
Ext.define('WebODFApp.store.FileStore', {
    extend: 'Ext.data.Store',
    config: {
        storeId: 'FileStore',
        model: 'WebODFApp.model.FileSystem',
        autoLoad: true,
        grouper: {
            groupFn: function (record) {
                "use strict";
                return record.get('fileName')[0].toUpperCase();
            }
        },
        proxy: {
            xtype: 'filesystemproxy'
        },
        sorters: function (a, b) {
            "use strict";
            a = a.get('fileName').toUpperCase();
            b = b.get('fileName').toUpperCase();
            if (a > b) {
                return 1;
            }
            if (a < b) {
                return -1;
            }
            return 0;
        }
    }
});
