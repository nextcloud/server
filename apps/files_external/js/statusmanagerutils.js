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

if (!OCA.External.StatusManager) {
    OCA.External.StatusManager = {};
}

OCA.External.StatusManager.Utils = {

    showAlert: function(message){
        if (!OC.Notification.isHidden()) {
            OC.Notification.hide();
            OC.Notification.showHtml(message);
        } else {
            OC.Notification.showHtml(message);
        }
        setTimeout(function() {
            if ($("#notification").text() === message) {
                OC.Notification.hide();
            }
        }, 10000);
    },

    showIconError: function(folder, clickAction, errorImageUrl) {
        var bgColor = '#F2DEDE';
        var imageUrl = "url(" + errorImageUrl + ")";
        var trFolder = $('#fileList tr[data-file=\"' + this.jqSelEscape(folder) + '\"]');
        this.changeFolderIcon(folder, imageUrl);
        this.toggleLink(folder, false, clickAction);
        trFolder.css('background-color', bgColor);
    },

    /**
     * @param folder string with the folder or jQuery element pointing to the tr element
     */
    storeDefaultFolderIconAndBgcolor: function(folder) {
        var trFolder;
        if (folder instanceof $) {
            trFolder = folder;
        } else {
            trFolder = $('#fileList tr[data-file=\"' + this.jqSelEscape(folder) + '\"]');
        }
        trFolder.each(function(){
            var thisElement = $(this);
            if (thisElement.data('oldbgcolor') === undefined) {
                thisElement.data('oldbgcolor', thisElement.css('background-color'));
            }
        });

        var icon = trFolder.find('td:first-child div.thumbnail');
        icon.each(function(){
            var thisElement = $(this);
            if (thisElement.data('oldImage') === undefined) {
                thisElement.data('oldImage', thisElement.css('background-image'));
            }
        });
    },

    /**
     * @param folder string with the folder or jQuery element pointing to the tr element
     */
    restoreFolder: function(folder) {
        var trFolder;
        if (folder instanceof $) {
            trFolder = folder;
        } else {
            trFolder = $('#fileList tr[data-file=\"' + this.jqSelEscape(folder) + '\"]');
        }
        trFolder.css('background-color', '');
        tdChilds = trFolder.find("td:first-child div.thumbnail");
        tdChilds.each(function(){
            var thisElement = $(this);
            thisElement.css('background-image', thisElement.data('oldImage'));
        });
    },

    /**
     * @param folder string with the folder or jQuery element pointing to the first td element
     * of the tr matching the folder name
     */
    changeFolderIcon: function(filename, route) {
        var file;
        if (filename instanceof $) {
            file = filename;
        } else {
            file = $("#fileList tr[data-file=\"" + this.jqSelEscape(filename) + "\"] > td:first-child div.thumbnail");
        }
        // file.css('background-image', route).hide().show(0);
        // previous line is required in Chrome to force the css update so the image url
        // is stored correctly later
        //file.css('background-image', route).height();
    },

    toggleLink: function(filename, active, action) {
        var link;
        if (filename instanceof $) {
            link = filename;
        } else {
            link = $("#fileList tr[data-file=\"" + this.jqSelEscape(filename) + "\"] > td:first-child a.name");
        }
        if (active) {
            link.off('click.connectivity');
            OCA.Files.App.fileList.fileActions.display(link.parent(), true, OCA.Files.App.fileList);
        } else {
            link.find('.fileactions, .nametext .action').remove();  // from files/js/fileactions (display)
            link.off('click.connectivity');
            link.on('click.connectivity', function(e){
                if (action && $.isFunction(action)) {
                    action(filename);
                }
                e.preventDefault();
                return false;
            });
        }
    },

    isCorrectViewAndRootFolder: function() {
        // correct views = files & extstoragemounts
        if (OCA.Files.App.getActiveView() === 'files' || OCA.Files.App.getActiveView() === 'extstoragemounts') {
            return OCA.Files.App.getCurrentAppContainer().find('#dir').val() === '/';
        }
        return false;
    },

    /* escape a selector expression for jQuery */
    jqSelEscape: function(expression) {
        return expression.replace(/[!"#$%&'()*+,.\/:;<=>?@\[\\\]^`{|}~]/g, '\\$&');
    },

    /* Copied from http://stackoverflow.com/questions/2631001/javascript-test-for-existence-of-nested-object-key */
    checkNested: function(cobj /*, level1, level2, ... levelN*/) {
        var args = Array.prototype.slice.call(arguments),
            obj = args.shift();

        for (var i = 0; i < args.length; i++) {
            if (!obj || !obj.hasOwnProperty(args[i])) {
                return false;
            }
            obj = obj[args[i]];
        }
        return true;
    }
};
