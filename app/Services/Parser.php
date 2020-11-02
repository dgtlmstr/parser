<?php
namespace App\Services;

use App\DTO\UpdateSummaryDTO;
use App\DTO\UserdataDTO;
use App\Repositories\UserdataRepository;
use Validator;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Parser {

    /**
     * Constant representing the name of the table where parser temporary data is stored.
     */
    public const WORK_TABLE_NAME = 'userdata';

    /**
     * Constant representing the count of records to be inserted to the temporary table at once.
     */
    public const BULK_INSERT_LIMIT = 1000;

    /**
     * Constant representing the threshold.
     * It requires special parameter {paramname} to be used to apply mass update.
     */
    public const UPDATE_THRESHOLD_LIMIT = 1000;

    /**
     * Constant representing a successfully updated user data.
     */
    public const PARSER_OK = 1;

    /**
     * Constant representing update failure due to file absence.
     */
    public const PARSER_NO_FILE_UPDATE = 2;

    /**
     * Constant representing update failure due to parsing error.
     */
    public const PARSER_FAILED = 3;

    /**
     * Constant representing update stop due to above threshold delete.
     */
    public const PARSER_ABOVE_THRESHOLD = 4;

    /**
     * The instance of the Filer class.
     *
     * @var Filer
     */
    private $filer;

    /**
     * The instance of the Userdata Repository.
     *
     * @var UserdataRepository
     */
    private $userdataRepository;

    /**
     * The summary stats DTO instance
     *
     * @var UpdateSummaryDTO
     */
    private $summary;

    /**
     * The Array to buffer entries for bulk insert to the work table.
     *
     * @var Array
     */
    private $entriesBuffer;

    /**
     * The Csv Parser instance.
     *
     * @var CsvParser
     */
    private $csvParser;

    /**
     * Create a new Parser instance.
     *
     * @param Filer $filer
     * @param CsvParser $csvParser
     * @param UserdataRepository $userdataRepository
     */
    public function __construct(Filer $filer, CsvParser $csvParser, UserdataRepository $userdataRepository){
        $this->filer = $filer;
        $this->filer->setFolder(env("UPDATE_DIR_PATH"));
        $this->filer->setFilename(env("UPDATE_FILENAME"));

        $this->csvParser = $csvParser;

        $this->userdataRepository = $userdataRepository;
    }

    /**
     * Return true if update file exists, otherwise false.
     * File path options should be defined in an environment constants UPDATE_DIR_PATH and UPDATE_FILENAME
     *
     * @return bool
     */
    public function CheckIfUpdateExists() {
        return $this->filer->fileExists();
    }

    /**
     * Synchronise user data from external source file with DB.
     *
     * @return int Status of the process
     * @throws \Exception
     */
    public function Update() {
        if (!$this->CheckIfUpdateExists()) return self::PARSER_NO_FILE_UPDATE;

        $this->userdataRepository->createTable();
        $this->parse($this->filer);

        $this->validateUpdate();
        $this->findDifference();
        $this->summary = $this->calcSummary();

        if ($this->summary->getToDelete() >= self::UPDATE_THRESHOLD_LIMIT ||
            $this->summary->getToUpdate() >= self::UPDATE_THRESHOLD_LIMIT) {
            return self::PARSER_ABOVE_THRESHOLD;
        }

        $this->applyUpdate();

        return self::PARSER_OK;
    }

    /**
     * Return parser update process statistics.
     *
     * @return UpdateSummaryDTO
     */
    public function getSummary() : UpdateSummaryDTO {
        return $this->summary;
    }

    /**
     * Remove user data source file.
     */
    public function DeleteUpdate() {
        $this->filer->fileRemove();
    }

    /**
     * Upload user data from a .csv file to userdata service table.
     * Validate each entry.
     * Add entry to DB.
     *
     * @param Filer $filer
     *
     * @throws \Exception
     */
    public function parse(Filer $filer) {
        if ($filer->isFileEmpty()) {
            throw new \Exception('Empty file');
        }

        $callback = [$this, "handleEntryCallback"];
        $this->csvParser->parse($filer, $callback);
        $this->commitEntries();
    }

    /**
     * Method to be passed as a callback to CSV parser.
     * Adding new entry to userdata.
     * Expect array [integer (customer_id), string (first_name), string (last_name), string (card_number)] as an input parameter.
     *
     * @param array $csvEntry
     */
    public function handleEntryCallback(array $csvEntry) {
        $entry = $this->mapEntry($csvEntry);
        if ($this->validateEntry($entry)) {
            $this->addEntryToDB($entry);
        }
    }

    /**
     * Map raw CSV entry to userdata DTO.
     *
     * @param $csvEntry
     * @return Array
     */
    protected function mapEntry($csvEntry) {
        $userdata = [];

        if (count($csvEntry) != 4) {
            return $userdata;
        }

        $userdata['identifier'] = $csvEntry[0];
        $userdata['first_name'] = $csvEntry[1];
        $userdata['last_name'] = $csvEntry[2];
        $userdata['card_number'] = $csvEntry[3];

        return $userdata;
    }

    /**
     * Check if entry from CSV passes type validation.
     *
     * @param $userdata
     * @return mixed
     */
    protected function validateEntry($userdata) {
        $validator = Validator::make($userdata, [
            'identifier' => 'required|numeric',
            'first_name' => 'required|string|max:30',
            'last_name' => 'required|string|max:30',
            'card_number' => 'required|string|max:16',
        ]);

        return !$validator->fails();
    }

    /**
     * Add entry to DB buffer and bulk insert to the DB when the buffer is full.
     *
     * @param $userdata
     */
    protected function addEntryToDB($userdata) {
        $this->entriesBuffer[] = $userdata;

        if (count($this->entriesBuffer) == self::BULK_INSERT_LIMIT) {
            $this->commitEntries();

            unset($this->entriesBuffer);
            $this->entriesBuffer = [];
        }
    }

    /**
     * Commit entries to DB
     */
    protected function commitEntries() {
        $this->userdataRepository->bulkInsert($this->entriesBuffer);
    }

    /**
     * Validate:
     * - identifier uniqueness
     * - card_number uniqueness
     * - field uniqueness against data existing in DB
     */
    protected function validateUpdate() {
        $this->userdataRepository->validateIdentifierDuplicates();
        $this->userdataRepository->validateCardNumberDuplicates();
        $this->userdataRepository->validateAgainstDb();
    }

    /**
     * Mark entries that are different between old and new table
     */
    protected function findDifference() {
        $this->userdataRepository->markEntriesToAdd();
        $this->userdataRepository->markEntriesToUpdate();
        $this->userdataRepository->markEntriesToRestore();
        $this->userdataRepository->markEntriesNotChanged();
    }

    /**
     * Return summary on current update.
     *
     * @return UpdateSummaryDTO
     */
    protected function calcSummary() : UpdateSummaryDTO {
        return $this->userdataRepository->getSummary();
    }

    /**
     * Apply all inserts, updates, deletes and restores.
     * Trigger events
     */
    protected function applyUpdate() {
        $this->userdataRepository->deleteRows();
        //$this->userdataRepository->restoreRows();
        //$this->userdataRepository->updateRows();
        //$this->userdataRepository->addRows();
        //$this->userdataRepository->reportNotChangedRows();
    }
}
