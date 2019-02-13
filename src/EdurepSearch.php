<?php
namespace Kennisnet\Edurep;

//TODO namespace?


//convert params array to class properties?
//still need x-api-key as constructor?
//default is production, set flag in constructor or other mechanism to determine type search? Lom/SMO search too?
//Make curl timeouts configurable? Constructor config?
//Cache mechanism?
//PHP 7 ready?
//Specific Network Exceptions needed?
//



class EdurepSearch
{
    # contains the raw curl request, the url
    private $request = "";

    # contains the raw curl response
    private $response = "";

    # baseurl for edurep production
    private $baseurl = "http://wszoeken.edurep.kennisnet.nl:8000/";

    # search path
    private $path = "edurep/sruns";

    # default search parameters, optional ones can be set by setParameter
    private $parameters = [
        "operation"     => "searchRetrieve",
        "version"       => "1.2",
        "recordPacking" => "xml",
        "x-api-key"     => "",
        "query"         => "edurep"
    ];

    # extra record schema's
    private $recordschemas = [];

    # maximum startRecord allowed by Edurep
    //TODO make const? non-configurable anyhow
    public $maxstartrecord = 1000;

    # internal counter for available startrecords
    //TODO make const? non-configurable anyhow
    private $availablestartrecords = 1000;

    # internal counter for curl retries
    //TODO make const? non-configurable anyhow
    private $curlretries = 0;

    # curl retries before an exception is thrown
    //TODO make const? non-configurable anyhow
    private $maxcurlretries = 3;

    /**
     * EdurepSearch constructor.
     *
     * @param $api_key
     * @throws \Exception
     */
    public function __construct($api_key)
    {
        if (!empty($api_key)) {
            $this->parameters["x-api-key"] = $api_key;
        } else {
            throw new \Exception("Use a valid Edurep API key", 21);
        }
    }

    /*
     * Simulate a search that was cached by the requesting
     * application. The application got the results from a local
     * cache, but performs an Edurep query to let Edurep keep
     * the statistics.
     * The exact same query as in the instance is fired, but with
     * 0 records and an x-cache parameter.
     *
     * TODO keep? If we still need this, split search/execute query etc again?
     */
    public function cacheSearch()
    {
        if (!empty($this->query)) {
            $this->query                        = "";
            $this->parameters["maximumRecords"] = 0;
            $this->parameters["x-cache"]        = 1;
            $this->search();

        }
    }

    /**
     * TODO PHP 7 typehinting?
     * @param $value
     */
    public function setMaximumRecords($value)
    {
        if ($value >= 0 && $value <= 100) {
            $this->parameters["maximumRecords"] = $value;
        } else {
            throw new \UnexpectedValueException("The value for maximumRecords should be between 0 and 100.", 22);
        }

        return $this;
    }

    public function setRecordSchema($value)
    {
        $this->parameters["recordSchema"] = $value;
        return $this;
    }

    public function setXtermDrilldown($value)
    {
        $this->parameters["x-term-drilldown"] = $value;
        return $this;
    }

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
        $this->parameters["query"] = urlencode($value);;
        return $this;
    }

    public function setStartRecord($value)
    {
        if ($value >= 1 && $value <= $this->maxstartrecord) {
            $this->parameters["startRecord"] = $value;
            $this->availablestartrecords = $this->maxstartrecord - $value;
        } else {
            throw new \UnexpectedValueException("The value for startRecords should be between 1 and " . $this->maxstartrecord . ".", 23);
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

//    /**
//     * Set Edurep parameters for the request.
//     * The query parameter should be provided urldecoded.
//     *
//     * @param string $key Edurep parameter.
//     * @param string $value Value for parameters, urlencoded.
//     */
//    public function setParameter($key, $value)
//    {
//        switch ($key) {
//            case "maximumRecords":
//                if ($value >= 0 && $value <= 100) {
//                    $this->parameters[$key] = $value;
//                } else {
//                    throw new UnexpectedValueException("The value for maximumRecords should be between 0 and 100.", 22);
//                }
//                break;
//
//            case "recordSchema":
//            case "x-term-drilldown":
//            case "sortKeys":
//                $this->parameters[$key] = $value;
//                break;
//
//            case "query":
//                $this->parameters[$key] = urlencode($value);
//                break;
//
//            case "startRecord":
//                if ($value >= 1 && $value <= $this->maxstartrecord) {
//                    $this->parameters[$key]      = $value;
//                    $this->availablestartrecords = $this->maxstartrecord - $value;
//                } else {
//                    throw new UnexpectedValueException("The value for startRecords should be between 1 and " . $this->maxstartrecord . ".", 23);
//                }
//                break;
//
//            case "x-recordSchema":
//                $this->recordschemas[] = $value;
//                break;
//
//            default:
//                throw new InvalidArgumentException("Unsupported Edurep parameter: " . $key, 1);
//        }
//    }

    /**
     * Retrieve all local parameters.
     *
     * @return array All Edurep parameters and values
     */
    public function getParameters()
    {
        $parameters = $this->parameters;
        if (!empty($recordschemas)) {
            $parameters["x-recordSchemas"] = array_unique($this->recordschemas);
        }
        return $parameters;
    }

    /**
     * Set another baseurl, for instance to Edurep Staging.
     *
     * @param string $baseurl An Edurep baseurl (including port)
     */
    public function setBaseurl($baseurl)
    {
        $this->baseurl = $baseurl;
        return $this;
    }

    /**
     * Sets the search type by inputting either lom or smo.
     * The search type (determined by path) is set to lom
     * by default.
     *
     * @param string $type The search type.
     */
    public function setSearchType($type)
    {
        switch ($type) {
            case "lom":
                $this->path = "edurep/sruns";
                break;
            case "smo":
                $this->path = "smo/sruns";
                break;
            case "plus":
                $this->path = "edurep/sruns/plus";
                break;
            default:
                $this->path = "edurep/sruns";
        }
    }

    public function setRecordpacking($recordpacking)
    {
        $this->parameters["recordPacking"] = $recordpacking;
    }

    /**
     * Delete existing query. After deleting the query a new
     * one can be build.
     *
     * @return bool
     */
    public function deleteQuery()
    {
        if (empty($this->query)) {
            return false;
        }
        $this->query = "";

        return true;
    }

    /**
     * Returns the Edurep query url.
     *
     * @return string Query url without host.
     */
    public function getQuery()
    {
        # making sure the startRecord/maximumRecord combo does
        # not trigger an exception.
        if ($this->availablestartrecords < $this->parameters["maximumRecords"]) {
            $this->parameters["maximumRecords"] = $this->availablestartrecords;
        }

        # setting arguments
        $arguments = [];
        foreach ($this->parameters as $key => $value) {
            $arguments[] = $key . "=" . $value;
        }

        # initial path and query
        $query = $this->path . "?" . implode("&", $arguments);

        # adding x-recordSchema's
        foreach (array_unique($this->recordschemas) as $recordschema) {
            $query .= "&x-recordSchema=" . $recordschema;
        }

        return $query;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getRequestUrl()
    {
        return $this->request;
    }


    /**
     * Combines set host with query url, executes query and
     * stores raw result in self::response and request in
     * self::request.
     *
     * @return bool|string
     * @throws \Exception
     */
    public function search()
    {
        $this->request = $this->baseurl . $this->getQuery();

        $curl = curl_init($this->request);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "gzip,deflate");
        $this->response = curl_exec($curl);

        if (!$this->response) {
            if (curl_errno($curl) == 56) {
                # Failure with receiving network data, could be a 403
                if ($this->curlretries < $this->maxcurlretries) {
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
}
