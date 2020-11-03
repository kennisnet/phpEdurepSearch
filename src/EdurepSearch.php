<?php

namespace Kennisnet\Edurep;

use Exception;
use UnexpectedValueException;

class EdurepSearch
{
    const EDUREP_MAX_STARTRECORD = 1000;
    const MAX_RECORDS            = 100;

    const SEARCHTYPE_LOM  = 'lom';
    const SEARCHTYPE_SMO  = 'smo';
    const SEARCHTYPE_PLUS = 'plus';

    /**
     * @var SearchClient
     */
    protected $searchClient;

    /**
     * @var DefaultSearchConfig
     */
    private $config;

    /**
     * @var string
     */
    private $response = "";

    /**
     * @var array<string,string|int|null>
     */
    private $parameters = [
        "operation"      => "searchRetrieve",
        "version"        => "1.2",
        "recordPacking"  => "xml",
        "query"          => "",
        "maximumRecords" => self::MAX_RECORDS
    ];

    /**
     * @var string
     */
    private $searchTerm = '';

    /**
     * @var array<mixed>
     */
    private $queryFilterParts = [];

    /**
     * extra record schema's
     * @var array
     */
    private $recordschemas = [];

    /**
     * internal counter for available startrecords
     * @var int
     */
    private $availablestartrecords = 1000;

    public function __construct(DefaultSearchConfig $config, ?SearchClient $searchClient = null)
    {
        if ($searchClient === null) {
            $searchClient = new SearchClient();
        }
        $this->config       = $config;
        $this->searchClient = $searchClient;
    }

    /**
     * @param int $value
     *
     * @return self
     */
    public function setMaximumRecords(int $value): self
    {
        if ($value >= 0 && $value <= self::MAX_RECORDS) {
            $this->parameters["maximumRecords"] = $value;
        } else {
            throw new UnexpectedValueException("The value for maximumRecords should be between 0 and 100.", 22);
        }

        return $this;
    }

    public function setRecordpacking(string $recordPacking): self
    {
        $this->parameters["recordPacking"] = $recordPacking;

        return $this;
    }

    public function setRecordSchema(string $value): self
    {
        $this->parameters["recordSchema"] = $value;

        return $this;
    }

    public function setXtermDrilldown(string $value): self
    {
        $this->parameters["x-term-drilldown"] = $value;

        return $this;
    }

    public function setSortKeys(string $value): self
    {
        $this->parameters["sortKeys"] = $value;

        return $this;
    }

    public function setQuery(string $value): self
    {
        $this->searchTerm = urlencode(trim($value));

        return $this;
    }

    public function addFilterPart(string $filterId, string $comparator, string $value, string $connector = '+OR+'): self
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
     * @throws UnexpectedValueException
     */
    public function setStartRecord(int $value): self
    {
        if ($value >= 1 && $value <= self::EDUREP_MAX_STARTRECORD) {
            $this->parameters["startRecord"] = $value;
            $this->availablestartrecords     = self::EDUREP_MAX_STARTRECORD - $value;
        } else {
            throw new UnexpectedValueException("The value for startRecords should be between 1 and " . self::EDUREP_MAX_STARTRECORD . ".",
                                               23);
        }

        return $this;
    }

    public function addXRecordSchema(string $value): self
    {
        $this->recordschemas[] = $value;

        return $this;
    }

    /**
     * Retrieve all local parameters.
     *
     * @return array<string, array|int|string|null> All Edurep parameters and values
     */
    public function getParameters(): array
    {
        $parameters = $this->parameters;
        if (!empty($this->recordschemas)) {
            $parameters["x-recordSchemas"] = array_unique($this->recordschemas);
        }

        return $parameters;
    }

    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * @throws Exception
     */
    public function search(?string $searchType = null): string
    {
        $request = $this->getRequestUrl($searchType);
        $result  = $this->searchClient->executeQuery($request, $this->config->getMaxCurlRetries());

        $this->response = $result;

        return $this->response;
    }

    /**
     * @throws Exception
     */
    public function getRequestUrl(?string $searchType = null): string
    {
        if (!$this->searchTerm) {
            throw new Exception('Missing query');
        }

        return $this->config->getBaseUrl() . $this->getQuery($searchType);
    }

    public function getQuery(?string $searchType = null): string
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
