<?php

namespace Kennisnet\Edurep;

class EckRecord
{
    const TITLE       = 'Title';
    const DESCRIPTION = 'Description';
    const LOCATION    = 'InformationLocation';
    const PUBLISHER   = 'Publisher';
    const AUTHORS     = 'Authors';

    /**
     * @var string
     */
    private $recordId;
    /**
     * @var string
     */
    private $title;
    /**
     * @var string
     */
    private $description = '';
    /**
     * @var string
     */
    private $location = '';
    /**
     * @var string
     */
    private $publisher = '';

    /**
     * @var string[]
     */
    private $authors = [];

    public function __construct(string $recordId, string $title)
    {
        $this->recordId = $recordId;
        $this->title    = $title;
    }

    public function getRecordId()
    {
        return $this->recordId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        if (null !== $description) {
            $this->description = $description;
        }
    }

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
     * @return string[]
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param string[] $authors
     */
    public function setAuthors(array $authors)
    {
        $this->authors = $authors;
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

}