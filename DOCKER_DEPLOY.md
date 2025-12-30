# Docker Configuration for Dokploy Deployment

## Changes Made

### 1. **Entrypoint Script** (`docker-entrypoint.sh`)

Created a bash script that runs when the container starts. This script:

-   Waits for the database to be ready
-   Runs Laravel migrations
-   Clears and caches Laravel configurations **after** environment variables are available
-   Creates storage links
-   Sets proper permissions
-   Starts supervisor (nginx + php-fpm)

### 2. **Dockerfile Updates**

-   Added entrypoint script to **base stage** (inherited by production)
-   Made script executable with `chmod +x`
-   Production stage uses `CMD` to execute the entrypoint script

### 3. **Docker Compose Updates**

-   Uses `env_file: .env` to inject environment variables
-   Dokploy creates and manages the `.env` file automatically
-   No need to explicitly map each environment variable

### 4. **.dockerignore Updates**

-   Added exception `!docker-entrypoint.sh` to ensure the script is copied during build

## Required Environment Variables in Dokploy

You **MUST** configure these environment variables in Dokploy:

### Critical (Application will fail without these)

```bash
APP_KEY=base64:YourGeneratedAppKeyHere
APP_URL=https://your-domain.com
DB_PASSWORD=your-secure-database-password
```

### Important (Have sensible defaults but should be set)

```bash
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=airobot
DB_USERNAME=airobot

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_PASSWORD=null

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
```

## How to Generate APP_KEY

If you don't have an `APP_KEY`, generate one locally:

```bash
# In your local project
php artisan key:generate --show
```

This will output something like:

```
base64:abcd1234efgh5678ijkl9012mnop3456qrst7890uvwx1234yzab5678
```

Copy the entire string (including `base64:`) and set it in Dokploy.

## Deployment Steps

1. **Commit and push these changes** to your repository
2. **In Dokploy dashboard:**
    - Go to your project settings
    - Add/update environment variables (especially `APP_KEY`)
    - Trigger a new deployment
3. **Monitor logs** during deployment to ensure:
    - Database connection succeeds
    - Migrations run successfully
    - Config caching completes

## Why This Fixes the Issue

**The Original Problem:**

-   Dokploy injects environment variables at **runtime** (when container starts)
-   The old setup tried to run Laravel commands during **build time** (or immediately on start)
-   Laravel couldn't find `APP_KEY` because it wasn't available yet

**The Solution:**

-   The entrypoint script runs **after** Dokploy injects environment variables
-   Laravel artisan commands now have access to `APP_KEY` and other env vars
-   Config caching happens at the right time with all variables available

## Troubleshooting

### If container fails to start:

```bash
# Check Dokploy logs for the container
# Look for these success messages:
# ✅ Database is up!
# 📦 Running migrations...
# 🔧 Optimizing application...
# ✨ Application ready! Starting services...
```

### If you see "APP_KEY not specified":

-   Verify APP_KEY is set in Dokploy environment variables
-   Make sure it starts with `base64:`
-   Redeploy after setting it

### If database connection fails:

-   Check `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` are correct
-   Ensure postgres service is healthy
-   Verify network configuration in docker-compose

## Notes

-   **DO NOT** commit `.env` file to the repository
-   **DO** keep APP_KEY secret and secure
-   The entrypoint script automatically runs migrations on each deployment
-   Storage and cache directories are automatically configured with proper permissions
