-- Create the user and tables that will actually be used for the system
-- 
-- mysql -p --user bumblebee < setup-tables.sql
-- 
-- $Id$

DROP DATABASE IF EXISTS bumblebeedb;
CREATE DATABASE bumblebeedb;

USE mysql;

DELETE FROM user WHERE User='bumblebee';
INSERT INTO user (Host,User,Password,Reload_priv) VALUES ('localhost','bumblebee',PASSWORD('bumblebeepass'),'N');

--REVOKE ALL PRIVILEGES ON *.* FROM bumblebee;
--REVOKE GRANT OPTION ON *.* FROM bumblebee;
--REVOKE ALL ON bumblebeedb.* FROM bumblebee;
GRANT SELECT,INSERT,UPDATE,DELETE ON bumblebeedb.* TO bumblebee;

USE bumblebeedb;

-- Table users
--   usernames, contact details, password etc

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(15) NOT NULL,
  name VARCHAR(63) NOT NULL,
  passwd CHAR(32) NOT NULL,
  email VARCHAR(63),
  phone VARCHAR(20),
  suspended BOOL DEFAULT 'FALSE',
  isadmin BOOL DEFAULT 'FALSE',
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
);
-- password contains MD5 hash

DROP TABLE IF EXISTS projects;
CREATE TABLE projects (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(31) NOT NULL,
  longname VARCHAR(255) NOT NULL,
  defaultclass SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(31) NOT NULL,
  longname VARCHAR(255) NOT NULL,
  addr1 VARCHAR(127),
  addr2 VARCHAR(127),
  suburb VARCHAR(63),
  state VARCHAR(31),
  code VARCHAR(15),
  country VARCHAR(31),
  email VARCHAR(63),
  fax VARCHAR(20),
  account VARCHAR(255),
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS userprojects;
CREATE TABLE userprojects (
  userid INTEGER UNSIGNED NOT NULL,
  projectid INTEGER UNSIGNED NOT NULL,
  isdefault BOOL DEFAULT 'FALSE'
);

DROP TABLE IF EXISTS projectgroups;
CREATE TABLE projectgroups (
  projectid INTEGER UNSIGNED NOT NULL,
  groupid INTEGER UNSIGNED NOT NULL,
  grouppc FLOAT(16)
);

DROP TABLE IF EXISTS instruments;
CREATE TABLE instruments (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(31) NOT NULL,
  longname VARCHAR(255) NOT NULL,
  location VARCHAR(127),
  class SMALLINT UNSIGNED NOT NULL,
  usualopen TIME NOT NULL,
  usualclose TIME NOT NULL,
  calprecision SMALLINT UNSIGNED NOT NULL,
  caltimemarks SMALLINT UNSIGNED NOT NULL,
  callength SMALLINT UNSIGNED NOT NULL,
  calhistory SMALLINT UNSIGNED NOT NULL,
  timeslotpicture TEXT NOT NULL,
  halfdaylength FLOAT(16) NOT NULL,
  fulldaylength FLOAT(16) NOT NULL,
  mindatechange FLOAT(16) NOT NULL,
  calendarcomment TEXT NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
  userid SMALLINT UNSIGNED NOT NULL,
  instrid SMALLINT UNSIGNED NOT NULL,
  isadmin BOOL DEFAULT 'FALSE',
  announce BOOL DEFAULT 'TRUE',
  unbook BOOL DEFAULT 'TRUE',
  haspriority BOOL DEFAULT 'FALSE',
  points SMALLINT UNSIGNED,
  pointsrecharge SMALLINT UNSIGNED
);
-- announce: receive announcement emails
-- unbook: receive unbook announcement emails
-- haspriority: is a priority user 
-- points: current points balance
-- pointsrecharge: how many points added per cycle 

DROP TABLE IF EXISTS instrumentclass;
CREATE TABLE instrumentclass (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(63) NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS userclass;
CREATE TABLE userclass (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(63) NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS costs;
CREATE TABLE costs (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instrumentclass SMALLINT UNSIGNED NOT NULL,
  userclass SMALLINT UNSIGNED NOT NULL,
  hourfactor FLOAT(16),
  halfdayfactor FLOAT(16),
  costfullday FLOAT(16),
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS projectrates;
CREATE TABLE projectrates (
  projectid SMALLINT UNSIGNED NOT NULL,
  instrid SMALLINT UNSIGNED NOT NULL,
  rate SMALLINT UNSIGNED,
  UNIQUE KEY rate (projectid,instrid)
);

DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  -- bookwhen DATE NOT NULL,
  bookwhen DATETIME NOT NULL,
  -- starttime TIME NOT NULL,
  -- stoptime TIME NOT NULL,
  duration TIME NOT NULL,
  -- ishalfday BOOL DEFAULT 'FALSE',
  -- isfullday BOOL DEFAULT 'FALSE',
  instrument SMALLINT UNSIGNED NOT NULL,
  bookedby SMALLINT UNSIGNED NOT NULL,
  userid SMALLINT UNSIGNED NOT NULL,
  projectid SMALLINT UNSIGNED NOT NULL,
  discount FLOAT(16) DEFAULT '0', -- as a percentage
  ip CHAR(16),
  comments VARCHAR(255),
  log TEXT,
  deleted BOOL DEFAULT 'FALSE',
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS adminconfirm;
CREATE TABLE adminconfirm (
  action ENUM ('book', 'unbook'),
  booking INTEGER UNSIGNED NOT NULL
);

-- separate this into a different table only so we can implement it later
--
DROP TABLE IF EXISTS bookingmeta;
CREATE TABLE bookingmeta (
  instrid SMALLINT UNSIGNED NOT NULL,
  requireapproval BOOL DEFAULT 'FALSE',
  min_unbook FLOAT(16), -- in hours
  alert_unbook BOOL DEFAULT 'FALSE',
  book_future_normal TINYINT UNSIGNED, -- days in advance that users can book
  book_priority_safe TINYINT UNSIGNED, -- days to protect priority users
  book_priority_days TINYINT UNSIGNED, -- which days are priority days
  points_hourly TINYINT UNSIGNED,
  points_halfday TINYINT UNSIGNED,
  points_fullday TINYINT UNSIGNED,
  points_evening TINYINT UNSIGNED,
  PRIMARY KEY (instrid)
);

DROP TABLE IF EXISTS consumables;
CREATE TABLE consumables (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(31) NOT NULL,
  longname VARCHAR(255) NOT NULL,
  cost FLOAT(16) NOT NULL,
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS consumables_use;
CREATE TABLE consumables_use (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  usewhen DATE NOT NULL,
  consumable SMALLINT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  addedby SMALLINT UNSIGNED NOT NULL,
  userid SMALLINT UNSIGNED NOT NULL,
  projectid SMALLINT UNSIGNED NOT NULL,
  ip CHAR(16),
  comments VARCHAR(31),
  log VARCHAR(255),
  PRIMARY KEY (id)
);

DROP TABLE IF EXISTS billing_formats;
CREATE TABLE billing_formats (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(63),
  localfile VARCHAR(255),
  PRIMARY KEY (id)
);


--     Create an admin user
INSERT INTO users (username,name,passwd,isadmin) VALUES
  ('BumblebeeAdmin','Queen Bee',PASSWORD('defaultpassword123'),1)
;
