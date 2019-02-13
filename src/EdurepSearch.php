<?php
namespace Kennisnet\Edurep;

//(overleg div over exception aan kapstok)

class EdurepSearch
{
    const EDUREP_MAX_STARTRECORD = 1000;

    const SEARCHTYPE_LOM = 'lom';
    const SEARCHTYPE_SMO = 'smo';
    const SEARCHTYPE_PLUS = 'plus';

    private $config;

    # contains the raw curl response
    private $response = "";

    # default search parameters, optional ones can be set by setParameter
    private $parameters = [
        "operation"     => "searchRetrieve",
        "version"       => "1.2",
        "recordPacking" => "xml", //TODO string ?
        "query"         => "",
        "maximumRecords" => 100
    ];

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
     * @param int $value
     * @return $this
     */
    public function setMaximumRecords(int $value)
    {
        if ($value >= 0 && $value <= 100) {
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
        $this->parameters["query"] = urlencode($value);;
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
            $this->availablestartrecords = self::EDUREP_MAX_STARTRECORD - $value;
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
        if (!empty($recordschemas)) {
            $parameters["x-recordSchemas"] = array_unique($this->recordschemas);
        }
        return $parameters;
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

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param null $searchType
     * @return string
     * @throws \Exception
     */
    public function getRequestUrl($searchType = null)
    {
        if (!$this->parameters['query']) {
            throw new \Exception('Missing query');
        }

        return $this->config->getBaseUrl() . $this->getQuery($searchType);
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
}
