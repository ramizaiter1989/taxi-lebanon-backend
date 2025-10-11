<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    protected $proxies = '*'; // Trust all proxies (for development only)
    // OR specify ngrok's IP range if you know it
    // protected $proxies = '192.0.0.1/32'; // Example
}
