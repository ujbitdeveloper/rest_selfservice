<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('api', static function ($routes) {
    $routes->post('token', 'Auth::token');      // login / enroll -> access + refresh token
    $routes->post('refresh', 'Auth::refresh');  // tukar refresh token -> token baru
    $routes->post('data', 'Data::create');      // post data (butuh Bearer access token)
});

// Dashboard admin (session login)
$routes->get('admin', 'Admin::index');
$routes->post('admin/login', 'Admin::login');
$routes->post('admin/logout', 'Admin::logout');
$routes->get('admin/devices', 'Admin::devices');
$routes->post('admin/devices/toggle', 'Admin::toggle');
