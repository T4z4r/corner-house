@extends('layouts.website.app')

@section('title', 'Corner House · Stay with us in Braunston')

@section('description', 'Book a direct stay at Corner House, a 175-year-old ivy-clad country house in Braunston. Five ensuite bedrooms, hot tub, cinema room and gym, sleeping up to 12 adults.')

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
