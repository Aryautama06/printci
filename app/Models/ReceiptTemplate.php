<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'paper_size', 'font_size', 'header', 'footer', 'logo_path', 'elements', 'is_default'])]
class ReceiptTemplate extends Model
{
    protected function casts(): array
    {
        return ['elements' => 'array', 'is_default' => 'boolean'];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
