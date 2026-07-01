<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class HelloController extends Controller
{
    /**
     * Hello Worldページを表示する
     */
    public function hello(): View
    {
        return view('hello');
    }
}
