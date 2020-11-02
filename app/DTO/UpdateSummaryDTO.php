<?php


namespace App\DTO;


class UpdateSummaryDTO
{
    private $idDuplicate = 0;
    private $cardNumberDuplicate = 0;
    private $dbDuplicate = 0;
    private $toAdd = 0;
    private $toUpdate = 0;
    private $toRestore = 0;
    private $toDelete = 0;
    private $notChanged = 0;

    /**
     * UpdateStatsDTO constructor.
     */
    public function __construct() {
    }

    /**
     * @return int
     */
    public function getIdDuplicate(): int
    {
        return $this->idDuplicate;
    }

    /**
     * @param int $idDuplicate
     */
    public function setIdDuplicate(int $idDuplicate): void
    {
        $this->idDuplicate = $idDuplicate;
    }

    /**
     * @return int
     */
    public function getCardNumberDuplicate(): int
    {
        return $this->cardNumberDuplicate;
    }

    /**
     * @param int $cardNumberDuplicate
     */
    public function setCardNumberDuplicate(int $cardNumberDuplicate): void
    {
        $this->cardNumberDuplicate = $cardNumberDuplicate;
    }

    /**
     * @return int
     */
    public function getDbDuplicate(): int
    {
        return $this->dbDuplicate;
    }

    /**
     * @param int $dbDuplicate
     */
    public function setDbDuplicate(int $dbDuplicate): void
    {
        $this->dbDuplicate = $dbDuplicate;
    }

    /**
     * @return int
     */
    public function getToAdd(): int
    {
        return $this->toAdd;
    }

    /**
     * @param int $toAdd
     */
    public function setToAdd(int $toAdd): void
    {
        $this->toAdd = $toAdd;
    }

    /**
     * @return int
     */
    public function getToUpdate(): int
    {
        return $this->toUpdate;
    }

    /**
     * @param int $toUpdate
     */
    public function setToUpdate(int $toUpdate): void
    {
        $this->toUpdate = $toUpdate;
    }

    /**
     * @return int
     */
    public function getToRestore(): int
    {
        return $this->toRestore;
    }

    /**
     * @param int $toRestore
     */
    public function setToRestore(int $toRestore): void
    {
        $this->toRestore = $toRestore;
    }

    /**
     * @return int
     */
    public function getNotChanged(): int
    {
        return $this->notChanged;
    }

    /**
     * @param int $notChanged
     */
    public function setNotChanged(int $notChanged): void
    {
        $this->notChanged = $notChanged;
    }

    /**
     * @return int
     */
    public function getToDelete(): int
    {
        return $this->toDelete;
    }

    /**
     * @param int $toDelete
     */
    public function setToDelete(int $toDelete): void
    {
        $this->toDelete = $toDelete;
    }

}
