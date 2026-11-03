<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Depot extends Model
{
    protected $fillable = [
        'montant',
        'distributeur_id',
        'client_id',
        'reference_depot',
    ];

    /**
     * @return BelongsTo<Portefeuille, $this>
     */
    public function distributeur(): BelongsTo
    {
        return $this->belongsTo(Portefeuille::class, 'distributeur_id');
    }

    /**
     * @return BelongsTo<Portefeuille, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Portefeuille::class, 'client_id');
    }
}
