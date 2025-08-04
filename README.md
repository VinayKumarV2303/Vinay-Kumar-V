# Instagram Clone

A fully functional Instagram clone built with PHP and modern web technologies. This application replicates the core features of Instagram including user authentication, photo sharing, social interactions, and real-time feed updates.

## Features

### 🔐 Authentication
- User registration and login
- Session management
- Profile management
- Password security with hashing

### 📸 Photo Sharing
- Image upload with drag & drop support
- Image processing and optimization
- Automatic thumbnail generation
- Caption and location support
- Hashtag parsing

### 👥 Social Features
- Follow/unfollow users
- User profiles with stats
- User search functionality
- Profile picture upload

### 💬 Interactions
- Like/unlike posts
- Comment system with replies
- Comment likes
- Real-time interaction counts

### 🏠 Feed & Discovery
- Personalized feed showing posts from followed users
- Explore page with trending posts
- Infinite scroll for seamless browsing
- Post modal for detailed view

### 📱 Modern UI
- Responsive design that works on all devices
- Instagram-like user interface
- Smooth animations and transitions
- Modal-based interactions

## Technology Stack

### Backend
- **PHP 8.0+** - Server-side programming
- **MySQL** - Database management
- **PDO** - Database abstraction layer
- **Composer** - Dependency management

### Frontend
- **HTML5** - Markup structure
- **CSS3** - Modern styling with Flexbox/Grid
- **Vanilla JavaScript** - Dynamic interactions
- **Font Awesome** - Icon library
- **Google Fonts** - Typography

### Libraries & Tools
- **Intervention Image** - Image processing
- **phpdotenv** - Environment configuration
- **Custom Router** - Clean URL routing
- **RESTful API** - Clean API architecture

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd instagram-clone
```

### Step 2: Install Dependencies
```bash
composer install
```

### Step 3: Database Setup
1. Create a MySQL database named `instagram_clone`
2. Import the database schema:
```bash
mysql -u your_username -p instagram_clone < database/schema.sql
```

### Step 4: Environment Configuration
1. Copy the environment file:
```bash
cp .env.example .env
```

2. Update the `.env` file with your database credentials:
```env
DB_HOST=localhost
DB_NAME=instagram_clone
DB_USER=your_username
DB_PASS=your_password
DB_PORT=3306

APP_NAME="Instagram Clone"
APP_URL=http://localhost:8000
APP_DEBUG=true

UPLOAD_PATH=uploads/
MAX_FILE_SIZE=5242880
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif
```

### Step 5: Set Up Upload Directory
```bash
mkdir -p public/uploads
chmod 755 public/uploads
```

### Step 6: Start the Application
Using PHP built-in server:
```bash
composer start
# or
php -S localhost:8000 -t public/
```

Visit `http://localhost:8000` in your browser.

## API Endpoints

### Authentication
- `POST /api/auth/register` - User registration
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user
- `PUT /api/auth/profile` - Update profile

### Users
- `GET /api/users/{id}` - Get user profile
- `GET /api/users/{id}/posts` - Get user posts
- `POST /api/users/{id}/follow` - Follow user
- `DELETE /api/users/{id}/follow` - Unfollow user
- `GET /api/users/search` - Search users
- `POST /api/users/upload-avatar` - Upload profile picture

### Posts
- `POST /api/posts` - Create new post
- `GET /api/posts/{id}` - Get specific post
- `PUT /api/posts/{id}` - Update post
- `DELETE /api/posts/{id}` - Delete post
- `GET /api/posts` - Get feed
- `GET /api/explore` - Get explore posts

### Interactions
- `POST /api/posts/{id}/like` - Like post
- `DELETE /api/posts/{id}/like` - Unlike post
- `GET /api/posts/{id}/likes` - Get post likes
- `POST /api/posts/{id}/comments` - Create comment
- `GET /api/posts/{id}/comments` - Get post comments
- `PUT /api/comments/{id}` - Update comment
- `DELETE /api/comments/{id}` - Delete comment
- `POST /api/comments/{id}/like` - Like comment
- `DELETE /api/comments/{id}/like` - Unlike comment

## Project Structure

```
instagram-clone/
├── public/                 # Web root directory
│   ├── css/               # Stylesheets
│   ├── js/                # JavaScript files
│   ├── uploads/           # Uploaded images
│   ├── api/              # API entry point
│   └── index.php         # Main entry point
├── src/                   # PHP source code
│   ├── Config/           # Configuration classes
│   ├── Controllers/      # API controllers
│   ├── Models/           # Data models
│   ├── Router/           # Routing system
│   └── Utils/            # Utility classes
├── database/             # Database files
│   └── schema.sql        # Database schema
├── vendor/               # Composer dependencies
├── .env                  # Environment configuration
├── .htaccess            # Apache rewrite rules
├── composer.json        # PHP dependencies
└── README.md           # Project documentation
```

## Database Schema

The application uses a well-structured MySQL database with the following main tables:

- **users** - User accounts and profiles
- **posts** - Photo posts with metadata
- **followers** - User follow relationships
- **likes** - Post likes
- **comments** - Post comments and replies
- **comment_likes** - Comment likes
- **stories** - User stories (future feature)
- **notifications** - User notifications
- **hashtags** - Hashtag system
- **user_sessions** - Session management

## Security Features

- **Password Hashing** - Secure password storage using PHP's password_hash()
- **SQL Injection Protection** - All queries use prepared statements
- **XSS Prevention** - Input sanitization and output escaping
- **CSRF Protection** - Session-based request validation
- **File Upload Security** - File type and size validation
- **Input Validation** - Comprehensive server-side validation

## Performance Optimizations

- **Image Optimization** - Automatic image compression and resizing
- **Database Indexing** - Optimized database queries with proper indexes
- **Lazy Loading** - Infinite scroll with pagination
- **Thumbnail Generation** - Faster loading with smaller image previews
- **Efficient Queries** - Optimized SQL with JOINs and proper relationships

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## Development

### Adding New Features
1. Create model classes in `src/Models/`
2. Add controller methods in `src/Controllers/`
3. Update routing in `src/Router/Router.php`
4. Add frontend JavaScript in `public/js/`
5. Update CSS styles in `public/css/`

### Testing
- Test all API endpoints using tools like Postman
- Verify responsive design on different screen sizes
- Test file upload functionality
- Validate form inputs and error handling

## Known Issues & Future Enhancements

### Planned Features
- [ ] Stories functionality
- [ ] Direct messaging
- [ ] Push notifications
- [ ] Video upload support
- [ ] Advanced search filters
- [ ] Email verification
- [ ] Two-factor authentication
- [ ] Admin panel
- [ ] Analytics dashboard

### Known Limitations
- No real-time notifications (requires WebSocket implementation)
- Limited file format support (images only)
- No video processing capabilities
- Basic search functionality

## License

This project is open source and available under the [MIT License](LICENSE).

## Support

If you encounter any issues or have questions:

1. Check the existing documentation
2. Search through existing issues
3. Create a new issue with detailed information
4. Include error messages and steps to reproduce

## Credits

Built with ❤️ using modern web technologies. Inspired by Instagram's user interface and functionality.

---

**Note**: This is a educational project and is not affiliated with or endorsed by Instagram/Meta.