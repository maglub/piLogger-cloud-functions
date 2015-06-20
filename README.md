# piLogger-cloud-functions
This repository contains core functions that are used by the [piLogger-cloud-frontend](https://github.com/do3meli/piLogger-cloud-frontend) and the [piLogger-cloud-rest ](https://github.com/do3meli/piLogger-cloud-rest) API.

##Package Installation
Using composer you can add the dependency to this package by executing the following command in your project's root directory: `composer require do3meli/piLogger-cloud-functions`. Alternatively you can manually adjust the `composer.json` file and add the following lines:   

    {
        "require": {
            "do3meli/piLogger-cloud-functions": "dev-master"
        }
    }
##Dependencies
If you are using composer to install this package you wont have to care about installing dependencies. If you are not using composer you will have to get the libraries installed that are defined in the `composer.json` file:
* [nesbot/carbon](http://carbon.nesbot.com) for Date and Time representations and calculations
* [datastax/php-driver](http://datastax.github.io/php-driver/) for the communication with Cassandra databases

##Cassandra Driver
The [datastax/php-driver](http://datastax.github.io/php-driver/) requires the Cassandra C/C++ driver which needs to be compiled on the system where this package is installed. A detailed build instruction is available [here] (http://datastax.github.io/cpp-driver/topics/building/). Once compiled the `cassandra.so` file needs to be added to the `php.ini` configuration so that the driver gets loaded correctly. If you have PHP and Apache2 installed on your system you most likely want to adjust the `php.ini` files which reside in `/etc/php5/cli/` and `/etc/php5/apache2/` and add the following line:

```
extension=/path/to/your/cassandra.so
```
##Cassandra Database Schema
This package is based on a single Cassandra database table that stores all the measurements from various sensors. You can use the following SQL to create the `sensordata` table:

```
CREATE TABLE piclouddb.sensordata (
    sensor_id text,
    day text,
    probe_time timestamp,
    probe_value float,
    PRIMARY KEY ((sensor_id, day), probe_time)
)
```
##MySQL Database Schema
As a second database this package requires MySQL to store all meta information for the sensor measurements. The structure of these tables is shown below.

**user table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`user` (
  `uid` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(64) NULL DEFAULT NULL,
  `authtoken` VARCHAR(32) NULL DEFAULT NULL,
  PRIMARY KEY (`uid`),
  UNIQUE INDEX `username_UNIQUE` (`username` ASC))
```
**cockpitview table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`cockpitview` (
  `cvid` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  `owner` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`cvid`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  INDEX `owner_idx` (`owner` ASC),
  CONSTRAINT `cockpitowner` FOREIGN KEY (`owner`) REFERENCES `piCloudDB`.`user` (`uid`))
```
**device table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`device` (
  `did` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  `identifier` VARCHAR(45) NOT NULL,
  `owner` INT(11) NOT NULL,
  PRIMARY KEY (`did`),
  UNIQUE INDEX `identifier_UNIQUE` (`identifier` ASC),
  INDEX `owner_idx` (`owner` ASC),
  CONSTRAINT `owner` FOREIGN KEY (`owner`) REFERENCES `piCloudDB`.`user` (`uid`))
```
**graph table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`graph` (
  `gid` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  `dataSinceDays` INT(11) NOT NULL,
  `view` INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`gid`),
  UNIQUE INDEX `name_UNIQUE` (`name` ASC),
  INDEX `view_idx` (`view` ASC),
  CONSTRAINT `view` FOREIGN KEY (`view`) REFERENCES `piCloudDB`.`cockpitview` (`cvid`))
```
**sensor table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`sensor` (
  `sid` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL DEFAULT NULL,
  `type` VARCHAR(45) NULL DEFAULT NULL,
  `identifier` VARCHAR(45) NOT NULL,
  `attached` INT(11) NOT NULL,
  PRIMARY KEY (`sid`),
  UNIQUE INDEX `UNIQUE_sensor` (`identifier` ASC, `attached` ASC),
  UNIQUE INDEX `identifier_UNIQUE` (`identifier` ASC),
  INDEX `attached_idx` (`attached` ASC),
  CONSTRAINT `attached` FOREIGN KEY (`attached`) REFERENCES `piCloudDB`.`device` (`did`))
```
**sensor2graph table:**
```
CREATE TABLE IF NOT EXISTS `piCloudDB`.`sensor2graph` (
  `sensor` INT(11) NOT NULL,
  `graph` INT(11) NOT NULL,
  PRIMARY KEY (`sensor`, `graph`),
  INDEX `graph_idx` (`graph` ASC),
  CONSTRAINT `graph` FOREIGN KEY (`graph`) REFERENCES `piCloudDB`.`graph` (`gid`),
  CONSTRAINT `sensor` FOREIGN KEY (`sensor`) REFERENCES `piCloudDB`.`sensor` (`sid`))

