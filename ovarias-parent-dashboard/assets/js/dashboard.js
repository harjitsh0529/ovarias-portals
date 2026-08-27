jQuery(document).ready(function($) {
    
    // View Details Modal functionality
    $('.ovarias-btn-view-details').on('click', function(e) {
        e.preventDefault();
        
        var donorDataStr = $(this).attr('data-donor');
        if (!donorDataStr) return;
        
        try {
            var donor = JSON.parse(donorDataStr);
            
            // Populate modal fields
            $('#modal-avatar').attr('src', donor.avatar).attr('alt', donor.name + ' Photo');
            $('#modal-name').text(donor.name);
            $('#modal-age').text(donor.age);
            $('#modal-blood').text(donor.blood_group);
            
            // Populate gallery thumbnails
            var galleryContainer = $('#modal-gallery');
            galleryContainer.empty();
            if (donor.gallery && donor.gallery.length > 0) {
                donor.gallery.forEach(function(imgUrl, index) {
                    var imgHtml = $('<img src="' + imgUrl + '" style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px; border: 2px solid ' + (index === 0 ? 'var(--primary)' : '#c2c7bd') + '; cursor: pointer; transition: 0.2s;">');
                    imgHtml.on('click', function() {
                        galleryContainer.find('img').css('border-color', '#c2c7bd');
                        $(this).css('border-color', 'var(--primary)');
                        $('#modal-avatar').attr('src', imgUrl);
                    });
                    galleryContainer.append(imgHtml);
                });
                galleryContainer.show();
            } else {
                galleryContainer.hide();
            }
            
            $('#modal-nationality').text(donor.nationality);
            $('#modal-education').text(donor.education);
            $('#modal-height').text(donor.height + ' cm');
            $('#modal-weight').text(donor.weight + ' kg');
            $('#modal-hair').text(donor.hair);
            $('#modal-eyes').text(donor.eyes);
            $('#modal-occupation').text(donor.occupation);
            
            $('#modal-about').text(donor.about_me);
            $('#modal-hobbies').text(donor.hobbies);
            $('#modal-why').text(donor.why_donate);
            
            // Open the modal
            $('#donor-detail-modal').addClass('active');
            $('body').css('overflow', 'hidden'); // Prevent background scrolling
            
        } catch(error) {
            console.error('Error parsing donor data:', error);
        }
    });

    // Close Modal when close button is clicked
    $('.ovarias-modal-close, .ovarias-modal-overlay').on('click', function(e) {
        e.preventDefault();
        closeDonorModal();
    });

    // Close Modal on Escape key press
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDonorModal();
        }
    });

    function closeDonorModal() {
        $('#donor-detail-modal').removeClass('active');
        $('body').css('overflow', ''); // Restore background scrolling
    }

});
