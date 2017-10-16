class TextareaController extends Controller
  # Catch query string behind the at char
  #
  # @return [Hash] Info of the query. Look likes this: {'text': "hello", 'headPos': 0, 'endPos': 0}
  catchQuery: ->
    content = @$inputor.val()
    caretPos = @$inputor.caret('pos', {iframe: @app.iframe})
    subtext = content.slice(0, caretPos)
    query = this.callbacks("matcher").call(this, @at, subtext, this.getOpt('startWithSpace'), @getOpt("acceptSpaceBar"))
    isString = typeof query is 'string'

    return if isString and query.length < this.getOpt('minLen', 0)

    if isString and query.length <= this.getOpt('maxLen', 20)
      start = caretPos - query.length
      end = start + query.length
      @pos = start
      query = {'text': query, 'headPos': start, 'endPos': end}
      this.trigger "matched", [@at, query.text]
    else
      query = null
      @view.hide()

    @query = query

  # Get offset of current at char(`flag`)
  #
  # @return [Hash] the offset which look likes this: {top: y, left: x, bottom: bottom}
  rect: ->
    return if not c = @$inputor.caret('offset', @pos - 1, {iframe: @app.iframe})
    if @app.iframe and not @app.iframeAsRoot
      iframeOffset = $(@app.iframe).offset()
      c.left += iframeOffset.left
      c.top += iframeOffset.top
    scaleBottom = if @app.document.selection then 0 else 2
    {left: c.left, top: c.top, bottom: c.top + c.height + scaleBottom}

  # Insert value of `data-value` attribute of chosen item into inputor
  #
  # @param content [String] string to insert
  insert: (content, $li) ->
    $inputor = @$inputor
    source = $inputor.val()
    startStr = source.slice 0, Math.max(@query.headPos - @at.length, 0)
    suffix = if (suffix = @getOpt 'suffix') == "" then suffix else suffix or " "
    content += suffix
    text = "#{startStr}#{content}#{source.slice @query['endPos'] || 0}"
    $inputor.val text
    $inputor.caret('pos', startStr.length + content.length, {iframe: @app.iframe})
    $inputor.focus() unless $inputor.is ':focus'
    $inputor.change()
