@extends('layouts.website.app')
@section('title', 'Contact')
@section('content')
@include('website._page-hero', ['kicker' => 'Host', 'title' => 'Contact', 'subtitle' => 'A note is enough. We will reply personally.'])

<section class="ch-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h2 class="ch-section-title mb-3">Say hello</h2>
                <p class="ch-prose mb-4">Whether you have a question about the house, need to change a booking, or just want to know what the area is like — we are happy to help.</p>
                <div class="ch-contact-detail">
                    <div class="ch-contact-icon"><i class="bi bi-envelope"></i></div>
                    <div>
                        <div class="ch-contact-label">Email</div>
                        <div>{{ $property?->email ?? config('mail.from.address', 'hello@cornerhouse.test') }}</div>
                    </div>
                </div>
                @if ($property?->phone)
                    <div class="ch-contact-detail mt-3">
                        <div class="ch-contact-icon"><i class="bi bi-telephone"></i></div>
                        <div>
                            <div class="ch-contact-label">Phone</div>
                            <div>{{ $property->phone }}</div>
                        </div>
                    </div>
                @endif
                @if ($property)
                    <div class="ch-contact-detail mt-3">
                        <div class="ch-contact-icon"><i class="bi bi-geo-alt"></i></div>
                        <div>
                            <div class="ch-contact-label">Address</div>
                            <div>{{ $property->address_line_1 }}{{ $property->address_line_2 ? ', '.$property->address_line_2 : '' }}, {{ $property->city }}</div>
                        </div>
                    </div>
                @endif
            </div>
            <div class="col-lg-7">
                <form method="POST" action="{{ route('contact.submit') }}" class="ch-form-card">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required>{{ old('message') }}</textarea>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-ch-book">Send message</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
