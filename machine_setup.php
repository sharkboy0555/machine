<?php
// machine_setup.php — full HTML page wrapped by FPP header/footer
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Config</title>
  <script src="../js/jquery.min.js"></script>
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
</head>
<body>
  <h1>Machine Status Configuration</h1>

  <h2>Overlay Model</h2>
  <div class="field">
    <label for="modelSel">Model:</label>
    <select id="modelSel"></select>
  </div>
  <div class="field">
    <label for="modelWidth">Width:</label><span id="modelWidth"></span>
  </div>
  <div class="field">
    <label for="modelHeight">Height:</label><span id="modelHeight"></span>
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
    // Load overlay models via FPP REST
    function loadModels() {
      $.getJSON('/rest/overlay/models', models => {
        const sel = $('#modelSel').empty();
        models.forEach(m => sel.append($('<option>').val(m).text(m)));
        sel.change(); // initial display
      });
    }

    // Display model dimensions and resize canvas
    function showModelInfo() {
      const model = $('#modelSel').val();
      if (!model) return;
      $.getJSON(`/rest/overlay/models/${model}`, info => {
        $('#modelWidth').text(info.pixelCountX + ' px');
        $('#modelHeight').text(info.pixelCountY + ' px');
        const canvas = document.getElementById('previewCanvas');
        canvas.width = info.pixelCountX;
        canvas.height = info.pixelCountY;
      });
    }

    // Activate/deactivate overlay model
    function activateModel() {
      const model = $('#modelSel').val();
      $.post(`/rest/overlay/models/${model}/activate`);
    }
    function deactivateModel() {
      $.post('/rest/overlay/models/deactivate');
    }

    // Load manual settings
    function loadSettings() {
      $.getJSON('/plugin/machine/settings', data => {
        ['line1','line2','line3','line4'].forEach(id => $('#' + id).val(data[id] || ''));
        $('#color').val(data.color || '#FFFFFF');
      });
    }

    // Draw onto canvas and send preview overlay
    function saveAndPreview() {
      const canvas = document.getElementById('previewCanvas');
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = $('#color').val() || '#FFFFFF';
      ctx.font = '12px sans-serif';
      let y = 14;
      ['line1','line2','line3','line4'].forEach(id => {
        ctx.fillText($('#' + id).val() || '', 0, y);
        y += 14;
      });
      const dataURL = canvas.toDataURL().split(',')[1];
      const model = $('#modelSel').val();
      $.ajax({
        url: `/plugin/machine/overlay?preview=1`,
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ model: model, data: dataURL }),
      });
    }

    $(document).ready(() => {
      $('#modelSel').on('change', showModelInfo);
      $('#activateBtn').on('click', activateModel);
      $('#deactivateBtn').on('click', deactivateModel);
      $('#saveBtn').on('click', saveAndPreview);
      loadModels();
      loadSettings();
    });
  </script>
</body>
</html>
