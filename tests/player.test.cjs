const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const script = fs.readFileSync(path.join(__dirname, '../assets/player.js'), 'utf8');

function environment() {
  class Element {}
  const listeners = [];
  const frames = [];
  const document = {
    addEventListener(type, callback) { listeners.push({ type, callback }); },
    createElement(tag) {
      const frame = { tag, style: {}, events: {}, playCalls: 0, duration: 89,
        setAttribute() {}, removeAttribute() {}, pause() {}, load() {}, addEventListener(type, fn) { this.events[type] = fn; },
        play() { this.playCalls++; return Promise.resolve(); }, focused: false, focus() { this.focused = true; } };
      frames.push(frame);
      return frame;
    }
  };
  const timers = [];
  const context = vm.createContext({ window: {}, document, Element, encodeURIComponent, URLSearchParams,
    setTimeout(fn) { timers.push(fn); return timers.length; }, clearTimeout() {} });
  vm.runInContext(script, context);
  function video(hash, options) {
    const container = { dataset: { lwpaAparat: hash, videoTitle: 'عنوان', playerOptions: options, playerType: 'aparat' }, querySelector() { return null; },
      children: ['poster'], replaceChildren(...children) { this.children = children; children.forEach(child => { if (typeof child === 'object') child.parentNode = this; }); } };
    const trigger = { closest() { return container; }, cloneNode() { return { setAttribute() {} }; } };
    const target = new Element();
    target.closest = () => trigger;
    return { container, target };
  }
  function click(target, overrides = {}) {
    const event = { target, button: 0, prevented: false, preventDefault() { this.prevented = true; }, ...overrides };
    listeners[0].callback(event);
    return event;
  }
  return { context, listeners, frames, video, click, timers };
}

test('no player before click; only clicked video changes; dynamic second video works', () => {
  const env = environment();
  const one = env.video('ytf50k5');
  const two = env.video('abc123');
  assert.equal(env.frames.length, 0);
  assert.equal(env.click(one.target).prevented, true);
  assert.equal(env.frames.length, 1);
  assert.equal(env.frames[0].src, 'https://www.aparat.com/video/video/embed/videohash/ytf50k5/vt/frame?autoplay=true');
  assert.equal(env.frames[0].focused, true);
  assert.equal(env.frames[0].allowFullscreen, true);
  assert.deepEqual(two.container.children, ['poster']);
  const dynamic = env.video('new456');
  env.click(dynamic.target);
  assert.equal(env.frames.length, 2);
});
test('modified clicks preserve direct link and invalid hashes cannot create players', () => {
  const env = environment();
  const video = env.video('ytf50k5');
  assert.equal(env.click(video.target, { ctrlKey: true }).prevented, false);
  assert.equal(env.click(video.target, { button: 1 }).prevented, false);
  const bad = env.video('bad/../hash');
  assert.equal(env.click(bad.target).prevented, false);
  assert.equal(env.frames.length, 0);
});
test('runtime registered twice still has one delegated listener', () => {
  const env = environment();
  vm.runInContext(script, env.context);
  assert.equal(env.listeners.length, 1);
});
test('legacy cached markup still starts native playback', () => {
  const env = environment();
  const one = env.video('ytf50k5');
  delete one.container.dataset.lwpaAparat;
  one.container.dataset.dsoAparat = 'ytf50k5';
  one.container.dataset.playerType = 'native';
  one.container.dataset.streamUrl = '/wp-admin/admin-ajax.php?action=dadsoo_aparat_stream&hash=ytf50k5';
  assert.equal(env.click(one.target).prevented, true);
  assert.equal(env.frames[0].tag, 'video');
  assert.equal(env.frames[0].playCalls, 1);
});
test('playback options are forwarded only after click without loading other videos', () => {
  const env = environment();
  const one = env.video('ytf50k5', JSON.stringify({ titleShow: true, startTime: 65, muted: true, recom: 'self' }));
  const two = env.video('ytf50k5', JSON.stringify({ titleShow: false, startTime: 0, muted: false }));
  assert.equal(env.frames.length, 0);
  env.click(one.target);
  const url = new URL(env.frames[0].src);
  assert.equal(url.searchParams.get('muted'), 'true');
  assert.equal(url.searchParams.get('titleShow'), 'true');
  assert.equal(url.searchParams.get('startTime'), '65');
  assert.equal(url.searchParams.get('recom'), 'self');
  assert.deepEqual(two.container.children, ['poster']);
  env.click(two.target);
  const second = new URL(env.frames[1].src);
  assert.equal(second.searchParams.get('muted'), 'false');
  assert.equal(second.searchParams.get('titleShow'), 'false');
  assert.equal(second.searchParams.get('startTime'), '0');
  assert.equal(second.searchParams.has('recom'), false);
});
test('malformed or unapproved player options cannot alter the embed destination', () => {
  const env = environment();
  env.click(env.video('ytf50k5', '{bad json').target);
  assert.equal(new URL(env.frames[0].src).search, '?autoplay=true');
  env.click(env.video('ytf50k5', JSON.stringify({ src: 'https://evil.test', muted: 'true', titleShow: [], startTime: -1, recom: 'evil' })).target);
  assert.equal(new URL(env.frames[1].src).hostname, 'www.aparat.com');
  assert.equal(new URL(env.frames[1].src).search, '?autoplay=true');
});
test('native playback starts during the first click; no iframe or second click; start time preserved', () => {
  const env = environment();
  const one = env.video('ytf50k5', JSON.stringify({ muted: true, startTime: 65 }));
  one.container.dataset.playerType = 'native';
  one.container.dataset.streamUrl = 'http://localhost/wp-json/lightweight-player/v1/stream/ytf50k5';
  assert.equal(env.frames.length, 0);
  env.click(one.target);
  assert.equal(env.frames.length, 1);
  const video = env.frames[0];
  assert.equal(video.tag, 'video');
  assert.equal(video.playCalls, 1);
  assert.equal(video.src, one.container.dataset.streamUrl);
  assert.equal(video.controls, true);
  assert.equal(video.muted, true);
  video.events.loadedmetadata();
  assert.equal(video.currentTime, 65);
});
test('failed primary route retries alternative automatically, preserving one-click playback', () => {
  const env = environment();
  const one = env.video('ytf50k5');
  one.container.dataset.playerType = 'native';
  one.container.dataset.streamUrl = '/wp-admin/admin-ajax.php?action=lwpa_aparat_stream&hash=ytf50k5';
  one.container.dataset.streamFallback = '/wp-json/lightweight-player/v1/stream/ytf50k5';
  env.click(one.target);
  const video = env.frames[0];
  video.events.error();
  assert.equal(video.src, one.container.dataset.streamFallback);
  assert.equal(video.playCalls, 2);
  assert.equal(one.container.children[0], video);
});
test('unavailable video restores retry icon and status; a second click retries without navigation', () => {
  const env = environment();
  const one = env.video('ytf50k5');
  one.container.dataset.playerType = 'native';
  one.container.dataset.streamUrl = '/stream';
  env.click(one.target);
  env.frames[0].events.error();
  assert.equal(one.container.dataset.playerType, 'native');
  assert.equal(one.container.children.length, 2);
  assert.match(one.container.children[1].textContent, /بارگذاری ویدئو ناموفق/);
  assert.equal(env.click(one.target).prevented, true);
  assert.equal(env.frames[2].tag, 'video');
});
test('stalled initial media load retries automatically and eventually reports failure', () => {
  const env = environment();
  const one = env.video('ytf50k5');
  one.container.dataset.playerType = 'native';
  one.container.dataset.streamUrl = '/stream';
  one.container.dataset.streamFallback = '/backup';
  env.click(one.target);
  env.timers[0]();
  assert.equal(env.frames[0].src, '/backup');
  env.timers[1]();
  assert.match(one.container.children[1].textContent, /بارگذاری ویدئو ناموفق/);
});
