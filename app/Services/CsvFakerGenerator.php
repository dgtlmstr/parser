<?php

namespace App\Services;

use App\Config\UserDataConfig;
use Faker\Factory as Faker;

/**
 * CSV random file generator
 *
 * @package App\Services
 */
class CsvFakerGenerator
{
    /**
     * @var UserDataConfig
     */
    private $config;

    /**
     * CsvFakerGenerator constructor.
     * @param Filer $filer
     * @param UserDataConfig $config
     */
    public function __construct(UserDataConfig $config)
    {
        $this->config = $config;
    }

    /**
     * Generate random CSV file on the Config basis.
     *
     * @param Filer $filer
     * @param $rowNumber
     */
    public function generateCsv(Filer $filer, $rowNumber) {
        //todo: take into account Config

        $filePointer = $filer->getFilePointerForWriting();
        $faker = Faker::create();

        $row = ['Identifier', 'Name', 'Last Name', 'Card'];
        fputcsv($filePointer, $row, "\t", '"', "\\");
        for ($i = 0; $i < $rowNumber; $i++) {
            $row = [
                $faker->randomNumber(4),
                $faker->firstName,
                $faker->lastName,
                $faker->creditCardNumber
            ];
            fputcsv($filePointer, $row, "\t", '"', "\\");
        }
    }
}
