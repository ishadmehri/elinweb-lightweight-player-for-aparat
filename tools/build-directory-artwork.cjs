// Build the WordPress.org directory assets from the author's supplied artwork.
// Run with Node.js and sharp installed.
const fs = require('node:fs');
const path = require('node:path');
const sharp = require('sharp');

const root = path.join(__dirname, '..');
const source = path.join(root, 'artwork-source');
const assets = path.join(root, 'wordpress-org-assets');

async function build() {
  const svg = fs.readFileSync(path.join(source, 'aparat.svg'), 'utf8');
  const original = 'width="498.16" height="347.64" viewBox="0 0 498.16 347.64"';
  if (!svg.includes(original)) {
    throw new Error('The supplied SVG dimensions changed; check the square icon canvas.');
  }

  // Add transparent space above and below the original design; its paths are unchanged.
  const squareIcon = svg.replace(original,
    'width="498.16" height="498.16" viewBox="0 -75.26 498.16 498.16"');
  fs.writeFileSync(path.join(assets, 'icon.svg'), squareIcon, 'utf8');
  await Promise.all([128, 256].map((size) => sharp(Buffer.from(squareIcon))
    .resize(size, size).png().toFile(path.join(assets, `icon-${size}x${size}.png`))));

  // The original is 2172×724. A small centered crop matches WordPress.org's 1544×500 ratio.
  const retinaBanner = await sharp(path.join(source, 'aparat-banner.png'))
    .resize(1544, 500, { fit: 'cover', position: 'centre' }).png().toBuffer();
  fs.writeFileSync(path.join(assets, 'banner-1544x500.png'), retinaBanner);
  await sharp(retinaBanner).resize(772, 250).png()
    .toFile(path.join(assets, 'banner-772x250.png'));
  console.log('Built WordPress.org icons and banners from the supplied artwork.');
}

build().catch((error) => { console.error(error); process.exitCode = 1; });
