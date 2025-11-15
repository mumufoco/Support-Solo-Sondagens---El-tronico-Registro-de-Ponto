#!/bin/bash

###############################################################################
# Deployment Verification Script
#
# Verifies that deployment was successful
# Usage: ./scripts/verify-deployment.sh [environment]
#   environment: staging|production (default: production)
###############################################################################

set -e

ENVIRONMENT=${1:-production}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration based on environment
if [ "$ENVIRONMENT" = "staging" ]; then
    BASE_URL="https://staging.pontoeletronico.com.br"
    DB_NAME="ponto_eletronico_staging"
elif [ "$ENVIRONMENT" = "production" ]; then
    BASE_URL="https://pontoeletronico.com.br"
    DB_NAME="ponto_eletronico"
else
    echo -e "${RED}Error: Invalid environment '$ENVIRONMENT'${NC}"
    echo "Usage: $0 [staging|production]"
    exit 1
fi

# Counters
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
    ((TESTS_PASSED++))
}

print_error() {
    echo -e "${RED}✗${NC} $1"
    ((TESTS_FAILED++))
}

print_info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

###############################################################################
# Verify Web Application
###############################################################################

print_header "Verifying Web Application - $ENVIRONMENT"

print_info "Base URL: $BASE_URL"

# Check homepage
print_info "Testing homepage..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "302" ]; then
    print_success "Homepage is accessible (HTTP $HTTP_CODE)"
else
    print_error "Homepage is not accessible (HTTP $HTTP_CODE)"
fi

# Check login page
print_info "Testing login page..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/login" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    print_success "Login page is accessible (HTTP $HTTP_CODE)"
else
    print_error "Login page is not accessible (HTTP $HTTP_CODE)"
fi

# Check health endpoint
print_info "Testing health endpoint..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/health" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    print_success "Health endpoint is accessible (HTTP $HTTP_CODE)"
else
    print_error "Health endpoint is not accessible (HTTP $HTTP_CODE)"
fi

###############################################################################
# Verify SSL/HTTPS
###############################################################################

print_header "Verifying SSL/HTTPS"

# Check SSL certificate
print_info "Checking SSL certificate..."
if curl -s -I "$BASE_URL" | grep -q "HTTP/.*200\|HTTP/.*302"; then
    print_success "SSL certificate is valid"
else
    print_error "SSL certificate check failed"
fi

# Check HTTPS redirect
print_info "Checking HTTP to HTTPS redirect..."
HTTP_REDIRECT=$(curl -s -o /dev/null -w "%{http_code}" "http://$(echo $BASE_URL | sed 's/https:\/\///')" 2>/dev/null || echo "000")

if [ "$HTTP_REDIRECT" = "301" ] || [ "$HTTP_REDIRECT" = "302" ]; then
    print_success "HTTP to HTTPS redirect is working (HTTP $HTTP_REDIRECT)"
else
    print_warning "HTTP to HTTPS redirect may not be configured (HTTP $HTTP_REDIRECT)"
fi

###############################################################################
# Verify API Endpoints
###############################################################################

print_header "Verifying API Endpoints"

# Check API health
print_info "Testing API health endpoint..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/health" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    print_success "API health endpoint is accessible (HTTP $HTTP_CODE)"
else
    print_warning "API health endpoint may not be accessible (HTTP $HTTP_CODE)"
fi

# Check API authentication
print_info "Testing API authentication..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/api/employee/profile" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "401" ]; then
    print_success "API authentication is working (HTTP $HTTP_CODE)"
else
    print_warning "API authentication response unexpected (HTTP $HTTP_CODE)"
fi

###############################################################################
# Verify Static Assets
###############################################################################

print_header "Verifying Static Assets"

# Check CSS
print_info "Testing CSS assets..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/assets/css/style.css" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "304" ]; then
    print_success "CSS assets are accessible (HTTP $HTTP_CODE)"
else
    print_warning "CSS assets may not be accessible (HTTP $HTTP_CODE)"
fi

# Check JavaScript
print_info "Testing JavaScript assets..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$BASE_URL/assets/js/app.js" 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "304" ]; then
    print_success "JavaScript assets are accessible (HTTP $HTTP_CODE)"
else
    print_warning "JavaScript assets may not be accessible (HTTP $HTTP_CODE)"
fi

###############################################################################
# Verify Database
###############################################################################

print_header "Verifying Database"

# Check database connection (requires SSH access)
print_info "Checking database migrations..."
if docker-compose exec -T php php spark migrate:status 2>/dev/null | grep -q "complete"; then
    print_success "Database migrations are up to date"
else
    print_warning "Unable to verify database migrations"
fi

###############################################################################
# Verify Services
###############################################################################

print_header "Verifying Services"

# Check Docker services
SERVICES=("mysql" "redis" "php" "nginx" "deepface")

for service in "${SERVICES[@]}"; do
    print_info "Checking $service service..."
    if docker-compose ps | grep -q "$service.*Up"; then
        print_success "Service '$service' is running"
    else
        print_error "Service '$service' is not running"
    fi
done

###############################################################################
# Verify Security Headers
###############################################################################

print_header "Verifying Security Headers"

# Check security headers
RESPONSE=$(curl -s -I "$BASE_URL" 2>/dev/null)

if echo "$RESPONSE" | grep -qi "X-Frame-Options"; then
    print_success "X-Frame-Options header is present"
else
    print_warning "X-Frame-Options header is missing"
fi

if echo "$RESPONSE" | grep -qi "X-Content-Type-Options"; then
    print_success "X-Content-Type-Options header is present"
else
    print_warning "X-Content-Type-Options header is missing"
fi

if echo "$RESPONSE" | grep -qi "X-XSS-Protection"; then
    print_success "X-XSS-Protection header is present"
else
    print_warning "X-XSS-Protection header is missing"
fi

###############################################################################
# Verify Performance
###############################################################################

print_header "Verifying Performance"

# Check page load time
print_info "Measuring page load time..."
LOAD_TIME=$(curl -s -o /dev/null -w "%{time_total}" "$BASE_URL" 2>/dev/null || echo "0")

if [ "$(echo "$LOAD_TIME < 2.0" | bc)" -eq 1 ]; then
    print_success "Page load time: ${LOAD_TIME}s (good)"
elif [ "$(echo "$LOAD_TIME < 5.0" | bc)" -eq 1 ]; then
    print_warning "Page load time: ${LOAD_TIME}s (acceptable)"
else
    print_error "Page load time: ${LOAD_TIME}s (slow)"
fi

###############################################################################
# Summary
###############################################################################

print_header "Deployment Verification Summary - $ENVIRONMENT"

TOTAL_TESTS=$((TESTS_PASSED + TESTS_FAILED))

echo "Total tests: $TOTAL_TESTS"
print_success "Passed: $TESTS_PASSED"

if [ "$TESTS_FAILED" -gt 0 ]; then
    print_error "Failed: $TESTS_FAILED"
    echo ""
    print_error "Deployment verification failed! Please investigate the errors above."
    exit 1
else
    echo ""
    print_success "All verification tests passed! Deployment successful."

    # Print deployment info
    echo ""
    echo -e "${BLUE}Deployment Information:${NC}"
    echo "  Environment: $ENVIRONMENT"
    echo "  Base URL: $BASE_URL"
    echo "  Timestamp: $(date '+%Y-%m-%d %H:%M:%S')"

    exit 0
fi
