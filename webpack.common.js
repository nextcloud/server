const core = require('./core/webpack');
const files_trashbin = require('./apps/files_trashbin/webpack')
const oauth2 = require('./apps/oauth2/webpack')

module.exports = [].concat(core, files_trashbin, oauth2);
