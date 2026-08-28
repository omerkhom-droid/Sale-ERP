<div class="row">

    <div class="col-md-12 mb-3">
        <label class="form-label fw-bold">
            صور الصنف
        </label>

        <input type="file"
               name="images[]"
               id="productImages"
               class="form-control"
               multiple
               accept="image/*">

            @if(isset($product) && $product->exists)
                <div class="row mb-3">
                    @foreach($product->images as $image)
                        <div class="col-md-2 text-center image-box" id="image-box-{{ $image->id }}">

                            <img src="{{ asset('storage/'.$image->image) }}"
                                 class="img-thumbnail mb-2"
                                 style="height:120px;width:100%;object-fit:cover;">

                            <button type="button"
                                    class="btn btn-danger btn-sm w-100 deleteImage" id="deleteImage" 
                                    data-id="{{ $image->id }}">
                                حذف
                            </button>

                        </div>
                    @endforeach
                </div>
            @endif
            
        <small class="text-muted">
            يمكنك رفع أكثر من صورة للصنف.
        </small>
    </div>

    <div class="col-md-12">
        <div id="imagePreview" class="d-flex flex-wrap gap-2"></div>
    </div>

</div>