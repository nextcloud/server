const stylelintConfig = require('@nextcloud/stylelint-config')

// Disable nested scss import
stylelintConfig.rules['no-invalid-position-at-import-rule'] = null

module.exports = stylelintConfig
