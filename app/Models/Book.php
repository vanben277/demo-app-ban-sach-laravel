<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Book extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'category_id',
        'title',
        'author',
        'description',
        'price',
        'stock',
        'image',
        'is_active',
        'rating_avg',
        'review_count'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(BookImage::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
