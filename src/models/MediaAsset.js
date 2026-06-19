const mongoose = require('mongoose');

const mediaAssetSchema = new mongoose.Schema(
  {
    assetId: {
      type: String,
      required: true,
      unique: true,
      index: true,
    },
    originalFilename: {
      type: String,
      required: true,
    },
    secureFilename: {
      type: String,
      required: true,
      unique: true,
    },
    mimeType: {
      type: String,
      required: true,
      validate: {
        validator: function (v) {
          return /^image\/(jpeg|png|gif|webp|svg\+xml|bmp|tiff)$/.test(v) ||
                 /^video\/(mp4|webm|ogg|quicktime|x-msvideo|x-matroska)$/.test(v);
        },
        message: (props) => `${props.value} is not a supported image or video mime type.`,
      },
    },
    fileSize: {
      type: Number,
      required: true,
      min: 0,
    },
    storagePath: {
      type: String,
      required: true,
    },
    publicUrl: {
      type: String,
      required: true,
    },
  },
  {
    timestamps: true,
  }
);

mediaAssetSchema.index({ createdAt: -1 });

module.exports = mongoose.model('MediaAsset', mediaAssetSchema);
