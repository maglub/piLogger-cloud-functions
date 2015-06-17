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
