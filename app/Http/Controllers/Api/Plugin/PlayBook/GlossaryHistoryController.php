<?php

namespace App\Http\Controllers\Api\Plugin\PlayBook;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Plugin\PlayBook\Glossary;

class GlossaryHistoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, Glossary $glossary)
    {
        $query = $glossary->histories()->latest();
        $histories = pagination($query, $request->limit ?: 10);

        return response()->json(compact('glossary', 'histories'));
    }
}
