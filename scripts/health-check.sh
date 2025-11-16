#!/bin/bash

###############################################################################
# Health Check Script
#
# Verifies that all services are running and healthy
# Usage: ./scripts/health-check.sh
###############################################################################

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
CHECKS_PASSED=0
CHECKS_FAILED=0

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((CHECKS_PASSED++))
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    ((CHECKS_FAILED++))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

###############################################################################
# Check Docker Services
###############################################################################

print_header "Checking Docker Services"

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    print_error "Docker is not running"
    exit 1
else
    print_success "Docker is running"
fi

# Check if docker-compose is available
if ! command -v docker-compose &> /dev/null; then
    print_error "docker-compose is not installed"
    exit 1
else
    print_success "docker-compose is installed"
fi

# Check running containers
EXPECTED_CONTAINERS=("mysql" "redis" "php" "nginx" "deepface")

for container in "${EXPECTED_CONTAINERS[@]}"; do
    if docker-compose ps | grep -q "$container.*Up"; then
        print_success "Container '$container' is running"
    else
        print_error "Container '$container' is not running"
    fi
done

###############################################################################
# Check Service Health
###############################################################################

print_header "Checking Service Health"

# Check MySQL
print_info "Checking MySQL..."
if docker-compose exec -T mysql mysqladmin ping -h localhost --silent 2>/dev/null; then
    print_success "MySQL is healthy"
else
    print_error "MySQL health check failed"
fi

# Check Redis
print_info "Checking Redis..."
if docker-compose exec -T redis redis-cli ping 2>/dev/null | grep -q "PONG"; then
    print_success "Redis is healthy"
else
    print_error "Redis health check failed"
fi

# Check PHP-FPM
print_info "Checking PHP-FPM..."
if docker-compose exec -T php php -v > /dev/null 2>&1; then
    print_success "PHP-FPM is healthy"
else
    print_error "PHP-FPM health check failed"
fi

# Check Nginx
print_info "Checking Nginx..."
if docker-compose exec -T nginx nginx -t > /dev/null 2>&1; then
    print_success "Nginx configuration is valid"
else
    print_error "Nginx configuration is invalid"
fi

# Check DeepFace API
print_info "Checking DeepFace API..."
if curl -f -s http://localhost:5000/health > /dev/null 2>&1; then
    print_success "DeepFace API is healthy"
else
    print_warning "DeepFace API health check failed (may be normal if not in use)"
fi

###############################################################################
# Check Application Health
###############################################################################

print_header "Checking Application Health"

# Check web application
print_info "Checking web application..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:80 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    print_success "Web application is accessible (HTTP $HTTP_CODE)"
else
    print_error "Web application is not accessible (HTTP $HTTP_CODE)"
fi

# Check database connection
print_info "Checking database connection..."
if docker-compose exec -T php php spark migrate:status > /dev/null 2>&1; then
    print_success "Database connection is working"
else
    print_error "Database connection failed"
fi

# Check writable directories
print_info "Checking writable directories..."
WRITABLE_DIRS=("writable/cache" "writable/logs" "writable/session" "writable/uploads")

for dir in "${WRITABLE_DIRS[@]}"; do
    if docker-compose exec -T php test -w "$dir" 2>/dev/null; then
        print_success "Directory '$dir' is writable"
    else
        print_error "Directory '$dir' is not writable"
    fi
done

###############################################################################
# Check Resource Usage
###############################################################################

print_header "Checking Resource Usage"

# Check disk space
DISK_USAGE=$(df -h . | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -lt 80 ]; then
    print_success "Disk usage: ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -lt 90 ]; then
    print_warning "Disk usage: ${DISK_USAGE}% (getting high)"
else
    print_error "Disk usage: ${DISK_USAGE}% (critical)"
fi

# Check container resource usage
print_info "Container resource usage:"
docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}" | head -n 10

###############################################################################
# Check Logs for Errors
###############################################################################

print_header "Checking Recent Logs for Errors"

# Check PHP errors
PHP_ERRORS=$(docker-compose logs --tail=100 php 2>/dev/null | grep -i "error\|fatal\|exception" | wc -l)
if [ "$PHP_ERRORS" -eq 0 ]; then
    print_success "No recent PHP errors found"
else
    print_warning "Found $PHP_ERRORS recent PHP errors in logs"
fi

# Check Nginx errors
NGINX_ERRORS=$(docker-compose logs --tail=100 nginx 2>/dev/null | grep -i "error" | wc -l)
if [ "$NGINX_ERRORS" -eq 0 ]; then
    print_success "No recent Nginx errors found"
else
    print_warning "Found $NGINX_ERRORS recent Nginx errors in logs"
fi

###############################################################################
# Summary
###############################################################################

print_header "Health Check Summary"

TOTAL_CHECKS=$((CHECKS_PASSED + CHECKS_FAILED))

echo "Total checks: $TOTAL_CHECKS"
print_success "Passed: $CHECKS_PASSED"

if [ "$CHECKS_FAILED" -gt 0 ]; then
    print_error "Failed: $CHECKS_FAILED"
    echo ""
    print_error "Health check failed! Please investigate the errors above."
    exit 1
else
    echo ""
    print_success "All health checks passed! System is healthy."
    exit 0
fi
