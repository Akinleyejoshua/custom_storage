# Custom Cloud Storage Gateway

A Node.js based cloud storage gateway tailored for media assets (images and videos). Built with Express, MongoDB, and a clean Tailwind CSS frontend.

## Features

- Upload images and videos with strict MIME type validation
- Streaming file serving with HTTP 206 partial content support for video seeking
- MongoDB storage for asset metadata
- Drag-and-drop upload interface with progress tracking
- Gallery view with thumbnails and video previews
- Copy public URLs for each asset
- Pagination support

## Project Structure

```
custom_storage/
├── src/
│   ├── models/
│   │   └── MediaAsset.js      # Mongoose schema
│   ├── routes/
│   │   ├── assets.js          # API CRUD routes
│   │   └── public.js          # Public streaming endpoint
│   ├── middleware/
│   │   └── upload.js          # Multer configuration
│   ├── utils/
│   │   └── helpers.js         # Utility functions
│   └── server.js              # Express entry point
├── public/
│   ├── index.html             # Gallery page
│   └── upload.html            # Upload page
├── uploads/                   # File storage directory
├── .env                       # Environment configuration
├── .env.example
├── .gitignore
├── package.json
└── README.md
```

## Setup Instructions

1. **Install dependencies**
   ```bash
   npm install
   ```

2. **Configure environment**
   Copy `.env.example` to `.env` and adjust settings:
   ```bash
   cp .env.example .env
   ```
   - `PORT`: Server port (default 3000)
   - `MONGODB_URI`: MongoDB connection string
   - `UPLOAD_DIR`: Directory for storing uploaded files (default "uploads")
   - `BASE_URL`: Public base URL for generating asset URLs
   - `MAX_FILE_SIZE`: Maximum upload size in bytes (default 500MB)

3. **Start MongoDB**
   Ensure MongoDB is running locally or update `MONGODB_URI` to point to your instance.

4. **Run the server**
   ```bash
   npm run dev
   ```
   Or for production:
   ```bash
   npm start
   ```

5. **Access the app**
   - Gallery: http://localhost:3000/
   - Upload: http://localhost:3000/upload

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/assets/upload` | Upload a file (multipart/form-data with `files` field) |
| GET | `/api/assets` | List assets (query: `page`, `limit`) |
| GET | `/api/assets/:id` | Get asset metadata by assetId |
| DELETE | `/api/assets/:id` | Delete asset (file + DB record) |
| GET | `/public/assets/:filename` | Stream file with range support |

## Database Schema

```javascript
{
  assetId: String,           // Unique ID
  originalFilename: String,  // Original file name
  secureFilename: String,    // Generated safe filename
  mimeType: String,          // Validated image/* or video/*
  fileSize: Number,          // Size in bytes
  storagePath: String,       // Path to file on disk
  publicUrl: String,         // Accessible URL
  createdAt: Date,
  updatedAt: Date
}
```

## Notes

- All uploads are saved to the `UPLOAD_DIR` (default: `uploads/`)
- MIME types are strictly restricted to image/* and video/*
- Video streaming supports HTTP Range requests for smooth scrubbing
- The frontend uses Tailwind CSS via CDN (no build step required)
