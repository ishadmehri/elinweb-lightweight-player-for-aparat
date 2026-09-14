(function () {
  'use strict';
  if (window.dadsooAparatPerformanceLoaded) return;
  window.dadsooAparatPerformanceLoaded = true;
  document.addEventListener('click', function (event) {
    if (!(event.target instanceof Element)) return;
    const trigger = event.target.closest('.dso-ap__trigger');
    if (!trigger || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
    const container = trigger.closest('[data-dso-aparat]');
    const hash = container && container.dataset.dsoAparat;
    if (!hash || !/^[a-zA-Z0-9]{1,40}$/.test(hash)) return;
    event.preventDefault();
    let options = {};
    try { options = JSON.parse(container.dataset.playerOptions || '{}') || {}; } catch (_) {}
    if (container.dataset.playerType === 'native' && container.dataset.streamUrl) {
      const video = document.createElement('video');
      video.controls = true;
      video.playsInline = true;
      video.autoplay = true;
      video.preload = 'auto';
      video.muted = options.muted === true;
      video.title = container.dataset.videoTitle || 'ویدئوی آپارات';
      video.setAttribute('aria-label', video.title);
      const poster = container.querySelector('.dso-ap__poster');
      if (poster) video.poster = poster.currentSrc || poster.src;
      Object.assign(video.style, { position: 'absolute', inset: '0', width: '100%', height: '100%', objectFit: 'contain', background: '#000' });
      if (Number.isInteger(options.startTime) && options.startTime > 0 && options.startTime <= 2147483647) {
        video.addEventListener('loadedmetadata', function () {
          const seconds = Number.isFinite(video.duration) ? Math.min(options.startTime, Math.max(0, video.duration - 0.1)) : options.startTime;
          video.currentTime = seconds;
        }, { once: true });
      }
      function startPlayback() {
        if (finished) return;
        const playback = video.play();
        if (playback && playback.catch) playback.catch(function (error) {
          if (!finished && error.name === 'NotAllowedError' && !video.muted) {
            video.muted = true;
            const retry = video.play();
            if (retry && retry.catch) retry.catch(function () {});
          }
        });
      }
      let retried = false;
      let finished = false;
      let loadingTimeout;
      function armTimeout() {
        clearTimeout(loadingTimeout);
        loadingTimeout = setTimeout(handleError, 15000);
      }
      video.addEventListener('loadeddata', function () { clearTimeout(loadingTimeout); });
      function handleError() {
        if (finished) return;
        clearTimeout(loadingTimeout);
        // Retry through the other WordPress entry point automatically, without another click.
        if (!retried && container.dataset.streamFallback) {
          retried = true;
          video.src = container.dataset.streamFallback;
          armTimeout();
          startPlayback();
          return;
        }
        finished = true;
        video.pause();
        video.removeAttribute('src');
        video.load();
        const retry = trigger.cloneNode(true);
        retry.setAttribute('aria-label', 'تلاش دوباره برای پخش ویدئو');
        const status = document.createElement('div');
        status.setAttribute('role', 'status');
        status.textContent = 'بارگذاری ویدئو ناموفق بود. برای تلاش دوباره روی آیکون پخش بزنید.';
        Object.assign(status.style, { position: 'absolute', bottom: '12px', insetInline: '12px', padding: '8px', borderRadius: '6px', background: 'rgba(0,0,0,.85)', color: '#fff', fontSize: '13px', textAlign: 'center', pointerEvents: 'none' });
        container.replaceChildren(retry, status);
        // Report API/host failures separately from CDN/media errors. No navigation or media download.
        if (typeof fetch === 'function' && container.dataset.streamUrl.includes('admin-ajax.php')) {
          fetch(container.dataset.streamUrl + '&format=json', { cache: 'no-store', credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (status.parentNode === container && typeof data.error === 'string') status.textContent = data.error + ' برای تلاش دوباره روی آیکون پخش بزنید.';
            }).catch(function () {});
        }
      }
      video.addEventListener('error', handleError);
      video.src = container.dataset.streamUrl;
      container.replaceChildren(video);
      video.focus();
      armTimeout();
      // Invoke inside the original click, before waiting for any request or event.
      startPlayback();
      return;
    }
    const iframe = document.createElement('iframe');
    iframe.title = container.dataset.videoTitle || 'ویدئوی آپارات';
    iframe.allow = 'autoplay; fullscreen; picture-in-picture';
    iframe.allowFullscreen = true;
    Object.assign(iframe.style, { position: 'absolute', inset: '0', width: '100%', height: '100%', border: '0' });
    const query = new URLSearchParams();
    query.set('autoplay', 'true');
    ['muted', 'titleShow'].forEach(function (key) {
      if (typeof options[key] === 'boolean') query.set(key, String(options[key]));
    });
    if (Number.isInteger(options.startTime) && options.startTime >= 0 && options.startTime <= 2147483647) {
      query.set('startTime', String(options.startTime));
    }
    if (options.recom === 'self') query.set('recom', 'self');
    const suffix = query.toString();
    iframe.src = 'https://www.aparat.com/video/video/embed/videohash/' + encodeURIComponent(hash) + '/vt/frame' + (suffix ? '?' + suffix : '');
    container.replaceChildren(iframe);
    iframe.focus();
  });
})();
