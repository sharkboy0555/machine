<?php
// machine_setup.php — full HTML page wrapped by FPP header/footer
?>
<style>
  body { font-family: sans-serif; padding: 15px; }
  h1, h2 { margin-top: 20px; }
  .field { margin-bottom: 12px; }
  label { display: inline-block; width: 120px; vertical-align: top; }
  input, select { width: 180px; }
  span { font-weight: bold; margin-left: 6px; }
  button { padding: 6px 10px; margin-right: 8px; }
  #previewCanvas { border: 1px solid #888; margin-top: 12px; display: block; }
</style>

<h1>Machine Status Configuration</h1>

<h2>Overlay Model</h2>
<div class="field">
  <label for="modelSel">Model:</label>
  <select id="modelSel"></select>
</div>
<div class="field">
  <label>Width:</label><span id="modelWidth"></span>
</div>
<div class="field">
  <label>Height:</label><span id="modelHeight"></span>
</div>
<div class="field">
  <button id="activateBtn">Activate</button>
  <button id="deactivateBtn">Deactivate</button>
</div>

<h2>Overlay Preview</h2>
<canvas id="previewCanvas"></canvas>

<h2>Manual Data Preview</h2>
<div class="field"><label for="line1">Line 1:</label><input id="line1" type="text"></div>
<div class="field"><label for="line2">Line 2:</label><input id="line2" type="text"></div>
<div class="field"><label for="line3">Line 3:</label><input id="line3" type="text"></div>
<div class="field"><label for="line4">Line 4:</label><input id="line4" type="text"></div>
<div class="field"><label for="color">Color:</label><input id="color" type="color" value="#FFFFFF"></div>
<button id="saveBtn">Apply &amp; Preview</button>

<script>
// Helper to try both REST and API endpoints
async function tryEndpoint(rest, api) {
  try {
    const r = await fetch(rest);
    if (!r.ok) throw new Error('REST failed');
    return await r.json();
  } catch {
    const r2 = await fetch(api);
    if (!r2.ok) throw new Error('API failed');
    return await r2.json();
  }
}

// Load overlay models
async function loadModels() {
  try {
    const models = await tryEndpoint(
      '/rest/overlay/models',
      '/api/overlay/models'
    );
    const sel = document.getElementById('modelSel');
    sel.innerHTML = '';
    if (!models.length) {
      const opt = document.createElement('option');
      opt.disabled = true;
      opt.text = 'No models defined';
      sel.add(opt);
      return;
    }
    models.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m;
      opt.text = m;
      sel.add(opt);
    });
    showModelInfo();
  } catch (e) {
    console.error('Error loading models', e);
    const sel = document.getElementById('modelSel');
    sel.innerHTML = '';
    const opt = document.createElement('option');
    opt.disabled = true;
    opt.text = 'Error loading models';
    sel.add(opt);
  }
}

// Display model metadata and resize canvas
async function showModelInfo() {
  const model = document.getElementById('modelSel').value;
  if (!model) return;
  try {
    const info = await tryEndpoint(
      '/rest/overlay/models/' + encodeURIComponent(model),
      '/api/overlay/models/' + encodeURIComponent(model)
    );
    document.getElementById('modelWidth').textContent = info.pixelCountX + ' px';
    document.getElementById('modelHeight').textContent = info.pixelCountY + ' px';
    const c = document.getElementById('previewCanvas');
    c.width = info.pixelCountX;
    c.height = info.pixelCountY;
  } catch (e) {
    console.error('Error loading model info', e);
  }
}

// Activate/deactivate model
async function activateModel() {
  const model = document.getElementById('modelSel').value;
  if (!model) return;
  try {
    await fetch(
      '/rest/overlay/models/' + encodeURIComponent(model) + '/activate',
      { method: 'POST' }
    );
  } catch {
    await fetch(
      '/api/overlay/models/' + encodeURIComponent(model) + '/activate',
      { method: 'POST' }
    );
  }
}
async function deactivateModel() {
  try {
    await fetch('/rest/overlay/models/deactivate', { method: 'POST' });
  } catch {
    await fetch('/api/overlay/models/deactivate', { method: 'POST' });
  }
}

// Load saved settings
async function loadSettings() {
  try {
    const r = await fetch('/plugin/machine/settings');
    const data = await r.json();
    ['line1','line2','line3','line4'].forEach(id => {
      document.getElementById(id).value = data[id] || '';
    });
    document.getElementById('color').value = data.color || '#FFFFFF';
  } catch (e) {
    console.error('Error loading settings', e);
  }
}

// Draw and preview overlay
async function saveAndPreview() {
  const c = document.getElementById('previewCanvas');
  const ctx = c.getContext('2d');
  ctx.clearRect(0, 0, c.width, c.height);
  ctx.fillStyle = document.getElementById('color').value || '#FFFFFF';
  ctx.font = '12px sans-serif';
  let y = 14;
  ['line1','line2','line3','line4'].forEach(id => {
    ctx.fillText(document.getElementById(id).value || '', 0, y);
    y += 14;
  });
  const dataURL = c.toDataURL().split(',')[1];
  const model = document.getElementById('modelSel').value;
  await fetch('/plugin/machine/overlay?preview=1', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ model: model, data: dataURL })
  });
}

// Wire up events
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('modelSel').addEventListener('change', showModelInfo);
  document.getElementById('activateBtn').addEventListener('click', activateModel);
  document.getElementById('deactivateBtn').addEventListener('click', deactivateModel);
  document.getElementById('saveBtn').addEventListener('click', saveAndPreview);
  loadModels();
  loadSettings();
});
</script>
