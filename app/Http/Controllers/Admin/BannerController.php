<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audiobook;
use App\Models\Banner;
use App\Services\S3Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        $banners = Banner::orderBy('order')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function create(): View
    {
        $audiobooks = Audiobook::where('status', 'approved')
            ->select('id', 'title')
            ->orderBy('title')
            ->get();
        return view('admin.banners.create', compact('audiobooks'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title'      => ['required', 'string', 'max:200'],
            'link_type'  => ['required', 'in:book,external,static'],
            'link_value' => ['nullable', 'string', 'max:500'],
            'is_active'  => ['boolean'],
            'order'      => ['nullable', 'integer', 'min:0'],
            'image'      => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = app(S3Service::class)->upload($request->file('image'), 'banners');
        }
        unset($data['image']);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['order']     = (int) ($data['order'] ?? Banner::max('order') + 1);

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function toggleActive(Banner $banner): RedirectResponse
    {
        $banner->update(['is_active' => ! $banner->is_active]);
        return back()->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        if ($banner->image_path) {
            app(S3Service::class)->delete($banner->image_path);
        }
        $banner->delete();
        return redirect()->route('admin.banners.index')->with('success', 'Banner deleted.');
    }
}
