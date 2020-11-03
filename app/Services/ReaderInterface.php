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
     * @param Filer $filer
     * @return \Iterator
     * @throws \Exception
     */
    public function getFilePointer(Filer $filer) : \Iterator;
}