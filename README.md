PHPClient to work with through AmoCRM Rest API
==============================================

PHPClient to work with through  [AmoCRM Rest API](https://developers.amocrm.ru/rest_api/).

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
