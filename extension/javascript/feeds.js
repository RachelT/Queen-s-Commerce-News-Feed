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

function openNewContentWindowWithContent(content) {
	window.open('<div><h2>I love cheese</h2></div>', '', 'toolbar=no, width=300, height=400');
}

/**
 * Fetch the feed from our website
 */
function fetch_feed() {
  chrome.extension.sendRequest({'action': 'fetch_feed', 'url': 'http://www.dracoli.com/commercefeeds/server/cache/feeds.json'},
    function(response) {
      display_feeds(response);
    }
  );
}

function display_feeds(feeds) {
	feeds = $.parseJSON(feeds);
	for (var oneKey in feeds) {
		// Get our content for this category
		var catFeeds = feeds[oneKey],
				sectionHTML = '';
		
		// Create new section - even if empty
		sectionHTML = '<section>\
										<div class="sectionHeader"> \
											<h4>' + oneKey + '<small class="stats"> \
											' + catFeeds.length + ' Feeds</small></h4> \
										</div> \
										<ul class="sectionContent unstyled"> \
										</ul> \
									</section>';
		$('#content #feeds').append(sectionHTML);
		
		// Add feeds in the section
		for (var i = 0; i < catFeeds.length; i++) {
			var feed = catFeeds[i],
					html = '<li class="feed"> \
										<a href="' + feed.url + '">' + feed.title + '</a> \
								  </li>';
			$('section:last .sectionContent').append(html);
		}
	}
}