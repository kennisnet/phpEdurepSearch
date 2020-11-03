<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Model;

use DateInterval;
use DateTime;
use Exception;
use Kennisnet\Edurep\Record;
use Kennisnet\Edurep\Value\Duration;
use Kennisnet\Edurep\Value\PublishDate;

class EdurepRecord implements Record
{
    /**
     * @var string
     */
    private $recordId;

    /**
     * @var array
     */
    private $catalogEntryUris = [];

    /**
     * @var string
     */
    private $location = '';

    /**
     * @var string
     */
    private $title = '';

    /**
     * @var string
     */
    private $description = '';

    /**
     * @var string[]
     */
    private $resourceTypes = [];

    /**
     * @var DateInterval|null
     */
    private $duration;

    /**
     * @var string
     */
    private $publisher = '';

    /**
     * @var string[]
     */
    private $authors = [];

    /**
     * @var DateTime|null
     */
    private $publishDate;

    /**
     * @var string
     */
    private $thumbnail;


    public function __construct(string $recordId)
    {
        $this->recordId = $recordId;
    }

    public function getRecordId(): string
    {
        return $this->recordId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
    }

    public function getResourceTypes(): array
    {
        return $this->resourceTypes;
    }

    /**
     * @param string[] $resourceTypes
     */
    public function setResourceTypes(array $resourceTypes): void
    {
        $this->resourceTypes = $resourceTypes;
    }

    public function getPublisher(): string
    {
        return $this->publisher;
    }

    public function setPublisher(string $publisher): void
    {
        $this->publisher = $publisher;
    }

    public function getThumbnail(): string
    {
        return $this->thumbnail;
    }

    public function setThumbnail(string $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }

    /**
     * @return array
     */
    public function getCatalogEntryUris()
    {
        return $this->catalogEntryUris;
    }

    public function getSimpleDuration(): ?Duration
    {
        return new Duration($this->getDuration() ?? null);
    }

    public function getDuration(): ?DateInterval
    {
        return $this->duration;
    }

    public function setDuration(?DateInterval $duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @throws Exception
     */
    public function getPublishedSince(): PublishDate
    {
        return new PublishDate($this->getPublishDate() ?? null);
    }

    public function getPublishDate(): ?DateTime
    {
        return $this->publishDate;
    }

    public function setPublishDate(?DateTime $publishDate): void
    {
        $this->publishDate = $publishDate;
    }

    /**
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param string[] $authors
     */
    public function setAuthors(array $authors): void
    {
        $this->authors = $authors;
    }

    /**
     * @param string $author
     */
    public function addAuthor(string $author): void
    {
        if (!in_array($author, $this->authors)) {
            $this->authors[] = $author;
        }
    }

}
