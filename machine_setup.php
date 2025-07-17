<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)

// Read and decode your overlay models JSON as a fallback
$configPath = '/home/fpp/media/config/model-overlays.json';
$modelFile  = @file_get_contents($configPath);
$staticModels = $modelFile
    ? (json_decode($modelFile, true)['models'] ?? [])
    : [];
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
    /* scale preview for visibility */
    #previewCanvas {
      border: 1px solid #888;
      margin-top: 12px;
      display: block;
      width: 320px;       /* example: 64px * 5 */
      height: 160px;      /* example: 32px * 5 */
      image-rendering: pixelated;
    }
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
  $(function(){
    // Fallback models from PHP
    var staticModels = <?php echo json_encode($staticModels, JSON_HEX_TAG); ?>;
    var modelsCfg = [];

    // Populate dropdown
    function populateDropdown(names) {
      var sel = $('#modelSel').empty();
      if (!names.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        names.forEach(function(n){
          sel.append($('<option>').val(n).text(n));
        });
      }
      sel.trigger('change');
    }

    // Load models from API, fallback to static JSON
    function loadModels() {
      $.getJSON('/api/models')
        .done(function(apiArray){
          modelsCfg = apiArray;
          populateDropdown(apiArray.map(m => m.Name));
        })
        .fail(function(){
          modelsCfg = staticModels;
          populateDropdown(staticModels.map(m => m.Name));
        });
    }

    // Update width/height and canvas resolution
    function updateModelInfo(name) {
      var m = modelsCfg.find(x => x.Name === name);
      if (!m) return;
      var width   = m.StringCount * m.StrandsPerString;
      var pixels  = m.ChannelCount / m.ChannelCountPerNode;
      var height  = pixels / width;
      $('#modelWidth').text(width + ' px');
      $('#modelHeight').text(height + ' px');
      $('#previewCanvas').attr({ width: width, height: height });
    }

    // Activate / Deactivate overlay model
    $('#activateBtn').click(function(){
      var name = $('#modelSel').val();
      if (name) {
        $.post('/api/models/' + encodeURIComponent(name) + '/activate');
      }
    });
    $('#deactivateBtn').click(function(){
      $.post('/api/models/deactivate');
    });

    // Apply & Preview: draw on canvas, POST to overlay hook, then activate
    $('#previewBtn').click(function(){
      var name = $('#modelSel').val() || '';
      var canvas = document.getElementById('previewCanvas');
      var ctx = canvas.getContext('2d');

      // black background
      ctx.fillStyle = '#000';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // draw text lines
      ctx.fillStyle = $('#color').val() || '#FFFFFF';
      ctx.font = '12px sans-serif';
      var y = 14;
      ['line1','line2','line3','line4'].forEach(function(id){
        ctx.fillText($('#' + id).val() || '', 0, y);
        y += 14;
      });

      // build params
      var params = { preview:1, model:name, color:$('#color').val() || '#FFFFFF' };
      ['line1','line2','line3','line4'].forEach(function(id){
        params[id] = $('#' + id).val() || '';
      });

      // POST to overlay hook, then activate
      $.ajax({
        url: '/plugin/machine/overlay',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(params)
      }).done(function(){
        if (name) {
          $.post('/api/models/' + encodeURIComponent(name) + '/activate');
        }
      }).fail(function(err){
        console.error('Overlay POST failed:', err);
      });
    });

    // On change, update the model info
    $('#modelSel').change(function(){
      updateModelInfo($(this).val());
    });

    // Initial load
    loadModels();
  });
  </script>
</body>
</html>
