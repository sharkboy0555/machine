<?php
class MachinePlugin extends FPPPlugin {
    public function __construct() {
        parent::__construct();
        $this->name = 'machine';
        // no more settings hook
        $this->addHook('overlay');
        $this->addMenuEntry('Machine Config','status','machine_setup.php',1);
    }

    public function overlay($args) {
        // take line1–4 and color out of $args, with fallbacks
        $fields = ['line1','line2','line3','line4'];
        $color  = $args['color'] ?? '#FFFFFF';
        $model  = $args['model'] ?? '';

        // clear that overlay (or all if none given)
        if ($model) {
            $this->clearOverlay($model);
        } else {
            $this->clearOverlay();
        }

        // draw each line
        $y = 0;
        foreach ($fields as $f) {
            $text = $args[$f] ?? '';
            $this->drawText($text, 'fixed', 0, $y, $color);
            $y += 14;
        }
    }
}
new MachinePlugin();
?>
