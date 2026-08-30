@extends('layouts.website.app')
@section('title', 'Gallery')
@section('content')
@include('website._page-hero', ['kicker' => 'Look inside', 'title' => 'Gallery', 'subtitle' => 'A preview of the house, its rooms, and the details that matter.'])

<section class="ch-section">
    <div class="container">
        @php $images = \App\Models\GalleryImage::active()->ordered()->get()->chunk(2); @endphp
        @forelse ($images as $chunk)
            <div class="row g-3 mb-3">
                @foreach ($chunk as $image)
                    <div class="col-md-6">
                        <div class="ch-gallery-frame">
                            <img src="{{ asset('storage/'.$image->path) }}" alt="{{ $image->alt }}" class="ch-gallery-img">
                            @if ($image->caption)
                                <div class="ch-gallery-caption">{{ $image->caption }}</div>
                            @elseif ($image->alt)
                                <div class="ch-gallery-caption">{{ $image->alt }}</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @empty
            <div class="text-center py-5">
                <p class="text-muted">Photos will appear once images are uploaded in gallery settings.</p>
            </div>
        @endforelse
    </div>
</section>

<section class="ch-editorial-cta">
    <div class="container">
        <div class="ch-editorial-panel">
            <p class="ch-kicker">See for yourself</p>
            <h2>Ready to experience Corner House?</h2>
            <a class="btn btn-ch-book" href="{{ route('booking.search') }}">Book your stay</a>
        </div>
    </div>
</section>
@endsection
