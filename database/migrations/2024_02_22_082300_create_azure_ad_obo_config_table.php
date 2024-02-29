<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAzureAdOboConfigTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(
            'azure_ad_obo_config',
            function (Blueprint $t){
                $t->integer('service_id')->unsigned()->primary();
                $t->foreign('service_id')->references('id')->on('service')->onDelete('cascade');
                $t->integer('default_role')->unsigned()->nullable();
                $t->foreign('default_role')->references('id')->on('role');
                $t->string('client_id');
                $t->longText('client_secret');
                $t->string('client_resource_scope');
                $t->string('api_resource_scope');
                $t->string('redirect_url');
                $t->string('icon_class')->nullable();
                $t->string('tenant_id')->default('common');
                $t->string('user_resource')->nullable()->default('https://graph.windows.net/');
            }
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('azure_ad_obo_config');
    }
}
