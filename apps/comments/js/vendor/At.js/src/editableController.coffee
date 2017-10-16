class EditableController extends Controller

  _getRange: ->
    sel = @app.window.getSelection()
    sel.getRangeAt(0) if sel.rangeCount > 0

  _setRange: (position, node, range=@_getRange()) ->
    return unless range and node
    node = $(node)[0]
    if position == 'after'
      range.setEndAfter node
      range.setStartAfter node
    else
      range.setEndBefore node
      range.setStartBefore node
    range.collapse false
    @_clearRange range

  _clearRange: (range=@_getRange()) ->
    sel = @app.window.getSelection()
    #ctrl+a remove defaults using the flag
    if !@ctrl_a_pressed?
      sel.removeAllRanges()
      sel.addRange range

  _movingEvent: (e) ->
    e.type == 'click' or e.which in [KEY_CODE.RIGHT, KEY_CODE.LEFT, KEY_CODE.UP, KEY_CODE.DOWN]

  _unwrap: (node) ->
    node = $(node).unwrap().get 0
    if (next = node.nextSibling) and next.nodeValue
      node.nodeValue += next.nodeValue
      $(next).remove()
    node

  catchQuery: (e) ->
    return unless range = @_getRange()
    return unless range.collapsed

    if e.which == KEY_CODE.ENTER
      ($query = $(range.startContainer).closest '.atwho-query')
        .contents().unwrap()
      $query.remove() if $query.is ':empty'
      ($query = $ ".atwho-query", @app.document)
        .text $query.text()
        .contents().last().unwrap()
      @_clearRange()
      return

    # absorb range
    # The range at the end of an element is not inside in firefox but not others browsers including IE.
    # To normolize them, we have to move the range inside the element while deleting content or moving caret right after .atwho-inserted
    if /firefox/i.test(navigator.userAgent)
      if $(range.startContainer).is @$inputor
        @_clearRange()
        return
      if e.which == KEY_CODE.BACKSPACE and range.startContainer.nodeType == document.ELEMENT_NODE \
          and (offset = range.startOffset - 1) >= 0
        _range = range.cloneRange()
        _range.setStart range.startContainer, offset
        if $(_range.cloneContents()).contents().last().is '.atwho-inserted'
          inserted = $(range.startContainer).contents().get(offset)
          @_setRange 'after', $(inserted).contents().last()
      else if e.which == KEY_CODE.LEFT and range.startContainer.nodeType == document.TEXT_NODE
        $inserted = $ range.startContainer.previousSibling
        if $inserted.is('.atwho-inserted') and range.startOffset == 0
          @_setRange 'after', $inserted.contents().last()

    # modifying inserted element
    $(range.startContainer)
      .closest '.atwho-inserted'
      .addClass 'atwho-query'
      .siblings().removeClass 'atwho-query'

    if ($query = $ ".atwho-query", @app.document).length > 0 \
        and $query.is(':empty') and $query.text().length == 0
      $query.remove()

    if not @_movingEvent e
      $query.removeClass 'atwho-inserted'

    if $query.length > 0
      switch e.which
        when KEY_CODE.LEFT
          @_setRange 'before', $query.get(0), range
          $query.removeClass 'atwho-query'
          return
        when KEY_CODE.RIGHT
          @_setRange 'after', $query.get(0).nextSibling, range
          $query.removeClass 'atwho-query'
          return

    # matching
    if $query.length > 0 and query_content = $query.attr('data-atwho-at-query')
      $query.empty().html(query_content).attr('data-atwho-at-query', null)
      @_setRange 'after', $query.get(0), range
    _range = range.cloneRange()
    _range.setStart range.startContainer, 0
    matched = @callbacks("matcher").call(this, @at, _range.toString(), @getOpt('startWithSpace'), @getOpt("acceptSpaceBar"))
    isString = typeof matched is 'string'

    # wrapping query with .atwho-query
    if $query.length == 0 and isString \
        and (index = range.startOffset - @at.length - matched.length) >= 0
      range.setStart range.startContainer, index
      $query = $ '<span/>', @app.document
        .attr @getOpt "editableAtwhoQueryAttrs"
        .addClass 'atwho-query'
      range.surroundContents $query.get 0
      lastNode = $query.contents().last().get(0)
      if lastNode
        if /firefox/i.test navigator.userAgent
          range.setStart lastNode, lastNode.length
          range.setEnd lastNode, lastNode.length
          @_clearRange range
        else
          @_setRange 'after', lastNode, range

    return if isString and matched.length < @getOpt('minLen', 0)

    # handle the matched result
    if isString and matched.length <= @getOpt('maxLen', 20)
      query = text: matched, el: $query
      @trigger "matched", [@at, query.text]
      @query = query
    else
      @view.hide()
      @query = el: $query
      if $query.text().indexOf(this.at) >= 0
        if @_movingEvent(e) and $query.hasClass 'atwho-inserted'
          $query.removeClass('atwho-query')
        else if false != @callbacks('afterMatchFailed').call this, @at, $query
          @_setRange "after", @_unwrap $query.text($query.text()).contents().first()
      null

  # Get offset of current at char(`flag`)
  #
  # @return [Hash] the offset which look likes this: {top: y, left: x, bottom: bottom}
  rect: ->
    rect = @query.el.offset()
    # do not use {top: 0, left: 0} from jQuery when element is hidden
    # happens every other time the menu is displayed on click in contenteditable
    return unless rect and @query.el[0].getClientRects().length
    if @app.iframe and not @app.iframeAsRoot
      iframeOffset = ($iframe = $ @app.iframe).offset()
      rect.left += iframeOffset.left - @$inputor.scrollLeft()
      rect.top += iframeOffset.top - @$inputor.scrollTop()
    rect.bottom = rect.top + @query.el.height()
    rect

  # Insert value of `data-value` attribute of chosen item into inputor
  #
  # @param content [String] string to insert
  insert: (content, $li) ->
    @$inputor.focus() unless @$inputor.is ':focus'
    overrides = @getOpt 'functionOverrides'
    if overrides.insert
      return overrides.insert.call this, content, $li
    suffix = if (suffix = @getOpt 'suffix') == "" then suffix else suffix or "\u00A0"
    data = $li.data('item-data')
    @query.el
      .removeClass 'atwho-query'
      .addClass 'atwho-inserted'
      .html content
      .attr 'data-atwho-at-query', "" + data['atwho-at'] + @query.text
      .attr 'contenteditable', "false"
    if range = @_getRange()
      if @query.el.length
        range.setEndAfter @query.el[0]
      range.collapse false
      range.insertNode suffixNode = @app.document.createTextNode "" + suffix
      @_setRange 'after', suffixNode, range
    @$inputor.focus() unless @$inputor.is ':focus'
    @$inputor.change()
