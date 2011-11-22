<?php

require_once(dirname(__FILE__) . '/../helpers/DatabaseManager.php');
require_once(dirname(__FILE__) . '/rssparser.class.php');
require_once(dirname(__FILE__) . '/commerceparser.class.php');

/**
 * The ParserManager is act as an interface to the other parsers.
 * This class has two main functions. Get feeds and update feeds
 *
 * @author Draco Li
 * @version 1.0
 */
class ParserManager {
	
	// We cached 20 most recent feeds for every source. 
	// If user needed more, we have to do a query!
	private static $MaxCachedFeeds = 30;
	
	private static $SourcesName = 'sources';
	
	/**
	 * A simple method to get cached feeds from targeted sources
	 * User can demand feeds from sources with a specified amount.
	 * If the user requires more than our cached amount, we do a query!
	 *
	 * @param $sources Something like ['DayOnbay' => 10, 'Commerce Portal' => 20]
	 * @param $options Includes the type of response we want. ex: 'json', 'array', 'object'
	 * @returns The specified result
	 */
	public static function getFeedsFromSources($sources, $options) {
			
			// Set some defaults
			$options['type'] = $options['type'] ? $options['type'] : 'json';
			
			// Results key is the source user specified (source title). The value is a feeds array
			$results = array();
			foreach ( $sources as $title=>$amount ) {
				$result = ParserManager::getCacheForSource($tile);
				
				// Check if user asked for more than we can afford
				if ( count($result) < $amount) {
					// If asked for more, we update cache with user's amount. Then we retreive cache again!
					$dbm = new DatabaseManager();
					$dbm->executeQuery("SELECT * FROM sources WHERE title='$title'");
					$sourceInfo = $dbm->getRow();
					if ( ParserManager::updateCacheForSource($sourceInfo, NULL, $amount) ) {
						$result = ParserManager::getCacheForSource($tile);
					}
				}
				
				$result = array_slice($result, 0, $amount);
				$results[$title] = $result;
			}
			
			// Change result to formate specified by user
			switch ($options['type']) {
				case 'json':
					$results = json_encode($results);
					break;
				case 'array':
					// Results is already in array (assoc)
					break;
				case 'object':
					$results = json_decode(json_encode($results));
					break;
			}
			
			return $results;
	}
	
	/**
	 * Get the most recent feed compared to a selected feed in a category directly from mysql database
	 * We get this data directly from database instead of the cache is because it is alot easier this way
	 * Benefit of getting data from cache is speed as the database will only be queries when cache cannot satisfy. 
	 * However, getting data from cache also forces me to re-update cache if its not enough... Maybe in future
	 * 
	 * @param $feedID The id of the feed before the one we will get
	 * @param $feedCategory The category of the feed
	 */
	public static function getOneMoreFeed($feedID, $feedCategory) {
		
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM feeds WHERE id>$feedID AND category='$feedCategory' LIMIT 0,1");
		$result = $dbm->getRow();
		
		return $result;
	}
	
	/**
	 * Returns all the available feed sources in the database
	 *
	 * @param $dbm Database reuse! No wasting is allowed!
	 * @returns Array the sources
	 */
	public static function updateAllSources($dbm) {
		$isNew = $dbm ? false : true;
		$dbm = ($dbm) ? $dbm : new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources");
		$results = $dbm->getAllRows();

		if ( !$results || count($results) <= 0 ) { return false; }
		
		$sourcesPath = $_SERVER["DOCUMENT_ROOT"] . '/cache/' . ParserManager::$SourcesName . '.json';
		file_put_contents($sourcesPath, $results);
		
		if ( $isNew ) { $dbm->closeConnection(); }
		
		return $results;
	}
	
	/**
	 * This methods update feeds from all of our sources in the database and caches results
	 *
	 * @return Array/Boolean Array if content is parsed. False if something went wrong!
	 */
	public static function updateAllFeeds() {
		
		// Get all our sources from database
		$dbm = new DatabaseManager();

		// Get and cache our sources
		$sourcesInfo = ParserManager::updateAllSources($dbm);

		// Check if results are returned
		if ( $sourcesInfo == NULL || count($sourcesInfo) == 0 ) { $dbm->closeConnection(); return false; }
		
		// Update every source
		$allFeeds = array();
		for ( $i = 0; $i < count($sourcesInfo); $i++ ) {
			
			// Parser source and save to database 
			$feedParsed = ParserManager::parseFeedsAndSaveToDatabase($sourcesInfo[$i], $dbm);
				
			// Update our source cache with recent feeds from database
			if ( $feedParsed && count($feedParsed) > 0 ) {
					ParserManager::updateCacheForSource($sourcesInfo[$i], $dbm);
			}
			
			$allFeeds[$sourcesInfo[$i]['title']] = $feedParsed;
		}
		
		$dbm->closeConnection();
		
		return $allFeeds;
	}
	
	/**
	 * Update feeds from a single source. The source is specified by title.
	 * The feeds are also stored to a JSON file
	 *
	 * @param $feedSource The name of the feed source
	 * @returns Boolean Returns false if update failed. Returns true if successful.
	 */
	public static function updateFeedsFromSource($sourceName) {
		
		// Query database for feedSource
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources WHERE title='" . $sourceName . "'");
		$sourceInfo = $dbm->getRow();
		
		if ( $sourceInfo == NULL ) { $dbm->closeConnection(); return false; }
		
		// Parser source and save to database 
		$feedParsed = ParserManager::parseFeedsAndSaveToDatabase($sourceInfo, $dbm);
		
		// If nothing is parsed due to some reason, we return immediately
		if ( !$feedParsed || count($feedParsed) <= 0 ) {
			$dbm->closeConnection();
			return false;
		}
		
		// Update our source cache with recent feeds from database if some feed is parsed
		if ( $feedParsed && count($feedParsed) > 0 ) {
			ParserManager::updateCacheForSource($sourceInfo, $dbm);
		}
		
		$dbm->closeConnection();
		
		return $feedParsed;
	}
	
	
	/**
	 * A utility function that parse contents from a source array then save info to database
	 *
	 * @param $sourceInfo The source array, containing evertying needs to parseContent
	 * @param $dbm A database object so we don't need to reconnect :)
	 * @returns Array/Null Parsed array if content is successfully parsed.
	 */
	private static function parseFeedsAndSaveToDatabase($sourceInfo, $dbm) {
		
		// Some variables
		$sourceID = $sourceInfo['id'];
		$sourceLink = $sourceInfo['link'];
		$sourceParser = $sourceInfo['parser'];
		$isNewDBM = !$dbm;
		$dbm = $dbm ? $dbm : new DatabaseManager();
		$category = $sourceInfo['title'];	// Category defaults to source title
		
		// Parse content from feed source
		$result = NULL;
		switch ( $sourceParser ) {
			case 'RSS':
				$result = RSSParser::parseRSSSource($sourceLink, $sourceID, $category);
				break;
			case 'CommercePortal':
				$result = CommerceParser::parsePortalContent($sourceLink, $sourceID);
				break;
			default:
				// Defaults to rss
				$result = RSSParser::parseRSSSource($sourceLink, $sourceID, $category);
		}
		
		// Return null if we parsed nothing
		if ( !$result || count($result) == 0 ) { 
			if ( $isNewDBM ) { $dbm->closeConnection; }
			return NULL; 
		}
		
		// Saved unique parsed content to database
		$dbm->executeQuery("SELECT title FROM feeds WHERE sourceID=" . $sourceID . " ORDER BY pubDate DESC LIMIT 0, " . count($result));
		$oldFeeds = $dbm->getAllRows(MYSQLI_NUM);
		
		// Adjust our old array to the right format
		$adjustedOldFeed = array();
		foreach ( $oldFeeds as $oneFeed ) {
			// We sanitize database data because we need to match it with our parsed data
			$adjustedOldFeed[] = $dbm->sanitizeData($oneFeed[0]);
		}
		
		/*
		echo "<pre>";
		print_r($adjustedOldFeed);
		echo "<hr>";
		print_r($result);
		echo "</pre>";
		*/
		for ( $i = 0; $i < count($result); $i++ ) {
			
			// Insert our assoc array into the database only if its not in the database
			// We determine duplicate by feed's title. This should work since we compare it with only recent feeds.
			if ( !in_array($result[$i]['title'], $adjustedOldFeed) ) {
				$dbm->insertRecords('feeds', $result[$i]);
				//echo "inserted!</br>";
			}else {
				//echo "duplicate!</br>";
			}
		}
		
		if ( $isNewDBM ) { $dbm->closeConnection; }
		return $result;
	}
	
	/**
	 * Get all cached feeds from a source.
	 *
	 * @returns Array the cached feeds in an assoc array
	 */
	private static function getCacheForSource($sourceName) {
		$jsonContent = file_get_contents(ParserManager::getCachePathForSource($sourceName));
		return json_decode($jsonContent, true);
	}
	
	/** 
   * Get the absolute path to the cache folder for the specified source.
	 * This class determines the file name of a cached source feeds
	 *
	 * @param $sourceName Name of the source
	 * @returns String the source's absolute path
	 */
	private static function getCachePathForSource($sourceName) {
		// Remove spaces from source. Ex: Commerce Feeds to CommerceFeeds
		$sourceName = str_replace(" ", "", $sourceName);
		return $_SERVER["DOCUMENT_ROOT"] . '/cache/' . $sourceName . '.json';
	}
	
	/**
	 * Get most recent feeds from database and cache the results
	 * This method should be called whenever the database is updated
	 *
	 * @param $sourceInfo Includes sourceID and source 
	 * @param $amount An optional amount on how many feeds to cache.
	 * @param $dbm Again, an optional DatabaseManager to encourage recycling
	 * @return Boolean True if content saved successfully
	 */
	private static function updateCacheForSource($sourceInfo, $dbm, $amount = NULL) {
		$isNewDB = $dbm ? false : true;
		$dbm = $dbm ? $dbm : new DatabaseManager();
		$amount = $amount ? $amount : ParserManager::$MaxCachedFeeds;
		
		$dbm->executeQuery("SELECT * FROM feeds WHERE sourceID=" . $sourceInfo['id'] .
											 " ORDER BY pubDate DESC LIMIT 0," . $amount);
		$updatedFeeds = $dbm->getAllRows();
		$cachePath = ParserManager::getCachePathForSource($sourceInfo['title']);
		if ( $isNewDB ) { $dbm->closeConnection(); }
		return ( file_put_contents($cachePath, $updatedFeeds) ) ? true : false;
	}
}

?>