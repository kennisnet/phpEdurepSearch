<?php
/**
 * Created by PhpStorm.
 * User: Andreas Warnaar
 * Date: 14-2-19
 * Time: 12:55
 */

namespace Kennisnet\Edurep;

use Kennisnet\ECK\EckRecordsNormalizer;
use Kennisnet\Edurep\Exception\InvalidRecordSchemaException;
use Kennisnet\Edurep\Model\DrilldownNavigator;
use Kennisnet\Edurep\Model\DrilldownNavigatorItem;
use Kennisnet\Edurep\Model\EdurepDrilldownResponse;
use Kennisnet\Edurep\Model\SearchResult;
use Kennisnet\Edurep\Transformer\EdurepRecordTransformer;

class EdurepResponseNormalizer implements Normalizer
{
    /**
     * @var Serializer
     */
    private $serializer;
    /**
     * @var RecordNormalizer
     */
    private $recordNormalizer;

    /**
     * EdurepResponseNormalizer constructor.
     *
     * @param Serializer $serializer
     * @param $recordNormalizer
     * @throws \Exception
     */
    public function __construct(Serializer $serializer, $recordNormalizer)
    {
        $this->serializer = $serializer;

        if (!$recordNormalizer instanceof EckRecordsNormalizer
            && !$recordNormalizer instanceof RecordNormalizer) {

            throw new \Exception('Incorrect record normalizer provided');
        }
        $this->recordNormalizer = $recordNormalizer;
    }


    public function supportsNormalization($data, $format = null)
    {
        return $format === 'xml';
    }

    /**
     * @param mixed $data
     * @param string $format
     * @param array $context
     * @return SearchResult
     * @throws InvalidRecordSchemaException
     */
    public function serialize($data, $format = 'xml', array $context = [])
    {
        $data = $this->serializer->deserialize($data);
        return $this->normalize($data);
    }

    /**
     * TODO : format must be a enum of supported schema's [eckcs2.1.1]
     *
     * @param array|string $data
     * @param string $schema of the given records
     * @param array $context
     * @return SearchResult
     * @throws InvalidRecordSchemaException
     * @throws \Exception
     */
    public function normalize($data): SearchResult
    {
        if (is_string($data)) {
            $data = $this->serializer->deserialize($data);
        }

        return $this->normalizeEdurepResponse($data);
    }

    /**
     * @param $data
     * @param RecordReader $recordReader
     * @return SearchResult
     * @throws \Exception
     */
    private function normalizeEdurepResponse($data): SearchResult
    {
        $result = new SearchResult();
        $result->setNumberOfRecords($data[EdurepResponseSerializer::NUMBER_OF_RECORDS]);
        $result->setNextRecordPosition($data[EdurepResponseSerializer::NEXT_RECORD_POSITION]);

        /** @var Record[] $records */
        $records = $this->recordNormalizer->normalize($data[EdurepResponseSerializer::RECORDS] ?? [], $data[EdurepResponseSerializer::SCHEMA]);

        $transformer = new EdurepRecordTransformer();
        $result->setRecords($transformer->transform($records) ?? []);

        // Normalizer aggregated record data

        // Normalize Drilldown
        $result->setDrilldown($this->normalizeDrilldowns($data[EdurepResponseSerializer::DRILLDOWN]));
        return $result;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param $schema
     * @param array $context
     * @return SearchResult
     * @throws InvalidRecordSchemaException
     */
    public function deserialize($data, $type = SearchResult::class, $schema, array $context = [])
    {
        if ($this->serializer) {
            return $this->normalize($this->serializer->deserialize($data), $schema);
        }
        throw  new \Exception('Invalid format provided.');
    }

    private function normalizeDrilldowns(array $navigators = [])
    {
        $navigators = array_map(function ($navigator) {
            /**
             * @var string $name
             * @var array $items
             */
            extract($navigator);
            $items = array_map(function ($item) {
                /**
                 * @var string $name
                 * @var integer $count
                 */
                extract($item);

                return new DrilldownNavigatorItem($name, $count);
            }, $items);
            return new DrilldownNavigator($name, $items);
        }, $navigators);

        return new EdurepDrilldownResponse($navigators);
    }

}
