/**
 * Facebook share URL.
 */
var FB_SHARE_URL = "http://www.facebook.com/sharer.php?u=";

/**
 * Twitter share URL
 */
var TWITTER_SHARE_URL = "http://www.twitter.com/share?&url=";

/**
 * This specifies when a feed is considered old
 */
var daysUntilOld = 8;

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
			$feedsSection = $('#content #feeds'),
			newFeeds = new Array();
	
	// Get feeds and convert them into real array
	feeds = $.parseJSON(feeds);
	for (var key in feeds) { newFeeds.push([key, feeds[key]]); }
	
	newFeeds.sort(function (a, b) {
		var keyA = a[0].replace(/\s/g, '') + '-index',
				keyB = b[0].replace(/\s/g, '') + '-index',
				valueA = parseInt(window.localStorage.getItem(keyA)),
				valueB = parseInt(window.localStorage.getItem(keyB));
		return valueA - valueB;
	});
	for (var i = 0; i < newFeeds.length; i++) {
		// Get our variables for this category
		var oneKey = newFeeds[i][0],
				catFeeds = newFeeds[i][1],
				totalFeeds = catFeeds.length,
				newKey = oneKey.replace(/\s/g, ''),
				maxFeeds = parseInt(window.localStorage.getItem(newKey + '-max')),
				sectionHTML = '<section data-sectionKey='+ newKey +'>\
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
		for (var j = 0; j < Math.min(maxFeeds, totalFeeds); j++) {
			var feed = catFeeds[j],
					newAndRead = (window.storageManager.getFeedState(feed.identifier) != 'read' && 
												isNewFeed(feed.date)) ? 'new' : '',
					html = '<li class="feed ' + newAndRead + '" data-identifier="' + feed.identifier + '"> \
										<h5> \
											<a href="' + feed.url + '">' + feed.title + '</a> \
											<small class="time" data-timestamp="' + feed.date + '"> \
											' + facebookTime(feed.date) + '</small> \
										</h5> \
								  </li>';
			console.log(feed.identifier + ': ' + newAndRead);
			$newSection.append(html);
		}
	}

	// Since we have our feeds, lets initialize it
	window.feedsManager.initialize();
	window.feedsManager.restoreSectionState();
}

function createSectionUtils() {
	var html = '<span class="sectionUtils"> \
								<span class="feedControls"> \
									<a class="removeFeed"></a> \
									<a class="addFeed"></a> \
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

function isNewFeed(timestamp) {
	// Return this is a new feed if its within 3 days
	var now = new Date(),
			feedDate = new Date(timestamp * 1000);
	if (getDateDifference(now, feedDate) <= daysUntilOld) {
		return true;
	}
	return false;
}

window.storageManager = {
	feedStatIdentifier: 'feedStates',
	
	setFeedState: function(identifier, state) {
		if (state != 'read') return;	// Assumes anything not read is unread
		
		var feedStats = window.localStorage.getItem(this.feedStatIdentifier);
		feedStats = feedStats ? JSON.parse(feedStats) : {}
		feedStats[identifier] = state;
		window.localStorage.setItem(this.feedStatIdentifier, JSON.stringify(feedStats))
	},
	getFeedState: function(identifier) {
		var feedStats = window.localStorage.getItem(this.feedStatIdentifier);
		if (feedStats) {
			feedStats = JSON.parse(feedStats);
			return feedStats[identifier] || 'unread';
		}
		return 'unread';
	}
}

window.feedsManager = {
	
	sectionStatusIdentifier: '-status',
	sectionMaxIdentifier: '-max',
	sectionIndexIdentifier: '-index',
	
	initialize: function() {
		this.$feedsSelection = $('#feeds section');
		this.$feedsContent = this.$feedsSelection.find('.sectionContent');
	},
	
	restoreSectionState: function() {
		var that = this;
		this.$feedsContent.each(function(index) {
			var sectionKey = $(this).parents('section').attr('data-sectionKey'),
					isOpen = that.getSectionState(sectionKey);
			if (isOpen) {
				$(this).show();
			}else {
				$(this).hide();
			}
		});
	},
	collapseAllFeeds: function() {
		var that = this;
		
		// Loop through all sections and hide them. Also update localStorage
		// Note: Each is a slower when elements are large. However, since we dont have
		// 		   much sections, it is okay!
		this.$feedsContent.each(function(index) {
			var $targetFeed = $(this);
			$targetFeed.hide();
			that.updateSectionState($targetFeed.parents('section').attr('data-sectionKey'));
		});
	},
	updateSectionState: function(theKey, status) {
		var newKey = theKey + this.sectionStatusIdentifier;
		window.localStorage.setItem(newKey, status);
	},
	getSectionState: function(theKey) {
		var newKey = theKey + this.sectionStatusIdentifier;
		return window.localStorage.getItem(newKey) == 'true';
	},
	updateSectionMax: function(theKey, newValue) {
		var newKey = theKey + this.sectionMaxIdentifier;
		window.localStorage.setItem(newKey, parseInt(newValue));
	},
	getSectionMax: function(theKey) {
		var newKey = theKey + this.sectionMaxIdentifier;
		return parseInt(window.localStorage.getItem(newKey));
	},
	removeLastFeed: function($sectionElement) {
		$sectionElement.find('.feed:last').remove();
	},
	appendOneFeed: function($sectionElement) {
		
		// TODO: Get one more feed either from cache or from server
		
		// Testing
		var $newFeed = $sectionElement.find('.feed:last').clone();
		$sectionElement.find('.sectionContent').append($newFeed);
	},
	updateSectionIndex: function(theKey, index) {
		var newKey = theKey + this.sectionIndexIdentifier;
		window.localStorage.setItem(newKey, parseInt(index));
	},
	getSectionIndex: function(theKey) {
		var newKey = theKey + this.sectionIndexIdentifier;
		return parseInt(window.localStorage.getItem(newKey));
	},
	update: function() {
		this.initialize();
	}
}