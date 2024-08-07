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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('created_by');
            $table->string('title');
            $table->string('category');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->text('description');
            $table->dateTime('complimentary_date')->nullable();
            $table->string('approval_status')->default("pending");
            $table->decimal('leave_count');
            $table->string('loss_of_pay')->nullable();
            $table->string('leave_type');
            $table->string('leave_session')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
