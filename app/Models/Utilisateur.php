<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Utilisateur extends Authenticatable
{
    use HasFactory;

    protected $table = 'utilisateurs';

    protected $fillable = [
        'nom',
        'prenom',
        'telephone',
        'code_secret',
        'role',
        'createur_id',
    ];

    protected $hidden = [
        'code_secret',
    ];

    public function getAuthPassword()
    {
        return $this->code_secret;
    }

    /**
     * @return HasOne<Portefeuille, $this>
     */
    public function portefeuille(): HasOne
    {
        return $this->hasOne(Portefeuille::class, 'utilisateur_id');
    }
}
