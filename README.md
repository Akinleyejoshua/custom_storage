# PHP Media Storage Gateway

A PHP-based cloud storage gateway for media assets (images and videos) that works on traditional PHP hosting with persistent file storage.

## Features

- **Persistent file storage** - Files remain between deployments
- **MongoDB metadata storage** - Track file information
- **Streaming support** - HTTP 206 partial content for video seeking
- **Clean frontend** - Tailwind CSS UI with gallery and upload pages
- **API endpoints** - RESTful interface for file management
- **Documentation** - Comprehensive API docs

## Requirements

- PHP 7.4 or higher
- MongoDB extension for PHP (`pecl install mongodb`)
- Web server (Apache, Nginx, etc.)
- MongoDB database

## Installation

1. **Upload files**
   Upload the contents of the `php-version` directory to your web hosting.

2. **Configure database**
   Edit `config/database.php` with your MongoDB connection details:
   ```php
   return [
       'host' => 'localhost',
       'port' => '27017',
       'database' => 'media_gateway',
       'username' => '', // Optional
       'password' => ''  // Optional
   ];
   ```

3. **Set permissions**
   Ensure the `uploads` directory is writable by the web server:
   ```bash
   chmod -R 755 uploads
   ```

4. **Access the application**
   Visit your domain in a web browser.

## File Structure

```
php-version/
├── config/
│   └── database.php      # Database configuration
├── includes/
│   └── api.php           # API endpoint logic
├── public/
│   ├── index.php         # Entry point
│   ├── index.html        # Gallery page
│   ├── upload.html       # Upload page
│   ├── docs.html         # Documentation
│   └── assets.php        # File streaming
├── uploads/              # Persistent file storage
└── README.md             # This file
```

## API Endpoints

| Method | Endpoint               | Description                     |
|--------|------------------------|---------------------------------|
| POST   | `/api.php/upload`      | Upload a file                   |
| GET    | `/api.php`             | List assets (paginated)         |
| GET    | `/api.php/{assetId}`   | Get asset metadata              |
| DELETE | `/api.php/{assetId}`   | Delete asset                    |
| GET    | `/assets.php/{file}`   | Stream/download file            |

## Usage

1. **Upload files** - Go to `/upload` and drag/drop files
2. **View gallery** - Go to `/` to see all uploaded assets
3. **View docs** - Go to `/docs` for API documentation
4. **Access files** - Files are served from `/assets.php/{filename}`

## Notes

- Files are stored in the `uploads` directory and persist between deployments
- MongoDB is used to store file metadata (filename, size, MIME type, etc.)
- The frontend uses Tailwind CSS via CDN (no build step required)
- Maximum file size is 500MB (configurable in `config/database.php`)

## Troubleshooting

- **File uploads failing**: Check that the `uploads` directory is writable
- **Database connection errors**: Verify MongoDB credentials in `config/database.php`
- **404 errors**: Ensure your web server is configured to route all requests to `index.php`
- **CORS issues**: The API includes CORS headers for cross-origin requests