<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            //$table->integer('user_id');
           	$table->string('team_name');
            $table->string('description');
            $table->unsignedBigInteger('project_manager_id');
            $table->unsignedBigInteger('frontend_team_lead_id');
            $table->unsignedBigInteger('backend_team_lead_id');
            $table->timestamps();

           /* $table->foreign('user_id')->references('id')->on('users')
                    ->onDelete('cascade');*/
            $table->foreign('project_manager_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('frontend_team_lead_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('backend_team_lead_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
