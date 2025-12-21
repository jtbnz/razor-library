<?php
/**
 * Legal Controller
 * Handles legal pages like Terms and Conditions
 */

class LegalController
{
    /**
     * Show Terms and Conditions page
     */
    public function terms(): string
    {
        return view('legal/terms');
    }
}
