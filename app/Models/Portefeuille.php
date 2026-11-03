<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Portefeuille extends Model
{
    protected $fillable = [
        'utilisateur_id',
        'solde',
        'devise',
    ];

    /**
     * @return BelongsTo<Utilisateur, $this>
     */
    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(Utilisateur::class, 'utilisateur_id');
    }
}
