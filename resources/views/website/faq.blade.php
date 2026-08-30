@extends('layouts.website.app')
@section('title', 'FAQ')
@section('content')
@include('website._page-hero', ['kicker' => 'Before you arrive', 'title' => 'Frequently asked questions'])
<div class="container ch-section">
    @forelse ($articles as $category => $items)
        <h2 class="ch-section-title mt-4">{{ ucfirst($category) }}</h2>
        <div class="accordion ch-accordion" id="faq{{ \Illuminate\Support\Str::slug($category) }}">
            @foreach ($items as $article)
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $article->id }}">
                            {{ $article->title }}
                        </button>
                    </h2>
                    <div id="faq{{ $article->id }}" class="accordion-collapse collapse">
                        <div class="accordion-body">{{ $article->content }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @empty
        <p class="text-muted">FAQs will appear here once the knowledge base is populated.</p>
    @endforelse
</div>
@endsection
