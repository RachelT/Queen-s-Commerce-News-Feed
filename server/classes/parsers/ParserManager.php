<?php

require_once('../helpers/DatabaseManager.php');

/**
 * The ParserManager is act as an interface to the other parsers.
 * This class will parse any kind of data source from the user by using the other parsing classes
 * ParserManager also handles interacting with the server to retreive parsing information
 *
 * @author Draco Li
 * @version 1.0
 */
class ParserManager {
	
	// We cached 20 most recent feeds for every source. 
	// If user needed more, we have to do a query!
	private static $MaxCachedFeeds = 30;
	
	private static $CacheFolder = '/cache/';
	
	/**
	 * A simple method to get cached feeds from targeted sources
	 * User can optionally set how many feeds to get per source
	 * If the user requires more than our cached amount, we do a query!
	 *
	 * @param $title The title of the feed source
	 * @param $type The type of response we want. ex: 'json', 'array', 'object'
	 * @return The specified result
	 */
	public static function getFeedsFromSources($sources, $options) {
			
	}
	
	/**
	 * This methods update all feeds from our sources in the database
	 * Combining feeds is handled by this method as well to make sure we always have most updated
	 * feeds in our database.
	 * Caching is also handled by this class. Each feed source is cached into a indidivual JSON file.
	 *
	 * @return Boolean True if everything is updated. False if something went wrong!
	 */
	public static function updateAllFeeds() {
		
		// Get all our sources from database
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources");
		$results = $dbm->getAllRows();
		
		for ( $i = 0; $i < count($results); $i++ ) {
			
			
			// Saved parsed feeds to database
			for ( $j = 0; $j < count($parsedFeeds); $j++ ) {
				
			}
			
			// Cache parsed feeds
		}
	}
	
	/**
	 * @param $feedSource The name of the feed source
	 * @returns True if successful
	 */
	public static function updateFeedsFromSource($sourceName) {
		
		// Query database for feedSource
		$dbm = new DatabaseManager();
		$dbm->executeQuery("SELECT * FROM sources WHERE title='" + $sourceName + "'");
		$sourceInfo = $dbm->getRow();
		
		if ( $sourceInfo == NULL ) return false;
		
		// Parser and save
		$parsedFeeds = ParserManager::parseFeedsAndSaveToDatabase($sourceInfo);
		
		// Cache updated feeds
		$cachePath = ParserManager::getCachePathForSource($sourceInfo['title']);
		$result = file_put_contents(json_encode($parsedFeeds));
		if ( !$result ) return false;
		
		return true;
	}
	
	
	/**
	 * A utility function that parse contents from a source array then save info to database
	 *
	 * @param $sourceInfo The source array, containing evertying needs to parseContent
	 */
	private static function parseFeedsAndSaveToDatabase($sourceInfo) {
		$sourceID = $sourceInfo['id'];
		$sourceTitle = $sourceInfo['title'];
		$sourceLink = $sourceInfo['link'];
		$sourceParser = $sourceInfo['parser'];
			
		// Parse content from feed source
		
		
		// Saved parsed content to database
		
		
		return $parsedFeeds;
	}
	
	/**
	 * Get all cached feeds from a source.
	 *
	 * @returns Array the cached feeds in an assoc array
	 */
	private static function getFeedsFromSource($source) {
		$jsonContent = file_get_contents(ParserManager::getCachePathForSource($source));
		return json_decode($jsonContent, true);
	}
	
	/** 
   * Get the absolute path to the cache folder for the specified source.
	 *
	 * @param $sourceName Name of the source
	 * @returns String the source's absolute path
	 */
	private static function getCachePathForSource($sourceName) {
		// Remove spaces from source. Ex: Commerce Feeds to CommerceFeeds
		$sourceName = str_replace(" ", "", $sourceName);
		return ParserManager::$CacheFolder . $sourceName . '.json';
	}
}

?>