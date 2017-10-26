<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNamespaceFieldToGitlabConfig extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('gitlab_config') && !Schema::hasColumn('gitlab_config', 'namespace')) {
            Schema::table('gitlab_config', function (Blueprint $t){
                $t->string('namespace')->nullable()->after('base_url');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('gitlab_config') && Schema::hasColumn('gitlab_config', 'namespace')) {
            Schema::table('script_config', function (Blueprint $t){
                $t->dropColumn('namespace');
            });
        }
    }
}
