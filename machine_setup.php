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
    <label for="modelWidth">Width:</label><span id="modelWidth">–</span>
  </div>
  <div class="field">
    <label for="modelHeight">Height:</label><span id="modelHeight">–</span>
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
    // Try REST endpoint or fallback to static JSON
    function loadModels() {
      $.getJSON('/api/overlays/models')
        .done(function(models) {
          populateModels(models.map(function(m){ return m.Name || m; }));
        })
        .fail(function() {
          // Fallback: read config file directly
          $.getJSON('/media/config/model-overlays.json')
            .done(function(cfg) {
              var names = cfg.models.map(function(m){ return m.Name; });
              populateModels(names);
            })
            .fail(function() {
              $('#modelSel').empty().append($('<option disabled>').text('Error loading models'));
            });
        });
    }

    function populateModels(names) {
      var sel = $('#modelSel').empty();
      if (!names.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        names.forEach(function(name) {
          sel.append($('<option>').val(name).text(name));
        });
        sel.trigger('change');
      }
    }

    // Show model metadata and resize canvas
    $('#modelSel').on('change', function() {
      var model = $(this).val();
      if (!model) return;
      // Try REST metadata endpoint
      $.getJSON('/api/overlays/models/' + encodeURIComponent(model))
        .done(function(info) {
          $('#modelWidth').text(info.pixelCountX + ' px');
          $('#modelHeight').text(info.pixelCountY + ' px');
          $('#previewCanvas')
            .attr('width', info.pixelCountX)
            .attr('height', info.pixelCountY);
        })
        .fail(function() {
          // Fallback: static config
          $.getJSON('/media/config/model-overlays.json')
            .done(function(cfg) {
              var m = cfg.models.find(function(x){ return x.Name === model; });
              if (m) {
                $('#modelWidth').text(m.pixelCountX + ' px');
                $('#modelHeight').text(m.pixelCountY + ' px');
                $('#previewCanvas')
                  .attr('width', m.pixelCountX)
                  .attr('height', m.pixelCountY);
              }
            });
        });
    });

    // Activate/deactivate overlay model
    $('#activateBtn').on('click', function() {
      var model = $('#modelSel').val();
      if (model) {
        $.post('/api/overlays/models/' + encodeURIComponent(model) + '/activate');
      }
    });
    $('#deactivateBtn').on('click', function() {
      $.post('/api/overlays/models/deactivate');
    });

    // Load saved manual settings
    function loadSettings() {
      $.getJSON('/plugin/machine/settings', function(data) {
        ['line1','line2','line3','line4'].forEach(function(id) {
          $('#' + id).val(data[id] || '');
        });
        $('#color').val(data.color || '#FFFFFF');
      });
    }

    // Draw manual data onto canvas and send preview overlay
    function saveAndPreview() {
      var canvas = document.getElementById('previewCanvas');
      var ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = $('#color').val();
      ctx.font = '12px sans-serif';
      var y = 14;
      $.each(['line1','line2','line3','line4'], function(i, id) {
        ctx.fillText($('#' + id).val() || '', 0, y);
        y += 14;
      });
      var dataURL = canvas.toDataURL().split(',')[1];
      var model = $('#modelSel').val();
      $.ajax({
        url: '/plugin/machine/overlay?preview=1',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ model: model, data: dataURL })
      });
    }

    // Initialize page
    $(function() {
      loadModels();
      loadSettings();
      $('#saveBtn').on('click', saveAndPreview);
    });
  </script>
</body>
</html>
