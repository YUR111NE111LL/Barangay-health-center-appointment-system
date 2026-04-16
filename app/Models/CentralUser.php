<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

/**
 * Users stored on the central database (e.g. Super Admin) for relationships from central models.
 */
class CentralUser extends Model
{
    use CentralConnection;

    protected $table = 'users';

    protected $guarded = [];
}
