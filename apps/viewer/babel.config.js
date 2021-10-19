const babelConfig = require('@nextcloud/babel-config')

babelConfig.plugins.push('@babel/plugin-proposal-class-properties')

module.exports = babelConfig
