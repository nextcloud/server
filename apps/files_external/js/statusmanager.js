/**
 * ownCloud
 *
 * @author Juan Pablo Villafañez Ramos <jvillafanez@owncloud.com>
 * @author Jesus Macias Portela <jesus@owncloud.com>
 * @copyright (C) 2014 ownCloud, Inc.
 *
 * This code is covered by the ownCloud Commercial License.
 *
 * You should have received a copy of the ownCloud Commercial License
 * along with this program. If not, see <https://owncloud.com/licenses/owncloud-commercial/>.
 *
 */

if (!OCA.External) {
    OCA.External = {};
}

OCA.External.StatusManager = {
    mountStatus : null,
    mountPointList : null,

    getMountStatus : function(afterCallback) {
        var self = this;
        if (typeof afterCallback !== 'function' || self.isGetMountStatusRunning) {
            return;
        }

        if (self.mountStatus) {
            afterCallback(self.mountStatus);
        } else {
            self.isGetMountStatusRunning = true;
            $.ajax({
                type : 'GET',
                url : OC.filePath('files_external', 'ajax', 'connectivityCheck.php'),
                success : function(response) {
                    self.mountStatus = response.data;
                    afterCallback(self.mountStatus);
                },
                error : function(jqxhr, state, error) {
                    OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Couldn\'t get the status of the external mounts: {type}', {type : error}));
                    if (!self.mountStatus) {
                        self.mountStatus = {};
                    }
                    $.each(self.mountPointList, function(name, value){
                        if (!self.mountStatus[value.mount_point]) {
                            self.mountStatus[value.mount_point] = {};
                        }
                        self.mountStatus[value.mount_point].status = 'ok';
                        OCA.External.StatusManager.Utils.restoreFolder(value);
                        OCA.External.StatusManager.Utils.toggleLink(value.mount_point, true, true);
                    });
                },
                complete : function() {
                    self.isGetMountStatusRunning = false;
                }
            });
        }
    },

    getMountPointListElement : function(mount_point) {
        var element;
        $.each(this.mountPointList, function(key, value){
            if (value.mount_point === mount_point) {
                element = value;
                return false;
            }
        });
        return element;
    },

    getMountStatusForMount : function(mountData, afterCallback) {
        var self = this;
        if (typeof afterCallback !== 'function' || self.isGetMountStatusRunning) {
            return $.Deferred().resolve();
        }

        var defObj;
        if (self.mountStatus[mountData.mount_point]) {
            defObj = $.Deferred();
                afterCallback(mountData.mount_point, self.mountStatus[mountData.mount_point]);
            defObj.resolve();  // not really useful, but it'll keep the same behaviour
        } else {
            defObj = $.ajax({
                type : 'GET',
                url: OC.webroot + '/index.php/apps/files_external/globalstorages/' + mountData.id,
                success : function(response) {
                    if (response && response.status === 0) {
                        self.mountStatus[mountData.mount_point] = response;
                    } else {
                        if (response && response.statusMessage) {
                            // failure response with error message
                            self.mountStatus[mountData.mount_point] = {code: 'GE',
                                                                        status: 1,
                                                                        error: response.statusMessage};
                        } else {
                            self.mountStatus[mountData.mount_point] = {code: 'GE',
                                                                        status: 1,
                                                                        error: t('files_external', 'Empty response from the server')};
                        }
                    }
                    afterCallback(mountData.mount_point, self.mountStatus[mountData.mount_point]);
                },
                error : function(jqxhr, state, error) {
                    var message;
                    if(mountData.location === 3){
                        // In this case the error is because  mount point use Login credentials and don't exist in the session
                        message = t('files_external', 'Couldn\'t access. Please logout and login to activate this mount point');
                    } else {
                        message = t('files_external', 'Couldn\'t get the information from the ownCloud server: {code} {type}', {code: jqxhr.status, type: error});
                    }
                    self.mountStatus[mountData.mount_point] = {code: 'GE',
                                                                status: 1,
                                                                location: mountData.location,
                                                                error: message};
                    afterCallback(mountData.mount_point, self.mountStatus[mountData.mount_point]);
                }
            });
        }
        return defObj;
    },

    getMountPointList : function(afterCallback) {
        var self = this;
        if (typeof afterCallback !== 'function' || self.isGetMountPointListRunning) {
            return;
        }


        if (self.mountPointList) {
            afterCallback(self.mountPointList);
        } else {
            self.isGetMountPointListRunning = true;
            $.ajax({
                type : 'GET',
                url : OC.linkToOCS('apps/files_external/api/v1') + 'mounts?format=json',
                success : function(response) {
                    self.mountPointList = [];
                    _.each(response.ocs.data, function(mount){
                        var element = {};
                        element.mount_point = mount.name;
                        element.type = mount.scope;
                        element.location = "";
                        element.id = mount.id;
                        element.backend = mount.backend;
                        element.class = mount.class;

                        self.mountPointList.push(element);
                    });
                    afterCallback(self.mountPointList);
                },
                error : function(jqxhr, state, error) {
                    self.mountPointList = [];
                    OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Couldn\'t get the list of external mount points: {type}', {type : error}));
                },
                complete : function() {
                    self.isGetMountPointListRunning = false;
                }
            });
        }
    },

    setMountPointAsGood : function(mountPoint) {
        OCA.External.StatusManager.Utils.restoreFolder(mountPoint);
        OCA.External.StatusManager.Utils.toggleLink(mountPoint, true, true);
        delete this.mountStatus[mountPoint].code;
        delete this.mountStatus[mountPoint].error;
        this.mountStatus[mountPoint].status = 'ok';
    },

    manageMountPointError : function(name) {
        var self = this;
        this.getMountStatus($.proxy(function(allMountStatus) {
            if (typeof allMountStatus[name] !== 'undefined' || allMountStatus[name].status === 1) {
                var mountData = allMountStatus[name];
                if ((mountData.code === 'CNP' || mountData.code === 'AD') && mountData.type === 'global' && mountData.location === 1) {
                    // admin set up mount point and let users use their credentials. Credentials
                    // aren't stored yet or are wrong (handled by the same ajax request)
                    this.showCredentialsDialog(name, mountData, null, 'saveCredential.php',
                                                null, this.setMountPointAsGood, this);

                } else if (mountData.code === 'AD' && mountData.type === 'personal' && mountData.location === 0) {
                    // personal set up mount point and let users use their credentials.
                    // Credentials are wrong so they need to be updated
                    // the "type 0" is a required parameter in the target ajax call
                    this.showCredentialsDialog(name, mountData, {type: 0}, 'updatePersonalMountPoint.php',
                                                null, this.setMountPointAsGood, this);

                } else if (mountData.code === 'AD' && mountData.type === 'personal' && mountData.location === 2) {
                    this.showCredentialsDialog(name, mountData, null, 'saveGlobalCredentials.php',
                                                t('files_external', 'WARNING: This mount point uses global credentials.\n\nChanging the credentials might affect to other mount points'),
                                                function() {
                                                    this.recheckConnectivityForMount([name], true, true);
                                                },
                                                this);

                } else if (mountData.code === 'AD' && mountData.type === 'global' && (mountData.location === 0 || mountData.location === 2)) {
                    OC.dialogs.message(t('files_external', 'The credentials for this mount point are wrong. This mount point was set by the administrator, please contact him / her to provide suitable credentials'), t('files_external', 'Credentials error'));

                } else if ((mountData.code === 'CE' || mountData.code === 'IH')) {
                    OC.dialogs.message(mountData.error, t('files_external', 'Connectivity error'));

                } else if ((mountData.code === 'GE' && mountData.location === 3)) {
                    OC.dialogs.message(mountData.error, t('files_external', 'Login credentials error'));

                } else {
                    OC.dialogs.message(mountData.error, t('files_external', 'External mount error'));
                }
            }
        }, this));
    },

    showCredentialsDialog : function(mountPoint, mountData, extraParams, target, extraInfo, successCallback, callbackCtx) {
        var self = this;
        var baseParams = {target: target,
                            m: mountData.mid,
                            name: mountPoint,
                            url: mountData.url,
                            share: mountData.share,
                            extra: extraInfo};
        var sendParams = ($.isPlainObject(extraParams)) ? $.extend(baseParams, extraParams) : baseParams;
        $.get(OC.filePath('files_external', 'ajax', 'dialog.php'),
                sendParams,
                function(data) {
                    if (typeof data.status !== 'undefined' && data.status === 0) {
                        $('body').append(data.form);
                        var wnd_send_button_click_func = function () {
                            $('.oc-dialog-close').hide();
                            var dataToSend = {};
                            $('#wnd_div_form').find('input').each(function(){
                                var thisElement = $(this);
                                if (thisElement.is('[type="checkbox"]')) {
                                    dataToSend[thisElement.attr('name')] = thisElement.prop('checked');
                                } else {
                                    dataToSend[thisElement.attr('name')] = thisElement.val();
                                }
                            });
                            $.ajax({type: 'POST',
                                url: $('#wnd_div_form form').attr('action'),
                                data: dataToSend,
                                success: function (data) {
                                    var dialog = $('#wnd_div_form');
                                    if (typeof(data.status) !== 'undefined' && data.status === 0) {
                                        dialog.ocdialog('close');

                                        if (successCallback && $.isFunction(successCallback)) {
                                            successCallback.call(callbackCtx || this, mountPoint);
                                        }
                                    } else {
                                        $('.oc-dialog-close').show();
                                        dialog.ocdialog('option', 'title', 'Windows Network Drive credentials validation failed');
                                        var title = $('.oc-dialog-title');
                                        var color = title.css('background-color');
                                        title.css('background-color', 'red');
                                        title.animate({backgroundColor: color}, 5000);
                                    }
                                },
                                error: function (){
                                    $('.oc-dialog-close').show();
                                }});
                        };

                        var buttonList = [{text : t('files_external', 'Save'),
                                            click : wnd_send_button_click_func,
                                            closeOnEscape : true}];

                        var ocdialogParams = {modal: true, buttons : buttonList,
                                                closeOnExcape : true};
                        $('#wnd_div_form').ocdialog(ocdialogParams)
                                        .bind('ocdialogclose', function(){
                                            $('#wnd_div_form').ocdialog('destroy').remove();
                                        });
                    }
                });
    },

    processMountStatus : function(mounts) {
        var hasErrors = false;
        var self = this;
        $.each(mounts, function(mountPoint, values){
            hasErrors = !self.processMountStatusIndividual(mountPoint, values) || hasErrors;
        });

        if (!this.notificationHasShown) {
            this.notificationHasShown = true;
            if (hasErrors) {
                OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Some of the configured external mount points are not connected. Please click on the red row(s) for more information'));
            }
        }
    },

    processMountStatusIndividual : function(mountPoint, mountData) {
        if (mountData.status === 1) {
            var errorImage = 'folder-windows-error';
            /*
            if (mountData.code === 'AD' || mountData.code === 'CNP') {
                errorImage += '-credentials';
            } else if (mountData.code === 'IH' || mountData.code === 'CE') {
                errorImage += '-timeout';
            } else {
                errorImage += '-error';
            }*/
            if (OCA.External.StatusManager.Utils.isCorrectViewAndRootFolder()) {
                OCA.External.StatusManager.Utils.showIconError(mountPoint, $.proxy(OCA.External.StatusManager.manageMountPointError, OCA.External.StatusManager), OC.imagePath('core', 'filetypes/' + errorImage));
            }
            return false;
        } else {
            if (OCA.External.StatusManager.Utils.isCorrectViewAndRootFolder()) {
                OCA.External.StatusManager.Utils.restoreFolder(mountPoint);
                OCA.External.StatusManager.Utils.toggleLink(mountPoint, true, true);
            }
            return true;
        }
    },

    processMountList : function(mountList) {
        var elementList = null;
        $.each(mountList, function(name, value){
            var trElement = $('#fileList tr[data-file=\"' + OCA.External.StatusManager.Utils.jqSelEscape(value.mount_point) + '\"]');
            if (elementList) {
                elementList = elementList.add(trElement);
            } else {
                elementList = trElement;
            }
        });

        if (elementList instanceof $) {
            if (OCA.External.StatusManager.Utils.isCorrectViewAndRootFolder()) {
                // Put their custom icon
                // OCA.External.StatusManager.Utils.changeFolderIcon(elementList.find('td:first-child div.thumbnail'), "url(" + OC.imagePath('windows_network_drive', 'folder-windows') + ")");
                // Save default view
                OCA.External.StatusManager.Utils.storeDefaultFolderIconAndBgcolor(elementList);
                // Disable row until check status
                elementList.css('background-color', '#CCC');
                OCA.External.StatusManager.Utils.toggleLink(elementList.find('a.name'), false, false);
            }
        }
    },

    launchFullConnectivityCheck : function() {
        var self = this;
        this.getMountPointList(function(list){
            // check if we have a list first
            if (list === undefined && !self.emptyWarningShown) {
                self.emptyWarningShown = true;
                OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Couldn\'t get the list of Windows network drive mount points: empty response from the server'));
                return;
            }
            if (list && list.length > 0) {
                self.processMountList(list);
                self.getMountStatus(function(mountStatus){
                    if (mountStatus === undefined && !self.notificationNoProcessListDone) {
                        self.notificationNoProcessListDone = true;
                        OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Couldn\'t get the status of the Windows network drive mounts: empty response from the server'));
                        if (!self.mountStatus) {
                            self.mountStatus = {};
                        }
                        $.each(list, function(name, value){
                            if (!self.mountStatus[value.mount_point]) {
                                self.mountStatus[value.mount_point] = {};
                            }
                            self.mountStatus[value.mount_point].status = 'ok';
                            OCA.External.StatusManager.Utils.restoreFolder(value.mount_point);
                            OCA.External.StatusManager.Utils.toggleLink(value.mount_point, true, true);
                        });
                        return;
                    }
                    self.processMountStatus(mountStatus);
                });
            }
        });
    },

    launchFullConnectivityCheckOneByOne : function() {
        var self = this;
        this.getMountPointList(function(list){
            // check if we have a list first
            if (list === undefined && !self.emptyWarningShown) {
                self.emptyWarningShown = true;
                OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Couldn\'t get the list of Windows network drive mount points: empty response from the server'));
                return;
            }
            if (list && list.length > 0) {
                self.processMountList(list);

                if (!self.mountStatus) {
                    self.mountStatus = {};
                }

                var ajaxQueue = [];
                $.each(list, function(key, value){
                    var queueElement = {funcName: $.proxy(self.getMountStatusForMount, self),
                                        funcArgs: [value,
                                                    $.proxy(self.processMountStatusIndividual, self)]};
                    ajaxQueue.push(queueElement);
                });

                var rolQueue = new OCA.External.StatusManager.RollingQueue(ajaxQueue, 4, function(){
                    if (!self.notificationHasShown) {
                        var showNotification = false;
                        $.each(self.mountStatus, function(key, value){
                            if (value.status === 1) {
                                self.notificationHasShown = true;
                                showNotification = true;
                            }
                        });
                        if (showNotification) {
                            OCA.External.StatusManager.Utils.showAlert(t('files_external', 'Some of the configured external mount points are not connected. Please click on the red row(s) for more information'));
                        }
                    }
                });
                rolQueue.runQueue();
            }
        });
    },

    launchPartialConnectivityCheck : function(mountListData, recheck) {
        if (mountListData.length === 0) {
            return;
        }

        var self = this;
        var ajaxQueue = [];
        $.each(mountListData, function(key, value){
            if (recheck && value.mount_point in self.mountStatus) {
                delete self.mountStatus[value.mount_point];
            }
            var queueElement = {funcName: $.proxy(self.getMountStatusForMount, self),
                                funcArgs: [value,
                                            $.proxy(self.processMountStatusIndividual, self)]};
            ajaxQueue.push(queueElement);
        });
        new OCA.External.StatusManager.RollingQueue(ajaxQueue, 4).runQueue();
    },

    recheckConnectivityForMount : function(mountListNames, recheck, checkGlobal) {
        if (mountListNames.length === 0) {
            return;
        }

        var self = this;
        var mountListData = [];
        var recheckPersonalGlobal = false;
        var recheckAdminGlobal = false;

        if (!self.mountStatus) {
            self.mountStatus = {};
        }

        $.each(mountListNames, function(key, value){
            var mountData = self.getMountPointListElement(value);
            if (mountData) {
                if (mountData.type === 'personal' && mountData.location === 2) {
                    recheckPersonalGlobal = true;
                }
                if (mountData.type === 'admin' && mountData.location === 2) {
                    recheckAdminGlobal = true;
                }
                mountListData.push(mountData);
            }
        });

        // we might need to check more mounts if a personal mount with global credentials is affected
        if (checkGlobal && (recheckPersonalGlobal || recheckAdminGlobal)) {
            $.each(self.mountPointList, function(key, value){
                if (((recheckPersonalGlobal && value.type === 'personal') || (recheckAdminGlobal && value.type === 'admin')) &&
                        value.location === 2 &&
                        $.inArray(value, mountListData) === -1) {
                    // personal mount using global credentials, not present in the mountListData
                    mountListData.push(value);
                }
            });
        }

        // for all mounts in the list, delete the cached status values
        if (recheck) {
            $.each(mountListData, function(key, value){
                if (value.mount_point in self.mountStatus) {
                    delete self.mountStatus[value.mount_point];
                }
            });
        }

        self.processMountList(mountListData);
        self.launchPartialConnectivityCheck(mountListData, recheck);
    }
};
