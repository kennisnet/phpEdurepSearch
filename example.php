<?php 
/**
 * Example use of Edurep search.
 */
require_once("edurepsearch.php");

# create with a valid api key
$edurep = new EdurepSearch( "12345" );

# set search terms, default edurep
$edurep->setParameter( "query", "math" );

# set another default record schema
# default lom
$edurep->setParameter( "recordSchema", "oai_dc" );

# set a different amount of records to return each page
# default 10, minimum 1, maximum 100
$edurep->setParameter( "maximumRecords", 5 );

# set a different start records (for paging results)
# default 1
$edurep->setParameter( "startRecord", 3 );

# set to return an additional recordschema
# can be called multiple times
$edurep->setParameter( "x-recordSchema", "smbAggregatedData" );

# perform a search for lom records
$edurep->lomSearch();

# the raw result is stored in $response
print_r( $edurep->response );
?>
