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
        Schema::connection('wp')->table('users', function (Blueprint $table) {
            if(!Schema::connection('wp')->hasColumn('users', 'app_uuid')){
                $table->uuid('app_uuid')->after('ID')->nullable();
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
        if (Schema::connection('wp')->hasColumn('users', 'app_uuid')) {
            Schema::connection('wp')->dropColumns('app_uuid');
        }
    }
};
