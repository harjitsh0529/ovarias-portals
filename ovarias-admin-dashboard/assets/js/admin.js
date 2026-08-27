jQuery(document).ready(function($) {
    
    // Tab switching
    $('.ovarias-admin-tab-btn').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).data('tab');
        
        $('.ovarias-admin-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        $('.ovarias-admin-tab-content').removeClass('active');
        $('#tab-' + tabId).addClass('active');
    });

    // Parent Search Filtering
    $('#search-parents').on('keyup', function() {
        var query = $(this).val().toLowerCase();
        $('#parents-table tbody tr.parent-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Donor Search Filtering
    $('#search-donors').on('keyup', function() {
        var query = $(this).val().toLowerCase();
        $('#donors-table tbody tr.donor-row').each(function() {
            var text = $(this).text().toLowerCase();
            if (text.indexOf(query) > -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // Toggle Donor Category UI (show/hide frozen stock inputs)
    $('.donor-egg-type-val').on('change', function() {
        var tr = $(this).closest('tr');
        var selectedType = $(this).val();
        if (selectedType === 'Frozen' || selectedType === 'Both') {
            tr.find('.frozen-stock-edit-wrapper').show();
            tr.find('.stock-n-a').hide();
        } else {
            tr.find('.frozen-stock-edit-wrapper').hide();
            tr.find('.stock-n-a').show();
        }
    });

    // MODAL HANDLERS (Create Client/Donor)
    $('.btn-open-modal').on('click', function(e) {
        e.preventDefault();
        var type = $(this).data('modal-type');
        var modal = $('#ovarias-create-user-modal');
        
        // Reset form
        $('#ovarias-create-user-form')[0].reset();
        $('#new-user-type').val(type);
        
        if (type === 'donor') {
            $('#modal-title').text('Add New Egg Donor Profile');
            $('#donor-specific-fields').show();
            $('#parent-specific-fields').hide();
        } else {
            $('#modal-title').text('Add New Intended Parent Client');
            $('#donor-specific-fields').hide();
            $('#parent-specific-fields').show();
        }
        
        modal.css('display', 'flex');
    });

    function closeModal() {
        $('#ovarias-create-user-modal').hide();
    }

    $('.btn-close-modal-x, .btn-close-modal-cancel').on('click', function(e) {
        e.preventDefault();
        closeModal();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#ovarias-create-user-modal')) {
            closeModal();
        }
    });

    // AJAX: Create User Form Submit
    $('#ovarias-create-user-form').on('submit', function(e) {
        e.preventDefault();
        
        var submitBtn = $(this).find('button[type="submit"]');
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Creating account...');

        var type = $('#new-user-type').val();
        var firstName = $('#new-first-name').val();
        var lastName = $('#new-last-name').val();
        var username = $('#new-username').val();
        var password = $('#new-password').val();

        var formData = new FormData();
        formData.append('action', 'ovarias_admin_create_user');
        formData.append('type', type);
        formData.append('first_name', firstName);
        formData.append('last_name', lastName);
        formData.append('username', username);
        formData.append('password', password);
        formData.append('nonce', ovariasAdminParams.nonce);

        if (type === 'donor') {
            formData.append('donor_id', $('#new-donor-id').val());
            formData.append('dob', $('#new-donor-dob').val());
            formData.append('nationality', $('#new-donor-nationality').val());
            formData.append('blood_group', $('#new-donor-blood').val());
            formData.append('height', $('#new-donor-height').val());
            formData.append('weight', $('#new-donor-weight').val());
            formData.append('eye_colour', $('#new-donor-eyes').val());
            formData.append('hair_colour', $('#new-donor-hair').val());
            formData.append('education_level', $('#new-donor-education').val());
            formData.append('field_of_study', $('#new-donor-study').val());
            formData.append('occupation', $('#new-donor-occupation').val());
            formData.append('languages_spoken', $('#new-donor-languages').val());
            formData.append('availability_status', $('#new-donor-avail').val());
            formData.append('egg_type', $('#new-donor-egg-type').val());
            formData.append('num_eggs', $('#new-donor-num-eggs').val());
            formData.append('storage_country', $('#new-donor-storage').val());
            formData.append('about_me', $('#new-donor-about').val());
            formData.append('hobbies', $('#new-donor-hobbies').val());
            formData.append('why_donate', $('#new-donor-why').val());
            
            // Append Profile Photo file
            var profileFile = $('#new-donor-profile-image')[0].files[0];
            if (profileFile) {
                formData.append('profile_image', profileFile);
            }
            
            // Append Gallery Photos files
            var galleryFiles = $('#new-donor-gallery')[0].files;
            if (galleryFiles && galleryFiles.length > 0) {
                for (var i = 0; i < galleryFiles.length; i++) {
                    formData.append('donor_gallery[]', galleryFiles[i]);
                }
            }
        } else {
            formData.append('country', $('#new-parent-country').val());
            formData.append('parent_preferences', $('#new-parent-preferences').val());
            formData.append('parent_notes', $('#new-parent-notes').val());
        }

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Success: Account created successfully!');
                    closeModal();
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                    submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert('Connection failure.');
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // AJAX: Delete Parent/Donor permanently
    $('.btn-delete-user').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var userId = btn.data('user-id');
        var tr = btn.closest('tr');
        var name = tr.find('strong').first().text();

        if (!confirm('Are you absolutely sure you want to permanently delete "' + name + '"?\nThis action cannot be undone and will erase all metadata from the database.')) {
            return;
        }

        btn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_delete_user',
                user_id: userId,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Account deleted successfully.');
                    tr.fadeOut(400, function() {
                        var isParent = tr.hasClass('parent-row');
                        if (isParent) {
                            var pCount = parseInt($('.count-parents').first().text()) || 0;
                            $('.count-parents').text(Math.max(0, pCount - 1));
                            
                            var pVal = parseInt($('.val-parents').first().text()) || 0;
                            $('.val-parents').text(Math.max(0, pVal - 1));
                        } else {
                            var dCount = parseInt($('.count-donors').first().text()) || 0;
                            $('.count-donors').text(Math.max(0, dCount - 1));
                            
                            var dVal = parseInt($('.val-donors').first().text()) || 0;
                            $('.val-donors').text(Math.max(0, dVal - 1));
                        }
                        tr.remove();
                    });
                } else {
                    alert('Error deleting user: ' + response.data.message);
                    btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Delete');
            }
        });
    });

    // AJAX: Toggle Parent Premium Plan Access
    $('.btn-toggle-premium').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var userId = btn.data('user-id');
        var currentStatus = btn.data('status');
        
        btn.prop('disabled', true).text('Processing...');
        
        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_toggle_premium',
                user_id: userId,
                current_status: currentStatus,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    var newStatus = response.data.new_status;
                    btn.data('status', newStatus);
                    
                    var statusBadge = btn.closest('tr').find('.status-badge');
                    if (newStatus === '1') {
                        btn.text('Revoke Access');
                        statusBadge.removeClass('inactive').addClass('active').text('Paid Access');
                    } else {
                        btn.text('Grant Access');
                        statusBadge.removeClass('active').addClass('inactive').text('Restricted');
                    }
                    
                    // Reload page after minor delay to refresh validity counts
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text(currentStatus === '1' ? 'Revoke Access' : 'Grant Access');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text(currentStatus === '1' ? 'Revoke Access' : 'Grant Access');
            }
        });
    });

    // AJAX: Update Donor Parameters
    $('.btn-save-donor').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var tr = btn.closest('tr');
        var userId = tr.data('user-id');
        
        var donorId = tr.find('.donor-id-val').val();
        var availability = tr.find('.donor-avail-val').val();
        var eggType = tr.find('.donor-egg-type-val').val();
        var numEggs = tr.find('.donor-eggs-val').val() || 0;
        var storageCountry = tr.find('.donor-country-val').val();

        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_update_donor',
                user_id: userId,
                donor_id: donorId,
                availability: availability,
                egg_type: eggType,
                num_eggs: numEggs,
                storage_country: storageCountry,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    btn.text('Saved ✔').css('background', '#2e7d32');
                    setTimeout(function() {
                        btn.prop('disabled', false).text('Save').css('background', '');
                    }, 1200);
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text('Save');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Save');
            }
        });
    });

    // AJAX: Update Match Inquiry Status
    $('.btn-save-inquiry').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var tr = btn.closest('tr');
        var parentId = btn.data('parent-id');
        var inquiryId = btn.data('inquiry-id');
        var newStatus = tr.find('.inquiry-status-val').val();

        btn.prop('disabled', true).text('Updating...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_update_inquiry',
                parent_id: parentId,
                inquiry_id: inquiryId,
                new_status: newStatus,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    btn.text('Updated ✔').css('background', '#2e7d32');
                    setTimeout(function() {
                        btn.prop('disabled', false).text('Update Status').css('background', '');
                    }, 1200);
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text('Update Status');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Update Status');
            }
        });
    });

    // AJAX: Delete Match Inquiry
    $('.btn-delete-inquiry').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var parentId = btn.data('parent-id');
        var inquiryId = btn.data('inquiry-id');
        var tr = btn.closest('tr');

        if (!confirm('Are you sure you want to permanently delete this match inquiry?')) {
            return;
        }

        btn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_delete_inquiry',
                parent_id: parentId,
                inquiry_id: inquiryId,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Inquiry deleted successfully.');
                    tr.fadeOut(400, function() {
                        var countEl = $('.count-match-inquiries').first();
                        if (countEl.length) {
                            var mCount = parseInt(countEl.text()) || 0;
                            countEl.text(Math.max(0, mCount - 1));
                        }
                        
                        var valEl = $('.val-pending-match').first();
                        if (valEl.length) {
                            var mVal = parseInt(valEl.text()) || 0;
                            valEl.text(Math.max(0, mVal - 1));
                        }
                        tr.remove();
                    });
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Delete');
            }
        });
    });

    // AJAX: Submit Public Contact General Inquiry Form
    $('#ovarias-public-inquiry-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#btn-submit-inquiry');
        var responseBox = $('#inq-form-response');
        
        var originalText = submitBtn.text();
        submitBtn.prop('disabled', true).text('Submitting inquiry...');
        responseBox.hide().removeClass('success error');

        // Check honeypot spam trigger
        if ($('#inq-hp').val() !== '') {
            responseBox.addClass('error').text('Spam validation failed.').show();
            submitBtn.prop('disabled', false).text(originalText);
            return;
        }

        var inqType = $('#inq-type').val();
        var email = $('#inq-email').val();
        var name = $('#inq-name').val();
        var phone = $('#inq-phone').val();
        var message = $('#inq-message').val();

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_public_submit_inquiry',
                inquiry_type: inqType,
                email: email,
                name: name,
                phone: phone,
                message: message,
                ovarias_hp: '',
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    responseBox.removeClass('error').addClass('success').css({
                        'color': '#2e7d32',
                        'background': '#e8f5e9',
                        'padding': '10px',
                        'border-radius': '4px',
                        'font-weight': 'bold'
                    }).text(response.data.message).show();
                    form[0].reset();
                } else {
                    responseBox.removeClass('success').addClass('error').css({
                        'color': '#c62828',
                        'background': '#ffebee',
                        'padding': '10px',
                        'border-radius': '4px',
                        'font-weight': 'bold'
                    }).text(response.data.message).show();
                }
                submitBtn.prop('disabled', false).text(originalText);
            },
            error: function() {
                responseBox.removeClass('success').addClass('error').css({
                    'color': '#c62828',
                    'background': '#ffebee',
                    'padding': '10px',
                    'border-radius': '4px',
                    'font-weight': 'bold'
                }).text('Connection failure. Please try again.').show();
                submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // AJAX: Save/Update General Inquiry Status (Admin Tab)
    $('.btn-save-general-inquiry').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var tr = btn.closest('tr');
        var inquiryId = btn.data('inquiry-id');
        var newStatus = tr.find('.general-inq-status-val').val();

        btn.prop('disabled', true).text('Saving...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_update_general_inquiry',
                inquiry_id: inquiryId,
                new_status: newStatus,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    btn.text('Saved ✔').css('background', '#2e7d32');
                    setTimeout(function() {
                        btn.prop('disabled', false).text('Update').css('background', '');
                    }, 1200);
                } else {
                    alert('Error: ' + response.data.message);
                    btn.prop('disabled', false).text('Update');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Update');
            }
        });
    });

    // AJAX: Delete General Inquiry (Admin Tab)
    $('.btn-delete-general-inquiry').on('click', function(e) {
        e.preventDefault();
        var btn = $(this);
        var inquiryId = btn.data('inquiry-id');
        var tr = btn.closest('tr');
        var name = tr.find('strong').first().text();

        if (!confirm('Are you sure you want to permanently delete this inquiry record from "' + name + '"?')) {
            return;
        }

        btn.prop('disabled', true).text('Deleting...');

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: {
                action: 'ovarias_admin_delete_general_inquiry',
                inquiry_id: inquiryId,
                nonce: ovariasAdminParams.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Inquiry deleted successfully.');
                    tr.fadeOut(400, function() {
                        var giCount = parseInt($('.count-general-inquiries').first().text()) || 0;
                        $('.count-general-inquiries').text(Math.max(0, giCount - 1));
                        
                        var giVal = parseInt($('.val-pending-general').first().text()) || 0;
                        $('.val-pending-general').text(Math.max(0, giVal - 1));
                        tr.remove();
                    });
                } else {
                    alert('Error deleting inquiry: ' + response.data.message);
                    btn.prop('disabled', false).text('Delete');
                }
            },
            error: function() {
                alert('Connection failure.');
                btn.prop('disabled', false).text('Delete');
            }
        });
    });

    // Admin: View Donor Details/Profile Modal
    $(document).on('click', '.btn-view-admin-donor-profile', function(e) {
        e.preventDefault();
        var donorStr = $(this).attr('data-donor');
        if (!donorStr) return;
        
        try {
            var donor = JSON.parse(donorStr);
            
            // Set large profile image
            $('#modal-avatar').attr('src', donor.avatar).attr('alt', donor.name + ' Photo');
            $('#modal-name').text(donor.name);
            $('#modal-age').text(donor.age);
            $('#modal-blood').text(donor.blood_group);
            
            // Grid fields
            $('#modal-donor-id').text(donor.donor_id);
            $('#modal-nationality').text(donor.nationality);
            $('#modal-education').text(donor.education);
            $('#modal-height').text(donor.height ? donor.height + ' cm' : 'N/A');
            $('#modal-weight').text(donor.weight ? donor.weight + ' kg' : 'N/A');
            $('#modal-hair').text(donor.hair);
            $('#modal-eyes').text(donor.eyes);
            $('#modal-num-donations').text(donor.num_donations);
            
            // Category eggs fields
            $('#modal-egg-type').text(donor.egg_type + ' Egg Donor');
            $('#modal-num-eggs').text(donor.num_eggs);
            $('#modal-storage-country').text(donor.storage_country);
            
            // Description sections
            $('#modal-about').text(donor.about_me);
            $('#modal-hobbies').text(donor.hobbies);
            $('#modal-why').text(donor.why_donate);
            
            // Build gallery thumbnails
            var galleryContainer = $('#modal-gallery');
            galleryContainer.empty();
            
            if (donor.gallery && donor.gallery.length > 0) {
                donor.gallery.forEach(function(imgUrl) {
                    var activeClass = (imgUrl === donor.avatar) ? 'active' : '';
                    var thumbHtml = $('<img src="' + imgUrl + '" class="ovarias-modal-thumb ' + activeClass + '" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px; border: 2px solid #CDDCBF; cursor: pointer; transition: 0.2s;">');
                    
                    thumbHtml.on('click', function() {
                        $('.ovarias-modal-thumb').css('border-color', '#CDDCBF');
                        $(this).css('border-color', 'var(--primary)');
                        $('#modal-avatar').attr('src', imgUrl);
                    });
                    
                    galleryContainer.append(thumbHtml);
                });
                galleryContainer.show();
            } else {
                galleryContainer.hide();
            }
            
            $('#donor-detail-modal').css('display', 'flex');
        } catch(err) {
            console.error('Error parsing donor data:', err);
        }
    });

    function closeDonorModal() {
        $('#donor-detail-modal').hide();
    }

    $(document).on('click', '.ovarias-modal-close, .btn-close-donor-modal', function(e) {
        e.preventDefault();
        closeDonorModal();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#donor-detail-modal') || $(e.target).is('.ovarias-modal-overlay')) {
            closeDonorModal();
        }
    });

});
