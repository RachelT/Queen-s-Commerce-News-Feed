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
					if ( ParserManager::updateCacheForSource($title, $amount) ) {
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
	 * This methods update feeds from all of our sources in the database and caches results
	 *
	 * @return Boolean True if everything is updated. False if something went wrong!
	 */
	public static function updateAllFeeds() {
		
		// Get all our sources from database
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources");
		$sourcesInfo = $dbm->getAllRows();

		// Check if results are returned
		if ( $sourcesInfo == NULL || count($sourcesInfo) == 0 ) { $dbm->closeConnection(); return false; }
		
		// Update every source
		for ( $i = 0; $i < count($sourcesInfo); $i++ ) {
			
			// Parser source and save to database 
			$parsedFeeds = ParserManager::parseFeedsAndSaveToDatabase($sourcesInfo[$i], $dbm);

			if ( $parsedFeeds && count($parsedFeeds) > 0 ) {
					// Update our source cache with recent feeds from database
					ParserManager::updateCacheForSource($sourceInfo, $dbm);
			}
		}
		
		$dbm->closeConnection();
		
		return true;
	}
	
	/**
	 * Update feeds from a single source. The source is specified by title.
	 * The feeds are also stored to a JSON file
	 *
	 * @param $feedSource The name of the feed source
	 * @returns Boolean/Array Returns false if update failed. Returns cached feeds if sucessful.
	 */
	public static function updateFeedsFromSource($sourceName) {
		
		// Query database for feedSource
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources WHERE title='" . $sourceName . "'");
		$sourceInfo = $dbm->getRow();
		
		if ( $sourceInfo == NULL ) { $dbm->closeConnection(); return false; }
		
		// Parser source and save to database 
		$parsedFeeds = ParserManager::parseFeedsAndSaveToDatabase($sourceInfo, $dbm);
		print_r($parsedFeeds);
		
		// If nothing is parsed due to some reason, we return immediately
		if ( $parsedFeeds && count($parsedFeeds) > 0 ) {
			$dbm->closeConnection();
			return NULL;
		}
		
		// Update our source cache with recent feeds from database
		$result = ParserManager::updateCacheForSource($sourceInfo, $dbm);
		
		$dbm->closeConnection();
		
		if ( !$result ) return false; // File not saved somehow?
		return $updatedFeeds;
	}
	
	
	/**
	 * A utility function that parse contents from a source array then save info to database
	 *
	 * @param $sourceInfo The source array, containing evertying needs to parseContent
	 * @param $dbm A database object so we don't need to reconnect :)
	 * @returns Array/Null Array if content is parsed. Null if nothing is parsed
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
			$adjustedOldFeed[] = $oneFeed[0];
		}
		
		for ( $i = 0; $i < count($result); $i++ ) {
			
			// Insert our assoc array into the database only if its not in the database
			// We determine duplicate by feed's title. This should work since we compare it with only recent feeds.
			if ( !in_array($result[$i]['title'], $adjustedOldFeed) ) {
				$dbm->insertRecords('feeds', $result[$i]);
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
	 */
	private static function updateCacheForSource($sourceInfo, $dbm) {
		$isNewDB = $dbm == NULL;
		$dbm = $dbm ? $dbm : new DatabaseManager();
		
		$dbm->executeQuery("SELECT * FROM feeds WHERE sourceID=" . $sourceInfo['id'] .
											 " ORDER BY pubDate DESC LIMIT 0," . ParserManager::$MaxCachedFeeds);
		$updatedFeeds = $dbm->getAllRows();
		$cachePath = ParserManager::getCachePathForSource($sourceInfo['title']);
		if ( $isNewDB ) { $dbm->closeConnection(); }
		return ( file_put_contents($cachePath, $updatedFeeds) ) ? true : false;
	}
}

?>