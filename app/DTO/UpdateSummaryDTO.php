<?php


namespace App\DTO;


class UpdateSummaryDTO
{
    private $newEntries = 0;
    private $updatedEntries = 0;
    private $deletedEntries = 0;
    private $restoredEntries = 0;
    private $rejectedEntries = 0;

    /**
     * UpdateStatsDTO constructor.
     */
    public function __construct() {
    }

    /**
     * @return int
     */
    public function getNewEntries(): int
    {
        return $this->newEntries;
    }

    /**
     * @param int $newEntries
     */
    public function setNewEntries(int $newEntries): void
    {
        $this->newEntries = $newEntries;
    }

    /**
     * @return int
     */
    public function getUpdatedEntries(): int
    {
        return $this->updatedEntries;
    }

    /**
     * @param int $updatedEntries
     */
    public function setUpdatedEntries(int $updatedEntries): void
    {
        $this->updatedEntries = $updatedEntries;
    }

    /**
     * @return int
     */
    public function getDeletedEntries(): int
    {
        return $this->deletedEntries;
    }

    /**
     * @param int $deletedEntries
     */
    public function setDeletedEntries(int $deletedEntries): void
    {
        $this->deletedEntries = $deletedEntries;
    }

    /**
     * @return int
     */
    public function getRestoredEntries(): int
    {
        return $this->restoredEntries;
    }

    /**
     * @param int $restoredEntries
     */
    public function setRestoredEntries(int $restoredEntries): void
    {
        $this->restoredEntries = $restoredEntries;
    }

    /**
     * @return int
     */
    public function getRejectedEntries(): int
    {
        return $this->rejectedEntries;
    }

    /**
     * @param int $rejectedEntries
     */
    public function setRejectedEntries(int $rejectedEntries): void
    {
        $this->rejectedEntries = $rejectedEntries;
    }

    public function incrementRejectedEntries(int $rejectedEntries) : void {
        $this->rejectedEntries += $rejectedEntries;
    }
}
