<?php

namespace Anwardote\ExportImportWpLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpUsermeta extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','meta_key','meta_value'];
    protected $connection = 'wp';
    protected $table='usermeta';
    public $timestamps = false;

}
