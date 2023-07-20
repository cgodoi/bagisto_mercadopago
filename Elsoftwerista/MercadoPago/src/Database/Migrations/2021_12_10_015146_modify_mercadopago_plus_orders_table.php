<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyMercadoPagoPlusOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('mercadopago_plus_orders')) {

            if (!Schema::hasColumn('mercadopago_plus_orders', 'response_detail')) {
                Schema::table('mercadopago_plus_orders', function (Blueprint $table) {
                    $table->longText('response_detail')->nullable();
                });

            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
}
