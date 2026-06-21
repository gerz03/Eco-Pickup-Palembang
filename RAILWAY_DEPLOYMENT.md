# Railway Deployment Guide

This guide will help you deploy the Bank Sampah Application to Railway.

## Prerequisites

1. **Railway Account**: Sign up at [railway.app](https://railway.app)
2. **Git Repository**: Your code pushed to GitHub, GitLab, or Bitbucket
3. **Database**: Railway MySQL plugin
4. **Environment Variables**: Set up in Railway dashboard

## Step 1: Push Code to Git Repository

```bash
git init
git add .
git commit -m "Initial commit for Railway deployment"
git branch -M main
git remote add origin https://github.com/your-username/your-repo.git
git push -u origin main
```

## Step 2: Create Railway Project

1. Go to [railway.app](https://railway.app)
2. Click "New Project"
3. Select "Deploy from GitHub"
4. Connect your GitHub account
5. Select your repository

## Step 3: Add MySQL Database

1. In your Railway project dashboard, click "+ Add Service"
2. Select "MySQL"
3. Accept the default configuration
4. Railway will automatically create `DATABASE_URL` environment variable

## Step 4: Configure Environment Variables

In Railway dashboard, go to Variables and add:

```
DB_HOST=mysql (Railway service name)
DB_USER=root
DB_PASSWORD=${{Mysql.MYSQL_PASSWORD}}
DB_NAME=bank_sampah_palembang

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://your-railway-domain.railway.app/auth/google_callback.php

APP_URL=https://your-railway-domain.railway.app
APP_ENV=production
PORT=8080
```

## Step 5: Database Migration

### Option A: Manual Migration
1. Connect to Railway MySQL from your local machine:
   ```bash
   mysql -h your-railway-host -u root -p${{Mysql.MYSQL_PASSWORD}} bank_sampah_palembang
   ```
2. Import the schema:
   ```bash
   mysql -h your-railway-host -u root -p${{Mysql.MYSQL_PASSWORD}} bank_sampah_palembang < Database_Bank_Sampah.sql
   ```

### Option B: Automatic Migration
Create a deployment script or use Railway's pre-deployment hooks.

## Step 6: Update Google OAuth Settings

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Update Authorized redirect URIs:
   ```
   https://your-railway-domain.railway.app/auth/google_callback.php
   ```

## Step 7: Deploy

1. Railway will automatically build and deploy when you push to main branch
2. Watch the deployment logs in Railway dashboard
3. Once deployed, access your app at the provided URL

## Troubleshooting

### PHP Extensions Not Loaded
- Check that `mysqli` and `pdo_mysql` are enabled in Dockerfile
- Verify with: `php -m`

### Database Connection Error
- Verify `DB_HOST` points to correct MySQL service
- Check `DB_USER` and `DB_PASSWORD` are correct
- Ensure database name matches the imported schema

### Missing Files/Folders
- Check `.gitignore` doesn't exclude necessary files
- Verify `uploads/` and `database/` directories exist

### Build Fails
- Check Dockerfile syntax
- Verify all required files are in repository
- Review Railway build logs

## Important Notes

- Keep `.env` file out of version control (use `.env.example`)
- Never commit sensitive credentials
- Use Railway's variable system for secrets
- Monitor logs regularly: `railway logs`
- Set up automatic deployments for main branch

## Useful Railway Commands

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login
railway login

# Link to project
railway link

# Check status
railway status

# View logs
railway logs

# Deploy
git push origin main  # Automatic deployment
```
