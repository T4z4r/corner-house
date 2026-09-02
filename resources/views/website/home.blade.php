@extends('layouts.website.app')

@section('title', 'Corner House · Stay with us in Braunston')

@section('content')
    @include('website.partials.home')
    @include('website.partials.about')
    @include('website.partials.rooms')
    @include('website.partials.places')
    @include('website.partials.book')
    @include('website.partials.spirits')
    @include('website.partials.foundation')
    @include('website.partials.terms')
    @include('website.partials.refunds')
@endsection
