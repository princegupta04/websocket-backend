# Laravel WebSocket Chat Backend

A real-time chat application backend built with Laravel and WebSockets.

## Features

- ✅ **Laravel 12** - Latest version with modern features
- ✅ **Laravel Sanctum** - API token authentication
- ✅ **WebSocket Server** - Real-time messaging with ReactPHP
- ✅ **SQLite Database** - Lightweight and portable
- ✅ **CORS Configured** - Ready for frontend integration
- ✅ **RESTful API** - Complete chat API endpoints

## Quick Start

### 1. Clone and Install
```bash
git clone https://github.com/princegupta04/websocket-backend.git
cd websocket-backend
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup
```bash
php artisan migrate
php artisan db:seed  # Optional: Create test users
```

### 4. Start Servers
```bash
# Terminal 1 - Laravel API Server
php artisan serve --port=8000

# Terminal 2 - WebSocket Server  
php artisan websocket:serve --port=8080
```

## API Endpoints

### Authentication
```
POST /api/register - Create new user
POST /api/login    - User login
POST /api/logout   - User logout (requires auth)
GET  /api/user     - Get current user (requires auth)
```

### Chat
```
GET  /api/messages - Get all messages (requires auth)
POST /api/messages - Send message (requires auth)
GET  /api/websocket/info - Get WebSocket connection info (requires auth)
```

### Health Check
```
GET /api/test - API health check
```

## WebSocket Usage

### Connection
```javascript
// Connect to WebSocket
const ws = new WebSocket('ws://localhost:8080');

// Authenticate after connection
ws.send(JSON.stringify({
  type: 'auth',
  token: 'your-bearer-token'
}));
```

### Send Message
```javascript
ws.send(JSON.stringify({
  type: 'message',
  message: 'Hello World!'
}));
```

### Message Types
- `auth` - Authenticate with bearer token
- `message` - Send chat message
- `typing` - Send typing indicator

## Frontend Integration

### API Configuration
```javascript
// Axios configuration
const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  },
  withCredentials: false
});

// Add token to requests
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});
```

### Authentication Flow
```javascript
// Login
const response = await api.post('/login', {
  email: 'user@example.com',
  password: 'password'
});

// Store token
localStorage.setItem('token', response.data.token);
localStorage.setItem('user', JSON.stringify(response.data.user));
```

## Deployment

### Environment Variables
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite

LOG_CHANNEL=daily
LOG_LEVEL=error

BROADCAST_CONNECTION=null
```

### Production Setup
1. **Web Server**: Configure Nginx/Apache for Laravel
2. **Process Manager**: Use Supervisor for WebSocket server
3. **SSL**: Configure HTTPS and WSS
4. **Database**: Use MySQL/PostgreSQL for production

### Supervisor Configuration
```ini
[program:websocket]
command=php /path/to/project/artisan websocket:serve --port=8080
autostart=true
autorestart=true
user=www-data
```

## Development

### Testing API
```bash
# Test health check
curl http://localhost:8000/api/test

# Test login
curl -X POST http://localhost:8000/api/login 
  -H "Content-Type: application/json" 
  -d '{"email":"test@example.com","password":"password"}'
```

### Logs
```bash
# View Laravel logs
tail -f storage/logs/laravel.log

# Clear caches
php artisan optimize:clear
```

## Technologies Used

- **Laravel 12** - PHP Framework
- **Laravel Sanctum** - Authentication
- **ReactPHP** - WebSocket Server
- **SQLite** - Database
- **Composer** - Dependency Management

## License

Open source - feel free to use and modify!

---

**Author**: Prince Gupta  
**Repository**: https://github.com/princegupta04/websocket-backend

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
