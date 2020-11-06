<?php

namespace App\Models;

use Doctrine\ORM\Mapping as ORM;
use Illuminate\Database\Eloquent\Model;

/**
 * Entry Status temporary table entity.
 *
 * @package App\Models
 */
class EntryStatus extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'entry_statuses';
}
