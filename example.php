<?
require 'AmoRestApi.php';

//Getting information about the leads
$leadsId = 2556;
$amo = new AmoRestApi($subDomain, $login, $key);
$order = $amo->getLeadsList(1, 0, $leadsId);

//Creating a contacts
$amo = new AmoRestApi($subDomain, $login, $key);
$contacts['add'] = array(
    'name' => 'Test',
    'request_id' => '2555',
    'date_create' => time()
);
$result = $amo->setContacts($contacts);
?>