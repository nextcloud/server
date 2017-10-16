Api =
  # load a flag's data
  #
  # @params at[String] the flag
  # @params data [Array] data to storage.
  load: (at, data) -> c.model.load data if c = this.controller(at)
  isSelecting: () -> !!this.controller()?.view.visible()
  hide: () -> this.controller()?.view.hide()
  reposition: () ->
    if c = this.controller()
      c.view.reposition(c.rect())
  setIframe: (iframe, asRoot) -> this.setupRootElement(iframe, asRoot); null;
  run: -> this.dispatch()
  destroy: ->
    this.shutdown()
    @$inputor.data('atwho', null)

$.fn.atwho = (method) ->
  _args = arguments
  result = null
  this.filter('textarea, input, [contenteditable=""], [contenteditable=true]').each ->
    if not app = ($this = $ this).data "atwho"
      $this.data 'atwho', (app = new App this)
    if typeof method is 'object' || !method
      app.reg method.at, method
    else if Api[method] and app
      result = Api[method].apply app, Array::slice.call(_args, 1)
    else
      $.error "Method #{method} does not exist on jQuery.atwho"
  if result? then result else this

$.fn.atwho.default =
  at: undefined
  alias: undefined
  data: null
  displayTpl: "<li>${name}</li>"
  insertTpl: "${atwho-at}${name}"
  headerTpl: null
  callbacks: DEFAULT_CALLBACKS
  functionOverrides: {}
  searchKey: "name"
  suffix: undefined
  hideWithoutSuffix: no
  startWithSpace: yes
  acceptSpaceBar: false
  highlightFirst: yes
  limit: 5
  maxLen: 20
  minLen: 0
  displayTimeout: 300
  delay: null
  spaceSelectsMatch: no
  tabSelectsMatch: yes
  editableAtwhoQueryAttrs: {}
  scrollDuration: 150
  suspendOnComposing: true
  lookUpOnClick: true

$.fn.atwho.debug = false
