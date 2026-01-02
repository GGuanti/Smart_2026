<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tab_maniglie', function (Blueprint $table) {
            $table->bigIncrements('IdManiglia');

            $table->string('DesMan', 191);
            $table->decimal('Importo', 10, 2)->default(0);

            $table->string('CodiceList', 191)->nullable();
            $table->string('Filtroman', 191)->nullable();

            $table->timestamps();

            // opzionali (se ti servono ricerche veloci)
            $table->index('CodiceList');
            $table->index('Filtroman');
            $table->index('DesMan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tab_maniglie');
    }
};
