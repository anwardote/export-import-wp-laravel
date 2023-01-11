<?php

namespace Anwardote\ExportImportWpLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpUser extends Model
{
    use HasFactory;

    protected $connection = 'wp';
    protected $table = 'users';

    protected $fillable = ['app_uuid'];
    protected $primaryKey = 'ID';

    public $timestamps = false;

    public function userMeta()
    {
        return $this->hasMany(WpUsermeta::class, 'user_id', 'ID');
    }

    public function userRegister()
    {
        return $this->hasMany(WpRegister::class, 'user_id', 'ID');
    }

    public function instructorRegister()
    {
        return $this->hasMany(WpRegister::class, 'instructor_id', 'ID');
    }
}
