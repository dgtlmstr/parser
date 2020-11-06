<?php
namespace App\Services;

use App\Repositories\EntryRepository;

/**
 * Allow to report parse details and handle warnings.
 * @package App\Services
 */
class ParseReportManager
{
    /**
     * @var ReportService
     */
    protected $reportService;

    /**
     * @var ParseSummaryManager
     */
    protected $summaryManager;

    /**
     * Array of warning messages.
     *
     * @var array
     */
    protected $warnings = [];

    /**
     * @var EntryRepository
     */
    protected $userDataRepository;

    /**
     * ParseReporter constructor.
     * @param ReportService $reportService
     */
    public function __construct(ReportService $reportService, EntryRepository $userDataRepository) {
        $this->reportService = $reportService;
        $this->userDataRepository = $userDataRepository;
    }

    /**
     * Set Summary Manager
     *
     * @param ParseSummaryManager $summaryManager
     */
    public function setSummaryManager(ParseSummaryManager $summaryManager) {
        $this->summaryManager = $summaryManager;
    }

    /**
     * Output summary stats to the Report Service.
     */
    public function reportSummary() {
        $this->reportService->block(REPORT_STATUS_INFO, $this->getSummary());
    }

    /**
     * Send to report all invalid entries.
     */
    public function reportRejectedEntries() {
        $this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_REJECTED), "Rejected entries");

        //$this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_PARSE_BAD_ID), "Bad Identifier entries");
        //$this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_PARSE_ERROR), "Parse error entries");
        //$this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_ID_DUPLICATE), "Identifier duplicate entries");
        //$this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_CARDNUMBER_DUPLICATE), "Card number dupicate entries");
        //$this->reportCollection($this->userDataRepository->getEntriesWithStatusId(ENTRY_STATUS_CARDNUMBER_ALREADY_TAKEN), "Card number already taken entries");
    }

    /**
     * Send to report each entry from the collection.
     *
     * @param $entryCollection
     * @param string $title
     */
    public function reportCollection($entryCollection, $title = "") {
        if (!empty($title)) {
            $this->reportService->line(REPORT_STATUS_INFO, $title);
        }

        foreach ($entryCollection as $entry) {
            $line = "Line {$entry->id}: Identifier={$entry->identifier}, {$entry->status_details}";
            $this->reportService->line(REPORT_STATUS_INFO, $line);
        }
    }

    /**
     * Return summary as a text block.
     *
     * @return string
     */
    public function getSummary() : string {
        $result = "";
        $result .= "Rejected entries: " . $this->summaryManager->getToRejectCount() . "\n";
        $result .= "Entries to add: " . $this->summaryManager->getToAddCount() . "\n";
        $result .= "Entries to update: " . $this->summaryManager->getToUpdateCount() . "\n";
        $result .= "Entries to restore: " . $this->summaryManager->getToRestoreCount() . "\n";
        $result .= "Entries to delete: " . $this->summaryManager->getToDeleteCount() . "\n";
        $result .= "Entries can't be deleted: " . $this->summaryManager->getCantDeleteCount() . "\n";
        $result .= "Entries not changed: " . $this->summaryManager->getNotChangedCount() . "\n";
        $result .= "Total entries: " . $this->summaryManager->getTotalEntryCount() . "\n";

        return $result;
    }

    /**
     * Add warning message to the warning buffer.
     *
     * @param $warning
     */
    public function addWarning($warning) {
        $this->warnings[] = $warning;
    }

    /**
     * Check if there are warnings.
     *
     * @return bool
     */
    public function hasWarnings() {
        return count($this->warnings) > 0;
    }

    /**
     * Return warnings and clear warning buffer.
     */
    public function getWarnings() {
        $result = $this->warnings;
        $this->warnings = [];
        return $result;
    }

    /**
     * Output message via Report Service.
     *
     * @param string $message
     */
    public function reportEntryProcessing(string $message){
        $this->reportService->line(REPORT_STATUS_INFO, $message);
    }
}
