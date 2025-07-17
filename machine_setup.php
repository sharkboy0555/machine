<?php
// machine_setup.php — UI page for Machine Config (wrapped by FPP header/footer)

// Read and decode your overlay models JSON as a fallback
$configPath   = '/home/fpp/media/config/model-overlays.json';
$modelFile    = @file_get_contents($configPath);
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
    /* scale preview up for readability */
    #previewCanvas {
      border: 1px solid #888;
      margin-top: 12px;
      display: block;
      width: 320px;    /* 64px × 5 */
      height: 160px;   /* 32px × 5 */
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
    // server-side fallback models
    var staticModels = <?php echo json_encode($staticModels, JSON_HEX_TAG); ?>;
    var modelsCfg    = [];

    // populate dropdown with names
    function populateDropdown(names) {
      var sel = $('#modelSel').empty();
      if (!names.length) {
        sel.append($('<option disabled>').text('No models defined'));
      } else {
        names.forEach(n => sel.append($('<option>').val(n).text(n)));
      }
      sel.trigger('change');
    }

    // load models: try API, then fallback
    function loadModels() {
      $.getJSON('/api/overlays/models')
        .done(function(list){
          modelsCfg = list.map(name => ({ Name: name }));
          populateDropdown(list);
        })
        .fail(function(){
          modelsCfg = staticModels;
          populateDropdown(staticModels.map(m => m.Name));
        });
    }

    // update dimensions & canvas size
    function updateModelInfo(name) {
      // try API metadata
      $.getJSON('/api/overlays/model/' + encodeURIComponent(name))
        .done(function(info){
          var width  = info.StringCount * info.StrandsPerString;
          var height = (info.ChannelCount / info.ChannelCountPerNode) / width;
          $('#modelWidth').text(width + ' px');
          $('#modelHeight').text(height + ' px');
          $('#previewCanvas').attr({ width: width, height: height });
        })
        .fail(function(){
          // fallback to staticModels
          var m = modelsCfg.find(x => x.Name === name);
          if (!m) return;
          var width  = m.StringCount * m.StrandsPerString;
          var height = (m.ChannelCount / m.ChannelCountPerNode) / width;
          $('#modelWidth').text(width + ' px');
          $('#modelHeight').text(height + ' px');
          $('#previewCanvas').attr({ width: width, height: height });
        });
    }

    // hook up selector change
    $('#modelSel').change(function(){
      updateModelInfo($(this).val());
    });

    // activate model by setting state = 1
    $('#activateBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return;
      $.ajax({
        url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ State: 1 })
      });
    });

    // deactivate (turn off) model state = 0
    $('#deactivateBtn').click(function(){
      var name = $('#modelSel').val();
      if (!name) return;
      $.ajax({
        url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
        type: 'PUT',
        contentType: 'application/json',
        data: JSON.stringify({ State: 0 })
      });
    });

    // Apply & Preview: draw locally, POST form data, then set state = 1
    $('#previewBtn').click(function(){
      var name   = $('#modelSel').val() || '';
      var canvas = document.getElementById('previewCanvas');
      var ctx    = canvas.getContext('2d');

      // black background
      ctx.fillStyle = '#000';
      ctx.fillRect(0, 0, canvas.width, canvas.height);

      // draw text lines
      ctx.fillStyle = $('#color').val() || '#FFFFFF';
      ctx.font      = '12px sans-serif';
      var y = 14;
      ['line1','line2','line3','line4'].forEach(function(id){
        ctx.fillText($('#'+id).val()||'', 0, y);
        y += 14;
      });

      // send form-encoded to your overlay hook
      var params = {
        preview:1,
        model:  name,
        color:  $('#color').val()||'#FFFFFF',
        line1:  $('#line1').val()||'',
        line2:  $('#line2').val()||'',
        line3:  $('#line3').val()||'',
        line4:  $('#line4').val()||''
      };

      $.post('/plugin/machine/overlay', params)
        .done(function(){
          // then PUT state=1 to light the matrix
          if (name) {
            $.ajax({
              url: '/api/overlays/model/' + encodeURIComponent(name) + '/state',
              type: 'PUT',
              contentType: 'application/json',
              data: JSON.stringify({ State: 1 })
            });
          }
        })
        .fail(function(err){
          console.error('Overlay POST failed:', err);
        });
    });

    // initial load
    loadModels();
  });
  </script>
</body>
</html>
