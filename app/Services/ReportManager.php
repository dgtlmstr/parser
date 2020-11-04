<?php


namespace App\Services;

/**
 * Class handling reporting.
 *
 * @package App\Services
 */
class Reporter
{
    /**
     * Constants for report statuses.
     */
    const REPORT_STATUS_INFO = 1;

    /**
     * @var Filer
     */
    private $filer;

    /**
     * Report file pointer to add information to.
     *
     * @var false|resource
     */
    private $filePointer;

    /**
     * Reporter constructor.
     * @param Filer $filer
     */
    public function __construct(Filer $filer) {
        $this->filer = $filer;
        $this->filer->setFolder(env("REPORT_DIR_PATH"));
        $this->filer->setFilename(env("REPORT_FILENAME"));

        $this->filePointer = $this->filer->getFilePointerForAdding();
    }

    /**
     * Add message to report.
     *
     * @param int $reportStatus
     * @param string $message
     */
    public function report(int $reportStatus, string $message) {
        fwrite($this->filePointer, $message . "\n");
    }

}
