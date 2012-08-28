/*global Ext, invokeString, document*/
/**
 * When the application has been initialized, this function is called, if
 * it has been set. By default, it will start scanning the files in the
 * files sytem. It can be overridden by e.g. PhoneGap to wait until the device
 * is ready.
 */
var onApplicationLaunch = function (app) {
    "use strict";
    app.startScanningDirectories();
};
Ext.application({
    name : 'WebODFApp',
    models: ['FileSystem'],
    views: ['Viewport', 'FilesList', 'FileDetail', 'OdfView'],
    controllers: ['Files'],
    stores: ['FileStore'],
    launch: function () {
        'use strict';
        var app = this;
        Ext.create('WebODFApp.view.Viewport');
        app.openUrl = function (url) {
            var proxy = Ext.getStore("FileStore").getProxy();
            proxy.getRecord(url, function (record) {
                var controller;
                if (!record) {
                    alert("Cannot open " + url);
                } else {
                    controller = app.getController('Files');
                    controller.show(null, null, null, record);
                }
            });
        };
        this.startScanningDirectories = function () {
            var proxy = Ext.getStore("FileStore").getProxy();
            proxy.startScanningDirectories();
        };
        onApplicationLaunch(this);
    }
});
