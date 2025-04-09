jQuery(document).ready(function($) {
    // Handle file uploads via AJAX
    $(document).on('change', 'input[type="file"]', function(e) {
        var $fileInput = $(this);
        var $form = $fileInput.closest('form');
        var $hiddenInput = $fileInput.siblings('input[type="hidden"]');
        var $statusMessage = $fileInput.siblings('.upload-status');
        
        // Create status message element if it doesn't exist
        if ($statusMessage.length === 0) {
            $statusMessage = $('<div class="upload-status"></div>');
            $fileInput.after($statusMessage);
        }
        
        // Check if a file was selected
        if ($fileInput[0].files.length === 0) {
            $statusMessage.html('No file selected');
            $hiddenInput.val('');
            return;
        }
        
        // Show loading message
        $statusMessage.html('Uploading file...');
        
        // Create form data
        var formData = new FormData();
        formData.append('action', 'mpwpb_upload_checkout_file');
        formData.append('nonce', mpwpb_file_upload.nonce);
        formData.append('file', $fileInput[0].files[0]);
        formData.append('field_name', $fileInput.attr('name'));
        
        // Send AJAX request
        $.ajax({
            url: mpwpb_file_upload.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Update hidden input with file URL
                    $hiddenInput.val(response.data.url);
                    
                    // Show success message
                    $statusMessage.html('File uploaded successfully: ' + response.data.filename);
                    $statusMessage.addClass('success').removeClass('error');
                    
                    console.log('File uploaded successfully:', response.data);
                } else {
                    // Show error message
                    $statusMessage.html('Error uploading file: ' + response.data.message);
                    $statusMessage.addClass('error').removeClass('success');
                    $hiddenInput.val('');
                    
                    console.error('Error uploading file:', response.data);
                }
            },
            error: function(xhr, status, error) {
                // Show error message
                $statusMessage.html('Error uploading file: ' + error);
                $statusMessage.addClass('error').removeClass('success');
                $hiddenInput.val('');
                
                console.error('AJAX error:', error);
            }
        });
    });
    
    // Add some basic styling for the upload status messages
    $('<style>\
        .upload-status { margin-top: 5px; font-size: 0.9em; }\
        .upload-status.success { color: green; }\
        .upload-status.error { color: red; }\
    </style>').appendTo('head');
});
