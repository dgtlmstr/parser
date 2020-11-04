<?php
namespace App\Services;

/**
 * Keep summary data.
 *
 * @package App\Services
 */
class Summary
{
    /**
     * Properties to keep summary values.
     */
    private $countParseError = 0;
    private $countIdDuplicate = 0; //iddupcoount
    private $countCardNumberDuplicate = 0;
    private $countDbDuplicate = 0;
    private $countToAdd = 0;
    private $countToUpdate = 0;
    private $countToRestore = 0;
    private $countToDelete = 0;
    private $countCantDelete = 0;
    private $countNotChanged = 0;
    private $totalEntries = 0;

    /**
     * Fill summary values with data provided.
     *
     * @param $summaryData
     */
    public function setSummary($summaryData) {
        foreach ($summaryData as $row) {
            switch ($row->status_id) {
                case ENTRY_STATUS_PARSE_ERROR:
                    $this->setCountParseError($row->total);
                    break;

                case ENTRY_STATUS_ID_DUPLICATE:
                    $this->setIdDuplicate($row->total);
                    break;

                case ENTRY_STATUS_CARDNUMBER_DUPLICATE:
                    $this->setCardNumberDuplicate($row->total);
                    break;

                case ENTRY_STATUS_DB_DUPLICATE:
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
                    $this->setNotChanged($row->total);
                    break;
            }
        }
    }


    /**
     * Return summary string representation.
     *
     * @return string
     */
    public function __toString() : string{
        $result = "";

        $result .= "Identifier duplicates count: {$this->countIdDuplicate} \n";
        $result .= "Card number duplicates count: {$this->countCardNumberDuplicate} \n";
        $result .= "Card number already taken count: {$this->countDbDuplicate} \n";
        $result .= "Entries to add: {$this->countToAdd} \n";
        $result .= "Entries to update: {$this->countToUpdate} \n";
        $result .= "Entries to restore: {$this->countToRestore} \n";
        $result .= "Entries to delete: {$this->countToDelete} \n";
        $result .= "Entries not changed: {$this->countNotChanged} \n";
        $result .= "Total entries: {$this->totalEntries} \n";

        return $result;
    }

    /**
     * @return int
     */
    public function getCountIdDuplicate(): int
    {
        return $this->countIdDuplicate;
    }

    /**
     * @param int $countIdDuplicate
     */
    public function setCountIdDuplicate(int $countIdDuplicate): void
    {
        $this->countIdDuplicate = $countIdDuplicate;
    }

    /**
     * @return int
     */
    public function getCountCardNumberDuplicate(): int
    {
        return $this->countCardNumberDuplicate;
    }

    /**
     * @param int $countCardNumberDuplicate
     */
    public function setCountCardNumberDuplicate(int $countCardNumberDuplicate): void
    {
        $this->countCardNumberDuplicate = $countCardNumberDuplicate;
    }

    /**
     * @return int
     */
    public function getCountDbDuplicate(): int
    {
        return $this->countDbDuplicate;
    }

    /**
     * @param int $countDbDuplicate
     */
    public function setCountDbDuplicate(int $countDbDuplicate): void
    {
        $this->countDbDuplicate = $countDbDuplicate;
    }

    /**
     * @return int
     */
    public function getCountToAdd(): int
    {
        return $this->countToAdd;
    }

    /**
     * @param int $countToAdd
     */
    public function setCountToAdd(int $countToAdd): void
    {
        $this->countToAdd = $countToAdd;
    }

    /**
     * @return int
     */
    public function getCountToUpdate(): int
    {
        return $this->countToUpdate;
    }

    /**
     * @param int $countToUpdate
     */
    public function setCountToUpdate(int $countToUpdate): void
    {
        $this->countToUpdate = $countToUpdate;
    }

    /**
     * @return int
     */
    public function getCountToRestore(): int
    {
        return $this->countToRestore;
    }

    /**
     * @param int $countToRestore
     */
    public function setCountToRestore(int $countToRestore): void
    {
        $this->countToRestore = $countToRestore;
    }

    /**
     * @return int
     */
    public function getCountToDelete(): int
    {
        return $this->countToDelete;
    }

    /**
     * @param int $countToDelete
     */
    public function setCountToDelete(int $countToDelete): void
    {
        $this->countToDelete = $countToDelete;
    }

    /**
     * @return int
     */
    public function getCountNotChanged(): int
    {
        return $this->countNotChanged;
    }

    /**
     * @param int $countNotChanged
     */
    public function setCountNotChanged(int $countNotChanged): void
    {
        $this->countNotChanged = $countNotChanged;
    }

    /**
     * @return int
     */
    public function getTotalEntries(): int
    {
        return $this->totalEntries;
    }

    /**
     * @param int $totalEntries
     */
    public function setTotalEntries(int $totalEntries): void
    {
        $this->totalEntries = $totalEntries;
    }

    /**
     * @return int
     */
    public function getCountCantDelete(): int
    {
        return $this->countCantDelete;
    }

    /**
     * @param int $countCantDelete
     */
    public function setCountCantDelete(int $countCantDelete): void
    {
        $this->countCantDelete = $countCantDelete;
    }

    /**
     * @return int
     */
    public function getCountParseError(): int
    {
        return $this->countParseError;
    }

    /**
     * @param int $countParseError
     */
    public function setCountParseError(int $countParseError): void
    {
        $this->countParseError = $countParseError;
    }


}
