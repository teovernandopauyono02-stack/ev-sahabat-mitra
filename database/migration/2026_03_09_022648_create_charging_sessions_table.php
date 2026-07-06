<?php

use Illuminate\Database\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migration.
     */
   public function up()
{
            Schema::create('charging_session', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id');
                $table->foreignId('charger_id');
                $table->float('energy_used');
                $table->integer('charging_time');
                $table->date('date');
                $table->timestamps();
            });
        }
        
    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('charging_session');
    }
};
