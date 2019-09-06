<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameServiceTypeFromBitbucket2ToBitbucket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('service')->where('type', '=', 'bitbucket2')
                            ->update(['type' => 'bitbucket']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('service')->where('type', '=', 'bitbucket')
                            ->update(['type' => 'bitbucket2']);
    }
}
