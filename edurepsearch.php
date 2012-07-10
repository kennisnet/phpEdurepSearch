<?php
/**
 * PHP package for interfacing with the Edurep search engine.
 *
 * @version 0.3
 * @link http://edurepdiensten.wiki.kennisnet.nl
 *
 * @todo srw interface
 * @todo source code comments
 * @todo expand class for returning result object
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
	public $numberOfRecords = 0;
	public $nextRecordPosition = 0;
	public $records = array();

	private $recordSchema = "";
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
		$this->nextRecordPosition = $array["nextRecordPosition"][0][0];
		$this->recordSchema = $array["echoedSearchRetrieveRequest"][0]["recordSchema"][0][0];

		switch ( $this->recordSchema )
		{
			case "lom":
			$this->loadLomRecords( $array["records"][0]["record"] );
			break;

			case "oai_dc":
			$this->loadDcRecords( $array["records"][0]["record"] );
			break;
		}
	}
	
	private function loadLomRecords( $records )
	{
		foreach ( $records as $array )
		{
			$record = array();
			$record["identifier"] = $array["recordIdentifier"][0][0];
			$record["repository"] = substr( $record["identifier"], 0, strpos( $record["identifier"], ":" ) );
			$record["title"] = $array["recordData"][0]["lom"][0]["general"][0]["title"][0]["langstring"][0][0];
			$this->records[] = $record;
		}
	}

	private function loadDcRecords( $records )
	{
		foreach ( $records as $array )
		{
			$record = array();
			$record["identifier"] = $array["recordIdentifier"][0][0];
			$record["repository"] = substr( $record["identifier"], 0, strpos( $record["identifier"], ":" ) );
			$record["title"] = $array["recordData"][0]["dc"][0]["title"][0][0];
			$this->records[] = $record;
		}
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
