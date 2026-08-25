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
    var formFields = $('.ovarias-form').find('input, select, textarea').not('#profile_image');
    
    // Set initial Read Only state on load
    formFields.prop('disabled', true);
    $('.ovarias-form-submit').hide();
    
    $('#btn-toggle-edit').on('click', function(e) {
        e.preventDefault();
        isEditing = !isEditing;
        
        if (isEditing) {
            formFields.prop('disabled', false);
            $('.ovarias-form-submit').fadeIn();
            
            // Show upload controls
            $('.ovarias-btn-change-photo').show();
            $('#dropzone-area').show();
            $('#no-photo-notice').hide();
            
            $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> <span>Cancel Edit</span>').css('background', '#C96464');
        } else {
            formFields.prop('disabled', true);
            $('.ovarias-form-submit').fadeOut();
            
            // Hide upload controls
            $('.ovarias-btn-change-photo').hide();
            $('#dropzone-area').hide();
            $('#no-photo-notice').show();
            
            $(this).html('<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="14 2 18 6 7 17 3 17 3 13 14 2"></polygon></svg> <span>Edit Profile</span>').css('background', '');
        }
    });

    // Make sure fields are enabled on submit so they get posted!
    $('.ovarias-form').on('submit', function() {
        formFields.prop('disabled', false);
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
            // Perform quick client-side file size and format validation
            var validFormats = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validFormats.includes(file.type)) {
                alert('Please select a valid image file (JPEG, PNG, or GIF).');
                this.value = ''; // clear input
                return;
            }
            
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                alert('Image size exceeds 5MB. Please choose a smaller image.');
                this.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(evt) {
                // If a preview image element exists, update it. Otherwise create it.
                var previewImg = $('#profile-image-preview-element');
                if (previewImg.length) {
                    previewImg.attr('src', evt.target.result);
                } else {
                    // Replace upload dropzone area with image preview card
                    var previewHtml = `
                        <div class="ovarias-uploaded-image-preview">
                            <img src="${evt.target.result}" id="profile-image-preview-element" style="max-width: 100%; max-height: 200px; border-radius: var(--ovarias-radius-sm); border: 1px solid var(--ovarias-border); object-fit: cover; display: block; margin-bottom: 15px;">
                            <button type="button" class="ovarias-submit-btn ovarias-btn-change-photo" id="btn-trigger-upload" style="padding: 10px 18px; font-size: 13px; border-radius: 6px; display: inline-flex;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                                Change Photo
                            </button>
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                        </div>
                    `;
                    $('.ovarias-photo-upload-container').html(previewHtml);
                }
                
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
});
