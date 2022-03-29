<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    public function productVariantOne()
    {
        return $this->hasOne(ProductVariant::class,'id','product_variant_one');
    }
    public function productVariantTwo()
    {
        return $this->hasOne(ProductVariant::class,'id','product_variant_two');
    }
    public function productVariantThree()
    {
        return $this->hasOne(ProductVariant::class,'id','product_variant_three');
    }
}
