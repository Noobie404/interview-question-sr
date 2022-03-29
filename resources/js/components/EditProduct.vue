<template>
    <section>
        <div class="row">
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group">
                            <label for="">Product Name</label>
                            <input type="text" v-model="product_name" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Product SKU</label>
                            <input type="text" v-model="product_sku" placeholder="Product Name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="">Description</label>
                            <textarea v-model="description" id="" cols="30" rows="4" class="form-control"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Media</h6>
                    </div>
                    <div class="card-body border">
                        <vue-dropzone ref="dropzone" id="dropzone" :options="dropzoneOptions" @vdropzone-complete="afterComplete" @vdropzone-mounted="loadPictures"       v-on:vdropzone-removed-file="removeThisFile"></vue-dropzone>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Variants</h6>
                    </div>
                    <div class="card-body">
                        <div class="row" v-for="(item,index) in product_variant">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="">Option</label>
                                    <select v-model="item.option" class="form-control">
                                        <option v-for="variant in variants"
                                                :value="variant.id">
                                            {{ variant.title }}
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label v-if="product_variant.length != 1" @click="product_variant.splice(index,1); checkVariant"
                                           class="float-right text-primary"
                                           style="cursor: pointer;">Remove</label>
                                    <label v-else for="">.</label>
                                    <input-tag v-model="item.tags" @input="checkVariant" class="form-control"></input-tag>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer" v-if="product_variant.length < variants.length && product_variant.length < 3">
                        <button @click="newVariant" class="btn btn-primary">Add another option</button>
                    </div>

                    <div class="card-header text-uppercase">Preview</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                <tr>
                                    <td>Variant</td>
                                    <td>Price</td>
                                    <td>Stock</td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="variant_price in product_variant_prices">
                                    <td>{{ variant_price.title }}</td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.price">
                                    </td>
                                    <td>
                                        <input type="text" class="form-control" v-model="variant_price.stock">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button @click="updateProduct" type="submit" class="btn btn-lg btn-primary">Update</button>
        <button type="button" class="btn btn-secondary btn-lg">Cancel</button>
    </section>
</template>

<script>
import vue2Dropzone from 'vue2-dropzone'
import 'vue2-dropzone/dist/vue2Dropzone.min.css'
import InputTag from 'vue-input-tag'

export default {
    components: {
        vueDropzone: vue2Dropzone,
        InputTag
    },
    props: {
        variants: {
            type: Array,
            required: true
        },
        product: {
            type: Object,
            required: true
        },
        query_selected_variants: {
            type: Array,
            required: true
        },
        selected_var_price: {
            type: Array,
            required: true
        },
        variant_img: {
            type: Array,
            required: true
        }
    },
    data() {
        return {
            product_name: this.product.title,
            product_sku: this.product.sku,
            description: this.product.description,
            images: [],
            product_variant: [],
            product_variant_prices: [],
            dropzoneOptions: {
                url: 'https://httpbin.org/post',
                thumbnailWidth: 150,
                maxFilesize: 0.5,
                headers: {"My-Awesome-Header": "header value"},
                addRemoveLinks: true
            },
        }
    },
    methods: {
        // it will push a new object into product variant
        newVariant() {
            let all_variants = this.variants.map(el => el.id)
            let selected_variants = this.product_variant.map(el => el.option);
            let available_variants = all_variants.filter(entry1 => !selected_variants.some(entry2 => entry1 == entry2))
            // console.log(available_variants)

            this.product_variant.push({
                option: available_variants[0],
                tags: []
            })
        },

        // check the variant and render all the combination
        checkVariant() {
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })
            this.getCombn(tags).forEach(item => {
                this.product_variant_prices.push({
                    title: item,
                    price: 0,
                    stock: 0
                })
            })
        },

        // combination algorithm
        getCombn(arr, pre) {
            pre = pre || '';
            if (!arr.length) {
                return pre;
            }
            let self = this;
            let ans = arr[0].reduce(function (ans, value) {
                return ans.concat(self.getCombn(arr.slice(1), pre + value + '/'));
            }, []);
            return ans;
        },

        // store product into database
        updateProduct() {
            let product = {
                title: this.product_name,
                sku: this.product_sku,
                description: this.description,
                product_image: this.images,
                product_variant: this.product_variant,
                product_variant_prices: this.product_variant_prices
            }
            axios.post('/product/'+this.product.id, {
                data: product,
                _method: 'PUT'
            }).then(response => {
                location.reload();
                console.log(response.data);
            }).catch(error => {
                location.reload();
                console.log(error);
            })
        },
        // get product data
        getEditData() {
            // it will push a new object into product variant
            this.query_selected_variants.forEach(item => {
                var push_tags = [];
                item.tags.forEach(tag => {
                    push_tags.push(tag.variant);
                })
                this.product_variant.push({
                    option: item.variant_id,
                    tags: push_tags
                })
            })
            let tags = [];
            this.product_variant_prices = [];
            this.product_variant.filter((item) => {
                tags.push(item.tags);
            })
            this.getCombn(tags).forEach((item,index) => {
                this.product_variant_prices.push({
                    title: item,
                    price: this.selected_var_price[index].price,
                    stock: this.selected_var_price[index].stock,
                })
            })
        },
        loadPictures(){
            this.variant_img.forEach(image => {
                var file = { size: 123,name: image.file_path };
                var url = window.location.origin+'/uploads/'+image.file_path;
                // this.$refs.dropzone.manuallyAddFile(file, url);
                this.$refs.dropzone.manuallyAddFile({
                    name: image.file_path , size: 2222}, window.location.origin+'/uploads/'+image.file_path,
                    null,
                    null,
                    {
                        dontSubstractMaxFiles: false,
                        addToFiles: true
                    }
                );
            });
        },
        removeThisFile (file) {
            let FileName = file.name
            axios.post('/upload/delete', {
                name: FileName
            })
            .then(function (response) {
                console.log(response);
                alert(response.data.msg);
            })
            .catch(function (error) {
                console.log(error);
            });
            file.previewTemplate = null;
            file.upload = null;
            file.dataURL = null;
        },
        async afterComplete(upload) {
            this.images.push({ upload });
        }
    },
    mounted() {
        this.getEditData();
    }
}
</script>
