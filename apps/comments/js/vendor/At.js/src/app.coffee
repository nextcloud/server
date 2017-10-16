# At.js central contoller(searching, matching, evaluating and rendering.)
class App

  # @param inputor [HTML DOM Object] `input` or `textarea`
  constructor: (inputor) ->
    @currentFlag = null
    @controllers = {}
    @aliasMaps = {}
    @$inputor = $(inputor)
    this.setupRootElement()
    this.listen()

  createContainer: (doc) ->
    @$el?.remove()
    $ doc.body
      .append @$el = $ "<div class='atwho-container'></div>"

  setupRootElement: (iframe, asRoot=false) ->
    if iframe
      @window = iframe.contentWindow
      @document = iframe.contentDocument || @window.document
      @iframe = iframe
    else
      @document = @$inputor[0].ownerDocument
      @window = @document.defaultView || @document.parentWindow
      try
        @iframe = @window.frameElement
      catch error
        @iframe = null
        if $.fn.atwho.debug
          throw new Error """
            iframe auto-discovery is failed.
            Please use `setIframe` to set the target iframe manually.
            #{error}
          """
    this.createContainer if @iframeAsRoot = asRoot then @document else document

  controller: (at) ->
    if @aliasMaps[at]
      current = @controllers[@aliasMaps[at]]
    else
      for currentFlag, c of @controllers
        if currentFlag is at
          current = c
          break

    if current then current else @controllers[@currentFlag]

  setContextFor: (at) ->
    @currentFlag = at
    this

  # At.js can register multiple at char (flag) to every inputor such as "@" and ":"
  # Along with their own `settings` so that it works differently.
  # After register, we still can update their `settings` such as updating `data`
  #
  # @param flag [String] at char (flag)
  # @param settings [Hash] the settings
  reg: (flag, setting) ->
    controller = @controllers[flag] ||=
      if @$inputor.is '[contentEditable]'
        new EditableController this, flag
      else
        new TextareaController this, flag
    # TODO: it will produce rubbish alias map, reduse this.
    @aliasMaps[setting.alias] = flag if setting.alias
    controller.init setting
    this

  # binding jQuery events of `inputor`'s
  listen: ->
    @$inputor
      .on 'compositionstart', (e) =>
        this.controller()?.view.hide()
        @isComposing = true
        null
      .on 'compositionend', (e) =>
        @isComposing = false
        setTimeout((e) => @dispatch(e))
        null
      .on 'keyup.atwhoInner', (e) =>
        this.onKeyup(e)
      .on 'keydown.atwhoInner', (e) =>
        this.onKeydown(e)
      .on 'blur.atwhoInner', (e) =>
        if c = this.controller()
          c.expectedQueryCBId = null
          c.view.hide(e,c.getOpt("displayTimeout"))
      .on 'click.atwhoInner', (e) =>
        this.dispatch e
      .on 'scroll.atwhoInner', do =>
        # make returned handler handle the very first call properly
        lastScrollTop = @$inputor.scrollTop()
        (e) =>
          currentScrollTop = e.target.scrollTop
          if lastScrollTop != currentScrollTop
            @controller()?.view.hide(e)
          lastScrollTop = currentScrollTop
          true  # ensure we don't stop bubbling

  shutdown: ->
    for _, c of @controllers
      c.destroy()
      delete @controllers[_]
    @$inputor.off '.atwhoInner'
    @$el.remove()

  dispatch: (e) ->
    c.lookUp(e) for _, c of @controllers

  onKeyup: (e) ->
    switch e.keyCode
      when KEY_CODE.ESC
        e.preventDefault()
        this.controller()?.view.hide()
      when KEY_CODE.DOWN, KEY_CODE.UP, KEY_CODE.CTRL, KEY_CODE.ENTER
        $.noop()
      when KEY_CODE.P, KEY_CODE.N
        this.dispatch e if not e.ctrlKey
      else
        this.dispatch e
    # coffeescript will return everywhere!!
    return

  onKeydown: (e) ->
    # return if not (view = this.controller().view).visible()
    view = this.controller()?.view
    return if not (view and view.visible())
    switch e.keyCode
      when KEY_CODE.ESC
        e.preventDefault()
        view.hide(e)
      when KEY_CODE.UP
        e.preventDefault()
        view.prev()
      when KEY_CODE.DOWN
        e.preventDefault()
        view.next()
      when KEY_CODE.P
        return if not e.ctrlKey
        e.preventDefault()
        view.prev()
      when KEY_CODE.N
        return if not e.ctrlKey
        e.preventDefault()
        view.next()
      when KEY_CODE.TAB, KEY_CODE.ENTER, KEY_CODE.SPACE
        return if not view.visible()
        return if not this.controller().getOpt('spaceSelectsMatch') and e.keyCode == KEY_CODE.SPACE
        return if not this.controller().getOpt('tabSelectsMatch') and e.keyCode == KEY_CODE.TAB
        if view.highlighted()
          e.preventDefault()
          view.choose(e)
        else
          view.hide(e)
      else
        $.noop()
    return
