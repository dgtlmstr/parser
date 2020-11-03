<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Illuminate\Database\Eloquent\Model;

/**
 * Entry temporary table entity.
 *
 * @package App\Models
 */
class UserData extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'userdata';
}
