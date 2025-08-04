// API client for Instagram Clone
class API {
    constructor() {
        this.baseURL = '/api';
    }

    async request(endpoint, options = {}) {
        const url = `${this.baseURL}${endpoint}`;
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json();

            if (!data.success) {
                throw new Error(data.message || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }

    // Authentication methods
    async register(userData) {
        return this.request('/auth/register', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    async login(credentials) {
        return this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify(credentials)
        });
    }

    async logout() {
        return this.request('/auth/logout', {
            method: 'POST'
        });
    }

    async getCurrentUser() {
        return this.request('/auth/me');
    }

    async updateProfile(profileData) {
        return this.request('/auth/profile', {
            method: 'PUT',
            body: JSON.stringify(profileData)
        });
    }

    // User methods
    async getUser(userId) {
        return this.request(`/users/${userId}`);
    }

    async getUserPosts(userId, limit = 12, offset = 0) {
        return this.request(`/users/${userId}/posts?limit=${limit}&offset=${offset}`);
    }

    async followUser(userId) {
        return this.request(`/users/${userId}/follow`, {
            method: 'POST'
        });
    }

    async unfollowUser(userId) {
        return this.request(`/users/${userId}/follow`, {
            method: 'DELETE'
        });
    }

    async searchUsers(query, limit = 20) {
        return this.request(`/users/search?q=${encodeURIComponent(query)}&limit=${limit}`);
    }

    async uploadAvatar(file) {
        const formData = new FormData();
        formData.append('avatar', file);

        return this.request('/users/upload-avatar', {
            method: 'POST',
            headers: {}, // Remove Content-Type to let browser set it
            body: formData
        });
    }

    // Post methods
    async createPost(formData) {
        return this.request('/posts', {
            method: 'POST',
            headers: {}, // Remove Content-Type for FormData
            body: formData
        });
    }

    async getPost(postId) {
        return this.request(`/posts/${postId}`);
    }

    async updatePost(postId, postData) {
        return this.request(`/posts/${postId}`, {
            method: 'PUT',
            body: JSON.stringify(postData)
        });
    }

    async deletePost(postId) {
        return this.request(`/posts/${postId}`, {
            method: 'DELETE'
        });
    }

    async getFeed(limit = 20, offset = 0) {
        return this.request(`/posts?limit=${limit}&offset=${offset}`);
    }

    async getExplorePosts(limit = 30) {
        return this.request(`/explore?limit=${limit}`);
    }

    // Interaction methods
    async likePost(postId) {
        return this.request(`/posts/${postId}/like`, {
            method: 'POST'
        });
    }

    async unlikePost(postId) {
        return this.request(`/posts/${postId}/like`, {
            method: 'DELETE'
        });
    }

    async getPostLikes(postId, limit = 20, offset = 0) {
        return this.request(`/posts/${postId}/likes?limit=${limit}&offset=${offset}`);
    }

    async createComment(postId, content, parentId = null) {
        return this.request(`/posts/${postId}/comments`, {
            method: 'POST',
            body: JSON.stringify({ content, parent_id: parentId })
        });
    }

    async getPostComments(postId, limit = 20, offset = 0) {
        return this.request(`/posts/${postId}/comments?limit=${limit}&offset=${offset}`);
    }

    async updateComment(commentId, content) {
        return this.request(`/comments/${commentId}`, {
            method: 'PUT',
            body: JSON.stringify({ content })
        });
    }

    async deleteComment(commentId) {
        return this.request(`/comments/${commentId}`, {
            method: 'DELETE'
        });
    }

    async likeComment(commentId) {
        return this.request(`/comments/${commentId}/like`, {
            method: 'POST'
        });
    }

    async unlikeComment(commentId) {
        return this.request(`/comments/${commentId}/like`, {
            method: 'DELETE'
        });
    }
}

// Create global API instance
window.api = new API();