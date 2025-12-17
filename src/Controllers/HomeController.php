<?php
/**
 * Home Controller - Landing page
 */

class HomeController
{
    /**
     * Show landing page or redirect to dashboard
     */
    public function index(): string
    {
        // If logged in, redirect to dashboard
        if (is_authenticated()) {
            redirect('/dashboard');
        }

        return view('home/index');
    }
}
