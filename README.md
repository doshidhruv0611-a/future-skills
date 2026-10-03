# Future Skills - Robotics & AI
Env vars: MYSQL_URL (Railway MySQL), ADMIN_PASSWORD (required for admin login).
Tables are created automatically on first visit (database/schema.sql).
## Railway
1. Push to GitHub. 2. Railway > New Project > Deploy from GitHub (Dockerfile auto-detected).
3. Add MySQL in same project. 4. Web service Variables: MYSQL_URL=${{MySQL.MYSQL_URL}}, ADMIN_PASSWORD=<strong>.
5. Settings > Networking > Generate Domain.
## Vercel (optional)
Vercel has no PHP by default; vercel.json uses vercel-php@0.7.4. Enable the MySQL public TCP proxy in Railway and set
MYSQL_URL to its public URL, plus ADMIN_PASSWORD, in Vercel env vars. Admin login uses a signed cookie (no PHP sessions).
## Local
MYSQL_URL=mysql://user:pass@host:3306/db ADMIN_PASSWORD=test php -S localhost:8000
