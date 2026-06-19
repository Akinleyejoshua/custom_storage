const crypto = require('crypto');
const path = require('path');

const ALLOWED_MIME_TYPES = [
  'image/jpeg',
  'image/png',
  'image/gif',
  'image/webp',
  'image/svg+xml',
  'image/bmp',
  'image/tiff',
  'video/mp4',
  'video/webm',
  'video/ogg',
  'video/quicktime',
  'video/x-msvideo',
  'video/x-matroska',
];

function isAllowedMimeType(mimeType) {
  return ALLOWED_MIME_TYPES.includes(mimeType);
}

function isImage(mimeType) {
  return mimeType.startsWith('image/');
}

function isVideo(mimeType) {
  return mimeType.startsWith('video/');
}

function generateSecureFilename(originalFilename) {
  const ext = path.extname(originalFilename).toLowerCase();
  const hash = crypto.randomBytes(16).toString('hex');
  const timestamp = Date.now().toString(36);
  return `${timestamp}-${hash}${ext}`;
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

module.exports = {
  ALLOWED_MIME_TYPES,
  isAllowedMimeType,
  isImage,
  isVideo,
  generateSecureFilename,
  formatFileSize,
};
