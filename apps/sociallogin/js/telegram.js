document.addEventListener('DOMContentLoaded', function () {
  var tgData = document.getElementById('tg-data')
  if (!tgData) {
    return
  }
  var botId = tgData.dataset.botId
  var redirectUrl = tgData.dataset.redirectUrl
  var tgBtn = document.querySelector('#alternative-logins .telegram')
  if (tgBtn) {
    var tgWidget = document.createElement('script')
    tgWidget.src = 'https://telegram.org/js/telegram-widget.js?21'
    document.head.appendChild(tgWidget)
    tgBtn.onclick = function (e) {
      e.preventDefault()
      Telegram.Login.auth({bot_id: botId}, function (data) {
        if (data) {
          try {
            var redirectObj = new URL(redirectUrl)
            new URLSearchParams(data).forEach((v, k) => {
              redirectObj.searchParams.set(k, v)
            })
            location = redirectObj.href
          } catch (err) {
            location = redirectUrl + '?' + new URLSearchParams(data)
          }
          document.body.style.display = 'none'
        }
      })
    }
  }
})
