<?php
namespace Core;

interface Middleware { public function handle(Request $req, callable $next): Response; }
