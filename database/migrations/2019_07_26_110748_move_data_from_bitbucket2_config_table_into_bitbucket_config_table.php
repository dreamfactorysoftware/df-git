<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveDataFromBitbucket2ConfigTableIntoBitbucketConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $bitbucket2_config_data = DB::table("bitbucket2_config")->get();
        foreach($bitbucket2_config_data as $bitbucket_record){
            if (!(DB::table('bitbucket_config')->where('service_id', '=', $bitbucket_record->service_id)->get()->count() > 0)) {
                DB::table('bitbucket_config')->insert([
                    'service_id' => $bitbucket_record->service_id,
                    'vendor'     => $bitbucket_record->vendor,
                    'username'   => $bitbucket_record->username,
                    'password'   => $bitbucket_record->password,
                    'token'      => $bitbucket_record->token,
                ]);
            }
        }
        DB::table("bitbucket2_config")->delete();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $bitbucket_config_data = DB::table("bitbucket_config")->get();
        foreach($bitbucket_config_data as $bitbucket_record){
            if(!(DB::table('bitbucket2_config')->where('service_id', '=', $bitbucket_record->service_id)->get()->count() > 0)){
                DB::table('bitbucket2_config')->insert([
                    'service_id' => $bitbucket_record->service_id, 
                    'vendor'     => $bitbucket_record->vendor,
                    'username'   => $bitbucket_record->username,
                    'password'   => $bitbucket_record->password,
                    'token'      => $bitbucket_record->token,
                ]);
            }
        }
        DB::table("bitbucket_config")->delete();
    }
}
