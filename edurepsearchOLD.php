<?php

/**
 * PHP package for interfacing with the Edurep search engine.
 *
 * @link http://developers.wiki.kennisnet.nl/index.php/Edurep:Hoofdpagina
 * @example phpEdurepSearch/example.php
 * @author Wim Muskee <wimmuskee@gmail.com>
 *
 *
 * Copyright 2012-2019 Stichting Kennisnet
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */
class EdurepSearchOld
{
    # contains the query part, excluding the host
    public $query = "";

    # contains the raw curl request, the url
    public $request = "";

    # contains the raw curl response
    public $response = "";

    # baseurl for edurep production
    private $baseurl = "http://wszoeken.edurep.kennisnet.nl:8000/";

    # search path
    private $path = "edurep/sruns";

    # default search parameters, optional ones can be set by setParameter
    private $parameters
        = [
            "operation"     => "searchRetrieve",
            "version"       => "1.2",
            "recordPacking" => "xml",
            "x-api-key"     => "",
            "query"         => "edurep"];

    # extra record schema's
    private $recordschemas = [];

    # maximum startRecord allowed by Edurep
    public $maxstartrecord = 1000;

    # internal counter for available startrecords
    private $availablestartrecords = 1000;

    # internal counter for curl retries
    private $curlretries = 0;

    # curl retries before an exception is thrown
    private $maxcurlretries = 3;


    public function __construct($api_key)
    {
        if (!empty($api_key)) {
            $this->parameters["x-api-key"] = $api_key;
        } else {
            throw new UnexpectedValueException("Use a valid Edurep API key", 21);
        }
    }

    /*
     * This function is a wrapper to set the query from the
     * parameters and execute the query to the server.
     */
    public function search()
    {
        $this->setQuery();
        $this->executeQuery();
    }

    /*
     * Simulate a search that was cached by the requesting
     * application. The application got the results from a local
     * cache, but performs an Edurep query to let Edurep keep
     * the statistics.
     * The exact same query as in the instance is fired, but with
     * 0 records and an x-cache parameter.
     */
    public function cacheSearch()
    {
        if (!empty($this->query)) {
            $this->query                        = "";
            $this->parameters["maximumRecords"] = 0;
            $this->parameters["x-cache"]        = 1;
            $this->search();
            $this->executeQuery();
        }
    }

    /**
     * Set Edurep parameters for the request.
     * The query parameter should be provided urldecoded.
     *
     * @param string $key Edurep parameter.
     * @param string $value Value for parameters, urlencoded.
     */
    public function setParameter($key, $value)
    {
        switch ($key) {
            case "maximumRecords":
                if ($value >= 0 && $value <= 100) {
                    $this->parameters[$key] = $value;
                } else {
                    throw new UnexpectedValueException("The value for maximumRecords should be between 0 and 100.", 22);
                }
                break;

            case "recordSchema":
            case "x-term-drilldown":
            case "sortKeys":
                $this->parameters[$key] = $value;
                break;

            case "query":
                $this->parameters[$key] = urlencode($value);
                break;

            case "startRecord":
                if ($value >= 1 && $value <= $this->maxstartrecord) {
                    $this->parameters[$key]      = $value;
                    $this->availablestartrecords = $this->maxstartrecord - $value;
                } else {
                    throw new UnexpectedValueException("The value for startRecords should be between 1 and " . $this->maxstartrecord . ".", 23);
                }
                break;

            case "x-recordSchema":
                $this->recordschemas[] = $value;
                break;

            default:
                throw new InvalidArgumentException("Unsupported Edurep parameter: " . $key, 1);
        }
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
     * Set another baseurl, for instance to Edurep Staging.
     *
     * @param string $baseurl An Edurep baseurl (including port)
     */
    public function setBaseurl($baseurl)
    {
        $this->baseurl = $baseurl;
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
     * Returns the Edurep query url. If the query
     * is not generated, it will be.
     *
     * @return string Query url without host.
     */
    public function getQuery()
    {
        if (empty($this->query)) {
            $this->setQuery();
        }
        return $this->query;
    }

    /**
     * Create an Edurep query url when empty. This url does
     * not contain the host (added in curl request). It makes sure
     * the startRecord does not exceed the Edurep maximum.
     */
    private function setQuery()
    {
        if (!empty($this->query)) {
            return true;
        }

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
        $this->query = $this->path . "?" . implode("&", $arguments);

        # adding x-recordSchema's
        foreach (array_unique($this->recordschemas) as $recordschema) {
            $this->query .= "&x-recordSchema=" . $recordschema;
        }
    }

    /**
     * Combines set host with query url, executes query and
     * stores raw result in self::response and request in
     * self::request.
     *
     * @param string $query Edurep query url without host.
     */
    private function executeQuery()
    {
        $this->request = $this->baseurl . $this->query;

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
                    $this->executeQuery($this->query);
                } else {
                    throw new NetworkException(curl_error($curl));
                }
            } else {
                throw new NetworkException(curl_error($curl));
            }
        }

        curl_close($curl);
    }
}


class NetworkException extends Exception
{
    public function __construct($message)
    {
        parent::__construct($message, 2);
    }
}

class XmlException extends Exception
{
    public function __construct()
    {
        parent::__construct("Error on creating SimpleXML object.", 3);
    }
}
