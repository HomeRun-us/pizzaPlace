<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('customer_user_id');
            $table->unsignedInteger('seller_user_id');
            $table->text('address')->nullable();
            $table->timestamp('time')->nullable()->useCurrent();
            $table->boolean('sent')->default(0);
            $table->timestamps();

            $table->foreign('')->references('id')->on('')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('')->references('id')->on('')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}