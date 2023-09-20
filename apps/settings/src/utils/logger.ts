import { getLoggerBuilder } from '@nextcloud/logger'

export const logger = getLoggerBuilder()
	.setApp('settings')
	.detectUser()
	.build()
