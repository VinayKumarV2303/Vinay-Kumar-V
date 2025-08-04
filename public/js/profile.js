// Profile module
class Profile {
    constructor() {
        this.currentProfileUserId = null;
        this.userPosts = [];
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Profile button in nav
        document.getElementById('profileBtn').addEventListener('click', () => {
            if (window.auth && window.auth.getCurrentUser()) {
                this.showUserProfile(window.auth.getCurrentUser().id);
            }
        });
    }

    async showUserProfile(userId) {
        this.currentProfileUserId = userId;
        
        try {
            // Load user data
            const userResponse = await api.getUser(userId);
            const user = userResponse.data.user;
            
            // Load user posts
            const postsResponse = await api.getUserPosts(userId, 12, 0);
            this.userPosts = postsResponse.data.posts;
            
            // Render profile
            this.renderProfile(user);
            this.renderUserPosts();
            
            // Show profile view
            if (window.app) {
                window.app.showView('profile');
            }
        } catch (error) {
            console.error('Failed to load profile:', error);
            this.showError('Failed to load profile');
        }
    }

    renderProfile(user) {
        const container = document.getElementById('profileHeader');
        const currentUser = window.auth ? window.auth.getCurrentUser() : null;
        const isOwnProfile = currentUser && currentUser.id === user.id;
        
        container.innerHTML = `
            <div class="profile-avatar-section">
                <img src="uploads/${user.profile_picture}" alt="${user.username}" class="profile-avatar">
                ${isOwnProfile ? '<button class="change-avatar-btn" style="display: none;">Change Avatar</button>' : ''}
            </div>
            <div class="profile-info">
                <div class="profile-username">
                    <span>${user.username}</span>
                    ${user.is_verified ? '<i class="fas fa-check-circle" style="color: #0095f6;"></i>' : ''}
                    <div class="profile-actions">
                        ${isOwnProfile ? `
                            <button class="btn-secondary" id="editProfileBtn">Edit Profile</button>
                        ` : `
                            <button class="btn-primary ${user.is_following ? 'btn-secondary' : ''}" id="followBtn" data-user-id="${user.id}">
                                ${user.is_following ? 'Unfollow' : 'Follow'}
                            </button>
                            <button class="btn-secondary">Message</button>
                        `}
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="profile-stat">
                        <span class="profile-stat-count">${user.posts_count}</span>
                        posts
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-count">${user.followers_count}</span>
                        followers
                    </div>
                    <div class="profile-stat">
                        <span class="profile-stat-count">${user.following_count}</span>
                        following
                    </div>
                </div>
                <div class="profile-bio">
                    <strong>${user.full_name}</strong>
                    ${user.bio ? `<div>${this.formatBio(user.bio)}</div>` : ''}
                    ${user.website ? `<a href="${user.website}" target="_blank" style="color: #0095f6;">${user.website}</a>` : ''}
                </div>
            </div>
        `;

        // Add event listeners
        if (!isOwnProfile) {
            const followBtn = document.getElementById('followBtn');
            if (followBtn) {
                followBtn.addEventListener('click', () => this.toggleFollow(user.id, followBtn));
            }
        }
    }

    renderUserPosts() {
        const container = document.getElementById('profilePosts');
        
        if (this.userPosts.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #8e8e8e;">
                    <i class="fas fa-camera" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                    <h3>No posts yet</h3>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="profile-posts-grid">
                ${this.userPosts.map(post => `
                    <div class="profile-post" data-post-id="${post.id}">
                        <img src="uploads/${post.image_url}" alt="Post">
                        <div class="explore-overlay">
                            <div class="explore-stats">
                                <div class="explore-stat">
                                    <i class="fas fa-heart"></i>
                                    <span>${post.likes_count}</span>
                                </div>
                                <div class="explore-stat">
                                    <i class="fas fa-comment"></i>
                                    <span>${post.comments_count}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;

        // Add click listeners to posts
        container.querySelectorAll('.profile-post').forEach(post => {
            post.addEventListener('click', () => {
                const postId = parseInt(post.dataset.postId);
                if (window.feed) {
                    window.feed.openPostModal(postId);
                }
            });
        });
    }

    async toggleFollow(userId, button) {
        const isFollowing = button.textContent.trim() === 'Unfollow';
        
        button.disabled = true;
        button.textContent = isFollowing ? 'Unfollowing...' : 'Following...';

        try {
            if (isFollowing) {
                await api.unfollowUser(userId);
                button.textContent = 'Follow';
                button.classList.remove('btn-secondary');
                button.classList.add('btn-primary');
            } else {
                await api.followUser(userId);
                button.textContent = 'Unfollow';
                button.classList.remove('btn-primary');
                button.classList.add('btn-secondary');
            }
        } catch (error) {
            console.error('Failed to toggle follow:', error);
            this.showError('Failed to update follow status');
            
            // Revert button state
            button.textContent = isFollowing ? 'Unfollow' : 'Follow';
        } finally {
            button.disabled = false;
        }
    }

    formatBio(bio) {
        // Simple formatting for mentions and hashtags
        return bio
            .replace(/#(\w+)/g, '<span style="color: #0095f6;">#$1</span>')
            .replace(/@(\w+)/g, '<span style="color: #0095f6;">@$1</span>')
            .replace(/\n/g, '<br>');
    }

    showError(message) {
        console.error(message);
        // In production, use a proper notification system
    }
}

// Create global profile instance
window.profile = new Profile();