import { getUploader, UploaderStatus } from '@nextcloud/upload'
import { t } from '@nextcloud/l10n'
let registered = false
function onBeforeUnload(event: BeforeUnloadEvent): void {
  const uploader = getUploader()
  if (uploader.info.status === UploaderStatus.IDLE) {
  	return
  }
  event.preventDefault()
  event.returnValue = t(
  	'files',
  	'File uploads are still in progress. Leaving the page will cancel them.',
  )
}
/**
* Warn before closing or navigating away while uploads are running or paused.
*/
export default function registerUploadBeforeUnload(): void {
  if (registered) {
  	return
  }
  registered = true
  window.addEventListener('beforeunload', onBeforeUnload)
}