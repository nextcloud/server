### v1.5.0

add `headerTpl` settings

* 7a41d93 - #375 from vcekov/fix_scroll_position - Valentin Cekov
* ecbf34f - #373 from vcekov/val/fix_key_navigation_interefence_with_mouse - Valentin Cekov
* b68cf84 - #364 from WorktileTech/master - Harold.Luo
* f836f04 - #372 from vcekov/fix_caret_for_space_after_@ - Harold.Luo
* 06cf6bb - Properly set caret position after failed match - Valentin Cekov
* c9ed2e2 - support header template. - htz

### v1.4.0

#### Contenteditable

Pressing `Backspace` will turn the inserted element back to the origin query 'moment'.

* 84edc9f - skip inserted element when moving left or right - ichord
* 25a61d3 - the jQuery npm package is now called jquery. Fixes #338 - Mick Staugaard
* 03ed71f - Merge pull request #351 from mociepka/master - Harold.Luo
* ae00dc3 - Point main script in package json - Michał Ociepka
* c5f31f5 - Merge branch 'dev' into HEAD - ichord
* c399397 - fix contenteditable cursor bug when typing "a" into query - ichord
* 7f4295a - fix previous replacements get clobbered when re-intering the inserted element - ichord
* f00fabd - Merge pull request #354 from lvegerano/master - Harold.Luo
* a42065e - Adds guard to event and dist file - Luis Vegerano
* e4aaa30 - Add option to disable loopUp on click - Luis Vegerano
* c9b7609 - Fix bug where callbacks would run before reaching minLen. Fixes #329. - Mike Leone
* f8692dc - Add support for minLen.  Connects to issue #316. - Mike Leone
* fd7d298 - FIX: the value of `isSelecting` - ichord
* c374c93 - FIX: IME typing error - ichord

### v1.3.0

* 7f2189d - fix #294 inserts "" suffix in contenteditable
* bae95d9 - add `tabSelectsMatch` setting to make tab selection optional 
* e966aba - Merge pull request #298 from kkirsche/patch-1 - Harold.Luo
* 9f78239 - Remove moot `version` property from bower.json - Kevin Kirsche

### v1.2.0

db09ac7 -> 886613f

* 886613f - add `$.fn.atwho.debug = false` to trigger debug mode
* 6567af9 - Enable default events when nothing is highlighted - Teemu
* 752ad4a - Add scrollDuration option. - Takuru
* bf17d43 - add parameter to allow for a spacebar in the middle of a search so that you can match a first + last name, for example - Feather Knee
* a1d5fe7 - add `reposition` API - ichord
* 9bcb06e - add "onInsert", "onDispaly" arguments to `tplEval` - ichord
* db09ac7 - add `hide` api - ichord

### v1.1.0

* lisafeather/displyTplCallBack - #259
* ADD: `editableAtwhoQueryAttrs` options
*  Added setting for 'spaceSelectsMatch' (default false/off)

### v1.0.0

**The naming convention are using camel case**.  
It means that every callback and setting's name are switched from underscope_naming to CamelNaming.
Sorry about this.

Future version's naming will follow the rules of http://semver.org constantly.

#### Options:

* Replaced `tpl` with `displayTpl`: display template of dropdown menu items.
  In previous versions, At.js will fetch the value of `data-value` to insert; It stops doing it.  
  Please use the `insertTpl` option to manage the content to insert instead.  
  The default value is `"<li>${name}</li>"`
* The `insertTpl` option will be used in *textarea* as well.
  The default value is `"${atwho-at}${name}"`

#### Callbacks:

* Added `afterMatchFailed` callback to *contentEditable*
  It will be invoked after fail to match any query and stopping matching.  
  Open *examples/hashtas.html* to examine how it work.  
* Removed `inserting_wrapper` callback to *contentEditable*

#### Internal changes:

* refactor the `Controller`
  Introduced `EditableController` class to control actions of `contenteditable` element.  
  Introduced `TextareaController` class to control actions of `textarea` element.  
  Both of them are inherit from the `Controller` class.

* Refactored contentEditable mode
  Inserted content are wrapped in a span: `<span class=".atwho-inserted"/>`  
  Querying content are wrapped in a span: `<span class=".atwho-query"/>`

* Bring back auto-discovery to iframe.
* Fix wrong offset in iframe
* Replaced `iframeStandalone` with `iframeAdRoot`
* All processed events are preventing default and stopping propagation. 

### v0.5.2

* e1f6566 - fix error that doesn't display mention list on new line
* 8fe3a54 - can insert multiple node from `inserting_wrapper`
* 4080151 - scroll to top after showing
* 01555f8 - scroll long dropdown list
* 1b8999d - Add spm support
* f2b8e9c - change name in package.json
* b61bfdc - search on click
* b1efd09 - Fixes error with selecting always first item on the list on iOS WebView when using https://github.com/ftlabs/fastclick
* 7ed2890 - Allow accented characters in matcher

### v0.5.1

* 219de3d - fix Goes off screen / gets cropped if there isn't enough room
* 1100c5b - No longer inherits text colour from document
* ce60958 - on more boolean argument for `setIframe` api to work cross-document issues #199

### v0.5.0

* 593893c - refactor inserting of contenteditable
  Adding `inserting_wrapper` for customize wrapping inserting content.
  Not to insert item as a block in Firefox. check out issue #109.
  Removing `getInsertedItems`, `getInsertedIDs` API. You have to collect them on your own.
* 4d3fb8f - have to set IFRAME manually
* 1f13a16 - change space_after to suffix
* b099ebb - fix caret position error after inserting
* 2c47d7a - fix #178 hide view while clicking somewhere else

### v0.4.12

* eeafab1 - fix error: will always call hidden atwho event
* b0f6ceb - Highlighter finds the first occurrence
* da256db - Adds possibility of having empty prefix (at keyword) in controllers
* b884225 - add `space_after` option
* 65d6273 - Passes esc/tab/return keyup events through to emitted hide event

### v0.4.11

* bf938db - add `delay` setting, support delay searching
* a0b5a6f - fix bug: terminate if query out of max_len
* 01d6d5b - add css min file

### v0.4.10

* update jquery dependence version

### v0.4.9

* f317bd7 not lowercase query, add `highlight_first` option

### v0.4.8

* 79bbef4  destroy atwho view container dom 
* 0372d65  update bower and component keywords 
* 52a41f5  add optional `before_repostion` callback 
* cc1c239  Fixes #143 - ichord

### v0.4.7

* resolved #133, #135, #137.
* add `beforeDestroy` event
* wouldn't concat `caret.js` into `dist/js/jquery.atwho.js` any more.
* seperate `jquery.atwho.coffee` into pieces.
* seperate testing.

### v0.4.6

* 2d9ab23 fix `wrong document` error in IE iframe

### v0.4.5

* 664a765 support iframe 

### v0.4.4

* 9ac7e75 - improve contentEditable for IE 8

	It's still some bugs in IE 8, just DON'T use it
    I don't want to spend more time on IE 8.
    So it would be the ending fixup. And i will still leave related code for
    a while maybe in case anyone want to help to improve it.
    Just encourge your users to upgrate the browers or just switch to a
    batter one please !!

* a8371b3 - move project page to master from gh-pages. 
* 24b6225 - fix bugs #122
* 645e030 - update Caret.js to v0.0.5

### v0.4.3

* e8e7561 update `Caret.js` to `v0.0.4`

### v0.4.2

* 4169b74 - binding data storage to the inputor. issues #121
* 11d053f - reduse querying twice. issues#112

### v0.4.1

* b7721be - fix bug at view id was not been assign. close issues #99
* 407f069 - fix bug: Can not autofocus after click the at-list in FireFox. #95
* 917f033 - fix bug: click do not work in div-contenteditable. close issues #93

### v0.4.0

* update `Caret.js` to `v0.0.2`
* `contenteditable` support !!
* change content of default item template `tpl`
* new rule to insert the `at` : will always remove the `at` from inputor but will add it back from `tpl` in default.
  so, if you are using your own `tpl` and want to show the `at` char, you have to do it yourself.
* add `insert_tpl` setting for `contenteditable`.
  it will insert `data-value` of li element that eval from `tpl` in default.
* new APIs for `contenteditable`: `getInsertedItemsWithIDs`, `getInsertedItems`, `getInsertedIDs`

### 2013-08-07 - v0.3.2

* bower
* remove `Caret.js` codes and add it as bower dependencies
* remove `display_flag` settings.
* add `start_with_space` settings, default `true`
* change `super_call` function to `call_default`

### 2013-04-28

* release new api `load`, `run`
* add `alias` setting for `load` data or as the view's id
* matching key with a space before it
* register key in settings `{at: "@", data: []}` instead of being a argument
* `max_len` setting for max length to search
* change the default matcher regrex rule: occur at start of line or after whitespace
* will not sort the datay without valid query string

### 2013-04-23

* group all data handlers as `Model` class.
* All callbacks's context would be current `Controller`

### 2013-04-05

* `data` setting will be used to load data either local or remote. If it's String as URL it will preload data from remote by launch a ajax request (every times At.js call `reg` to update settings)

* remove default `remote_filter` from callbacks list.
* add `get_data` and `save_data` function to contoller. They are used to get and save whole data for At.js
* `save_data` will invoke `data_refactor` everytime

* will filter local data which is set in `settings` first and if it get nothing then call `remote_filter` if it's exists in callbacks list that is set by user.

### 2013-04

* remove ability of changing common setting after inputor binded
* can fix list view after matched query in IE now.
* separated core function (get offset of inputor) as a jquery plugins.

### v0.2.0 - 2012-12

**No more testing in IEs browsers.**

#### Note
The name `atWho` was changed to `atwho`.

#### New features

* Customer data handlers(matcher, filter, sorter) and template renders(highlight, template eval) by a group of configurable callbacks.
* Support **AMD**

#### Removed features

* Filter by local data and remote (by ajax) data at the same time.
* Caching
* Mouse event

#### Changed settings

`-` mean removed option
`+` mean new added option
The one that start without `-` or `+` mean not change.

* `-` data: [],
* `+` data: null,

* `-` choose: "data-value",
* `+` search_key: "name",

* `-` callback: null,
* `+` callbacks: DEFAULT_CALLBACKS,

* `+` display_timeout: 300,

* `-` tpl: _DEFAULT_TPL
* `+` tpl: DEFAULT_TPL

* `-` cache: false

Not change settings

*     cache: true,
*     limit: 5,
*     display_flag: true,

### v0.1.7

同步 `jquery-atwho-rails` gem 的版本号
这会是 `v0.1` 的固定版本. 不再有新功能更新.

###v0.1.2 2012-3-23
* box showing above instead of bottom when it get close to the bottom of window
* coffeescript here is.
* every registered character able to have thire own options such as template(`tpl`)
* every inputor (textarea, input) able to have their own registered character and different behavior
  even the same character to other inputor

###v0.1.0
* 可以監聽多個字符
    multiple char listening.
* 顯示缺省列表.
    show default list.
