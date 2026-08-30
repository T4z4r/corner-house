<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Amenity;
use App\Models\FoodAndDrink;
use App\Models\KnowledgeBaseArticle;
use App\Models\PlacesOfInterest;
use App\Models\Property;
use App\Services\System\MailConfigurationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function home(): View
    {
        return view('website.home', $this->propertyData());
    }

    public function about(): View
    {
        return view('website.about', $this->propertyData());
    }

    public function property(): View
    {
        return view('website.property', $this->propertyData());
    }

    public function amenities(): View
    {
        return view('website.amenities', $this->propertyData());
    }

    public function gallery(): View
    {
        return view('website.gallery', $this->propertyData());
    }

    public function location(): View
    {
        return view('website.location', $this->propertyData());
    }

    public function faq(): View
    {
        $data = $this->propertyData();
        $data['articles'] = KnowledgeBaseArticle::query()
            ->where('status', 'active')
            ->where('show_on_website', true)
            ->orderByDesc('priority')
            ->orderBy('title')
            ->get()
            ->groupBy('category');

        return view('website.faq', $data);
    }

    public function foodAndDrink(): View
    {
        $data = $this->propertyData();
        $data['establishments'] = FoodAndDrink::query()->where('is_active', true)->orderBy('sort_order')->get();
        $data['addons'] = AddOn::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('website.food-drink', $data);
    }

    public function places(): View
    {
        $data = $this->propertyData();
        $data['places'] = PlacesOfInterest::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('website.places', $data);
    }

    public function contact(): View
    {
        return view('website.contact', $this->propertyData());
    }

    public function submitContact(Request $request, MailConfigurationService $mailConfigurationService): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $mailConfigurationService->apply();

        Mail::raw(
            "From: {$data['name']} <{$data['email']}>\n\n{$data['message']}",
            function ($message) use ($data): void {
                $message->to(config('mail.from.address'))
                    ->subject('Website enquiry from '.$data['name']);
            },
        );

        return back()->with('status', 'Thank you. We will get back to you shortly.');
    }

    public function privacy(): View
    {
        return view('website.legal', ['title' => 'Privacy Policy', 'heading' => 'Privacy Policy'] + $this->propertyData());
    }

    public function terms(): View
    {
        return view('website.legal', ['title' => 'Terms', 'heading' => 'Terms of Stay'] + $this->propertyData());
    }

    public function cancellation(): View
    {
        return view('website.legal', ['title' => 'Cancellation Policy', 'heading' => 'Cancellation Policy'] + $this->propertyData());
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyData(): array
    {
        $property = Property::query()->where('status', 'active')->with(['amenities', 'policies', 'rooms.images'])->first();

        return [
            'property' => $property,
            'rooms' => $property?->rooms ?? collect(),
            'amenities' => $property?->amenities ?? Amenity::query()->orderBy('name')->get(),
        ];
    }
}
