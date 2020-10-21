<?php

namespace App\Repositories;

use App\DTO\UpdateSummaryDTO;
use App\Models\Userdata;
use App\Services\Filer;
use Illuminate\Support\Facades\DB;

class UserdataRepository
{
    /**
     * The summary stats DTO instance.
     *
     * @var UpdateSummaryDTO
     */
    private $summary;

    /**
     * The Userdata entity instance.
     *
     * @var Userdata
     */
    private $model;


    /**
     * Create a new Userdata Repository instance.
     *
     * @param Userdata $model
     * @param UpdateSummaryDTO $summary
     */
    public function __construct(Userdata $model, UpdateSummaryDTO $summary){
        $this->model = $model;
        $this->summary = $summary;
    }

    /**
     * Upload user data from a .csv file to userdata service table.
     * Todo: get warnings/errors info from MySQL and trigger exception on parsing errors/warnings.
     *
     * @param Filer $filer
     *
     * @throws \Exception
     */
    public function uploadUpdate(Filer $filer) {
        $filePath = $filer->getFilePath();

        DB::connection()->getpdo()->exec(
            "LOAD DATA LOCAL INFILE '$filePath'
            INTO TABLE userdata
            FIELDS TERMINATED BY '\\t'
            LINES TERMINATED BY '\\n'
            IGNORE 1 LINES
            (customer_id, first_name, last_name, card_number)"
        );

        //Todo: handle warning count and trigger exception
        //$warningCount = DB::select(DB::raw('SELECT @@warning_count'));
    }

    /**
     * Clear userdata table before using.
     */
    public function prepare() {
        DB::table($this->model->getTable())->truncate();
    }

    /**
     * Validate user data and gather reject summary:
     * - reject rows with empty name and last name or empty card
     * - reject id duplicates
     * - reject card number duplicates
     * - reject rows where card numbers already belong to other customers
     */
    public function validate() {
        $this->summary->incrementRejectedEntries($this->getRejectedEmptyValuesCount());
        $this->rejectEmptyValues();

        $this->summary->incrementRejectedEntries($this->getRejectedIdDuplicatesCount());
        $this->rejectIdDuplicates();

        $this->summary->incrementRejectedEntries($this->getRejectedCardNumberDuplicatesCount());
        $this->rejectCardNumberDuplicates();

        $this->summary->incrementRejectedEntries($this->getRejectedCardNumberReservedCount());
        $this->rejectCardNumberReserved();
    }

    /**
     * Calculate update statistics to store in the $summary.
     */
    public function calcSummary() {
        $this->summary->setNewEntries($this->getNewCount());
        $this->summary->setDeletedEntries($this->getDeletedCount());
        $this->summary->setRestoredEntries($this->getRestoredCount());
        $this->summary->setUpdatedEntries($this->getUpdatedCount());
    }

    /**
     * Processe update:
     * - soft delete old entries
     * - add new entries
     * - update existing entries
     * - restore previously deleted entries
     */
    public function applyUpdate()
    {
        //DB::beginTransaction();

        $this->removeEntries();
        $this->addOrUpdateOrRestoreEntries();

        //DB::commit();
    }

    /**
     * Return parser update process statistics.
     *
     * @return UpdateSummaryDTO
     */
    public function getSummary() {
        return $this->summary;
    }

    /**
     * Return the number of entries rejected because of empty name and last name or empty card number.
     *
     * @return int
     */
    protected function getRejectedEmptyValuesCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM userdata
            WHERE (first_name = '' AND last_name = '') OR card_number = ''"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Remove (from service userdata table) entries rejected because of empty name and last name or empty card number
     */
    protected function rejectEmptyValues()
    {
        DB::statement(
            "DELETE FROM userdata
            WHERE (first_name = '' AND last_name = '') OR card_number = ''"
        );
    }

    /**
     * Return the number of entries rejected because of id duplicates.
     *
     * @return int
     */
    protected function getRejectedIdDuplicatesCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM userdata
            GROUP BY customer_id
            HAVING COUNT(*) > 1"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Remove (from service userdata table) entries rejected because of id duplicates.
     */
    protected function rejectIdDuplicates()
    {
        DB::statement(
            "DELETE u1 FROM userdata u1
            INNER JOIN userdata u2
            ON u1.id != u2.id AND u1.customer_id = u2.customer_id"
        );
    }

    /**
     * Return the number of entries rejected because of card number duplicates.
     *
     * @return int
     */
    protected function getRejectedCardNumberDuplicatesCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM userdata
            GROUP BY card_number
            HAVING COUNT(*) > 1"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Remove (from service userdata table) entries rejected because of card number duplicates.
     */
    protected function rejectCardNumberDuplicates()
    {
        DB::statement(
            "DELETE u1 FROM userdata u1
            INNER JOIN userdata u2
            ON u1.id != u2.id AND u1.card_number = u2.card_number"
        );
    }

    /**
     * Return the number of entries rejected because of card numbers reserved by other customers.
     *
     * @return int
     */
    protected function getRejectedCardNumberReservedCount() {
        DB::statement(
            "UPDATE userdata u SET u.idr =
            IFNULL((SELECT c.id FROM customers c WHERE c.card_number = u.card_number), 0)"
        );

        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM userdata WHERE customer_id != idr AND idr != 0"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Remove (from service userdata table) entries rejected because of card numbers reserved by other customers.
     *
     * @return int
     */
    protected function rejectCardNumberReserved()
    {
        DB::statement(
            "DELETE FROM userdata WHERE customer_id != idr AND idr != 0"
        );
    }

    /**
     * Return the number of new entries to be added to DB.
     *
     * @return int
     */
    protected function getNewCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM userdata u WHERE
            u.customer_id NOT IN (SELECT c.id FROM customers c)"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Return the number of entries to be soft deleted from DB.
     *
     * @return int
     */
    protected function getDeletedCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM customers c WHERE c.deleted_at IS NOT NULL
            AND c.id NOT IN (SELECT u.customer_id FROM userdata u)"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Return the number of entries to be restored in DB.
     *
     * @return int
     */
    protected function getRestoredCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM customers c WHERE c.deleted_at IS NOT NULL
            AND c.id IN (SELECT u.customer_id FROM userdata u)"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Return number of records to be updated in DB.
     *
     * @return int
     */
    protected function getUpdatedCount() {
        $result = DB::selectOne(DB::raw(
            "SELECT COUNT(*) num FROM customers c WHERE c.deleted_at IS NULL
            AND c.id IN (SELECT u.customer_id FROM userdata u)"
        ));

        return empty($result) ? 0 : $result->num;
    }

    /**
     * Soft delete entries that are not present in the new user data.
     */
    protected function removeEntries() {
        DB::statement(
            "UPDATE customers c
            SET c.deleted_at = NOW()
            WHERE c.id NOT IN (SELECT u.customer_id FROM userdata u)"
        );
    }

    /**
     * Add, update or restore entries in according with the new user data.
     */
    protected function addOrUpdateOrRestoreEntries() {
        DB::statement(
            "INSERT INTO customers(id, first_name, last_name, card_number, created_at, updated_at, deleted_at)
            SELECT u.customer_id, u.first_name, u.last_name, u.card_number, NOW(), NOW(), NULL FROM userdata u
            ON DUPLICATE KEY UPDATE
            first_name = u.first_name, last_name = u.last_name, card_number = u.card_number, updated_at = NOW(), deleted_at = NULL"
        );
    }

}
