<?php

namespace Anwardote\ExportImportWpLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpRegisterPiv extends Model
{
    protected $connection = 'wp';
    protected $table = 'nwds_registration_pivot';
}
