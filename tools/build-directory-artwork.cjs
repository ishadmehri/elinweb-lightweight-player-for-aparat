// Run with Node.js and sharp. These graphics are independent of Aparat branding.
const fs = require('node:fs');
const path = require('node:path');
const sharp = require('sharp');

const assets = path.join(__dirname, '..', 'wordpress-org-assets');

const icon = `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="512" height="512" viewBox="0 0 512 512">
  <rect x="8" y="8" width="496" height="496" rx="112" fill="#20354c"/>
  <path d="M110 164h123M110 252h88M110 340h123" fill="none" stroke="#42c2b4" stroke-width="27" stroke-linecap="round"/>
  <path d="M257 145c0-13 15-21 26-14l138 102c16 12 16 34 0 46L283 381c-11 8-26 0-26-14V145z" fill="#ffffff"/>
  <path d="M299 207l69 49-69 49z" fill="#42c2b4"/>
</svg>`;

const banner = `<svg xmlns="http://www.w3.org/2000/svg" width="1544" height="500" viewBox="0 0 1544 500">
  <defs>
    <linearGradient id="background" x1="0" y1="0" x2="1" y2="1">
      <stop stop-color="#ffffff"/><stop offset="1" stop-color="#eefaf8"/>
    </linearGradient>
    <linearGradient id="card" x1="0" y1="0" x2="1" y2="1">
      <stop stop-color="#27425d"/><stop offset="1" stop-color="#172839"/>
    </linearGradient>
  </defs>
  <rect width="1544" height="500" fill="url(#background)"/>
  <circle cx="1501" cy="38" r="184" fill="#e3f6f2"/>
  <circle cx="1005" cy="506" r="228" fill="#e7f5f3"/>
  <rect x="112" y="81" width="74" height="74" rx="16" fill="#20354c"/>
  <path d="M130 106h17m-17 16h13m-13 16h17" fill="none" stroke="#42c2b4" stroke-width="5" stroke-linecap="round"/>
  <path d="M151 105l22 13-22 14z" fill="#fff"/>
  <text x="211" y="139" font-family="Arial,sans-serif" font-size="43" font-weight="bold" letter-spacing="3" fill="#20354c">ELINWEB</text>
  <text x="112" y="238" font-family="Arial,sans-serif" font-size="51" font-weight="bold" fill="#20354c">Lightweight Player</text>
  <text x="112" y="302" font-family="Arial,sans-serif" font-size="49" font-weight="bold" fill="#20354c">for Aparat</text>
  <text x="112" y="373" font-family="Vazir,Tahoma,sans-serif" font-size="33" fill="#3e6873">پخش سریع ویدئوهای آپارات در وردپرس</text>
  <rect x="110" y="409" width="175" height="42" rx="21" fill="#d9f5f0"/>
  <text x="133" y="438" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="#226e70">CLICK TO PLAY</text>
  <rect x="985" y="82" width="424" height="329" rx="26" fill="#cdeae5" opacity=".55"/>
  <rect x="970" y="68" width="424" height="329" rx="26" fill="url(#card)"/>
  <rect x="970" y="68" width="424" height="54" rx="26" fill="#324d62"/>
  <rect x="970" y="106" width="424" height="16" fill="#324d62"/>
  <circle cx="1005" cy="95" r="7" fill="#42c2b4"/><circle cx="1030" cy="95" r="7" fill="#bdd8dc"/>
  <circle cx="1182" cy="260" r="84" fill="#42c2b4"/>
  <circle cx="1182" cy="260" r="72" fill="#fff"/>
  <path d="M1158 220l62 40-62 40z" fill="#20354c"/>
  <rect x="1023" y="354" width="223" height="8" rx="4" fill="#55768c"/>
  <rect x="1023" y="354" width="93" height="8" rx="4" fill="#42c2b4"/>
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
