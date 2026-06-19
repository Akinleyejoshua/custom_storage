# Custom Cloud Storage Gateway — Advanced API Reference

**Version:** 1.0.0
**Base URL:** `http://localhost:3000` (or your configured `BASE_URL`)
**Protocol:** HTTP/1.1
**Data Format:** JSON (requests and responses)
**Authentication:** None (open API)
**Rate Limiting:** Not implemented

---

## Table of Contents

- [Getting Started](#getting-started)
- [Authentication & Security](#authentication--security)
- [Core Concepts](#core-concepts)
- [Endpoints](#endpoints)
  - [Upload Asset](#1-upload-asset)
  - [List Assets](#2-list-assets)
  - [Get Asset](#3-get-asset)
  - [Delete Asset](#4-delete-asset)
  - [Stream Asset](#5-stream-asset)
- [Error Codes](#error-codes)
- [MIME Types Supported](#mime-types-supported)
- [File Size Limits](#file-size-limits)
- [Rate Limits & Throttling](#rate-limits--throttling)
- [CORS & Headers](#cors--headers)
- [Webhooks (Planned)](#webhooks-planned)
- [Client SDK Examples](#client-sdk-examples)
- [Changelog](#changelog)

---

## Getting Started

### Prerequisites

- Node.js 18+
- MongoDB 4.4+ (local or Atlas)
- Server running on configured port

### Quick Test

```bash
# Check server health
curl http://localhost:3000/api/assets

# Expected: 200 OK with empty or paginated list
```

---

## Authentication & Security

This API does not currently implement authentication. All endpoints are publicly accessible.

### Recommendations for Production

| Concern | Recommendation |
|---------|----------------|
| Access Control | Add API key middleware |
| Rate Limiting | Use `express-rate-limit` |
| HTTPS | Terminate TLS at load balancer or reverse proxy |
| CORS | Restrict origins in `src/server.js` |
| File Access | Move uploads outside web root, proxy through API |

### CORS Headers

```http
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Range
```

---

## Core Concepts

### Assets

An asset represents an uploaded media file. Assets are identified by two keys:

- **assetId** — 12-byte random hex string, returned in API responses. Use this for lookups.
- **secureFilename** — Opaque generated filename (timestamp-hash.ext). Used in public URLs. Prevents filename enumeration.

### Streaming (HTTP Range)

The `/public/assets/:filename` endpoint supports `Range` headers, enabling:

- Video seeking without full download
- Resume interrupted downloads
- Progressive loading in browsers

Range request flow:

```
Client                    Server
  │                         │
  │── GET /file.mp4 ──────▶│
  │   (no Range)           │
  │◀── 200 OK + full body ─│
  │                         │
  │── GET /file.mp4 ──────▶│
  │   Range: bytes=0-1023  │
  │◀── 206 Partial Content │
  │   + Content-Range       │
```

---

## Endpoints

### 1. Upload Asset

Upload a single media file (image or video).

`POST /api/assets/upload`

**Headers:**
```
Content-Type: multipart/form-data
```

**Body (Form Data):**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `file` | file | Yes | The media file to upload |

**cURL Example:**
```bash
curl -X POST \
  -F "file=@/path/to/vacation.jpg" \
  http://localhost:3000/api/assets/upload
```

**JavaScript (Fetch):**
```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);

const response = await fetch('/api/assets/upload', {
  method: 'POST',
  body: formData,
});

const result = await response.json();
console.log(result.data.assetId);
```

**JavaScript (XMLHttpRequest with Progress):**
```javascript
const xhr = new XMLHttpRequest();
const formData = new FormData();
formData.append('file', file);

xhr.upload.addEventListener('progress', (e) => {
  if (e.lengthComputable) {
    const percent = (e.loaded / e.total) * 100;
    console.log(`Upload: ${percent.toFixed(1)}%`);
  }
});

xhr.onload = () => {
  const result = JSON.parse(xhr.responseText);
  if (xhr.status === 201) {
    console.log('Asset ID:', result.data.assetId);
  }
};

xhr.open('POST', '/api/assets/upload');
xhr.send(formData);
```

**Success Response (201 Created):**
```json
{
  "success": true,
  "data": {
    "_id": "662f1a2b3c4d5e6f7890abcd",
    "assetId": "a1b2c3d4e5f6",
    "originalFilename": "vacation.jpg",
    "secureFilename": "m3k9x2v1n4b6q7w8.jpg",
    "mimeType": "image/jpeg",
    "fileSize": 2458624,
    "publicUrl": "http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.jpg",
    "createdAt": "2026-06-19T16:00:00.000Z",
    "updatedAt": "2026-06-19T16:00:00.000Z"
  }
}
```

**Error Responses:**

| Status | Condition | Response Body |
|--------|-----------|---------------|
| `400` | No file in request | `{"error": "No file uploaded"}` |
| `400` | Invalid MIME type | `{"error": "File type \"application/pdf\" is not allowed. Only image/* and video/* files are accepted."}` |
| `413` | File exceeds `MAX_FILE_SIZE` | `{"error": "File too large"}` |
| `500` | Database save failed | `{"error": "Failed to upload file", "details": "E11000 duplicate key error..."}` |

**Validation Rules:**

- Only one file per request (`files: 1` in Multer limits)
- MIME must match `/^image\/(jpeg\|png\|gif\|webp\|svg\+xml\|bmp\|tiff)$/` or `/^video\/(mp4\|webm\|ogg\|quicktime\|x-msvideo\|x-matroska)$/`
- Size must be ≤ `MAX_FILE_SIZE` (default 500MB)
- Original filename is sanitized; extension is preserved in secure filename

---

### 2. List Assets

Retrieve a paginated list of uploaded assets, sorted by newest first.

`GET /api/assets`

**Query Parameters:**

| Parameter | Type | Default | Description |
|-----------|------|---------|-------------|
| `page` | integer | `1` | Page number (1-indexed) |
| `limit` | integer | `20` | Items per page (max 100 recommended) |

**cURL Example:**
```bash
curl "http://localhost:3000/api/assets?page=2&limit=10"
```

**JavaScript Example:**
```javascript
const response = await fetch('/api/assets?page=1&limit=20');
const result = await response.json();

console.log(`Total assets: ${result.pagination.total}`);
console.log(`Current page: ${result.pagination.page}`);
console.log(`Assets:`, result.data);
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "_id": "662f1a2b3c4d5e6f7890abcd",
      "assetId": "a1b2c3d4e5f6",
      "originalFilename": "demo.mp4",
      "secureFilename": "m3k9x2v1n4b6q7w8.mp4",
      "mimeType": "video/mp4",
      "fileSize": 15728640,
      "publicUrl": "http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.mp4",
      "createdAt": "2026-06-19T16:00:00.000Z",
      "updatedAt": "2026-06-19T16:00:00.000Z"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 42,
    "totalPages": 3
  }
}
```

**Pagination Logic:**
```
skip = (page - 1) * limit
totalPages = ceil(total / limit)
```

**Notes:**
- Results are sorted by `createdAt` descending
- `storagePath` is excluded from list responses for security
- `total` is computed via `countDocuments()` in parallel with the query

---

### 3. Get Asset

Retrieve metadata for a specific asset by its `assetId`.

`GET /api/assets/:id`

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | string | The assetId (12-byte hex string) |

**cURL Example:**
```bash
curl http://localhost:3000/api/assets/a1b2c3d4e5f6
```

**JavaScript Example:**
```javascript
const assetId = 'a1b2c3d4e5f6';
const response = await fetch(`/api/assets/${assetId}`);

if (response.ok) {
  const result = await response.json();
  console.log('Public URL:', result.data.publicUrl);
} else {
  console.error('Asset not found');
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "_id": "662f1a2b3c4d5e6f7890abcd",
    "assetId": "a1b2c3d4e5f6",
    "originalFilename": "vacation.jpg",
    "secureFilename": "m3k9x2v1n4b6q7w8.jpg",
    "mimeType": "image/jpeg",
    "fileSize": 2458624,
    "publicUrl": "http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.jpg",
    "createdAt": "2026-06-19T16:00:00.000Z",
    "updatedAt": "2026-06-19T16:00:00.000Z"
  }
}
```

**Error Responses:**

| Status | Condition | Response Body |
|--------|-----------|---------------|
| `404` | Asset not found | `{"error": "Asset not found"}` |
| `500` | Database error | `{"error": "Failed to retrieve asset"}` |

---

### 4. Delete Asset

Delete an asset's metadata record and its associated file from disk.

`DELETE /api/assets/:id`

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `id` | string | The assetId to delete |

**cURL Example:**
```bash
curl -X DELETE http://localhost:3000/api/assets/a1b2c3d4e5f6
```

**JavaScript Example:**
```javascript
const assetId = 'a1b2c3d4e5f6';
const response = await fetch(`/api/assets/${assetId}`, {
  method: 'DELETE',
});

const result = await response.json();
if (result.success) {
  console.log('Deleted:', result.message);
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Asset deleted successfully"
}
```

**Error Responses:**

| Status | Condition | Response Body |
|--------|-----------|---------------|
| `404` | Asset not found | `{"error": "Asset not found"}` |
| `500` | File or DB deletion failed | `{"error": "Failed to delete asset"}` |

**Behavior:**
1. Finds asset by `assetId` in MongoDB
2. Deletes file from `UPLOAD_DIR` using `secureFilename`
3. Removes MongoDB document
4. Returns success message

**Note:** If file deletion fails but DB delete succeeds, the record is removed but orphaned file remains on disk.

---

### 5. Stream Asset

Stream a file with HTTP Range support. Used by browsers for video seeking and download resumption.

`GET /public/assets/:filename`

**URL Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `filename` | string | The secure filename (e.g., `m3k9x2v1n4b6q7w8.jpg`) |

**Headers (Optional):**

| Header | Example | Description |
|--------|---------|-------------|
| `Range` | `bytes=0-1023` | Request partial content |

**Browser Usage (HTML):**
```html
<!-- Image -->
<img src="http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.jpg" alt="Photo">

<!-- Video with seeking -->
<video src="http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.mp4" controls></video>

<!-- Download -->
<a href="http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.pdf" download>Download</a>
```

**cURL Examples:**

```bash
# Full file download
curl -o output.jpg http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.jpg

# Partial content request
curl -v -H "Range: bytes=0-1023" http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.mp4

# Download with progress
curl -# -o output.mp4 http://localhost:3000/public/assets/m3k9x2v1n4b6q7w8.mp4
```

**Responses:**

**Full File (200 OK):**
```http
HTTP/1.1 200 OK
Content-Length: 2458624
Content-Type: image/jpeg
Accept-Ranges: bytes

<binary data>
```

**Partial Content (206 Partial Content):**
```http
HTTP/1.1 206 Partial Content
Content-Range: bytes 0-1023/2458624
Accept-Ranges: bytes
Content-Length: 1024
Content-Type: image/jpeg

<partial binary data>
```

**Error Responses:**

| Status | Condition | Response Body |
|--------|-----------|---------------|
| `404` | File not found on disk | `{"error": "File not found"}` |

**Supported Content Types:**

Extension mapping is handled in `src/routes/public.js`. See MIME types section for full list.

---

## Error Codes

All errors return JSON. Top-level structure:

```json
{
  "error": "Short error message",
  "details": "Optional extended context (only present in 500 errors)"
}
```

| Status | Error Key | Description |
|--------|-----------|-------------|
| `400` | `No file uploaded` | Request missing file field |
| `400` | `File type \"...\" is not allowed...` | MIME validation failed |
| `404` | `Asset not found` | assetId not found in DB |
| `404` | `File not found` | File missing from disk |
| `413` | `File too large` | Exceeds MAX_FILE_SIZE |
| `500` | `Internal server error` | Unhandled exception |
| `500` | `Failed to upload file` | DB save or storage error |
| `500` | `Failed to retrieve asset` | DB query error |
| `500` | `Failed to delete asset` | File or DB delete error |

---

## MIME Types Supported

### Images

| MIME Type | Extensions |
|-----------|-----------|
| `image/jpeg` | `.jpg`, `.jpeg` |
| `image/png` | `.png` |
| `image/gif` | `.gif` |
| `image/webp` | `.webp` |
| `image/svg+xml` | `.svg` |
| `image/bmp` | `.bmp` |
| `image/tiff` | `.tiff` |

### Videos

| MIME Type | Extensions |
|-----------|-----------|
| `video/mp4` | `.mp4` |
| `video/webm` | `.webm` |
| `video/ogg` | `.ogg` |
| `video/quicktime` | `.mov` |
| `video/x-msvideo` | `.avi` |
| `video/x-matroska` | `.mkv` |

MIME validation is enforced at two layers:
1. **Multer fileFilter** — Prevents file from being stored if MIME is invalid
2. **Mongoose schema validator** — Prevents invalid records from being saved to DB

---

## File Size Limits

| Setting | Default | Configurable |
|---------|---------|--------------|
| Per-file upload limit | 500 MB | Yes, via `MAX_FILE_SIZE` env var (bytes) |
| Multiple file upload | Not supported | N/A (Multer `files: 1`) |

**Configure:**
```env
# 100MB
MAX_FILE_SIZE=104857600

# 1GB
MAX_FILE_SIZE=1073741824
```

---

## Rate Limits & Throttling

Not implemented. No rate limiting headers are returned.

### Recommended for Production

```javascript
const rateLimit = require('express-rate-limit');

const uploadLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 10, // 10 uploads per window
  message: { error: 'Too many uploads, try again later.' },
});

app.post('/api/assets/upload', uploadLimiter, upload.single('file'), assetsRoutes);
```

---

## CORS & Headers

### CORS

Enabled globally with default settings (allow all origins):

```javascript
app.use(cors());
```

**Custom origin restriction:**
```javascript
app.use(cors({ origin: 'https://your-app.com' }));
```

### Request Headers

| Header | Required | Description |
|--------|----------|-------------|
| `Content-Type` | For uploads | `multipart/form-data` |
| `Range` | Optional | For video seeking (`bytes=start-end`) |

### Response Headers (Streaming)

| Header | Value | Description |
|--------|-------|-------------|
| `Content-Range` | `bytes start-end/total` | Present on 206 responses |
| `Accept-Ranges` | `bytes` | Server supports range requests |
| `Content-Length` | number | Size of response body |
| `Content-Type` | MIME type | Derived from file extension |

---

## Webhooks (Planned)

Webhook support is planned for a future release. Planned events:

| Event | Payload |
|-------|---------|
| `asset.uploaded` | assetId, publicUrl, mimeType, fileSize |
| `asset.deleted` | assetId, originalFilename |
| `upload.failed` | error, mimeType (if known) |

---

## Client SDK Examples

### cURL

```bash
# Upload
curl -X POST -F "file=@photo.jpg" http://localhost:3000/api/assets/upload

# List
curl http://localhost:3000/api/assets?page=1&limit=10

# Get
curl http://localhost:3000/api/assets/abc123

# Delete
curl -X DELETE http://localhost:3000/api/assets/abc123

# Stream
curl -o photo.jpg http://localhost:3000/public/assets/abc123.jpg
```

### JavaScript / Fetch

```javascript
const API_BASE = 'http://localhost:3000';

async function uploadAsset(file) {
  const formData = new FormData();
  formData.append('file', file);

  const res = await fetch(`${API_BASE}/api/assets/upload`, {
    method: 'POST',
    body: formData,
  });
  return res.json();
}

async function listAssets(page = 1, limit = 20) {
  const res = await fetch(`${API_BASE}/api/assets?page=${page}&limit=${limit}`);
  return res.json();
}

async function getAsset(assetId) {
  const res = await fetch(`${API_BASE}/api/assets/${assetId}`);
  return res.json();
}

async function deleteAsset(assetId) {
  const res = await fetch(`${API_BASE}/api/assets/${assetId}`, {
    method: 'DELETE',
  });
  return res.json();
}
```

### Python / requests

```python
import requests

BASE = 'http://localhost:3000'

def upload_asset(file_path):
    with open(file_path, 'rb') as f:
        files = {'file': f}
        r = requests.post(f'{BASE}/api/assets/upload', files=files)
    return r.json()

def list_assets(page=1, limit=20):
    r = requests.get(f'{BASE}/api/assets', params={'page': page, 'limit': limit})
    return r.json()

def get_asset(asset_id):
    r = requests.get(f'{BASE}/api/assets/{asset_id}')
    return r.json()

def delete_asset(asset_id):
    r = requests.delete(f'{BASE}/api/assets/{asset_id}')
    return r.json()
```

### PHP / cURL

```php
<?php
function uploadAsset($filePath, $baseUrl = 'http://localhost:3000') {
    $ch = curl_init("$baseUrl/api/assets/upload");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CurlFile($filePath)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}
?>
```

---

## Changelog

### 1.0.0 (Current)

- Initial release with Node.js/Express/MongoDB stack
- Image and video upload with MIME validation
- Streaming delivery with HTTP 206 Range support
- Paginated asset listing
- Gallery frontend with drag & drop
- PHP variant with MongoDB and MySQL options
- Interactive API documentation (`/docs`)

### Upcoming

- API key authentication
- Rate limiting
- Batch upload support
- Webhook notifications
- Thumbnail generation
- Expiring signed URLs
