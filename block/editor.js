(function (wp) {
  'use strict';
  const el = wp.element.createElement;
  const Fragment = wp.element.Fragment;
  const { useState, useEffect } = wp.element;
  const { TextControl, SelectControl, ToggleControl, PanelBody, Button, Notice, Spinner } = wp.components;
  const { InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } = wp.blockEditor;

  function sourceOptions(input) {
    try {
      const match = input.match(/<(?:iframe|script)\b[^>]*\bsrc\s*=\s*(["'])(.*?)\1/i);
      const url = new URL((match ? match[2] : input).replace(/&amp;/g, '&'));
      if (!['aparat.com', 'www.aparat.com'].includes(url.hostname)) return {};
      const options = {};
      ['muted', 'titleShow'].forEach(function (key) {
        const value = url.searchParams.get(key);
        if (value === 'true' || value === '1') options[key] = true;
        if (value === 'false' || value === '0') options[key] = false;
      });
      const time = url.searchParams.get('startTime');
      if (time !== null && /^\d+$/.test(time) && Number(time) <= 2147483647) options.startTime = Number(time);
      if (url.searchParams.get('recom') === 'self') options.recom = 'self';
      return options;
    } catch (_) { return {}; }
  }

  wp.blocks.registerBlockType('dadsoo/aparat-performance', {
    edit: function Edit(props) {
      const a = props.attributes;
      const set = props.setAttributes;
      const [metadata, setMetadata] = useState(null);
      const [busy, setBusy] = useState(false);
      const [error, setError] = useState('');
      useEffect(function () {
        let cancelled = false;
        setMetadata(null);
        setError('');
        setBusy(false);
        if (!a.url.trim()) return;
        const timer = setTimeout(function () {
          setBusy(true);
          wp.apiFetch({ path: '/dadsoo-aparat/v1/resolve', method: 'POST', data: { url: a.url } })
            .then(function (data) { if (!cancelled) setMetadata(data); })
            .catch(function (err) { if (!cancelled) setError(err.message || 'دریافت پوستر ممکن نشد.'); })
            .finally(function () { if (!cancelled) setBusy(false); });
        }, 600);
        return function () { cancelled = true; clearTimeout(timer); };
      }, [a.url]);

      const poster = a.posterId ? a.posterURL : (metadata && metadata.posterUrl);
      const inherited = sourceOptions(a.url);
      const muted = a.muted === undefined ? (inherited.muted ?? false) : a.muted;
      const titleShow = a.titleShow === undefined ? (inherited.titleShow ?? true) : a.titleShow;
      const startTime = a.startTime === undefined ? (inherited.startTime ?? 0) : a.startTime;
      const recom = a.recom === undefined ? (inherited.recom || 'default') : a.recom;
      return el(Fragment, null,
        el(InspectorControls, null,
          el(PanelBody, { title: 'تنظیمات ویدئو', initialOpen: true },
            el(TextControl, { label: 'عنوان دلخواه (اختیاری)', value: a.title, onChange: function (title) { set({ title }); } }),
            el(SelectControl, { label: 'نسبت تصویر', value: a.ratio,
              options: [{ label: 'افقی 16:9', value: '16/9' }, { label: 'عمودی 9:16', value: '9/16' },
                { label: 'مربع 1:1', value: '1/1' }, { label: '4:3', value: '4/3' }],
              onChange: function (ratio) { set({ ratio }); } }),
            el(ToggleControl, { label: 'پوستر در ابتدای صفحه است', checked: a.aboveFold,
              help: 'فقط اگر بدون اسکرول دیده می‌شود، روشن کنید.', onChange: function (aboveFold) { set({ aboveFold }); } }),
            el(MediaUploadCheck, null, el(MediaUpload, { allowedTypes: ['image'], value: a.posterId,
              onSelect: function (media) { set({ posterId: media.id, posterURL: media.url }); },
              render: function (media) { return el(Button, { variant: 'secondary', onClick: media.open }, 'پوستر جایگزین (اختیاری)'); } })),
            a.posterId ? el(Button, { variant: 'tertiary', onClick: function () { set({ posterId: 0, posterURL: '' }); } }, 'استفاده از پوستر خودکار') : null
          ),
          el(PanelBody, { title: 'تنظیمات پخش', initialOpen: true },
            el(SelectControl, { label: 'پلیر ویدئو', value: a.playerType || 'native',
              options: [{ label: 'پلیر مرورگر؛ پخش تک‌کلیک', value: 'native' }, { label: 'پلیر رسمی آپارات', value: 'aparat' }],
              onChange: function (playerType) { set({ playerType }); },
              help: 'پلیر مرورگر فایل ویدئو را پس از کلیک پخش می‌کند. امکانات پیشنهاد ویدئو مخصوص پلیر رسمی آپارات‌اند.' }),
            el(TextControl, { label: 'شروع پخش از (ثانیه)', type: 'number', min: 0, max: 2147483647, step: 1, value: startTime,
              help: '۶۵ یعنی ۱ دقیقه و ۵ ثانیه.',
              onChange: function (value) { set({ startTime: value === '' ? undefined : Math.min(2147483647, Math.max(0, parseInt(value, 10) || 0)) }); } }),
            el(ToggleControl, { label: 'پخش اولیه بی‌صدا', checked: muted, onChange: function (value) { set({ muted: value }); } }),
            a.playerType === 'aparat' ? el(ToggleControl, { label: 'نمایش عنوان و آیکون‌های پلیر', checked: titleShow, onChange: function (value) { set({ titleShow: value }); } }) : null,
            a.playerType === 'aparat' ? el(SelectControl, { label: 'پیشنهادهای پایان ویدئو', value: recom,
              options: [{ label: 'پیش‌فرض آپارات', value: 'default' }, { label: 'فقط ویدئوهای همین کانال', value: 'self' }],
              onChange: function (value) { set({ recom: value }); } }) : null,
            el(Button, { variant: 'tertiary', onClick: function () { set({ muted: undefined, titleShow: undefined, startTime: undefined, recom: undefined }); } }, 'بازنشانی به تنظیمات لینک')
          )
        ),
        el('div', useBlockProps(),
          el(TextControl, { label: 'لینک آپارات', placeholder: 'https://www.aparat.com/v/ytf50k5', value: a.url,
            onChange: function (url) { set({ url }); }, help: 'پوستر خودکار در رسانه‌های وردپرس ذخیره می‌شود.' }),
          busy ? el(Spinner) : null,
          error ? el(Notice, { status: 'warning', isDismissible: false }, error) : null,
          a.url ? el('div', { className: 'dso-ap', style: { aspectRatio: a.ratio } },
            el('div', { className: 'dso-ap__trigger' },
              poster ? el('img', { className: 'dso-ap__poster', src: poster, alt: '' }) : null,
              el('span', { className: 'dso-ap__label', 'aria-hidden': true },
                el('svg', { width: 32, height: 32, viewBox: '0 0 24 24', focusable: false },
                  el('path', { fill: 'currentColor', d: 'M8 5v14l11-7z' })
                )
              )
            )
          ) : null,
          metadata ? el('p', null, a.title || metadata.title) : null
        )
      );
    },
    save: function () { return null; }
  });
})(window.wp);
