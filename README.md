# Cpanel's API
Cpanel's API 1 and 2 for Laravel 5.2

## Contents
- [Installation Guide](#installation-guide)
- [Configuration](#configuration)
- [Usage](#usage)
- [Functions](#functions)
- [Documentation](#documentation)

### Installation Guide
Require this package in your composer.json and update composer. This will download the package.

    composer require zanysoft/cpanel-api

After updating composer, add the ServiceProvider to the providers array in config/app.php

    ZanySoft\Cpanel\CpanelServiceProvider::class,

You can optionally use the facade for shorter code. Add this to your facades:

    'Cpanel' => ZanySoft\Cpanel\CpanelFacade::class,

### Configuration
The defaults configuration settings are set in `config/cpanel.php`. Copy this file to your own config directory to modify the values. You can publish the config using this command:

    php artisan vendor:publish --provider="ZanySoft\Cpanel\CpanelServiceProvider"
 
### Usage

You can create a new Cpanel instance.

    $cpanel = App::make('cpanel');
    $cpanel->setHost($host_ip);
    $cpanel->setAuth($username, $password) //if you don't want to set in config file
    return $cpanel->api2($user, $module, $function, $args = array());

Or use the facade with chain the methods:

    return Cpanel::api2($user, $module, $function, $args = array());
    
You can use with chain the methods:

    return Cpanel::setHost($host_ip)->setAuth($username, $password)->api2($user, $module, $function, $args = array());

You can set the authentication before chain if you don't want to set this in config/cpanel.php file.

    return Cpanel::setAuth($username, $password)->api2($user, $module, $function, $args = array());

### Functions
This is the example when you want to define your configuration

```php
  <?php
    $cpanel = App::make('cpanel');
    $cpanel->setHost($host_ip);
    $cpanel->setAuth($username, $password)
    return $cpanel->api2($user, $module, $function, $args = array());
```

If you like to get some list accounts from cPanel/WHM
```php
	<?php

	$accounts = $cpanel->listaccts();
	// passing parameters
	$accounts = $cpanel->listaccts($searchtype, $search);
	
```
If you want to create new subdomain
```php
	<?php
	
	// createSubdomain(Domain Name, Username, Dubdomain Directory, Main Domain)
         $cpanel->createSubdomain('subdomain', 'username', '/public_html/subdomain', 'example.com')
```


For accessing cPanel API 2, you can use this.

```php
	<?php
	
	return $cpanel->api2($user, $module, $function, $args = array());
```


For accessing cPanel API 1, you can use this.
```php
	<?php
	
	return $cpanel->api1($user, $module, $function, $args = array());
```

### Documentation

 Visit this link for api2 options: https://documentation.cpanel.net/display/SDK/Guide+to+cPanel+API+2

 Visit this link for api1 options: https://documentation.cpanel.net/display/SDK/Guide+to+cPanel+API+1

