<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tipo_telaio', function (Blueprint $table) {
            $table->bigIncrements('id_tipo_telaio');

            // TESTO
            $table->string('stipite', 191)->nullable();
            $table->string('filtro', 191)->nullable();
            $table->string('filtro_colore', 191)->nullable();
            $table->string('filtro_soluzione', 191)->nullable();
            $table->string('filtro_imbotte', 191)->nullable();

            $table->string('cod_copr40', 191)->nullable();
            $table->string('cop_int', 191)->nullable();
            $table->string('cop_est', 191)->nullable();
            $table->string('cop_int_h', 191)->nullable();
            $table->string('cop_est_h', 191)->nullable();
            $table->string('kit_scor1', 191)->nullable();
            $table->string('kit_scor2', 191)->nullable();

            $table->string('st80r229', 191)->nullable();
            $table->string('st110r229', 191)->nullable();
            $table->string('st130r229', 191)->nullable();

            $table->string('st80r249', 191)->nullable();
            $table->string('st110r249', 191)->nullable();
            $table->string('st130r249', 191)->nullable();

            $table->string('st80r60', 191)->nullable();
            $table->string('st110r60', 191)->nullable();
            $table->string('st130r60', 191)->nullable();

            $table->string('st80r2p', 191)->nullable();
            $table->string('st110r2p', 191)->nullable();
            $table->string('st130r2p', 191)->nullable();

            $table->string('st80r60p', 191)->nullable();
            $table->string('st110r60p', 191)->nullable();
            $table->string('st130r60p', 191)->nullable();

            $table->string('st80rh49', 191)->nullable();
            $table->string('st110rh49', 191)->nullable();
            $table->string('st130rh49', 191)->nullable();

            $table->string('cod_se', 191)->nullable();
            $table->string('cod_sbt29', 191)->nullable();
            $table->string('cod_sbt49', 191)->nullable();
            $table->string('cod_sbt60', 191)->nullable();

            $table->string('cod_liba80', 191)->nullable();
            $table->string('cod_liba110', 191)->nullable();
            $table->string('cod_liba130', 191)->nullable();

            // NUMERICI / COSTI
            $table->decimal('cst_telp', 10, 2)->nullable();
            $table->string('cod_telp80', 191)->nullable();
            $table->string('cod_telp110', 191)->nullable();
            $table->string('cod_telp130', 191)->nullable();

            $table->string('cod_imb_foglio', 191)->nullable();
            $table->string('cod_imb_fascia', 191)->nullable();
            $table->string('cod_imbottino', 191)->nullable();

            $table->decimal('cst_tel_bt', 10, 2)->nullable();
            $table->decimal('cst_tel_si', 10, 2)->nullable();

            $table->string('filtro_imbotte2', 191)->nullable();

            $table->decimal('magg_kit_scorr', 10, 2)->nullable();

            $table->integer('ordinamento')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_telaio');
    }
};
