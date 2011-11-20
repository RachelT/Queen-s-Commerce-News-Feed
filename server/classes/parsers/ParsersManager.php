<?php

/**
 * The ParserManager is act as an interface to the other parsers.
 * This class will parse any kind of data source from the user by using the other parsing classes
 * ParserManager also handles interacting with the server to retreive parsing information
 *
 * @author Draco Li
 * @version 1.0
 */
class ParsersManager {
	
	/**
	 * A simple method to get feeds from a source specified by the title.
	 * If multiple sources have same title. The last title in the database is used.
	 *
	 * @param $title The title of the feed source
	 * @param $type The type of response we want. ex: 'json', 'array', 'object'
	 * @return The specified result
	 */
	public static function getResultsFromSource($title, $type) {
		
	}
	
	
	/**
	 * This static method handles parsing content with everything needed supplied
	 *
	 * @param $url the url to parse
	 * @param $method the method to parse the link. ex: 'rss', 'commerce', ...
	 * @param $category Optional category for the parsed content
	 * @retuns Array The parsed content in an array
	 */
	private static function parseContent($url, $method, $category) {
		
	}
}

?>