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
    // Model definitions fallback store
    var modelsCfg = [];

    // Load models: first try REST, then fallback to static JSON
    // at the top of your <script> block, replace your entire loadModels() with:

function loadModels() {
  $.getJSON('/media/config/model-overlays.json')
    .done(function(data) {
      // stash the raw model info
      modelsCfg = data.models;

      // populate the dropdown
      var sel = $('#modelSel').empty();
      if (!data.models.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        data.models.forEach(function(m) {
          sel.append($('<option>').val(m.Name).text(m.Name));
        });
      }

      // fire change so width/height + canvas size update
      sel.trigger('change');
    })
    .fail(function() {
      $('#modelSel').empty()
        .append($('<option disabled>').text('Error loading models'));
    });
}


    // Populate <select> with model names
    function populateDropdown(names) {
      var sel = $('#modelSel').empty();
      if (!names.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        names.forEach(function(name) {
          sel.append($('<option>').val(name).text(name));
        });
      }
      sel.trigger('change');
    }

    // Update width/height and canvas size when model changes
    function updateModelInfo(name) {
      var model = modelsCfg.find(m => m.Name === name);
      if (!model) return;
      var width  = model.StringCount * model.StrandsPerString;
      var pixels = model.ChannelCount / model.ChannelCountPerNode;
      var height = pixels / width;
      $('#modelWidth').text(width + ' px');
      $('#modelHeight').text(height + ' px');
      $('#previewCanvas').attr({ width: width, height: height });
    }

    // Activate or deactivate the selected overlay model
    $('#activateBtn').on('click', function() {
      var name = $('#modelSel').val();
      if (name) {
        $.post('/rest/overlay/models/' + encodeURIComponent(name) + '/activate');
      }
    });
    $('#deactivateBtn').on('click', function() {
      $.post('/rest/overlay/models/deactivate');
    });

    // Draw manual preview on canvas and fire overlay hook
    $('#previewBtn').on('click', function() {
      var name   = $('#modelSel').val();
      var canvas = document.getElementById('previewCanvas');
      var ctx    = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.fillStyle = $('#color').val();
      ctx.font      = '12px sans-serif';
      var y = 14;
      ['line1','line2','line3','line4'].forEach(function(id) {
        ctx.fillText($('#' + id).val() || '', 0, y);
        y += 14;
      });
      $.get('/plugin/machine/overlay?preview=1&model=' +
        encodeURIComponent(name)
      );
    });

    // Re-bind model change after populate
    $('#modelSel').on('change', function() {
      updateModelInfo($(this).val());
    });

    // Initial load
    loadModels();
  });
  </script>
</body>
</html>
