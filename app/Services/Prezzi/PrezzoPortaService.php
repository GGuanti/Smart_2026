<?php

namespace App\Services\Prezzi;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PrezzoPortaService
{

    public function calcola(array $p): float
    {
        $tag = 'PREZZO_CAD';
        // ✅ 0) INPUT
        Log::info("[$tag] INPUT", $p);
        // helper per loggare numeri in modo consistente
        $logNum = function (string $k, $v) use ($tag) {
            Log::info("[$tag] $k = " . (is_scalar($v) ? (string)$v : json_encode($v)));
        };
        // === 1) carico dati base ===
        $idModello   = (int)($p['IdModello'] ?? 0);
        $idSoluzione = (int)($p['IdSoluzione'] ?? 0);
        $NANTE       = (int)($p['NANTE'] ?? 0);
        $IdTipTelaio   = (int)($p['IdTipTelaio'] ?? 0);
        $logNum('IdModello', $idModello);
        $logNum('IdSoluzione', $idSoluzione);
        $logNum('IdTipTelaio', $IdTipTelaio);
        $logNum('NANTE (input)', $NANTE);
        $listino = DB::table('listini')->where('id_listino', $idModello)->first();
        if (!$listino) {
            Log::warning("[$tag] listino NOT FOUND", ['id_listino' => $idModello]);
            return 0.0;
        }
        Log::info("[$tag] listino FOUND", [
            'id_listino'   => $listino->id_listino ?? null,
            'nome_modello' => $listino->nome_modello ?? null,
            'collezione'   => $listino->collezione ?? null,
        ]);
        $sol = DB::table('tab_soluzioni')->where('id_tab_soluzioni', $idSoluzione)->first();
        if (!$sol) {
            Log::warning("[$tag] tab_soluzioni NOT FOUND", ['id_tab_soluzioni' => $idSoluzione]);
            return 0.0;
        }
        // se NANTE non arriva dal client, prendilo da tab_soluzioni.nante
        if ($NANTE <= 0) {
            $NANTE = (int)($sol->nante ?? 0);
            $logNum('NANTE (from tab_soluzioni.nante)', $NANTE);
        }
        if ($NANTE <= 0) $NANTE = 1;
        $maggListino = 1; // se poi lo prendi da impostazioni.CF, logga anche quello
        $colonnaListino = strtoupper(trim((string)($sol->ass_collistino ?? '')));
        $modelloNome    = trim((string)($listino->nome_modello ?? ''));
        $collezione     = trim((string)($listino->collezione ?? ''));
        $maggMan = 0.0;
        if (!empty($p['IdManiglia'])) {
            $maggMan = (float) DB::table('tab_maniglie')
                ->where('idmaniglia', (int)$p['IdManiglia'])
                ->value('importo');
        }
        $maggFascMant = (float)($sol->magg_fasc_mant ?? 0);
        $maggColAnta = 0.0;
        if (!empty($p['IdColAnta'])) {
            $maggColAnta = (float) DB::table('finitura_anta')
                ->where('IdFinAnta', (int)$p['IdColAnta'])
                ->value('MaggAnta');
            $logNum('maggColAnta base (finitura_anta.MaggAnta)', $maggColAnta);
            $maggColAnta *= $NANTE;
        }
        $logNum('IdColAnta', $p['IdColAnta'] ?? null);
        $logNum('maggColAnta * NANTE', $maggColAnta);

        $maggSerr = 0.0;
        if (!empty($p['IdSerratura'])) {
            $maggSerr = (float) DB::table('tab_serratura')
                ->where('id_serratura', (int)$p['IdSerratura'])
                ->value('importo');
        }

        $maggCern = 0.0;
        if (!empty($p['IdColFerr'])) {
            $maggCern = (float) DB::table('tab_cerniere')
                ->where('id_col_ferr', (int)$p['IdColFerr'])
                ->value('importo');
            $logNum('maggCern base (tab_cerniere.importo)', $maggCern);
            $maggCern *= $NANTE;
        }

        // === 3) VETRO: colonna dinamica ===
        $filtroVetro = $this->colonnaListVetroByModello($modelloNome);
        $logNum('filtroVetro (colonna dinamica da ass_mod_vetri)', $filtroVetro);

        $maggVetro = $this->calcolaVetro($filtroVetro, $modelloNome, array_merge($p, ['NANTE' => $NANTE]));
        $logNum('maggVetro (final)', $maggVetro);

        // === 4) PREZZO BASE LISTINO (colonna dinamica BT/RT/...) ===
        $safeCol = $this->safeCol($colonnaListino);
        $logNum('safeCol(colonnaListino)', $safeCol);

        // logga anche se la proprietà non esiste
        $listinoArr = (array)$listino;
        $exists = array_key_exists($safeCol, $listinoArr) || array_key_exists(strtolower($safeCol), array_change_key_case($listinoArr, CASE_LOWER));
        $logNum("listino has column {$safeCol} ?", $exists ? 'YES' : 'NO');

        $listinoPorta = (float)($listino->{$safeCol} ?? 0);
        $logNum('listinoPorta base', $listinoPorta);

        if (in_array($colonnaListino, ['BT', 'BT2A', 'BT2S', 'LIBA', 'RT'], true)) {
            $md = (float)($listino->magg_detrazioni ?? 0);
            $logNum('magg_detrazioni (listini.magg_detrazioni)', $md);
            $listinoPorta += $md;
        }
        $logNum('listinoPorta after MaggDetrazioni', $listinoPorta);

        if (in_array(strtoupper($modelloNome), ['GS1', 'GS2', 'GS3', 'GS4'], true)) {
            $listinoPorta *= $NANTE;
            $logNum('listinoPorta after GS* * NANTE', $listinoPorta);
        }


        $cstTelP = 0.0;
        $maggKitScFM   = 0.0;
        if (!empty($p['IdTipTelaio'])) {
            $maggKitScFM = (float) DB::table('tipo_telaio')
                ->where('id_tipo_telaio', (int) $p['IdTipTelaio'])
                ->value('magg_kit_scorr');

            switch ($colonnaListino) {

                case 'TELBT':
                    // Telaio incluso
                    $cstTelP = (float) DB::table('tipo_telaio')
                        ->where('id_tipo_telaio', (int) $p['IdTipTelaio'])
                        ->value('cst_telbt');
                    break;

                case 'TELP':
                    // Prezzo telaio particolare
                    $cstTelP = (float) DB::table('tipo_telaio')
                        ->where('id_tipo_telaio', (int) $p['IdTipTelaio'])
                        ->value('cst_telp');
                    break;
                case 'TELSI':
                    // Prezzo telaio particolare
                    $cstTelP = (float) DB::table('tipo_telaio')
                        ->where('id_tipo_telaio', (int) $p['IdTipTelaio'])
                        ->value('cst_telsi');
                    break;

                default:
                    // Prezzo telaio standard

                    $cstTelP = 0.0;
                    break;
            }
        }


        // === 5) placeholder ===
        $totMaggTelaio = 0.0;
        $totMaggAnta   = 0.0;
        $magDetTelaio  = 0.0;
        $maggImbSI     = 0.0;


        // === 6) somma finale stile VBA (log parziali) ===
        $prezzo = 0.0;

        $add = function (string $name, float $val) use (&$prezzo, $logNum) {
            $prezzo += $val;
            $logNum("ADD {$name}", $val);
            $logNum("PARZIALE", $prezzo);
        };
        $sub = function (string $name, float $val) use (&$prezzo, $logNum) {
            $prezzo -= $val;
            $logNum("SUB {$name}", $val);
            $logNum("PARZIALE", $prezzo);
        };

        $add('ListinoPorta', $this->appEccesso($maggListino * $listinoPorta));
        $add('CstTelP',      $this->appEccesso($maggListino * $cstTelP));
        $add('MaggColAnta',  $this->appEccesso($maggListino * $maggColAnta));
        $add('MaggKitScFM',  $this->appEccesso($maggListino * $maggKitScFM));
        $add('MaggFascMant', $this->appEccesso($maggListino * $maggFascMant));
        $add('MaggMan',      $this->appEccesso($maggListino * $maggMan));
        $add('MaggVetro',    $this->appEccesso($maggListino * $maggVetro));
        $add('MaggCern',     $this->appEccesso($maggListino * $maggCern));
        $add('MaggSerr',     $this->appEccesso($maggListino * $maggSerr));
        $add('TotMaggTelaio', $this->appEccesso($maggListino * $totMaggTelaio));
        $add('MaggImbSI',    $this->appEccesso($maggListino * $maggImbSI));
        $add('TotMaggAnta',  $this->appEccesso($maggListino * $totMaggAnta));
        $sub('MagDetTelaio', $this->appEccesso($maggListino * $magDetTelaio));

        $finale = (float) round($prezzo, 0);
        $logNum('FINALE round(#)', $finale);

        return $finale;
    }


    private function colonnaListVetroByModello(string $nomeModello): string
    {
        $nomeModello = trim($nomeModello);
        if ($nomeModello === '') return '0';

        // ass_mod_vetri.des_modello contiene lista token tipo "SIG;SKG;...;R3V;..."
        $row = DB::table('ass_mod_vetri')
            ->whereRaw("CONCAT(';', des_modello, ';') LIKE ?", ["%;{$nomeModello};%"])
            ->first();

        return trim((string)($row->colonna_list_vetro ?? '0'));
    }

    private function calcolaVetro(string $filtro, string $modelloNome, array $p): float
    {
        $filtro = trim($filtro);
        if ($filtro === '' || $filtro === '0') return 0.0;

        $vetro = DB::table('tab_vetri')->where('id_vetro', $p['IdVetro'])->first();
        if (!$vetro) return 0.0;

        $col = $this->safeCol($filtro); // "A","AA",...
        $val = $vetro->{$col} ?? null;

        // in VBA: se "U" => 0
        if (is_string($val) && strtoupper(trim($val)) === 'U') return 0.0;

        // GS* usa area
        if (in_array($modelloNome, ['GS1', 'GS2', 'GS3', 'GS4'], true)) {
            $prezzoMq = (float)$val;
            return ($p['DimL'] / 1000) * ($p['DimA'] / 1000) * $prezzoMq;
        }

        return (float)$val * (int)$p['NANTE'];
    }

    private function safeCol(string $col): string
    {
        // protezione base: solo A-Z 0-9 e _
        $c = strtoupper(preg_replace('/[^A-Z0-9_]/', '', $col));
        return $c === '' ? '0' : $c;
    }

    private function appEccesso(float $x): float
    {
        // Se la tua ApprossEccesso fa CEIL:
        return ceil($x);
    }
}
