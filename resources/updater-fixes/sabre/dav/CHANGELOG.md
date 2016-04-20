ChangeLog
=========

3.0.9 (2016-04-06)
------------------

* Set minimum libxml version to 2.7.0 in `composer.json`.
* #727: Added another workaround to make CalDAV work for Windows 10 clients.
* #805: It wasn't possible to create calendars that hold events, journals and
  todos using MySQL, because the `components` column was 1 byte too small.
* The zip release ships with [sabre/vobject 3.5.1][vobj],
  [sabre/http 4.2.1][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.1.0][uri] and [sabre/xml 1.4.1][xml].


3.0.8 (2016-03-12)
------------------

* #784: Sync logs for address books were not correctly cleaned up after
  deleting them.
* #787: Cannot use non-seekable stream-wrappers with range requests.
* Faster XML parsing and generating due to sabre/xml update.
* The zip release ships with [sabre/vobject 3.5.0][vobj],
  [sabre/http 4.2.1][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.1.0][uri] and [sabre/xml 1.4.1][xml].


3.0.7 (2016-01-12)
------------------

* #752: PHP 7 support for 3.0 branch. (@DeepDiver1975)
* The zip release ships with [sabre/vobject 3.5.0][vobj],
  [sabre/http 4.2.1][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.3.0][xml].


3.0.6 (2016-01-04)
------------------

* #730: Switched all mysql tables to `utf8mb4` character set, allowing you to
  use emoji in some tables where you couldn't before.
* #729: Not all calls to `Sabre\DAV\Tree::getChildren()` were properly cached.
* #734: Return `418 I'm a Teapot` when generating a multistatus response that
  has resources with no returned properties.
* #740: Bugs in `migrate20.php` script.
* The zip release ships with [sabre/vobject 3.4.8][vobj],
  [sabre/http 4.1.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.3.0][xml].


3.0.5 (2015-09-15)
------------------

* #704: Fixed broken uri encoding in multistatus responses. This affected
  at least CyberDuck, but probably also others.
* The zip release ships with [sabre/vobject 3.4.7][vobj],
  [sabre/http 4.1.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.2.0][xml].


3.0.4 (2015-09-06)
------------------

* #703: PropPatch in client is not correctly encoded.
* #709: Throw exception when running into empty
  `supported-calendar-component-set`.
* #711: Don't trigger deserializers for empty elements in `{DAV:}prop`. This
  fixes issues when using sabre/dav as a client.
* #705: A `MOVE` request that gets prevented from deleting the source resource
  will still remove the target resource. Now all events are triggered before
  any destructive operations.
* The zip release ships with [sabre/vobject 3.4.7][vobj],
  [sabre/http 4.1.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.2.0][xml].


3.0.3 (2015-08-06)
------------------

* #700: Digest Auth fails on `HEAD` requests.
* Fixed example files to no longer use now-deprecated realm argument.
* The zip release ships with [sabre/vobject 3.4.6][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.1.0][xml].


3.0.2 (2015-07-21)
------------------

* #657: Migration script would break when coming a cross an iCalendar object
  with no UID.
* #691: Workaround for broken Windows Phone client.
* Fixed a whole bunch of incorrect php docblocks.
* The zip release ships with [sabre/vobject 3.4.5][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.1.0][xml].


3.0.1 (2015-07-02)
------------------

* #674: Postgres sql file fixes. (@davesouthey)
* #677: Resources with the name '0' would not get retrieved when using
  `Depth: infinity` in a `PROPFIND` request.
* #680: Fix 'autoprefixing' of dead `{DAV:}href` properties.
* #675: NTLM support in DAV\Client. (@k42b3)
* The zip release ships with [sabre/vobject 3.4.5][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.1.0][xml].


3.0.0 (2015-06-02)
------------------

* No changes since last beta.
* The zip release ships with [sabre/vobject 3.4.5][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.0.0][xml].


3.0.0-beta3 (2015-05-29)
------------------------

* Fixed deserializing href properties with no value.
* Fixed deserializing `{DAV:}propstat` without a `{DAV:}prop`.
* #668: More information about vcf-export-plugin in browser plugin.
* #669: Add export button to browser plugin for address books. (@mgee)
* #670: multiget report hrefs were not decoded.
* The zip release ships with [sabre/vobject 3.4.4][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.0.0][xml].


3.0.0-beta2 (2015-05-27)
------------------------

* A node's properties should not overwrite properties that were already set.
* Some uris were not correctly encoded in notifications.
* The zip release ships with [sabre/vobject 3.4.4][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.0.0][xml].


3.0.0-beta1 (2015-05-25)
------------------------

* `migrate22.php` is now called `migrate30.php`.
* Using php-cs-fixer for automated coding standards enforcement and fixing.
* #660: principals could break html output.
* #662: Fixed several bugs in the `share` request parser.
* #665: Fix a bug in serialization of complex properties in the proppatch
  request in the client.
* #666: expand-property report did not correctly prepend the base uri when
  generating uris, this caused delegation to break.
* #659: Don't throw errors when when etag-related checks are done on
  collections.
* Fully supporting the updated `Prefer` header syntax, as defined in
  [rfc7240][rfc7240].
* The zip release ships with [sabre/vobject 3.4.3][vobj],
  [sabre/http 4.0.0][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 1.0.0][xml].


3.0.0-alpha1 (2015-05-19)
-------------------------

* It's now possible to get all property information from files using the
  browser plugin.
* Browser plugin will now show a 'calendar export' button when the
  ics-export plugin is enabled.
* Some nodes that by default showed the current time as their last
  modification time, now no longer has a last modification time.
* CardDAV namespace was missing from default namespaceMap.
* #646: Properties can now control their own HTML output in the browser plugin.
* #646: Nicer HTML output for the `{DAV:}acl` property.
* Browser plugin no longer shows a few properties that take up a lot of space,
  but are likely not really interesting for most users.
* #654: Added a collection, `Sabre\DAVACL\FS\HomeCollection` for automatically
  creating a private home collection per-user.
* Changed all MySQL columns from `VARCHAR` to `VARBINARY` where possible.
* Improved older migration scripts a bit to allow easier testing.
* The zip release ships with [sabre/vobject 3.4.3][vobj],
  [sabre/http 4.0.0-alpha3][http], [sabre/event 2.0.2][evnt],
  [sabre/uri 1.0.1][uri] and [sabre/xml 0.4.3][xml].


2.2.0-alpha4 (2015-04-13)
-------------------------

* Complete rewrite of the XML system. We now use our own [sabre/xml][xml],
  which has a much smarter XML Reader and Writer.
* BC Break: It's no longer possible to instantiate the Locks plugin without
  a locks backend. I'm not sure why this ever made sense.
* Simplified the Locking system and fixed a bug related to if tokens checking
  locks unrelated to the current request.
* `FSExt` Directory and File no longer do custom property storage. This
  functionality is already covered pretty well by the `PropertyStorage` plugin,
  so please switch.
* Renamed `Sabre\CardDAV\UserAddressBooks` to `Sabre\CardDAV\AddressBookHome`
  to be more consistent with `CalendarHome` as well as the CardDAV
  specification.
* `Sabre\DAV\IExtendedCollection` now receives a `Sabre\DAV\MkCol` object as
  its second argument, and no longer receives seperate properties and
  resourcetype arguments.
* `MKCOL` now integrates better with propertystorage plugins.
* The zip release ships with [sabre/vobject 3.4.2][vobj],
  [sabre/http 4.0.0-alpha1][http], [sabre/event 2.0.1][evnt],
  [sabre/uri 1.0.0][uri] and [sabre/xml 0.4.3][xml].


2.2.0-alpha3 (2015-02-25)
-------------------------

* Contains all the changes introduced between 2.1.2 and 2.1.3.
* The zip release ships with [sabre/vobject 3.4.2][vobj],
  [sabre/http 4.0.0-alpha1][http], [sabre/event 2.0.1][evnt] and
  [sabre/uri 1.0.0][uri].


2.2.0-alpha2 (2015-01-09)
-------------------------

* Renamed `Sabre\DAV\Auth\Backend\BackendInterface::requireAuth` to
  `challenge`, which is a more correct and better sounding name.
* The zip release ships with [sabre/vobject 3.3.5][vobj],
  [sabre/http 3.0.4][http], [sabre/event 2.0.1][evnt].


2.2.0-alpha1 (2014-12-10)
-------------------------

* The browser plugin now has a new page with information about your sabredav
  server, and shows information about every plugin that's loaded in the
  system.
* #191: The Authentication system can now support multiple authentication
  backends.
* Removed: all `$tableName` arguments from every PDO backend. This was already
  deprecated, but has now been fully removed. All of these have been replaced
  with public properties.
* Deleted several classes that were already deprecated much earlier:
  * `Sabre\CalDAV\CalendarRootNode`
  * `Sabre\CalDAV\UserCalendars`
  * `Sabre\DAV\Exception\FileNotFound`
  * `Sabre\DAV\Locks\Backend\FS`
  * `Sabre\DAV\PartialUpdate\IFile`
  * `Sabre\DAV\URLUtil`
* Removed: `Sabre\DAV\Client::addTrustedCertificates` and
  `Sabre\DAV\Client::setVerifyPeer`.
* Removed: `Sabre\DAV\Plugin::getPlugin()` can now no longer return plugins
  based on its class name.
* Removed: `Sabre\DAVACL\Plugin::getPrincipalByEmail()`.
* #560: GuessContentType plugin will now set content-type to
  `application/octet-stream` if a better content-type could not be determined.
* #568: Added a `componentType` argument to `ICSExportPlugin`, allowing you to
  specifically fetch `VEVENT`, `VTODO` or `VJOURNAL`.
* #582: Authentication backend interface changed to be stateless. If you
  implemented your own authentication backend, make sure you upgrade your class
  to the latest API!
* #582: `Sabre\DAV\Auth\Plugin::getCurrentUser()` is now deprecated. Use
  `Sabre\DAV\Auth\Plugin::getCurrentPrincipal()` instead.
* #193: Fix `Sabre\DAV\FSExt\Directory::getQuotaInfo()` on windows.


2.1.11 (2016-??-??)
-------------------

* #805: It wasn't possible to create calendars that hold events, journals and
  todos using MySQL, because the `components` column was 1 byte too small.


2.1.10 (2016-03-10)
-------------------

* #784: Sync logs for address books were not correctly cleaned up after
  deleting them.


2.1.9 (2016-01-25)
------------------

* #674: PHP7 support (@DeepDiver1975).
* The zip release ships with [sabre/vobject 3.5.0][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.8 (2016-01-04)
------------------

* #729: Fixed a caching problem in the Tree object.
* #740: Bugs in `migrate20.php` script.
* The zip release ships with [sabre/vobject 3.4.8][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.7 (2015-09-05)
------------------

* #705: A `MOVE` request that gets prevented from deleting the source resource
  will still remove the target resource. Now all events are triggered before
  any destructive operations.
* The zip release ships with [sabre/vobject 3.4.7][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.6 (2015-07-21)
------------------

* #657: Migration script would break when coming a cross an iCalendar object
  with no UID.
* #691: Workaround for broken Windows Phone client.
* The zip release ships with [sabre/vobject 3.4.5][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.5 (2015-07-11)
------------------

* #677: Resources with the name '0' would not get retrieved when using
  `Depth: infinity` in a `PROPFIND` request.
* The zip release ships with [sabre/vobject 3.4.5][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.4 (2015-05-25)
------------------

* #651: Double-encoded path in the browser plugin. Should fix a few broken
  links in some setups.
* #650: Correctly cleaning up change info after deleting calendars (@ErrOrnAmE).
* #658: Updating `schedule-calendar-default-URL` does not work well, so we're
  disabling it until there's a better fix.
* The zip release ships with [sabre/vobject 3.4.3][vobj],
  [sabre/http 3.0.5][http], and [sabre/event 2.0.2][evnt].


2.1.3 (2015-02-25)
------------------

* #586: `SCHEDULE-STATUS` should not contain a reason-phrase.
* #539: Fixed a bug related to scheduling in shared calendars.
* #595: Support for calendar-timezone in iCalendar exports.
* #581: findByUri would send empty prefixes to the principal backend (@soydeedo)
* #611: Escaping a bit more HTML output in the browser plugin. (@LukasReschke)
* #610: Don't allow discovery of arbitrary files using `..` in the browser
  plugin (@LukasReschke).
* Browser plugin now shows quota properties.
* #612: PropertyStorage didn't delete properties from nodes when a node's
  parents get deleted.
* #581: Fixed problems related to finding attendee information during
  scheduling.
* The zip release ships with [sabre/vobject 3.4.2][vobj],
  [sabre/http 3.0.4][http], and [sabre/event 2.0.1][evnt].


2.1.2 (2014-12-10)
------------------

* #566: Another issue related to the migration script, which would cause
  scheduling to not work well for events that were already added before the
  migration.
* #567: Doing freebusy requests on accounts that had 0 calendars would throw
  a `E_NOTICE`.
* #572: `HEAD` requests trigger a PHP warning.
* #579: Browser plugin can throw exception for a few resourcetypes that didn't
  have an icon defined.
* The zip release ships with [sabre/vobject 3.3.4][vobj],
  [sabre/http 3.0.4][http], and [sabre/event 2.0.1][evnt].


2.1.1 (2014-11-22)
------------------

* #561: IMip Plugin didn't strip mailto: from email addresses.
* #566: Migration process had 2 problems related to adding the `uid` field
  to the `calendarobjects` table.
* The zip release ships with [sabre/vobject 3.3.4][vobj],
  [sabre/http 3.0.2][http], and [sabre/event 2.0.1][evnt].


2.1.0 (2014-11-19)
------------------

* #541: CalDAV PDO backend didn't respect overridden PDO table names.
* #550: Scheduling invites are no longer delivered into shared calendars.
* #554: `calendar-multiget` `REPORT` did not work on inbox items.
* #555: The `calendar-timezone` property is now respected for floating times
  and all-day events in the `calendar-query`, `calendar-multiget` and
  `free-busy-query` REPORTs.
* #555: The `calendar-timezone` property is also respected for scheduling
  free-busy requests.
* #547: CalDAV system too aggressively 'corrects' incoming iCalendar data, and
  as a result doesn't return an etag for common cases.
* The zip release ships with [sabre/vobject 3.3.4][vobj],
  [sabre/http 3.0.2][http], and [sabre/event 2.0.1][evnt].


2.1.0-alpha2 (2014-10-23)
-------------------------

* Added: calendar-user-address-set to default principal search properties
  list. This should fix iOS attendee autocomplete support.
* Changed: Moved all 'notifications' functionality from `Sabre\CalDAV\Plugin`
  to a new plugin: `Sabre\CalDAV\Notifications\Plugin`. If you want to use
  notifications-related functionality, just add this plugin.
* Changed: Accessing the caldav inbox, outbox or notification collection no
  longer triggers getCalendarsForUser() on backends.
* #533: New invites are no longer delivered to taks-only calendars.
* #538: Added `calendarObjectChange` event.
* Scheduling speedups.
* #539: added `afterResponse` event. (@joserobleda)
* Deprecated: All the "tableName" constructor arguments for all the PDO
  backends are now deprecated. They still work, but will be removed in the
  next major sabredav version. Every argument that is now deprecated can now
  be accessed as a public property on the respective backends.
* #529: Added getCalendarObjectByUID to PDO backend, speeding up scheduling
  operations on large calendars.
* The zip release ships with [sabre/vobject 3.3.3][vobj],
  [sabre/http 3.0.2][http], and [sabre/event 2.0.1][evnt].


2.1.0-alpha1 (2014-09-23)
-------------------------

* Added: Support for [rfc6638][rfc6638], also known as CalDAV Scheduling.
* Added: Automatically converting between vCard 3, 4 and jCard using the
  `Accept:` header, in CardDAV reports, and automatically converting from
  jCard to vCard upon `PUT`. It's important to note that your backends _may_
  now recieve both vCard 3.0 and 4.0.
* Added: #444. Collections can now opt-in to support high-speed `MOVE`.
* Changed: PropertyStorage backends now have a `move` method.
* Added: `beforeMove`, and `afterMove` events.
* Changed: A few database changes for the CalDAV PDO backend. Make sure you
  run `bin/migrate21.php` to upgrade your database schema.
* Changed: CalDAV backends have a new method: `getCalendarObjectByUID`. This
  method MUST be implemented by all backends, but the `AbstractBackend` has a
  simple default implementation for this.
* Changed: `Sabre\CalDAV\UserCalendars` has been renamed to
  `Sabre\CalDAV\CalendarHome`.
* Changed: `Sabre\CalDAV\CalendarRootNode` has been renamed to
  `Sabre\CalDAV\CalendarRoot`.
* Changed: The IMipHandler has been completely removed. With CalDAV scheduling
  support, it is no longer needed. It's functionality has been replaced by
  `Sabre\CalDAV\Schedule\IMipPlugin`, which can now send emails for clients
  other than iCal.
* Removed: `Sabre\DAV\ObjectTree` and `Sabre\DAV\Tree\FileSystem`. All this
  functionality has been merged into `Sabre\DAV\Tree`.
* Changed: PrincipalBackend now has a findByUri method.
* Changed: `PrincipalBackend::searchPrincipals` has a new optional `test`
  argument.
* Added: Support for the `{http://calendarserver.org/ns/}email-address-set`
  property.
* #460: PropertyStorage must move properties during `MOVE` requests.
* Changed: Restructured the zip distribution to be a little bit more lean
  and consistent.
* #524: Full support for the `test="anyof"` attribute in principal-search
  `REPORT`.
* #472: Always returning lock tokens in the lockdiscovery property.
* Directory entries in the Browser plugin are sorted by type and name.
  (@aklomp)
* #486: It's now possible to return additional properties when an 'allprop'
  PROPFIND request is being done. (@aklomp)
* Changed: Now return HTTP errors when an addressbook-query REPORT is done
  on a uri that's not a vcard. This should help with debugging this common
  mistake.
* Changed: `PUT` requests with a `Content-Range` header now emit a 400 status
  instead of 501, as per RFC7231.
* Added: Browser plugin can now display the contents of the
  `{DAV:}supported-privilege-set` property.
* Added: Now reporting `CALDAV:max-resource-size`, but we're not actively
  restricting it yet.
* Changed: CalDAV plugin is now responsible for reporting
  `CALDAV:supported-collation-set` and `CALDAV:supported-calendar-data`
  properties.
* Added: Now reporting `CARDDAV:max-resource-size`, but we're not actively
  restricting it yet.
* Added: Support for `CARDDAV:supported-collation-set`.
* Changed: CardDAV plugin is now responsible for reporting
  `CARDDAV:supported-address-data`. This functionality has been removed from
  the CardDAV PDO backend.
* When a REPORT is not supported, we now emit HTTP error 415, instead of 403.
* #348: `HEAD` requests now work wherever `GET` also works.
* Changed: Lower priority for the iMip plugins `schedule` event listener.
* Added: #523 Custom CalDAV backends can now mark any calendar as read-only.
* The zip release ships with [sabre/vobject 3.3.3][vobj],
  [sabre/http 3.0.0][http], and [sabre/event 2.0.0][evnt].


2.0.9 (2015-09-04)
------------------

* #705: A `MOVE` request that gets prevented from deleting the source resource
  will still remove the target resource. Now all events are triggered before
  any destructive operations.
* The zip release ships with [sabre/vobject 3.4.6][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].



2.0.8 (2015-07-11)
------------------

* #677: Resources with the name '0' would not get retrieved when using
  `Depth: infinity` in a `PROPFIND` request.
* The zip release ships with [sabre/vobject 3.3.5][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.7 (2015-05-25)
------------------

* #650: Correctly cleaning up change info after deleting calendars (@ErrOrnAmE).
* The zip release ships with [sabre/vobject 3.3.4][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.6 (2014-12-10)
------------------

* Added `Sabre\CalDAV\CalendarRoot` as an alias for
  `Sabre\CalDAV\CalendarRootNode`. The latter is going to be deprecated in 2.1,
  so this makes it slightly easier to write code that works in both branches.
* #497: Making sure we're initializing the sync-token field with a value after
  migration.
* The zip release ships with [sabre/vobject 3.3.4][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.5 (2014-10-14)
------------------

* #514: CalDAV PDO backend didn't work when overriding the 'calendar changes'
  database table name.
* #515: 304 status code was not being sent when checking preconditions.
* The zip release ships with [sabre/vobject 3.3.3][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.4 (2014-08-27)
------------------

* #483: typo in calendars creation for PostgreSQL.
* #487: Locks are now automatically removed after a node has been deleted.
* #496: Improve CalDAV and CardDAV sync when there is no webdav-sync support.
* Added: Automatically mapping internal sync-tokens to getctag.
* The zip release ships with [sabre/vobject 3.3.1][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.3 (2014-07-14)
------------------

* #474: Fixed PropertyStorage `pathFilter()`.
* #476: CSP policy incorrect, causing stylesheets to not load in the browser
  plugin.
* #475: Href properties in the browser plugin sometimes included a backslash.
* #478: `TooMuchMatches` exception never worked. This was fixed, and we also
  took this opportunity to rename it to `TooManyMatches`.
* The zip release ships with [sabre/vobject 3.2.4][vobj],
  [sabre/http 2.0.4][http], and [sabre/event 1.0.1][evnt].


2.0.2 (2014-06-12)
------------------

* #470: Fixed compatibility with PHP < 5.4.14.
* #467: Fixed a problem in `examples/calendarserver.php`.
* #466: All the postgresql sample files have been updated.
* Fixed: An error would be thrown if a client did a propfind on a node the
  user didn't have access to.
* Removed: Old and broken example code from the `examples/` directory.
* The zip release ships with [sabre/vobject 3.2.3][vobj],
  [sabre/http 2.0.3][http], and [sabre/event 1.0.1][evnt].


2.0.1 (2014-05-28)
------------------

* #459: PROPFIND requests on Files with no Depth header would return a fatal
  error.
* #464: A PROPFIND allprops request should not return properties with status
  404.
* The zip release ships with [sabre/vobject 3.2.2][vobj],
  [sabre/http 2.0.3][http], and [sabre/event 1.0.0][evnt].


2.0.0 (2014-05-22)
------------------

* The zip release ships with [sabre/vobject 3.2.2][vobj],
  [sabre/http 2.0.3][http], and [sabre/event 1.0.0][evnt].
* Fixed: #456: Issue in sqlite migration script.
* Updated: MySQL database schema optimized by using more efficient column types.
* Cleaned up browser design.


2.0.0-beta1 (2014-05-15)
-------------------------

* The zip release ships with [sabre/vobject 3.2.2][vobj],
  [sabre/http 2.0.3][http], and [sabre/event 1.0.0][evnt].
* BC Break: Property updating and fetching got refactored. Read the [migration
  document][mi20] for more information. This allows for creation of a generic
  property storage, and other property-related functionality that was not
  possible before.
* BC Break: Removed `propertyUpdate`, `beforeGetProperties` and
  `afterGetProperties` events.
* Fixed: #413: Memory optimizations for the CardDAV PDO backend.
* Updated: Brand new browser plugin with more debugging features and a design
  that is slightly less painful.
* Added: Support for the `{DAV:}supported-method-set` property server-wide.
* Making it easier for implementors to override how the CardDAV addressbook
  home is located.
* Fixed: Issue #422 Preconditions were not being set on PUT on non-existant
  files. Not really a chance for data-loss, but incorrect nevertheless.
* Fixed: Issue #428: Etag check with `If:` fails if the target is a collection.
* Fixed: Issues #430, #431, #433: Locks plugin didn't not properly release
  filesystem based locks.
* Fixed: #443. Support for creating new calendar subscriptions for OS X 10.9.2
  and up.
* Removed: `Sabre\DAV\Server::NODE_*` constants.
* Moved all precondition checking into a central place, instead of having to
  think about it on a per-method basis.
* jCal transformation for calendar-query REPORT now works again.
* Switched to PSR-4
* Fixed: #175. Returning ETag header upon a failed `If-Match` or
  `If-None-Match` check.
* Removed: `lib/Sabre/autoload.php`. Use `vendor/autoload.php` instead.
* Removed: all the rfc documentation from the sabre/dav source. This made the
  package needlessly larger.
* Updated: Issue #439. Lots of updates in PATCH support. The
  Sabre_DAV_PartialUpdate_IFile interface is now deprecated and will be
  removed in a future version.
* Added: `Sabre\DAV\Exception\LengthRequired`.

1.9.0-alpha2 (2014-01-14)
-------------------------

* The zip release ships with sabre/vobject 3.1.3, sabre/http 2.0.1, and
  sabre/event 1.0.0.
* Added: Browser can now inspect any node, if ?sabreaction=browser is appended.
* Fixed: Issue #178. Support for multiple items in the Timeout header.
* Fixed: Issue #382. Stricter checking if calendar-query is allowed to run.
* Added: Depth: Infinity support for PROPFIND request. Thanks Thomas Müller and
  Markus Goetz.


1.9.0-alpha1 (2013-11-07)
-------------------------

* The zip release ships with sabre/vobject 3.1.3, sabre/http 2.0.0alpha5, and
  sabre/event 1.0.0.
* BC Break: The CardDAV and CalDAV BackendInterface each have a new method:
  getMultipleCards and getMultipleCalendarObjects. The Abstract and PDO backends
  have default implementations, but if you implement that interface directly,
  this method is now required.
* BC Break: XML property classes now receive an extra argument in their
  unserialize method ($propertyMap). This allows for recursively parsing
  properties, if needed.
* BC Break: Now using sabre/event for event emitting/subscription. For plugin
  authors this means Server::subscribeEvent is now Server::on, and
  Server::broadcastEvent is now Server::emit.
* BC Break: Almost all core functionality moved into a CorePlugin.
* BC Break: Most events triggered by the server got an overhaul.
* Changed: Sabre\HTTP now moved into a dedicated sabre/http package.
* Added: Support for WebDAV-sync (rfc6578).
* Added: Support for caldav-subscriptions, which is an easy way for caldav
  clients to manage a list of subscriptions on the server.
* Added: Support for emitting and receiving jCal instead of iCalendar for
  CalDAV.
* Added: BasicCallback authenticaton backend, for creating simple authentication
  systems without having to define any classes.
* Added: A $transactionType property on the server class. This can be used for
  logging and performance measuring purposes.
* Fixed: If event handlers modify the request body from a PUT request, an ETag
  is no longer sent back.
* Added: Sabre\DAV\IMultiGet to optimize requests that retrieve information
  about lists of resources.
* Added: MultiGet support to default CalDAV and CardDAV backends, speeding up
  the multiget and sync reports quite a bit!
* Added: ICSExportPlugin can now generate jCal, filter on time-ranges and expand
  recurrences.
* Fixed: Read-only access to calendars still allows the sharee to modify basic
  calendar properties, such as the displayname and color.
* Changed: The default supportedPrivilegeSet has changed. Most privileges are no
  longer marked as abstract.
* Changed: More elegant ACL management for CalendarObject and Card nodes.
* Added: Browser plugin now marks a carddav directory as type Directory, and a
  shared calendar as 'Shared'.
* Added: When debugExceptions is turned on, all previous exceptions are also
  traversed.
* Removed: Got rid of the Version classes for CalDAV, CardDAV, HTTP, and DAVACL.
  Now that there's no separate packages anymore, this makes a bit more sense.
* Added: Generalized the multistatus response parser a bit more, for better
  re-use.
* Added: Sabre\DAV\Client now has support for complex properties for PROPPATCH.
  (Issue #299).
* Added: Sabre\DAV\Client has support for gzip and deflate encoding.
* Added: Sabre\DAV\Client now has support for sending objects as streams.
* Added: Deserializer for {DAV:}current-user-privilege-set.
* Added: Addressbooks or backends can now specify custom acl rules when creating
  cards.
* Added: The ability for plugins to validate custom tokens in If: headers.
* Changed: Completely refactored the Lock plugin to deal with the new If: header
  system.
* Added: Checking preconditions for MOVE, COPY, DELETE and PROPPATCH methods.
* Added: has() method on DAV\Property\SupportedReportSet.
* Added: If header now gets checked (with ETag) all the time. Before the dealing
  with the If-header was a responsibility of the Locking plugin.
* Fixed: Outbox access for delegates.
* Added: Issue 333: It's now possible to override the calendar-home in the
  CalDAV plugin.
* Added: A negotiateContentType to HTTP\Request. A convenience method.
* Fixed: Issue 349: Denying copying or moving a resource into it's own subtree.
* Fixed: SabreDAV catches every exception again.
* Added: Issue #358, adding a component=vevent parameter to the content-types
  for calendar objects, if the caldav backend provides this info.


1.8.12-stable (2015-01-21)
--------------------------

* The zip release ships with sabre/vobject 2.1.7.
* #568: Support empty usernames and passwords in basic auth.


1.8.11 (2014-12-10)
-------------------

* The zip release ships with sabre/vobject 2.1.6.
* Updated: MySQL database schema optimized by using more efficient column types.
* #516: The DAV client will now only redirect to HTTP and HTTPS urls.


1.8.10 (2014-05-15)
-------------------

* The zip release ships with sabre/vobject 2.1.4.
* includes changes from version 1.7.12.


1.8.9 (2014-02-26)
------------------

* The zip release ships with sabre/vobject 2.1.3.
* includes changes from version 1.7.11.


1.8.8 (2014-02-09)
------------------

* includes changes from version 1.7.10.
* The zip release ships with sabre/vobject 2.1.3.

1.8.7 (2013-10-02)
------------------

* the zip release ships with sabre/vobject 2.1.3.
* includes changes from version 1.7.9.


1.8.6 (2013-06-18)
------------------

* The zip release ships with sabre/vobject 2.1.0.
* Includes changes from version 1.7.8.


1.8.5 (2013-04-11)
------------------

* The zip release ships with sabre/vobject 2.0.7.
* Includes changes from version 1.7.7.


1.8.4 (2013-04-08)
------------------

* The zip release ships with sabre/vobject 2.0.7.
* Includes changes from version 1.7.6.


1.8.3 (2013-03-01)
------------------

* The zip release ships with sabre/vobject 2.0.6.
* Includes changes from version 1.7.5.
* Fixed: organizer email-address for shared calendars is now prefixed with
  mailto:, as it should.


1.8.2 (2013-01-19)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* Includes changes from version 1.7.4.


1.8.1 (2012-12-01)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* Includes changes from version 1.7.3.
* Fixed: Typo in 1.7 migration script caused it to fail.


1.8.0 (2012-11-08)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* BC Break: Moved the entire codebase to PHP namespaces.
* BC Break: Every backend package (CalDAV, CardDAV, Auth, Locks, Principals) now
  has consistent naming conventions. There's a BackendInterface, and an
  AbstractBackend class.
* BC Break: Changed a bunch of constructor signatures in the CalDAV package, to
  reduce dependencies on the ACL package.
* BC Break: Sabre_CalDAV_ISharedCalendar now also has a getShares method, so
  sharees can figure out who is also on a shared calendar.
* Added: Sabre_DAVACL_IPrincipalCollection interface, to advertise support for
  principal-property-search on any node.
* Added: Simple console script to fire up a fileserver in the current directory
  using PHP 5.4's built-in webserver.
* Added: Sharee's can now also read out the list of invites for a shared
  calendar.
* Added: The Proxy principal classes now both implement an interface, for
  greater flexiblity.


1.7.13 (2014-07-28)
-------------------

* The zip release ships with sabre/vobject 2.1.4.
* Changed: Removed phing and went with a custom build script for now.


1.7.12 (2014-05-15)
-------------------

* The zip release ships with sabre/vobject 2.1.4.
* Updated: Issue #439. Lots of updates in PATCH support. The
  Sabre_DAV_PartialUpdate_IFile interface is now deprecated and will be removed
  in a future version.
* Fixed: Restoring old setting after changing libxml_disable_entity_loader.
* Fixed: Issue #422: Preconditions were not being set on PUT on non-existant
  files. Not really a chance for data-loss, but incorrect nevertheless.
* Fixed: Issue #427: Now checking preconditions on DELETE requests.
* Fixed: Issue #428: Etag check with If: fails if the target is a collection.
* Fixed: Issue #393: PATCH request with missing end-range was handled
  incorrectly.
* Added: Sabre_DAV_Exception_LengthRequired to omit 411 errors.


1.7.11 (2014-02-26)
-------------------

* The zip release ships with sabre/vobject 2.1.3.
* Fixed: Issue #407: large downloads failed.
* Fixed: Issue #414: XXE security problem on older PHP versions.


1.7.10 (2014-02-09)
-------------------

* Fixed: Issue #374: Don't urlescape colon (:) when it's not required.
* Fixed: Potential security vulnerability in the http client.


1.7.9 (2013-10-02)
------------------

* The zip release ships with sabre/vobject 2.1.3.
* Fixed: Issue #365. Incorrect output when principal urls have spaces in them.
* Added: Issue #367: Automatically adding a UID to vcards that don't have them.


1.7.8 (2013-06-17)
------------------

* The zip release ships with sabre/vobject 2.1.0.
* Changed: Sabre\DAV\Client::verifyPeer is now a protected property (instead of
  private).
* Fixed: Text was incorrectly escaped in the Href and HrefList properties,
  disallowing urls with ampersands (&) in them.
* Added: deserializer for Sabre\DAVACL\Property\CurrentUserPrivilegeSet.
* Fixed: Issue 335: Client only deserializes properties with status 200.
* Fixed: Issue 341: Escaping xml in 423 Locked error responses.
* Added: Issue 339: beforeGetPropertiesForPath event.


1.7.7 (2013-04-11)
------------------

* The zip release ships with sabre/vobject 2.0.7.
* Fixed: Assets in the browser plugins were not being served on windows
  machines.


1.7.6 (2013-04-08)
------------------

* The zip release ships with sabre/vobject 2.0.7.
* Fixed: vcardurl in database schema can now hold 255 characters instead of 80
  (which is often way to small).
* Fixed: The browser plugin potentially allowed people to open any arbitrary
  file on windows servers (CVE-2013-1939).


1.7.5 (2013-03-01)
------------------

* The zip release ships with sabre/vobject 2.0.6.
* Change: No longer advertising support for 4.0 vcards. iOS and OS X address
  book don't handle this well, and just advertising 3.0 support seems like the
  most logical course of action.
* Added: ->setVerifyPeers to Sabre_DAV_Client (greatly resisting against it,
  don't use this..).


1.7.4 (2013-01-19)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* Changed: To be compatibile with MS Office 2011 for Mac, a workaround was
  removed that was added to support old versions of Windows XP (pre-SP3).
  Indeed! We needed a crazy workaround to work with one MS product in the past,
  and we can't keep that workaround to be compatible with another MS product.
* Fixed: expand-properties REPORT had incorrect values for the href element.
* Fixed: Range requests now work for non-seekable streams. (Thanks Alfred
  Klomp).
* Fixed: Changed serialization of {DAV:}getlastmodified and {DAV:}supportedlock
  to improve compatiblity with MS Office 2011 for Mac.
* Changed: reverted the automatic translation of 'DAV:' xml namespaces to
  'urn:DAV' when parsing files. Issues were reported with libxml 2.6.32, on a
  relatively recent debian release, so we'll wait till 2015 to take this one out
  again.
* Added: Sabre_DAV_Exception_ServiceUnavailable, for emitting 503's.


1.7.3 (2012-12-01)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* Fixed: Removing double slashes from getPropertiesForPath.
* Change: Marked a few more properties in the CardDAV as protected, instead of
  private.
* Fixed: SharingPlugin now plays nicer with other plugins with similar
  functionality.
* Fixed: Issue 174. Sending back HTTP/1.0 for requests with this version.


1.7.2 (2012-11-08)
------------------

* The zip release ships with sabre/vobject 2.0.5.
* Added: ACL plugin advertises support for 'calendarserver-principal-
  property-search'.
* Fixed: [#153] Allowing for relative http principals in iMip requests.
* Added: Support for cs:first-name and cs:last-name properties in sharing
  invites.
* Fixed: Made a bunch of properties protected, where they were private before.
* Added: Some non-standard properties for sharing to improve compatibility.
* Fixed: some bugfixes in postgres sql script.
* Fixed: When requesting some properties using PROPFIND, they could show up as
  both '200 Ok' and '403 Forbidden'.
* Fixed: calendar-proxy principals were not checked for deeper principal
  membership than 1 level.
* Fixed: setGroupMemberSet argument now correctly receives relative principal
  urls, instead of the absolute ones.
* Fixed: Server class will filter out any bonus properties if any extra were
  returned. This means the implementor of the IProperty class can be a bit
  lazier when implementing. Note: bug numbers after this line refer to Google
  Code tickets. We're using github now.


1.7.1 (2012-10-07)
------------------

* Fixed: include path problem in the migration script.


1.7.0 (2012-10-06)
------------------

* BC Break: The calendarobjects database table has a bunch of new fields, and a
  migration script is required to ensure everything will keep working. Read the
  wiki for more details.
* BC Break: The ICalendar interface now has a new method: calendarQuery.
* BC Break: In this version a number of classes have been deleted, that have
  been previously deprecated. Namely: - Sabre_DAV_Directory (now:
  Sabre_DAV_Collection) - Sabre_DAV_SimpleDirectory (now:
  Sabre_DAV_SimpleCollection)
* BC Break: Sabre_CalDAV_Schedule_IMip::sendMessage now has an extra argument.
  If you extended this class, you should fix this method. It's only used for
  informational purposes.
* BC Break: The DAV: namespace is no longer converted to urn:DAV. This was a
  workaround for a bug in older PHP versions (pre-5.3).
* Removed: Sabre.includes.php was deprecated, and is now removed.
* Removed: Sabre_CalDAV_Server was deprecated, and is now removed. Please use
  Sabre_DAV_Server and check the examples in the examples/ directory.
* Changed: The Sabre_VObject library now spawned into it's own project! The
  VObject library is still included in the SabreDAV zip package.
* Added: Experimental interfaces to allow implementation of caldav-sharing. Note
  that no implementation is provided yet, just the api hooks.
* Added: Free-busy reporting compliant with the caldav-scheduling standard. This
  allows iCal and other clients to fetch other users' free-busy data.
* Added: Experimental NotificationSupport interface to add caldav notifications.
* Added: VCF Export plugin. If enabled, it can generate an export of an entire
  addressbook.
* Added: Support for PATCH using a SabreDAV format, to live-patch files.
* Added: Support for Prefer: return-minimal and Brief: t headers for PROPFIND
  and PROPPATCH requests.
* Changed: Responsibility for dealing with the calendar-query is now moved from
  the CalDAV plugin to the CalDAV backends. This allows for heavy optimizations.
* Changed: The CalDAV PDO backend is now a lot faster for common calendar
  queries.
* Changed: We are now using the composer autoloader.
* Changed: The CalDAV backend now all implement an interface.
* Changed: Instead of Sabre_DAV_Property, Sabre_DAV_PropertyInterface is now the
  basis of every property class.
* Update: Caching results for principal lookups. This should cut down queries
  and performance for a number of heavy requests.
* Update: ObjectTree caches lookups much more aggresively, which will help
  especially speeding up a bunch of REPORT queries.
* Added: Support for the schedule-calendar-transp property.
* Fixed: Marking both the text/calendar and text/x-vcard as UTF-8 encoded.
* Fixed: Workaround for the SOGO connector, as it doesn't understand receiving
  "text/x-vcard; charset=utf-8" for a contenttype.
* Added: Sabre_DAV_Client now throws more specific exceptions in cases where we
  already has an exception class.
* Added: Sabre_DAV_PartialUpdate. This plugin allows you to use the PATCH method
  to update parts of a file.
* Added: Tons of timezone name mappings for Microsoft Exchange.
* Added: Support for an 'exception' event in the server class.
* Fixed: Uploaded VCards without a UID are now rejected. (thanks Dominik!)
* Fixed: Rejecting calendar objects if they are not in the
  supported-calendar-component list. (thanks Armin!)
* Fixed: Issue 219: serialize() now reorders correctly.
* Fixed: Sabre_DAV_XMLUtil no longer returns empty $dom->childNodes if there is
  whitespace in $dom.
* Fixed: Returning 409 Conflict instead of 500 when an attempt is made to create
  a file as a child of something that's not a collection.
* Fixed: Issue 237: xml-encoding values in SabreDAV error responses.
* Fixed: Returning 403, instead of 501 when an unknown REPORT is requested.
* Fixed: Postfixing slash on {DAV:}owner properties.
* Fixed: Several embarrassing spelling mistakes in docblocks.


1.6.10 (2013-06-17)
-------------------

* Fixed: Text was incorrectly escaped in the Href and HrefList properties,
  disallowing urls with ampersands (&) in them.
* Fixed: Issue 341: Escaping xml in 423 Locked error responses.


1.6.9 (2013-04-11)
------------------

* Fixed: Assets in the browser plugins were not being served on windows
  machines.


1.6.8 (2013-04-08)
------------------

* Fixed: vcardurl in database schema can now hold 255 characters instead of 80
  (which is often way to small).
* Fixed: The browser plugin potentially allowed people to open any arbitrary
  file on windows servers. (CVE-2013-1939).


1.6.7 (2013-03-01)
------------------

* Change: No longer advertising support for 4.0 vcards. iOS and OS X address
  book don't handle this well, and just advertising 3.0 support seems like the
  most logical course of action.
* Added: ->setVerifyPeers to Sabre_DAV_Client (greatly resisting against it,
  don't use this..).


1.6.6 (2013-01-19)
------------------

* Fixed: Backported a fix for broken XML serialization in error responses.
  (Thanks @DeepDiver1975!)


1.6.5 (2012-10-04)
------------------

* Fixed: Workaround for line-ending bug OS X 10.8 addressbook has.
* Added: Ability to allow users to set SSL certificates for the Client class.
  (Thanks schiesbn!).
* Fixed: Directory indexes with lots of nodes should be a lot faster.
* Fixed: Issue 235: E_NOTICE thrown when doing a propfind request with
  Sabre_DAV_Client, and no valid properties are returned.
* Fixed: Issue with filtering on alarms in tasks.


1.6.4 (2012-08-02)
------------------

* Fixed: Issue 220: Calendar-query filters may fail when filtering on alarms, if
  an overridden event has it's alarm removed.
* Fixed: Compatibility for OS/X 10.8 iCal in the IMipHandler.
* Fixed: Issue 222: beforeWriteContent shouldn't be called for lock requests.
* Fixed: Problem with POST requests to the outbox if mailto: was not lower
  cased.
* Fixed: Yearly recurrence rule expansion on leap-days no behaves correctly.
* Fixed: Correctly checking if recurring, all-day events with no dtstart fall in
  a timerange if the start of the time-range exceeds the start of the instance
  of an event, but not the end.
* Fixed: All-day recurring events wouldn't match if an occurence ended exactly
  on the start of a time-range.
* Fixed: HTTP basic auth did not correctly deal with passwords containing colons
  on some servers.
* Fixed: Issue 228: DTEND is now non-inclusive for all-day events in the
  calendar-query REPORT and free-busy calculations.


1.6.3 (2012-06-12)
------------------

* Added: It's now possible to specify in Sabre_DAV_Client which type of
  authentication is to be used.
* Fixed: Issue 206: Sabre_DAV_Client PUT requests are fixed.
* Fixed: Issue 205: Parsing an iCalendar 0-second date interval.
* Fixed: Issue 112: Stronger validation of iCalendar objects. Now making sure
  every iCalendar object only contains 1 component, and disallowing vcards,
  forcing every component to have a UID.
* Fixed: Basic validation for vcards in the CardDAV plugin.
* Fixed: Issue 213: Workaround for an Evolution bug, that prevented it from
  updating events.
* Fixed: Issue 211: A time-limit query on a non-relative alarm trigger in a
  recurring event could result in an endless loop.
* Fixed: All uri fields are now a maximum of 200 characters. The Bynari outlook
  plugin used much longer strings so this should improve compatibility.
* Fixed: Added a workaround for a bug in KDE 4.8.2 contact syncing. See
  https://bugs.kde.org/show_bug.cgi?id=300047
* Fixed: Issue 217: Sabre_DAV_Tree_FileSystem was pretty broken.


1.6.2 (2012-04-16)
------------------

* Fixed: Sabre_VObject_Node::$parent should have been public.
* Fixed: Recurrence rules of events are now taken into consideration when doing
  time-range queries on alarms.
* Fixed: Added a workaround for the fact that php's DateInterval cannot parse
  weeks and days at the same time.
* Added: Sabre_DAV_Server::$exposeVersion, allowing you to hide SabreDAV's
  version number from various outputs.
* Fixed: DTSTART values would be incorrect when expanding events.
* Fixed: DTSTART and DTEND would be incorrect for expansion of WEEKLY BYDAY
  recurrences.
* Fixed: Issue 203: A problem with overridden events hitting the exact date and
  time of a subsequent event in the recurrence set.
* Fixed: There was a problem with recurrence rules, for example the 5th tuesday
  of the month, if this day did not exist.
* Added: New HTTP status codes from draft-nottingham-http-new-status-04.


1.6.1 (2012-03-05)
------------------

* Added: createFile and put() can now return an ETag.
* Added: Sending back an ETag on for operations on CardDAV backends. This should
  help with OS X 10.6 Addressbook compatibility.
* Fixed: Fixed a bug where an infinite loop could occur in the recurrence
  iterator if the recurrence was YEARLY, with a BYMONTH rule, and either BYDAY
  or BYMONTHDAY match the first day of the month.
* Fixed: Events that are excluded using EXDATE are still counted in the COUNT=
  parameter in the RRULE property.
* Added: Support for time-range filters on VALARM components.
* Fixed: Correctly filtering all-day events.
* Fixed: Sending back correct mimetypes from the browser plugin (thanks
  Jürgen).
* Fixed: Issue 195: Sabre_CardDAV pear package had an incorrect dependency.
* Fixed: Calendardata would be destroyed when performing a MOVE request.


1.6.0 (2012-02-22)
------------------

* BC Break: Now requires PHP 5.3
* BC Break: Any node that implemented Sabre_DAVACL_IACL must now also implement
  the getSupportedPrivilegeSet method. See website for details.
* BC Break: Moved functions from Sabre_CalDAV_XMLUtil to
  Sabre_VObject_DateTimeParser.
* BC Break: The Sabre_DAVACL_IPrincipalCollection now has two new methods:
  'searchPrincipals' and 'updatePrincipal'.
* BC Break: Sabre_DAV_ILockable is removed and all related per-node locking
  functionality.
* BC Break: Sabre_DAV_Exception_FileNotFound is now deprecated in favor of
  Sabre_DAV_Exception_NotFound. The former will be removed in a later version.
* BC Break: Removed Sabre_CalDAV_ICalendarUtil, use Sabre_VObject instead.
* BC Break: Sabre_CalDAV_Server is now deprecated, check out the documentation
  on how to setup a caldav server with just Sabre_DAV_Server.
* BC Break: Default Principals PDO backend now needs a new field in the
  'principals' table. See the website for details.
* Added: Ability to create new calendars and addressbooks from within the
  browser plugin.
* Added: Browser plugin: icons for various nodes.
* Added: Support for FREEBUSY reports!
* Added: Support for creating principals with admin-level privileges.
* Added: Possibility to let server send out invitation emails on behalf of
  CalDAV client, using Sabre_CalDAV_Schedule_IMip.
* Changed: beforeCreateFile event now passes data argument by reference.
* Changed: The 'propertyMap' property from Sabre_VObject_Reader, must now be
  specified in Sabre_VObject_Property::$classMap.
* Added: Ability for plugins to tell the ACL plugin which principal plugins are
  searchable.
* Added: [DAVACL] Per-node overriding of supported privileges. This allows for
  custom privileges where needed.
* Added: [DAVACL] Public 'principalSearch' method on the DAVACL plugin, which
  allows for easy searching for principals, based on their properties.
* Added: Sabre_VObject_Component::getComponents() to return a list of only
  components and not properties.
* Added: An includes.php file in every sub-package (CalDAV, CardDAV, DAV,
  DAVACL, HTTP, VObject) as an alternative to the autoloader. This often works
  much faster.
* Added: Support for the 'Me card', which allows Addressbook.app users specify
  which vcard is their own.
* Added: Support for updating principal properties in the DAVACL principal
  backends.
* Changed: Major refactoring in the calendar-query REPORT code. Should make
  things more flexible and correct.
* Changed: The calendar-proxy-[read|write] principals will now only appear in
  the tree, if they actually exist in the Principal backend. This should reduce
  some problems people have been having with this.
* Changed: Sabre_VObject_Element_* classes are now renamed to
  Sabre_VObject_Property. Old classes are retained for backwards compatibility,
  but this will be removed in the future.
* Added: Sabre_VObject_FreeBusyGenerator to generate free-busy reports based on
  lists of events.
* Added: Sabre_VObject_RecurrenceIterator to find all the dates and times for
  recurring events.
* Fixed: Issue 97: Correctly handling RRULE for the calendar-query REPORT.
* Fixed: Issue 154: Encoding of VObject parameters with no value was incorrect.
* Added: Support for {DAV:}acl-restrictions property from RFC3744.
* Added: The contentlength for calendar objects can now be supplied by a CalDAV
  backend, allowing for more optimizations.
* Fixed: Much faster implementation of Sabre_DAV_URLUtil::encodePath.
* Fixed: {DAV:}getcontentlength may now be not specified.
* Fixed: Issue 66: Using rawurldecode instead of urldecode to decode paths from
  clients. This means that + will now be treated as a literal rather than a
  space, and this should improve compatibility with the Windows built-in client.
* Added: Sabre_DAV_Exception_PaymentRequired exception, to emit HTTP 402 status
  codes.
* Added: Some mysql unique constraints to example files.
* Fixed: Correctly formatting HTTP dates.
* Fixed: Issue 94: Sending back Last-Modified header for 304 responses.
* Added: Sabre_VObject_Component_VEvent, Sabre_VObject_Component_VJournal,
  Sabre_VObject_Component_VTodo and Sabre_VObject_Component_VCalendar.
* Changed: Properties are now also automatically mapped to their appropriate
  classes, if they are created using the add() or __set() methods.
* Changed: Cloning VObject objects now clones the entire tree, rather than just
  the default shallow copy.
* Added: Support for recurrence expansion in the CALDAV:calendar-multiget and
  CALDAV:calendar-query REPORTS.
* Changed: CalDAV PDO backend now sorts calendars based on the internal
  'calendarorder' field.
* Added: Issue 181: Carddav backends may no optionally not supply the carddata
  in getCards, if etag and size are specified. This may speed up certain
  requests.
* Added: More arguments to beforeWriteContent and beforeCreateFile (see
  WritingPlugins wiki document).
* Added: Hook for iCalendar validation. This allows us to validate iCalendar
  objects when they're uploaded. At the moment we're just validating syntax.
* Added: VObject now support Windows Timezone names correctly (thanks mrpace2).
* Added: If a timezonename could not be detected, we fall back on the default
  PHP timezone.
* Added: Now a Composer package (thanks willdurand).
* Fixed: Support for \N as a newline character in the VObject reader.
* Added: afterWriteContent, afterCreateFile and afterUnbind events.
* Added: Postgresql example files. Not part of the unittests though, so use at
  your own risk.
* Fixed: Issue 182: Removed backticks from sql queries, so it will work with
  Postgres.


1.5.9 (2012-04-16)
------------------

* Fixed: Issue with parsing timezone identifiers that were surrounded by quotes.
  (Fixes emClient compatibility).


1.5.8 (2012-02-22)
------------------

* Fixed: Issue 95: Another timezone parsing issue, this time in calendar-query.


1.5.7 (2012-02-19)
------------------

* Fixed: VObject properties are now always encoded before components.
* Fixed: Sabre_DAVACL had issues with multiple levels of privilege aggregration.
* Changed: Added 'GuessContentType' plugin to fileserver.php example.
* Fixed: The Browser plugin will now trigger the correct events when creating
  files.
* Fixed: The ICSExportPlugin now considers ACL's.
* Added: Made it optional to supply carddata from an Addressbook backend when
  requesting getCards. This can make some operations much faster, and could
  result in much lower memory use.
* Fixed: Issue 187: Sabre_DAV_UUIDUtil was missing from includes file.
* Fixed: Issue 191: beforeUnlock was triggered twice.


1.5.6 (2012-01-07)
------------------

* Fixed: Issue 174: VObject could break UTF-8 characters.
* Fixed: pear package installation issues.


1.5.5 (2011-12-16)
------------------

* Fixed: CalDAV time-range filter workaround for recurring events.
* Fixed: Bug in Sabre_DAV_Locks_Backend_File that didn't allow multiple files to
  be locked at the same time.


1.5.4 (2011-10-28)
------------------

* Fixed: GuessContentType plugin now supports mixed case file extensions.
* Fixed: DATE-TIME encoding was wrong in VObject. (we used 'DATETIME').
* Changed: Sending back HTTP 204 after a PUT request on an existing resource
  instead of HTTP 200. This should fix Evolution CardDAV client compatibility.
* Fixed: Issue 95: Parsing X-LIC-LOCATION if it's available.
* Added: All VObject elements now have a reference to their parent node.


1.5.3 (2011-09-28)
------------------

* Fixed: Sabre_DAV_Collection was missing from the includes file.
* Fixed: Issue 152. iOS 1.4.2 apparantly requires HTTP/1.1 200 OK to be in
  uppercase.
* Fixed: Issue 153: Support for files with mixed newline styles in
  Sabre_VObject.
* Fixed: Issue 159: Automatically converting any vcard and icalendardata to
  UTF-8.
* Added: Sabre_DAV_SimpleFile class for easy static file creation.
* Added: Issue 158: Support for the CARDDAV:supported-address-data property.


1.5.2 (2011-09-21)
------------------

* Fixed: carddata and calendardata MySQL fields are now of type 'mediumblob'.
  'TEXT' was too small sometimes to hold all the data.
* Fixed: {DAV:}supported-report-set is now correctly reporting the reports for
  IAddressBook.
* Added: Sabre_VObject_Property::add() to add duplicate parameters to
  properties.
* Added: Issue 151: Sabre_CalDAV_ICalendar and Sabre_CalDAV_ICalendarObject
  interfaces.
* Fixed: Issue 140: Not returning 201 Created if an event cancelled the creation
  of a file.
* Fixed: Issue 150: Faster URLUtil::encodePath() implementation.
* Fixed: Issue 144: Browser plugin could interfere with
  TemporaryFileFilterPlugin if it was loaded first.
* Added: It's not possible to specify more 'alternate uris' in principal
  backends.


1.5.1 (2011-08-24)
------------------

* Fixed: Issue 137. Hiding action interface in HTML browser for non-collections.
* Fixed: addressbook-query is now correctly returned from the
  {DAV:}supported-report-set property.
* Fixed: Issue 142: Bugs in groupwareserver.php example.
* Fixed: Issue 139: Rejecting PUT requests with Content-Range.


1.5.0 (2011-08-12)
------------------

* Added: CardDAV support.
* Added: An experimental WebDAV client.
* Added: MIME-Directory grouping support in the VObject library. This is very
  useful for people attempting to parse vcards.
* BC Break: Adding parameters with the VObject libraries now overwrites the
  previous parameter, rather than just add it. This makes more sense for 99% of
  the cases.
* BC Break: lib/Sabre.autoload.php is now removed in favor of
  lib/Sabre/autoload.php.
* Deprecated: Sabre_DAV_Directory is now deprecated and will be removed in a
  future version. Use Sabre_DAV_Collection instead.
* Deprecated: Sabre_DAV_SimpleDirectory is now deprecated and will be removed in
  a future version. Use Sabre_DAV_SimpleCollection instead.
* Fixed: Problem with overriding tablenames for the CalDAV backend.
* Added: Clark-notation parser to XML utility.
* Added: unset() support to VObject components.
* Fixed: Refactored CalDAV property fetching to be faster and simpler.
* Added: Central string-matcher for CalDAV and CardDAV plugins.
* Added: i;unicode-casemap support
* Fixed: VObject bug: wouldn't parse parameters if they weren't specified in
  uppercase.
* Fixed: VObject bug: Parameters now behave more like Properties.
* Fixed: VObject bug: Parameters with no value are now correctly parsed.
* Changed: If calendars don't specify which components they allow, 'all'
  components are assumed (e.g.: VEVENT, VTODO, VJOURNAL).
* Changed: Browser plugin now uses POST variable 'sabreAction' instead of
  'action' to reduce the chance of collisions.


1.4.4 (2011-07-07)
------------------

* Fixed: Issue 131: Custom CalDAV backends could break in certain cases.
* Added: The option to override the default tablename all PDO backends use.
  (Issue 60).
* Fixed: Issue 124: 'File' authentication backend now takes realm into
  consideration.
* Fixed: Sabre_DAV_Property_HrefList now properly deserializes. This allows
  users to update the {DAV:}group-member-set property.
* Added: Helper functions for DateTime-values in Sabre_VObject package.
* Added: VObject library can now automatically map iCalendar properties to
  custom classes.


1.4.3 (2011-04-25)
------------------

* Fixed: Issue 123: Added workaround for Windows 7 UNLOCK bug.
* Fixed: datatype of lastmodified field in mysql.calendars.sql. Please change
  the DATETIME field to an INT to ensure this field will work correctly.
* Change: Sabre_DAV_Property_Principal is now renamed to
  Sabre_DAVACL_Property_Principal.
* Added: API level support for ACL HTTP method.
* Fixed: Bug in serializing {DAV:}acl property.
* Added: deserializer for {DAV:}resourcetype property.
* Added: deserializer for {DAV:}acl property.
* Added: deserializer for {DAV:}principal property.


1.4.2-beta (2011-04-01)
-----------------------

* Added: It's not possible to disable listing of nodes that are denied read
  access by ACL.
* Fixed: Changed a few properties in CalDAV classes from private to protected.
* Fixed: Issue 119: Terrible things could happen when relying on guessBaseUri,
  the server was running on the root of the domain and a user tried to access a
  file ending in .php. This is a slight BC break.
* Fixed: Issue 118: Lock tokens in If headers without a uri should be treated as
  the request uri, not 'all relevant uri's.
* Fixed: Issue 120: PDO backend was incorrectly fetching too much locks in cases
  where there were similar named locked files in a directory.


1.4.1-beta (2011-02-26)
-----------------------

* Fixed: Sabre_DAV_Locks_Backend_PDO returned too many locks.
* Fixed: Sabre_HTTP_Request::getHeader didn't return Content-Type when running
  on apache, so a few workarounds were added.
* Change: Slightly changed CalDAV Backend API's, to allow for heavy
  optimizations. This is non-bc breaking.


1.4.0-beta (2011-02-12)
-----------------------

* Added: Partly RFC3744 ACL support.
* Added: Calendar-delegation (caldav-proxy) support.
* BC break: In order to fix Issue 99, a new argument had to be added to
  Sabre_DAV_Locks_Backend_*::getLocks classes. Consult the classes for details.
* Deprecated: Sabre_DAV_Locks_Backend_FS is now deprecated and will be removed
  in a later version. Use PDO or the new File class instead.
* Deprecated: The Sabre_CalDAV_ICalendarUtil class is now marked deprecated, and
  will be removed in a future version. Please use Sabre_VObject instead.
* Removed: All principal-related functionality has been removed from the
  Sabre_DAV_Auth_Plugin, and moved to the Sabre_DAVACL_Plugin.
* Added: VObject library, for easy vcard/icalendar parsing using a natural
  interface.
* Added: Ability to automatically generate full .ics feeds off calendars. To
  use: Add the Sabre_CalDAV_ICSExportPlugin, and add ?export to your calendar
  url.
* Added: Plugins can now specify a pluginname, for easy access using
  Sabre_DAV_Server::getPlugin().
* Added: beforeGetProperties event.
* Added: updateProperties event.
* Added: Principal listings and calendar-access can now be done privately,
  disallowing users from accessing or modifying other users' data.
* Added: You can now pass arrays to the Sabre_DAV_Server constructor. If it's an
  array with node-objects, a Root collection will automatically be created, and
  the nodes are used as top-level children.
* Added: The principal base uri is now customizable. It used to be hardcoded to
  'principals/[user]'.
* Added: getSupportedReportSet method in ServerPlugin class. This allows you to
  easily specify which reports you're implementing.
* Added: A '..' link to the HTML browser.
* Fixed: Issue 99: Locks on child elements were ignored when their parent nodes
  were deleted.
* Fixed: Issue 90: lockdiscovery property and LOCK response now include a
  {DAV}lockroot element.
* Fixed: Issue 96: support for 'default' collation in CalDAV text-match filters.
* Fixed: Issue 102: Ensuring that copy and move with identical source and
  destination uri's fails.
* Fixed: Issue 105: Supporting MKCALENDAR with no body.
* Fixed: Issue 109: Small fixes in Sabre_HTTP_Util.
* Fixed: Issue 111: Properly catching the ownername in a lock (if it's a string)
* Fixed: Sabre_DAV_ObjectTree::nodeExist always returned false for the root
  node.
* Added: Global way to easily supply new resourcetypes for certain node classes.
* Fixed: Issue 59: Allowing the user to override the authentication realm in
  Sabre_CalDAV_Server.
* Update: Issue 97: Looser time-range checking if there's a recurrence rule in
  an event. This fixes 'missing recurring events'.


1.3.0 (2010-10-14)
------------------

* Added: childExists method to Sabre_DAV_ICollection. This is an api break, so
  if you implement Sabre_DAV_ICollection directly, add the method.
* Changed: Almost all HTTP method implementations now take a uri argument,
  including events. This allows for internal rerouting of certain calls. If you
  have custom plugins, make sure they use this argument. If they don't, they
  will likely still work, but it might get in the way of future changes.
* Changed: All getETag methods MUST now surround the etag with double-quotes.
  This was a mistake made in all previous SabreDAV versions. If you don't do
  this, any If-Match, If-None-Match and If: headers using Etags will work
  incorrectly. (Issue 85).
* Added: Sabre_DAV_Auth_Backend_AbstractBasic class, which can be used to easily
  implement basic authentication.
* Removed: Sabre_DAV_PermissionDenied class. Use Sabre_DAV_Forbidden instead.
* Removed: Sabre_DAV_IDirectory interface, use Sabre_DAV_ICollection instead.
* Added: Browser plugin now uses {DAV:}displayname if this property is
  available.
* Added: Cache layer in the ObjectTree.
* Added: Tree classes now have a delete and getChildren method.
* Fixed: If-Modified-Since and If-Unmodified-Since would be incorrect if the
  date is an exact match.
* Fixed: Support for multiple ETags in If-Match and If-None-Match headers.
* Fixed: Improved baseUrl handling.
* Fixed: Issue 67: Non-seekable stream support in ::put()/::get().
* Fixed: Issue 65: Invalid dates are now ignored.
* Updated: Refactoring in Sabre_CalDAV to make everything a bit more ledgable.
* Fixed: Issue 88, Issue 89: Fixed compatibility for running SabreDAV on
  Windows.
* Fixed: Issue 86: Fixed Content-Range top-boundary from 'file size' to 'file
  size'-1.


1.2.5 (2010-08-18)
------------------

* Fixed: Issue 73: guessBaseUrl fails for some servers.
* Fixed: Issue 67: SabreDAV works better with non-seekable streams.
* Fixed: If-Modified-Since and If-Unmodified-Since would be incorrect if
  the date is an exact match.


1.2.4 (2010-07-13)
------------------

* Fixed: Issue 62: Guessing baseUrl fails when url contains a query-string.
* Added: Apache configuration sample for CGI/FastCGI setups.
* Fixed: Issue 64: Only returning calendar-data when it was actually requested.


1.2.3 (2010-06-26)
------------------

* Fixed: Issue 57: Supporting quotes around etags in If-Match and If-None-Match


1.2.2 (2010-06-21)
------------------

* Updated: SabreDAV now attempts to guess the BaseURI if it's not set.
* Updated: Better compatibility with BitKinex
* Fixed: Issue 56: Incorrect behaviour for If-None-Match headers and GET
  requests.
* Fixed: Issue with certain encoded paths in Browser Plugin.


1.2.1 (2010-06-07)
------------------

* Fixed: Issue 50, patch by Mattijs Hoitink.
* Fixed: Issue 51, Adding windows 7 lockfiles to TemporaryFileFilter.
* Fixed: Issue 38, Allowing custom filters to be added to TemporaryFileFilter.
* Fixed: Issue 53, ETags in the If: header were always failing. This behaviour
  is now corrected.
* Added: Apache Authentication backend, in case authentication through .htaccess
  is desired.
* Updated: Small improvements to example files.


1.2.0 (2010-05-24)
------------------

* Fixed: Browser plugin now displays international characters.
* Changed: More properties in CalDAV classes are now protected instead of
  private.


1.2.0beta3 (2010-05-14)
-----------------------

* Fixed: Custom properties were not properly sent back for allprops requests.
* Fixed: Issue 49, incorrect parsing of PROPPATCH, affecting Office 2007.
* Changed: Removed CalDAV items from includes.php, and added a few missing ones.


1.2.0beta2 (2010-05-04)
-----------------------

* Fixed: Issue 46: Fatal error for some non-existent nodes.
* Updated: some example sql to include email address.
* Added: 208 and 508 statuscodes from RFC5842.
* Added: Apache2 configuration examples


1.2.0beta1 (2010-04-28)
-----------------------

* Fixed: redundant namespace declaration in resourcetypes.
* Fixed: 2 locking bugs triggered by litmus when no Sabre_DAV_ILockable
  interface is used.
* Changed: using http://sabredav.org/ns for all custom xml properties.
* Added: email address property to principals.
* Updated: CalendarObject validation.


1.2.0alpha4 (2010-04-24)
------------------------

* Added: Support for If-Range, If-Match, If-None-Match, If-Modified-Since,
  If-Unmodified-Since.
* Changed: Brand new build system. Functionality is split up between Sabre,
  Sabre_HTTP, Sabre_DAV and Sabre_CalDAV packages. In addition to that a new
  non-pear package will be created with all this functionality combined.
* Changed: Autoloader moved to Sabre/autoload.php.
* Changed: The Allow: header is now more accurate, with appropriate HTTP methods
  per uri.
* Changed: Now throwing back Sabre_DAV_Exception_MethodNotAllowed on a few
  places where Sabre_DAV_Exception_NotImplemented was used.


1.2.0alpha3 (2010-04-20)
------------------------

* Update: Complete rewrite of property updating. Now easier to use and atomic.
* Fixed: Issue 16, automatically adding trailing / to baseUri.
* Added: text/plain is used for .txt files in GuessContentType plugin.
* Added: support for principal-property-search and principal-search-property-set
  reports.
* Added: Issue 31: Hiding exception information by default. Can be turned on
  with the Sabre_DAV_Server::$debugExceptions property.


1.2.0alpha2 (2010-04-08)
------------------------

* Added: Calendars are now private and can only be read by the owner.
* Fixed: double namespace declaration in multistatus responses.
* Added: MySQL database dumps. MySQL is now also supported next to SQLite.
* Added: expand-properties REPORT from RFC 3253.
* Added: Sabre_DAV_Property_IHref interface for properties exposing urls.
* Added: Issue 25: Throwing error on broken Finder behaviour.
* Changed: Authentication backend is now aware of current user.


1.2.0alpha1 (2010-03-31)
------------------------

* Fixed: Issue 26: Workaround for broken GVFS behaviour with encoded special
  characters.
* Fixed: Issue 34: Incorrect Lock-Token response header for LOCK. Fixes Office
  2010 compatibility.
* Added: Issue 35: SabreDAV version to header to OPTIONS response to ease
  debugging.
* Fixed: Issue 36: Incorrect variable name, throwing error in some requests.
* Fixed: Issue 37: Incorrect smultron regex in temporary filefilter.
* Fixed: Issue 33: Converting ISO-8859-1 characters to UTF-8.
* Fixed: Issue 39 & Issue 40: Basename fails on non-utf-8 locales.
* Added: More unittests.
* Added: SabreDAV version to all error responses.
* Added: URLUtil class for decoding urls.
* Changed: Now using pear.sabredav.org pear channel.
* Changed: Sabre_DAV_Server::getCopyAndMoveInfo is now a public method.


1.1.2-alpha (2010-03-18)
------------------------

* Added: RFC5397 - current-user-principal support.
* Fixed: Issue 27: encoding entities in property responses.
* Added: naturalselection script now allows the user to specify a 'minimum
  number of bytes' for deletion. This should reduce load due to less crawling
* Added: Full support for the calendar-query report.
* Added: More unittests.
* Added: Support for complex property deserialization through the static
  ::unserialize() method.
* Added: Support for modifying calendar-component-set
* Fixed: Issue 29: Added TIMEOUT_INFINITE constant


1.1.1-alpha (2010-03-11)
------------------------

* Added: RFC5689 - Extended MKCOL support.
* Fixed: Evolution support for CalDAV.
* Fixed: PDO-locks backend was pretty much completely broken. This is 100%
  unittested now.
* Added: support for ctags.
* Fixed: Comma's between HTTP methods in 'Allow' method.
* Changed: default argument for Sabre_DAV_Locks_Backend_FS. This means a
  datadirectory must always be specified from now on.
* Changed: Moved Sabre_DAV_Server::parseProps to
  Sabre_DAV_XMLUtil::parseProperties.
* Changed: Sabre_DAV_IDirectory is now Sabre_DAV_ICollection.
* Changed: Sabre_DAV_Exception_PermissionDenied is now
  Sabre_DAV_Exception_Forbidden.
* Changed: Sabre_CalDAV_ICalendarCollection is removed.
* Added: Sabre_DAV_IExtendedCollection.
* Added: Many more unittests.
* Added: support for calendar-timezone property.


1.1.0-alpha (2010-03-01)
------------------------

* Note: This version is forked from version 1.0.5, so release dates may be out
  of order.
* Added: CalDAV - RFC 4791
* Removed: Sabre_PHP_Exception. PHP has a built-in ErrorException for this.
* Added: PDO authentication backend.
* Added: Example sql for auth, caldav, locks for sqlite.
* Added: Sabre_DAV_Browser_GuessContentType plugin
* Changed: Authentication plugin refactored, making it possible to implement
  non-digest authentication.
* Fixed: Better error display in browser plugin.
* Added: Support for {DAV:}supported-report-set
* Added: XML utility class with helper functions for the WebDAV protocol.
* Added: Tons of unittests
* Added: PrincipalCollection and Principal classes
* Added: Sabre_DAV_Server::getProperties for easy property retrieval
* Changed: {DAV:}resourceType defaults to 0
* Changed: Any non-null resourceType now gets a / appended to the href value.
  Before this was just for {DAV:}collection's, but this is now also the case for
  for example {DAV:}principal.
* Changed: The Href property class can now optionally create non-relative uri's.
* Changed: Sabre_HTTP_Response now returns false if headers are already sent and
  header-methods are called.
* Fixed: Issue 19: HEAD requests on Collections
* Fixed: Issue 21: Typo in Sabre_DAV_Property_Response
* Fixed: Issue 18: Doesn't work with Evolution Contacts


1.0.15 (2010-05-28)
-------------------

* Added: Issue 31: Hiding exception information by default. Can be turned on
  with the Sabre_DAV_Server::$debugExceptions property.
* Added: Moved autoload from lib/ to lib/Sabre/autoload.php. This is also the
  case in the upcoming 1.2.0, so it will improve future compatibility.


1.0.14 (2010-04-15)
-------------------

* Fixed: double namespace declaration in multistatus responses.


1.0.13 (2010-03-30)
-------------------

* Fixed: Issue 40: Last references to basename/dirname


1.0.12 (2010-03-30)
-------------------

* Fixed: Issue 37: Incorrect smultron regex in temporary filefilter.
* Fixed: Issue 26: Workaround for broken GVFS behaviour with encoded special
  characters.
* Fixed: Issue 33: Converting ISO-8859-1 characters to UTF-8.
* Fixed: Issue 39: Basename fails on non-utf-8 locales.
* Added: More unittests.
* Added: SabreDAV version to all error responses.
* Added: URLUtil class for decoding urls.
* Updated: Now using pear.sabredav.org pear channel.


1.0.11 (2010-03-23)
-------------------

* Non-public release. This release is identical to 1.0.10, but it is used to
  test releasing packages to pear.sabredav.org.


1.0.10 (2010-03-22)
-------------------

* Fixed: Issue 34: Invalid Lock-Token header response.
* Added: Issue 35: Addign SabreDAV version to HTTP OPTIONS responses.


1.0.9 (2010-03-19)
------------------

* Fixed: Issue 27: Entities not being encoded in PROPFIND responses.
* Fixed: Issue 29: Added missing TIMEOUT_INFINITE constant.


1.0.8 (2010-03-03)
------------------

* Fixed: Issue 21: typos causing errors
* Fixed: Issue 23: Comma's between methods in Allow header.
* Added: Sabre_DAV_ICollection interface, to aid in future compatibility.
* Added: Sabre_DAV_Exception_Forbidden exception. This will replace
  Sabre_DAV_Exception_PermissionDenied in the future, and can already be used to
  ensure future compatibility.


1.0.7 (2010-02-24)
------------------

* Fixed: Issue 19 regression for MS Office


1.0.6 (2010-02-23)
------------------

* Fixed: Issue 19: HEAD requests on Collections


1.0.5 (2010-01-22)
------------------

* Fixed: Fatal error when a malformed url was used for unlocking, in conjuction
  with Sabre.autoload.php due to a incorrect filename.
* Fixed: Improved unittests and build system


1.0.4 (2010-01-11)
------------------

* Fixed: needed 2 different releases. One for googlecode and one for pearfarm.
  This is to retain the old method to install SabreDAV until pearfarm becomes
  the standard installation method.


1.0.3 (2010-01-11)
------------------

* Added: RFC4709 support (davmount)
* Added: 6 unittests
* Added: naturalselection. A tool to keep cache directories below a specified
  theshold.
* Changed: Now using pearfarm.org channel server.


1.0.1 (2009-12-22)
------------------

* Fixed: Issue 15: typos in examples
* Fixed: Minor pear installation issues


1.0.0 (2009-11-02)
------------------

* Added: SimpleDirectory class. This class allows creating static directory
  structures with ease.
* Changed: Custom complex properties and exceptions now get an instance of
  Sabre_DAV_Server as their first argument in serialize()
* Changed: Href complex property now prepends server's baseUri
* Changed: delete before an overwriting copy/move is now handles by server class
  instead of tree classes
* Changed: events must now explicitly return false to stop execution. Before,
  execution would be stopped by anything loosely evaluating to false.
* Changed: the getPropertiesForPath method now takes a different set of
  arguments, and returns a different response. This allows plugin developers to
  return statuses for properties other than 200 and 404. The hrefs are now also
  always calculated relative to the baseUri, and not the uri of the request.
* Changed: generatePropFindResponse is renamed to generateMultiStatus, and now
  takes a list of properties similar to the response of getPropertiesForPath.
  This was also needed to improve flexibility for plugin development.
* Changed: Auth plugins are no longer included. They were not yet stable
  quality, so they will probably be reintroduced in a later version.
* Changed: PROPPATCH also used generateMultiStatus now.
* Removed: unknownProperties event. This is replaced by the afterGetProperties
  event, which should provide more flexibility.
* Fixed: Only calling getSize() on IFile instances in httpHead()
* Added: beforeBind event. This is invoked upon file or directory creation
* Added: beforeWriteContent event, this is invoked by PUT and LOCK on an
  existing resource.
* Added: beforeUnbind event. This is invoked right before deletion of any
  resource.
* Added: afterGetProperties event. This event can be used to make modifications
  to property responses.
* Added: beforeLock and beforeUnlock events.
* Added: afterBind event.
* Fixed: Copy and Move could fail in the root directory. This is now fixed.
* Added: Plugins can now be retrieved by their classname. This is useful for
  inter-plugin communication.
* Added: The Auth backend can now return usernames and user-id's.
* Added: The Auth backend got a getUsers method
* Added: Sabre_DAV_FSExt_Directory now returns quota info


0.12.1-beta (2009-09-11)
------------------------

* Fixed: UNLOCK bug. Unlock didn't work at all


0.12-beta (2009-09-10)
----------------------

* Updated: Browser plugin now shows multiple {DAV:}resourcetype values if
  available.
* Added: Experimental PDO backend for Locks Manager
* Fixed: Sending Content-Length: 0 for every empty response. This improves NGinx
  compatibility.
* Fixed: Last modification time is reported in UTC timezone. This improves
  Finder compatibility.


0.11-beta (2009-08-11)
----------------------

* Updated: Now in Beta
* Updated: Pear package no longer includes docs/ directory. These just contained
  rfc's, which are publically available. This reduces the package from ~800k to
  ~60k
* Added: generatePropfindResponse now takes a baseUri argument
* Added: ResourceType property can now contain multiple resourcetypes.
* Fixed: Issue 13.


0.10-alpha (2009-08-03)
-----------------------

* Added: Plugin to automatically map GET requests to non-files to PROPFIND
  (Sabre_DAV_Browser_MapGetToPropFind). This should allow easier debugging of
  complicated WebDAV setups.
* Added: Sabre_DAV_Property_Href class. For future use.
* Added: Ability to choose to use auth-int, auth or both for HTTP Digest
  authentication. (Issue 11)
* Changed: Made more methods in Sabre_DAV_Server public.
* Fixed: TemporaryFileFilter plugin now intercepts HTTP LOCK requests to
  non-existent files. (Issue 12)
* Added: Central list of defined xml namespace prefixes. This can reduce
  Bandwidth and legibility for xml bodies with user-defined namespaces.
* Added: now a PEAR-compatible package again, thanks to Michael Gauthier
* Changed: moved default copy and move logic from ObjectTree to Tree class

0.9a-alpha (2009-07-21)
----------------------

* Fixed: Broken release

0.9-alpha (2009-07-21)
----------------------

* Changed: Major refactoring, removed most of the logic from the Tree objects.
  The Server class now directly works with the INode, IFile and IDirectory
  objects. If you created your own Tree objects, this will most likely break in
  this release.
* Changed: Moved all the Locking logic from the Tree and Server classes into a
  separate plugin.
* Changed: TemporaryFileFilter is now a plugin.
* Added: Comes with an autoloader script. This can be used instead of the
  includer script, and is preferred by some people.
* Added: AWS Authentication class.
* Added: simpleserversetup.py script. This will quickly get a fileserver up and
  running.
* Added: When subscribing to events, it is now possible to supply a priority.
  This is for example needed to ensure that the Authentication Plugin is used
  before any other Plugin.
* Added: 22 new tests.
* Added: Users-manager plugin for .htdigest files. Experimental and subject to
  change.
* Added: RFC 2324 HTTP 418 status code
* Fixed: Exclusive locks could in some cases be picked up as shared locks
* Fixed: Digest auth for non-apache servers had a bug (still not actually tested
  this well).


0.8-alpha (2009-05-30)
----------------------

* Changed: Renamed all exceptions! This is a compatibility break. Every
  Exception now follows Sabre_DAV_Exception_FileNotFound convention instead of
  Sabre_DAV_FileNotFoundException.
* Added: Browser plugin now allows uploading and creating directories straight
  from the browser.
* Added: 12 more unittests
* Fixed: Locking bug, which became prevalent on Windows Vista.
* Fixed: Netdrive support
* Fixed: TemporaryFileFilter filtered out too many files. Fixed some of the
  regexes.
* Fixed: Added README and ChangeLog to package


0.7-alpha (2009-03-29)
----------------------

* Added: System to return complex properties from PROPFIND.
* Added: support for {DAV:}supportedlock.
* Added: support for {DAV:}lockdiscovery.
* Added: 6 new tests.
* Added: New plugin system.
* Added: Simple HTML directory plugin, for browser access.
* Added: Server class now sends back standard pre-condition error xml bodies.
  This was new since RFC4918.
* Added: Sabre_DAV_Tree_Aggregrate, which can 'host' multiple Tree objects into
  one.
* Added: simple basis for HTTP REPORT method. This method is not used yet, but
  can be used by plugins to add reports.
* Changed: ->getSize is only called for files, no longer for collections. r303
* Changed: Sabre_DAV_FilterTree is now Sabre_DAV_Tree_Filter
* Changed: Sabre_DAV_TemporaryFileFilter is now called
  Sabre_DAV_Tree_TemporaryFileFilter.
* Changed: removed functions (get(/set)HTTPRequest(/Response)) from Server
  class, and using a public property instead.
* Fixed: bug related to parsing proppatch and propfind requests. Didn't show up
  in most clients, but it needed fixing regardless. (r255)
* Fixed: auth-int is now properly supported within HTTP Digest.
* Fixed: Using application/xml for a mimetype vs. text/xml as per RFC4918 sec
  8.2.
* Fixed: TemporaryFileFilter now lets through GET's if they actually exist on
  the backend. (r274)
* FIxed: Some methods didn't get passed through in the FilterTree (r283).
* Fixed: LockManager is now slightly more complex, Tree classes slightly less.
  (r287)


0.6-alpha (2009-02-16)
----------------------

* Added: Now uses streams for files, instead of strings. This means it won't
  require to hold entire files in memory, which can be an issue if you're
  dealing with big files. Note that this breaks compatibility for put() and
  createFile methods.
* Added: HTTP Digest Authentication helper class.
* Added: Support for HTTP Range header
* Added: Support for ETags within If: headers
* Added: The API can now return ETags and override the default Content-Type
* Added: starting with basic framework for unittesting, using PHPUnit.
* Added: 49 unittests.
* Added: Abstraction for the HTTP request.
* Updated: Using Clark Notation for tags in properties. This means tags are
  serialized as {namespace}tagName instead of namespace#tagName
* Fixed: HTTP_BasicAuth class now works as expected.
* Fixed: DAV_Server uses / for a default baseUrl.
* Fixed: Last modification date is no longer ignored in PROPFIND.
* Fixed: PROPFIND now sends back information about the requestUri even when
  "Depth: 1" is specified.


0.5-alpha (2009-01-14)
----------------------

* Added: Added a very simple example for implementing a mapping to PHP file
  streams. This should allow easy implementation of for example a WebDAV to FTP
  proxy.
* Added: HTTP Basic Authentication helper class.
* Added: Sabre_HTTP_Response class. This centralizes HTTP operations and will be
  a start towards the creating of a testing framework.
* Updated: Backwards compatibility break: all require_once() statements are
  removed from all the files. It is now recommended to use autoloading of
  classes, or just including lib/Sabre.includes.php. This fix was made to allow
  easier integration into applications not using this standard inclusion model.
* Updated: Better in-file documentation.
* Updated: Sabre_DAV_Tree can now work with Sabre_DAV_LockManager.
* Updated: Fixes a shared-lock bug.
* Updated: Removed ?> from the bottom of each php file.
* Updated: Split up some operations from Sabre_DAV_Server to
  Sabre_HTTP_Response.
* Fixed: examples are now actually included in the pear package.


0.4-alpha (2008-11-05)
----------------------

* Passes all litmus tests!
* Added: more examples
* Added: Custom property support
* Added: Shared lock support
* Added: Depth support to locks
* Added: Locking on unmapped urls (non-existent nodes)
* Fixed: Advertising as WebDAV class 3 support


0.3-alpha (2008-06-29)
----------------------

* Fully working in MS Windows clients.
* Added: temporary file filter: support for smultron files.
* Added: Phing build scripts
* Added: PEAR package
* Fixed: MOVE bug identified using finder.
* Fixed: Using gzuncompress instead of gzdecode in the temporary file filter.
  This seems more common.


0.2-alpha (2008-05-27)
----------------------

* Somewhat working in Windows clients
* Added: Working PROPPATCH method (doesn't support custom properties yet)
* Added: Temporary filename handling system
* Added: Sabre_DAV_IQuota to return quota information
* Added: PROPFIND now reads the request body and only supplies the requested
  properties


0.1-alpha (2008-04-04)
----------------------

* First release!
* Passes litmus: basic, http and copymove test.
* Fully working in Finder and DavFSv2 Project started: 2007-12-13


[vobj]: http://sabre.io/vobject/
[evnt]: http://sabre.io/event/
[http]: http://sabre.io/http/
[uri]: http://sabre.io/uri/
[xml]: http://sabre.io/xml/
[mi20]: http://sabre.io/dav/upgrade/1.8-to-2.0/
[rfc6638]: http://tools.ietf.org/html/rfc6638 "CalDAV Scheduling"
[rfc7240]: http://tools.ietf.org/html/rfc7240
