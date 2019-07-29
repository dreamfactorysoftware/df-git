<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBitbucket2ConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'bitbucket2_config',
            function (Blueprint $t) {
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->string('vendor');
                $t->string('username')->nullable();
                $t->text('password')->nullable();
                $t->text('token')->nullable();
            }
        );
        DB::table('service')->where('service.type', '=', 'bitbucket')
                            ->update(['service.type' => 'bitbucket2']);
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
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
        DB::table('service')->where('service.type', '=', 'bitbucket2')
                            ->update(['service.type' => 'bitbucket']);
        DB::table("bitbucket2_config")->delete();
        Schema::dropIfExists('bitbucket2_config');
    }
}
