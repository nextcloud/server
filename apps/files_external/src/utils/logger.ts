import { getLoggerBuilder } from '@nextcloud/logger'

export default getLoggerBuilder().setApp('files_external').build()
