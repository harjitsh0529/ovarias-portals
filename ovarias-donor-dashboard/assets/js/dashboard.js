jQuery(document).ready(function($) {
    console.log('Ovarias Donor Dashboard: JS controller initialized.');

    function toggleFrozenFields() {
        var val = $('#egg_type').val();
        if (val === 'Frozen' || val === 'Both') {
            $('#frozen-donor-fields').css('display', 'flex');
        } else {
            $('#frozen-donor-fields').css('display', 'none');
        }
    }
    
    $('#egg_type').on('change', toggleFrozenFields);
    toggleFrozenFields();

    // Profile Edit Mode Toggle
    var isEditing = false;
    var formFields = $('.ovarias-form').find('input, select, textarea').not('#profile_image, #donor_gallery');
    
    // Set initial Read Only state on load
    formFields.prop('disabled', true);
    $('#profile_image, #donor_gallery').prop('disabled', true); // Keep file inputs disabled initially
    $('.ovarias-form-submit').hide();
    
    $('#btn-toggle-edit').on('click', function(e) {
        e.preventDefault();
        isEditing = !isEditing;
        
        if (isEditing) {
            formFields.prop('disabled', false);
            $('#profile_image, #donor_gallery').prop('disabled', false);
            $('.ovarias-form-submit').fadeIn();
            
            // Show upload controls
            $('.ovarias-btn-change-photo').show();
            $('.btn-delete-profile-photo').css('display', 'inline-flex');
            $('#dropzone-area-profile, #dropzone-area-gallery').show();
            $('#no-photo-notice-profile, #no-photo-notice-gallery').hide();
            
            $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> <span>Cancel Edit</span>').css('background', '#C96464');
        } else {
            formFields.prop('disabled', true);
            $('#profile_image, #donor_gallery').prop('disabled', true);
            $('.ovarias-form-submit').fadeOut();
            
            // Hide upload controls
            $('.ovarias-btn-change-photo').hide();
            $('.btn-delete-profile-photo').hide();
            $('#dropzone-area-profile, #dropzone-area-gallery').hide();
            $('#no-photo-notice-profile, #no-photo-notice-gallery').show();
            $('#gallery-temp-previews').remove(); // Clear previews on cancel
            galleryDataTransfer = new DataTransfer(); // Reset accumulated files
            $('#donor_gallery, #profile_image').val(''); // Clear input values
            
            $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="14 2 18 6 7 17 3 17 3 13 14 2"></polygon></svg> <span>Edit Profile</span>').css('background', '');
        }
    });

    // Make sure fields are enabled on submit so they get posted!
    $('.ovarias-form').on('submit', function() {
        formFields.prop('disabled', false);
        $('#profile_image, #donor_gallery').prop('disabled', false);
    });

    // Trigger file browser on "Change Photo" click
    $(document).on('click', '#btn-trigger-upload', function(e) {
        e.preventDefault();
        $('#profile_image').trigger('click');
    });
    // Live preview local image when selected
    $(document).on('change', '#profile_image', function(e) {
        var file = this.files[0];
        if (file) {
            var validFormats = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validFormats.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, or GIF).');
                this.value = '';
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) {
                alert('Image size exceeds 5MB. Please choose a smaller image.');
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(evt) {
                var previewImg = $('#profile-image-preview-element');
                if (previewImg.length) {
                    previewImg.attr('src', evt.target.result).show();
                } else {
                    var newPreview = $('<div class="ovarias-uploaded-image-preview" style="text-align: center; margin-bottom: 15px;"><img src="' + evt.target.result + '" id="profile-image-preview-element" style="max-width: 100%; max-height: 250px; border-radius: var(--ovarias-radius-sm); border: 1px solid var(--ovarias-border); object-fit: cover; display: block; margin: 0 auto 15px auto;"></div>');
                    $('#profile-photo-upload-container').prepend(newPreview);
                }
                
                $('#dropzone-area-profile').hide();

                // Live sync to top left header avatar preview
                $('#avatar-preview').attr('src', evt.target.result);
                var placeholder = $('#avatar-preview-placeholder');
                if (placeholder.length) {
                    placeholder.replaceWith(`<img src="${evt.target.result}" alt="Profile Photo" class="ovarias-user-avatar" id="avatar-preview">`);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Accumulate multiple selected gallery images via DataTransfer API
    var galleryDataTransfer = new DataTransfer();

    $(document).on('change', '#donor_gallery', function(e) {
        var files = this.files;
        if (files && files.length > 0) {
            // Append newly selected files
            for (var i = 0; i < files.length; i++) {
                galleryDataTransfer.items.add(files[i]);
            }
            
            // Sync files back to input element
            this.files = galleryDataTransfer.files;
            
            // Render previews
            renderGalleryPreviews(this.files);
        }
    });

    function renderGalleryPreviews(files) {
        $('#gallery-temp-previews').remove();
        if (files.length === 0) return;
        
        var previewWrapper = $('<div id="gallery-temp-previews" style="margin-top: 15px; display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 10px; padding: 10px; background: rgba(0,0,0,0.02); border: 1px dashed var(--ovarias-border); border-radius: 4px; width: 100%; box-sizing: border-box;"></div>');
        
        Array.from(files).forEach(function(file, index) {
            if (file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(evt) {
                    var imgCard = $('<div style="text-align: center; position: relative;" data-index="' + index + '"></div>');
                    var img = $('<img src="' + evt.target.result + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 1px solid var(--ovarias-border);">');
                    var removeBtn = $('<span style="position: absolute; top: -5px; right: -5px; background: #c62828; color: #fff; border-radius: 50%; width: 16px; height: 16px; font-size: 10px; line-height: 16px; text-align: center; cursor: pointer; font-weight: bold; user-select: none;">&times;</span>');
                    
                    removeBtn.on('click', function(event) {
                        event.stopPropagation();
                        var newDt = new DataTransfer();
                        for (var j = 0; j < galleryDataTransfer.files.length; j++) {
                            if (j !== index) {
                                newDt.items.add(galleryDataTransfer.files[j]);
                            }
                        }
                        galleryDataTransfer = newDt;
                        var inputEl = document.getElementById('donor_gallery');
                        if (inputEl) {
                            inputEl.files = galleryDataTransfer.files;
                        }
                        renderGalleryPreviews(galleryDataTransfer.files);
                    });
                    
                    imgCard.append(img).append(removeBtn);
                    previewWrapper.append(imgCard);
                };
                reader.readAsDataURL(file);
            }
        });
        
        $('#dropzone-area-gallery').after(previewWrapper);
    }
});
