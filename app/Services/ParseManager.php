<?php
namespace App\Services;

use App\Repositories\UserDataRepository;

/**
 * Feed manager allowing to handle and apply CSV update.
 *
 * @package App\Services
 */
class ParseManager {

    /**
     * Constant representing the threshold limit.
     */
    public const UPDATE_THRESHOLD_LIMIT = 1000;

    /**
     * Desired mode to run parser in.
     * Either normal with DB updates or dry with reporting only.
     *
     * @var int
     */
    protected $runMode = RUN_MODE_NORMAL;

    /**
     * Flag determining if threshold limit should be taken into account.
     * Limit is applied by default (false value).
     *
     * @var bool
     */
    protected $ignoreThresholdLimit = false;

    /**
     * The instance of the Userdata Repository.
     *
     * @var UserDataRepository
     */
    protected $userDataRepository;

    /**
     * The Summary instance.
     *
     * @var ParseSummaryManager
     */
    protected $summaryManager;

    /**
     * The Parse Report Service instance.
     *
     * @var ParseReportManager
     */
    protected $reportManager;

    /**
     * Csv Reader.
     *
     * @var ReaderInterface
     */
    protected $csvReader;

    /**
     * The Parse Csv Manager.
     *
     * @var ParseCsvManager
     */
    protected $csvManager;

    /**
     * @var ParseDatabaseManager
     */
    protected $dbManager;

    /**
     * Create a new Parser instance.
     *
     * @param FileService $fileService
     * @param ReaderInterface $csvParser
     * @param ParseReportManager $parseReportManager
     * @param ParseSummaryManager $summaryManager
     * @param UserDataRepository $userDataRepository
     * @param ParseCsvManager $csvManager
     * @param ParseDatabaseManager $dbManager
     */
    public function __construct(
        FileService $fileService,
        ReaderInterface $csvParser,
        ParseReportManager $parseReportManager,
        ParseSummaryManager $summaryManager,
        UserDataRepository $userDataRepository,
        ParseCsvManager $csvManager,
        ParseDatabaseManager $dbManager
    ){
        $this->fileService = $fileService;
        $this->fileService->setFolder(env("UPDATE_DIR_PATH"));
        $this->fileService->setFilename(env("UPDATE_FILENAME"));

        $this->csvReader = $csvParser;
        $this->csvManager = $csvManager;
        $this->userDataRepository = $userDataRepository;
        $this->summaryManager = $summaryManager;

        $this->reportManager = $parseReportManager;
        $this->reportManager->setSummaryManager($this->summaryManager);

        $this->dbManager = $dbManager;
        $this->dbManager->setReportManager($this->reportManager);
    }

    /**
     * Synchronise user data from external source file with DB.
     *
     * 1. Create temporary table for entries.
     * 2. Parse CSV, validate raw data and add entries to the temporary table.
     * 3. Validate entries in DB (check duplicates, find rows that can't be deleted, etc) - mark invalid rows.
     * 4. Mark entries for operations.
     * 5. Calculate summary.
     * 6. Apply update if it's not a dry run.
     *
     * Throws exception on failure.
     *
     * @throws \Exception
     */
    public function processFeed() {
        $this->dbManager->createTemporaryTables();

        if (!$this->csvManager->processCsv()) {
            throw new \Exception("Parse CSV error"); //todo: custom exceptions
        }

        $this->dbManager->validateEntries();

        $this->dbManager->defineActionsForEntries();
        $this->summaryManager->calcSummary();

        if ($this->checkIfUpdateCanBeApplied()) {
            $this->dbManager->applyUpdate();
        }

        $this->summaryManager->calcSummary(); // recalc
    }

    /**
     * Return parse report service.
     */
    public function getReportManager() : ParseReportManager {
        return $this->reportManager;
    }

    /**
     * Remove user data source file.
     */
    public function DeleteUpdate() {
        $this->fileService->fileRemove();
    }

    /**
     * Calculate summary on current update.
     * @deprecated
     */
    protected function calcSummary() {
        $this->summaryManager->setSummary($this->userDataRepository->getSummary());
        // make few requests for better code
        $this->summaryManager->setToDeleteCount($this->userDataRepository->countEntriesToDelete());
        $this->summaryManager->setCantDeleteCount($this->userDataRepository->countEntriesCantDelete());
        $this->summaryManager->setTotalEntryCount($this->userDataRepository->countTotalEntries());
    }

    /**
     * Check:
     * - if it's a dry run mode
     * - if number of records to be updated within threshold,
     *
     * @returns bool
     */
    protected function checkIfUpdateCanBeApplied() : bool {
        $result = true;

        if ($this->isInDryMode()) {
            $this->reportManager->addWarning("Dry run mode. Update can't be applied");
            $result = false;
        }

        if ($this->summaryManager->getParseBadIdCount() > 0) {
            $this->reportManager->addWarning("Parse bad identifier errors. Update can't be applied");
            $result = false;
        }

        if ($this->summaryManager->getToDeleteCount() >= self::UPDATE_THRESHOLD_LIMIT ||
            $this->summaryManager->getToUpdateCount() >= self::UPDATE_THRESHOLD_LIMIT) {
            if (!$this->ignoreThresholdLimit) {
                $result = false;
                $this->reportManager->addWarning("Update row count is above threshold.");
            }
        }

        return $result;
    }

    /**
     * Set run mode, either normal or dry.
     *
     * @param $runMode
     */
    public function setRunMode($runMode) {
        if (!in_array($runMode, [RUN_MODE_NORMAL, RUN_MODE_DRY])) {
            $this->runMode = $runMode;
        }
    }

    /**
     * Report summary totals.
     * @deprecated
     */
    protected function reportSummary() {
        //make report and print,
        // files to be able to get info on process - another method (flag/option - create file or not)
        $this->reportManager->reportSummary();
    }

    /**
     * @param bool $ignoreThresholdLimit
     */
    public function setIgnoreThresholdLimit(bool $ignoreThresholdLimit): void
    {
        $this->ignoreThresholdLimit = $ignoreThresholdLimit;
    }

    /**
     * Checks if the parser run mode is dry.
     */
    protected function isInDryMode() {
        return $this->runMode == RUN_MODE_DRY;
    }
}
