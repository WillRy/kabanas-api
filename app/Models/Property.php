<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    /** @use HasFactory<\Database\Factories\PropertyFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        "name",
        "maxCapacity",
        "regularPrice",
        "discount",
        "description",
        "image"
    ];

    protected $casts = [
        'regularPrice' => 'float',
        'discount' => 'float',
    ];


    public function newProperty(array $attributes = [])
    {
        $property = self::create($attributes);

        if (!empty($attributes['image'])) {
            $attributes['image'] = $attributes['image']->store('properties', 'public');
        }

        $property->fill($attributes);

        $property->save();

        return $property;
    }

    public function list(string $sortBy = 'id', string $sortOrder = 'asc', ?string $discountFilter = null)
    {
        $sortBy = in_array($sortBy, ['id', 'name', 'regularPrice', 'discount']) ? $sortBy : 'id';

        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'asc';



        return Property::query()
            ->when($discountFilter, function ($query) use ($discountFilter) {
                if ($discountFilter === 'with-discount') {
                    $query->whereNotNull('discount');
                } elseif ($discountFilter === 'without-discount') {
                    $query->whereNull('discount');
                }
            })
            ->orderBy($sortBy, $sortOrder)
            ->paginate(10);
    }

    public function updateProperty(array $attributes = [])
    {
        if (!empty($attributes['image'])) {
            $attributes['image'] = $attributes['image']->store('properties', 'public');
        }

        $this->fill($attributes);

        $this->save();

        return $this;
    }


}
