<?php
// UI page for Machine Config
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Config</title>
  <style>
    body { font-family: sans-serif; padding: 10px; }
    .field { margin-bottom: 8px; }
    label { display: inline-block; width: 100px; }
    input { width: 150px; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>
  <div class="field"><label for="line1">Line 1:</label><input id="line1" type="text"></div>
  <div class="field"><label for="line2">Line 2:</label><input id="line2" type="text"></div>
  <div class="field"><label for="line3">Line 3:</label><input id="line3" type="text"></div>
  <div class="field"><label for="line4">Line 4:</label><input id="line4" type="text"></div>
  <div class="field"><label for="color">Color:</label><input id="color" type="color" value="#FFFFFF"></div>
  <button id="saveBtn">Apply & Preview</button>

  <script>
    // Load existing settings from FPP
    function loadSettings() {
      fetch('/plugin/machine/settings')
        .then(response => response.json())
        .then(data => {
          document.getElementById('line1').value = data.line1 || '';
          document.getElementById('line2').value = data.line2 || '';
          document.getElementById('line3').value = data.line3 || '';
          document.getElementById('line4').value = data.line4 || '';
          document.getElementById('color').value = data.color || '#FFFFFF';
        });
    }

    // Save settings and trigger a preview update
    function saveSettings() {
      const payload = {
        line1: document.getElementById('line1').value,
        line2: document.getElementById('line2').value,
        line3: document.getElementById('line3').value,
        line4: document.getElementById('line4').value,
        color: document.getElementById('color').value
      };
      fetch('/plugin/machine/settings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(() => {
        // Request an overlay preview
        fetch('/plugin/machine/overlay?preview=1');
      });
    }

    // Initialize once DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
      loadSettings();
      document.getElementById('saveBtn').addEventListener('click', saveSettings);
    });
  </script>
</body>
</php
<?php
// UI page for Machine Config
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Config</title>
  <script src="../js/jquery.min.js"></script>
  <style>
    body { font-family: sans-serif; padding: 10px; }
    .field { margin-bottom: 8px; }
    label { display: inline-block; width: 100px; }
    input { width: 150px; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>
  <div class="field"><label for="line1">Line 1:</label><input id="line1" type="text"></div>
  <div class="field"><label for="line2">Line 2:</label><input id="line2" type="text"></div>
  <div class="field"><label for="line3">Line 3:</label><input id="line3" type="text"></div>
  <div class="field"><label for="line4">Line 4:</label><input id="line4" type="text"></div>
  <div class="field"><label for="color">Color:</label><input id="color" type="color" value="#FFFFFF"></div>
  <button id="saveBtn">Apply & Preview</button>

  <script>
    function loadSettings() {
      $.get('../plugin/machine/settings', function(data) {
        $('#line1').val(data.line1);
        $('#line2').val(data.line2);
        $('#line3').val(data.line3);
        $('#line4').val(data.line4);
        $('#color').val(data.color || '#FFFFFF');
      });
    }

    function saveSettings() {
      const payload = {
        line1: $('#line1').val(),
        line2: $('#line2').val(),
        line3: $('#line3').val(),
        line4: $('#line4').val(),
        color: $('#color').val()
      };
      $.ajax({
        url: '../plugin/machine/settings',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: () => $.get('../plugin/machine/overlay?preview=1')
      });
    }

    $(loadSettings);
    $('#saveBtn').click(saveSettings);
  </script>
</body>
</html>
