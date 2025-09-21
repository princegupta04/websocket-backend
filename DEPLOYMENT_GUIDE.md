# 🚀 Laravel WebSocket Chat Backend - Deployment Guide

## Repository
✅ **Successfully pushed to**: https://github.com/princegupta04/websocket-backend.git

## What's Included in the Repository

### Core Application Files
- ✅ **Laravel 12 Framework** - Latest version with all features
- ✅ **Authentication System** - Sanctum API token auth
- ✅ **WebSocket Server** - Real-time chat with ReactPHP
- ✅ **Database Migrations** - SQLite with all necessary tables
- ✅ **API Controllers** - Complete REST API for chat
- ✅ **Configuration Files** - Production-ready configs

### Excluded Files (via .gitignore)
- ❌ `.env` - Environment variables (security)
- ❌ `/vendor` - Dependencies (installed via composer)
- ❌ `/node_modules` - Node dependencies
- ❌ `/storage/logs` - Log files
- ❌ Test files and development tools

## Deployment Steps

### 1. Clone Repository
```bash
git clone https://github.com/princegupta04/websocket-backend.git
cd websocket-backend
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
```

### 3. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
```bash
php artisan migrate --force
php artisan db:seed  # Optional: Create test users
```

### 5. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 6. Start Services
```bash
# Laravel API Server
php artisan serve --host=0.0.0.0 --port=8000

# WebSocket Server (separate terminal)
php artisan websocket:serve --port=8080
```

## Production Deployment

### Environment Variables (.env)
```bash
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/production/database.sqlite

# Logging
LOG_CHANNEL=daily
LOG_LEVEL=error

# CORS (adjust for your frontend domain)
CORS_ALLOWED_ORIGINS=https://your-frontend-domain.com
```

### Web Server Configuration

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/websocket-backend/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
    }
}

# WebSocket Proxy
server {
    listen 8080;
    server_name your-domain.com;
    
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
    }
}
```

#### Supervisor Configuration (for WebSocket server)
```ini
[program:websocket-chat]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/websocket-backend/artisan websocket:serve --port=8080
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/websocket-backend/storage/logs/websocket.log
```

### SSL/TLS Configuration
For production, configure SSL for both HTTP API and WebSocket connections:
- **API**: `https://your-domain.com/api`
- **WebSocket**: `wss://your-domain.com:8080`

## API Endpoints

### Base URL
- **Development**: `http://localhost:8000/api`
- **Production**: `https://your-domain.com/api`

### Available Endpoints
```
POST /api/register        - User registration
POST /api/login          - User login
POST /api/logout         - User logout (auth required)
GET  /api/user           - Get current user (auth required)
GET  /api/messages       - Get messages (auth required)
POST /api/messages       - Send message (auth required)
GET  /api/websocket/info - WebSocket connection info (auth required)
GET  /api/test           - Health check
```

## WebSocket Connection

### Development
```javascript
const ws = new WebSocket('ws://localhost:8080');
```

### Production
```javascript
const ws = new WebSocket('wss://your-domain.com:8080');
```

## Frontend Integration

Your React frontend should use these configurations:

```javascript
// API Base URL
const API_BASE_URL = process.env.NODE_ENV === 'production' 
  ? 'https://your-domain.com/api'
  : 'http://localhost:8000/api';

// WebSocket URL
const WS_URL = process.env.NODE_ENV === 'production'
  ? 'wss://your-domain.com:8080'
  : 'ws://localhost:8080';
```

## Monitoring & Logs

### View Logs
```bash
# Application logs
tail -f storage/logs/laravel.log

# WebSocket logs (if using Supervisor)
tail -f storage/logs/websocket.log
```

### Health Checks
```bash
# API Health
curl https://your-domain.com/api/test

# WebSocket Health
# Use WebSocket test client or browser console
```

## Security Considerations

1. **Environment Variables**: Never commit `.env` files
2. **CORS**: Configure allowed origins properly
3. **Rate Limiting**: Consider adding API rate limits
4. **SSL**: Always use HTTPS/WSS in production
5. **Database**: Use MySQL/PostgreSQL for production
6. **Firewall**: Secure server ports appropriately

## Support

For issues or questions:
- **Repository**: https://github.com/princegupta04/websocket-backend
- **Documentation**: Check README.md in repository

---

**🎉 Your Laravel WebSocket Chat Backend is ready for deployment!**
