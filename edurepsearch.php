<?php
/**
 * PHP package for interfacing with the Edurep search engine.
 *
 * @version 0.2
 * @link http://edurepdiensten.wiki.kennisnet.nl
 *
 * @todo srw interface
 * @todo source code comments
 * @todo class for returning result object
 * @todo prepare page nrs
 * @todo save url
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
?>
