<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'bluetooth_id', 'connection_type', 'status', 'paper_size', 'battery_level', 'last_seen_at', 'metadata'])]
class Printer extends Model
{
    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'metadata' => 'array'];
    }

    public function printLogs(): HasMany
    {
        return $this->hasMany(PrintLog::class);
    }
}
