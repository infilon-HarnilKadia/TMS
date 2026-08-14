<?php

it('renders the panel shell with the Transport ERP branding and sidebar user block', function () {
    $this->get('/users')
        ->assertOk()
        // Custom brand logo view (resources/views/filament/admin/logo.blade.php)
        ->assertSee('Transport ERP')
        ->assertSee('Fleet Suite')
        // SIDEBAR_FOOTER render hook — shows the acting user's name and role
        ->assertSee('Super Admin');
});
