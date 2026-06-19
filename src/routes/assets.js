const express = require('express');
const router = express.Router();
const MediaAsset = require('../models/MediaAsset');
const { generateSecureFilename } = require('../utils/helpers');
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const UPLOAD_DIR = process.env.UPLOAD_DIR || 'uploads';
const BASE_URL = process.env.BASE_URL || 'http://localhost:3000';

// POST /api/assets/upload
router.post('/upload', async (req, res) => {
  try {
    console.log('Upload request received');
    console.log('Request file:', req.file);
    console.log('Request body:', req.body);
    
    if (!req.file) {
      console.log('No file uploaded');
      return res.status(400).json({ error: 'No file uploaded' });
    }

    const { originalname, filename, mimetype, size, path: storagePath } = req.file;
    console.log(`File saved to: ${storagePath}`);
    
    const assetId = crypto.randomBytes(12).toString('hex');
    const publicUrl = `${BASE_URL}/public/assets/${filename}`;
    console.log(`Public URL: ${publicUrl}`);

    const asset = new MediaAsset({
      assetId,
      originalFilename: originalname,
      secureFilename: filename,
      mimeType: mimetype,
      fileSize: size,
      storagePath,
      publicUrl,
    });

    await asset.save();

    // Return the asset without the storagePath for security
    const { storagePath: _, ...assetData } = asset.toObject();
    res.status(201).json({
      success: true,
      data: assetData,
    });
  } catch (error) {
    console.error('Upload error:', error);
    res.status(500).json({ error: 'Failed to upload file', details: error.message });
  }
});

// GET /api/assets
router.get('/', async (req, res) => {
  try {
    const page = parseInt(req.query.page) || 1;
    const limit = parseInt(req.query.limit) || 20;
    const skip = (page - 1) * limit;

    const [total, assets] = await Promise.all([
      MediaAsset.countDocuments(),
      MediaAsset.find()
        .sort({ createdAt: -1 })
        .skip(skip)
        .limit(limit),
    ]);

    res.json({
      success: true,
      data: assets,
      pagination: {
        page,
        limit,
        total,
        totalPages: Math.ceil(total / limit),
      },
    });
  } catch (error) {
    console.error('Get assets error:', error);
    res.status(500).json({ error: 'Failed to retrieve assets' });
  }
});

// GET /api/assets/:id
router.get('/:id', async (req, res) => {
  try {
    const asset = await MediaAsset.findOne({ assetId: req.params.id });
    if (!asset) {
      return res.status(404).json({ error: 'Asset not found' });
    }
    res.json({ success: true, data: asset });
  } catch (error) {
    console.error('Get asset error:', error);
    res.status(500).json({ error: 'Failed to retrieve asset' });
  }
});

// DELETE /api/assets/:id
router.delete('/:id', async (req, res) => {
  try {
    const asset = await MediaAsset.findOne({ assetId: req.params.id });
    if (!asset) {
      return res.status(404).json({ error: 'Asset not found' });
    }

    // Delete file from storage
    const filePath = path.join(UPLOAD_DIR, asset.secureFilename);
    if (fs.existsSync(filePath)) {
      fs.unlinkSync(filePath);
    }

    await asset.deleteOne();

    res.json({ success: true, message: 'Asset deleted successfully' });
  } catch (error) {
    console.error('Delete asset error:', error);
    res.status(500).json({ error: 'Failed to delete asset' });
  }
});

module.exports = router;
