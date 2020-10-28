<?php

declare(strict_types=1);

namespace Kennisnet\Edurep\Model;

use Kennisnet\Edurep\Record;

class SearchResult
{
    /**
     * @var int
     */
    private $numberOfRecords = 0;

    /**
     * @var int
     */
    private $nextRecordPosition = 0;

    /**
     * @var Record[]
     */
    private $records = [];

    /**
     * @var EdurepDrilldownResponse
     */
    private $drilldown;

    /**
     * @return int
     */
    public function getNumberOfRecords()
    {
        return $this->numberOfRecords;
    }

    /**
     * @param int $numberOfRecords
     */
    public function setNumberOfRecords($numberOfRecords)
    {
        $this->numberOfRecords = $numberOfRecords;
    }

    /**
     * @return int
     */
    public function getNextRecordPosition()
    {
        return $this->nextRecordPosition;
    }

    /**
     * @param int $nextRecordPosition
     */
    public function setNextRecordPosition($nextRecordPosition)
    {
        $this->nextRecordPosition = $nextRecordPosition;
    }

    /**
     * @return Record[]
     */
    public function getRecords()
    {
        return $this->records;
    }

    /**
     * @param Record[] $records
     */
    public function setRecords(array $records)
    {
        $this->records = $records;
    }

    /**
     * @param Record $record
     */
    public function addRecord(Record $record)
    {
        $this->records[] = $record;
    }

    /**
     * @return EdurepDrilldownResponse
     */
    public function getDrilldown()
    {
        return $this->drilldown;
    }

    /**
     * @param EdurepDrilldownResponse $drilldown
     */
    public function setDrilldown($drilldown)
    {
        $this->drilldown = $drilldown;
    }
}
