// Feed module
class Feed {
    constructor() {
        this.posts = [];
        this.offset = 0;
        this.loading = false;
        this.hasMore = true;
    }

    async loadFeed(refresh = false) {
        if (this.loading) return;
        
        if (refresh) {
            this.offset = 0;
            this.hasMore = true;
            this.posts = [];
        }

        this.loading = true;

        try {
            const response = await api.getFeed(20, this.offset);
            const newPosts = response.data.posts;

            if (newPosts.length === 0) {
                this.hasMore = false;
            } else {
                this.posts = refresh ? newPosts : [...this.posts, ...newPosts];
                this.offset += newPosts.length;
            }

            this.renderPosts();
        } catch (error) {
            console.error('Failed to load feed:', error);
            this.showError('Failed to load posts');
        } finally {
            this.loading = false;
        }
    }

    async loadExplorePosts() {
        try {
            const response = await api.getExplorePosts(30);
            this.renderExplorePosts(response.data.posts);
        } catch (error) {
            console.error('Failed to load explore posts:', error);
            this.showError('Failed to load explore posts');
        }
    }

    renderPosts() {
        const container = document.getElementById('postsContainer');
        container.innerHTML = '';

        if (this.posts.length === 0) {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; color: #8e8e8e;">
                    <i class="fas fa-camera" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                    <h3>No posts yet</h3>
                    <p>Follow some users or create your first post!</p>
                </div>
            `;
            return;
        }

        this.posts.forEach(post => {
            const postElement = this.createPostElement(post);
            container.appendChild(postElement);
        });
    }

    renderExplorePosts(posts) {
        const container = document.getElementById('exploreGrid');
        container.innerHTML = '';

        posts.forEach(post => {
            const item = document.createElement('div');
            item.className = 'explore-item';
            item.innerHTML = `
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
            `;
            
            item.addEventListener('click', () => {
                this.openPostModal(post.id);
            });
            
            container.appendChild(item);
        });
    }

    createPostElement(post) {
        const article = document.createElement('article');
        article.className = 'post';
        article.innerHTML = `
            <header class="post-header">
                <img src="uploads/${post.profile_picture}" alt="${post.username}" class="post-avatar">
                <div class="post-user-info">
                    <a href="#" class="post-username" data-user-id="${post.user_id}">${post.username}</a>
                    ${post.location ? `<div class="post-location">${post.location}</div>` : ''}
                </div>
                <button class="post-menu">
                    <i class="fas fa-ellipsis-h"></i>
                </button>
            </header>
            
            <div class="post-image-container">
                <img src="uploads/${post.image_url}" alt="Post" class="post-image">
            </div>
            
            <div class="post-actions">
                <button class="post-action like-btn ${post.is_liked ? 'liked' : ''}" data-post-id="${post.id}">
                    <i class="fas fa-heart"></i>
                </button>
                <button class="post-action comment-btn" data-post-id="${post.id}">
                    <i class="fas fa-comment"></i>
                </button>
                <button class="post-action share-btn">
                    <i class="fas fa-share"></i>
                </button>
                <button class="post-action bookmarked">
                    <i class="fas fa-bookmark"></i>
                </button>
            </div>
            
            ${post.likes_count > 0 ? `
                <div class="post-likes">
                    <button class="likes-count" data-post-id="${post.id}">
                        ${post.likes_count} ${post.likes_count === 1 ? 'like' : 'likes'}
                    </button>
                </div>
            ` : ''}
            
            ${post.caption ? `
                <div class="post-content">
                    <div class="post-caption">
                        <span class="post-username-caption">${post.username}</span>
                        ${this.formatCaption(post.caption)}
                    </div>
                </div>
            ` : ''}
            
            ${post.comments_count > 0 ? `
                <div class="post-comments">
                    <button class="view-comments" data-post-id="${post.id}">
                        View all ${post.comments_count} comments
                    </button>
                </div>
            ` : ''}
            
            <div class="post-time">
                ${this.formatTime(post.created_at)}
            </div>
        `;

        // Add event listeners
        this.addPostEventListeners(article, post);

        return article;
    }

    addPostEventListeners(element, post) {
        // Like button
        const likeBtn = element.querySelector('.like-btn');
        likeBtn.addEventListener('click', () => this.toggleLike(post.id, likeBtn));

        // Comment button
        const commentBtn = element.querySelector('.comment-btn');
        commentBtn.addEventListener('click', () => this.openPostModal(post.id));

        // View comments
        const viewCommentsBtn = element.querySelector('.view-comments');
        if (viewCommentsBtn) {
            viewCommentsBtn.addEventListener('click', () => this.openPostModal(post.id));
        }

        // Username links
        element.querySelectorAll('.post-username, .post-username-caption').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (window.profile) {
                    window.profile.showUserProfile(post.user_id);
                }
            });
        });
    }

    async toggleLike(postId, button) {
        const isLiked = button.classList.contains('liked');
        
        try {
            if (isLiked) {
                await api.unlikePost(postId);
                button.classList.remove('liked');
            } else {
                await api.likePost(postId);
                button.classList.add('liked');
            }
            
            // Update the post in our local data
            const post = this.posts.find(p => p.id === postId);
            if (post) {
                post.is_liked = !isLiked;
                post.likes_count += isLiked ? -1 : 1;
                
                // Update likes count display
                const postElement = button.closest('.post');
                const likesElement = postElement.querySelector('.likes-count');
                if (likesElement) {
                    likesElement.textContent = `${post.likes_count} ${post.likes_count === 1 ? 'like' : 'likes'}`;
                }
            }
        } catch (error) {
            console.error('Failed to toggle like:', error);
            this.showError('Failed to update like');
        }
    }

    async openPostModal(postId) {
        try {
            const response = await api.getPost(postId);
            const post = response.data.post;
            
            const modal = document.getElementById('postModal');
            const content = document.getElementById('postModalContent');
            
            content.innerHTML = this.createPostModalContent(post);
            modal.classList.add('show');
            
            // Load comments
            this.loadPostComments(postId);
        } catch (error) {
            console.error('Failed to load post:', error);
            this.showError('Failed to load post');
        }
    }

    createPostModalContent(post) {
        return `
            <div style="flex: 1; display: flex; align-items: center; justify-content: center; background: black;">
                <img src="uploads/${post.image_url}" alt="Post" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
            <div style="flex: 0 0 400px; background: white; display: flex; flex-direction: column;">
                <header class="post-header">
                    <img src="uploads/${post.profile_picture}" alt="${post.username}" class="post-avatar">
                    <div class="post-user-info">
                        <a href="#" class="post-username">${post.username}</a>
                        ${post.location ? `<div class="post-location">${post.location}</div>` : ''}
                    </div>
                </header>
                
                ${post.caption ? `
                    <div class="post-content">
                        <div class="post-caption">
                            <span class="post-username-caption">${post.username}</span>
                            ${this.formatCaption(post.caption)}
                        </div>
                    </div>
                ` : ''}
                
                <div class="comments-section" id="commentsSection">
                    <!-- Comments will be loaded here -->
                </div>
                
                <div class="post-actions">
                    <button class="post-action like-btn ${post.is_liked ? 'liked' : ''}" data-post-id="${post.id}">
                        <i class="fas fa-heart"></i>
                    </button>
                    <button class="post-action comment-btn">
                        <i class="fas fa-comment"></i>
                    </button>
                    <button class="post-action share-btn">
                        <i class="fas fa-share"></i>
                    </button>
                </div>
                
                ${post.likes_count > 0 ? `
                    <div class="post-likes">
                        ${post.likes_count} ${post.likes_count === 1 ? 'like' : 'likes'}
                    </div>
                ` : ''}
                
                <div class="add-comment">
                    <input type="text" placeholder="Add a comment..." id="commentInput">
                    <button id="postCommentBtn">Post</button>
                </div>
            </div>
        `;
    }

    async loadPostComments(postId) {
        try {
            const response = await api.getPostComments(postId);
            const comments = response.data.comments;
            
            const container = document.getElementById('commentsSection');
            container.innerHTML = '';
            
            comments.forEach(comment => {
                const commentElement = this.createCommentElement(comment);
                container.appendChild(commentElement);
            });
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }

    createCommentElement(comment) {
        const div = document.createElement('div');
        div.className = 'comment';
        div.innerHTML = `
            <div>
                <span class="comment-username">${comment.username}</span>
                ${comment.content}
            </div>
            <div class="comment-actions">
                <span class="comment-time">${this.formatTime(comment.created_at)}</span>
                ${comment.likes_count > 0 ? `<span>${comment.likes_count} likes</span>` : ''}
                <button class="comment-action">Reply</button>
            </div>
        `;
        return div;
    }

    formatCaption(caption) {
        // Simple hashtag and mention formatting
        return caption
            .replace(/#(\w+)/g, '<span style="color: #0095f6;">#$1</span>')
            .replace(/@(\w+)/g, '<span style="color: #0095f6;">@$1</span>');
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);
        
        if (minutes < 1) return 'now';
        if (minutes < 60) return `${minutes}m`;
        if (hours < 24) return `${hours}h`;
        if (days < 7) return `${days}d`;
        
        return date.toLocaleDateString();
    }

    showError(message) {
        console.error(message);
        // In production, use a proper notification system
    }
}

// Create global feed instance
window.feed = new Feed();