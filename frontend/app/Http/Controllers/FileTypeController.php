<?php

namespace App\Http\Controllers;

use App\Models\FileType;
use Illuminate\Http\Request; // <-- FIXED import

class FileTypeController extends Controller
{
    //
    public function index(){
        $filetypes = FileType::all();
        return view('filetypes.index', compact('filetypes'));
    }

    public function show($id)
    {
        $filetype = FileType::findOrFail($id);
        return view('filetypes.partials.show', compact('filetype'));
    }

    // Show add modal
    public function create()
    {
        return view('filetypes.partials.add'); // Blade partial for Add modal
    }



    // Store new file type
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique|string|max:255',
            'status' => 'required|in:0,1',
        ]);

        FileType::create([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        // Redirect back to filetypes list with a success message
        return redirect()->route('filetypes.index')->with('success', 'File Type added successfully.');
    }
    public function edit($id)
    {
        $filetype = FileType::findOrFail($id);
        return view('filetypes.partials.edit', compact('filetype'));
    }

    public function destroy($id)
    {
        $filetype = FileType::findOrFail($id);
        $filetype->delete();

         // Redirect back to the filetypes list with a success message
        return redirect()->route('filetypes.index')->with('success', 'File Type updated successfully.');
    }






    public function update(Request $request, $id)
    {
        $filetype = FileType::findOrFail($id);
        $filetype->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);

        // Redirect back to the filetypes list with a success message
        return redirect()->route('filetypes.index')->with('success', 'File Type updated successfully.');
    }
}
