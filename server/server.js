const express = require('express');
const path = require('path');
const fs = require('fs');
const morgan = require('morgan');
const multer = require('multer');
const sharp = require('sharp');
const basicAuth = require('express-basic-auth');
const dotenv = require('dotenv');

dotenv.config({ path: path.join(__dirname, '.env') });

const app = express();
const PORT = process.env.PORT || 3000;
const ADMIN_USER = process.env.ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.ADMIN_PASS || 'admin123';
const MAX_FILE_MB = Number(process.env.MAX_FILE_MB || 15);

const ROOT_PUBLIC = path.join(__dirname, '..'); // racine du site statique
const UPLOADS_DIR = path.join(__dirname, 'uploads');

// Ensure uploads dir
fs.mkdirSync(UPLOADS_DIR, { recursive: true });

app.use(morgan('dev'));
app.use(express.json());

// Static: site root and uploads
app.use('/uploads', express.static(UPLOADS_DIR, { maxAge: '1y', immutable: true }));
app.use(express.static(ROOT_PUBLIC));

// Basic auth for admin and upload routes
const authMiddleware = basicAuth({
  users: { [ADMIN_USER]: ADMIN_PASS },
  challenge: true,
  unauthorizedResponse: () => 'Auth required'
});

// ADMIN page
app.get('/admin', authMiddleware, (req, res) => {
  res.sendFile(path.join(__dirname, 'admin', 'index.html'));
});

app.use('/admin', authMiddleware, express.static(path.join(__dirname, 'admin')));

// Multer setup (memory storage -> sharp -> file)
const upload = multer({
  storage: multer.memoryStorage(),
  limits: { fileSize: MAX_FILE_MB * 1024 * 1024 },
  fileFilter: (req, file, cb) => {
    const ok = /image\/(jpeg|png|webp|avif)/.test(file.mimetype);
    cb(ok ? null : new Error('Type de fichier non supporté'), ok);
  }
});

// Helpers
function slugify(str) {
  return (str || 'album')
    .toString()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-zA-Z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .toLowerCase() || 'album';
}
function ensureAlbumDir(slug){
  const dir = path.join(UPLOADS_DIR, slug);
  const thumbs = path.join(dir, '_thumbs');
  fs.mkdirSync(dir, { recursive: true });
  fs.mkdirSync(thumbs, { recursive: true });
  return { dir, thumbs };
}
function isImageFile(name){
  return /\.(jpe?g|png|webp|avif)$/i.test(name);
}

// API: list albums
app.get('/api/albums', async (req,res)=>{
  try{
    const albums = fs.readdirSync(UPLOADS_DIR, { withFileTypes:true })
      .filter(d => d.isDirectory())
      .map(d => d.name);

    const out = albums.map(slug => {
      const dir = path.join(UPLOADS_DIR, slug);
      const files = fs.readdirSync(dir).filter(isImageFile);
      const cover = files[0] || null;
      return {
        slug,
        title: slug.replace(/-/g, ' ').replace(/\b\w/g, m=>m.toUpperCase()),
        count: files.length,
        cover: cover ? `/uploads/${slug}/_thumbs/${cover.replace(/\.(avif|webp|png)$/i, '.jpg')}` : null
      };
    }).sort((a,b)=> a.title.localeCompare(b.title));
    res.json(out);
  }catch(e){
    console.error(e);
    res.status(500).json({error:'Albums read error'});
  }
});

// API: list images in album
app.get('/api/albums/:slug', async (req,res)=>{
  const slug = req.params.slug;
  const dir = path.join(UPLOADS_DIR, slug);
  if(!fs.existsSync(dir)) return res.status(404).json({error:'Album not found'});
  try{
    const files = fs.readdirSync(dir).filter(isImageFile);
    const out = files.map(name => ({
      file: `/uploads/${slug}/${name.replace(/\.(avif|webp|png)$/i, '.jpg')}`,
      thumb:`/uploads/${slug}/_thumbs/${name.replace(/\.(avif|webp|png)$/i, '.jpg')}`
    }));
    res.json({ slug, title: slug.replace(/-/g,' ').replace(/\b\w/g, m=>m.toUpperCase()), images: out });
  }catch(e){
    console.error(e);
    res.status(500).json({error:'Album read error'});
  }
});

// API: upload (basic-auth)
app.post('/api/upload', authMiddleware, upload.array('files', 50), async (req,res)=>{
  try{
    const albumName = req.body.albumName || 'Album';
    const slug = slugify(req.body.albumSlug || albumName);
    const { dir, thumbs } = ensureAlbumDir(slug);

    const processed = [];
    for (const file of req.files){
      const base = require('path').parse(file.originalname).name;
      const destFull = require('path').join(dir, `${base}.jpg`);
      const destThumb = require('path').join(thumbs, `${base}.jpg`);

      const img = sharp(file.buffer, { failOn: 'none' });
      // full (max 1600px)
      await img.clone()
        .rotate()
        .resize({ width:1600, height:1600, fit:'inside', withoutEnlargement:true })
        .jpeg({ quality:82, mozjpeg:true })
        .toFile(destFull);
      // thumb (max 420px)
      await img.clone()
        .rotate()
        .resize({ width:420, height:420, fit:'inside', withoutEnlargement:true })
        .jpeg({ quality:76, mozjpeg:true })
        .toFile(destThumb);

      processed.push({
        file:`/uploads/${slug}/${require('path').basename(destFull)}`,
        thumb:`/uploads/${slug}/_thumbs/${require('path').basename(destThumb)}`
      });
    }
    res.json({ ok:true, slug, count: processed.length, files: processed });
  }catch(e){
    console.error(e);
    res.status(500).json({error: e.message || 'Upload error'});
  }
});
const registerHomeCMS = require('./home-cms');
registerHomeCMS(app);

app.listen(PORT, ()=>{
  console.log(`HKC gallery backend running on http://localhost:${PORT}`);
  console.log(`Serving static from: ${ROOT_PUBLIC}`);
});
// Renommer un album
app.post('/api/albums/:slug/rename', authMiddleware, express.json(), (req,res)=>{
  const oldSlug = req.params.slug;
  const newSlug = (req.body.newSlug||'').trim().toLowerCase().replace(/[^a-z0-9-]+/g,'-').replace(/^-+|-+$/g,'') || oldSlug;
  const from = path.join(UPLOADS_DIR, oldSlug);
  const to   = path.join(UPLOADS_DIR, newSlug);
  if (!fs.existsSync(from)) return res.status(404).json({error:'not_found'});
  if (fs.existsSync(to))    return res.status(409).json({error:'exists'});
  fs.renameSync(from, to);
  return res.json({ok:true, slug:newSlug});
});

// Supprimer une image d’un album
app.delete('/api/albums/:slug/images/:file', authMiddleware, (req,res)=>{
  const { slug, file } = req.params;
  const dir = path.join(UPLOADS_DIR, slug);
  const p1 = path.join(dir, file);
  const p2 = path.join(dir, '_thumbs', file);
  if (!fs.existsSync(dir)) return res.status(404).json({error:'album_not_found'});
  [p1,p2].forEach(p=>{ try{ fs.unlinkSync(p); }catch{} });
  return res.json({ok:true});
});
