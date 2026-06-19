require('dotenv').config();
const express = require('express');
const mongoose = require('mongoose');
const cors = require('cors');
const multer = require('multer');
const path = require('path');
const fs = require('fs');

const assetsRoutes = require('./routes/assets');
const publicRoutes = require('./routes/public');

const app = express();

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Serve static files from uploads directory
app.use('/uploads', express.static(process.env.UPLOAD_DIR || 'uploads'));

// Import upload middleware
const upload = require('./middleware/upload');

// API Routes
app.post('/api/assets/upload', upload.single('file'), assetsRoutes);
app.use('/api/assets', assetsRoutes);

// Public streaming route
app.use('/public/assets', publicRoutes);

// Frontend routes
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

app.get('/upload', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/upload.html'));
});

// Error handling middleware
app.use((err, req, res, next) => {
  console.error(err.stack);
  if (err.code === 'LIMIT_FILE_SIZE') {
    return res.status(413).json({ error: 'File too large' });
  }
  res.status(500).json({ error: 'Internal server error' });
});

// Connect to MongoDB and start server
const PORT = process.env.PORT || 3000;
const MONGODB_URI = process.env.MONGODB_URI || 'mongodb://localhost:27017/media-gateway';

mongoose
  .connect(MONGODB_URI)
  .then(() => {
    console.log('Connected to MongoDB');
    app.listen(PORT, () => {
      console.log(`Server running on http://localhost:${PORT}`);
    });
  })
  .catch((err) => {
    console.error('Failed to connect to MongoDB:', err);
    process.exit(1);
  });
