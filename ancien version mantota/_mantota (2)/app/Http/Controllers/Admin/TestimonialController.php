<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Testimonial;
use Illuminate\Http\Request;

class TestimonialController extends Controller
{
    public function index()
    {
        return inertia('Testimonials/Index', [
            'testimonials' => Testimonial::orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'role'       => 'nullable|string|max:100',
            'content'    => 'required|string|max:500',
            'rating'     => 'required|integer|min:1|max:5',
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        Testimonial::create($data);

        return back()->with('success', 'Temoignage ajoute.');
    }

    public function update(Request $request, Testimonial $testimonial)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'role'       => 'nullable|string|max:100',
            'content'    => 'required|string|max:500',
            'rating'     => 'required|integer|min:1|max:5',
            'is_active'  => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $testimonial->update($data);

        return back()->with('success', 'Temoignage mis a jour.');
    }

    public function destroy(Testimonial $testimonial)
    {
        $testimonial->delete();

        return back()->with('success', 'Temoignage supprime.');
    }
}
