// Upload module
class Upload {
    constructor() {
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Open upload modal
        document.getElementById('addPostBtn').addEventListener('click', () => {
            this.openUploadModal();
        });

        // Close upload modal
        document.getElementById('closeUploadModal').addEventListener('click', () => {
            this.closeUploadModal();
        });

        // File input change
        document.getElementById('postImage').addEventListener('change', (e) => {
            this.handleImageSelect(e.target.files[0]);
        });

        // Upload form submission
        document.getElementById('uploadForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleUpload();
        });

        // Drag and drop
        const uploadArea = document.querySelector('.upload-area');
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#0095f6';
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.style.borderColor = '#dbdbdb';
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.style.borderColor = '#dbdbdb';
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                this.handleImageSelect(files[0]);
            }
        });
    }

    openUploadModal() {
        document.getElementById('uploadModal').classList.add('show');
        this.resetUploadForm();
    }

    closeUploadModal() {
        document.getElementById('uploadModal').classList.remove('show');
        this.resetUploadForm();
    }

    resetUploadForm() {
        document.getElementById('uploadForm').reset();
        document.getElementById('imagePreview').style.display = 'none';
        document.querySelector('.upload-placeholder').style.display = 'block';
    }

    handleImageSelect(file) {
        if (!file) return;

        // Validate file type
        if (!file.type.startsWith('image/')) {
            this.showError('Please select an image file');
            return;
        }

        // Validate file size (5MB limit)
        if (file.size > 5 * 1024 * 1024) {
            this.showError('Image must be less than 5MB');
            return;
        }

        // Update file input
        const fileInput = document.getElementById('postImage');
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        fileInput.files = dataTransfer.files;

        // Show preview
        const reader = new FileReader();
        reader.onload = (e) => {
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
            document.querySelector('.upload-placeholder').style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    async handleUpload() {
        const fileInput = document.getElementById('postImage');
        const caption = document.getElementById('postCaption').value;
        const location = document.getElementById('postLocation').value;

        if (!fileInput.files[0]) {
            this.showError('Please select an image');
            return;
        }

        const submitBtn = document.querySelector('#uploadForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sharing...';

        try {
            const formData = new FormData();
            formData.append('image', fileInput.files[0]);
            formData.append('caption', caption);
            formData.append('location', location);

            await api.createPost(formData);
            
            this.closeUploadModal();
            this.showSuccess('Post shared successfully!');
            
            // Refresh feed to show new post
            if (window.feed) {
                window.feed.loadFeed(true);
            }
            
            // Switch to feed view
            if (window.app) {
                window.app.showView('feed');
            }
        } catch (error) {
            console.error('Upload failed:', error);
            this.showError(error.message || 'Failed to share post');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Share';
        }
    }

    showError(message) {
        // Simple error display - in production, use a proper notification system
        alert('Error: ' + message);
    }

    showSuccess(message) {
        // Simple success display - in production, use a proper notification system
        console.log('Success: ' + message);
    }
}

// Create global upload instance
window.upload = new Upload();