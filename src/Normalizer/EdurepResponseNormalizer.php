<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Normalizer;

use Exception;
use Kennisnet\Edurep\Model\DrilldownNavigator;
use Kennisnet\Edurep\Model\DrilldownNavigatorItem;
use Kennisnet\Edurep\Model\EdurepDrilldownResponse;
use Kennisnet\Edurep\Model\SearchResult;
use Kennisnet\Edurep\Serializer\EdurepResponseUnserializer;
use Kennisnet\Edurep\Transformer\EdurepRecordTransformer;
use Kennisnet\Edurep\Unserializer;
use ReflectionMethod;

class EdurepResponseNormalizer
{
    /**
     * @var EdurepRecordTransformer|null
     */
    protected $edurepRecordTransformer;

    /**
     * @var Unserializer
     */
    private $unserializer;

    /**
     * @var RecordNormalizer
     */
    private $recordNormalizer;

    /**
     * @throws Exception
     */
    public function __construct(
        Unserializer $unserializer,
        RecordNormalizer $recordNormalizer,
        ?EdurepRecordTransformer $edurepRecordTransformer = null
    ) {
        $this->unserializer = $unserializer;

        try {
            $reflection = new ReflectionMethod($recordNormalizer, 'normalize');
            if (count($reflection->getParameters()) < 2) {
                throw new Exception('Incorrect record normalizer provided, normalize method must accept 2 parameters');
            }
        } catch (\ReflectionException $reflectionException) {
            throw new Exception('Incorrect record normalizer provided. ' . $reflectionException->getMessage());
        }
        $this->recordNormalizer        = $recordNormalizer;
        $this->edurepRecordTransformer = $edurepRecordTransformer !== null ? $edurepRecordTransformer : new EdurepRecordTransformer();
    }

    public function supportsNormalization(array $data, string $format = null): bool
    {
        return $format === 'xml';
    }

    public function unSerialize(string $data, string $format = 'xml'): SearchResult
    {
        return $this->normalize($this->unserializer->deserialize($data, $format), $format);
    }

    public function deserialize(string $data, string $format = 'xml'): SearchResult
    {
        return $this->normalize($this->unserializer->deserialize($data, $format), $format);
    }

    public function normalize(array $data, string $schema): SearchResult
    {
        return $this->normalizeEdurepResponse($data);
    }

    private function normalizeEdurepResponse(array $data): SearchResult
    {
        $result = new SearchResult();
        $result->setNumberOfRecords($data[EdurepResponseUnserializer::NUMBER_OF_RECORDS] ?? 0);
        $result->setNextRecordPosition($data[EdurepResponseUnserializer::NEXT_RECORD_POSITION] ?? 0);

        /** @phpstan-ignore-next-line */
        $records = $this->recordNormalizer->normalize(
            $data[EdurepResponseUnserializer::RECORDS] ?? [], $data[EdurepResponseUnserializer::SCHEMA]
        );

        $result->setRecords($this->edurepRecordTransformer->transform($records) ?? []);

        // Normalizer aggregated record data

        $result->setDrilldown($this->normalizeDrilldowns($data[EdurepResponseUnserializer::DRILLDOWN]));

        return $result;
    }

    /**
     * @param array<int,array<string,string|array<int,array<string,int|string>>>>|array<mixed> $navigators
     *
     * @return EdurepDrilldownResponse
     */
    private function normalizeDrilldowns(array $navigators = []): EdurepDrilldownResponse
    {
        $navigators = array_map(function ($navigator) {

            $name  = (string)$navigator['name'] ?? '';
            $items = $navigator['items'] ?? [];
            $items = array_map(function ($item) {
                return new DrilldownNavigatorItem((string)$item['name'] ?? '', (int)$item['count'] ?? 0);
            }, $items);

            return new DrilldownNavigator($name, $items);
        }, $navigators);

        return new EdurepDrilldownResponse($navigators);
    }



}
