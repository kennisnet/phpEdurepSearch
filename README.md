# phpEdurepSearch
This is a PHP package for searching with the Edurep or Edurep CatalogService search engines. In Edurep you can search in NLLOM metadata records, where you can search in CatalogService Entry metadata records in the CatalogService.
Detailed API instructions for the respective services can be found here (in Dutch):
* [nllom](https://developers.wiki.kennisnet.nl/index.php?title=Edurep:LOM_SearchRetrieve)
* [catalogservice](https://developers.wiki.kennisnet.nl/index.php?title=CS:Entry_SearchRetrieve)

## usage
To distinguish between the different endpoints, different *strategies* can be used. 
* EdurepStrategyType
* CatalogusStrategyType

Each strategy is combined with the endpoint host to create the class configuration:
* (edurep) https://wszoeken.edurep.kennisnet.nl/
* (catalogservice) https://catalogusservice.edurep.nl/

## example
While a full *example.php* file is available, a smaller example is shown here:
```php
$strategy = new \Kennisnet\Edurep\EdurepStrategyType();
$config = new \Kennisnet\Edurep\DefaultSearchConfig($strategy, "https://wszoeken.edurep.kennisnet.nl/");
$edurep = new \Kennisnet\Edurep\EdurepSearch($config);

$edurep
    # set search terms
    # should be provided urldecoded, the class will encode it
    ->setQuery("math")

    # set another default record schema
    # default lom
    ->setRecordSchema("oai_dc")

    # set to return drilldowns, default none
    ->setXtermDrilldown("lom.technical.format:5,lom.rights.cost:2")

    # set to return an additional recordschema
    # can be called multiple times
    ->addXRecordSchema("smbAggregatedData")
;

# perform a search for lom records
$response = $edurep->search('lom');

print $edurep->getRequestUrl();

print $response;
```
