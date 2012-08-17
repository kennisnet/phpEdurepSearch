<?php 
/**
 * Example use of Edurep search and results.
 */
require_once("edurepsearch.php");

# create with a valid api key
$edurep = new EdurepSearch( "12345" );

# optionally, set a different baseurl
# the default baseurl points to production
# $edurep->setBaseurl( "http://anotheredurepurl.nl" );

# set search terms, default edurep
$edurep->setParameter( "query", "math" );

# set another default record schema
# default lom
$edurep->setParameter( "recordSchema", "oai_dc" );

# set a different amount of records to return each page
# default 10, minimum 1, maximum 100
$edurep->setParameter( "maximumRecords", 7 );

# set a different start records (for paging results)
# default 1
$edurep->setParameter( "startRecord", 3 );

# set to return drilldowns, default none
$edurep->setParameter( "x-term-drilldown", "lom.technical.format:5,lom.rights.cost:2" );

# set to return an additional recordschema
# can be called multiple times
$edurep->setParameter( "x-recordSchema", "smbAggregatedData" );

# set recordSchema extra for contributes and classifications
# in the results, also, typicallearningtime is returned in seconds
# rather than the PTxM format
$edurep->setParameter( "x-recordSchema", "extra" );

# perform a search for lom records
$edurep->lomSearch();

# the raw result is stored in $response
# call the EdurepResults class to fill the result object
$results = new EdurepResults( $edurep->response );

# print the result records
print_r( $results->records );

# print startrecord values for a navigation bar
print_r( $results->navigation );
?>
