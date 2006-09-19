-- Creates the user and tables that will actually be used for the system
--
-- You can load this file using either phpMyAdmin or MySQL's command line tools
--
-- mysql -p --user root < bumbbelee.sql
--
-- $Id$

DROP DATABASE IF EXISTS bumblebeedb;
CREATE DATABASE bumblebeedb DEFAULT CHARACTER SET utf8;

USE mysql;

DELETE FROM user WHERE User='bumblebee';
INSERT INTO user (Host,User,Password,Reload_priv) VALUES ('localhost','bumblebee',PASSWORD('bumblebeepass'),'N');
FLUSH PRIVILEGES;
--REVOKE ALL PRIVILEGES ON *.* FROM bumblebee;
--REVOKE GRANT OPTION ON *.* FROM bumblebee;
--REVOKE ALL ON bumblebeedb.* FROM bumblebee;
GRANT SELECT,INSERT,UPDATE,DELETE ON bumblebeedb.* TO bumblebee@localhost;
FLUSH PRIVILEGES;
USE bumblebeedb;

-- Table users
--   usernames, contact details, password etc

DROP TABLE IF EXISTS users;
CREATE TABLE users (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(127) CHARACTER SET utf8 NOT NULL,
  name VARCHAR(255) CHARACTER SET utf8 NOT NULL,
  passwd VARCHAR(50) CHARACTER SET utf8 NOT NULL,
  email VARCHAR(255) CHARACTER SET utf8,
  phone VARCHAR(20) CHARACTER SET utf8,
  suspended BOOL DEFAULT 0,
  isadmin BOOL DEFAULT 0,
  deleted BOOL DEFAULT 0,
  permissions INTEGER UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY username (username)
) DEFAULT CHARACTER SET utf8;
-- password contains MD5 hash

DROP TABLE IF EXISTS projects;
CREATE TABLE projects (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) CHARACTER SET utf8 NOT NULL,
  longname TEXT CHARACTER SET utf8 NOT NULL,
  defaultclass SMALLINT UNSIGNED NOT NULL,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS groups;
CREATE TABLE groups (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) CHARACTER SET utf8 NOT NULL,
  longname TEXT CHARACTER SET utf8 NOT NULL,
  addr1 TEXT CHARACTER SET utf8,
  addr2 TEXT CHARACTER SET utf8,
  suburb TEXT CHARACTER SET utf8,
  state VARCHAR(127) CHARACTER SET utf8,
  code VARCHAR(63) CHARACTER SET utf8,
  country VARCHAR(255) CHARACTER SET utf8,
  email VARCHAR(255) CHARACTER SET utf8,
  fax VARCHAR(63) CHARACTER SET utf8,
  account TEXT CHARACTER SET utf8,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS userprojects;
CREATE TABLE userprojects (
  userid SMALLINT UNSIGNED NOT NULL,
  projectid SMALLINT UNSIGNED NOT NULL,
  isdefault BOOL DEFAULT 0
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS projectgroups;
CREATE TABLE projectgroups (
  projectid SMALLINT UNSIGNED NOT NULL,
  groupid SMALLINT UNSIGNED NOT NULL,
  grouppc FLOAT(16)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS instruments;
CREATE TABLE instruments (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) CHARACTER SET utf8 NOT NULL,
  longname TEXT CHARACTER SET utf8 NOT NULL,
  location TEXT CHARACTER SET utf8,
  class SMALLINT UNSIGNED NOT NULL,
  usualopen TIME NOT NULL,
  usualclose TIME NOT NULL,
  calprecision SMALLINT UNSIGNED NOT NULL,
  caltimemarks SMALLINT UNSIGNED NOT NULL,
  callength SMALLINT UNSIGNED NOT NULL,
  calhistory SMALLINT UNSIGNED NOT NULL,
  calfuture SMALLINT UNSIGNED DEFAULT '365' NOT NULL,
  timeslotpicture TEXT CHARACTER SET utf8 NOT NULL,
  halfdaylength FLOAT(16) NOT NULL,
  fulldaylength FLOAT(16) NOT NULL,
  mindatechange FLOAT(16) NOT NULL,
  calendarcomment TEXT NOT NULL,
  supervisors TEXT CHARACTER SET utf8,
  emailonbooking BOOL DEFAULT 0,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS permissions;
CREATE TABLE permissions (
  userid SMALLINT UNSIGNED NOT NULL,
  instrid SMALLINT UNSIGNED NOT NULL,
  isadmin BOOL DEFAULT 0,
  announce BOOL DEFAULT 1,
  unbook BOOL DEFAULT 1,
  haspriority BOOL DEFAULT 0,
  points SMALLINT UNSIGNED,
  pointsrecharge SMALLINT UNSIGNED
) DEFAULT CHARACTER SET utf8;
-- announce: receive announcement emails
-- unbook: receive unbook announcement emails
-- haspriority: is a priority user  (unused)
-- points: current points balance  (unused)
-- pointsrecharge: how many points added per cycle  (unused)

DROP TABLE IF EXISTS instrumentclass;
CREATE TABLE instrumentclass (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(127) CHARACTER SET utf8 NOT NULL,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS userclass;
CREATE TABLE userclass (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(127) CHARACTER SET utf8 NOT NULL,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS costs;
CREATE TABLE costs (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  instrumentclass SMALLINT UNSIGNED NOT NULL,
  userclass SMALLINT UNSIGNED NOT NULL,
  hourfactor FLOAT(16),
  halfdayfactor FLOAT(16),
  costfullday FLOAT(16),
  dailymarkdown FLOAT(16) DEFAULT '0' NOT NULL,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS projectrates;
CREATE TABLE projectrates (
  projectid SMALLINT UNSIGNED NOT NULL,
  instrid SMALLINT UNSIGNED NOT NULL,
  rate SMALLINT UNSIGNED,
  UNIQUE KEY rate (projectid,instrid)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS bookings;
CREATE TABLE bookings (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  -- bookwhen DATE NOT NULL,
  bookwhen DATETIME NOT NULL,
  -- starttime TIME NOT NULL,
  -- stoptime TIME NOT NULL,
  duration TIME NOT NULL,
  -- ishalfday BOOL DEFAULT 0,
  -- isfullday BOOL DEFAULT 0,
  instrument SMALLINT UNSIGNED NOT NULL,
  bookedby SMALLINT UNSIGNED NOT NULL,
  userid SMALLINT UNSIGNED NOT NULL,
  projectid SMALLINT UNSIGNED NOT NULL,
  discount FLOAT(16) DEFAULT '0', -- as a percentage
  ip CHAR(16) CHARACTER SET utf8,
  comments TEXT CHARACTER SET utf8,
  log TEXT CHARACTER SET utf8,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

-- DROP TABLE IF EXISTS adminconfirm;
-- CREATE TABLE adminconfirm (
--   action ENUM ('book', 'unbook'),
--   booking INTEGER UNSIGNED NOT NULL
-- );

-- separate this into a different table only so we can implement it later
--
-- DROP TABLE IF EXISTS bookingmeta;
-- CREATE TABLE bookingmeta (
--   instrid SMALLINT UNSIGNED NOT NULL,
--   requireapproval BOOL DEFAULT 0,
--   min_unbook FLOAT(16), -- in hours
--   alert_unbook BOOL DEFAULT 0,
--   book_future_normal TINYINT UNSIGNED, -- days in advance that users can book
--   book_priority_safe TINYINT UNSIGNED, -- days to protect priority users
--   book_priority_days TINYINT UNSIGNED, -- which days are priority days
--   points_hourly TINYINT UNSIGNED,
--   points_halfday TINYINT UNSIGNED,
--   points_fullday TINYINT UNSIGNED,
--   points_evening TINYINT UNSIGNED,
--   PRIMARY KEY (instrid)
-- );

DROP TABLE IF EXISTS consumables;
CREATE TABLE consumables (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(255) CHARACTER SET utf8 NOT NULL,
  longname TEXT CHARACTER SET utf8 NOT NULL,
  cost FLOAT(16) NOT NULL,
  deleted BOOL DEFAULT 0,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

DROP TABLE IF EXISTS consumables_use;
CREATE TABLE consumables_use (
  id INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  usewhen DATE NOT NULL,
  consumable SMALLINT UNSIGNED NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  addedby SMALLINT UNSIGNED NOT NULL,
  userid SMALLINT UNSIGNED NOT NULL,
  projectid SMALLINT UNSIGNED NOT NULL,
  ip CHAR(16) CHARACTER SET utf8,
  comments VARCHAR(127) CHARACTER SET utf8,
  log TEXT CHARACTER SET utf8,
  PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8;

-- DROP TABLE IF EXISTS billing_formats;
-- CREATE TABLE billing_formats (
--   id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
--   name VARCHAR(63),
--   localfile VARCHAR(255),
--   PRIMARY KEY (id)
-- );


--     Create an admin user
INSERT INTO users (username,name,passwd,isadmin) VALUES
  ('BumblebeeAdmin','Queen Bee',MD5('defaultpassword123'),1)
;
