<?php

require_once('vendor/autoload.php');
/**
 * Example use of Edurep search and results.
 */
date_default_timezone_set('Europe/Amsterdam');


//Create a Edurep Search strategy and config
$strategy = new \Kennisnet\Edurep\EdurepStrategyType();
$config   = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "https://wszoeken.edurep.kennisnet.nl/");
$edurep   = new \Kennisnet\Edurep\EdurepSearch($config);

$edurep
    # set search terms
    # should be provided urldecoded, the class will encode it
    ->setQuery("math")
    # set another default record schema
    # default lom
    ->setRecordSchema("oai_dc")
    # set a different amount of records to return each page
    # default 10, minimum 1, maximum 100
    ->setMaximumRecords(7)
    # set a different start records (for paging results)
    # default 1
    ->setStartRecord(3)
    # set to return drilldowns, default none
    ->setXtermDrilldown("lom.technical.format:5,lom.rights.cost:2")
    # set to return an additional recordschema
    # can be called multiple times
    ->addXRecordSchema("smbAggregatedData")
    # set recordSchema extra for contributes and classifications
    # in the results.
    ->addXRecordSchema("extra");

# get the query (without host) before the search
# for caching purposes

# perform a search for lom records
$response = $edurep->search('lom');

print $edurep->getRequestUrl();

print $response;
