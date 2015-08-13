[![Latest Stable Version](https://poser.pugx.org/dmamontov/amo-restapi/v/stable.svg)](https://packagist.org/packages/dmamontov/amo-restapi)
[![License](https://poser.pugx.org/dmamontov/amo-restapi/license.svg)](https://packagist.org/packages/dmamontov/amo-restapi)
[![Total Downloads](https://poser.pugx.org/dmamontov/amo-restapi/downloads.svg)](https://packagist.org/packages/dmamontov/amo-restapi)

AmoCRM API Client
=================

This class can Manage accounts of AmoCRM using its REST API.

It can obtain an authorization token for a given account, so it can send HTTP requests to the [AmoCRM Rest API](https://developers.amocrm.ru/rest_api/) in order to perform several types of operations.

Currently it can the current accounts, set and get contacts, contact links, leads, companies, tasks, notes and fields.

## Requirements
* PHP version >5.0
* curl

## Available methods
* `getAccounts`
* `setContacts`, `getContactsList`, `getContactsLinks`
* `setLeads`, `getLeadsList`
* `setCompany`, `getCompanyList`
* `setTasks`, `getTasksList`
* `setNotes`, `getNotesList`
* `setFields`

## Installation

1) Install [composer](https://getcomposer.org/download/)

2) Follow in the project folder:
```bash
composer require dmamontov/amo-restapi ~1.0.2
```

In config `composer.json` your project will be added to the library `dmamontov/amo-restapi`, who settled in the folder `vendor/`. In the absence of a config file or folder with vendors they will be created.

If before your project is not used `composer`, connect the startup file vendors. To do this, enter the code in the project:
```php
require 'path/to/vendor/autoload.php';
```

## Examples of use

### Getting information about the leads

``` php
$amo = new AmoRestApi($subDomain, $login, $key);
$order = $amo->getLeadsList(1, 0, 2556);
```
### Creating a contacts

``` php
$amo = new AmoRestApi($subDomain, $login, $key);
$contacts['add'] = array(
    'name' => 'Test',
    'request_id' => '2555',
    'date_create' => time()
);
$result = $amo->setContacts($contacts);
```
