jQuery(document).ready(function ($) {
    // Only run once - check for existing styled containers
    if ($('.acf-image-upload-container.usp-styled').length) {
        return;
    }

    var uspFilesInput = $('input[name="usp-files[]"]');

    if (uspFilesInput.length) {
        console.log('USP Files input found, styling interface');

        // FILE SIZE LIMIT CONFIGURATION (in bytes)
        // Set to 5MB as displayed in UI
        var MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB in bytes

        // Determine if this is the guest form or host form
        var isGuestForm = false;
        if ($('.guest-registration-form').length ||
            window.location.href.indexOf('guest-registration') !== -1 ||
            $('form:contains("Guest Registration")').length) {
            isGuestForm = true;
            console.log('Guest registration form detected');
        }

        // Check if we're on the CWM member registration page
        var isCwmForm = false;
        if ($('form[id*="247110"]').length ||
            $('input[name="usp-form-id"][value="247110"]').length ||
            $('form').find('input[value="247110"]').length ||
            window.location.href.indexOf('cwm-registration') !== -1) {
            isCwmForm = true;
            console.log('CWM member form detected (Form ID: 247110)');
        }

        // Set requirements based on form type
        var requiredCount, headerText, validationText;

        if (isCwmForm) {
            requiredCount = 1;
            headerText = 'Choose a file from your computer';
            validationText = '1 image max';
        } else if (isGuestForm) {
            requiredCount = 1;
            headerText = 'Select 1 image';
            validationText = '1 image max';
        } else {
            requiredCount = 1;
            headerText = 'Select up to 5 images. The first image added will be used as the featured image.';
            validationText = 'Upload between 1 - 5 images.';
        }

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
            '<p class="upload-instructions">' + (isCwmForm ? 'Upload Your Photo' : 'Upload your photos') + '</p>' +
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
            '<p class="drag-instructions">Drag and drop ' + (isGuestForm || isCwmForm ? 'an image' : 'multiple files') + ' here</p>' +
            '<p class="or-separator">or</p>' +
            '<button type="button" class="select-file-button">Select ' + (isGuestForm || isCwmForm ? 'Image' : 'Files') + '</button>' +
            '</div>' +
            '</div>'
        );
        wrapper.append(dropZone);

        // File info section
        var fileInfo = $(
            '<div class="file-info multi-info" style="display:none;">' +
            '<p><span class="file-count">0</span> ' + (isGuestForm || isCwmForm ? 'image' : 'files') + ' selected (' + validationText + ')</p>' +
            '<button type="button" class="remove-files">Remove ' + (isGuestForm || isCwmForm ? 'Image' : 'All') + '</button>' +
            '</div>'
        );
        wrapper.append(fileInfo);

        // Error message container (NEW)
        var errorContainer = $(
            '<div class="file-upload-error" style="display:none; color: #d32f2f; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 4px; border: 1px solid #ef9a9a;">' +
            '<p class="error-message" style="margin: 0;"></p>' +
            '</div>'
        );
        wrapper.append(errorContainer);

        // Restrictions
        wrapper.append(
            '<div class="file-restrictions">' +
            '<p>Supported formats: JPG, JPEG, PNG, GIF</p>' +
            '<p>Maximum file size: 5MB per image</p>' +
            '</div>'
        );

        // Hide all original inputs
        $('input[name="usp-files[]"]').each(function () {
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
        uspFilesInput.attr('multiple', !isGuestForm && !isCwmForm);

        // NEW: Function to validate file size
        function validateFileSize(file) {
            if (file.size > MAX_FILE_SIZE) {
                var fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                var maxSizeMB = (MAX_FILE_SIZE / (1024 * 1024)).toFixed(0);
                return {
                    valid: false,
                    message: 'File "' + file.name + '" is ' + fileSizeMB + 'MB. Maximum file size is ' + maxSizeMB + 'MB. Please choose a smaller file.'
                };
            }
            return { valid: true };
        }

        // NEW: Function to show error
        function showError(message) {
            errorContainer.find('.error-message').text(message);
            errorContainer.show();
            // Auto-hide after 8 seconds
            setTimeout(function () {
                errorContainer.fadeOut();
            }, 8000);
        }

        // NEW: Function to clear error
        function clearError() {
            errorContainer.hide();
            errorContainer.find('.error-message').text('');
        }

        // Make elements clickable
        dropZone.find('.select-file-button').on('click', function (e) {
            e.preventDefault();
            clearError(); // Clear any existing errors
            uspFilesInput.click();
        });

        dropZone.on('click', function (e) {
            if (!$(e.target).closest('button').length) {
                clearError(); // Clear any existing errors
                uspFilesInput.click();
            }
        });

        // Handle file selection with IMMEDIATE validation
        uspFilesInput.on('change', function () {
            if (this.files && this.files.length) {
                var invalidFiles = [];
                var validFiles = [];

                // VALIDATE EACH FILE IMMEDIATELY
                for (var i = 0; i < this.files.length; i++) {
                    var validation = validateFileSize(this.files[i]);
                    if (validation.valid) {
                        validFiles.push(this.files[i]);
                    } else {
                        invalidFiles.push({
                            file: this.files[i],
                            message: validation.message
                        });
                    }
                }

                // If any files are invalid, show error and prevent upload
                if (invalidFiles.length > 0) {
                    // Show error message for the first invalid file
                    showError(invalidFiles[0].message);

                    // Reset the file input
                    uspFilesInput.val('');

                    // Don't add any files to selection
                    console.log('File upload blocked: File too large');
                    return false;
                }

                // If we get here, all files are valid
                clearError();

                if (isGuestForm || isCwmForm) {
                    // For guest and CWM form, just take the first file
                    selectedFiles = [];
                    selectedFiles.push(validFiles[0]);
                } else {
                    // For host form, add new files to existing selection
                    for (var i = 0; i < validFiles.length; i++) {
                        // Skip duplicates
                        var isDuplicate = false;
                        for (var j = 0; j < selectedFiles.length; j++) {
                            if (selectedFiles[j].name === validFiles[i].name &&
                                selectedFiles[j].size === validFiles[i].size) {
                                isDuplicate = true;
                                break;
                            }
                        }
                        if (!isDuplicate) {
                            selectedFiles.push(validFiles[i]);
                        }
                    }
                }

                // Update UI with all selected files
                updatePreview();
            }
        });

        // Reset button
        fileInfo.find('.remove-files').on('click', function () {
            selectedFiles = [];
            uspFilesInput.val('');
            clearError();
            updatePreview();
        });

        // Drag and drop support
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (event) {
            dropZone[0].addEventListener(event, function (e) {
                e.preventDefault();
                e.stopPropagation();
            }, false);
        });

        ['dragenter', 'dragover'].forEach(function (event) {
            dropZone[0].addEventListener(event, function () {
                dropZone.addClass('highlight');
            }, false);
        });

        ['dragleave', 'drop'].forEach(function (event) {
            dropZone[0].addEventListener(event, function () {
                dropZone.removeClass('highlight');
            }, false);
        });

        // Handle file drop with IMMEDIATE validation
        dropZone[0].addEventListener('drop', function (e) {
            var dt = e.dataTransfer;
            var files = dt.files;

            if (files.length) {
                var invalidFiles = [];
                var validFiles = [];

                // VALIDATE EACH FILE IMMEDIATELY
                for (var i = 0; i < files.length; i++) {
                    var validation = validateFileSize(files[i]);
                    if (validation.valid) {
                        validFiles.push(files[i]);
                    } else {
                        invalidFiles.push({
                            file: files[i],
                            message: validation.message
                        });
                    }
                }

                // If any files are invalid, show error and prevent upload
                if (invalidFiles.length > 0) {
                    showError(invalidFiles[0].message);
                    console.log('Drag & drop blocked: File too large');
                    return false;
                }

                // If we get here, all files are valid
                clearError();

                if (isGuestForm || isCwmForm) {
                    selectedFiles = [];
                    selectedFiles.push(validFiles[0]);
                } else {
                    for (var i = 0; i < validFiles.length; i++) {
                        var isDuplicate = false;
                        for (var j = 0; j < selectedFiles.length; j++) {
                            if (selectedFiles[j].name === validFiles[i].name &&
                                selectedFiles[j].size === validFiles[i].size) {
                                isDuplicate = true;
                                break;
                            }
                        }
                        if (!isDuplicate) {
                            selectedFiles.push(validFiles[i]);
                        }
                    }
                }

                updatePreview();
            }
        }, false);

        // Preview update function
        function updatePreview() {
            if (selectedFiles.length > 0) {
                previewContainer.empty();

                for (var i = 0; i < selectedFiles.length; i++) {
                    (function (file, index) {
                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var preview = $(
                                '<div class="preview-item">' +
                                '<img src="' + e.target.result + '" alt="Preview">' +
                                '<div class="file-name">' +
                                (file.name.length > 15 ? file.name.substring(0, 12) + '...' : file.name) +
                                '</div>' +
                                ((isGuestForm || isCwmForm) ? '' : '<button type="button" class="remove-file" data-index="' + index + '">×</button>') +
                                '</div>'
                            );
                            previewContainer.append(preview);

                            if (!isGuestForm && !isCwmForm) {
                                preview.find('.remove-file').on('click', function () {
                                    var idx = $(this).data('index');
                                    selectedFiles.splice(idx, 1);
                                    clearError();
                                    updatePreview();
                                });
                            }
                        };
                        reader.readAsDataURL(file);
                    })(selectedFiles[i], i);
                }

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

                updateFormData();
            } else {
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
                var dataTransfer = new DataTransfer();

                for (var i = 0; i < selectedFiles.length; i++) {
                    dataTransfer.items.add(selectedFiles[i]);
                }

                uspFilesInput[0].files = dataTransfer.files;

                $('input[name="usp_file_count"], input[name="usp_pro_files_validated"]').remove();
                if (selectedFiles.length >= requiredCount) {
                    $('<input type="hidden" name="usp_file_count" value="' + selectedFiles.length + '">').appendTo(container);
                    $('<input type="hidden" name="usp_pro_files_validated" value="true">').appendTo(container);
                }
            } catch (error) {
                console.error('Error updating form data:', error);
            }
        }

        // Form submission validation (makes CWM upload optional)
        $('form:has(input[name="usp-files[]"])').on('submit', function (e) {
            if (isCwmForm) {
                return true; // No validation for CWM forms
            }

            if (selectedFiles.length < requiredCount) {
                alert('Please select ' + requiredCount + ' image(s) before submitting.');
                e.preventDefault();
                return false;
            }
            return true;
        });

        // Add CSS for remove button and error messages
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
            .file-upload-error {\
                animation: slideDown 0.3s ease-out;\
            }\
            @keyframes slideDown {\
                from {\
                    opacity: 0;\
                    transform: translateY(-10px);\
                }\
                to {\
                    opacity: 1;\
                    transform: translateY(0);\
                }\
            }\
        </style>').appendTo('head');
    }
});