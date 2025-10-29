<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ContoDeposito;
use Illuminate\Support\Facades\DB;

class EliminaDepositiProva extends Command
{
    protected $signature = 'depositi:elimina-prova {--limit=4 : Numero di depositi da eliminare}';
    protected $description = 'Elimina i depositi di prova/test';

    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info('🔍 Cerca depositi di prova...');
        
        // Lista tutti i depositi
        $depositi = ContoDeposito::with(['ddtInvio', 'ddtReso'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
        
        if ($depositi->isEmpty()) {
            $this->info('✅ Nessun deposito trovato');
            return 0;
        }
        
        $this->table(
            ['ID', 'Codice', 'Stato', 'DDT Invio', 'DDT Reso', 'Articoli'],
            $depositi->map(function ($d) {
                return [
                    $d->id,
                    $d->codice,
                    $d->stato,
                    $d->ddtInvio->numero ?? '-',
                    $d->ddtReso->numero ?? '-',
                    $d->articoli_inviati,
                ];
            })->toArray()
        );
        
        if (!$this->confirm("Vuoi eliminare questi {$depositi->count()} depositi?")) {
            $this->info('❌ Operazione annullata');
            return 0;
        }
        
        try {
            DB::beginTransaction();
            
            foreach ($depositi as $deposito) {
                // Elimina il deposito (cascade eliminerà movimenti e dettagli)
                $deposito->delete();
                $this->line("✅ Eliminato deposito {$deposito->codice} (ID: {$deposito->id})");
            }
            
            DB::commit();
            
            $this->newLine();
            $this->info("✅ Eliminati {$depositi->count()} depositi di prova");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('❌ Errore durante eliminazione: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
