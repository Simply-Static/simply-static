<?php
namespace Simply_Static;
// Pro-only placeholder for Command Center in the Free plugin.
// Visible in the UI, cannot run without Pro.
class SS_Command_Center_Integration extends Pro_Integration {
    /** @var string */
    protected $id = 'ss-command-center';
    public function __construct() {
        $this->name        = __( 'Command Center (Core)', 'simply-static' );
        $this->description = __( 'Replaces the sidebar admin menu with a compact admin-bar widget. Access Generate, Settings, Diagnostics and Form Submissions directly from the toolbar.', 'simply-static' );
    }
}
