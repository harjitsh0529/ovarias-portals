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

            // New PDF fields
            formData.append('ethnic_origin', $('#new-donor-ethnic-origin').val());
            formData.append('race', $('#new-donor-race').val());
            formData.append('ethnicity', $('#new-donor-ethnicity').val());
            formData.append('body_type', $('#new-donor-body-type').val());
            formData.append('face_shape', $('#new-donor-face-shape').val());
            formData.append('nose_shape', $('#new-donor-nose-shape').val());
            formData.append('lips_shape', $('#new-donor-lips-shape').val());
            formData.append('hair_type', $('#new-donor-hair-type').val());
            formData.append('skin_tone', $('#new-donor-skin-tone').val());
            formData.append('freckles', $('#new-donor-freckles').val());
            formData.append('favourite_lessons', $('#new-donor-favourite-lessons').val());
            formData.append('proven_fertility', $('#new-donor-proven-fertility').val());
            formData.append('hearing', $('#new-donor-hearing').val());
            formData.append('vision', $('#new-donor-vision').val());
            formData.append('wearing_glasses', $('#new-donor-wearing-glasses').val());
            formData.append('wearing_lenses', $('#new-donor-wearing-lenses').val());
            formData.append('surgeries', $('#new-donor-surgeries').val());
            formData.append('allergies', $('#new-donor-allergies').val());
            formData.append('dental_history', $('#new-donor-dental-history').val());
            formData.append('twins_history', $('#new-donor-twins-history').val());
            formData.append('alcohol_use', $('#new-donor-alcohol-use').val());
            formData.append('smoking_tobacco', $('#new-donor-smoking-tobacco').val());
            formData.append('vaping', $('#new-donor-vaping').val());
            formData.append('drug_use', $('#new-donor-drug-use').val());
            formData.append('medications', $('#new-donor-medications').val());
            formData.append('decl_anonymous', $('#new-donor-decl-anonymous').val());
            formData.append('decl_genetic_tests', $('#new-donor-decl-genetic-tests').val());
            formData.append('zodiac_sign', $('#new-donor-zodiac-sign').val());
            formData.append('fav_colour', $('#new-donor-fav-colour').val());
            formData.append('fav_dish', $('#new-donor-fav-dish').val());
            formData.append('fav_season', $('#new-donor-fav-season').val());
            formData.append('fav_holiday', $('#new-donor-fav-holiday').val());
            formData.append('fav_sport', $('#new-donor-fav-sport').val());
            formData.append('fav_music', $('#new-donor-fav-music').val());
            formData.append('childhood_dream', $('#new-donor-childhood-dream').val());
            formData.append('fav_author', $('#new-donor-fav-author').val());
            formData.append('fav_movie', $('#new-donor-fav-movie').val());
            formData.append('countries_visited', $('#new-donor-countries-visited').val());
            formData.append('goals_in_life', $('#new-donor-goals-in-life').val());
            formData.append('idols_heroes', $('#new-donor-idols-heroes').val());
            formData.append('personality_words', $('#new-donor-personality-words').val());
            formData.append('strong_side', $('#new-donor-strong-side').val());
            formData.append('weak_side', $('#new-donor-weak-side').val());

            // Medical History Checkboxes
            $('.new-donor-med-checkbox:checked').each(function() {
                var key = $(this).data('key');
                formData.append('medical_history[' + key + ']', 'Yes');
            });
            
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

            // New PDF grid fields
            $('#modal-ethnic-origin').text(donor.ethnic_origin || 'N/A');
            $('#modal-race-ethnicity').text((donor.race || 'N/A') + ' / ' + (donor.ethnicity || 'N/A'));
            $('#modal-body-face').text((donor.body_type || 'N/A') + ' / ' + (donor.face_shape || 'N/A'));
            $('#modal-nose-lips').text('Nose: ' + (donor.nose_shape || 'N/A') + ' / Lips: ' + (donor.lips_shape || 'N/A'));
            $('#modal-hairtype-skin').text('Hair Type: ' + (donor.hair_type || 'N/A') + ' / Skin: ' + (donor.skin_tone || 'N/A'));
            $('#modal-freckles').text(donor.freckles || 'N/A');

            // Health & Lifestyle
            $('#modal-proven-fertility').text(donor.proven_fertility || 'N/A');
            $('#modal-hearing-vision').text('Hearing: ' + (donor.hearing || 'N/A') + ' / Vision: ' + (donor.vision || 'N/A'));
            $('#modal-glasses-lenses').text('Glasses: ' + (donor.wearing_glasses || 'N/A') + ' / Lenses: ' + (donor.wearing_lenses || 'N/A'));
            $('#modal-surgeries-allergies').text('Surgeries: ' + (donor.surgeries || 'N/A') + ' / Allergies: ' + (donor.allergies || 'N/A'));
            $('#modal-dental-twins').text('Dental: ' + (donor.dental_history || 'N/A') + ' / Twins: ' + (donor.twins_history || 'N/A'));
            $('#modal-smoking-alcohol-vaping').text('Smoker: ' + (donor.smoking_tobacco || 'N/A') + ' / Alcohol: ' + (donor.alcohol_use || 'N/A') + ' / Vaping: ' + (donor.vaping || 'N/A'));
            $('#modal-drugs-meds').text('Drugs: ' + (donor.drug_use || 'N/A') + ' / Meds: ' + (donor.medications || 'N/A'));
            $('#modal-anonymity-testing').text('Anonymous: ' + (donor.decl_anonymous || 'N/A') + ' / Open to Tests: ' + (donor.decl_genetic_tests || 'N/A'));

            // Preferences
            $('#modal-zodiac-sign').text(donor.zodiac_sign || 'N/A');
            $('#modal-fav-colour').text(donor.fav_colour || 'N/A');
            $('#modal-fav-dish-season').text('Dish: ' + (donor.fav_dish || 'N/A') + ' / Season: ' + (donor.fav_season || 'N/A'));
            $('#modal-fav-holiday-sport').text('Holiday: ' + (donor.fav_holiday || 'N/A') + ' / Sport: ' + (donor.fav_sport || 'N/A'));
            $('#modal-fav-music').text(donor.fav_music || 'N/A');
            $('#modal-childhood-dream').text(donor.childhood_dream || 'N/A');
            $('#modal-fav-book-movie').text('Book: ' + (donor.fav_author || 'N/A') + ' / Movie: ' + (donor.fav_movie || 'N/A'));
            $('#modal-countries-visited').text(donor.countries_visited || 'N/A');
            $('#modal-goals-in-life').text(donor.goals_in_life || 'N/A');
            $('#modal-idols-heroes').text(donor.idols_heroes || 'N/A');
            $('#modal-personality-words').text(donor.personality_words || 'N/A');
            $('#modal-strong-weak').text('Strong: ' + (donor.strong_side || 'N/A') + ' / Weak: ' + (donor.weak_side || 'N/A'));
            $('#modal-favourite-lessons-val').text(donor.favourite_lessons || 'N/A');

            // Render Medical & Family History Display
            var medicalContainer = $('#modal-medical-history-display');
            medicalContainer.empty();
            
            var conditionsMap = {
                'heart_disease_under40': 'Heart Disease (under 40)',
                'heart_attack_under40': 'Heart Attack (under 40)',
                'high_blood_pressure': 'High Blood Pressure',
                'other_heart_conditions': 'Other Heart Conditions',
                'hemophilia': 'Hemophilia',
                'sickle_cell_anemia': 'Sickle Cell Anemia',
                'lymphoma': 'Lymphoma',
                'other_blood_disorders': 'Other Blood Disorders',
                'emphysema_copd': 'Emphysema / COPD',
                'lung_cancer': 'Lung Cancer',
                'environmental_allergies': 'Environmental Allergies',
                'neurofibromatosis': 'Neurofibromatosis',
                'skin_cancer_melanoma': 'Skin Cancer / Melanoma',
                'eczema': 'Eczema',
                'pigmentation_disorders': 'Pigmentation Disorders',
                'rectal_cancer': 'Colo-Rectal Cancer',
                'crohns_disease': "Crohn's Disease",
                'cystic_fibrosis': 'Cystic Fibrosis',
                'liver_disease': 'Liver Disease',
                'schizophrenia': 'Schizophrenia',
                'bipolar_disorder': 'Bipolar Disorder',
                'depression': 'Depression',
                'suicide': 'Suicide',
                'congenital_heart_malformation': 'Congenital Heart Malformation',
                'arthritis': 'Arthritis',
                'congenital_spine_malformation': 'Congenital Spine Malformation',
                'dwarfism': 'Dwarfism',
                'muscular_dystrophy': 'Muscular Dystrophy',
                'osteoporosis': 'Osteoporosis',
                'congenital_blindness': 'Congenital Blindness',
                'cataracts_under50': 'Cataracts (under 50)',
                'dyslexia': 'Dyslexia',
                'retinoblastoma': 'Retinoblastoma',
                'glaucoma': 'Glaucoma',
                'congenital_deafness': 'Congenital Deafness',
                'cleft_lip_palate': 'Cleft Lip / Palate',
                'club_foot': 'Club Foot',
                'turners_syndrome': "Turner's Syndrome",
                'klinefelters_syndrome': "Klinefelter's Syndrome",
                'fragile_x_syndrome': 'Fragile X Syndrome',
                'other_disorders': 'Other Disorders'
            };
            
            var hasMedicalHistory = false;
            if (donor.medical_history) {
                Object.keys(conditionsMap).forEach(function(key) {
                    if (donor.medical_history[key] === 'Yes') {
                        medicalContainer.append('<div style="color: #c62828; font-weight: 600; padding: 4px 8px; background: #ffebee; border-radius: 4px; border: 1px solid #ffcdd2;">✔ ' + conditionsMap[key] + '</div>');
                        hasMedicalHistory = true;
                    }
                });
            }
            if (!hasMedicalHistory) {
                medicalContainer.append('<div style="grid-column: 1 / -1; color: #8A9181; font-style: italic; text-align: center; padding: 10px;">No medical history reported (all clean).</div>');
            }
            
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
            
            currentViewedDonor = donor;
            $('#donor-detail-modal').css('display', 'flex');
        } catch(err) {
            console.error('Error parsing donor data:', err);
        }
    });

    var currentViewedDonor = null;

    function closeDonorModal() {
        $('#donor-detail-modal').hide();
    }

    $(document).on('click', '.ovarias-modal-close, .btn-close-donor-modal', function(e) {
        e.preventDefault();
        closeDonorModal();
    });

    // Trigger edit from inside view profile modal
    $(document).on('click', '.btn-trigger-edit-from-view', function(e) {
        e.preventDefault();
        closeDonorModal();
        if (currentViewedDonor) {
            openEditDonorModal(currentViewedDonor);
        }
    });

    // Edit Donor Button in Table Row
    $(document).on('click', '.btn-edit-admin-donor-profile', function(e) {
        e.preventDefault();
        var donorStr = $(this).attr('data-donor');
        if (!donorStr) return;
        try {
            var donor = JSON.parse(donorStr);
            openEditDonorModal(donor);
        } catch(err) {
            console.error('Error parsing donor edit data:', err);
        }
    });

    function openEditDonorModal(donor) {
        if (!donor) return;

        $('#edit-donor-user-id').val(donor.user_id || '');
        $('#edit-first-name').val(donor.first_name || '');
        $('#edit-last-name').val(donor.last_name || '');
        $('#edit-donor-id').val(donor.donor_id || '');
        $('#edit-donor-avail').val(donor.availability || 'Available');
        $('#edit-donor-egg-type').val(donor.egg_type || 'Fresh');
        $('#edit-donor-num-eggs').val(donor.num_eggs || 0);
        $('#edit-donor-storage').val(donor.storage_country || '');
        $('#edit-donor-dob').val(donor.dob || '');
        $('#edit-donor-nationality').val(donor.nationality !== 'N/A' ? donor.nationality : '');
        $('#edit-donor-blood').val(donor.blood_group !== 'N/A' ? donor.blood_group : '');
        $('#edit-donor-height').val(donor.height !== 'N/A' ? donor.height : '');
        $('#edit-donor-weight').val(donor.weight !== 'N/A' ? donor.weight : '');
        $('#edit-donor-eyes').val(donor.eyes !== 'N/A' ? donor.eyes : '');
        $('#edit-donor-hair').val(donor.hair !== 'N/A' ? donor.hair : '');
        $('#edit-donor-education').val(donor.education !== 'N/A' ? donor.education : '');
        $('#edit-donor-study').val(donor.study !== 'N/A' ? donor.study : '');
        $('#edit-donor-occupation').val(donor.occupation !== 'N/A' ? donor.occupation : '');
        $('#edit-donor-languages').val(donor.languages !== 'N/A' ? donor.languages : '');

        // Physical
        $('#edit-donor-ethnic-origin').val(donor.ethnic_origin !== 'N/A' ? donor.ethnic_origin : '');
        $('#edit-donor-race').val(donor.race !== 'N/A' ? donor.race : '');
        $('#edit-donor-ethnicity').val(donor.ethnicity !== 'N/A' ? donor.ethnicity : '');
        $('#edit-donor-body-type').val(donor.body_type !== 'N/A' ? donor.body_type : '');
        $('#edit-donor-face-shape').val(donor.face_shape !== 'N/A' ? donor.face_shape : '');
        $('#edit-donor-nose-shape').val(donor.nose_shape !== 'N/A' ? donor.nose_shape : '');
        $('#edit-donor-lips-shape').val(donor.lips_shape !== 'N/A' ? donor.lips_shape : '');
        $('#edit-donor-hair-type').val(donor.hair_type !== 'N/A' ? donor.hair_type : '');
        $('#edit-donor-skin-tone').val(donor.skin_tone !== 'N/A' ? donor.skin_tone : '');
        $('#edit-donor-freckles').val(donor.freckles !== 'N/A' ? donor.freckles : '');

        // Health & Lifestyle
        $('#edit-donor-proven-fertility').val(donor.proven_fertility !== 'N/A' ? donor.proven_fertility : '');
        $('#edit-donor-hearing').val(donor.hearing !== 'N/A' ? donor.hearing : '');
        $('#edit-donor-vision').val(donor.vision !== 'N/A' ? donor.vision : '');
        $('#edit-donor-wearing-glasses').val(donor.wearing_glasses !== 'N/A' ? donor.wearing_glasses : '');
        $('#edit-donor-wearing-lenses').val(donor.wearing_lenses !== 'N/A' ? donor.wearing_lenses : '');
        $('#edit-donor-surgeries').val(donor.surgeries !== 'N/A' ? donor.surgeries : '');
        $('#edit-donor-allergies').val(donor.allergies !== 'N/A' ? donor.allergies : '');
        $('#edit-donor-dental-history').val(donor.dental_history !== 'N/A' ? donor.dental_history : '');
        $('#edit-donor-twins-history').val(donor.twins_history !== 'N/A' ? donor.twins_history : '');
        $('#edit-donor-alcohol-use').val(donor.alcohol_use !== 'N/A' ? donor.alcohol_use : '');
        $('#edit-donor-smoking-tobacco').val(donor.smoking_tobacco !== 'N/A' ? donor.smoking_tobacco : '');
        $('#edit-donor-vaping').val(donor.vaping !== 'N/A' ? donor.vaping : '');
        $('#edit-donor-drug-use').val(donor.drug_use !== 'N/A' ? donor.drug_use : '');
        $('#edit-donor-medications').val(donor.medications !== 'N/A' ? donor.medications : '');

        // Preferences & Personality
        $('#edit-donor-zodiac-sign').val(donor.zodiac_sign !== 'N/A' ? donor.zodiac_sign : '');
        $('#edit-donor-fav-colour').val(donor.fav_colour !== 'N/A' ? donor.fav_colour : '');
        $('#edit-donor-fav-dish').val(donor.fav_dish !== 'N/A' ? donor.fav_dish : '');
        $('#edit-donor-fav-season').val(donor.fav_season !== 'N/A' ? donor.fav_season : '');
        $('#edit-donor-fav-holiday').val(donor.fav_holiday !== 'N/A' ? donor.fav_holiday : '');
        $('#edit-donor-fav-sport').val(donor.fav_sport !== 'N/A' ? donor.fav_sport : '');
        $('#edit-donor-fav-music').val(donor.fav_music !== 'N/A' ? donor.fav_music : '');
        $('#edit-donor-childhood-dream').val(donor.childhood_dream !== 'N/A' ? donor.childhood_dream : '');
        $('#edit-donor-fav-author').val(donor.fav_author !== 'N/A' ? donor.fav_author : '');
        $('#edit-donor-fav-movie').val(donor.fav_movie !== 'N/A' ? donor.fav_movie : '');
        $('#edit-donor-countries-visited').val(donor.countries_visited !== 'N/A' ? donor.countries_visited : '');
        $('#edit-donor-goals-in-life').val(donor.goals_in_life !== 'N/A' ? donor.goals_in_life : '');
        $('#edit-donor-idols-heroes').val(donor.idols_heroes !== 'N/A' ? donor.idols_heroes : '');
        $('#edit-donor-personality-words').val(donor.personality_words !== 'N/A' ? donor.personality_words : '');
        $('#edit-donor-strong-side').val(donor.strong_side !== 'N/A' ? donor.strong_side : '');
        $('#edit-donor-weak-side').val(donor.weak_side !== 'N/A' ? donor.weak_side : '');

        // Narrative
        $('#edit-donor-about').val(donor.about_me !== 'N/A' ? donor.about_me : '');
        $('#edit-donor-hobbies').val(donor.hobbies !== 'N/A' ? donor.hobbies : '');
        $('#edit-donor-why').val(donor.why_donate !== 'N/A' ? donor.why_donate : '');

        // Medical History Checkboxes
        $('.edit-med-checkbox').prop('checked', false);
        if (donor.medical_history) {
            $('.edit-med-checkbox').each(function() {
                var key = $(this).data('key');
                if (donor.medical_history[key] === 'Yes') {
                    $(this).prop('checked', true);
                }
            });
        }

        // Reset file inputs
        $('#edit-donor-avatar').val('');
        $('#edit-donor-gallery').val('');

        $('#ovarias-edit-donor-modal').css('display', 'flex');
    }

    // Save Full Donor Profile AJAX
    $('#ovarias-edit-donor-form').on('submit', function(e) {
        e.preventDefault();
        var submitBtn = $('#btn-submit-edit-donor');
        submitBtn.prop('disabled', true).text('Saving Changes...');

        var formElement = document.getElementById('ovarias-edit-donor-form');
        var formData = new FormData(formElement);
        formData.append('action', 'ovarias_admin_save_donor_full_profile');
        formData.append('nonce', ovariasAdminParams.nonce);

        $.ajax({
            url: ovariasAdminParams.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    submitBtn.text('Profile Saved ✔').css('background', '#2e7d32');
                    setTimeout(function() {
                        location.reload();
                    }, 800);
                } else {
                    alert('Error: ' + (response.data ? response.data.message : 'Could not save profile.'));
                    submitBtn.prop('disabled', false).text('Save Profile Changes');
                }
            },
            error: function() {
                alert('Server connection error. Please try again.');
                submitBtn.prop('disabled', false).text('Save Profile Changes');
            }
        });
    });

    $(document).on('click', '.btn-close-edit-modal, .btn-close-edit-modal-x', function(e) {
        e.preventDefault();
        $('#ovarias-edit-donor-modal').hide();
    });

    $(window).on('click', function(e) {
        if ($(e.target).is('#donor-detail-modal')) {
            closeDonorModal();
        }
        if ($(e.target).is('#ovarias-edit-donor-modal')) {
            $('#ovarias-edit-donor-modal').hide();
        }
    });

});
