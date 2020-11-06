<?php

namespace App\Services;


/**
 * Allow to read from source.
 *
 * Class ReaderInterface
 * @package App\Services
 */
interface ReaderInterface
{
    /**
     * Return pointer to iterator.
     *
     * @param FileService $fileService
     * @return \Iterator
     * @throws \Exception
     */
    public function getFilePointer(FileService $fileService) : \Iterator;
}
