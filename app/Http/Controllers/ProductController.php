<?php

namespace App\Http\Controllers;

use DB;
use File;
use App\Models\Product;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $products = Product::select('products.*');
        if ($request->title) {
            $products = $products->where('title', 'like', '%' .$request->title. '%');
        }
        // if ($request->variant || ($request->price_from && $request->price_to)) {
        //     $products = $products->leftjoin('product_variants as v','v.product_id','products.id');
        //     $products = $products->leftjoin('product_variant_prices as vp1','vp1.product_variant_one','v.id');
        //     $products = $products->leftjoin('product_variant_prices as vp2','vp2.product_variant_two','v.id');
        //     $products = $products->leftjoin('product_variant_prices as vp3','vp3.product_variant_three','v.id');
        //     if ($request->variant) {
        //         $products = $products->where('v.variant',$request->variant);
        //     }
        //     if ($request->price_from && $request->price_to) {
        //         $products = $products->whereBetween('vp1.price', [$request->price_from, $request->price_to]);
        //         $products = $products->orwhereBetween('vp2.price', [$request->price_from, $request->price_to]);
        //         $products = $products->orwhereBetween('vp3.price', [$request->price_from, $request->price_to]);
        //     }
        //     $products = $products->groupBy('products.id');
        // }
        if ($request->variant || ($request->price_from && $request->price_to)) {
            $products = $products->join('product_variants as v','v.product_id','products.id');
            $products = $products->join('product_variant_prices as vp','vp.product_id','products.id');
            if ($request->variant) {
                $products = $products->where('v.variant',$request->variant);
            }
            if ($request->price_from && $request->price_to) {
                $products = $products->whereBetween('vp.price', [$request->price_from, $request->price_to]);
            }
            $products = $products->groupBy('products.id');
        }
        if ($request->date) {
            $products = $products->whereDate('products.created_at',$request->date);
        }
        $products = $products->paginate(2);
        $variants = Variant::select('id','title')->get();
        return view('products.index', compact('products','variants'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $product                = new Product();
            $product->title         = $request->title;
            $product->sku           = $request->sku;
            $product->description   = $request->description;
            $product->save();
            foreach ($request['product_image'] as $key => $value) {
                if (isset($value['upload']['dataURL']) && !empty($value['upload']['dataURL'])) {
                    $destinationPath = '/uploads';
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                    $image = $value['upload']['dataURL'];
                    $imageName = $value['upload']['upload']['uuid'];
                    $image_parts = explode(";base64,", $image);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $file = $destinationPath .'/'. $imageName . '.'.$image_type;
                    file_put_contents(public_path().$file, $image_base64);
                    $productIng             = new ProductImage();
                    $productIng->product_id = $product->id;
                    $productIng->file_path  = $imageName . '.'.$image_type;
                    $productIng->save();
                }
            }
            foreach ($request->product_variant as $key => $prdVariants) {
                foreach ($prdVariants['tags'] as $key => $tag) {
                    $productVariant             = new ProductVariant();
                    $productVariant->variant    = $tag;
                    $productVariant->variant_id = $prdVariants['option'];
                    $productVariant->product_id = $product->id;
                    $productVariant->save();
                }
            }
            foreach ($request->product_variant_prices as $key => $prdVarPrice) {

                $titles = explode('/',$prdVarPrice['title']);
                $prdVariantPrice        = new ProductVariantPrice();
                if (isset($titles[0]) && $titles[0] != '') {
                    $var1 = DB::table('product_variants')->select('id')->where('variant',$titles[0])->first();
                    $prdVariantPrice->product_variant_one = $var1->id;
                }
                if (isset($titles[1]) && $titles[1] != '') {
                    $var2 = DB::table('product_variants')->select('id')->where('variant',$titles[1])->first();
                    $prdVariantPrice->product_variant_two = $var2->id;
                }
                if (isset($titles[2]) && $titles[2] != '') {
                    $var3 = DB::table('product_variants')->select('id')->where('variant',$titles[2])->first();
                    $prdVariantPrice->product_variant_three = $var3->id;
                }
                $prdVariantPrice->price = $prdVarPrice['price'];
                $prdVariantPrice->stock = $prdVarPrice['stock'];
                $prdVariantPrice->product_id = $product->id;
                $prdVariantPrice->save();
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Session::flash('flashMessageError', 'Something went wrong !');
            return response()->json(['status' => 0]);
        }
        DB::commit();
        \Session::flash('flashMessageSuccess', 'Product added successfully !');
        return response()->json(['status' => 1]);
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request,$id)
    {
        $variants = Variant::all();
        $product  = Product::find($id);
        $selected_variants = ProductVariant::select('variant_id')->where('product_id',$id)->groupBy('variant_id')->get();
        foreach ($selected_variants as $key => $value) {
            $value->tags = DB::table('product_variants')->select('variant_id','variant')->where('product_id',$id)->where('variant_id',$value->variant_id)->get();
        }
        $selected_var_price = DB::table('product_variant_prices')->select('price','stock')->where('product_id',$id)->get();
        $variant_img = DB::table('product_images')->select('id','file_path')->where('product_id',$id)->get();
        return view('products.edit', compact('variants','product','selected_variants','selected_var_price','variant_img'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            $request                = $request['data'];
            $product                = Product::find($id);
            $product->title         = $request['title'];
            $product->sku           = $request['sku'];
            $product->description   = $request['description'];
            $product->update();
            foreach ($request['product_image'] as $key => $value) {
                if (isset($value['upload']['dataURL']) && !empty($value['upload']['dataURL'])) {
                    $destinationPath = '/uploads';
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }
                    $image = $value['upload']['dataURL'];
                    $imageName = $value['upload']['upload']['uuid'];
                    $image_parts = explode(";base64,", $image);
                    $image_type_aux = explode("image/", $image_parts[0]);
                    $image_type = $image_type_aux[1];
                    $image_base64 = base64_decode($image_parts[1]);
                    $file = $destinationPath .'/'. $imageName . '.'.$image_type;
                    file_put_contents(public_path().$file, $image_base64);
                    $productIng             = new ProductImage();
                    $productIng->product_id = $product->id;
                    $productIng->file_path  = $imageName . '.'.$image_type;
                    $productIng->save();
                }
            }
            ProductVariant::where('product_id',$id)->delete();
            foreach ($request['product_variant'] as $key => $prdVariants) {
                foreach ($prdVariants['tags'] as $key => $tag) {
                    $productVariant             = new ProductVariant();
                    $productVariant->variant    = $tag;
                    $productVariant->variant_id = $prdVariants['option'];
                    $productVariant->product_id = $product->id;
                    $productVariant->save();
                }
            }
            ProductVariantPrice::where('product_id',$id)->delete();
            foreach ($request['product_variant_prices'] as $key => $prdVarPrice) {
                $titles = explode('/',$prdVarPrice['title']);
                $prdVariantPrice        = new ProductVariantPrice();
                if (isset($titles[0]) && $titles[0] != '') {
                    $var1 = DB::table('product_variants')->select('id')->where('variant',$titles[0])->first();
                    $prdVariantPrice->product_variant_one = $var1->id;
                }
                if (isset($titles[1]) && $titles[1] != '') {
                    $var2 = DB::table('product_variants')->select('id')->where('variant',$titles[1])->first();
                    $prdVariantPrice->product_variant_two = $var2->id;
                }
                if (isset($titles[2]) && $titles[2] != '') {
                    $var3 = DB::table('product_variants')->select('id')->where('variant',$titles[2])->first();
                    $prdVariantPrice->product_variant_three = $var3->id;
                }
                $prdVariantPrice->price = $prdVarPrice['price'];
                $prdVariantPrice->stock = $prdVarPrice['stock'];
                $prdVariantPrice->product_id = $product->id;
                $prdVariantPrice->save();
            }
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            \Session::flash('flashMessageError', 'Something went wrong !');
            return response()->json(['status' => 0]);
        }
        DB::commit();
        \Session::flash('flashMessageSuccess', 'Product updated successfully !');
        return response()->json(['status' => 1]);
    }

    public function deleteImage(Request $request)
    {
        DB::beginTransaction();
        try {
            if (File::exists(public_path('/uploads/'.$request->name))) {
                File::delete(public_path('/uploads/'.$request->name));
            }
            DB::table('product_images')->where('file_path',$request->name)->delete();
        } catch (\Exception $e) {
            DB::rollback();
            // dd($e);
            return response()->json(['status' => 0,'msg' => 'Something went wrong !']);
        }
        DB::commit();
        return response()->json(['status' => 1, 'msg' => 'Image Deleted successfully !']);
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
