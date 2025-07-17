<?php
// … your existing PHP above …
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- … your existing <head> … -->
  <style>
    /* keep your existing styles */
    .line-input {
      display: inline-block; width: 200px;
    }
    .size-input {
      width: 60px; margin-left: 8px;
    }
  </style>
</head>
<body>
  <!-- … your existing UI above … -->

  <h2>Manual Data Preview</h2>
  <div class="field">
    <label for="line1">Line 1:</label>
    <input id="line1" class="line-input" type="text">
    <input id="size1"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line2">Line 2:</label>
    <input id="line2" class="line-input" type="text">
    <input id="size2"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line3">Line 3:</label>
    <input id="line3" class="line-input" type="text">
    <input id="size3"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="line4">Line 4:</label>
    <input id="line4" class="line-input" type="text">
    <input id="size4"  class="size-input" type="number" min="6" max="72" value="12" title="Font Size">
  </div>
  <div class="field">
    <label for="color">Color:</label>
    <input id="color" type="color" value="#FFFFFF">
  </div>
  <button id="sendBtn">Send Text</button>

  <script>
  $(function(){
    // … your existing setup/loadModels/updateModelInfo …

    $('#sendBtn').click(function(){
      var name  = $('#modelSel').val();
      if (!name) return alert('Please select a model.');

      // gather lines
      var lines   = ['line1','line2','line3','line4'].map(id => $('#'+id).val().trim());
      var message = lines.filter(l => l).join('\n');
      if (!message) return alert('Enter at least one line of text.');

      // gather sizes (we'll use size1 as the global FontSize)
      var size1 = parseInt($('#size1').val(),10) || 12;

      var payload = {
        Message         : message,
        Position        : 'Center',
        Font            : 'FreeSans',
        FontSize        : size1,          // ← from Line 1’s size input
        AntiAlias       : false,
        PixelsPerSecond : 0,
        Color           : $('#color').val(),
        AutoEnable      : false
      };

      console.log('PUT →', '/api/overlays/model/'+name+'/text', payload);

      $.ajax({
        url         : '/api/overlays/model/' + encodeURIComponent(name) + '/text',
        type        : 'PUT',
        contentType : 'application/json',
        data        : JSON.stringify(payload),
        success     : function(){
          // enable overlay in override mode
          $.ajax({
            url         : '/api/overlays/model/' + encodeURIComponent(name) + '/state',
            type        : 'PUT',
            contentType: 'application/json',
            data: JSON.stringify({ State: 1 })
          });
        },
        error       : function(xhr){
          console.error('Text PUT error', xhr.status, xhr.responseText);
          alert('Failed to send text: ' + xhr.statusText);
        }
      });
    });

    // … rest of your JS …
  });
  </script>
</body>
</html>
