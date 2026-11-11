<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    // Satuan jual yang diizinkan untuk hasil tani
    public const UNITS = ['kg', 'ton', 'kuintal', 'ikat', 'buah', 'butir', 'liter', 'karung', 'pak'];

    protected $fillable = [
        'seller_id', 'category_id', 'name', 'description',
        'price', 'stock', 'unit', 'min_order', 'harvest_date', 'is_organic',
        'origin_region', 'image_url', 'is_active',
    ];

    protected $casts = [
        'price' => 'integer',
        'stock' => 'integer',
        'min_order' => 'integer',
        'harvest_date' => 'date',
        'is_organic' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
