<?php
namespace App\Services;

use App\DTO\UpdateSummaryDTO;
use App\Repositories\UserdataRepository;

class Parser {

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
    private $updateRepository;

    /**
     * The summary stats DTO instance
     *
     * @var UpdateSummaryDTO
     */
    private $summary;

    /**
     * Create a new Parser instance.
     *
     * @param Filer $filer
     * @param UserdataRepository $updateRepository
     */
    public function __construct(Filer $filer, UserdataRepository $updateRepository){
        $this->filer = $filer;
        $this->filer->setFolder(env("UPDATE_DIR_PATH"));
        $this->filer->setFilename(env("UPDATE_FILENAME"));

        $this->updateRepository = $updateRepository;
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

        try {
            $this->updateRepository->prepare();
            $this->updateRepository->uploadUpdate($this->filer);
            $this->updateRepository->validate();
            $this->updateRepository->calcSummary();
            $this->updateRepository->applyUpdate();
            $this->summary = $this->updateRepository->getSummary();
        } catch (\Exception $e) {
            //Todo: logging
            return self::PARSER_FAILED;
        }

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
}
