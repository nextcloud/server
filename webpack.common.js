const core = require('./core/webpack');
const oauth2 = require('./apps/oauth2/webpack')

module.exports = [].concat(core, oauth2);
