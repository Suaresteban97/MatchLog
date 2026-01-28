<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PcComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'brand'
    ];

    /**
     * Tipos de componentes disponibles
     */
    const TYPE_CPU = 'cpu';
    const TYPE_GPU = 'gpu';
    const TYPE_RAM = 'ram';
    const TYPE_STORAGE = 'storage';

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope para CPUs
     */
    public function scopeCpus($query)
    {
        return $query->where('type', self::TYPE_CPU);
    }

    /**
     * Scope para GPUs
     */
    public function scopeGpus($query)
    {
        return $query->where('type', self::TYPE_GPU);
    }

    /**
     * Scope para RAM
     */
    public function scopeRams($query)
    {
        return $query->where('type', self::TYPE_RAM);
    }

    /**
     * Scope para Storage
     */
    public function scopeStorages($query)
    {
        return $query->where('type', self::TYPE_STORAGE);
    }
}
