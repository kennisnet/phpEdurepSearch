<?php
declare(strict_types=1);

namespace Kennisnet\Edurep\Model;

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
     * @var \DateInterval
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
     * @var \DateTime
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

    /**
     * @return string
     */
    public function getRecordId(): string
    {
        return $this->recordId;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location)
    {
        $this->location = $location;
    }



    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \string[]
     */
    public function getResourceTypes()
    {
        return $this->resourceTypes;
    }

    /**
     * @param \string[] $resourceTypes
     */
    public function setResourceTypes($resourceTypes)
    {
        $this->resourceTypes = $resourceTypes;
    }

    /**
     * @return string
     */
    public function getPublisher()
    {
        return $this->publisher;
    }

    /**
     * @param string $publisher
     */
    public function setPublisher(string $publisher)
    {
        $this->publisher = $publisher;
    }

    /**
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * @param string $thumbnail
     */
    public function setThumbnail($thumbnail)
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

    /**
     * @return Duration|null
     */
    public function getSimpleDuration()
    {
        return new Duration($this->getDuration() ?? null);
    }

    /**
     * @return \DateInterval
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param \DateInterval $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return PublishDate
     * @throws \Exception
     */
    public function getPublishedSince()
    {
        return new PublishDate($this->getPublishDate() ?? null);
    }

    /**
     * @return \DateTime
     */
    public function getPublishDate()
    {
        return $this->publishDate;
    }

    /**
     * @param \DateTime $publishDate
     */
    public function setPublishDate($publishDate)
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
     * @param string $author
     */
    public function addAuthor(string $author)
    {
        if (!in_array($author, $this->authors)) {
            $this->authors[] = $author;
        }
    }

    /**
     * @param string[] $authors
     */
    public function setAuthors(array $authors)
    {
        $this->authors = $authors;
    }

}
