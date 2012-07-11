<?php
/**
 * PHP package for interfacing with the Edurep search engine.
 *
 * @version 0.6
 * @link http://edurepdiensten.wiki.kennisnet.nl
 *
 * @todo srw interface
 * @todo source code comments
 * @todo result support for lom
 * @todo prepare page nrs
 * 
 * Copyright 2012 Wim Muskee <wimmuskee@gmail.com>
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
	# contains the raw curl request, the url
	public $request = "";

	# contains the raw curl response
	public $response = "";

	# baseurl for edurep production
	private $baseurl = "http://wszoeken.edurep.kennisnet.nl:8000/";

	# default search parameters, optional ones can be set by setParameter
	private $parameters = array(
		"operation" => "searchRetrieve",
		"version" => "1.2",
		"recordPacking" => "xml",
		"x-api-key" => "",
		"query" => "edurep" );

	# extra record schema's
	private $recordschemas = array();

	# internal counter for curl retries
	private $curlretries = 0;

	# curl retries before an exception is thrown
	private $maxcurlretries = 3;


	public function __construct( $api_key )
	{
		if ( !empty( $api_key ) )
		{
			$this->parameters["x-api-key"] = $api_key;
		}
		else
		{
			throw new InvalidArgumentException( "Use a valid Edurep API key" );
		}
	}

	public function lomSearch()
	{
		$this->executeQuery( $this->getQuery( "edurep/sruns" ) );
	}

	public function smoSearch()
	{
		$this->executeQuery( $this->getQuery( "smo/sruns" ) );
	}

	public function setParameter( $key, $value )
	{
		switch ( $key )
		{
			case "query":
			case "maximumRecords":
			case "startRecord":
			case "recordSchema":
			case "x-term-drilldown":
			case "sortKeys":
			$this->parameters[$key] = $value;
			break;

			case "x-recordSchema":
			$this->recordschemas[] = $value;
			break;

			default:
			throw new InvalidArgumentException( "Unknown Edurep parameter: ".$key );
		}
	}

	public function loadParameters( $parameters )
	{
		foreach ( $parameters as $key => $value )
		{
			switch ( $key )
			{
				case "query":
				case "maximumRecords":
				case "startRecord":
				case "recordSchema":
				case "x-term-drilldown":
				case "sortKeys":
				$this->parameters[$key] = $value;
				break;

				case "x-recordSchemas":
				foreach( $value as $xrecordschema )
				{
					$this->recordschemas[] = $xrecordschema;
				}
				break;
			}
		}
	}

	public function getParameters()
	{
		$parameters = $this->parameters;
		if ( !empty( $recordschemas ) )
		{
			$parameters["x-recordSchemas"] = array_unique( $this->recordschemas );
		}
		return $parameters;
	}

	public function setBaseurl( $baseurl )
	{
		$this->baseurl = $baseurl;
	}
	
	public function setRecordpacking( $recordpacking )
	{
		$this->parameters["recordPacking"] = $recordpacking;
	}
	
	private function getQuery( $path )
	{
		# setting arguments
		$arguments = array();
		foreach ( $this->parameters as $key => $value )
		{
			$arguments[] = $key."=".$value;
		}
		
		# initial path and query
		$query = $path."?".implode( "&", $arguments );
		
		# adding x-recordSchema's
		foreach ( array_unique( $this->recordschemas ) as $recordschema )
		{
			$query .= "&x-recordSchema=".$recordschema;
		}
		
		return $query;
	}
	
	private function executeQuery( $query )
	{
		$this->request = $this->baseurl.$query;

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
				else
				{
					throw new Exception( "Curl error: ".curl_error( $curl ).", high server load." );
				}
			}
			else
			{
				throw new Exception( "Curl error: ".curl_error( $curl ) );
			}
		}

		curl_close( $curl );
	}
}

class EdurepResults
{
	# public result vars
	public $numberOfRecords = 0;
	public $nextRecordPosition = 0;
	public $records = array();
	public $drilldowns = array();

	# private result vars
	private $recordSchema = "";
	private $xrecordSchemas = array();

	# namespaces used in edurep results
	private $namespaces = array(
		"local:recorddata" => "rd",
		"http://www.loc.gov/zing/srw/" => "srw",
		"http://www.imsglobal.org/xsd/imsmd_v1p2" => "lom",
		"http://purl.org/dc/elements/1.1/" => "dc",
		"http://www.openarchives.org/OAI/2.0/oai_dc/" => "oai_dc",
		"http://meresco.org/namespace/harvester/meta" => "meta",
		"http://meresco.org/namespace/drilldown" => "dd",
		"http://xsd.kennisnet.nl/smd/sad" => "sad",
		"http://xsd.kennisnet.nl/smd/1.0/" => "smo",
		"http://xsd.kennisnet.nl/smd/hreview/1.0/" => "hr" );

	# type definition for each record field
	private $record_template = array(
		"title" => "",
		"description" => "",
		"keyword" => array(),
		"language" => "",
		"publisher" => "",
		"location" => "",
		"mimetype" => "" );

	# defines how a dc record maps on the object record
	private	$mapping_dc = array(
		"title" => "title",
		"description" => "description",
		"keyword" => "subject",
		"language" => "language",
		"publisher" => "publisher",
		"location" => "identifier",
		"mimetype" => "format" );


	public function __construct( $xmlstring )
	{
		# set custom namespace in namespace-less element
		$xmlstring = str_replace( "<recordData ", "<recordData xmlns=\"local:recorddata\" ", $xmlstring );

		# create simple xml object
		$xml = simplexml_load_string( $xmlstring );

		if ( !is_object( $xml ) )
		{
			throw new Exception( "Error on creating SimpleXML object." );
		}
		else
		{
			$this->loadObject( $this->load( $xml ) );
		}
	}
	
	private function loadObject( $array )
	{
		$this->numberOfRecords = $array["numberOfRecords"][0][0];
		$this->nextRecordPosition = ( array_key_exists( "nextRecordPosition", $array ) ? $array["nextRecordPosition"][0][0] : 0 );
		$this->recordSchema = $array["echoedSearchRetrieveRequest"][0]["recordSchema"][0][0];

		# get optional x-recordSchemas
		if ( array_key_exists( "x-recordSchema", $array["echoedSearchRetrieveRequest"][0] ) )
		{
			foreach( $array["echoedSearchRetrieveRequest"][0]["x-recordSchema"] as $xrecordschema )
			{
				$this->xrecordSchemas[] = $xrecordschema[0];
			}
		}

		# get records
		foreach ( $array["records"][0]["record"] as $record_array )
		{
			$record = array();
			$record["identifier"] = $record_array["recordIdentifier"][0][0];
			$record["repository"] = substr( $record["identifier"], 0, strpos( $record["identifier"], ":" ) );

			# merge recorddata, either lom or dc
			switch ( $this->recordSchema )
			{
				case "oai_dc":
				$record = array_merge( $record, $this->getDcRecord( $record_array["recordData"][0]["dc"][0] ) );
				break;
			}

			# merge optional smbAggregatedData
			if ( in_array( "smbAggregatedData", $this->xrecordSchemas ) )
			{
				$pos = array_search( "smbAggregatedData", $this->xrecordSchemas );
				$record = array_merge( $record, $this->getSmbAggregatedData( $record_array["extraRecordData"][0]["recordData"][$pos]["smbAggregatedData"][0] ) );
			}
			
			# merge optional smo's
			if ( in_array( "smo", $this->xrecordSchemas ) )
			{
				$pos = array_search( "smo", $this->xrecordSchemas );
				$record = array_merge( $record, $this->getSmos( $record_array["extraRecordData"][0]["recordData"][$pos] ) );
			}			

			$this->records[] = $record;
		}

		# get optional drilldowns
		if ( array_key_exists( "drilldown", $array["extraResponseData"][0] ) )
		{
			foreach ( $array["extraResponseData"][0]["drilldown"][0]["term-drilldown"][0]["navigator"] as $navigator )
			{
				if ( array_key_exists( "item", $navigator ) )
				{
					foreach ( $navigator["item"] as $item )
					{
						$counts[$item[0]] = $item["@attributes"]["count"];
					}
					$this->drilldowns[$navigator["@attributes"]["name"]] = $counts;
					unset( $counts );
				}
			}
		}
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
				if ( is_string( $this->record_template[$record_key] ) )
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

	private function getSmbAggregatedData( $array )
	{
		$sad["nrofreviews"] = $array["numberOfReviews"][0][0];
		$sad["nrofratings"] = $array["numberOfRatings"][0][0];
		$sad["nroftags"] = $array["numberOfTags"][0][0];
		$sad["rating"] = $array["averageNormalizedRating"][0][0];
		return $sad;	
	}

	private function getSmos( $array )
	{
		$record["smo"] = array();
		
		# return immediately if no smo's available for a record
		if ( !array_key_exists( "smo", $array ) )
		{
			return $record;
		}
		
		foreach( $array["smo"] as $smo )
		{
			$result["smoid"] = $smo["smoId"][0][0];
			$result["supplierid"] = $smo["supplierId"][0][0];
			$result["identifier"] = $smo["hReview"][0]["info"][0][0];
			
			# optional fields
			if ( array_key_exists( "userId", $smo ) )
			{
				$result["userid"] =  $smo["userId"][0][0];
			}
			
			$hreviewfields = array( "summary", "dtreviewed", "rating", "worst", "best", "description" );
			foreach ( $hreviewfields as $field )
			{
				if ( array_key_exists( $field, $smo["hReview"][0] ) )
				{
					$result[$field] = $smo["hReview"][0][$field][0][0];
				}
			}
			
			$record["smo"][] = $result;
		}
		return $record;
	}

	# inspired by T CHASSAGNETTE's example:
	# http://www.php.net/manual/en/ref.simplexml.php#52512
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
?>
