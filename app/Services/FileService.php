<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class FileService implements FileServiceInterface
{
    /**
     * Filename without path.
     *
     * @var string
     */
    protected $filename;

    /**
     * Path with trailing slash.
     *
     * @var string
     */
    protected $folder;

    /**
     * Create an instance of Filer class.
     */
    public function __construct() {
    }

    /**
     * Set filename.
     *
     * @param $filename
     */
    public function setFilename($filename) {
        $this->filename = $filename;
    }

    /**
     * Set folder.
     *
     * @param $folder
     */
    public function setFolder($folder) {
        $this->folder = $folder;
    }

    /**
     * Create and return filename with full path basing on properties defined.
     *
     * @return string
     */
    public function getFullFileName(): string
    {
        return $this->folder . $this->filename;
    }

    /**
     * Check if file exists.
     *
     * @return bool
     */
    public function fileExists() {
        return File::exists($this->getFullFileName());
    }

    /**
     * Remove file.
     */
    public function fileRemove() {
        //File::delete($this->getFilePath());
    }

    /**
     * Return true in case of empty file, otherwise false
     *
     * @return bool
     */
    public function isFileEmpty() {
        return File::size($this->getFullFileName()) == 0;
    }

    /**
     * Return file pointer to read data from file
     *
     * @return false|resource
     */
    public function getFilePointerForReading() {
        return fopen($this->getFullFileName(), 'r');
    }

    /**
     * Return file pointer to write data to file
     *
     * @return false|resource
     */
    public function getFilePointerForWriting() {
        return fopen($this->getFullFileName(), 'w');
    }

    /**
     * Return file pointer to write data to file
     *
     * @return false|resource
     */
    public function getFilePointerForAdding() {
        return fopen($this->getFullFileName(), 'a');
    }
}
