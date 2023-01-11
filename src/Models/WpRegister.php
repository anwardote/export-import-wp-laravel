<?php

namespace Anwardote\ExportImportWpLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpRegister extends Model
{
    use HasFactory;

    protected $connection = 'wp';
    protected $table = 'nwds_registration';

    public $fillable = ['user_uuid','uuid','driving_ed_class_date_id','new_course_type_id','driving_program_id'];

    public function registerTime()
    {
        $this->hasMany(WpRegisterPiv::class, 'uuid','r_uuid',);
    }

}
