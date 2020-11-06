<?php
namespace App\DTO;

/**
 * Data Transfer Object for entry status.
 * @package App\DTO
 */
class EntryStatusDTO
{
    /**
     * @var int
     */
    private $statusId;

    /**
     * EntryStatusDTO constructor.
     * @param int $statusId
     * @param string $statusDetails
     */
    public function __construct(int $statusId, string $statusDetails)
    {
        $this->statusId = $statusId;
        $this->statusDetails = $statusDetails;
    }

    /**
     * @return int
     */
    public function getStatusId(): int
    {
        return $this->statusId;
    }

    /**
     * @param int $statusId
     */
    public function setStatusId(int $statusId): void
    {
        $this->statusId = $statusId;
    }

    /**
     * @return string
     */
    public function getStatusDetails(): string
    {
        return $this->statusDetails;
    }

    /**
     * @param string $statusDetails
     */
    public function setStatusDetails(string $statusDetails): void
    {
        $this->statusDetails = $statusDetails;
    }

    /**
     * @var string
     */
    private $statusDetails;
}
