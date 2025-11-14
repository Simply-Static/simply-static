<?php
namespace Simply_Static;

class The_Events_Calendar_Integration extends Pro_Integration {
    /**
     * A string ID of integration.
     *
     * @var string
     */
    protected $id = 'the-events-calendar';

    public function __construct() {
        $this->name        = __( 'The Events Calendar', 'simply-static' );
        $this->description = __( 'Support for events, archives, and assets from The Events Calendar. Requires Simply Static Pro.', 'simply-static' );
    }

    /**
     * Return if the dependency is active.
     * The Events Calendar defines the main class Tribe__Events__Main when active.
     *
     * @return bool
     */
    public function dependency_active() {
        return class_exists( 'Tribe__Events__Main' );
    }
}
