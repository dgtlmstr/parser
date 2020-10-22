<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class Filer
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
     * Create and return filename with full path basing on pproperties defined.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->folder . $this->filename;
    }

    /**
     * Check if file exists.
     *
     * @return bool
     */
    public function fileExists() {
        return File::exists($this->getFilePath());
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
        return File::size($this->getFilePath()) == 0;
    }

    /**
     * Return file pointer to read data from file
     *
     * @return false|resource
     */
    public function getFilePointerForReading() {
        return fopen($this->getFilePath(), 'r');
    }
}
