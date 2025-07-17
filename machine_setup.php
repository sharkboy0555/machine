<?php
// UI page for Machine Config
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Status Configuration</title>
  <style>
    body { font-family: sans-serif; padding: 10px; }
    .field { margin-bottom: 8px; }
    label { display: inline-block; width: 140px; }
    input, select { width: 160px; }
    button { padding: 6px 12px; margin-right: 8px; }
    h2 { margin-top: 20px; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>

  <h2>Overlay Model</h2>
  <div class="field">
    <label for="modelSel">Select Model:</label>
    <select id="modelSel"></select>
  </div>
  <div>
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
    // Load available overlay models from FPP REST API
    async function loadModels() {
      try {
        const res = await fetch('/rest/overlay/models');
        const models = await res.json();
        const sel = document.getElementById('modelSel');
        models.forEach(m => {
          const opt = document.createElement('option');
          opt.value = m;
          opt.text = m;
          sel.add(opt);
        });
      } catch (e) {
        console.error('Error loading models:', e);
      }
    }

    // Activate selected model
    async function activateModel() {
      const model = document.getElementById('modelSel').value;
      try {
        await fetch(`/rest/overlay/models/${model}/activate`, { method: 'POST' });
      } catch (e) {
        console.error('Failed to activate model:', e);
      }
    }

    // Deactivate any active model
    async function deactivateModel() {
      try {
        await fetch('/rest/overlay/models/deactivate', { method: 'POST' });
      } catch (e) {
        console.error('Failed to deactivate model:', e);
      }
    }

    // Load manual settings
    async function loadSettings() {
      try {
        const res = await fetch('/plugin/machine/settings');
        const data = await res.json();
        document.getElementById('line1').value = data.line1 || '';
        document.getElementById('line2').value = data.line2 || '';
        document.getElementById('line3').value = data.line3 || '';
        document.getElementById('line4').value = data.line4 || '';
        document.getElementById('color').value = data.color || '#FFFFFF';
      } catch (e) {
        console.error('Failed to load settings:', e);
      }
    }

    // Save manual settings and preview overlay
    async function saveSettings() {
      const payload = {
        line1: document.getElementById('line1').value,
        line2: document.getElementById('line2').value,
        line3: document.getElementById('line3').value,
        line4: document.getElementById('line4').value,
        color: document.getElementById('color').value
      };
      try {
        await fetch('/plugin/machine/settings', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        // trigger preview
        await fetch('/plugin/machine/overlay?preview=1');
      } catch (e) {
        console.error('Failed to save settings or trigger preview:', e);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadModels();
      loadSettings();
      document.getElementById('activateBtn').addEventListener('click', activateModel);
      document.getElementById('deactivateBtn').addEventListener('click', deactivateModel);
      document.getElementById('saveBtn').addEventListener('click', saveSettings);
    });
  </script>
</body>
</html>
```php
<?php
// UI page for Machine Config
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Status Configuration</title>
  <style>
    body { font-family: sans-serif; padding: 10px; }
    .field { margin-bottom: 8px; }
    label { display: inline-block; width: 100px; }
    input { width: 150px; }
    button { padding: 6px 12px; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>

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
    // Load existing settings
    async function loadSettings() {
      try {
        const res = await fetch('/plugin/machine/settings');
        const data = await res.json();
        document.getElementById('line1').value = data.line1 || '';
        document.getElementById('line2').value = data.line2 || '';
        document.getElementById('line3').value = data.line3 || '';
        document.getElementById('line4').value = data.line4 || '';
        document.getElementById('color').value = data.color || '#FFFFFF';
      } catch (e) {
        console.error('Failed to load settings:', e);
      }
    }

    // Save settings and preview
    async function saveSettings() {
      const payload = {
        line1: document.getElementById('line1').value,
        line2: document.getElementById('line2').value,
        line3: document.getElementById('line3').value,
        line4: document.getElementById('line4').value,
        color: document.getElementById('color').value
      };
      try {
        await fetch('/plugin/machine/settings', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        // trigger preview
        await fetch('/plugin/machine/overlay?preview=1');
      } catch (e) {
        console.error('Failed to save settings or trigger preview:', e);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadSettings();
      document.getElementById('saveBtn').addEventListener('click', saveSettings);
    });
  </script>
</body>
</html>
