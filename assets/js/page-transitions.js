(function () {
  'use strict';

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  if (!document.querySelector('.ib-app')) return;

  const SAME_PAGE = new Set(['#', 'javascript:void(0)']);
  const delay = 130;

  document.addEventListener('click', function (event) {
    const link = event.target.closest('a[href]');
    if (!link) return;
    if (event.defaultPrevented || event.button !== 0) return;
    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target && link.target !== '_self') return;
    if (link.hasAttribute('download')) return;

    const href = link.getAttribute('href') || '';
    if (SAME_PAGE.has(href.trim())) return;

    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) return;
    if (url.pathname === window.location.pathname && url.search === window.location.search && url.hash) return;

    event.preventDefault();
    document.body.classList.add('ib-page-leaving');
    window.setTimeout(function () {
      window.location.href = url.href;
    }, delay);
  });

  window.addEventListener('pageshow', function () {
    document.body.classList.remove('ib-page-leaving');
  });
})();
