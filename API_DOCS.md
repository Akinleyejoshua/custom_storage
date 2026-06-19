# Custom Cloud Storage Gateway - API Documentation

## Overview
This API provides a cloud storage gateway for media assets (images and videos) with streaming support.

## Base URL
`http://localhost:4000` (or your configured BASE_URL)

## Authentication
This API currently does not require authentication.

## Error Responses
All errors return JSON with the following structure:
```json
{
  "error": "Error message",
  "details": "Optional additional details"
}
```

## Endpoints

### 1. Upload Asset
Upload a media file (image or video).

**Endpoint:** `POST /api/assets/upload`

**Content-Type:** `multipart/form-data`

**Parameters:**
| Field | Type   | Required | Description                     |
|-------|--------|----------|---------------------------------|
| file  | file   | Yes      | The media file to upload        |

**Request Example:**
```bash
curl -X POST -F "file=@/path/to/your/file.jpg" http://localhost:4000/api/assets/upload
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "_id": "60d5ec9f8b3a8b0015d4a7e1",
    "assetId": "a1b2c3d4e5f6",
    "originalFilename": "my-image.jpg",
    "secureFilename": "1624567890-abcdef123456.jpg",
    "mimeType": "image/jpeg",
    "fileSize": 1024567,
    "publicUrl": "http://localhost:4000/public/assets/1624567890-abcdef123456.jpg",
    "createdAt": "2023-06-24T12:34:56.789Z",
    "updatedAt": "2023-06-24T12:34:56.789Z"
  }
}
```

**Error Responses:**
- 400 Bad Request: No file uploaded or invalid file type
- 413 Payload Too Large: File exceeds size limit
- 500 Internal Server Error: Upload failed

### 2. List Assets
Get a paginated list of all uploaded assets.

**Endpoint:** `GET /api/assets`

**Query Parameters:**
| Parameter | Type   | Default | Description                     |
|-----------|--------|---------|---------------------------------|
| page      | number | 1       | Page number                     |
| limit     | number | 20      | Items per page                  |

**Request Example:**
```bash
curl http://localhost:4000/api/assets?page=1&limit=10
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "_id": "60d5ec9f8b3a8b0015d4a7e1",
      "assetId": "a1b2c3d4e5f6",
      "originalFilename": "my-image.jpg",
      "secureFilename": "1624567890-abcdef123456.jpg",
      "mimeType": "image/jpeg",
      "fileSize": 1024567,
      "publicUrl": "http://localhost:4000/public/assets/1624567890-abcdef123456.jpg",
      "createdAt": "2023-06-24T12:34:56.789Z",
      "updatedAt": "2023-06-24T12:34:56.789Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 10,
    "total": 1,
    "totalPages": 1
  }
}
```

### 3. Get Asset
Get metadata for a specific asset.

**Endpoint:** `GET /api/assets/:id`

**URL Parameters:**
| Parameter | Type   | Description                     |
|-----------|--------|---------------------------------|
| id        | string | The assetId of the asset        |

**Request Example:**
```bash
curl http://localhost:4000/api/assets/a1b2c3d4e5f6
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "_id": "60d5ec9f8b3a8b0015d4a7e1",
    "assetId": "a1b2c3d4e5f6",
    "originalFilename": "my-image.jpg",
    "secureFilename": "1624567890-abcdef123456.jpg",
    "mimeType": "image/jpeg",
    "fileSize": 1024567,
    "publicUrl": "http://localhost:4000/public/assets/1624567890-abcdef123456.jpg",
    "createdAt": "2023-06-24T12:34:56.789Z",
    "updatedAt": "2023-06-24T12:34:56.789Z"
  }
}
```

**Error Responses:**
- 404 Not Found: Asset not found

### 4. Delete Asset
Delete an asset and its associated file.

**Endpoint:** `DELETE /api/assets/:id`

**URL Parameters:**
| Parameter | Type   | Description                     |
|-----------|--------|---------------------------------|
| id        | string | The assetId of the asset        |

**Request Example:**
```bash
curl -X DELETE http://localhost:4000/api/assets/a1b2c3d4e5f6
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Asset deleted successfully"
}
```

**Error Responses:**
- 404 Not Found: Asset not found
- 500 Internal Server Error: Failed to delete asset

### 5. Stream Asset
Stream an asset file (supports HTTP range requests for video seeking).

**Endpoint:** `GET /public/assets/:filename`

**URL Parameters:**
| Parameter | Type   | Description                     |
|-----------|--------|---------------------------------|
| filename  | string | The secure filename             |

**Headers:**
| Header    | Value          | Description                     |
|-----------|----------------|---------------------------------|
| Range     | bytes=0-1023   | For partial content requests    |

**Request Example:**
```bash
curl -v http://localhost:4000/public/assets/1624567890-abcdef123456.jpg
```

**Success Responses:**
- 200 OK: Full file
- 206 Partial Content: Partial file (for video seeking)

**Error Responses:**
- 404 Not Found: File not found

## MIME Types Supported

### Images
- image/jpeg (.jpg, .jpeg)
- image/png (.png)
- image/gif (.gif)
- image/webp (.webp)
- image/svg+xml (.svg)
- image/bmp (.bmp)
- image/tiff (.tiff)

### Videos
- video/mp4 (.mp4)
- video/webm (.webm)
- video/ogg (.ogg)
- video/quicktime (.mov)
- video/x-msvideo (.avi)
- video/x-matroska (.mkv)

## File Size Limits
- Default: 500MB (configurable via MAX_FILE_SIZE environment variable)

## Rate Limits
- Currently no rate limiting is implemented

## Versioning
- API version: 1.0.0

## Changelog
- 1.0.0: Initial release with core functionality