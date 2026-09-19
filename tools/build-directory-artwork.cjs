// Run with Node.js and sharp. Aparat-inspired colors, independent Elinweb symbol.
const fs = require('node:fs');
const path = require('node:path');
const sharp = require('sharp');

const assets = path.join(__dirname, '..', 'wordpress-org-assets');

const icon = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <rect x="8" y="8" width="496" height="496" rx="112" fill="#fff6f9" stroke="#f9dce6" stroke-width="8"/>
  <path d="M110 164h123M110 252h88M110 340h123" fill="none" stroke="#242d33" stroke-width="27" stroke-linecap="round"/>
  <path d="M257 145c0-13 15-21 26-14l138 102c16 12 16 34 0 46L283 381c-11 8-26 0-26-14V145z" fill="#ed0c4f"/>
  <path d="M299 207l69 49-69 49z" fill="#ffffff"/>
</svg>`;

const banner = `<svg xmlns="http://www.w3.org/2000/svg" width="1544" height="500" viewBox="0 0 1544 500">
  <defs>
    <linearGradient id="background" x1="0" y1="0" x2="1" y2="1">
      <stop stop-color="#ffffff"/><stop offset="1" stop-color="#fff3f7"/>
    </linearGradient>
    <linearGradient id="card" x1="0" y1="0" x2="1" y2="1">
      <stop stop-color="#323b40"/><stop offset="1" stop-color="#1d2529"/>
    </linearGradient>
  </defs>
  <rect width="1544" height="500" fill="url(#background)"/>
  <circle cx="1501" cy="38" r="184" fill="#ffe1eb"/>
  <circle cx="1005" cy="506" r="228" fill="#ffecf2"/>
  <rect x="112" y="81" width="74" height="74" rx="16" fill="#fff6f9" stroke="#f9dce6" stroke-width="2"/>
  <path d="M130 106h17m-17 16h13m-13 16h17" fill="none" stroke="#242d33" stroke-width="5" stroke-linecap="round"/>
  <path d="M151 105l22 13-22 14z" fill="#ed0c4f"/>
  <text x="211" y="139" font-family="Arial,sans-serif" font-size="43" font-weight="bold" letter-spacing="3" fill="#242d33">ELINWEB</text>
  <text x="112" y="245" font-family="Vazir,Tahoma,sans-serif" font-size="62" font-weight="bold" fill="#242d33">پخش سبک ویدئوهای آپارات</text>
  <text x="112" y="315" font-family="Vazir,Tahoma,sans-serif" font-size="35" fill="#4b5053">برای وردپرس، با یک کلیک</text>
  <rect x="110" y="367" width="405" height="54" rx="27" fill="#ffe4ed"/>
  <text x="145" y="404" font-family="Vazir,Tahoma,sans-serif" font-size="27" font-weight="bold" fill="#b70e43">بلوک گوتنبرگ + ویجت المنتور</text>
  <text x="112" y="459" font-family="Vazir,Tahoma,sans-serif" font-size="21" fill="#62696c">افزونه‌ای مستقل، بدون وابستگی رسمی به آپارات</text>
  <rect x="985" y="82" width="424" height="329" rx="26" fill="#f4c4d3" opacity=".55"/>
  <rect x="970" y="68" width="424" height="329" rx="26" fill="url(#card)"/>
  <rect x="970" y="68" width="424" height="54" rx="26" fill="#41494d"/>
  <rect x="970" y="106" width="424" height="16" fill="#41494d"/>
  <circle cx="1005" cy="95" r="7" fill="#ed0c4f"/><circle cx="1030" cy="95" r="7" fill="#f5a3bc"/>
  <circle cx="1182" cy="260" r="84" fill="#ed0c4f"/>
  <circle cx="1182" cy="260" r="72" fill="#fff"/>
  <path d="M1158 220l62 40-62 40z" fill="#ed0c4f"/>
  <rect x="1023" y="354" width="223" height="8" rx="4" fill="#687177"/>
  <rect x="1023" y="354" width="93" height="8" rx="4" fill="#ed0c4f"/>
  <circle cx="1116" cy="358" r="10" fill="#fff"/>
</svg>`;

async function build() {
  fs.writeFileSync(path.join(assets, 'icon.svg'), icon, 'utf8');
  const jobs = [128, 256].map((size) => sharp(Buffer.from(icon))
    .resize(size, size).png().toFile(path.join(assets, `icon-${size}x${size}.png`)));
  jobs.push(sharp(Buffer.from(banner)).png().toFile(path.join(assets, 'banner-1544x500.png')));
  jobs.push(sharp(Buffer.from(banner)).resize(772, 250).png().toFile(path.join(assets, 'banner-772x250.png')));
  await Promise.all(jobs);
  console.log('Built independent WordPress.org icon and banner assets.');
}

build().catch((error) => { console.error(error); process.exitCode = 1; });
