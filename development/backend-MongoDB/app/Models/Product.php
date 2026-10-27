<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'products';

    protected $fillable = [
        'id',
        'name',
        'description',
        'price',
        'image_key',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'string',
            'is_available' => 'boolean',
        ];
    }
}