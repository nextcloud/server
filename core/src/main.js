// If you remove the line below, tests won't pass
import OC from './OC/index'

__webpack_nonce__ = btoa(OC.requestToken)

require('./mainReal')
