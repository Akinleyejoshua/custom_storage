# PHP Media Storage Gateway - Troubleshooting

## Common Issues and Solutions

### 1. MongoDB Extension Not Installed

**Error**: `Class "MongoDB\Client" not found` or similar MongoDB errors

**Solution**: Install the MongoDB extension for PHP:

```bash
# Install using PECL
pecl install mongodb

# Add to php.ini
echo "extension=mongodb.so" >> /path/to/php.ini

# Restart your web server
# For Apache:
sudo service apache2 restart
# For Nginx + PHP-FPM:
sudo service php-fpm restart
```

### 2. File Permissions Issues

**Error**: Uploads failing or "Permission denied" errors

**Solution**: Set proper permissions on the uploads directory:

```bash
chmod -R 755 uploads
chown -R www-data:www-data uploads  # Replace www-data with your web server user
```

### 3. Empty JSON Responses

**Error**: `Unexpected end of JSON input` in browser console

**Solution**: This usually indicates a PHP error. Enable error reporting:

1. Edit `includes/api.php` and add at the top:
```php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
```

2. Check your web server error logs:
```bash
# Apache error logs
tail -f /var/log/apache2/error.log

# Nginx error logs
tail -f /var/log/nginx/error.log
```

### 4. MongoDB Connection Issues

**Error**: Connection timeout or authentication errors

**Solution**: Verify your MongoDB URI in `config/database.php`:

```php
define('MONGODB_URI', 'mongodb+srv://username:password@cluster0.example.mongodb.net/?appName=Cluster0');
```

- Make sure your IP is whitelisted in MongoDB Atlas
- Verify username and password are correct
- Check that the database name is correct

### 5. Web Server Configuration

**Error**: 404 errors or API endpoints not working

**Apache Solution**: Ensure `.htaccess` is enabled and working:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]
</IfModule>
```

**Nginx Solution**: Add this to your server configuration:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 6. Testing the Environment

Run these test scripts to diagnose issues:

```bash
# Test PHP environment
php test-php.php

# Test API endpoint
php test-api.php

# Test MongoDB connection
php test-mongo.php
```

### 7. Alternative Database Option

If you're having trouble with MongoDB, I can provide a MySQL version. MySQL is more commonly available on shared hosting. To switch to MySQL:

1. Let me know you want the MySQL version
2. I'll provide the modified files
3. You'll need to:
   - Create a MySQL database
   - Import the schema
   - Update the configuration

## Debugging Steps

1. **Check PHP version**: `php -v`
2. **Check installed extensions**: `php -m`
3. **Check MongoDB extension**: `php -m | grep mongodb`
4. **Check file permissions**: `ls -la uploads/`
5. **Check web server logs**: `tail -f /var/log/apache2/error.log`

## Getting Help

If you're still having issues, please provide:
1. The output from `php test-php.php`
2. Any error messages from your web server logs
3. Your PHP version (`php -v`)
4. Your operating system

This will help me diagnose and fix the issue more effectively.