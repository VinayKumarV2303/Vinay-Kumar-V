// Authentication module
class Auth {
    constructor() {
        this.currentUser = null;
        this.initializeEventListeners();
    }

    initializeEventListeners() {
        // Form toggles
        document.getElementById('showRegister').addEventListener('click', (e) => {
            e.preventDefault();
            this.showRegisterForm();
        });

        document.getElementById('showLogin').addEventListener('click', (e) => {
            e.preventDefault();
            this.showLoginForm();
        });

        // Form submissions
        document.getElementById('loginForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });

        document.getElementById('registerForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleRegister();
        });

        // Logout
        document.getElementById('logoutBtn').addEventListener('click', () => {
            this.handleLogout();
        });
    }

    showLoginForm() {
        document.getElementById('login-form').style.display = 'block';
        document.getElementById('register-form').style.display = 'none';
    }

    showRegisterForm() {
        document.getElementById('login-form').style.display = 'none';
        document.getElementById('register-form').style.display = 'block';
    }

    async handleLogin() {
        const login = document.getElementById('loginUsername').value;
        const password = document.getElementById('loginPassword').value;

        if (!login || !password) {
            this.showError('Please fill in all fields');
            return;
        }

        const submitBtn = document.querySelector('#loginForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        try {
            const response = await api.login({ login, password });
            this.currentUser = response.data.user;
            this.showMainApp();
            this.showSuccess('Welcome back!');
        } catch (error) {
            this.showError(error.message || 'Login failed');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Log In';
        }
    }

    async handleRegister() {
        const email = document.getElementById('registerEmail').value;
        const fullName = document.getElementById('registerFullName').value;
        const username = document.getElementById('registerUsername').value;
        const password = document.getElementById('registerPassword').value;

        if (!email || !fullName || !username || !password) {
            this.showError('Please fill in all fields');
            return;
        }

        if (password.length < 6) {
            this.showError('Password must be at least 6 characters');
            return;
        }

        const submitBtn = document.querySelector('#registerForm button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Signing up...';

        try {
            const response = await api.register({
                email,
                full_name: fullName,
                username,
                password
            });
            this.currentUser = response.data.user;
            this.showMainApp();
            this.showSuccess('Welcome to Instagram!');
        } catch (error) {
            this.showError(error.message || 'Registration failed');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Sign Up';
        }
    }

    async handleLogout() {
        try {
            await api.logout();
            this.currentUser = null;
            this.showAuthForms();
            this.showSuccess('Logged out successfully');
        } catch (error) {
            console.error('Logout error:', error);
            // Still log out locally even if server request fails
            this.currentUser = null;
            this.showAuthForms();
        }
    }

    async checkAuthStatus() {
        try {
            const response = await api.getCurrentUser();
            this.currentUser = response.data.user;
            this.showMainApp();
            return true;
        } catch (error) {
            this.showAuthForms();
            return false;
        }
    }

    showAuthForms() {
        document.getElementById('auth-container').style.display = 'flex';
        document.getElementById('navbar').style.display = 'none';
        document.getElementById('main-content').style.display = 'none';
        document.getElementById('loading-screen').style.display = 'none';
    }

    showMainApp() {
        document.getElementById('auth-container').style.display = 'none';
        document.getElementById('navbar').style.display = 'block';
        document.getElementById('main-content').style.display = 'block';
        document.getElementById('loading-screen').style.display = 'none';
        
        // Load feed by default
        if (window.feed) {
            window.feed.loadFeed();
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

    getCurrentUser() {
        return this.currentUser;
    }

    isAuthenticated() {
        return this.currentUser !== null;
    }
}

// Create global auth instance
window.auth = new Auth();