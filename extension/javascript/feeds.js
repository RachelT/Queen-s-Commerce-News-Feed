/**
 * Facebook share URL.
 */
var FB_SHARE_URL = "http://www.facebook.com/sharer.php?u=";

/**
 * Twitter share URL
 */
var TWITTER_SHARE_URL = "http://www.twitter.com/share?&url=";

/**
 * Utitlity prototype functions
 */
String.prototype.capitalize = function() {
    return this.charAt(0).toUpperCase() + this.slice(1);
}

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
	var sectionUtilsHTML = createSectionUtils(),
			$feedsSection = $('#content #feeds');
	
	feeds = $.parseJSON(feeds);
	for (var oneKey in feeds) {
		// Get our variables for this category
		var catFeeds = feeds[oneKey],
				maxFeeds = 10, //parseInt(window.localStorage.getItem(oneKey.replace(/\s/g, '').capitalize())),
				totalFeeds = catFeeds.length,
				sectionHTML = '<section>\
												<div class="sectionHeader noSelect"> \
													<h4>' + oneKey + '<small> \
													<span class="totalFeeds">' + totalFeeds + '</span> Feeds (max \
													<span class="maxFeeds">' + maxFeeds + '</span>)</small></h4> \
													' + sectionUtilsHTML + ' \
											 	</div>';
		
		// Only add our feed content section if we have content
		// This is to avoid the empty space between empty sections
		if (totalFeeds > 0) {
			sectionHTML += '<ul class="sectionContent unstyled"> \
										  </ul> \
											</section>';
		}
		
		// Cache the new section into a variable since we inserting feeds later
		// TODO: Test if we can move this to the beginning. Don't think we can do that
		// 			 right now since we are finding something we just appended
		var $newSection = $feedsSection.append(sectionHTML).find('.sectionContent:last');
		
		// Add feeds to the new section
		for (var i = 0; i < totalFeeds; i++) {
			var feed = catFeeds[i],
					html = '<li class="feed"> \
										<h5> \
											<a href="' + feed.url + '">' + feed.title + '</a> \
											<small class="time" data-timestamp="' + feed.date + '"> \
											' + facebookTime(feed.date) + '</small> \
										</h5> \
								  </li>';
			$newSection.append(html);
		}
	}

	// Since we have our feeds, lets initialize it
	window.feedsManager.initialize();
	window.feedsManager.collapseAllFeeds();
}

function createSectionUtils() {
	var html = '<span class="sectionUtils"> \
								<span class="feedControls"> \
									<img class="removeFeed" src="../images/minus_sign.png"/> \
									<img class="addFeed" src="../images/plus_sign.png"/> \
								</span> \
							</span>';
	return html;
}

// This method turns a timestamp into facebook like time. ex: 3hrs ago, 1 day ago
function facebookTime(timestamp) {
	var now = new Date(),
			feedTime = new Date(timestamp * 1000), // Since javascript's time is in miliseconds
			currentHour = now.getHours(),
			feedHour = feedTime.getHours(),
			currentMinutes = now.getMinutes(),
			feedMinutes = feedTime.getMinutes();
			
	// Figure out if this time is within 1 day
	if ( feedTime != now ) {
		// Output number of days since feed
		var dateDiff = getDateDifference(now, feedTime);
		return dateDiff + 'd ago';
	}
	
	// Figure out if this time is within hours
	if ( currentHour != feedHour ) {
		return (currentHour - feedHour) + 'hr ago';
	}
	
	// Figure out if this time is within mintues
	if ( currentMinutes != feedMinutes ) {
		return (currentMinutes - feedMinutes) + 'min ago';
	}
	
	// If days, hours, minutes all matches, then its now
	return 'now';
}

function getDateDifference(newDate, oldDate) {
	var diff = newDate - oldDate,
			daysDiff = diff / 1000 / 60 / 60 / 24;
	
	return parseInt(daysDiff); // Round down
}

window.feedsManager = {
	
	initialize: function() {
		this.$feedsSelection = $('#feeds section');
		this.$feedsContent = this.$feedsSelection.find('.sectionContent');
	},
	
	collapseAllFeeds: function() {
		this.$feedsContent.hide();
	}
}