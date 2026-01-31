/**
 * Demo Builder - Chunked Upload
 * 
 * Handles large file uploads by splitting into chunks
 * 
 * @package DemoBuilder
 */

window.DemoBuilderUploader = (function($) {
    'use strict';

    const CHUNK_SIZE = 5 * 1024 * 1024; // 5MB chunks

    /**
     * Generate UUID for upload session
     */
    function generateUUID() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    /**
     * Upload file in chunks
     * 
     * @param {File} file - File to upload
     * @param {Object} options - Upload options
     * @returns {Promise}
     */
    async function uploadChunked(file, options = {}) {
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        const uploadId = generateUUID();
        
        const callbacks = {
            onProgress: options.onProgress || function() {},
            onChunkComplete: options.onChunkComplete || function() {},
            onComplete: options.onComplete || function() {},
            onError: options.onError || function() {}
        };

        try {
            // Upload each chunk
            for (let i = 0; i < totalChunks; i++) {
                const start = i * CHUNK_SIZE;
                const end = Math.min(start + CHUNK_SIZE, file.size);
                const chunk = file.slice(start, end);

                const formData = new FormData();
                formData.append('chunk', chunk);
                formData.append('chunkIndex', i);
                formData.append('totalChunks', totalChunks);
                formData.append('uploadId', uploadId);
                formData.append('filename', file.name);
                formData.append('action', 'demo_builder_upload_chunk');
                formData.append('nonce', window.demoBuilderData?.nonce || '');

                const response = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.data?.message || 'Chunk upload failed');
                }

                // Progress callback
                const percent = Math.round(((i + 1) / totalChunks) * 100);
                callbacks.onProgress(percent, i + 1, totalChunks);
                callbacks.onChunkComplete(i, result.data);
            }

            // Finalize upload
            const finalFormData = new FormData();
            finalFormData.append('uploadId', uploadId);
            finalFormData.append('filename', file.name);
            finalFormData.append('action', 'demo_builder_finalize_upload');
            finalFormData.append('nonce', window.demoBuilderData?.nonce || '');

            const finalResponse = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: finalFormData
            });

            const finalResult = await finalResponse.json();

            if (!finalResult.success) {
                throw new Error(finalResult.data?.message || 'Finalize upload failed');
            }

            callbacks.onComplete(finalResult.data);
            return finalResult.data;

        } catch (error) {
            callbacks.onError(error.message);
            throw error;
        }
    }

    /**
     * Cancel ongoing upload
     * 
     * @param {string} uploadId - Upload session ID
     */
    async function cancelUpload(uploadId) {
        const formData = new FormData();
        formData.append('uploadId', uploadId);
        formData.append('action', 'demo_builder_cancel_upload');
        formData.append('nonce', window.demoBuilderData?.nonce || '');

        await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });
    }

    /**
     * Get PHP upload limits
     */
    async function getUploadLimits() {
        const formData = new FormData();
        formData.append('action', 'demo_builder_get_upload_limits');
        formData.append('nonce', window.demoBuilderData?.nonce || '');

        const response = await fetch(window.ajaxurl || '/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();
        return result.success ? result.data : null;
    }

    /**
     * Check if file needs chunked upload
     * 
     * @param {File} file - File to check
     * @param {number} limit - PHP upload limit in bytes
     * @returns {boolean}
     */
    function needsChunkedUpload(file, limit) {
        // Use chunked upload for files larger than 80% of PHP limit
        return file.size > limit * 0.8;
    }

    /**
     * Format file size
     * 
     * @param {number} bytes - Bytes
     * @returns {string}
     */
    function formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB'];
        let index = 0;
        while (bytes >= 1024 && index < units.length - 1) {
            bytes /= 1024;
            index++;
        }
        return bytes.toFixed(2) + ' ' + units[index];
    }

    // Public API
    return {
        uploadChunked: uploadChunked,
        cancelUpload: cancelUpload,
        getUploadLimits: getUploadLimits,
        needsChunkedUpload: needsChunkedUpload,
        formatSize: formatSize,
        CHUNK_SIZE: CHUNK_SIZE
    };

})(jQuery);

// jQuery plugin wrapper
(function($) {
    'use strict';

    $.fn.demoBuilderUploader = function(options) {
        const defaults = {
            onProgress: function(percent) {},
            onComplete: function(data) {},
            onError: function(message) {}
        };

        const settings = $.extend({}, defaults, options);

        return this.each(function() {
            const $input = $(this);

            $input.on('change', async function(e) {
                const file = e.target.files[0];
                if (!file) return;

                try {
                    // Get PHP limits
                    const limits = await DemoBuilderUploader.getUploadLimits();
                    
                    if (limits && DemoBuilderUploader.needsChunkedUpload(file, limits.effective_limit)) {
                        // Use chunked upload
                        await DemoBuilderUploader.uploadChunked(file, settings);
                    } else {
                        // Use standard upload
                        const formData = new FormData();
                        formData.append('file', file);
                        formData.append('action', 'demo_builder_upload_backup');
                        formData.append('nonce', window.demoBuilderData?.nonce || '');

                        const response = await fetch(window.ajaxurl, {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();
                        if (result.success) {
                            settings.onComplete(result.data);
                        } else {
                            throw new Error(result.data?.message || 'Upload failed');
                        }
                    }
                } catch (error) {
                    settings.onError(error.message);
                }
            });
        });
    };

})(jQuery);
