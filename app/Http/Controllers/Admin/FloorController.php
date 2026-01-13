<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Floor;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FloorController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Floors/Index', [
            'floors' => Floor::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer',
        ]);

        Floor::create($request->all());
        return redirect()->back()->with('success', 'Lantai berhasil ditambahkan.');
    }

    public function update(Request $request, Floor $floor)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'level' => 'required|integer',
        ]);

        $floor->update($request->all());
        return redirect()->back()->with('success', 'Lantai berhasil diperbarui.');
    }

    public function destroy(Floor $floor)
    {
        $floor->delete();
        return redirect()->back()->with('success', 'Lantai berhasil dihapus.');
    }
}
