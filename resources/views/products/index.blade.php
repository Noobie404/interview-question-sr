@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>
    <div class="card">
        <form action="" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" value="{{ request()->title ?? '' }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <option value="">Select Option</option>
                        @foreach ($variants as $variant)
                        <optgroup label="{{ $variant->title }}">
                            @foreach ($variant->productVariants as $productVariant)
                            <option value="{{ $productVariant->variant }}" {{ request()->variant == $productVariant->variant ? 'selected' : '' }}>{{ $productVariant->variant }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="number" step=".02" name="price_from" aria-label="First name" placeholder="From" class="form-control" value="{{ request()->price_from }}">
                        <input type="number" step=".02" name="price_to" aria-label="Last name" placeholder="To" class="form-control" value="{{ request()->price_to }}">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th width="600px">Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $key => $product)
                            <tr>
                                <td>{{ $key + $products->firstItem() }}</td>
                                <td>{{ $product->title }} <br> Created at : {{ $product->created_at->diffForHumans() }}</td>
                                <td>{{ $product->description }}</td>
                                <td>
                                    <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">
                                        @foreach ($product->productVariantPrices as $productVariantPrice)
                                        <dt class="col-sm-3 pb-0">
                                            {{ $productVariantPrice->productVariantOne->variant ?? '' }}/ {{ $productVariantPrice->productVariantTwo->variant ?? '' }}/ {{ $productVariantPrice->productVariantThree->variant ?? '' }}
                                        </dt>
                                        <dd class="col-sm-9">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-5 pb-0">Price : {{ number_format($productVariantPrice->price,2) }}</dt>
                                                <dd class="col-sm-7 pb-0">InStock : {{ number_format($productVariantPrice->stock,2) }}</dd>
                                            </dl>
                                        </dd>
                                        @endforeach
                                    </dl>
                                    {{-- <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link" style="box-shadow: none;">Show more</button> --}}
                                    <button onclick="show_more(this)" class="btn btn-sm btn-link" style="box-shadow: none;">Show more</button>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('product.edit', $product->id) }}" class="btn btn-success">Edit</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} out of {{ $products->total() }}</p>
                </div>
                <div class="col-md-2">
                    {{ $products->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
@push('custom_js')
<script>
    
    function show_more(the) {
        $(the).parent().find('#variant').toggleClass('h-auto');
    }
</script>
@endpush('custom_js')
