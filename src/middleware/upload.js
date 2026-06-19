const multer = require('multer');
const path = require('path');
const fs = require('fs');
const { isAllowedMimeType, generateSecureFilename } = require('../utils/helpers');

const UPLOAD_DIR = process.env.UPLOAD_DIR || 'uploads';

// Ensure upload directory exists
if (!fs.existsSync(UPLOAD_DIR)) {
  fs.mkdirSync(UPLOAD_DIR, { recursive: true });
}

const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, UPLOAD_DIR);
  },
  filename: (req, file, cb) => {
    const secureName = generateSecureFilename(file.originalname);
    cb(null, secureName);
  },
});

const fileFilter = (req, file, cb) => {
  if (isAllowedMimeType(file.mimetype)) {
    cb(null, true);
  } else {
    cb(new Error(`File type "${file.mimetype}" is not allowed. Only image/* and video/* files are accepted.`), false);
  }
};

const upload = multer({
  storage,
  fileFilter,
  limits: {
    fileSize: parseInt(process.env.MAX_FILE_SIZE, 10) || 500 * 1024 * 1024, // 500MB default
    files: 1 // Only allow one file at a time
  },
});

module.exports = upload;
