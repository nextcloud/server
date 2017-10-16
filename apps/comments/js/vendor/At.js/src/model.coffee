# Class to process data
class Model

  constructor: (@context) ->
    @at = @context.at
    # NOTE: bind data storage to inputor maybe App class can handle it.
    @storage = @context.$inputor

  destroy: ->
    @storage.data(@at, null)

  saved: ->
    this.fetch() > 0

  # fetch data from storage by query.
  # will invoke `callback` to return data
  #
  # @param query [String] catched string for searching
  # @param callback [Function] for receiving data
  query: (query, callback) ->
    data = this.fetch()
    searchKey = @context.getOpt("searchKey")
    data = @context.callbacks('filter').call(@context, query, data, searchKey) || []
    _remoteFilter = @context.callbacks('remoteFilter')
    if data.length > 0 or (!_remoteFilter and data.length == 0)
      callback data
    else
      _remoteFilter.call(@context, query, callback)

  # get or set current data which would be shown on the list view.
  #
  # @param data [Array] set data
  # @return [Array|undefined] current data that are showing on the list view.
  fetch: ->
    @storage.data(@at) || []

  # save special flag's data to storage
  #
  # @param data [Array] data to save
  save: (data) ->
    @storage.data @at, @context.callbacks("beforeSave").call(@context, data || [])

  # load data. It wouldn't load for a second time if it has been loaded.
  #
  # @param data [Array] data to load
  load: (data) ->
    this._load(data) unless this.saved() or not data

  reload: (data) ->
    this._load(data)

  # load data from local or remote with callback
  #
  # @param data [Array|String] data to load.
  _load: (data) ->
    if typeof data is "string"
      $.ajax(data, dataType: "json").done (data) => this.save(data)
    else
      this.save data
