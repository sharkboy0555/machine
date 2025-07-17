<?php
// machine.php — core plugin for “machine”

class MachinePlugin extends FPPPlugin {
    public function __construct() {
        parent::__construct();

        // plugin “machine”
        $this->name = 'machine';

        // hook to serve settings.json
        $this->addHook('settings');

        // hook to render overlay
        $this->addHook('overlay');

        // status‐menu → machine_setup.php
        $this->addMenuEntry('Machine Config','status','machine_setup.php',1);
    }

    /**
     * settings() will auto‐read/write settings.json in plugin root.
     */
    public function settings() {
        return $this->settingsPage();
    }

    /**
     * overlay() is invoked on:
     *  - GET /plugin/machine/overlay?preview=1&model=Foo
     *  - (and whenever FPP reloads your persistent overlay)
     */
    public function overlay($args) {
        // load your saved lines & color
        $settings = $this->getSettings();

        // model name passed from the UI
        $model = $args['model'] ?? '';

        // clear whatever overlay is active (if model provided, clear that one)
        if ($model) {
            $this->clearOverlay($model);
        } else {
            $this->clearOverlay();
        }

        // draw each line in sequence
        $y = 0;
        foreach (['line1','line2','line3','line4'] as $field) {
            $text  = $settings[$field] ?? '';
            $color = $settings['color'] ?? '#FFFFFF';

            // drawText($text, $font, $x, $y, $color)
            // Note: clearOverlay($model) directed subsequent drawText into that model
            $this->drawText($text, 'fixed', 0, $y, $color);

            // move down 14 pixels (matches your JS preview font)
            $y += 14;
        }
    }
}

// instantiate
new MachinePlugin();
?>
