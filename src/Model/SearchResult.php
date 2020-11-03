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

    public function getNumberOfRecords(): int
    {
        return $this->numberOfRecords;
    }

    public function setNumberOfRecords(int $numberOfRecords): void
    {
        $this->numberOfRecords = $numberOfRecords;
    }

    public function getNextRecordPosition(): int
    {
        return $this->nextRecordPosition;
    }

    public function setNextRecordPosition(int $nextRecordPosition): void
    {
        $this->nextRecordPosition = $nextRecordPosition;
    }

    /**
     * @return Record[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    /**
     * @param array<mixed,Record> $records
     */
    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    public function addRecord(Record $record): void
    {
        $this->records[] = $record;
    }

    public function getDrilldown(): ?EdurepDrilldownResponse
    {
        return $this->drilldown;
    }

    public function setDrilldown(EdurepDrilldownResponse $drilldown): void
    {
        $this->drilldown = $drilldown;
    }
}
