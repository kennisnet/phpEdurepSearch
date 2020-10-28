<?php

namespace Kennisnet\Edurep;

//(overleg div over exception aan kapstok)

class EdurepSearch
{
    const EDUREP_MAX_STARTRECORD = 1000;
    const MAX_RECORDS = 100;

    const SEARCHTYPE_LOM  = 'lom';
    const SEARCHTYPE_SMO  = 'smo';
    const SEARCHTYPE_PLUS = 'plus';

    private $config;

    # contains the raw curl response
    private $response = "";

    # default search parameters, optional ones can be set by setParameter
    private $parameters
        = [
            "operation"      => "searchRetrieve",
            "version"        => "1.2",
            "recordPacking"  => "xml",
            "query"          => "",
            "maximumRecords" => self::MAX_RECORDS
        ];

    private $searchTerm       = '';
    private $queryFilterParts = [];

    # extra record schema's
    private $recordschemas = [];

    # internal counter for available startrecords
    private $availablestartrecords = 1000;

    # internal counter for curl retries
    private $curlretries = 0;

    public function __construct(DefaultSearchConfig $config)
    {
        $this->config = $config;
    }

    /**
     * TODO Handle multiple formats, drilldowns, x-recordSchema etc?
     * Split results
     *
     * @param string $response
     * @return \StdClass
     */
    public static function splitResponse(string $response)
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadXML($response, LIBXML_NOENT | LIBXML_NSCLEAN);

        $xpath = self::createXPath($dom);

        $obj = new \StdClass;

        $obj->version            = (string)$xpath->query('//srw:version')[0]->nodeValue;
        $obj->numberOfRecords    = (int)$xpath->query('//srw:numberOfRecords')[0]->nodeValue;
        $obj->nextRecordPosition = (int)$xpath->query('//srw:nextRecordPosition')[0]->nodeValue;
        $obj->records            = [];

        $records = $xpath->query('//srw:records/srw:record');

        foreach ($records as $r) {

            $record = new \StdClass();

            //TODO code from wikiwijs zoeken, cleanup or optimize?
            $record->lomrecordId     = $xpath->evaluate("string(./srw:recordIdentifier/text()[1])", $r);
            $record->lomrating       = $xpath->evaluate("string(./srw:extraRecordData/recordData[@recordSchema='smbAggregatedData']/sad:smbAggregatedData/sad:averageNormalizedRating/text()[1])", $r);
            $record->lomnumOfRatings = $xpath->evaluate("string(./srw:extraRecordData/recordData[@recordSchema='smbAggregatedData']/sad:smbAggregatedData/sad:numberOfRatings/text()[1])", $r);
            $record->lomreviewNodes  = $xpath->query("./srw:extraRecordData/recordData[@recordSchema='smo']/smo:smo", $r);
            $record->lomtagNodes     = $xpath->query("./srw:extraRecordData/recordData[@recordSchema='smbAggregatedDataExtra']/sad:smbAggregatedDataExtra/edurep:tag", $r);

            //Fetch Lom record
            $record->lom = $xpath->query('./srw:recordData/czp:lom', $r)->item(0);

            $obj->records[] = $record;
        }

        return $obj;
    }

    private static function createXPath(\DOMDocument $doc)
    {
        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('srw', 'http://www.loc.gov/zing/srw/');
        $xpath->registerNamespace('czp', 'http://www.imsglobal.org/xsd/imsmd_v1p2');
        $xpath->registerNamespace('sad', 'http://xsd.kennisnet.nl/smd/sad');
        $xpath->registerNamespace('smo', 'http://xsd.kennisnet.nl/smd/1.0/');
        $xpath->registerNamespace('edurep', 'http://meresco.org/namespace/users/kennisnet/edurep');
        $xpath->registerNamespace('dd', 'http://meresco.org/namespace/drilldown');
        $xpath->registerNamespace('hr', 'http://xsd.kennisnet.nl/smd/hreview/1.0/');
        $xpath->registerNamespace('hr2', 'http://xsd.kennisnet.nl/smd/1.0/');

        return $xpath;
    }

    /**
     * @param int $value
     * @return $this
     */
    public function setMaximumRecords(int $value)
    {
        if ($value >= 0 && $value <= self::MAX_RECORDS) {
            $this->parameters["maximumRecords"] = $value;
        } else {
            throw new \UnexpectedValueException("The value for maximumRecords should be between 0 and 100.", 22);
        }

        return $this;
    }

    /**
     * @param $recordpacking
     * @return $this
     */
    public function setRecordpacking($recordpacking)
    {
        $this->parameters["recordPacking"] = $recordpacking;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setRecordSchema($value)
    {
        $this->parameters["recordSchema"] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setXtermDrilldown($value)
    {
        $this->parameters["x-term-drilldown"] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setSortKeys($value)
    {
        $this->parameters["sortKeys"] = $value;
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setQuery($value)
    {
        $this->searchTerm = urlencode(trim($value));

        return $this;
    }

    /**
     * @param string $queryPart
     * @return $this
     */
    public function addFilterPart(string $filterId, string $comparator, string $value, string $connector = '+OR+')
    {
        if (!array_key_exists($filterId, $this->queryFilterParts)) {
            $this->queryFilterParts[$filterId] = [];
        }

        $this->queryFilterParts[$filterId][] = [
            'comparator' => $comparator,
            'value'      => $value,
            'connector'  => $connector
        ];

        return $this;
    }


    /**
     * @param $value
     * @return $this
     */
    public function setStartRecord($value)
    {
        if ($value >= 1 && $value <= self::EDUREP_MAX_STARTRECORD) {
            $this->parameters["startRecord"] = $value;
            $this->availablestartrecords     = self::EDUREP_MAX_STARTRECORD - $value;
        } else {
            throw new \UnexpectedValueException("The value for startRecords should be between 1 and " . self::EDUREP_MAX_STARTRECORD . ".", 23);
        }
        return $this;
    }

    /**
     * @param $value
     * @return $this
     */
    public function addXRecordSchema($value)
    {
        $this->recordschemas[] = $value;
        return $this;
    }

    /**
     * Retrieve all local parameters.
     *
     * @return array All Edurep parameters and values
     */
    public function getParameters()
    {
        $parameters = $this->parameters;
        if (!empty($this->recordschemas)) {
            $parameters["x-recordSchemas"] = array_unique($this->recordschemas);
        }
        return $parameters;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param null $searchType (lom, smo etc)
     * @return bool|string
     * @throws \Exception
     */
    public function search($searchType = null)
    {
        $request = $this->getRequestUrl($searchType);

        $curl = curl_init($request);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
        $this->response = curl_exec($curl);

        if (!$this->response) {
            if (curl_errno($curl) == 56) {
                # Failure with receiving network data, could be a 403
                if ($this->curlretries < $this->config->getMaxCurlRetries()) {
                    sleep(1);
                    $this->curlretries++;
                    $this->search();
                } else {
                    throw new \Exception(curl_error($curl));
                }
            } else {
                throw new \Exception(curl_error($curl));
            }
        }

        curl_close($curl);

        return $this->response;
    }

    /**
     * @param null $searchType
     * @return string
     * @throws \Exception
     */
    public function getRequestUrl($searchType = null)
    {
        if (!$this->searchTerm) {
            throw new \Exception('Missing query');
        }

        return $this->config->getBaseUrl() . $this->getQuery($searchType);
    }

    /**
     * @param null $searchType
     * @return string
     */
    public function getQuery($searchType = null)
    {
        # making sure the startRecord/maximumRecord combo does
        # not trigger an exception.
        if ($this->availablestartrecords < $this->parameters["maximumRecords"]) {
            $this->parameters["maximumRecords"] = $this->availablestartrecords;
        }

        $searchQuery = $this->searchTerm;
        foreach ($this->queryFilterParts as $filterId => $queryFilterPart) {
            $searchQuery .= '+AND+' . urlencode('(');
            foreach ($queryFilterPart as $key => $item) {
                if ($key > 0) {
                    $searchQuery .= $item['connector'];
                }
                $searchQuery .= urlencode('(') . $filterId . $item['comparator'] . urlencode($item['value']) . urlencode(')');

            }
            $searchQuery .= urlencode(')');
        }

        $this->parameters['query'] = $searchQuery;

        # setting arguments
        $arguments = [];
        foreach ($this->parameters as $key => $value) {
            $arguments[] = $key . "=" . $value;
        }

        # initial path and query
        $query = $this->config->getStrategy()->getSearchUrl($searchType) . "?" . implode("&", $arguments);

        # adding x-recordSchema's
        foreach (array_unique($this->recordschemas) as $recordschema) {
            $query .= "&x-recordSchema=" . $recordschema;
        }

        return $query;
    }
}
