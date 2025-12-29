<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listini', function (Blueprint $table) {
            // IdListino (Numerazione automatica)
            $table->bigIncrements('id_listino');

            // Numeric (Access: Intero lungo)
            $table->integer('ordinamento')->nullable();

            // Testo breve
            $table->string('campo3')->nullable();
            $table->string('nome_modello')->nullable();        // "Nome Modello"
            $table->string('finiture_anta')->nullable();       // "Finiture Anta"
            $table->string('finiture_telaio')->nullable();     // "Finiture Telaio"
            $table->string('telaio')->nullable();
            $table->string('soluzioni')->nullable();
            $table->string('vetro')->nullable();

            // Numeric
            $table->integer('bt')->nullable();
            $table->integer('bt2s')->nullable();
            $table->integer('bt2a')->nullable();
            $table->integer('si')->nullable();
            $table->integer('sis')->nullable();
            $table->integer('si2m')->nullable();
            $table->integer('si2s')->nullable();
            $table->integer('se')->nullable();
            $table->integer('ses')->nullable();
            $table->integer('se2m')->nullable();
            $table->integer('se2s')->nullable();
            $table->integer('libs')->nullable();
            $table->integer('liba')->nullable();
            $table->integer('rt')->nullable();
            $table->integer('eslidem1')->nullable();
            $table->integer('eslides1')->nullable();
            $table->integer('eslidem2')->nullable();
            $table->integer('eslides2')->nullable();

            // Testo breve / Numeric
            $table->string('maniglie')->nullable();
            $table->integer('magg_lrg')->nullable();
            $table->integer('magg_lrg90')->nullable();
            $table->integer('magg_lrg100')->nullable();
            $table->integer('magg_lrg101')->nullable();
            $table->integer('magg_alt_minus')->nullable();     // "MaggAlt-"
            $table->integer('magg_tgl_obl')->nullable();       // "MaggTglObl"
            $table->integer('magg_alt_plus')->nullable();      // "MaggAlt+"

            $table->string('collezione')->nullable();
            $table->integer('magg_detrazioni')->nullable();
            $table->integer('magg_svetratura')->nullable();
            $table->string('note')->nullable();

            $table->integer('filtro_tit_tel')->nullable();     // "FiltroTitTel"

            $table->string('var_pred1')->nullable();
            $table->string('var_pred2')->nullable();
            $table->string('var_pred3')->nullable();
            $table->string('var_pred4')->nullable();

            // Sì/No
            $table->boolean('limiti_dim')->default(false);     // "LimitiDim"

            // Testo breve
            $table->string('dis_libro_simm')->nullable();      // "DisLibroSimm"
            $table->string('tipo_battuta')->nullable();        // "TipoBattuta"
            $table->string('campo1')->nullable();
            $table->string('campo2')->nullable();

            // Numeric / Testo breve / Numeric
            $table->integer('telp')->nullable();               // "TelP"
            $table->string('verifica_librb')->nullable();      // "VerificaLIBRB"
            $table->integer('librb')->nullable();              // "LibRB"

            // Testo breve
            $table->string('stampa_sp')->nullable();           // "StampaSp"
            $table->string('msg_errore')->nullable();          // "MsgErrore"

            // Sì/No
            $table->boolean('nascondi')->default(false);       // "Nascondi"

            // Testo breve (HManiglia*)
            $table->string('hmaniglia_bt')->nullable();
            $table->string('hmaniglia_bt2a')->nullable();
            $table->string('hmaniglia_bt2s')->nullable();
            $table->string('hmaniglia_eslidem1')->nullable();
            $table->string('hmaniglia_eslidem2')->nullable();
            $table->string('hmaniglia_liba')->nullable();
            $table->string('hmaniglia_librb')->nullable();
            $table->string('hmaniglia_libs')->nullable();
            $table->string('hmaniglia_rt')->nullable();
            $table->string('hmaniglia_se')->nullable();
            $table->string('hmaniglia_se2m')->nullable();
            $table->string('hmaniglia_se2s')->nullable();
            $table->string('hmaniglia_ses')->nullable();
            $table->string('hmaniglia_si')->nullable();
            $table->string('hmaniglia_si2m')->nullable();
            $table->string('hmaniglia_si2s')->nullable();
            $table->string('hmaniglia_sis')->nullable();
            $table->string('hmaniglia_telbt')->nullable();
            $table->string('hmaniglia_telp')->nullable();
            $table->string('hmaniglia_telsi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listini');
    }
};
