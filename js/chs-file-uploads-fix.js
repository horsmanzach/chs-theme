/**
 * USP Pro File Upload Fixes
 * Fixes issues with file upload fields in USP Pro forms
 */
jQuery(document).ready(function($) {
    console.log('Running file upload fixes');
    
    // 1. Fix the host_featured_image field
    const featuredField = $('input[name="host_featured_image"]');
    if (featuredField.length) {
        console.log('Found featured image field, fixing...');
        
        // Make sure file selection triggers UI update
        const featuredWrapper = featuredField.closest('.custom-file-upload-wrapper');
        const featuredPreview = featuredWrapper.find('.file-preview-container');
        
        // Add click handler to the entire drop zone
        featuredWrapper.find('.drag-drop-zone').on('click', function(e) {
            if (!$(e.target).closest('button').length) {
                featuredField.trigger('click');
            }
        });
        
        // Select button should trigger file input
        featuredWrapper.find('.select-file-button').on('click', function(e) {
            e.preventDefault();
            featuredField.trigger('click');
        });
        
        // Update UI when file selected
        featuredField.on('change', function() {
            if (this.files && this.files.length) {
                const file = this.files[0];
                
                // Show preview
                const reader = new FileReader();
                reader.onloadend = function() {
                    const previewImg = featuredPreview.find('img');
                    previewImg.attr('src', reader.result);
                    featuredPreview.show();
                };
                reader.readAsDataURL(file);
                
                // Update UI
                featuredWrapper.find('.main-upload-section').hide();
                featuredWrapper.find('.drag-drop-zone').addClass('has-file');
                featuredWrapper.find('.file-info').show();
                featuredWrapper.find('.filename').text(file.name);
            }
        });
    }
    
    // 2. Fix the multiple file upload field
    const multipleFileField = $('input[name="usp-files[]"]');
    if (multipleFileField.length) {
        console.log('Found multiple file input:', multipleFileField.attr('id'));
        
        // Ensure multiple attribute is set
        multipleFileField.prop('multiple', true);
        
        // Force the multiple attribute with setAttribute for some browsers
        multipleFileField[0].setAttribute('multiple', 'multiple');
        
        // Get the wrapper and UI elements
        const multiWrapper = multipleFileField.closest('.custom-file-upload-wrapper');
        const multiPreview = multiWrapper.find('.file-preview-container');
        const fileCount = multiWrapper.find('.file-count');
        const dragZone = multiWrapper.find('.drag-drop-zone');
        const selectButton = multiWrapper.find('.select-file-button');
        
        // Initialize data attributes
        if (!multiWrapper.attr('data-files')) {
            multiWrapper.attr('data-files', JSON.stringify([]));
            multiWrapper.data('file-counter', 0);
        }
        
        // Add CSS for file preview and remove button
        $('<style>\
            .preview-item {\
                position: relative;\
                display: inline-block;\
                margin: 5px;\
                width: 100px;\
            }\
            .preview-item img {\
                max-width: 100px;\
                max-height: 100px;\
                object-fit: cover;\
                border-radius: 4px;\
            }\
            .file-name {\
                font-size: 10px;\
                max-width: 100px;\
                overflow: hidden;\
                text-overflow: ellipsis;\
                white-space: nowrap;\
            }\
            .remove-file {\
                position: absolute;\
                top: -8px;\
                right: -8px;\
                background: red;\
                color: white;\
                border-radius: 50%;\
                width: 20px;\
                height: 20px;\
                line-height: 18px;\
                text-align: center;\
                cursor: pointer;\
                border: none;\
                font-size: 14px;\
                padding: 0;\
            }\
        </style>').appendTo('head');
        
        // Hide the default file input completely
        multipleFileField.css({
            'position': 'absolute',
            'width': '1px',
            'height': '1px',
            'overflow': 'hidden',
            'opacity': '0.01',
            'z-index': '-1'
        });
        
        // Make select button work - using direct event binding
        selectButton.off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Select button clicked, triggering file input');
            multipleFileField.trigger('click');
        });
        
        // Make drop zone clickable
        dragZone.on('click', function(e) {
            if (!$(e.target).closest('button').length) {
                multipleFileField.trigger('click');
                console.log('Drop zone clicked, triggering file input');
            }
        });
        
        // Handle file selection with incremental addition
        multipleFileField.on('change', function(e) {
            if (this.files && this.files.length) {
                console.log('Files selected:', this.files.length);
                
                // Create DataTransfer to hold all files
                const dt = new DataTransfer();
                
                // Get existing files data
                const existingFilesData = multiWrapper.attr('data-files') ? JSON.parse(multiWrapper.attr('data-files')) : [];
                let fileCounter = multiWrapper.data('file-counter') || 0;
                
                // Add new files to collection
                Array.from(this.files).forEach(file => {
                    fileCounter++;
                    const fileId = 'file-' + fileCounter;
                    
                    // Check for duplicate filenames
                    const isDuplicate = existingFilesData.some(existingFile => 
                        existingFile.name === file.name && existingFile.size === file.size
                    );
                    
                    if (!isDuplicate) {
                        existingFilesData.push({
                            id: fileId,
                            name: file.name,
                            type: file.type,
                            size: file.size
                        });
                        
                        // Create preview
                        const reader = new FileReader();
                        reader.onloadend = function() {
                            const preview = $(`<div class="preview-item" data-file-id="${fileId}">
                                <img src="${reader.result}" alt="Preview">
                                <div class="file-name">${file.name}</div>
                                <button type="button" class="remove-file" data-file-id="${fileId}">×</button>
                            </div>`);
                            multiPreview.append(preview);
                            
                            // Update UI elements
                            multiPreview.show();
                            multiWrapper.find('.file-info').show();
                            fileCount.text(existingFilesData.length);
                            
                            // Hide main upload section if we have files
                            if (existingFilesData.length > 0) {
                                multiWrapper.find('.main-upload-section').hide();
                                dragZone.addClass('has-file');
                            }
                            
                            // Store updated data
                            multiWrapper.attr('data-files', JSON.stringify(existingFilesData));
                            multiWrapper.data('file-counter', fileCounter);
                            
                            // Add hidden field for tracking file count
                            if ($('#usp-file-count').length === 0) {
                                $('<input type="hidden" id="usp-file-count" name="usp_file_count" value="' + existingFilesData.length + '">').appendTo(multiWrapper);
                            } else {
                                $('#usp-file-count').val(existingFilesData.length);
                            }
                            
                            // Check if we've met minimum requirements
                            if (existingFilesData.length >= 4) {
                                multipleFileField.addClass('valid').removeClass('invalid');
                                multiWrapper.addClass('has-enough-files');
                                
                                // Add validation bypass
                                if ($('#usp-files-validated').length === 0) {
                                    $('<input type="hidden" id="usp-files-validated" name="usp_pro_files_validated" value="true">').appendTo(multiWrapper);
                                }
                            } else {
                                multipleFileField.addClass('invalid').removeClass('valid');
                                multiWrapper.removeClass('has-enough-files');
                                $('#usp-files-validated').remove();
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
        
        // Handle removing individual files
        multiPreview.on('click', '.remove-file', function() {
            const fileId = $(this).data('file-id');
            const previewItem = $(this).closest('.preview-item');
            
            // Remove from data attribute
            const existingFilesData = multiWrapper.attr('data-files') ? JSON.parse(multiWrapper.attr('data-files')) : [];
            const updatedFiles = existingFilesData.filter(file => file.id !== fileId);
            multiWrapper.attr('data-files', JSON.stringify(updatedFiles));
            
            // Remove preview
            previewItem.remove();
            
            // Update file count
            fileCount.text(updatedFiles.length);
            $('#usp-file-count').val(updatedFiles.length);
            
            // Update UI if no files left
            if (updatedFiles.length === 0) {
                multiWrapper.find('.file-info').hide();
                multiPreview.hide();
                multiWrapper.find('.main-upload-section').show();
                dragZone.removeClass('has-file');
                multiWrapper.removeClass('has-enough-files');
                $('#usp-files-validated').remove();
            }
            
            // Update validation class
            if (updatedFiles.length < 4) {
                multipleFileField.removeClass('valid').addClass('invalid');
                multiWrapper.removeClass('has-enough-files');
                $('#usp-files-validated').remove();
            }
        });
        
        // Reset button functionality (remove all files)
        multiWrapper.find('.remove-files').on('click', function() {
            // Clear file data
            multiWrapper.attr('data-files', JSON.stringify([]));
            
            // Reset UI
            multiPreview.empty().hide();
            multiWrapper.find('.file-info').hide();
            multiWrapper.find('.main-upload-section').show();
            dragZone.removeClass('has-file');
            multiWrapper.removeClass('has-enough-files');
            fileCount.text('0');
            $('#usp-file-count').val('0');
            $('#usp-files-validated').remove();
            
            // Clear file input
            multipleFileField.val('').removeClass('valid').addClass('invalid');
        });
    }
    
    // 3. Specifically target all select files buttons again to ensure they work
    $('.custom-file-upload-wrapper .select-file-button').off('click').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        // Find the closest file input
        const fileInput = $(this).closest('.custom-file-upload-wrapper').find('input[type="file"]');
        console.log('Select button clicked, found input:', fileInput.length > 0, fileInput.attr('name'));
        
        // Trigger click
        if(fileInput.length) {
            fileInput.trigger('click');
        }
    });
    
    // 4. Override form validation for file inputs
    $('form.usp-form').on('submit', function(e) {
        console.log('Form submitted, checking files...');
        
        let hasErrors = false;
        let errorMsg = '';
        
        // Check featured image field if required
        if (featuredField.length && featuredField.prop('required')) {
            if (!featuredField[0].files || featuredField[0].files.length === 0) {
                hasErrors = true;
                errorMsg += "Please select a featured image.\n";
                console.log('Featured image missing');
            } else {
                console.log('Featured image OK');
            }
        }
        
        // Check multiple files
        if (multipleFileField.length) {
            const minFiles = 4; // Minimum required
            const fileCountVal = parseInt($('#usp-file-count').val() || '0');
            
            console.log('File count check:', fileCountVal, 'minimum:', minFiles);
            
            if (fileCountVal < minFiles) {
                hasErrors = true;
                errorMsg += "Please select at least " + minFiles + " images for your listing.\n";
                console.log('Not enough files:', fileCountVal);
            } else {
                console.log('Multiple files OK:', fileCountVal);
                
                // Add validation bypass if not already present
                if ($('#usp-files-validated').length === 0) {
                    $('<input type="hidden" id="usp-files-validated" name="usp_pro_files_validated" value="true">').appendTo($(this));
                }
            }
        }
        
        // Show errors if any
        if (hasErrors) {
            alert(errorMsg);
            e.preventDefault();
            return false;
        }
        
        console.log('Form validation passed');
        // Add final validation bypass flag
        $('<input type="hidden" name="usp_pro_files_validated" value="true">').appendTo($(this));
        return true;
    });
    
    // 5. Add drag and drop support for both file inputs
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        $('.drag-drop-zone').each(function() {
            this.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });
    });
    
    // Highlight when dragging
    ['dragenter', 'dragover'].forEach(eventName => {
        $('.drag-drop-zone').each(function() {
            this.addEventListener(eventName, function() {
                $(this).addClass('highlight');
            }, false);
        });
    });
    
    // Remove highlight when leaving or dropping
    ['dragleave', 'drop'].forEach(eventName => {
        $('.drag-drop-zone').each(function() {
            this.addEventListener(eventName, function() {
                $(this).removeClass('highlight');
            }, false);
        });
    });
    
    // Handle file drop
    $('.drag-drop-zone').each(function() {
        const zone = this;
        const wrapper = $(zone).closest('.custom-file-upload-wrapper');
        const fileInput = wrapper.find('input[type="file"]');
        
        zone.addEventListener('drop', function(e) {
            if (fileInput.length) {
                const dt = e.dataTransfer;
                if (dt.files && dt.files.length) {
                    console.log('Files dropped:', dt.files.length);
                    
                    // Transfer files to input
                    fileInput[0].files = dt.files;
                    
                    // Trigger change event to update UI
                    fileInput.trigger('change');
                }
            }
        }, false);
    });
});