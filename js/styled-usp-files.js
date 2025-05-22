jQuery(document).ready(function ($) {
    // Only run once - check for existing styled containers
    if ($('.acf-image-upload-container.usp-styled').length) {
        return;
    }
    
    var uspFilesInput = $('input[name="usp-files[]"]');
    
    if (uspFilesInput.length) {
        console.log('USP Files input found, styling interface');
        
        // Determine if this is the guest form or host form
        var isGuestForm = false;
        
        // Check if we're on the guest registration page (customize this check)
        if ($('.guest-registration-form').length || 
            window.location.href.indexOf('guest-registration') !== -1 ||
            $('form:contains("Guest Registration")').length) {
            isGuestForm = true;
            console.log('Guest registration form detected');
        }
        
        // Set requirements based on form type
        var requiredCount = isGuestForm ? 1 : 5;
        var headerText = isGuestForm ? 'Select 1 image' : 'Select 5 images from your computer';
        var validationText = isGuestForm ? '1 required' : '5 required';
        
        // Track selected files
        var selectedFiles = [];
        
        // Create container with unique class
        var container = $('<div class="acf-image-upload-container usp-styled"></div>');
        var wrapper = $('<div class="custom-file-upload-wrapper"></div>');
        container.append(wrapper);
        
        // Main upload section
        wrapper.append(
            '<div class="main-upload-section">' +
            '<div class="upload-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Default-Image.png" alt="Upload"></div>' +
            '<p class="upload-instructions">Upload your photos</p>' +
            '<p class="upload-subtitle">' + headerText + '</p>' +
            '</div>'
        );
        
        // Preview container
        var previewContainer = $('<div class="file-preview-container multi-preview" style="display:none;"></div>');
        wrapper.append(previewContainer);
        
        // Drag and drop zone
        var dropZone = $(
            '<div class="drag-drop-zone">' +
            '<div class="drag-drop-content">' +
            '<div class="drag-icon"><img src="https://cortescommunityhousing.org/wp-content/uploads/2025/04/Upload-Image.png" alt="Drag"></div>' +
            '<p class="drag-instructions">Drag and drop ' + (isGuestForm ? 'an image' : 'multiple files') + ' here</p>' +
            '<p class="or-separator">or</p>' +
            '<button type="button" class="select-file-button">Select ' + (isGuestForm ? 'Image' : 'Files') + '</button>' +
            '</div>' +
            '</div>'
        );
        wrapper.append(dropZone);
        
        // File info section
        var fileInfo = $(
            '<div class="file-info multi-info" style="display:none;">' +
            '<p><span class="file-count">0</span> ' + (isGuestForm ? 'image' : 'files') + ' selected (' + validationText + ')</p>' +
            '<button type="button" class="remove-files">Remove ' + (isGuestForm ? 'Image' : 'All') + '</button>' +
            '</div>'
        );
        wrapper.append(fileInfo);
        
        // Restrictions
        wrapper.append(
            '<div class="file-restrictions">' +
            '<p>Supported formats: JPG, JPEG, PNG, GIF</p>' +
            '<p>Maximum file size: 10MB per image</p>' +
            '</div>'
        );
        
        // Hide all original inputs
        $('input[name="usp-files[]"]').each(function() {
            $(this).css({
                'position': 'absolute',
                'width': '1px',
                'height': '1px',
                'opacity': '0.01',
                'z-index': '-1'
            });
        });
        
        // Add styled interface before first input
        $('input[name="usp-files[]"]:first').before(container);
        
        // Use first input for operations
        uspFilesInput = $('input[name="usp-files[]"]:first');
        uspFilesInput.attr('multiple', !isGuestForm); // Only allow multiple for host form
        
        // Make elements clickable
        dropZone.find('.select-file-button').on('click', function(e) {
            e.preventDefault();
            uspFilesInput.click();
        });
        
        dropZone.on('click', function(e) {
            if (!$(e.target).closest('button').length) {
                uspFilesInput.click();
            }
        });
        
        // Handle file selection
        uspFilesInput.on('change', function() {
            if (this.files && this.files.length) {
                if (isGuestForm) {
                    // For guest form, just take the first file
                    selectedFiles = [];
                    selectedFiles.push(this.files[0]);
                } else {
                    // For host form, add new files to existing selection
                    for (var i = 0; i < this.files.length; i++) {
                        // Skip duplicates
                        var isDuplicate = false;
                        for (var j = 0; j < selectedFiles.length; j++) {
                            if (selectedFiles[j].name === this.files[i].name && 
                                selectedFiles[j].size === this.files[i].size) {
                                isDuplicate = true;
                                break;
                            }
                        }
                        if (!isDuplicate) {
                            selectedFiles.push(this.files[i]);
                        }
                    }
                }
                
                // Update UI with all selected files
                updatePreview();
            }
        });
        
        // Reset button
        fileInfo.find('.remove-files').on('click', function() {
            selectedFiles = [];
            uspFilesInput.val('');
            updatePreview();
        });
        
        // Drag and drop support
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(event) {
            dropZone[0].addEventListener(event, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
        
        ['dragenter', 'dragover'].forEach(function(event) {
            dropZone[0].addEventListener(event, function() {
                dropZone.addClass('highlight');
            }, false);
        });
        
        ['dragleave', 'drop'].forEach(function(event) {
            dropZone[0].addEventListener(event, function() {
                dropZone.removeClass('highlight');
            }, false);
        });
        
        // Handle file drop
        dropZone[0].addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            
            if (isGuestForm && files.length > 0) {
                // For guest form, just take the first file
                selectedFiles = [files[0]];
            } else {
                // For host form, add dropped files to selection
                for (var i = 0; i < files.length; i++) {
                    var isDuplicate = false;
                    for (var j = 0; j < selectedFiles.length; j++) {
                        if (selectedFiles[j].name === files[i].name && 
                            selectedFiles[j].size === files[i].size) {
                            isDuplicate = true;
                            break;
                        }
                    }
                    if (!isDuplicate) {
                        selectedFiles.push(files[i]);
                    }
                }
            }
            
            // Update UI
            updatePreview();
        }, false);
        
        // Function to update the UI with all selected files
        function updatePreview() {
            previewContainer.empty();
            
            if (selectedFiles.length > 0) {
                // Create previews for each file
                for (var i = 0; i < selectedFiles.length; i++) {
                    (function(file, index) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var preview = $(
                                '<div class="preview-item" data-index="' + index + '">' +
                                '<img src="' + e.target.result + '" alt="Preview">' +
                                '<div class="file-name">' + 
                                (file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name) +
                                '</div>' +
                                (isGuestForm ? '' : '<button type="button" class="remove-file" data-index="' + index + '">×</button>') +
                                '</div>'
                            );
                            previewContainer.append(preview);
                            
                            // Add remove button handler for host form
                            if (!isGuestForm) {
                                preview.find('.remove-file').on('click', function() {
                                    var idx = $(this).data('index');
                                    selectedFiles.splice(idx, 1);
                                    updatePreview();
                                });
                            }
                        };
                        reader.readAsDataURL(file);
                    })(selectedFiles[i], i);
                }
                
                // Show preview and info
                previewContainer.show();
                fileInfo.show();
                fileInfo.find('.file-count').text(selectedFiles.length);
                
                if (selectedFiles.length >= requiredCount) {
                    fileInfo.find('.file-count').parent().css('color', 'green');
                } else {
                    fileInfo.find('.file-count').parent().css('color', '');
                }
                
                dropZone.addClass('has-file');
                $('.main-upload-section').hide();
                
                // Update form data for submission
                updateFormData();
            } else {
                // Reset UI if no files
                previewContainer.hide();
                fileInfo.hide();
                $('.main-upload-section').show();
                dropZone.removeClass('has-file');
                $('input[name="usp_file_count"], input[name="usp_pro_files_validated"]').remove();
            }
        }
        
        // Update form data for submission
        function updateFormData() {
            try {
                // Create DataTransfer object
                var dataTransfer = new DataTransfer();
                
                // Add all files
                for (var i = 0; i < selectedFiles.length; i++) {
                    dataTransfer.items.add(selectedFiles[i]);
                }
                
                // Set files property on input
                uspFilesInput[0].files = dataTransfer.files;
                
                // Add validation fields
                $('input[name="usp_file_count"], input[name="usp_pro_files_validated"]').remove();
                if (selectedFiles.length >= requiredCount) {
                    $('<input type="hidden" name="usp_file_count" value="' + selectedFiles.length + '">').appendTo(container);
                    $('<input type="hidden" name="usp_pro_files_validated" value="true">').appendTo(container);
                }
            } catch (error) {
                console.error('Error updating form data:', error);
            }
        }
        
        // Form submission validation
        $('form:has(input[name="usp-files[]"])').on('submit', function(e) {
            if (selectedFiles.length < requiredCount) {
                alert('Please select ' + requiredCount + ' image(s) before submitting.');
                e.preventDefault();
                return false;
            }
            return true;
        });
        
        // Add CSS for remove button
        $('<style>\
            .preview-item {\
                position: relative;\
            }\
            .preview-item .remove-file {\
                position: absolute;\
                top: 5px;\
                right: 5px;\
                background: rgba(0,0,0,0.5);\
                color: white;\
                border: none;\
                border-radius: 50%;\
                width: 20px;\
                height: 20px;\
                line-height: 20px;\
                text-align: center;\
                padding: 0;\
                cursor: pointer;\
            }\
        </style>').appendTo('head');
    }
});