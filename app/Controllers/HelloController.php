<?php
namespace App\Controllers;
use Core\{Request, Response, View};

final class HelloController
{
    public function greet(Request $r): Response
    {
        // Demo: név az útvonal végéről (amíg a Routert nem bővítjük paramokkal)
        $name = trim(basename($r->uri()));
        $html = View::render('hello', ['title' => 'Üdvözlet', 'name' => $name ?: 'Világ']);
        return (new Response())->html($html);
    }
}
