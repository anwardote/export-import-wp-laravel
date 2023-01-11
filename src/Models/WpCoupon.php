<?php

namespace Anwardote\ExportImportWpLaravel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WpCoupon extends Model
{
    protected $connection = 'wp';
    protected $table = "nwds_coupon_codes";
}
