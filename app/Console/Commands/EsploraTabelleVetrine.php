<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class EsploraTabelleVetrine extends Command
{
    protected $signature = 'esplora:vetrine';
    protected $description = 'Esplora le tabelle vetrine nel database di produzione';

    public function handle()
    {
        try {
            $connection = DB::connection('mssql_prod');
            $this->info('✅ Connesso al database MSSQL di produzione');

            // Cerca tabelle che contengono 'vetrin'
            $this->info('🔍 Cercando tabelle che contengono "vetrin"...');
            $tables = $connection->select("
                SELECT TABLE_NAME 
                FROM INFORMATION_SCHEMA.TABLES 
                WHERE TABLE_NAME LIKE '%vetrin%'
            ");

            if (empty($tables)) {
                $this->warn('⚠️  Nessuna tabella trovata con "vetrin"');
                
                // Cerca tabelle che potrebbero essere correlate
                $this->info('🔍 Cercando tabelle che potrebbero essere correlate...');
                $possibleTables = $connection->select("
                    SELECT TABLE_NAME 
                    FROM INFORMATION_SCHEMA.TABLES 
                    WHERE TABLE_NAME LIKE '%shop%' 
                       OR TABLE_NAME LIKE '%display%'
                       OR TABLE_NAME LIKE '%showcase%'
                       OR TABLE_NAME LIKE '%window%'
                       OR TABLE_NAME LIKE '%esposiz%'
                ");

                if (empty($possibleTables)) {
                    $this->warn('⚠️  Nessuna tabella correlata trovata');
                    
                    // Mostra tutte le tabelle per vedere cosa c'è
                    $this->info('📋 Mostrando tutte le tabelle disponibili...');
                    $allTables = $connection->select("
                        SELECT TABLE_NAME 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_TYPE = 'BASE TABLE'
                        ORDER BY TABLE_NAME
                    ");
                    
                    foreach ($allTables as $table) {
                        $this->line("   - {$table->TABLE_NAME}");
                    }
                } else {
                    $this->info('📋 Tabelle correlate trovate:');
                    foreach ($possibleTables as $table) {
                        $this->line("   - {$table->TABLE_NAME}");
                    }
                }
            } else {
                $this->info('📋 Tabelle vetrine trovate:');
                foreach ($tables as $table) {
                    $this->line("   - {$table->TABLE_NAME}");
                    
                    // Mostra struttura della tabella
                    $columns = $connection->select("
                        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_NAME = '{$table->TABLE_NAME}'
                        ORDER BY ORDINAL_POSITION
                    ");
                    
                    $this->info("     Colonne:");
                    foreach ($columns as $column) {
                        $nullable = $column->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
                        $this->line("       - {$column->COLUMN_NAME} ({$column->DATA_TYPE}) {$nullable}");
                    }
                    
                    // Conta i record
                    $count = $connection->selectOne("SELECT COUNT(*) as count FROM [{$table->TABLE_NAME}]");
                    $this->info("     Record: {$count->count}");
                    $this->line('');
                }
            }

        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}