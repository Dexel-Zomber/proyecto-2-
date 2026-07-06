<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        return static::firstWhere('key', $key)?->value ?? $default;
    }

    public static function setValue(string $key, mixed $value): static
    {
        return static::updateOrCreate([
            'key' => $key,
        ], [
            'value' => $value,
        ]);
    }
}
