<?php

namespace App\Repositories;

use App\DTO\UpdateSummaryDTO;
use App\DTO\UserDataDTO;
use App\Events\DeleteCustomer;
use App\Models\UserData;
use App\Services\CsvReader;
use App\Services\Filer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow to work with entry temporary table
 *
 * @package App\Repositories
 */
class UserDataRepository
{
    /**
     * The name of the table where parser temporary data is stored.
     */
    public const WORK_TABLE_NAME = 'worktable';

    /**
     * Constants representing the initial status of row in temporary table.
     */
    public const ENTRY_STATUS_UNKNOWN = 0;
    public const ENTRY_STATUS_PARSE_ERROR = 1;
    public const ENTRY_STATUS_ID_DUPLICATE = 2;
    public const ENTRY_STATUS_CARDNUMBER_DUPLICATE = 3;
    public const ENTRY_STATUS_DB_DUPLICATE = 4;
    public const ENTRY_STATUS_TO_ADD = 5;
    public const ENTRY_STATUS_TO_UPDATE = 6;
    public const ENTRY_STATUS_TO_RESTORE = 7;
    public const ENTRY_STATUS_TO_DELETE = 8;
    public const ENTRY_STATUS_NOT_CHANGED = 9;

    /**
     * The Userdata entity instance.
     *
     * @var UserData
     */
    private $model;

    /**
     * The Customer Repository instance.
     *
     * @var CustomerRepository
     */
    private $customerRepository;


    /**
     * Create a new Userdata Repository instance.
     *
     * @param UserData $model
     * @param CustomerRepository $customerRepository
     */
    public function __construct(UserData $model, CustomerRepository $customerRepository){
        $this->model = $model;
        $this->customerRepository = $customerRepository;
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
     * Soft delete entries that are not present in the new user data.
     */
    protected function removeEntries() {
        DB::statement(
            "UPDATE customers c
            SET c.deleted_at = NOW()
            WHERE c.id NOT IN (SELECT u.customer_id FROM userdata u) AND c.deleted_at IS NULL"
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

    /**
     * Adding new row to the userdata table
     *
     * @param UserDataDTO $userdata
     */
    /*public function addRow(UserdataDTO $userdata) {
        DB::insert("INSERT INTO userdata(customer_id, first_name, last_name, card_number) values(?, ?, ?, ?)", $userdata->toArray());
    }*/

    /**
     * Recreate work table.
     * @todo create custom table based on config
     */
    public function createTemporaryTable() {
        Schema::dropIfExists(self::WORK_TABLE_NAME);

        Schema::create(self::WORK_TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identifier')->default(0);
            $table->string('first_name', 30);
            $table->string('last_name', 30);
            $table->string('card_number');
            $table->integer('status_id')->default(self::ENTRY_STATUS_UNKNOWN);
            $table->index(['identifier']);
            $table->index(['card_number']);
            $table->index(['status_id']);
        });
    }

    /**
     * Bulk insert a number of rows.
     *
     * @param $rows
     */
    public function bulkInsert($rows) { //work_table_name -> model->getTable
        DB::table(self::WORK_TABLE_NAME)->insert($rows); //performance (compare with one-by-one)
    }

    /**
     * Insert a single row.
     *
     * @param $row
     */
    public function singleInsert($row) {
        DB::table(self::WORK_TABLE_NAME)->insert($row);
    }

    /**
     * Insert few rows using SQL with parameter binding.
     *
     * @param $rows
     */
    public function bulkInsertSql($rows) {
        $values = [];
        foreach($rows as $row) {
            $values = array_merge($values, array_values($row));
        }

        DB::insert("INSERT INTO " . self::WORK_TABLE_NAME . "(id,identifier,first_name,last_name,card_number,status_id) "
            . "VALUES ".
            rtrim(str_repeat("(NULL,?,?,?,?,". self::ENTRY_STATUS_UNKNOWN . "),",count($rows)),","),
            $values);
    }

    /**
     * Insert a single row using SQL with parameter binding.
     *
     * @param $row
     */
    public function singleInsertSql($row) {
        DB::insert("INSERT INTO " . self::WORK_TABLE_NAME . "(id,identifier,first_name,last_name,card_number,status_id) "
            . "VALUES (NULL,?,?,?,?,". self::ENTRY_STATUS_UNKNOWN . ")", array_values($row));
    }

    /**
     * Insert few rows using raw SQL.
     *
     * @param $rows
     */
    public function bulkInsertSqlRaw($rows) {
        $values = "";
        foreach ($rows as $row) {
            $values .= "(NULL,'".addslashes($row['identifier'])."','".addslashes($row['first_name'])."','".addslashes($row['last_name'])."','".addslashes($row['card_number'])."',". self::ENTRY_STATUS_UNKNOWN . "),";
        }

        DB::statement("INSERT INTO " . self::WORK_TABLE_NAME . "(id,identifier,first_name,last_name,card_number,status_id) "
            . "VALUES " . rtrim($values, ","));
    }

    /**
     * Insert few rows using raw SQL.
     *
     * @param $row
     */
    public function singleInsertSqlRaw($row) {
        DB::statement("INSERT INTO " . self::WORK_TABLE_NAME . "(id,identifier,first_name,last_name,card_number,status_id) "
            . "VALUES (NULL,'".addslashes($row['identifier'])."','".addslashes($row['first_name'])."','".addslashes($row['last_name'])."','".addslashes($row['card_number'])."',". self::ENTRY_STATUS_UNKNOWN . ")");
    }

    /**
     * Mark identifier duplicates.
     */
    public function validateIdentifierDuplicates() { // who is whose duplicate!
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->join(self::WORK_TABLE_NAME .' as u2', 'u1.identifier', '=', 'u2.identifier')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN], // AND NOT _REJECTED
                ['u2.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u1.id <> u2.id')
            ->update(['u1.status_id' => self::ENTRY_STATUS_ID_DUPLICATE]);
    }

    /**
     * Mark card number duplicates in DB tmp table.
     * Describe if complex structure is used to store status.
     */
    public function validateCardNumberDuplicates() { // do update however is called validate
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->join(self::WORK_TABLE_NAME .' as u2', 'u1.card_number', '=', 'u2.card_number')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
                ['u2.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u1.id <> u2.id')
            ->update(['u1.status_id' => self::ENTRY_STATUS_CARDNUMBER_DUPLICATE]);
    }


    /**
     * Mark entries whose card numbers has already been taken by others.
     */
    public function validateAgainstDb() { // validate what exactly? + what is dup id
        // we'd maybe better go with subquery
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->join('customers' .' as u2', 'u1.card_number', '=', 'u2.card_number')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u1.identifier <> u2.id AND u2.deleted_at IS NULL')
            ->update(['u1.status_id' => self::ENTRY_STATUS_DB_DUPLICATE]);
    }

    /**
     * Mark entries to be added to DB.
     */
    public function markEntriesToAdd() {
        // we'd maybe better go with subquery
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->leftJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u2.id IS NULL')
            ->update(['u1.status_id' => self::ENTRY_STATUS_TO_ADD]);
    }

    /**
     * Mark entries to be added to DB.
     */
    public function markEntriesToUpdate() {
        // we'd maybe better go with subquery
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->leftJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw(
                'u2.id IS NOT NULL
                AND u2.deleted_at IS NULL
                AND (u1.first_name <> u2.first_name OR u1.last_name <> u2.last_name OR u1.card_number <> u2.card_number)'
            )
            ->update(['u1.status_id' => self::ENTRY_STATUS_TO_UPDATE]);
    }

    /**
     * Mark entries to do not touch.
     * Must be called as a final method in validation chain!
     */
    public function markEntriesNotChanged() {
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->update(['u1.status_id' => self::ENTRY_STATUS_NOT_CHANGED]);

        // check all fields approach - this one is better!
        // hardcode fields - to config (configs as set of classes based on base class)
        /*DB::table(self::WORK_TABLE_NAME .' as u1')
            ->leftJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u2.id IS NOT NULL AND (u1.first_name = u2.first_name AND u1.last_name = u2.last_name AND u1.card_number = u2.card_number)')
            ->update(['u1.status_id' => self::ENTRY_STATUS_NOT_CHANGED]);*/
    }

    /**
     * Mark entries to be restored to DB.
     */
    public function markEntriesToRestore() {
        // we'd maybe better go with subquery
        DB::table(self::WORK_TABLE_NAME .' as u1')
            ->leftJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->where([
                ['u1.status_id', '=', self::ENTRY_STATUS_UNKNOWN],
            ])
            ->whereRaw('u2.deleted_at IS NOT NULL')
            ->update(['u1.status_id' => self::ENTRY_STATUS_TO_RESTORE]);
    }

    /**
     * Count entries to be deleted in DB.
     */
    public function countEntriesToDelete() {
        // we'd maybe better go with subquery
        $result = DB::table(self::WORK_TABLE_NAME .' as u1')
            ->rightJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->whereRaw('u2.deleted_at IS NULL AND u1.id IS NULL')
            ->selectRaw('COUNT(*) AS total')
            ->first();

        return $result->total;
    }

    /**
     * Delete rows from database.
     * Trigger event on each deletion.
     *
     * @todo Put all rows that can't be deleted to can_not delete list.
     */
    public function deleteRows() { // add exception on each row + report on exception
        $cursor = DB::table(self::WORK_TABLE_NAME .' as u1')
            ->rightJoin('customers' .' as u2', 'u1.identifier', '=', 'u2.id')
            ->whereRaw('u2.deleted_at IS NULL AND u1.id IS NULL')
            ->select('u2.id')
            ->cursor();

        foreach ($cursor as $row) {
            // transaction performance - mass delete vs one-by-one, people+users
            $customer = $this->customerRepository->deleteRow($row->id);

            // check if row can be deleted - on validation, not here
            event(new DeleteCustomer($customer)); // reporting independently, not in events
        }
    }

    /**
     * stub
     */
    public function restoreRows()
    {
        $cursor = null;

        foreach ($cursor as $row) {
            //$customer = $this->customerRepository->restoreRow($row->id);

            //event(new RestoreCustomer($customer));
        }
    }

    /**
     * stub
     */
    public function updateRows()
    {
    }

    /**
     * stub
     */
    public function addRows()
    {
    }

    /**
     * stub
     */
    public function reportNotChangedRows()
    {
    }


    /**
     * Return validate and update summary.
     *
     * @return UpdateSummaryDTO
     */
    public function getSummary() {
        $summary = new UpdateSummaryDTO();

        $result = DB::table(self::WORK_TABLE_NAME .' as u1')
            ->select('status_id', DB::raw('count(*) as total'))
            ->groupBy('status_id')
            ->get();

        foreach ($result as $row) {
            switch ($row->status_id) {
                case self::ENTRY_STATUS_ID_DUPLICATE:
                    $summary->setIdDuplicate($row->total);
                    break;

                case self::ENTRY_STATUS_CARDNUMBER_DUPLICATE:
                    $summary->setCardNumberDuplicate($row->total);
                    break;

                case self::ENTRY_STATUS_DB_DUPLICATE:
                    $summary->setDbDuplicate($row->total);
                    break;

                case self::ENTRY_STATUS_TO_ADD:
                    $summary->setToAdd($row->total);
                    break;

                case self::ENTRY_STATUS_TO_UPDATE:
                    $summary->setToUpdate($row->total);
                    break;

                case self::ENTRY_STATUS_TO_RESTORE:
                    $summary->setToRestore($row->total);
                    break;

                case self::ENTRY_STATUS_NOT_CHANGED:
                    $summary->setNotChanged($row->total);
                    break;
            }
        }

        $summary->setToDelete($this->countEntriesToDelete());

        return $summary;
    }
}
