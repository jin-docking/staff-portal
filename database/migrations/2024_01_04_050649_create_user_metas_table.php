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
        Schema::create('user_metas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('address');
            $table->bigInteger('contact_no');
            $table->string('gender');
            $table->dateTime('join_date');
            $table->dateTime('date_of_birth');
            $table->string('father');
            $table->string('mother');
            $table->string('spouse')->nullable();
            $table->string('children')->nullable();
            $table->integer('pincode');
            $table->string('aadhar');
            $table->string('pan');
            $table->string('profile_pic');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')
                    ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_datas');
    }
};
