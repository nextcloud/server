/*global Ext, runtime*/
Ext.define('WebODFApp.controller.Files', {
    extend: 'Ext.app.Controller',

    config: {
        refs: {
            mainView: 'mainview',
            filesList: 'fileslist list',
            fileDetail: 'filedetail',
            openButton: '#openButton',
            odfView: 'odfview',
            title: 'titlebar'
        },
        control: {
            mainView: {
                pop: 'pop',
                push: 'push',
                back: 'back'
            },
            filesList: {
                itemtap: 'show'
            },
            openButton: {
                tap: 'open'
            }
        }
    },
    back: function () {
        "use strict";
        this.odfView.hideCanvas();
    },
    push: function (view, item) {
        "use strict";
        if (item.xtype === "filedetail") {
            this.getOpenButton().show();
        } else {
            this.getOpenButton().hide();
        }
    },
    pop: function (view, item) {
        "use strict";
        if (item.xtype === "odfview") { // going to filedetail
            this.getOpenButton().show();
        } else {
            this.getOpenButton().hide();
        }
        this.getFilesList().deselectAll();
    },
    show: function (list, index, target, record, e) {
        "use strict";
        // set the record in the details view and the file view
        // this way, document starts loading in the background
        var c = this;
        if (!this.fileDetail) {
            this.fileDetail = Ext.create('WebODFApp.view.FileDetail');
        }
        if (!this.odfView) {
            this.odfView = Ext.create('WebODFApp.view.OdfView');
            this.odfView.addCanvasListener(this.fileDetail.canvasListener);
        }
        this.odfView.hideCanvas();
        this.fileDetail.odfView = this.odfView;
        this.fileDetail.setRecord(record);
        this.getMainView().push(this.fileDetail);
        runtime.setTimeout(function () {
            c.odfView.setRecord(record);
        }, 300);
    },
    open: function (options) {
        "use strict";
        var c = this;
        if (!this.odfView) {
            this.odfView = Ext.create('WebODFApp.view.OdfView');
        }
        this.odfView.hideCanvas();
        this.odfView.setRecord(this.fileDetail.getRecord());
        this.getMainView().push(this.odfView);
        runtime.setTimeout(function () {
            c.odfView.showCanvas();
        }, 300);
    }
});
