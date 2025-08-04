// Main application controller
class App {
    constructor() {
        this.currentView = 'feed';
        this.initializeEventListeners();
        this.initialize();
    }

    initializeEventListeners() {
        // Navigation
        document.getElementById('homeBtn').addEventListener('click', () => {
            this.showView('feed');
        });

        document.getElementById('exploreBtn').addEventListener('click', () => {
            this.showView('explore');
        });

        // Modal close listeners
        document.getElementById('closePostModal').addEventListener('click', () => {
            this.closeModal('postModal');
        });

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target.id);
            }
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.handleSearch(e.target.value);
            }, 300);
        });

        // Infinite scroll for feed
        window.addEventListener('scroll', () => {
            if (this.currentView === 'feed') {
                this.handleFeedScroll();
            }
        });
    }

    async initialize() {
        try {
            // Check if user is already authenticated
            const isAuthenticated = await window.auth.checkAuthStatus();
            
            if (isAuthenticated) {
                this.showMainApp();
            }
        } catch (error) {
            console.error('Initialization error:', error);
            window.auth.showAuthForms();
        }
    }

    showMainApp() {
        // Load initial data
        this.showView('feed');
        window.feed.loadFeed();
    }

    showView(viewName) {
        // Hide all views
        document.querySelectorAll('.view').forEach(view => {
            view.classList.remove('active');
        });

        // Show selected view
        const targetView = document.getElementById(`${viewName}-view`);
        if (targetView) {
            targetView.classList.add('active');
            this.currentView = viewName;

            // Load view-specific data
            switch (viewName) {
                case 'feed':
                    if (window.feed && window.feed.posts.length === 0) {
                        window.feed.loadFeed();
                    }
                    break;
                case 'explore':
                    window.feed.loadExplorePosts();
                    break;
                case 'profile':
                    // Profile loading is handled by the profile module
                    break;
            }
        }

        // Update navigation active state
        this.updateNavigationState(viewName);
    }

    updateNavigationState(viewName) {
        document.querySelectorAll('.nav-icon').forEach(icon => {
            icon.classList.remove('active');
        });

        const activeButton = document.getElementById(`${viewName}Btn`) || 
                           (viewName === 'profile' ? document.getElementById('profileBtn') : null);
        
        if (activeButton) {
            activeButton.classList.add('active');
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
        }
    }

    async handleSearch(query) {
        if (!query.trim()) {
            // Hide search results
            return;
        }

        try {
            const response = await api.searchUsers(query.trim(), 10);
            this.showSearchResults(response.data.users);
        } catch (error) {
            console.error('Search failed:', error);
        }
    }

    showSearchResults(users) {
        // Simple search results display
        // In production, implement a proper dropdown with better styling
        let dropdown = document.querySelector('.search-results');
        
        if (!dropdown) {
            dropdown = document.createElement('div');
            dropdown.className = 'search-results';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #dbdbdb;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                max-height: 300px;
                overflow-y: auto;
                z-index: 1000;
            `;
            document.querySelector('.nav-search').style.position = 'relative';
            document.querySelector('.nav-search').appendChild(dropdown);
        }

        if (users.length === 0) {
            dropdown.innerHTML = '<div style="padding: 16px; color: #8e8e8e;">No users found</div>';
            return;
        }

        dropdown.innerHTML = users.map(user => `
            <div class="search-result-item" data-user-id="${user.id}" style="
                display: flex;
                align-items: center;
                padding: 12px 16px;
                cursor: pointer;
                border-bottom: 1px solid #efefef;
            ">
                <img src="uploads/${user.profile_picture}" alt="${user.username}" style="
                    width: 32px;
                    height: 32px;
                    border-radius: 50%;
                    margin-right: 12px;
                    object-fit: cover;
                ">
                <div>
                    <div style="font-weight: 600; font-size: 14px;">${user.username}</div>
                    <div style="color: #8e8e8e; font-size: 12px;">${user.full_name}</div>
                </div>
                ${user.is_verified ? '<i class="fas fa-check-circle" style="color: #0095f6; margin-left: auto;"></i>' : ''}
            </div>
        `).join('');

        // Add click listeners
        dropdown.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                const userId = parseInt(item.dataset.userId);
                window.profile.showUserProfile(userId);
                dropdown.remove();
                document.getElementById('searchInput').value = '';
            });
        });

        // Remove dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-search')) {
                dropdown.remove();
            }
        }, { once: true });
    }

    handleFeedScroll() {
        if (this.currentView !== 'feed') return;

        const scrollPosition = window.innerHeight + window.scrollY;
        const documentHeight = document.documentElement.offsetHeight;

        // Load more posts when near bottom
        if (scrollPosition >= documentHeight - 1000) {
            if (window.feed && !window.feed.loading && window.feed.hasMore) {
                window.feed.loadFeed();
            }
        }
    }

    showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'error' ? '#ed4956' : '#0095f6'};
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            z-index: 3000;
            animation: slideIn 0.3s ease;
        `;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new App();
});

// Add CSS animation for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    .nav-icon.active {
        background-color: #f5f5f5;
    }
`;
document.head.appendChild(style);