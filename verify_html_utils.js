const assert = require('node:assert/strict');
const { escapeHtml } = require('./html_utils.js');

assert.equal(escapeHtml(null), '');
assert.equal(escapeHtml(undefined), '');
assert.equal(escapeHtml('ข้อความไทย'), 'ข้อความไทย');
assert.equal(
  escapeHtml(`<img src=x onerror="alert('x')"> &`),
  '&lt;img src=x onerror=&quot;alert(&#39;x&#39;)&quot;&gt; &amp;'
);
assert.equal(escapeHtml('"quoted" <tag>'), '&quot;quoted&quot; &lt;tag&gt;');

console.log('HTML escaping checks passed');
