<?php

require_once('../libraries/simple_html_dom.php');
require_once('../helpers/GeneralUtils.php');
require_once('../helpers/RestUtils.php');
require_once('../helpers/URLConnect.php');

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
	 * @param $category An optional category for the parsed feeds
	 * @returns Array An array with the parsed data
	 */
	public static function parseRSSSource($url, $category)
	{
		$content = FeedParser::getContentFromUrl($url);
		$feeds = str_get_html($content)->find('item');
		$resultData = array();
		
		foreach ( $feeds as $feed ) {
			
			$oneResult = array();
			
			// Get feed date
			$dateString = $feed->find('pubDate', 0)->plaintext;
			$date = (int)strtotime($dateString);
			$oneResult['date'] = $date;
			
			// Get feed title
			$title = $feed->find('title', 0)->plaintext;
			$oneResult['title'] = $title;
		
			// Get feed status - new, default
			$oneResult['status'] = 'default';
			
			// Get feed url	
			$theUrl = $feed->find('guid', 0)->plaintext;
			$oneResult['url'] = $theUrl;
			
			// Get feed description
			$description = $feed->find('description', 0)->plaintext;
			$description = FeedParser::cleanUpDescription($description);
			$oneResult['description'] = $description;
			
			// We also associate a hash for each feed - used for identifying feeds
			// Note that this is not foolproof since its possible for two feeds to have same title (unlikely though)
			$oneResult['identifier'] = md5($title);
				
			$resultData[$category][] = $oneResult;
		}
		
		return $resultData;
	}
	
	private static function getContentFromUrl($url)
	{
		
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