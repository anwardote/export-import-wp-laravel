<?php

namespace Anwardote\ExportImportWpLaravel\Http\Controllers;


use Anwardote\ExportImportWpLaravel\Models\WpUser;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function index()
    {
        /*
         SELECT DISTINCT wp_9t854h_nwds_registration.course_type_id, wp_9t854h_nwds_course_types.name
FROM `wp_9t854h_nwds_registration` left join wp_9t854h_nwds_course_types
on wp_9t854h_nwds_registration.course_type_id = wp_9t854h_nwds_course_types.id;
         */
        $wpUsers = WpUser::query()
            ->withWhereHas('userMeta', function ($query) {
//                $query->
            })
//            ->limit(10)->get();
            ->count();
        dd($wpUsers);
    }
}
