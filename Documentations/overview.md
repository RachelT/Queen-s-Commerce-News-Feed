Overview
========

Commerce Feeds has two major components. The server and the extension.

Server
------

### The server has three main components ###
1. **Database**
2. **Parser**
3. **API**


####Database####
The server code is written in PHP and handles retrieving and parsing the feeds used by the chrome extension. All feeds parsed by our server is stored in our MYSQL database under the feeds table. The exact schema of our MYSQL database can be found under server/database/schema.sql

####Parser####
Parsing of the different sources is handled by parser classes located under server/classes/parsers. Currently most of our feeds are RSS feeds that can be parsed using the rssparser class. However, for feeds that are not RSS (the Commerce portal feeds), a class can be constructed to handle parsing of these feeds. Right now we did not implement an abstract class that specify the common interface of these parsers. In the future this should be implemented to allow more parsers to be constructed and used.
The Parser Manager acts as an interface to our API client. The Parser Manager handles parsing any url link passed to it by using the parser classes. The Parser Manager also handles database interactions such as saving and loading from the database when needed.

####API####
The API directory contains various PHP files that the client calls upon to get feeds from our database. All results are returned in json format using the helper class RestUtils.
Below is a breakdown of the apis we currently provide:
**getFeed.php:** Handles getting a single feed from the database. From example, this file can handle getting the most recent feed of a certain category.
**getFeeds.php:** Handles getting a batch of feeds from the database. This is used by our chrome extension to populate all its categories in the beginning.
**updateFeeds.php:** Handles updating all categories from our database. This is called by our host provider periodically using [cron](http://unixgeeks.org/security/newbie/unix/cron-1.html) to make sure that we have the most recent feeds.
**

Extension
---------

### The extension has three main components ###
1. **Markup**
2. **Layout**
3. **Logic**


####Markup####
The markup is under the views directory. The markup is written in html and has three main files:
**background.html:** This script operates in the background when our extension is opened. Currently this script does two things. 
1. Open the options page when application is installed
2. Fetch the feeds required by our our main extension view (popup.html).
**options.html:** This script handles displaying our options view. Which contains configurations to allow users to customize their feeds.
**popup.html:** This script handles displaying our extension and communicating with our javascript. Currently a lot of javascript is embedded in this script. In the future great effort will be made to reduce the amount of javascript contained in this file.

####Layout####
Layout of the markup files is written in [less](http://lesscss.org/), which compiles to the css in the css folder. [Bootstrap](http://twitter.github.com/bootstrap/) and [jQuery UI](http://jqueryui.com/) are used simply for layout and to provide a consistent web app look to the extension :)

####Logic####
The logic of the web app is written in javascript. [jQuery](http://jquery.com/) and [Bootstrap](http://twitter.github.com/bootstrap/) are used to provide some functionalities to the web app.
Currently all logic is written in feeds.js. As a result this file is pretty long. In the future great efforts will be made to rewrite the extension using [backbone.js](http://documentcloud.github.com/backbone/) in order to benefit from the MVC pattern. 
