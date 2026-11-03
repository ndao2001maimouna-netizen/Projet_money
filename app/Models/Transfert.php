<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transfert extends Model
{
    protected $table = 'transferts';

    protected $fillable = [
        'montant',
        'expediteur_id',
        'destinataire_id',
        'reference_transfert',
    ];

    /**
     * @return BelongsTo<Portefeuille, $this>
     */
    public function expediteur(): BelongsTo
    {
        return $this->belongsTo(Portefeuille::class, 'expediteur_id');
    }

    /**
     * @return BelongsTo<Portefeuille, $this>
     */
    public function destinataire(): BelongsTo
    {
        return $this->belongsTo(Portefeuille::class, 'destinataire_id');
    }
}
