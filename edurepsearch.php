<?php
/**
 * PHP package for interfacing with the Edurep search engine.
 *
 * @version 0.31.3
 * @link http://developers.wiki.kennisnet.nl/index.php/Edurep:Hoofdpagina
 * @example phpEdurepSearch/example.php
 *
 * @todo srw interface
 * @todo more source code comments
 * @todo full result support for lom
 * @todo full result support for smo
 * @todo select language attribute to return
 * 
 * Copyright 2012-2014 Wim Muskee <wimmuskee@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
class EdurepSearch
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
	private $parameters = array(
		"operation" => "searchRetrieve",
		"version" => "1.2",
		"recordPacking" => "xml",
		"x-api-key" => "",
		"query" => "edurep" );

	# extra record schema's
	private $recordschemas = array();

	# maximum startRecord allowed by Edurep
	public $maxstartrecord = 1000;
	
	# internal counter for available startrecords
	private $availablestartrecords = 1000;

	# internal counter for curl retries
	private $curlretries = 0;

	# curl retries before an exception is thrown
	private $maxcurlretries = 3;


	public function __construct( $api_key ) {
		if ( !empty( $api_key ) ) {
			$this->parameters["x-api-key"] = $api_key;
		}
		else {
			throw new UnexpectedValueException( "Use a valid Edurep API key", 21 );
		}
	}

	/*
	 * This function is a wrapper to set the query from the
	 * parameters and execute the query to the server.
	 */
	public function search() {
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
	public function cacheSearch() {
		if ( !empty( $this->query ) ) { 
			$this->query = "";
			$this->parameters["maximumRecords"] = 0;
			$this->parameters["x-cache"] = 1;
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
	public function setParameter( $key, $value )
	{
		switch ( $key )
		{
			case "maximumRecords":
			if ( $value >= 0 && $value <= 100 ) {
				$this->parameters[$key] = $value;
			}
			else {
				throw new UnexpectedValueException( "The value for maximumRecords should be between 0 and 100.", 22 );
			}
			break;
			
			case "recordSchema":
			case "x-term-drilldown":
			case "sortKeys":
			$this->parameters[$key] = $value;
			break;

			case "query":
			$this->parameters[$key] = urlencode( $value );
			break;

			case "startRecord":
			if ( $value >= 1 && $value <= 4000 ) {
				$this->parameters[$key] = $value;
				$this->availablestartrecords = $this->maxstartrecord - $value;
			}
			else {
				throw new UnexpectedValueException( "The value for startRecords should be between 1 and ".$this->maxstartrecord.".", 23 );
			}
			break;

			case "x-recordSchema":
			$this->recordschemas[] = $value;
			break;

			default:
			throw new InvalidArgumentException( "Unsupported Edurep parameter: ".$key, 1 );
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
		if ( !empty( $recordschemas ) )
		{
			$parameters["x-recordSchemas"] = array_unique( $this->recordschemas );
		}
		return $parameters;
	}
	
	/**
	 * Set another baseurl, for instance to Edurep Staging.
	 *
	 * @param string $baseurl An Edurep baseurl (including port)
	 */
	public function setBaseurl( $baseurl )
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
	public function setSearchType( $type ) {
		switch ( $type ) {
			case "lom": $this->path = "edurep/sruns"; break;
			case "smo": $this->path = "smo/sruns"; break;
			default: $this->path = "edurep/sruns";
		}
	}

	public function setRecordpacking( $recordpacking )
	{
		$this->parameters["recordPacking"] = $recordpacking;
	}

	/**
	 * Returns the Edurep query url. If the query
	 * is not generated, it will be.
	 *
	 * @return string Query url without host.
	 */
	public function getQuery() {
		if ( empty( $this->query ) ) {
			$this->setQuery();
		}
		return $this->query;
	}
	
	/**
	 * Create an Edurep query url when empty. This url does
	 * not contain the host (added in curl request). It makes sure
	 * the startRecord does not exceed the Edurep maximum.
	 */
	private function setQuery() {
		if ( !empty($this->query ) ) {
			return TRUE;
		}

		# making sure the startRecord/maximumRecord combo does
		# not trigger an exception.
		if ( $this->availablestartrecords < $this->parameters["maximumRecords"] ) {
			$this->parameters["maximumRecords"] = $this->availablestartrecords;
		}

		# setting arguments
		$arguments = array();
		foreach ( $this->parameters as $key => $value ) {
			$arguments[] = $key."=".$value;
		}
		
		# initial path and query
		$this->query = $this->path."?".implode( "&", $arguments );
		
		# adding x-recordSchema's
		foreach ( array_unique( $this->recordschemas ) as $recordschema ) {
			$this->query .= "&x-recordSchema=".$recordschema;
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
		$this->request = $this->baseurl.$this->query;

		$curl = curl_init( $this->request );
		curl_setopt( $curl, CURLOPT_HEADER, FALSE );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $curl, CURLOPT_ENCODING, "gzip,deflate" );
		$this->response = curl_exec( $curl );

		if ( !$this->response )
		{
			if ( curl_errno( $curl ) == 56 )
			{
				# Failure with receiving network data, could be a 403
				if ( $this->curlretries < $this->maxcurlretries )
				{
					sleep( 1 );
					$this->curlretries++;
					$this->executeQuery( $query );
				}
				else {
					throw new NetworkException( curl_error( $curl ) );
				}
			}
			else {
				throw new NetworkException( curl_error( $curl ) );
			}
		}

		curl_close( $curl );
	}
}

class EdurepResults
{
	# public result vars
	public $recordcount = 0;
	public $pagesize = 10;
	public $startrecord = 0;
	public $nextrecord = 0;
	public $records = array();
	public $drilldowns = array();
	public $navigation = array();

	# private result vars
	private $recordSchema = "";
	private $xrecordSchemas = array();

	# maximum startRecord allowed by Edurep
	private $maxstartrecord = 4000;

	# namespaces used in edurep results
	private $namespaces = array(
		"local:recorddata" => "rd",
		"http://www.loc.gov/zing/srw/" => "srw",
		"http://www.loc.gov/zing/srw/diagnostic/" => "diag",
		"http://www.imsglobal.org/xsd/imsmd_v1p2" => "lom",
		"http://purl.org/dc/elements/1.1/" => "dc",
		"http://www.openarchives.org/OAI/2.0/oai_dc/" => "oai_dc",
		"http://edurep.cq2.org/extra" => "extra",
		"http://meresco.org/namespace/harvester/meta" => "meta",
		"http://meresco.org/namespace/drilldown" => "dd",
		"http://xsd.kennisnet.nl/smd/sad" => "sad",
		"http://xsd.kennisnet.nl/smd/1.0/" => "smo",
		"http://xsd.kennisnet.nl/smd/hreview/1.0/" => "hr" );

	# type definition for each lom field
	private $lom_template = array(
		"title" => "",
		"description" => "",
		"keyword" => array(),
		"language" => array(),
		"aggregationlevel" => "",
		"publisher" => array(),
		"author" => array(),
		"location" => "",
		"format" => "",
		"duration" => -1,
		"learningresourcetype" => array(),
		"intendedenduserrole" => array(),
		"context" => array(),
		"typicalagerange" => "",
		"typicallearningtime" => -1,
		"cost" => "",
		"copyright" => "",
		"rights" => "",
		"competency" => array(),
		"discipline" => array(),
		"educationallevel" => array(),
		"educationalobjective" => array(),
		"time" => -1,
		"embed" => "",
		"thumbnail" => "",
		"icon" => "",
		"previewimage" => "",
		"landingpage" => "",
		"hasformat" => array());

	# type definition for each smo field
	private $smo_template = array(
		"smoid" => "",
		"supplierid" => "",
		"userid" => "",
		"identifier" => "",
		"summary" => "",
		"dtreviewed" => "",
		"rating" => -1,
		"worst" => -1,
		"best" => -1,
		"description" => -1 );

	# valid lom contribute roles to check in extra record
	private $contribute_roles = array(
		"author", 
		"publisher" );

	# valid lom purpose types to check in extra record
	private $purpose_types = array(
		"competency", 
		"discipline",
		"educationallevel",
		"educationalobjective" );

	# defines how a lom record maps on the object record
	private $mapping_lom = array(
		"title" => "general.title.langstring",
		"description" => "general.description.langstring",
		"keyword" => "general.keyword.langstring",
		"language" => "general.language",
		"aggregationlevel" => "general.aggregationlevel.value.langstring",
		"location" => "technical.location",
		"format" => "technical.format",
		"duration" => "technical.duration.datetime",
		"learningresourcetype" => "educational.learningresourcetype.value.langstring",
		"intendedenduserrole" => "educational.intendedenduserrole.value.langstring",
		"context" => "educational.context.value.langstring",
		"typicalagerange" => "educational.typicalagerange.langstring",
		"typicallearningtime" => "educational.typicallearningtime.datetime",
		"cost" => "rights.cost.value.langstring",
		"copyright" => "rights.copyrightandotherrestrictions.value.langstring",
		"rights" => "rights.description.langstring" );

	# defines how a dc record maps on the object record
	private $mapping_dc = array(
		"title" => "title",
		"description" => "description",
		"keyword" => "subject",
		"language" => "language",
		"publisher" => "publisher",
		"author" => "creator",
		"location" => "identifier",
		"format" => "format",
		"rights" => "rights" );

	# controls the texts for the textual navigation buttons
	private $navigation_text = array(
		"firstpage" => "<<",
		"previouspage" => "<",
		"currentpage" => "-",
		"nextpage" => ">",
		"lastpage" => ">>" );


	/**
	 * Loads the results from the Edurep XML string if the
	 * string is correct XML.
	 * Because the load() function is namespace dependent, and
	 * one element has no namespace, this is created manually.
	 *   
	 * @param string $xmlstring XML string.
	 */
	public function __construct( $xmlstring ) {
		# When available, include static include.
		if ( file_exists( dirname(__FILE__)."/edurepsearch.inc.php" ) ) {
			require_once( dirname(__FILE__)."/edurepsearch.inc.php" );
		}

		# set custom namespace in namespace-less element
		$xmlstring = str_replace( "<recordData ", "<recordData xmlns=\"local:recorddata\" ", $xmlstring );

		# create simple xml object
		$xml = simplexml_load_string( $xmlstring );

		if ( is_object( $xml ) ) {
			$this->loadObject( $this->load( $xml ) );
		}
		else {
			throw new XmlException();
		}
	}
	
	/**
	 * Loads the entire result object, also by calling various 
	 * support functions.
	 *
	 * @param array $array XML array.
	 */
	private function loadObject( $array ) {
		if ( array_key_exists( "diagnostics", $array ) ) {
			$this->getDiagnostics( $array["diagnostics"][0]["diagnostic"][0]["details"][0][0] );
		}

		$this->recordcount = (int) $array["numberOfRecords"][0][0];
		$this->pagesize = (int) $array["echoedSearchRetrieveRequest"][0]["maximumRecords"][0][0];
		$this->startrecord = (int) $array["echoedSearchRetrieveRequest"][0]["startRecord"][0][0];
		$this->nextrecord = ( array_key_exists( "nextRecordPosition", $array ) ? (int) $array["nextRecordPosition"][0][0] : 0 );
		$this->recordSchema = $array["echoedSearchRetrieveRequest"][0]["recordSchema"][0][0];

		if ( $this->recordcount > 0 && $this->pagesize > 0 ) {
			$this->setNavigation();

			# get optional x-recordSchemas
			if ( array_key_exists( "x-recordSchema", $array["echoedSearchRetrieveRequest"][0] ) ) {
				foreach( $array["echoedSearchRetrieveRequest"][0]["x-recordSchema"] as $xrecordschema ) {
					$this->xrecordSchemas[] = $xrecordschema[0];
				}
			}

			# get records
			foreach ( $array["records"][0]["record"] as $record_array ) {
				# create basic recorddata, either lom, dc or smo
				switch ( $this->recordSchema ) {
					case "lom":
					$record = $this->lom_template;
					$id_separator = ":";
					$record = array_merge( $record, $this->getLomRecord( $record_array["recordData"][0]["lom"][0] ) );
					break;
	
					case "oai_dc":
					$record = $this->lom_template;
					$id_separator = ":";
					$record = array_merge( $record, $this->getDcRecord( $record_array["recordData"][0]["dc"][0] ) );
					break;

					case "smo":
					$record = $this->smo_template;
					$id_separator = ".";
					$record = array_merge( $record, $this->getSmoRecord( $record_array["recordData"][0]["smo"][0] ) );
					break;
				}

				# srw identifier info
				$record["recordidentifier"] = $record_array["recordIdentifier"][0][0];
				$record["repository"] = substr( $record["recordidentifier"], 0, strpos( $record["recordidentifier"], $id_separator ) );

				# merge duration fields
				# execute before extra merge so technical duration won't overwrite typicallearningtime
				$record = $this->normalizeDurations( $record );

				# merge copyrightandotherrestrictions and rights description
				$record = $this->normalizeRights( $record );

				# merge optional extra data
				if ( in_array( "extra", $this->xrecordSchemas ) )  {
					$pos = array_search( "extra", $this->xrecordSchemas );
					$record = array_merge( $record, $this->getExtraData( $record_array["extraRecordData"][0]["recordData"][$pos]["extra"][0] ) );
				}

				# merge optional smbAggregatedData
				if ( in_array( "smbAggregatedData", $this->xrecordSchemas ) ) {
					$pos = array_search( "smbAggregatedData", $this->xrecordSchemas );
					$record = array_merge( $record, $this->getSmbAggregatedData( $record_array["extraRecordData"][0]["recordData"][$pos]["smbAggregatedData"][0] ) );
				}

				# merge optional smo's
				if ( in_array( "smo", $this->xrecordSchemas ) ) {
					$pos = array_search( "smo", $this->xrecordSchemas );
					if ( array_key_exists( "smo", $record_array["extraRecordData"][0]["recordData"][$pos] ) ) {
						$record = array_merge( $record, $this->getSmos( $record_array["extraRecordData"][0]["recordData"][$pos] ) );
					}
				}

				$this->records[] = $record;
			}
		}

		# get optional drilldowns
		if ( array_key_exists( "extraResponseData", $array ) && array_key_exists( "drilldown", $array["extraResponseData"][0] ) ) {
			foreach ( $array["extraResponseData"][0]["drilldown"][0]["term-drilldown"][0]["navigator"] as $navigator ) {
				if ( array_key_exists( "item", $navigator ) ) {
					foreach ( $navigator["item"] as $item ) {
						$counts[$item[0]] = (int) $item["@attributes"]["count"];
					}
					$this->drilldowns[$navigator["@attributes"]["name"]] = $counts;
					unset( $counts );
				}
			}
		}
	}

	/**
	 * Intended to retrieve different exceptions from
	 * a bad query.
	 *
	 * @param string $details Diagnostics details string.
	 */
	private function getDiagnostics( $details ) {
		if ( substr_count($details, 'Postfix', 0, 7) == 1 ) {
			throw new UnexpectedValueException( "Postfix query not allowed.", 25 );
		}
		elseif ( substr_count($details, 'Prefix query only allowed with one wildcard', 0, 43) == 1 ) {
			throw new UnexpectedValueException( "Prefix query only allowed with one wildcard.", 26 );
		}
		elseif ( substr_count($details, 'Prefix query only allowed with a minimum of 2', 0, 45) == 1 ) {
			throw new UnexpectedValueException( "Prefix query only allowed with a minimum of 2 characters.", 27 );
		}
		else {
			throw new UnexpectedValueException( "Error in Edurep query: ".$details, 26 );
		}
		throw new UnexpectedValueException( "Error in Edurep query: ".$details, 24 );
	}

	/**
	 * Get entire LOM record.
	 */
	private function getLomRecord( $record_array )
	{
		$record = array();
		
		foreach ( $this->mapping_lom as $record_key => $mapping_key ) {
			# first, set individual fields
			$field_sections = explode( ".", $mapping_key );
			$lom_category = $field_sections[0];
			$lom_field = $field_sections[1];
			$field_count = count( $field_sections );
			
			if ( $field_count > 2 ) {
				# either langstring or datetime
				$field_content = $field_sections[2];
			}
			
			# break if category doesn't exists
			if ( !array_key_exists( $lom_category, $record_array ) ) {
				break;
			}
			
			# three types of fields, langstring, vocabularyvalues and none of these
			# two types of returns, single or multiple
			if ( array_key_exists( $lom_field, $record_array[$lom_category][0] ) ) {
				$field_array = $record_array[$lom_category][0][$lom_field];
				
				if ( is_string( $this->lom_template[$record_key] ) || is_int( $this->lom_template[$record_key] ) ) {
					switch( $field_count ) {
						case 2: $record[$record_key] = $field_array[0][0]; break;
						case 3: $record[$record_key] = $field_array[0][$field_content][0][0]; break;
						case 4: $record[$record_key] = $field_array[0]["value"][0]["langstring"][0][0]; break;
					}
				}
				else {
					switch( $field_count ) {
						case 2:
							foreach ( $field_array as $value) {
								if ( !empty( $value[0] ) ) {
								$record[$record_key][] = $value[0];
								}
							} 
						break;
						
						case 3:
							foreach ( $field_array as $value ) {
								if ( !empty($value[$field_content][0][0]) ) {
								$record[$record_key][] = $value[$field_content][0][0];
								}
							}
						break;
						
						case 4:
							foreach ( $field_array as $value ) {
								if (!empty($value["value"][0]["langstring"][0][0])) {
								$record[$record_key][] = $value["value"][0]["langstring"][0][0];
								}
							}
						break;
					}
				}
			}
		}

		# include video embed, icon, landingpage and image thumbnail links
		# put different formats in hasformat array
		# merge icon and thumbnail into generic preview-image
		if ( array_key_exists( "relation", $record_array ) ) {
			$valid_types = array( "embed", "icon", "thumbnail", "landingpage" );
			
			foreach( $record_array["relation"] as $relation ) {
				$type = $relation["kind"][0]["value"][0]["langstring"][0][0];
				$value = $relation["resource"][0]["catalogentry"][0]["entry"][0]["langstring"][0][0];
				
				if ( in_array( $type, $valid_types ) && !empty( $value ) ) {
					$record[$type] = $value;
				}
				
				if ( $type == "hasformat" && !empty( $value ) && array_key_exists( "description", $relation["resource"][0] ) ) {
					$format = $relation["resource"][0]["description"][0]["langstring"][0][0];
					if ( !empty( $format ) ) {
						$record["hasformat"][$format] = $value;
					}
				}
			}
			# merge
			if ( !empty( $record["icon"] ) ) {
				$record["previewimage"] = $record["icon"];
			}
			if ( !empty( $record["thumbnail"] ) ) {
				$record["previewimage"] = $record["thumbnail"];
			}
		}
		return $record;
	}

	// walk across record_array and fill record 
	// according to record template and mapping
	private function getDcRecord( $record_array )
	{
		$record = array();

		foreach ( $this->mapping_dc as $record_key => $mapping_key )
		{
			if ( array_key_exists( $mapping_key, $record_array ) )
			{
				if ( is_string( $this->lom_template[$record_key] ) )
				{
					$record[$record_key] = $record_array[$mapping_key][0][0];
				}
				else
				{
					# field type is array
					foreach ( $record_array[$mapping_key] as $value )
					{
						$record[$record_key][] = $value[0];
					}
				}
			}
		}
		return $record;
	}

	// walk across record_array and fill record 
	// also used by getSmos
	private function getSmoRecord( $record_array )
	{
		$record = array();
		
		$record["smoid"] = $record_array["smoId"][0][0];
		$record["supplierid"] = $record_array["supplierId"][0][0];
		$record["identifier"] = $record_array["hReview"][0]["info"][0][0];
		
		# optional fields
		if ( array_key_exists( "userId", $record_array ) )
		{
			$record["userid"] =  $record_array["userId"][0][0];
		}
		
		$hreviewfields = array( "summary", "dtreviewed", "rating", "worst", "best", "description" );
		foreach ( $hreviewfields as $field )
		{
			if ( array_key_exists( $field, $record_array["hReview"][0] ) )
			{
				$record[$field] = $record_array["hReview"][0][$field][0][0];
			}
		}
		return $record;
	}

	private function getExtraData( $array )
	{
		$extra = array();
		
		foreach( $array as $category => $field )
		{
			switch( $category )
			{
				case "lifeCycle":
				$extra = array_merge( $extra, $this->getExtraContributes( $field[0]["contribute"] ) );
				break;
				
				case "educational":
				$extra = array_merge( $extra, $this->getExtraEducationals( $field ) );
				break;
				
				case "classification":
				$extra = array_merge( $extra, $this->getExtraClassifications( $field ) );
				break;
			}
		}
		return $extra;
	}

	private function getSmbAggregatedData( $array ) {
		$sad["nrofreviews"] = (int) $array["numberOfReviews"][0][0];
		$sad["nrofratings"] = (int) $array["numberOfRatings"][0][0];
		$sad["nroftags"] = (int) $array["numberOfTags"][0][0];
		$sad["rating"] = (float) $array["averageNormalizedRating"][0][0];
		return $sad;
	}

	private function getSmos( $array )
	{
		$record["smo"] = array();
		
		foreach( $array["smo"] as $smo )
		{
			$record["smo"][] = array_merge( $this->smo_template, $this->getSmoRecord( $smo ) );
		}
		return $record;
	}

	/**
	 * Normalizes PT duration fields from technical duration and
	 * typicallearningtime.
	 *
	 * @param array $record Partial record array.
	 * @return array $record Partial record array.
	 */
	private function normalizeDurations( $record ) {
		$record = $this->normalizeDuration( $record, "duration" );
		$record = $this->normalizeDuration( $record, "typicallearningtime" );
		return $record;
	}

	/**
	 * Normalizes values from copyrightandotherrestrictions and
	 * rights description fields.
	 *
	 * @param array $record Partial record array.
	 * @return array $record Partial record array.
	 */
	private function normalizeRights( $record ) {
		# If copyright is a non-standard value, always overwrite rights when available in static include
		if ( !empty( $record["copyright"] ) && $record["copyright"] != "yes" && $record["copyright"] != "no" ) {
			if ( array_key_exists("voc_rights", $GLOBALS ) && array_key_exists( $record["copyright"], $GLOBALS["voc_rights"] ) ) {
				$record["rights"] = $GLOBALS["voc_rights"][$record["copyright"]];
			}
			else {
				$record["rights"] = $record["copyright"];
			}
		}

		# for DC output there is no copyright
		if ( empty( $record["copyright"] ) && !empty( $record["rights"] ) ) {
			$record["copyright"] = "yes";
		}
		return $record;
	}

	/**
	 * Normalizes PT duration fields to seconds, and fill
	 * aggregated object time.
	 *
	 * @param array $record Partial record array.
	 * @param string $field Which duration field.
	 * @return array $record Partial record array.
	 */
	private function normalizeDuration( $record, $field ) {
		if ( array_key_exists( $field, $record ) && substr( $record[$field], 0, 2 ) == "PT" ) {
			$time = $this->durationToSeconds( $record[$field] );

			if ( !empty( $time ) ) {
				$record[$field] = $time;
				$record["time"] = $time;
			}
			else {
				$record[$field] = -1;
			}
		}
		else {
			$record[$field] = -1;
		}
		return $record;
	}

	/**
	 * Converts PT duration format to seconds.
	 *
	 * @param string $pt_time Duration in PT format.
	 * @return integer $seconds PT duration format converted to seconds.
	 */
	private function durationToSeconds( $pt_time ) {
		$interval = new DateInterval( $pt_time );
		return ($interval->y * 365 * 24 * 60 * 60) +
			($interval->m * 30 * 24 * 60 * 60) +
			($interval->d * 24 * 60 * 60) +
			($interval->h * 60 * 60) +
			($interval->i * 60) +
			$interval->s;
	}

	/**
	 * Sets page navigation values. In the navigation
	 * array, pages are mapped with startrecord values.
	 * This function makes sure that no unavailable navigation
	 * options are generated due to Edurep's max startRecord.
	 */
	private function setNavigation()
	{
		$nr_of_pages = ceil( $this->recordcount/$this->pagesize );
		$current_page = ( empty( $this->nextrecord ) ? $nr_of_pages : ceil( ($this->nextrecord - 1)/$this->pagesize ) );

		$this->navigation[$this->navigation_text["firstpage"]] = 1;

		if ( $current_page > 1 ) {
			$this->navigation[$this->navigation_text["previouspage"]] = $this->selectStartrecord( $current_page - 1 );
		}

		if ( $current_page > 2 ) {
			$this->navigation[$current_page - 2] = $this->selectStartrecord( $current_page - 2 );
		}

		if ( $current_page > 1 ) {
			$this->navigation[$current_page - 1] = $this->selectStartrecord( $current_page - 1 );
        }
		
		$this->navigation[$this->navigation_text["currentpage"]] = $this->startrecord;

		if ( $current_page < $nr_of_pages ) {
			$this->navigation[$current_page + 1] = $this->nextrecord;
		}

		if ( $current_page < $nr_of_pages - 1 && $this->nextrecord < $this->maxstartrecord  ) {
			$this->navigation[$current_page + 2] = $this->selectStartrecord( $current_page + 2 );
		}

		if ( $current_page < $nr_of_pages ) {
			$this->navigation[$this->navigation_text["nextpage"]] = $this->nextrecord;
		}

		if ( $this->selectStartrecord( $nr_of_pages ) <= $this->maxstartrecord ) {
			$this->navigation[$this->navigation_text["lastpage"]] = $this->selectStartrecord( $nr_of_pages );
		}
	}

	/**
	 * Contribute helper function for getExtraData() 
	 * For each role, all names are stored in the name-key array,
	 * while any dates are aggregated into a single value in the
	 * timestamp and datetime keys. The latest datetime is chosen.
	 * Datetimes with timezones and without can be parsed. All others
	 * get timestamp 0.
	 * 
	 * @param array $contribute An xml array of the contributes.
	 * @return array $extra Result array part to be merged.
	 */
	private function getExtraContributes( $contributes ) {
		$extra = array();
		
		foreach( $this->contribute_roles as $role ) {
			# set working attributes
			$extra[$role]["name"] = array();
			$extra[$role]["datetime"] = array();
			$extra[$role]["timestamp"] = array();
			
			# retrieve the data for the role
			foreach( $contributes as $contribute ) {
				if ( array_key_exists( $role, $contribute ) ) {
					foreach ( $contribute[$role] as $entity ) {
						if ( array_key_exists( "name", $entity ) ) {
							$extra[$role]["name"][] = $entity["name"][0][0];
						}
						if ( array_key_exists( "dateTime", $entity ) ) {
							# save timestamp for easy sorting
							# convert back into datetime later on
							if ( DateTime::createFromFormat("Y-m-d\TH:i:sP", $entity["dateTime"][0][0] ) ) { 
								$date = DateTime::createFromFormat("Y-m-d\TH:i:sP", $entity["dateTime"][0][0] );
								$extra[$role]["timestamp"][] = $date->getTimestamp();
							}
							elseif ( DateTime::createFromFormat("Y-m-d\TH:i:s", $entity["dateTime"][0][0] ) ) {
								$date = DateTime::createFromFormat("Y-m-d\TH:i:s", $entity["dateTime"][0][0] );
								$extra[$role]["timestamp"][] = $date->getTimestamp();
							}
							else {
								$extra[$role]["timestamp"][] = 0;
							}
						}
					}
				}
			}
			
			# only select the latest datestamp for each role
			if ( !empty( $extra[$role]["timestamp"] ) ) {
				sort( $extra[$role]["timestamp"], SORT_NUMERIC );
				$extra[$role]["timestamp"] = array_pop( $extra[$role]["timestamp"] );
				$extra[$role]["datetime"] = date( "Y-m-d\TH:i:s", $extra[$role]["timestamp"] );
			}
			else {
				# change working array into default empty string
				$extra[$role]["timestamp"] = -1;
				$extra[$role]["datetime"] = "";
			}  
		}

		return $extra;
	}

	/**
	 * Educational helper function for getExtraData() 
	 * More educationals can exists, but some values get
	 * overwritten.
	 * 
	 * @param array $educational An xml array of the educationals.
	 * @return array $extra Result array part to be merged.
	 */
	private function getExtraEducationals( $educationals )
	{
		$extra = array();
		
		foreach( $educationals as $educational )
		{
			if ( array_key_exists( "typicalLearningTime", $educational ) )
			{
				$extra["typicallearningtime"] = (int) $educational["typicalLearningTime"][0]["duration"][0][0];
				$extra["time"] = (int) $extra["typicallearningtime"];
			}
		}
		return $extra;
	}
	
	/**
	 * Classification helper function for getExtraData() 
	 * 
	 * @param array $classification An xml array of the classifications.
	 * @return array $extra Result array part to be merged.
	 */
	private function getExtraClassifications( $classifications )
	{
		$extra = array();
		
		foreach( $this->purpose_types as $purpose )
		{
			foreach( $classifications as $classification )
			{
				if ( array_key_exists( $purpose, $classification ) )
				{
					foreach( $classification[$purpose] as $taxon )
					{
						if ( array_key_exists( "id", $taxon ) )
						{
							$id = $taxon["id"][0][0];
							if ( preg_match( "/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i", $id ) )
							{
								$entry = ( array_key_exists( "entry", $taxon ) ? $taxon["entry"][0][0] : "" );
								$extra[$purpose][$id] = $entry;
							}
						}
					}
				}
			}
		}
		return $extra;
	}
	
	/**
	 * Helper function for setNavigation() 
	 *
	 * @param integer $page The result page number.
	 * @return integer $startrecord The startrecord for that page.
	 */
	private function selectStartrecord( $page )
	{
		return ( $page * $this->pagesize ) - ( $this->pagesize - 1 );
	}
	
	/**
	 * Loads raw xml (with different namespaces) into array. 
	 * Keeps attributes without namespace or xml prefix.
	 * 
	 * @param object $xml SimpleXML object.
	 * @return array $array XML array.
	 * @see http://www.php.net/manual/en/ref.simplexml.php#52512
	 */
	private function load( $xml )
	{
		$fils = 0;
		$array = array();

		foreach( $this->namespaces as $uri => $prefix )
		{   
			foreach( $xml->children($uri) as $key => $value )
			{   
				$child = $this->load( $value );

				// To deal with the attributes, 
				// only works for attributes without a namespace, or in with xml namespace prefixes 
				if (count( $value->attributes() ) > 0  || count( $value->attributes("xml", TRUE) ) > 0 )
				{   
					$child["@attributes"] = $this->getAttributes( $value );
				}
				// Also add the namespace when there is one
				if ( !empty( $uri ) )
				{   
					$child["@namespace"] = $uri;
				}

				//Let see if the new child is not in the array
				if( !in_array( $key, array_keys($array) ) )
				{
					$array[$key] = NULL;
					$array[$key][] = $child;
				}
				else
				{   
					//Add an element in an existing array
					$array[$key][] = $child;
				}

				$fils++;
			}
		}

		# no container, returning value
		if ( $fils == 0 )
		{
			return array( (string) $xml );
		}

		return $array;
	}

	/**
	 * Support function for XML load function. Returns
	 * attribute parts for the XML array.
	 *
	 * @param object $xml SimpleXML object.
	 * @return array $array XML array.
	 */
	private function getAttributes( $xml )
	{
		foreach( $xml->attributes() as $key => $value )
		{
			$arr[$key] = (string) current( $value );
		}

		foreach( $xml->attributes( "xml", TRUE ) as $key => $value )
		{
			$arr[$key][] = (string) current( $value );
			$arr[$key]["@namespace"] = "http://www.w3.org/XML/1998/namespace";
		}

		return $arr;
	}
}

class NetworkException extends Exception {
	public function __construct( $message ) {
		parent::__construct( $message, 2 );
	}
}

class XmlException extends Exception {
	public function __construct() {
		parent::__construct( "Error on creating SimpleXML object.", 3 );
	}
}

?>
