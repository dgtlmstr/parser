<?php

namespace App\Services;

interface FilerInterface
{
    /**
     * Set filename.
     *
     * @param $filename
     */
    public function setFilename($filename);

    /**
     * Set folder.
     *
     * @param $folder
     */
    public function setFolder($folder);

    /**
     * Create and return filename with full path basing on pproperties defined.
     *
     * @return string
     */
    public function getFilePath(): string;

    /**
     * Check if file exists.
     *
     * @return bool
     */
    public function fileExists();

    /**
     * Remove file.
     */
    public function fileRemove();

    /**
     * Return true in case of empty file, otherwise false
     *
     * @return bool
     */
    public function isFileEmpty();

    /**
     * Return file pointer to read data from file
     *
     * @return false|resource
     */
    public function getFilePointerForReading();
}
