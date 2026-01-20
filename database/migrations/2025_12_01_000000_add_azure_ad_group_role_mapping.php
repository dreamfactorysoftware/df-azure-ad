<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAzureAdGroupRoleMapping extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add map_group_to_role column to azure_ad_config table
        if (!Schema::hasColumn('azure_ad_config', 'map_group_to_role')) {
            Schema::table(
                'azure_ad_config',
                function (Blueprint $t) {
                    $t->boolean('map_group_to_role')->default(0);
                }
            );
        }

        // Create role_azure_ad table for mapping Entra ID groups to DreamFactory roles
        if (!Schema::hasTable('role_azure_ad')) {
            Schema::create(
                'role_azure_ad',
                function (Blueprint $t) {
                    $t->integer('role_id')->unsigned()->primary();
                    $t->foreign('role_id')->references('id')->on('role')->onDelete('cascade');
                    $t->string('group_name', 255);
                    $t->index('group_name');
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop role_azure_ad table
        Schema::dropIfExists('role_azure_ad');

        // Drop map_group_to_role column from azure_ad_config table
        if (Schema::hasColumn('azure_ad_config', 'map_group_to_role')) {
            Schema::table(
                'azure_ad_config',
                function (Blueprint $t) {
                    $t->dropColumn('map_group_to_role');
                }
            );
        }
    }
}
