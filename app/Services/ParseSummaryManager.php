<?php
namespace App\Services;

use App\Repositories\UserDataRepository;

/**
 * Class to manage parse summary.
 *
 * @package App\Services
 */
class ParseSummaryManager
{
    /**
     * Properties to keep summary values.
     */
    private $parseErrorCount = 0;
    private $parseBadIdCount = 0;
    private $idDuplicateCount = 0;
    private $cardNumberDuplicateCount = 0;
    private $dbDuplicateCount = 0;
    private $toAddCount = 0;
    private $toUpdateCount = 0;
    private $toRestoreCount = 0;
    private $toDeleteCount = 0;
    private $cantDeleteCount = 0;
    private $notChangedCount = 0;
    private $totalEntryCount = 0;

    /**
     * @var UserDataRepository
     */
    private $userDataRepository;

    /**
     * ParseSummaryManager constructor.
     * @param UserDataRepository $userDataRepository
     */
    public function __construct(UserDataRepository $userDataRepository) {
        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Fill summary totals with actual data.
     */
    public function calcSummary() {
        $this->setParseErrorCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_PARSE_ERROR));
        $this->setParseErrorCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_PARSE_BAD_ID));
        $this->setIdDuplicateCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_ID_DUPLICATE));
        $this->setCardNumberDuplicateCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_CARDNUMBER_DUPLICATE));
        $this->setDbDuplicateCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_CARDNUMBER_ALREADY_TAKEN));
        $this->setToAddCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_TO_ADD));
        $this->setToUpdateCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_TO_UPDATE));
        $this->setToRestoreCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_TO_RESTORE));
        $this->setNotChangedCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_NOT_CHANGED));
        $this->setParseErrorCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_PARSE_ERROR));
        $this->setParseErrorCount($this->userDataRepository->getByStatusId(ENTRY_STATUS_PARSE_ERROR));
        $this->setToDeleteCount($this->userDataRepository->countEntriesToDelete());
        $this->setCantDeleteCount($this->userDataRepository->countEntriesCantDelete());
        $this->setTotalEntryCount($this->userDataRepository->countTotalEntries());
    }

    /**
     * Fill summary values with data provided.
     *
     * @param $summaryData
     *
     * @deprecated
     */
    public function setSummary($summaryData) {
        foreach ($summaryData as $row) {
            switch ($row->status_id) {
                case ENTRY_STATUS_PARSE_ERROR:
                    $this->setParseErrorCount($row->total);
                    break;

                case ENTRY_STATUS_ID_DUPLICATE:
                    $this->setIdDuplicate($row->total);
                    break;

                case ENTRY_STATUS_CARDNUMBER_DUPLICATE:
                    $this->setCardNumberDuplicateCount($row->total);
                    break;

                case ENTRY_STATUS_CARDNUMBER_ALREADY_TAKEN:
                    $this->setDbDuplicate($row->total);
                    break;

                case ENTRY_STATUS_TO_ADD:
                    $this->setToAdd($row->total);
                    break;

                case ENTRY_STATUS_TO_UPDATE:
                    $this->setToUpdate($row->total);
                    break;

                case ENTRY_STATUS_TO_RESTORE:
                    $this->setToRestore($row->total);
                    break;

                case ENTRY_STATUS_NOT_CHANGED:
                    $this->setNotChangedCount($row->total);
                    break;
            }
        }
    }


    /**
     * Return summary string representation.
     *
     * @return string
     * @deprecated
     */
    public function __toString() : string{
        $result = "";

        $result .= "Identifier duplicates count: {$this->idDuplicateCount} \n";
        $result .= "Card number duplicates count: {$this->cardNumberDuplicateCount} \n";
        $result .= "Card number already taken count: {$this->dbDuplicateCount} \n";
        $result .= "Entries to add: {$this->toAddCount} \n";
        $result .= "Entries to update: {$this->toUpdateCount} \n";
        $result .= "Entries to restore: {$this->toRestoreCount} \n";
        $result .= "Entries to delete: {$this->toDeleteCount} \n";
        $result .= "Entries not changed: {$this->notChangedCount} \n";
        $result .= "Total entries: {$this->totalEntryCount} \n";

        return $result;
    }

    /**
     * @return int
     */
    public function getIdDuplicateCount(): int
    {
        return $this->idDuplicateCount;
    }

    /**
     * @param int $idDuplicateCount
     */
    public function setIdDuplicateCount(int $idDuplicateCount): void
    {
        $this->idDuplicateCount = $idDuplicateCount;
    }

    /**
     * @return int
     */
    public function getCardNumberDuplicateCount(): int
    {
        return $this->cardNumberDuplicateCount;
    }

    /**
     * @param int $cardNumberDuplicateCount
     */
    public function setCardNumberDuplicateCount(int $cardNumberDuplicateCount): void
    {
        $this->cardNumberDuplicateCount = $cardNumberDuplicateCount;
    }

    /**
     * @return int
     */
    public function getDbDuplicateCount(): int
    {
        return $this->dbDuplicateCount;
    }

    /**
     * @param int $dbDuplicateCount
     */
    public function setDbDuplicateCount(int $dbDuplicateCount): void
    {
        $this->dbDuplicateCount = $dbDuplicateCount;
    }

    /**
     * @return int
     */
    public function getToAddCount(): int
    {
        return $this->toAddCount;
    }

    /**
     * @param int $toAddCount
     */
    public function setToAddCount(int $toAddCount): void
    {
        $this->toAddCount = $toAddCount;
    }

    /**
     * @return int
     */
    public function getToUpdateCount(): int
    {
        return $this->toUpdateCount;
    }

    /**
     * @param int $toUpdateCount
     */
    public function setToUpdateCount(int $toUpdateCount): void
    {
        $this->toUpdateCount = $toUpdateCount;
    }

    /**
     * @return int
     */
    public function getToRestoreCount(): int
    {
        return $this->toRestoreCount;
    }

    /**
     * @param int $toRestoreCount
     */
    public function setToRestoreCount(int $toRestoreCount): void
    {
        $this->toRestoreCount = $toRestoreCount;
    }

    /**
     * @return int
     */
    public function getToDeleteCount(): int
    {
        return $this->toDeleteCount;
    }

    /**
     * @param int $toDeleteCount
     */
    public function setToDeleteCount(int $toDeleteCount): void
    {
        $this->toDeleteCount = $toDeleteCount;
    }

    /**
     * @return int
     */
    public function getNotChangedCount(): int
    {
        return $this->notChangedCount;
    }

    /**
     * @param int $notChangedCount
     */
    public function setNotChangedCount(int $notChangedCount): void
    {
        $this->notChangedCount = $notChangedCount;
    }

    /**
     * @return int
     */
    public function getTotalEntryCount(): int
    {
        return $this->totalEntryCount;
    }

    /**
     * @param int $totalEntryCount
     */
    public function setTotalEntryCount(int $totalEntryCount): void
    {
        $this->totalEntryCount = $totalEntryCount;
    }

    /**
     * @return int
     */
    public function getCantDeleteCount(): int
    {
        return $this->cantDeleteCount;
    }

    /**
     * @param int $cantDeleteCount
     */
    public function setCantDeleteCount(int $cantDeleteCount): void
    {
        $this->cantDeleteCount = $cantDeleteCount;
    }

    /**
     * @return int
     */
    public function getParseErrorCount(): int
    {
        return $this->parseErrorCount;
    }

    /**
     * @param int $parseErrorCount
     */
    public function setParseErrorCount(int $parseErrorCount): void
    {
        $this->parseErrorCount = $parseErrorCount;
    }

    /**
     * @return int
     */
    public function getParseBadIdCount(): int
    {
        return $this->parseBadIdCount;
    }

    /**
     * @param int $parseBadIdCount
     */
    public function setParseBadIdCount(int $parseBadIdCount): void
    {
        $this->parseBadIdCount = $parseBadIdCount;
    }
}
