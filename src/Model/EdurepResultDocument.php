<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Model;

use DOMDocument;
use DOMXPath;

class EdurepResultDocument
{
    /**
     * @var DOMXPath
     */
    public $xpath;

    public function __construct(string $response)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        $doc->loadXML($response);
        $xpath = new DOMXPath($doc);
        $xpath->registerNamespace('srw', 'http://www.loc.gov/zing/srw/');
        $xpath->registerNamespace('czp', 'http://www.imsglobal.org/xsd/imsmd_v1p2');
        $xpath->registerNamespace('sad', 'http://xsd.kennisnet.nl/smd/sad');
        $xpath->registerNamespace('smo', 'http://xsd.kennisnet.nl/smd/1.0/');
        $xpath->registerNamespace('edurep', 'http://meresco.org/namespace/users/kennisnet/edurep');
        $xpath->registerNamespace('dd', 'http://meresco.org/namespace/drilldown');
        $xpath->registerNamespace('hr', 'http://xsd.kennisnet.nl/smd/hreview/1.0/');
        $this->xpath = $xpath;
    }

    public function __invoke(): DOMXPath
    {
        return $this->xpath;
    }
}