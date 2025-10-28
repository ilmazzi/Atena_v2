<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Stampante;

class StampantiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stampanti = [
            [
                'nome' => 'Cavour (Lecco)',
                'ip_address' => '192.168.11.175',
                'port' => 9100,
                'modello' => 'ZT230',
                'categorie_permesse' => [1, 2, 3, 4, 5, 6, 7, 8, 9], // Categorie 1-8 + PF 9
                'sedi_permesse' => [1], // CAVOUR
                'attiva' => true,
            ],
            [
                'nome' => 'Jolly (Lecco)',
                'ip_address' => '192.168.12.11',
                'port' => 9100,
                'modello' => 'ZT230',
                'categorie_permesse' => [10, 11, 12], // Solo categorie Jolly
                'sedi_permesse' => [4], // JOLLY
                'attiva' => true,
            ],
            [
                'nome' => 'Roma',
                'ip_address' => '192.168.18.100',
                'port' => 9100,
                'modello' => 'ZT620',
                'categorie_permesse' => [13, 14, 15, 16, 17, 18, 19, 20, 21, 22], // Categorie 13-21 + PF 22
                'sedi_permesse' => [5], // ROMA
                'attiva' => true,
            ],
            [
                'nome' => 'Bellagio (Monastero)',
                'ip_address' => '192.168.16.117',
                'port' => 9100,
                'modello' => 'ZT420',
                'categorie_permesse' => [1, 2, 3, 4, 5, 6, 7, 8, 9], // Categorie 1-8 + PF 9
                'sedi_permesse' => [3], // MONASTERO
                'attiva' => true,
            ],
        ];

        foreach ($stampanti as $stampante) {
            Stampante::create($stampante);
        }
    }
}