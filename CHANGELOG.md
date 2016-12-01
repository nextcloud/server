ChangeLog
=========

NC 11 (????-??-??)
------------------
* PHP 5.4 and 5.5 no longer supported
* PHP 7.1 support
* OC_L10N removed use \OCP\IL10N (#1948)
* Preview handling is improved by sharing previews:
  * Preview sharing (shared files/external storages)
  * Previews are stored in the AppData
  * Previews are served faster by not first converting them to image objects
* Core preview route changed:
  * Route for the urlgenerator changed from 'core_ajax_preview' to 'core.Preview.getPreview'
  * $urlGenerator->linkToRoute('core_ajax_preview', ...) => $urlGenerator->linkToRoute('core.Preview.getPreview', ...)
* Avatars are cached
* Avatars are moved to AppData
  * For existing avatars this happens automatically in a background job which means that on upgrade you might
    not see your avatar right away. However after the job has run it should show up again automatically.

