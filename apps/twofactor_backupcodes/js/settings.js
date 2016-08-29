/* global OC */

(function (OC) {
    'use strict';

    OC.Settings = OC.Settings || {};
    OC.Settings.TwoFactorBackupCodes = OC.Settings.TwoFactorBackupCodes || {};

    $(function () {
        var view = new OC.Settings.TwoFactorBackupCodes.View({
            el: $('#twofactor-backupcodes-settings')
        });
        view.render();
    });
})(OC);

