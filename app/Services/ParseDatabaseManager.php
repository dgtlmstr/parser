<?php
namespace App\Services;

use App\Events\DeleteCustomer;
use App\Repositories\CustomerRepository;
use App\Repositories\UserDataRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


/**
 * Manage parse tasks related to database.
 *
 * @package App\Services
 */
class ParseDatabaseManager
{
    /**
     * The instance of the Userdata Repository.
     *
     * @var UserDataRepository
     */
    protected $userDataRepository;

    /**
     * The instance of Customer Repository.
     *
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var ParseReportManager
     */
    protected $reportManager;

    /**
     * @var ReportFormatService
     */
    protected $reportFormatService;

    /**
     * ParseDatabaseManager constructor.
     * @param UserDataRepository $userDataRepository
     * @param CustomerRepository $customerRepository
     * @param ReportFormatService $reportFormatService
     */
    public function __construct(
        UserDataRepository $userDataRepository,
        CustomerRepository $customerRepository,
        ReportFormatService $reportFormatService
    ) {
        $this->userDataRepository = $userDataRepository;
        $this->customerRepository = $customerRepository;
        $this->reportFormatService = $reportFormatService;
    }

    /**
     * Create temporary table to upload entries from CSV.
     */
    public function createTemporaryTables()
    {
        $this->createTemporaryEntriesTable();
        $this->createTemporaryStatusesTable();
    }

    /**
     * Recreate Entries table.
     * @todo create custom table based on config
     */
    public function createTemporaryEntriesTable() {
        Schema::dropIfExists(WORK_TABLE_NAME);

        Schema::create(WORK_TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identifier')->default(0);
            $table->string('first_name', 30);
            $table->string('last_name', 30);
            $table->string('card_number');
            $table->integer('status_id')->default(ENTRY_STATUS_UNKNOWN);
            $table->index(['identifier']);
            $table->index(['card_number']);
            $table->index(['status_id']);
        });
    }

    /**
     * Recreate Entry Statuses table.
     * @todo create custom table based on config
     */
    public function createTemporaryStatusesTable() {
        Schema::dropIfExists(STATUS_TABLE_NAME);

        Schema::create(STATUS_TABLE_NAME, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_id')->default(0);
            $table->unsignedBigInteger('status_id')->default(0);
            $table->string('message');
            $table->index(['entry_id']);
            $table->index(['status_id']);
        });
    }

    /**
     * Validate:
     * - identifier uniqueness
     * - card_number uniqueness
     * - field uniqueness against data existing in DB
     */
    public function validateEntries() {
        //todo: make calls based on Config
        $this->userDataRepository->markIdentifierDuplicates();
        $this->userDataRepository->markCardNumberDuplicates();
        $this->userDataRepository->markEntriesWithCardNumbersAlreadyTaken();
    }

    /**
     * Mark entries that are different between old and new table
     */
    public function defineActionsForEntries() {
        //todo: define entry operations in Config
        $this->userDataRepository->markEntriesToAdd();
        $this->userDataRepository->markEntriesToUpdate();
        $this->userDataRepository->markEntriesToRestore();
        $this->userDataRepository->markEntriesNotChanged();
    }

    /**
     * Apply all inserts, updates, deletes and restores.
     * Trigger events.
     */
    public function applyUpdate() { // errors can occur (UNIQUE CONTRAINT or smth)
        $this->deleteRows();
        $this->restoreRows();
        $this->updateRows();
        $this->addRows();
        //$this->userDataRepository->reportNotChangedRows();
    }

    /**
     * Process row deleting.
     */
    protected function deleteRows() {
        $cursor = $this->userDataRepository->getCursorForDeleteRows();

        foreach ($cursor as $row) {
            // transaction performance - mass delete vs one-by-one, people+users
            try {
                $customer = $this->customerRepository->deleteRow($row->id);

                event(new DeleteCustomer($customer));

                $this->reportManager->reportEntryProcessing($this->reportFormatService->customerDeleted($customer));
            } catch (\Exception $exception) {
                $this->reportManager->reportEntryProcessing($this->reportFormatService->customerDeleteError($customer));
                // correct stats, change status or smth
            }
        }
    }

    /**
     * Process row restoring.
     */
    protected function restoreRows() {
        $cursor = $this->userDataRepository->getCursorForRestoreRows();
        //todo
    }

    /**
     * Process row updating.
     */
    protected function updateRows() {
        $cursor = $this->userDataRepository->getCursorForUpdateRows();
        //todo
    }

    /**
     * Process row adding.
     */
    protected function addRows() {
        $cursor = $this->userDataRepository->getCursorForAddRows();
        //todo
    }

    /**
     * Set Report Manager.
     *
     * @param $reportManager
     */
    public function setReportManager(ParseReportManager $reportManager) {
        $this->reportManager = $reportManager;
    }
}
