(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  if (root) root.HtmlUtils = api;
})(typeof window !== 'undefined' ? window : globalThis, function () {
  'use strict';

  /**
   * Escape an untrusted value before interpolating it into an HTML template.
   * This changes only the rendered representation; it never mutates source data.
   */
  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>'"]/g, char => ({
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      "'": '&#39;',
      '"': '&quot;'
    })[char]);
  }

  return Object.freeze({ escapeHtml });
});
