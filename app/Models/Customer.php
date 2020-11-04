<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Customer entity.
 *
 * @package App\Models
 */
class Customer extends Model
{
    use SoftDeletes;
}
