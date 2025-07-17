<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Machine Status Configuration</title>
  <script src="/js/jquery.min.js"></script>
  <style>
    body { font-family: sans-serif; padding: 15px; }
    h1, h2 { margin-top: 20px; }
    .field { margin-bottom: 12px; }
    label { display: inline-block; width: 120px; vertical-align: top; }
    select, input { width: 180px; }
    button { padding: 6px 10px; margin-right: 8px; }
    #previewCanvas { border: 1px solid #888; margin-top: 12px; display: block; }
  </style>
</head>
<body>
  <h1>Machine Status Configuration</h1>

  <h2>Overlay Model</h2>
  <div class="field">
    <label for="modelSel">Model:</label>
    <select id="modelSel"><option>Loading...</option></select>
  </div>
  <div class="field">
    <label for="modelWidth">Width:</label>
    <span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label for="modelHeight">Height:</label>
    <span id="modelHeight">–</span>
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
  <button id="previewBtn">Apply &amp; Preview</button>

  <script>
  $(function() {
    let modelConfig = null;

    function loadModels() {
      $.getJSON('/media/config/model-overlays.json')
        .done(function(cfg) {
          modelConfig = cfg;
          const sel = $('#modelSel').empty();
          if (!cfg.models.length) {
            sel.append($('<option disabled>').text('No models defined'));
            return;
          }
          cfg.models.forEach(function(m) {
            sel.append($('<option>').val(m.Name).text(m.Name));
          });
          sel.trigger('change');
        })
        .fail(function() {
          $('#modelSel').empty().append(
            $('<option disabled>').text('Error loading models')
          );
        });
    }

    function updateModelInfo(name) {
      if (!modelConfig) return;
      const m = modelConfig.models.find(x => x.Name === name);
      if (!m) return;
      const width = m.StringCount * m.StrandsPerString;
      const pixels = m.ChannelCount / m.ChannelCountPerNode;
      const height = pixels / width;
      $('#modelWidth').text(width + ' px');
      $('#modelHeight').text(height + ' px');
      $('#previewCanvas').attr({ width: width, height: height });
    }

    $('#modelSel').on('change', function() {
      const name = $(this).val();
      updateModelInfo(name);
    });

    $('#activateBtn').on('click', function() {
      const name = $('#modelSel').val();
      if (name) {
        $.post(
          '/rest/overlay/models/' + encodeURIComponent(name) + '/activate'
        );
      }
    });

    $('#deactivateBtn').on('click', function() {
      $.post('/rest/overlay/models/deactivate');
    });

    // Load saved settings
    $.getJSON('/plugin/machine/settings', function(data) {
      if (data.model) {
        $('#modelSel').val(data.model);
        updateModelInfo(data.model);
      }
      ['line1','line2','line3','line4'].forEach(function(id) {
        $('#' + id).val(data[id] || '');
      });
      $('#color').val(data.color || '#FFFFFF');
    });

    $('#previewBtn').on('click', function() {
      const payload = {
        model: $('#modelSel').val(),
        line1: $('#line1').val(),
        line2: $('#line2').val(),
        line3: $('#line3').val(),
        line4: $('#line4').val(),
        color: $('#color').val()
      };
      // Draw locally on canvas
      const canvas = document.getElementById('previewCanvas');
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = payload.color;
      ctx.font = '12px sans-serif';
      let y = 14;
      ['line1','line2','line3','line4'].forEach(function(id) {
        ctx.fillText(payload[id] || '', 0, y);
        y += 14;
      });
      // Save settings and trigger overlay hook
      $.ajax({
        url: '/plugin/machine/settings',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(payload)
      }).always(function() {
        $.get(
          '/plugin/machine/overlay?preview=1&model=' +
            encodeURIComponent(payload.model)
        );
      });
    });

    loadModels();
  });
  </script>
</body>
</html>
