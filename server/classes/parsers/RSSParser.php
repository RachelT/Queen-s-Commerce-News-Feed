<?php

require_once('../libraries/simple_html_dom.php');
require_once('../helpers/GeneralUtils.php');
require_once('../helpers/NetworkUtils.php');
require_once('../helpers/RestUtils.php');

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
	public static function parseRSSSource($url, $category)
	{
		$content = NetworkUtils::getContentFromUrl($url);
		$feeds = str_get_html($content)->find('item');
		$results = array();
		
		// Get feed category if not supplied
		if ( $category == NULL ) {
			$category = str_get_html($content)->find('title', 0)->plaintext;
			
			// Remove the words rss in the title if any
			$category  = str_ireplace('rss', '', $category);
		}
		
		foreach ( $feeds as $feed ) {
			
			$oneResult = array();
			
			// Get feed date
			if ( $dateString = $feed->find('pubDate', 0) ) {
				$date = (int)strtotime($dateString->plaintext);
				$oneResult['pubDate'] = $date;
			}
			
			// Get feed title
			if ( $title = $feed->find('title', 0) ) {
				$oneResult['title'] = $title->plaintext;
			}
			
			// Get feed url	
			if ( $theUrl = $feed->find('link', 0) ) {
				$oneResult['link'] = $theUrl->plaintext;
			}
			
			// Get feed description
			if ( $description = $feed->find('description', 0) ) {
				$description = RSSParser::cleanUpDescription($description->plaintext);
				$oneResult['description'] = $description;
			}
			
			// Get feed author
			if ( $author = $feed->find('author', 0) ) {
				$oneResult['author'] = $author->plaintext;
			}
			
			// Get feed category
			if ( strlen($category) > 0 ) {
				$oneResult['category'] = $category;
			}
			
			$results[] = $oneResult;
		}
		
		return $resultData;
	}

	private static function cleanUpDescription($description)
	{
		// Remove description CDATA
		$description = str_replace('<![CDATA[', '', $description);
		$description = str_replace(']]>', '', $description);
			
		// Remove description images
		$description = preg_replace('/\<img[^(\>)]*\/\>/', '', $description);
			
		// Remove empty paragraph tags
		$description = preg_replace('/\<p\>\s*\<\/p\>/', '', $description);
		
		// Remove read more paragrahs
		$description = preg_replace('/\<p\>\s*\<a[^(\\\>)]*\>Read more(.)*\<\/a\>\s*\<\/p\>/', '', $description);	
		
		return $description;
	}
}

?>