<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'stampante_default_id',
        'categorie_permesse',
        'sedi_permesse',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'categorie_permesse' => 'array',
            'sedi_permesse' => 'array',
        ];
    }

    /**
     * Relazione con la stampante predefinita
     */
    public function stampanteDefault()
    {
        return $this->belongsTo(Stampante::class, 'stampante_default_id');
    }

    /**
     * Verifica se l'utente è admin (senza restrizioni)
     */
    public function isAdmin(): bool
    {
        // Se non ha permessi configurati, è admin
        return empty($this->categorie_permesse) && empty($this->sedi_permesse);
    }

    /**
     * Verifica se l'utente può accedere a una categoria
     */
    public function canAccessCategory(int $categoriaId): bool
    {
        // Admin può accedere a tutto
        if ($this->isAdmin()) {
            return true;
        }
        
        return in_array($categoriaId, $this->categorie_permesse ?? []);
    }

    /**
     * Verifica se l'utente può accedere a una sede
     */
    public function canAccessSede(int $sedeId): bool
    {
        // Admin può accedere a tutto
        if ($this->isAdmin()) {
            return true;
        }
        
        return in_array($sedeId, $this->sedi_permesse ?? []);
    }

    /**
     * Verifica se l'utente può accedere a un articolo
     */
    public function canAccessArticolo(Articolo $articolo): bool
    {
        // Admin può accedere a tutto
        if ($this->isAdmin()) {
            return true;
        }
        
        return $this->canAccessCategory($articolo->categoria_merceologica_id) &&
               $this->canAccessSede($articolo->sede_id);
    }
}
