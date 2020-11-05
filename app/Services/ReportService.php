<?php


namespace App\Services;

/**
 * Class handling reporting.
 *
 * @package App\Services
 */
class ReportService
{
    /**
     * @var FileService
     */
    private $fileService;

    /**
     * Report file pointer to write information to.
     *
     * @var false|resource
     */
    private $filePointer;

    /**
     * Reporter constructor.
     * @param FileService $fileService
     */
    public function __construct(FileService $fileService) {
        $this->fileService = $fileService;
        $this->fileService->setFolder(env("REPORT_DIR_PATH"));
        $this->fileService->setFilename(env("REPORT_FILENAME"));

        $this->filePointer = $this->fileService->getFilePointerForAdding();
    }

    /**
     * Add line to report.
     *
     * @param int $reportStatus
     * @param string $line
     */
    public function line(int $reportStatus, string $line) {
        fwrite($this->filePointer, $line . "\n");
    }

    /**
     * Add block to report.
     *
     * @param int $reportStatus
     * @param string $block
     */
    public function block(int $reportStatus, string $block) {
        fwrite($this->filePointer, $block);
    }


}
