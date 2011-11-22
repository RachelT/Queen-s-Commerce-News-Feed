<?php

require_once(dirname(__FILE__) . '/../libraries/simple_html_dom.php');
require_once(dirname(__FILE__) . '/../helpers/GeneralUtils.php');
require_once(dirname(__FILE__) . '/../helpers/NetworkUtils.php');
require_once(dirname(__FILE__) . '/../helpers/DatabaseManager.php');
/**
 * Provides functions to parse feeds from RSS sources
 *
 * @author Draco Li
 * @version 1.1
 */
class RSSParser {
	
	/**
	 * This is the method that does all the work!
	 * 
	 * @param $url The link of the rss source
	 * @param $category An optional category for the parsed feeds. If not provided, the rss feed's channel tag is used.
	 * @returns Array An array of the parsed data
	 */
	public static function parseRSSSource($url, $sourceID, $category)
	{
		$content = NetworkUtils::getContentFromUrl($url);
		$feeds = str_get_html($content)->find('item');
		$results = array();
		
		// Get feed category from rss if not supplied
		if ( $category == NULL ) {
			$category = str_get_html($content)->find('title', 0)->plaintext;
			
			// Remove the words rss in the title if any
			$category  = str_ireplace('rss', '', $category);
		}
		
		$dbm = new DatabaseManager();
		foreach ( $feeds as $feed ) {
			
			$oneResult = array();
			
			// Get feed date
			if ( $dateString = $feed->find('pubDate', 0) ) {
				$date = (int)strtotime($dateString->plaintext);
				$oneResult['pubDate'] = GeneralUtils::timeStampToMYSQLTime($date);
			}
			
			// Get feed title
			if ( $title = $feed->find('title', 0) ) {
				$oneResult['title'] = $dbm->sanitizeData($title->plaintext);
			}
			
			// Get feed url	- somehow link tag doesn't return anything... Dunno why
			if ( $theUrl = $feed->find('guid', 0) ) {
				$oneResult['link'] = $dbm->sanitizeData($theUrl->plaintext);
			}
			
			// Get feed description
			if ( $description = $feed->find('description', 0) ) {
				$description = RSSParser::cleanUpDescription($description->plaintext);
				$oneResult['description'] = $dbm->sanitizeData($description);
			}
			
			// Get feed author
			if ( $author = $feed->find('author', 0) ) {
				$oneResult['author'] = $dbm->sanitizeData($author->plaintext);
			}
			
			// Get feed category
			if ( strlen($category) > 0 ) {
				$oneResult['category'] = $category;
			}
			
			// Set feed sourceID
			$oneResult['sourceID'] = $sourceID;
				
			$results[] = $oneResult;
		}
		return $results;
	}

	private static function cleanUpDescription($description)
	{
		// Remove description CDATA
		$description = str_replace('<![CDATA[', '', $description);
		$description = str_replace(']]>', '', $description);
			
		// Remove read more paragrahs
		$description = preg_replace('/\<p\>\s*\<a[^(\\\>)]*\>Read more(.)*\<\/a\>\s*\<\/p\>/', '', $description);	
		
		// Remove all tags
		$description = strip_tags($description);
		
		// Trim the description
		$description = trim($description);
		
		return $description;
	}
}

?>