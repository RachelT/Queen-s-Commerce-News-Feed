DROP DATABASE IF EXISTS commercefeeds;
CREATE DATABASE commercefeeds;
USE `commercefeeds`;

CREATE TABLE feeds (
	id INT(11) UNSIGNED PRIMARY KEY NOT NULL AUTO_INCREMENT,
	title CHAR(255) NOT NULL,
	description TEXT,
	content MEDIUMTEXT,
	link CHAR(255),
	author CHAR(255),
	pubDate DATETIME NOT NULL,
	category CHAR(255) NOT NULL,
	sourceID INT(11) NOT NULL
);

CREATE TABLE sources (
	id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
	title CHAR(255) NOT NULL,
	link CHAR(255),
	parser CHAR(255)
);

GRANT ALL PRIVILEGES ON commercefeeds.* TO 'commercefeeds'@'%' IDENTIFIED BY 'projectxjj';USE commercefeeds;

INSERT INTO sources (`title`, `link`, `parser`) VALUES
('Commerce Portal', 'https://commerce.queensu.ca/commerce/2006/commerce.nsf/homepage',
	'CommercePortal'),
('ComSoc', 'https://comsoc.queensu.ca/home/index.php?option=com_ninjarsssyndicator&feed_id=1&format=raw',
	'RSS'),
('DayOnBay', 'http://dayonbay.ca/index.php/component/option,com_ninjarsssyndicator?feed_id=1&format=raw',
	'RSS');