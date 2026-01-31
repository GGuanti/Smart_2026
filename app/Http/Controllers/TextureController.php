<?php

namespace App\Http\Controllers;

use App\Models\Texture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TextureController extends Controller
{
    public function index()
    {
        return response()->json(
            Texture::where('user_id', Auth::id())->latest()->get()
        );
    }

    public function store(Request $request)
    {
        abort_if(!Auth::check(), 401);

        $request->validate([
            'file' => ['required','image','max:5120'],
            'type' => ['required','in:anta,telaio,generic'],
            'name' => ['nullable','string','max:120'],
        ]);

        $file = $request->file('file');
        $path = $file->store('textures', 'public');

        $w = null; $h = null;
        try {
            $info = @getimagesize($file->getRealPath());
            if ($info) { $w = $info[0]; $h = $info[1]; }
        } catch (\Throwable $e) {}

        $tex = Texture::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name') ?: $file->getClientOriginalName(),
            'type' => $request->input('type'),
            'path' => $path,
            'width' => $w,
            'height' => $h,
        ]);

        return response()->json($tex);
    }

    public function destroy(Texture $texture)
    {
        abort_unless($texture->user_id === Auth::id(), 403);

        Storage::disk('public')->delete($texture->path);
        $texture->delete();

        return response()->json(['ok' => true]);
    }
}
