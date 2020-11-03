<?php
use App\Services;
use App\Services\FilerInterface;

class UserdataFileFaker implements FilerInterface
{
    protected const FILENAME = 'userdata1.csv';

    public function setFilename($filename) {
    }

    public function setFolder($folder) {
    }

    public function getFullFileName(): string {
        return './';
    }

    public function fileExists() {
        return true;
    }

    public function fileRemove() {
    }

    public function isFileEmpty()
    {
        return false;
    }

    public function getFilePointerForReading()
    {
        // TODO: Implement getFilePointerForReading() method.
    }
}
