<?php
// machine_setup.php
// This page is wrapped by FPP header + footer (wrap=1)
?>
<style>
  .field { margin-bottom: 8px; }
  label { display: inline-block; width: 140px; }
  input, select, button { margin-right: 8px; }
</style>

<h1>Machine Status Configuration</h1>

<h2>Overlay Model</h2>
<div class="field">
  <label for="modelSel">Model:</label>
  <select id="modelSel"></select>
</div>
<div class="field">
  <button id="activateBtn">Activate</button>
  <button id="deactivateBtn">Deactivate</button>
</div>

<h2>Manual Data Preview</h2>
<div class="field">
  <label for="line1">Line 1:</label>
  <input id="line1" type="text">
</div>
<div class="field">
  <label for="line2">Line 2:</label>
  <input id="line2" type="text">
</div>
<div class="field">
  <label for="line3">Line 3:</label>
  <input id="line3" type="text">
</div>
<div class="field">
  <label for="line4">Line 4:</label>
  <input id="line4" type="text">
</div>
<div class="field">
  <label for="color">Color:</label>
  <input id="color" type="color" value="#FFFFFF">
</div>
<button id="saveBtn">Apply &amp; Preview</button>

<script>
// --- MODEL HANDLING (identical to Matrix Tools) ---
async function loadModels() {
  try {
    const res = await fetch('/rest/overlay/models');
    const models = await res.json();
    const sel = document.getElementById('modelSel');
    sel.innerHTML = '';
    models.forEach(m => {
      const opt = document.createElement('option');
      opt.value = m;
      opt.text  = m;
      sel.add(opt);
    });
  } catch(e) {
    console.error('Error loading overlay models:', e);
  }
}

async function activateModel() {
  const model = document.getElementById('modelSel').value;
  await fetch(`/rest/overlay/models/${model}/activate`, { method: 'POST' });
}
async function deactivateModel() {
  await fetch('/rest/overlay/models/deactivate`, { method: 'POST' });
}

// --- MANUAL PREVIEW HANDLING ---
async function loadSettings() {
  try {
    const res = await fetch('/plugin/machine/settings');
    const data = await res.json();
    ['line1','line2','line3','line4'].forEach(id => {
      document.getElementById(id).value = data[id] || '';
    });
    document.getElementById('color').value = data.color || '#FFFFFF';
  } catch(e) {
    console.error('Error loading settings:', e);
  }
}

async function saveAndPreview() {
  const payload = {};
  ['line1','line2','line3','line4'].forEach(id => {
    payload[id] = document.getElementById(id).value;
  });
  payload.color = document.getElementById('color').value;

  await fetch('/plugin/machine/settings', {
    method: 'POST',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify(payload)
  });
  // trigger the overlay hook with preview=1
  await fetch('/plugin/machine/overlay?preview=1');
}

document.addEventListener('DOMContentLoaded', () => {
  loadModels();
  loadSettings();
  document.getElementById('activateBtn').addEventListener('click', activateModel);
  document.getElementById('deactivateBtn').addEventListener('click', deactivateModel);
  document.getElementById('saveBtn').addEventListener('click', saveAndPreview);
});
</script>
