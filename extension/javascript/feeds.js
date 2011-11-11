/**
 * Facebook share URL.
 */
var FB_SHARE_URL = "http://www.facebook.com/sharer.php?u=";

/**
 * Twitter share URL
 */
var TWITTER_SHARE_URL = "http://www.twitter.com/share?&url=";

/**
 * Opens new window either of facebook or twitter.
 * @param {String} id Specified whether to share news on Facebook or Twitter
 * @param {String} url Contains URL of the News to be shared.
 */
function openNewShareWindow(id, url) {
  var newsUrl = url.substring(url.indexOf('&url=') + 5);
  var openUrl;
  switch (id) {
    case 'fb':
      openUrl = FB_SHARE_URL;
      break;
    case 'twitter':
      openUrl = TWITTER_SHARE_URL;
      break;
  }
  window.open(openUrl + newsUrl, '_blank', 'resizable=0, scrollbars=0, width=690, height=415');
}

/**
 * Fetch the feed from our website
 */
function fetch_feed() {
  chrome.extension.sendRequest({'action': 'fetch_feed', 'url': 'https://www.dracoli.com/commercefeeds/all.json'},
    function(response) {
      display_feeds(feeds);
    }
  );
}

function display_feeds(feeds) {
	for (var feed in feeds) {
		var html = '<div class="feed">\
									<a href="' + feed.url + '">' + feed.title + '</a>\
							  </div>';
		$('#content #feeds').append(html);
	}
}