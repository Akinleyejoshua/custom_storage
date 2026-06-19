# Custom Cloud Storage Gateway

![Node.js](https://img.shields.io/badge/Node.js-18%2B-green)
![Express](https://img.shields.io/badge/Express-4.21-black)
![MongoDB](https://img.shields.io/badge/MongoDB-8.9-green)
![License](https://img.shields.io/badge/License-MIT-blue)

A production-grade Node.js cloud storage gateway built for media asset management. Supports image and video uploads, streaming delivery with HTTP 206 partial content, and a clean Tailwind CSS frontend. Includes a PHP variant for traditional hosting environments.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Usage](#usage)
- [API Overview](#api-overview)
- [Database Schema](#database-schema)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [PHP Version](#php-version)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

## Features

- **Media Uploads** — Strict MIME validation for images (JPEG, PNG, GIF, WebP, SVG, BMP, TIFF) and videos (MP4, WebM, OGG, MOV, AVI, MKV)
- **Secure Filenames** — Cryptographically random filenames prevent enumeration and path traversal
- **Streaming Delivery** — HTTP 206 partial content support enables efficient video seeking without full downloads
- **Pagination** — Built-in paginated asset listing with configurable page size
- **Gallery UI** — Responsive gallery with thumbnails, video previews, and copy-to-clipboard URL support
- **Drag & Drop** — Modern upload interface with client-side validation and progress tracking
- **MongoDB Metadata** — Full asset tracking including original filename, MIME type, size, timestamps, and public URL
- **CORS Enabled** — Ready for cross-origin frontend integration
- **Error Handling** — Centralized error middleware with specific handling for file size limits and database errors
- **Environment Config** — All sensitive settings managed via environment variables
- **PHP Alternative** — Includes a PHP + MongoDB variant for shared hosting or traditional LAMP stacks

## Architecture

```
┌─────────────┐     HTTP      ┌──────────────────┐     Mongoose      ┌─────────────┐
│   Browser   │ ───────────▶ │   Express API    │ ────────────────▶ │   MongoDB   │
│ (Frontend)  │ ◀──────────── │   server.js      │ ◀──────────────── │  Database   │
└─────────────┘   JSON/Media  └────────┬─────────┘    ODM           └─────────────┘
                                       │
                              ┌────────┴────────┐
                              │                 │
                    ┌─────────▼──────┐  ┌──────▼─────────┐
                    │  Multer Upload │  │  Static File    │
                    │  Middleware    │  │  Streaming      │
                    │  (Validation,  │  │  (Range, 206)   │
                    │   DiskStorage) │  │                 │
                    └────────────────┘  └────────────────┘
```

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Runtime | Node.js 18+ |
| Framework | Express 4.21 |
| Database | MongoDB 8.x (Mongoose ODM) |
| Upload | Multer 1.4 |
| Frontend | HTML5 + Tailwind CSS (CDN) + Font Awesome |
| Icons | Font Awesome 6.4 |
| PHP Variant | PHP 7.4+ + MongoDB Extension |

## Project Structure

```
custom_storage/
├── src/
│   ├── models/
│   │   └── MediaAsset.js          # Mongoose schema & validation
│   ├── routes/
│   │   ├── assets.js              # CRUD API routes
│   │   └── public.js              # Streaming endpoint with Range support
│   ├── middleware/
│   │   └── upload.js              # Multer config, MIME filter, size limits
│   ├── utils/
│   │   └── helpers.js             # MIME allowlist, secure filename gen, formatters
│   └── server.js                  # Express entry, middleware, error handling
├── public/
│   ├── index.html                 # Gallery page
│   ├── upload.html                # Upload page with drag & drop
│   └── docs.html                  # Interactive API docs (SPA)
├── uploads/                       # File storage directory
├── .env                           # Environment variables (gitignored)
├── .env.example                   # Environment template
├── .gitignore
├── package.json
├── package-lock.json
├── README.md                      # This file
├── API_DOCS.md                    # Advanced API reference
└── php-version/                   # Alternative PHP implementation
    ├── README.md
    └── README-PHP.md              # PHP troubleshooting guide
```

## Quick Start

### Prerequisites

- Node.js 18 or higher
- MongoDB (local or Atlas)
- npm or yarn

### Installation

```bash
# Clone or navigate to the project
cd custom_storage

# Install dependencies
npm install
```

### Configuration

Copy the example environment file and update values:

```bash
cp .env.example .env
```

Minimum required configuration:

```env
PORT=3000
MONGODB_URI=mongodb://localhost:27017/media-gateway
UPLOAD_DIR=uploads
BASE_URL=http://localhost:3000
MAX_FILE_SIZE=524288000
```

### Running

```bash
# Development (auto-restart with --watch)
npm run dev

# Production
npm start
```

The server will start on `http://localhost:3000` (or your configured `PORT`).

### Verify

Open in browser:
- Gallery: `http://localhost:3000/`
- Upload: `http://localhost:3000/upload`
- Docs: `http://localhost:3000/docs`

## Configuration

All settings are managed via environment variables in `.env`:

| Variable | Default | Description |
|----------|---------|-------------|
| `PORT` | `3000` | Server port |
| `MONGODB_URI` | `mongodb://localhost:27017/media-gateway` | MongoDB connection string (supports SRV) |
| `UPLOAD_DIR` | `uploads` | Directory for storing uploaded files |
| `BASE_URL` | `http://localhost:3000` | Public base URL for generating asset URLs |
| `MAX_FILE_SIZE` | `524288000` (500MB) | Maximum upload size in bytes |
| `NODE_ENV` | `development` | Environment mode |

### MongoDB Atlas

For cloud MongoDB, use the SRV format:

```env
MONGODB_URI=mongodb+srv://username:password@cluster0.mongodb.net/media-gateway?retryWrites=true&w=majority
```

## Usage

### Upload Media

Navigate to `/upload` or use the API:

```bash
curl -X POST -F "file=@/path/to/image.jpg" http://localhost:3000/api/assets/upload
```

### View Gallery

Navigate to `/` to see all uploaded assets with pagination.

### Copy URLs

Click any asset in the gallery to copy its public URL to clipboard.

### Stream Videos

Videos are served with HTTP Range support, enabling native browser seeking:

```html
<video src="http://localhost:3000/public/assets/abc123.mp4" controls></video>
```

## API Overview

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/assets/upload` | Upload a single file |
| `GET` | `/api/assets` | List assets (paginated) |
| `GET` | `/api/assets/:id` | Get asset metadata |
| `DELETE` | `/api/assets/:id` | Delete asset and file |
| `GET` | `/public/assets/:filename` | Stream file (Range support) |

For complete API reference, see [API_DOCS.md](API_DOCS.md) or visit `/docs` in the browser.

## Database Schema

```javascript
{
  assetId: String,           // Unique ID (hex, 12 bytes)
  originalFilename: String,  // Original file name (user-provided)
  secureFilename: String,    // Safe random filename (timestamp-hash.ext)
  mimeType: String,          // Validated image/* or video/*
  fileSize: Number,          // Size in bytes (min: 0)
  storagePath: String,       // Absolute path on disk
  publicUrl: String,         // Fully qualified public URL
  createdAt: Date,           // Auto timestamp
  updatedAt: Date            // Auto timestamp
}
```

Indexes:
- `assetId` (unique, indexed)
- `secureFilename` (unique)
- `createdAt` (descending)

## Development

### Scripts

```bash
npm run dev   # Start with --watch (auto-restart)
npm start     # Start production server
```

### Code Style

- JavaScript (CommonJS modules)
- Async/await for all database operations
- Centralized error handling middleware
- No build step required

### Adding New MIME Types

1. Add to `ALLOWED_MIME_TYPES` in `src/utils/helpers.js`
2. Add regex pattern to `mediaAssetSchema` mimeType validator in `src/models/MediaAsset.js`
3. Add extension mapping in `getContentType()` in `src/routes/public.js`

## Testing

### Manual API Testing

```bash
# Health check
curl http://localhost:3000/api/assets

# Upload
curl -X POST -F "file=@test.jpg" http://localhost:3000/api/assets/upload

# Get asset
curl http://localhost:3000/api/assets/{assetId}

# Delete asset
curl -X DELETE http://localhost:3000/api/assets/{assetId}

# Stream with range
curl -v -H "Range: bytes=0-1023" http://localhost:3000/public/assets/{filename}
```

### Upload Validation

- Invalid MIME type returns 400
- File exceeds `MAX_FILE_SIZE` returns 413
- Missing file returns 400

## Deployment

### Environment

```env
NODE_ENV=production
PORT=3000
MONGODB_URI=mongodb+srv://...
UPLOAD_DIR=/var/www/uploads
BASE_URL=https://storage.example.com
MAX_FILE_SIZE=524288000
```

### Process Manager

Use PM2 or similar for production:

```bash
npm install -g pm2
pm2 start src/server.js --name storage-gateway
pm2 save
pm2 startup
```

### Reverse Proxy (Nginx)

```nginx
server {
    listen 80;
    server_name storage.example.com;

    client_max_body_size 500M;

    location / {
        proxy_pass http://localhost:3000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Directory Permissions

Ensure uploads directory is writable:

```bash
chmod 755 uploads
# Or for production:
chown -R www-data:www-data uploads
```

## PHP Version

An alternative PHP implementation is available in `php-version/` for traditional PHP hosting.

### Features (PHP Version)

- Persistent file storage between deployments
- MongoDB metadata storage via PHP extension
- HTTP 206 streaming support
- Tailwind CSS frontend
- `.htaccess` routing

### Setup

See `php-version/README.md` for full instructions.

### PHP Troubleshooting

See `php-version/README-PHP.md` for common issues:
- MongoDB extension installation
- File permissions
- Empty JSON responses
- Database connection issues
- Web server configuration (Apache/Nginx)

### MySQL Alternative

A MySQL version is available in `php-version-mysql/` for shared hosting without MongoDB support.

## Troubleshooting

### MongoDB Connection Failed

```bash
# Check MongoDB is running
mongosh --eval "db.adminCommand('ping')"

# Verify URI format
echo $MONGODB_URI
```

### Upload Fails

- Check `MAX_FILE_SIZE` in `.env`
- Verify `UPLOAD_DIR` exists and is writable
- Check server logs for Multer errors

### 404 on Asset Access

- Confirm file exists in `UPLOAD_DIR`
- Verify `secureFilename` in database matches file on disk
- Check `BASE_URL` is correct

### CORS Errors

The API enables CORS globally. For strict origins, modify in `src/server.js`:

```javascript
app.use(cors({ origin: 'https://your-frontend.com' }));
```

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

Distributed under the MIT License. See `LICENSE` for more information.

## Support

- Documentation: `/docs` or [API_DOCS.md](API_DOCS.md)
- Issues: Open an issue with reproduction steps
- PHP Variant Docs: `php-version/README.md`
