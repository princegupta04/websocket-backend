#!/bin/bash

# Laravel WebSocket Backend Deployment Verification Script
# This script verifies that the deployment is working correctly

echo "🚀 Laravel WebSocket Backend Deployment Verification"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Please run this script from the Laravel project root directory${NC}"
    exit 1
fi

echo -e "\n${BLUE}📋 Environment Check${NC}"
echo "==================="

# Check PHP version
PHP_VERSION=$(php -v | head -n1)
echo -e "✅ PHP: ${GREEN}$PHP_VERSION${NC}"

# Check Composer
COMPOSER_VERSION=$(composer --version 2>/dev/null || echo "Not installed")
echo -e "✅ Composer: ${GREEN}$COMPOSER_VERSION${NC}"

# Check if .env exists
if [ -f ".env" ]; then
    echo -e "✅ Environment file: ${GREEN}.env exists${NC}"
else
    echo -e "❌ Environment file: ${RED}.env missing${NC}"
    exit 1
fi

# Check if vendor directory exists
if [ -d "vendor" ]; then
    echo -e "✅ Dependencies: ${GREEN}vendor directory exists${NC}"
else
    echo -e "❌ Dependencies: ${RED}vendor directory missing - run 'composer install'${NC}"
    exit 1
fi

# Check database
echo -e "\n${BLUE}🗄️  Database Check${NC}"
echo "=================="

if [ -f "database/database.sqlite" ]; then
    echo -e "✅ Database file: ${GREEN}database.sqlite exists${NC}"
else
    echo -e "❌ Database file: ${RED}database.sqlite missing${NC}"
    exit 1
fi

# Check storage permissions
echo -e "\n${BLUE}📁 Storage Permissions${NC}"
echo "====================="

if [ -w "storage" ]; then
    echo -e "✅ Storage: ${GREEN}writable${NC}"
else
    echo -e "❌ Storage: ${RED}not writable - run 'chmod -R 775 storage'${NC}"
    exit 1
fi

if [ -w "bootstrap/cache" ]; then
    echo -e "✅ Bootstrap cache: ${GREEN}writable${NC}"
else
    echo -e "❌ Bootstrap cache: ${RED}not writable - run 'chmod -R 775 bootstrap/cache'${NC}"
    exit 1
fi

echo -e "\n${BLUE}🌐 API Endpoints Test${NC}"
echo "====================="

# Test the public API endpoint
echo "Testing public API endpoint..."
API_RESPONSE=$(curl -s http://localhost:8000/api/test 2>/dev/null || echo "FAILED")

if [[ $API_RESPONSE == *"API is working"* ]]; then
    echo -e "✅ API Health: ${GREEN}Working${NC}"
else
    echo -e "❌ API Health: ${RED}Failed - make sure Laravel server is running on port 8000${NC}"
    echo -e "   ${YELLOW}Start with: php artisan serve --host=0.0.0.0 --port=8000${NC}"
fi

echo -e "\n${BLUE}🔧 WebSocket Server Check${NC}"
echo "========================="

# Check if WebSocket server is running
WS_CHECK=$(netstat -tlnp 2>/dev/null | grep ":8080" || echo "NOT_RUNNING")

if [[ $WS_CHECK == *"8080"* ]]; then
    echo -e "✅ WebSocket Server: ${GREEN}Running on port 8080${NC}"
else
    echo -e "❌ WebSocket Server: ${RED}Not running on port 8080${NC}"
    echo -e "   ${YELLOW}Start with: php artisan websocket:serve --port=8080${NC}"
fi

echo -e "\n${BLUE}📚 Available Commands${NC}"
echo "===================="
echo -e "• ${GREEN}php artisan serve --host=0.0.0.0 --port=8000${NC} - Start API server"
echo -e "• ${GREEN}php artisan websocket:serve --port=8080${NC} - Start WebSocket server"
echo -e "• ${GREEN}php artisan websocket:test${NC} - Test WebSocket connectivity"
echo -e "• ${GREEN}php artisan websocket:client${NC} - Test WebSocket client"

echo -e "\n${BLUE}🔗 Endpoints${NC}"
echo "============"
echo -e "• API Base URL: ${GREEN}http://localhost:8000/api${NC}"
echo -e "• WebSocket URL: ${GREEN}ws://localhost:8080${NC}"
echo -e "• Health Check: ${GREEN}http://localhost:8000/api/test${NC}"
echo -e "• Registration: ${GREEN}POST http://localhost:8000/api/register${NC}"
echo -e "• Login: ${GREEN}POST http://localhost:8000/api/login${NC}"

echo -e "\n${GREEN}🎉 Deployment verification complete!${NC}"

# Show running processes
echo -e "\n${BLUE}🏃 Running Processes${NC}"
echo "==================="
ps aux | grep -E "(artisan serve|websocket:serve)" | grep -v grep || echo "No Laravel/WebSocket processes running"

echo -e "\n${YELLOW}💡 Tip: Keep both servers running in separate terminals for full functionality${NC}"