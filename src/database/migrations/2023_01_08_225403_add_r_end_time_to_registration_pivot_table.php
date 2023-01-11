<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('wp')->table('nwds_registration_pivot', function (Blueprint $table) {
            if(!Schema::connection('wp')->hasColumn('nwds_registration_pivot', 'r_end_date_time')){
                $table->dateTime('r_end_date_time')->after('r_time')->nullable();
            }

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::connection('wp')->hasColumn('nwds_registration_pivot', 'r_end_date_time')) {
            Schema::connection('wp')->dropColumns('r_end_date_time');
        }
    }
};
