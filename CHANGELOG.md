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
