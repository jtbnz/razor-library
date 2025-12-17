<?php
/**
 * Dashboard Controller
 */

class DashboardController
{
    /**
     * Show dashboard - redirect to razors
     */
    public function index(): void
    {
        redirect('/razors');
    }
}
