<div>
    <h5>Upload New Images</h5>
    <p class="text-muted">You can select multiple images at once.</p>
   <form id="product-images-upload-form" ...>
        <div class="input-group">
            {{-- Give the input an ID for easier selection with jQuery/JS --}}
            <input type="file" class="form-control" id="image_files_input" name="images[]" multiple>
            <button class="btn btn-success" type="submit" id="uploadImagesBtn">Upload</button>
        </div>
    </form>
    <hr>
    <h5>Existing Images</h5>
    <div id="existing-images-container" class="row mt-3">
        {{-- Images will be loaded here by JavaScript --}}
        <p class="text-center">No images found for this product.</p>
    </div>
</div>