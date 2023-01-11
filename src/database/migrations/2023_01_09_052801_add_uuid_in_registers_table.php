<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('wp')->table('nwds_registration', function (Blueprint $table) {
            if(!Schema::connection('wp')->hasColumn('nwds_registration', 'uuid')){
                $table->uuid('uuid')->after('id')->nullable();
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
        if (Schema::connection('wp')->hasColumn('nwds_registration', 'uuid')) {
            Schema::connection('wp')->dropColumns('uuid');
        }
    }
};
