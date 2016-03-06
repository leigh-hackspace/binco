<?php namespace BinCo;

require_once '../vendor/autoload.php';

$ScrapeAddresses = new Scrapers\ScrapeAddress('WN5 9SX');
$Addresses = $ScrapeAddresses->getAddresses();

var_dump($Addresses);

$ASPData = array(
    'EventValidation' => $Addresses['EventValidation'],
    'ViewState' => $Addresses['ViewState']
);
$ScrapeBin = new Scrapers\ScrapeBinDetails('UPRN100011808364', $ASPData);
$BinDetails = $ScrapeBin->getDetails();

var_dump($BinDetails);
